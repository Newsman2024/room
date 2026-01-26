<?php
/**
 * RoomMate Lagos - Database & Logic Handler
 * Final Version for GitHub & Public URL
 */

// 1. SESSION & OUTPUT BUFFERING
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); 

// 2. CONFIGURATION & PATHS
$upload_dir_name = 'upload';
$upload_path = __DIR__ . '/' . $upload_dir_name;
$dbfile = __DIR__ . "/roommate.db";

// Ensure upload directory exists safely
if (!is_dir($upload_path)) {
    @mkdir($upload_path, 0755, true);
    // Security: Prevent directory browsing
    file_put_contents($upload_path . '/index.php', '<?php // Silence');
}

// 3. DATABASE CONNECTION
try {
    $pdo = new PDO("sqlite:$dbfile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    // If this fails, the folder /var/www/html isn't writable by the web server
    die("Database Error: Ensure the directory is writable. Details: " . $e->getMessage());
}

// 4. DATABASE TABLES SETUP
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

// 5. LOGIN LOGIC
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

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

// 6. REGISTRATION LOGIC
if (isset($_POST['register'])) {
    $surname = trim($_POST['surname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $code = sprintf("%06d", mt_rand(0, 999999));

    try {
        $stmt = $pdo->prepare("INSERT INTO users (surname, lastname, username, email, phone, password, verification_code) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$surname, $lastname, $username, $email, $phone, $password, $code]);
        
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        
        if (function_exists('sendVerificationEmail')) {
            @sendVerificationEmail($email, $code);
        }
        
        session_write_close();
        header("Location: verify.php");
        exit;
    } catch (PDOException $e) {
        $msg = (strpos($e->getMessage(), 'UNIQUE') !== false) ? "Username/Email already taken!" : "Registration Error";
        echo "<script>alert('$msg'); window.location.href='index.php';</script>";
        exit;
    }
}

// 7. ROOM POSTING LOGIC (The Fix for Permissions)
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
        
        if (in_array($ext, $allowed)) {
            $unique_name = "room_" . bin2hex(random_bytes(8)) . ".$ext";
            $full_destination = $upload_path . '/' . $unique_name;
            $relative_db_path = $upload_dir_name . '/' . $unique_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $full_destination)) {
                $stmt = $pdo->prepare("INSERT INTO rooms (user_id, title, description, price, location, gender, whatsapp, photo) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $title, $desc, $price, $location, $gender, $wa, $relative_db_path]);
                header("Location: index.php");
                exit;
            } else {
                // Diagnostic Output for the Permission issue
                $server_user = exec('whoami');
                die("Upload Error: Failed to save file. <br> PHP is running as: <b>$server_user</b>. <br> Please run: <code>sudo chown -R $server_user:$server_user " . __DIR__ . "</code> on your server.");
            }
        } else {
            die("Error: Invalid file type. Please use JPG or PNG.");
        }
    }
}

// 8. LOGOUT LOGIC
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 9. HELPER FUNCTIONS (REDECLARATION FIX)
if (!function_exists('sendVerificationEmail')) {
    function sendVerificationEmail($email, $code) {
        $subject = "Verify your RoomMate Lagos account";
        $message = "Your verification code is: <b>$code</b>";
        $headers = "From: no-reply@roommatelagos.com\r\nContent-Type: text/html\r\n";
        @mail($email, $subject, $message, $headers);
    }
}
?>