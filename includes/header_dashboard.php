<?php
// includes/header_dashboard.php - Cabeçalho para as áreas logadas (Admin, Imobiliária, Corretor)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// A ordem de inclusão é crucial: config -> database -> auth -> helpers -> alerts
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/alerts.php';

// A conexão com o banco de dados ($conn) DEVE ser estabelecida no script principal
// (e.g., admin/index.php, imobiliaria/index.php, etc.)
// ANTES de incluir este cabeçalho, pois funções como count_unread_alerts() dependem dela.

// Redireciona para login se não estiver logado
require_login();

// Obtém os dados do usuário logado APÓS require_login() e a conexão DB estar ativa
$logged_user_info = get_user_info();

// Fallback defensivo
if (!$logged_user_info) {
    $logged_user_info = ['id' => 0, 'name' => 'Visitante', 'type' => 'guest'];
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$page_title = isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard SISReserva';

$unread_alerts_count = 0;
try {
    $unread_alerts_count = count_unread_alerts($logged_user_info['id']);
} catch (Exception $e) {
    error_log("Erro ao buscar alertas para usuário " . $logged_user_info['id'] . ": " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const BASE_URL_JS = "<?php echo BASE_URL; ?>";
    </script>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="mainSidebar"> <div class="sidebar-header">
                <a href="<?php
                    $logo_link = BASE_URL . 'admin/index.php';
                    if (isset($logged_user_info['type'])) {
                        if ($logged_user_info['type'] === 'admin_imobiliaria') {
                            $logo_link = BASE_URL . 'imobiliaria/index.php';
                        } elseif (strpos($logged_user_info['type'], 'corretor') !== false) {
                            $logo_link = BASE_URL . 'corretor/index.php';
                        }
                    }
                    echo $logo_link;
                ?>" class="logo-link">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo_dashboard.png" alt="Dashboard Logo">
                </a>
                <button class="menu-toggle close-dashboard-menu" aria-label="Fechar Menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <?php if ($logged_user_info['type'] === 'admin'): ?>
                        <li><a href="<?php echo BASE_URL; ?>admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/empreendimentos/index.php"><i class="fas fa-building"></i> Empreendimentos</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/reservas/index.php"><i class="fas fa-calendar-alt"></i> Reservas</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/documentos/index.php"><i class="fas fa-file-alt"></i> Docs. p/ Análise</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/contratos/index.php"><i class="fas fa-file-contract"></i> Contratos</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/vendas/index.php"><i class="fas fa-handshake"></i> Vendas</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/leads/index.php"><i class="fas fa-users"></i> Leads</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/clientes/index.php"><i class="fas fa-user-friends"></i> Clientes</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/relatorios/index.php"><i class="fas fa-chart-line"></i> Relatórios</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/usuarios/index.php"><i class="fas fa-user-shield"></i> Usuários</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/imobiliarias/index.php"><i class="fas fa-city"></i> Imobiliárias</a></li>
                    <?php elseif ($logged_user_info['type'] === 'admin_imobiliaria'): ?>
                        <li><a href="<?php echo BASE_URL; ?>imobiliaria/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>imobiliaria/corretores/index.php"><i class="fas fa-users"></i> Meus Corretores</a></li>
                        <li><a href="<?php echo BASE_URL; ?>imobiliaria/reservas/index.php"><i class="fas fa-calendar-alt"></i> Reservas da Equipe</a></li>
                        <li><a href="<?php echo BASE_URL; ?>imobiliaria/vendas/index.php"><i class="fas fa-handshake"></i> Vendas da Equipe</a></li>
                        <li><a href="<?php echo BASE_URL; ?>imobiliaria/leads/index.php"><i class="fas fa-users"></i> Leads</a></li>
                        <li><a href="<?php echo BASE_URL; ?>imobiliaria/clientes/index.php"><i class="fas fa-user-friends"></i> Clientes</a></li>
                        <li><a href="<?php echo BASE_URL; ?>imobiliaria/perfil/index.php"><i class="fas fa-user-circle"></i> Perfil da Imobiliária</a></li>
                        <li><a href="<?php echo BASE_URL; ?>imobiliaria/relatorios/index.php"><i class="fas fa-chart-line"></i> Relatórios</a></li>
                    <?php elseif (strpos($logged_user_info['type'], 'corretor') !== false): ?>
                        <li><a href="<?php echo BASE_URL; ?>corretor/index.php"><i class="fas fa-tachometer-alt"></i> Meu Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>corretor/reservas/index.php"><i class="fas fa-calendar-alt"></i> Minhas Reservas</a></li>
                        <li><a href="<?php echo BASE_URL; ?>corretor/vendas/index.php"><i class="fas fa-handshake"></i> Minhas Vendas</a></li>
                        <li><a href="<?php echo BASE_URL; ?>corretor/leads/index.php"><i class="fas fa-bullhorn"></i> Meus Leads</a></li>
                        <li><a href="<?php echo BASE_URL; ?>corretor/perfil/index.php"><i class="fas fa-user-circle"></i> Meu Perfil</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo BASE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </aside>
        <div class="sidebar-overlay" id="mainSidebarOverlay"></div>

        <div class="main-content-dashboard">
            <header class="dashboard-header">
                <div class="dashboard-header-logo-title">
                    <a href="<?php echo $logo_link; ?>" class="header-logo-link">
                        <img src="<?php echo BASE_URL; ?>assets/images/logo_dashboard.png" alt="SISReserva Logo">
                    </a>
                </div>
                <div class="dashboard-header-right">
                    <span class="user-greeting">Olá, <?php echo htmlspecialchars($logged_user_info['name'] ?? ''); ?></span>
                    <a href="<?php echo BASE_URL; ?>admin/alertas/index.php" class="alerts-icon" title="Alertas">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_alerts_count > 0): ?>
                            <span class="alert-count"><?php echo htmlspecialchars($unread_alerts_count); ?></span>
                        <?php endif; ?>
                    </a>
                    <button class="menu-toggle dashboard-menu-toggle" aria-label="Abrir Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </header>
            <div class="dashboard-body">