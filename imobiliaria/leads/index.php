<?php
// imobiliaria/leads/index.php - Painel de Leads da Imobiliária

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/leads/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Leads da Equipe";

$logged_user_info = get_user_info();
$imobiliaria_id = $logged_user_info['imobiliaria_id'] ?? null;

$leads = [];
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


// --- KPIs de Leads para a Imobiliária ---
$total_leads_gestao = 0; // Todos os leads que o corretor gerencia (solicitada e em andamento)
$leads_aguardando_atendimento_equipe = 0; // Leads em status solicitada (não movidos para aprovação)
$leads_docs_pendentes_equipe = 0; // Leads onde documentos estão pendentes/rejeitados
$leads_convertidos_venda_equipe = 0;
$leads_dispensados_equipe = 0;

if ($imobiliaria_id) {
    try {
        // KPI: Total de Leads em Gestão (solicitada e todos os status em andamento)
        $sql_total_leads_gestao = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status NOT IN ('vendida', 'cancelada', 'expirada') {$imobiliaria_filter_clause}";
        $result_total_leads_gestao = fetch_single($sql_total_leads_gestao, $imobiliaria_filter_params, $imobiliaria_filter_types);
        $total_leads_gestao = $result_total_leads_gestao['total'] ?? 0;

        // KPI: Leads Aguardando Atendimento (solicitada)
        $sql_aguardando_atendimento = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status = 'solicitada' {$imobiliaria_filter_clause}";
        $result_aguardando_atendimento = fetch_single($sql_aguardando_atendimento, $imobiliaria_filter_params, $imobiliaria_filter_types);
        $leads_aguardando_atendimento_equipe = $result_aguardando_atendimento['total'] ?? 0;

        // KPI: Leads com Documentação Pendente/Rejeitada
        $sql_docs_pendentes = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status IN ('documentos_pendentes', 'documentos_rejeitados') {$imobiliaria_filter_clause}";
        $result_docs_pendentes = fetch_single($sql_docs_pendentes, $imobiliaria_filter_params, $imobiliaria_filter_types);
        $leads_docs_pendentes_equipe = $result_docs_pendentes['total'] ?? 0;

        // KPI: Leads Convertidos em Venda
        $sql_convertidos_venda = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status = 'vendida' {$imobiliaria_filter_clause}";
        $result_convertidos_venda = fetch_single($sql_convertidos_venda, $imobiliaria_filter_params, $imobiliaria_filter_types);
        $leads_convertidos_venda_equipe = $result_convertidos_venda['total'] ?? 0;

        // KPI: Leads Dispensados/Cancelados
        $sql_dispensados = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status IN ('dispensada', 'cancelada', 'expirada') {$imobiliaria_filter_clause}";
        $result_dispensados = fetch_single($sql_dispensados, $imobiliaria_filter_params, $imobiliaria_filter_types);
        $leads_dispensados_equipe = $result_dispensados['total'] ?? 0;

    } catch (Exception $e) {
        error_log("Erro ao carregar KPIs de leads da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os indicadores de leads da equipe: " . $e->getMessage();
    }
}


// --- Parâmetros de Filtro para a Tabela de Leads ---
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW); // 'solicitada', 'em_atendimento', 'docs_pendentes', 'dispensada'
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';


