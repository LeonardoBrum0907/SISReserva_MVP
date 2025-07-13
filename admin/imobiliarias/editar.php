<?php
// admin/imobiliarias/editar.php - VERSÃO FINAL COM CORREÇÃO NO FORM

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

require_permission(['admin']);
$page_title = "Editar Imobiliária";
$conn = get_db_connection();

$imobiliaria_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$imobiliaria_id) {
    header("Location: index.php");
    exit();
}

$imobiliaria_data = fetch_single("SELECT * FROM imobiliarias WHERE id = ?", [$imobiliaria_id], "i");
if (!$imobiliaria_data) {
    $_SESSION['message'] = create_alert('error', 'Imobiliária não encontrada.');
    header("Location: index.php");
    exit();
}

require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?>: <?php echo htmlspecialchars($imobiliaria_data['nome']); ?></h2>
    
    <div class="admin-form-section">
        <form id="editImobiliariaForm" method="POST">
            <input type="hidden" name="imobiliaria_id" value="<?php echo htmlspecialchars($imobiliaria_data['id']); ?>">
            <input type="hidden" name="action" value="update_imobiliaria">
            
            <h3>Dados da Imobiliária</h3>
            <div class="form-group">
                <label for="nome">Nome da Imobiliária:</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($imobiliaria_data['nome']); ?>" required>
            </div>

            <div class="form-group-inline">
                 <div class="form-group">
                    <label for="cnpj">CNPJ:</label>
                    <input type="text" id="cnpj" name="cnpj" class="form-control mask-cnpj" value="<?php echo htmlspecialchars($imobiliaria_data['cnpj']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($imobiliaria_data['email']); ?>" required>
                </div>
            </div>
             <div class="form-group-inline">
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" class="form-control mask-telefone" value="<?php echo htmlspecialchars($imobiliaria_data['telefone']); ?>">
                </div>
                <div class="form-group">
                    <label for="cep">CEP:</label>
                    <input type="text" id="cep" name="cep" class="form-control mask-cep" value="<?php echo htmlspecialchars($imobiliaria_data['cep'] ?? ''); ?>">
                </div>
            </div>
             <div class="form-group">
                <label for="endereco">Endereço:</label>
                <input type="text" id="endereco" name="endereco" class="form-control" value="<?php echo htmlspecialchars($imobiliaria_data['endereco'] ?? ''); ?>">
            </div>
             <div class="form-group-inline">
                 <div class="form-group">
                    <label for="numero">Número:</label>
                    <input type="text" id="numero" name="numero" class="form-control" value="<?php echo htmlspecialchars($imobiliaria_data['numero'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="complemento">Complemento:</label>
                    <input type="text" id="complemento" name="complemento" class="form-control" value="<?php echo htmlspecialchars($imobiliaria_data['complemento'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group-inline">
                 <div class="form-group">
                    <label for="bairro">Bairro:</label>
                    <input type="text" id="bairro" name="bairro" class="form-control" value="<?php echo htmlspecialchars($imobiliaria_data['bairro'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade" class="form-control" value="<?php echo htmlspecialchars($imobiliaria_data['cidade'] ?? ''); ?>">
                </div>
                <div class="form-group">
                   <label for="estado">Estado:</label>
                    <input type="text" id="estado" name="estado" class="form-control" value="<?php echo htmlspecialchars($imobiliaria_data['estado'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                <a href="index.php" class="btn btn-secondary">Voltar à Lista</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>