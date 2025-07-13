<?php
// SISReserva_MVP/admin/empreendimentos/unidades.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para format_currency_brl() e format_date_br()

require_permission(['admin']);

$empreendimento_id = filter_input(INPUT_GET, 'empreendimento_id', FILTER_VALIDATE_INT);

if (!$empreendimento_id) {
    header("Location: " . BASE_URL . "admin/empreendimentos/index.php?error=no_empreendimento_id");
    exit();
}

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em admin/empreendimentos/unidades.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível.");
}

$empreendimento_nome = "Empreendimento Desconhecido";
$unidades = [];
$tipos_unidade_map = []; // Para mapear tipo_unidade_id para o nome do tipo

// KPIs para a página de unidades
$total_unidades_empreendimento = 0;
$unidades_disponiveis_empreendimento = 0;
$unidades_reservadas_empreendimento = 0;
$unidades_vendidas_empreendimento = 0;
$unidades_pausadas_empreendimento = 0;
$unidades_bloqueadas_empreendimento = 0;


try {
    $emp_data = fetch_single("SELECT nome FROM empreendimentos WHERE id = ?", [$empreendimento_id], "i");
    if ($emp_data) {
        $empreendimento_nome = $emp_data['nome'];
    }

    $raw_tipos = fetch_all("SELECT id, tipo, metragem FROM tipos_unidades WHERE empreendimento_id = ?", [$empreendimento_id], "i");
    foreach ($raw_tipos as $tipo) {
        $tipos_unidade_map[$tipo['id']] = $tipo['tipo'] . ' (' . $tipo['metragem'] . 'm²)';
    }

    // DEBUG: Loga a query de unidades e os dados retornados
    $sql_unidades = "SELECT id, tipo_unidade_id, numero, andar, posicao, area, valor, status, informacoes_pagamento FROM unidades WHERE empreendimento_id = ? ORDER BY andar ASC, numero ASC";
    error_log("DEBUG unidades.php: SQL Unidades: " . $sql_unidades);
    $unidades = fetch_all($sql_unidades, [$empreendimento_id], "i");
    error_log("DEBUG unidades.php: Unidades carregadas: " . print_r($unidades, true)); // Log detalhado das unidades

    $sql_kpis_unidades = "
        SELECT
            COUNT(id) AS total_unidades,
            SUM(CASE WHEN status = 'disponivel' THEN 1 ELSE 0 END) AS total_disponiveis,
            SUM(CASE WHEN status = 'reservada' THEN 1 ELSE 0 END) AS total_reservadas,
            SUM(CASE WHEN status = 'vendida' THEN 1 ELSE 0 END) AS total_vendidas,
            SUM(CASE WHEN status = 'pausada' THEN 1 ELSE 0 END) AS total_pausadas,
            SUM(CASE WHEN status = 'bloqueada' THEN 1 ELSE 0 END) AS total_bloqueadas
        FROM
            unidades
        WHERE
            empreendimento_id = ?;
    ";
    $kpis_unidades = fetch_single($sql_kpis_unidades, [$empreendimento_id], "i");
    error_log("DEBUG unidades.php: KPIs carregados: " . print_r($kpis_unidades, true)); // Log detalhado dos KPIs

    $total_unidades_empreendimento      = $kpis_unidades['total_unidades'] ?? 0;
    $unidades_disponiveis_empreendimento= $kpis_unidades['total_disponiveis'] ?? 0;
    $unidades_reservadas_empreendimento = $kpis_unidades['total_reservadas'] ?? 0;
    $unidades_vendidas_empreendimento   = $kpis_unidades['total_vendidas'] ?? 0;
    $unidades_pausadas_empreendimento   = $kpis_unidades['total_pausadas'] ?? 0;
    $unidades_bloqueadas_empreendimento = $kpis_unidades['total_bloqueadas'] ?? 0;


} catch (Exception $e) {
    error_log("Erro ao carregar unidades do empreendimento: " . $e->getMessage());
} finally {
    // A conexão é fechada apenas no footer_dashboard.php
}

$page_title = "Unidades de " . htmlspecialchars($empreendimento_nome);