// --- Listagem de Leads para a Tabela ---
if ($imobiliaria_id) {
    try {
        $sql_leads_table = "
            SELECT
                r.id AS reserva_id,
                r.data_reserva,
                r.valor_reserva,
                r.status,
                r.data_ultima_interacao,
                COALESCE(cl.nome, 'N/A') AS cliente_nome,
                cl.whatsapp AS cliente_whatsapp,
                cl.email AS cliente_email,
                u_corretor.nome AS corretor_nome, -- Corretor da equipe
                e.nome AS empreendimento_nome,
                un.numero AS unidade_numero,
                un.andar AS unidade_andar,
                COALESCE(uiu.nome, 'Sistema/Não Identificado') AS usuario_ultima_interacao_nome
            FROM
                reservas r
            LEFT JOIN
                reservas_clientes rc ON r.id = rc.reserva_id
            LEFT JOIN
                clientes cl ON rc.cliente_id = cl.id
            JOIN
                unidades un ON r.unidade_id = un.id
            JOIN
                empreendimentos e ON un.empreendimento_id = e.id
            JOIN
                usuarios u_corretor ON r.corretor_id = u_corretor.id -- JOIN obrigatório para filtrar por corretor da imobiliária
            WHERE
                u_corretor.imobiliaria_id = ?
        ";
        $params = [$imobiliaria_id];
        $types = "i";

        // Aplica filtro de busca (se houver)
        if ($search_term) {
            $sql_leads_table .= " AND (cl.nome LIKE ? OR cl.whatsapp LIKE ? OR cl.email LIKE ? OR u_corretor.nome LIKE ? OR e.nome LIKE ? OR un.numero LIKE ?)";
            $search_param = '%' . $search_term . '%';
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
            $types .= "ssssss";
        }

        // Aplica filtro de status (se houver)
        if ($filter_status) {
            if ($filter_status === 'solicitada') { // Aguardando Atendimento
                $sql_leads_table .= " AND r.status = 'solicitada'";
            } elseif ($filter_status === 'em_atendimento') { // Aprovada e em fluxo
                $sql_leads_table .= " AND r.status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica')";
            } elseif ($filter_status === 'docs_pendentes') { // Documentação pendente
                $sql_leads_table .= " AND r.status IN ('documentos_pendentes', 'documentos_rejeitados')";
            } elseif ($filter_status === 'dispensada') { // Dispensados/Cancelados/Expirados
                $sql_leads_table .= " AND r.status IN ('dispensada', 'cancelada', 'expirada')";
            } else {
                // Filtra por um status exato se não for uma categoria especial
                $sql_leads_table .= " AND r.status = ?";
                $params[] = $filter_status;
                $types .= "s";
            }
        } else {
            // Por padrão, mostra todos os leads que não foram vendidos ou cancelados/expirados
            $sql_leads_table .= " AND r.status NOT IN ('vendida', 'cancelada', 'expirada')";
        }

        // Adiciona ordenação
        $order_clause = "r.data_reserva DESC"; // Padrão
        if ($sort_by) {
            $db_column_map = [
                'reserva_id' => 'r.id',
                'data_reserva' => 'r.data_reserva',
                'cliente_nome' => 'cl.nome',
                'corretor_nome' => 'u_corretor.nome',
                'empreendimento_nome' => 'e.nome',
                'unidade_numero' => 'un.numero',
                'status' => 'r.status',
                'data_ultima_interacao' => 'r.data_ultima_interacao',
                'usuario_ultima_interacao_nome' => 'usuario_ultima_interacao_nome'
            ];
            if (isset($db_column_map[$sort_by])) {
                $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
            }
        }
        $sql_leads_table .= " ORDER BY {$order_clause}";

        $leads = fetch_all($sql_leads_table, $params, $types);

    } catch (Exception $e) {
        error_log("Erro ao carregar lista de leads da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os leads da equipe: " . $e->getMessage();
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
            <span class="kpi-label">Leads em Gestão</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_leads_gestao); ?></span>
            <small>Total de leads da equipe</small>
        </div>
        <div class="kpi-card kpi-card-info">
            <span class="kpi-label">Aguardando Atendimento</span>
            <span class="kpi-value"><?php echo htmlspecialchars($leads_aguardando_atendimento_equipe); ?></span>
            <small>Novas solicitações</small>
        </div>
        <div class="kpi-card kpi-card-warning">
            <span class="kpi-label">Docs. Pendentes</span>
            <span class="kpi-value"><?php echo htmlspecialchars($leads_docs_pendentes_equipe); ?></span>
            <small>Aguardando documentos</small>
        </div>
        <div class="kpi-card kpi-card-success">
            <span class="kpi-label">Convertidos em Venda</span>
            <span class="kpi-value"><?php echo htmlspecialchars($leads_convertidos_venda_equipe); ?></span>
            <small>Leads que viraram vendas</small>
        </div>
        <div class="kpi-card kpi-card-danger">
            <span class="kpi-label">Dispensados</span>
            <span class="kpi-value"><?php echo htmlspecialchars($leads_dispensados_equipe); ?></span>
            <small>Leads não aproveitados</small>
        </div>
    </div>
    <div class="admin-controls-bar">
        <div class="search-box">
            <input type="text" id="leadSearch" name="search" placeholder="Buscar lead..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
            <i class="fas fa-search"></i>
        </div>
        <div class="filters-box">
            <select id="leadFilterStatus" name="status">
                <option value="">Todos os Status</option>
                <option value="solicitada" <?php echo ($filter_status === 'solicitada') ? 'selected' : ''; ?>>Aguardando Atendimento</option>
                <option value="em_atendimento" <?php echo ($filter_status === 'em_atendimento') ? 'selected' : ''; ?>>Em Atendimento</option>
                <option value="docs_pendentes" <?php echo ($filter_status === 'docs_pendentes') ? 'selected' : ''; ?>>Docs. Pendentes</option>
                <option value="dispensada" <?php echo ($filter_status === 'dispensada') ? 'selected' : ''; ?>>Dispensados</option>
                <option value="aprovada" <?php echo ($filter_status === 'aprovada') ? 'selected' : ''; ?>>Aprovada (Específico)</option>
                <option value="documentos_enviados" <?php echo ($filter_status === 'documentos_enviados') ? 'selected' : ''; ?>>Docs. Enviados (Específico)</option>
                <option value="documentos_aprovados" <?php echo ($filter_status === 'documentos_aprovados') ? 'selected' : ''; ?>>Docs. Aprovados (Específico)</option>
                <option value="documentos_rejeitados" <?php echo ($filter_status === 'documentos_rejeitados') ? 'selected' : ''; ?>>Docs. Rejeitados (Específico)</option>
                <option value="contrato_enviado" <?php echo ($filter_status === 'contrato_enviado') ? 'selected' : ''; ?>>Contrato Enviado (Específico)</option>
                <option value="aguardando_assinatura_eletronica" <?php echo ($filter_status === 'aguardando_assinatura_eletronica') ? 'selected' : ''; ?>>Aguardando Assinatura (Específico)</option>
            </select>
            <button type="submit" class="btn btn-primary" id="applyLeadFiltersBtn">Aplicar Filtros</button>
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
                    <th data-sort-by="corretor_nome">Corretor <i class="fas fa-sort"></i></th>
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
                    <tr><td colspan="11" style="text-align: center;">Nenhum lead encontrado para a equipe com os filtros aplicados.</td></tr>
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
                            <td><?php echo htmlspecialchars($lead['corretor_nome']); ?></td>
                            <td><?php echo htmlspecialchars($lead['empreendimento_nome']); ?></td>
                            <td><?php echo htmlspecialchars($lead['unidade_numero'] . ' (' . $lead['unidade_andar'] . 'º Andar)'); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($lead['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $lead['status']))); ?></span></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($lead['data_ultima_interacao'])); ?></td>
                            <td><?php echo htmlspecialchars($lead['usuario_ultima_interacao_nome'] ?? 'N/A'); ?></td>
                            <td class="admin-table-actions">
                                <?php if ($lead['status'] !== 'vendida' && $lead['status'] !== 'cancelada' && $lead['status'] !== 'expirada' && $lead['status'] !== 'dispensada'): ?>
                                    <button class="btn btn-danger btn-sm cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($lead['reserva_id']); ?>">Cancelar</button>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/detalhes.php?id=<?php echo htmlspecialchars($lead['reserva_id']); ?>" class="btn btn-info btn-sm mt-1">Ver Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="cancelReservaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Cancelamento da Reserva</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja **cancelar** esta reserva? Esta ação não pode ser desfeita e a unidade voltará a ficar disponível.</p>
                <form id="cancelReservaForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="cancel_reserva">
                    <input type="hidden" name="reserva_id" id="cancelReservaId">
                    <div class="form-group">
                        <label for="cancelReasonReserva">Motivo do Cancelamento (Opcional):</label>
                        <textarea id="cancelReasonReserva" name="motivo_cancelamento" rows="3" placeholder="Ex: Cliente desistiu, documentação inválida."></textarea>
                    </div>
                    <div class="form-actions" style="justify-content: space-around;">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>

<?php require_once '../../includes/footer_dashboard.php'; ?>