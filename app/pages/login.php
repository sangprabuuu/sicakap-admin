<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($email, $password)) {
        header('Location: ?p=dashboard');
        exit;
    } else {
        $error = "Email atau password salah";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login - SiCakap Admin</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
  <div class="login-box">
    <h2>Login Admin</h2>
    <?php if(!empty($error)): ?><div class="error"><?=h($error)?></div><?php endif; ?>
    <form method="post" action="?p=login">
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>