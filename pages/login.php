<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - VielaDefis</title>
    <!-- Fonts -->
    <link
      href="https://api.fontshare.com/v2/css?f[]=satoshi@300,400,500,700&display=swap"
      rel="stylesheet"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Vollkorn:ital,wght@0,400..900;1,400..900&display=swap"
      rel="stylesheet"
    />
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="../assets/styles/style.css" rel="stylesheet" />
    <style>
      body,
      html {
        font-family: Satoshi, sans-serif;
        height: 100%;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
      }
      .form-signup-login-section h1 {
        margin-bottom: 10px;
        font-family: Vollkorn, serif;
        font-weight: 900;
        font-size: 26px;
      }
      .form-signup-login-section p {
        margin-bottom: 20px;
        color: #000;
        font-family: Vollkorn, serif;
      }

      .custom-notification {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
        display: none;
        z-index: 1000;
      }
      .notification-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 999;
      }

      .custom-notification button {
        margin-top: 15px;
        padding: 8px 20px;
        background: #49749c;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
      }
      .footer-login {
        width: 100%;
        position: fixed;
        height: 30px;
        bottom: 0;
        background-color: #49749c;
        text-align: center;
        padding: 10px 0;
      }
      .sign-up p{
        display: inline-block;
        margin-top: 30px;
        margin-bottom: -90px;
        margin-left: 100px
      }
      .sign-up a{
        color: blue;
      }
      .home p{
        display: inline-block;
        margin-bottom: 500px;
        align-items: normal;
      }
    </style>
  </head>
  <body>
  <a class="home" href="index.php"><p><</p>Home</a>
    <div class="container-login">
      <div class="form-signup-login-section">
        <form id="loginForm" method="post">
          <h1>Login to your account</h1>
          <p>it's great to see you again</p>
          <label for="email">Email<br /></label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email address"
          /><br />
          <label for="password">Password<br /></label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
          /><br /><br />
          <button class="login-register" data-button="login">LOGIN</button>
          <div class="sign-up">
            <p>already have an account?
              <a href="signup.php">Sign Up</a>
            </p>
          </div>
        </form>
      </div>

      <div class="container-image">
        <img src="../assets/image/log-pict.png" alt="image" />
      </div>
    </div>
    <script>
      function showNotification(message) {
        document.getElementById("notification-message").textContent = message;
        document.getElementById("notification").style.display = "block";
        document.getElementById("overlay").style.display = "block";
      }
      function closeNotification() {
        document.getElementById("notification").style.display = "none";
        document.getElementById("overlay").style.display = "none";
      }

      document
        .getElementById("loginForm")
        .addEventListener("submit", function (event) {
          event.preventDefault();
          var formData = new FormData(this);
          
          fetch('../php/login.php', {
            method: 'POST',
            body: formData
          })
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(data => {
            console.log('Response:', data);
            
            if (data.status === 'success') {
              sessionStorage.setItem('user', JSON.stringify(data.user));
              showNotification(data.message || 'Login berhasil!');
              setTimeout(() => {
                window.location.href = 'index.php';
              }, 2000);
            } else {
              showNotification(data.message || 'Login gagal');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan. Silakan coba lagi.');
          });
        });

      document.querySelector('.custom-notification button').addEventListener('click', closeNotification);
    </script>
    <div class="notification-overlay" id="overlay"></div>
    <div class="custom-notification" id="notification">
      <p id="notification-message"></p>
      <button onclick="closeNotification()">OK</button>
    </div>
  </body>
  <footer class="footer-login"></footer>
</html>
