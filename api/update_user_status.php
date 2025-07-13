<?php
// api/update_user_status.php
// Endpoint para atualizar o status de usuários (incluindo corretores) via AJAX

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php'; // Para verificar permissão e get_user_info
require_once '../includes/helpers.php'; // Para funções auxiliares como format_email_body
require_once '../includes/alerts.php'; // Para create_alert
require_once '../includes/email.php'; // Para send_email

header('Content-Type: application/json');

// 1. ESTABELECER A CONEXÃO COM O BANCO DE DADOS
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em api/update_user_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

// 2. OBTENÇÃO DE INFORMAÇÕES DO USUÁRIO LOGADO E VERIFICAÇÃO DE AUTENTICAÇÃO
$logged_user_info = get_user_info();
$current_user_id = $logged_user_info['id'] ?? null;
$current_user_name = $logged_user_info['nome'] ?? 'Sistema'; // Usar 'nome'
$current_user_type = $logged_user_info['tipo'] ?? 'guest';
$current_user_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

if (!$current_user_id) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado. Por favor, faça login novamente.']);
    exit();
}

$response = ['success' => false, 'message' => 'Requisição inválida ou ação não suportada.'];

// Apenas aceita requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método não permitido.';
    http_response_code(405);
    echo json_encode($response);
    exit();
}

// Lendo dados do $_POST ( FormData do JavaScript )
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT); // Nome da variável ajustado
$action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW); // 'aprovar_usuario', 'ativar_usuario', etc.

if (!$userId || !$action) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para a ação.']);
    exit();
}

// Inicia a transação
$conn->begin_transaction();

