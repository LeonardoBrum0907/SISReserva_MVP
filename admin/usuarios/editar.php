<?php
// api/usuarios/editar.php - Editar e Ver Detalhes de Usuário (Admin Master)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../includes/alerts.php';
require_once '../../includes/email.php';

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em api/usuarios/editar.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Requer permissão de Admin Master
require_permission(['admin']);

$page_title = "Editar Usuário";
$errors = [];
$success_message = '';
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_data = null;
$imobiliarias = [];

if (!$user_id) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'ID do usuário não fornecido.'];
    header("Location: index.php");
    exit();
}

// Obter lista de imobiliárias para o select
try {
    $imobiliarias = fetch_all("SELECT id, nome FROM imobiliarias ORDER BY nome ASC");
} catch (Exception $e) {
    error_log("Erro ao carregar imobiliárias em api/usuarios/editar.php: " . $e->getMessage());
    $errors[] = "Não foi possível carregar a lista de imobiliárias.";
}

// Carregar dados do usuário
try {
    $user_data = fetch_single("SELECT id, nome, email, cpf, creci, telefone, tipo, aprovado, ativo, imobiliaria_id, data_cadastro, data_aprovacao FROM usuarios WHERE id = ?", [$user_id], "i");
    if (!$user_data) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Usuário não encontrado.'];
        header("Location: index.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Erro ao carregar dados do usuário {$user_id}: " . $e->getMessage());
    $errors[] = "Erro ao carregar dados do usuário: " . $e->getMessage();
}

// Processar submissão do formulário (edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ações de formulário (salvar edição)
    $action = $_POST['action'] ?? 'save_edit'; // Se nenhum action específico, assume salvar edição

    if ($action === 'save_edit') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $creci = trim($_POST['creci'] ?? '');
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $imobiliaria_id_new = filter_input(INPUT_POST, 'imobiliaria_id', FILTER_VALIDATE_INT);
        $aprovado_new = isset($_POST['aprovado']) ? 1 : 0;
        $ativo_new = isset($_POST['ativo']) ? 1 : 0;

        // Validações
        if (empty($nome)) $errors[] = "O nome é obrigatório.";
        if (empty($email)) $errors[] = "O e-mail é obrigatório.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
        
        // Verifica CPF/CRECI para corretores
        if (in_array($tipo, ['corretor_autonomo', 'corretor_imobiliaria'])) {
            if (empty($cpf)) $errors[] = "CPF é obrigatório para corretores.";
            if (strlen($cpf) !== 11) $errors[] = "CPF deve ter 11 dígitos.";
            if (empty($creci)) $errors[] = "CRECI é obrigatório para corretores.";
        }

        // Validação condicional da imobiliária
        if ($tipo === 'corretor_imobiliaria' || $tipo === 'admin_imobiliaria') {
            if (!$imobiliaria_id_new) $errors[] = "A imobiliária é obrigatória para este tipo de usuário.";
        } else {
            $imobiliaria_id_new = NULL;
        }

        // Verificar unicidade de email/cpf (se forem alterados)
        if (empty($errors)) {
            $existing_user_by_email = fetch_single("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $user_id], "si");
            if ($existing_user_by_email) {
                $errors[] = "Já existe outro usuário com este e-mail.";
            }
            if (!empty($cpf)) {
                $existing_user_by_cpf = fetch_single("SELECT id FROM usuarios WHERE cpf = ? AND id != ?", [$cpf, $user_id], "si");
                if ($existing_user_by_cpf) {
                    $errors[] = "Já existe outro usuário com este CPF.";
                }
            }
        }
        
        // Se não houver erros de validação, procede com a atualização
        if (empty($errors)) {
            $sql_update = "UPDATE usuarios SET nome = ?, email = ?, cpf = ?, creci = ?, telefone = ?, tipo = ?, imobiliaria_id = ?, aprovado = ?, ativo = ?, data_atualizacao = NOW()";
            $params_update = [$nome, $email, $cpf, $creci, $telefone, $tipo, $imobiliaria_id_new, $aprovado_new, $ativo_new];
            $types_update = "ssssssiii";

            // Se o usuário está sendo aprovado agora e data_aprovacao não existe, preenche
            if ($aprovado_new == 1 && ($user_data['aprovado'] == 0 || $user_data['data_aprovacao'] === NULL)) {
                 $sql_update .= ", data_aprovacao = NOW()";
            }
            $sql_update .= " WHERE id = ?";
            $params_update[] = $user_id;
            $types_update .= "i";

            try {
                $stmt_result = execute_query($sql_update, $params_update, $types_update);
                if ($stmt_result && $stmt_result->affected_rows > 0) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Dados do usuário atualizados com sucesso!'];
                    
                    // Recarregar os dados do usuário para refletir as mudanças na página
                    $user_data = fetch_single("SELECT id, nome, email, cpf, creci, telefone, tipo, aprovado, ativo, imobiliaria_id, data_cadastro, data_aprovacao FROM usuarios WHERE id = ?", [$user_id], "i");

                    // Criar alertas sobre aprovação/ativação/inativação se o status mudou
                    if ($aprovado_new == 1 && $user_data['aprovado'] == 0) { // Aprovado agora
                        create_alert('corretor_aprovado', "Sua conta foi aprovada! Você já pode acessar o sistema.", $user_id, $user_id, 'usuario');
                    }
                    if ($ativo_new == 1 && $user_data['ativo'] == 0) { // Ativado agora
                        create_alert('notificacao_geral', "Sua conta foi ativada! Você já pode usar o sistema.", $user_id, $user_id, 'usuario');
                    }
                    if ($ativo_new == 0 && $user_data['ativo'] == 1) { // Inativado agora
                        create_alert('notificacao_geral', "Sua conta foi inativada. Contate o administrador.", $user_id, $user_id, 'usuario');
                    }
                    
                } else {
                    $errors[] = "Nenhuma alteração foi salva ou o usuário já estava com os dados informados.";
                }
            } catch (Exception $e) {
                error_log("Erro ao salvar edição de usuário {$user_id}: " . $e->getMessage());
                $errors[] = "Erro inesperado ao salvar: " . $e->getMessage();
            }
        }
    }
    // As ações de "aprovar", "rejeitar", "ativar", "inativar", "excluir" serão tratadas via AJAX em `api/processa_usuario.php`.
    // Não há lógica de POST aqui para essas ações.
}

