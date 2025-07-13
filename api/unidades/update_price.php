<?php
// SISReserva_MVP/api/unidades/update_price.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CORREÇÃO: Adicionar 'true' para indicar que é uma requisição AJAX.
    // Isso fará com que require_permission retorne JSON em caso de falha, em vez de redirecionar para HTML.
    require_permission(['admin'], true); 

    $unit_id = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);
    $new_price = filter_input(INPUT_POST, 'new_price', FILTER_VALIDATE_FLOAT); 

    // Validação básica
    if (!$unit_id || $new_price === false || $new_price < 0) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos para atualizar o preço da unidade.']);
        exit();
    }

    global $conn;
    try {
        $conn = get_db_connection();
        $sql = "UPDATE unidades SET valor = ?, data_atualizacao = NOW() WHERE id = ?";
        update_delete_data($sql, [$new_price, $unit_id], "di"); // 'd' para double/float

        echo json_encode(['success' => true, 'message' => 'Preço da unidade atualizado com sucesso!']);

    } catch (Exception $e) {
        error_log("Erro ao atualizar preço da unidade (ID: {$unit_id}, Preço: {$new_price}): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao atualizar o preço.']);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>