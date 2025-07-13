<?php
// scripts/popular.php - Script para popular o banco de dados com dados de teste iniciais

// ESTE SCRIPT DEVE SER USADO APENAS PARA DESENVOLVIMENTO/TESTES!
// REMOVA-O OU PROTEJA-O EM PRODUÇÃO!

// Adicionado ini_set para exibir erros PHP no navegador durante a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificação de existência dos arquivos antes de incluir
if (!file_exists('../includes/config.php')) {
    die("Erro: ../includes/config.php não encontrado. Verifique o caminho.");
}
if (!file_exists('../includes/database.php')) {
    die("Erro: ../includes/database.php não encontrado. Verifique o caminho.");
}
if (!file_exists('../includes/helpers.php')) {
    die("Erro: ../includes/helpers.php não encontrado. Verifique o caminho.");
}
if (!file_exists('../includes/alerts.php')) {
    die("Erro: ../includes/alerts.php não encontrado. Verifique o caminho.");
}


require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/helpers.php'; // Para password_hash, generate_token
require_once '../includes/alerts.php'; // Para criar alertas de teste

echo "<h1>População Inicial do Banco de Dados</h1>";
echo "<p>Este script irá inserir dados de teste para Usuários, Imobiliárias, Empreendimentos, Unidades e Reservas.</p>";
echo "<p style='color: red; font-weight: bold;'>AVISO: Pode resetar ou adicionar dados, use com cautela!</p>";
echo "<hr>";

try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// ==========================================================================
// FUNÇÕES AUXILIARES PARA INSERÇÃO
// ==========================================================================

function insert_user($nome, $email, $senha_plana, $tipo, $aprovado = TRUE, $ativo = TRUE, $imobiliaria_id = NULL, $cpf = NULL, $creci = NULL, $telefone = NULL) {
    global $conn;
    $hashed_password = password_hash($senha_plana, PASSWORD_DEFAULT);

    // Verificar se o usuário já existe
    $existing_user = fetch_single("SELECT id FROM usuarios WHERE email = ?", [$email], "s");
    if ($existing_user) {
        echo "<p style='color: orange;'>Usuário {$email} já existe. Pulando criação.</p>";
        return $existing_user['id'];
    }

    $sql = "INSERT INTO usuarios (nome, email, senha, tipo, aprovado, ativo, imobiliaria_id, cpf, creci, telefone, data_cadastro, data_atualizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $types = "sssiisiiss";
    $params = [$nome, $email, $hashed_password, $tipo, $aprovado, $ativo, $imobiliaria_id, $cpf, $creci, $telefone];

    $user_id = insert_data($sql, $params, $types);
    if ($user_id) {
        echo "<p style='color: green;'>Usuário {$nome} ({$tipo}) criado com ID: {$user_id}</p>";
    } else {
        echo "<p style='color: red;'>Erro ao criar usuário {$nome}.</p>";
    }
    return $user_id;
}

function insert_imobiliaria($nome, $cnpj, $email, $admin_id) {
    global $conn;
    $existing_imobiliaria = fetch_single("SELECT id FROM imobiliarias WHERE cnpj = ?", [$cnpj], "s");
    if ($existing_imobiliaria) {
        echo "<p style='color: orange;'>Imobiliária {$nome} já existe. Pulando criação.</p>";
        return $existing_imobiliaria['id'];
    }

    $sql = "INSERT INTO imobiliarias (nome, cnpj, email, admin_id, data_cadastro, data_atualizacao) VALUES (?, ?, ?, ?, NOW(), NOW())";
    $imobiliaria_id = insert_data($sql, [$nome, $cnpj, $email, $admin_id], "sssi");
    if ($imobiliaria_id) {
        echo "<p style='color: green;'>Imobiliária {$nome} criada com ID: {$imobiliaria_id}</p>";
    } else {
        echo "<p style='color: red;'>Erro ao criar imobiliária {$nome}.</p>";
    }
    return $imobiliaria_id;
}

function insert_empreendimento($nome, $tipo_uso, $tipo_empreendimento, $descricao, $cep, $endereco, $numero, $bairro, $cidade, $estado, $momento_envio_documentacao, $documentos_obrigatorios_json) {
    global $conn;
    $existing_emp = fetch_single("SELECT id FROM empreendimentos WHERE nome = ?", [$nome], "s");
    if ($existing_emp) {
        echo "<p style='color: orange;'>Empreendimento {$nome} já existe. Pulando criação.</p>";
        return $existing_emp['id'];
    }

    $sql = "INSERT INTO empreendimentos (nome, tipo_uso, tipo_empreendimento, descricao, cep, endereco, numero, bairro, cidade, estado, momento_envio_documentacao, documentos_obrigatorios, status, data_cadastro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', NOW())";
    $params = [$nome, $tipo_uso, $tipo_empreendimento, $descricao, $cep, $endereco, $numero, $bairro, $cidade, $estado, $momento_envio_documentacao, $documentos_obrigatorios_json];
    $types = "ssssssssssss";
    $emp_id = insert_data($sql, $params, $types);
    if ($emp_id) {
        echo "<p style='color: green;'>Empreendimento {$nome} criado com ID: {$emp_id}</p>";
    } else {
        echo "<p style='color: red;'>Erro ao criar empreendimento {$nome}.</p>";
    }
    return $emp_id;
}

