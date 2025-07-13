<?php
// admin/contratos/index.php - Painel de Gestão de Contratos (Admin Master)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação (CPF, WhatsApp, moeda, data)
require_once '../../includes/alerts.php'; // Para mensagens do sistema

// Inicializar a conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Falha ao iniciar conexão com o DB em admin/contratos/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

require_permission(['admin']); // Apenas Admin Master pode gerenciar contratos

$page_title = "Gestão de Contratos";

$errors = [];
$success_message = '';

// Recuperar mensagens da sessão
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// ======================================================================================================
// Lógica de Backend para KPIs de Contratos (Atualizada para NOVO status)
// ======================================================================================================

$contratos_aguardando_envio = 0;
$contratos_enviados_mes = 0;
$contratos_aguardando_assinatura = 0; // NOVO: KPI real para aguardando assinatura
$contratos_finalizados_mes = 0;

try {
    // KPI: Contratos Aguardando Envio (Status 'documentos_aprovados')
    $sql_aguardando_envio = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'documentos_aprovados'";
    $result_aguardando = fetch_single($sql_aguardando_envio);
    $contratos_aguardando_envio = $result_aguardando['total'] ?? 0;

    // KPI: Contratos Enviados no Mês (Status 'contrato_enviado' ou 'aguardando_assinatura_eletronica')
    $mes_atual = date('Y-m-01 00:00:00');
    $proximo_mes = date('Y-m-01 00:00:00', strtotime('+1 month'));
    $sql_enviados_mes = "SELECT COUNT(id) AS total FROM reservas WHERE status IN ('contrato_enviado', 'aguardando_assinatura_eletronica') AND data_ultima_interacao >= ? AND data_ultima_interacao < ?";
    $result_enviados_mes = fetch_single($sql_enviados_mes, [$mes_atual, $proximo_mes], "ss");
    $contratos_enviados_mes = $result_enviados_mes['total'] ?? 0;

    // KPI: Contratos Aguardando Assinatura (NOVO: status `aguardando_assinatura_eletronica`)
    $sql_aguardando_assinatura = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'aguardando_assinatura_eletronica'";
    $result_aguardando_assinatura = fetch_single($sql_aguardando_assinatura);
    $contratos_aguardando_assinatura = $result_aguardando_assinatura['total'] ?? 0;

    // KPI: Contratos Finalizados no Mês (Vendas Concluídas)
    $sql_finalizados_mes = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'vendida' AND data_ultima_interacao >= ? AND data_ultima_interacao < ?";
    $result_finalizados_mes = fetch_single($sql_finalizados_mes, [$mes_atual, $proximo_mes], "ss");
    $contratos_finalizados_mes = $result_finalizados_mes['total'] ?? 0;


} catch (Exception $e) {
    error_log("Erro ao carregar KPIs de contratos: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar os indicadores de contratos: " . $e->getMessage();
}

// ======================================================================================================
// Lógica de Backend para a Tabela de Contratos (Atualizada para NOVO status)
// ======================================================================================================

$contratos = [];

$search_term = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);

$orderBy = filter_input(INPUT_GET, 'order_by', FILTER_SANITIZE_STRING);
$orderDir = filter_input(INPUT_GET, 'order_dir', FILTER_SANITIZE_STRING);

$allowedOrderBy = ['reserva_id', 'data_ultima_interacao', 'cliente_nome', 'empreendimento_nome', 'status'];
$allowedOrderDir = ['ASC', 'DESC'];

if (!in_array($orderBy, $allowedOrderBy)) {
    $orderBy = 'data_ultima_interacao';
}
if (!in_array(strtoupper($orderDir), $allowedOrderDir)) {
    $orderDir = 'DESC';
}


