<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RoomMate Lagos <?php echo isset($page_title) ? '• ' . $page_title : ''; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<!-- TOP BAR — ONLY LOGO + LOGIN/LOGOUT -->
<header class="bg-green-700 text-white shadow-lg sticky top-0 z-50">
  <div class="container mx-auto px-6 py-4 flex justify-between items-center">
    
    <!-- LOGO -->
    <a href="index.php" class="flex items-center space-x-3">
      <img src="logo.png" alt="Logo" class="h-10">
      <span class="text-2xl font-bold">RoomMate Lagos</span>
    </a>

    <!-- RIGHT SIDE: USER STATUS ONLY -->
    <div class="flex items-center space-x-4">
      <?php if(isset($_SESSION['user_id'])): ?>
        <span class="bg-white text-green-700 px-4 py-2 rounded font-bold">
          <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <?php if($_SESSION['username'] === 'admin'): ?>
          <a href="admin.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-bold text-sm">
            ADMIN
          </a>
        <?php endif; ?>
        <a href="?logout=1" class="bg-white text-red-600 hover:bg-red-50 px-5 py-2 rounded font-bold">
          LOGOUT
        </a>
      <?php else: ?>
        <button onclick="document.getElementById('loginModal').showModal()" 
                class="bg-white text-green-700 hover:bg-gray-100 px-6 py-2 rounded font-bold">
          Login
        </button>
        <button onclick="document.getElementById('signupModal').showModal()" 
                class="bg-green-600 hover:bg-green-500 text-white px-6 py-2 rounded font-bold">
          Signup
        </button>
      <?php endif; ?>
      <?php if(isset($_SESSION['user_id'])): ?>
  <a href="profile.php" class="block py-3 text-lg font-bold text-blue-300">My Profile</a>
<?php endif; ?>

    </div>
  </div>
  
</header>

<main class="min-h-screen">