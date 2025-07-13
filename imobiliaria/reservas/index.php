<?php
// imobiliaria/reservas/index.php - Gestão de Reservas da Equipe (Admin Imobiliária)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatar moeda, datas
require_once '../../includes/alerts.php';

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Falha ao iniciar conexão com o DB em imobiliaria/reservas/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

require_permission(['admin_imobiliaria']); // Apenas Admin Imobiliária pode ver reservas de sua equipe

$page_title = "Reservas da Equipe";

$logged_user_info = get_user_info();
$imobiliaria_id = null;

if ($logged_user_info && $logged_user_info['type'] === 'admin_imobiliaria') {
    $imobiliaria_data = fetch_single("SELECT id FROM imobiliarias WHERE admin_id = ?", [$logged_user_info['id']], "i");
    if ($imobiliaria_data) {
        $imobiliaria_id = $imobiliaria_data['id'];
    } else {
        // Redireciona com mensagem de erro se a imobiliária não for encontrada para o admin logado.
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Nenhuma imobiliária encontrada vinculada ao seu usuário. Por favor, contate o suporte.'];
        header("Location: " . BASE_URL . "imobiliaria/index.php");
        exit();
    }
} else {
    // Fallback defensivo, caso a permissão falhe ou o tipo de usuário seja inesperado.
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Acesso negado. Tipo de usuário inválido para esta área.'];
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$reservas = [];
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


// --- KPIs para a página de Reservas da Imobiliária ---
$total_reservas_equipe_ativas = 0;
$reservas_equipe_solicitadas = 0;
$reservas_equipe_docs_pendentes = 0;
$reservas_equipe_aguardando_contrato = 0;
$reservas_equipe_vendidas = 0;
$reservas_equipe_canceladas_expiradas = 0;

if ($imobiliaria_id) {
    try {
        // KPI: Total de Reservas da Equipe Ativas (não vendidas, canceladas, expiradas, dispensadas)
        $sql_ativas = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada') {$imobiliaria_filter_clause}";
        $params_ativas = $imobiliaria_filter_params;
        $types_ativas = $imobiliaria_filter_types;
        $result_ativas = fetch_single($sql_ativas, $params_ativas, $types_ativas);
        $total_reservas_equipe_ativas = $result_ativas['total'] ?? 0;

        // KPI: Reservas Solicitadas (novos leads atribuídos ou aguardando aprovação)
        $sql_solicitadas = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status = 'solicitada' {$imobiliaria_filter_clause}";
        $params_solicitadas = $imobiliaria_filter_params;
        $types_solicitadas = $imobiliaria_filter_types;
        $result_solicitadas = fetch_single($sql_solicitadas, $params_solicitadas, $types_solicitadas);
        $reservas_equipe_solicitadas = $result_solicitadas['total'] ?? 0;

        // KPI: Reservas com Documentos Pendentes/Rejeitados
        $sql_docs_pendentes = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status IN ('documentos_pendentes', 'documentos_rejeitados') {$imobiliaria_filter_clause}";
        $params_docs_pendentes = $imobiliaria_filter_params;
        $types_docs_pendentes = $imobiliaria_filter_types;
        $result_docs_pendentes = fetch_single($sql_docs_pendentes, $params_docs_pendentes, $types_docs_pendentes);
        $reservas_equipe_docs_pendentes = $result_docs_pendentes['total'] ?? 0;

        // KPI: Reservas Aguardando Contrato (documentos aprovados, contrato enviado, aguardando assinatura)
        $sql_aguardando_contrato = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status IN ('documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica') {$imobiliaria_filter_clause}";
        $params_aguardando_contrato = $imobiliaria_filter_params;
        $types_aguardando_contrato = $imobiliaria_filter_types;
        $result_aguardando_contrato = fetch_single($sql_aguardando_contrato, $params_aguardando_contrato, $types_aguardando_contrato);
        $reservas_equipe_aguardando_contrato = $result_aguardando_contrato['total'] ?? 0;

        // KPI: Reservas Vendidas
        $sql_vendidas = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status = 'vendida' {$imobiliaria_filter_clause}";
        $params_vendidas = $imobiliaria_filter_params;
        $types_vendidas = $imobiliaria_filter_types;
        $result_vendidas = fetch_single($sql_vendidas, $params_vendidas, $types_vendidas);
        $reservas_equipe_vendidas = $result_vendidas['total'] ?? 0;

        // KPI: Reservas Canceladas/Expiradas/Dispensadas
        $sql_canceladas_expiradas = "SELECT COUNT(id) AS total FROM reservas r WHERE r.status IN ('cancelada', 'expirada', 'dispensada') {$imobiliaria_filter_clause}";
        $params_canceladas_expiradas = $imobiliaria_filter_params;
        $types_canceladas_expiradas = $imobiliaria_filter_types;
        $result_canceladas_expiradas = fetch_single($sql_canceladas_expiradas, $params_canceladas_expiradas, $types_canceladas_expiradas);
        $reservas_equipe_canceladas_expiradas = $result_canceladas_expiradas['total'] ?? 0;

    } catch (Exception $e) {
        error_log("Erro ao carregar KPIs de reservas da equipe: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os indicadores de reservas da equipe: " . $e->getMessage();
    }
}


// Parâmetros de filtro e ordenação da URL
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';

// --- Listagem de Reservas para a Tabela ---
if ($imobiliaria_id) {
    try {
        // Buscar TODAS as reservas feitas pelos corretores desta imobiliária
        $sql_reservas = "
            SELECT
                r.id AS reserva_id,
                r.data_reserva,
                r.valor_reserva,
                r.status,
                r.data_ultima_interacao,
                COALESCE(cl.nome, 'N/A') AS cliente_nome,
                u_corretor.nome AS corretor_nome, -- Nome do corretor que fez a reserva
                e.nome AS empreendimento_nome,
                un.numero AS unidade_numero,
                un.andar AS unidade_andar,
                un.posicao AS unidade_posicao,
                COALESCE(uiu.nome, 'Sistema/Não Identificado') AS usuario_ultima_interacao_nome
            FROM
                reservas r
            JOIN
                usuarios u_corretor ON r.corretor_id = u_corretor.id
            LEFT JOIN
                reservas_clientes rc ON r.id = rc.reserva_id
            LEFT JOIN
                clientes cl ON rc.cliente_id = cl.id
            JOIN
                unidades un ON r.unidade_id = un.id
            JOIN
                empreendimentos e ON un.empreendimento_id = e.id
            LEFT JOIN
                usuarios uiu ON r.usuario_ultima_interacao = uiu.id
            WHERE
                u_corretor.imobiliaria_id = ?
        ";
        $params = [$imobiliaria_id];
        $types = "i";

        // Aplica filtro de busca (se houver)
        if ($search_term) {
            $sql_reservas .= " AND (cl.nome LIKE ? OR u_corretor.nome LIKE ? OR e.nome LIKE ? OR un.numero LIKE ?)";
            $params[] = '%' . $search_term . '%';
            $params[] = '%' . $search_term . '%';
            $params[] = '%' . $search_term . '%';
            $params[] = '%' . $search_term . '%';
            $types .= "ssss";
        }

        // Aplica filtro de status (se houver)
        if ($filter_status) {
            // Ajusta o filtro para corresponder aos KPIs e categorias relevantes
            if ($filter_status === 'em_andamento') {
                $sql_reservas .= " AND r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada')";
            } elseif ($filter_status === 'solicitada') {
                $sql_reservas .= " AND r.status = 'solicitada'";
            } elseif ($filter_status === 'docs_pendentes') {
                $sql_reservas .= " AND r.status IN ('documentos_pendentes', 'documentos_rejeitados')";
            } elseif ($filter_status === 'aguardando_contrato') {
                $sql_reservas .= " AND r.status IN ('documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica')";
            } elseif ($filter_status === 'vendida') {
                $sql_reservas .= " AND r.status = 'vendida'";
            } elseif ($filter_status === 'finalizadas_diversas') {
                $sql_reservas .= " AND r.status IN ('cancelada', 'expirada', 'dispensada')";
            } else {
                // Filtra por um status exato se não for uma categoria especial
                $sql_reservas .= " AND r.status = ?";
                $params[] = $filter_status;
                $types .= "s";
            }
        }

        // Adiciona ordenação
        $order_clause = "r.data_reserva DESC"; // Padrão
        if ($sort_by) {
            $db_column_map = [
                'reserva_id' => 'r.id',
                'data_reserva' => 'r.data_reserva',
                'empreendimento_nome' => 'e.nome',
                'unidade_numero' => 'un.numero',
                'cliente_nome' => 'cl.nome',
                'corretor_nome' => 'u_corretor.nome',
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
        error_log("Erro ao carregar reservas da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar as reservas da equipe: " . $e->getMessage();
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
            <span class="kpi-label">Total Ativas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_equipe_ativas); ?></span>
            <small>Reservas em andamento</small>
        </div>
        <div class="kpi-card kpi-card-info">
            <span class="kpi-label">Solicitadas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($reservas_equipe_solicitadas); ?></span>
            <small>Novos leads dos corretores</small>
        </div>
        <div class="kpi-card kpi-card-warning">
            <span class="kpi-label">Docs. Pendentes</span>
            <span class="kpi-value"><?php echo htmlspecialchars($reservas_equipe_docs_pendentes); ?></span>
            <small>Aguardando documentos/correção</small>
        </div>
        <div class="kpi-card kpi-card-primary">
            <span class="kpi-label">Aguardando Contrato</span>
            <span class="kpi-value"><?php echo htmlspecialchars($reservas_equipe_aguardando_contrato); ?></span>
            <small>Prontas para formalizar</small>
        </div>
        <div class="kpi-card kpi-card-success">
            <span class="kpi-label">Vendas Concluídas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($reservas_equipe_vendidas); ?></span>
            <small>Total de vendas da equipe</small>
        </div>
        <div class="kpi-card kpi-card-danger">
            <span class="kpi-label">Canceladas/Dispensadas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($reservas_equipe_canceladas_expiradas); ?></span>
            <small>Reservas não concretizadas</small>
        </div>
    </div>
    <div class="admin-controls-bar">
        <div class="search-box">
            <input type="text" id="reservaSearch" name="search" placeholder="Buscar reserva..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
            <i class="fas fa-search"></i>
        </div>
        <div class="filters-box">
            <select id="reservaFilterStatus" name="status">
                <option value="">Todos os Status</option>
                <option value="em_andamento" <?php echo ($filter_status === 'em_andamento') ? 'selected' : ''; ?>>Em Andamento</option>
                <option value="solicitada" <?php echo ($filter_status === 'solicitada') ? 'selected' : ''; ?>>Solicitada</option>
                <option value="docs_pendentes" <?php echo ($filter_status === 'docs_pendentes') ? 'selected' : ''; ?>>Docs. Pendentes</option>
                <option value="aguardando_contrato" <?php echo ($filter_status === 'aguardando_contrato') ? 'selected' : ''; ?>>Aguardando Contrato</option>
                <option value="vendida" <?php echo ($filter_status === 'vendida') ? 'selected' : ''; ?>>Vendidas</option>
                <option value="finalizadas_diversas" <?php echo ($filter_status === 'finalizadas_diversas') ? 'selected' : ''; ?>>Outras Finalizadas</option>
                <option value="aprovada" <?php echo ($filter_status === 'aprovada') ? 'selected' : ''; ?>>Aprovada (Específico)</option>
                <option value="documentos_enviados" <?php echo ($filter_status === 'documentos_enviados') ? 'selected' : ''; ?>>Docs. Enviados (Específico)</option>
                <option value="documentos_aprovados" <?php echo ($filter_status === 'documentos_aprovados') ? 'selected' : ''; ?>>Docs. Aprovados (Específico)</option>
                <option value="documentos_rejeitados" <?php echo ($filter_status === 'documentos_rejeitados') ? 'selected' : ''; ?>>Docs. Rejeitados (Específico)</option>
                <option value="contrato_enviado" <?php echo ($filter_status === 'contrato_enviado') ? 'selected' : ''; ?>>Contrato Enviado (Específico)</option>
                <option value="aguardando_assinatura_eletronica" <?php echo ($filter_status === 'aguardando_assinatura_eletronica') ? 'selected' : ''; ?>>Aguardando Assinatura (Específico)</option>
            </select>
            <button type="submit" class="btn btn-primary" id="applyReservaFiltersBtn">Aplicar Filtros</button>
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
                    <th data-sort-by="corretor_nome">Corretor <i class="fas fa-sort"></i></th>
                    <th data-sort-by="valor_reserva">Valor <i class="fas fa-sort"></i></th>
                    <th data-sort-by="status">Status <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_ultima_interacao">Última Interação <i class="fas fa-sort"></i></th>
                    <th data-sort-by="usuario_ultima_interacao_nome">Por <i class="fas fa-sort"></i></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="11" style="text-align: center;">Nenhuma reserva encontrada para a equipe com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>" data-reserva-status="<?php echo htmlspecialchars($reserva['status']); ?>">
                            <td><?php echo htmlspecialchars($reserva['reserva_id']); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($reserva['data_reserva'])); ?></td>
                            <td><?php echo htmlspecialchars($reserva['empreendimento_nome']); ?></td>
                            <td><?php echo htmlspecialchars($reserva['unidade_numero'] . ' (' . $reserva['unidade_andar'] . 'º Andar)'); ?></td>
                            <td><?php echo htmlspecialchars($reserva['cliente_nome']); ?></td>
                            <td><?php echo htmlspecialchars($reserva['corretor_nome']); ?></td>
                            <td><?php echo format_currency_brl($reserva['valor_reserva']); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($reserva['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva['status']))); ?></span></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($reserva['data_ultima_interacao'])); ?></td>
                            <td><?php echo htmlspecialchars($reserva['usuario_ultima_interacao_nome'] ?? 'N/A'); ?></td>
                            <td class="admin-table-actions">
                                <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/detalhes.php?id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes (Admin Master)</a>
                                <?php if ($reserva['status'] !== 'vendida' && $reserva['status'] !== 'cancelada' && $reserva['status'] !== 'expirada' && $reserva['status'] !== 'dispensada'): ?>
                                    <button class="btn btn-danger btn-sm cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Cancelar</button>
                                <?php endif; ?>
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