require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <div class="admin-page-header">
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar para Empreendimentos</a>
        
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#batchPriceUpdateModal">
            <i class="fas fa-money-bill-wave"></i> Ajustar Preços em Lote
        </button>
        <button type="button" class="btn btn-info" id="printTableBtn">
            <i class="fas fa-print"></i> Imprimir Tabela
        </button>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Total de Unidades</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_unidades_empreendimento); ?></span>
        </div>
        <div class="kpi-card status-available-bg">
            <span class="kpi-label">Disponíveis</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_disponiveis_empreendimento); ?></span>
        </div>
        <div class="kpi-card status-reserved-bg">
            <span class="kpi-label">Reservadas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_reservadas_empreendimento); ?></span>
        </div>
        <div class="kpi-card status-sold-bg">
            <span class="kpi-label">Vendidas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_vendidas_empreendimento); ?></span>
        </div>
        <div class="kpi-card status-paused-bg">
            <span class="kpi-label">Pausadas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_pausadas_empreendimento); ?></span>
        </div>
        <div class="kpi-card status-blocked-bg">
            <span class="kpi-label">Bloqueadas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_bloqueadas_empreendimento); ?></span>
        </div>
    </div>
    <?php if (!empty($unidades)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table table-hover">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 12%;">Tipo</th>
                        <th style="width: 8%;">Número</th>
                        <th style="width: 5%;">Andar</th>
                        <th style="width: 5%;">Posição</th>
                        <th style="width: 10%;">Área (m²)</th>
                        <th style="width: 10%;">Valor (R$)</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 40%;">Ações</th> </tr>
                </thead>
                <tbody>
                    <?php foreach ($unidades as $unidade): ?>
                        <?php error_log("DEBUG unidades.php: Status da unidade " . $unidade['id'] . ": [" . $unidade['status'] . "]"); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($unidade['id']); ?></td>
                            <td><?php echo htmlspecialchars($tipos_unidade_map[$unidade['tipo_unidade_id']] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($unidade['numero']); ?></td>
                            <td><?php echo htmlspecialchars($unidade['andar']); ?></td>
                            <td><?php echo htmlspecialchars($unidade['posicao']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($unidade['area'] ?? 0, 2, ',', '.')); ?></td> <td><?php echo format_currency_brl($unidade['valor'] ?? 0); ?></td> <td>
                                <span class="badge badge-<?php
                                    switch ($unidade['status']) {
                                        case 'disponivel': echo 'success'; break;
                                        case 'reservada': echo 'warning'; break;
                                        case 'vendida': echo 'info'; break;
                                        case 'pausada': echo 'secondary'; break;
                                        case 'bloqueada': echo 'danger'; break;
                                        default: echo 'light';
                                    }
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $unidade['status']))); ?>
                                </span>
                            </td>
                            <td>
                                <a href="criar_e_editar.php?id=<?php echo $empreendimento_id; ?>&step=6&unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-sm btn-primary" title="Configurar Plano de Pagamento"><i class="fas fa-hand-holding-usd"></i> Plano</a>

                                <button type="button" class="btn btn-sm btn-info edit-price-btn" data-unit-id="<?php echo $unidade['id']; ?>" data-current-price="<?php echo htmlspecialchars($unidade['valor'] ?? 0); ?>" title="Editar Preço"><i class="fas fa-dollar-sign"></i> Preço</button>

                                <button type="button" class="btn btn-sm btn-warning edit-payment-btn" 
                                        data-unit-id="<?php echo htmlspecialchars($unidade['id']); ?>" 
                                        data-current-payment-info="<?php echo htmlspecialchars($unidade['informacoes_pagamento'] ?? '[]'); ?>" 
                                        data-unit-value="<?php echo htmlspecialchars($unidade['valor'] ?? 0); ?>" 
                                        title="Editar Condição de Pagamento"><i class="fas fa-file-invoice-dollar"></i> Condição</button>

                                <?php if ($unidade['status'] === 'disponivel'): ?>
                                    <button type="button" class="btn btn-sm btn-secondary toggle-unit-status-btn" data-unit-id="<?php echo $unidade['id']; ?>" data-new-status="pausada" title="Pausar Unidade"><i class="fas fa-pause-circle"></i> Pausar</button>
                                    <button type="button" class="btn btn-sm btn-danger toggle-unit-status-btn" data-unit-id="<?php echo $unidade['id']; ?>" data-new-status="bloqueada" title="Bloquear Unidade"><i class="fas fa-lock"></i> Bloquear</button>
                                <?php elseif ($unidade['status'] === 'pausada' || $unidade['status'] === 'bloqueada'): ?>
                                    <button type="button" class="btn btn-sm btn-success toggle-unit-status-btn" data-unit-id="<?php echo $unidade['id']; ?>" data-new-status="disponivel" title="Tornar Disponível"><i class="fas fa-play-circle"></i> Ativar</button>
                                <?php endif; ?>

                                <button type="button" class="btn btn-sm btn-danger delete-unit-btn" data-unit-id="<?php echo $unidade['id']; ?>" title="Excluir Unidade"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="alert alert-info">Nenhuma unidade cadastrada para este empreendimento.</div>
    <?php endif; ?>
