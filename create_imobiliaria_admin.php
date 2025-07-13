<?php
// scripts/create_imobiliaria_admin.php
// Script para criar um usuário admin_imobiliaria e vincular a uma nova imobiliária
// USE APENAS EM AMBIENTE DE DESENVOLVIMENTO!

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php'; // Para verificar se o usuário está logado
require_once 'includes/helpers.php'; // Para format_currency_brl, format_cpf, format_whatsapp, sanitize_input

echo "<h1>Criador de Admin de Imobiliária</h1>";
echo "<p>Este script criará um usuário 'admin_imobiliaria' e uma nova imobiliária associada.</p>";
echo "<p>Após a execução, <strong>REMOVA OU PROTEJA ESTE ARQUIVO!</strong></p>";
echo "<hr>";

try {
    $conn = get_db_connection();
} catch (Exception $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Dados para o novo admin da imobiliária
$admin_nome = "Admin Imobiliaria Teste";
$admin_email = "admin.imobiliaria@teste.com";
$admin_senha_plana = "senha123"; // Mude para uma senha forte em produção!
$admin_cpf = "00000000000"; // CPF fictício
$admin_creci = "IMOB001";
$admin_telefone = "21987654321";
$admin_tipo = "admin_imobiliaria";
$admin_aprovado = TRUE; // Já aprovado para teste
$admin_ativo = TRUE; // Ativo para teste

// Dados para a nova imobiliária
$imobiliaria_nome = "Imobiliaria Teste S.A.";
$imobiliaria_cnpj = "00.000.000/0001-00";

// 1. Verificar se o admin_imobiliaria já existe
$existing_admin = fetch_single("SELECT id FROM usuarios WHERE email = ?", [$admin_email], "s");

if ($existing_admin) {
    echo "<p style='color: orange;'>Admin de imobiliária com o email '{$admin_email}' já existe. Usando o existente.</p>";
    $admin_id = $existing_admin['id'];
} else {
    // Inserir o novo admin da imobiliária
    $hashed_password = password_hash($admin_senha_plana, PASSWORD_DEFAULT);

    $sql_insert_admin = "INSERT INTO usuarios (nome, email, senha, cpf, creci, telefone, tipo, aprovado, ativo, data_cadastro, data_aprovacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $admin_id = insert_data($sql_insert_admin, 
        [$admin_nome, $admin_email, $hashed_password, $admin_cpf, $admin_creci, $admin_telefone, $admin_tipo, $admin_aprovado, $admin_ativo], 
        "ssssssiis"
    );

    if ($admin_id) {
        echo "<p style='color: green;'>Admin de imobiliária '{$admin_email}' criado com sucesso! ID: {$admin_id}</p>";
    } else {
        echo "<p style='color: red;'>Erro ao criar o admin da imobiliária.</p>";
        exit();
    }
}

// 2. Verificar se a imobiliária já existe (pelo CNPJ ou nome e admin_id)
$existing_imobiliaria = fetch_single("SELECT id FROM imobiliarias WHERE cnpj = ? OR (nome = ? AND admin_id = ?)", [$imobiliaria_cnpj, $imobiliaria_nome, $admin_id], "ssi");

if ($existing_imobiliaria) {
    echo "<p style='color: orange;'>Imobiliária '{$imobiliaria_nome}' (CNPJ: {$imobiliaria_cnpj}) já existe e/ou está vinculada a este admin. Usando a existente.</p>";
    $imobiliaria_db_id = $existing_imobiliaria['id'];
    // Certificar que o admin_id está correto, caso exista pelo CNPJ mas com outro admin_id
    update_delete_data("UPDATE imobiliarias SET admin_id = ? WHERE id = ?", [$admin_id, $imobiliaria_db_id], "ii");
    echo "<p style='color: green;'>Imobiliária existente atualizada com o admin_id correto.</p>";

} else {
    // Inserir a nova imobiliária vinculada ao admin recém-criado/existente
    $sql_insert_imobiliaria = "INSERT INTO imobiliarias (nome, cnpj, admin_id, data_cadastro) VALUES (?, ?, ?, NOW())";
    $imobiliaria_db_id = insert_data($sql_insert_imobiliaria, 
        [$imobiliaria_nome, $imobiliaria_cnpj, $admin_id], 
        "ssi"
    );

    if ($imobiliaria_db_id) {
        echo "<p style='color: green;'>Imobiliária '{$imobiliaria_nome}' criada com sucesso! ID: {$imobiliaria_db_id}</p>";
    } else {
        echo "<p style='color: red;'>Erro ao criar a imobiliária.</p>";
        exit();
    }
}

echo "<hr>";
echo "<p>Configuração concluída.</p>";
echo "<p>Agora você pode tentar logar com:</p>";
echo "<p><strong>Email:</strong> {$admin_email}</p>";
echo "<p><strong>Senha:</strong> {$admin_senha_plana}</p>";
echo "<p>No endereço: " . BASE_URL . "auth/login.php</p>";
echo "<p style='color: red; font-weight: bold;'>LEMBRE-SE DE REMOVER OU PROTEGER O ARQUIVO 'scripts/create_imobiliaria_admin.php' APÓS O USO!</p>";

?>