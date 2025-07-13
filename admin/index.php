<?php
// admin/index.php - Dashboard do Admin Master

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php'; // Inclui helpers.php para format_currency_brl()

try {
    global $conn; // Declara que você vai usar a variável global $conn
    $conn = get_db_connection(); // Chama a função para obter a conexão
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/index.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}

// Redireciona se não for um admin
require_permission(['admin']);

$page_title = "Dashboard Admin Master";
$logged_user_info = get_user_info(); // Obtém informações do usuário logado

// --- Lógica para os Cards de KPI ---
$total_empreendimentos = 0;
$unidades_cadastradas_total = 0; // NOVO KPI
$unidades_reservadas_mes = 0;
$unidades_vendidas_mes = 0;
$pedidos_reservas_pendentes = 0;
$total_corretores_cadastrados = 0;
$total_imobiliarias_cadastradas = 0;
$total_leads_gerados = 0;
$vgv_gerado = 0;
$documentos_pendentes_aprovacao = 0; // NOVO KPI
$contratos_pendentes_assinatura = 0; // NOVO KPI
$alertas_nao_lidos_admin = 0; // NOVO KPI
$ultimos_alertas = []; // NOVO: Para o bloco de alertas

try {
    // KPI: Empreendimentos Cadastrados
    $result = fetch_single("SELECT COUNT(id) AS total FROM empreendimentos");
    $total_empreendimentos = $result['total'] ?? 0;

    // NOVO KPI: Unidades Cadastradas (Total Geral)
    $result = fetch_single("SELECT COUNT(id) AS total FROM unidades");
    $unidades_cadastradas_total = $result['total'] ?? 0;

    // KPI: Unidades Reservadas no Mês (status = 'reservada' ou 'aprovada')
    $mes_atual = date('Y-m-01 00:00:00');
    $proximo_mes = date('Y-m-01 00:00:00', strtotime('+1 month'));
    $sql_reservadas = "SELECT COUNT(id) AS total FROM reservas WHERE (status = 'reservada' OR status = 'aprovada') AND data_reserva >= ? AND data_reserva < ?";
    $result = fetch_single($sql_reservadas, [$mes_atual, $proximo_mes], "ss");
    $unidades_reservadas_mes = $result['total'] ?? 0;

    // KPI: Unidades Vendidas no Mês
    $sql_vendidas = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'vendida' AND data_ultima_interacao >= ? AND data_ultima_interacao < ?";
    $result = fetch_single($sql_vendidas, [$mes_atual, $proximo_mes], "ss");
    $unidades_vendidas_mes = $result['total'] ?? 0;

    // KPI: Pedidos de Reservas Pendentes (Leads)
    $result = fetch_single("SELECT COUNT(id) AS total FROM reservas WHERE status = 'solicitada'");
    $pedidos_reservas_pendentes = $result['total'] ?? 0;

    // KPI: Total de Corretores Cadastrados
    $result = fetch_single("SELECT COUNT(id) AS total FROM usuarios WHERE tipo LIKE 'corretor%'");
    $total_corretores_cadastrados = $result['total'] ?? 0;

    // KPI: Total de Imobiliárias Cadastradas
    $result = fetch_single("SELECT COUNT(id) AS total FROM imobiliarias WHERE ativa = TRUE");
    $total_imobiliarias_cadastradas = $result['total'] ?? 0;

    // KPI: Total de Leads Gerados (todas as solicitações de não-logados)
    // Assumimos que leads gerados são reservas onde corretor_id IS NULL e status = 'solicitada'
    $result = fetch_single("SELECT COUNT(id) AS total FROM reservas WHERE corretor_id IS NULL AND status = 'solicitada'");
    $total_leads_gerados = $result['total'] ?? 0;

    // KPI: VGV Gerado (Valor Geral de Vendas - total de unidades vendidas)
    $result = fetch_single("SELECT SUM(valor_reserva) AS total_vgv FROM reservas WHERE status = 'vendida'");
    $vgv_gerado = $result['total_vgv'] ?? 0;

    // NOVO KPI: Documentos Pendentes de Aprovação
    $result = fetch_single("SELECT COUNT(id) AS total FROM documentos_reserva WHERE status = 'pendente'");
    $documentos_pendentes_aprovacao = $result['total'] ?? 0;

    // NOVO KPI: Contratos Pendentes de Assinatura
    $sql_contratos_pendentes = "SELECT COUNT(id) AS total FROM reservas WHERE status IN ('contrato_enviado', 'aguardando_assinatura_eletronica')";
    $result = fetch_single($sql_contratos_pendentes);
    $contratos_pendentes_assinatura = $result['total'] ?? 0;

    // NOVO KPI: Total de Alertas Não Lidos (para o admin logado)
    $result = fetch_single("SELECT COUNT(id) AS total FROM alertas WHERE usuario_id = ? AND lido = 0", [$logged_user_info['id']], "i");
    $alertas_nao_lidos_admin = $result['total'] ?? 0;

    // --- Lógica para Ranking: Corretores e Imobiliárias que Mais Venderam ---
    $sql_top_corretores = "
        SELECT u.id, u.nome, COUNT(r.id) AS total_vendas
        FROM usuarios u
        JOIN reservas r ON u.id = r.corretor_id
        WHERE r.status = 'vendida'
        GROUP BY u.id, u.nome
        ORDER BY total_vendas DESC
        LIMIT 5;
    ";
    $top_corretores = fetch_all($sql_top_corretores);

    $sql_top_imobiliarias = "
        SELECT i.id, i.nome, COUNT(r.id) AS total_vendas
        FROM imobiliarias i
        JOIN usuarios u ON i.id = u.imobiliaria_id
        JOIN reservas r ON u.id = r.corretor_id
        WHERE r.status = 'vendida'
        GROUP BY i.id, i.nome
        ORDER BY total_vendas DESC
        LIMIT 5;
    ";
    $top_imobiliarias = fetch_all($sql_top_imobiliarias);

    // --- Lógica para Gráfico de Vendas (Últimos 30 Dias) ---
    $vendas_por_dia = [];
    $data_hoje = new DateTime();
    $formato_data_sql = 'Y-m-d';

    for ($i = 29; $i >= 0; $i--) {
        $data_dia = (clone $data_hoje)->modify("-{$i} days");
        $data_inicio_dia = $data_dia->format($formato_data_sql . ' 00:00:00');
        $data_fim_dia = $data_dia->format($formato_data_sql . ' 23:59:59');

        $sql_vendas_dia = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'vendida' AND data_ultima_interacao >= ? AND data_ultima_interacao <= ?";
        $result = fetch_single($sql_vendas_dia, [$data_inicio_dia, $data_fim_dia], "ss");
        $vendas_por_dia[$data_dia->format('d/m')] = $result['total'] ?? 0;
    }

    $chart_labels = json_encode(array_keys($vendas_por_dia));
    $chart_data_values = json_encode(array_values($vendas_por_dia));

    // NOVO: Últimos Alertas para o Usuário Logado
    $sql_ultimos_alertas = "SELECT id, titulo, mensagem, link, lido, data_criacao FROM alertas WHERE usuario_id = ? ORDER BY data_criacao DESC LIMIT 5";
    $ultimos_alertas = fetch_all($sql_ultimos_alertas, [$logged_user_info['id']], "i");


} catch (Exception $e) {
    error_log("Erro no Dashboard Admin: " . $e->getMessage());
    // Você pode definir um array de erros para exibir na página se necessário
}