function insert_tipo_unidade($empreendimento_id, $tipo, $metragem, $quartos, $banheiros, $vagas, $foto_planta_path = NULL) {
    global $conn;
    $existing_tipo = fetch_single("SELECT id FROM tipos_unidades WHERE empreendimento_id = ? AND tipo = ?", [$empreendimento_id, $tipo], "is");
    if ($existing_tipo) {
        echo "<p style='color: orange;'>Tipo de Unidade {$tipo} para Emp. {$empreendimento_id} já existe. Pulando criação.</p>";
        return $existing_tipo['id'];
    }

    $sql = "INSERT INTO tipos_unidades (empreendimento_id, tipo, metragem, quartos, banheiros, vagas, foto_planta, data_cadastro) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $tu_id = insert_data($sql, [$empreendimento_id, $tipo, $metragem, $quartos, $banheiros, $vagas, $foto_planta_path], "isiiiis");
    if ($tu_id) {
        echo "<p style='color: green;'>Tipo de Unidade {$tipo} para Emp. {$empreendimento_id} criado com ID: {$tu_id}</p>";
    } else {
        echo "<p style='color: red;'>Erro ao criar tipo de unidade {$tipo}.</p>";
    }
    return $tu_id;
}

function insert_unidade($empreendimento_id, $tipo_unidade_id, $numero, $andar, $posicao, $valor, $status, $informacoes_pagamento_json) {
    global $conn;
    $existing_unidade = fetch_single("SELECT id FROM unidades WHERE empreendimento_id = ? AND numero = ?", [$empreendimento_id, $numero], "is");
    if ($existing_unidade) {
        echo "<p style='color: orange;'>Unidade {$numero} do Emp. {$empreendimento_id} já existe. Pulando criação.</p>";
        return $existing_unidade['id'];
    }

    $sql = "INSERT INTO unidades (empreendimento_id, tipo_unidade_id, numero, andar, posicao, valor, status, informacoes_pagamento, data_cadastro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $u_id = insert_data($sql, [$empreendimento_id, $tipo_unidade_id, $numero, $andar, $posicao, $valor, $status, $informacoes_pagamento_json], "iisidsis");
    if ($u_id) {
        echo "<p style='color: green;'>Unidade {$numero} do Emp. {$empreendimento_id} ({$status}) criado com ID: {$u_id}</p>";
    } else {
        echo "<p style='color: red;'>Erro ao criar unidade {$numero}.</p>";
    }
    return $u_id;
}

function insert_cliente($nome, $cpf, $email, $whatsapp, $cep=NULL, $endereco=NULL, $numero=NULL, $complemento=NULL, $bairro=NULL, $cidade=NULL, $estado=NULL) {
    global $conn;
    $existing_cliente = fetch_single("SELECT id FROM clientes WHERE cpf = ?", [$cpf], "s");
    if ($existing_cliente) {
        echo "<p style='color: orange;'>Cliente {$nome} (CPF: {$cpf}) já existe. Pulando criação.</p>";
        return $existing_cliente['id'];
    }

    $sql = "INSERT INTO clientes (nome, cpf, email, whatsapp, cep, endereco, numero, complemento, bairro, cidade, estado, data_cadastro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $params = [$nome, $cpf, $email, $whatsapp, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado];
    $types = "sssssssssss";
    $cliente_id = insert_data($sql, $params, $types);
    if ($cliente_id) {
        echo "<p style='color: green;'>Cliente {$nome} criado com ID: {$cliente_id}</p>";
    } else {
        echo "<p style='color: red;'>Erro ao criar cliente {$nome}.</p>";
    }
    return $cliente_id;
}

function insert_reserva($empreendimento_id, $unidade_id, $corretor_id, $valor_reserva, $status, $cliente_id, $observacoes = NULL) {
    global $conn;
    $expiracao = null; // Para status sem expiração definida explicitamente aqui

    // Define data de expiração se a reserva for aprovada e tiver um prazo (exemplo: 7 dias)
    if ($status === 'aprovada') {
        $expiracao = date('Y-m-d H:i:s', strtotime('+7 days'));
    }

    $sql = "INSERT INTO reservas (empreendimento_id, unidade_id, corretor_id, valor_reserva, status, data_reserva, data_expiracao, observacoes, data_ultima_interacao) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, NOW())";
    $params = [$empreendimento_id, $unidade_id, $corretor_id, $valor_reserva, $status, $expiracao, $observacoes];
    $types = "iidsiss";
    $reserva_id = insert_data($sql, $params, $types);

    if ($reserva_id) {
        // Vincula cliente à reserva
        insert_data("INSERT INTO reserva_clientes (reserva_id, cliente_id) VALUES (?, ?)", [$reserva_id, $cliente_id], "ii");
        // Atualiza status da unidade se a reserva for 'reservada' ou 'vendida'
        if ($status === 'reservada' || $status === 'vendida') {
            update_delete_data("UPDATE unidades SET status = ? WHERE id = ?", [$status, $unidade_id], "si");
            echo "<p style='color: green;'>Status da unidade {$unidade_id} atualizado para '{$status}'.</p>";
        }
        echo "<p style='color: green;'>Reserva {$reserva_id} ({$status}) para Unidade {$unidade_id} criada.</p>";
    } else {
        echo "<p style='color: red;'>Erro ao criar reserva para Unidade {$unidade_id}.</p>";
    }
    return $reserva_id;
}