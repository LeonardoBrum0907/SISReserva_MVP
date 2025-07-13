<?php
// corretor/perfil/index.php - Página de Perfil do Corretor

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação e sanitização
require_once '../../includes/alerts.php'; // Para mensagens

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em corretor/perfil/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Corretor
require_permission(['corretor_autonomo', 'corretor_imobiliaria']);

$page_title = "Meu Perfil";

$logged_user_info = get_user_info();
$corretor_id = $logged_user_info['id'];

$user_data = null;
$errors = [];
$success_message = '';

// Lida com mensagens da sessão (se houverem)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// Carrega os dados atuais do corretor
try {
    $sql_user_data = "
        SELECT 
            u.id, u.nome, u.email, u.cpf, u.creci, u.telefone, u.tipo, u.data_cadastro, u.data_atualizacao, u.data_aprovacao,
            i.nome AS imobiliaria_nome
        FROM 
            usuarios u
        LEFT JOIN
            imobiliarias i ON u.imobiliaria_id = i.id
        WHERE 
            u.id = ?;
    ";
    $user_data = fetch_single($sql_user_data, [$corretor_id], "i");

    if (!$user_data) {
        // Isso não deveria acontecer se o usuário está logado, mas é uma proteção
        header("Location: " . BASE_URL . "auth/logout.php");
        exit();
    }

} catch (Exception $e) {
    error_log("Erro ao carregar dados do perfil do corretor: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar seus dados de perfil.";
}


// Processa a submissão do formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_nome = trim($_POST['nome'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
    $current_password = $_POST['current_password'] ?? ''; // Senha atual para validação
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // Validação de Nome e Telefone
    if (empty($new_nome)) $errors[] = "O nome é obrigatório.";
    if (empty($new_telefone)) $errors[] = "O telefone é obrigatório.";

    // Validação de E-mail
    if (empty($new_email)) $errors[] = "O e-mail é obrigatório.";
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
    // Verificar unicidade do novo e-mail, exceto se for o próprio e-mail atual
    if ($new_email !== $user_data['email']) {
        $existing_user_by_email = fetch_single("SELECT id FROM usuarios WHERE email = ?", [$new_email], "s");
        if ($existing_user_by_email) {
            $errors[] = "Já existe outro usuário com este e-mail.";
        }
    }

    // Validação de Senha (apenas se nova senha for fornecida)
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) $errors[] = "A nova senha deve ter no mínimo 6 caracteres.";
        if ($new_password !== $confirm_new_password) $errors[] = "As novas senhas não coincidem.";
        // Validação da senha atual para permitir a mudança
        if (empty($current_password)) $errors[] = "Para alterar a senha, você deve informar sua senha atual.";
        else {
             $check_current_password = fetch_single("SELECT senha FROM usuarios WHERE id = ?", [$corretor_id], "i");
             if (!$check_current_password || !password_verify($current_password, $check_current_password['senha'])) {
                 $errors[] = "Sua senha atual está incorreta.";
             }
        }
    }

    if (empty($errors)) {
        $sql_update = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, data_atualizacao = NOW()";
        $params_update = [$new_nome, $new_email, $new_telefone];
        $types_update = "sss";

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update .= ", senha = ?";
            $params_update[] = $hashed_password;
            $types_update .= "s";
        }

        $sql_update .= " WHERE id = ?";
        $params_update[] = $corretor_id;
        $types_update .= "i";

        try {
            $affected_rows = update_delete_data($sql_update, $params_update, $types_update);

            if ($affected_rows > 0) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Seu perfil foi atualizado com sucesso!'];
                // Atualizar dados da sessão caso nome ou email tenham mudado
                $_SESSION['user_name'] = $new_nome;
                $_SESSION['user_email'] = $new_email;
                
                // Recarrega os dados do usuário para refletir as mudanças na página
                $user_data = fetch_single($sql_user_data, [$corretor_id], "i");

            } else {
                $_SESSION['message'] = ['type' => 'info', 'text' => "Nenhuma alteração foi salva ou os dados já estavam atualizados."];
            }
            // Redireciona para evitar reenvio do formulário e para limpar o POST
            header("Location: " . BASE_URL . "corretor/perfil/index.php");
            exit();

        } catch (Exception $e) {
            error_log("Erro ao atualizar perfil do corretor: " . $e->getMessage());
            $errors[] = "Erro inesperado ao salvar seu perfil: " . $e->getMessage();
        }
    }
}

require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="message-box message-box-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message-box message-box-<?php echo $_SESSION['message']['type']; ?>">
            <p><?php echo $_SESSION['message']['text']; ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if ($user_data): ?>
    <div class="admin-form-section">
        <form method="POST" action="">
            <h3>Dados Pessoais</h3>
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($user_data['nome'] ?? ''); ?>" required maxlength="100">
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required maxlength="255">
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone (WhatsApp):</label>
                    <input type="text" id="telefone" name="telefone" class="form-control mask-whatsapp" value="<?php echo htmlspecialchars($user_data['telefone'] ?? ''); ?>" maxlength="20">
                </div>
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" class="form-control mask-cpf" value="<?php echo htmlspecialchars(format_cpf($user_data['cpf'] ?? '')); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="creci">CRECI:</label>
                    <input type="text" id="creci" name="creci" class="form-control" value="<?php echo htmlspecialchars($user_data['creci'] ?? ''); ?>" readonly>
                </div>
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label>Tipo de Corretor:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user_data['tipo'] ?? ''))); ?>" readonly>
                </div>
                <?php if (!empty($user_data['imobiliaria_nome'])): ?>
                <div class="form-group">
                    <label>Imobiliária Vinculada:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['imobiliaria_nome']); ?>" readonly>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group-inline">
                <div class="form-group">
                    <label>Data de Cadastro:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(format_datetime_br($user_data['data_cadastro'] ?? '')); ?>" readonly>
                </div>
                <?php if (!empty($user_data['data_aprovacao'])): ?>
                <div class="form-group">
                    <label>Data de Aprovação:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(format_datetime_br($user_data['data_aprovacao'] ?? '')); ?>" readonly>
                </div>
                <?php endif; ?>
            </div>

            <h3 class="mt-2xl">Alterar Senha</h3>
            <div class="form-group">
                <label for="current_password">Senha Atual:</label>
                <input type="password" id="current_password" name="current_password" class="form-control">
            </div>
            <div class="form-group password-input-wrapper">
                <label for="new_password">Nova Senha:</label>
                <input type="password" id="new_password" name="new_password" class="form-control">
                <button type="button" class="toggle-password-visibility" aria-label="Mostrar/Esconder Senha"><i class="fas fa-eye"></i></button>
            </div>
            <div class="form-group password-input-wrapper">
                <label for="confirm_new_password">Confirmar Nova Senha:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control">
                <button type="button" class="toggle-password-visibility" aria-label="Mostrar/Esconder Senha"><i class="fas fa-eye"></i></button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>