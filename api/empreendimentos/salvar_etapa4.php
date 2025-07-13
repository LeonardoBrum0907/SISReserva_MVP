<?php
// api/empreendimentos/salvar_etapa4.php - Salva os dados de Unidades (Estoque)
// api/empreendimentos/salvar_etapa4.php - Salva os dados de Unidades (Estoque)
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para sanitize_input
require_once '../../includes/helpers.php'; // Para sanitize_input
header('Content-Type: application/json');
global $conn;


try {
    $conn = get_db_connection();
    // Loga o nome do banco de dados ao qual o PHP está conectado
    $current_db_result = $conn->query("SELECT DATABASE() AS db_name");
    if ($current_db_result) {
        $current_db = $current_db_result->fetch_assoc()['db_name'];
        error_log("DEBUG: PHP conectado ao banco de dados: " . $current_db);
        $current_db_result->free();
    } else {
        error_log("DEBUG: Erro ao obter nome do banco de dados: " . $conn->error);
    }
    // Verifica se a coluna 'andar' existe na tabela 'unidades' NESTA CONEXÃO
    $table_check_sql = "SHOW COLUMNS FROM unidades LIKE 'andar'";
    $stmt_check = $conn->prepare($table_check_sql);
    if ($stmt_check) {
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            error_log("DEBUG: Coluna 'andar' encontrada na tabela 'unidades' NESTA CONEXÃO.");
        } else {
            error_log("DEBUG: Coluna 'andar' NÃO encontrada na tabela 'unidades' NESTA CONEXÃO.");
        }
        $stmt_check->close();
    } else {
        error_log("DEBUG: Erro ao preparar SHOW COLUMNS FROM unidades: " . $conn->error);
    }
    // Loga o nome do banco de dados ao qual o PHP está conectado
    $current_db_result = $conn->query("SELECT DATABASE() AS db_name");
    if ($current_db_result) {
        $current_db = $current_db_result->fetch_assoc()['db_name'];
        error_log("DEBUG: PHP conectado ao banco de dados: " . $current_db);
        $current_db_result->free();
    } else {
        error_log("DEBUG: Erro ao obter nome do banco de dados: " . $conn->error);
    }
    // Verifica se a coluna 'andar' existe na tabela 'unidades' NESTA CONEXÃO
    $table_check_sql = "SHOW COLUMNS FROM unidades LIKE 'andar'";
    $stmt_check = $conn->prepare($table_check_sql);
    if ($stmt_check) {
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            error_log("DEBUG: Coluna 'andar' encontrada na tabela 'unidades' NESTA CONEXÃO.");
        } else {
            error_log("DEBUG: Coluna 'andar' NÃO encontrada na tabela 'unidades' NESTA CONEXÃO.");
        }
        $stmt_check->close();
    } else {
        error_log("DEBUG: Erro ao preparar SHOW COLUMNS FROM unidades: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Erro de conexão DB em salvar_etapa4.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

require_permission(['admin'], true);

$response = ['success' => false, 'message' => 'Requisição inválida ou dados incompletos.', 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empreendimento_id = filter_input(INPUT_POST, 'empreendimento_id', FILTER_VALIDATE_INT);
    $unidades_json = $_POST['unidades_json'] ?? '[]';
    $num_andares = filter_input(INPUT_POST, 'num_andares', FILTER_VALIDATE_INT);
    $unidades_por_andar_json = $_POST['unidades_por_andar_json'] ?? '[]';

    if (!$empreendimento_id) {
        $response['message'] = 'ID do empreendimento não fornecido ou inválido.';
        $response['message'] = 'ID do empreendimento não fornecido ou inválido.';
        echo json_encode($response);
        exit();
    }

    $unidades_data = json_decode($unidades_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($unidades_data)) {
        $response['message'] = 'Dados de unidades em formato inválido (JSON inválido).';
        echo json_encode($response);
        exit();
    }

    $unidades_por_andar_data = json_decode($unidades_por_andar_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($unidades_por_andar_data)) {
        $response['message'] = 'Dados de unidades por andar em formato inválido (JSON inválido).';
        $response['message'] = 'Dados de unidades em formato inválido (JSON inválido).';
        echo json_encode($response);
        exit();
    }

    $unidades_por_andar_data = json_decode($unidades_por_andar_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($unidades_por_andar_data)) {
        $response['message'] = 'Dados de unidades por andar em formato inválido (JSON inválido).';
        echo json_encode($response);
        exit();
    }

    // Validação dos dados das unidades (simplificada para o contexto)
    foreach ($unidades_data as $index => $unidade) {
        if (empty($unidade['tipo_unidade_id'])) {
            $response['errors']["unidade_{$index}_tipo_unidade_id"] = "Tipo de unidade é obrigatório para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (empty($unidade['numero'])) {
            $response['errors']["unidade_{$index}_numero"] = "Número da unidade é obrigatório para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (!isset($unidade['andar']) || !is_numeric($unidade['andar'])) {
            $response['errors']["unidade_{$index}_andar"] = "Andar é obrigatório e deve ser um número para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (!isset($unidade['area']) || !is_numeric($unidade['area']) || $unidade['area'] <= 0) {
            $response['errors']["unidade_{$index}_area"] = "Área é obrigatória e deve ser um número positivo para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (!isset($unidade['multiplier']) || !is_numeric($unidade['multiplier']) || $unidade['multiplier'] <= 0) {
            $response['errors']["unidade_{$index}_multiplier"] = "Multiplicador é obrigatório e deve ser um número positivo para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (!isset($unidade['valor']) || !is_numeric($unidade['valor']) || $unidade['valor'] <= 0) {
            $response['errors']["unidade_{$index}_valor"] = "Valor é obrigatório e deve ser um número positivo para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
    }

    if (!empty($response['errors'])) {
        $response['message'] = 'Erros de validação encontrados para as unidades.';

    // Validação dos dados das unidades (simplificada para o contexto)
    foreach ($unidades_data as $index => $unidade) {
        if (empty($unidade['tipo_unidade_id'])) {
            $response['errors']["unidade_{$index}_tipo_unidade_id"] = "Tipo de unidade é obrigatório para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (empty($unidade['numero'])) {
            $response['errors']["unidade_{$index}_numero"] = "Número da unidade é obrigatório para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (!isset($unidade['andar']) || !is_numeric($unidade['andar'])) {
            $response['errors']["unidade_{$index}_andar"] = "Andar é obrigatório e deve ser um número para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (!isset($unidade['area']) || !is_numeric($unidade['area']) || $unidade['area'] <= 0) {
            $response['errors']["unidade_{$index}_area"] = "Área é obrigatória e deve ser um número positivo para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (!isset($unidade['multiplier']) || !is_numeric($unidade['multiplier']) || $unidade['multiplier'] <= 0) {
            $response['errors']["unidade_{$index}_multiplier"] = "Multiplicador é obrigatório e deve ser um número positivo para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
        if (!isset($unidade['valor']) || !is_numeric($unidade['valor']) || $unidade['valor'] <= 0) {
            $response['errors']["unidade_{$index}_valor"] = "Valor é obrigatório e deve ser um número positivo para a unidade " . ($unidade['numero'] ?? $index + 1) . ".";
        }
    }

    if (!empty($response['errors'])) {
        $response['message'] = 'Erros de validação encontrados para as unidades.';
        echo json_encode($response);
        exit();
    }

    $conn->begin_transaction();
    try {
        // Atualiza o número total de andares e unidades por andar no empreendimento
        $update_empreendimento_sql = "UPDATE empreendimentos SET andar = ?, unidades_por_andar = ?, data_atualizacao = NOW() WHERE id = ?";
        error_log("DEBUG: Empreendimento UPDATE SQL: " . $update_empreendimento_sql);
        error_log("DEBUG: Empreendimento UPDATE Params: " . json_encode([$andar, json_encode($unidades_por_andar_data), $empreendimento_id]));
        error_log("DEBUG: Empreendimento UPDATE Types: isi");
        $stmt_empreendimento_result = update_delete_data($update_empreendimento_sql, [$andar, json_encode($unidades_por_andar_data), $empreendimento_id], "isi");
        if ($stmt_empreendimento_result === false) {
             throw new Exception("Erro ao atualizar dados de andares e unidades por andar no empreendimento.");
        }

        // IDs das unidades existentes para verificar quais foram removidas
        $existing_unit_ids = fetch_all("SELECT id FROM unidades WHERE empreendimento_id = ?", [$empreendimento_id], "i");
        $existing_unit_ids = array_column($existing_unit_ids, 'id');
        $updated_or_inserted_ids = [];

        foreach ($unidades_data as $unidade) {
            $unidade_id = $unidade['id'] ?? null;
            // Garante que tipo_unidade_id e andar são inteiros
            $tipo_unidade_id = (int)$unidade['tipo_unidade_id'];
            $numero = $unidade['numero'];
            $andar = (int)$unidade['andar'];
            $posicao = $unidade['posicao'];
            $area = $unidade['area'];
            $multiplier = $unidade['multiplier'];
            $valor = $unidade['valor'];
        // IDs das unidades existentes para verificar quais foram removidas
        $existing_unit_ids = fetch_all("SELECT id FROM unidades WHERE empreendimento_id = ?", [$empreendimento_id], "i");
        $existing_unit_ids = array_column($existing_unit_ids, 'id');
        $updated_or_inserted_ids = [];

        foreach ($unidades_data as $unidade) {
            $unidade_id = $unidade['id'] ?? null;
            // Garante que tipo_unidade_id e andar são inteiros
            $tipo_unidade_id = (int)$unidade['tipo_unidade_id'];
            $numero = $unidade['numero'];
            $andar = (int)$unidade['andar'];
            $posicao = $unidade['posicao'];
            $area = $unidade['area'];
            $multiplier = $unidade['multiplier'];
            $valor = $unidade['valor'];

            if ($unidade_id) {
            if ($unidade_id) {
                // Atualizar unidade existente
                $sql = "UPDATE unidades SET
                            tipo_unidade_id = ?, numero = ?, andar = ?, posicao = ?, area = ?, multiplier = ?, valor = ?, data_atualizacao = NOW()
                        WHERE id = ? AND empreendimento_id = ?";
                // Tipos: i (tipo_unidade_id), s (numero), i (andar), s (posicao), d (area), d (multiplier), d (valor), i (unidade_id), i (empreendimento_id)
                $params = [$tipo_unidade_id, $numero, $andar, $posicao, $area, $multiplier, $valor, $unidade_id, $empreendimento_id];
                $types = "isidsddii"; // 9 parâmetros, 9 tipos - CORRETO
                $result = update_delete_data($sql, $params, $types);
                if ($result === false) { throw new Exception("Erro ao atualizar a unidade {$numero}."); }
                $updated_or_inserted_ids[] = $unidade_id;
                $sql = "UPDATE unidades SET
                            tipo_unidade_id = ?, numero = ?, andar = ?, posicao = ?, area = ?, multiplier = ?, valor = ?, data_atualizacao = NOW()
                        WHERE id = ? AND empreendimento_id = ?";
                // Tipos: i (tipo_unidade_id), s (numero), i (andar), s (posicao), d (area), d (multiplier), d (valor), i (unidade_id), i (empreendimento_id)
                $params = [$tipo_unidade_id, $numero, $andar, $posicao, $area, $multiplier, $valor, $unidade_id, $empreendimento_id];
                $types = "isidsddii"; // 9 parâmetros, 9 tipos - CORRETO
                $result = update_delete_data($sql, $params, $types);
                if ($result === false) { throw new Exception("Erro ao atualizar a unidade {$numero}."); }
                $updated_or_inserted_ids[] = $unidade_id;
            } else {
                // Inserir nova unidade
                $sql = "INSERT INTO unidades (empreendimento_id, tipo_unidade_id, numero, andar, posicao, area, multiplier, valor, data_cadastro, data_atualizacao)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                // Tipos: i (empreendimento_id), i (tipo_unidade_id), s (numero), i (andar), s (posicao), d (area), d (multiplier), d (valor)
                $sql = "INSERT INTO unidades (empreendimento_id, tipo_unidade_id, numero, andar, posicao, area, multiplier, valor, data_cadastro, data_atualizacao)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                // Tipos: i (empreendimento_id), i (tipo_unidade_id), s (numero), i (andar), s (posicao), d (area), d (multiplier), d (valor)
                $params = [$empreendimento_id, $tipo_unidade_id, $numero, $andar, $posicao, $area, $multiplier, $valor];
                $types = "iisidsdd"; // 8 parâmetros, 8 tipos - CORRIGIDO
                $types = "iisidsdd"; // 8 parâmetros, 8 tipos - CORRIGIDO
                $new_id = insert_data($sql, $params, $types);
                if (!$new_id) { throw new Exception("Erro ao inserir a unidade {$numero}."); }
                $updated_or_inserted_ids[] = $new_id;
            }
                if (!$new_id) { throw new Exception("Erro ao inserir a unidade {$numero}."); }
                $updated_or_inserted_ids[] = $new_id;
            }
        }

        // Remover unidades que não estão mais na lista
        $ids_to_remove = array_diff($existing_unit_ids, $updated_or_inserted_ids);
        if (!empty($ids_to_remove)) {
            $placeholders = implode(',', array_fill(0, count($ids_to_remove), '?'));
            $delete_sql = "DELETE FROM unidades WHERE id IN ({$placeholders}) AND empreendimento_id = ?";
            $delete_params = array_merge($ids_to_remove, [$empreendimento_id]);
            $delete_types = str_repeat('i', count($ids_to_remove)) . 'i';
            $result = update_delete_data($delete_sql, $delete_params, $delete_types);
            if ($result === false) { throw new Exception("Erro ao remover unidades antigas."); }
        }
        // Remover unidades que não estão mais na lista
        $ids_to_remove = array_diff($existing_unit_ids, $updated_or_inserted_ids);
        if (!empty($ids_to_remove)) {
            $placeholders = implode(',', array_fill(0, count($ids_to_remove), '?'));
            $delete_sql = "DELETE FROM unidades WHERE id IN ({$placeholders}) AND empreendimento_id = ?";
            $delete_params = array_merge($ids_to_remove, [$empreendimento_id]);
            $delete_types = str_repeat('i', count($ids_to_remove)) . 'i';
            $result = update_delete_data($delete_sql, $delete_params, $delete_types);
            if ($result === false) { throw new Exception("Erro ao remover unidades antigas."); }
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Estoque de unidades salvo com sucesso!';
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao salvar Etapa 4 do empreendimento: " . $e->getMessage());
        $response['message'] = 'Erro ao salvar estoque de unidades: ' . $e->getMessage();
    } finally {
        $conn->close();
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);

