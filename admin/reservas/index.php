<?php
// admin/reservas/index.php - Painel de Gestão de Reservas (Admin Master)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para format_datetime_br, format_currency_brl
require_once '../../includes/alerts.php';

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/reservas/index.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}

require_login();
$logged_user_info = get_user_info();
$user_id = $logged_user_info['id'] ?? 0;

require_permission(['admin']); // Apenas Admin Master pode gerenciar todas as reservas

$page_title = "Gestão de Reservas";

$reservas = [];
$errors = [];
$success_message = '';

if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// KPIs para Gestão de Reservas
$total_reservas_solicitadas = 0;
$total_reservas_aprovadas_em_andamento = 0;
$total_reservas_docs_pendentes = 0;
$total_reservas_vendidas = 0;
$total_reservas_cadastradas = 0;
$total_reservas_expiradas = 0;
$total_reservas_canceladas = 0;
$total_reservas_dispensadas = 0;
$reservas_expirando_em_breve = 0;

try {
    $sql_total_reservas_cadastradas = "SELECT COUNT(id) AS total FROM reservas";
    $result_total_cadastradas = fetch_single($sql_total_reservas_cadastradas);
    $total_reservas_cadastradas = $result_total_cadastradas['total'] ?? 0;

    $sql_solicitadas = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'solicitada'";
    $result_solicitadas = fetch_single($sql_solicitadas);
    $total_reservas_solicitadas = $result_solicitadas['total'] ?? 0;

    $sql_aprovadas_em_andamento = "
        SELECT COUNT(id) AS total FROM reservas
        WHERE status IN ('aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'contrato_enviado', 'aguardando_assinatura_eletronica', 'documentos_solicitados')
    "; // Incluído 'documentos_solicitados'
    $result_aprovadas_em_andamento = fetch_single($sql_aprovadas_em_andamento);
    $total_reservas_aprovadas_em_andamento = $result_aprovadas_em_andamento['total'] ?? 0;

    $sql_docs_pendentes = "SELECT COUNT(id) AS total FROM reservas WHERE status IN ('documentos_pendentes', 'documentos_rejeitados', 'documentos_solicitados')"; // Incluído 'documentos_solicitados'
    $result_docs_pendentes = fetch_single($sql_docs_pendentes);
    $total_reservas_docs_pendentes = $result_docs_pendentes['total'] ?? 0;

    $sql_vendidas = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'vendida'";
    $result_vendidas = fetch_single($sql_vendidas);
    $total_reservas_vendidas = $result_vendidas['total'] ?? 0;
    
    $sql_expiradas = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'expirada'";
    $result_expiradas = fetch_single($sql_expiradas);
    $total_reservas_expiradas = $result_expiradas['total'] ?? 0;

    $sql_canceladas = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'cancelada'";
    $result_canceladas = fetch_single($sql_canceladas);
    $total_reservas_canceladas = $result_canceladas['total'] ?? 0;

    $sql_dispensadas = "SELECT COUNT(id) AS total FROM reservas WHERE status = 'dispensada'";
    $result_dispensadas = fetch_single($sql_dispensadas);
    $total_reservas_dispensadas = $result_dispensadas['total'] ?? 0;

    $now = date('Y-m-d H:i:s');
    $next_24_hours = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $sql_expirando_breve = "SELECT COUNT(id) AS total FROM reservas WHERE status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada') AND data_expiracao BETWEEN ? AND ?";
    $result_expirando_breve = fetch_single($sql_expirando_breve, [$now, $next_24_hours], "ss");
    $reservas_expirando_em_breve = $result_expirando_breve['total'] ?? 0;

} catch (Exception $e) {
    error_log("Erro ao carregar KPIs de reservas: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar os indicadores de reservas: " . $e->getMessage();
}

try {
    $sql_reservas = "
        SELECT
            r.id AS reserva_id,
            r.data_reserva,
            r.data_expiracao,
            r.valor_reserva,
            r.status,
            COALESCE(cl.nome, 'N/A') AS cliente_nome,
            COALESCE(corr.nome, 'Não Atribuído') AS corretor_nome,
            COALESCE(i.nome, 'N/A') AS imobiliaria_nome,
            e.nome AS empreendimento_nome,
            u.numero AS unidade_numero,
            u.andar AS unidade_andar,
            u.posicao AS unidade_posicao,
            r.data_ultima_interacao,
            usu_ult_int.nome AS usuario_ultima_interacao_nome,
            r.corretor_id as reserva_corretor_id,
            r.link_documentos_upload -- Adicionado para a lógica do botão e exibição
        FROM
            reservas r
        LEFT JOIN
            reservas_clientes rc ON r.id = rc.reserva_id
        LEFT JOIN
            clientes cl ON rc.cliente_id = cl.id
        LEFT JOIN
            usuarios corr ON r.corretor_id = corr.id
        LEFT JOIN
            imobiliarias i ON corr.imobiliaria_id = i.id
        JOIN
            unidades u ON r.unidade_id = u.id
        JOIN
            empreendimentos e ON u.empreendimento_id = e.id
        LEFT JOIN
            usuarios usu_ult_int ON r.usuario_ultima_interacao = usu_ult_int.id
        ORDER BY
            r.data_reserva DESC;
    ";
    $reservas = fetch_all($sql_reservas);

} catch (Exception $e) {
    error_log("Erro ao carregar reservas: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar as reservas: " . $e->getMessage();
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

    <div id="feedbackModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="feedbackModalTitle"></h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p id="feedbackModalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary modal-close-btn">OK</button>
            </div>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Total de Reservas Cadastradas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_cadastradas); ?></span>
            <small>Todas as reservas no sistema</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Reservas Solicitadas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_solicitadas); ?></span>
            <small>Aguardando aprovação</small>
        </div>
        <div class="kpi-card kpi-card-info">
            <span class="kpi-label">Reservas Aprovadas/Andamento</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_aprovadas_em_andamento); ?></span>
            <small>Em processo de venda</small>
        </div>
        <div class="kpi-card kpi-card-warning">
            <span class="kpi-label">Documentação Pendente</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_docs_pendentes); ?></span>
            <small>Aguardando docs do cliente</small>
        </div>
        <div class="kpi-card kpi-card-success">
            <span class="kpi-label">Vendas Concluídas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_vendidas); ?></span>
            <small>Reservas finalizadas</small>
        </div>
        <div class="kpi-card kpi-card-danger">
            <span class="kpi-label">Reservas Expiradas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_expiradas); ?></span>
            <small>Ultrapassaram o prazo</small>
        </div>
        <div class="kpi-card kpi-card-danger">
            <span class="kpi-label">Reservas Canceladas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_canceladas); ?></span>
            <small>Foram canceladas</small>
        </div>
        <div class="kpi-card kpi-card-danger">
            <span class="kpi-label">Reservas Dispensadas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_dispensadas); ?></span>
            <small>Leads não aproveitados</l>
        </div>
        <div class="kpi-card kpi-card-urgent">
            <span class="kpi-label">Expirando em Breve</span>
            <span class="kpi-value"><?php echo htmlspecialchars($reservas_expirando_em_breve); ?></span>
            <small>Próximas 24 horas</small>
        </div>
    </div>

    <div class="admin-controls-bar">
        <div class="search-box">
            <input type="text" id="reservaSearch" placeholder="Buscar reserva...">
            <i class="fas fa-search"></i>
        </div>
        <div class="filters-box">
            <select id="reservaFilterStatus">
                <option value="">Todos os Status</option>
                <option value="solicitada">Solicitada</option>
                <option value="aprovada">Aprovada</option>
                <option value="documentos_pendentes">Docs. Pendentes</option>
                <option value="documentos_enviados">Docs. Enviados</option>
                <option value="documentos_aprovados">Docs. Aprovados</option>
                <option value="documentos_rejeitados">Docs. Rejeitados</option>
                <option value="contrato_enviado">Contrato Enviado</option>
                <option value="aguardando_assinatura_eletronica">Aguardando Assinatura</option>
                <option value="vendida">Vendida</option>
                <option value="cancelada">Cancelada</option>
                <option value="expirada">Expirada</option>
                <option value="dispensada">Dispensada</option>
                <option value="documentos_solicitados">Docs. Solicitados</option> </select>
        </div>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="reservasTable">
            <thead>
                <tr>
                    <th class="col-id" data-sort-by="reserva_id">ID <i class="fas fa-sort"></i></th>
                    <th class="col-data" data-sort-by="data_reserva">Data <i class="fas fa-sort"></i></th>
                    <th class="col-empreendimento" data-sort-by="empreendimento_nome">Empreendimento <i class="fas fa-sort"></i></th>
                    <th class="col-unidade" data-sort-by="unidade_numero">Unidade <i class="fas fa-sort"></i></th>
                    <th class="col-cliente" data-sort-by="cliente_nome">Cliente <i class="fas fa-sort"></i></th>
                    <th class="col-corretor" data-sort-by="corretor_nome">Corretor <i class="fas fa-sort"></i></th>
                    <th class="col-valor" data-sort-by="valor_reserva">Valor <i class="fas fa-sort"></i></th>
                    <th class="col-status" data-sort-by="status">Status <i class="fas fa-sort"></i></th>
                    <th class="col-expira" data-sort-by="data_expiracao">Expira <i class="fas fa-sort"></i></th>
                    <th class="col-ultima-interacao" data-sort-by="data_ultima_interacao">Última Interação <i class="fas fa-sort"></i></th>
                    <th class="col-acoes">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="11" style="text-align: center;">Nenhuma reserva encontrada no momento.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>" data-reserva-status="<?php echo htmlspecialchars($reserva['status']); ?>">
                            <td><?php echo htmlspecialchars($reserva['reserva_id']); ?></td>
                            <td class="small-font-data">
                                <?php echo htmlspecialchars((new DateTime($reserva['data_reserva']))->format('d/m/Y')); ?><br>
                                <span class="time-text"><?php echo htmlspecialchars((new DateTime($reserva['data_reserva']))->format('H:i:s')); ?></span>
                            </td>
                            <td class="wrap-text"><?php echo htmlspecialchars($reserva['empreendimento_nome']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($reserva['unidade_numero']); ?><br>
                                <small>(<?php echo htmlspecialchars($reserva['unidade_andar']); ?>º Andar)</small>
                            </td>
                            <td class="wrap-text"><?php echo htmlspecialchars($reserva['cliente_nome']); ?></td>
                            <td class="wrap-text">
                                <?php echo htmlspecialchars($reserva['corretor_nome']); ?><br>
                                <small><?php echo htmlspecialchars($reserva['imobiliaria_nome'] === 'N/A' ? 'Autônomo' : $reserva['imobiliaria_nome']); ?></small>
                            </td>
                            <td class="text-right"><?php echo format_currency_brl($reserva['valor_reserva']); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($reserva['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva['status']))); ?></span></td>
                            <td>
                                <?php 
                                    $expiration_string = time_remaining_string($reserva['data_expiracao']);
                                    $terminal_statuses = ['vendida', 'cancelada', 'expirada', 'dispensada'];

                                    if (in_array($reserva['status'], $terminal_statuses)) {
                                        echo 'N/A';
                                    } else {
                                        echo htmlspecialchars($expiration_string);
                                    }
                                ?>
                            </td>
                            <td class="small-font-data">
                                <?php echo htmlspecialchars((new DateTime($reserva['data_ultima_interacao']))->format('d/m/Y H:i:s')); ?><br>
                                <small>Por: <?php echo htmlspecialchars($reserva['usuario_ultima_interacao_nome'] ?? 'Sistema'); ?></small>
                            </td>
                            <td class="admin-table-actions">
                                <?php 
                                $expiration_string = time_remaining_string($reserva['data_expiracao']);
                                $terminal_statuses = ['vendida', 'cancelada', 'expirada', 'dispensada'];

                                if ($expiration_string === 'Expirada' || in_array($reserva['status'], $terminal_statuses)): ?>
                                <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                            <?php else: ?>
                                <?php if ($reserva['status'] === 'solicitada'): ?>
                                    <button class="btn btn-success btn-sm approve-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Aprovar</button>
                                    <button class="btn btn-danger btn-sm reject-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Rejeitar</button>
                                <?php elseif (in_array($reserva['status'], ['aprovada', 'documentos_rejeitados', 'documentos_pendentes'])): ?>
                                    <button class="btn btn-info btn-sm request-docs-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Solicitar Docs</button>
                                    <button class="btn btn-danger btn-sm cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Cancelar</button>
                                <?php elseif ($reserva['status'] === 'documentos_solicitados'): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                    <button class="btn btn-danger btn-sm cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Cancelar</button>
                                <?php elseif ($reserva['status'] === 'documentos_enviados'): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/documentos/index.php?reserva_id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-secondary btn-sm">Analisar Docs</a>
                                    <button class="btn btn-danger btn-sm cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Cancelar</button>
                                <?php elseif ($reserva['status'] === 'documentos_aprovados'): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/contratos/index.php?reserva_id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-secondary btn-sm">Enviar Contrato</a>
                                    <button class="btn btn-danger btn-sm cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Cancelar</button>
                                <?php elseif ($reserva['status'] === 'contrato_enviado' || $reserva['status'] === 'aguardando_assinatura_eletronica'): ?>
                                    <button class="btn btn-success btn-sm finalize-sale-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Finalizar Venda</button>
                                    <button class="btn btn-danger btn-sm cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Cancelar</button>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                            <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="approveReservaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Aprovação</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja **aprovar** esta reserva?</p>
                <form id="approveReservaForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="aprovar_reserva">
                    <input type="hidden" name="reserva_id" id="approveReservaId">
                    <div class="form-actions" style="justify-content: space-around;">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-success">Aprovar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="rejectReservaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Rejeição</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja **rejeitar** esta reserva? Esta ação irá **cancelá-la**.</p>
                <form id="rejectReservaForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="rejeitar_reserva">
                    <input type="hidden" name="reserva_id" id="rejectReservaId">
                    <div class="form-group">
                        <label for="rejectionReasonReserva">Motivo da Rejeição (Opcional):</label>
                        <textarea id="rejectionReasonReserva" name="motivo_cancelamento" rows="3" placeholder="Ex: Cliente desistiu, unidade vendida para outro cliente."></textarea>
                    </div>
                    <div class="form-actions" style="justify-content: space-around;">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Rejeitar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="requestDocsModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Solicitar Documentação</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Confirmar solicitação de documentos para a reserva. Um e-mail será enviado ao corretor.</p>
                <form id="requestDocsForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="solicitar_documentacao">
                    <input type="hidden" name="reserva_id" id="requestDocsReservaId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-info">Confirmar Solicitação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="finalizeSaleModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Finalizar Venda</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="reserva_id" id="finalizeSaleReservaIdInput">
                <p>Você tem certeza que deseja FINALIZAR a venda da reserva <strong id="modalReservaIdDisplay"></strong>?</p>
                <p>Esta ação marcará a reserva como 'Vendida' e a unidade associada também será atualizada para 'Vendida'.</p>
                <p><strong>Esta ação é irreversível.</strong> Tem certeza que deseja continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmFinalizeSaleBtn">Confirmar Finalização</button>
            </div>
        </div>
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
                        <textarea id="cancelReasonReserva" name="motivo_cancelamento" rows="3" placeholder="Ex: Cliente desistiu, erro na reserva."></textarea>
                    </div>
                    <div class="form-actions" style="justify-content: space-around;">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editClientsModal" class="modal-overlay">
        <div class="modal-content large-modal-content">
            <div class="modal-header">
                <h3>Editar Clientes da Reserva</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <form id="editClientsForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="edit_clients">
                    <input type="hidden" name="reserva_id" id="editClientsReservaId">
                    <div id="clientsContainer">
                        </div>
                    <button type="button" class="btn btn-info mt-3" id="addClientBtn"><i class="fas fa-plus"></i> Adicionar Outro Comprador</button>
                    <div class="form-actions mt-4" style="justify-content: space-around;">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="simulateSignContractModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Simular Assinatura de Contrato</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Você está prestes a simular a assinatura eletrônica do contrato para a reserva <strong id="simulateSignReservaIdDisplay"></strong>.</p>
                <p>Esta ação irá **finalizar a venda** e marcar a reserva e a unidade como 'Vendida'.</p>
                <p><strong>Esta ação é irreversível.</strong> Deseja continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmSimulateSignContractBtn">Confirmar Simulação e Finalizar</button>
            </div>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>