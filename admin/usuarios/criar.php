<?php
// admin/usuarios/criar.php - Formulário para criar novo usuário (Admin Master)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../includes/alerts.php';

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/usuarios/criar.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Requer permissão de Admin Master
require_permission(['admin']);

$page_title = "Criar Novo Usuário";
$errors = [];
$success_message = '';
$imobiliarias = [];

// Variáveis para pré-preenchimento do formulário (se houver erro de validação)
$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$cpf = $_POST['cpf'] ?? ''; // Mantém a máscara para reexibir
$creci = $_POST['creci'] ?? '';
$imobiliaria_id = filter_input(INPUT_POST, 'imobiliaria_id', FILTER_VALIDATE_INT) ?? '';
$aprovado = isset($_POST['aprovado']) ? 1 : 0; // Padrão 0 ou 1 do POST
$ativo = isset($_POST['ativo']) ? 1 : 0;     // Padrão 0 ou 1 do POST


// Obter lista de imobiliárias para vincular corretores/admin de imobiliária
try {
    $imobiliarias = fetch_all("SELECT id, nome FROM imobiliarias ORDER BY nome ASC");
} catch (Exception $e) {
    error_log("Erro ao carregar imobiliárias em admin/usuarios/criar.php: " . $e->getMessage());
    $errors[] = "Não foi possível carregar a lista de imobiliárias.";
}

