<?php
// imobiliaria/vendas/index.php - Painel de Vendas da Imobiliária

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatar moeda, datas

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/vendas/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Vendas da Equipe";

$logged_user_info = get_user_info();
$imobiliaria_id = $logged_user_info['imobiliaria_id'] ?? null;

$vendas = [];
$errors = [];
$success_message = '';

// Lida com mensagens da sessão (se houverem)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// --- Lógica de Filtragem por Imobiliária para Queries ---
$corretores_ids_da_imobiliaria = [];
$imobiliaria_filter_clause = " AND 1=0"; // Default para não retornar nada se não houver corretores
$imobiliaria_filter_params = [];
$imobiliaria_filter_types = "";

if ($imobiliaria_id) {
    try {
        // Buscar IDs de todos os corretores vinculados a esta imobiliária
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
    } catch (Exception $e) {
        error_log("Erro ao buscar corretores da imobiliária para filtro: " . $e->getMessage());
        $errors[] = "Erro ao carregar dados dos corretores da sua imobiliária para filtros.";
    }
}


// --- Parâmetros de Filtro para Vendas ---
$filter_start_date = filter_input(INPUT_GET, 'start_date', FILTER_UNSAFE_RAW) ?? date('Y-m-01'); // Padrão: início do mês
$filter_end_date = filter_input(INPUT_GET, 'end_date', FILTER_UNSAFE_RAW) ?? date('Y-m-t');     // Padrão: fim do mês
$filter_corretor_id = filter_input(INPUT_GET, 'corretor_id', FILTER_VALIDATE_INT);
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';


// --- KPIs de Vendas para a Imobiliária ---
$total_vendas_periodo = 0;
$valor_total_vendido_periodo = 0;
$comissao_total_imobiliaria = 0;
$comissao_total_corretores = 0;
$ticket_medio_venda = 0;

