<?php
$pageTitle = 'Shop';
include '../templates/header.php';
?>
    <div class="profile-container">
      <h2>Profile</h2>
      <div id="userProfile">
        <!-- Data profil akan dimuat di sini -->
      </div>
      <button id="logoutButton" class="logout-btn">Logout</button>
    </div>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const userData = sessionStorage.getItem("user");
        if (!userData) {
          window.location.href = "login.php";
          return;
        }

        const user = JSON.parse(userData);
        const userProfile = document.getElementById("userProfile");

        // Tampilkan data profil
        userProfile.innerHTML = `
            <div class="profile-info">
                <p><strong>Nama:</strong> ${user.nama_user}</p>
                <p><strong>Email:</strong> ${user.email}</p>
            </div>
        `;

        // Handle logout
        document
          .getElementById("logoutButton")
          .addEventListener("click", function () {
            // Hapus data user dari session storage
            sessionStorage.removeItem("user");

            // Redirect ke halaman login
            window.location.href = "index.php";
          });
      });
    </script>

    <style>
      .profile-container {
        max-width: 600px;
        margin: 50px auto;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      }

      .profile-info {
        margin: 20px 0;
      }

      .profile-info p {
        margin: 10px 0;
        padding: 10px;
        background: #f5f5f5;
        border-radius: 5px;
      }

      .logout-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
      }

      .logout-btn:hover {
        background: #c82333;
      }
    </style>
  </body>
</html>
