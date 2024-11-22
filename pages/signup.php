<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up - VielaDefis</title>
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
      .footer-signup {
        width: 100%;
        position: fixed;
        height: 30px;
        bottom: 0;
        background-color: #49749c;
        text-align: center;
        padding: 10px 0;
      }
      .sign-up p{
        display: inline;
        margin-top: 30px;
        margin-bottom: -90px;
        margin-left: 205px
      }
      .home p{
        display: inline-block;
        margin-bottom: 550px;
        align-items: normal;
      }
    </style>
  </head>
  <body>
    <div class="container-signup">
    <a class="home" href="index.php"><p><</p>Home</a>
      <div class="form-signup-login-section">
        <form method="post" id="registrasiForm">
          <h1>Create an Account</h1>
          <p>Let's create your account</p>
          <label for="fname">Full Name<br /></label>
          <input
            type="text"
            name="name"
            id="name"
            placeholder="Enter your full name"
          /><br />
          <label for="email">E-Mail<br /></label>
          <input
            type="email"
            name="email"
            id="email"
            placeholder="Enter your E-mail address"
          /><br />
          <label for="pass">Password<br /></label>
          <input
            type="password"
            name="password"
            id="password"
            placeholder="Enter your Password"
          /><br />
          <label for="cpass">Confirm Password<br /></label>
          <input
            type="password"
            name="cpassword"
            id="cpassword"
            placeholder="Enter your Password again"
          /><br /><br />
          <button class="login-register" data-button="register">CREATE AN ACCOUNT</button>
          <div class="sign-up">
          <p>or</p>
          <button class="to-login" data-button="login" id="loginButton">LOGIN</button>
          <script>
            document.getElementById("loginButton").addEventListener("click", function(){
              window.location.href = "login.php";
            });
          </script>
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
        .getElementById("registrasiForm")
        .addEventListener("submit", function (event) {
          event.preventDefault();
          var formData = new FormData(this);
          var xhr = new XMLHttpRequest();
          xhr.open("POST", "../php/register.php", true);
          xhr.onload = function () {
            if (xhr.status === 200) {
              var response = xhr.responseText.trim();
              console.log("Response:", response);

              switch (response) {
                case "Registrasi berhasil":
                  showNotification("Registrasi berhasil!");
                  setTimeout(() => {
                    window.location.href = "login.php";
                  }, 2000);
                  break;
                case "Password tidak sama":
                  showNotification("Password tidak sama!");
                  break;
                case "Email sudah terdaftar":
                  showNotification(
                    "Email sudah terdaftar. Silakan gunakan email lain."
                  );
                  break;
                default:
                  showNotification("Registrasi gagal. Silakan coba lagi.");
              }
            } else {
              showNotification("Terjadi kesalahan. Silakan coba lagi.");
            }
          };
          xhr.send(formData);
        });
    </script>
    <div class="notification-overlay" id="overlay"></div>
    <div class="custom-notification" id="notification">
      <p id="notification-message"></p>
      <button onclick="closeNotification()">OK</button>
    </div>
  </body>
  <footer class="footer-signup"></footer>
</html>
