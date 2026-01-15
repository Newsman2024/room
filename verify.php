<?php include 'header.php'; include 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user = $pdo->prepare("SELECT * FROM users WHERE id = ?")->execute([$_SESSION['user_id']]);
$user = $pdo->query("SELECT * FROM users WHERE id = " . $_SESSION['user_id'])->fetch();

if (isset($_POST['verify'])) {
    if ($_POST['code'] === $user['verification_code']) {
        $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL WHERE id = ?")
            ->execute([$_SESSION['user_id']]);
        echo "<script>alert('Email verified! You can now post rooms'); location='index.php';</script>";
    } else {
        $error = "Wrong code!";
    }
}
?>

<div class="container mx-auto max-w-md p-6">
  <div class="bg-white rounded-3xl shadow-2xl p-10 text-center">
    <h1 class="text-4xl font-bold text-green-700 mb-6">Verify Your Email</h1>
    <p class="text-lg mb-8">We sent a 6-digit code to:<br><strong><?= htmlspecialchars($user['email']) ?></strong></p>
    
    <?php if (isset($error)): ?>
      <p class="text-red-600 font-bold mb-4"><?= $error ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-6">
      <input type="text" name="code" placeholder="Enter 6-digit code" maxlength="6" required 
             class="w-full p-5 text-2xl text-center border-2 rounded-xl tracking-widest">
      <button type="submit" name="verify" 
              class="w-full bg-green-700 hover:bg-green-800 text-white py-5 rounded-xl font-bold text-xl">
        VERIFY EMAIL
      </button>
    </form>

    <p class="mt-6 text-sm text-gray-600">
      Didn’t get code? Check spam or <a href="index.php" class="text-green-600 underline">go back</a>
    </p>
  </div>
</div>
<?php include 'footer.php'; ?>