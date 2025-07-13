<?php
// corretor/index.php - Dashboard do Corretor

// Inclua os arquivos necessários
require_once '../includes/config.php';
require_once '../includes/database.php'; // Este arquivo define get_db_connection()
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn; // Declara que você vai usar a variável global $conn
    $conn = get_db_connection(); // Chama a função para obter a conexão
} catch (Exception $e) {
    // Se a conexão falhar, logue o erro e exiba uma mensagem amigável ao usuário.
    error_log("Erro crítico na inicialização do DB em corretor/index.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Redireciona se não for um corretor (autônomo ou de imobiliária)
require_permission(['corretor_autonomo', 'corretor_imobiliaria']);

$page_title = "Dashboard do Corretor";

$user_info = get_user_info();
$corretor_id = $user_info['id'];

$minhas_reservas_ativas = 0; // Renomeado para mais clareza
$minhas_vendas = 0;
$novos_leads = 0; // Leads atribuídos ao corretor e não atendidos

try {
    // KPI: Minhas Reservas Ativas (status 'aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica')
    $sql_reservas_ativas = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica')";
    $result_reservas_ativas = fetch_single($sql_reservas_ativas, [$corretor_id], "i");
    $minhas_reservas_ativas = $result_reservas_ativas['total'] ?? 0;

    // KPI: Minhas Vendas (status 'vendida' e atribuídas a este corretor)
    $sql_vendas = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status = 'vendida'";
    $result_vendas = fetch_single($sql_vendas, [$corretor_id], "i");
    $minhas_vendas = $result_vendas['total'] ?? 0;

    // KPI: Novos Pedidos (Leads) - reservas com status 'solicitada' atribuídas a este corretor
    // Esta métrica pode precisar de refinamento, mas por enquanto, mantém como 'solicitada'
    $sql_leads = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status = 'solicitada'";
    $result_leads = fetch_single($sql_leads, [$corretor_id], "i");
    $novos_leads = $result_leads['total'] ?? 0;

} catch (Exception $e) {
    error_log("Erro no Dashboard do Corretor: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar os dados do dashboard.";
}

require_once '../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="message-box message-box-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Minhas Reservas Ativas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($minhas_reservas_ativas); ?></span>
            <a href="<?php echo BASE_URL; ?>corretor/reservas/index.php" class="kpi-action-link">Ver Detalhes</a>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Minhas Vendas Realizadas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($minhas_vendas); ?></span>
            <a href="<?php echo BASE_URL; ?>corretor/vendas/index.php" class="kpi-action-link">Ver Detalhes</a>
        </div>
        <div class="kpi-card kpi-card-urgent">
            <span class="kpi-label">Novos Pedidos (Leads)</span>
            <span class="kpi-value"><?php echo htmlspecialchars($novos_leads); ?></span>
            <a href="<?php echo BASE_URL; ?>corretor/leads/index.php" class="kpi-action-link">Ver Pedidos</a>
        </div>
    </div>

    </div>

<?php require_once '../includes/footer_dashboard.php'; ?>