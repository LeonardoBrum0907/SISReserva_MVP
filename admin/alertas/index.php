<?php
// admin/alertas/index.php - Painel de Gestão de Alertas

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../includes/alerts.php'; // Inclui alerts.php com _format_single_alert

// NOVO: Estabelecer a conexão com o banco de dados para esta página
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Falha ao iniciar conexão com o DB em admin/alertas/index.php: " . $e->getMessage());
    $errors_page_load[] = "Erro crítico de conexão com o banco de dados. Por favor, tente novamente mais tarde.";
    $conn = null; // Garante que $conn é null se a conexão falhar
}

// Obtém os dados do usuário logado
$logged_user_info = get_user_info();

require_permission(['admin', 'corretor_autonomo', 'corretor_imobiliaria', 'admin_imobiliaria']);

$page_title = "Gestão de Alertas";

$user_id = $logged_user_info['id'] ?? 0;
$alerts = [];
$errors_page_load = []; // Erros específicos desta página

// ======================================================================================================
// Lógica de KPIs para Alertas
// ======================================================================================================
$total_alertas = 0;
$alertas_nao_lidos = 0;
$alertas_lidos = 0;
$alertas_tipos_unicos = 0; // Novo KPI para contagem de tipos únicos

if ($user_id > 0 && $conn instanceof mysqli) {
    try {
        // KPI: Total de Alertas
        $sql_total_alerts_kpi = "SELECT COUNT(id) AS total FROM alertas WHERE usuario_id = ?";
        $total_alertas_kpi_result = fetch_single($sql_total_alerts_kpi, [$user_id], "i");
        $total_alertas = $total_alertas_kpi_result['total'] ?? 0;

        // KPI: Alertas Não Lidos
        $sql_unread_alerts_kpi = "SELECT COUNT(id) AS total FROM alertas WHERE usuario_id = ? AND lido = 0";
        $unread_alerts_kpi_result = fetch_single($sql_unread_alerts_kpi, [$user_id], "i");
        $alertas_nao_lidos = $unread_alerts_kpi_result['total'] ?? 0;

        // KPI: Alertas Lidos
        $sql_read_alerts_kpi = "SELECT COUNT(id) AS total FROM alertas WHERE usuario_id = ? AND lido = 1";
        $read_alerts_kpi_result = fetch_single($sql_read_alerts_kpi, [$user_id], "i");
        $alertas_lidos = $read_alerts_kpi_result['total'] ?? 0;

        // KPI: Contagem de Tipos de Alertas Únicos
        // Buscar links e extrair event_type, depois contar únicos
        $sql_unique_alert_types = "SELECT DISTINCT link FROM alertas WHERE usuario_id = ?";
        $unique_links = fetch_all($sql_unique_alert_types, [$user_id], "i");
        $unique_event_types = [];
        foreach($unique_links as $row) { // Corrigido $link_row para $row
            // CORREÇÃO AQUI: Verifica se $row['link'] não é nulo ou vazio antes de parse_str
            if (!empty($row['link'])) {
                parse_str($row['link'], $link_params);
                if (isset($link_params['event_type'])) {
                    $unique_event_types[$link_params['event_type']] = true; // Usa um array associativo para contagem única
                }
            }
        }
        $alertas_tipos_unicos = count($unique_event_types);

    } catch (Exception $e) {
        error_log("Erro ao carregar KPIs de alertas: " . $e->getMessage());
        $errors_page_load[] = "Ocorreu um erro ao carregar os indicadores de alertas: " . $e->getMessage();
    }
}


