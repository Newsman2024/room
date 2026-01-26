<?php
/**
 * RoomMate Lagos - Database & Logic Handler
 * GitHub Ready Version
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); 

// --- 1. CONFIGURATION & PERMISSIONS ---
$upload_dir = 'upload';
$dbfile = "roommate.db";

// Silently try to create the upload folder if it doesn't exist
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

// --- 2. DATABASE CONNECTION ---
try {
    $pdo = new PDO("sqlite:$dbfile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON;"); // Enables relational integrity
} catch (PDOException $e) {
    die("Database Error: Make sure the folder is writable. Error: " . $e->getMessage());
}

// --- 3. TABLE SCHEMA ---
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

// --- 4. LOGIN LOGIC ---
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
        echo "<script>alert('Invalid login credentials'); window.location.href='index.php';</script>";
        exit;
    }
}

// --- 5. REGISTRATION LOGIC ---
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
        $msg = (strpos($e->getMessage(), 'UNIQUE') !== false) ? "Username or Email already exists." : "Registration failed.";
        echo "<script>alert('$msg'); window.location.href='index.php';</script>";
        exit;
    }
}

// --- 6. ROOM POSTING (WITH PERMISSION CHECK) ---
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
            $filename = $upload_dir . "/room_" . bin2hex(random_bytes(8)) . ".$ext";

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $filename)) {
                $stmt = $pdo->prepare("INSERT INTO rooms (user_id, title, description, price, location, gender, whatsapp, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $title, $desc, $price, $location, $gender, $wa, $filename]);
                header("Location: index.php");
                exit;
            } else {
                die("Error: Folder permission denied. Run 'chmod 775 upload' on your server.");
            }
        } else {
            die("Error: Invalid file type. Use JPG or PNG.");
        }
    }
}

// --- 7. HELPER FUNCTIONS ---
function sendVerificationEmail($email, $code) {
    $subject = "Verify RoomMate Lagos";
    $message = "Your code: $code";
    $headers = "From: no-reply@roommatelagos.com\r\n";
    @mail($email, $subject, $message, $headers);
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>