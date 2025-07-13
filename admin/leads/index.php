<?php
// admin/leads/index.php - VERSÃO FINAL, COMPLETA E CORRIGIDA

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

require_permission(['admin']);
$page_title = "Gestão de Leads";
$conn = get_db_connection();

// --- KPIs APRIMORADOS PARA LEADS ---
$kpis = [
    'leads_pendentes' => fetch_single("SELECT COUNT(id) as total FROM reservas WHERE status = 'solicitada' AND corretor_id IS NULL")['total'] ?? 0,
    'leads_hoje' => fetch_single("SELECT COUNT(id) as total FROM reservas WHERE status = 'solicitada' AND corretor_id IS NULL AND DATE(data_reserva) = CURDATE()")['total'] ?? 0,
    'leads_atribuidos' => fetch_single("SELECT COUNT(id) as total FROM reservas WHERE status = 'aprovada' AND corretor_id IS NOT NULL")['total'] ?? 0,
    'total_mes' => fetch_single("SELECT COUNT(id) as total FROM reservas WHERE status = 'solicitada' AND YEAR(data_reserva) = YEAR(CURDATE()) AND MONTH(data_reserva) = MONTH(CURDATE())")['total'] ?? 0
];

// --- LÓGICA DE FILTROS E BUSCA ---
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$empreendimento_id_filter = filter_input(INPUT_GET, 'empreendimento_id', FILTER_VALIDATE_INT);

// CONSULTA SQL CORRIGIDA que busca os leads pendentes
$sql = "
    SELECT 
        r.id as lead_id, r.data_reserva, r.data_ultima_interacao,
        c.nome as cliente_nome, c.whatsapp as cliente_whatsapp,
        e.nome as empreendimento_nome,
        u.numero as unidade_numero
    FROM reservas r
    JOIN reservas_clientes rc ON r.id = rc.reserva_id
    JOIN clientes c ON rc.cliente_id = c.id
    JOIN unidades u ON r.unidade_id = u.id
    JOIN empreendimentos e ON u.empreendimento_id = e.id
    WHERE r.status = 'solicitada' AND r.corretor_id IS NULL
";
$params = [];
$types = "";

if ($search_term) {
    $sql .= " AND (c.nome LIKE ? OR c.whatsapp LIKE ? OR e.nome LIKE ?)";
    $search_param = '%' . $search_term . '%';
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}
if ($empreendimento_id_filter) {
    $sql .= " AND r.empreendimento_id = ?";
    $params[] = $empreendimento_id_filter;
    $types .= "i";
}

$sql .= " ORDER BY r.data_reserva ASC";
$leads = fetch_all($sql, $params, $types);

// Dados para preencher os selects dos filtros
$empreendimentos = fetch_all("SELECT id, nome FROM empreendimentos WHERE status='ativo' ORDER BY nome ASC");
$corretores_ativos = fetch_all("SELECT id, nome FROM usuarios WHERE tipo LIKE 'corretor_%' AND ativo = 1 ORDER BY nome ASC");

require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>
    <p class="text-secondary">Leads são solicitações de reserva feitas por visitantes do site que aguardam atribuição a um corretor.</p>

    <div class="kpi-grid">
        <div class="kpi-card kpi-card-urgent"><span class="kpi-label">Leads Pendentes</span><span class="kpi-value"><?php echo $kpis['leads_pendentes']; ?></span></div>
        <div class="kpi-card"><span class="kpi-label">Novos Leads Hoje</span><span class="kpi-value"><?php echo $kpis['leads_hoje']; ?></span></div>
        <div class="kpi-card"><span class="kpi-label">Leads Atribuídos</span><span class="kpi-value"><?php echo $kpis['leads_atribuidos']; ?></span></div>
        <div class="kpi-card"><span class="kpi-label">Total de Leads no Mês</span><span class="kpi-value"><?php echo $kpis['total_mes']; ?></span></div>
    </div>

    <div class="admin-controls-bar">
        <form id="leadsFiltersForm" method="GET" action="">
            <div class="search-box">
                <input type="text" id="leadSearch" name="search" placeholder="Buscar por cliente, telefone, empreendimento..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
            </div>
            <div class="filters-box">
                <select name="empreendimento_id" class="form-control">
                    <option value="">Todos os Empreendimentos</option>
                    <?php foreach ($empreendimentos as $empreendimento): ?>
                        <option value="<?php echo $empreendimento['id']; ?>" <?php if($empreendimento_id_filter == $empreendimento['id']) echo 'selected'; ?>><?php echo htmlspecialchars($empreendimento['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>
         <div class="actions-box">
             <a href="<?php echo BASE_URL; ?>admin/leads/dispensados.php" class="btn btn-secondary">Ver Leads Dispensados</a>
        </div>
    </div>

    <div class="admin-table-responsive mt-lg">
        <table class="admin-table" id="leadsTable">
            <thead>
                <tr>
                    <th>ID Lead</th>
                    <th>Data Solicitação</th>
                    <th>Cliente</th>
                    <th>Empreendimento/Unidade</th>
                    <th>Tempo Decorrido</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr><td colspan="6" class="text-center">Nenhum lead pendente encontrado com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lead['lead_id']); ?></td>
                            <td>
                                <div class="datetime-cell">
                                    <span><?php echo format_datetime_br($lead['data_reserva'], true); ?></span>
                                    <small><?php echo format_datetime_br($lead['data_reserva'], false, true); ?></small>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($lead['cliente_nome']); ?></strong><br>
                                <small><?php echo htmlspecialchars(format_whatsapp($lead['cliente_whatsapp'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($lead['empreendimento_nome'] . ' / Unidade ' . $lead['unidade_numero']); ?></td>
                            <td><?php echo htmlspecialchars(time_elapsed_string($lead['data_reserva'])); ?></td>
                            <td class="admin-table-actions">
                                <button type="button" class="btn btn-primary btn-sm assign-lead-btn" data-lead-id="<?php echo $lead['lead_id']; ?>" data-cliente-nome="<?php echo htmlspecialchars($lead['cliente_nome']); ?>">Atribuir</button>
                                <button type="button" class="btn btn-success btn-sm attend-lead-btn" data-lead-id="<?php echo $lead['lead_id']; ?>">Atender</button>
                                <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo $lead['lead_id']; ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                <button type="button" class="btn btn-danger btn-sm dismiss-lead-btn" data-lead-id="<?php echo $lead['lead_id']; ?>">Dispensar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="assignCorretorModal" class="modal-overlay">
    <div class="modal-content">
        <form id="assignCorretorForm" method="POST" action="<?php echo BASE_URL; ?>api/processa_lead.php">
            <input type="hidden" name="action" value="assign_broker">
            <input type="hidden" id="assign_lead_id" name="lead_id">
            <div class="modal-header">
                <h3 class="modal-title">Atribuir Lead</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Selecione um corretor para atender o lead de <strong><span id="modalClienteNome"></span></strong>.</p>
                <div class="form-group">
                    <label for="assign_corretor_id">Corretor:</label>
                    <select id="assign_corretor_id" name="corretor_id" class="form-control" required>
                        <option value="" disabled selected>Selecione um corretor...</option>
                        <?php foreach ($corretores_ativos as $corretor): ?>
                            <option value="<?php echo $corretor['id']; ?>"><?php echo htmlspecialchars($corretor['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-cancel-btn">Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar Atribuição</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>