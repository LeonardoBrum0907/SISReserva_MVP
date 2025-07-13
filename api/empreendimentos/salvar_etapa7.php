<?php
// api/empreendimentos/salvar_etapa7.php
// Refatorado para salvar também o campo 'documentos_necessarios'.

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em salvar_etapa7.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

require_permission(['admin'], true);

$response = ['success' => false, 'message' => 'Requisição inválida ou dados incompletos.', 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empreendimento_id = filter_input(INPUT_POST, 'empreendimento_id', FILTER_VALIDATE_INT);
    $permissoes_visualizacao_json = $_POST['permissoes_visualizacao_json'] ?? '[]';
    $permissao_reserva_tipo = sanitize_input($_POST['permissao_reserva_tipo'] ?? '');
    $corretores_permitidos_json = $_POST['corretores_permitidos_json'] ?? '[]';
    $imobiliarias_permitidas_json = $_POST['imobiliarias_permitidas_json'] ?? '[]';
    $prazo_expiracao_reserva = filter_input(INPUT_POST, 'prazo_expiracao_reserva', FILTER_VALIDATE_INT);
    $limitacao_reservas_corretor = filter_input(INPUT_POST, 'limitacao_reservas_corretor', FILTER_VALIDATE_INT);
    $limitacao_reservas_imobiliaria = filter_input(INPUT_POST, 'limitacao_reservas_imobiliaria', FILTER_VALIDATE_INT);
    // NOVO: Adiciona o campo documentos_necessarios do frontend
    $documentos_necessarios_json = $_POST['documentos_necessarios_json'] ?? '[]';


    if (!$empreendimento_id) {
        $response['message'] = 'ID do empreendimento não fornecido.';
        echo json_encode($response);
        exit();
    }

    $permissoes_visualizacao_data = json_decode($permissoes_visualizacao_json, true);
    $corretores_permitidos_data = json_decode($corretores_permitidos_json, true);
    $imobiliarias_permitidas_data = json_decode($imobiliarias_permitidas_json, true);
    $documentos_necessarios_data = json_decode($documentos_necessarios_json, true); // NOVO: Decodifica


    // Validações
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($permissoes_visualizacao_data) || empty($permissoes_visualizacao_data)) {
        $response['errors']['permissoes_visualizacao'] = 'Selecione pelo menos uma permissão de visualização.';
    }
    $allowed_perm_reserva_types = ['Todos', 'Corretores Selecionados', 'Imobiliarias Selecionadas'];
    if (!in_array($permissao_reserva_tipo, $allowed_perm_reserva_types)) {
        $response['errors']['permissao_reserva_tipo'] = 'Tipo de permissão de reserva inválido.';
    }

    if ($permissao_reserva_tipo === 'Corretores Selecionados') {
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($corretores_permitidos_data) || empty($corretores_permitidos_data)) {
            $response['errors']['corretores_permitidos'] = 'Selecione pelo menos um corretor permitido para reserva.';
        }
    } elseif ($permissao_reserva_tipo === 'Imobiliarias Selecionadas') {
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($imobiliarias_permitidas_data) || empty($imobiliarias_permitidas_data)) {
            $response['errors']['imobiliarias_permitidas'] = 'Selecione pelo menos uma imobiliária permitida para reserva.';
        }
    }

    if ($prazo_expiracao_reserva === false || $prazo_expiracao_reserva <= 0) $response['errors']['prazo_expiracao_reserva'] = 'Prazo de expiração deve ser um número inteiro positivo.';
    if ($limitacao_reservas_corretor !== false && $limitacao_reservas_corretor < 0) $response['errors']['limitacao_reservas_corretor'] = 'Limitação de reservas por corretor deve ser um número não negativo.';
    if ($limitacao_reservas_imobiliaria !== false && $limitacao_reservas_imobiliaria < 0) $response['errors']['limitacao_reservas_imobiliaria'] = 'Limitação de reservas por imobiliária deve ser um número não negativo.';

    // NOVO: Validação para documentos_necessarios_json
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($documentos_necessarios_data)) {
        $response['errors']['documentos_necessarios'] = 'Dados de documentos necessários em formato inválido.';
    }


    if (!empty($response['errors'])) {
        $response['message'] = 'Erros de validação encontrados.';
        echo json_encode($response);
        exit();
    }

    $conn->begin_transaction();

    try {
        // Atualiza campos em 'empreendimentos', incluindo 'documentos_necessarios'
        $sql_update_emp = "UPDATE empreendimentos SET 
                            permissoes_visualizacao = ?, 
                            permissao_reserva = ?, 
                            limitacao_reservas_corretor = ?, 
                            limitacao_reservas_imobiliaria = ?, 
                            prazo_expiracao_reserva = ?, 
                            documentos_necessarios = ?, -- NOVO CAMPO
                            data_atualizacao = NOW() 
                        WHERE id = ?";
        $params = [
            json_encode($permissoes_visualizacao_data), 
            $permissao_reserva_tipo, 
            ($limitacao_reservas_corretor ?: null), 
            ($limitacao_reservas_imobiliaria ?: null), 
            $prazo_expiracao_reserva, 
            json_encode($documentos_necessarios_data), // NOVO DADO
            $empreendimento_id
        ];
        $types = "ssiiisi"; // Ajuste os tipos conforme seus campos: 2 's', 2 'i', 1 'i', 1 's', 1 'i' = 7 caracteres
        update_delete_data($sql_update_emp, $params, $types);

        // Sincroniza tabela pivô 'empreendimentos_corretores_permitidos'
        update_delete_data("DELETE FROM empreendimentos_corretores_permitidos WHERE empreendimento_id = ?", [$empreendimento_id], "i");
        if ($permissao_reserva_tipo === 'Corretores Selecionados' && !empty($corretores_permitidos_data)) {
            $insert_values = [];
            $insert_types = "";
            foreach ($corretores_permitidos_data as $corretor_id) {
                $insert_values[] = $empreendimento_id;
                $insert_values[] = $corretor_id;
                $insert_types .= "ii";
            }
            $sql_insert_pivot = "INSERT INTO empreendimentos_corretores_permitidos (empreendimento_id, corretor_id) VALUES " . implode(", ", array_fill(0, count($corretores_permitidos_data), "(?, ?)"));
            insert_data($sql_insert_pivot, $insert_values, $insert_types);
        }

        // Sincroniza tabela pivô 'empreendimentos_imobiliarias_permitidas'
        update_delete_data("DELETE FROM empreendimentos_imobiliarias_permitidas WHERE empreendimento_id = ?", [$empreendimento_id], "i");
        if ($permissao_reserva_tipo === 'Imobiliarias Selecionadas' && !empty($imobiliarias_permitidas_data)) {
            $insert_values = [];
            $insert_types = "";
            foreach ($imobiliarias_permitidas_data as $imobiliaria_id) {
                $insert_values[] = $empreendimento_id;
                $insert_values[] = $imobiliaria_id;
                $insert_types .= "ii";
            }
            $sql_insert_pivot = "INSERT INTO empreendimentos_imobiliarias_permitidas (empreendimento_id, imobiliaria_id) VALUES " . implode(", ", array_fill(0, count($imobiliarias_permitidas_data), "(?, ?)"));
            insert_data($sql_insert_pivot, $insert_values, $insert_types);
        }

        // Auditoria
        insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'Atualização Empreendimento Etapa 7', 'Empreendimento', $empreendimento_id, "Regras e permissões do empreendimento atualizadas na etapa 7.", $_SERVER['REMOTE_ADDR']],
                    "isssss");

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Regras e permissões salvas com sucesso! Empreendimento finalizado!';

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao salvar Etapa 7 do empreendimento: " . $e->getMessage());
        $response['message'] = 'Erro ao salvar regras e permissões: ' . $e->getMessage();
    } finally {
        if (isset($conn) && $conn) {
            $conn->close();
        }
    }
}

echo json_encode($response);
?>