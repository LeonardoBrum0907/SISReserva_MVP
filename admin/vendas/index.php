<?php
// admin/vendas/index.php - Painel de Gestão de Vendas (Admin Master)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação (CPF, WhatsApp, moeda, data)
require_once '../../includes/alerts.php'; // Para mensagens

// Inicializar a conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

require_permission(['admin']); // Apenas Admin Master pode acessar vendas

$page_title = "Gestão de Vendas";

$errors = [];
$success_message = '';

// Recuperar mensagens da sessão, se houverem
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// ======================================================================================================
// Lógica de Exportação CSV
// (Similar ao admin/relatorios/index.php, mas focado nas vendas)
// ======================================================================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "relatorio_vendas_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // Adiciona BOM para UTF-8 no Excel

    fputcsv($output, [
        'ID Venda', 'Data Venda', 'Empreendimento', 'Unidade', 'Cliente', 'CPF Cliente',
        'Corretor', 'CRECI Corretor', 'Imobiliaria', 'Valor Venda', 'Comissao Corretor',
        'Comissao Imobiliaria', 'Ultima Interacao Por'
    ]);

    // Reutiliza os filtros de pesquisa da seção principal
    $filter_start_date_export = filter_input(INPUT_GET, 'start_date', FILTER_UNSAFE_RAW) ?? ''; // Corrigido FILTER_SANITIZE_STRING
    $filter_end_date_export = filter_input(INPUT_GET, 'end_date', FILTER_UNSAFE_RAW) ?? '';     // Corrigido FILTER_SANITIZE_STRING
    $filter_corretor_id_export = filter_input(INPUT_GET, 'corretor_id', FILTER_VALIDATE_INT);
    $filter_imobiliaria_id_export = filter_input(INPUT_GET, 'imobiliaria_id', FILTER_VALIDATE_INT);
    $filter_empreendimento_id_export = filter_input(INPUT_GET, 'empreendimento_id', FILTER_VALIDATE_INT);
    $search_term_export = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW); // Corrigido FILTER_SANITIZE_STRING

    $sql_export_vendas = "
        SELECT
            r.id AS venda_id,
            r.data_ultima_interacao AS data_venda,
            r.valor_reserva,
            r.comissao_corretor,
            r.comissao_imobiliaria,
            e.nome AS empreendimento_nome,
            u.numero AS unidade_numero,
            u.andar AS unidade_andar,
            COALESCE(cl.nome, 'N/A') AS cliente_nome,
            cl.cpf AS cliente_cpf,
            COALESCE(corr.nome, 'N/A') AS corretor_nome,
            corr.creci AS corretor_creci,
            COALESCE(i.nome, 'N/A') AS imobiliaria_nome,
            COALESCE(uiu.nome, 'Sistema/Não Identificado') AS usuario_ultima_interacao_nome
        FROM
            reservas r
        JOIN
            unidades u ON r.unidade_id = u.id
        JOIN
            empreendimentos e ON u.empreendimento_id = e.id
        LEFT JOIN
            reservas_clientes rc ON r.id = rc.reserva_id
        LEFT JOIN
            clientes cl ON rc.cliente_id = cl.id
        LEFT JOIN
            usuarios corr ON r.corretor_id = corr.id
        LEFT JOIN
            imobiliarias i ON corr.imobiliaria_id = i.id
        LEFT JOIN
            usuarios uiu ON r.usuario_ultima_interacao = uiu.id
        WHERE
            r.status = 'vendida'
    ";
    $params_export = [];
    $types_export = "";

    if (!empty($filter_start_date_export) && !empty($filter_end_date_export)) {
        $sql_export_vendas .= " AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?";
        $params_export[] = $filter_start_date_export;
        $params_export[] = $filter_end_date_export;
        $types_export .= "ss";
    }
    if ($filter_corretor_id_export) {
        $sql_export_vendas .= " AND r.corretor_id = ?";
        $params_export[] = $filter_corretor_id_export;
        $types_export .= "i";
    }
    if ($filter_imobiliaria_id_export) {
        $sql_export_vendas .= " AND corr.imobiliaria_id = ?";
        $params_export[] = $filter_imobiliaria_id_export;
        $types_export .= "i";
    }
    if ($filter_empreendimento_id_export) {
        $sql_export_vendas .= " AND e.id = ?";
        $params_export[] = $filter_empreendimento_id_export;
        $types_export .= "i";
    }
    if (!empty($search_term_export)) {
        $sql_export_vendas .= " AND (cl.nome LIKE ? OR cl.cpf LIKE ? OR corr.nome LIKE ? OR e.nome LIKE ? OR u.numero LIKE ?)";
        $search_param = '%' . $search_term_export . '%';
        $params_export = array_merge($params_export, [$search_param, $search_param, $search_param, $search_param, $search_param]);
        $types_export .= "sssss";
    }

    $sql_export_vendas .= " ORDER BY r.data_ultima_interacao DESC";
    $vendas_to_export = fetch_all($sql_export_vendas, $params_export, $types_export);

    foreach ($vendas_to_export as $row) {
        fputcsv($output, [
            $row['venda_id'],
            format_datetime_br($row['data_venda']),
            $row['empreendimento_nome'],
            $row['unidade_numero'] . ' (' . $row['unidade_andar'] . 'º Andar)',
            $row['cliente_nome'],
            format_cpf($row['cliente_cpf']),
            $row['corretor_nome'],
            $row['corretor_creci'],
            $row['imobiliaria_nome'],
            str_replace('.', ',', $row['valor_reserva']), // Formato para Excel
            str_replace('.', ',', $row['comissao_corretor']), // Formato para Excel
            str_replace('.', ',', $row['comissao_imobiliaria']), // Formato para Excel
            $row['usuario_ultima_interacao_nome']
        ]);
    }
    fclose($output);
    exit();
}
// ======================================================================================================
// Fim da Lógica de Exportação CSV
// ======================================================================================================

