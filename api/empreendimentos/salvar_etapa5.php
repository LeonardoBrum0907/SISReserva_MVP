<?php
// api/empreendimentos/salvar_etapa5.php
// Responsável por salvar as mídias (fotos, vídeos, documentos) de um empreendimento.
// Refatorado para lidar com novos tipos de mídias e garantir persistência correta.
// Adicionado logging mais detalhado para problemas de move_uploaded_file.

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para funções como generate_unique_filename
require_once '../../includes/helpers.php'; // Para funções como generate_unique_filename

header('Content-Type: application/json');

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro de conexão DB em salvar_etapa5.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao conectar ao banco de dados.']);
    exit();
}

require_permission(['admin'], true);

$response = ['success' => false, 'message' => '', 'data' => []];
$response = ['success' => false, 'message' => '', 'data' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empreendimento_id = filter_input(INPUT_POST, 'empreendimento_id', FILTER_VALIDATE_INT);
    $midias_existentes_json = $_POST['midias_existentes_json'] ?? '[]';
    $videos_youtube_json = $_POST['videos_youtube_json'] ?? '[]';

    $midias_do_form = json_decode($midias_existentes_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($midias_do_form)) {
        $response['message'] = 'Dados de mídias existentes inválidos (JSON inválido ou não é array): ' . json_last_error_msg();
        echo json_encode($response);
        $conn->close();
        $conn->close();
        exit();
    }

    $videos_youtube_urls_from_form = json_decode($videos_youtube_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($videos_youtube_urls_from_form)) {
        $response['message'] = 'Dados de vídeos YouTube enviados em formato inválido.';
        echo json_encode($response);
        $conn->close();
        $conn->close();
        exit();
    }
    $videos_youtube_urls_from_form = array_filter(array_map('trim', $videos_youtube_urls_from_form));


    if (!$empreendimento_id) {
        $response['message'] = 'ID do empreendimento não fornecido.';
        echo json_encode($response);
        $conn->close();
        $conn->close();
        exit();
    }

    $upload_base_dir = '../../uploads/empreendimentos/' . $empreendimento_id . '/';
    $upload_sub_dirs = [
        'foto_principal' => $upload_base_dir . 'principais/',
        'galeria_foto' => $upload_base_dir . 'galeria/',
        'documento_contrato' => $upload_base_dir . 'documentos/',
        'documento_memorial' => $upload_base_dir . 'documentos/',
        'explore_video_thumb' => $upload_base_dir . 'thumbs/',
        'explore_gallery_thumb' => $upload_base_dir . 'thumbs/',
    ];

    foreach ($upload_sub_dirs as $dir) {
        if (!is_dir($dir)) {
            // Log do erro de criação de diretório, se ocorrer
            if (!mkdir($dir, 0777, true)) {
                $response['message'] = 'Erro: Não foi possível criar o diretório de upload: ' . $dir;
                error_log("Erro ao criar diretório: " . $dir);
                echo json_encode($response);
                $conn->close();
                exit();
            }
        }
    }

    $conn->begin_transaction();
    try {
        $current_midias_in_db = fetch_all("SELECT id, caminho_arquivo, tipo FROM midias_empreendimentos WHERE empreendimento_id = ?", [$empreendimento_id], "i");
        $current_midias_map_by_path_and_type = [];
        foreach ($current_midias_in_db as $midia) {
            $current_midias_map_by_path_and_type[$midia['caminho_arquivo'] . '|' . $midia['tipo']] = $midia['id'];
        }

        $ids_to_keep_in_db = [];

        $file_input_map = [
            'foto_principal' => 'foto_principal',
            'documento_contrato' => 'documento_contrato',
            'documento_memorial' => 'documento_memorial',
            'explore_video_thumb' => 'explore_video_thumb',
            'explore_gallery_thumb' => 'explore_gallery_thumb',
        ];

        foreach ($file_input_map as $input_name => $media_type) {
            if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$input_name];
                $unique_filename = generate_unique_filename($file['name']);
                $destination_path = $upload_sub_dirs[$media_type] . $unique_filename;
                $relative_path_for_db = str_replace('../../', '', $destination_path);

                // --- LOGGING ADICIONAL PARA DIAGNÓSTICO ---
                error_log("DEBUG upload_midia: Tentando mover arquivo '{$file['tmp_name']}' para '{$destination_path}' (media_type: {$media_type}).");
                // --- FIM DO LOGGING ADICIONAL ---

                if (move_uploaded_file($file['tmp_name'], $destination_path)) {
                    $existing_midia_id_for_type = fetch_single("SELECT id FROM midias_empreendimentos WHERE empreendimento_id = ? AND tipo = ?", [$empreendimento_id, $media_type], "is");
                    
                    if ($existing_midia_id_for_type) {
                        $old_path_row = fetch_single("SELECT caminho_arquivo FROM midias_empreendimentos WHERE id = ?", [$existing_midia_id_for_type['id']], "i");
                        if ($old_path_row && file_exists('../../' . $old_path_row['caminho_arquivo'])) {
                            unlink('../../' . $old_path_row['caminho_arquivo']);
                        }
                        update_delete_data("UPDATE midias_empreendimentos SET caminho_arquivo = ?, data_upload = NOW() WHERE id = ?", [$relative_path_for_db, $existing_midia_id_for_type['id']], "si");
                        $ids_to_keep_in_db[] = $existing_midia_id_for_type['id'];
                    } else {
                        $new_midia_id = insert_data("INSERT INTO midias_empreendimentos (empreendimento_id, tipo, caminho_arquivo, data_upload) VALUES (?, ?, ?, NOW())",
                                                    [$empreendimento_id, $media_type, $relative_path_for_db], "iss");
                        if ($new_midia_id) $ids_to_keep_in_db[] = $new_midia_id;
                    }
                    error_log("DEBUG upload_midia: Arquivo '{$unique_filename}' movido com SUCESSO para '{$destination_path}'.");
                } else {
                    // Logging detalhado se move_uploaded_file falhar
                    $php_error = error_get_last();
                    error_log("ERRO FATAL move_uploaded_file para '{$file['name']}' (media_type: {$media_type}). Destination: '{$destination_path}'. PHP Error: " . ($php_error['message'] ?? 'N/A') . ".");
                    throw new Exception("Erro ao mover o arquivo {$file['name']} para '{$media_type}'. Por favor, verifique as permissões da pasta 'uploads/empreendimentos/{$empreendimento_id}/'.");
                }
            } else if (isset($_FILES[$input_name]['error']) && $_FILES[$input_name]['error'] !== UPLOAD_ERR_NO_FILE) {
                // Este bloco será executado apenas se houver um erro de upload que não seja UPLOAD_ERR_OK ou UPLOAD_ERR_NO_FILE
                error_log("Erro de upload de arquivo para input '{$input_name}': Código " . $_FILES[$input_name]['error'] . ". Nome do arquivo: " . ($_FILES[$input_name]['name'] ?? 'N/A'));
                throw new Exception("Erro no upload do arquivo {$input_name}. Código: " . $_FILES[$input_name]['error']);
            }
        }

        // Processar múltiplas galerias de fotos (mantido como estava)
        if (isset($_FILES['galeria_fotos']) && is_array($_FILES['galeria_fotos']['name'])) {
            $gallery_upload_dir = $upload_sub_dirs['galeria_foto'];
            foreach ($_FILES['galeria_fotos']['name'] as $idx => $name) {
                if ($_FILES['galeria_fotos']['error'][$idx] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $_FILES['galeria_fotos']['tmp_name'][$idx];
                    $unique_filename = generate_unique_filename($name);
                    $destination_path = $gallery_upload_dir . $unique_filename;
                    $relative_path_for_db = str_replace('../../', '', $destination_path);

                    // --- LOGGING ADICIONAL PARA DIAGNÓSTICO ---
                    error_log("DEBUG upload_midia: Tentando mover arquivo de galeria '{$file_tmp_name}' para '{$destination_path}'.");
                    // --- FIM DO LOGGING ADICIONAL ---

                    if (move_uploaded_file($file_tmp_name, $destination_path)) {
                        $new_midia_id = insert_data("INSERT INTO midias_empreendimentos (empreendimento_id, tipo, caminho_arquivo, data_upload) VALUES (?, ?, ?, NOW())",
                                                    [$empreendimento_id, 'galeria_foto', $relative_path_for_db], "iss");
                        if ($new_midia_id) $ids_to_keep_in_db[] = $new_midia_id;
                        error_log("DEBUG upload_midia: Arquivo de galeria '{$unique_filename}' movido com SUCESSO para '{$destination_path}'.");
                    } else {
                        // Logging detalhado se move_uploaded_file falhar
                        $php_error = error_get_last();
                        error_log("ERRO FATAL move_uploaded_file para galeria '{$name}'. Destination: '{$destination_path}'. PHP Error: " . ($php_error['message'] ?? 'N/A') . ".");
                        throw new Exception("Erro ao mover arquivo de galeria: {$name}. Por favor, verifique as permissões da pasta 'uploads/empreendimentos/{$empreendimento_id}/galeria/'.");
                    }
                } else if ($_FILES['galeria_fotos']['error'][$idx] !== UPLOAD_ERR_NO_FILE) {
                    error_log("Erro de upload para galeria_fotos[{$idx}]: Código " . $_FILES['galeria_fotos']['error'][$idx]);
                    throw new Exception("Erro no upload do arquivo de galeria {$name}.");
                }
            }
        }

        // Processar URLs de Vídeo (manter existentes, adicionar novas) - Mantido como estava
        // ... (código existente para vídeos do YouTube) ...
        $current_videos_in_db = fetch_all("SELECT id, caminho_arquivo FROM midias_empreendimentos WHERE empreendimento_id = ? AND tipo = 'video'", [$empreendimento_id], "i");
        $current_video_urls_map_by_path = [];
        foreach ($current_videos_in_db as $video) {
            $current_video_urls_map_by_path[$video['caminho_arquivo']] = $video['id'];
        }

        foreach ($videos_youtube_urls_from_form as $video_url) {
            if (empty($video_url)) continue;

            $youtube_id = '';
            if (preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|watch\?v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $matches)) {
                $youtube_id = $matches[1];
            } else {
                if (strlen($video_url) === 11 && ctype_alnum(str_replace(['-', '_'], '', $video_url))) {
                    $youtube_id = $video_url;
                } else {
                    error_log("URL de vídeo do YouTube inválida ignorada: " . htmlspecialchars($video_url));
                    continue;
                }
            }
            
            if (isset($current_video_urls_map_by_path[$youtube_id])) {
                $ids_to_keep_in_db[] = $current_video_urls_map_by_path[$youtube_id];
                unset($current_video_urls_map_by_path[$youtube_id]);
            } else {
                $new_midia_id = insert_data("INSERT INTO midias_empreendimentos (empreendimento_id, tipo, caminho_arquivo, data_upload) VALUES (?, ?, ?, NOW())",
                                            [$empreendimento_id, 'video', $youtube_id], "iss");
                if ($new_midia_id) $ids_to_keep_in_db[] = $new_midia_id;
            }
        }
        
        foreach ($midias_do_form as $midia_item) {
            if (isset($midia_item['caminho_arquivo']) && isset($midia_item['tipo'])) {
                 $composed_key = $midia_item['caminho_arquivo'] . '|' . $midia_item['tipo'];
                if (isset($current_midias_map_by_path_and_type[$composed_key])) {
                     $ids_to_keep_in_db[] = $current_midias_map_by_path_and_type[$composed_key];
                }
            }
        }
        $ids_to_keep_in_db = array_unique($ids_to_keep_in_db);

        $midias_a_remover_ids = [];
        foreach ($current_midias_in_db as $midia_db) {
            if (!in_array($midia_db['id'], $ids_to_keep_in_db)) {
                $midias_a_remover_ids[] = $midia_db['id'];
            }
        }

        if (!empty($midias_a_remover_ids)) {
            $placeholders = implode(',', array_fill(0, count($midias_a_remover_ids), '?'));
            $stmt_delete_db = $conn->prepare("DELETE FROM midias_empreendimentos WHERE id IN ({$placeholders}) AND empreendimento_id = ?");
            if (!$stmt_delete_db) {
                throw new Exception("Erro ao preparar exclusão de mídias antigas: " . $conn->error);
            }
            $params_delete = array_merge($midias_a_remover_ids, [$empreendimento_id]);
            $types_delete = str_repeat('i', count($midias_a_remover_ids)) . 'i';
            $stmt_delete_db->bind_param($types_delete, ...$params_delete);
            $stmt_delete_db->execute();
            if ($stmt_delete_db->error) {
                throw new Exception("Erro ao deletar mídias antigas do DB: " . $stmt_delete_db->error);
            }
            $stmt_delete_db->close();
            
            foreach ($midias_a_remover_ids as $removed_id) {
                $midia_info = array_values(array_filter($current_midias_in_db, fn($m) => $m['id'] == $removed_id));
                if (!empty($midia_info) && $midia_info[0]['tipo'] !== 'video') {
                    $full_path = '../../' . $midia_info[0]['caminho_arquivo'];
                    if (file_exists($full_path) && is_file($full_path)) {
                        unlink($full_path);
                    }
                }
            }
        }

        insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'Atualização Empreendimento Etapa 5', 'Empreendimento', $empreendimento_id, "Mídias do empreendimento atualizadas na etapa 5.", $_SERVER['REMOTE_ADDR']],
                    "isssss");

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Mídias salvas com sucesso.';
        $response['message'] = 'Mídias salvas com sucesso.';

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao salvar mídias em salvar_etapa5.php: " . $e->getMessage());
        error_log("Erro ao salvar mídias em salvar_etapa5.php: " . $e->getMessage());
        $response['message'] = 'Erro ao salvar mídias: ' . $e->getMessage();
    } finally {
        if (isset($conn) && $conn) {
            $conn->close();
        }
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);