<?php
// SISReserva_MVP/api/unidades/excluir.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission(['admin']); // Apenas admins podem excluir unidades

    $unit_id = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);

    if (!$unit_id) {
        echo json_encode(['success' => false, 'message' => 'ID da unidade inválido.']);
        exit();
    }

    global $conn;
    try {
        $conn = get_db_connection();
        $conn->begin_transaction(); // Inicia transação para garantir atomicidade

        // 1. Excluir ou anular registros dependentes na tabela 'reservas'
        // Opção 1a: Excluir reservas que referenciam esta unidade (CASCADE via PHP)
        $sql_delete_reservas = "DELETE FROM reservas WHERE unidade_id = ?";
        update_delete_data($sql_delete_reservas, [$unit_id], "i");

        // Opção 1b: Se você tivesse uma tabela 'vendas' que referencia 'unidades', faria o mesmo aqui.
        // DELETE FROM vendas WHERE unidade_id = ?;

        // 2. Agora, exclua a unidade
        $sql_delete_unit = "DELETE FROM unidades WHERE id = ?";
        update_delete_data($sql_delete_unit, [$unit_id], "i");

        $conn->commit(); // Confirma as operações
        echo json_encode(['success' => true, 'message' => 'Unidade e suas reservas associadas excluídas com sucesso!']);

    } catch (Exception $e) {
        $conn->rollback(); // Reverte todas as operações em caso de erro
        error_log("Erro ao excluir unidade (ID: {$unit_id}): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao excluir a unidade: ' . $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>