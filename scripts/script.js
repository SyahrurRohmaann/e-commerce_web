// Register
const container = document.querySelector(".form-signup-section");

firebase.auth().onAuthStateChanged((user) => {
  if (user) {
  } else {
    Landing();
  }
});

const Landing = () => {
  const element = document.createElement("div");
  element.classList.add("Landing");
  element.innerHTML = `
  <div class="form-signup-section">
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
        <button data-button="register">CREATE AN ACCOUNT</button>
      </div>
  `;

  container.innerHTML = "";
  container.appendChild(element);

  const email = document.querySelector("#email");
  const password = document.querySelector("#password");
  const registerBtn = document.querySelector(`[data-button="register"]`);

  registerBtn.onclick = () => {
    const cpassword = document.getElementById("cpassword").value;

    if (password.value === cpassword) {
      firebase
        .auth()
        .createUserWithEmailAndPassword(email.value, password.value)
        .then((cred) => {
          alert("Registrasi Berhasil!");
          window.location.href = "login.html";
        })
        .catch((error) => {
          alert("Isi data anda!");
        });
    } else {
      alert("Password tidak sama");
    }
  };
};
