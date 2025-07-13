<?php
// api/empreendimentos/salvar_etapa1.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para sanitize_input e validação de data

header('Content-Type: application/json');

// Conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em api/empreendimentos/salvar_etapa1.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

// Requer permissão de Admin Master
require_permission(['admin'], true); // 'true' para indicar que é uma requisição AJAX

$response = ['success' => false, 'message' => 'Requisição inválida ou dados incompletos.', 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empreendimento_id = filter_input(INPUT_POST, 'empreendimento_id', FILTER_VALIDATE_INT);
    
    // Sanitização e Coleta de Dados - APENAS CAMPOS DA ETAPA 1, conforme sua lista e as novas colunas
    $nome = sanitize_input($_POST['nome'] ?? '');
    $tipo_empreendimento = sanitize_input($_POST['tipo_empreendimento'] ?? '');
    $tipo_uso = sanitize_input($_POST['tipo_uso'] ?? '');
    $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? ''); // Apenas números
    $endereco = sanitize_input($_POST['endereco'] ?? '');
    $numero = sanitize_input($_POST['numero'] ?? '');
    $complemento = sanitize_input($_POST['complemento'] ?? '');
    $bairro = sanitize_input($_POST['bairro'] ?? '');
    $cidade = sanitize_input($_POST['cidade'] ?? '');
    $estado = sanitize_input($_POST['estado'] ?? '');
    $descricao = sanitize_input($_POST['descricao'] ?? ''); // Descrição completa
    $status = sanitize_input($_POST['status'] ?? ''); // Status operacional (ativo/pausado)
    $fase_empreendimento = sanitize_input($_POST['fase_empreendimento'] ?? ''); // Nova coluna de fase
    $preco_por_m2_sugerido = filter_input(INPUT_POST, 'preco_por_m2_sugerido', FILTER_UNSAFE_RAW);
    $momento_envio_documentacao = sanitize_input($_POST['momento_envio_documentacao'] ?? '');
    $documentos_obrigatorios = $_POST['documentos_obrigatorios'] ?? '[]'; // JSON string
    $data_lancamento = sanitize_input($_POST['data_lancamento'] ?? '');
    $previsao_entrega = sanitize_input($_POST['previsao_entrega'] ?? '');

    // Convertendo valores monetários para float
    $preco_por_m2_sugerido = str_replace(['R$', '.', ','], ['', '', '.'], $preco_por_m2_sugerido);
    $preco_por_m2_sugerido = filter_var($preco_por_m2_sugerido, FILTER_VALIDATE_FLOAT);

    // Validações
    if (empty($nome)) $response['errors']['nome'] = 'Nome do empreendimento é obrigatório.';
    if (empty($tipo_empreendimento)) $response['errors']['tipo_empreendimento'] = 'Tipo de empreendimento é obrigatório.';
    if (empty($tipo_uso)) $response['errors']['tipo_uso'] = 'Tipo de uso é obrigatório.';
    if (empty($cep) || strlen($cep) !== 8) $response['errors']['cep'] = 'CEP inválido (8 dígitos numéricos).';
    if (empty($endereco)) $response['errors']['endereco'] = 'Endereço é obrigatório.';
    if (empty($numero)) $response['errors']['numero'] = 'Número é obrigatório.';
    if (empty($bairro)) $response['errors']['bairro'] = 'Bairro é obrigatório.';
    if (empty($cidade)) $response['errors']['cidade'] = 'Cidade é obrigatória.';
    if (empty($estado)) $response['errors']['estado'] = 'Estado é obrigatório.';
    if (empty($descricao)) $response['errors']['descricao'] = 'Descrição completa é obrigatória.';
    // Validações para as novas colunas de status e fase
    if (empty($status) || !in_array($status, ['ativo', 'pausado'])) $response['errors']['status'] = 'Status operacional é obrigatório e deve ser "ativo" ou "pausado".';
    if (empty($fase_empreendimento) || !in_array($fase_empreendimento, ['pre_lancamento', 'lancamento', 'em_obra', 'pronto_para_morar'])) $response['errors']['fase_empreendimento'] = 'Fase do empreendimento é obrigatória e deve ser válida.';

    if ($preco_por_m2_sugerido === false || $preco_por_m2_sugerido <= 0) $response['errors']['preco_por_m2_sugerido'] = 'Preço por M² sugerido deve ser um número positivo.';

    // Validação de momento_envio_documentacao e documentos_obrigatorios
    $allowed_momentos = ['Na Proposta de Reserva', 'Após Confirmação de Reserva', 'Na Assinatura do Contrato'];
    if (!in_array($momento_envio_documentacao, $allowed_momentos)) {
        $response['errors']['momento_envio_documentacao'] = 'Momento de envio de documentação inválido.';
    }

    if ($momento_envio_documentacao === 'Na Proposta de Reserva') {
        $decoded_docs = json_decode($documentos_obrigatorios, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_docs) || empty($decoded_docs)) {
            $response['errors']['documentos_obrigatorios'] = 'Documentos obrigatórios são mandatórios para esta opção e devem ser um JSON válido de array não vazio.';
        }
    } else {
        $documentos_obrigatorios = '[]';
    }

    if (!empty($data_lancamento) && !is_valid_date($data_lancamento)) {
        $response['errors']['data_lancamento'] = 'Data de lançamento inválida.';
    }
    if (!empty($previsao_entrega) && !is_valid_date($previsao_entrega)) {
        $response['errors']['previsao_entrega'] = 'Previsão de entrega inválida.';
    }
    
    if (!empty($response['errors'])) {
        $response['message'] = 'Erros de validação encontrados.';
        echo json_encode($response);
        exit();
    }

    $conn->begin_transaction();

    try {
        if ($empreendimento_id) {
            // Atualizar empreendimento existente
            $sql = "UPDATE empreendimentos SET 
                        nome = ?, tipo_uso = ?, tipo_empreendimento = ?, fase_empreendimento = ?, descricao = ?, 
                        cep = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?, 
                        status = ?, preco_por_m2_sugerido = ?, momento_envio_documentacao = ?, documentos_obrigatorios = ?, 
                        data_lancamento = ?, previsao_entrega = ?, data_atualizacao = NOW() 
                    WHERE id = ?";
            // Tipos: s (nome), s (tipo_uso), s (tipo_empreendimento), s (fase_empreendimento), s (descricao), 
            //        s (cep), s (endereco), s (numero), s (complemento), s (bairro), s (cidade), s (estado), 
            //        s (status), d (preco_por_m2_sugerido), s (momento_envio_documentacao), s (documentos_obrigatorios), 
            //        s (data_lancamento), s (previsao_entrega), i (empreendimento_id)
            $params = [
                $nome, $tipo_uso, $tipo_empreendimento, $fase_empreendimento, $descricao,
                $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado,
                $status, $preco_por_m2_sugerido, $momento_envio_documentacao, $documentos_obrigatorios,
                ($data_lancamento ?: null), ($previsao_entrega ?: null), $empreendimento_id
            ];
            $types = "sssssssssssssssdsssi"; // 17 's', 1 'd', 1 'i' = 19 caracteres
            update_delete_data($sql, $params, $types);
            $message = "Empreendimento '{$nome}' atualizado com sucesso na etapa 1!";
            
            // Auditoria
            insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                        [$_SESSION['user_id'], 'Atualização Empreendimento Etapa 1', 'Empreendimento', $empreendimento_id, "Empreendimento '{$nome}' atualizado na etapa 1.", $_SERVER['REMOTE_ADDR']],
                        "isssss");

        } else {
            // Inserir novo empreendimento
            $sql = "INSERT INTO empreendimentos (
                        nome, tipo_uso, tipo_empreendimento, fase_empreendimento, descricao, 
                        cep, endereco, numero, complemento, bairro, cidade, estado, 
                        status, preco_por_m2_sugerido, momento_envio_documentacao, documentos_obrigatorios, 
                        data_lancamento, previsao_entrega, data_cadastro, data_atualizacao
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            // Tipos: s (nome), s (tipo_uso), s (tipo_empreendimento), s (fase_empreendimento), s (descricao), 
            //        s (cep), s (endereco), s (numero), s (complemento), s (bairro), s (cidade), s (estado), 
            //        s (status), d (preco_por_m2_sugerido), s (momento_envio_documentacao), s (documentos_obrigatorios), 
            //        s (data_lancamento), s (previsao_entrega)
            $params = [
                $nome, $tipo_uso, $tipo_empreendimento, $fase_empreendimento, $descricao,
                $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado,
                $status, $preco_por_m2_sugerido, $momento_envio_documentacao, $documentos_obrigatorios,
                ($data_lancamento ?: null), ($previsao_entrega ?: null)
            ];
            $types = "sssssssssssssdssss"; // 17 's', 1 'd' = 18 caracteres
            $empreendimento_id = insert_data($sql, $params, $types);
            $message = "Empreendimento '{$nome}' cadastrado com sucesso na etapa 1!";
            
            // Auditoria
            insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                        [$_SESSION['user_id'], 'Cadastro Empreendimento Etapa 1', 'Empreendimento', $empreendimento_id, "Empreendimento '{$nome}' cadastrado na etapa 1.", $_SERVER['REMOTE_ADDR']],
                        "isssss");

            $_SESSION['current_empreendimento_id'] = $empreendimento_id; // Armazena na sessão para os próximos passos
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = $message;
        $response['empreendimento_id'] = $empreendimento_id;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao salvar Etapa 1 do empreendimento: " . $e->getMessage());
        $response['message'] = 'Erro ao salvar dados da Etapa 1: ' . $e->getMessage();
    } finally {
        $conn->close();
    }
}

echo json_encode($response);
?>