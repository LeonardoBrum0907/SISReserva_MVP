<?php
// imobiliaria/relatorios/index.php - Painel de Relatórios da Imobiliária

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação
require_once '../../includes/alerts.php'; // Para alertas, se necessário

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/relatorios/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Relatórios da Equipe";

$logged_user_info = get_user_info();
$imobiliaria_id = $logged_user_info['imobiliaria_id'] ?? null;

$errors = [];
$success_message = '';

// Lida com mensagens da sessão (se houverem)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// --- Lógica para Obter IDs de Corretores da Imobiliária (para filtros) ---
$corretores_ids_da_imobiliaria = [];
$imobiliaria_filter_clause = " AND 1=0"; // Default para não retornar nada se não houver corretores
$imobiliaria_filter_params_base = []; // Parâmetros apenas para o filtro de imobiliária (IDs dos corretores)
$imobiliaria_filter_types_base = "";

if ($imobiliaria_id) {
    try {
        $sql_corretores_imobiliaria = "SELECT id FROM usuarios WHERE imobiliaria_id = ? AND tipo LIKE 'corretor_%'";
        $corretores_vinculados = fetch_all($sql_corretores_imobiliaria, [$imobiliaria_id], "i");
        
        if (!empty($corretores_vinculados)) {
            foreach ($corretores_vinculados as $corretor) {
                $corretores_ids_da_imobiliaria[] = $corretor['id'];
            }
            $placeholders = implode(',', array_fill(0, count($corretores_ids_da_imobiliaria), '?'));
            $imobiliaria_filter_clause = " AND r.corretor_id IN ({$placeholders})";
            $imobiliaria_filter_params_base = $corretores_ids_da_imobiliaria;
            $imobiliaria_filter_types_base = str_repeat('i', count($corretores_ids_da_imobiliaria));
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar corretores da imobiliária para filtro: " . $e->getMessage());
        $errors[] = "Erro ao carregar dados dos corretores da sua imobiliária para filtros.";
    }
}


// ======================================================================================================
// Lógica de Exportação CSV
// ======================================================================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $report_type = filter_input(INPUT_GET, 'report', FILTER_UNSAFE_RAW);
    $filename = "relatorio_imobiliaria_" . $report_type . "_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // Adiciona BOM para UTF-8 no Excel

    switch ($report_type) {
        case 'vendas_periodo':
            fputcsv($output, ['Data', 'Total de Vendas', 'Valor Total (R$)', 'Comissão Imobiliária (R$)', 'Comissão Corretores (R$)']);
            $filter_vendas_start_date_export = filter_input(INPUT_GET, 'vendas_start_date', FILTER_UNSAFE_RAW) ?? date('Y-m-01');
            $filter_vendas_end_date_export = filter_input(INPUT_GET, 'vendas_end_date', FILTER_UNSAFE_RAW) ?? date('Y-m-t');
            $filter_vendas_corretor_id_export = filter_input(INPUT_GET, 'vendas_corretor_id', FILTER_VALIDATE_INT);

            $sql_export = "
                SELECT
                    DATE(r.data_ultima_interacao) AS data_venda,
                    COUNT(r.id) AS total_vendas,
                    SUM(r.valor_reserva) AS total_valor,
                    SUM(r.comissao_imobiliaria) AS total_comissao_imobiliaria,
                    SUM(r.comissao_corretor) AS total_comissao_corretor
                FROM
                    reservas r
                WHERE
                    r.status = 'vendida'
                    AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
                    {$imobiliaria_filter_clause} -- Filtro por corretores da imobiliária
            ";
            $params_export = array_merge([$filter_vendas_start_date_export, $filter_vendas_end_date_export], $imobiliaria_filter_params_base);
            $types_export = "ss" . $imobiliaria_filter_types_base;

            if ($filter_vendas_corretor_id_export) {
                $sql_export .= " AND r.corretor_id = ?";
                $params_export[] = $filter_vendas_corretor_id_export;
                $types_export .= "i";
            }
            $sql_export .= " GROUP BY data_venda ORDER BY data_venda ASC";
            $data_to_export = fetch_all($sql_export, $params_export, $types_export);
            foreach ($data_to_export as $row) {
                fputcsv($output, [
                    format_datetime_br($row['data_venda']), // Usar format_date_br para a data
                    $row['total_vendas'],
                    number_format($row['total_valor'], 2, ',', '.'),
                    number_format($row['total_comissao_imobiliaria'], 2, ',', '.'),
                    number_format($row['total_comissao_corretor'], 2, ',', '.')
                ]);
            }
            break;

        case 'status_unidades':
            fputcsv($output, ['Empreendimento', 'Unidade', 'Andar', 'Tipo de Unidade', 'Valor', 'Status']);
            $filter_status_empreendimento_id_export = filter_input(INPUT_GET, 'status_empreendimento_id', FILTER_VALIDATE_INT);

            $sql_export = "
                SELECT
                    e.nome AS empreendimento_nome,
                    u.numero AS unidade_numero,
                    u.andar AS unidade_andar,
                    tu.tipo AS tipo_unidade_nome,
                    u.valor,
                    u.status AS unidade_status
                FROM
                    unidades u
                JOIN
                    empreendimentos e ON u.empreendimento_id = e.id
                LEFT JOIN
                    tipos_unidades tu ON u.tipo_unidade_id = tu.id
                JOIN
                    reservas r ON u.id = r.unidade_id -- Garante que a unidade tem uma reserva
                WHERE
                    1=1 {$imobiliaria_filter_clause} -- Filtra por unidades associadas a corretores da imobiliária
            ";
            $params_export = $imobiliaria_filter_params_base;
            $types_export = $imobiliaria_filter_types_base;

            if ($filter_status_empreendimento_id_export) {
                $sql_export .= " AND e.id = ?";
                $params_export[] = $filter_status_empreendimento_id_export;
                $types_export .= "i";
            }
            $sql_export .= " GROUP BY u.id ORDER BY e.nome, u.andar, u.numero ASC"; // GROUP BY u.id para evitar duplicação por reservas

            $data_to_export = fetch_all($sql_export, $params_export, $types_export);
            foreach ($data_to_export as $row) {
                fputcsv($output, [
                    $row['empreendimento_nome'],
                    $row['unidade_numero'],
                    $row['unidade_andar'],
                    $row['tipo_unidade_nome'],
                    number_format($row['valor'], 2, ',', '.'),
                    ucfirst($row['unidade_status'])
                ]);
            }
            break;

        case 'desempenho':
            fputcsv($output, ['Nome Corretor', 'Vendas (Qtd)', 'Vendas (Valor R$)', 'Comissão Recebida R$', 'Reservas Ativas (Qtd)']);
            $filter_desempenho_start_date_export = filter_input(INPUT_GET, 'desempenho_start_date', FILTER_UNSAFE_RAW) ?? date('Y-m-01');
            $filter_desempenho_end_date_export = filter_input(INPUT_GET, 'desempenho_end_date', FILTER_UNSAFE_RAW) ?? date('Y-m-t');

            $sql_corretores_export = "
                SELECT
                    u.nome AS nome_corretor,
                    COALESCE(COUNT(CASE WHEN r.status = 'vendida' THEN r.id END), 0) AS total_vendas_qtd,
                    COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.valor_reserva ELSE 0 END), 0) AS total_vendas_valor,
                    COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.comissao_corretor ELSE 0 END), 0) AS comissao_recebida,
                    COALESCE(COUNT(CASE WHEN r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada') THEN r.id END), 0) AS total_reservas_qtd
                FROM
                    usuarios u
                LEFT JOIN
                    reservas r ON u.id = r.corretor_id
                    AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
                WHERE
                    u.imobiliaria_id = ? AND u.tipo LIKE 'corretor_%' AND u.ativo = TRUE
                GROUP BY
                    u.id, u.nome
                ORDER BY
                    total_vendas_valor DESC;
            ";
            $corretores_export = fetch_all($sql_corretores_export, [$filter_desempenho_start_date_export, $filter_desempenho_end_date_export, $imobiliaria_id], "ssi");
            foreach ($corretores_export as $row) {
                fputcsv($output, [
                    $row['nome_corretor'],
                    $row['total_vendas_qtd'],
                    number_format($row['total_vendas_valor'], 2, ',', '.'),
                    number_format($row['comissao_recebida'], 2, ',', '.'),
                    $row['total_reservas_qtd']
                ]);
            }
            break;
    }

    fclose($output);
    exit(); // Encerra a execução após a exportação
}


