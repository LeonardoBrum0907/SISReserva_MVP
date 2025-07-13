<?php

if (session_status() == PHP_SESSION_NONE) {
   session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php'; 

$is_logged_in = false;
$logged_user_info_public = ['id' => null, 'name' => 'Visitante', 'email' => null, 'type' => null, 'imobiliaria_id' => null];

if (isset($_SESSION['user_id'])) {
   $is_logged_in = true;
   $logged_user_info_public['id'] = $_SESSION['user_id'];
   $logged_user_info_public['name'] = $_SESSION['user_name'] ?? 'Usuário';
   $logged_user_info_public['email'] = $_SESSION['user_email'] ?? null;
   $logged_user_info_public['type'] = $_SESSION['user_type'] ?? null;
   $logged_user_info_public['imobiliaria_id'] = $_SESSION['imobiliaria_id'] ?? null;
}

global $conn;
if (!isset($conn) || !$conn instanceof mysqli) { 
   try {
      $conn = get_db_connection();
   } catch (Exception $e) {
      error_log("Falha ao iniciar conexão com o DB em header_public.php: " . $e->getMessage());
   }
}


$page_title = isset($page_title) ? htmlspecialchars($page_title) : 'MVP SISReserva';
?>
<!DOCTYPE html>
<html lang="pt-BR">


<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo $page_title; ?></title>
   <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=Wix+Madefor+Text:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
   <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
   <script>
      const BASE_URL_JS = "<?php echo BASE_URL; ?>"; 
      window.IS_LOGGED_IN_JS = <?php echo json_encode($is_logged_in); ?>;
      window.LOGGED_USER_INFO_JS = <?php echo json_encode($logged_user_info_public); ?>;
   </script>
</head>


<body>

   <div class="public-header-wrapper">
      <aside class="sidebar" id="mainSidebar">
         <div class="sidebar-header">
            <a href="<?php
                     $logo_link = BASE_URL . 'admin/index.php';
                     // CORREÇÃO: Usar $logged_user_info_public para verificar o tipo de usuário
                     if (isset($logged_user_info_public['type'])) {
                        if ($logged_user_info_public['type'] === 'admin_imobiliaria') {
                           $logo_link = BASE_URL . 'imobiliaria/index.php';
                        } elseif (strpos($logged_user_info_public['type'], 'corretor') !== false) {
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
               <li><a href="<?php echo BASE_URL; ?>auth/login.php">Entrar</a></li>
               <li><a href="<?php echo BASE_URL; ?>auth/cadastro.php">Cadastre-se</a></li>
               <li><a href="<?php echo BASE_URL; ?>sobre.php">Sobre</a></li>
               <li><a href="<?php echo BASE_URL; ?>suporte.php">Suporte</a></li>
            </ul>
         </nav>
      </aside>

      <div class="sidebar-overlay" id="mainSidebarOverlay"></div>

      <header class="public-header">
         <div class="public-header-logo-title">
            <a href="<?php echo BASE_URL; ?>index.php" class="header-logo-link">
               <img src="<?php echo BASE_URL; ?>assets/images/logo_dashboard.png" alt="SISReserva Logo">
            </a>
         </div>
         <div class="public-header-right">
            <span class="user-greeting">Olá, <?php echo htmlspecialchars($logged_user_info_public['name'] ?? ''); ?></span>
            <button class="menu-toggle public-menu-toggle" aria-label="Abrir Menu">
               <i class="fas fa-bars"></i>
            </button>
         </div>
      </header>
   <div class="off-canvas-overlay" id="publicMenuOverlay"></div>
   <main>