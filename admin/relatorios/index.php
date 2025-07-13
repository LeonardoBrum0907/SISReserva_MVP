<?php
// admin/relatorios/index.php - Painel de Relatórios (Admin Master)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação
require_once '../../includes/alerts.php'; // Para alertas, se necessário

// Inicializar a conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Falha ao iniciar conexão com o DB em admin/relatorios/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

require_permission(['admin']); // Apenas Admin Master pode acessar relatórios

// ======================================================================================================
// Lógica de Exportação CSV (Não alterada, apenas para contexto)
// ======================================================================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $report_type = filter_input(INPUT_GET, 'report', FILTER_SANITIZE_STRING);
    $filename = "relatorio_" . $report_type . "_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // Adiciona BOM para UTF-8 no Excel

    switch ($report_type) {
        case 'vendas_periodo':
            fputcsv($output, ['Data', 'Total de Vendas', 'Valor Total (R$)']);
            $filter_vendas_start_date_export = filter_input(INPUT_GET, 'vendas_start_date', FILTER_SANITIZE_STRING) ?? date('Y-m-01');
            $filter_vendas_end_date_export = filter_input(INPUT_GET, 'vendas_end_date', FILTER_SANITIZE_STRING) ?? date('Y-m-t');
            $filter_vendas_corretor_id_export = filter_input(INPUT_GET, 'vendas_corretor_id', FILTER_VALIDATE_INT);
            $filter_vendas_imobiliaria_id_export = filter_input(INPUT_GET, 'vendas_imobiliaria_id', FILTER_VALIDATE_INT);

            $sql_export = "
                SELECT
                    DATE(r.data_ultima_interacao) AS data_venda,
                    SUM(r.valor_reserva) AS total_valor,
                    COUNT(r.id) AS total_vendas
                FROM
                    reservas r
                WHERE
                    r.status = 'vendida'
                    AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
            ";
            $params_export = [$filter_vendas_start_date_export, $filter_vendas_end_date_export];
            $types_export = "ss";

            if ($filter_vendas_corretor_id_export) {
                $sql_export .= " AND r.corretor_id = ?";
                $params_export[] = $filter_vendas_corretor_id_export;
                $types_export .= "i";
            }
            if ($filter_vendas_imobiliaria_id_export) {
                $corretores_da_imobiliaria_export = fetch_all("SELECT id FROM usuarios WHERE imobiliaria_id = ?", [$filter_vendas_imobiliaria_id_export], "i");
                if (!empty($corretores_da_imobiliaria_export)) {
                    $ids_export = array_column($corretores_da_imobiliaria_export, 'id');
                    $placeholders_export = implode(',', array_fill(0, count($ids_export), '?'));
                    $sql_export .= " AND r.corretor_id IN ({$placeholders_export})";
                    $params_export = array_merge($params_export, $ids_export);
                    $types_export .= str_repeat('i', count($ids_export));
                } else {
                    $sql_export .= " AND 1=0";
                }
            }
            $sql_export .= " GROUP BY data_venda ORDER BY data_venda ASC";
            $data_to_export = fetch_all($sql_export, $params_export, $types_export);
            foreach ($data_to_export as $row) {
                fputcsv($output, [
                    format_datetime_br($row['data_venda']), // Usar format_date_br para a data
                    $row['total_vendas'],
                    number_format($row['total_valor'], 2, ',', '.') // Formato para Excel com vírgula decimal
                ]);
            }
            break;

        case 'status_unidades':
            fputcsv($output, ['Empreendimento', 'Disponíveis', 'Reservadas', 'Vendidas', 'Total de Unidades']);
            $filter_status_empreendimento_id_export = filter_input(INPUT_GET, 'status_empreendimento_id', FILTER_VALIDATE_INT);

            $sql_export = "
                SELECT
                    e.nome AS empreendimento_nome,
                    u.status AS unidade_status,
                    COUNT(u.id) AS total_unidades
                FROM
                    unidades u
                JOIN
                    empreendimentos e ON u.empreendimento_id = e.id
            ";
            $params_export = [];
            $types_export = "";
            if ($filter_status_empreendimento_id_export) {
                $sql_export .= " WHERE e.id = ?";
                $params_export[] = $filter_status_empreendimento_id_export;
                $types_export .= "i";
            }
            $sql_export .= " GROUP BY e.nome, u.status ORDER BY e.nome, u.status ASC";
            $raw_export_data = fetch_all($sql_export, $params_export, $types_export);

            $consolidated_export_data = [];
            foreach ($raw_export_data as $row) {
                if (!isset($consolidated_export_data[$row['empreendimento_nome']])) {
                    $consolidated_export_data[$row['empreendimento_nome']] = ['disponivel' => 0, 'reservada' => 0, 'vendida' => 0, 'total' => 0];
                }
                $consolidated_export_data[$row['empreendimento_nome']][$row['unidade_status']] = $row['total_unidades'];
                $consolidated_export_data[$row['empreendimento_nome']]['total'] += $row['total_unidades'];
            }
            foreach ($consolidated_export_data as $emp_name => $data) {
                fputcsv($output, [
                    $emp_name,
                    $data['disponivel'],
                    $data['reservada'],
                    $data['vendida'],
                    $data['total']
                ]);
            }
            break;

        case 'desempenho':
            fputcsv($output, ['Tipo', 'Nome', 'Vendas (Qtd)', 'Vendas (Valor)', 'Reservas Ativas (Qtd)']);
            $filter_desempenho_start_date_export = filter_input(INPUT_GET, 'desempenho_start_date', FILTER_SANITIZE_STRING) ?? date('Y-m-01');
            $filter_desempenho_end_date_export = filter_input(INPUT_GET, 'desempenho_end_date', FILTER_SANITIZE_STRING) ?? date('Y-m-t');

            $sql_corretores_export = "
                SELECT
                    'Corretor' AS tipo,
                    u.nome AS nome,
                    COALESCE(COUNT(CASE WHEN r.status = 'vendida' THEN r.id END), 0) AS total_vendas_qtd,
                    COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.valor_reserva ELSE 0 END), 0) AS total_vendas_valor,
                    COALESCE(COUNT(CASE WHEN r.status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado') THEN r.id END), 0) AS total_reservas_qtd
                FROM
                    usuarios u
                LEFT JOIN
                    reservas r ON u.id = r.corretor_id
                    AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
                WHERE
                    u.tipo LIKE 'corretor_%' AND u.status = 'ativo'
                GROUP BY
                    u.id, u.nome
                ORDER BY
                    total_vendas_valor DESC;
            ";
            $corretores_export = fetch_all($sql_corretores_export, [$filter_desempenho_start_date_export, $filter_desempenho_end_date_export], "ss");
            foreach ($corretores_export as $row) {
                fputcsv($output, [
                    $row['tipo'],
                    $row['nome'],
                    $row['total_vendas_qtd'],
                    number_format($row['total_vendas_valor'], 2, ',', '.'),
                    $row['total_reservas_qtd']
                ]);
            }

            $sql_imobiliarias_export = "
                SELECT
                    'Imobiliária' AS tipo,
                    i.nome AS nome,
                    COALESCE(COUNT(CASE WHEN r.status = 'vendida' THEN r.id END), 0) AS total_vendas_qtd,
                    COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.valor_reserva ELSE 0 END), 0) AS total_vendas_valor,
                    COALESCE(COUNT(CASE WHEN r.status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado') THEN r.id END), 0) AS total_reservas_qtd
                FROM
                    imobiliarias i
                LEFT JOIN
                    usuarios u ON i.id = u.imobiliaria_id
                LEFT JOIN
                    reservas r ON u.id = r.corretor_id
                    AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
                WHERE
                    i.ativa = 1
                GROUP BY
                    i.id, i.nome
                ORDER BY
                    total_vendas_valor DESC;
            ";
            $imobiliarias_export = fetch_all($sql_imobiliarias_export, [$filter_desempenho_start_date_export, $filter_desempenho_end_date_export], "ss");
            foreach ($imobiliarias_export as $row) {
                fputcsv($output, [
                    $row['tipo'],
                    $row['nome'],
                    $row['total_vendas_qtd'],
                    number_format($row['total_vendas_valor'], 2, ',', '.'),
                    $row['total_reservas_qtd']
                ]);
            }
            break;
    }

    fclose($output);
    exit(); // Encerra a execução após a exportação
}

