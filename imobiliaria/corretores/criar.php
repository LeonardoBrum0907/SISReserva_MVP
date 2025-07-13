<?php
// imobiliaria/corretores/criar.php - Formulário para adicionar novo corretor pela Imobiliária

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php'; // Incluir auth.php para require_permission e get_user_info
require_once '../../includes/helpers.php';
require_once '../../includes/alerts.php';
require_once '../../includes/email.php'; // Para enviar e-mails de notificação

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/corretores/criar.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Adicionar Novo Corretor";
$errors = [];

// Obter informações do usuário logado (Admin Imobiliária)
$logged_user_info = get_user_info();
$imobiliaria_id_logada = $logged_user_info['imobiliaria_id'] ?? null;
$imobiliaria_nome_logada = 'N/A'; // Valor padrão

if ($imobiliaria_id_logada) {
    $imob_data = fetch_single("SELECT nome FROM imobiliarias WHERE id = ?", [$imobiliaria_id_logada], "i");
    if ($imob_data) {
        $imobiliaria_nome_logada = $imob_data['nome'];
    } else {
        // Se a imobiliária_id na sessão não corresponde a uma imobiliária real
        $imobiliaria_id_logada = null; // Invalida o ID
    }
}

// Redireciona se a imobiliária do admin não for encontrada (erro de configuração)
if (!$imobiliaria_id_logada) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Sua conta de administrador de imobiliária não está vinculada a uma imobiliária válida. Por favor, contate o administrador mestre para vincular sua conta a uma imobiliária.'];
    header("Location: ../index.php"); // Redireciona para o dashboard da imobiliária
    exit();
}


