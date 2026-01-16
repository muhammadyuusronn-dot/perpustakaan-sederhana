<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    $q = mysqli_query(
        $koneksi,
        "SELECT * FROM admin WHERE username='$u' AND password='$p'"
    );

    if (mysqli_num_rows($q) > 0) {
        $_SESSION['admin'] = $u;
        header("location:dashboard.php");
    } else {
        $error = "Login gagal!";
    }
}
?>

<link rel="stylesheet" href="assets/login.css">
<script defer src="assets/login.js"></script>

<div class="bubble"></div>
<div class="bubble"></div>

<div class="login-area">
    <div class="book">
        <img src="assets/logo.png" alt="YuPerpus Logo" class="book-logo">
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <h2>Login</h2>

            <?php if (isset($error))
                echo "<p class='error'>$error</p>"; ?>

            <form method="post" autocomplete="off">
                <div class="input-group">
                    <input type="text" name="username" autocomplete="off" required>
                    <label>Username</label>
                </div>

                <div class="input-group">
                    <input type="password" name="password" autocomplete="off" required>
                    <label>Password</label>
                </div>

                <button name="login">Masuk</button>
            </form>
        </div>
    </div>
</div>