</div>

<div class="modal fade" id="batchPriceUpdateModal" tabindex="-1" role="dialog" aria-labelledby="batchPriceUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchPriceUpdateModalLabel">Ajustar Preços de Todas as Unidades</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="batchPriceUpdateForm">
                    <p>Aplicar um multiplicador de preço a **todas as unidades disponíveis, pausadas e reservadas** deste empreendimento.</p>
                    <div class="form-group">
                        <label for="priceMultiplier">Multiplicador de Preço (Ex: 1.05 para +5%, 0.98 para -2%)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="priceMultiplier" name="price_multiplier" required>
                        <small class="form-text text-muted">Use 1.00 para não alterar.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveBatchPriceUpdateBtn">Aplicar Ajuste</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editPriceModal" tabindex="-1" role="dialog" aria-labelledby="editPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPriceModalLabel">Editar Preço da Unidade</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editPriceForm">
                    <input type="hidden" id="editPriceUnitId" name="unit_id">
                    <div class="form-group">
                        <label for="newPrice">Novo Preço (R$)</label>
                        <input type="text" class="form-control currency-mask" id="newPrice" name="new_price" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveNewPriceBtn">Salvar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"> <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPaymentModalLabel">Editar Plano de Pagamento da Unidade</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editPaymentForm">
                    <input type="hidden" id="editPaymentUnitId" name="unit_id">
                    <p class="mt-3"><strong>Valor da Unidade:</strong> <span id="payment_unit_value_modal_display">R$ 0,00</span></p>
                    <table class="dynamic-table payment-flow-table mt-3">
                        <thead>
                            <tr>
                                <th>Descrição <span class="required">*</span></th>
                                <th>Parcelas <span class="required">*</span></th>
                                <th>Tipo de Valor <span class="required">*</span></th>
                                <th>Cálculo <span class="required">*</span></th>
                                <th>Valor / % <span class="required">*</span></th>
                                <th>Total Condição</th>
                                <th>Valor por Parcela</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="payment_flow_modal_tbody">
                            </tbody>
                    </table>
                    <button type="button" id="add_parcela_modal" class="add-row-btn"><i class="fas fa-plus"></i> Adicionar Condição</button>
                    <div class="payment-info-summary mt-3">
                        <p><strong>Total do Plano:</strong> <span id="total_plano_modal_display">R$ 0,00</span></p>
                        <p id="total_plano_modal_validation" class="text-red"></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveNewPaymentBtn">Salvar Plano</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ESTA VARIÁVEL SERÁ LIDA PELO admin_unidades.js
    // Ela precisa ser definida AQUI, no HTML do PHP, para que o JS externo a acesse.
    window.currentEmpreendimentoId = <?php echo json_encode($empreendimento_id); ?>; 
</script>
<script src="<?php echo BASE_URL; ?>js/admin_unidades.js"></script>

<?php require_once '../../includes/footer_dashboard.php'; ?>