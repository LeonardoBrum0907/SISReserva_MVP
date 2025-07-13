<?php
// imobiliaria/clientes/index.php - Painel de Clientes da Imobiliária

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/clientes/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Clientes da Imobiliária";

$logged_user_info = get_user_info();
$imobiliaria_id = $logged_user_info['imobiliaria_id'] ?? null;

$clientes = [];
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


// --- KPIs para Clientes da Imobiliária ---
$total_clientes_equipe = 0;
$clientes_com_reservas_ativas_equipe = 0;
$clientes_com_vendas_concluidas_equipe = 0;

if ($imobiliaria_id) { // Calcula KPIs apenas se a imobiliária for identificada
    try {
        // KPI: Total de Clientes únicos associados a corretores da equipe
        $sql_total_clientes_equipe = "
            SELECT COUNT(DISTINCT c.id) AS total
            FROM clientes c
            JOIN reservas_clientes rc ON c.id = rc.cliente_id
            JOIN reservas r ON rc.reserva_id = r.id
            WHERE 1=1 {$imobiliaria_filter_clause}
        ";
        $params_total_clientes = $imobiliaria_filter_params;
        $types_total_clientes = $imobiliaria_filter_types;
        $result_total_clientes_equipe = fetch_single($sql_total_clientes_equipe, $params_total_clientes, $types_total_clientes);
        $total_clientes_equipe = $result_total_clientes_equipe['total'] ?? 0;

        // KPI: Clientes com Reservas Ativas
        $sql_clientes_reservas_ativas_equipe = "
            SELECT COUNT(DISTINCT c.id) AS total
            FROM clientes c
            JOIN reservas_clientes rc ON c.id = rc.cliente_id
            JOIN reservas r ON rc.reserva_id = r.id
            WHERE r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada') {$imobiliaria_filter_clause}
        ";
        $params_reservas_ativas_equipe = $imobiliaria_filter_params;
        $types_reservas_ativas_equipe = $imobiliaria_filter_types;
        $result_clientes_reservas_ativas_equipe = fetch_single($sql_clientes_reservas_ativas_equipe, $params_reservas_ativas_equipe, $types_reservas_ativas_equipe);
        $clientes_com_reservas_ativas_equipe = $result_clientes_reservas_ativas_equipe['total'] ?? 0;

        // KPI: Clientes com Vendas Concluídas
        $sql_clientes_vendas_concluidas_equipe = "
            SELECT COUNT(DISTINCT c.id) AS total
            FROM clientes c
            JOIN reservas_clientes rc ON c.id = rc.cliente_id
            JOIN reservas r ON rc.reserva_id = r.id
            WHERE r.status = 'vendida' {$imobiliaria_filter_clause}
        ";
        $params_vendas_concluidas_equipe = $imobiliaria_filter_params;
        $types_vendas_concluidas_equipe = $imobiliaria_filter_types;
        $result_clientes_vendas_concluidas_equipe = fetch_single($sql_clientes_vendas_concluidas_equipe, $params_vendas_concluidas_equipe, $types_vendas_concluidas_equipe);
        $clientes_com_vendas_concluidas_equipe = $result_clientes_vendas_concluidas_equipe['total'] ?? 0;

    } catch (Exception $e) {
        error_log("Erro ao carregar KPIs de clientes da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os indicadores de clientes da equipe: " . $e->getMessage();
    }
}


// --- Parâmetros de Filtro para a Tabela de Clientes ---
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';


// --- Listagem de Clientes para a Tabela ---
if ($imobiliaria_id) {
    try {
        // Se não houver corretores vinculados, não há clientes para mostrar
        if (empty($corretores_ids_da_imobiliaria)) {
            $errors[] = "Nenhum corretor vinculado à sua imobiliária encontrado. Clientes associados a corretores serão exibidos aqui.";
        } else {
            $placeholders = implode(',', array_fill(0, count($corretores_ids_da_imobiliaria), '?'));

            $sql_clientes = "
                SELECT DISTINCT
                    c.id AS cliente_id,
                    c.nome,
                    c.cpf,
                    c.email,
                    c.whatsapp,
                    c.cep,
                    c.endereco,
                    c.numero,
                    c.complemento,
                    c.bairro,
                    c.cidade,
                    c.estado,
                    c.data_cadastro
                FROM
                    clientes c
                JOIN
                    reservas_clientes rc ON c.id = rc.cliente_id
                JOIN
                    reservas r ON rc.reserva_id = r.id
                WHERE
                    r.corretor_id IN ({$placeholders})
            ";
            $params = $corretores_ids_da_imobiliaria;
            $types = str_repeat('i', count($corretores_ids_da_imobiliaria));

            // Aplica filtro de busca (se houver)
            if ($search_term) {
                $sql_clientes .= " AND (c.nome LIKE ? OR c.cpf LIKE ? OR c.email LIKE ? OR c.whatsapp LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                $types .= "ssss";
            }

            // Adiciona ordenação
            $order_clause = "c.data_cadastro DESC"; // Padrão
            if ($sort_by) {
                $db_column_map = [
                    'cliente_id' => 'c.id',
                    'nome' => 'c.nome',
                    'cpf' => 'c.cpf',
                    'email' => 'c.email',
                    'whatsapp' => 'c.whatsapp',
                    'data_cadastro' => 'c.data_cadastro',
                ];
                if (isset($db_column_map[$sort_by])) {
                    $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
                }
            }
            $sql_clientes .= " ORDER BY {$order_clause}";

            $clientes = fetch_all($sql_clientes, $params, $types);
        }

    } catch (Exception $e) {
        error_log("Erro ao carregar lista de clientes da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os clientes da equipe: " . $e->getMessage();
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
            <span class="kpi-label">Total de Clientes</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_clientes_equipe); ?></span>
            <small>Associados à sua equipe</small>
        </div>
        <div class="kpi-card kpi-card-info">
            <span class="kpi-label">Com Reservas Ativas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($clientes_com_reservas_ativas_equipe); ?></span>
            <small>Em andamento</small>
        </div>
        <div class="kpi-card kpi-card-success">
            <span class="kpi-label">Com Vendas Concluídas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($clientes_com_vendas_concluidas_equipe); ?></span>
            <small>Clientes com compras finalizadas</small>
        </div>
    </div>
    <div class="admin-controls-bar">
        <form id="clientesFiltersForm" method="GET" action="<?php echo BASE_URL; ?>imobiliaria/clientes/index.php">
            <div class="search-box">
                <input type="text" id="clientesSearch" name="search" placeholder="Buscar cliente por nome, CPF, email, WhatsApp..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
                <i class="fas fa-search"></i>
            </div>
            <div class="filters-box">
                <button type="submit" class="btn btn-primary" id="applyClientesFiltersBtn">Aplicar Filtros</button>
            </div>
        </form>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="clientesTable">
            <thead>
                <tr>
                    <th data-sort-by="cliente_id">ID <i class="fas fa-sort"></i></th>
                    <th data-sort-by="nome">Nome <i class="fas fa-sort"></i></th>
                    <th data-sort-by="cpf">CPF <i class="fas fa-sort"></i></th>
                    <th data-sort-by="email">E-mail <i class="fas fa-sort"></i></th>
                    <th data-sort-by="whatsapp">WhatsApp <i class="fas fa-sort"></i></th>
                    <th>Endereço Completo</th>
                    <th data-sort-by="data_cadastro">Data Cadastro <i class="fas fa-sort"></i></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr><td colspan="8" style="text-align: center;">Nenhum cliente encontrado para sua imobiliária com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cliente['cliente_id']); ?></td>
                            <td><?php echo htmlspecialchars(format_cpf($cliente['cpf'])); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo htmlspecialchars(format_whatsapp($cliente['whatsapp'])); ?></td>
                            <td><?php echo htmlspecialchars($cliente['endereco'] . (!empty($cliente['numero']) ? ', ' . $cliente['numero'] : '') . (!empty($cliente['complemento']) ? ' (' . $cliente['complemento'] . ')' : '') . ' - ' . ($cliente['bairro'] ?? 'N/A') . ', ' . $cliente['cidade'] . '/' . $cliente['estado'] . ' - CEP: ' . format_cep($cliente['cep'])); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($cliente['data_cadastro'])); ?></td>
                            <td class="admin-table-actions">
                                <a href="<?php echo BASE_URL; ?>imobiliaria/clientes/detalhes.php?id=<?php echo htmlspecialchars($cliente['cliente_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>