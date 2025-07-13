<?php
// api/public_reserve.php - Endpoint para processar solicitações de reserva (público e corretor)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Caminhos absolutos para maior robustez
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/alerts.php';
require_once __DIR__ . '/../includes/email.php';

session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'reserva_id' => null, 'upload_link' => null];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método não permitido.';
    echo json_encode($response);
    exit();
}

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    $response['message'] = 'Erro interno do servidor ao conectar ao banco de dados.';
    error_log("Erro de conexão em public_reserve.php: " . $e->getMessage());
    echo json_encode($response);
    exit();
}

$unidade_id = filter_input(INPUT_POST, 'unidade_id', FILTER_VALIDATE_INT);
$empreendimento_id = filter_input(INPUT_POST, 'empreendimento_id', FILTER_VALIDATE_INT);
$valor_reserva = filter_input(INPUT_POST, 'valor_reserva', FILTER_VALIDATE_FLOAT);
$momento_envio_documentacao = filter_input(INPUT_POST, 'momento_envio_documentacao', FILTER_UNSAFE_RAW);
$documentos_obrigatorios_json = $_POST['documentos_obrigatorios_json'] ?? '[]';

// Dados do cliente, sanitizados e formatados.
$nome_cliente = sanitize_input($_POST['nome_cliente'] ?? '');
$cpf_cliente = preg_replace('/[^0-9]/', '', sanitize_input($_POST['cpf_cliente'] ?? ''));
$email_cliente = filter_var($_POST['email_cliente'] ?? '', FILTER_SANITIZE_EMAIL);
$whatsapp_cliente = preg_replace('/[^0-9]/', '', sanitize_input($_POST['whatsapp_cliente'] ?? ''));

// Garante que campos de endereço sejam NULL se não forem enviados pelo formulário (ocorre para leads)
$cep_cliente = isset($_POST['cep_cliente']) ? preg_replace('/[^0-9]/', '', sanitize_input($_POST['cep_cliente'])) : NULL;
$endereco_cliente = isset($_POST['endereco_cliente']) ? sanitize_input($_POST['endereco_cliente']) : NULL;
$numero_cliente = isset($_POST['numero_cliente']) ? sanitize_input($_POST['numero_cliente']) : NULL;
$complemento_cliente = isset($_POST['complemento_cliente']) ? sanitize_input($_POST['complemento_cliente']) : NULL;
$bairro_cliente = isset($_POST['bairro_cliente']) ? sanitize_input($_POST['bairro_cliente']) : NULL;
$cidade_cliente = isset($_POST['cidade_cliente']) ? sanitize_input($_POST['cidade_cliente']) : NULL;
$estado_cliente = isset($_POST['estado_cliente']) ? sanitize_input($_POST['estado_cliente']) : NULL;

$observacoes_reserva = sanitize_input($_POST['observacoes_reserva'] ?? '');


$is_logged_in = isset($_SESSION['user_id']);
$logged_user_id = $_SESSION['user_id'] ?? null;
$logged_user_type = $_SESSION['user_type'] ?? null;
$imobiliaria_id_corretor_logado = $_SESSION['imobiliaria_id'] ?? null;


// Validate core fields (always required for any submission)
if (!$unidade_id || !$empreendimento_id || !$valor_reserva || empty($nome_cliente) || empty($cpf_cliente) || empty($email_cliente) || empty($whatsapp_cliente)) {
    $response['message'] = 'Dados obrigatórios do cliente (Nome, CPF, Email, WhatsApp) ou da unidade ausentes.';
    echo json_encode($response);
    exit();
}
if (!filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Formato de e-mail inválido.';
    echo json_encode($response);
    exit();
}
if (strlen($cpf_cliente) !== 11) {
    $response['message'] = 'CPF inválido. Deve conter 11 dígitos numéricos.';
    echo json_encode($response);
    exit();
}

// Validate address fields ONLY if submitted by a logged-in broker
if ($is_logged_in && ($logged_user_type === 'corretor_autonomo' || $logged_user_type === 'corretor_imobiliaria')) {
    if (is_null($cep_cliente) || strlen($cep_cliente) !== 8 || is_null($endereco_cliente) || is_null($numero_cliente) || is_null($bairro_cliente) || is_null($cidade_cliente) || is_null($estado_cliente)) {
        $response['message'] = 'Para corretores, todos os campos de endereço são obrigatórios e devem ser válidos.';
        echo json_encode($response);
        exit();
    }
}


