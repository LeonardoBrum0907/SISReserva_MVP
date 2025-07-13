<?php
// api/empreendimentos/get_midias.php
// Retorna as mídias de um empreendimento específico

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php'; // Para require_permission()

header('Content-Type: application/json');

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em get_midias.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

require_permission(['admin'], true);

$response = ['success' => false, 'data' => [], 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $empreendimento_id = filter_input(INPUT_GET, 'empreendimento_id', FILTER_VALIDATE_INT);

    if (!$empreendimento_id) {
        $response['message'] = 'ID do empreendimento não fornecido.';
        echo json_encode($response);
        exit();
    }

    try {
        $midias = fetch_all("SELECT tipo, caminho_arquivo FROM midias_empreendimentos WHERE empreendimento_id = ?", [$empreendimento_id], "i");
        $response['success'] = true;
        $response['data'] = $midias;
        $response['message'] = 'Mídias carregadas com sucesso.';

    } catch (Exception $e) {
        error_log("Erro ao buscar mídias: " . $e->getMessage());
        $response['message'] = 'Erro ao carregar mídias: ' . $e->getMessage();
    } finally {
        if (isset($conn) && $conn) {
            $conn->close();
        }
    }
} else {
    $response['message'] = 'Método de requisição não permitido.';
}

echo json_encode($response);
?>