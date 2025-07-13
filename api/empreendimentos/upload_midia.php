<?php
// SISReserva_MVP/api/empreendimentos/upload_midia.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php'; // Para require_permission()

header('Content-Type: application/json');

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em upload_midia.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

require_permission(['admin'], true); // Requer permissão de admin para upload via AJAX

$response = ['success' => false, 'message' => '', 'file_url' => '', 'file_name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['midia_file']) || $_FILES['midia_file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Nenhum arquivo enviado ou erro no upload. Código: ' . ($_FILES['midia_file']['error'] ?? 'N/A');
        echo json_encode($response);
        exit();
    }

    $file = $_FILES['midia_file'];
    
    // Define diretórios de upload baseados no tipo do arquivo
    $upload_base_dir = '../../uploads/empreendimentos/';
    $upload_file_path = ''; // Caminho final a ser salvo no DB

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_image_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_doc_ext = ['pdf', 'doc', 'docx', 'xlsx', 'xls', 'txt']; // Adicione outros se necessário

    // Determine o subdiretório e tipo de mídia a ser salvo
    $media_type = '';
    $unique_filename_prefix = '';

    if (in_array($file_extension, $allowed_image_ext)) {
        $upload_dir = $upload_base_dir . 'imagens/';
        $media_type = 'imagem'; // Tipo genérico para a tabela midias_empreendimentos
        $unique_filename_prefix = 'img_';
    } elseif (in_array($file_extension, $allowed_doc_ext)) {
        // Pode ser mais específico aqui se precisar separar contratos de memoriais
        $upload_dir = $upload_base_dir . 'documentos/';
        $media_type = 'documento'; // Tipo genérico para a tabela midias_empreendimentos
        $unique_filename_prefix = 'doc_';
    } else {
        $response['message'] = 'Tipo de arquivo não permitido.';
        echo json_encode($response);
        exit();
    }

    // Cria o diretório se não existir
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $response['message'] = 'Falha ao criar diretório de upload: ' . $upload_dir;
            echo json_encode($response);
            exit();
        }
    }

    $new_file_name = uniqid($unique_filename_prefix) . '.' . $file_extension;
    $destination_path = $upload_dir . $new_file_name;
    
    // Caminho relativo para salvar no banco de dados e usar na web
    // Ex: uploads/empreendimentos/imagens/img_uniqueid.jpg
    $public_file_path = str_replace('../../', '', $destination_path); 

    if (move_uploaded_file($file['tmp_name'], $destination_path)) {
        $response['success'] = true;
        $response['message'] = 'Arquivo enviado com sucesso.';
        $response['file_url'] = BASE_URL . $public_file_path; // URL completa para preview no frontend
        $response['file_path_db'] = $public_file_path; // Caminho para salvar no DB
        $response['file_name'] = $new_file_name;
        $response['media_type'] = $media_type; // Pode ser útil para o JS
    } else {
        $response['message'] = 'Falha ao mover o arquivo enviado. Verifique permissões do diretório: ' . $upload_dir;
    }
} else {
    $response['message'] = 'Método de requisição não permitido.';
}

echo json_encode($response);
?>