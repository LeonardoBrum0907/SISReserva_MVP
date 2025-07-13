<?php
// api/empreendimentos/get_unidades.php
// Retorna as unidades de um empreendimento específico

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php'; // Para require_permission()

header('Content-Type: application/json');

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em get_unidades.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

require_permission(['admin'], true);

$response = ['success' => false, 'data' => [], 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $empreendimento_id = filter_input(INPUT_GET, 'empreendimento_id', FILTER_VALIDATE_INT);
    $unit_id = filter_input(INPUT_GET, 'unit_id', FILTER_VALIDATE_INT); // Opcional: para buscar uma unidade específica

    if (!$empreendimento_id) {
        $response['message'] = 'ID do empreendimento não fornecido.';
        echo json_encode($response);
        exit();
    }

    try {
        // Selecionando 'informacoes_pagamento' e confirmando 'andar', 'area'
        $sql = "SELECT id, tipo_unidade_id, numero, andar, posicao, area, multiplier, valor, informacoes_pagamento FROM unidades WHERE empreendimento_id = ?";
        $params = [$empreendimento_id];
        $types = "i";

        if ($unit_id) {
            $sql .= " AND id = ?";
            $params[] = $unit_id;
            $types .= "i";
            $unidades = fetch_single($sql, $params, $types);
            $response['data'] = $unidades ? [$unidades] : []; // Retorna como array para consistência
        } else {
            $unidades = fetch_all($sql, $params, $types);
            $response['data'] = $unidades;
        }
        
        $response['success'] = true;
        $response['message'] = 'Unidades carregadas com sucesso.';

    } catch (Exception $e) {
        error_log("Erro ao buscar unidades: " . $e->getMessage());
        $response['message'] = 'Erro ao buscar unidades: ' . $e->getMessage();
    } finally {
        $conn->close();
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);