$page_title = "Relatórios do Sistema";

$errors = [];
$success_message = '';

// Recuperar mensagens da sessão, se houverem
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// ======================================================================================================
// Lógica de Backend para os Relatórios (Não alterada)
// ======================================================================================================

// Dados para filtros
$corretores = fetch_all("SELECT id, nome FROM usuarios WHERE tipo LIKE 'corretor_%' AND status = 'ativo' ORDER BY nome ASC");
$imobiliarias = fetch_all("SELECT id, nome FROM imobiliarias WHERE ativa = 1 ORDER BY nome ASC");
$empreendimentos_ativos = fetch_all("SELECT id, nome FROM empreendimentos WHERE status = 'ativo' ORDER BY nome ASC");

// --- 0. LÓGICA PARA BUSCAR KPIs GERAIS ---
$kpis = [
    'total_vendas' => 0,
    'vgv_total' => 0.00,
    'reservas_ativas' => 0,
    'ticket_medio' => 0.00
];
try {
    $kpis['total_vendas'] = fetch_single("SELECT COUNT(id) as total FROM reservas WHERE status = 'vendida'")['total'] ?? 0;
    $kpis['vgv_total'] = fetch_single("SELECT SUM(valor_reserva) as total FROM reservas WHERE status = 'vendida'")['total'] ?? 0.00;
    $kpis['reservas_ativas'] = fetch_single("SELECT COUNT(id) as total FROM reservas WHERE status = 'aprovada'")['total'] ?? 0;
    if ($kpis['total_vendas'] > 0) {
        $kpis['ticket_medio'] = $kpis['vgv_total'] / $kpis['total_vendas'];
    }
} catch (Exception $e) {
    // Silencioso para não quebrar a página, mas loga o erro
    error_log("Erro ao buscar KPIs de Relatórios: " . $e->getMessage());
}

