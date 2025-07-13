<?php
header('Content-Type: application/json'); // Retorna JSON

require_once '../includes/config.php'; // Para BASE_URL (se necessário para logs ou outros)
require_once '../includes/database.php'; // Inclui as funções de banco de dados

try {
    global $conn; // Declara que você vai usar a variável global $conn
    $conn = get_db_connection(); // Chama a função para obter a conexão
} catch (Exception $e) {
    // Em um endpoint de API, você pode retornar um erro JSON em vez de um die() HTML
    error_log("Erro crítico na inicialização do DB em api/cep.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Serviço temporariamente indisponível.']);
    exit(); // Termina a execução do script
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Inicialização da variável de resposta
$response = ['success' => false, 'data' => null, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['cep'])) {
    $cep = filter_input(INPUT_GET, 'cep', FILTER_UNSAFE_RAW); // CORREÇÃO: Usar FILTER_UNSAFE_RAW
    $cep = preg_replace('/[^0-9]/', '', $cep); // Remove caracteres não numéricos

    if (strlen($cep) !== 8) {
        $response['message'] = 'CEP inválido. Deve conter 8 dígitos.';
        echo json_encode($response);
        exit();
    }

    $url_viacep = "https://viacep.com.br/ws/{$cep}/json/"; // Usar HTTPS

    // Inicializa uma sessão cURL
    $ch = curl_init();

    // Define as opções para a requisição cURL
    curl_setopt($ch, CURLOPT_URL, $url_viacep);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Retorna o resultado como string
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Segue redirecionamentos
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos
    
    // Executa a requisição
    $json_response = curl_exec($ch);

    // Verifica por erros cURL
    if (curl_errno($ch)) {
        $response['message'] = 'Erro ao consultar ViaCEP: ' . curl_error($ch);
        error_log("cURL Error (api/cep.php): " . curl_error($ch));
        echo json_encode($response);
        curl_close($ch);
        exit();
    }

    // Fecha a sessão cURL
    curl_close($ch);

    $data = json_decode($json_response, true);

    if (isset($data['erro'])) {
        $response['message'] = 'CEP não encontrado ou erro na consulta.';
    } else {
        $response['success'] = true;
        $response['data'] = [
            'logradouro' => $data['logradouro'] ?? '',
            'bairro' => $data['bairro'] ?? '',
            'localidade' => $data['localidade'] ?? '', // Cidade
            'uf' => $data['uf'] ?? '' // Estado
        ];
    }

} else {
    $response['message'] = 'Requisição inválida. CEP não fornecido.';
}

echo json_encode($response);