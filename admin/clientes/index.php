<?php
// admin/clientes/index.php - VERSÃO CORRETA DA PÁGINA DE LISTAGEM

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

require_permission(['admin']);
$page_title = "Gestão de Clientes";
$conn = get_db_connection();

// --- LÓGICA PARA KPIs GERAIS DE CLIENTES ---
$kpis = [
    'total_clientes' => fetch_single("SELECT COUNT(id) as total FROM clientes")['total'] ?? 0,
    'clientes_com_reservas' => fetch_single("SELECT COUNT(DISTINCT cliente_id) as total FROM reservas_clientes rc JOIN reservas r ON rc.reserva_id = r.id WHERE r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada')")['total'] ?? 0,
    'clientes_com_vendas' => fetch_single("SELECT COUNT(DISTINCT cliente_id) as total FROM reservas_clientes rc JOIN reservas r ON rc.reserva_id = r.id WHERE r.status = 'vendida'")['total'] ?? 0,
];

// --- LÓGICA DE FILTROS E BUSCA DA LISTAGEM ---
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);

// Constrói a query SQL base
$sql = "
    SELECT c.*,
           (SELECT COUNT(r.id) FROM reservas r JOIN reservas_clientes rc ON r.id = rc.reserva_id WHERE rc.cliente_id = c.id AND r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada')) as num_reservas_ativas,
           (SELECT COUNT(r.id) FROM reservas r JOIN reservas_clientes rc ON r.id = rc.reserva_id WHERE rc.cliente_id = c.id AND r.status = 'vendida') as num_vendas_concluidas
    FROM clientes c
    WHERE 1=1
";
$params = [];
$types = "";

if ($search_term) {
    $sql .= " AND (c.nome LIKE ? OR c.cpf LIKE ? OR c.email LIKE ?)";
    $search_param = '%' . $search_term . '%';
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

if ($filter_status === 'com_reservas') {
    $sql .= " HAVING num_reservas_ativas > 0";
} elseif ($filter_status === 'com_vendas') {
    $sql .= " HAVING num_vendas_concluidas > 0";
} elseif ($filter_status === 'sem_negocios') {
    $sql .= " HAVING num_reservas_ativas = 0 AND num_vendas_concluidas = 0";
}

$sql .= " ORDER BY c.data_cadastro DESC";
$clientes = fetch_all($sql, $params, $types);

require_once '../../includes/header_dashboard.php';
?>
<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>

    <div class="kpi-grid">
        <div class="kpi-card"><span class="kpi-label">Total de Clientes</span><span class="kpi-value"><?php echo $kpis['total_clientes']; ?></span></div>
        <div class="kpi-card"><span class="kpi-label">Com Reservas Ativas</span><span class="kpi-value"><?php echo $kpis['clientes_com_reservas']; ?></span></div>
        <div class="kpi-card"><span class="kpi-label">Com Vendas Concluídas</span><span class="kpi-value"><?php echo $kpis['clientes_com_vendas']; ?></span></div>
    </div>

    <div class="admin-controls-bar">
        <form id="clienteFiltersForm" method="GET" action="">
            <div class="search-box">
                <input type="text" id="clienteSearch" name="search" placeholder="Buscar por nome, CPF ou e-mail..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
            </div>
            <div class="filters-box">
                <select id="clienteFilterStatus" name="status" class="form-control">
                    <option value="">Todos os Clientes</option>
                    <option value="com_reservas" <?php if($filter_status === 'com_reservas') echo 'selected'; ?>>Com Reservas Ativas</option>
                    <option value="com_vendas" <?php if($filter_status === 'com_vendas') echo 'selected'; ?>>Com Vendas Concluídas</option>
                    <option value="sem_negocios" <?php if($filter_status === 'sem_negocios') echo 'selected'; ?>>Sem Negócios</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Contato</th>
                    <th>Reservas Ativas</th>
                    <th>Vendas Concluídas</th>
                    <th>Data Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr><td colspan="7" style="text-align: center;">Nenhum cliente encontrado com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cliente['id']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($cliente['email']); ?><br>
                                <?php echo htmlspecialchars(format_whatsapp($cliente['whatsapp'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($cliente['num_reservas_ativas']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['num_vendas_concluidas']); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($cliente['data_cadastro'])); ?></td>
                            <td class="admin-table-actions">
                                <a href="<?php echo BASE_URL; ?>admin/clientes/detalhes.php?id=<?php echo $cliente['id']; ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../../includes/footer_dashboard.php'; ?>