// ======================================================================================================
// Lógica de Backend para os Relatórios (para exibição na tela)
// ======================================================================================================

// Dados para filtros
$corretores_imobiliaria = fetch_all("SELECT id, nome FROM usuarios WHERE imobiliaria_id = ? AND tipo LIKE 'corretor_%' AND ativo = TRUE ORDER BY nome ASC", [$imobiliaria_id], "i");
// Para empreendimentos da imobiliária, buscar empreendimentos que têm unidades reservadas/vendidas por corretores da imobiliária
$empreendimentos_da_imobiliaria = fetch_all("
    SELECT DISTINCT e.id, e.nome
    FROM empreendimentos e
    JOIN unidades u ON e.id = u.empreendimento_id
    JOIN reservas r ON u.id = r.unidade_id
    WHERE r.corretor_id IN ({$placeholders}) ORDER BY e.nome ASC", $imobiliaria_filter_params_base, $imobiliaria_filter_types_base);


// --- 1. Relatório de Vendas por Período ---
$vendas_por_periodo_data = [];
$vendas_por_periodo_labels = [];
$vendas_por_periodo_total_valor = 0;
$vendas_por_periodo_total_vendas = 0;
$vendas_por_periodo_comissao_imobiliaria = 0;
$vendas_por_periodo_comissao_corretor = 0;

$filter_vendas_start_date = filter_input(INPUT_GET, 'vendas_start_date', FILTER_UNSAFE_RAW) ?? date('Y-m-01');
$filter_vendas_end_date = filter_input(INPUT_GET, 'vendas_end_date', FILTER_UNSAFE_RAW) ?? date('Y-m-t');
$filter_vendas_corretor_id = filter_input(INPUT_GET, 'vendas_corretor_id', FILTER_VALIDATE_INT);

if ($imobiliaria_id) {
    try {
        $sql_vendas_periodo = "
            SELECT
                DATE(r.data_ultima_interacao) AS data_venda,
                COUNT(r.id) AS total_vendas,
                SUM(r.valor_reserva) AS total_valor,
                SUM(r.comissao_imobiliaria) AS total_comissao_imobiliaria,
                SUM(r.comissao_corretor) AS total_comissao_corretor
            FROM
                reservas r
            WHERE
                r.status = 'vendida'
                AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
                {$imobiliaria_filter_clause}
        ";
        $params = array_merge([$filter_vendas_start_date, $filter_vendas_end_date], $imobiliaria_filter_params_base);
        $types = "ss" . $imobiliaria_filter_types_base;

        if ($filter_vendas_corretor_id) {
            $sql_vendas_periodo .= " AND r.corretor_id = ?";
            $params[] = $filter_vendas_corretor_id;
            $types .= "i";
        }
        $sql_vendas_periodo .= " GROUP BY data_venda ORDER BY data_venda ASC";
        $raw_vendas_periodo = fetch_all($sql_vendas_periodo, $params, $types);

        // Popula dados para o gráfico e soma totais
        $current_date = new DateTime($filter_vendas_start_date);
        $end_date_obj = new DateTime($filter_vendas_end_date);
        $vendas_map = [];
        foreach ($raw_vendas_periodo as $row) {
            $vendas_map[$row['data_venda']] = [
                'valor' => $row['total_valor'],
                'quantidade' => $row['total_vendas'],
                'comissao_imobiliaria' => $row['total_comissao_imobiliaria'],
                'comissao_corretor' => $row['total_comissao_corretor']
            ];
            $vendas_por_periodo_total_valor += $row['total_valor'];
            $vendas_por_periodo_total_vendas += $row['total_vendas'];
            $vendas_por_periodo_comissao_imobiliaria += $row['total_comissao_imobiliaria'];
            $vendas_por_periodo_comissao_corretor += $row['total_comissao_corretor'];
        }

        while ($current_date <= $end_date_obj) {
            $date_str = $current_date->format('Y-m-d');
            $vendas_por_periodo_labels[] = $current_date->format('d/m');
            $vendas_por_periodo_data[] = $vendas_map[$date_str]['quantidade'] ?? 0;
            $current_date->modify('+1 day');
        }

    } catch (Exception $e) {
        error_log("Erro ao carregar relatório de vendas por período (imobiliaria): " . $e->getMessage());
        $errors[] = "Erro ao carregar vendas por período da equipe: " . $e->getMessage();
    }
}

// --- 2. Relatório de Status de Unidades por Empreendimento (associadas à imobiliária) ---
$status_unidades_data = []; // {empreendimento_nome: {disponivel: X, reservada: Y, vendida: Z}}
$filter_status_empreendimento_id = filter_input(INPUT_GET, 'status_empreendimento_id', FILTER_VALIDATE_INT);

if ($imobiliaria_id) {
    try {
        $sql_status_unidades = "
            SELECT
                e.nome AS empreendimento_nome,
                u.status AS unidade_status,
                COUNT(DISTINCT u.id) AS total_unidades -- COUNT(DISTINCT u.id) para não duplicar se houver várias reservas para a mesma unidade (ex: canceladas)
            FROM
                unidades u
            JOIN
                empreendimentos e ON u.empreendimento_id = e.id
            JOIN
                reservas r ON u.id = r.unidade_id -- Junta com reservas para poder filtrar por corretor da imobiliária
            WHERE
                1=1 {$imobiliaria_filter_clause}
        ";
        $params_status = $imobiliaria_filter_params_base;
        $types_status = $imobiliaria_filter_types_base;

        if ($filter_status_empreendimento_id) {
            $sql_status_unidades .= " AND e.id = ?";
            $params_status[] = $filter_status_empreendimento_id;
            $types_status .= "i";
        }
        $sql_status_unidades .= " GROUP BY e.nome, u.status ORDER BY e.nome, u.status ASC";
        $raw_status_unidades = fetch_all($sql_status_unidades, $params_status, $types_status);

        foreach ($raw_status_unidades as $row) {
            if (!isset($status_unidades_data[$row['empreendimento_nome']])) {
                $status_unidades_data[$row['empreendimento_nome']] = ['disponivel' => 0, 'reservada' => 0, 'vendida' => 0, 'total' => 0];
            }
            $status_unidades_data[$row['empreendimento_nome']][$row['unidade_status']] += $row['total_unidades']; // Soma, pois COUNT(DISTINCT) já garante unicidade da unidade
            $status_unidades_data[$row['empreendimento_nome']]['total'] += $row['total_unidades'];
        }

    } catch (Exception $e) {
        error_log("Erro ao carregar relatório de status de unidades (imobiliaria): " . $e->getMessage());
        $errors[] = "Erro ao carregar status de unidades da equipe: " . $e->getMessage();
    }
}


// --- 3. Relatório de Desempenho de Corretores ---
$desempenho_corretores = [];

$filter_desempenho_start_date = filter_input(INPUT_GET, 'desempenho_start_date', FILTER_UNSAFE_RAW) ?? date('Y-m-01');
$filter_desempenho_end_date = filter_input(INPUT_GET, 'desempenho_end_date', FILTER_UNSAFE_RAW) ?? date('Y-m-t');

if ($imobiliaria_id) {
    try {
        // Desempenho de Corretores da imobiliária
        $sql_desempenho_corretores = "
            SELECT
                u.id AS corretor_id,
                u.nome AS corretor_nome,
                COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.valor_reserva ELSE 0 END), 0) AS total_vendas_valor,
                COALESCE(COUNT(CASE WHEN r.status = 'vendida' THEN r.id END), 0) AS total_vendas_qtd,
                COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.comissao_corretor ELSE 0 END), 0) AS comissao_recebida_corretor,
                COALESCE(COUNT(CASE WHEN r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada') THEN r.id END), 0) AS total_reservas_qtd
            FROM
                usuarios u
            LEFT JOIN
                reservas r ON u.id = r.corretor_id
                AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
            WHERE
                u.imobiliaria_id = ? AND u.tipo LIKE 'corretor_%' AND u.ativo = TRUE
            GROUP BY
                u.id, u.nome
            ORDER BY
                total_vendas_valor DESC;
        ";
        $desempenho_corretores = fetch_all($sql_desempenho_corretores, [$filter_desempenho_start_date, $filter_desempenho_end_date, $imobiliaria_id], "ssi");

    } catch (Exception $e) {
        error_log("Erro ao carregar relatório de desempenho de corretores (imobiliaria): " . $e->getMessage());
        $errors[] = "Erro ao carregar desempenho de corretores da equipe: " . $e->getMessage();
    }
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
                        <?php foreach ($corretores_imobiliaria as $corretor): ?>
                            <option value="<?php echo htmlspecialchars($corretor['id']); ?>" <?php echo ($filter_vendas_corretor_id == $corretor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($corretor['nome']); ?>
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
            <p><strong>Comissão da Imobiliária:</strong> <?php echo format_currency_brl($vendas_por_periodo_comissao_imobiliaria); ?></p>
            <p><strong>Comissão dos Corretores:</strong> <?php echo format_currency_brl($vendas_por_periodo_comissao_corretor); ?></p>
        </div>

        <div class="chart-container" style="height: 350px;">
            <canvas id="vendasPeriodoChart"></canvas>
        </div>
    </div>

    <div class="report-section mt-2xl">
        <h3>Status de Unidades por Empreendimento (Relacionadas à sua Imobiliária)</h3>
        <form class="filters-bar" method="GET" action="">
            <input type="hidden" name="report" value="status_unidades">
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="status_empreendimento_id">Empreendimento:</label>
                    <select id="status_empreendimento_id" name="status_empreendimento_id" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($empreendimentos_da_imobiliaria as $emp): ?>
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
                        <tr><td colspan="5" style="text-align: center;">Nenhum dado de status de unidades relacionado à sua imobiliária encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($status_unidades_data as $emp_name => $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp_name); ?></td>
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
        <h3>Desempenho dos Corretores da Equipe</h3>
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
                        <th>Vendas (Valor R$)</th>
                        <th>Comissão Recebida R$</th>
                        <th>Reservas Ativas (Qtd)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($desempenho_corretores)): ?>
                        <tr><td colspan="5" style="text-align: center;">Nenhum dado de desempenho de corretor encontrado para sua equipe.</td></tr>
                    <?php else: ?>
                        <?php foreach ($desempenho_corretores as $corretor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($corretor['nome_corretor']); ?></td>
                                <td><?php echo htmlspecialchars($corretor['total_vendas_qtd']); ?></td>
                                <td><?php echo format_currency_brl($corretor['total_vendas_valor']); ?></td>
                                <td><?php echo format_currency_brl($corretor['comissao_recebida_corretor']); ?></td>
                                <td><?php echo htmlspecialchars($corretor['total_reservas_qtd']); ?></td>
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
    window.vendasPeriodoChartData = <?php echo $vendas_por_periodo_data_json; ?>;

    // Dados para o gráfico de Status de Unidades
    window.statusUnidadesChartLabels = <?php echo $status_unidades_chart_labels_json; ?>;
    window.statusUnidadesChartDataDisponivel = <?php echo $status_unidades_chart_data_disponivel_json; ?>;
    window.statusUnidadesChartDataReservada = <?php echo $status_unidades_chart_data_reservada_json; ?>;
    window.statusUnidadesChartDataVendida = <?php echo $status_unidades_chart_data_vendida_json; ?>;
</script>

<?php require_once '../../includes/footer_dashboard.php'; ?>