<?php
// admin/reservas/detalhes.php - Página de Detalhes da Reserva (Admin Master)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../includes/alerts.php';

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Falha ao iniciar conexão com o DB em admin/reservas/detalhes.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

require_permission(['admin', 'corretor_autonomo', 'corretor_imobiliaria', 'admin_imobiliaria']);

$page_title = "Detalhes da Reserva";

$reserva_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$reserva = null;
$clientes_da_reserva = [];
$documentos_da_reserva = [];
$historico_auditoria = []; 

$errors = [];
$success_message = '';

if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}


if (!$reserva_id) {
    $errors[] = "ID da reserva não fornecido.";
    $reserva_id = 0;
} else {
    try {
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
                r.link_documentos_upload,
                corr.id AS corretor_id,
                COALESCE(corr.nome, 'N/A') AS corretor_nome,
                corr.email AS corretor_email,
                corr.creci AS corretor_creci,
                i.id AS imobiliaria_id,
                COALESCE(i.nome, 'N/A') AS imobiliaria_nome,
                i.cnpj AS imobiliaria_cnpj,
                i.email AS imobiliaria_email,
                i.telefone AS imobiliaria_telefone,
                i.cep AS imobiliaria_cep,
                i.endereco AS imobiliaria_endereco,
                i.numero AS imobiliaria_numero,
                i.complemento AS imobiliaria_complemento,
                i.bairro AS imobiliaria_bairro,
                i.cidade AS imobiliaria_cidade,
                i.estado AS imobiliaria_estado,
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
            LEFT JOIN
                imobiliarias i ON corr.imobiliaria_id = i.id
            WHERE
                r.id = ?;
        ";
        $reserva = fetch_single($sql_reserva, [$reserva_id], "i");

        if (!$reserva) {
            $errors[] = "Reserva não encontrada.";
            $reserva_id = 0;
        } else {
            $page_title .= " #{$reserva_id}";

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

            error_log("DEBUG admin/reservas/detalhes.php: Documentos da Reserva {$reserva_id}: " . var_export($documentos_da_reserva, true));

            $informacoes_pagamento_html = '<p>Nenhuma informação de pagamento detalhada para esta unidade.</p>';
            if (!empty($reserva['informacoes_pagamento'])) {
                $payment_info_array = json_decode($reserva['informacoes_pagamento'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($payment_info_array)) {
                    $informacoes_pagamento_html = '<table class="admin-table payment-info-table">';
                    $informacoes_pagamento_html .= '<thead><tr><th>Descrição</th><th>Vezes</th><th>Tipo</th><th>Valor</th><th>Cálculo</th></tr></thead>';
                    $informacoes_pagamento_html .= '<tbody>';
                    foreach ($payment_info_array as $item) {
                        $val = $item['valor'] ?? 0;
                        $display_val = ($item['tipo_valor'] == 'Percentual (%)') ? number_format($val, 2, ',', '.') . '%' : format_currency_brl($val);
                        $informacoes_pagamento_html .= '<tr>';
                        $informacoes_pagamento_html .= '<td data-label="Descrição:">' . htmlspecialchars($item['descricao'] ?? 'N/A') . '</td>';
                        $informacoes_pagamento_html .= '<td data-label="Vezes:">' . htmlspecialchars($item['quantas_vezes'] ?? 'N/A') . '</td>';
                        $informacoes_pagamento_html .= '<td data-label="Tipo:">' . htmlspecialchars($item['tipo_valor'] ?? 'N/A') . '</td>';
                        $informacoes_pagamento_html .= '<td data-label="Valor:">' . $display_val . '</td>';
                        $informacoes_pagamento_html .= '<td data-label="Cálculo:">' . htmlspecialchars($item['tipo_calculo'] ?? 'N/A') . '</td>';
                        $informacoes_pagamento_html .= '</tr>';
                    }
                    $informacoes_pagamento_html .= '</tbody></table>';
                }
            }

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
    } catch (Exception $e) {
        error_log("Erro ao carregar detalhes da reserva: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os detalhes da reserva: " . $e->getMessage();
    }
}


require_once '../../includes/header_dashboard.php';
$logged_user_info_for_html = get_user_info();
$logged_user_type_for_html = $logged_user_info_for_html['tipo'] ?? 'guest';
$logged_user_id_for_html = $logged_user_info_for_html['id'] ?? null;
$imobiliaria_id_logado_for_html = $logged_user_info_for_html['imobiliaria_id'] ?? null;

// DEBUG: Logar o tipo de usuário que está acessando a página para depurar permissões
error_log("DEBUG admin/reservas/detalhes.php: User Type for Permissions Check: " . ($logged_user_info_for_html['tipo'] ?? 'N/A'));
// DEBUG: Logar a sessão completa para verificar user_type e outros dados
error_log("DEBUG admin/reservas/detalhes.php: Full Session: " . var_export($_SESSION, true));


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

    <?php if ($reserva) { ?>
    
    <div class="details-section">
        <h3>Ações da Reserva</h3>
        <div class="form-actions reservation-actions-footer top-actions">
            <?php 
            $expiration_string = time_remaining_string($reserva['data_expiracao']);
            $terminal_statuses = ['vendida', 'cancelada', 'expirada', 'dispensada'];

            $can_approve_reject = ($logged_user_type_for_html === 'admin');
            $can_finalize_sale = ($logged_user_type_for_html === 'admin');
            $can_cancel = ($logged_user_type_for_html === 'admin');

            if (in_array($logged_user_type_for_html, ['corretor_autonomo', 'corretor_imobiliaria'])) {
                if ($reserva['corretor_id'] === $logged_user_id_for_html) {
                    $can_cancel = true;
                }
            }
            if ($logged_user_type_for_html === 'admin_imobiliaria') {
                $corretores_imob_ids = [];
                $corret_vinculados_imob = fetch_all("SELECT id FROM usuarios WHERE imobiliaria_id = ? AND tipo LIKE 'corretor_%'", [$imobiliaria_id_logado_for_html], "i");
                foreach($corret_vinculados_imob as $corret_vinc) {
                    $corretores_imob_ids[] = $corret_vinc['id'];
                }
                if (in_array($reserva['corretor_id'], $corretores_imob_ids)) {
                    $can_cancel = true;
                }
            }
            
            if (!in_array($reserva['status'], $terminal_statuses)) {
                if ($can_approve_reject && $reserva['status'] === 'solicitada') { ?>
                    <button class="btn btn-success" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>" id="approveReservaBtnDetails">Aprovar</button>
                    <button class="btn btn-danger" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>" id="rejectReservaBtnDetails">Rejeitar</button>
                <?php } elseif ($reserva['status'] === 'documentos_enviados' && $can_approve_reject) { ?>
                    <a href="<?php echo BASE_URL; ?>admin/documentos/index.php?reserva_id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-secondary">Analisar Docs</a>
                <?php } elseif ($reserva['status'] === 'documentos_aprovados' && $can_finalize_sale) { ?>
                    <a href="<?php echo BASE_URL; ?>admin/contratos/index.php?reserva_id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-secondary">Enviar Contrato</a>
                <?php } elseif (in_array($reserva['status'], ['contrato_enviado', 'aguardando_assinatura_eletronica']) && $can_finalize_sale) { ?>
                    <button class="btn btn-success finalize-sale-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Finalizar Venda</button>
                    <button class="btn btn-primary simulate-sign-contract-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Simular Assinatura</button>
                <?php }
                if ($can_cancel) { ?>
                    <button class="btn btn-danger cancel-reserva-btn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>">Cancelar Reserva</button>
                <?php }
            } ?>
            <button class="btn btn-primary" id="printReservaBtn"><i class="fas fa-print"></i> Imprimir</button>
            <a href="<?php echo BASE_URL; ?>admin/reservas/index.php" class="btn btn-secondary">Voltar à Lista</a>
        </div>
    </div>


    <div class="details-section">
        <h3>Dados da Reserva</h3>
        <div class="details-grid">
            <p><strong>ID da Reserva:</strong> <?php echo htmlspecialchars($reserva['reserva_id']); ?></p>
            <p><strong>Status:</strong> <span class="status-badge status-<?php echo htmlspecialchars($reserva['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva['status']))); ?></span></p>
            <p><strong>Data da Reserva:</strong> <?php echo htmlspecialchars(format_datetime_br($reserva['data_reserva'])); ?></p>
            <p><strong>Data de Expiração:</strong> <?php echo htmlspecialchars(format_datetime_br($reserva['data_expiracao'])); ?></p>
            <p><strong>Valor da Reserva:</strong> <?php echo format_currency_brl($reserva['valor_reserva']); ?></p>
            
            <?php if (!empty($reserva['motivo_cancelamento'])) { ?>
            <p class="full-width"><strong>Motivo Cancelamento:</strong> <?php echo nl2br(htmlspecialchars($reserva['motivo_cancelamento'])); ?></p>
            <?php } ?>
            <p><strong>Última Interação:</strong> <?php echo htmlspecialchars(format_datetime_br($reserva['data_ultima_interacao'])); ?></p>
            
            <p><strong>Empreendimento:</strong> <?php echo htmlspecialchars($reserva['empreendimento_nome']); ?></p>
            <p><strong>Unidade:</strong> <?php echo htmlspecialchars($reserva['unidade_numero'] . ' (' . $reserva['unidade_andar'] . 'º Andar)'); ?></p>
            <p><strong>Posição:</strong> <?php echo htmlspecialchars($reserva['unidade_posicao']); ?></p>
            <p><strong>Tipo de Unidade:</strong> <?php echo htmlspecialchars($reserva['tipo_unidade_nome'] . ' (' . $reserva['metragem'] . 'm², ' . $reserva['quartos'] . 'Q, ' . $reserva['banheiros'] . 'B, ' . $reserva['vagas'] . 'V)'); ?></p>
            <?php if (!empty($reserva['foto_planta'])) { ?>
            <p class="full-width">
                <strong>Planta da Unidade:</strong><br>
                <img src="<?php echo BASE_URL . htmlspecialchars($reserva['foto_planta']); ?>" alt="Planta da Unidade" style="max-width: 300px; height: auto; margin-top: 10px; border-radius: var(--border-radius-sm);">
            </p>
            <?php } ?>
        </div>
    </div>

    <div class="details-section">
        <h3>Observações do Corretor/Lead</h3>
        <div class="details-grid">
            <p class="full-width"><?php echo nl2br(htmlspecialchars($reserva['observacoes'] ?? 'N/A')); ?></p>
        </div>
    </div>

    <div class="details-section">
        <h3>Dados do Corretor e Imobiliária</h3>
        <div class="details-grid">
            <p><strong>Corretor:</strong> <?php echo htmlspecialchars($reserva['corretor_nome']); ?></p>
            <p><strong>CRECI do Corretor:</strong> <?php echo htmlspecialchars($reserva['corretor_creci'] ?? 'N/A'); ?></p>
            <p><strong>Email do Corretor:</strong> <?php echo htmlspecialchars($reserva['corretor_email']); ?></p>
            <p><strong>Comissão Corretor:</strong> <?php echo format_currency_brl($reserva['comissao_corretor']); ?></p>
            <p class="full-width">
                <?php if (!empty($reserva['corretor_id'])) { ?>
                    <a href="<?php echo BASE_URL; ?>admin/usuarios/editar.php?id=<?php echo htmlspecialchars($reserva['corretor_id']); ?>" class="btn btn-secondary btn-sm">Ver Perfil do Corretor</a>
                <?php } else { ?>
                    <span class="text-muted">Corretor não atribuído ou removido.</span>
                <?php } ?>
            </p>
            <?php if (!empty($reserva['imobiliaria_id'])) { ?>
                <p><strong>Imobiliária:</strong> <?php echo htmlspecialchars($reserva['imobiliaria_nome']); ?></p>
                <p><strong>CNPJ da Imobiliária:</strong> <?php echo htmlspecialchars(format_cnpj($reserva['imobiliaria_cnpj'])); ?></p>
                <p><strong>Email da Imobiliária:</strong> <?php echo htmlspecialchars($reserva['imobiliaria_email']); ?></p>
                <p><strong>Telefone da Imobiliária:</strong> <?php echo htmlspecialchars(format_whatsapp($reserva['imobiliaria_telefone'])); ?></p>
                <p class="full-width"><strong>Endereço da Imobiliária:</strong> <?php echo htmlspecialchars(($reserva['imobiliaria_endereco'] ?? '') . (!empty($reserva['imobiliaria_numero']) ? ', ' . $reserva['imobiliaria_numero'] : '') . (!empty($reserva['imobiliaria_complemento']) ? ' (' . $reserva['imobiliaria_complemento'] . ')' : '') . ' - ' . ($reserva['imobiliaria_bairro'] ?? 'N/A') . ', ' . ($reserva['imobiliaria_cidade'] ?? 'N/A') . '/' . ($reserva['imobiliaria_estado'] ?? 'N/A') . ' - CEP: ' . format_cep($reserva['imobiliaria_cep'] ?? '')); ?></p>
                <p><strong>Comissão Imobiliária:</strong> <?php echo format_currency_brl($reserva['comissao_imobiliaria']); ?></p>
                <p class="full-width">
                    <a href="<?php echo BASE_URL; ?>admin/imobiliarias/editar.php?id=<?php echo htmlspecialchars($reserva['imobiliaria_id']); ?>" class="btn btn-secondary btn-sm">Ver Imobiliária</a>
                </p>
            <?php } else { ?>
                <p class="full-width">Corretor autônomo (sem vínculo com imobiliária).</p>
            <?php } ?>
        </div>
    </div>


    <h3>Clientes da Reserva</h3>
    <?php if (!empty($clientes_da_reserva)) { ?>
        <div class="admin-table-responsive">
            <table class="admin-table client-table"> <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>WhatsApp</th>
                        <th>Email</th>
                        <th>Endereço Completo</th> <th>Cadastro</th>
                        <th>Ações</th> </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes_da_reserva as $cliente) { ?>
                        <tr>
                            <td data-label="Nome:"><?php echo htmlspecialchars($cliente['nome']); ?></td>
                            <td data-label="CPF:"><?php echo htmlspecialchars(format_cpf($cliente['cpf'])); ?></td>
                            <td data-label="WhatsApp:"><?php echo htmlspecialchars(format_whatsapp($cliente['whatsapp'])); ?></td>
                            <td data-label="Email:"><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td data-label="Endereço Completo:"><?php echo htmlspecialchars(($cliente['endereco'] ?? '') . (!empty($cliente['numero']) ? ', ' . $cliente['numero'] : '') . (!empty($cliente['complemento']) ? ' (' . $cliente['complemento'] . ')' : '') . ' - ' . ($cliente['bairro'] ?? 'N/A') . ', ' . ($cliente['cidade'] ?? 'N/A') . '/' . ($cliente['estado'] ?? 'N/A') . ' - CEP: ' . format_cep($cliente['cep'] ?? '')); ?></td>
                            <td data-label="Cadastro:"><?php echo htmlspecialchars(format_datetime_br($cliente['data_cadastro'])); ?></td>
                            <td class="admin-table-actions">
                                <a href="<?php echo BASE_URL; ?>admin/clientes/detalhes.php?id=<?php echo htmlspecialchars($cliente['cliente_id']); ?>" class="btn btn-info btn-sm" title="Ver Detalhes do Cliente">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <p>Nenhum cliente vinculado a esta reserva.</p>
    <?php } ?>
    <button class="btn btn-secondary mt-3" id="editClientsBtn" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>"><i class="fas fa-edit"></i> Editar Clientes</button>


    <h3>Informações de Pagamento da Unidade (Plano Padrão)</h3>
    <div class="payment-info-display">
        <?php echo $informacoes_pagamento_html; ?>
    </div>

    <h3>Documentos Enviados</h3>
    <?php if (!empty($documentos_da_reserva)) { ?>
        <div class="admin-table-responsive">
            <table class="admin-table document-table">
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
                    <?php foreach ($documentos_da_reserva as $doc) { ?>
                        <tr>
                            <td data-label="Documento:"><i class="<?php echo htmlspecialchars($icon_class); ?> <?php echo htmlspecialchars($icon_color_class); ?>"></i> <?php echo htmlspecialchars($doc['nome_documento']); ?></td>
                            <td data-label="Data Upload:"><?php echo htmlspecialchars(format_datetime_br($doc['data_upload'])); ?></td>
                            <td data-label="Status:"><span class="status-badge status-<?php echo htmlspecialchars($doc['status']); ?>"><?php echo htmlspecialchars(ucfirst($doc['status'])); ?></span></td>
                            <td data-label="Motivo Rejeição:"><?php echo !empty($doc['motivo_rejeicao']) ? nl2br(htmlspecialchars($doc['motivo_rejeicao'])) : 'N/A'; ?></td>
                            <td class="admin-table-actions">
                                <?php if (!empty($doc['caminho_arquivo'])) { ?>
                                <a href="<?php echo BASE_URL . htmlspecialchars($doc['caminho_arquivo']); ?>" target="_blank" class="btn btn-info btn-sm" title="Ver Documento">
                                    <i class="fas fa-eye"></i> Ver Doc
                                </a>
                                <?php } else { ?>
                                <button class="btn btn-info btn-sm" disabled title="Documento não enviado ou caminho ausente"><i class="fas fa-eye-slash"></i> Sem Doc</button>
                                <?php } ?>

                                <?php 
                                    $can_manage_docs = in_array($logged_user_info['tipo'] ?? 'guest', ['admin']);
                                    if ($can_manage_docs) { 
                                ?>
                                    <?php if ($doc['status'] === 'pendente' || $doc['status'] === 'rejeitado') { ?>
                                        <button class="btn btn-success btn-sm approve-doc-btn" data-doc-id="<?php echo htmlspecialchars($doc['id']); ?>" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>" data-nome="<?php echo htmlspecialchars($doc['nome_documento']); ?>" data-status-atual="<?php echo htmlspecialchars($doc['status']); ?>" data-motivo-rejeicao="<?php echo htmlspecialchars($doc['motivo_rejeicao'] ?? ''); ?>" title="Aprovar Documento"><i class="fas fa-check"></i> Aprovar</button>
                                        <button class="btn btn-danger btn-sm reject-doc-btn" data-doc-id="<?php echo htmlspecialchars($doc['id']); ?>" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>" data-nome="<?php echo htmlspecialchars($doc['nome_documento']); ?>" data-status-atual="<?php echo htmlspecialchars($doc['status']); ?>" data-motivo-rejeicao="<?php echo htmlspecialchars($doc['motivo_rejeicao'] ?? ''); ?>" title="Rejeitar Documento"><i class="fas fa-times"></i> Rejeitar</button>
                                    <?php } ?>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <p>Nenhum documento enviado para esta reserva.</p>
    <?php } ?>

    <?php 
    $can_upload_doc_admin = in_array($logged_user_info['tipo'] ?? 'guest', ['admin']);
    $can_request_docs_admin = in_array($logged_user_info['tipo'] ?? 'guest', ['admin']);
    ?>
    <div class="details-section">
        <h3>Gestão de Documentos da Reserva</h3>
        <div class="form-actions">
            <?php if ($can_request_docs_admin && !in_array($reserva['status'], $terminal_statuses)) { ?>
                <button class="btn btn-info" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>" id="requestDocsBtnDetails">Solicitar Documentação (Link)</button>
            <?php } ?>

            <?php if ($can_upload_doc_admin && !in_array($reserva['status'], $terminal_statuses)) { ?>
                <button class="btn btn-primary" data-reserva-id="<?php echo htmlspecialchars($reserva['reserva_id']); ?>" id="uploadDocAdminBtn">Upload Manual de Documento</button>
            <?php } ?>
            
            <?php if (!empty($reserva['link_documentos_upload'])) { ?>
            <button class="btn btn-secondary copy-link-btn" data-link="<?php echo htmlspecialchars($reserva['link_documentos_upload']); ?>"><i class="fas fa-copy"></i> Copiar Link Atual</button>
            <?php } ?>
        </div>
    </div>


    <h3>Histórico de Andamentos e Auditoria</h3>
    <?php if (!empty($historico_auditoria)) { ?>
        <div class="admin-table-responsive">
            <table class="admin-table audit-table"> <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Ação</th>
                        <th>Detalhes</th>
                        <th>Realizado Por</th>
                        <th>IP Origem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico_auditoria as $log) { ?>
                        <tr>
                            <td data-label="Data/Hora:"><?php echo htmlspecialchars(format_datetime_br($log['data_acao'])); ?></td>
                            <td data-label="Ação:"><?php echo htmlspecialchars($log['acao']); ?></td>
                            <td data-label="Detalhes:">
                                <?php
                                $detalhes_json = json_decode($log['detalhes'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($detalhes_json)) {
                                    echo '<pre><code>' . htmlspecialchars(json_encode($detalhes_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</code></pre>';
                                } else {
                                    echo nl2br(htmlspecialchars($log['detalhes']));
                                }
                                ?>
                            </td>
                            <td data-label="Realizado Por:"><?php echo htmlspecialchars($log['usuario_acao_nome']); ?></td>
                            <td data-label="IP Origem:"><?php echo htmlspecialchars($log['ip_origem']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <p>Nenhum histórico de andamento ou log de auditoria encontrado para esta reserva.</p>
    <?php } ?>


    <div id="approveDocumentModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Aprovar Documento</h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <p>Confirmar a aprovação deste documento (ID: <strong id="approveDocumentIdDisplay"></strong>) para a Reserva #<strong id="approveDocumentReservaIdDisplay"></strong>?</p>
                <form id="approveDocumentForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php">
                    <input type="hidden" name="action" value="approve_document">
                    <input type="hidden" name="document_id" id="approveDocumentIdInput">
                    <input type="hidden" name="reserva_id" id="approveDocumentReservaIdInput">
                    <div class="form-actions" style="justify-content: space-around;">
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
                        <label for="rejectionReason">Motivo da Rejeição:</label>
                        <textarea id="rejectionReason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                    <div class="form-actions" style="justify-content: space-around;">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Rejeitar</button>
                    </div>
                </form>
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
                <input type="hidden" name="reserva_id" id="simulateSignReservaIdInput">
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

    <div id="uploadDocAdminModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Manual de Documento para Reserva #<span id="uploadDocAdminReservaIdDisplay"></span></h3>
                <button type="button" class="modal-close-btn">×</button>
            </div>
            <div class="modal-body">
                <form id="uploadDocAdminForm" method="POST" action="<?php echo BASE_URL; ?>api/reserva.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_document_admin">
                    <input type="hidden" name="reserva_id" id="uploadDocAdminReservaId">
                    
                    <div class="form-group">
                        <label for="clienteSelectUpload">Cliente:</label>
                        <select id="clienteSelectUpload" name="cliente_id_upload" class="form-control" required>
                            </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="nomeDocumentoUpload">Nome do Documento (Ex: RG, Comprovante de Renda):</label>
                        <input type="text" id="nomeDocumentoUpload" name="nome_documento_upload" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="documentFileAdmin">Arquivo (PDF, JPG, PNG):</label>
                        <input type="file" id="documentFileAdmin" name="document_file_admin" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="form-text text-muted">Tamanho máximo: 10MB.</small>
                    </div>

                    <div class="form-actions mt-4">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Upload e Aprovar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php } // Fim do if ($reserva) ?>

<?php require_once '../../includes/footer_dashboard.php'; ?>