// ======================================================================================================
// Lógica de Backend para KPIs de Vendas
// ======================================================================================================

$total_vendas_periodo = 0;
$valor_total_vendido_periodo = 0;
$ticket_medio_venda = 0;
$unidades_contrato_enviado = 0;
$comissao_total_corretores = 0;
$comissao_total_imobiliarias = 0;


// Filtros para KPIs e tabela principal
$filter_start_date = filter_input(INPUT_GET, 'start_date', FILTER_UNSAFE_RAW) ?? date('Y-m-01'); // Corrigido FILTER_SANITIZE_STRING
$filter_end_date = filter_input(INPUT_GET, 'end_date', FILTER_UNSAFE_RAW) ?? date('Y-m-t');     // Corrigido FILTER_SANITIZE_STRING
$filter_corretor_id = filter_input(INPUT_GET, 'corretor_id', FILTER_VALIDATE_INT);
$filter_imobiliaria_id = filter_input(INPUT_GET, 'imobiliaria_id', FILTER_VALIDATE_INT);
$filter_empreendimento_id = filter_input(INPUT_GET, 'empreendimento_id', FILTER_VALIDATE_INT);
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW); // Corrigido FILTER_SANITIZE_STRING


try {
    // KPI: Unidades em 'Contrato Enviado' (Aguardando Finalização)
    $sql_contrato_enviado = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'contrato_enviado'";
    $result_contrato_enviado = fetch_single($sql_contrato_enviado);
    $unidades_contrato_enviado = $result_contrato_enviado['total'] ?? 0;

    // Base para KPIs de vendas por período
    $sql_kpi_base = "
        SELECT
            COALESCE(SUM(r.valor_reserva), 0) AS valor_total,
            COUNT(r.id) AS total_vendas,
            COALESCE(SUM(r.comissao_corretor), 0) AS total_comissao_corretores,
            COALESCE(SUM(r.comissao_imobiliaria), 0) AS total_comissao_imobiliarias
        FROM
            reservas r
        LEFT JOIN
            usuarios corr ON r.corretor_id = corr.id
        LEFT JOIN
            imobiliarias i ON corr.imobiliaria_id = i.id
        WHERE
            r.status = 'vendida'
            AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
    ";
    $kpi_params = [$filter_start_date, $filter_end_date];
    $kpi_types = "ss";

    if ($filter_corretor_id) {
        $sql_kpi_base .= " AND r.corretor_id = ?";
        $kpi_params[] = $filter_corretor_id;
        $kpi_types .= "i";
    }
    if ($filter_imobiliaria_id) {
        $sql_kpi_base .= " AND corr.imobiliaria_id = ?";
        $kpi_params[] = $filter_imobiliaria_id;
        $kpi_types .= "i";
    }
    // A query KPI para empreendimento é separada pois precisa de um JOIN a mais
    if ($filter_empreendimento_id) {
        $sql_kpi_base_empreendimento = "
            SELECT
                COALESCE(SUM(r.valor_reserva), 0) AS valor_total,
                COUNT(r.id) AS total_vendas,
                COALESCE(SUM(r.comissao_corretor), 0) AS total_comissao_corretores,
                COALESCE(SUM(r.comissao_imobiliaria), 0) AS total_comissao_imobiliarias
            FROM
                reservas r
            JOIN
                unidades u ON r.unidade_id = u.id
            WHERE
                r.status = 'vendida'
                AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
                AND u.empreendimento_id = ?
        ";
        $kpi_params_emp = [$filter_start_date, $filter_end_date, $filter_empreendimento_id];
        $kpi_types_emp = "ssi";
        $result_kpis = fetch_single($sql_kpi_base_empreendimento, $kpi_params_emp, $kpi_types_emp);
    } else {
        // Se não houver filtro de empreendimento, usa a query KPI base
        $result_kpis = fetch_single($sql_kpi_base, $kpi_params, $kpi_types);
    }

    $total_vendas_periodo = $result_kpis['total_vendas'] ?? 0;
    $valor_total_vendido_periodo = $result_kpis['valor_total'] ?? 0;
    $comissao_total_corretores = $result_kpis['total_comissao_corretores'] ?? 0;
    $comissao_total_imobiliarias = $result_kpis['total_comissao_imobiliarias'] ?? 0;
    $comissoes_totais_pagas = $comissao_total_corretores + $comissao_total_imobiliarias; // Novo KPI

    if ($total_vendas_periodo > 0) {
        $ticket_medio_venda = $valor_total_vendido_periodo / $total_vendas_periodo;
    }

} catch (Exception $e) {
    $errors[] = "Ocorreu um erro ao carregar os indicadores de vendas: " . $e->getMessage();
}