// Inclui o cabeçalho do dashboard
require_once '../includes/header_dashboard.php';
?>

<div class="dashboard-content-wrapper">
    <div class="kpi-grid">
        <a href="<?php echo BASE_URL; ?>admin/empreendimentos/index.php" class="kpi-card">
            <span class="kpi-label">Empreendimentos Cadastrados</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_empreendimentos); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/empreendimentos/index.php" class="kpi-card">
            <span class="kpi-label">Unidades Cadastradas (Total)</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_cadastradas_total); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/reservas/index.php?status=reservada" class="kpi-card">
            <span class="kpi-label">Reservas no Mês</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_reservadas_mes); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/vendas/index.php" class="kpi-card">
            <span class="kpi-label">Vendas no Mês</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_vendidas_mes); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/leads/index.php" class="kpi-card kpi-card-urgent">
            <span class="kpi-label">Pedidos Pendentes</span>
            <span class="kpi-value"><?php echo htmlspecialchars($pedidos_reservas_pendentes); ?></span>
            <span class="kpi-action-link">Ver Pedidos</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/documentos/index.php?status=pendente" class="kpi-card kpi-card-urgent">
            <span class="kpi-label">Documentos Pendentes</span>
            <span class="kpi-value"><?php echo htmlspecialchars($documentos_pendentes_aprovacao); ?></span>
            <span class="kpi-action-link">Analisar Docs</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/contratos/index.php?status=pendente" class="kpi-card kpi-card-urgent">
            <span class="kpi-label">Contratos Pendentes</span>
            <span class="kpi-value"><?php echo htmlspecialchars($contratos_pendentes_assinatura); ?></span>
            <span class="kpi-action-link">Gerenciar Contratos</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/usuarios/index.php?tipo=corretor" class="kpi-card">
            <span class="kpi-label">Corretores Cadastrados</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_corretores_cadastrados); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/imobiliarias/index.php" class="kpi-card">
            <span class="kpi-label">Imobiliárias Cadastradas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_imobiliarias_cadastradas); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/leads/index.php" class="kpi-card">
            <span class="kpi-label">Leads Gerados</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_leads_gerados); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/vendas/index.php" class="kpi-card">
            <span class="kpi-label">VGV Gerado</span>
            <span class="kpi-value"><?php echo format_currency_brl($vgv_gerado); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/alertas/index.php" class="kpi-card kpi-card-urgent">
            <span class="kpi-label">Alertas Não Lidos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($alertas_nao_lidos_admin); ?></span>
            <span class="kpi-action-link">Ver Alertas</span>
        </a>
    </div>

    <div class="dashboard-analytics-grid">
        <div class="analytics-card">
            <h3>Corretores que Mais Venderam</h3>
            <?php if (!empty($top_corretores)): ?>
                <ol class="ranking-list">
                    <?php $pos = 1; foreach ($top_corretores as $corretor): ?>
                        <li>
                            <span class="ranking-position"><?php echo $pos++; ?>.</span>
                            <a href="<?php echo BASE_URL; ?>admin/usuarios/editar.php?id=<?php echo htmlspecialchars($corretor['id']); ?>" class="ranking-name"><?php echo htmlspecialchars($corretor['nome']); ?></a>
                            <span class="ranking-value"><?php echo htmlspecialchars($corretor['total_vendas']); ?> vendas</span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p>Nenhuma venda registrada para ranking de corretores.</p>
            <?php endif; ?>
        </div>

        <div class="analytics-card">
            <h3>Imobiliárias que Mais Venderam</h3>
            <?php if (!empty($top_imobiliarias)): ?>
                <ol class="ranking-list">
                    <?php $pos = 1; foreach ($top_imobiliarias as $imobiliaria): ?>
                        <li>
                            <span class="ranking-position"><?php echo $pos++; ?>.</span>
                            <a href="<?php echo BASE_URL; ?>admin/imobiliarias/editar.php?id=<?php echo htmlspecialchars($imobiliaria['id']); ?>" class="ranking-name"><?php echo htmlspecialchars($imobiliaria['nome']); ?></a>
                            <span class="ranking-value"><?php echo htmlspecialchars($imobiliaria['total_vendas']); ?> vendas</span>
                            </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p>Nenhuma venda registrada para ranking de imobiliárias.</p>
            <?php endif; ?>
        </div>

        <div class="analytics-card chart-card">
            <h3>Vendas Diárias (Últimos 30 Dias)</h3>
            <canvas id="vendasChart"></canvas>
        </div>

        <div class="analytics-card alerts-card">
            <h3>Últimos Alertas</h3>
            <?php if (!empty($ultimos_alertas)): ?>
                <ul class="alerts-list">
                    <?php foreach ($ultimos_alertas as $alerta): ?>
                        <li class="<?php echo $alerta['lido'] ? 'read' : 'unread'; ?>">
                            <span class="alert-title"><?php echo htmlspecialchars($alerta['titulo']); ?></span>
                            <p class="alert-message"><?php echo htmlspecialchars($alerta['mensagem']); ?></p>
                            <?php if (!empty($alerta['link'])): ?>
                                <a href="<?php echo BASE_URL . htmlspecialchars($alerta['link']); ?>" class="alert-action-link">Ver Detalhes</a>
                            <?php endif; ?>
                            <span class="alert-date"><?php echo date('d/m/Y H:i', strtotime($alerta['data_criacao'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Nenhum alerta recente.</p>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>admin/alertas/index.php" class="view-all-alerts-link">Ver Todos os Alertas</a>
        </div>
    </div>
</div>

<?php
// Inclui o rodapé do dashboard
require_once '../includes/footer_dashboard.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('vendasChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo $chart_labels; ?>,
                datasets: [{
                    label: 'Vendas',
                    data: <?php echo $chart_data_values; ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Garante que as labels do eixo Y sejam inteiras
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Oculta a legenda, já que só tem um dataset
                    }
                }
            }
        });
    }
});
</script>