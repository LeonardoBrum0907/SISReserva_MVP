<?php
// api/check_uniqueness.php - Endpoint para verificar unicidade de campos (email, cpf)

require_once '../includes/config.php';
require_once '../includes/database.php';
// auth.php não é necessário, pois esta API é pública para cadastro

header('Content-Type: application/json');

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em api/check_uniqueness.php: " . $e->getMessage());
    echo json_encode(['is_unique' => false, 'message' => 'Serviço temporariamente indisponível.']);
    exit();
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

$response = ['is_unique' => true, 'message' => ''];

$field = filter_input(INPUT_GET, 'field', FILTER_SANITIZE_STRING);
$value = filter_input(INPUT_GET, 'value', FILTER_SANITIZE_STRING);

if (empty($field) || empty($value)) {
    $response['is_unique'] = false;
    $response['message'] = 'Campo ou valor para verificação de unicidade ausente.';
    echo json_encode($response);
    exit();
}

try {
    $table = 'usuarios'; // A tabela a ser verificada

    switch ($field) {
        case 'email':
            $sql = "SELECT id FROM {$table} WHERE email = ?";
            $existing = fetch_single($sql, [$value], "s");
            if ($existing) {
                $response['is_unique'] = false;
                $response['message'] = 'E-mail já cadastrado.';
            }
            break;
        case 'cpf':
            $sql = "SELECT id FROM {$table} WHERE cpf = ?";
            $existing = fetch_single($sql, [$value], "s");
            if ($existing) {
                $response['is_unique'] = false;
                $response['message'] = 'CPF já cadastrado.';
            }
            break;
        default:
            $response['is_unique'] = false;
            $response['message'] = 'Campo de verificação inválido.';
            break;
    }

} catch (Exception $e) {
    error_log("Erro na verificação de unicidade (API): " . $e->getMessage());
    $response['is_unique'] = false;
    $response['message'] = 'Erro interno do servidor ao verificar unicidade.';
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);