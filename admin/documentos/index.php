<?php
// admin/documentos/index.php - Painel de Gestão de Documentos (Admin Master)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../includes/alerts.php';

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/documentos/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Master
require_permission(['admin']);

$page_title = "Gestão de Documentos";

$documents = [];
$errors = [];
$success_message = '';

// Lida com mensagens da sessão (se houverem)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// Parâmetros de filtro e ordenação da URL
$filter_reserva_id = filter_input(INPUT_GET, 'reserva_id', FILTER_VALIDATE_INT);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW); // 'pendente', 'aprovado', 'rejeitado'
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';

// DEBUG: Adicionado para verificar o conteúdo da sessão nesta página
error_log("DEBUG admin/documentos/index.php: Conteúdo da Sessão: " . var_export($_SESSION, true));


// --- KPIs de Documentos ---
$total_documentos = 0;
$documentos_pendentes = 0;
$documentos_aprovados = 0;
$documentos_rejeitados = 0;

try {
    // Queries base para KPIs (podem ser otimizadas para uma única query se complexo)
    $sql_base_kpi = "SELECT COUNT(id) AS total FROM documentos_reserva";
    $sql_base_kpi_params = [];
    $sql_base_kpi_types = "";

    if ($filter_reserva_id) {
        $sql_base_kpi .= " WHERE reserva_id = ?";
        $sql_base_kpi_params[] = $filter_reserva_id;
        $sql_base_kpi_types .= "i";
    }

    // KPI: Total de Documentos
    $result_total = fetch_single($sql_base_kpi, $sql_base_kpi_params, $sql_base_kpi_types);
    $total_documentos = $result_total['total'] ?? 0;

    // KPI: Documentos Pendentes
    $sql_pendentes = $sql_base_kpi . ($filter_reserva_id ? " AND" : " WHERE") . " status = 'pendente'";
    $result_pendentes = fetch_single($sql_pendentes, $sql_base_kpi_params, $sql_base_kpi_types);
    $documentos_pendentes = $result_pendentes['total'] ?? 0;

    // KPI: Documentos Aprovados
    $sql_aprovados = $sql_base_kpi . ($filter_reserva_id ? " AND" : " WHERE") . " status = 'aprovado'";
    $result_aprovados = fetch_single($sql_aprovados, $sql_base_kpi_params, $sql_base_kpi_types);
    $documentos_aprovados = $result_aprovados['total'] ?? 0;

    // KPI: Documentos Rejeitados
    $sql_rejeitados = $sql_base_kpi . ($filter_reserva_id ? " AND" : " WHERE") . " status = 'rejeitado'";
    $result_rejeitados = fetch_single($sql_rejeitados, $sql_base_kpi_params, $sql_base_kpi_types);
    $documentos_rejeitados = $result_rejeitados['total'] ?? 0;

} catch (Exception $e) {
    error_log("Erro ao carregar KPIs de documentos: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar os indicadores de documentos: " . $e->getMessage();
}


// --- Listagem de Documentos para a Tabela ---
try {
    $sql_documents = "
        SELECT
            dr.id AS document_id,
            dr.reserva_id,
            dr.nome_documento,
            dr.caminho_arquivo,
            dr.data_upload,
            dr.status,
            dr.motivo_rejeicao,
            dr.data_analise,
            COALESCE(u.nome, 'N/A') AS usuario_analise_nome,
            COALESCE(cl.nome, 'N/A') AS cliente_nome,
            e.nome AS empreendimento_nome,
            un.numero AS unidade_numero
        FROM
            documentos_reserva dr
        LEFT JOIN
            usuarios u ON dr.usuario_analise_id = u.id
        LEFT JOIN
            reservas r ON dr.reserva_id = r.id
        LEFT JOIN
            reservas_clientes rc ON r.id = rc.reserva_id
        LEFT JOIN
            clientes cl ON rc.cliente_id = cl.id
        LEFT JOIN
            unidades un ON r.unidade_id = un.id
        LEFT JOIN
            empreendimentos e ON un.empreendimento_id = e.id
        WHERE 1=1
    ";
    $params = [];
    $types = "";

    if ($filter_reserva_id) {
        $sql_documents .= " AND dr.reserva_id = ?";
        $params[] = $filter_reserva_id;
        $types .= "i";
    }

    if ($filter_status && $filter_status !== '') {
        $sql_documents .= " AND dr.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    if ($search_term) {
        $sql_documents .= " AND (dr.nome_documento LIKE ? OR cl.nome LIKE ? OR e.nome LIKE ?)";
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $types .= "sss";
    }

    $order_clause = "dr.data_upload DESC";
    if ($sort_by) {
        $db_column_map = [
            'document_id' => 'dr.id',
            'reserva_id' => 'dr.reserva_id',
            'nome_documento' => 'dr.nome_documento',
            'data_upload' => 'dr.data_upload',
            'status' => 'dr.status',
            'cliente_nome' => 'cl.nome',
            'empreendimento_nome' => 'e.nome',
            'unidade_numero' => 'un.numero',
        ];
        if (isset($db_column_map[$sort_by])) {
            $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
        }
    }
    $sql_documents .= " ORDER BY {$order_clause}";

    $documents = fetch_all($sql_documents, $params, $types);

} catch (Exception $e) {
    error_log("Erro ao carregar lista de documentos: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar os documentos: " . $e->getMessage();
}


require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>

    <?php if (!empty($errors)) { ?>
        <div class="message-box message-box-error">
            <?php foreach ($errors as $error) { ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (!empty($success_message)) { ?>
        <div class="message-box message-box-success">
            <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
    <?php } ?>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Total de Documentos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_documentos); ?></span>
            <small>Documentos carregados</small>
        </div>
        <div class="kpi-card kpi-card-warning">
            <span class="kpi-label">Pendentes de Análise</span>
            <span class="kpi-value"><?php echo htmlspecialchars($documentos_pendentes); ?></span>
            <small>Aguardando sua ação</small>
        </div>
        <div class="kpi-card kpi-card-success">
            <span class="kpi-label">Documentos Aprovados</span>
            <span class="kpi-value"><?php echo htmlspecialchars($documentos_aprovados); ?></span>
            <small>Documentos válidos</small>
        </div>
        <div class="kpi-card kpi-card-danger">
            <span class="kpi-label">Documentos Rejeitados</span>
            <span class="kpi-value"><?php echo htmlspecialchars($documentos_rejeitados); ?></span>
            <small>Precisam de correção</small>
        </div>
    </div>
    <div class="admin-controls-bar">
        <form method="GET" action="<?php echo BASE_URL; ?>admin/documentos/index.php" id="documentosFilterForm">
            <div class="search-box">
                <input type="text" id="documentSearch" name="search" placeholder="Buscar documento..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
                <i class="fas fa-search"></i>
            </div>
            <div class="filters-box">
                <?php if ($filter_reserva_id) { ?>
                    <input type="hidden" name="reserva_id" value="<?php echo htmlspecialchars($filter_reserva_id); ?>">
                    <p style="margin-right: 15px;">**Filtrando por Reserva ID: #<?php echo htmlspecialchars($filter_reserva_id); ?>**</p>
                <?php } ?>
                <select id="documentFilterStatus" name="status">
                    <option value="">Todos os Status</option>
                    <option value="pendente" <?php echo ($filter_status === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                    <option value="aprovado" <?php echo ($filter_status === 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                    <option value="rejeitado" <?php echo ($filter_status === 'rejeitado') ? 'selected' : ''; ?>>Rejeitado</option>
                </select>
                <button type="submit" class="btn btn-primary" id="applyDocumentFiltersBtn">Aplicar Filtros</button>
                <?php if ($filter_reserva_id) { ?>
                    <a href="<?php echo BASE_URL; ?>admin/documentos/index.php" class="btn btn-secondary">Ver Todos os Documentos</a>
                <?php } ?>
            </div>
        </form>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="documentosTable">
            <thead>
                <tr>
                    <th data-sort-by="document_id">ID Doc <i class="fas fa-sort"></i></th>
                    <th data-sort-by="reserva_id">ID Reserva <i class="fas fa-sort"></i></th>
                    <th data-sort-by="nome_documento">Documento <i class="fas fa-sort"></i></th>
                    <th data-sort-by="cliente_nome">Cliente <i class="fas fa-sort"></i></th>
                    <th data-sort-by="empreendimento_nome">Empreendimento <i class="fas fa-sort"></i></th>
                    <th data-sort-by="unidade_numero">Unidade <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_upload">Data Upload <i class="fas fa-sort"></i></th>
                    <th data-sort-by="status">Status <i class="fas fa-sort"></i></th>
                    <th>Analisado Por</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)) { ?>
                    <tr><td colspan="10" style="text-align: center;">Nenhum documento encontrado com os filtros aplicados.</td></tr>
                <?php } else { ?>
                    <?php foreach ($documents as $doc) { ?>
                        <tr data-document-id="<?php echo htmlspecialchars($doc['document_id'] ?? ''); ?>"
                            data-document-status="<?php echo htmlspecialchars($doc['status'] ?? ''); ?>">
                            <td><?php echo htmlspecialchars($doc['document_id'] ?? ''); ?></td>
                            <td><a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($doc['reserva_id'] ?? ''); ?>"><?php echo htmlspecialchars($doc['reserva_id'] ?? ''); ?></a></td>
                            <td class="admin-table-text-overflow"><?php echo htmlspecialchars($doc['nome_documento'] ?? ''); ?></td>
                            <td class="admin-table-text-overflow"><?php echo htmlspecialchars($doc['cliente_nome'] ?? ''); ?></td>
                            <td class="admin-table-text-overflow"><?php echo htmlspecialchars($doc['empreendimento_nome'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($doc['unidade_numero'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($doc['data_upload'] ?? '')); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($doc['status'] ?? ''); ?>"><?php echo htmlspecialchars(ucfirst($doc['status'] ?? 'N/A')); ?></span></td>
                            <td><?php echo htmlspecialchars($doc['usuario_analise_nome'] ?? ''); ?></td>
                            <td class="admin-table-actions">
                                <?php if (!empty($doc['caminho_arquivo'])) { ?>
                                <a href="<?php echo BASE_URL . htmlspecialchars($doc['caminho_arquivo']); ?>" target="_blank" class="btn btn-info btn-sm" title="Ver Documento">
                                    <i class="fas fa-eye"></i> Ver Doc
                                </a>
                                <?php } else { ?>
                                <button class="btn btn-info btn-sm" disabled title="Documento não enviado ou caminho ausente"><i class="fas fa-eye-slash"></i> Sem Doc</button>
                                <?php } ?>

                                <?php 
                                    $logged_user_info_for_docs_table = get_user_info();
                                    $can_manage_docs = in_array($logged_user_info_for_docs_table['type'] ?? 'guest', ['admin']);
                                    if ($can_manage_docs) { 
                                ?>
                                    <?php if (($doc['status'] ?? '') === 'pendente' || ($doc['status'] ?? '') === 'rejeitado') { ?>
                                        <button class="btn btn-success btn-sm approve-document-btn" 
                                            data-document-id="<?php echo htmlspecialchars($doc['document_id']); ?>" 
                                            data-reserva-id="<?php echo htmlspecialchars($doc['reserva_id']); ?>" 
                                            data-nome="<?php echo htmlspecialchars($doc['nome_documento']); ?>" 
                                            data-status-atual="<?php echo htmlspecialchars($doc['status']); ?>" 
                                            data-motivo-rejeicao="<?php echo htmlspecialchars($doc['motivo_rejeicao'] ?? ''); ?>" 
                                            title="Aprovar Documento"><i class="fas fa-check"></i> Aprovar</button>
                                    <button class="btn btn-danger btn-sm reject-document-btn" 
                                            data-document-id="<?php echo htmlspecialchars($doc['document_id']); ?>" 
                                            data-reserva-id="<?php echo htmlspecialchars($doc['reserva_id']); ?>" 
                                            data-nome="<?php echo htmlspecialchars($doc['nome_documento']); ?>" 
                                            data-status-atual="<?php echo htmlspecialchars($doc['status']); ?>" 
                                            data-motivo-rejeicao="<?php echo htmlspecialchars($doc['motivo_rejeicao'] ?? ''); ?>" 
                                            title="Rejeitar Documento"><i class="fas fa-times"></i> Rejeitar</button>
                                    <?php } ?>
                                    <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($doc['reserva_id']); ?>" class="btn btn-secondary btn-sm" title="Ver Detalhes da Reserva">
                                    <i class="fas fa-eye"></i> Detalhes
                                </a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div id="approveDocumentModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Aprovar Documento</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja **aprovar** este documento (ID: <strong id="approveDocumentIdDisplay"></strong>) para a Reserva #<strong id="approveDocumentReservaIdDisplay"></strong>?</p>
                <form id="approveDocumentForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="approve_document">
                    <input type="hidden" name="document_id" id="approveDocumentIdInput">
                    <input type="hidden" name="reserva_id" id="approveDocumentReservaIdInput">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-success">Aprovar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="rejectDocumentModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rejeitar Documento</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja **rejeitar** este documento (ID: <strong id="rejectDocumentIdDisplay"></strong>) para a Reserva #<strong id="rejectDocumentReservaIdDisplay"></strong>?</p>
                <form id="rejectDocumentForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="reject_document">
                    <input type="hidden" name="document_id" id="rejectDocumentIdInput">
                    <input type="hidden" name="reserva_id" id="rejectDocumentReservaIdInput">
                    <div class="form-group">
                        <label for="rejectionReasonDoc">Motivo da Rejeição:</label>
                        <textarea id="rejectionReasonDoc" name="rejection_reason" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Rejeitar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>