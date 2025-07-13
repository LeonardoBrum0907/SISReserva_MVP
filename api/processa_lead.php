<?php
// api/processa_lead.php - VERSÃO FINAL E CORRIGIDA
session_start();

// Inclui os arquivos de configuração e helpers
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php'; // Para log_auditoria e outras funções auxiliares
require_once '../includes/alerts.php'; // Para criar alertas

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ação inválida.'];
$conn = null; // Inicializa a conexão como nula
$transaction_started = false;

try {
    // 1. ESTABELECER A CONEXÃO COM O BANCO DE DADOS GLOBALMENTE NO INÍCIO
    // Garante que a variável $conn seja acessível globalmente para as funções helper
    global $conn; 
    $conn = get_db_connection(); // Tenta obter a conexão
    if (!$conn) { // Verificação adicional caso get_db_connection falhe sem lançar exceção
        throw new Exception("Falha ao estabelecer conexão com o banco de dados.");
    }

    // 2. OBTENÇÃO DE INFORMAÇÕES DO USUÁRIO E VERIFICAÇÃO DE AUTENTICAÇÃO
    // Agora que $conn está disponível, get_user_info() pode buscar dados do DB
    $logged_user_info = get_user_info();
    $user_id = $logged_user_info['id'] ?? 0; // Se não houver user_info, $user_id será 0
    $user_name = $logged_user_info['nome'] ?? 'Desconhecido';
    $user_type = $logged_user_info['tipo'] ?? 'guest';

    // Se o user_id for 0, significa que a sessão não é válida.
    if ($user_id === 0) {
        throw new Exception("Sessão de administrador inválida ou expirada. Faça login novamente.");
    }

    // 3. VERIFICAÇÃO DE PERMISSÃO APÓS A CONEXÃO E OBTENÇÃO DO USER_INFO
    // A função require_permission() verificará se o usuário logado tem o tipo 'admin'.
    require_permission(['admin'], true);

    // Verifica o método da requisição
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método de requisição inválido.");
    }

    $action = $_POST['action'] ?? '';
    $lead_id = filter_input(INPUT_POST, 'lead_id', FILTER_VALIDATE_INT);

    if (!$lead_id) {
        throw new Exception("ID do Lead não fornecido ou inválido.");
    }

    // Buscar detalhes completos do lead antes de qualquer alteração
    // Isso é crucial para validação e para os logs/alertas detalhados
    $lead_details = fetch_single("
        SELECT
            r.id, r.status, r.corretor_id,
            c.nome as cliente_nome,
            e.nome as empreendimento_nome,
            u.numero as unidade_numero
        FROM reservas r
        JOIN reservas_clientes rc ON r.id = rc.reserva_id AND rc.principal = 1
        JOIN clientes c ON rc.cliente_id = c.id
        JOIN unidades u ON r.unidade_id = u.id
        JOIN empreendimentos e ON u.empreendimento_id = e.id
        WHERE r.id = ?", [$lead_id], "i"
    );

    // Validação do estado do lead: deve estar 'solicitada' e sem corretor atribuído
    if (!$lead_details || $lead_details['status'] !== 'solicitada' || $lead_details['corretor_id'] !== null) {
        throw new Exception("Este lead não está mais pendente, já foi atribuído ou não existe.");
    }

    // Inicia a transação para garantir atomicidade das operações
    $conn->begin_transaction();
    $transaction_started = true;

    switch ($action) {
        case 'assign_broker':
            $corretor_id = filter_input(INPUT_POST, 'corretor_id', FILTER_VALIDATE_INT);
            if (!$corretor_id) {
                throw new Exception("Corretor inválido selecionado para atribuição.");
            }

            // Atualiza o lead com o corretor e muda o status para 'aprovada'
            update_delete_data("UPDATE reservas SET corretor_id = ?, status = 'aprovada', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?", [$corretor_id, $user_id, $lead_id], "iii");

            // Cria um alerta para o corretor atribuído
            create_alert(
                'novo_lead_atribuido',
                "O lead #{$lead_id} (Cliente: " . htmlspecialchars($lead_details['cliente_nome']) . ", Unidade: " . htmlspecialchars($lead_details['empreendimento_nome'] . ' / Unidade ' . $lead_details['unidade_numero']) . ") foi atribuído a você.",
                $corretor_id, // ID do destinatário (corretor)
                $lead_id,
                'reserva'
            );

            // Registra a ação no log de auditoria
            log_auditoria($user_id, 'reserva', $lead_id, 'atribuicao_corretor', "Lead #{$lead_id} atribuído ao corretor #{$corretor_id} por Admin ({$user_name}).");

            $response = ['success' => true, 'message' => 'Lead atribuído ao corretor com sucesso!'];
            break;

        case 'attend_lead':
            // O próprio administrador assume o atendimento do lead
            update_delete_data("UPDATE reservas SET corretor_id = ?, status = 'aprovada', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?", [$user_id, $user_id, $lead_id], "iii");

            // Cria um alerta para o administrador que assumiu o lead
            create_alert(
                'lead_assumido_admin',
                "Você assumiu o atendimento do lead #{$lead_id} (Cliente: " . htmlspecialchars($lead_details['cliente_nome']) . ", Unidade: " . htmlspecialchars($lead_details['empreendimento_nome'] . ' / Unidade ' . $lead_details['unidade_numero']) . ").",
                $user_id, // ID do destinatário (admin)
                $lead_id,
                'reserva'
            );

            // Registra a ação no log de auditoria
            log_auditoria($user_id, 'reserva', $lead_id, 'atribuicao_admin', "Admin #{$user_id} ({$user_name}) assumiu o lead #{$lead_id}.");

            $response = ['success' => true, 'message' => 'Você assumiu o atendimento do lead!'];
            break;

        case 'dismiss_lead':
            // Muda o status do lead para 'dispensada'
            update_delete_data("UPDATE reservas SET status = 'dispensada', data_ultima_interacao = NOW(), usuario_ultima_interacao = ? WHERE id = ?", [$user_id, $lead_id], "ii");

            // Registra a ação no log de auditoria
            log_auditoria($user_id, 'reserva', $lead_id, 'dispensar_lead', "Lead #{$lead_id} dispensado por Admin ({$user_name}).");

            $response = ['success' => true, 'message' => 'Lead dispensado com sucesso.'];
            break;

        default:
            throw new Exception("Ação desconhecida ou não suportada.");
    }

    // Confirma a transação se todas as operações foram bem-sucedidas
    $conn->commit();

} catch (Exception $e) {
    // Em caso de erro, reverte a transação se ela foi iniciada
    if ($conn && $transaction_started) {
        $conn->rollback();
    }
    // Define o código de status HTTP para 400 (Bad Request) para erros de cliente/lógica
    http_response_code(400); 
    $response['message'] = $e->getMessage(); // Retorna a mensagem de erro da exceção
} finally {
    // Garante que a conexão com o banco de dados seja fechada
    if ($conn) {
        $conn->close();
    }
}

// Retorna a resposta em formato JSON
echo json_encode($response);
?>
