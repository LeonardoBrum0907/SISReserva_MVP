<?php
// api/empreendimentos/toggle_status.php

// 1. INCLUDES PADRÃO DO PROJETO
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

// 2. VERIFICAÇÃO DE PERMISSÃO
require_permission(['admin'], true);

// 3. OBTENÇÃO DA CONEXÃO
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em api/empreendimentos/toggle_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
    exit();
}

// 4. LÓGICA PRINCIPAL
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Método de requisição inválido.');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['empreendimento_id']) || !is_numeric($data['empreendimento_id'])) {
        http_response_code(400);
        throw new Exception('ID do empreendimento inválido.');
    }
    if (!isset($data['new_status']) || !in_array($data['new_status'], ['ativo', 'pausado'])) {
        http_response_code(400);
        throw new Exception('Novo status inválido.');
    }

    $id_empreendimento = (int)$data['empreendimento_id'];
    $new_status = $data['new_status'];

    $stmt = $conn->prepare("UPDATE empreendimentos SET status = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Falha ao preparar a query de atualização: ' . $conn->error);
    }
    
    $stmt->bind_param("si", $new_status, $id_empreendimento);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Status do empreendimento alterado com sucesso!']);
    } else {
        // Pode não ser um erro se o status já for o desejado
        echo json_encode(['success' => true, 'message' => 'Nenhuma alteração necessária ou empreendimento não encontrado.']);
    }
    
    $stmt->close();

} catch (Exception $e) {
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>