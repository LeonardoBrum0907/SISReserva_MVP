<?php
// auth/logout.php - Processa o logout do usuário

require_once '../includes/config.php'; // Para BASE_URL

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Destroi todas as variáveis de sessão
$_SESSION = array();

// Se a sessão for usada em cookies, também destrói o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão
session_destroy();

// Redireciona para a página de login ou página inicial
header("Location: " . BASE_URL . "auth/login.php");
exit();
?>