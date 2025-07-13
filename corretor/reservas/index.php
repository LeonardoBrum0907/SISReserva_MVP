<?php
// corretor/reservas/index.php - Listagem de Reservas do Corretor

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para format_datetime_br, format_currency_brl
require_once '../../includes/alerts.php';   // Para create_alert e geração de tokens

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em corretor/reservas/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Corretor
require_permission(['corretor_autonomo', 'corretor_imobiliaria']);

$page_title = "Minhas Reservas";

$logged_user_info = get_user_info();
$corretor_id = $logged_user_info['id'];

$reservas = [];
$errors = [];
$success_message = '';

// Lida com mensagens da sessão (se houverem, após um POST de ação)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']); // Limpa as mensagens após exibir
}

// --- KPIs para a página de Reservas do Corretor ---
$total_reservas_ativas_corretor = 0;
$reservas_docs_pendentes_corretor = 0;
$reservas_aguardando_contrato_corretor = 0;
$reservas_canceladas_expiradas_corretor = 0;

try {
    // KPI: Total de Reservas Ativas (todas em andamento, exceto vendidas/canceladas/expiradas)
    $sql_ativas = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada')";
    $result_ativas = fetch_single($sql_ativas, [$corretor_id], "i");
    $total_reservas_ativas_corretor = $result_ativas['total'] ?? 0;

    // KPI: Reservas com Documentos Pendentes/Rejeitados (aguardando ação do corretor/cliente)
    $sql_docs_pendentes = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status IN ('documentos_pendentes', 'documentos_rejeitados')";
    $result_docs_pendentes = fetch_single($sql_docs_pendentes, [$corretor_id], "i");
    $reservas_docs_pendentes_corretor = $result_docs_pendentes['total'] ?? 0;

    // KPI: Reservas Aguardando Contrato (documentos aprovados, contrato enviado, aguardando assinatura)
    $sql_aguardando_contrato = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status IN ('documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica')";
    $result_aguardando_contrato = fetch_single($sql_aguardando_contrato, [$corretor_id], "i");
    $reservas_aguardando_contrato_corretor = $result_aguardando_contrato['total'] ?? 0;

    // KPI: Reservas Canceladas ou Expiradas
    $sql_canceladas_expiradas = "SELECT COUNT(id) AS total FROM reservas WHERE corretor_id = ? AND status IN ('cancelada', 'expirada', 'dispensada')";
    $result_canceladas_expiradas = fetch_single($sql_canceladas_expiradas, [$corretor_id], "i");
    $reservas_canceladas_expiradas_corretor = $result_canceladas_expiradas['total'] ?? 0;

} catch (Exception $e) {
    error_log("Erro ao carregar KPIs de reservas do corretor: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar os indicadores de suas reservas: " . $e->getMessage();
}


// Parâmetros de filtro e ordenação da URL
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';

try {
    // Consulta para buscar as reservas do corretor logado
    $sql_reservas = "
        SELECT
            r.id AS reserva_id,
            r.data_reserva,
            r.valor_reserva,
            r.status,
            r.data_ultima_interacao,
            COALESCE(cl.nome, 'N/A') AS cliente_nome,
            e.nome AS empreendimento_nome,
            u.numero AS unidade_numero,
            u.andar AS unidade_andar,
            u.posicao AS unidade_posicao,
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
        $sql_reservas .= " AND (cl.nome LIKE ? OR e.nome LIKE ? OR u.numero LIKE ?)";
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $types .= "sss";
    }

    // Aplica filtro de status (se houver)
    if ($filter_status) {
        $sql_reservas .= " AND r.status = ?";
        $params[] = $filter_status;
        $types .= "s";
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
            'valor_reserva' => 'r.valor_reserva',
            'status' => 'r.status',
            'data_ultima_interacao' => 'r.data_ultima_interacao',
            'usuario_ultima_interacao_nome' => 'usuario_ultima_interacao_nome'
        ];
        if (isset($db_column_map[$sort_by])) {
            $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
        }
    }
    $sql_reservas .= " ORDER BY {$order_clause}";

    $reservas = fetch_all($sql_reservas, $params, $types);

} catch (Exception $e) {
    error_log("Erro ao carregar reservas do corretor: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar suas reservas: " . $e->getMessage();
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
            <span class="kpi-label">Total Ativas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_ativas_corretor); ?></span>
            <small>Reservas em andamento</small>
        </div>
        <div class="kpi-card kpi-card-warning">
            <span class="kpi-label">Docs. Pendentes</span>
            <span class="kpi-value"><?php echo htmlspecialchars($reservas_docs_pendentes_corretor); ?></span>
            <small>Aguardando documentos</small>
        </div>
        <div class="kpi-card kpi-card-info">
            <span class="kpi-label">Aguardando Contrato</span>
            <span class="kpi-value"><?php echo htmlspecialchars($reservas_aguardando_contrato_corretor); ?></span>
            <small>Prontas para formalizar</small>
        </div>
        <div class="kpi-card kpi-card-danger">
            <span class="kpi-label">Canceladas/Expiradas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($reservas_canceladas_expiradas_corretor); ?></span>
            <small>Reservas finalizadas</small>
        </div>
    </div>
    <div class="admin-controls-bar">
        <div class="search-box">
            <input type="text" id="reservaSearch" placeholder="Buscar reserva..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
            <i class="fas fa-search"></i>
        </div>
        <div class="filters-box">
            <select id="reservaFilterStatus">
                <option value="">Todos os Status</option>
                <option value="solicitada" <?php echo ($filter_status === 'solicitada') ? 'selected' : ''; ?>>Solicitada</option>
                <option value="aprovada" <?php echo ($filter_status === 'aprovada') ? 'selected' : ''; ?>>Aprovada</option>
                <option value="documentos_pendentes" <?php echo ($filter_status === 'documentos_pendentes') ? 'selected' : ''; ?>>Docs. Pendentes</option>
                <option value="documentos_enviados" <?php echo ($filter_status === 'documentos_enviados') ? 'selected' : ''; ?>>Docs. Enviados</option>
                <option value="documentos_aprovados" <?php echo ($filter_status === 'documentos_aprovados') ? 'selected' : ''; ?>>Docs. Aprovados</option>
                <option value="documentos_rejeitados" <?php echo ($filter_status === 'documentos_rejeitados') ? 'selected' : ''; ?>>Docs. Rejeitados</option>
                <option value="contrato_enviado" <?php echo ($filter_status === 'contrato_enviado') ? 'selected' : ''; ?>>Contrato Enviado</option>
                <option value="aguardando_assinatura_eletronica" <?php echo ($filter_status === 'aguardando_assinatura_eletronica') ? 'selected' : ''; ?>>Aguardando Assinatura</option>
                <option value="vendida" <?php echo ($filter_status === 'vendida') ? 'selected' : ''; ?>>Vendida</option>
                <option value="cancelada" <?php echo ($filter_status === 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                <option value="expirada" <?php echo ($filter_status === 'expirada') ? 'selected' : ''; ?>>Expirada</option>
            </select>
            <button class="btn btn-primary" id="applyReservaFiltersBtn">Aplicar Filtros</button>
        </div>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="reservasTable">
            <thead>
                <tr>
                    <th data-sort-by="reserva_id">ID <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_reserva">Data <i class="fas fa-sort"></i></th>
                    <th data-sort-by="empreendimento_nome">Empreendimento <i class="fas fa-sort"></i></th>
                    <th data-sort-by="unidade_numero">Unidade <i class="fas fa-sort"></i></th>
                    <th data-sort-by="cliente_nome">Cliente <i class="fas fa-sort"></i></th>
                    <th data-sort-by="valor_reserva">Valor <i class="fas fa-sort"></i></th>
                    <th data-sort-by="status">Status <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_ultima_interacao">Última Interação <i class="fas fa-sort"></i></th>
                    <th data-sort-by="usuario_ultima_interacao_nome">Por <i class="fas fa-sort"></i></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="10" style="text-align: center;">Nenhuma reserva encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>" data-reserva-status="<?php echo htmlspecialchars($reserva['status']); ?>">
                            <td><?php echo htmlspecialchars($reserva['reserva_id']); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($reserva['data_reserva'])); ?></td>
                            <td><?php echo htmlspecialchars($reserva['empreendimento_nome']); ?></td>
                            <td><?php echo htmlspecialchars($reserva['unidade_numero'] . ' (' . $reserva['unidade_andar'] . 'º Andar)'); ?></td>
                            <td><?php echo htmlspecialchars($reserva['cliente_nome']); ?></td>
                            <td><?php echo format_currency_brl($reserva['valor_reserva']); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($reserva['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva['status']))); ?></span></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($reserva['data_ultima_interacao'])); ?></td>
                            <td><?php echo htmlspecialchars($reserva['usuario_ultima_interacao_nome'] ?? 'N/A'); ?></td>
                            <td class="admin-table-actions">
                                <?php if ($reserva['status'] === 'documentos_pendentes' || $reserva['status'] === 'documentos_rejeitados'): ?>
                                    <?php
                                        // Gerar token único para upload de documentos
                                        $token = generate_token();
                                        $token_expiracao = date('Y-m-d H:i:s', strtotime('+7 days'));
                                        
                                        // Acha o cliente principal da reserva para vincular o token
                                        $cliente_principal_reserva = fetch_single("SELECT cliente_id FROM reservas_clientes WHERE reserva_id = ? LIMIT 1", [$reserva['reserva_id']], "i");
                                        $cliente_id_para_token = $cliente_principal_reserva['cliente_id'] ?? null;
                                        
                                        $upload_link = '#'; // Fallback link
                                        if ($cliente_id_para_token) {
                                            $inserted_token_id = insert_data(
                                                "INSERT INTO documentos_upload_tokens (reserva_id, cliente_id, token, data_expiracao, utilizado) VALUES (?, ?, ?, ?, FALSE)",
                                                [$reserva['reserva_id'], $cliente_id_para_token, $token, $token_expiracao],
                                                "iiss"
                                            );
                                            if($inserted_token_id) {
                                                $upload_link = BASE_URL . 'public/documentos.php?token=' . urlencode($token);
                                            } else {
                                                error_log("Erro ao inserir token para reserva ID " . $reserva['reserva_id']);
                                            }
                                        } else {
                                            error_log("Cliente principal não encontrado para reserva ID " . $reserva['reserva_id'] . ". Não foi possível gerar link de upload.");
                                        }
                                    ?>
                                    <a href="<?php echo htmlspecialchars($upload_link); ?>" target="_blank" class="btn btn-warning btn-sm">Enviar Documentos</a>
                                <?php endif; ?>
                                <?php if ($reserva['status'] !== 'vendida' && $reserva['status'] !== 'cancelada' && $reserva['status'] !== 'expirada' && $reserva['status'] !== 'dispensada'): ?>
                                    <button class="btn btn-danger btn-sm cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Cancelar</button>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
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
                    <input type="hidden" name="action" value="cancel_reserva"> <input type="hidden" name="reserva_id" id="cancelReservaId">
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