// Processar submissão do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-sanitizar e re-validar (conforme a lógica que já temos)
    $nome = trim($nome);
    $email = trim($email);
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo = trim($tipo);
    $cpf_cleaned = preg_replace('/[^0-9]/', '', $cpf); // CPF sem máscara para validação e DB
    $creci = trim($creci);
    $telefone_cleaned = preg_replace('/[^0-9]/', '', $telefone); // Telefone sem máscara para DB


    // Validações
    if (empty($nome)) $errors[] = "O nome é obrigatório.";
    if (empty($email)) $errors[] = "O e-mail é obrigatório.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
    if (empty($senha)) $errors[] = "A senha é obrigatória.";
    if (strlen($senha) < 6) $errors[] = "A senha deve ter no mínimo 6 caracteres.";
    if ($senha !== $confirmar_senha) $errors[] = "As senhas não coincidem.";
    if (empty($tipo)) $errors[] = "O tipo de usuário é obrigatório.";

    // Validações condicionais
    if (in_array($tipo, ['corretor_autonomo', 'corretor_imobiliaria'])) {
        if (empty($cpf_cleaned)) $errors[] = "CPF é obrigatório para corretores.";
        if (strlen($cpf_cleaned) !== 11) $errors[] = "CPF deve ter 11 dígitos.";
        // TODO: Adicionar validação de CPF real (algoritmo)
        if (empty($creci)) $errors[] = "CRECI é obrigatório para corretores.";
    }

    if ($tipo === 'corretor_imobiliaria' || $tipo === 'admin_imobiliaria') {
        if (!$imobiliaria_id) $errors[] = "A imobiliária é obrigatória para este tipo de usuário.";
    } else {
        $imobiliaria_id = NULL; // Garante que não há imobiliária vinculada se o tipo não exige
    }

    // Verificar se e-mail já existe
    if (empty($errors)) {
        $existing_user_by_email = fetch_single("SELECT id FROM usuarios WHERE email = ?", [$email], "s");
        if ($existing_user_by_email) {
            $errors[] = "Já existe um usuário com este e-mail.";
        }
        if (!empty($cpf_cleaned)) { // Verificar CPF apenas se foi preenchido
            $existing_user_by_cpf = fetch_single("SELECT id FROM usuarios WHERE cpf = ?", [$cpf_cleaned], "s");
            if ($existing_user_by_cpf) {
                $errors[] = "Já existe um usuário com este CPF.";
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
                "INSERT INTO usuarios (nome, email, senha, tipo, cpf, creci, telefone, imobiliaria_id, aprovado, ativo, data_cadastro, data_atualizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [$nome, $email, $hashed_password, $tipo, $cpf_cleaned, $creci, $telefone_cleaned, $imobiliaria_id, $aprovado, $ativo],
                "sssssssiis" // (string)nome, (string)email, (string)senha, (string)tipo, (string)cpf_cleaned, (string)creci, (string)telefone_cleaned, (int)imobiliaria_id, (int)aprovado, (int)ativo
            );

            if ($new_user_id) {
                $_SESSION['message'] = ['type' => 'success', 'text' => "Usuário '{$nome}' criado com sucesso!"];
                
                // Crie o alerta para o novo usuário e para o admin
                create_alert('novo_usuario_criado', "O usuário {$nome} ({$tipo}) foi criado.", $new_user_id, $new_user_id, 'usuario');
                if ($tipo === 'corretor_autonomo' || $tipo === 'corretor_imobiliaria') {
                    // Se for um corretor (autônomo ou de imobiliária), avisa o Admin Master
                    create_alert('novo_corretor_cadastro', "Um novo usuário ({$nome} - " . ucfirst(str_replace('_', ' ', $tipo)) . ") aguarda aprovação.", ADMIN_MASTER_USER_ID, $new_user_id, 'usuario');
                    // Se for corretor de imobiliária, avisa o Admin da Imobiliária
                    if ($tipo === 'corretor_imobiliaria' && $imobiliaria_id) {
                        $admin_imob_email = fetch_single("SELECT id, nome FROM usuarios WHERE imobiliaria_id = ?", [$imobiliaria_id], "i");
                        if ($admin_imob_email) {
                           create_alert('novo_corretor_cadastro', "Um novo usuário ({$nome} - " . ucfirst(str_replace('_', ' ', $tipo)) . ") aguarda aprovação na sua imobiliária.", $admin_imob_email['id'], $new_user_id, 'usuario');
                        }
                    }
                }
                
                header("Location: index.php"); // Redireciona para a lista de usuários
                exit();
            } else {
                $errors[] = "Erro ao criar usuário no banco de dados.";
            }
        } catch (Exception $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            $errors[] = "Erro inesperado ao criar usuário: " . $e->getMessage();
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

    <?php if (!empty($success_message)): ?>
        <div class="message-box message-box-success">
            <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="admin-form-section">
        <form id="createUserForm" method="POST" action="">
            <div class="form-group">
                <label for="tipo">Tipo de Usuário:</label>
                <select id="tipo" name="tipo" class="form-control" required>
                    <option value="">Selecione</option>
                    <option value="admin" <?php echo (($tipo ?? '') === 'admin') ? 'selected' : ''; ?>>Admin Master</option>
                    <option value="admin_imobiliaria" <?php echo (($tipo ?? '') === 'admin_imobiliaria') ? 'selected' : ''; ?>>Admin de Imobiliária</option>
                    <option value="corretor_autonomo" <?php echo (($tipo ?? '') === 'corretor_autonomo') ? 'selected' : ''; ?>>Corretor Autônomo</option>
                    <option value="corretor_imobiliaria" <?php echo (($tipo ?? '') === 'corretor_imobiliaria') ? 'selected' : ''; ?>>Corretor de Imobiliária</option>
                </select>
            </div>

            <div class="form-group" id="imobiliaria_select_group" style="display: <?php echo (in_array($tipo, ['corretor_imobiliaria', 'admin_imobiliaria']) ? 'block' : 'none'); ?>;">
                <label for="imobiliaria_id">Imobiliária:</label>
                <select id="imobiliaria_id" name="imobiliaria_id" class="form-control">
                    <option value="">Selecione a Imobiliária</option>
                    <?php foreach ($imobiliarias as $imob): ?>
                        <option value="<?php echo htmlspecialchars($imob['id']); ?>" <?php echo (($imobiliaria_id ?? '') == $imob['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($imob['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($nome); ?>" required maxlength="100">
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required maxlength="255">
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone (WhatsApp):</label>
                    <input type="text" id="telefone" name="telefone" class="form-control mask-whatsapp" value="<?php echo htmlspecialchars($telefone); ?>" maxlength="20">
                </div>
            </div>

            <div class="form-group-inline">
                <div class="form-group password-input-wrapper">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" class="form-control" required minlength="6" maxlength="255">
                    <button type="button" class="toggle-password-visibility" aria-label="Mostrar/Esconder Senha"><i class="fas fa-eye"></i></button>
                </div>
                <div class="form-group password-input-wrapper">
                    <label for="confirmar_senha">Confirmar Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" value="<?php echo htmlspecialchars($confirmar_senha); ?>" required minlength="6" maxlength="255">
                    <button type="button" class="toggle-password-visibility" aria-label="Mostrar/Esconder Senha"><i class="fas fa-eye"></i></button>
                </div>
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" class="form-control mask-cpf" value="<?php echo htmlspecialchars($cpf); ?>" maxlength="14">
                </div>
                <div class="form-group">
                    <label for="creci">CRECI:</label>
                    <input type="text" id="creci" name="creci" class="form-control" value="<?php echo htmlspecialchars($creci); ?>" maxlength="20">
                </div>
            </div>
            
            <div class="form-group-inline checkbox-group">
                <div class="form-group">
                    <input type="checkbox" id="aprovado" name="aprovado" value="1" <?php echo ($aprovado == 1) ? 'checked' : ''; ?>>
                    <label for="aprovado">Aprovado (Permite login)</label>
                </div>
                <div class="form-group">
                    <input type="checkbox" id="ativo" name="ativo" value="1" <?php echo ($ativo == 1) ? 'checked' : ''; ?>>
                    <label for="ativo">Ativo (Pode usar o sistema)</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Criar Usuário</button>
                <a href="index.php" class="btn btn-secondary">Voltar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>