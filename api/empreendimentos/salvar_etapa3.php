<?php
// api/empreendimentos/salvar_etapa3.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em salvar_etapa3.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

require_permission(['admin'], true);

$response = ['success' => false, 'message' => 'Requisição inválida ou dados incompletos.', 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empreendimento_id = filter_input(INPUT_POST, 'empreendimento_id', FILTER_VALIDATE_INT);
    $areas_comuns_selecionadas_json = $_POST['areas_comuns_selecionadas_json'] ?? '[]';
    $outras_areas_comuns = sanitize_input($_POST['outras_areas_comuns'] ?? '');

    if (!$empreendimento_id) {
        $response['message'] = 'ID do empreendimento não fornecido.';
        echo json_encode($response);
        exit();
    }

    $areas_comuns_selecionadas_data = json_decode($areas_comuns_selecionadas_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($areas_comuns_selecionadas_data)) {
        $response['message'] = 'Dados de áreas comuns inválidos (não é um JSON válido).';
        echo json_encode($response);
        exit();
    }
    if (strlen($outras_areas_comuns) > 1000) {
        $response['errors']['outras_areas_comuns'] = 'O campo "Outras Áreas Comuns" não pode exceder 1000 caracteres.';
        $response['message'] = 'Erros de validação.';
        echo json_encode($response);
        exit();
    }

    $conn->begin_transaction();

    try {
        // 1. Atualiza o campo 'outras_areas_comuns' na tabela 'empreendimentos'
        $sql_update_emp = "UPDATE empreendimentos SET outras_areas_comuns = ?, data_atualizacao = NOW() WHERE id = ?";
        update_delete_data($sql_update_emp, [$outras_areas_comuns, $empreendimento_id], "si");

        // 2. Sincroniza a tabela pivô 'empreendimentos_areas_comuns'
        // Remove todas as entradas existentes para este empreendimento
        update_delete_data("DELETE FROM empreendimentos_areas_comuns WHERE empreendimento_id = ?", [$empreendimento_id], "i");

        // Insere as novas seleções
        if (!empty($areas_comuns_selecionadas_data)) {
            $insert_values = [];
            $insert_types = "";
            foreach ($areas_comuns_selecionadas_data as $area_id) {
                $insert_values[] = $empreendimento_id;
                $insert_values[] = $area_id;
                $insert_types .= "ii";
            }
            $sql_insert_pivot = "INSERT INTO empreendimentos_areas_comuns (empreendimento_id, area_comum_id) VALUES " . implode(", ", array_fill(0, count($areas_comuns_selecionadas_data), "(?, ?)"));
            insert_data($sql_insert_pivot, $insert_values, $insert_types);
        }

        // Auditoria
        insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'Atualização Empreendimento Etapa 3', 'Empreendimento', $empreendimento_id, "Áreas comuns do empreendimento atualizadas na etapa 3.", $_SERVER['REMOTE_ADDR']],
                    "isssss");

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Áreas comuns salvas com sucesso!';

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao salvar Etapa 3 do empreendimento: " . $e->getMessage());
        $response['message'] = 'Erro ao salvar áreas comuns: ' . $e->getMessage();
    } finally {
        $conn->close();
    }
}

echo json_encode($response);
?>