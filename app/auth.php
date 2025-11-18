<?php
function login($email, $password) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = ['id' => $user['id'], 'email' => $user['email'], 'name' => $user['name']];
        return true;
    }
    return false;
}

function logout() {
    unset($_SESSION['user']);
    session_destroy();
}

function is_logged_in() {
    return !empty($_SESSION['user']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}