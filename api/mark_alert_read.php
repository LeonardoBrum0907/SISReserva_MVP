<?php
// api/mark_alert_read.php

require_once '../includes/config.php';
require_once '../includes/database.php'; // Define get_db_connection() e as funções de DB
require_once '../includes/auth.php';
require_once '../includes/alerts.php'; // Contém a lógica para marcar alertas como lidos
header('Content-Type: application/json');

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn; // Declara que você vai usar a variável global $conn
    $conn = get_db_connection(); // Chama a função para obter a conexão
} catch (Exception $e) {
    // Em um endpoint de API, retorne um erro JSON em caso de falha na conexão
    error_log("Erro crítico na inicialização do DB em api/mark_alert_read.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serviço temporariamente indisponível.']);
    exit(); // Termina a execução do script
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

$response = ['success' => false, 'message' => ''];

// NOVO: Estabelecer a conexão com o banco de dados para esta API
// Ela será acessada globalmente pelas funções abaixo.
global $conn; // Acessa a variável global $conn
try {
    $conn = get_db_connection(); // Tenta obter/criar a conexão
} catch (Exception $e) {
    error_log("API Falha na conexão DB (mark_alert_read.php): " . $e->getMessage());
    $response['message'] = 'Erro de conexão com o banco de dados.';
    echo json_encode($response);
    exit();
}


// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se o usuário está logado
    if (!is_logged_in()) { 
        $response['message'] = 'Usuário não autenticado.';
        echo json_encode($response);
        exit();
    }

    $logged_user_info = get_user_info(); 
    $user_id = $logged_user_info['id'] ?? 0;

    if ($user_id === 0) {
        $response['message'] = 'ID de usuário inválido.';
        echo json_encode($response);
        exit();
    }

    $alert_id = filter_input(INPUT_POST, 'alert_id', FILTER_VALIDATE_INT);

    if ($alert_id === false || $alert_id <= 0) {
        $response['message'] = 'ID do alerta inválido.';
        echo json_encode($response);
        exit();
    }

    try {
        // Tenta marcar o alerta como lido, garantindo que seja o alerta do usuário logado
        if (mark_alert_as_read($alert_id, $user_id)) { 
            $response['success'] = true;
            $response['message'] = 'Alerta marcado como lido com sucesso.';
        } else {
            $response['message'] = 'Falha ao marcar alerta como lido.';
        }
    } catch (Exception $e) {
        error_log("API Erro (mark_alert_read.php): " . $e->getMessage());
        $response['message'] = 'Erro interno do servidor ao processar a solicitação.';
    }

} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);
exit();