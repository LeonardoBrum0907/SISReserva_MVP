<?php
// includes/auth.php - Funções de Autenticação e Autorização
// Assume que config.php e database.php já foram incluídos pelo script principal
// (ex: login.php, dashboard pages)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Tenta realizar o login de um usuário.
 * @param string $email O e-mail do usuário.
 * @param string $password A senha do usuário (texto puro).
 * @return bool True se o login for bem-sucedido, False caso contrário.
 */
function login_user($email, $password) {
    global $conn; // Assume $conn está disponível globalmente

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "E-mail e senha são obrigatórios.";
        return false;
    }

    // Usar a função fetch_single do database.php
    $user = fetch_single("SELECT id, nome, email, senha, tipo, aprovado, imobiliaria_id FROM usuarios WHERE email = ?", [$email], "s");

    if ($user && password_verify($password, $user['senha'])) {
        if (!$user['aprovado']) {
            $_SESSION['login_error'] = "Sua conta ainda não foi aprovada. Por favor, aguarde a aprovação do administrador.";
            return false;
        }
        // Verifica se é corretor de imobiliária e se está vinculado
        if ($user['tipo'] === 'corretor_imobiliaria' && empty($user['imobiliaria_id'])) {
            $_SESSION['login_error'] = "Sua conta de corretor de imobiliária precisa ser vinculada a uma imobiliária por um administrador.";
            return false;
        }

        // Login bem-sucedido
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['tipo'];
        $_SESSION['imobiliaria_id'] = $user['imobiliaria_id'] ?? null; // Adiciona imobiliaria_id à sessão

        session_regenerate_id(true); // Regenera o ID da sessão para segurança (Session Fixation)
        return true;
    } else {
        $_SESSION['login_error'] = "E-mail ou senha inválidos.";
        return false;
    }
}

/**
 * Realiza o logout do usuário.
 */
function logout_user() {
    session_unset();    // Remove todas as variáveis de sessão
    session_destroy(); // Destrói a sessão
    // Opcional: Iniciar uma nova sessão vazia e regenerar ID para evitar problemas futuros
    session_start();
    session_regenerate_id(true);
}

/**
 * Verifica se o usuário está logado.
 * @return bool True se logado, False caso contrário.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Obtém informações do usuário logado.
 * @return array|null Array com info do usuário ou null se não estiver logado.
 */
function get_user_info() {
    if (is_logged_in()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'type' => $_SESSION['user_type'],
            'imobiliaria_id' => $_SESSION['imobiliaria_id'] ?? null // Inclui imobiliaria_id
        ];
    }
    return null;
}

/**
 * Redireciona o usuário para a página de login se não estiver autenticado.
 * @param string $redirect_to URL para redirecionar após o login (opcional).
 */
function require_login($redirect_to = '') {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $redirect_to; // Guarda a URL original
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }
}

/**
 * Verifica se o usuário logado tem uma das permissões exigidas.
 * Redireciona para uma página de acesso negado ou dashboard se não tiver.
 * @param array|string $allowed_types Tipos de usuário permitidos (ex: ['admin', 'corretor_autonomo']).
 * @param bool $is_ajax Se true, retorna JSON de erro em vez de redirecionar.
 */
function require_permission($allowed_types, $is_ajax = false) { // Added $is_ajax parameter
    require_login(); // Ensures user is logged in
    $user_info = get_user_info();
    if (!$user_info) { // Fallback if session issues
        if ($is_ajax) {
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Usuário não autenticado ou sessão expirada.']);
            exit();
        } else {
            header("Location: " . BASE_URL . "auth/login.php");
            exit();
        }
    }
    if (is_string($allowed_types)) {
        $allowed_types = [$allowed_types];
    }
    if (!in_array($user_info['type'], $allowed_types)) {
        if ($is_ajax) {
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'Acesso negado. Você não tem permissão para realizar esta ação.']);
            exit();
        } else {
            // Redirect based on user type if not allowed
            if ($user_info['type'] === 'admin') {
                header("Location: " . BASE_URL . "admin/index.php");
            } elseif ($user_info['type'] === 'admin_imobiliaria') {
                header("Location: " . BASE_URL . "imobiliaria/index.php");
            } elseif (in_array($user_info['type'], ['corretor_autonomo', 'corretor_imobiliaria'])) {
                header("Location: " . BASE_URL . "corretor/index.php");
            } else {
                header("Location: " . BASE_URL . "index.php?error=access_denied");
            }
            exit();
        }
    }
}
?>