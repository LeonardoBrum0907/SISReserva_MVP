<?php
// api/empreendimentos/excluir.php

// 1. INCLUDES PADRÃO DO PROJETO
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';       // Adicionado para consistência e segurança
require_once '../../includes/helpers.php';   // Adicionado para consistência

header('Content-Type: application/json');

// 2. VERIFICAÇÃO DE PERMISSÃO (ESSENCIAL)
// Apenas 'admin' pode excluir empreendimentos.
// O 'true' indica que é uma requisição AJAX para tratamento de erro adequado.
require_permission(['admin'], true);

// 3. OBTENÇÃO DA CONEXÃO
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em api/empreendimentos/excluir.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

// 4. LÓGICA PRINCIPAL DO SCRIPT
try {
    // Verifica se o método da requisição é POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        throw new Exception('Método de requisição inválido.');
    }

    // Pega os dados enviados via POST (JSON)
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400); // Bad Request
        throw new Exception('JSON inválido no corpo da requisição.');
    }

    // Verifica se o ID do empreendimento foi recebido e é um número
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        http_response_code(400); // Bad Request
        throw new Exception('ID do empreendimento inválido ou não fornecido.');
    }
    $id_empreendimento = (int)$data['id'];

    // Inicia uma transação para garantir a integridade dos dados
    $conn->begin_transaction();

    /*
    * LÓGICA DE EXCLUSÃO EM CASCATA
    * Se você configurou 'ON DELETE CASCADE' nas chaves estrangeiras no banco,
    * a linha abaixo é suficiente. Caso contrário, descomente e adicione a
    * exclusão manual de tabelas relacionadas (unidades, midias, etc.) aqui.
    */
    
    // Exclusão do empreendimento principal
    $stmt = $conn->prepare("DELETE FROM empreendimentos WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Falha ao preparar a query de exclusão: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $id_empreendimento);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Sucesso, confirma a transação
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Empreendimento excluído com sucesso!']);
    } else {
        // Se não afetou linhas, o ID não existia. Reverte para não deixar a transação aberta.
        $conn->rollback();
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Nenhum empreendimento encontrado com o ID fornecido.']);
    }
    
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    // Captura especificamente erros de SQL (como violações de chave estrangeira)
    $conn->rollback();
    http_response_code(500);
    // Verifica se o erro é de FK, para uma mensagem mais clara
    if (str_contains($e->getMessage(), 'foreign key constraint')) {
        $message = 'Não é possível excluir este empreendimento, pois ele possui dados associados (como unidades ou reservas). Remova os dados associados primeiro.';
    } else {
        $message = 'Erro no banco de dados durante a exclusão.';
    }
    error_log("SQL Error em excluir.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $message]);

} catch (Exception $e) {
    // Captura outras exceções gerais
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Define o código de erro HTTP, se ainda não tiver sido definido
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    
    error_log("Generic Error em excluir.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    // Garante que a conexão seja sempre fechada
    if (isset($conn)) {
        $conn->close();
    }
}
?>