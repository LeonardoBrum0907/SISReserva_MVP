<?php
// corretor/leads/index.php - Painel de Leads do Corretor

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em corretor/leads/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Corretor
require_permission(['corretor_autonomo', 'corretor_imobiliaria']);

$page_title = "Meus Leads";

$logged_user_info = get_user_info();
$corretor_id = $logged_user_info['id'];

$leads = [];
$errors = [];
$success_message = '';

// Lida com mensagens da sessão (se houverem)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// --- KPIs de Leads para o Corretor ---
$total_leads_atribuidos = 0;
$leads_em_atendimento = 0; // Leads que o corretor iniciou o processo
$leads_convertidos_venda = 0;
$leads_dispensados = 0;

try {
    // KPI: Total de Leads Atribuídos (todos os leads com status 'solicitada' atribuídos a este corretor)
    $sql_total_leads = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status = 'solicitada'";
    $result_total_leads = fetch_single($sql_total_leads, [$corretor_id], "i");
    $total_leads_atribuidos = $result_total_leads['total'] ?? 0;

    // KPI: Leads em Atendimento (leads que o corretor já moveu para 'aprovada' ou 'documentos_pendentes')
    $sql_em_atendimento = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica')";
    $result_em_atendimento = fetch_single($sql_em_atendimento, [$corretor_id], "i");
    $leads_em_atendimento = $result_em_atendimento['total'] ?? 0;

    // KPI: Leads Convertidos em Venda
    $sql_convertidos = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status = 'vendida'";
    $result_convertidos = fetch_single($sql_convertidos, [$corretor_id], "i");
    $leads_convertidos_venda = $result_convertidos['total'] ?? 0;

    // KPI: Leads Dispensados
    $sql_dispensados = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status = 'dispensada'";
    $result_dispensados = fetch_single($sql_dispensados, [$corretor_id], "i");
    $leads_dispensados = $result_dispensados['total'] ?? 0;

} catch (Exception $e) {
    error_log("Erro ao carregar KPIs de leads do corretor: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar os indicadores de leads: " . $e->getMessage();
}


// --- Parâmetros de Filtro para Leads ---
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW); // 'solicitada', 'em_atendimento', 'dispensada'
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';


