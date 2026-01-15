<?php include 'header.php'; ?>
<?php include 'db.php'; ?>

<div class="min-h-screen bg-gray-50 py-16">
  <div class="container mx-auto px-6 max-w-4xl">
    <h1 class="text-5xl font-bold text-center text-green-700 mb-10">Contact Us</h1>
    
    <div class="bg-white rounded-3xl shadow-xl p-10 text-lg text-gray-700">
      <p class="text-xl mb-8">
        Got question? See fake post? Want to say thank you?  
        <strong>We dey here 24/7</strong>
      </p>

      <div class="grid md:grid-cols-2 gap-10">
        <!-- Contact Info -->
        <div class="space-y-6">
          <div>
            <h3 class="text-2xl font-bold text-green-700">WhatsApp (Fastest)</h3>
            <a href="https://wa.me/234070552959" class="text-3xl font-bold text-green-600 hover:underline">
              → +234 7055295945
            </a>
            <p class="text-gray-600">Chat me directly — I reply sharp!</p>
          </div>

          <div>
            <h3 class="text-2xl font-bold text-green-700">Email</h3>
            <p class="text-xl">hello@roommatelagos.com</p>
          </div>

          <div>
            <h3 class="text-2xl font-bold text-green-700">Instagram</h3>
            <a href="https://instagram.com/roommatelagos" class="text-xl text-green-600 hover:underline">@roommatelagos</a>
          </div>
        </div>

        <!-- Message Form -->
        <div>
          <h3 class="text-2xl font-bold text-green-700 mb-4">Send Message</h3>
          <form action="https://formsubmit.co/hello@roommatelagos.com" method="POST" class="space-y-5">
            <input type="text" name="name" placeholder="Your name" required 
                   class="w-full p-4 border rounded-xl text-lg">
            <input type="tel" name="phone" placeholder="Your phone" required 
                   class="w-full p-4 border rounded-xl text-lg">
            <textarea name="message" rows="6" placeholder="Your message..." required 
                      class="w-full p-4 border rounded-xl text-lg"></textarea>
            
            <button type="submit" class="w-full bg-green-700 text-white py-5 rounded-xl font-bold text-xl hover:bg-green-800">
              Send Message
            </button>
            <input type="hidden" name="_next" value="https://your-site.com/contact.php?sent=1">
            <input type="hidden" name="_subject" value="New message from RoomMate Lagos">
          </form>

          <?php if(isset($_GET['sent'])): ?>
            <p class="mt-6 text-green-600 font-bold text-xl text-center">Message sent! I go reply you sharp</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="text-center mt-12">
        <a href="index.php" class="inline-block bg-gray-700 text-white px-10 py-5 rounded-xl text-xl font-bold">
          ← Back to Site
        </a>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>