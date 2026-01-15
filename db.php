<?php

$dbfile = "roommate.db";
$pdo = new PDO("sqlite:$dbfile");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create tables (unchanged)
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
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status TEXT DEFAULT 'active',
  reported INTEGER DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE,
  password TEXT,
  surname TEXT,
  lastname TEXT,
  email TEXT UNIQUE,
  phone TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");



// Add missing columns safely (run once)
try { $pdo->exec("ALTER TABLE users ADD COLUMN surname TEXT"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN lastname TEXT"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN email TEXT UNIQUE"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN phone TEXT"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verified INTEGER DEFAULT 0"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN verification_code TEXT"); } catch(Exception $e) {}


$pdo->exec("CREATE TABLE IF NOT EXISTS reports (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  room_id INTEGER,
  user_id INTEGER,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(room_id, user_id)
)");

// REPORT FUNCTION (unchanged)
if (isset($_GET['report'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "Login required to report.";
        exit;
    }
    $room_id = (int)$_GET['report'];
    $user_id = $_SESSION['user_id'];
    $check = $pdo->prepare("SELECT 1 FROM reports WHERE room_id = ? AND user_id = ?");
    $check->execute([$room_id, $user_id]);
    if ($check->fetch()) {
        echo "You already reported this room.";
        exit;
    }
    $pdo->prepare("INSERT INTO reports (room_id, user_id) VALUES (?, ?)")->execute([$room_id, $user_id]);
    $pdo->prepare("UPDATE rooms SET reported = reported + 1 WHERE id = ?")->execute([$room_id]);
    echo "Thank you! Report received. Admin will review.";
    exit;
}

// LIST ROOMS WITH WORKING FILTER
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    $sql = "SELECT r.*, u.username FROM rooms r LEFT JOIN users u ON r.user_id = u.id WHERE 1=1";
    $params = [];

    if (!empty($_GET['loc'])) {
        $sql .= " AND r.location LIKE ?";
        $params[] = '%' . $_GET['loc'] . '%';
    }
    if (!empty($_GET['maxprice'])) {           // ← FIXED: was 'price' before
        $sql .= " AND r.price <= ?";
        $params[] = $_GET['maxprice'];
    }
    if (!empty($_GET['gender']) && $_GET['gender'] !== 'Any Gender') {
        $sql .= " AND r.gender = ?";
        $params[] = $_GET['gender'];
    }

    $sql .= " ORDER BY r.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    foreach ($stmt->fetchAll() as $r) {
        $posted = new DateTime($r['created_at']);
        $now = new DateTime();
        $interval = $now->diff($posted);

        if ($interval->days == 0) {
            $timeago = $interval->h > 0 ? $interval->h . "h ago" : ($interval->i > 0 ? $interval->i . "min ago" : "Just now");
        } elseif ($interval->days == 1) {
            $timeago = "Yesterday";
        } else {
            $timeago = $interval->days . " days ago";
        }

        $status_badge = ($r['status'] === 'found')
            ? '<span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full">Match Found</span>'
            : '<span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1 rounded-full">Looking</span>';

        echo "<div class='bg-white rounded-3xl shadow-xl overflow-hidden mb-8'>
                <img src='{$r['photo']}' class='w-full h-64 object-cover' onerror=\"this.src='upload/default.jpg'\">
                <div class='p-6'>
                  <div class='flex justify-between items-start mb-2'>
                    <h3 class='font-bold text-2xl'>" . htmlspecialchars($r['title']) . "</h3>
                    $status_badge
                  </div>
                  <p class='text-3xl font-bold text-green-700 mt-2'>₦" . number_format($r['price']) . "</p>
                  <p class='text-gray-600 text-lg mt-1'>
                    " . htmlspecialchars($r['location']) . " • {$r['gender']}
                  </p>
                  <p class='text-sm text-gray-500 mt-2'>
                    Posted <strong>$timeago</strong> by <strong>" . ($r['username'] ?? 'Guest') . "</strong>
                  </p>
                  <p class='mt-4 text-gray-700 leading-relaxed'>" . nl2br(htmlspecialchars($r['description'])) . "</p>

                  <a href='https://wa.me/" . preg_replace('/[^0-9]/', '', $r['whatsapp']) . "'
                     target='_blank' class='block mt-6 bg-green-700 text-white text-center py-4 rounded-xl font-bold text-lg hover:bg-green-800 transition'>
                    Chat on WhatsApp
                  </a>

                  <div class='text-right mt-4'>
                    <button onclick='reportRoom({$r['id']})' 
                            class='text-red-600 text-sm font-medium underline hover:no-underline'>
                      Report Abuse / Scam
                    </button>
                  </div>
                </div>
              </div>";
    }

    echo "<script>
    function reportRoom(id) {
      if(!" . (isset($_SESSION['user_id']) ? 'true' : 'false') . ") {
        alert('Please login to report a post.');
        return;
      }
      if(confirm('Report this post for scam, fake details or abuse?')) {
        fetch('db.php?report='+id)
          .then(r => r.text())
          .then(msg => alert(msg + ' Thank you for keeping RoomMate Lagos safe'));
      }
    }
    </script>";
    exit;
}

// === REGISTER, LOGIN, POST (unchanged below) ===
if (isset($_POST['register'])) {
    $surname = trim($_POST['surname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $code = sprintf("%06d", mt_rand(0, 999999)); // 6-digit code

    $stmt = $pdo->prepare("INSERT INTO users (surname, lastname, username, email, phone, password, verification_code) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$surname, $lastname, $username, $email, $phone, $password, $code]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        sendVerificationEmail($email, $code);
        header("Location: verify.php");
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Username or Email already taken!');</script>";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (isset($_POST['post']) && isset($_SESSION['user_id'])) {
    $title = trim($_POST['title']);
    $desc = trim($_POST['desc']);
    $price = (int)$_POST['price'];
    $location = trim($_POST['location']);
    $gender = $_POST['gender'];
    $wa = trim($_POST['wa']);

    if (empty($title) || $price <= 0 || empty($location)) {
        echo "<script>alert('Please fill all required fields');</script>";
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        echo "<script>alert('Only JPG, PNG, GIF, WebP allowed');</script>";
        exit;
    }

    $filename = "upload/room_" . uniqid() . ".$ext";
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $filename)) {
        $stmt = $pdo->prepare("INSERT INTO rooms (user_id, title, description, price, location, gender, whatsapp, photo) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $desc, $price, $location, $gender, $wa, $filename]);
        echo "<script>alert('Room posted successfully!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Photo upload failed');</script>";
    }
    exit;
}



function sendVerificationEmail($email, $code) {
    $subject = "Verify your RoomMate Lagos account";
    $message = "Your verification code is: <b>$code</b><br><br>Valid for 10 minutes.<br><br>— RoomMate Lagos Team";
    $headers = "From: no-reply@roommatelagos.atwebpages.com\r\n";
    $headers .= "Content-Type: text/html\r\n";
    mail($email, $subject, $message, $headers);
}
?>