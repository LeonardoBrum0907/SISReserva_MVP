<?php
// imobiliaria/index.php - Dashboard da Imobiliária

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php'; // Para formatar moeda, datas
require_once '../includes/alerts.php'; // Para contar alertas e criar

// --- Conexão com o Banco de Dados ---
global $conn; // Declara que você vai usar a variável global $conn
try {
    $conn = get_db_connection(); // Chama a função para obter a conexão
} catch (Exception $e) {
    // Se a conexão falhar, logue o erro e exiba uma mensagem amigável ao usuário.
    error_log("Erro crítico na inicialização do DB em imobiliaria/index.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Dashboard da Imobiliária";

// Obter informações do usuário logado
$logged_user_info = get_user_info();

$imobiliaria_id = null;
$imobiliaria_nome = 'Sua Imobiliária';
$errors = []; // Inicializa array de erros para mensagens internas

// Lógica para obter o ID da imobiliária vinculada ao admin_imobiliaria logado
if ($logged_user_info && ($logged_user_info['type'] === 'admin_imobiliaria')) {
    // Buscar o ID da imobiliária onde o 'admin_id' é o ID do usuário logado
    $imobiliaria_data = fetch_single("SELECT id, nome FROM imobiliarias WHERE admin_id = ?", [$logged_user_info['id']], "i");
    if ($imobiliaria_data) {
        $imobiliaria_id = $imobiliaria_data['id'];
        $imobiliaria_nome = $imobiliaria_data['nome'];
    } else {
        $errors[] = "Nenhuma imobiliária encontrada vinculada ao seu usuário. Por favor, contate o suporte.";
        $imobiliaria_id = null; // Garante que não tentará buscar KPIs sem ID
    }
} else {
    // Caso o tipo de usuário não seja 'admin_imobiliaria' apesar do require_permission
    // (isto é um fallback defensivo, não deveria ser alcançado)
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Acesso negado. Tipo de usuário inválido para esta área.'];
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Variáveis para os KPIs
$total_corretores_ativos = 0;
$total_reservas_atribuidas_em_andamento = 0; // Mais específico
$total_reservas_aguardando_aprovacao = 0; // Novas solicitações de corretor
$total_reservas_docs_pendentes = 0; // Documentos pendentes/rejeitados
$total_vendas_concluidas = 0;
$valor_total_vendas_mes = 0;
$comissao_total_imobiliaria_mes = 0; // Comissão da imobiliária no mês
$top_corretores_vendas = [];
$latest_alerts = [];
$chart_labels = '[]';
$chart_sales_data = '[]';
$chart_reserves_data = '[]';

$corretores_ids_da_imobiliaria = []; // Para filtrar por todos os corretores da imobiliária
$imobiliaria_filter_clause = " AND 1=0"; // Default para não retornar nada se não houver corretores
$imobiliaria_filter_params = [];
$imobiliaria_filter_types = "";


if ($imobiliaria_id) { // Só tenta buscar KPIs se uma imobiliária foi encontrada
    try {
        // Obter IDs de todos os corretores vinculados a esta imobiliária
        $sql_corretores_imobiliaria = "SELECT id FROM usuarios WHERE imobiliaria_id = ? AND tipo LIKE 'corretor_%'";
        $corretores_vinculados = fetch_all($sql_corretores_imobiliaria, [$imobiliaria_id], "i");
        
        if (!empty($corretores_vinculados)) {
            foreach ($corretores_vinculados as $corretor) {
                $corretores_ids_da_imobiliaria[] = $corretor['id'];
            }
            // Cria a cláusula IN para as queries principais
            $placeholders = implode(',', array_fill(0, count($corretores_ids_da_imobiliaria), '?'));
            $imobiliaria_filter_clause = " AND r.corretor_id IN ({$placeholders})";
            $imobiliaria_filter_params = $corretores_ids_da_imobiliaria;
            $imobiliaria_filter_types = str_repeat('i', count($corretores_ids_da_imobiliaria));
        }

        // KPI 1: Total de Corretores Ativos da Imobiliária
        $sql_corretores_ativos = "SELECT COUNT(id) AS total FROM usuarios WHERE imobiliaria_id = ? AND aprovado = TRUE AND ativo = TRUE AND tipo LIKE 'corretor_%'";
        $result_corretores = fetch_single($sql_corretores_ativos, [$imobiliaria_id], "i");
        $total_corretores_ativos = $result_corretores['total'] ?? 0;

        // KPI 2: Total de Reservas Atribuídas e Em Andamento (qualquer status não finalizado)
        $sql_reservas_atribuidas = "
            SELECT COUNT(r.id) AS total
            FROM reservas r
            WHERE r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada') {$imobiliaria_filter_clause}
        ";
        $result_reservas_atribuidas = fetch_single($sql_reservas_atribuidas, $imobiliaria_filter_params, $imobiliaria_filter_types);
        $total_reservas_atribuidas_em_andamento = $result_reservas_atribuidas['total'] ?? 0;

        // KPI 3: Reservas Aguardando Aprovação (novas solicitações de corretor ou docs enviados para análise)
        $sql_aguardando_aprovacao = "
            SELECT COUNT(r.id) AS total
            FROM reservas r
            WHERE r.status IN ('solicitada', 'documentos_enviados') {$imobiliaria_filter_clause}
        ";
        $result_aguardando_aprovacao = fetch_single($sql_aguardando_aprovacao, $imobiliaria_filter_params, $imobiliaria_filter_types);
        $total_reservas_aguardando_aprovacao = $result_aguardando_aprovacao['total'] ?? 0;

        // KPI 4: Reservas com Documentos Pendentes (documentos que precisam ser enviados ou corrigidos pelo corretor/cliente)
        $sql_docs_pendentes = "
            SELECT COUNT(r.id) AS total
            FROM reservas r
            WHERE r.status IN ('documentos_pendentes', 'documentos_rejeitados') {$imobiliaria_filter_clause}
        ";
        $result_docs_pendentes = fetch_single($sql_docs_pendentes, $imobiliaria_filter_params, $imobiliaria_filter_types);
        $total_reservas_docs_pendentes = $result_docs_pendentes['total'] ?? 0;

        // KPI 5: Total de Vendas Concluídas
        $sql_vendas_concluidas = "
            SELECT COUNT(r.id) AS total
            FROM reservas r
            WHERE r.status = 'vendida' {$imobiliaria_filter_clause}
        ";
        $result_vendas_concluidas = fetch_single($sql_vendas_concluidas, $imobiliaria_filter_params, $imobiliaria_filter_types);
        $total_vendas_concluidas = $result_vendas_concluidas['total'] ?? 0;

        // KPI 6: Valor Total de Vendas no Mês Atual
        $current_month_start = date('Y-m-01 00:00:00');
        $current_month_end = date('Y-m-t 23:59:59');
        $sql_valor_vendas_mes = "
            SELECT COALESCE(SUM(r.valor_reserva), 0) AS total_valor_mes
            FROM reservas r
            WHERE r.status = 'vendida'
            AND r.data_ultima_interacao >= ? AND r.data_ultima_interacao <= ? {$imobiliaria_filter_clause}
        ";
        $params_valor_vendas_mes = array_merge([$current_month_start, $current_month_end], $imobiliaria_filter_params);
        $types_valor_vendas_mes = "ss" . $imobiliaria_filter_types;
        $result_valor_vendas_mes = fetch_single($sql_valor_vendas_mes, $params_valor_vendas_mes, $types_valor_vendas_mes);
        $valor_total_vendas_mes = $result_valor_vendas_mes['total_valor_mes'] ?? 0;

        // KPI 7: Comissão Total da Imobiliária no Mês Atual
        $sql_comissao_imobiliaria_mes = "
            SELECT COALESCE(SUM(r.comissao_imobiliaria), 0) AS total_comissao
            FROM reservas r
            WHERE r.status = 'vendida'
            AND r.data_ultima_interacao >= ? AND r.data_ultima_interacao <= ? {$imobiliaria_filter_clause}
        ";
        $params_comissao_imobiliaria_mes = array_merge([$current_month_start, $current_month_end], $imobiliaria_filter_params);
        $types_comissao_imobiliaria_mes = "ss" . $imobiliaria_filter_types;
        $result_comissao_imobiliaria_mes = fetch_single($sql_comissao_imobiliaria_mes, $params_comissao_imobiliaria_mes, $types_comissao_imobiliaria_mes);
        $comissao_total_imobiliaria_mes = $result_comissao_imobiliaria_mes['total_comissao'] ?? 0;


        // Ranking: Corretores que Mais Venderam (Top 5) para esta imobiliária
        $sql_top_corretores = "
            SELECT
                u.nome AS corretor_nome,
                COUNT(r.id) AS total_vendas
            FROM reservas r
            JOIN usuarios u ON r.corretor_id = u.id
            WHERE r.status = 'vendida' {$imobiliaria_filter_clause}
            GROUP BY u.id, u.nome
            ORDER BY total_vendas DESC
            LIMIT 5
        ";
        // Ajusta os parâmetros para o ranking (já incluídos no imobiliaria_filter_params)
        $top_corretores_vendas = fetch_all($sql_top_corretores, $imobiliaria_filter_params, $imobiliaria_filter_types);


        // Últimos alertas não lidos para o admin da imobiliária
        $latest_alerts = get_user_alerts($logged_user_info['id'], false, 5, 0); // Pega os 5 últimos não lidos


        // Dados para o Gráfico de Vendas e Reservas (Últimos 7 dias, como exemplo)
        $data_hoje = new DateTime();
        $labels_chart = [];
        $sales_chart_data = [];
        $reserves_chart_data = [];

        for ($i = 6; $i >= 0; $i--) { // Últimos 7 dias
            $data_dia = (clone $data_hoje)->modify("-{$i} days");
            $data_inicio_dia = $data_dia->format('Y-m-d 00:00:00');
            $data_fim_dia = $data_dia->format('Y-m-d 23:59:59');

            // Vendas do dia para esta imobiliária
            $sql_sales_day = "
                SELECT COUNT(r.id) AS total
                FROM reservas r
                WHERE r.status = 'vendida'
                AND r.data_ultima_interacao >= ? AND r.data_ultima_interacao <= ? {$imobiliaria_filter_clause}
            ";
            $params_sales_day = array_merge([$data_inicio_dia, $data_fim_dia], $imobiliaria_filter_params);
            $types_sales_day = "ss" . $imobiliaria_filter_types;
            $result_sales_day = fetch_single($sql_sales_day, $params_sales_day, $types_sales_day);
            $sales_chart_data[] = $result_sales_day['total'] ?? 0;

            // Reservas do dia para esta imobiliária (status 'aprovada' ou em andamento)
            $sql_reserves_day = "
                SELECT COUNT(r.id) AS total
                FROM reservas r
                WHERE r.status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica')
                AND r.data_reserva >= ? AND r.data_reserva <= ? {$imobiliaria_filter_clause}
            ";
            $params_reserves_day = array_merge([$data_inicio_dia, $data_fim_dia], $imobiliaria_filter_params);
            $types_reserves_day = "ss" . $imobiliaria_filter_types;
            $result_reserves_day = fetch_single($sql_reserves_day, $params_reserves_day, $types_reserves_day);
            $reserves_chart_data[] = $result_reserves_day['total'] ?? 0;

            $labels_chart[] = $data_dia->format('d/m');
        }

        $chart_labels = json_encode($labels_chart);
        $chart_sales_data = json_encode($sales_chart_data);
        $chart_reserves_data = json_encode($reserves_chart_data);

    } catch (Exception $e) {
        error_log("Erro ao carregar KPIs e dados adicionais da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os indicadores da imobiliária: " . $e->getMessage();
    }
}

// --- Funções para Obter Corretores Pendentes ---
function get_pending_realtors_for_imobiliaria($imobiliaria_id, $limit = 5) {
    global $conn;
    $realtors = [];
    if (!$imobiliaria_id) return [];

    $sql = "SELECT id, nome, email, telefone, cpf, data_cadastro FROM usuarios WHERE imobiliaria_id = ? AND aprovado = FALSE AND ativo = TRUE ORDER BY data_cadastro ASC LIMIT ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $imobiliaria_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $realtors[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Erro ao preparar statement para get_pending_realtors_for_imobiliaria: " . $conn->error);
    }
    return $realtors;
}

$pending_realtors = [];
if ($imobiliaria_id) { // Só busca corretores pendentes se a imobiliária foi identificada
    $pending_realtors = get_pending_realtors_for_imobiliaria($imobiliaria_id, 5); // Limite de 5 para o dashboard
}


// Inclui o cabeçalho do dashboard
require_once '../includes/header_dashboard.php';
?>
<div class="admin-content-wrapper">
    <h2>Dashboard - <?php echo htmlspecialchars($imobiliaria_nome); ?></h2>

    <?php
    // Exibir mensagens de feedback (se houver)
    if (isset($_SESSION['message'])): ?>
        <div class="message-box message-box-<?php echo $_SESSION['message']['type']; ?>">
            <?php echo $_SESSION['message']['text']; ?>
        </div>
        <?php unset($_SESSION['message']); // Limpa a mensagem após exibir
    endif;

    // Exibir erros internos do script
    if (!empty($errors)): ?>
        <div class="message-box message-box-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($latest_alerts)): ?>
        <section class="section-block latest-alerts-section">
            <h3>Últimos Alertas para Você</h3>
            <div class="alerts-container">
                <?php foreach ($latest_alerts as $alert): ?>
                    <div class="alert-item <?php echo $alert['is_read'] ? 'alert-read' : 'alert-unread'; ?>" data-alert-id="<?php echo htmlspecialchars($alert['id']); ?>">
                        <div class="alert-header">
                            <h4><?php echo htmlspecialchars($alert['title']); ?></h4>
                            <span class="alert-date"><?php echo format_datetime_br($alert['created_at']); ?></span>
                        </div>
                        <p class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></p>
                        <div class="alert-actions">
                            <?php if (!empty($alert['link'])): // O link já vem completo de get_user_alerts ?>
                                <a href="<?php echo htmlspecialchars($alert['link']); ?>" class="btn btn-sm btn-info">Ver Detalhes</a>
                            <?php endif; ?>
                            <?php if (!$alert['is_read']): ?>
                                <button class="btn btn-sm btn-secondary mark-read-btn" data-alert-id="<?php echo htmlspecialchars($alert['id']); ?>">Marcar como Lido</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center" style="margin-top: var(--spacing-md);">
                <a href="<?php echo BASE_URL; ?>admin/alertas/index.php" class="btn btn-outline-primary">Ver Todos os Alertas</a>
            </div>
        </section>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Corretores Ativos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_corretores_ativos); ?></span>
            <a href="<?php echo BASE_URL; ?>imobiliaria/corretores/index.php?status=aprovado" class="kpi-action-link">Ver Corretores</a>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Reservas Ativas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_atribuidas_em_andamento); ?></span>
            <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/index.php?status=em_andamento" class="kpi-action-link">Ver Reservas</a>
        </div>
        <div class="kpi-card kpi-card-warning">
            <span class="kpi-label">Aguardando Aprovação</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_aguardando_aprovacao); ?></span>
            <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/index.php?status=solicitada" class="kpi-action-link">Ver Pedidos</a>
        </div>
        <div class="kpi-card kpi-card-info">
            <span class="kpi-label">Documentos Pendentes</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_docs_pendentes); ?></span>
            <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/index.php?status=documentos_pendentes" class="kpi-action-link">Ver Docs</a>
        </div>
        <div class="kpi-card kpi-card-success">
            <span class="kpi-label">Vendas Concluídas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_vendas_concluidas); ?></span>
            <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/index.php?status=vendida" class="kpi-action-link">Ver Vendas</a>
        </div>
        <div class="kpi-card kpi-card-primary">
            <span class="kpi-label">Valor Vendas Mês</span>
            <span class="kpi-value"><?php echo format_currency_brl($valor_total_vendas_mes); ?></span>
            <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/index.php?status=vendida&periodo=mes_atual" class="kpi-action-link">Ver Detalhes</a>
        </div>
        <div class="kpi-card kpi-card-primary">
            <span class="kpi-label">Comissão Imobiliária Mês</span>
            <span class="kpi-value"><?php echo format_currency_brl($comissao_total_imobiliaria_mes); ?></span>
            <small>Gerada pelos corretores</small>
        </div>
    </div>

    <div class="dashboard-analytics-grid">
        <div class="analytics-card">
            <h3>Corretores Top Vendas</h3>
            <?php if (!empty($top_corretores_vendas)): ?>
            <ol class="ranking-list">
                <?php $pos = 1; foreach ($top_corretores_vendas as $corretor_venda): ?>
                <li>
                    <span class="ranking-position">#<?php echo $pos++; ?>.</span>
                    <span class="ranking-name"><?php echo htmlspecialchars($corretor_venda['corretor_nome']); ?></span>
                    <span class="ranking-value"><?php echo htmlspecialchars($corretor_venda['total_vendas']); ?> vendas</span>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php else: ?>
                <p>Nenhuma venda registrada pelos corretores da imobiliária ainda.</p>
            <?php endif; ?>
        </div>

        <div class="analytics-card chart-card">
            <h3>Vendas e Reservas (Últimos 7 Dias)</h3>
            <canvas id="salesReservesChart"></canvas>
        </div>
    </div>

    <section class="section-block">
        <h3>Solicitações de Corretores Pendentes</h3>
        <?php if (!empty($pending_realtors)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>CPF</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_realtors as $realtor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($realtor['id']); ?></td>
                                <td><?php echo htmlspecialchars($realtor['nome']); ?></td>
                                <td><?php echo htmlspecialchars($realtor['email']); ?></td>
                                <td><?php echo htmlspecialchars(format_whatsapp($realtor['telefone'])); ?></td>
                                <td><?php echo htmlspecialchars(format_cpf($realtor['cpf'])); ?></td>
                                <td><?php echo htmlspecialchars(format_datetime_br($realtor['data_cadastro'])); ?></td>
                                <td>
                                    <button class="btn btn-success btn-sm approve-realtor" data-id="<?php echo $realtor['id']; ?>" data-nome="<?php echo htmlspecialchars($realtor['nome']); ?>">Aprovar</button>
                                    <button class="btn btn-danger btn-sm reject-realtor" data-id="<?php echo $realtor['id']; ?>" data-nome="<?php echo htmlspecialchars($realtor['nome']); ?>">Rejeitar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center" style="margin-top: var(--spacing-md);">
                <a href="<?php echo BASE_URL; ?>imobiliaria/corretores/index.php?status=pendente" class="btn btn-primary">Ver Todas as Solicitações</a>
            </div>
        <?php else: ?>
            <p>Nenhuma solicitação de corretor pendente no momento.</p>
        <?php endif; ?>
    </section>
</div>

<?php
// Passa os dados dos gráficos para o JavaScript (já presente no seu código original)
// Assegure-se que o footer_dashboard.php inclua o admin.js e chart.js para isso.
?>
<script>
    window.chartLabels = <?php echo $chart_labels; ?>;
    window.chartSalesData = <?php echo $chart_sales_data; ?>;
    window.chartReservesData = <?php echo $chart_reserves_data; ?>;
</script>

<?php require_once '../includes/footer_dashboard.php'; ?>