require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?>: <?php echo htmlspecialchars($user_data['nome']); ?></h2>

    <div class="main-form-column">
        <form id="editUserForm" method="POST" action="<?php echo BASE_URL; ?>api/processa_usuario.php">
            <input type="hidden" name="action" value="update_usuario">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_data['id']); ?>">

            <h3>Dados do Usuário</h3>
            
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($user_data['nome']); ?>" required>
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone (WhatsApp):</label>
                    <input type="text" id="telefone" name="telefone" class="form-control mask-whatsapp" value="<?php echo htmlspecialchars($user_data['telefone']); ?>">
                </div>
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" class="form-control mask-cpf" value="<?php echo htmlspecialchars($user_data['cpf'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="creci">CRECI:</label>
                    <input type="text" id="creci" name="creci" class="form-control" value="<?php echo htmlspecialchars($user_data['creci'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="tipo">Tipo de Usuário:</label>
                <select id="tipo" name="tipo" class="form-control" required>
                    <option value="admin" <?php if($user_data['tipo'] === 'admin') echo 'selected'; ?>>Admin Master</option>
                    <option value="admin_imobiliaria" <?php if($user_data['tipo'] === 'admin_imobiliaria') echo 'selected'; ?>>Admin de Imobiliária</option>
                    <option value="corretor_autonomo" <?php if($user_data['tipo'] === 'corretor_autonomo') echo 'selected'; ?>>Corretor Autônomo</option>
                    <option value="corretor_imobiliaria" <?php if($user_data['tipo'] === 'corretor_imobiliaria') echo 'selected'; ?>>Corretor de Imobiliária</option>
                </select>
            </div>

            <div class="form-group" id="imobiliaria_select_group" style="display: none;">
                <label for="imobiliaria_id">Imobiliária:</label>
                <select id="imobiliaria_id" name="imobiliaria_id" class="form-control">
                    <option value="">Selecione a Imobiliária</option>
                    <?php foreach ($imobiliarias as $imob): ?>
                        <option value="<?php echo $imob['id']; ?>" <?php if($user_data['imobiliaria_id'] == $imob['id']) echo 'selected'; ?>><?php echo htmlspecialchars($imob['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions-group">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                <a href="index.php" class="btn btn-secondary">Voltar</a>
                
                <div class="action-buttons-inline">
                    <?php if (!$user_data['aprovado']): ?>
                        <button type="button" class="btn btn-success approve-user" data-id="<?php echo htmlspecialchars($user_data['id']); ?>">Aprovar</button>
                    <?php elseif ($user_data['ativo']): ?>
                        <button type="button" class="btn btn-warning deactivate-user" data-id="<?php echo htmlspecialchars($user_data['id']); ?>">Inativar</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-success activate-user" data-id="<?php echo htmlspecialchars($user_data['id']); ?>">Ativar</button>
                    <?php endif; ?>
                    
                    <?php if ($user_data['id'] != ($_SESSION['user_info']['id'] ?? 0)): ?>
                        <button type="button" class="btn btn-danger delete-user" data-id="<?php echo htmlspecialchars($user_data['id']); ?>">Excluir</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>