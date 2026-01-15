<?php 
session_start();  // ← This line was missing before — now added
include 'db.php';
include 'header.php'; 
 
?>

<header class="bg-green-700 text-white p-8 text-center">
  <h1 class="text-4xl font-bold">RoomMate Lagos</h1>
  <p class="text-xl mt-3">Find roommates • Share rent • Lagos only</p>
</header>

<div class="container mx-auto p-5 max-w-6xl">
  <div class="bg-white rounded-2xl shadow p-6 mb-8 grid grid-cols-2 md:grid-cols-4 gap-4">
    <input type="text" id="loc" placeholder="Area" class="p-3 border rounded-lg">
    <input type="number" id="maxprice" placeholder="Max ₦" class="p-3 border rounded-lg">
    <select id="gender" class="p-3 border rounded-lg">
      <option value="">Any Gender</option>
      <option>Male Only</option><option>Female Only</option><option>Both OK</option>
    </select>
    <button onclick="filter()" class="bg-green-700 text-white font-bold rounded-lg">Search</button>
  </div>

  <!-- FIXED BUTTON — ONLY SHOW + WHEN LOGGED IN -->
  <?php if (!empty($_SESSION['user_id'])): ?>
    <button onclick="document.getElementById('modal').showModal()" 
            class="fixed bottom-8 right-8 bg-green-700 hover:bg-green-800 text-white w-16 h-16 rounded-full text-5xl font-bold shadow-2xl z-50 transition hover:scale-110 flex items-center justify-center">
      +
    </button>
  <?php endif; ?>
  <!-- No lock button for guests → clean & simple -->

  <div id="grid" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3"></div>
  <p id="empty" class="text-center py-20 text-gray-500 text-xl">No rooms yet — be the first!</p>
</div>

<!-- POST MODAL -->
<dialog id="modal" class="p-8 rounded-3xl max-w-lg w-full backdrop:bg-black/70">
  <h2 class="text-3xl font-bold text-center mb-8">Post a Room</h2>
  <form method="post" enctype="multipart/form-data" class="space-y-6">
    <input type="text" name="title" placeholder="Title" required class="w-full p-4 border rounded-xl">
    <textarea name="desc" placeholder="Description..." required class="w-full p-4 border rounded-xl h-32"></textarea>
    <input type="number" name="price" placeholder="Price ₦" required class="w-full p-4 border rounded-xl">
    <div class="grid grid-cols-2 gap-4">
      <input type="text" name="location" placeholder="Location" required class="p-4 border rounded-xl">
      <select name="gender" required class="p-4 border rounded-xl">
        <option value="">Gender</option>
        <option>Male Only</option><option>Female Only</option><option>Both OK</option>
      </select>
    </div>
    <input type="tel" name="wa" placeholder="WhatsApp (+234...)" required class="w-full p-4 border rounded-xl">

    <div class="border-2 border-dashed rounded-2xl p-10 text-center">
      <input type="file" name="photo" accept="image/*" required class="hidden" id="photo">
      <label for="photo" class="cursor-pointer text-green-700 font-bold text-xl block">Click to Add Photo</label>
      <div id="preview" class="mt-5"></div>
    </div>

    <button type="submit" name="post" class="w-full bg-green-700 text-white py-4 rounded-xl font-bold text-xl">
      Post Room
    </button>
  </form>
</dialog>

<!-- LOGIN & SIGNUP MODALS (unchanged) -->
<dialog id="loginModal" class="p-8 rounded-3xl max-w-lg w-full backdrop:bg-black/70">
  <h2 class="text-3xl font-bold text-center mb-8">Login</h2>
  <form method="post" class="space-y-6">
    <input type="text" name="username" placeholder="Username" required class="w-full p-4 border rounded-xl">
    <input type="password" name="password" placeholder="Password" required class="w-full p-4 border rounded-xl">
    <button type="submit" name="login" class="w-full bg-blue-700 text-white py-4 rounded-xl font-bold text-xl">
      Login
    </button>
  </form>
  <p class="text-center mt-4"><a href="#" onclick="document.getElementById('signupModal').showModal(); this.closest('dialog').close(); return false;" class="text-blue-600">Need an account? Signup</a></p>
</dialog>

<dialog id="signupModal" class="p-10 rounded-3xl max-w-lg w-full backdrop:bg-black/70 shadow-2xl">
  <h2 class="text-4xl font-bold text-center mb-8 text-green-700">Create Account</h2>
  <form method="post" class="space-y-5">
    <div class="grid md:grid-cols-2 gap-4">
      <input type="text" name="surname" placeholder="Surname" required class="w-full p-4 border-2 rounded-xl text-lg">
      <input type="text" name="lastname" placeholder="Last Name" required class="w-full p-4 border-2 rounded-xl text-lg">
    </div>
    <input type="text" name="username" placeholder="Username" required class="w-full p-4 border-2 rounded-xl text-lg">
    <input type="email" name="email" placeholder="Email Address" required class="w-full p-4 border-2 rounded-xl text-lg">
    <input type="tel" name="phone" placeholder="Phone Number (+234...)" required class="w-full p-4 border-2 rounded-xl text-lg">
    <input type="password" name="password" placeholder="Create Password" required class="w-full p-4 border-2 rounded-xl text-lg">
    
    <button type="submit" name="register" class="w-full bg-green-700 hover:bg-green-800 text-white py-5 rounded-xl font-bold text-xl shadow-lg">
      SIGN UP FREE
    </button>
  </form>
  <p class="text-center mt-6 text-lg">
    Have account? <a href="#" onclick="document.getElementById('loginModal').showModal(); this.closest('dialog').close();" class="text-green-600 font-bold underline">Login</a>
  </p>
</dialog>
<?php include 'footer.php'; ?>

<script>
  document.getElementById('photo').onchange = e => {
    const file = e.target.files[0];
    if(file){
      const img = new Image();
      img.src = URL.createObjectURL(file);
      img.className = "mx-auto max-h-64 rounded-xl shadow";
      document.getElementById('preview').innerHTML = '<p class="text-green-600 font-bold">' + file.name + '</p>';
      document.getElementById('preview').appendChild(img);
    }
  };

  function load(f={}){
    fetch('db.php?action=list&loc='+encodeURIComponent(f.loc||'')+
          '&price='+(f.price||'')+'&gender='+encodeURIComponent(f.gender||''))
      .then(r => r.text())
      .then(html => {
        document.getElementById('grid').innerHTML = html;
        document.getElementById('empty').style.display = html.trim() ? 'none' : 'block';
      });
  }
  load();

  function filter(){
    load({
      loc: document.getElementById('loc').value,
      price: document.getElementById('maxprice').value,
      gender: document.getElementById('gender').value
    });
  }
</script>