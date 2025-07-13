<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../includes/alerts.php';

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/imobiliarias/criar.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}
require_permission(['admin']);

$page_title = "Criar Nova Imobiliária";
$errors = [];
$success_message = '';

$nome = $cnpj = $email = $telefone = $cep = $endereco = $numero = $complemento = $bairro = $cidade = $estado = '';
$ativa = 1;
$admin_id = '';

// Mensagens de sessão
if (isset($_SESSION['form_messages'])) {
    $errors = array_merge($errors, $_SESSION['form_messages']['errors'] ?? []);
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    if (!empty($errors) && isset($_SESSION['form_data'])) {
        $data = $_SESSION['form_data'];
        $nome = $data['nome'] ?? '';
        $cnpj = $data['cnpj'] ?? '';
        $email = $data['email'] ?? '';
        $telefone = $data['telefone'] ?? '';
        $cep = $data['cep'] ?? '';
        $endereco = $data['endereco'] ?? '';
        $numero = $data['numero'] ?? '';
        $complemento = $data['complemento'] ?? '';
        $bairro = $data['bairro'] ?? '';
        $cidade = $data['cidade'] ?? '';
        $estado = $data['estado'] ?? '';
        $ativa = $data['ativa'] ?? 0;
        $admin_id = $data['admin_id'] ?? '';
    }
    unset($_SESSION['form_messages']);
    unset($_SESSION['form_data']);
}

// Lista admins
$admins_imobiliarias = [];
try {
    $admins_imobiliarias = fetch_all("SELECT id, nome, email FROM usuarios WHERE (tipo = 'admin' OR tipo = 'admin_imobiliaria') AND ativo = TRUE ORDER BY nome ASC");
} catch (Exception $e) {
    error_log("Erro ao carregar admins de imobiliárias: " . $e->getMessage());
    $errors[] = "Não foi possível carregar a lista de administradores de imobiliárias.";
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
        <form id="createImobiliariaForm" method="POST" action="">
            <input type="hidden" name="action" value="create_imobiliaria">
            <h3>Dados da Imobiliária</h3>
            <div class="form-group">
                <label for="nome">Nome da Imobiliária:</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($nome); ?>" required maxlength="255">
            </div>
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="cnpj">CNPJ:</label>
                    <input type="text" id="cnpj" name="cnpj" class="form-control mask-cnpj" value="<?php echo htmlspecialchars($cnpj); ?>" required maxlength="18">
                </div>
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required maxlength="255">
                </div>
            </div>
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" class="form-control mask-telefone" value="<?php echo htmlspecialchars($telefone); ?>" maxlength="20">
                </div>
                <div class="form-group">
                    <label for="cep">CEP:</label>
                    <input type="text" id="cep" name="cep" class="form-control mask-cep" value="<?php echo htmlspecialchars($cep); ?>" required maxlength="10">
                </div>
            </div>
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <input type="text" id="endereco" name="endereco" class="form-control" value="<?php echo htmlspecialchars($endereco); ?>" required maxlength="255">
            </div>
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="numero">Número:</label>
                    <input type="text" id="numero" name="numero" class="form-control" value="<?php echo htmlspecialchars($numero); ?>" maxlength="20">
                </div>
                <div class="form-group">
                    <label for="complemento">Complemento:</label>
                    <input type="text" id="complemento" name="complemento" class="form-control" value="<?php echo htmlspecialchars($complemento); ?>" maxlength="100">
                </div>
            </div>
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="bairro">Bairro:</label>
                    <input type="text" id="bairro" name="bairro" class="form-control" value="<?php echo htmlspecialchars($bairro); ?>" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade" class="form-control" value="<?php echo htmlspecialchars($cidade); ?>" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="estado">Estado (UF):</label>
                    <input type="text" id="estado" name="estado" class="form-control" value="<?php echo htmlspecialchars($estado); ?>" required maxlength="2">
                </div>
            </div>
            <div class="form-group">
                <label for="admin_id">Admin Responsável (opcional, pode ser vinculado depois):</label>
                <select id="admin_id" name="admin_id" class="form-control">
                    <option value="">Nenhum (selecione um usuário existente)</option>
                    <?php foreach ($admins_imobiliarias as $admin_imob): ?>
                        <option value="<?php echo htmlspecialchars($admin_imob['id']); ?>" <?php echo (($admin_id ?? '') == $admin_imob['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($admin_imob['nome'] . " (" . $admin_imob['email'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Apenas usuários com tipo 'admin_imobiliaria' ou que podem ser convertidos serão listados. Se o admin desejado não estiver na lista, <a href="<?php echo BASE_URL; ?>admin/usuarios/criar.php" target="_blank">crie um novo usuário</a>.</small>
            </div>
            <div class="form-group checkbox-group">
                <input type="checkbox" id="ativa" name="ativa" value="1" <?php echo ($ativa == 1) ? 'checked' : ''; ?>>
                <label for="ativa">Imobiliária Ativa (Permite operações)</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="createButton">Criar Imobiliária</button>
                <a href="index.php" class="btn btn-secondary">Voltar</a>
            </div>
        </form>
    </div>
    <div id="genericConfirmationModalContainer"></div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/admin_imobiliarias.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/admin_criar_imobiliaria.js"></script>
<?php require_once '../../includes/footer_dashboard.php'; ?>
