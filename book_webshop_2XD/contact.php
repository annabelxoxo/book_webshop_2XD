<?php
require __DIR__ . "/includes/config.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - Book Webshop</title>
  <link rel="stylesheet" href="css/styles.css">
</head>

<body>

<?php include 'includes/header.php'; ?>

<main>
  <div class="container">

<section class="contact-page">
  <h2>Contact Us</h2>
  <p class="contact-intro">
    Have a question or need help? Reach out to us or send a message.
  </p>

  <div class="contact-layout">

    <form class="contact-form" method="post">
      <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
      </div>

      <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="5" required></textarea>
      </div>

      <button type="submit" class="btn-primary">Send message</button>
    </form>

    <div class="contact-info">
      <h3>or want to talk directly to us on the phone</h3>



      <p>
        📞 <a href="tel:+32483450862">
          +32 483 45 08 62
        </a>
      </p>

      <div class="contact-socials">
        <h4>Follow me on social media</h4>
        <a href="https://facebook.com/uitsprakenannabel.smeulders" target="_blank">
          <img src="images/facebook.png" alt="Facebook">
        </a>

        <a href="https://www.instagram.com/smeuldersannabel/" target="_blank">
          <img src="images/instagram.png" alt="Instagram">
        </a>

        <a href="https://www.tiktok.com/@annabel_happycow" target="_blank">
          <img src="images/tiktok.png" alt="Tiktok">
        </a>
      </div>
    </div>

  </div>
</section>


  </div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