if ($user_id > 0 && $conn instanceof mysqli) {
    try {
        // Paginação
        $limit = 10;
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $offset = ($page - 1) * $limit;

        // Obter alertas com base nos filtros da URL
        $filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);
        $filter_type = filter_input(INPUT_GET, 'type', FILTER_UNSAFE_RAW);
        $search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
        $sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
        $sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';


        $sql_alerts = "SELECT id, usuario_id, titulo, mensagem, link, lido, data_criacao FROM alertas WHERE usuario_id = ?";
        $params_alerts = [$user_id];
        $types_alerts = "i";

        if ($filter_status === 'unread') {
            $sql_alerts .= " AND lido = 0";
        } elseif ($filter_status === 'read') {
            $sql_alerts .= " AND lido = 1";
        }

        if ($filter_type && $filter_type !== '') {
            $sql_alerts .= " AND link LIKE ?";
            $params_alerts[] = "%event_type={$filter_type}%";
            $types_alerts .= "s";
        }

        if ($search_term) {
            $sql_alerts .= " AND (titulo LIKE ? OR mensagem LIKE ?)";
            $params_alerts[] = '%' . $search_term . '%';
            $params_alerts[] = '%' . $search_term . '%';
            $types_alerts .= "ss";
        }

        $order_clause = "data_criacao DESC";
        if ($sort_by) {
            $db_column_map = [
                'title' => 'titulo',
                'message' => 'mensagem',
                'type' => 'link', // Tipo está no link_key
                'created_at' => 'data_criacao',
                'is_read' => 'lido',
            ];
            if (isset($db_column_map[$sort_by])) {
                $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
            }
        }
        $sql_alerts .= " ORDER BY {$order_clause} LIMIT ? OFFSET ?";
        $params_alerts[] = $limit;
        $params_alerts[] = $offset;
        $types_alerts .= "ii";

        $raw_alerts_data = fetch_all($sql_alerts, $params_alerts, $types_alerts);

        $alerts = [];
        // Processa os alertas crus para adicionar o 'link' final usando _format_single_alert
        foreach ($raw_alerts_data as $alert_row) {
            $alerts[] = _format_single_alert($alert_row); // AQUI, USE A FUNÇÃO _format_single_alert
        }

        // Contar o total de alertas para a paginação (com base nos filtros)
        $sql_count_alerts = "SELECT COUNT(id) AS total FROM alertas WHERE usuario_id = ?";
        $params_count = [$user_id];
        $types_count = "i";

        if ($filter_status === 'unread') {
            $sql_count_alerts .= " AND lido = 0";
        } elseif ($filter_status === 'read') {
            $sql_count_alerts .= " AND lido = 1";
        }

        if ($filter_type && $filter_type !== '') {
            $sql_count_alerts .= " AND link LIKE ?";
            $params_count[] = "%event_type={$filter_type}%";
            $types_count .= "s";
        }

        if ($search_term) {
            $sql_count_alerts .= " AND (titulo LIKE ? OR mensagem LIKE ?)";
            $params_count[] = '%' . $search_term . '%';
            $params_count[] = '%' . $search_term . '%';
            $types_count .= "ss";
        }

        $total_alerts_query = fetch_single($sql_count_alerts, $params_count, $types_count);
        $total_alerts = $total_alerts_query['total'] ?? 0;
        $total_pages = ceil($total_alerts / $limit);

    } catch (Exception $e) {
        error_log("Erro ao carregar/gerar alertas: " . $e->getMessage());
        $errors_page_load[] = "Ocorreu um erro ao carregar ou gerar seus alertas: " . $e->getMessage();
    }
} else {
    $errors_page_load[] = "ID de usuário não identificado ou conexão com o banco de dados não estabelecida. Por favor, faça login novamente.";
}

require_once '../../includes/header_dashboard.php';

?>