try {
    // Obter informações do usuário alvo para validações e mensagens
    $target_user = fetch_single("SELECT id, nome, email, tipo, aprovado, ativo, imobiliaria_id FROM usuarios WHERE id = ?", [$userId], "i");

    if (!$target_user) {
        throw new Exception("Usuário alvo não encontrado.");
    }

    $target_user_name = $target_user['nome'];
    $target_user_email = $target_user['email'];
    $target_user_type = $target_user['tipo'];
    $target_imobiliaria_id = $target_user['imobiliaria_id'];
    $current_aprovado = $target_user['aprovado'];
    $current_ativo = $target_user['ativo'];

    // Lógica de permissão:
    // Admin Master pode fazer qualquer ação em qualquer usuário.
    // Admin Imobiliária pode fazer ações em corretores (autônomos ou da sua imobiliária).
    $has_permission = false;
    if ($current_user_type === 'admin') { // Ajustado para 'admin' ao invés de 'admin_master' para consistência
        $has_permission = true;
    } elseif ($current_user_type === 'admin_imobiliaria') {
        // Admin Imobiliária só pode gerenciar corretores
        if (in_array($target_user_type, ['corretor_autonomo', 'corretor_imobiliaria'])) {
            // Se for corretor da mesma imobiliária, o ID da imobiliária deve coincidir
            if ($target_user_type === 'corretor_imobiliaria' && $target_imobiliaria_id === $logged_user_info['imobiliaria_id']) {
                $has_permission = true;
            }
            // Admin Imobiliária pode gerenciar corretores autônomos (se a regra de negócio permitir)
            if ($target_user_type === 'corretor_autonomo') {
                $has_permission = true;
            }
        }
    }

    if (!$has_permission) {
        throw new Exception('Você não tem permissão para realizar esta ação neste usuário.');
    }

    $update_sql = "";
    $update_params = [];
    $update_types = "";
    $message_success = "";
    $email_subject = "";
    $email_body = "";
    $alert_message_for_user = "";

    switch ($action) {
        case 'aprovar_usuario':
            if ($current_aprovado == 1) {
                throw new Exception("Usuário já está aprovado.");
            }
            $update_sql = "UPDATE usuarios SET aprovado = 1, data_aprovacao = NOW(), data_atualizacao = NOW() WHERE id = ?";
            $update_params = [$userId];
            $update_types = "i";
            $message_success = "Usuário '{$target_user_name}' aprovado com sucesso.";
            $email_subject = "Sua conta no SISReserva foi aprovada!";
            $email_body = "Olá " . htmlspecialchars($target_user_name) . ",\n\nSua conta na plataforma SISReserva foi aprovada e agora você pode fazer login.\n\nSeu login é: " . htmlspecialchars($target_user_email) . ".\n\nAtenciosamente,\nEquipe SISReserva";
            $alert_message_for_user = "Sua conta foi aprovada! Você já pode acessar o sistema.";
            break;

        case 'rejeitar_usuario':
            if ($current_aprovado == 1) {
                throw new Exception("Não é possível rejeitar um usuário já aprovado. Inative-o ou exclua-o.");
            }
            // Marcar como não aprovado e inativo para 'rejeição'
            $update_sql = "UPDATE usuarios SET aprovado = 0, ativo = 0, data_atualizacao = NOW() WHERE id = ?";
            $update_params = [$userId];
            $update_types = "i";
            $message_success = "Usuário '{$target_user_name}' rejeitado com sucesso.";
            $email_subject = "Atualização sobre sua conta no SISReserva";
            $email_body = "Olá " . htmlspecialchars($target_user_name) . ",\n\nInformamos que sua solicitação de cadastro na plataforma SISReserva foi rejeitada. Por favor, entre em contato para mais informações.\n\nAtenciosamente,\nEquipe SISReserva";
            $alert_message_for_user = "Sua solicitação de cadastro foi rejeitada. Contate o administrador para mais informações.";
            break;

        case 'ativar_usuario':
            if ($current_ativo == 1) {
                throw new Exception("Usuário já está ativo.");
            }
            $update_sql = "UPDATE usuarios SET ativo = 1, data_atualizacao = NOW() WHERE id = ?";
            $update_params = [$userId];
            $update_types = "i";
            $message_success = "Usuário '{$target_user_name}' ativado com sucesso.";
            $email_subject = "Sua conta no SISReserva foi ativada!";
            $email_body = "Olá " . htmlspecialchars($target_user_name) . ",\n\nSua conta na plataforma SISReserva foi ativada e você já pode acessar o sistema.\n\nAtenciosamente,\nEquipe SISReserva";
            $alert_message_for_user = "Sua conta foi ativada! Você já pode usar o sistema.";
            break;

        case 'inativar_usuario':
            if ($current_ativo == 0) {
                throw new Exception("Usuário já está inativo.");
            }
            if ($userId == $current_user_id) { // Não pode inativar a si mesmo
                throw new Exception("Você não pode inativar sua própria conta.");
            }
            $update_sql = "UPDATE usuarios SET ativo = 0, data_atualizacao = NOW() WHERE id = ?";
            $update_params = [$userId];
            $update_types = "i";
            $message_success = "Usuário '{$target_user_name}' inativado com sucesso.";
            $email_subject = "Sua conta no SISReserva foi inativada";
            $email_body = "Olá " . htmlspecialchars($target_user_name) . ",\n\nSua conta na plataforma SISReserva foi inativada. Se você acredita que isso é um engano, por favor, entre em contato.\n\nAtenciosamente,\nEquipe SISReserva";
            $alert_message_for_user = "Sua conta foi inativada. Contate o administrador.";
            break;

        case 'excluir_usuario':
            if ($userId == $current_user_id) { // Não pode excluir a si mesmo
                throw new Exception("Você não pode excluir sua própria conta.");
            }
            // TODO: Considerar a regra de negócio para a exclusão de usuários com dependências (reservas).
            // Se as FKs estiverem como ON DELETE CASCADE/SET NULL, o DB lida com isso.
            // Para maior segurança, você pode verificar manualmente:
            // $reservas_vinculadas = fetch_single("SELECT COUNT(id) FROM reservas WHERE corretor_id = ?", [$userId], "i")['COUNT(id)'];
            // if ($reservas_vinculadas > 0) {
            //     throw new Exception("Não é possível excluir usuário com reservas vinculadas. Inative-o ou reatribua as reservas.");
            // }

            $update_sql = "DELETE FROM usuarios WHERE id = ?"; // Exclusão física
            $update_params = [$userId];
            $update_types = "i";
            $message_success = "Usuário '{$target_user_name}' excluído com sucesso.";
            $email_subject = "Sua conta no SISReserva foi excluída";
            $email_body = "Olá " . htmlspecialchars($target_user_name) . ",\n\nInformamos que sua conta na plataforma SISReserva foi excluída permanentemente.\n\nAtenciosamente,\nEquipe SISReserva";
            $alert_message_for_user = "Sua conta foi excluída do sistema.";
            break;

        default:
            throw new Exception("Ação '$action' não reconhecida.");
    }

    // Executa a query de atualização
    $affected_rows = update_delete_data($update_sql, $update_params, $update_types);

    if ($affected_rows > 0 || $action === 'excluir_usuario') { // Se for exclusão, affected_rows pode ser 0 se já não existia
        // Log de auditoria
        insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                    [$current_user_id, ucfirst(str_replace('_', ' ', $action)) . ' Usuário', 'Usuario', $userId, $message_success, $current_user_ip],
                    "isisss");

        // Enviar e-mail de notificação (se assunto e corpo definidos)
        if (function_exists('send_email') && !empty($email_subject)) {
            send_email($target_user_email, $target_user_name, $email_subject, $email_body);
        }

        // Criar alerta para o usuário modificado (se mensagem definida)
        if (!empty($alert_message_for_user)) {
            create_alert('status_usuario_alterado', $alert_message_for_user, $userId, $userId, 'usuario');
        }

        $conn->commit(); // Confirma a transação
        $response = ['success' => true, 'message' => $message_success];

    } else {
        throw new Exception("Nenhuma alteração foi realizada. O usuário pode já estar no status desejado ou não foi encontrado.");
    }

} catch (Exception $e) {
    $conn->rollback(); // Reverte a transação em caso de erro
    error_log("Erro no API de Usuário ({$action} para ID {$userId}): " . $e->getMessage());
    $response = ['success' => false, 'message' => "Erro na operação: " . $e->getMessage()];
} finally {
    // A conexão será fechada automaticamente no final do script
}

echo json_encode($response);
exit();