// --- 1. Relatório de Vendas por Período ---
$vendas_por_periodo_data = [];
$vendas_por_periodo_labels = [];
$vendas_por_periodo_total_valor = 0;
$vendas_por_periodo_total_vendas = 0;

$filtro_vendas_inicio = filter_input(INPUT_GET, 'vendas_start_date', FILTER_SANITIZE_SPECIAL_CHARS) ?? date('Y-m-01');
$filtro_vendas_fim = filter_input(INPUT_GET, 'vendas_end_date', FILTER_SANITIZE_SPECIAL_CHARS) ?? date('Y-m-t');

if ($filtro_vendas_inicio && $filtro_vendas_fim) {
    try {
        $sql_vendas = "
            SELECT 
                r.id, r.data_ultima_interacao, r.valor_reserva,
                c.nome as cliente_nome,
                co.nome as corretor_nome,
                e.nome as empreendimento_nome,
                u.numero as unidade_numero
            FROM reservas r
            JOIN clientes c ON r.cliente_id = c.id
            JOIN usuarios co ON r.corretor_id = co.id
            JOIN unidades u ON r.unidade_id = u.id
            JOIN empreendimentos e ON u.empreendimento_id = e.id
            WHERE r.status = 'vendida' 
            AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
            ORDER BY r.data_ultima_interacao DESC
        ";
        $vendas_por_periodo = fetch_all($sql_vendas, [$filtro_vendas_inicio, $filtro_vendas_fim], "ss");
    } catch (Exception $e) {
        error_log("Erro ao gerar relatório de vendas: " . $e->getMessage());
    }
}

$filter_vendas_corretor_id = filter_input(INPUT_GET, 'vendas_corretor_id', FILTER_VALIDATE_INT);
$filter_vendas_imobiliaria_id = filter_input(INPUT_GET, 'vendas_imobiliaria_id', FILTER_VALIDATE_INT);

