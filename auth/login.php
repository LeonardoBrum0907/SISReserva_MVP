<?php

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php'; 

try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em auth/login.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

if (is_logged_in()) {
    $user_info = get_user_info();
    if ($user_info['type'] === 'admin') {
        header("Location: " . BASE_URL . "admin/index.php");
    } elseif ($user_info['type'] === 'admin_imobiliaria') {
        header("Location: " . BASE_URL . "imobiliaria/index.php");
    } elseif (in_array($user_info['type'], ['corretor_autonomo', 'corretor_imobiliaria'])) { 
        header("Location: " . BASE_URL . "corretor/index.php");
    } else { 
        header("Location: " . BASE_URL . "index.php");
    }
    exit();
}

$page_title = "Login";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login_user($email, $password)) {
        $user_info = get_user_info();
        if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
            $redirect_url = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirect_url);
        } elseif ($user_info['type'] === 'admin') {
            header("Location: " . BASE_URL . "admin/index.php");
        } elseif ($user_info['type'] === 'admin_imobiliaria') {
            header("Location: " . BASE_URL . "imobiliaria/index.php");
        } elseif (strpos($user_info['type'], 'corretor') !== false) {
            header("Location: " . BASE_URL . "corretor/index.php");
        } else { 
            header("Location: " . BASE_URL . "index.php");
        }
        exit();
    } else {
        
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="login-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="light-mode">
    <div class="container">
        <!-- Logo no canto superior esquerdo -->
        <div class="top-logo">
            <div class="logo-badge">
                <span class="logo-text">manus</span>
            </div>
        </div>

        <!-- Botão de alternância de tema -->
        <button id="theme-toggle" class="theme-toggle">
            <i class="fas fa-moon"></i>
        </button>

        <!-- Conteúdo principal centralizado -->
        <div class="main-content">
            <!-- Ícone central -->
            <div class="center-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
                </svg>
            </div>

            <!-- Título principal -->
            <h1 class="main-title">Entrar na sua conta</h1>
            
            <!-- Subtítulo -->
            <div class="subtitle">
                <p>Acesse sua conta para continuar</p>
                <p>Faça login para acessar o sistema</p>
            </div>

            <!-- Mensagens de erro/sucesso -->
            <?php
            if (isset($_SESSION['login_error'])): ?>
                <div class="message-box message-box-error">
                    <p><?php echo $_SESSION['login_error']; ?></p>
                </div>
                <?php unset($_SESSION['login_error']);
            endif;
            if (isset($_SESSION['message'])): ?>
                <div class="message-box message-box-<?php echo $_SESSION['message']['type']; ?>">
                    <p><?php echo $_SESSION['message']['text']; ?></p>
                </div>
                <?php unset($_SESSION['message']);
            endif;
            ?>

            <!-- Botões de login social (adaptados para formulário de email/senha) -->
            <div class="auth-buttons">
                <!-- Formulário de Login -->
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <input type="email" id="email" name="email" placeholder="E-mail" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Senha" required>
                            <button type="button" class="toggle-password" aria-label="Mostrar/Esconder Senha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-email">Entrar</button>
                </form>
            </div>

            <!-- Links adicionais abaixo do botão Entrar -->
            <div class="additional-links">
                <a href="recuperar-senha.php" class="text-link">Esqueceu sua senha?</a>
                <span class="link-separator">•</span>
                <a href="cadastro.php" class="text-link">Cadastre-se aqui</a>
            </div>

            <!-- Link de login (mantido para compatibilidade, se necessário) -->
            <div class="login-link">
                <span>Já tem uma conta? </span>
                <a href="#" class="sign-in">Entrar</a>
            </div>
        </div>

        <!-- Links do rodapé (ocultos) -->
        <div class="footer-links">
            <!-- Estes links estão agora no additional-links -->
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Theme toggle
        document.getElementById('theme-toggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            document.body.classList.toggle('light-mode');
            const icon = this.querySelector('i');
            if (document.body.classList.contains('dark-mode')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });
    </script>
</body>
</html>

