<?php
// imobiliaria/processa_corretor.php - Processa ações de aprovação/rejeição de corretores

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php'; // Para verificar permissão e get_user_info
require_once '../includes/alerts.php'; // Para criar alertas
require_once '../includes/email.php'; // Inclui o arquivo email.php

header('Content-Type: application/json'); // Responde sempre em JSON

// 1. ESTABELECER A CONEXÃO COM O BANCO DE DADOS
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/processa_corretor.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.']);
    exit();
}

// Requer permissão de Admin Imobiliária, e informa que é uma requisição AJAX (true)
require_permission(['admin_imobiliaria'], true);

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Usar FILTER_UNSAFE_RAW ou FILTER_VALIDATE_INT para obter os dados do POST
    $corretor_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT); // O nome do campo no JS é 'id'
    $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW); // Substituído FILTER_SANITIZE_STRING

    $logged_user_info = get_user_info(); // Obter informações do usuário logado
    $imobiliaria_admin_id = $logged_user_info['id'];

    // Buscar o ID e nome da imobiliária vinculada ao admin_imobiliaria logado
    $imobiliaria_data = fetch_single("SELECT id, nome FROM imobiliarias WHERE admin_id = ?", [$imobiliaria_admin_id], "i");
    $imobiliaria_id_logada = $imobiliaria_data['id'] ?? null;
    $imobiliaria_nome = $imobiliaria_data['nome'] ?? 'Sua Imobiliária'; // Fallback

    if (!$corretor_id || !$action || !$imobiliaria_id_logada) {
        $response['message'] = 'Dados inválidos ou sessão expirada. Corretor ID: ' . var_export($corretor_id, true) . ', Ação: ' . var_export($action, true) . ', Imobiliária ID: ' . var_export($imobiliaria_id_logada, true);
        echo json_encode($response);
        exit();
    }

    // Iniciar transação (importante para operações que modificam múltiplos estados)
    $conn->begin_transaction();

    try {
        // 1. Verificar se o corretor pertence a esta imobiliária e obter seu status de aprovação
        $corretor = fetch_single("SELECT nome, email, imobiliaria_id, aprovado, ativo, tipo FROM usuarios WHERE id = ?", [$corretor_id], "i");

        if (!$corretor) {
            throw new Exception('Corretor não encontrado.');
        }

        // Verificação de permissão: o corretor deve pertencer à imobiliária do admin logado
        if ($corretor['imobiliaria_id'] != $imobiliaria_id_logada) {
            throw new Exception('Você não tem permissão para gerenciar este corretor.');
        }

        $email_subject = '';
        $email_body = '';
        $update_sql = "";
        $update_params = [];
        $update_param_types = "";
        $alert_message = "";
        $alert_event_type = "";

        switch ($action) {
            case 'aprovar':
                if ($corretor['aprovado'] == 1 && $corretor['ativo'] == 1) { // Já aprovado e ativo
                    throw new Exception('Corretor já está aprovado e ativo.');
                }
                // Apenas define 'aprovado' para TRUE e 'ativo' para TRUE. O 'tipo' permanece inalterado.
                $update_sql = "UPDATE usuarios SET aprovado = TRUE, ativo = TRUE, data_aprovacao = NOW(), data_atualizacao = NOW() WHERE id = ?";
                $update_params = [$corretor_id];
                $update_param_types = "i";
                $email_subject = "Sua solicitação de cadastro foi APROVADA!";
                $email_body = "Olá " . htmlspecialchars($corretor['nome']) . ",\n\nSua solicitação de cadastro como corretor na plataforma SISReserva foi aprovada pela " . htmlspecialchars($imobiliaria_nome) . ". Agora você pode acessar sua área de corretor e começar a trabalhar.\n\nSeu login é: " . htmlspecialchars($corretor['email']) . "\n\nAtenciosamente,\nEquipe SISReserva";
                $response['message'] = 'Corretor aprovado com sucesso.';
                $alert_message = "O corretor " . htmlspecialchars($corretor['nome']) . " foi aprovado por " . htmlspecialchars($logged_user_info['name']) . ".";
                $alert_event_type = 'corretor_aprovado';
                break;

            case 'rejeitar':
                if ($corretor['aprovado'] == 0 && $corretor['ativo'] == 0) { // Já rejeitado e inativo
                    throw new Exception('Corretor já está rejeitado/inativo.');
                }
                // Rejeitar: Marca como não aprovado e inativo.
                $update_sql = "UPDATE usuarios SET aprovado = FALSE, ativo = FALSE, data_atualizacao = NOW() WHERE id = ?";
                $update_params = [$corretor_id];
                $update_param_types = "i";
                $email_subject = "Sua solicitação de cadastro foi REJEITADA.";
                $email_body = "Olá " . htmlspecialchars($corretor['nome']) . ",\n\nLamentamos informar que sua solicitação de cadastro como corretor na plataforma SISReserva foi rejeitada pela " . htmlspecialchars($imobiliaria_nome) . ". Se você acredita que houve um engano, por favor, entre em contato com a administração.\n\nAtenciosamente,\nEquipe SISReserva";
                $response['message'] = 'Corretor rejeitado com sucesso.';
                $alert_message = "O cadastro do corretor " . htmlspecialchars($corretor['nome']) . " foi rejeitado por " . htmlspecialchars($logged_user_info['name']) . ".";
                $alert_event_type = 'notificacao_geral'; // Um tipo genérico, pois não há 'corretor_rejeitado'
                break;

            case 'ativar':
                if ($corretor['ativo'] == 1) {
                    throw new Exception('Corretor já está ativo.');
                }
                $update_sql = "UPDATE usuarios SET ativo = TRUE, data_atualizacao = NOW() WHERE id = ?";
                $update_params = [$corretor_id];
                $update_param_types = "i";
                $email_subject = "Sua conta foi ATIVADA!";
                $email_body = "Olá " . htmlspecialchars($corretor['nome']) . ",\n\nSua conta de corretor na plataforma SISReserva foi ativada. Você já pode acessar e utilizar o sistema.\n\nAtenciosamente,\nEquipe SISReserva";
                $response['message'] = 'Corretor ativado com sucesso.';
                $alert_message = "O corretor " . htmlspecialchars($corretor['nome']) . " foi ativado por " . htmlspecialchars($logged_user_info['name']) . ".";
                $alert_event_type = 'notificacao_geral';
                break;
            
            case 'inativar':
                if ($corretor['ativo'] == 0) {
                    throw new Exception('Corretor já está inativo.');
                }
                 // Não pode inativar a si mesmo (o Admin Imobiliária)
                if ($corretor_id == $imobiliaria_admin_id) {
                    throw new Exception('Você não pode inativar sua própria conta de administrador de imobiliária.');
                }
                $update_sql = "UPDATE usuarios SET ativo = FALSE, data_atualizacao = NOW() WHERE id = ?";
                $update_params = [$corretor_id];
                $update_param_types = "i";
                $email_subject = "Sua conta foi INATIVADA!";
                $email_body = "Olá " . htmlspecialchars($corretor['nome']) . ",\n\nSua conta de corretor na plataforma SISReserva foi inativada. Se você acredita que isso é um engano, por favor, entre em contato com a administração da sua imobiliária.\n\nAtenciosamente,\nEquipe SISReserva";
                $response['message'] = 'Corretor inativado com sucesso.';
                $alert_message = "O corretor " . htmlspecialchars($corretor['nome']) . " foi inativado por " . htmlspecialchars($logged_user_info['name']) . ".";
                $alert_event_type = 'notificacao_geral';
                break;

            default:
                throw new Exception('Ação inválida.');
        }

        // --- INÍCIO LOG DE DEPURAÇÃO ---
        error_log("DEBUG processa_corretor: Action: {$action}, Corretor ID: {$corretor_id}");
        error_log("DEBUG processa_corretor: SQL: {$update_sql}");
        error_log("DEBUG processa_corretor: Params: " . json_encode($update_params));
        error_log("DEBUG processa_corretor: Param Types: {$update_param_types}");
        // --- FIM LOG DE DEPURAÇÃO ---

        // 2. Executar a atualização do status do usuário
        $affected_rows = update_delete_data($update_sql, $update_params, $update_param_types);

        // --- INÍCIO LOG DE DEPURAÇÃO ---
        error_log("DEBUG processa_corretor: Affected Rows: {$affected_rows}");
        // --- FIM LOG DE DEPURAÇÃO ---

        if ($affected_rows > 0) { // Verifica se a query afetou alguma linha
            // 3. Enviar e-mail de notificação
            if (function_exists('send_email') && !empty($email_subject)) {
                if (!send_email($corretor['email'], $corretor['nome'], $email_subject, $email_body)) {
                    $response['message'] .= ' No entanto, houve um erro ao enviar o e-mail de notificação.';
                    error_log("Erro ao enviar e-mail para corretor " . $corretor['email'] . " após " . $action . ".");
                }
            }
            // 4. Criar alerta para o corretor e o admin da imobiliária (e Admin Master se for relevante)
            if (!empty($alert_message)) {
                create_alert($alert_event_type, $alert_message, $corretor_id, $corretor_id, 'usuario', $imobiliaria_id_logada); // Alerta para o corretor
                create_alert($alert_event_type, $alert_message, $imobiliaria_admin_id, $corretor_id, 'usuario', $imobiliaria_id_logada); // Alerta para o admin da imobiliária
            }
            $conn->commit(); // Confirma a transação

            // --- INÍCIO LOG DE DEPURAÇÃO (PÓS-COMMIT) ---
            $post_commit_corretor_state = fetch_single("SELECT id, aprovado, ativo, data_aprovacao FROM usuarios WHERE id = ?", [$corretor_id], "i");
            error_log("DEBUG processa_corretor: Transaction committed. Post-commit state for Corretor ID {$corretor_id}: " . json_encode($post_commit_corretor_state));
            // --- FIM LOG DE DEPURAÇÃO ---

            $response['success'] = true;
            // A mensagem já é definida no switch, aqui confirmamos o sucesso.
        } else {
            // Se nenhuma linha foi afetada, algo está errado (corretor já no estado, ou erro lógico)
            throw new Exception('Nenhuma alteração foi aplicada. O status do corretor pode já estar atualizado ou não há pendências.');
        }

    } catch (Exception $e) {
        $conn->rollback(); // Reverte a transação em caso de erro
        error_log("ERRO CAPTURADO em imobiliaria/processa_corretor.php: " . $e->getMessage()); // Mensagem de erro mais clara
        $response = ['success' => false, 'message' => 'Ocorreu um erro inesperado: ' . $e->getMessage()];
    } finally {
        if ($conn) {
            $conn->close(); // Fecha a conexão
        }
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);