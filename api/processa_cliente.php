<?php

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ação inválida.'];

$conn = null;

try {
    require_permission(['admin'], true);
    $conn = get_db_connection();

    // Ativa modo de exceções para erros do MySQLi
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_client') {
        $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
        if (!$cliente_id) throw new Exception("ID do cliente inválido.");

        $nome = trim($_POST['nome'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $whatsapp = preg_replace('/[^0-9]/', '', $_POST['whatsapp'] ?? '');
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $bairro = trim($_POST['bairro'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');

        if (empty($nome) || empty($cpf) || empty($email)) {
            throw new Exception("Nome, CPF e E-mail são obrigatórios.");
        }
        if (strlen($cpf) !== 11) {
            throw new Exception("CPF deve conter 11 dígitos.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Formato de e-mail inválido.");
        }

        $existing_cpf = fetch_single("SELECT id FROM clientes WHERE cpf = ? AND id != ?", [$cpf, $cliente_id], "si");
        if ($existing_cpf) throw new Exception("Este CPF já está em uso por outro cliente.");

        $existing_email = fetch_single("SELECT id FROM clientes WHERE email = ? AND id != ?", [$email, $cliente_id], "si");
        if ($existing_email) throw new Exception("Este E-mail já está em uso por outro cliente.");

        $sql = "UPDATE clientes SET nome = ?, cpf = ?, email = ?, whatsapp = ?, cep = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?, data_atualizacao = NOW() WHERE id = ?";
        $params = [$nome, $cpf, $email, $whatsapp, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $cliente_id];
        $types = "sssssssssssi";

        update_delete_data($sql, $params, $types);
        $conn->commit();

        $response = ['success' => true, 'message' => 'Dados do cliente atualizados com sucesso!'];
    } else {
        throw new Exception("Ação desconhecida: '$action'.");
    }

} catch (Exception $e) {
    if ($conn && $conn->errno === 0) {
        $conn->rollback();
    }
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
