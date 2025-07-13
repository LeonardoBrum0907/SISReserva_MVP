<?php
// api/empreendimentos/salvar_etapa2.php
// Refatorado para receber o caminho da foto_planta via JSON (upload feito separadamente no frontend).

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para sanitize_input

header('Content-Type: application/json');

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em salvar_etapa2.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

require_permission(['admin'], true);

$response = ['success' => false, 'message' => 'Requisição inválida ou dados incompletos.', 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empreendimento_id = filter_input(INPUT_POST, 'empreendimento_id', FILTER_VALIDATE_INT);
    $tipos_unidade_json = $_POST['tipos_unidade_json'] ?? '[]'; // Array JSON de tipos de unidade

    if (!$empreendimento_id) {
        $response['message'] = 'ID do empreendimento não fornecido.';
        echo json_encode($response);
        exit();
    }

    $tipos_unidade_data = json_decode($tipos_unidade_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($tipos_unidade_data)) {
        $response['message'] = 'Dados de tipos de unidade inválidos (não é um JSON válido).';
        echo json_encode($response);
        exit();
    }
    if (empty($tipos_unidade_data)) {
        $response['errors']['tipos_unidade'] = 'Pelo menos um tipo de unidade deve ser adicionado.';
        $response['message'] = 'Erros de validação.';
        echo json_encode($response);
        exit();
    }

    $conn->begin_transaction();

    try {
        $existing_tipos_ids = [];
        $current_db_tipos = fetch_all("SELECT id, foto_planta FROM tipos_unidades WHERE empreendimento_id = ?", [$empreendimento_id], "i");
        $current_db_tipos_map = [];
        foreach ($current_db_tipos as $row) {
            $existing_tipos_ids[] = $row['id'];
            $current_db_tipos_map[$row['id']] = $row['foto_planta']; // Store current foto_planta path
        }

        $processed_tipos_ids = [];
        $deleted_foto_planta_paths = []; // To track paths of deleted photos for physical removal

        foreach ($tipos_unidade_data as $index => $tipo_data) {
            $id = filter_var($tipo_data['id'] ?? null, FILTER_VALIDATE_INT);
            $tipo = sanitize_input($tipo_data['tipo'] ?? '');
            $metragem = filter_var($tipo_data['metragem'] ?? 0, FILTER_VALIDATE_FLOAT);
            $quartos = filter_var($tipo_data['quartos'] ?? 0, FILTER_VALIDATE_INT);
            $banheiros = filter_var($tipo_data['banheiros'] ?? 0, FILTER_VALIDATE_INT);
            $vagas = filter_var($tipo_data['vagas'] ?? 0, FILTER_VALIDATE_INT);
            $foto_planta_path = sanitize_input($tipo_data['foto_planta'] ?? null); // Recebido via JSON

            // Validação de campos individuais
            if (empty($tipo)) $response['errors']["tipos_unidade_tipo_{$index}"] = "Tipo de unidade na linha " . ($index + 1) . " é obrigatório.";
            if ($metragem === false || $metragem <= 0) $response['errors']["tipos_unidade_metragem_{$index}"] = "Metragem na linha " . ($index + 1) . " deve ser um número positivo.";
            if ($quartos === false || $quartos < 0) $response['errors']["tipos_unidade_quartos_{$index}"] = "Quartos na linha " . ($index + 1) . " deve ser um número não negativo.";
            if ($banheiros === false || $banheiros < 0) $response['errors']["tipos_unidade_banheiros_{$index}"] = "Banheiros na linha " . ($index + 1) . " deve ser um número não negativo.";
            if ($vagas === false || $vagas < 0) $response['errors']["tipos_unidade_vagas_{$index}"] = "Vagas na linha " . ($index + 1) . " deve ser um número não negativo.";

            // Se houver erros de validação, não continue processando esta linha
            if (!empty($response['errors'])) continue;

            if ($id) {
                // Atualizar tipo de unidade existente
                // Verifica se a foto da planta foi alterada para apagar a antiga
                if (isset($current_db_tipos_map[$id]) && $current_db_tipos_map[$id] !== $foto_planta_path && !empty($current_db_tipos_map[$id])) {
                    $deleted_foto_planta_paths[] = '../../' . $current_db_tipos_map[$id];
                }

                $sql = "UPDATE tipos_unidades SET tipo = ?, metragem = ?, quartos = ?, banheiros = ?, vagas = ?, foto_planta = ?, data_atualizacao = NOW() WHERE id = ? AND empreendimento_id = ?";
                $params = [$tipo, $metragem, $quartos, $banheiros, $vagas, $foto_planta_path, $id, $empreendimento_id];
                $types = "sdiiissi";
                update_delete_data($sql, $params, $types);
                $processed_tipos_ids[] = $id;
            } else {
                // Inserir novo tipo de unidade
                $sql = "INSERT INTO tipos_unidades (empreendimento_id, tipo, metragem, quartos, banheiros, vagas, foto_planta, data_cadastro) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $params = [$empreendimento_id, $tipo, $metragem, $quartos, $banheiros, $vagas, $foto_planta_path];
                $types = "isiiiss";
                $new_id = insert_data($sql, $params, $types);
                if ($new_id) {
                    $processed_tipos_ids[] = $new_id;
                }
            }
        }

        // Remover tipos de unidade que foram deletados no frontend
        $ids_to_delete = array_diff($existing_tipos_ids, $processed_tipos_ids);
        if (!empty($ids_to_delete)) {
            // Coleta os caminhos dos arquivos a serem deletados fisicamente
            $paths_to_delete_from_db = fetch_all("SELECT foto_planta FROM tipos_unidades WHERE id IN (" . implode(',', $ids_to_delete) . ") AND empreendimento_id = ?", [$empreendimento_id], "i");
            foreach ($paths_to_delete_from_db as $row) {
                if (!empty($row['foto_planta'])) {
                    $deleted_foto_planta_paths[] = '../../' . $row['foto_planta'];
                }
            }

            $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
            $sql_delete = "DELETE FROM tipos_unidades WHERE id IN ({$placeholders}) AND empreendimento_id = ?";
            $params_delete = array_merge($ids_to_delete, [$empreendimento_id]);
            $types_delete = str_repeat('i', count($ids_to_delete)) . 'i';
            update_delete_data($sql_delete, $params_delete, $types_delete);
        }

        // Remover arquivos físicos das plantas que não são mais referenciadas
        foreach ($deleted_foto_planta_paths as $path) {
            if (file_exists($path) && is_file($path)) {
                unlink($path);
                error_log("DEBUG: Planta deletada fisicamente: " . $path);
            }
        }

        // Auditoria
        insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'Atualização Empreendimento Etapa 2', 'Empreendimento', $empreendimento_id, "Tipos de unidade do empreendimento atualizados na etapa 2.", $_SERVER['REMOTE_ADDR']],
                    "isssss");

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Tipos de unidades salvos com sucesso!';

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao salvar Etapa 2 do empreendimento: " . $e->getMessage());
        $response['message'] = 'Erro ao salvar tipos de unidade: ' . $e->getMessage();
        // Adiciona os erros de validação na resposta, se existirem
        if (!empty($response['errors'])) {
            $response['message'] .= ' Validação falhou.';
        }
    } finally {
        if (isset($conn) && $conn) {
            $conn->close();
        }
    }
}

echo json_encode($response);
?>