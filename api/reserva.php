<?php
// api/reserva.php - Endpoint API para gestão de reservas, documentos e clientes relacionados

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/alerts.php';
require_once __DIR__ . '/../includes/email.php';

session_start();

header('Content-Type: application/json');

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Falha ao iniciar conexão com o DB em api/reserva.php: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Erro crítico: Não foi possível conectar ao banco de dados.'];
    echo json_encode($response);
    exit();
}

$logged_user_info = get_user_info();
// DEBUG: Verifique o que get_user_info() retorna para o usuário logado
error_log("API Reserva: logged_user_info no início da API: " . var_export($logged_user_info, true));

$user_id = $logged_user_info['id'] ?? null;
$user_name = $logged_user_info['name'] ?? 'Desconhecido'; // Corrigido para 'name'
$user_type = $logged_user_info['type'] ?? 'guest'; // Corrigido para 'type'
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
$imobiliaria_id_logado = $logged_user_info['imobiliaria_id'] ?? null;

if (!$user_id) {
    $response = ['success' => false, 'message' => 'Usuário não autenticado. Por favor, faça login novamente.'];
    echo json_encode($response);
    exit();
}

$response = ['success' => false, 'message' => 'Requisição inválida ou ação não suportada.'];

$action = null;
if (isset($_POST['action'])) {
    $action = trim(strtolower($_POST['action']));
} elseif (isset($_GET['action'])) {
    $action = trim(strtolower($_GET['action']));
}

error_log("API Reserva: Ação recebida (sanitizada): '{$action}' (length: " . strlen((string)$action) . ")");


$reserva_id = filter_input(INPUT_POST, 'reserva_id', FILTER_VALIDATE_INT);
if (!$reserva_id) {
    $reserva_id = filter_input(INPUT_GET, 'reserva_id', FILTER_VALIDATE_INT);
}

