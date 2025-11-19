<?php
function login($username, $password) {
    // Query ke Supabase table user_admin via REST API
    $endpoint = "user_admin?username=eq.$username";
    $result = supabase_request('GET', $endpoint);
    
    if ($result['code'] === 200 && !empty($result['data'])) {
        $user = $result['data'][0];
        
        // Cek password (plain text untuk sementara)
        if (isset($user['password']) && $user['password'] === $password) {
            $_SESSION['user'] = [
                'id' => $user['id'], 
                'username' => $user['username'],
                'name' => $user['username']
            ];
            return true;
        }
    }
    
    // Log error untuk debugging
    error_log("Login failed for user: $username - Response code: " . ($result['code'] ?? 'N/A'));
    
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