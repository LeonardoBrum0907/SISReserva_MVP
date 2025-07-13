<?php
// imobiliaria/corretores/editar.php - Editar Dados do Corretor (Admin Imobiliária)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação (CPF, WhatsApp)
require_once '../../includes/alerts.php';   // Para mensagens

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/corretores/editar.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Editar Corretor";

$logged_user_info = get_user_info();
$admin_imobiliaria_id = $logged_user_info['id'];
$imobiliaria_id_logado = $logged_user_info['imobiliaria_id'] ?? null;

$corretor_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$corretor_data = null;
$errors = [];
$success_message = '';

// Lida com mensagens da sessão (se houverem)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// 1. Carregar dados do corretor
if (!$corretor_id || !$imobiliaria_id_logado) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'ID do corretor não fornecido ou imobiliária não identificada.'];
    header("Location: " . BASE_URL . "imobiliaria/corretores/index.php");
    exit();
} else {
    try {
        // Buscar detalhes do corretor, garantindo que ele pertença à imobiliária logada
        $sql_corretor = "
            SELECT 
                u.id, u.nome, u.email, u.cpf, u.creci, u.telefone, u.tipo, u.aprovado, u.ativo, 
                u.data_cadastro, u.data_atualizacao, u.data_aprovacao,
                i.nome AS imobiliaria_nome
            FROM 
                usuarios u
            LEFT JOIN
                imobiliarias i ON u.imobiliaria_id = i.id
            WHERE 
                u.id = ? AND u.imobiliaria_id = ? AND u.tipo LIKE 'corretor_%';
        ";
        $corretor_data = fetch_single($sql_corretor, [$corretor_id, $imobiliaria_id_logado], "ii");

        if (!$corretor_data) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Corretor não encontrado ou não pertence à sua imobiliária.'];
            header("Location: " . BASE_URL . "imobiliaria/corretores/index.php");
            exit();
        }
        $page_title .= ": " . htmlspecialchars($corretor_data['nome']);

    } catch (Exception $e) {
        error_log("Erro ao carregar dados do corretor (imobiliaria/editar): " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os dados do corretor: " . $e->getMessage();
    }
}


