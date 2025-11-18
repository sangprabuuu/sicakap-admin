<?php
// helper umum

if (!function_exists('h')) {
    /**
     * Escape HTML (shortcut)
     */
    function h($s) {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

function flash_set($msg) {
    $_SESSION['flash'] = $msg;
}
function flash_get() {
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}