<?php
session_start();
include 'db.php';

// ========= CHANGE THESE ANYTIME =========
$ADMIN_USER = "admin";
$ADMIN_PASS = "lagos2025";   // ← Change this password

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}


// Show success message only once
if (isset($_GET['deleted'])) {
    echo "<script>alert('Room deleted successfully!');</script>";
}
// ALREADY LOGGED IN → SHOW PANEL
if (!empty($_SESSION['admin_logged_in'])) {
    show_admin_panel();
    exit;
}

// LOGIN ATTEMPT
// LOGIN ATTEMPT — FIXED VERSION
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === 'admin' && $password === 'lagos2025') {   // ← change here if you want a different password
        $_SESSION['admin_logged_in'] = true;
        show_admin_panel();
        exit;
    } else {
        $error = "Invalid credentials!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin Login • RoomMate Lagos</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
  <div class="bg-white p-10 rounded-3xl shadow-2xl w-96">
    <h1 class="text-4xl font-bold text-center mb-8 text-green-700">ADMIN LOGIN</h1>
    <?php if (!empty($error)): ?>
      <div class="bg-red-100 text-red-700 p-4 rounded mb-6 text-center font-medium"><?= $error ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-6">
      <input type="text" name="username" placeholder="Username" required autocomplete="off"
             class="w-full p-4 border-2 border-gray-300 rounded-xl text-lg focus:border-green-600 focus:outline-none">
      <input type="password" name="password" placeholder="Password" required
             class="w-full p-4 border-2 border-gray-300 rounded-xl text-lg focus:border-green-600 focus:outline-none">
      <button type="submit" name="login" 
              class="w-full bg-green-700 hover:bg-green-800 text-white py-4 rounded-xl font-bold text-xl transition">
        Login as Admin
      </button>
    </form>
  </div>
</body>
</html>

<?php
function show_admin_panel() {
    global $pdo;

    // MARK AS FOUND
    if (isset($_POST['mark_found'])) {
        $id = (int)$_POST['mark_found'];
        $pdo->prepare("UPDATE rooms SET status = 'found' WHERE id = ?")->execute([$id]);
        echo "<script>alert('Marked as Match Found!'); location.reload();</script>";
    }

    // DELETE ROOM
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete_id'];
        $photo = $pdo->query("SELECT photo FROM rooms WHERE id = $id")->fetchColumn();
        if ($photo && file_exists($photo)) unlink($photo);
        $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
       header("Location: admin.php?deleted=1");
exit;
    }

    // EXPORT CSV
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="rooms_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Title','Price','Location','Gender','WhatsApp','Posted By','Date','Status','Reports']);
        $stmt = $pdo->query("SELECT r.*, u.username FROM rooms r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.id DESC");
        foreach ($stmt as $row) {
            fputcsv($out, [
                $row['id'],
                $row['title'],
                $row['price'],
                $row['location'],
                $row['gender'],
                $row['whatsapp'],
                $row['username'] ?? 'Guest',
                $row['created_at'],
                $row['status'] ?? 'active',
                $row['reported'] ?? 0
            ]);
        }
        exit;
    }
    ?>

    <!DOCTYPE html>
    <html>
    <head>
      <title>Admin Panel • RoomMate Lagos</title>
      <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen py-10">
      <div class="container mx-auto max-w-7xl px-4">
        <div class="bg-white rounded-3xl shadow-2xl p-8">
          <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold text-red-600">ADMIN DASHBOARD</h1>
            <div class="flex gap-4">
              <a href="?export=csv" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold">Export CSV</a>
              <a href="index.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-xl font-bold">View Site</a>
              <a href="?logout=1" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-bold">Logout</a>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
              <thead>
                <tr class="bg-green-700 text-white">
                  <th class="p-4 text-left">ID</th>
                  <th class="p-4 text-left">Title</th>
                  <th class="p-4 text-left">Price</th>
                  <th class="p-4 text-left">Location</th>
                  <th class="p-4 text-left">WhatsApp</th>
                  <th class="p-4 text-left">Posted By</th>
                  <th class="p-4 text-left">Date</th>
                  <th class="p-4 text-left">Status</th>
                  <th class="p-4 text-left">Reports</th>
                  <th class="p-4 text-left">Photo</th>
                  <th class="p-4 text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $stmt = $pdo->query("SELECT r.*, u.username FROM rooms r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.id DESC");
                foreach ($stmt as $r):
                  $date = date('j M Y, g:ia', strtotime($r['created_at']));
                  $status = $r['status'] ?? 'active';
                  $reports = $r['reported'] ?? 0;
                ?>
                  <tr class="border-b hover:bg-gray-50">
                    <td class="p-4"><?= $r['id'] ?></td>
                    <td class="p-4 font-medium"><?= htmlspecialchars($r['title']) ?></td>
                    <td class="p-4">₦<?= number_format($r['price']) ?></td>
                    <td class="p-4"><?= htmlspecialchars($r['location']) ?></td>
                    <td class="p-4"><?= $r['whatsapp'] ?></td>
                    <td class="p-4"><?= $r['username'] ?? 'Guest' ?></td>
                    <td class="p-4 text-sm"><?= $date ?></td>
                    <td class="p-4">
                      <?php if ($status === 'found'): ?>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-bold">Found</span>
                      <?php else: ?>
                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm">Active</span>
                      <?php endif; ?>
                    </td>
                    <td class="p-4 text-center font-bold <?= $reports > 0 ? 'text-red-600' : '' ?>"><?= $reports ?></td>
                    <td class="p-4">
                      <img src="<?= htmlspecialchars($r['photo']) ?>" class="w-20 h-20 object-cover rounded-lg shadow">
                    </td>
                    <td class="p-4 space-y-2">
                      <?php if ($status !== 'found'): ?>
                        <form method="post" class="inline">
                          <input type="hidden" name="mark_found" value="<?= $r['id'] ?>">
                          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm w-full">
                            Mark as Found
                          </button>
                        </form>
                      <?php endif; ?>
                      <form method="post" onsubmit="return confirm('Delete forever?')" class="inline">
                        <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                        <button type="submit" name="delete" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm w-full mt-2">
                          Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
}
?>