try {
    $sql_contratos = "
        SELECT
            r.id AS reserva_id,
            r.data_reserva,
            r.data_ultima_interacao,
            r.status,
            r.caminho_contrato_final, -- Inclui o caminho do contrato final
            COALESCE(cl.nome, 'N/A') AS cliente_nome,
            cl.email AS cliente_email,
            COALESCE(corr.nome, 'N/A') AS corretor_nome,
            e.nome AS empreendimento_nome,
            u.numero AS unidade_numero,
            u.andar AS unidade_andar,
            COALESCE(uiu.nome, 'Sistema/Não Identificado') AS usuario_ultima_interacao_nome
        FROM
            reservas r
        LEFT JOIN
            reservas_clientes rc ON r.id = rc.reserva_id
        LEFT JOIN
            clientes cl ON rc.cliente_id = cl.id
        LEFT JOIN
            usuarios corr ON r.corretor_id = corr.id
        JOIN
            unidades u ON r.unidade_id = u.id
        JOIN
            empreendimentos e ON u.empreendimento_id = e.id
        LEFT JOIN
            usuarios uiu ON r.usuario_ultima_interacao = uiu.id
        WHERE 1=1
    ";
    $params = [];
    $types = "";

    // Aplica filtro de status
    if (!empty($filter_status)) {
        if ($filter_status === 'aguardando') { // Documentos aprovados, aguardando envio do contrato
            $sql_contratos .= " AND r.status = 'documentos_aprovados'";
        } elseif ($filter_status === 'enviados') { // Contrato já enviado, aguardando venda OU aguardando assinatura eletrônica
            $sql_contratos .= " AND r.status IN ('contrato_enviado', 'aguardando_assinatura_eletronica')";
        } elseif ($filter_status === 'finalizados') { // Venda concluída
            $sql_contratos .= " AND r.status = 'vendida'";
        }
    } else {
        // Por padrão, mostra aguardando envio, já enviados e aguardando assinatura
        $sql_contratos .= " AND r.status IN ('documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica')";
    }

    // Aplica filtro de busca (termo genérico)
    if (!empty($search_term)) {
        $sql_contratos .= " AND (cl.nome LIKE ? OR r.id LIKE ? OR e.nome LIKE ? OR u.numero LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        $types .= "ssss";
    }

    $sql_contratos .= " ORDER BY " . $orderBy . " " . $orderDir;

    $contratos = fetch_all($sql_contratos, $params, $types);

} catch (Exception $e) {
    error_log("Erro ao carregar lista de contratos: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar a lista de contratos: " . $e->getMessage();
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
            <span class="kpi-label">Aguardando Envio</span>
            <span class="kpi-value"><?php echo htmlspecialchars($contratos_aguardando_envio); ?></span>
            <small>Prontos para formalização</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Contratos Enviados (Mês)</span>
            <span class="kpi-value"><?php echo htmlspecialchars($contratos_enviados_mes); ?></span>
            <small>Aguardando resposta do cliente</small>
        </div>
        <div class="kpi-card kpi-card-warning">
            <span class="kpi-label">Aguardando Assinatura</span>
            <span class="kpi-value"><?php echo htmlspecialchars($contratos_aguardando_assinatura); ?></span>
            <small>Via ferramenta de assinatura (simulado)</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Contratos Finalizados (Mês)</span>
            <span class="kpi-value"><?php echo htmlspecialchars($contratos_finalizados_mes); ?></span>
            <small>Vendas concretizadas</small>
        </div>
    </div>

    <div class="admin-controls-bar">
        <form id="contratosFiltersForm" method="GET" action="<?php echo BASE_URL; ?>admin/contratos/index.php">
            <div class="search-box">
                <input type="text" id="contratosSearch" name="search" placeholder="Buscar contrato..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
                <i class="fas fa-search"></i>
            </div>
            <div class="filters-box">
                <select id="contratosFilterStatus" name="status" class="form-control">
                    <option value="">Todos os Status</option>
                    <option value="aguardando" <?php echo ($filter_status === 'aguardando') ? 'selected' : ''; ?>>Aguardando Envio</option>
                    <option value="enviados" <?php echo ($filter_status === 'enviados') ? 'selected' : ''; ?>>Enviados (Aguardando Assinatura)</option>
                    <option value="finalizados" <?php echo ($filter_status === 'finalizados') ? 'selected' : ''; ?>>Finalizados (Vendidos)</option>
                </select>
                <button type="submit" class="btn btn-primary" id="applyContratosFiltersBtn">Aplicar Filtros</button>
            </div>
        </form>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="contratosTable">
            <thead>
                <tr>
                    <th data-sort-by="reserva_id">ID Reserva <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_reserva">Data Reserva <i class="fas fa-sort"></i></th>
                    <th data-sort-by="cliente_nome">Cliente <i class="fas fa-sort"></i></th>
                    <th data-sort-by="empreendimento_nome">Empreendimento <i class="fas fa-sort"></i></th>
                    <th data-sort-by="unidade_numero">Unidade <i class="fas fa-sort"></i></th>
                    <th data-sort-by="status">Status Contrato <i class="fas fa-sort"></i></th>
                    <th>Última Interação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contratos)): ?>
                    <tr><td colspan="8" style="text-align: center;">Nenhum contrato encontrado com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($contratos as $contrato): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($contrato['reserva_id']); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($contrato['data_reserva'])); ?></td>
                            <td><?php echo htmlspecialchars($contrato['cliente_nome']); ?><br><small><?php echo htmlspecialchars($contrato['cliente_email']); ?></small></td>
                            <td><?php echo htmlspecialchars($contrato['empreendimento_nome']); ?></td>
                            <td><?php echo htmlspecialchars($contrato['unidade_numero'] . ' (' . $contrato['unidade_andar'] . 'º Andar)'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($contrato['status']); ?>">
                                    <?php
                                    // Ajusta o texto do status para ser mais amigável na tela de contratos
                                    $display_status = str_replace('_', ' ', $contrato['status']);
                                    if ($contrato['status'] === 'documentos_aprovados') {
                                        $display_status = 'Aguardando Envio';
                                    } elseif ($contrato['status'] === 'contrato_enviado') {
                                        $display_status = 'Enviado';
                                    } elseif ($contrato['status'] === 'aguardando_assinatura_eletronica') {
                                        $display_status = 'Aguardando Assinatura';
                                    } elseif ($contrato['status'] === 'vendida') {
                                        $display_status = 'Finalizado (Vendido)';
                                    }
                                    echo htmlspecialchars(ucfirst($display_status));
                                    ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(format_datetime_br($contrato['data_ultima_interacao'])); ?><br><small>(Por: <?php echo htmlspecialchars($contrato['usuario_ultima_interacao_nome']); ?>)</small></td>
                            <td class="admin-table-actions">
                                <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($contrato['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                <?php if ($contrato['status'] === 'documentos_aprovados'): ?>
                                    <button class="btn btn-success btn-sm upload-and-send-contract-btn" 
                                            data-reserva-id="<?php echo htmlspecialchars($contrato['reserva_id']); ?>"
                                            data-cliente-email="<?php echo htmlspecialchars($contrato['cliente_email']); ?>"
                                            data-cliente-nome="<?php echo htmlspecialchars($contrato['cliente_nome']); ?>"
                                            title="Fazer Upload e Enviar Contrato">
                                        <i class="fas fa-upload"></i> Enviar Contrato
                                    </button>
                                    <button class="btn btn-secondary btn-sm mark-contract-sent-btn" 
                                            data-reserva-id="<?php echo htmlspecialchars($contrato['reserva_id']); ?>"
                                            title="Marcar Contrato como Enviado (Manual)">
                                        Marcar Enviado
                                    </button>
                                <?php elseif ($contrato['status'] === 'contrato_enviado' || $contrato['status'] === 'aguardando_assinatura_eletronica'): ?>
                                    <?php if (!empty($contrato['caminho_contrato_final'])): ?>
                                        <a href="<?php echo BASE_URL . htmlspecialchars($contrato['caminho_contrato_final']); ?>" target="_blank" class="btn btn-primary btn-sm" title="Ver Contrato Final">
                                            <i class="fas fa-file-contract"></i> Ver Contrato
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-success btn-sm simulate-sign-contract-btn" 
                                            data-reserva-id="<?php echo htmlspecialchars($contrato['reserva_id']); ?>"
                                            title="Simular Assinatura Finalizada">
                                        <i class="fas fa-check-circle"></i> Simular Assinatura
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="uploadContractModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Enviar Contrato - Reserva <span id="modalContractReservaIdDisplay"></span></h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <form id="formUploadContract" action="<?php echo BASE_URL; ?>api/reserva.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="send_contract">
                    <input type="hidden" name="reserva_id" id="modalUploadContractReservaId">
                    <input type="hidden" name="cliente_email" id="modalUploadContractClienteEmail">
                    <input type="hidden" name="cliente_nome" id="modalUploadContractClienteNomeHidden"> <p><strong>Cliente:</strong> <span id="modalUploadContractClienteNome"></span></p>
                    
                    <p class="mt-3">Selecione o arquivo do contrato para upload (PDF):</p>
                    <div class="form-group">
                        <label for="contractFile">Arquivo do Contrato (PDF):</label>
                        <input type="file" id="contractFile" name="contract_file" class="form-control" accept=".pdf" required>
                    </div>

                    <p class="mt-3">Opções de Envio:</p>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_method" id="sendMethodManual" value="manual" checked>
                            <label class="form-check-label" for="sendMethodManual">
                                Enviar Manualmente (Apenas marca como 'Enviado' no sistema)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_method" id="sendMethodClickSign" value="clicksign">
                            <label class="form-check-label" for="sendMethodClickSign">
                                Enviar para Assinatura Eletrônica (Simulado)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Prosseguir com Envio</button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>