// 2. Processar submissão do formulário (edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_nome = trim($_POST['nome'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
    $current_password_input = $_POST['current_password'] ?? ''; // Senha atual fornecida no formulário
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $aprovado_new = isset($_POST['aprovado']) ? 1 : 0;
    $ativo_new = isset($_POST['ativo']) ? 1 : 0;
    
    // Admin da imobiliária não pode mudar o tipo do corretor nem a imobiliária
    $current_corretor_type = $corretor_data['tipo'];
    $current_corretor_imobiliaria_id = $corretor_data['imobiliaria_id'];


    // Validações
    if (empty($new_nome)) $errors[] = "O nome é obrigatório.";
    if (empty($new_telefone)) $errors[] = "O telefone é obrigatório.";
    
    if (empty($new_email)) $errors[] = "O e-mail é obrigatório.";
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
    // Verificar unicidade do novo e-mail, exceto se for o próprio e-mail atual
    if ($new_email !== $corretor_data['email']) {
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
        if (empty($current_password_input)) $errors[] = "Para alterar a senha, você deve informar a senha atual do corretor.";
        else {
             // Buscar a senha hashed do corretor
             $check_password_query = fetch_single("SELECT senha FROM usuarios WHERE id = ?", [$corretor_id], "i");
             if (!$check_password_query || !password_verify($current_password_input, $check_password_query['senha'])) {
                 $errors[] = "A senha atual informada está incorreta.";
             }
        }
    }

    if (empty($errors)) {
        $sql_update = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, aprovado = ?, ativo = ?, data_atualizacao = NOW()";
        $params_update = [$new_nome, $new_email, $new_telefone, $aprovado_new, $ativo_new];
        $types_update = "sssii";

        // Se nova senha foi fornecida e validada, adiciona ao update
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update .= ", senha = ?";
            $params_update[] = $hashed_password;
            $types_update .= "s";
        }

        // Se o status de aprovação mudou de PENDENTE para APROVADO, atualiza data_aprovacao
        if ($aprovado_new == 1 && ($corretor_data['aprovado'] == 0)) {
            $sql_update .= ", data_aprovacao = NOW()";
        }
        
        $sql_update .= " WHERE id = ? AND imobiliaria_id = ?"; // Garante que só pode editar seus próprios corretores
        $params_update[] = $corretor_id;
        $params_update[] = $imobiliaria_id_logado;
        $types_update .= "ii";

        try {
            $affected_rows = update_delete_data($sql_update, $params_update, $types_update);

            if ($affected_rows > 0) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Dados do corretor atualizados com sucesso!'];
                
                // Recarrega os dados do corretor para refletir as mudanças na página
                $corretor_data = fetch_single($sql_corretor, [$corretor_id, $imobiliaria_id_logado], "ii");

                // Notificar sobre mudanças de status (aprovado/ativo)
                if ($aprovado_new == 1 && $corretor_data['aprovado'] == 0) {
                    create_alert('corretor_aprovado', "Sua conta foi aprovada pela Imobiliária {$corretor_data['imobiliaria_nome']}! Você já pode acessar o sistema.", $corretor_id, $corretor_id, 'usuario');
                }
                if ($ativo_new == 1 && $corretor_data['ativo'] == 0) {
                    create_alert('notificacao_geral', "Sua conta foi ativada pela Imobiliária {$corretor_data['imobiliaria_nome']}! Você já pode usar o sistema.", $corretor_id, $corretor_id, 'usuario');
                }
                if ($ativo_new == 0 && $corretor_data['ativo'] == 1) {
                    create_alert('notificacao_geral', "Sua conta foi inativada pela Imobiliária {$corretor_data['imobiliaria_nome']}. Contate o administrador.", $corretor_id, $corretor_id, 'usuario');
                }

            } else {
                $_SESSION['message'] = ['type' => 'info', 'text' => "Nenhuma alteração foi salva ou os dados já estavam atualizados."];
            }
            // Redireciona para evitar reenvio do formulário e para limpar o POST
            header("Location: " . BASE_URL . "imobiliaria/corretores/editar.php?id=" . $corretor_id);
            exit();

        } catch (Exception $e) {
            error_log("Erro ao salvar edição de corretor (imobiliaria): " . $e->getMessage());
            $errors[] = "Erro inesperado ao salvar: " . $e->getMessage();
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

    <?php if ($corretor_data): ?>
    <div class="admin-form-section">
        <form method="POST" action="">
            <h3>Dados Cadastrais</h3>
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($corretor_data['nome'] ?? ''); ?>" required maxlength="100">
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($corretor_data['email'] ?? ''); ?>" required maxlength="255">
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone (WhatsApp):</label>
                    <input type="text" id="telefone" name="telefone" class="form-control mask-whatsapp" value="<?php echo htmlspecialchars($corretor_data['telefone'] ?? ''); ?>" maxlength="20">
                </div>
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" class="form-control mask-cpf" value="<?php echo htmlspecialchars(format_cpf($corretor_data['cpf'] ?? '')); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="creci">CRECI:</label>
                    <input type="text" id="creci" name="creci" class="form-control" value="<?php echo htmlspecialchars($corretor_data['creci'] ?? ''); ?>" readonly>
                </div>
            </div>
            
            <div class="form-group-inline">
                <div class="form-group">
                    <label>Tipo de Corretor:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $corretor_data['tipo'] ?? ''))); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Imobiliária Vinculada:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($corretor_data['imobiliaria_nome'] ?? 'N/A'); ?>" readonly>
                </div>
            </div>

            <h3 class="mt-2xl">Gestão de Status e Acesso</h3>
            <div class="form-group-inline checkbox-group">
                <div class="form-group">
                    <input type="checkbox" id="aprovado" name="aprovado" value="1" <?php echo ($corretor_data['aprovado'] ?? 0) ? 'checked' : ''; ?>>
                    <label for="aprovado">Aprovado (Permite login)</label>
                </div>
                <div class="form-group">
                    <input type="checkbox" id="ativo" name="ativo" value="1" <?php echo ($corretor_data['ativo'] ?? 0) ? 'checked' : ''; ?>>
                    <label for="ativo">Ativo (Pode usar o sistema)</label>
                </div>
            </div>
            
            <div class="form-group-inline">
                <div class="form-group">
                    <label>Data de Cadastro:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(format_datetime_br($corretor_data['data_cadastro'] ?? '')); ?>" readonly>
                </div>
                <?php if (!empty($corretor_data['data_aprovacao'])): ?>
                <div class="form-group">
                    <label>Data de Aprovação:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(format_datetime_br($corretor_data['data_aprovacao'] ?? '')); ?>" readonly>
                </div>
                <?php endif; ?>
            </div>

            <h3 class="mt-2xl">Alterar Senha do Corretor</h3>
            <p class="text-danger">Apenas preencha os campos abaixo se deseja alterar a senha do corretor. Você precisará da senha atual dele.</p>
            <div class="form-group">
                <label for="current_password">Senha Atual do Corretor:</label>
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
                <a href="<?php echo BASE_URL; ?>imobiliaria/corretores/index.php" class="btn btn-secondary">Voltar à Lista</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>