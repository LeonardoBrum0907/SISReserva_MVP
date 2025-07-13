<?php
// imobiliaria/reservas/detalhes.php - Página de Detalhes da Reserva (Admin Imobiliária)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para format_datetime_br
require_once '../../includes/alerts.php'; // Para mensagens

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/reservas/detalhes.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Detalhes da Reserva";

$logged_user_info = get_user_info();
$admin_imobiliaria_id = $logged_user_info['id'];
$imobiliaria_id_logado = $logged_user_info['imobiliaria_id'] ?? null;

$reserva_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$reserva = null;
$clientes_da_reserva = [];
$documentos_da_reserva = [];
$historico_auditoria = [];

$errors = [];
$success_message = '';

// Lida com mensagens da sessão (após uma possível ação via AJAX/redirecionamento)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']); // Limpa as mensagens após exibir
}

if (!$reserva_id || !$imobiliaria_id_logado) {
    $errors[] = "ID da reserva não fornecido ou imobiliária não identificada.";
    $reserva_id = 0; // Para evitar erros se o ID não for válido
} else {
    try {
        // Buscar IDs de todos os corretores vinculados a esta imobiliária
        $sql_corretores_imobiliaria = "SELECT id FROM usuarios WHERE imobiliaria_id = ? AND tipo LIKE 'corretor_%'";
        $corretores_vinculados = fetch_all($sql_corretores_imobiliaria, [$imobiliaria_id_logado], "i");
        
        $corretores_ids = [];
        if (!empty($corretores_vinculados)) {
            foreach ($corretores_vinculados as $corretor) {
                $corretores_ids[] = $corretor['id'];
            }
        }
        
        // Se não houver corretores vinculados, ou se a reserva não foi feita por um deles, acesso negado
        if (empty($corretores_ids)) {
            $errors[] = "Nenhum corretor vinculado à sua imobiliária encontrado para esta reserva.";
            $reserva_id = 0;
        } else {
            $placeholders = implode(',', array_fill(0, count($corretores_ids), '?'));

            // Buscar detalhes da reserva, garantindo que ela pertença a um corretor da imobiliária logada
            $sql_reserva = "
                SELECT
                    r.id AS reserva_id,
                    r.data_reserva,
                    r.data_expiracao,
                    r.valor_reserva,
                    r.status,
                    r.observacoes,
                    r.motivo_cancelamento,
                    r.data_ultima_interacao,
                    r.comissao_corretor,
                    r.comissao_imobiliaria,
                    COALESCE(corr.nome, 'N/A') AS corretor_nome,
                    e.nome AS empreendimento_nome,
                    u.numero AS unidade_numero,
                    u.andar AS unidade_andar,
                    u.posicao AS unidade_posicao,
                    tu.tipo AS tipo_unidade_nome,
                    tu.metragem,
                    tu.quartos,
                    tu.banheiros,
                    tu.vagas,
                    tu.foto_planta,
                    u.informacoes_pagamento
                FROM
                    reservas r
                JOIN
                    unidades u ON r.unidade_id = u.id
                JOIN
                    empreendimentos e ON u.empreendimento_id = e.id
                LEFT JOIN
                    tipos_unidades tu ON u.tipo_unidade_id = tu.id
                LEFT JOIN
                    usuarios corr ON r.corretor_id = corr.id
                WHERE
                    r.id = ? AND r.corretor_id IN ({$placeholders}); -- CRÍTICO: Filtra por ID da reserva e corretores da imobiliária
            ";
            $params_reserva = array_merge([$reserva_id], $corretores_ids);
            $types_reserva = "i" . str_repeat('i', count($corretores_ids));

            $reserva = fetch_single($sql_reserva, $params_reserva, $types_reserva);

            if (!$reserva) {
                $errors[] = "Reserva não encontrada ou não pertence a um corretor da sua imobiliária.";
                $reserva_id = 0;
            } else {
                $page_title .= " #{$reserva_id}";

                // Buscar clientes vinculados a esta reserva
                $sql_clientes_reserva = "
                    SELECT
                        c.id AS cliente_id,
                        c.nome,
                        c.cpf,
                        c.email,
                        c.whatsapp,
                        c.cep,
                        c.endereco,
                        c.numero,
                        c.complemento,
                        c.bairro,
                        c.cidade,
                        c.estado,
                        c.data_cadastro
                    FROM
                        clientes c
                    JOIN
                        reservas_clientes rc ON c.id = rc.cliente_id
                    WHERE
                        rc.reserva_id = ?;
                ";
                $clientes_da_reserva = fetch_all($sql_clientes_reserva, [$reserva_id], "i");

                // Buscar documentos enviados para esta reserva
                $sql_documentos = "
                    SELECT
                        id,
                        nome_documento, 
                        caminho_arquivo, 
                        data_upload,   
                        status,
                        motivo_rejeicao, 
                        data_analise,    
                        usuario_analise_id 
                    FROM
                        documentos_reserva
                    WHERE
                        reserva_id = ?
                    ORDER BY data_upload DESC;
                ";
                $documentos_da_reserva = fetch_all($sql_documentos, [$reserva_id], "i");

                // Decodificar informações de pagamento se existirem
                $informacoes_pagamento_html = '<p>Nenhuma informação de pagamento detalhada.</p>';
                if (!empty($reserva['informacoes_pagamento'])) {
                    $payment_info_array = json_decode($reserva['informacoes_pagamento'], true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($payment_info_array)) {
                        $informacoes_pagamento_html = '<table class="admin-table">';
                        $informacoes_pagamento_html .= '<thead><tr><th>Descrição</th><th>Vezes</th><th>Tipo</th><th>Valor</th><th>Cálculo</th></tr></thead>';
                        $informacoes_pagamento_html .= '<tbody>';
                        foreach ($payment_info_array as $item) {
                            $val = $item['valor'] ?? 0;
                            $display_val = ($item['tipo_valor'] == 'Percentual (%)') ? number_format($val, 2, ',', '.') . '%' : format_currency_brl($val);
                            $informacoes_pagamento_html .= '<tr>';
                            $informacoes_pagamento_html .= '<td>' . htmlspecialchars($item['descricao'] ?? 'N/A') . '</td>';
                            $informacoes_pagamento_html .= '<td>' . htmlspecialchars($item['quantas_vezes'] ?? 'N/A') . '</td>';
                            $informacoes_pagamento_html .= '<td>' . htmlspecialchars($item['tipo_valor'] ?? 'N/A') . '</td>';
                            $informacoes_pagamento_html .= '<td>' . $display_val . '</td>';
                            $informacoes_pagamento_html .= '<td>' . htmlspecialchars($item['tipo_calculo'] ?? 'N/A') . '</td>';
                            $informacoes_pagamento_html .= '</tr>';
                        }
                        $informacoes_pagamento_html .= '</tbody></table>';
                    }
                }

                // Buscar Histórico de Auditoria para esta reserva
                $sql_auditoria = "
                    SELECT
                        a.acao,
                        a.detalhes,
                        a.data_acao,
                        COALESCE(u.nome, 'Sistema/Desconhecido') AS usuario_acao_nome,
                        a.ip_origem
                    FROM
                        auditoria a
                    LEFT JOIN
                        usuarios u ON a.usuario_id = u.id
                    WHERE
                        a.entidade = 'Reserva' AND a.entidade_id = ?
                    ORDER BY
                        a.data_acao DESC;
                ";
                $historico_auditoria = fetch_all($sql_auditoria, [$reserva_id], "i");

            }
        }
    } catch (Exception $e) {
        error_log("Erro ao carregar detalhes da reserva para Admin Imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os detalhes da reserva: " . $e->getMessage();
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

    <?php if ($reserva): ?>
    <div class="details-section">
        <h3>Dados da Reserva</h3>
        <div class="details-grid">
            <p><strong>ID da Reserva:</strong> <?php echo htmlspecialchars($reserva['reserva_id']); ?></p>
            <p><strong>Status:</strong> <span class="status-badge status-<?php echo htmlspecialchars($reserva['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva['status']))); ?></span></p>
            <p><strong>Data da Reserva:</strong> <?php echo htmlspecialchars(format_datetime_br($reserva['data_reserva'])); ?></p>
            <p><strong>Data de Expiração:</strong> <?php echo htmlspecialchars(format_datetime_br($reserva['data_expiracao'])); ?></p>
            <p><strong>Valor da Reserva:</strong> <?php echo format_currency_brl($reserva['valor_reserva']); ?></p>
            <p><strong>Comissão Corretor:</strong> <?php echo format_currency_brl($reserva['comissao_corretor']); ?></p>
            <p><strong>Comissão Imobiliária:</strong> <?php echo format_currency_brl($reserva['comissao_imobiliaria']); ?></p>
            <p class="full-width"><strong>Observações:</strong> <?php echo nl2br(htmlspecialchars($reserva['observacoes'])); ?></p>
            <?php if (!empty($reserva['motivo_cancelamento'])): ?>
            <p class="full-width"><strong>Motivo Cancelamento:</strong> <?php echo nl2br(htmlspecialchars($reserva['motivo_cancelamento'])); ?></p>
            <?php endif; ?>
            <p><strong>Última Interação:</strong> <?php echo htmlspecialchars(format_datetime_br($reserva['data_ultima_interacao'])); ?></p>
            <p><strong>Corretor:</strong> <?php echo htmlspecialchars($reserva['corretor_nome']); ?></p>
            <p><strong>Empreendimento:</strong> <?php echo htmlspecialchars($reserva['empreendimento_nome']); ?></p>
            <p><strong>Unidade:</strong> <?php echo htmlspecialchars($reserva['unidade_numero'] . ' (' . $reserva['unidade_andar'] . 'º Andar)'); ?></p>
            <p><strong>Posição:</strong> <?php echo htmlspecialchars($reserva['unidade_posicao']); ?></p>
            <p><strong>Tipo de Unidade:</strong> <?php echo htmlspecialchars($reserva['tipo_unidade_nome'] . ' (' . $reserva['metragem'] . 'm², ' . $reserva['quartos'] . 'Q, ' . $reserva['banheiros'] . 'B, ' . $reserva['vagas'] . 'V)'); ?></p>
            <?php if (!empty($reserva['foto_planta'])): ?>
            <p class="full-width">
                <strong>Planta da Unidade:</strong> <a href="<?php echo BASE_URL . htmlspecialchars($reserva['foto_planta']); ?>" target="_blank" class="btn btn-sm btn-info">Ver Planta</a>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <h3>Clientes da Reserva</h3>
    <?php if (!empty($clientes_da_reserva)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>WhatsApp</th>
                        <th>Email</th>
                        <th>Endereço</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes_da_reserva as $cliente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                            <td><?php echo htmlspecialchars(format_cpf($cliente['cpf'])); ?></td>
                            <td><?php echo htmlspecialchars(format_whatsapp($cliente['whatsapp'])); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['endereco'] . (!empty($cliente['numero']) ? ', ' . $cliente['numero'] : '') . (!empty($cliente['complemento']) ? ' (' . $cliente['complemento'] . ')' : '') . ' - ' . ($cliente['bairro'] ?? 'N/A') . ', ' . $cliente['cidade'] . '/' . $cliente['estado']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Nenhum cliente vinculado a esta reserva.</p>
    <?php endif; ?>

    <h3>Informações de Pagamento da Unidade</h3>
    <div class="payment-info-display">
        <?php echo $informacoes_pagamento_html; ?>
    </div>

    <h3>Documentos Enviados</h3>
    <?php if (!empty($documentos_da_reserva)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Data Upload</th>
                        <th>Status</th>
                        <th>Motivo Rejeição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos_da_reserva as $doc): ?>
                        <tr id="document-row-<?php echo htmlspecialchars($doc['id']); ?>"> 
                            <td><?php echo htmlspecialchars($doc['nome_documento']); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($doc['data_upload'])); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($doc['status']); ?>"><?php echo htmlspecialchars(ucfirst($doc['status'])); ?></span></td>
                            <td><?php echo !empty($doc['motivo_rejeicao']) ? htmlspecialchars($doc['motivo_rejeicao']) : 'N/A'; ?></td>
                            <td class="admin-table-actions">
                                <a href="<?php echo BASE_URL; ?>admin/documentos/download_documento.php?id=<?php echo htmlspecialchars($doc['id']); ?>" class="btn btn-info btn-sm" title="Download Documento" target="_blank">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhum documento enviado para esta reserva.</p>
                <?php endif; ?>

    <h3>Histórico de Andamentos e Auditoria</h3>
    <?php if (!empty($historico_auditoria)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Ação</th>
                        <th>Detalhes</th>
                        <th>Realizado Por</th>
                        <th>IP Origem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico_auditoria as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(format_datetime_br($log['data_acao'])); ?></td>
                            <td><?php echo htmlspecialchars($log['acao']); ?></td>
                            <td><?php echo htmlspecialchars($log['detalhes']); ?></td>
                            <td><?php echo htmlspecialchars($log['usuario_acao_nome']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_origem']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Nenhum histórico de andamento ou log de auditoria encontrado para esta reserva.</p>
    <?php endif; ?>

    <h3>Ações da Reserva</h3>
    <div class="form-actions" style="justify-content: flex-start;">
        <?php 
        // Botão Cancelar Reserva - Admin da Imobiliária pode cancelar
        // Se a reserva não está vendida, cancelada, expirada ou dispensada
        if ($reserva['status'] !== 'vendida' && $reserva['status'] !== 'cancelada' && $reserva['status'] !== 'expirada' && $reserva['status'] !== 'dispensada'): ?>
            <button class="btn btn-danger cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Cancelar Reserva</button>
        <?php endif; ?>
        
        <button class="btn btn-primary" id="printReservaBtn"><i class="fas fa-print"></i> Imprimir</button>
        <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/index.php" class="btn btn-secondary">Voltar à Lista</a>
    </div>

    <?php endif; /* Fim do if ($reserva) */ ?>

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