<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>

    <?php if (!empty($errors_page_load)): ?>
        <div class="message-box message-box-error">
            <?php foreach ($errors_page_load as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Total de Alertas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_alertas); ?></span>
            <small>Alertas no sistema</small>
        </div>
        <div class="kpi-card kpi-card-warning">
            <span class="kpi-label">Não Lidos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($alertas_nao_lidos); ?></span>
            <small>Aguardando sua atenção</small>
        </div>
        <div class="kpi-card kpi-card-success">
            <span class="kpi-label">Alertas Lidos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($alertas_lidos); ?></span>
            <small>Alertas já processados</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Tipos de Alertas Únicos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($alertas_tipos_unicos); ?></span>
            <small>Categorias de notificação</small>
        </div>
    </div>
    <div class="admin-controls-bar">
        <form method="GET" action="<?php echo BASE_URL; ?>admin/alertas/index.php" id="alertsFilterForm">
            <div class="search-box">
                <input type="text" id="alertSearch" name="search" placeholder="Buscar alerta..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
                <i class="fas fa-search"></i>
            </div>
            <div class="filters-box">
                <select id="alertFilterStatus" name="status">
                    <option value="">Todos os Status</option>
                    <option value="unread" <?php echo ($filter_status === 'unread') ? 'selected' : ''; ?>>Não Lidos</option>
                    <option value="read" <?php echo ($filter_status === 'read') ? 'selected' : ''; ?>>Lidos</option>
                </select>
                <select id="alertFilterType" name="type">
                    <option value="">Todos os Tipos</option>
                    <option value="venda_concluida" <?php echo ($filter_type === 'venda_concluida') ? 'selected' : ''; ?>>Venda Concluída</option>
                    <option value="novo_lead" <?php echo ($filter_type === 'novo_lead') ? 'selected' : ''; ?>>Novo Lead</option>
                    <option value="novo_corretor_cadastro" <?php echo ($filter_type === 'novo_corretor_cadastro') ? 'selected' : ''; ?>>Novo Corretor</option>
                    <option value="corretor_aprovado" <?php echo ($filter_type === 'corretor_aprovado') ? 'selected' : ''; ?>>Corretor Aprovado</option>
                    <option value="reserva_aprovada" <?php echo ($filter_type === 'reserva_aprovada') ? 'selected' : ''; ?>>Reserva Aprovada</option>
                    <option value="solicitar_documentacao" <?php echo ($filter_type === 'solicitar_documentacao') ? 'selected' : ''; ?>>Docs. Pendentes</option>
                    <option value="documentos_enviados" <?php echo ($filter_type === 'documentos_enviados') ? 'selected' : ''; ?>>Docs. Enviados</option>
                    <option value="documentos_aprovados" <?php echo ($filter_type === 'documentos_aprovados') ? 'selected' : ''; ?>>Docs. Aprovados</option>
                    <option value="documentos_rejeitados" <?php echo ($filter_type === 'documentos_rejeitados') ? 'selected' : ''; ?>>Docs. Rejeitados</option>
                    <option value="contrato_enviado" <?php echo ($filter_type === 'contrato_enviado') ? 'selected' : ''; ?>>Contrato Enviado</option>
                    <option value="reserva_cancelada" <?php echo ($filter_type === 'reserva_cancelada') ? 'selected' : ''; ?>>Reserva Cancelada</option>
                    <option value="notificacao_geral" <?php echo ($filter_type === 'notificacao_geral') ? 'selected' : ''; ?>>Geral</option>
                    <option value="nova_imobiliaria" <?php echo ($filter_type === 'nova_imobiliaria') ? 'selected' : ''; ?>>Nova Imobiliária</option>
                    <option value="status_usuario_alterado" <?php echo ($filter_type === 'status_usuario_alterado') ? 'selected' : ''; ?>>Status Usuário</option>
                    <option value="nova_reserva" <?php echo ($filter_type === 'nova_reserva') ? 'selected' : ''; ?>>Nova Reserva</option>
                </select>
                <button type="submit" class="btn btn-primary" id="applyFiltersBtn">Aplicar Filtros</button>
                <button type="button" class="btn btn-warning" id="markAllAsReadBtn">Marcar Todos como Lidos</button>
            </div>
        </form>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="alertsTable">
            <thead>
                <tr>
                    <th data-sort-by="title">Título <i class="fas fa-sort"></i></th>
                    <th data-sort-by="message" class="wrap-text">Mensagem <i class="fas fa-sort"></i></th>
                    <th data-sort-by="type">Tipo <i class="fas fa-sort"></i></th>
                    <th data-sort-by="created_at">Data <i class="fas fa-sort"></i></th>
                    <th data-sort-by="is_read">Status <i class="fas fa-sort"></i></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alerts)): ?>
                    <tr><td colspan="6" style="text-align: center;">Nenhum alerta encontrado com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($alerts as $alert): ?>
                        <tr data-alert-id="<?php echo htmlspecialchars($alert['id']); ?>"
                            data-alert-read="<?php echo $alert['is_read'] ? 'read' : 'unread'; ?>"
                            data-alert-type="<?php echo htmlspecialchars($alert['event_type'] ?? ''); ?>">
                            <td><?php echo htmlspecialchars($alert['title']); ?></td>
                            <td class="wrap-text"><?php echo htmlspecialchars($alert['message']); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $alert['event_type'] ?? ''))); ?></span></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($alert['created_at'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $alert['is_read'] ? 'status-info' : 'status-danger'; ?>">
                                    <?php echo $alert['is_read'] ? 'Lido' : 'Não Lido'; ?>
                                </span>
                            </td>
                            <td class="admin-table-actions">
                                <?php if (!empty($alert['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($alert['link']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                <?php endif; ?>
                                <?php if (!$alert['is_read']): ?>
                                    <button class="btn btn-primary btn-sm mark-read-btn" data-alert-id="<?php echo htmlspecialchars($alert['id']); ?>">Marcar como Lido</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="Paginação de Alertas" class="pagination-container">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo htmlspecialchars($filter_status ?? ''); ?>&type=<?php echo htmlspecialchars($filter_type ?? ''); ?>&search=<?php echo htmlspecialchars($search_term ?? ''); ?>&sort_by=<?php echo htmlspecialchars($sort_by ?? ''); ?>&sort_order=<?php echo htmlspecialchars($sort_order ?? ''); ?>">Anterior</a></li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo htmlspecialchars($filter_status ?? ''); ?>&type=<?php echo htmlspecialchars($filter_type ?? ''); ?>&search=<?php echo htmlspecialchars($search_term ?? ''); ?>&sort_by=<?php echo htmlspecialchars($sort_by ?? ''); ?>&sort_order=<?php echo htmlspecialchars($sort_order ?? ''); ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo htmlspecialchars($filter_status ?? ''); ?>&type=<?php echo htmlspecialchars($filter_type ?? ''); ?>&search=<?php echo htmlspecialchars($search_term ?? ''); ?>&sort_by=<?php echo htmlspecialchars($sort_by ?? ''); ?>&sort_order=<?php htmlspecialchars($sort_order ?? ''); ?>">Próxima</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>