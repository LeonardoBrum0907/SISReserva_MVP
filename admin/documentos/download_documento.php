<?php
// admin/documentos/download_documento.php - Download Seguro de Documentos

require_once '../../includes/config.php';
require_once '../../includes/database.php'; // Para funções fetch_single
require_once '../../includes/auth.php';     // Para require_permission()

// Inicializar a conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Falha ao iniciar conexão com o DB em admin/documentos/download_documento.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Verifica se o usuário está logado e tem permissão de Admin Master
// OU se é um corretor/imobiliária associado à reserva (lógica mais complexa, para depois)
require_permission(['admin']); // Por simplicidade inicial, apenas Admin Master pode baixar.

$documentoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$documentoId) {
    die("ID do documento inválido.");
}

$sql = "SELECT nome_documento, caminho_arquivo FROM documentos_reserva WHERE id = ?";
$documento = fetch_single($sql, [$documentoId], "i");

if (!$documento) {
    die("Documento não encontrado.");
}

// Ajuste o caminho conforme sua estrutura de upload
// Exemplo: se uploads/documentos/ está na raiz do projeto, e download_documento.php está em admin/documentos/
// então ../../uploads/documentos/...
$filePath = __DIR__ . '/../../' . $documento['caminho_arquivo']; 
$fileName = $documento['nome_documento'];

// Verifica se o arquivo existe e é legível
if (file_exists($filePath) && is_readable($filePath)) {
    // Tenta determinar o tipo MIME para uma melhor experiência do navegador
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($mimeType ?: 'application/octet-stream')); // Usa o MIME type detectado ou um genérico
    header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} else {
    die("Arquivo não encontrado ou inacessível no servidor. Caminho: " . htmlspecialchars($filePath));
}
?>