try {
    $sql_vendas_periodo = "
        SELECT
            DATE(r.data_ultima_interacao) AS data_venda,
            SUM(r.valor_reserva) AS total_valor,
            COUNT(r.id) AS total_vendas
        FROM
            reservas r
        WHERE
            r.status = 'vendida'
            AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
    ";
    $params = [$filter_vendas_start_date, $filter_vendas_end_date];
    $types = "ss";

    if ($filter_vendas_corretor_id) {
        $sql_vendas_periodo .= " AND r.corretor_id = ?";
        $params[] = $filter_vendas_corretor_id;
        $types .= "i";
    }
    if ($filter_vendas_imobiliaria_id) {
        // Precisa verificar os corretores da imobiliária
        $corretores_da_imobiliaria = fetch_all("SELECT id FROM usuarios WHERE imobiliaria_id = ?", [$filter_vendas_imobiliaria_id], "i");
        if (!empty($corretores_da_imobiliaria)) {
            $ids = array_column($corretores_da_imobiliaria, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql_vendas_periodo .= " AND r.corretor_id IN ({$placeholders})";
            $params = array_merge($params, $ids);
            $types .= str_repeat('i', count($ids));
        } else {
            $sql_vendas_periodo .= " AND 1=0"; // Força resultado vazio se não há corretores na imobiliária
        }
    }

    $sql_vendas_periodo .= " GROUP BY data_venda ORDER BY data_venda ASC";
    $raw_vendas_periodo = fetch_all($sql_vendas_periodo, $params, $types);

    // Popula dados para o gráfico e soma totais
    $current_date = new DateTime($filter_vendas_start_date);
    $end_date_obj = new DateTime($filter_vendas_end_date);
    $vendas_map = [];
    foreach ($raw_vendas_periodo as $row) {
        $vendas_map[$row['data_venda']] = ['valor' => $row['total_valor'], 'quantidade' => $row['total_vendas']];
        $vendas_por_periodo_total_valor += $row['total_valor'];
        $vendas_por_periodo_total_vendas += $row['total_vendas'];
    }

    while ($current_date <= $end_date_obj) {
        $date_str = $current_date->format('Y-m-d');
        $vendas_por_periodo_labels[] = $current_date->format('d/m');
        $vendas_por_periodo_data[] = $vendas_map[$date_str]['quantidade'] ?? 0;
        $current_date->modify('+1 day');
    }

} catch (Exception $e) {
    error_log("Erro ao carregar relatório de vendas por período: " . $e->getMessage());
    $errors[] = "Erro ao carregar vendas por período: " . $e->getMessage();
}


// --- 2. Relatório de Status de Unidades por Empreendimento ---
$status_unidades_data = []; // {empreendimento_nome: {disponivel: X, reservada: Y, vendida: Z}}
$filter_status_empreendimento_id = filter_input(INPUT_GET, 'status_empreendimento_id', FILTER_VALIDATE_INT);

try {
    $sql_status_unidades = "
        SELECT
            e.nome AS empreendimento_nome,
            u.status AS unidade_status,
            COUNT(u.id) AS total_unidades
        FROM
            unidades u
        JOIN
            empreendimentos e ON u.empreendimento_id = e.id
    ";
    $params_status = [];
    $types_status = "";

    if ($filter_status_empreendimento_id) {
        $sql_status_unidades .= " WHERE e.id = ?";
        $params_status[] = $filter_status_empreendimento_id;
        $types_status .= "i";
    }
    $sql_status_unidades .= " GROUP BY e.nome, u.status ORDER BY e.nome, u.status ASC";
    $raw_status_unidades = fetch_all($sql_status_unidades, $params_status, $types_status);

    foreach ($raw_status_unidades as $row) {
        if (!isset($status_unidades_data[$row['empreendimento_nome']])) {
            $status_unidades_data[$row['empreendimento_nome']] = ['disponivel' => 0, 'reservada' => 0, 'vendida' => 0, 'total' => 0];
        }
        $status_unidades_data[$row['empreendimento_nome']][$row['unidade_status']] = $row['total_unidades'];
        $status_unidades_data[$row['empreendimento_nome']]['total'] += $row['total_unidades'];
    }

} catch (Exception $e) {
    error_log("Erro ao carregar relatório de status de unidades: " . $e->getMessage());
    $errors[] = "Erro ao carregar status de unidades: " . $e->getMessage();
}


// --- 3. Relatório de Desempenho de Corretores/Imobiliárias ---
$desempenho_corretores = [];
$desempenho_imobiliarias = [];

$filter_desempenho_start_date = filter_input(INPUT_GET, 'desempenho_start_date', FILTER_SANITIZE_STRING) ?? date('Y-m-01');
$filter_desempenho_end_date = filter_input(INPUT_GET, 'desempenho_end_date', FILTER_SANITIZE_STRING) ?? date('Y-m-t');

try {
    // Desempenho de Corretores
    $sql_desempenho_corretores = "
        SELECT
            u.id AS corretor_id,
            u.nome AS corretor_nome,
            COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.valor_reserva ELSE 0 END), 0) AS total_vendas_valor,
            COALESCE(COUNT(CASE WHEN r.status = 'vendida' THEN r.id END), 0) AS total_vendas_qtd,
            COALESCE(COUNT(CASE WHEN r.status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado') THEN r.id END), 0) AS total_reservas_qtd
        FROM
            usuarios u
        LEFT JOIN
            reservas r ON u.id = r.corretor_id
            AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
        WHERE
            u.tipo LIKE 'corretor_%' AND u.status = 'ativo'
        GROUP BY
            u.id, u.nome
        ORDER BY
            total_vendas_valor DESC;
    ";
    $desempenho_corretores = fetch_all($sql_desempenho_corretores, [$filter_desempenho_start_date, $filter_desempenho_end_date], "ss");

    // Desempenho de Imobiliárias
    $sql_desempenho_imobiliarias = "
        SELECT
            i.id AS imobiliaria_id,
            i.nome AS imobiliaria_nome,
            COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.valor_reserva ELSE 0 END), 0) AS total_vendas_valor,
            COALESCE(COUNT(CASE WHEN r.status = 'vendida' THEN r.id END), 0) AS total_vendas_qtd,
            COALESCE(COUNT(CASE WHEN r.status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado') THEN r.id END), 0) AS total_reservas_qtd
        FROM
            imobiliarias i
        LEFT JOIN
            usuarios u ON i.id = u.imobiliaria_id
        LEFT JOIN
            reservas r ON u.id = r.corretor_id
            AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
        WHERE
            i.ativa = 1
        GROUP BY
            i.id, i.nome
        ORDER BY
            total_vendas_valor DESC;
    ";
    $desempenho_imobiliarias = fetch_all($sql_desempenho_imobiliarias, [$filter_desempenho_start_date, $filter_desempenho_end_date], "ss");

} catch (Exception $e) {
    error_log("Erro ao carregar relatório de desempenho: " . $e->getMessage());
    $errors[] = "Erro ao carregar desempenho: " . $e->getMessage();
}


require_once '../../includes/header_dashboard.php';
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

    <?php if (!empty($success_message)): ?>
        <div class="message-box message-box-success">
            <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Total de Vendas (Histórico)</span>
            <span class="kpi-value"><?php echo number_format($kpis['total_vendas']); ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">VGV Total (R$)</span>
            <span class="kpi-value"><?php echo format_currency_brl($kpis['vgv_total']); ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Reservas Ativas</span>
            <span class="kpi-value"><?php echo number_format($kpis['reservas_ativas']); ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Ticket Médio de Venda (R$)</span>
            <span class="kpi-value"><?php echo format_currency_brl($kpis['ticket_medio']); ?></span>
        </div>
    </div>

    <div class="report-section">
        <h3>Vendas por Período</h3>
        <form class="filters-bar" method="GET" action="">
            <input type="hidden" name="report" value="vendas_periodo">
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="vendas_start_date">Data Início:</label>
                    <input type="date" id="vendas_start_date" name="vendas_start_date" class="form-control" value="<?php echo htmlspecialchars($filter_vendas_start_date); ?>">
                </div>
                <div class="form-group">
                    <label for="vendas_end_date">Data Fim:</label>
                    <input type="date" id="vendas_end_date" name="vendas_end_date" class="form-control" value="<?php echo htmlspecialchars($filter_vendas_end_date); ?>">
                </div>
                <div class="form-group">
                    <label for="vendas_corretor_id">Corretor:</label>
                    <select id="vendas_corretor_id" name="vendas_corretor_id" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($corretores as $corretor): ?>
                            <option value="<?php echo htmlspecialchars($corretor['id']); ?>" <?php echo ($filter_vendas_corretor_id == $corretor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($corretor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="vendas_imobiliaria_id">Imobiliária:</label>
                    <select id="vendas_imobiliaria_id" name="vendas_imobiliaria_id" class="form-control">
                        <option value="">Todas</option>
                        <?php foreach ($imobiliarias as $imobiliaria): ?>
                            <option value="<?php echo htmlspecialchars($imobiliaria['id']); ?>" <?php echo ($filter_vendas_imobiliaria_id == $imobiliaria['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($imobiliaria['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions" style="justify-content: flex-start; margin-top: var(--spacing-md);">
                <button type="submit" class="btn btn-primary">Gerar Relatório</button>
                <button type="button" class="btn btn-info export-report-btn" data-report-type="vendas_periodo">Exportar CSV</button>
            </div>
        </form>

        <div class="report-summary mt-lg">
            <p><strong>Total de Vendas no Período:</strong> <?php echo htmlspecialchars($vendas_por_periodo_total_vendas); ?></p>
            <p><strong>Valor Total Vendido:</strong> <?php echo format_currency_brl($vendas_por_periodo_total_valor); ?></p>
        </div>

        <div class="chart-container" style="height: 350px;">
            <canvas id="vendasPeriodoChart"></canvas>
        </div>
    </div>

    <div class="report-section mt-2xl">
        <h3>Status de Unidades por Empreendimento</h3>
        <form class="filters-bar" method="GET" action="">
            <input type="hidden" name="report" value="status_unidades">
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="status_empreendimento_id">Empreendimento:</label>
                    <select id="status_empreendimento_id" name="status_empreendimento_id" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($empreendimentos_ativos as $emp): ?>
                            <option value="<?php echo htmlspecialchars($emp['id']); ?>" <?php echo ($filter_status_empreendimento_id == $emp['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions" style="justify-content: flex-start; margin-top: var(--spacing-md);">
                <button type="submit" class="btn btn-primary">Gerar Relatório</button>
                <button type="button" class="btn btn-info export-report-btn" data-report-type="status_unidades">Exportar CSV</button>
            </div>
        </form>

        <div class="admin-table-responsive mt-lg">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Empreendimento</th>
                        <th>Disponíveis</th>
                        <th>Reservadas</th>
                        <th>Vendidas</th>
                        <th>Total de Unidades</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($status_unidades_data)): ?>
                        <tr><td colspan="5" style="text-align: center;">Nenhum dado encontrado para o status das unidades.</td></tr>
                    <?php else: ?>
                        <?php foreach ($status_unidades_data as $emp_nome => $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp_nome); ?></td>
                                <td><?php echo htmlspecialchars($data['disponivel']); ?></td>
                                <td><?php echo htmlspecialchars($data['reservada']); ?></td>
                                <td><?php echo htmlspecialchars($data['vendida']); ?></td>
                                <td><?php echo htmlspecialchars($data['total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="chart-container" style="height: 350px; margin-top: var(--spacing-xl);">
            <canvas id="statusUnidadesChart"></canvas>
        </div>
    </div>

    <div class="report-section mt-2xl">
        <h3>Desempenho de Corretores e Imobiliárias</h3>
        <form class="filters-bar" method="GET" action="">
            <input type="hidden" name="report" value="desempenho">
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="desempenho_start_date">Data Início:</label>
                    <input type="date" id="desempenho_start_date" name="desempenho_start_date" class="form-control" value="<?php echo htmlspecialchars($filter_desempenho_start_date); ?>">
                </div>
                <div class="form-group">
                    <label for="desempenho_end_date">Data Fim:</label>
                    <input type="date" id="desempenho_end_date" name="desempenho_end_date" class="form-control" value="<?php echo htmlspecialchars($filter_desempenho_end_date); ?>">
                </div>
            </div>
            <div class="form-actions" style="justify-content: flex-start; margin-top: var(--spacing-md);">
                <button type="submit" class="btn btn-primary">Gerar Relatório</button>
                <button type="button" class="btn btn-info export-report-btn" data-report-type="desempenho">Exportar CSV</button>
            </div>
        </form>

        <h4 class="mt-lg">Desempenho por Corretor</h4>
        <div class="admin-table-responsive">
            <table class="admin-table" id="desempenhoCorretoresTable">
                <thead>
                    <tr>
                        <th>Corretor</th>
                        <th>Vendas (Qtd)</th>
                        <th>Vendas (Valor)</th>
                        <th>Reservas Ativas (Qtd)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($desempenho_corretores)): ?>
                        <tr><td colspan="4" style="text-align: center;">Nenhum dado de desempenho de corretor encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($desempenho_corretores as $corretor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($corretor['corretor_nome']); ?></td>
                                <td><?php echo htmlspecialchars($corretor['total_vendas_qtd']); ?></td>
                                <td><?php echo format_currency_brl($corretor['total_vendas_valor']); ?></td>
                                <td><?php echo htmlspecialchars($corretor['total_reservas_qtd']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h4 class="mt-2xl">Desempenho por Imobiliária</h4>
        <div class="admin-table-responsive">
            <table class="admin-table" id="desempenhoImobiliariasTable">
                <thead>
                    <tr>
                        <th>Imobiliária</th>
                        <th>Vendas (Qtd)</th>
                        <th>Vendas (Valor)</th>
                        <th>Reservas Ativas (Qtd)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($desempenho_imobiliarias)): ?>
                        <tr><td colspan="4" style="text-align: center;">Nenhum dado de desempenho de imobiliária encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($desempenho_imobiliarias as $imobiliaria): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($imobiliaria['imobiliaria_nome']); ?></td>
                                <td><?php echo htmlspecialchars($imobiliaria['total_vendas_qtd']); ?></td>
                                <td><?php echo format_currency_brl($imobiliaria['total_vendas_valor']); ?></td>
                                <td><?php echo htmlspecialchars($imobiliaria['total_reservas_qtd']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
// Passa os dados dos gráficos para o JavaScript
$vendas_periodo_data_json = json_encode($vendas_por_periodo_data);
$vendas_periodo_labels_json = json_encode($vendas_por_periodo_labels);

// Para o gráfico de Status de Unidades, preparamos os dados.
// Se não houver filtro, mostrará todos os empreendimentos. Se houver, apenas o selecionado.
// Para fins de demonstração, vamos focar no primeiro empreendimento retornado,
// ou consolidar se houver vários e não tiver filtro.
$status_unidades_chart_labels = [];
$status_unidades_chart_data_disponivel = [];
$status_unidades_chart_data_reservada = [];
$status_unidades_chart_data_vendida = [];

foreach ($status_unidades_data as $emp_name => $data) {
    $status_unidades_chart_labels[] = $emp_name;
    $status_unidades_chart_data_disponivel[] = $data['disponivel'];
    $status_unidades_chart_data_reservada[] = $data['reservada'];
    $status_unidades_chart_data_vendida[] = $data['vendida'];
}

$status_unidades_chart_labels_json = json_encode($status_unidades_chart_labels);
$status_unidades_chart_data_disponivel_json = json_encode($status_unidades_chart_data_disponivel);
$status_unidades_chart_data_reservada_json = json_encode($status_unidades_chart_data_reservada);
$status_unidades_chart_data_vendida_json = json_encode($status_unidades_chart_data_vendida);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Dados para o gráfico de Vendas por Período
    window.vendasPeriodoChartLabels = <?php echo $vendas_periodo_labels_json; ?>;
    window.vendasPeriodoChartData = <?php echo $vendas_periodo_data_json; ?>;

    // Dados para o gráfico de Status de Unidades
    window.statusUnidadesChartLabels = <?php echo $status_unidades_chart_labels_json; ?>;
    window.statusUnidadesChartDataDisponivel = <?php echo $status_unidades_chart_data_disponivel_json; ?>;
    window.statusUnidadesChartDataReservada = <?php echo $status_unidades_chart_data_reservada_json; ?>;
    window.statusUnidadesChartDataVendida = <?php echo $status_unidades_chart_data_vendida_json; ?>;
</script>

<?php require_once '../../includes/footer_dashboard.php'; ?>