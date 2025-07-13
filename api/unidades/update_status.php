<?php
// api/unidades/update_status.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission(['admin'], true); // Indica que é uma requisição AJAX

    $unit_id = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);
    $new_status = sanitize_input($_POST['new_status'] ?? ''); // O status deve vir como string 'pausada', 'disponivel', etc.

    // DEBUG: Loga os dados recebidos
    error_log("DEBUG update_status.php: unit_id=" . ($unit_id ?? 'NULL') . ", new_status=" . ($new_status ?? 'NULL'));

    // Validação básica do status (deve ser um dos valores ENUM permitidos)
    $allowed_statuses = ['disponivel', 'reservada', 'vendida', 'pausada', 'bloqueada'];
    if (!$unit_id || !in_array($new_status, $allowed_statuses)) {
        error_log("DEBUG update_status.php: Dados inválidos recebidos. unit_id: " . ($unit_id ?? 'NULL') . ", new_status: " . ($new_status ?? 'NULL'));
        echo json_encode(['success' => false, 'message' => 'Dados inválidos para atualizar o status da unidade.']);
        exit();
    }

    global $conn;
    try {
        $conn = get_db_connection();
        $sql = "UPDATE unidades SET status = ?, data_atualizacao = NOW() WHERE id = ?";
        
        // DEBUG: Loga o SQL e os parâmetros antes de executar
        error_log("DEBUG update_status.php: SQL=" . $sql . ", Params=[" . $new_status . ", " . $unit_id . "], Types=si");

        $stmt_result = update_delete_data($sql, [$new_status, $unit_id], "si"); // 's' para string, 'i' para int

        if ($stmt_result === false || $stmt_result === 0) { // update_delete_data retorna num_rows ou false em erro
            error_log("Erro ao atualizar status da unidade: Nenhuma linha afetada ou erro na execução. ID: {$unit_id}, Status: {$new_status}");
            throw new Exception("Nenhuma alteração aplicada ou erro no banco de dados.");
        }

        echo json_encode(['success' => true, 'message' => 'Status da unidade atualizado com sucesso!']);

    } catch (Exception $e) {
        error_log("Erro ao atualizar status da unidade (ID: {$unit_id}, Status: {$new_status}): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao atualizar o status: ' . $e->getMessage()]);
    } finally {
        // A conexão não deve ser fechada aqui se for usada por outros includes (ex: footer_dashboard.php)
        // Se a conexão for fechada por database.php::execute_query, isso já está ok.
        // Se ela é gerenciada globalmente e fechada no footer, não precisa de $conn->close() aqui.
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>