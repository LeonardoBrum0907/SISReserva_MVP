<?php
// auth/cadastro.php - Página de Cadastro de Usuários

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/helpers.php';
require_once '../includes/alerts.php';

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em auth/cadastro.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados para o cadastro. Por favor, tente novamente mais tarde.</p>");
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

$page_title = "Cadastre-se";
$errors = [];
$imobiliarias = [];

try {
    $imobiliarias = fetch_all("SELECT id, nome FROM imobiliarias WHERE ativa = TRUE ORDER BY nome ASC");
} catch (Exception $e) {
    error_log("Erro ao carregar imobiliárias em auth/cadastro.php: " . $e->getMessage());
}

$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$confirmar_email = $_POST['confirmar_email'] ?? '';
$tipo_selecionado = $_POST['tipo'] ?? '';
$imobiliaria_id_selecionada = $_POST['imobiliaria_id'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$creci = $_POST['creci'] ?? '';
$telefone = $_POST['telefone'] ?? '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $confirmar_email = trim($_POST['confirmar_email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo_selecionado = $_POST['tipo'] ?? '';
    $imobiliaria_id_selecionada = filter_input(INPUT_POST, 'imobiliaria_id', FILTER_VALIDATE_INT);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $creci = trim($_POST['creci'] ?? '');
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');

    // Validações básicas PHP (server-side)
    if (empty($nome)) $errors[] = "O nome é obrigatório.";
    if (empty($email)) $errors[] = "O e-mail é obrigatório.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
    if ($email !== $confirmar_email) $errors[] = "Os e-mails não coincidem.";
    if (empty($senha)) $errors[] = "A senha é obrigatória.";
    if (strlen($senha) < 6) $errors[] = "A senha deve ter no mínimo 6 caracteres.";
    if ($senha !== $confirmar_senha) $errors[] = "As senhas não coincidem.";
    if (empty($tipo_selecionado)) $errors[] = "Você deve selecionar o tipo de usuário.";

    // Lógica para exigir CPF/CRECI apenas para corretores
    if (in_array($tipo_selecionado, ['corretor_autonomo', 'corretor_imobiliaria'])) {
        if (empty($cpf)) $errors[] = "CPF é obrigatório para corretores.";
        if (strlen($cpf) !== 11) $errors[] = "CPF deve ter 11 dígitos.";
        if (empty($creci)) $errors[] = "CRECI é obrigatório para corretores.";
    } else {
        $cpf = null;
        $creci = null;
    }

    // Validação condicional da imobiliária
    $user_type_for_db = $tipo_selecionado;
    $imobiliaria_id_for_db = NULL;
    
    if ($tipo_selecionado === 'corretor_imobiliaria') {
        if (!$imobiliaria_id_selecionada) {
            $errors[] = "A imobiliária é obrigatória para corretor de imobiliária.";
        } else {
            $imobiliaria_id_for_db = $imobiliaria_id_selecionada;
        }
    }
    elseif ($tipo_selecionado === 'imobiliaria' || $tipo_selecionado === 'cliente') {
        $errors[] = "Tipo de usuário não permitido para cadastro público.";
    }


    // Verificar unicidade de email/cpf no servidor
    if (empty($errors)) {
        $existing_user_by_email = fetch_single("SELECT id FROM usuarios WHERE email = ?", [$email], "s");
        if ($existing_user_by_email) {
            $errors[] = "Já existe um usuário com este e-mail cadastrado.";
        }
        if (!empty($cpf)) {
            $existing_user_by_cpf = fetch_single("SELECT id FROM usuarios WHERE cpf = ?", [$cpf], "s");
            if ($existing_user_by_cpf) {
                $errors[] = "Já existe um usuário com este CPF cadastrado.";
            }
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
        $aprovado = FALSE; // Todo novo cadastro de corretor começa como NÃO APROVADO
        $ativo = TRUE;

        $data_aprovacao_value = ($aprovado ? date('Y-m-d H:i:s') : NULL);
        $aprovado_type = 'i'; // Tipo para o bind_param para aprovado (0 ou 1)
        $ativo_type = 'i';   // Tipo para o bind_param para ativo (0 ou 1)
        $data_aprovacao_bind_type = 's'; // Tipo para o bind_param para data_aprovacao (string ou NULL)

        try {
            $new_user_id = insert_data(
                "INSERT INTO usuarios (nome, email, senha, tipo, cpf, creci, telefone, imobiliaria_id, aprovado, ativo, data_cadastro, data_aprovacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)",
                [$nome, $email, $hashed_password, $user_type_for_db, $cpf, $creci, $telefone, $imobiliaria_id_for_db, $aprovado, $ativo, $data_aprovacao_value],
                "sssssssiiss"
            );

            if ($new_user_id) {
                $alert_message = "Um novo usuário ({$nome} - " . ucfirst(str_replace('_', ' ', $user_type_for_db)) . ") aguarda aprovação.";
                $alert_event_type = 'novo_corretor_cadastro';
                
                // Alerta para Admin Master (ID 1)
                create_alert($alert_event_type, $alert_message, ADMIN_MASTER_USER_ID, $new_user_id, 'usuario');
                
                // Alerta para Admin da Imobiliária (se aplicável)
                if ($imobiliaria_id_for_db) {
                   $admin_imobiliaria_data = fetch_single("SELECT admin_id FROM imobiliarias WHERE id = ?", [$imobiliaria_id_for_db], "i");
                   if ($admin_imobiliaria_data && $admin_imobiliaria_data['admin_id']) {
                       create_alert($alert_event_type, $alert_message, $admin_imobiliaria_data['admin_id'], $new_user_id, 'usuario');
                   }
                }

                $_SESSION['message'] = ['type' => 'success', 'text' => 'Cadastro realizado com sucesso! Sua conta está aguardando aprovação.'];
                header("Location: " . BASE_URL . "auth/login.php");
                exit();
            } else {
                $errors[] = "Erro ao cadastrar usuário no banco de dados.";
            }
        } catch (Exception $e) {
            error_log("Erro ao cadastrar usuário: " . $e->getMessage());
            $errors[] = "Erro inesperado ao cadastrar: " . $e->getMessage();
        }
    }
}

require_once '../includes/header_public.php';
?>

<div class="auth-wrapper">
    <div class="auth-container">
        <h2><?php echo htmlspecialchars($page_title); ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="message-box message-box-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php
        // A mensagem de sucesso agora é definida na sessão e será exibida na página de login
        // Não precisamos de um bloco 'success' aqui, pois o redirecionamento ocorre.
        // Se houver mensagens de outros locais na sessão, elas ainda serão exibidas.
        if (isset($_SESSION['message'])): ?>
            <div class="message-box message-box-<?php echo $_SESSION['message']['type']; ?>">
                <p><?php echo $_SESSION['message']['text']; ?></p>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <form method="POST" action="" id="cadastro-form">
            <div class="form-group">
                <label>Você é:</label>
                <div class="radio-group">
                    <input type="radio" id="corretor_autonomo_radio" name="tipo" value="corretor_autonomo" <?php echo (($tipo_selecionado ?? '') === 'corretor_autonomo') ? 'checked' : ''; ?> required>
                    <label for="corretor_autonomo_radio">Corretor Autônomo</label>
                    
                    <input type="radio" id="corretor_imobiliaria_radio" name="tipo" value="corretor_imobiliaria" <?php echo (($tipo_selecionado ?? '') === 'corretor_imobiliaria') ? 'checked' : ''; ?> required>
                    <label for="corretor_imobiliaria_radio">Corretor de Imobiliária</label>

                    </div>
            </div>

            <div class="form-group" id="imobiliaria_select_group" style="display: none;">
                <label for="imobiliaria_id">Vincular à Imobiliária:</label>
                <select id="imobiliaria_id" name="imobiliaria_id" class="form-control">
                    <option value="">Selecione uma imobiliária</option>
                    <?php foreach ($imobiliarias as $imob): ?>
                        <option value="<?php echo htmlspecialchars($imob['id']); ?>" <?php echo (($imobiliaria_id_selecionada ?? '') == $imob['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($imob['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h3>Dados de Cadastro</h3>
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($nome ?? ''); ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email ?? ''); ?>" required maxlength="255">
                <div id="email_match_status" class="validation-message"></div>
                <div id="email_unique_status" class="validation-message"></div>
            </div>
            <div class="form-group">
                <label for="confirmar_email">Confirmar E-mail:</label>
                <input type="email" id="confirmar_email" name="confirmar_email" class="form-control" value="<?php echo htmlspecialchars($confirmar_email ?? ''); ?>" required maxlength="255">
                <div id="confirm_email_match_status" class="validation-message"></div>
            </div>

            <div class="form-group password-input-wrapper">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" class="form-control" required minlength="6" maxlength="255">
                <button type="button" class="toggle-password-visibility" aria-label="Mostrar/Esconder Senha"><i class="fas fa-eye"></i></button>
                <div id="password_strength" class="validation-message"></div>
                <ul id="password_rules" class="password-rules">
                    <li>Mínimo 6 caracteres</li>
                    <li>Pelo menos uma letra maiúscula</li>
                    <li>Pelo menos uma letra minúscula</li>
                    <li>Pelo menos um número</li>
                    <li>Pelo menos um caractere especial (!@#$%^&*)</li>
                </ul>
            </div>
            <div class="form-group password-input-wrapper">
                <label for="confirmar_senha">Confirmar Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" value="<?php echo htmlspecialchars($confirmar_senha ?? ''); ?>" required minlength="6" maxlength="255">
                <button type="button" class="toggle-password-visibility" aria-label="Mostrar/Esconder Senha"><i class="fas fa-eye"></i></button>
                <div id="confirm_password_match_status" class="validation-message"></div>
            </div>

            <div class="form-group-inline" id="corretor_info_group">
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" class="form-control mask-cpf" value="<?php echo htmlspecialchars($cpf ?? ''); ?>" required maxlength="14">
                    <div id="cpf_unique_status" class="validation-message"></div>
                </div>
                <div class="form-group" id="creci_group">
                    <label for="creci">CRECI:</label>
                    <input type="text" id="creci" name="creci" class="form-control" value="<?php echo htmlspecialchars($creci ?? ''); ?>" required maxlength="20">
                </div>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone (WhatsApp):</label>
                <input type="text" id="telefone" name="telefone" class="form-control mask-whatsapp" value="<?php echo htmlspecialchars($telefone ?? ''); ?>" required maxlength="20">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </div>
        </form>
        <p class="text-center mt-md"><a href="login.php">Já tem uma conta? Faça Login!</a></p>
    </div>
</div>

<?php require_once '../includes/footer_public.php'; ?>