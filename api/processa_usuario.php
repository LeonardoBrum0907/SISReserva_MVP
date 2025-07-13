<?php
// api/processa_usuario.php - Processador Centralizado e Corrigido

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
require_once '../includes/alerts.php';

// Resposta padrão para requisições AJAX
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ação inválida ou dados incompletos.'];

// Inicializa a conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em processa_usuario.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
    exit();
}

// Requer permissão de Admin Master para todas as ações
try {
    require_permission(['admin']);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;

// Começa a transação
$conn->begin_transaction();

try {
    switch ($action) {
        case 'aprovar':
            if (!$user_id) throw new Exception("ID do usuário não fornecido.");
            update_delete_data("UPDATE usuarios SET aprovado = TRUE, ativo = TRUE, data_aprovacao = NOW() WHERE id = ?", [$user_id], "i");
            $response = ['success' => true, 'message' => 'Usuário aprovado com sucesso!'];
            break;

        case 'rejeitar':
        case 'excluir':
            if (!$user_id) throw new Exception("ID do usuário não fornecido.");
            // Antes de excluir, é uma boa prática verificar dependências, mas por simplicidade vamos direto.
            $affected_rows = update_delete_data("DELETE FROM usuarios WHERE id = ?", [$user_id], "i");
            if ($affected_rows > 0) {
                $response = ['success' => true, 'message' => 'Usuário excluído com sucesso.'];
            } else {
                throw new Exception('Usuário não encontrado ou já foi excluído.');
            }
            break;

        case 'inativar':
            if (!$user_id) throw new Exception("ID do usuário não fornecido.");
            update_delete_data("UPDATE usuarios SET ativo = FALSE WHERE id = ?", [$user_id], "i");
            $response = ['success' => true, 'message' => 'Usuário inativado com sucesso.'];
            break;

        case 'ativar':
            if (!$user_id) throw new Exception("ID do usuário não fornecido.");
            update_delete_data("UPDATE usuarios SET ativo = TRUE WHERE id = ?", [$user_id], "i");
            $response = ['success' => true, 'message' => 'Usuário ativado com sucesso.'];
            break;

        case 'update_usuario':
            if (!$user_id) throw new Exception("ID do usuário para atualização não fornecido.");
            
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');
            $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
            $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            $creci = trim($_POST['creci'] ?? '');
            $imobiliaria_id = filter_input(INPUT_POST, 'imobiliaria_id', FILTER_VALIDATE_INT) ?: null;

            if (empty($nome) || empty($email) || empty($tipo)) throw new Exception("Nome, E-mail e Tipo são obrigatórios.");
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Formato de e-mail inválido.");

            $existing_user_email = fetch_single("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $user_id], "si");
            if ($existing_user_email) throw new Exception("Erro: Já existe outro usuário com este e-mail.");

            if (!empty($cpf)) {
                $existing_user_cpf = fetch_single("SELECT id FROM usuarios WHERE cpf = ? AND id != ?", [$cpf, $user_id], "si");
                if ($existing_user_cpf) throw new Exception("Erro: Já existe outro usuário com este CPF.");
            }

            $sql = "UPDATE usuarios SET nome = ?, email = ?, tipo = ?, telefone = ?, cpf = ?, creci = ?, imobiliaria_id = ?, data_atualizacao = NOW() WHERE id = ?";
            $params = [$nome, $email, $tipo, $telefone, $cpf, $creci, $imobiliaria_id, $user_id];
            $types = "ssssssii";
            update_delete_data($sql, $params, $types);
            $response = ['success' => true, 'message' => 'Usuário atualizado com sucesso!'];
            break;

        case 'create_usuario': // Ação vinda do formulário de criação
            // Sanitização e Validação
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $confirmar_senha = $_POST['confirmar_senha'] ?? '';
            $tipo = trim($_POST['tipo'] ?? '');
            $cpf_cleaned = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            $creci = trim($_POST['creci'] ?? '');
            $telefone_cleaned = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
            $imobiliaria_id = filter_input(INPUT_POST, 'imobiliaria_id', FILTER_VALIDATE_INT) ?: null;

            // Validações
            if (empty($nome) || empty($email) || empty($senha) || empty($tipo)) {
                throw new Exception("Nome, E-mail, Senha e Tipo são obrigatórios.");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Formato de e-mail inválido.");
            }
            if ($senha !== $confirmar_senha) {
                throw new Exception("As senhas não coincidem.");
            }
            if (strlen($senha) < 6) {
                throw new Exception("A senha deve ter no mínimo 6 caracteres.");
            }

            // Checar se e-mail ou CPF já existem
            if (fetch_single("SELECT id FROM usuarios WHERE email = ?", [$email], "s")) {
                throw new Exception("Este e-mail já está cadastrado.");
            }
            if (!empty($cpf_cleaned) && fetch_single("SELECT id FROM usuarios WHERE cpf = ?", [$cpf_cleaned], "s")) {
                throw new Exception("Este CPF já está cadastrado.");
            }

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            // Usuário criado por Admin Master já vem aprovado e ativo
            $aprovado = 1;
            $ativo = 1;

            $sql = "INSERT INTO usuarios (nome, email, senha, tipo, cpf, creci, telefone, imobiliaria_id, aprovado, ativo, data_aprovacao, data_cadastro, data_atualizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())";
            $new_user_id = insert_data($sql, [$nome, $email, $senha_hash, $tipo, $cpf_cleaned, $creci, $telefone_cleaned, $imobiliaria_id, $aprovado, $ativo], "sssssssiii");

            if ($new_user_id) {
                $_SESSION['message'] = create_alert('success', 'Usuário criado com sucesso!');
                // A resposta JSON irá instruir o JS a redirecionar
                $response = ['success' => true, 'message' => 'Usuário criado com sucesso!', 'redirectUrl' => BASE_URL . 'admin/usuarios/index.php'];
            } else {
                throw new Exception("Erro ao inserir usuário no banco de dados.");
            }
            break;

        default:
            throw new Exception("Ação desconhecida: '{$action}'.");
    }

    $conn->commit();
    echo json_encode($response);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400); // Bad Request ou 500 para erro de servidor
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}