// ======================================================================================================
// Lógica de Backend para a Tabela de Vendas
// ======================================================================================================

$vendas = [];
// Obter listas para os filtros dropdown
$corretores = fetch_all("SELECT id, nome FROM usuarios WHERE tipo LIKE 'corretor_%' AND status = 'ativo' ORDER BY nome ASC");
$imobiliarias = fetch_all("SELECT id, nome FROM imobiliarias WHERE ativa = 1 ORDER BY nome ASC");
$empreendimentos = fetch_all("SELECT id, nome FROM empreendimentos WHERE status = 'ativo' ORDER BY nome ASC");

try {
    $sql_vendas_table = "
        SELECT
            r.id AS venda_id,
            r.data_ultima_interacao AS data_venda,
            r.valor_reserva,
            r.status, -- Será 'vendida'
            r.comissao_corretor,
            r.comissao_imobiliaria,
            e.nome AS empreendimento_nome,
            u.numero AS unidade_numero,
            u.andar AS unidade_andar,
            COALESCE(cl.nome, 'N/A') AS cliente_nome,
            cl.cpf AS cliente_cpf,
            COALESCE(corr.nome, 'N/A') AS corretor_nome,
            corr.creci AS corretor_creci,
            COALESCE(i.nome, 'N/A') AS imobiliaria_nome,
            COALESCE(uiu.nome, 'Sistema/Não Identificado') AS usuario_ultima_interacao_nome
        FROM
            reservas r
        JOIN
            unidades u ON r.unidade_id = u.id
        JOIN
            empreendimentos e ON u.empreendimento_id = e.id
        LEFT JOIN
            reservas_clientes rc ON r.id = rc.reserva_id
        LEFT JOIN
            clientes cl ON rc.cliente_id = cl.id
        LEFT JOIN
            usuarios corr ON r.corretor_id = corr.id
        LEFT JOIN
            imobiliarias i ON corr.imobiliaria_id = i.id
        LEFT JOIN
            usuarios uiu ON r.usuario_ultima_interacao = uiu.id
        WHERE
            r.status = 'vendida'
    ";
    $params_table = [];
    $types_table = "";

    // Aplica filtros de pesquisa para a tabela
    if (!empty($filter_start_date) && !empty($filter_end_date)) {
        $sql_vendas_table .= " AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?";
        $params_table[] = $filter_start_date;
        $params_table[] = $filter_end_date;
        $types_table .= "ss";
    }
    if ($filter_corretor_id) {
        $sql_vendas_table .= " AND r.corretor_id = ?";
        $params_table[] = $filter_corretor_id;
        $types_table .= "i";
    }
    if ($filter_imobiliaria_id) {
        $sql_vendas_table .= " AND corr.imobiliaria_id = ?";
        $params_table[] = $filter_imobiliaria_id;
        $types_table .= "i";
    }
    if ($filter_empreendimento_id) {
        $sql_vendas_table .= " AND e.id = ?";
        $params_table[] = $filter_empreendimento_id;
        $types_table .= "i";
    }
    if (!empty($search_term)) {
        $sql_vendas_table .= " AND (cl.nome LIKE ? OR cl.cpf LIKE ? OR corr.nome LIKE ? OR e.nome LIKE ? OR u.numero LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $params_table = array_merge($params_table, [$search_param, $search_param, $search_param, $search_param, $search_param]);
        $types_table .= "sssss";
    }

    $sql_vendas_table .= " ORDER BY r.data_ultima_interacao DESC";

    // --- DEPURACAO DA QUERY FINAL (COM VALORES) ---
    $debug_sql = $sql_vendas_table;
    $debug_params_str = [];
    $param_index = 0;
    foreach ($params_table as $param_val) {
        // Substitui o primeiro '?' encontrado por seu valor, corretamente aspas.
        // Para strings e datas, adicione aspas. Para números, não.
        // Esta é uma simulação, não use para construir queries reais em produção!
        if ($types_table !== '' && $types_table[$param_index] === 's') { // É string (e $types_table não está vazio)
            $debug_params_str[] = "'" . addslashes($param_val) . "'";
        } else { // É inteiro ou decimal, ou $types_table está vazio (sem parâmetros)
            $debug_params_str[] = $param_val;
        }
        $param_index++;
    }

    // A lógica de substituição é mais complexa do que um simples str_replace em um loop.
    // Para depuração, vamos tentar uma abordagem mais visual:
    $final_debug_sql = $sql_vendas_table;
    if (!empty($params_table)) {
        $parts = explode('?', $final_debug_sql);
        $final_debug_sql = array_shift($parts); // Parte antes do primeiro '?'
        for ($i = 0; $i < count($debug_params_str); $i++) {
            $final_debug_sql .= $debug_params_str[$i] . ($parts[$i] ?? ''); // Adiciona o valor e a próxima parte da query
        }
    }
    
    // --- FIM DEPURACAO DA QUERY FINAL ---


    $vendas = fetch_all($sql_vendas_table, $params_table, $types_table);


} catch (Exception $e) {
    $errors[] = "Ocorreu um erro ao carregar a tabela de vendas: " . $e->getMessage();
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
        <span class="kpi-label">Vendas Concluídas</span>
        <span class="kpi-value"><?php echo htmlspecialchars($total_vendas_periodo); ?></span>
        <small>No período selecionado</small>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">Valor Total Vendido</span>
        <span class="kpi-value"><?php echo format_currency_brl($valor_total_vendido_periodo); ?></span>
        <small>No período selecionado</small>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">Ticket Médio</span>
        <span class="kpi-value"><?php echo format_currency_brl($ticket_medio_venda); ?></span>
        <small>Por venda</small>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">Total Comissões</span>
        <span class="kpi-value"><?php echo format_currency_brl($comissoes_totais_pagas); ?></span>
        <small>Corretores + Imobiliárias</small>
    </div>
    <div class="kpi-card kpi-card-urgent">
        <span class="kpi-label">Em Contrato Enviado</span>
        <span class="kpi-value"><?php echo htmlspecialchars($unidades_contrato_enviado); ?></span>
        <small>Aguardando Finalização</small>
    </div>
</div>

    <div class="admin-controls-bar">
        <form id="vendasFiltersForm" method="GET" action="<?php echo BASE_URL; ?>admin/vendas/index.php">
            <div class="search-box">
                <input type="text" id="vendasSearch" name="search" placeholder="Buscar venda..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
                <i class="fas fa-search"></i>
            </div>
            <div class="filters-box">
                <input type="date" id="vendasStartDate" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                <input type="date" id="vendasEndDate" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                <select id="vendasCorretorFilter" name="corretor_id" class="form-control">
                    <option value="">Todos os Corretores</option>
                    <?php foreach ($corretores as $corr): ?>
                        <option value="<?php echo htmlspecialchars($corr['id']); ?>" <?php echo ($filter_corretor_id == $corr['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($corr['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="vendasImobiliariaFilter" name="imobiliaria_id" class="form-control">
                    <option value="">Todas as Imobiliárias</option>
                    <?php foreach ($imobiliarias as $imob): ?>
                        <option value="<?php echo htmlspecialchars($imob['id']); ?>" <?php echo ($filter_imobiliaria_id == $imob['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($imob['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="vendasEmpreendimentoFilter" name="empreendimento_id" class="form-control">
                    <option value="">Todos os Empreendimentos</option>
                    <?php foreach ($empreendimentos as $emp): ?>
                        <option value="<?php echo htmlspecialchars($emp['id']); ?>" <?php echo ($filter_empreendimento_id == $emp['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary" id="applyVendasFiltersBtn">Aplicar Filtros</button>
                <button type="button" class="btn btn-info" id="exportVendasCsvBtn">Exportar CSV</button>
            </div>
        </form>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="vendasTable">
            <thead>
                <tr>
                    <th data-sort-by="venda_id">ID Venda <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_venda">Data Venda <i class="fas fa-sort"></i></th>
                    <th data-sort-by="empreendimento_nome">Empreendimento <i class="fas fa-sort"></i></th>
                    <th data-sort-by="unidade_numero">Unidade <i class="fas fa-sort"></i></th>
                    <th data-sort-by="cliente_nome">Cliente <i class="fas fa-sort"></i></th>
                    <th data-sort-by="corretor_nome">Corretor <i class="fas fa-sort"></i></th>
                    <th data-sort-by="imobiliaria_nome">Imobiliária <i class="fas fa-sort"></i></th>
                    <th data-sort-by="valor_reserva">Valor Venda <i class="fas fa-sort"></i></th>
                    <th data-sort-by="comissao_corretor">Com. Corretor <i class="fas fa-sort"></i></th>
                    <th data-sort-by="comissao_imobiliaria">Com. Imob. <i class="fas fa-sort"></i></th>
                    <th>Finalizado Por</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vendas)): ?>
                    <tr><td colspan="12" style="text-align: center;">Nenhuma venda encontrada com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($venda['venda_id']); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($venda['data_venda'])); ?></td>
                            <td><?php echo htmlspecialchars($venda['empreendimento_nome']); ?></td>
                            <td><?php echo htmlspecialchars($venda['unidade_numero'] . ' (' . $venda['unidade_andar'] . 'º Andar)'); ?></td>
                            <td><?php echo htmlspecialchars($venda['cliente_nome']); ?><br><small><?php echo htmlspecialchars(format_cpf($venda['cliente_cpf'] ?? 'N/A')); ?></small></td>
                            <td><?php echo htmlspecialchars($venda['corretor_nome'] ?? 'N/A'); ?><br><small><?php echo htmlspecialchars($venda['corretor_creci'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($venda['imobiliaria_nome'] ?? 'N/A'); ?></td>
                            <td><?php echo format_currency_brl($venda['valor_reserva'] ?? 0); ?></td>
                            <td><?php echo format_currency_brl($venda['comissao_corretor'] ?? 0); ?></td>
                            <td><?php echo format_currency_brl($venda['comissao_imobiliaria'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($venda['usuario_ultima_interacao_nome'] ?? 'N/A'); ?></td>
                            <td class="admin-table-actions">
                                <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($venda['venda_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                <button class="btn btn-secondary btn-sm print-venda-btn" data-venda-id="<?php echo htmlspecialchars($venda['venda_id']); ?>">Imprimir</button>
                            </td> 
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>