if ($imobiliaria_id) { // Calcula KPIs apenas se a imobiliária for identificada
    try {
        $sql_kpis = "
            SELECT
                COUNT(r.id) AS total_vendas,
                COALESCE(SUM(r.valor_reserva), 0) AS valor_total,
                COALESCE(SUM(r.comissao_imobiliaria), 0) AS comissao_imobiliaria_total,
                COALESCE(SUM(r.comissao_corretor), 0) AS comissao_corretores_total
            FROM
                reservas r
            WHERE
                r.status = 'vendida'
                AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
                {$imobiliaria_filter_clause}
        ";
        $params_kpis = array_merge([$filter_start_date, $filter_end_date], $imobiliaria_filter_params);
        $types_kpis = "ss" . $imobiliaria_filter_types;
        
        // Aplica filtro por corretor específico se selecionado
        if ($filter_corretor_id) {
            $sql_kpis .= " AND r.corretor_id = ?";
            $params_kpis[] = $filter_corretor_id;
            $types_kpis .= "i";
        }

        $result_kpis = fetch_single($sql_kpis, $params_kpis, $types_kpis);

        $total_vendas_periodo = $result_kpis['total_vendas'] ?? 0;
        $valor_total_vendido_periodo = $result_kpis['valor_total'] ?? 0;
        $comissao_total_imobiliaria = $result_kpis['comissao_imobiliaria_total'] ?? 0;
        $comissao_total_corretores = $result_kpis['comissao_corretores_total'] ?? 0;

        if ($total_vendas_periodo > 0) {
            $ticket_medio_venda = $valor_total_vendido_periodo / $total_vendas_periodo;
        }

    } catch (Exception $e) {
        error_log("Erro ao carregar KPIs de vendas da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os indicadores de vendas da equipe: " . $e->getMessage();
    }
}


// --- Listagem de Vendas para a Tabela ---
if ($imobiliaria_id) {
    try {
        $sql_vendas = "
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
                corr.nome AS corretor_nome,
                corr.creci AS corretor_creci,
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
            JOIN
                usuarios corr ON r.corretor_id = corr.id -- JOIN obrigatório para filtrar por imobiliaria_id do corretor
            LEFT JOIN
                usuarios uiu ON r.usuario_ultima_interacao = uiu.id
            WHERE
                r.status = 'vendida'
                AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
                AND corr.imobiliaria_id = ? -- Filtro principal por imobiliária do corretor
        ";
        $params = [$filter_start_date, $filter_end_date, $imobiliaria_id];
        $types = "ssi";

        // Aplica filtro por corretor específico se selecionado
        if ($filter_corretor_id) {
            $sql_vendas .= " AND r.corretor_id = ?";
            $params[] = $filter_corretor_id;
            $types .= "i";
        }

        // Aplica filtro de busca (se houver)
        if ($search_term) {
            $sql_vendas .= " AND (cl.nome LIKE ? OR cl.cpf LIKE ? OR corr.nome LIKE ? OR e.nome LIKE ? OR u.numero LIKE ?)";
            $search_param = '%' . $search_term . '%';
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
            $types .= "sssss";
        }

        // Adiciona ordenação
        $order_clause = "r.data_ultima_interacao DESC"; // Padrão
        if ($sort_by) {
            $db_column_map = [
                'venda_id' => 'r.id',
                'data_venda' => 'r.data_ultima_interacao',
                'empreendimento_nome' => 'e.nome',
                'unidade_numero' => 'u.numero',
                'cliente_nome' => 'cl.nome',
                'corretor_nome' => 'corr.nome',
                'valor_reserva' => 'r.valor_reserva',
                'comissao_corretor' => 'r.comissao_corretor',
                'comissao_imobiliaria' => 'r.comissao_imobiliaria',
            ];
            if (isset($db_column_map[$sort_by])) {
                $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
            }
        }
        $sql_vendas .= " ORDER BY {$order_clause}";

        $vendas = fetch_all($sql_vendas, $params, $types);

        // Obter lista de corretores ativos da imobiliária para o filtro dropdown
        $corretores_ativos_imobiliaria = fetch_all("SELECT id, nome FROM usuarios WHERE imobiliaria_id = ? AND ativo = TRUE AND tipo LIKE 'corretor_%' ORDER BY nome ASC", [$imobiliaria_id], "i");

    } catch (Exception $e) {
        error_log("Erro ao carregar lista de vendas da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar as vendas da equipe: " . $e->getMessage();
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
            <span class="kpi-label">Comissão da Imobiliária</span>
            <span class="kpi-value"><?php echo format_currency_brl($comissao_total_imobiliaria); ?></span>
            <small>No período selecionado</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Comissão Corretores</span>
            <span class="kpi-value"><?php echo format_currency_brl($comissao_total_corretores); ?></span>
            <small>No período selecionado</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Ticket Médio</span>
            <span class="kpi-value"><?php echo format_currency_brl($ticket_medio_venda); ?></span>
            <small>Por venda</small>
        </div>
    </div>
    <div class="admin-controls-bar">
        <form id="vendasFiltersForm" method="GET" action="<?php echo BASE_URL; ?>imobiliaria/vendas/index.php">
            <div class="search-box">
                <input type="text" id="vendasSearch" name="search" placeholder="Buscar venda..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
                <i class="fas fa-search"></i>
            </div>
            <div class="filters-box">
                <input type="date" id="vendasStartDate" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                <input type="date" id="vendasEndDate" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                <select id="vendasCorretorFilter" name="corretor_id" class="form-control">
                    <option value="">Todos os Corretores</option>
                    <?php foreach ($corretores_ativos_imobiliaria as $corr): ?>
                        <option value="<?php echo htmlspecialchars($corr['id']); ?>" <?php echo ($filter_corretor_id == $corr['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($corr['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary" id="applyVendasFiltersBtn">Aplicar Filtros</button>
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
                    <th data-sort-by="valor_reserva">Valor Venda <i class="fas fa-sort"></i></th>
                    <th data-sort-by="comissao_corretor">Com. Corretor <i class="fas fa-sort"></i></th>
                    <th data-sort-by="comissao_imobiliaria">Com. Imob. <i class="fas fa-sort"></i></th>
                    <th>Finalizado Por</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vendas)): ?>
                    <tr><td colspan="11" style="text-align: center;">Nenhuma venda encontrada para a equipe com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($venda['venda_id']); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($venda['data_venda'])); ?></td>
                            <td><?php echo htmlspecialchars($venda['empreendimento_nome']); ?></td>
                            <td><?php echo htmlspecialchars($venda['unidade_numero'] . ' (' . $venda['unidade_andar'] . 'º Andar)'); ?></td>
                            <td><?php echo htmlspecialchars($venda['cliente_nome']); ?><br><small><?php echo htmlspecialchars(format_cpf($venda['cliente_cpf'] ?? 'N/A')); ?></small></td>
                            <td><?php echo htmlspecialchars($venda['corretor_nome'] ?? 'N/A'); ?></td>
                            <td><?php echo format_currency_brl($venda['valor_reserva'] ?? 0); ?></td>
                            <td><?php echo format_currency_brl($venda['comissao_corretor'] ?? 0); ?></td>
                            <td><?php echo format_currency_brl($venda['comissao_imobiliaria'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($venda['usuario_ultima_interacao_nome'] ?? 'N/A'); ?></td>
                            <td class="admin-table-actions">
                                <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/detalhes.php?id=<?php echo htmlspecialchars($venda['venda_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>