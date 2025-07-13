<?php
// SISReserva_MVP/api/unidades/update_payment_info.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission(['admin']); // Apenas admins podem alterar condição de pagamento

    $unit_id = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);
    $new_payment_info = sanitize_input($_POST['new_payment_info'] ?? ''); // Pode ser um texto longo

    // Validação básica
    if (!$unit_id) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos para atualizar a condição de pagamento da unidade.']);
        exit();
    }

    global $conn;
    try {
        $conn = get_db_connection();
        $sql = "UPDATE unidades SET informacoes_pagamento = ?, data_atualizacao = NOW() WHERE id = ?";
        update_delete_data($sql, [$new_payment_info, $unit_id], "si"); // 's' para string

        echo json_encode(['success' => true, 'message' => 'Condição de pagamento da unidade atualizada com sucesso!']);

    } catch (Exception $e) {
        error_log("Erro ao atualizar condição de pagamento da unidade (ID: {$unit_id}): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao atualizar a condição de pagamento.']);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>