// --- Listagem de Leads para a Tabela ---
try {
    $sql_leads = "
        SELECT
            r.id AS reserva_id,
            r.data_reserva,
            r.valor_reserva,
            r.status,
            r.data_ultima_interacao,
            COALESCE(cl.nome, 'N/A') AS cliente_nome,
            cl.whatsapp AS cliente_whatsapp,
            cl.email AS cliente_email,
            e.nome AS empreendimento_nome,
            u.numero AS unidade_numero,
            u.andar AS unidade_andar,
            -- Subquery para obter o nome do usuário da última interação
            (SELECT nome FROM usuarios WHERE id = r.usuario_ultima_interacao) AS usuario_ultima_interacao_nome
        FROM
            reservas r
        LEFT JOIN
            reservas_clientes rc ON r.id = rc.reserva_id
        LEFT JOIN
            clientes cl ON rc.cliente_id = cl.id
        JOIN
            unidades u ON r.unidade_id = u.id
        JOIN
            empreendimentos e ON u.empreendimento_id = e.id
        WHERE
            r.corretor_id = ?
    ";
    $params = [$corretor_id];
    $types = "i";

    // Aplica filtro de busca (se houver)
    if ($search_term) {
        $sql_leads .= " AND (cl.nome LIKE ? OR cl.whatsapp LIKE ? OR cl.email LIKE ? OR e.nome LIKE ? OR u.numero LIKE ?)";
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $types .= "sssss";
    }

    // Aplica filtro de status (se houver)
    if ($filter_status) {
        if ($filter_status === 'solicitada') {
            $sql_leads .= " AND r.status = 'solicitada'";
        } elseif ($filter_status === 'em_atendimento') {
            $sql_leads .= " AND r.status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica')";
        } elseif ($filter_status === 'dispensada') {
            $sql_leads .= " AND r.status = 'dispensada'";
        }
    } else {
        // Por padrão, mostra todos os leads que não foram vendidos ou cancelados (ativos do corretor)
        $sql_leads .= " AND r.status NOT IN ('vendida', 'cancelada', 'expirada')";
    }


    // Adiciona ordenação
    $order_clause = "r.data_reserva DESC"; // Padrão
    if ($sort_by) {
        $db_column_map = [
            'reserva_id' => 'r.id',
            'data_reserva' => 'r.data_reserva',
            'cliente_nome' => 'cl.nome',
            'empreendimento_nome' => 'e.nome',
            'unidade_numero' => 'u.numero',
            'status' => 'r.status',
            'data_ultima_interacao' => 'r.data_ultima_interacao',
        ];
        if (isset($db_column_map[$sort_by])) {
            $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
        }
    }
    $sql_leads .= " ORDER BY {$order_clause}";

    $leads = fetch_all($sql_leads, $params, $types);

} catch (Exception $e) {
    error_log("Erro ao carregar lista de leads do corretor: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar seus leads: " . $e->getMessage();
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
            <span class="kpi-label">Total Atribuídos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_leads_atribuidos); ?></span>
            <small>Leads para seu atendimento</small>
        </div>
        <div class="kpi-card kpi-card-info">
            <span class="kpi-label">Em Atendimento</span>
            <span class="kpi-value"><?php echo htmlspecialchars($leads_em_atendimento); ?></span>
            <small>Leads em progresso</small>
        </div>
        <div class="kpi-card kpi-card-success">
            <span class="kpi-label">Convertidos em Venda</span>
            <span class="kpi-value"><?php echo htmlspecialchars($leads_convertidos_venda); ?></span>
            <small>Leads que viraram vendas</small>
        </div>
        <div class="kpi-card kpi-card-danger">
            <span class="kpi-label">Dispensados</span>
            <span class="kpi-value"><?php echo htmlspecialchars($leads_dispensados); ?></span>
            <small>Leads não aproveitados</small>
        </div>
    </div>
    <div class="admin-controls-bar">
        <div class="search-box">
            <input type="text" id="leadSearch" placeholder="Buscar lead..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
            <i class="fas fa-search"></i>
        </div>
        <div class="filters-box">
            <select id="leadFilterStatus">
                <option value="">Todos os Leads</option>
                <option value="solicitada" <?php echo ($filter_status === 'solicitada') ? 'selected' : ''; ?>>Aguardando Atendimento</option>
                <option value="em_atendimento" <?php echo ($filter_status === 'em_atendimento') ? 'selected' : ''; ?>>Em Atendimento</option>
                <option value="dispensada" <?php echo ($filter_status === 'dispensada') ? 'selected' : ''; ?>>Dispensados</option>
            </select>
            <button class="btn btn-primary" id="applyLeadFiltersBtn">Aplicar Filtros</button>
        </div>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="leadsTable">
            <thead>
                <tr>
                    <th data-sort-by="reserva_id">ID <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_reserva">Data Solicitação <i class="fas fa-sort"></i></th>
                    <th data-sort-by="cliente_nome">Cliente <i class="fas fa-sort"></i></th>
                    <th>Contato (WhatsApp/Email)</th>
                    <th data-sort-by="empreendimento_nome">Empreendimento <i class="fas fa-sort"></i></th>
                    <th data-sort-by="unidade_numero">Unidade <i class="fas fa-sort"></i></th>
                    <th data-sort-by="status">Status <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_ultima_interacao">Última Interação <i class="fas fa-sort"></i></th>
                    <th data-sort-by="usuario_ultima_interacao_nome">Por <i class="fas fa-sort"></i></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr><td colspan="10" style="text-align: center;">Nenhum lead encontrado com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                        <tr data-lead-id="<?php echo htmlspecialchars($lead['reserva_id']); ?>" data-lead-status="<?php echo htmlspecialchars($lead['status']); ?>">
                            <td><?php echo htmlspecialchars($lead['reserva_id']); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($lead['data_reserva'])); ?></td>
                            <td><?php echo htmlspecialchars($lead['cliente_nome']); ?></td>
                            <td>
                                WhatsApp: <?php echo htmlspecialchars(format_whatsapp($lead['cliente_whatsapp'])); ?><br>
                                E-mail: <?php echo htmlspecialchars($lead['cliente_email']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($lead['empreendimento_nome']); ?></td>
                            <td><?php echo htmlspecialchars($lead['unidade_numero'] . ' (' . $lead['unidade_andar'] . 'º Andar)'); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($lead['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $lead['status']))); ?></span></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($lead['data_ultima_interacao'])); ?></td>
                            <td><?php echo htmlspecialchars($lead['usuario_ultima_interacao_nome'] ?? 'N/A'); ?></td>
                            <td class="admin-table-actions">
                                <?php if ($lead['status'] === 'solicitada'): ?>
                                    <button class="btn btn-success btn-sm take-lead-broker-btn" data-reserva-id="<?php echo htmlspecialchars($lead['reserva_id']); ?>">Atender Lead</button>
                                    <button class="btn btn-warning btn-sm dispense-lead-broker-btn" data-reserva-id="<?php echo htmlspecialchars($lead['reserva_id']); ?>">Dispensar</button>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>corretor/reservas/detalhes.php?id=<?php echo htmlspecialchars($lead['reserva_id']); ?>" class="btn btn-info btn-sm mt-1">Ver Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="takeLeadBrokerModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Atender Lead</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja **assumir o atendimento** deste lead (Reserva #<strong id="takeLeadBrokerReservaId"></strong>)?</p>
                <form id="takeLeadBrokerForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="take_lead_broker">
                    <input type="hidden" name="reserva_id" id="takeLeadBrokerReservaIdInput">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Atendimento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="dispenseLeadBrokerModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Dispensar Lead</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja **dispensar** este lead (Reserva #<strong id="dispenseLeadBrokerReservaId"></strong>)? Esta ação o removerá da sua lista ativa.</p>
                <form id="dispenseLeadBrokerForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="dispense_lead_broker">
                    <input type="hidden" name="reserva_id" id="dispenseLeadBrokerReservaIdInput">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Confirmar Dispensar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>