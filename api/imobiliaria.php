<?php
// api/imobiliaria.php - Endpoint API para ações de gestão de imobiliárias (Admin Master) - UNIFICADO e ATUALIZADO
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/alerts.php';

require_permission(['admin'], true);

$response = ['success' => false, 'message' => ''];

$logged_user_info = get_user_info();
$current_user_id = $logged_user_info['id'] ?? null;
$current_user_name = $logged_user_info['name'] ?? 'Sistema';
$current_user_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $imobiliaria_id = filter_input(INPUT_POST, 'imobiliaria_id', FILTER_VALIDATE_INT);

    $actions_requiring_id = ['activate_imobiliaria', 'inactivate_imobiliaria', 'delete_imobiliaria', 'update_imobiliaria'];
    if (in_array($action, $actions_requiring_id) && (!$imobiliaria_id || $imobiliaria_id <= 0)) {
        $response['message'] = 'ID da imobiliária inválido ou não fornecido para esta ação.';
        echo json_encode($response);
        exit();
    }

    try {
        global $conn;
        $conn = get_db_connection();
        if (!$conn instanceof mysqli || $conn->connect_errno !== 0) {
            throw new Exception("Erro interno: Conexão com o banco de dados perdida antes da transação.");
        }
        $conn->begin_transaction();

        $target_imobiliaria_name = 'N/A';
        $imobiliaria_ativa_alvo = null;

        if ($imobiliaria_id && $action !== 'create_imobiliaria') {
            $target_imobiliaria = fetch_single(
                "SELECT id, nome, ativa FROM imobiliarias WHERE id = ?", [$imobiliaria_id], "i"
            );
            if (!$target_imobiliaria) {
                throw new Exception("Imobiliária alvo não encontrada.");
            }
            $target_imobiliaria_name = $target_imobiliaria['nome'];
            $imobiliaria_ativa_alvo = $target_imobiliaria['ativa'];
        }

        if ($action === 'create_imobiliaria') {
            $nome = trim($_POST['nome'] ?? '');
            $cnpj_cleaned = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefone_cleaned = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
            $cep_cleaned = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            $numero = trim($_POST['numero'] ?? '');
            $complemento = trim($_POST['complemento'] ?? '');
            $bairro = trim($_POST['bairro'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $estado = trim($_POST['estado'] ?? '');
            $ativa = isset($_POST['ativa']) ? 1 : 0;
            $admin_id_new_raw = $_POST['admin_id'] ?? null;
            $admin_id_new = null;

            if ($admin_id_new_raw !== '' && $admin_id_new_raw !== null) {
                $filtered_id = filter_var($admin_id_new_raw, FILTER_VALIDATE_INT);
                if ($filtered_id !== false && $filtered_id > 0) {
                    $admin_id_new = $filtered_id;
                }
            }

            if (empty($nome)) throw new Exception("O nome da imobiliária é obrigatório.");
            if (empty($cnpj_cleaned) || strlen($cnpj_cleaned) !== 14) throw new Exception("CNPJ inválido (14 dígitos numéricos).");
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Formato de e-mail inválido.");
            if (empty($cep_cleaned)) throw new Exception("CEP é obrigatório.");
            if (empty($endereco)) throw new Exception("Endereço é obrigatório.");
            if (empty($cidade)) throw new Exception("Cidade é obrigatória.");
            if (empty($estado)) throw new Exception("Estado é obrigatório.");

            if (fetch_single("SELECT id FROM imobiliarias WHERE cnpj = ?", [$cnpj_cleaned], "s")) throw new Exception("Já existe uma imobiliária com este CNPJ.");
            if (fetch_single("SELECT id FROM imobiliarias WHERE email = ?", [$email], "s")) throw new Exception("Já existe uma imobiliária com este e-mail.");
            
            $new_imob_id = insert_data(
                "INSERT INTO imobiliarias (nome, cnpj, email, telefone, endereco, numero, complemento, bairro, cidade, estado, cep, ativa, admin_id, data_cadastro, data_atualizacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [$nome, $cnpj_cleaned, $email, $telefone_cleaned, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $cep_cleaned, $ativa, $admin_id_new],
                "ssssssssssisi"
            );

            if (!$new_imob_id) throw new Exception("Erro ao criar imobiliária no banco de dados.");

            if ($admin_id_new) {
                update_delete_data(
                    "UPDATE usuarios SET tipo = 'admin_imobiliaria', imobiliaria_id = ? WHERE id = ?",
                    [$new_imob_id, $admin_id_new],
                    "ii"
                );
                create_alert('notificacao_geral', "Você foi definido como administrador da nova imobiliária: '{$nome}'.", $admin_id_new, $new_imob_id, 'imobiliaria');
            }

            create_alert('nova_imobiliaria', "Nova imobiliária '{$nome}' criada por Admin Master.", ADMIN_MASTER_USER_ID, $new_imob_id, 'imobiliaria');
            $response['success'] = true;
            $response['message'] = "Imobiliária '{$nome}' criada com sucesso!";
            $response['imobiliaria_id'] = $new_imob_id; // retorna ID

        }
        else if ($action === 'update_imobiliaria') {
            $nome = trim($_POST['nome'] ?? '');
            $cnpj_cleaned = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefone_cleaned = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
            $cep_cleaned = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            $numero = trim($_POST['numero'] ?? '');
            $complemento = trim($_POST['complemento'] ?? '');
            $bairro = trim($_POST['bairro'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $estado = trim($_POST['estado'] ?? '');
            $ativa = isset($_POST['ativa']) ? 1 : 0;
            $admin_id_new_raw = $_POST['admin_id'] ?? null;
            $admin_id_new = null;

            if ($admin_id_new_raw !== '' && $admin_id_new_raw !== null) {
                $filtered_id = filter_var($admin_id_new_raw, FILTER_VALIDATE_INT);
                if ($filtered_id !== false && $filtered_id > 0) {
                    $admin_id_new = $filtered_id;
                }
            }
            if (empty($nome)) throw new Exception("O nome é obrigatório.");
            if (empty($cnpj_cleaned) || strlen($cnpj_cleaned) !== 14) throw new Exception("CNPJ inválido (14 dígitos).");
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Formato de e-mail inválido.");
            if (empty($cep_cleaned)) throw new Exception("CEP é obrigatório.");
            if (empty($endereco)) throw new Exception("Endereço é obrigatório.");
            if (empty($cidade)) throw new Exception("Cidade é obrigatória.");
            if (empty($estado)) throw new Exception("Estado é obrigatório.");

            if (fetch_single("SELECT id FROM imobiliarias WHERE cnpj = ? AND id != ?", [$cnpj_cleaned, $imobiliaria_id], "si")) throw new Exception("Já existe outra imobiliária com este CNPJ.");
            if (fetch_single("SELECT id FROM imobiliarias WHERE email = ? AND id != ?", [$email, $imobiliaria_id], "si")) throw new Exception("Já existe outra imobiliária com este e-mail.");

            $old_imobiliaria_data = fetch_single("SELECT admin_id FROM imobiliarias WHERE id = ?", [$imobiliaria_id], "i");
            $old_admin_id = $old_imobiliaria_data['admin_id'] ?? null;

            update_delete_data(
                "UPDATE imobiliarias SET nome = ?, cnpj = ?, email = ?, telefone = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?, cep = ?, ativa = ?, admin_id = ?, data_atualizacao = NOW() WHERE id = ?",
                [$nome, $cnpj_cleaned, $email, $telefone_cleaned, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $cep_cleaned, $ativa, $admin_id_new, $imobiliaria_id],
                "sssssssssssiis"
            );

            if ($old_admin_id != $admin_id_new) {
                if ($old_admin_id) {
                    update_delete_data("UPDATE usuarios SET imobiliaria_id = NULL, tipo = 'admin' WHERE id = ? AND tipo = 'admin_imobiliaria'", [$old_admin_id], "i");
                    create_alert('notificacao_geral', "Você não é mais o administrador da imobiliária '{$target_imobiliaria_name}'.", $old_admin_id, $imobiliaria_id, 'imobiliaria');
                }
                if ($admin_id_new) {
                    update_delete_data("UPDATE usuarios SET imobiliaria_id = ?, tipo = 'admin_imobiliaria' WHERE id = ?", [$imobiliaria_id, $admin_id_new], "ii");
                    create_alert('notificacao_geral', "Você foi definido como administrador da imobiliária: '{$nome}'.", $admin_id_new, $imobiliaria_id, 'imobiliaria');
                }
            }
            create_alert('notificacao_geral', "Imobiliária '{$nome}' atualizada por Admin Master.", ADMIN_MASTER_USER_ID, $imobiliaria_id, 'imobiliaria');
            $response['success'] = true;
            $response['message'] = "Imobiliária '{$nome}' atualizada com sucesso!";
            $response['imobiliaria_id'] = $imobiliaria_id;
        }
        else if ($action === 'activate_imobiliaria') {
            if ($imobiliaria_ativa_alvo == 1) throw new Exception("Imobiliária já está ativa.");
            update_delete_data("UPDATE imobiliarias SET ativa = 1, data_atualizacao = NOW() WHERE id = ?", [$imobiliaria_id], "i");
            create_alert('imobiliaria_ativada', "Imobiliária '{$target_imobiliaria_name}' ativada por {$current_user_name}.", ADMIN_MASTER_USER_ID, $imobiliaria_id, 'imobiliaria');
            $response['success'] = true;
            $response['message'] = 'Imobiliária ativada com sucesso!';
        }
        else if ($action === 'inactivate_imobiliaria') {
            $motivo = $_POST['motivo_inativacao'] ?? 'Motivo não especificado.';
            if ($imobiliaria_ativa_alvo === 0) throw new Exception("Imobiliária já está inativa.");
            update_delete_data("UPDATE imobiliarias SET ativa = 0, data_atualizacao = NOW(), motivo_inativacao = ? WHERE id = ?", [$motivo, $imobiliaria_id], "si");
            create_alert('imobiliaria_inativada', "Imobiliária '{$target_imobiliaria_name}' inativada por {$current_user_name}. Motivo: {$motivo}", ADMIN_MASTER_USER_ID, $imobiliaria_id, 'imobiliaria');
            $response['success'] = true;
            $response['message'] = 'Imobiliária inativada com sucesso!';
        }
        else if ($action === 'delete_imobiliaria') {
            $admins_vinculados = fetch_single("SELECT COUNT(id) AS total FROM usuarios WHERE imobiliaria_id = ? AND tipo = 'admin_imobiliaria'", [$imobiliaria_id], "i")['total'];
            if ($admins_vinculados > 0) {
                throw new Exception("Não é possível excluir imobiliária com administradores vinculados. Desvincule-os primeiro.");
            }
            update_delete_data("UPDATE usuarios SET imobiliaria_id = NULL, tipo = 'corretor_autonomo', data_atualizacao = NOW() WHERE imobiliaria_id = ?", [$imobiliaria_id], "i");
            update_delete_data("DELETE FROM imobiliarias WHERE id = ?", [$imobiliaria_id], "i");
            create_alert('imobiliaria_excluida', "Imobiliária '{$target_imobiliaria_name}' excluída por {$current_user_name}. Vínculos de corretores foram desfeitos.", ADMIN_MASTER_USER_ID, $imobiliaria_id, 'imobiliaria');
            $response['success'] = true;
            $response['message'] = 'Imobiliária e vínculos com corretores removidos com sucesso!';
        }
        else {
            throw new Exception("Ação desconhecida: '{$action}'.");
        }

        $conn->commit();
    } catch (Exception $e) {
        if ($conn && $conn->errno === 0 && $conn->connect_errno === 0) $conn->rollback();
        error_log("Erro na API imobiliaria.php - Ação {$action}: " . $e->getMessage());
        $response['message'] = 'Erro ao processar solicitação: ' . $e->getMessage();
    } finally {
        if ($conn) $conn->close();
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}
echo json_encode($response);
