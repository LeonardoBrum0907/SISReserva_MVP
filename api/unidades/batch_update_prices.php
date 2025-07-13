<?php
// SISReserva_MVP/api/unidades/batch_update_prices.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission(['admin']); // Apenas admins podem fazer ajuste em lote

    $empreendimento_id = filter_input(INPUT_POST, 'empreendimento_id', FILTER_VALIDATE_INT);
    $price_multiplier = filter_input(INPUT_POST, 'price_multiplier', FILTER_VALIDATE_FLOAT);

    // Validação
    if (!$empreendimento_id || $price_multiplier === false || $price_multiplier <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos. ID do empreendimento e multiplicador são obrigatórios e o multiplicador deve ser maior que zero.']);
        exit();
    }

    global $conn;
    try {
        $conn = get_db_connection();
        $conn->begin_transaction();

        // Atualiza o valor das unidades disponíveis e pausadas
        // (Você pode ajustar os status a serem afetados conforme sua regra de negócio)
        $sql = "UPDATE unidades 
                SET valor = valor * ?, data_atualizacao = NOW() 
                WHERE empreendimento_id = ? AND status IN ('disponivel', 'pausada', 'reservada');"; // Incluído 'reservada'
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da query: " . $conn->error);
        }
        $stmt->bind_param("di", $price_multiplier, $empreendimento_id);
        
        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Preços das unidades ajustados com sucesso!']);
        } else {
            throw new Exception("Erro ao executar atualização em lote: " . $stmt->error);
        }

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao ajustar preços em lote para o empreendimento ID {$empreendimento_id}: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao ajustar preços em lote: ' . $e->getMessage()]);
    } finally {
        if ($stmt) $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>