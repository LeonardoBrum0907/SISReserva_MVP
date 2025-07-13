<?php
// public/documentos.php - Página pública para upload de documentos de reserva

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/helpers.php';
require_once '../includes/alerts.php'; // Para alerts internos do sistema, se precisar
require_once '../includes/email.php'; // Para enviar notificação de sucesso

$page_title = "Enviar Documentos de Reserva";
$token = filter_input(INPUT_GET, 'token', FILTER_UNSAFE_RAW);
$reserva_info = null;
$cliente_info = null;
$documentos_obrigatorios = [];
$errors = [];
$success_message = '';

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico de DB em public/documentos.php: " . $e->getMessage());
    die("<h1>Erro</h1><p>Não foi possível conectar ao sistema. Tente novamente mais tarde.</p>");
}

if (!$token) {
    $errors[] = "Token de acesso inválido ou ausente.";
} else {
    try {
        // Valida o token e busca as informações da reserva e cliente
        $token_data = fetch_single(
            "SELECT id, reserva_id, cliente_id, data_expiracao, utilizado FROM documentos_upload_tokens WHERE token = ?",
            [$token],
            "s"
        );

        if (!$token_data) {
            $errors[] = "Token não encontrado. O link pode ser inválido.";
        } elseif ($token_data['utilizado']) {
            $errors[] = "Este link já foi utilizado. Para enviar novos documentos, solicite um novo link ao corretor ou administrador.";
        } elseif (new DateTime() > new DateTime($token_data['data_expiracao'])) {
            $errors[] = "Este link expirou. Por favor, solicite um novo link.";
        } else {
            // Token válido, buscar dados da reserva e empreendimento
            $reserva_info = fetch_single(
                "SELECT
                    r.id AS reserva_id,
                    r.status,
                    e.nome AS empreendimento_nome,
                    u.numero AS unidade_numero,
                    e.documentos_obrigatorios,
                    e.momento_envio_documentacao
                FROM
                    reservas r
                JOIN unidades u ON r.unidade_id = u.id
                JOIN empreendimentos e ON u.empreendimento_id = e.id
                WHERE r.id = ? AND r.status IN ('documentos_pendentes', 'documentos_rejeitados', 'documentos_solicitados')", // Inclui documentos_solicitados aqui
                [$token_data['reserva_id']],
                "i"
            );

            if (!$reserva_info) {
                $errors[] = "Informações da reserva não encontradas ou reserva não está em um status que aceite upload de documentos.";
            } else {
                $cliente_info = fetch_single("SELECT id, nome, email FROM clientes WHERE id = ?", [$token_data['cliente_id']], "i");
                if (!$cliente_info) {
                    $errors[] = "Informações do cliente não encontradas para este token.";
                }

                // Decodificar documentos obrigatórios do empreendimento
                if (!empty($reserva_info['documentos_obrigatorios'])) {
                    $documentos_obrigatorios = json_decode($reserva_info['documentos_obrigatorios'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $errors[] = "Erro ao carregar a lista de documentos obrigatórios.";
                        $documentos_obrigatorios = []; // Reseta para evitar loop em erro
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Erro em public/documentos.php: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao validar o link de acesso: " . $e->getMessage();
    }
}


// Processar upload de documentos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    try {
        if (!$reserva_info || !$cliente_info || empty($documentos_obrigatorios)) {
            throw new Exception("Dados da reserva ou documentos obrigatórios não carregados.");
        }

        // Marcar o token como utilizado para evitar reenvios com o mesmo link
        // Apenas se todos os uploads forem bem-sucedidos ou se a lógica for controlada posteriormente.
        // Por enquanto, vamos manter a marcação como utilizada APÓS o sucesso dos uploads.
        // update_delete_data("UPDATE documentos_upload_tokens SET utilizado = TRUE WHERE id = ?", [$token_data['id']], "i");

        $upload_dir_base = __DIR__ . '/../uploads/documentos_reservas/';
        $upload_dir_reserva = $upload_dir_base . 'reserva_' . $reserva_info['reserva_id'] . '/';
        if (!is_dir($upload_dir_reserva)) {
            mkdir($upload_dir_reserva, 0777, true);
        }

        $all_uploaded_successfully = true;
        $uploaded_docs_count = 0;

        foreach ($documentos_obrigatorios as $doc_nome_esperado) {
            $file_key_slug = slugify($doc_nome_esperado);
            $file_key = 'doc_' . $file_key_slug;

            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES[$file_key]['tmp_name'];
                $file_name = basename($_FILES[$file_key]['name']);
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_file_name = uniqid($file_key_slug . '_') . '.' . $file_extension;
                $target_file = $upload_dir_reserva . $new_file_name;
                $db_path = 'uploads/documentos_reservas/reserva_' . $reserva_info['reserva_id'] . '/' . $new_file_name;

                $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
                $max_size = 5 * 1024 * 1024; // 5MB

                if (!in_array($file_extension, $allowed_types)) {
                    $errors[] = "Arquivo '{$doc_nome_esperado}': Tipo não permitido. Apenas PDF, JPG, JPEG, PNG.";
                    $all_uploaded_successfully = false;
                    continue;
                }
                if ($_FILES[$file_key]['size'] > $max_size) {
                    $errors[] = "Arquivo '{$doc_nome_esperado}': Excede o tamanho máximo de 5MB.";
                    $all_uploaded_successfully = false;
                    continue;
                }

                if (move_uploaded_file($file_tmp_name, $target_file)) {
                    // Inserir/Atualizar registro do documento na base de dados
                    // Verificar se já existe um documento com este nome para esta reserva e cliente
                    $existing_doc = fetch_single("SELECT id FROM documentos_reserva WHERE reserva_id = ? AND cliente_id = ? AND nome_documento = ?", [$reserva_info['reserva_id'], $cliente_info['id'], $doc_nome_esperado], "iis");
                    if ($existing_doc) {
                        update_delete_data(
                            "UPDATE documentos_reserva SET caminho_arquivo = ?, status = 'pendente', motivo_rejeicao = NULL, data_upload = NOW() WHERE id = ?",
                            [$db_path, $existing_doc['id']],
                            "si"
                        );
                    } else {
                        insert_data(
                            "INSERT INTO documentos_reserva (reserva_id, cliente_id, nome_documento, caminho_arquivo, status, data_upload) VALUES (?, ?, ?, ?, 'pendente', NOW())",
                            [$reserva_info['reserva_id'], $cliente_info['id'], $doc_nome_esperado, $db_path],
                            "iiss"
                        );
                    }
                    $uploaded_docs_count++;
                } else {
                    $errors[] = "Falha ao fazer upload do arquivo '{$doc_nome_esperado}'.";
                    $all_uploaded_successfully = false;
                }
            } else {
                // Se o campo é obrigatório mas não foi selecionado, adicionar erro
                $errors[] = "O documento '{$doc_nome_esperado}' é obrigatório e não foi enviado.";
                $all_uploaded_successfully = false;
            }
        }

        if (empty($errors) && $uploaded_docs_count > 0) {
            // Se todos os documentos foram enviados com sucesso (e não há outros erros)
            // Atualizar status da reserva para documentos_enviados
            update_delete_data(
                "UPDATE reservas SET status = 'documentos_enviados', data_ultima_interacao = NOW() WHERE id = ? AND status IN ('documentos_pendentes', 'documentos_rejeitados', 'documentos_solicitados')",
                [$reserva_info['reserva_id']],
                "i"
            );
            $success_message = "Documentos enviados com sucesso! Eles agora estão em análise.";

            // Marcar o token como utilizado apenas se o processo foi um sucesso
            update_delete_data("UPDATE documentos_upload_tokens SET utilizado = TRUE WHERE id = ?", [$token_data['id']], "i");

            // Notificar admins que documentos foram enviados
            create_alert('documentos_enviados', "Documentos enviados para a Reserva #{$reserva_info['reserva_id']} da unidade {$reserva_info['unidade_numero']}. Aguardam análise.", ADMIN_MASTER_USER_ID, $reserva_info['reserva_id'], 'reserva');

        } elseif (!empty($errors)) {
            $success_message = ''; // Limpa mensagem de sucesso se houver erros
            // Não reverter token para não utilizado aqui, pois pode haver arquivos enviados parcialmente.
            // O admin pode re-solicitar um novo link se precisar.
            $errors[] = "Alguns documentos não puderam ser enviados. Por favor, corrija os erros e tente novamente. Um novo link de upload pode ser necessário se este expirar.";
        } else {
             $errors[] = "Nenhum documento válido foi selecionado ou enviado. Por favor, selecione os arquivos obrigatórios.";
        }


    } catch (Exception $e) {
        $errors[] = "Erro ao processar o upload: " . $e->getMessage();
        error_log("Erro no upload de documentos public/documentos.php: " . $e->getMessage());
    }
}

require_once '../includes/header_public.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">

<div class="public-document-upload-wrapper">
    <div class="upload-container">
        <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="<?php echo APP_NAME; ?> Logo" class="upload-logo">
        <h2><?php echo htmlspecialchars($page_title); ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="message-box message-box-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message-box message-box-success">
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($reserva_info): ?>
            <p class="reserva-details">
                Documentos para a reserva da unidade <strong><?php echo htmlspecialchars($reserva_info['unidade_numero']); ?></strong> do empreendimento <strong><?php echo htmlspecialchars($reserva_info['empreendimento_nome']); ?></strong>.
            </p>
            <p class="reserva-details">
                Status atual da reserva: <span class="status-badge status-<?php echo htmlspecialchars($reserva_info['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva_info['status']))); ?></span>
            </p>

            <?php if (!empty($documentos_obrigatorios)): ?>
                <form method="POST" action="" enctype="multipart/form-data" class="document-upload-form">
                    <input type="hidden" name="submitted_form" value="true">
                    <?php foreach ($documentos_obrigatorios as $doc_name):
                        $file_key_slug = slugify($doc_name);
                        $input_name = 'doc_' . $file_key_slug;
                    ?>
                        <div class="form-group">
                            <label for="<?php echo htmlspecialchars($input_name); ?>"><?php echo htmlspecialchars($doc_name); ?>:</label>
                            <input type="file" id="<?php echo htmlspecialchars($input_name); ?>" name="<?php echo htmlspecialchars($input_name); ?>" required>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary upload-btn">Enviar Documentos</button>
                </form>
            <?php else: ?>
                <p class="info-message">Nenhum documento obrigatório especificado para esta reserva no momento.</p>
            <?php endif; ?>

        <?php else: ?>
            <p class="error-message">Não foi possível carregar os detalhes da reserva. Verifique o link ou contate o suporte.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer_public.php'; ?>