<?php
session_start();                    // ← THIS WAS MISSING
include 'header.php';
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// CORRECT WAY TO GET USER DATA
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<script>alert('User not found'); window.location='index.php';</script>";
    exit;
}
?>

<div class="container mx-auto max-w-2xl p-6 py-20">
  <div class="bg-white rounded-3xl shadow-2xl p-10">
    <h1 class="text-4xl font-bold text-center mb-10 text-green-700">My Profile</h1>

    <div class="space-y-8 text-lg">
      <div class="flex justify-between py-4 border-b border-gray-200">
        <span class="font-bold text-gray-700">Full Name</span>
        <span><?= htmlspecialchars($user['surname'] . ' ' . $user['lastname']) ?></span>
      </div>
      <div class="flex justify-between py-4 border-b border-gray-200">
        <span class="font-bold text-gray-700">Username</span>
        <span class="text-green-600">@<?= htmlspecialchars($user['username']) ?></span>
      </div>
      <div class="flex justify-between py-4 border-b border-gray-200">
        <span class="font-bold text-gray-700">Email</span>
        <div class="text-right">
          <span><?= htmlspecialchars($user['email']) ?></span><br>
          <?php if ($user['email_verified'] == 1): ?>
            <span class="text-green-600 font-bold text-sm">✓ Verified</span>
          <?php else: ?>
            <span class="text-red-600 font-bold text-sm">✗ Not Verified</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="flex justify-between py-4 border-b border-gray-200">
        <span class="font-bold text-gray-700">Phone</span>
        <span><?= htmlspecialchars($user['phone']) ?></span>
      </div>
      <div class="flex justify-between py-4 border-b border-gray-200">
        <span class="font-bold text-gray-700">Joined</span>
        <span><?= date('j M Y', strtotime($user['created_at'])) ?></span>
      </div>
    </div>

    <div class="text-center mt-12">
      <a href="index.php" class="bg-green-700 hover:bg-green-800 text-white px-10 py-4 rounded-xl font-bold text-xl transition">
        ← Back to Home
      </a>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>