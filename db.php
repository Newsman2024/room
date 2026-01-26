<?php
// 1. CRITICAL: Start session and buffering before ANY output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); 

// --- UPLOAD DIRECTORY SETUP ---
$upload_dir = 'upload';
if (!is_dir($upload_dir)) {
    // Attempt to create, but don't die if it fails here
    @mkdir($upload_dir, 0755, true);
}

$dbfile = "roommate.db";
try {
    $pdo = new PDO("sqlite:$dbfile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 2. Ensure Tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT,
    surname TEXT,
    lastname TEXT,
    email TEXT UNIQUE,
    phone TEXT,
    email_verified INTEGER DEFAULT 0,
    verification_code TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT,
    description TEXT,
    price INTEGER,
    location TEXT,
    gender TEXT,
    whatsapp TEXT,
    photo TEXT,
    status TEXT DEFAULT 'active',
    reported INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// --- LOGIN / REGISTER / LOGOUT LOGIC (Unchanged) ---
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        session_write_close();
        header("Location: index.php");
        exit;
    } else {
        echo "<script>alert('Invalid username or password!'); window.location.href='index.php';</script>";
        exit;
    }
}

if (isset($_POST['register'])) {
    $surname = trim($_POST['surname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $code = sprintf("%06d", mt_rand(0, 999999));
    try {
        $stmt = $pdo->prepare("INSERT INTO users (surname, lastname, username, email, phone, password, verification_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$surname, $lastname, $username, $email, $phone, $password, $code]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        @sendVerificationEmail($email, $code);
        session_write_close();
        header("Location: verify.php");
        exit;
    } catch (PDOException $e) {
        $msg = (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) ? 'Username or Email already taken!' : 'Database Error';
        echo "<script>alert('$msg'); window.location.href='index.php';</script>";
        exit;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- LIST ROOMS ---
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    $sql = "SELECT r.*, u.username FROM rooms r LEFT JOIN users u ON r.user_id = u.id WHERE 1=1";
    $params = [];
    if (!empty($_GET['loc'])) { $sql .= " AND r.location LIKE ?"; $params[] = '%' . $_GET['loc'] . '%'; }
    if (!empty($_GET['maxprice'])) { $sql .= " AND r.price <= ?"; $params[] = $_GET['maxprice']; }
    if (!empty($_GET['gender']) && $_GET['gender'] !== 'Any Gender') { $sql .= " AND r.gender = ?"; $params[] = $_GET['gender']; }

    $sql .= " ORDER BY r.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    foreach ($stmt->fetchAll() as $r) {
        $posted = new DateTime($r['created_at']);
        $now = new DateTime();
        $interval = $now->diff($posted);
        $timeago = ($interval->days == 0) ? ($interval->h > 0 ? $interval->h."h ago" : "Just now") : ($interval->days == 1 ? "Yesterday" : $interval->days." days ago");
        $status_badge = ($r['status'] === 'found') ? '<span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full">Match Found</span>' : '<span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1 rounded-full">Looking</span>';

        echo "<div class='bg-white rounded-3xl shadow-xl overflow-hidden mb-8'>
                <img src='{$r['photo']}' class='w-full h-64 object-cover' onerror=\"this.src='upload/default.jpg'\">
                <div class='p-6'>
                  <div class='flex justify-between items-start mb-2'>
                    <h3 class='font-bold text-2xl'>" . htmlspecialchars($r['title']) . "</h3>
                    $status_badge
                  </div>
                  <p class='text-3xl font-bold text-green-700 mt-2'>₦" . number_format($r['price']) . "</p>
                  <p class='text-gray-600 text-lg mt-1'>" . htmlspecialchars($r['location']) . " • {$r['gender']}</p>
                  <p class='text-sm text-gray-500 mt-2'>Posted <strong>$timeago</strong> by <strong>" . ($r['username'] ?? 'Guest') . "</strong></p>
                  <p class='mt-4 text-gray-700 leading-relaxed'>" . nl2br(htmlspecialchars($r['description'])) . "</p>
                  <a href='https://wa.me/" . preg_replace('/[^0-9]/', '', $r['whatsapp']) . "' target='_blank' class='block mt-6 bg-green-700 text-white text-center py-4 rounded-xl font-bold text-lg hover:bg-green-800 transition'>Chat on WhatsApp</a>
                  <div class='text-right mt-4'>
                    <button onclick='reportRoom({$r['id']})' class='text-red-600 text-sm font-medium underline hover:no-underline'>Report Abuse / Scam</button>
                  </div>
                </div>
              </div>";
    }
    exit;
}

// --- POST ROOM (IMPROVED ERROR HANDLING) ---
if (isset($_POST['post']) && isset($_SESSION['user_id'])) {
    $title = trim($_POST['title']);
    $desc = trim($_POST['desc']);
    $price = (int)$_POST['price'];
    $location = trim($_POST['location']);
    $gender = $_POST['gender'];
    $wa = trim($_POST['wa']);

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            die("Error: Invalid file type.");
        }

        $filename = $upload_dir . "/room_" . uniqid() . ".$ext";

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $filename)) {
            $stmt = $pdo->prepare("INSERT INTO rooms (user_id, title, description, price, location, gender, whatsapp, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $desc, $price, $location, $gender, $wa, $filename]);
            header("Location: index.php");
            exit;
        } else {
            // Check if folder is writable if move fails
            $error = is_writable($upload_dir) ? "Internal move error." : "Folder '$upload_dir' is not writable. Please set permissions to 775 or 777 via FTP/SSH.";
            die("Error: $error");
        }
    } else {
        die("Error: File upload failed with code " . ($_FILES['photo']['error'] ?? 'No file'));
    }
}

function sendVerificationEmail($email, $code) {
    $subject = "Verify your RoomMate Lagos account";
    $message = "Your verification code is: <b>$code</b>";
    $headers = "From: no-reply@roommatelagos.com\r\nContent-Type: text/html\r\n";
    @mail($email, $subject, $message, $headers);
}
?>