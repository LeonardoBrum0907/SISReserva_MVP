<?php
// api/alert_actions.php - Endpoint API para ações em alertas (marcar como lido)

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/alerts.php'; // Para a função mark_alert_as_read() e count_unread_alerts

header('Content-Type: application/json'); // Responde sempre em JSON

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn; // Declara que você vai usar a variável global $conn
    $conn = get_db_connection(); // Chama a função para obter a conexão
} catch (Exception $e) {
    // Se a conexão falhar, logue o erro e exiba uma mensagem amigável ao usuário.
    error_log("Erro crítico na inicialização do DB em api/alert_actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.']);
    exit();
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Requer que o usuário esteja logado (qualquer tipo logado pode marcar seu próprio alerta como lido)
require_login(null, true); // true para indicar que é uma requisição AJAX

$response = ['success' => false, 'message' => 'Requisição inválida.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW); // CORRIGIDO: de FILTER_SANITIZE_STRING
    $alert_id = filter_input(INPUT_POST, 'alert_id', FILTER_VALIDATE_INT);
    $logged_user_id = get_user_info()['id'] ?? null;

    if (!$logged_user_id) {
        $response['message'] = 'Usuário não identificado.';
        echo json_encode($response);
        exit();
    }

    try {
        switch ($action) {
            case 'mark_read':
                if (!$alert_id) {
                    throw new Exception("ID do alerta inválido.");
                }
                if (mark_alert_as_read($alert_id, $logged_user_id)) {
                    $response = ['success' => true, 'message' => 'Alerta marcado como lido.'];
                } else {
                    $response['message'] = 'Falha ao marcar alerta como lido. Pode já estar lido ou não pertencer a você.';
                }
                break;

            case 'mark_all_read': // NOVA AÇÃO
                // Marcar todos os alertas NÃO LIDOS do usuário como lidos
                $sql_mark_all = "UPDATE alertas SET lido = 1 WHERE usuario_id = ? AND lido = 0";
                $stmt = $conn->prepare($sql_mark_all);
                if ($stmt === false) {
                    throw new Exception("Erro ao preparar query para marcar todos os alertas como lidos: " . $conn->error);
                }
                $stmt->bind_param("i", $logged_user_id);
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao executar query para marcar todos os alertas como lidos: " . $stmt->error);
                }
                $affected_rows = $stmt->affected_rows;
                $stmt->close();

                if ($affected_rows > 0) {
                    $response = ['success' => true, 'message' => "{$affected_rows} alertas marcados como lidos."];
                } else {
                    $response['message'] = "Nenhum alerta não lido encontrado para marcar como lido.";
                }
                break;

            default:
                $response['message'] = 'Ação não suportada.';
                break;
        }
    } catch (Exception $e) {
        error_log("Erro na API de alertas: " . $e->getMessage());
        $response['message'] = 'Ocorreu um erro interno: ' . $e->getMessage();
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
}

echo json_encode($response);