$document_id = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
if (!$document_id) {
    $document_id = filter_input(INPUT_GET, 'document_id', FILTER_VALIDATE_INT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        switch ($action) {
            case 'get_clients':
                require_permission(['admin', 'corretor_autonomo', 'corretor_imobiliaria', 'admin_imobiliaria'], true);
                if (!$reserva_id) {
                    throw new Exception("ID da reserva não fornecido para obter clientes.");
                }
                $clientes_reserva = fetch_all("
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
                        c.estado
                    FROM
                        clientes c
                    JOIN
                        reservas_clientes rc ON c.id = rc.cliente_id
                    WHERE
                        rc.reserva_id = ?;
                ", [$reserva_id], "i");
                $response['success'] = true;
                $response['clients'] = $clientes_reserva;
                break;
            default:
                $response['message'] = 'Ação GET não suportada ou sem ID de reserva válido.';
                break;
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $actions_requiring_reserva_id = [
            'aprovar_reserva', 'rejeitar_reserva', 'solicitar_documentacao',
            'finalize_sale', 'cancel_reserva', 'edit_clients',
            'approve_document', 'reject_document', 'dispensar_lead',
            'send_contract', 'mark_contract_sent', 'simulate_sign_contract',
            'assign_corretor_to_lead', 'take_lead_admin', 'take_lead_broker', 'dispense_lead_broker'
        ];

        if (in_array($action, $actions_requiring_reserva_id) && !$reserva_id) {
            throw new Exception("ID da reserva inválido ou não fornecido para a ação '{$action}'.");
        }

        $current_reserva = null;
        if ($reserva_id) {
            $current_reserva = fetch_single("SELECT id, unidade_id, corretor_id, status, empreendimento_id, link_documentos_upload FROM reservas WHERE id = ?", [$reserva_id], "i");
            if (!$current_reserva) {
                throw new Exception("Reserva com ID #{$reserva_id} não encontrada.");
            }
        }
        
        // Obter nome do empreendimento e número da unidade para alertas
        $empreendimento_info_for_alert = fetch_single("SELECT nome FROM empreendimentos WHERE id = ?", [$current_reserva['empreendimento_id']], "i");
        $unidade_info_for_alert = fetch_single("SELECT numero FROM unidades WHERE id = ?", [$current_reserva['unidade_id']], "i");


        switch ($action) {
            case 'aprovar_reserva':
                error_log("API Reserva: Action in switch: aprovar_reserva");
                require_permission(['admin'], true);
                if (!in_array($current_reserva['status'], ['solicitada', 'documentos_pendentes'])) {
                    throw new Exception("Reserva #{$reserva_id} não está no status 'solicitada' ou 'documentos_pendentes' para ser aprovada. Status atual: " . htmlspecialchars($current_reserva['status']));
                }

                $empreendimento_info = fetch_single("SELECT momento_envio_documentacao FROM empreendimentos WHERE id = ?", [$current_reserva['empreendimento_id']], "i");
                $momento_envio_documentacao_empreendimento = $empreendimento_info['momento_envio_documentacao'] ?? '';

                $new_reserva_status = 'aprovada';
                if ($momento_envio_documentacao_empreendimento === 'Na Proposta de Reserva' || $momento_envio_documentacao_empreendimento === 'Após Confirmação de Reserva') {
                    $new_reserva_status = 'documentos_pendentes';
                }
                
                $unidade_atual = fetch_single("SELECT status FROM unidades WHERE id = ?", [$current_reserva['unidade_id']], "i");
                if ($unidade_atual && ($unidade_atual['status'] === 'vendida' || $unidade_atual['status'] === 'cancelada')) {
                     throw new Exception("Unidade #{$current_reserva['unidade_id']} já está " . htmlspecialchars($unidade_atual['status']) . ". Não é possível aprovar a reserva.");
                }
                
                if ($unidade_atual['status'] === 'disponivel') {
                    update_delete_data("UPDATE unidades SET status = 'reservada' WHERE id = ?", [$current_reserva['unidade_id']], "i");
                }

                $affected_reservas = update_delete_data("UPDATE reservas SET status = ?, data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?", [$new_reserva_status, $user_id, $reserva_id], "sii");
                if ($affected_reservas === 0) {
                    throw new Exception("Reserva #{$reserva_id} não pôde ser atualizada para '{$new_reserva_status}'. Nenhuma linha afetada.");
                }

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Aprovar Reserva', 'Reserva', $reserva_id, "Reserva #{$reserva_id} aprovada por {$user_name}. Unidade #{$unidade_info_for_alert['numero']} marcada como 'reservada'. Novo status: {$new_reserva_status}.", $user_ip],
                            "isssss");

                create_alert('reserva_aprovada', "Sua reserva #{$reserva_id} para a unidade {$unidade_info_for_alert['numero']} foi aprovada. Prossiga com o processo.", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                create_alert('reserva_aprovada', "Você ({$user_name}) aprovou a reserva #{$reserva_id}. Status: {$new_reserva_status}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Reserva #{$reserva_id} aprovada com sucesso! Unidade marcada como reservada. Próxima etapa: Análise de Documentos.";
                break;

            case 'send_contract':
                error_log("API Reserva: Action in switch: send_contract");
                require_permission(['admin'], true);
                if ($current_reserva['status'] !== 'documentos_aprovados') {
                    throw new Exception("Contrato da Reserva #{$reserva_id} não pode ser enviado. Status atual: " . htmlspecialchars($current_reserva['status']));
                }

                $send_method = filter_input(INPUT_POST, 'send_method', FILTER_UNSAFE_RAW);
                $cliente_email = filter_input(INPUT_POST, 'cliente_email', FILTER_SANITIZE_EMAIL);
                $cliente_nome = filter_input(INPUT_POST, 'cliente_nome', FILTER_UNSAFE_RAW);

                $contract_file_path = null;
                $new_status = 'contrato_enviado';

                if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $_FILES['contract_file']['tmp_name'];
                    $file_name_original = basename($_FILES['contract_file']['name']);
                    $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
                    $allowed_ext = ['pdf'];

                    if (!in_array($file_ext, $allowed_ext)) {
                        throw new Exception("Tipo de arquivo inválido. Apenas PDF é permitido.");
                    }
                    if ($_FILES['contract_file']['size'] > 10 * 1024 * 1024) {
                        throw new Exception("Tamanho do arquivo excede o limite de 10MB.");
                    }

                    $upload_dir = __DIR__ . '/../uploads/contratos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $new_file_name = "contrato_{$reserva_id}_" . uniqid() . ".{$file_ext}";
                    $destination = $upload_dir . $new_file_name;

                    if (!move_uploaded_file($file_tmp_name, $destination)) {
                        throw new Exception("Falha ao fazer upload do arquivo do contrato.");
                    }
                    $contract_file_path = 'uploads/contratos/' . $new_file_name;
                } else if ($send_method === 'manual') {
                    if (empty($contract_file_path) && ($_POST['mark_only'] ?? 'false') !== 'true') {
                        throw new Exception("Arquivo do contrato é obrigatório para envio. Se desejar apenas marcar como enviado, use a opção específica.");
                    }
                }
                
                if ($send_method === 'clicksign') {
                    $new_status = 'aguardando_assinatura_eletronica';
                    if (empty($contract_file_path)) {
                        $contract_file_path = null;
                    }
                    // TODO: Chamar API da ClickSign aqui (simulado)
                } else if ($send_method === 'manual') {
                    $new_status = 'contrato_enviado';
                }

                $affected_rows = update_delete_data(
                    "UPDATE reservas SET status = ?, caminho_contrato_final = ?, data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?",
                    [$new_status, $contract_file_path, $user_id, $reserva_id],
                    "ssii"
                );

                if ($affected_rows === 0) {
                    throw new Exception("Nenhuma alteração feita na reserva. O contrato pode já ter sido enviado ou outro problema ocorreu.");
                }

                if ($send_method === 'manual') {
                    $message = "Contrato da Reserva #{$reserva_id} enviado manualmente e salvo no sistema. Notifique o cliente para assinatura.";
                    create_alert('contrato_enviado', "Contrato da reserva #{$reserva_id} enviado manualmente. Aguarde assinatura do cliente {$cliente_nome}.", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                    create_alert('contrato_enviado', "Você ({$user_name}) enviou o contrato da reserva #{$reserva_id} manualmente.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                } elseif ($send_method === 'clicksign') {
                    $message = "Processo de Assinatura Eletrônica (Simulado) iniciado para o contrato da Reserva #{$reserva_id}. Status: 'Aguardando Assinatura Eletrônica'.";
                    create_alert('contrato_enviado', "Contrato da reserva #{$reserva_id} enviado para assinatura eletrônica (simulado).", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                    create_alert('contrato_enviado', "Você ({$user_name}) iniciou o envio ClickSign (simulado) para a reserva #{$reserva_id}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);
                } else {
                    throw new Exception("Método de envio de contrato inválido.");
                }
                
                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Enviar Contrato', 'Reserva', $reserva_id, "Contrato da Reserva #{$reserva_id} enviado por {$user_name} via {$send_method}. Caminho: {$contract_file_path}.", $user_ip],
                            "isssss");

                $response['success'] = true;
                $response['message'] = $message;
                break;

            case 'mark_contract_sent':
                error_log("API Reserva: Action in switch: mark_contract_sent");
                require_permission(['admin'], true);
                if ($current_reserva['status'] !== 'documentos_aprovados') {
                    throw new Exception("Contrato da Reserva #{$reserva_id} não pode ser marcado como enviado. Status atual: " . htmlspecialchars($current_reserva['status']));
                }

                $affected_rows = update_delete_data(
                    "UPDATE reservas SET status = 'contrato_enviado', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?",
                    [$user_id, $reserva_id],
                    "ii"
                );

                if ($affected_rows === 0) {
                    throw new Exception("Falha ao marcar contrato da reserva #{$reserva_id} como enviado. Nenhuma linha afetada.");
                }

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Marcar Contrato como Enviado (Manual)', 'Reserva', $reserva_id, "Contrato da Reserva #{$reserva_id} marcado como enviado manualmente por {$user_name}.", $user_ip],
                            "isssss");

                create_alert('contrato_enviado', "Contrato da reserva #{$reserva_id} marcado como enviado manualmente. Aguarde assinatura.", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                create_alert('contrato_enviado', "Você ({$user_name}) marcou o contrato da reserva #{$reserva_id} como enviado.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Contrato da Reserva #{$reserva_id} marcado como enviado com sucesso!";
                break;

            case 'simulate_sign_contract':
                error_log("API Reserva: Action in switch: simulate_sign_contract");
                require_permission(['admin'], true);
                if (!in_array($current_reserva['status'], ['contrato_enviado', 'aguardando_assinatura_eletronica'])) {
                    throw new Exception("Não é possível simular assinatura para a Reserva #{$reserva_id}. Status atual: " . htmlspecialchars($current_reserva['status']));
                }
                
                $affected_rows = update_delete_data(
                    "UPDATE reservas SET status = 'vendida', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?",
                    [$user_id, $reserva_id],
                    "ii"
                );

                if ($affected_rows === 0) {
                    throw new Exception("Falha ao simular assinatura do contrato da Reserva #{$reserva_id}. Nenhuma linha afetada.");
                }

                update_delete_data(
                    "UPDATE unidades SET status = 'vendida', data_atualizacao = NOW() WHERE id = ?",
                    [$current_reserva['unidade_id']],
                    "i"
                );

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Simular Assinatura Contrato', 'Reserva', $reserva_id, "Assinatura do contrato da Reserva #{$reserva_id} simulada por {$user_name}. Venda finalizada.", $user_ip],
                            "isssss");

                create_alert('venda_concluida', "Contrato da reserva #{$reserva_id} assinado (simulado). Venda finalizada!", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                create_alert('venda_concluida', "Você ({$user_name}) simulou a assinatura e finalizou a venda da reserva #{$reserva_id}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Assinatura do contrato da Reserva #{$reserva_id} simulada e venda finalizada com sucesso!";
                break;

            case 'finalize_sale':
                error_log("API Reserva: Action in switch: finalize_sale - Inside the case!");
                require_permission(['admin'], true);
                if (!in_array($current_reserva['status'], ['contrato_enviado', 'aguardando_assinatura_eletronica'])) {
                    throw new Exception("Reserva #{$reserva_id} não está no status 'contrato_enviado' ou 'aguardando_assinatura_eletronica' para ser finalizada. Status atual: " . htmlspecialchars($current_reserva['status']));
                }
                
                $affected_rows_reserva = update_delete_data(
                    "UPDATE reservas SET status = 'vendida', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?",
                    [$user_id, $reserva_id],
                    "ii"
                );

                if ($affected_rows_reserva === 0) {
                    throw new Exception("Nenhuma alteração feita na reserva #{$reserva_id}. Pode já estar vendida ou outro problema.");
                }

                update_delete_data(
                    "UPDATE unidades SET status = 'vendida', data_atualizacao = NOW(), usuario_atualizacao_id = ? WHERE id = ?",
                    [$user_id, $current_reserva['unidade_id']],
                    "ii"
                );

                insert_data(
                    "INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                    [$user_id, 'Finalizar Venda', 'Reserva', $reserva_id, "Reserva #{$reserva_id} e Unidade #{$unidade_info_for_alert['numero']} marcadas como vendidas por {$user_name}.", $user_ip],
                    "isssss"
                );

                create_alert('venda_concluida', 'A venda da unidade ' . $unidade_info_for_alert['numero'] . ' do empreendimento ' . $empreendimento_info_for_alert['nome'] . ' foi finalizada!', $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                create_alert('venda_concluida', 'Você (' . $user_name . ') finalizou a venda da reserva ' . $reserva_id . '.', $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = 'Venda finalizada com sucesso!';
                break;
            
            case 'assign_corretor_to_lead':
                error_log("API Reserva: Action in switch: assign_corretor_to_lead");
                require_permission(['admin'], true);
                $corretor_id_to_assign = filter_input(INPUT_POST, 'corretor_id', FILTER_VALIDATE_INT);

                if (!$corretor_id_to_assign) {
                    throw new Exception("ID do corretor para atribuição inválido.");
                }
                if ($current_reserva['status'] !== 'solicitada' || $current_reserva['corretor_id'] !== null) {
                    throw new Exception("Este lead (Reserva #{$reserva_id}) não está no status 'solicitada' ou já foi atribuído e não pode ser reatribuído como lead.");
                }

                $empreendimento_info = fetch_single("SELECT momento_envio_documentacao FROM empreendimentos WHERE id = ?", [$current_reserva['empreendimento_id']], "i");
                $momento_envio_documentacao_empreendimento = $empreendimento_info['momento_envio_documentacao'] ?? '';

                $new_reserva_status = 'aprovada';
                if ($momento_envio_documentacao_empreendimento === 'Na Proposta de Reserva' || $momento_envio_documentacao_empreendimento === 'Após Confirmação de Reserva') {
                    $new_reserva_status = 'documentos_pendentes';
                }

                $affected_rows = update_delete_data(
                    "UPDATE reservas SET corretor_id = ?, status = ?, data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?",
                    [$corretor_id_to_assign, $new_reserva_status, $user_id, $reserva_id],
                    "isii"
                );

                if ($affected_rows === 0) {
                    throw new Exception("Falha ao atribuir corretor ao lead #{$reserva_id}. Nenhuma linha afetada.");
                }

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Atribuir Lead a Corretor', 'Reserva', $reserva_id, "Lead (Reserva #{$reserva_id}) atribuído por {$user_name} ao corretor ID: {$corretor_id_to_assign}. Novo status: {$new_reserva_status}.", $user_ip],
                            "isssss");

                create_alert('novo_lead_atribuido', "Um novo lead para a unidade {$unidade_info_for_alert['numero']} foi atribuído a você (Reserva #{$reserva_id}).", $corretor_id_to_assign, $reserva_id, 'reserva', $imobiliaria_id_logado);
                create_alert('lead_atribuido', "Você ({$user_name}) atribuiu o lead (Reserva #{$reserva_id}) ao corretor.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Lead #{$reserva_id} atribuído ao corretor com sucesso! Status: {$new_reserva_status}.";
                break;

            case 'take_lead_admin':
                error_log("API Reserva: Action in switch: take_lead_admin");
                require_permission(['admin'], true);

                if ($current_reserva['status'] !== 'solicitada' || $current_reserva['corretor_id'] !== null) {
                    throw new Exception("Este lead (Reserva #{$reserva_id}) não está no status 'solicitada' ou já foi atribuído e não pode ser assumido.");
                }
                
                $empreendimento_info = fetch_single("SELECT momento_envio_documentacao FROM empreendimentos WHERE id = ?", [$current_reserva['empreendimento_id']], "i");
                $momento_envio_documentacao_empreendimento = $empreendimento_info['momento_envio_documentacao'] ?? '';

                $new_reserva_status = 'aprovada';
                if ($momento_envio_documentacao_empreendimento === 'Na Proposta de Reserva' || $momento_envio_documentacao_empreendimento === 'Após Confirmação de Reserva') {
                    $new_reserva_status = 'documentos_pendentes';
                }

                $affected_rows = update_delete_data(
                    "UPDATE reservas SET corretor_id = ?, status = ?, data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?",
                    [$user_id, $new_reserva_status, $user_id, $reserva_id],
                    "sii"
                );

                if ($affected_rows === 0) {
                    throw new Exception("Falha ao atender o lead #{$reserva_id} como Admin. Nenhuma linha afetada.");
                }

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Atender Lead como Admin', 'Reserva', $reserva_id, "Lead (Reserva #{$reserva_id}) assumido por {$user_name} (Admin). Novo status: {$new_reserva_status}.", $user_ip],
                            "isssss");

                create_alert('lead_assumido_admin', "Você ({$user_name}) assumiu o atendimento do lead (Reserva #{$reserva_id}) para a unidade {$unidade_info_for_alert['numero']}. Status: {$new_reserva_status}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Lead #{$reserva_id} assumido com sucesso pelo Admin. Status da reserva atualizado para '{$new_reserva_status}'.";
                break;    

            case 'rejeitar_reserva':
                error_log("API Reserva: Action in switch: rejeitar_reserva");
                require_permission(['admin'], true);
                if (!in_array($current_reserva['status'], ['solicitada', 'aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'documentos_rejeitados', 'contrato_enviado', 'aguardando_assinatura_eletronica', 'documentos_solicitados'])) {
                    throw new Exception("Reserva #{$reserva_id} não está em um status que possa ser rejeitado/cancelado. Status atual: " . htmlspecialchars($current_reserva['status']));
                }
                $motivo_cancelamento = filter_input(INPUT_POST, 'motivo_cancelamento', FILTER_UNSAFE_RAW) ?? 'Rejeitada pelo administrador.';

                $affected_reservas = update_delete_data("UPDATE reservas SET status = 'cancelada', motivo_cancelamento = ?, data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?", [$motivo_cancelamento, $user_id, $reserva_id], "sii");
                if ($affected_reservas === 0) {
                    throw new Exception("Reserva #{$reserva_id} não pôde ser atualizada para 'cancelada'. Nenhuma linha afetada.");
                }
                update_delete_data("UPDATE unidades SET status = 'disponivel' WHERE id = ? AND status IN ('reservada')", [$current_reserva['unidade_id']], "i");
                
                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Rejeitar Reserva', 'Reserva', $reserva_id, "Reserva #{$reserva_id} rejeitada e cancelada por {$user_name}. Motivo: '{$motivo_cancelamento}'. Unidade #{$unidade_info_for_alert['numero']} voltou a ser 'disponivel' (se aplicável).", $user_ip],
                            "isssss");

                create_alert('reserva_cancelada', "Sua reserva #{$reserva_id} para a unidade {$unidade_info_for_alert['numero']} foi rejeitada e cancelada. Motivo: {$motivo_cancelamento}. Contate o admin para detalhes.", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                create_alert('reserva_cancelada', "Você ({$user_name}) rejeitou a reserva #{$reserva_id}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Reserva #{$reserva_id} rejeitada e cancelada com sucesso.";
                break;

            case 'solicitar_documentacao':
                error_log("API Reserva: Action in switch: solicitar_documentacao");
                require_permission(['admin'], true);
                if (!in_array($current_reserva['status'], ['aprovada', 'documentos_rejeitados', 'documentos_pendentes', 'documentos_solicitados'])) {
                    throw new Exception("Reserva #{$reserva_id} não está em um status que permita solicitar documentação. Status atual: " . htmlspecialchars($current_reserva['status']));
                }
                
                $cliente_principal_info = fetch_single("SELECT cliente_id FROM reservas_clientes WHERE reserva_id = ? LIMIT 1", [$reserva_id], "i");
                if (!$cliente_principal_info || !isset($cliente_principal_info['cliente_id'])) {
                    throw new Exception("Cliente principal não encontrado para a reserva #{$reserva_id}. Não é possível gerar link de upload.");
                }
                $cliente_id_para_token = $cliente_principal_info['cliente_id'];

                $token = bin2hex(random_bytes(32));
                $token_expiracao = date('Y-m-d H:i:s', strtotime('+7 days'));

                insert_data(
                    "INSERT INTO documentos_upload_tokens (reserva_id, cliente_id, token, data_criacao, data_expiracao, utilizado) VALUES (?, ?, ?, NOW(), ?, FALSE)",
                    [$reserva_id, $cliente_id_para_token, $token, $token_expiracao],
                    "iiss"
                );
                $upload_link = BASE_URL . 'public/documentos.php?token=' . $token;

                update_delete_data(
                    "UPDATE reservas SET status = 'documentos_solicitados', link_documentos_upload = ?, data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?", 
                    [$upload_link, $user_id, $reserva_id], 
                    "sii"
                );

                $corretor_email_info = fetch_single("SELECT email, nome FROM usuarios WHERE id = ?", [$current_reserva['corretor_id']], "i");
                $cliente_para_email_info = fetch_single("SELECT nome, email FROM clientes WHERE id = ?", [$cliente_id_para_token], "i");


                if ($corretor_email_info && $corretor_email_info['email']) {
                    $assunto = "Documentação Pendente para a Reserva #{$reserva_id} - " . APP_NAME;
                    $corpo_email = "Prezado(a) {$corretor_email_info['nome']},<br><br>"
                               . "A documentação para a reserva da unidade {$unidade_info_for_alert['numero']} (Reserva #{$reserva_id}) está pendente ou precisa ser reenviada. "
                               . "Por favor, acesse o link abaixo para gerenciar/enviar os documentos:<br><br>"
                               . "<a href=\"{$upload_link}\">Clique aqui para enviar os documentos</a><br><br>"
                               . "Este link é válido por 7 dias. Não o compartilhe com terceiros.<br><br>"
                               . "Atenciosamente,<br>" . APP_NAME;
                    send_email($corretor_email_info['email'], $corretor_email_info['nome'], $assunto, $corpo_email);

                    insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Solicitar Documentação', 'Reserva', $reserva_id, "Documentação solicitada por {$user_name} para a reserva #{$reserva_id}. Status atualizado para 'documentos_solicitados'. Link gerado: {$upload_link}. Email enviado para corretor.", $user_ip],
                            "isssss");

                    create_alert('solicitar_documentacao', "Documentos são necessários para a reserva #{$reserva_id}. Acesse o link para upload e informe o cliente. Unidade: {$unidade_info_for_alert['numero']}.", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                    create_alert('solicitar_documentacao', "Você ({$user_name}) solicitou a documentação da reserva #{$reserva_id} para o cliente.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                } elseif ($cliente_para_email_info && $cliente_para_email_info['email']) {
                    $assunto = "Documentação Pendente para sua Reserva #{$reserva_id} - " . APP_NAME;
                    $corpo_email = "Prezado(a) {$cliente_para_email_info['nome']},<br><br>"
                               . "A documentação para a sua reserva da unidade {$unidade_info_for_alert['numero']} (Reserva #{$reserva_id}) está pendente ou precisa ser reenviada. "
                               . "Por favor, acesse o link abaixo para gerenciar/enviar os documentos:<br><br>"
                               . "<a href=\"{$upload_link}\">Clique aqui para enviar os documentos</a><br><br>"
                               . "Este link é válido por 7 dias. Não o compartilhe com terceiros.<br><br>"
                               . "Atenciosamente,<br>" . APP_NAME;
                    send_email($cliente_para_email_info['email'], $cliente_para_email_info['nome'], $assunto, $corpo_email);

                    insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Solicitar Documentação', 'Reserva', $reserva_id, "Documentação solicitada por {$user_name} para a reserva #{$reserva_id}. Status atualizado para 'documentos_solicitados'. Link gerado: {$upload_link}. Email enviado para cliente.", $user_ip],
                            "isssss");

                    create_alert('solicitar_documentacao', "Você ({$user_name}) solicitou a documentação da reserva #{$reserva_id} para o cliente.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);
                } else {
                     error_log("API Reserva: Nao foi possivel enviar email de solicitacao de docs para reserva #{$reserva_id}. Sem email de corretor ou cliente.");
                }

                $response['success'] = true;
                $response['message'] = "Solicitação de documentação para Reserva #{$reserva_id} enviada com sucesso.";
                break;

            case 'cancel_reserva':
                error_log("API Reserva: Action in switch: cancel_reserva");
                // Verificar permissão para cancelar
                $can_cancel = ($user_type === 'admin'); // Admin pode cancelar qualquer reserva
                if (in_array($user_type, ['corretor_autonomo', 'corretor_imobiliaria'])) {
                    if ($current_reserva['corretor_id'] === $user_id) {
                        $can_cancel = true; // Corretor pode cancelar suas próprias reservas
                    }
                }
                if ($user_type === 'admin_imobiliaria') {
                    $corretores_imob_ids = [];
                    $corret_vinculados_imob = fetch_all("SELECT id FROM usuarios WHERE imobiliaria_id = ? AND tipo LIKE 'corretor_%'", [$imobiliaria_id_logado], "i");
                    foreach($corret_vinculados_imob as $corret_vinc) {
                        $corretores_imob_ids[] = $corret_vinc['id'];
                    }
                    if (in_array($current_reserva['corretor_id'], $corretores_imob_ids)) {
                        $can_cancel = true; // Admin da imobiliária pode cancelar reservas de seus corretores
                    }
                }

                if (!$can_cancel) {
                    throw new Exception("Você não tem permissão para cancelar esta reserva.");
                }

                if (!in_array($current_reserva['status'], ['solicitada', 'aprovada', 'documentos_pendentes', 'documentos_enviados', 'documentos_aprovados', 'documentos_rejeitados', 'contrato_enviado', 'aguardando_assinatura_eletronica', 'documentos_solicitados'])) {
                    throw new Exception("Não é possível cancelar uma reserva com status '{$current_reserva['status']}'.");
                }
                $motivo_cancelamento = filter_input(INPUT_POST, 'motivo_cancelamento', FILTER_UNSAFE_RAW) ?? 'Cancelada pelo usuário.';

                $affected_reservas = update_delete_data("UPDATE reservas SET status = 'cancelada', motivo_cancelamento = ?, data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?", [$motivo_cancelamento, $user_id, $reserva_id], "sii");
                if ($affected_reservas === 0) {
                    throw new Exception("Reserva #{$reserva_id} não pôde ser atualizada para 'cancelada'. Nenhuma linha afetada.");
                }
                update_delete_data("UPDATE unidades SET status = 'disponivel' WHERE id = ? AND status = 'reservada'", [$current_reserva['unidade_id']], "i");

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Cancelar Reserva', 'Reserva', $reserva_id, "Reserva #{$reserva_id} cancelada por {$user_name}. Motivo: '{$motivo_cancelamento}'. Unidade #{$unidade_info_for_alert['numero']} voltou a ser 'disponivel' (se aplicável).", $user_ip],
                            "isssss");

                create_alert('reserva_cancelada', "Sua reserva #{$reserva_id} para a unidade {$unidade_info_for_alert['numero']} foi cancelada. Motivo: {$motivo_cancelamento}. Entre em contato com o admin para mais informações.", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                create_alert('reserva_cancelada', "Reserva #{$reserva_id} cancelada por {$user_name}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Reserva #{$reserva_id} cancelada com sucesso. Unidade voltou a ser disponível.";
                break;
            
            case 'dispensar_lead':
                error_log("API Reserva: Action in switch: dispensar_lead");
                require_permission(['admin'], true);
                
                if ($current_reserva['status'] !== 'solicitada' || $current_reserva['corretor_id'] !== null) {
                    throw new Exception("Este lead (Reserva #{$reserva_id}) não está no status 'solicitada' ou já foi atribuído e não pode ser dispensado como lead.");
                }

                $affected_reservas = update_delete_data("UPDATE reservas SET status = 'dispensada', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?", [$user_id, $reserva_id], "ii");
                if ($affected_reservas === 0) {
                    throw new Exception("Falha ao dispensar lead #{$reserva_id}. Nenhuma linha afetada.");
                }

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Dispensar Lead', 'Reserva', $reserva_id, "Lead (Reserva #{$reserva_id}) dispensado por {$user_name}.", $user_ip],
                            "isssss");
                
                create_alert('lead_dispensado', "Lead (Reserva #{$reserva_id}) dispensado por {$user_name}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Lead (Reserva #{$reserva_id}) dispensado com sucesso.";
                break;

            case 'approve_document': 
                error_log("API Reserva: Action in switch: approve_document");
                require_permission(['admin'], true);
                if (!$document_id) {
                    throw new Exception("ID do documento não fornecido para aprovação.");
                }
                $current_doc = fetch_single("SELECT id, reserva_id, nome_documento, status FROM documentos_reserva WHERE id = ?", [$document_id], "i");
                if (!$current_doc || $current_doc['reserva_id'] != $reserva_id) {
                    throw new Exception("Documento não encontrado ou não pertence à reserva #{$reserva_id}.");
                }
                if (!in_array($current_doc['status'], ['pendente', 'rejeitado'])) {
                    throw new Exception("Documento '{$current_doc['nome_documento']}' já foi aprovado ou rejeitado. Status atual: " . htmlspecialchars($current_doc['status']));
                }

                update_delete_data("UPDATE documentos_reserva SET status = 'aprovado', motivo_rejeicao = NULL, data_analise = NOW(), usuario_analise_id = ? WHERE id = ?", [$user_id, $document_id], "ii");
                
                $total_docs_reserva = fetch_single("SELECT COUNT(id) AS total FROM documentos_reserva WHERE reserva_id = ?", [$reserva_id], "i")['total'] ?? 0;
                $approved_docs_reserva = fetch_single("SELECT COUNT(id) AS total FROM documentos_reserva WHERE reserva_id = ? AND status = 'aprovado'", [$reserva_id], "i")['total'] ?? 0;
                
                if ($total_docs_reserva > 0 && $total_docs_reserva == $approved_docs_reserva) {
                    update_delete_data("UPDATE reservas SET status = 'documentos_aprovados', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ? AND status IN ('documentos_pendentes', 'documentos_rejeitados', 'documentos_enviados', 'documentos_solicitados')", [$user_id, $reserva_id], "ii");
                    create_alert('documentos_aprovados', "Os documentos da reserva #{$reserva_id} da unidade {$unidade_info_for_alert['numero']} foram aprovados. Prossiga para o contrato!", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                    create_alert('documentos_aprovados', "Você ({$user_name}) aprovou todos os documentos da reserva #{$reserva_id}. Status da reserva atualizado.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);
                    $response['message'] = "Documento aprovado com sucesso! Todos os documentos da reserva foram aprovados, status da reserva atualizado.";
                } else {
                    $response['message'] = "Documento aprovado com sucesso! Outros documentos ainda aguardam análise para a reserva.";
                }

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Aprovar Documento', 'Documento_Reserva', $document_id, "Documento '{$current_doc['nome_documento']}' da reserva #{$reserva_id} aprovado por {$user_name}.", $user_ip],
                            "isssss");

                $response['success'] = true;
                $response['message'] = "Documento aprovado com sucesso! Outros documentos ainda aguardam análise para a reserva.";
                break;

            case 'reject_document':
                error_log("API Reserva: Action in switch: reject_document");
                require_permission(['admin'], true);
                $rejection_reason = trim($_POST['rejection_reason'] ?? '');

                if (!$document_id) {
                    throw new Exception("ID do documento não fornecido para rejeição.");
                }
                $current_doc = fetch_single("SELECT id, reserva_id, nome_documento, status FROM documentos_reserva WHERE id = ?", [$document_id], "i");
                if (!$current_doc || $current_doc['reserva_id'] != $reserva_id) {
                    throw new Exception("Documento não encontrado ou não pertence à reserva #{$reserva_id}.");
                }
                if (!in_array($current_doc['status'], ['pendente', 'aprovado'])) {
                    throw new Exception("Documento '{$current_doc['nome_documento']}' já foi rejeitado ou não está em um status válido para rejeição. Status atual: " . htmlspecialchars($current_doc['status']));
                }
                if (empty($rejection_reason)) {
                    throw new Exception("Motivo da rejeição é obrigatório.");
                }

                update_delete_data("UPDATE documentos_reserva SET status = 'rejeitado', motivo_rejeicao = ?, data_analise = NOW(), usuario_analise_id = ? WHERE id = ?", [$rejection_reason, $user_id, $document_id], "sii");
                update_delete_data("UPDATE reservas SET status = 'documentos_rejeitados', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ? AND status NOT IN ('cancelada', 'vendida', 'documentos_solicitados')", [$user_id, $reserva_id], "ii");
                
                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Rejeitar Documento', 'Documento_Reserva', $document_id, "Documento '{$current_doc['nome_documento']}' da reserva #{$reserva_id} rejeitado por {$user_name}. Motivo: '{$rejection_reason}'.", $user_ip],
                            "isssss");

                create_alert('documentos_rejeitados', "Um documento da reserva #{$reserva_id} da unidade {$unidade_info_for_alert['numero']} foi rejeitado. Verifique o motivo no sistema: " . $current_doc['nome_documento'], $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                create_alert('documentos_rejeitados', "Você ({$user_name}) rejeitou um documento da reserva #{$reserva_id}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Documento rejeitado com sucesso. Status da reserva atualizado para 'Documentos Rejeitados'.";
                break;
            
            case 'edit_clients':
                error_log("API Reserva: Action in switch: edit_clients");
                $can_edit_clients = ($user_type === 'admin');
                if (in_array($user_type, ['corretor_autonomo', 'corretor_imobiliaria'])) {
                    if ($current_reserva['corretor_id'] === $user_id) {
                        $can_edit_clients = true;
                    }
                }
                if ($user_type === 'admin_imobiliaria') {
                    $corretores_imob_ids = [];
                    $corret_vinculados_imob = fetch_all("SELECT id FROM usuarios WHERE imobiliaria_id = ? AND tipo LIKE 'corretor_%'", [$imobiliaria_id_logado], "i");
                    foreach($corret_vinculados_imob as $corret_vinc) {
                        $corretores_imob_ids[] = $corret_vinc['id'];
                    }
                    if (in_array($current_reserva['corretor_id'], $corretores_imob_ids)) {
                        $can_edit_clients = true;
                    }
                }
                if (!$can_edit_clients) {
                    throw new Exception("Você não tem permissão para editar os clientes desta reserva.");
                }

                $clients_data = $_POST['clients'] ?? [];
                $current_reserva_clients_ids = [];

                $existing_clients_links = fetch_all("SELECT cliente_id FROM reservas_clientes WHERE reserva_id = ?", [$reserva_id], "i");
                foreach ($existing_clients_links as $client_link) {
                    $current_reserva_clients_ids[] = $client_link['cliente_id'];
                }

                $new_clients_ids_in_post = [];

                foreach ($clients_data as $index => $client) {
                    $client_id = filter_var($client['id'] ?? 'new', FILTER_UNSAFE_RAW);
                    $nome = sanitize_input($client['nome']);
                    $cpf = preg_replace('/[^0-9]/', '', $client['cpf']);
                    $email = filter_var($client['email'], FILTER_SANITIZE_EMAIL);
                    $whatsapp = preg_replace('/[^0-9]/', '', $client['whatsapp']);
                    $cep = preg_replace('/[^0-9]/', '', $client['cep']);
                    $endereco = sanitize_input($client['endereco']);
                    $numero = sanitize_input($client['numero']);
                    $complemento = sanitize_input($client['complemento']);
                    $bairro = sanitize_input($client['bairro']);
                    $cidade = sanitize_input($client['cidade']);
                    $estado = sanitize_input($client['estado']);

                    if (empty($nome) || empty($cpf) || empty($email) || empty($whatsapp)) {
                        throw new Exception("Dados do cliente incompletos ou inválidos para o comprador #". ($index + 1));
                    }
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Formato de e-mail inválido para o comprador #". ($index + 1));
                    }
                    if (strlen($cpf) !== 11) {
                         throw new Exception("CPF inválido (deve ter 11 dígitos) para o comprador #". ($index + 1));
                    }

                    $existing_cpf_client = fetch_single("SELECT id FROM clientes WHERE cpf = ? AND id != ?", [$cpf, ($client_id === 'new' ? 0 : (int)$client_id)], "si");
                    if ($existing_cpf_client) {
                        throw new Exception("CPF já cadastrado para outro cliente (ID: {$existing_cpf_client['id']}): " . format_cpf($cpf) . " para o comprador #". ($index + 1));
                    }
                    $existing_email_client = fetch_single("SELECT id FROM clientes WHERE email = ? AND id != ?", [$email, ($client_id === 'new' ? 0 : (int)$client_id)], "si");
                    if ($existing_email_client) {
                        throw new Exception("Email já cadastrado para outro cliente (ID: {$existing_email_client['id']}): " . htmlspecialchars($email) . " para o comprador #". ($index + 1));
                    }


                    if ($client_id === 'new') {
                        $new_cliente_id = insert_data(
                            "INSERT INTO clientes (nome, cpf, email, whatsapp, cep, endereco, numero, complemento, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$nome, $cpf, $email, $whatsapp, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado],
                            "sssssssssss"
                        );
                        if (!$new_cliente_id) {
                            throw new Exception("Erro ao adicionar novo cliente para o comprador #". ($index + 1));
                        }
                        $new_clients_ids_in_post[] = $new_cliente_id;

                        insert_data("INSERT INTO reservas_clientes (reserva_id, cliente_id) VALUES (?, ?)", [$reserva_id, $new_cliente_id], "ii");
                        
                        insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                                    [$user_id, 'Cliente Adicionado a Reserva', 'Cliente', $new_cliente_id, "Cliente {$nome} adicionado à reserva #{$reserva_id} por {$user_name}.", $user_ip],
                                    "isssss");

                    } else {
                        update_delete_data(
                            "UPDATE clientes SET nome = ?, cpf = ?, email = ?, whatsapp = ?, cep = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ? WHERE id = ?",
                            [$nome, $cpf, $email, $whatsapp, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, (int)$client_id],
                            "sssssssssssi"
                        );
                        $new_clients_ids_in_post[] = (int)$client_id;
                        
                        insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                                    [$user_id, 'Cliente da Reserva Atualizado', 'Cliente', (int)$client_id, "Dados do cliente {$nome} (ID: {$client_id}) atualizados na reserva #{$reserva_id} por {$user_name}.", $user_ip],
                                    "isssss");
                    }
                }

                $clients_to_remove = array_diff($current_reserva_clients_ids, $new_clients_ids_in_post);
                foreach ($clients_to_remove as $cliente_id_to_remove) {
                    update_delete_data("DELETE FROM reservas_clientes WHERE reserva_id = ? AND cliente_id = ?", [$reserva_id, $cliente_id_to_remove], "ii");
                    
                    $remaining_links = fetch_single("SELECT COUNT(*) AS total FROM reservas_clientes WHERE cliente_id = ?", [$cliente_id_to_remove], "i")['total'];
                    if ($remaining_links == 0) {
                        update_delete_data("DELETE FROM clientes WHERE id = ?", [$cliente_id_to_remove], "i");
                        insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                                    [$user_id, 'Cliente Removido da Base (Desvinculado)', 'Cliente', $cliente_id_to_remove, "Cliente (ID: {$cliente_id_to_remove}) removido da base de dados e da reserva #{$reserva_id} por {$user_name}.", $user_ip],
                                    "isssss");
                    } else {
                         insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                                     [$user_id, 'Cliente Desvinculado da Reserva', 'Cliente', $cliente_id_to_remove, "Cliente (ID: {$cliente_id_to_remove}) desvinculado da reserva #{$reserva_id} por {$user_name}.", $user_ip],
                                     "isssss");
                    }
                }

                $response['success'] = true;
                $response['message'] = 'Clientes da reserva atualizados com sucesso!';
                break;
            
            case 'take_lead_broker':
                error_log("API Reserva: Action in switch: take_lead_broker");
                require_permission(['corretor_autonomo', 'corretor_imobiliaria'], true);
                if ($current_reserva['status'] !== 'solicitada' || $current_reserva['corretor_id'] !== $user_id) {
                    throw new Exception("Este lead (Reserva #{$reserva_id}) não está no status 'solicitada' ou não está atribuído a você e não pode ser atendido.");
                }

                $empreendimento_info = fetch_single("SELECT momento_envio_documentacao FROM empreendimentos WHERE id = ?", [$current_reserva['empreendimento_id']], "i");
                $momento_envio_documentacao_empreendimento = $empreendimento_info['momento_envio_documentacao'] ?? '';

                $new_reserva_status = 'aprovada';
                if ($momento_envio_documentacao_empreendimento === 'Na Proposta de Reserva' || $momento_envio_documentacao_empreendimento === 'Após Confirmação de Reserva') {
                    $new_reserva_status = 'documentos_pendentes';
                }

                $affected_rows = update_delete_data(
                    "UPDATE reservas SET status = ?, data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?",
                    [$new_reserva_status, $user_id, $reserva_id],
                    "sii"
                );

                if ($affected_rows === 0) {
                    throw new Exception("Falha ao atender o lead #{$reserva_id}. Nenhuma linha afetada.");
                }

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Atender Lead (Corretor)', 'Reserva', $reserva_id, "Corretor {$user_name} atendeu o lead (Reserva #{$reserva_id}). Novo status: {$new_reserva_status}.", $user_ip],
                            "isssss");

                create_alert('lead_atendido_corretor', "Você ({$user_name}) atendeu o lead (Reserva #{$reserva_id}) para a unidade {$unidade_info_for_alert['numero']}. Status: {$new_reserva_status}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Lead #{$reserva_id} atendido com sucesso. Status da reserva atualizado para '{$new_reserva_status}'.";
                break;

            case 'dispense_lead_broker':
                error_log("API Reserva: Action in switch: dispense_lead_broker");
                require_permission(['corretor_autonomo', 'corretor_imobiliaria'], true);
                if ($current_reserva['status'] !== 'solicitada' || $current_reserva['corretor_id'] !== $user_id) {
                    throw new Exception("Este lead (Reserva #{$reserva_id}) não está no status 'solicitada' ou não está atribuído a você e não pode ser dispensado.");
                }

                $affected_reservas = update_delete_data("UPDATE reservas SET status = 'dispensada', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?", [$user_id, $reserva_id], "ii");
                if ($affected_reservas === 0) {
                    throw new Exception("Falha ao dispensar lead #{$reserva_id}. Nenhuma linha afetada.");
                }

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Dispensar Lead (Corretor)', 'Reserva', $reserva_id, "Corretor {$user_name} dispensou o lead (Reserva #{$reserva_id}).", $user_ip],
                            "isssss");
                
                create_alert('lead_dispensado_corretor', "Você ({$user_name}) dispensou o lead (Reserva #{$reserva_id}) para a unidade {$unidade_info_for_alert['numero']}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);

                $response['success'] = true;
                $response['message'] = "Lead #{$reserva_id} dispensado com sucesso.";
                break;
            
            // NOVO: Case para upload de documento pelo Admin
            case 'upload_document_admin':
                error_log("API Reserva: Action in switch: upload_document_admin");
                require_permission(['admin'], true); // Apenas admin pode fazer upload direto

                $cliente_id_upload = filter_input(INPUT_POST, 'cliente_id_upload', FILTER_VALIDATE_INT);
                $nome_documento_upload = sanitize_input($_POST['nome_documento_upload'] ?? '');

                if (!$reserva_id || !$cliente_id_upload || empty($nome_documento_upload)) {
                    throw new Exception("Dados incompletos para upload de documento pelo admin.");
                }

                if (!isset($_FILES['document_file_admin']) || $_FILES['document_file_admin']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Nenhum arquivo enviado ou erro no upload do arquivo pelo admin.");
                }

                $file_tmp_name = $_FILES['document_file_admin']['tmp_name'];
                $file_name = basename($_FILES['document_file_admin']['name']);
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_file_name_prefix = slugify($nome_documento_upload);
                $new_file_name = uniqid($new_file_name_prefix . '_') . '.' . $file_extension;

                $upload_dir = __DIR__ . '/../uploads/documentos_reservas/reserva_' . $reserva_id . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $target_file = $upload_dir . $new_file_name;
                $db_path = 'uploads/documentos_reservas/reserva_' . $reserva_id . '/' . $new_file_name;

                $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
                $max_size = 10 * 1024 * 1024; // 10MB para upload admin

                if (!in_array($file_extension, $allowed_types)) {
                    throw new Exception("Tipo de arquivo não permitido. Apenas PDF, JPG, JPEG, PNG.");
                }
                if ($_FILES['document_file_admin']['size'] > $max_size) {
                    throw new Exception("Arquivo excede o tamanho máximo de 10MB.");
                }

                if (!move_uploaded_file($file_tmp_name, $target_file)) {
                    throw new Exception("Falha ao mover o arquivo para o diretório de upload.");
                }

                $documento_id_inserido = insert_data(
                    "INSERT INTO documentos_reserva (reserva_id, cliente_id, nome_documento, caminho_arquivo, status, data_upload, usuario_analise_id, data_analise) VALUES (?, ?, ?, ?, 'aprovado', NOW(), ?, NOW())", // Admin já aprova no upload
                    [$reserva_id, $cliente_id_upload, $nome_documento_upload, $db_path, $user_id],
                    "iissi"
                );

                if (!$documento_id_inserido) {
                    throw new Exception("Falha ao registrar o documento no banco de dados.");
                }

                // Verifica se todos os documentos agora estão aprovados para atualizar o status da reserva
                $total_docs_reserva = fetch_single("SELECT COUNT(id) AS total FROM documentos_reserva WHERE reserva_id = ?", [$reserva_id], "i")['total'] ?? 0;
                $approved_docs_reserva = fetch_single("SELECT COUNT(id) AS total FROM documentos_reserva WHERE reserva_id = ? AND status = 'aprovado'", [$reserva_id], "i")['total'] ?? 0;
                
                if ($total_docs_reserva > 0 && $total_docs_reserva == $approved_docs_reserva) {
                    update_delete_data("UPDATE reservas SET status = 'documentos_aprovados', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ? AND status IN ('documentos_pendentes', 'documentos_rejeitados', 'documentos_enviados', 'documentos_solicitados')", [$user_id, $reserva_id], "ii");
                    create_alert('documentos_aprovados', "Admin Master fez upload e aprovou o documento '{$nome_documento_upload}' para a reserva #{$reserva_id}. Todos os docs agora estão aprovados!", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                    create_alert('documentos_aprovados', "Você ({$user_name}) fez upload e aprovou documento para a reserva #{$reserva_id}. Todos os docs aprovados.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);
                } else {
                    create_alert('documentos_enviados', "Admin Master fez upload e aprovou o documento '{$nome_documento_upload}' para a reserva #{$reserva_id}.", $current_reserva['corretor_id'], $reserva_id, 'reserva', $imobiliaria_id_logado);
                    create_alert('documentos_enviados', "Você ({$user_name}) fez upload e aprovou documento para a reserva #{$reserva_id}.", $user_id, $reserva_id, 'reserva', $imobiliaria_id_logado);
                }

                insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                            [$user_id, 'Upload Documento Admin', 'Documento_Reserva', $documento_id_inserido, "Admin {$user_name} fez upload manual do documento '{$nome_documento_upload}' para a reserva #{$reserva_id}.", $user_ip],
                            "isssss");

                $response['success'] = true;
                $response['message'] = "Documento '{$nome_documento_upload}' carregado e aprovado com sucesso pelo Admin!";
                break;

            default:
                error_log("API Reserva: Ação POST não reconhecida: '{$action}'.");
                $response['message'] = 'Ação POST não suportada.';
                break;
        }
    } else {
        $response['message'] = 'Método de requisição não suportado.';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->commit();
    }

} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
        $conn->rollback();
    }
    error_log("Erro na API de Reserva - Ação: {$action} - User: {$user_id} - IP: {$user_ip} - Erro: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'Erro interno ao processar a requisição: ' . $e->getMessage();
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);
exit();

// Funções auxiliares (se não estiverem em helpers.php)
function user_belongs_to_imobiliaria($admin_imobiliaria_id, $corretor_id) {
    global $conn;
    if (is_null($corretor_id)) {
        return false;
    }
    $admin_imob_data = fetch_single("SELECT imobiliaria_id FROM usuarios WHERE id = ? AND tipo = 'admin_imobiliaria'", [$admin_imobiliaria_id], "i");
    if (!$admin_imob_data || !$admin_imob_data['imobiliaria_id']) return false;

    $corretor_data = fetch_single("SELECT imobiliaria_id FROM usuarios WHERE id = ? AND tipo IN ('corretor_autonomo', 'corretor_imobiliaria')", [$corretor_id], "i");
    if (!$corretor_data) {
        return false;
    }
    if ($corretor_data['imobiliaria_id'] === null) {
        return false;
    }

    return $admin_imob_data['imobiliaria_id'] === $corretor_data['imobiliaria_id'];
}