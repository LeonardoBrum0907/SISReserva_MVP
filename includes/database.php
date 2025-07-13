<?php
// includes/database.php
require_once INC_ROOT . '/config.php';
global $conn; // Declara a variável global de conexão

function get_db_connection() {
    global $conn;
    
    // Verifica se já existe uma conexão válida
    if (isset($conn) && $conn instanceof mysqli && $conn->connect_errno === 0) {
        return $conn; // Retorna conexão existente
    }
    
    // Tenta estabelecer uma nova conexão
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verifica se houve erro na conexão
    if ($conn->connect_error) {
        error_log("Erro de conexão ao banco de dados: " . $conn->connect_error);
        throw new Exception("Falha na conexão ao banco de dados: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4"); // Define charset para UTF-8
    return $conn; // Retorna nova conexão
}

function execute_query($sql, $params = [], $param_types = "") {
    global $conn;
    
    // Garante que a conexão está ativa
    if (!$conn instanceof mysqli || $conn->connect_error) {
        throw new Exception("Conexão com o banco de dados inválida.");
    }
    
    // Prepara a query
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erro na preparação da query: " . $conn->error . " | Query: " . $sql);
        throw new Exception("Erro ao preparar a consulta: " . $conn->error);
    }
    
    // Bind dos parâmetros
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    // Executa a query
    if (!$stmt->execute()) {
        error_log("Erro na execução da query: " . $stmt->error . " | Query: " . $sql . " | Params: " . json_encode($params));
        throw new Exception("Erro ao executar a consulta: " . $stmt->error);
    }
    
    // Lógica para tratar diferentes tipos de SQL
    $sql_trimmed = strtolower(trim($sql));
    if (strpos($sql_trimmed, 'insert') === 0) {
        $insert_id = $stmt->insert_id; // Retorna o ID inserido
        $stmt->close();
        return $insert_id;
    }
    
    if (strpos($sql_trimmed, 'select') === 0) {
        return $stmt; // Retorna o statement para ser usado por fetch_all e fetch_single
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    return $affected_rows; // Para UPDATE e DELETE
}

function fetch_all($sql, $params = [], $param_types = "") {
    try {
        $stmt = execute_query($sql, $params, $param_types);
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    } catch (Exception $e) {
        error_log("Erro em fetch_all: " . $e->getMessage());
        return [];
    }
}

function fetch_single($sql, $params = [], $param_types = "") {
    try {
        $stmt = execute_query($sql, $params, $param_types);
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    } catch (Exception $e) {
        error_log("Erro em fetch_single: " . $e->getMessage());
        return null;
    }
}

// Funções para inserção e atualização, mantidas para compatibilidade
function insert_data($sql, $params = [], $param_types = "") {
    try {
        return execute_query($sql, $params, $param_types); // Já retorna insert_id
    } catch (Exception $e) {
        error_log("Erro em insert_data: " . $e->getMessage());
        throw $e;
    }
}

function update_delete_data($sql, $params = [], $param_types = "") {
    try {
        return execute_query($sql, $params, $param_types); // Já retorna linhas afetadas
    } catch (Exception $e) {
        error_log("Erro em update_delete_data: " . $e->getMessage());
        throw $e;
    }
}
?>