// Preencher valores do formulário com base em POST (se houver erro de validação)
$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$confirmar_email = $_POST['confirmar_email'] ?? '';
$senha = $_POST['senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';
$tipo_corretor_selecionado = $_POST['tipo'] ?? 'corretor_imobiliaria'; // O Admin Imobiliária pode escolher entre autônomo e de imobiliária
$cpf = $_POST['cpf'] ?? '';
$creci = $_POST['creci'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$aprovado_checkbox = isset($_POST['aprovado_checkbox']) ? 1 : 0;
$ativo_checkbox = isset($_POST['ativo_checkbox']) ? 1 : 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $confirmar_email = trim($_POST['confirmar_email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo_corretor_post = $_POST['tipo'] ?? ''; // O tipo (radio) que vem do POST
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $creci = trim($_POST['creci'] ?? '');
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
    $aprovado_new = isset($_POST['aprovado_checkbox']) ? 1 : 0;
    $ativo_new = isset($_POST['ativo_checkbox']) ? 1 : 0;

    // Validações server-side
    if (empty($nome)) $errors[] = "O nome é obrigatório.";
    if (empty($email)) $errors[] = "O e-mail é obrigatório.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
    if ($email !== $confirmar_email) $errors[] = "Os e-mails não coincidem.";
    if (empty($senha)) $errors[] = "A senha é obrigatória.";
    if (strlen($senha) < 6) $errors[] = "A senha deve ter no mínimo 6 caracteres.";
    if ($senha !== $confirmar_senha) $errors[] = "As senhas não coincidem.";
    if (empty($tipo_corretor_post)) $errors[] = "Você deve selecionar o tipo de corretor (Autônomo ou De Imobiliária)."; // Ainda valida, mesmo que as opções sejam restritas
    if (empty($cpf)) $errors[] = "CPF é obrigatório.";
    if (strlen($cpf) !== 11) $errors[] = "CPF deve ter 11 dígitos.";
    if (empty($creci)) $errors[] = "CRECI é obrigatório.";

    // Restrição: Admin Imobiliária só pode criar corretores (autônomos ou da sua imobiliária)
    if (!in_array($tipo_corretor_post, ['corretor_autonomo', 'corretor_imobiliaria'])) {
        $errors[] = "Tipo de corretor inválido para criação por Admin de Imobiliária.";
    }

    // Definir imobiliaria_id_for_db com base no tipo selecionado
    // Se Autônomo, imobiliaria_id é NULL. Se de Imobiliária, é o ID da imobiliária logada.
    $imobiliaria_id_for_db = ($tipo_corretor_post === 'corretor_imobiliaria') ? $imobiliaria_id_logada : NULL;

    // Verificar unicidade de email/cpf no servidor
    if (empty($errors)) {
        $existing_user_by_email = fetch_single("SELECT id FROM usuarios WHERE email = ?", [$email], "s");
        if ($existing_user_by_email) { $errors[] = "Já existe um usuário com este e-mail cadastrado."; }
        $existing_user_by_cpf = fetch_single("SELECT id FROM usuarios WHERE cpf = ?", [$cpf], "s");
        if ($existing_user_by_cpf) { $errors[] = "Já existe um usuário com este CPF cadastrado."; }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
        
        // Aprovado e Ativo são definidos pelos checkboxes no formulário para este admin
        $aprovado_final = $aprovado_new;
        $ativo_final = $ativo_new;

        $data_aprovacao_value = ($aprovado_final ? date('Y-m-d H:i:s') : NULL);

        try {
            $new_user_id = insert_data(
                "INSERT INTO usuarios (nome, email, senha, tipo, cpf, creci, telefone, imobiliaria_id, aprovado, ativo, data_cadastro, data_aprovacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)",
                [$nome, $email, $hashed_password, $tipo_corretor_post, $cpf, $creci, $telefone, $imobiliaria_id_for_db, $aprovado_final, $ativo_final, $data_aprovacao_value],
                "sssssssiiss"
            );

            if ($new_user_id) {
                // Notificar Admin Master sobre o novo corretor
                $alert_message = "Um novo corretor ({$nome} - " . ucfirst(str_replace('_', ' ', $tipo_corretor_post)) . ") foi adicionado por {$logged_user_info['name']} ({$imobiliaria_nome_logada}).";
                create_alert('novo_usuario_criado', $alert_message, null, $new_user_id, 'usuario'); // Alerta para Admin Master

                // Notificar o próprio corretor que foi criado
                $status_msg = ($aprovado_new && $ativo_new) ? 'aprovada e ativa' : 'pendente de aprovação';
                $email_corretor_subject = "Sua conta de Corretor no SISReserva foi criada!";
                $email_corretor_body = "Olá " . htmlspecialchars($nome) . ",\n\nSua conta de corretor na plataforma SISReserva foi criada por {$logged_user_info['name']} ({$imobiliaria_nome_logada}).\n\nSeu login é: " . htmlspecialchars($email) . ".\n\nSua conta está {$status_msg}.\n\nAtenciosamente,\nEquipe SISReserva";
                if (function_exists('send_email')) {
                    send_email($email, $nome, $email_corretor_subject, $email_corretor_body);
                }

                $_SESSION['message'] = ['type' => 'success', 'text' => "Corretor '{$nome}' adicionado com sucesso!"];
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Erro ao adicionar corretor no banco de dados.";
            }
        } catch (Exception $e) {
            error_log("Erro ao adicionar corretor: " . $e->getMessage());
            $errors[] = "Erro inesperado ao adicionar corretor: " . $e->getMessage();
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

    <div class="admin-form-section">
        <form method="POST" action="" id="criar-corretor-form"> <div class="form-group">
                <label>Tipo de Corretor:</label>
                <div class="radio-group">
                    <input type="radio" id="corretor_autonomo_radio" name="tipo" value="corretor_autonomo" <?php echo (($tipo_corretor_selecionado ?? '') === 'corretor_autonomo') ? 'checked' : ''; ?> required>
                    <label for="corretor_autonomo_radio">Autônomo</label>
                    
                    <input type="radio" id="corretor_imobiliaria_radio" name="tipo" value="corretor_imobiliaria" <?php echo (($tipo_corretor_selecionado ?? '') === 'corretor_imobiliaria') ? 'checked' : ''; ?> required>
                    <label for="corretor_imobiliaria_radio">De Imobiliária</label>
                </div>
            </div>

            <div class="form-group" id="imobiliaria_info_group" style="display: none;">
                <label>Vinculado à Imobiliária:</label>
                <input type="text" id="imobiliaria_nome_display" class="form-control" value="<?php echo htmlspecialchars($imobiliaria_nome_logada); ?>" readonly>
                <input type="hidden" name="imobiliaria_id" value="<?php echo htmlspecialchars($imobiliaria_id_logada); ?>">
            </div>

            <h3>Dados do Corretor</h3>
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

            <div class="form-group-inline checkbox-group">
                <div class="form-group">
                    <input type="checkbox" id="aprovado_checkbox" name="aprovado_checkbox" value="1" <?php echo ($aprovado_checkbox ?? 1) ? 'checked' : ''; ?>>
                    <label for="aprovado_checkbox">Aprovado (Permite login)</label>
                </div>
                <div class="form-group">
                    <input type="checkbox" id="ativo_checkbox" name="ativo_checkbox" value="1" <?php echo ($ativo_checkbox ?? 1) ? 'checked' : ''; ?>>
                    <label for="ativo_checkbox">Ativo (Pode usar o sistema)</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Adicionar Corretor</button>
                <a href="index.php" class="btn btn-secondary">Voltar à Lista</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>