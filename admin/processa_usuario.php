<?php
// admin/processa_usuario.php - Endpoint AJAX para processar ações de usuários (Admin Master)

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/alerts.php';
require_once '../includes/email.php';

header('Content-Type: application/json');

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn;
    $conn = get_db_connection();
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão ao banco de dados: " . $conn->connect_error);
    }
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/processa_usuario.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.']);
    exit();
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Requer permissão de Admin Master, e informa que é uma requisição AJAX
require_permission(['admin'], true);

$response = ['success' => false, 'message' => 'Requisição inválida.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $logged_user_info = get_user_info();

    if (!$user_id || !$action) {
        $response['message'] = 'Dados inválidos ou faltando.';
        echo json_encode($response);
        exit();
    }

    $conn->begin_transaction();

    try {
        $user_data = fetch_single("SELECT id, nome, email, tipo, aprovado, ativo, imobiliaria_id FROM usuarios WHERE id = ?", [$user_id], "i");

        if (!$user_data) {
            $response['message'] = 'Usuário não encontrado.';
            $conn->rollback();
            echo json_encode($response);
            exit();
        }

        if ($action === 'excluir' || $action === 'inativar') {
            if ($user_data['id'] === $logged_user_info['id'] && $user_data['tipo'] === 'admin') {
                $response['message'] = 'Um Admin Master não pode excluir ou inativar a si mesmo.';
                $conn->rollback();
                echo json_encode($response);
                exit();
            }
        }

        $email_subject = '';
        $email_body = '';
        $update_sql = "";
        $update_params = [];
        $update_param_types = "";
        $alert_message = "";
        $alert_event_type = "";
        $action_executed_on_db = false;

        switch ($action) {
            case 'aprovar':
                if ($user_data['aprovado'] == 0 || $user_data['ativo'] == 0) { // Se NÃO estiver aprovado OU NÃO estiver ativo
                    $update_sql = "UPDATE usuarios SET aprovado = TRUE, ativo = TRUE, data_aprovacao = NOW(), data_atualizacao = NOW() WHERE id = ?";
                    $update_params = [$user_id];
                    $update_param_types = "i";
                    $email_subject = "Sua conta SISReserva foi APROVADA!";
                    $email_body = "Olá " . htmlspecialchars($user_data['nome']) . ",\n\nSua conta na plataforma SISReserva foi aprovada. Agora você pode acessar sua área.\n\nLogin: " . htmlspecialchars($user_data['email']) . "\n\nAtenciosamente,\nEquipe SISReserva";
                    $response['message'] = 'Usuário aprovado com sucesso.';
                    $alert_message = "O usuário " . htmlspecialchars($user_data['nome']) . " ({$user_data['tipo']}) foi aprovado por " . htmlspecialchars($logged_user_info['name']) . ".";
                    $alert_event_type = 'corretor_aprovado';
                } else {
                    $response['message'] = 'Usuário já está aprovado e ativo. Nenhuma mudança necessária.';
                }
                break;
            case 'rejeitar':
                if ($user_data['aprovado'] == 1 || $user_data['ativo'] == 1) {
                    $update_sql = "UPDATE usuarios SET aprovado = FALSE, ativo = FALSE, data_atualizacao = NOW() WHERE id = ?";
                    $update_params = [$user_id];
                    $update_param_types = "i";
                    $email_subject = "Sua solicitação de conta SISReserva foi REJEITADA.";
                    $email_body = "Olá " . htmlspecialchars($user_data['nome']) . ",\n\nLamentamos informar que sua solicitação de conta na plataforma SISReserva foi rejeitada. Para mais informações, entre em contato com o suporte.\n\nAtenciosamente,\nEquipe SISReserva";
                    $response['message'] = 'Usuário rejeitado com sucesso.';
                    $alert_message = "O usuário " . htmlspecialchars($user_data['nome']) . " ({$user_data['tipo']}) foi rejeitado por " . htmlspecialchars($logged_user_info['name']) . ".";
                    $alert_event_type = 'notificacao_geral';
                } else {
                    $response['message'] = 'Usuário já está rejeitado e inativo. Nenhuma mudança necessária.';
                }
                break;
            case 'ativar':
                if ($user_data['ativo'] == 1) {
                    $response['message'] = 'Usuário já está ativo. Nenhuma mudança necessária.';
                } else {
                    $update_sql = "UPDATE usuarios SET ativo = TRUE, data_atualizacao = NOW() WHERE id = ?";
                    $update_params = [$user_id];
                    $update_param_types = "i";
                    $response['message'] = 'Usuário ativado com sucesso.';
                    $alert_message = "O usuário " . htmlspecialchars($user_data['nome']) . " ({$user_data['tipo']}) foi ativado por " . htmlspecialchars($logged_user_info['name']) . ".";
                    $alert_event_type = 'notificacao_geral';
                }
                break;
            case 'inativar':
                if ($user_data['ativo'] == 0) {
                    $response['message'] = 'Usuário já está inativo.';
                } else {
                    $update_sql = "UPDATE usuarios SET ativo = FALSE, data_atualizacao = NOW() WHERE id = ?";
                    $update_params = [$user_id];
                    $update_param_types = "i";
                    $response['message'] = 'Usuário inativado com sucesso.';
                    $alert_message = "O usuário " . htmlspecialchars($user_data['nome']) . " ({$user_data['tipo']}) foi inativado por " . htmlspecialchars($logged_user_info['name']) . ".";
                    $alert_event_type = 'notificacao_geral';
                }
                break;
            case 'excluir':
                // REMOVIDO: update_delete_data("DELETE FROM alertas WHERE usuario_id = ?", [$user_id], "i");
                // AGORA O `ON DELETE SET NULL` NA FK DE ALERTAS VAI LIDAR COM ISSO.
                $update_sql = "DELETE FROM usuarios WHERE id = ?";
                $update_params = [$user_id];
                $update_param_types = "i";
                $response['message'] = 'Usuário excluído com sucesso.';
                $alert_message = "O usuário " . htmlspecialchars($user_data['nome']) . " ({$user_data['tipo']}) foi excluído por " . htmlspecialchars($logged_user_info['name']) . ".";
                $alert_event_type = 'notificacao_geral';
                break;
            default:
                $response['message'] = 'Ação inválida.';
                break;
        }

        if (!empty($update_sql)) {
            $stmt_result = execute_query($update_sql, $update_params, $update_param_types);
            if ($stmt_result && $stmt_result->affected_rows > 0) {
                $action_executed_on_db = true;
            }
        }

        if ($action_executed_on_db) {
            $response['success'] = true;
            if (function_exists('send_email') && !empty($email_subject)) {
                if (!send_email($user_data['email'], $user_data['nome'], $email_subject, $email_body)) {
                    $response['message'] .= ' Houve um erro ao enviar o e-mail de notificação.';
                    error_log("Erro ao enviar e-mail para usuário " . $user_data['email'] . " após " . $action . ".");
                }
            }
            if (!empty($alert_event_type)) {
                create_alert($alert_event_type, $alert_message, $user_id, $user_id, 'usuario');
                create_alert($alert_event_type, $alert_message, $logged_user_info['id'], $user_id, 'usuario');
            }
            $conn->commit();
        } else {
            $conn->rollback();
            if (empty($response['message'])) {
                $response['message'] = 'Nenhuma alteração aplicada ao usuário (o status pode já estar no estado desejado ou erro interno sem afetar linhas).';
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro em admin/processa_usuario.php: " . $e->getMessage());
        $response['message'] = 'Ocorreu um erro inesperado: ' . $e->getMessage();
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);