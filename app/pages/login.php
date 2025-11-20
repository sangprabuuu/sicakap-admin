<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: ' . APP_URL . '/?p=dashboard');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - SiCakap Admin</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(135deg, #0B7A2F 0%, #064d1d 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .login-wrapper {
      width: 100%;
      max-width: 900px;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .login-container {
      display: flex;
      min-height: 500px;
    }
    
    .login-left {
      flex: 1;
      background: #0B7A2F;
      color: white;
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
    }
    
    .login-logo {
      width: 110px;
      height: 110px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 35px;
      padding: 20px;
    }
    
    .login-logo img {
      width: 160%;
      height: 160%;
      object-fit: contain;
    }
    
    .login-left h1 {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 18px;
      line-height: 1.5;
    }
    
    .login-left p {
      font-size: 12px;
      opacity: 0.95;
      line-height: 1.8;
      font-weight: 400;
    }
    
    .login-right {
      flex: 1;
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: white;
    }
    
    .login-right h2 {
      font-size: 22px;
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
      text-align: center;
    }
    
    .login-subtitle {
      font-size: 13px;
      color: #666;
      margin-bottom: 35px;
      text-align: center;
    }
    
    .error {
      background: #fee;
      color: #c33;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-size: 13px;
      border: 1px solid #fcc;
      text-align: center;
    }
    
    .form-group {
      margin-bottom: 18px;
    }
    
    .form-group input {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      transition: border-color 0.3s;
      background: white;
    }
    
    .form-group input:focus {
      outline: none;
      border-color: #0B7A2F;
    }
    
    .form-group input::placeholder {
      color: #999;
    }
    
    .forgot-password {
      text-align: right;
      margin-bottom: 25px;
    }
    
    .forgot-password a {
      color: #0B7A2F;
      text-decoration: none;
      font-size: 12px;
    }
    
    .forgot-password a:hover {
      text-decoration: underline;
    }
    
    .btn-login {
      width: 100%;
      padding: 13px;
      background: #0B7A2F;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .btn-login:hover {
      background: #096624;
    }
    
    @media (max-width: 768px) {
      .login-container {
        flex-direction: column;
      }
      
      .login-left {
        padding: 40px 30px;
      }
      
      .login-logo {
        width: 90px;
        height: 90px;
        margin-bottom: 25px;
      }
      
      .login-left h1 {
        font-size: 16px;
      }
      
      .login-left p {
        font-size: 11px;
      }
      
      .login-right {
        padding: 40px 30px;
      }
    }
  </style>
</head>
<body>
  <div class="login-wrapper">
    <div class="login-container">
      <div class="login-left">
        <div class="login-logo">
          <img src="<?= APP_URL ?>/assets/images/logo_putih.png" alt="Logo SiCakap">
        </div>
        <h1>Selamat Datang di Dashboard Admin<br>SiCakap</h1>
        <p>SISKA CAKAP BERKUALITAS<br>PERMUDAHNYA</p>
      </div>
      <div class="login-right">
        <h2>Selamat datang</h2>
        <p class="login-subtitle">Silahkan login untuk melanjut ke website</p>
        
        <?php if(!empty($error)): ?>
          <div class="error"><?=h($error)?></div>
        <?php endif; ?>
        
        <form method="post" action="?p=login">
          <div class="form-group">
            <input type="text" name="username" placeholder="Email" required>
          </div>
          
          <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
          </div>
          
          <div class="forgot-password">
            <a href="#">No Login Password?</a>
          </div>
          
          <button type="submit" class="btn-login">Login</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