$conn->begin_transaction();

try {
    $unidade = fetch_single("SELECT id, status, empreendimento_id FROM unidades WHERE id = ?", [$unidade_id], "i");
    
    if (!$unidade) {
        throw new Exception("Unidade não encontrada.");
    }

    if ($unidade['status'] !== 'disponivel') {
        throw new Exception("A unidade #{$unidade_id} não está disponível para reserva. Status atual: " . htmlspecialchars($unidade['status']) . ". Por favor, atualize a página.");
    }

    $cliente_id = null;
    // Verifica se o CPF já existe na tabela de clientes
    $cliente_existente = fetch_single("SELECT id FROM clientes WHERE cpf = ?", [$cpf_cliente], "s");

    if ($cliente_existente) {
        $cliente_id = $cliente_existente['id'];
        // Atualiza os dados do cliente existente
        update_delete_data(
            "UPDATE clientes SET nome = ?, email = ?, whatsapp = ?, cep = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ? WHERE id = ?",
            [
                $nome_cliente, $email_cliente, $whatsapp_cliente, $cep_cliente, $endereco_cliente, $numero_cliente,
                $complemento_cliente, $bairro_cliente, $cidade_cliente, $estado_cliente, $cliente_id
            ],
            "ssssssssssi"
        );
    } else {
        // Cria um novo cliente
        $cliente_id = insert_data(
            "INSERT INTO clientes (nome, cpf, email, whatsapp, cep, endereco, numero, complemento, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $nome_cliente, $cpf_cliente, $email_cliente, $whatsapp_cliente, $cep_cliente, $endereco_cliente,
                $numero_cliente, $complemento_cliente, $bairro_cliente, $cidade_cliente, $estado_cliente
            ],
            "sssssssssss"
        );
        if (!$cliente_id) {
            throw new Exception("Falha ao criar novo cliente.");
        }
    }

    $status_reserva = 'solicitada'; // Padrão: 'solicitada' para leads
    $corretor_da_reserva_id = NULL;
    $usuario_ultima_interacao_id = NULL;

    // Lógica para determinar o status da reserva e o corretor/interação
    if ($is_logged_in && ($logged_user_type === 'corretor_autonomo' || $logged_user_type === 'corretor_imobiliaria')) {
        $corretor_da_reserva_id = $logged_user_id;
        $usuario_ultima_interacao_id = $logged_user_id;

        // CORRIGIDO: Se a documentação é enviada na proposta, o status é 'documentos_enviados'
        if ($momento_envio_documentacao === 'Na Proposta de Reserva') {
            $status_reserva = 'documentos_enviados'; // Documentos foram enviados e estão aguardando análise
        } elseif ($momento_envio_documentacao === 'Após Confirmação de Reserva') {
            $status_reserva = 'documentos_pendentes'; // Documentos são esperados para serem enviados posteriormente
        } else {
            $status_reserva = 'aprovada'; // Reserva aprovada diretamente (sem docs pendentes explícitos nesta fase)
        }
    } else {
        // Visitante (não logado): status sempre 'solicitada'.
        $status_reserva = 'solicitada';
        $usuario_ultima_interacao_id = NULL;
    }

    $prazo_expiracao_reserva_data = fetch_single("SELECT prazo_expiracao_reserva FROM empreendimentos WHERE id = ?", [$empreendimento_id], "i");
    $prazo_expiracao_reserva = $prazo_expiracao_reserva_data['prazo_expiracao_reserva'] ?? 7;
    $data_expiracao = date('Y-m-d H:i:s', strtotime("+$prazo_expiracao_reserva days"));


    $reserva_id = insert_data(
        "INSERT INTO reservas (empreendimento_id, unidade_id, corretor_id, data_reserva, data_expiracao, valor_reserva, status, observacoes, usuario_ultima_interacao) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?)",
        [
            $empreendimento_id, $unidade_id, $corretor_da_reserva_id, $data_expiracao,
            $valor_reserva, $status_reserva, $observacoes_reserva, $usuario_ultima_interacao_id
        ],
        "iiisdssi"
    );

    if (!$reserva_id) {
        throw new Exception("Falha ao criar a reserva.");
    }

    insert_data("INSERT INTO reservas_clientes (reserva_id, cliente_id) VALUES (?, ?)", [$reserva_id, $cliente_id], "ii");

    // Lógica para BAIXA NO ESTOQUE: APENAS se a reserva foi feita por um CORRETOR LOGADO
    if ($is_logged_in && ($logged_user_type === 'corretor_autonomo' || $logged_user_type === 'corretor_imobiliaria')) {
        update_delete_data("UPDATE unidades SET status = 'reservada' WHERE id = ?", [$unidade_id], "i");

        // Processa upload de documentos *se for corretor* e a configuração do empreendimento exigir "Na Proposta"
        if ($momento_envio_documentacao === 'Na Proposta de Reserva') {
            $documentos_obrigatorios_array = json_decode($documentos_obrigatorios_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erro ao decodificar documentos obrigatórios do empreendimento para reserva {$reserva_id}: " . json_last_error_msg());
                $documentos_obrigatorios_array = [];
            }

            $upload_dir = __DIR__ . '/../uploads/documentos_reservas/reserva_' . $reserva_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($documentos_obrigatorios_array as $doc_nome_esperado) {
                $doc_nome_esperado_str = (string)$doc_nome_esperado;
                $file_key_slug = slugify($doc_nome_esperado_str);
                $file_key = 'doc_' . $file_key_slug;
                
                // DEBUG: Loga informações do arquivo antes de tentar mover
                error_log("DEBUG public_reserve.php: Tentando processar arquivo para chave {$file_key}. _FILES: " . var_export($_FILES[$file_key] ?? null, true));

                if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $_FILES[$file_key]['tmp_name'];
                    $file_name = basename($_FILES[$file_key]['name']);
                    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $new_file_name = uniqid($file_key_slug . '_') . '.' . $file_extension;
                    $target_file = $upload_dir . $new_file_name;
                    $db_path = 'uploads/documentos_reservas/reserva_' . $reserva_id . '/' . $new_file_name;

                    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
                    $max_size = 5 * 1024 * 1024;

                    if (!in_array($file_extension, $allowed_types)) {
                        error_log("Tipo de arquivo não permitido para '{$doc_nome_esperado_str}' na reserva {$reserva_id}. Apenas PDF, JPG, JPEG, PNG. Arquivo: {$file_name}");
                        continue; 
                    }
                    if ($_FILES[$file_key]['size'] > $max_size) {
                        error_log("Arquivo '{$doc_nome_esperado_str}' excede o tamanho máximo de 5MB na reserva {$reserva_id}. Arquivo: {$file_name}");
                        continue;
                    }

                    if (move_uploaded_file($file_tmp_name, $target_file)) {
                        error_log("DEBUG public_reserve.php: Arquivo {$file_name} movido com SUCESSO para {$target_file}. Caminho DB: {$db_path}");
                        insert_data(
                            "INSERT INTO documentos_reserva (reserva_id, cliente_id, nome_documento, caminho_arquivo, status) VALUES (?, ?, ?, ?, 'pendente')",
                            [$reserva_id, $cliente_id, $doc_nome_esperado_str, $db_path],
                            "iiss"
                        );
                    } else {
                        $php_upload_error_code = $_FILES[$file_key]['error'];
                        error_log("DEBUG public_reserve.php: Falha ao mover o arquivo {$file_name} para {$target_file} na reserva {$reserva_id}. Erro PHP: {$php_upload_error_code}. Caminho temp: {$file_tmp_name}. Detalhes: " . print_r(error_get_last(), true));
                        insert_data(
                            "INSERT INTO documentos_reserva (reserva_id, cliente_id, nome_documento, caminho_arquivo, status) VALUES (?, ?, ?, ?, 'pendente')",
                            [$reserva_id, $cliente_id, $doc_nome_esperado_str, ''],
                            "iiss"
                        );
                    }
                } else {
                    error_log("DEBUG public_reserve.php: Arquivo {$doc_nome_esperado_str} NÃO enviado ou COM ERRO de upload para reserva {$reserva_id}. Erro: " . ($_FILES[$file_key]['error'] ?? 'N/A') . ". Nenhum arquivo em _FILES['{$file_key}'] ou erro inesperado.");
                    insert_data(
                        "INSERT INTO documentos_reserva (reserva_id, cliente_id, nome_documento, caminho_arquivo, status) VALUES (?, ?, ?, ?, 'pendente')",
                        [$reserva_id, $cliente_id, $doc_nome_esperado_str, ''],
                        "iiss"
                    );
                }
            }
        }
    } else {
        $token = bin2hex(random_bytes(32));
        $token_expiracao = date('Y-m-d H:i:s', strtotime('+7 days'));

        insert_data(
            "INSERT INTO documentos_upload_tokens (reserva_id, cliente_id, token, data_criacao, data_expiracao, utilizado) VALUES (?, ?, ?, NOW(), ?, FALSE)",
            [$reserva_id, $cliente_id, $token, $token_expiracao],
            "iiss"
        );
        $response['upload_link'] = BASE_URL . 'public/documentos.php?token=' . $token;

        $empreendimento_info_for_alert_temp = fetch_single("SELECT nome FROM empreendimentos WHERE id = ?", [$empreendimento_id], "i");
        $unidade_info_for_alert_temp = fetch_single("SELECT numero FROM unidades WHERE id = ?", [$unidade_id], "i");


        $assunto = "Documentos para sua Solicitação de Reserva #{$reserva_id} - " . APP_NAME;
        $corpo = "Prezado(a) {$nome_cliente},<br><br>"
               . "Sua solicitação de reserva para a unidade {$unidade_info_for_alert_temp['numero']} (Empreendimento: {$empreendimento_info_for_alert_temp['nome']}) foi registrada com sucesso! Para prosseguir, por favor, envie os documentos necessários através do link abaixo:<br><br>"
               . "<a href=\"{$response['upload_link']}\">Clique aqui para enviar seus documentos</a><br><br>"
               . "Este link é válido por 7 dias. Não compartilhe este link com terceiros.<br><br>"
               . "Atenciosamente,<br>" . APP_NAME;
        
        send_email($email_cliente, $nome_cliente, $assunto, $corpo);
    }
    

    $empreendimento_info_for_alert = fetch_single("SELECT nome FROM empreendimentos WHERE id = ?", [$empreendimento_id], "i");
    $unidade_info_for_alert = fetch_single("SELECT numero FROM unidades WHERE id = ?", [$unidade_id], "i");

    create_alert(
        $is_logged_in ? 'nova_reserva' : 'novo_lead',
        "Uma nova " . ($is_logged_in ? "reserva" : "solicitação de reserva") . " foi feita para a Unidade " . ($unidade_info_for_alert['numero'] ?? 'N/A') . " (Empreendimento: " . ($empreendimento_info_for_alert['nome'] ?? 'N/A') . ", Reserva #{$reserva_id}). Status: " . ucfirst(str_replace('_', ' ', $status_reserva)) . ".",
        ADMIN_MASTER_USER_ID,
        $reserva_id,
        'reserva',
        null
    );

    if ($corretor_da_reserva_id && $corretor_da_reserva_id !== ADMIN_MASTER_USER_ID) {
         create_alert(
            'nova_reserva',
            "Você efetuou uma nova reserva para a Unidade " . ($unidade_info_for_alert['numero'] ?? 'N/A') . " (Empreendimento: " . ($empreendimento_info_for_alert['nome'] ?? 'N/A') . ", Reserva #{$reserva_id}). Status: " . ucfirst(str_replace('_', ' ', $status_reserva)) . ".",
            $corretor_da_reserva_id,
            $reserva_id,
            'reserva',
            $imobiliaria_id_corretor_logado
        );
    }


    $conn->commit();
    $response['success'] = true;
    $response['message'] = $is_logged_in ? 'Reserva efetuada com sucesso!' : 'Sua solicitação de reserva foi enviada com sucesso! Um link para envio de documentos foi enviado para o seu e-mail.';
    $response['reserva_id'] = $reserva_id;

} catch (Exception $e) {
    if ($conn) {
        $conn->rollback();
    }
    error_log("Erro ao processar reserva em public_reserve.php: " . $e->getMessage());
    $response['message'] = 'Erro ao processar sua solicitação: ' . $e->getMessage();
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);
exit();