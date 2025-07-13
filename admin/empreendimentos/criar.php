<?php
// admin/empreendimentos/criar.php - Wizard de Criação de Empreendimento (Etapas 1 a 7)

require_once '../../includes/config.php';
require_once '../../includes/database.php'; // Este arquivo define get_db_connection()
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn; // Declara que você vai usar a variável global $conn
    $conn = get_db_connection(); // Chama a função para obter a conexão
} catch (Exception $e) {
    // Se a conexão falhar, logue o erro e exiba uma mensagem amigável ao usuário.
    error_log("Erro crítico na inicialização do DB em admin/empreendimentos/criar.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Redireciona se não for um admin master
require_permission(['admin']);

$page_title = "Criar Novo Empreendimento";

// Inicia ou continua a sessão para armazenar dados do wizard
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($current_step < 1 || $current_step > 7) { // Limita as etapas
    $current_step = 1;
}

$errors = [];
$success_message = '';

// Lógica de Processamento do Wizard (POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("DEBUG PHP POST: Requisição POST recebida.");
    error_log("DEBUG PHP POST: Conteúdo de \$_POST: " . print_r($_POST, true));
    error_log("DEBUG PHP POST: Conteúdo de \$_FILES: " . print_r($_FILES, true));

    if (isset($_POST['next_step'])) {
        error_log("DEBUG PHP POST: Botão 'Próxima Etapa' clicado.");
        
        switch ($current_step) {
            case 1:
                $nome_empreendimento = trim($_POST['nome_empreendimento'] ?? '');
                $tipo_uso = trim($_POST['tipo_uso'] ?? '');
                $tipo_empreendimento = trim($_POST['tipo_empreendimento'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $momento_envio_documentacao = $_POST['momento_envio_documentacao'] ?? '';
                $documentos_obrigatorios_raw = trim($_POST['documentos_obrigatorios'] ?? '');

                if (empty($nome_empreendimento)) { $errors[] = "O nome do empreendimento é obrigatório."; }
                if (strlen($nome_empreendimento) > 255) { $errors[] = "O nome do empreendimento deve ter no máximo 255 caracteres."; }
                $tipos_uso_validos = ['Residencial', 'Comercial', 'Misto'];
                if (empty($tipo_uso) || !in_array($tipo_uso, $tipos_uso_validos)) { $errors[] = "Selecione um tipo de uso válido."; }
                if (empty($tipo_empreendimento)) { $errors[] = "O tipo de empreendimento é obrigatório."; }
                if (strlen($tipo_empreendimento) > 100) { $errors[] = "O tipo de empreendimento deve ter no máximo 100 caracteres."; }
                $momentos_validos = ['Na Proposta de Reserva', 'Após Confirmação de Reserva', 'Na Assinatura do Contrato', 'Não é necessário enviar documentos'];
                if (empty($momento_envio_documentacao) || !in_array($momento_envio_documentacao, $momentos_validos)) {
                    $errors[] = "O momento de envio da documentação é obrigatório.";
                }
                $documentos_obrigatorios_array = [];
                if ($momento_envio_documentacao !== 'Não é necessário enviar documentos') {
                    if (empty($documentos_obrigatorios_raw)) {
                        $errors[] = "Liste os documentos obrigatórios, ou selecione 'Não é necessário enviar documentos'.";
                    } else {
                        $documentos_obrigatorios_array = array_map('trim', explode(',', $documentos_obrigatorios_raw));
                        $documentos_obrigatorios_array = array_filter($documentos_obrigatorios_array);
                        if (empty($documentos_obrigatorios_array)) {
                            $errors[] = "Liste os documentos obrigatórios (separados por vírgula).";
                        }
                    }
                }
                if ($momento_envio_documentacao === 'Não é necessário enviar documentos') {
                    $documentos_obrigatorios_array = [];
                }

                if (empty($errors)) {
                    $_SESSION['wizard_form_data']['etapa_1'] = [
                        'nome_empreendimento' => $nome_empreendimento,
                        'tipo_uso' => $tipo_uso,
                        'tipo_empreendimento' => $tipo_empreendimento,
                        'descricao' => $descricao,
                        'momento_envio_documentacao' => $momento_envio_documentacao,
                        'documentos_obrigatorios' => $documentos_obrigatorios_array
                    ];
                    $current_step++;
                    header("Location: " . BASE_URL . "admin/empreendimentos/criar.php?step=" . $current_step);
                    exit();
                }
                break;

            case 2:
                $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
                $endereco = trim($_POST['endereco'] ?? '');
                $numero = trim($_POST['numero'] ?? '');
                $complemento = trim($_POST['complemento'] ?? '');
                $bairro = trim($_POST['bairro'] ?? '');
                $cidade = trim($_POST['cidade'] ?? '');
                $estado = trim($_POST['estado'] ?? '');

                $foto_localizacao_path = $_SESSION['wizard_form_data']['etapa_2']['foto_localizacao'] ?? '';

                if (empty($cep) || strlen($cep) !== 8) { $errors[] = "O CEP é obrigatório e deve ter 8 dígitos."; }
                if (empty($endereco)) { $errors[] = "O endereço é obrigatório."; }
                if (empty($numero)) { $errors[] = "O número é obrigatório."; }
                if (empty($bairro)) { $errors[] = "O bairro é obrigatório."; }
                if (empty($cidade)) { $errors[] = "A cidade é obrigatória."; }
                if (empty($estado) || strlen($estado) !== 2) { $errors[] = "O estado é obrigatório e deve ter 2 letras."; }

                if (isset($_FILES['foto_localizacao']) && $_FILES['foto_localizacao']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../../uploads/empreendimentos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_extension = pathinfo($_FILES['foto_localizacao']['name'], PATHINFO_EXTENSION);
                    $new_file_name = uniqid('loc_') . '.' . $file_extension;
                    $upload_file = $upload_dir . $new_file_name;

                    if (move_uploaded_file($_FILES['foto_localizacao']['tmp_name'], $upload_file)) {
                        $foto_localizacao_path = 'uploads/empreendimentos/' . $new_file_name;
                    } else {
                        $errors[] = "Erro ao fazer upload da foto de localização.";
                    }
                }


                if (empty($errors)) {
                    $_SESSION['wizard_form_data']['etapa_2'] = [
                        'cep' => $cep,
                        'endereco' => $endereco,
                        'numero' => $numero,
                        'complemento' => $complemento,
                        'bairro' => $bairro,
                        'cidade' => $cidade,
                        'estado' => $estado,
                        'foto_localizacao' => $foto_localizacao_path
                    ];
                    $current_step++;
                    header("Location: " . BASE_URL . "admin/empreendimentos/criar.php?step=" . $current_step);
                    exit();
                }
                break;

            case 3: // Processamento da Etapa 3: Estrutura e Tipos de Unidades
                error_log("DEBUG PHP POST: Processando Etapa 3.");
                $andar = filter_input(INPUT_POST, 'andar', FILTER_VALIDATE_INT);
                $unidades_por_andar_input = $_POST['unidades_por_andar'] ?? [];
                $tipos_unidades_input_raw = $_POST['tipos_unidade'] ?? [];

                if ($andar === false || $andar < 1) {
                    $errors[] = "O número de andares é obrigatório e deve ser no mínimo 1.";
                }

                if ($andar > 0) {
                    for ($i = 1; $i <= $andar; $i++) {
                        $qty = filter_var($unidades_por_andar_input[$i] ?? '', FILTER_VALIDATE_INT);
                        if ($qty === false || $qty < 0) {
                            $errors[] = "A quantidade de unidades para o " . $i . "º andar é inválida.";
                        }
                    }
                }

                $tipos_unidades_validos = [];
                if (empty($tipos_unidades_input_raw['tipo'] ?? [])) {
                    $errors[] = "É obrigatório cadastrar pelo menos um Tipo de Unidade.";
                } else {
                    foreach ($tipos_unidades_input_raw['tipo'] as $index => $tipo_nome) {
                        if (empty(trim($tipo_nome)) && (!isset($_FILES['tipos_unidade']['name']['foto_planta'][$index]) || $_FILES['tipos_unidade']['error']['foto_planta'][$index] === UPLOAD_ERR_NO_FILE)) {
                            if (count($tipos_unidades_input_raw['tipo']) == 1 && empty(trim($tipo_nome))) {
                                $errors[] = "O nome do Tipo de Unidade é obrigatório.";
                            }
                            continue;
                        }

                        $metragem = filter_var($tipos_unidades_input_raw['metragem'][$index] ?? '', FILTER_VALIDATE_FLOAT);
                        $quartos = filter_var($tipos_unidades_input_raw['quartos'][$index] ?? '', FILTER_VALIDATE_INT);
                        $banheiros = filter_var($_POST['tipos_unidades']['banheiros'][$index] ?? '', FILTER_VALIDATE_INT);
                        $vagas = filter_var($tipos_unidades_input_raw['vagas'][$index] ?? '', FILTER_VALIDATE_INT);

                        if (empty(trim($tipo_nome))) { $errors[] = "O nome do Tipo de Unidade #" . ($index + 1) . " é obrigatório."; }
                        if ($metragem === false || $metragem <= 0) { $errors[] = "A metragem do Tipo de Unidade #" . ($index + 1) . " é inválida."; }
                        if ($quartos === false || $quartos < 0) { $errors[] = "O número de quartos do Tipo de Unidade #" . ($index + 1) . " é inválido."; }
                        if ($banheiros === false || $banheiros < 0) { $errors[] = "O número de banheiros do Tipo de Unidade #" . ($index + 1) . " é inválido."; }
                        if ($vagas === false || $vagas < 0) { $errors[] = "O número de vagas do Tipo de Unidade #" . ($index + 1) . " é inválido."; }

                        $foto_planta_path = $_SESSION['wizard_form_data']['etapa_3']['tipos_unidades'][$index]['foto_planta'] ?? '';
                        
                        if (isset($_FILES['tipos_unidade']['tmp_name']['foto_planta'][$index]) && $_FILES['tipos_unidade']['error']['foto_planta'][$index] === UPLOAD_ERR_OK) {
                            $upload_dir = '../../uploads/plantas/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }
                            $file_extension = pathinfo($_FILES['tipos_unidade']['name']['foto_planta'][$index], PATHINFO_EXTENSION);
                            $new_file_name = uniqid('planta_') . '.' . $file_extension;
                            $upload_file = $upload_dir . $new_file_name;

                            if (move_uploaded_file($_FILES['tipos_unidade']['tmp_name'][$index], $upload_file)) {
                                $foto_planta_path = 'uploads/plantas/' . $new_file_name;
                            } else {
                                $errors[] = "Erro ao fazer upload da foto da planta para o Tipo de Unidade #" . ($index + 1) . ".";
                            }
                        } else if (isset($_FILES['tipos_unidade']['error']['foto_planta'][$index]) && $_FILES['tipos_unidade']['error']['foto_planta'][$index] !== UPLOAD_ERR_NO_FILE) {
                            $errors[] = "Erro no upload da foto da planta para o Tipo de Unidade #" . ($index + 1) . ": Código de erro " . $_FILES['tipos_unidade']['error']['foto_planta'][$index];
                        }

                        $tipos_unidades_validos[] = [
                            'tipo' => $tipo_nome,
                            'metragem' => $metragem,
                            'quartos' => $quartos,
                            'banheiros' => $banheiros,
                            'vagas' => $vagas,
                            'foto_planta' => $foto_planta_path
                        ];
                    }
                }
                
                if (empty($errors)) {
                    $_SESSION['wizard_form_data']['etapa_3'] = [
                        'andar' => $andar,
                        'unidades_por_andar' => $unidades_por_andar_input,
                        'tipos_unidades' => $tipos_unidades_validos
                    ];
                    $current_step++;
                    header("Location: " . BASE_URL . "admin/empreendimentos/criar.php?step=" . $current_step);
                    exit();
                }
                break;

            case 4: // Processamento da Etapa 4: Montagem do Estoque
                error_log("DEBUG PHP POST: Processando Etapa 4.");
                $estoque_unidades_input = $_POST['unidades_estoque'] ?? [];

                if (empty($estoque_unidades_input['numero'] ?? [])) {
                    $errors[] = "É obrigatório gerar e configurar pelo menos uma unidade.";
                } else {
                    foreach ($estoque_unidades_input['numero'] as $index => $numero_unidade) {
                        $andar = filter_var($estoque_unidades_input['andar'][$index] ?? '', FILTER_VALIDATE_INT);
                        $posicao = trim($estoque_unidades_input['posicao'][$index] ?? '');
                        $tipo_unidade_id_raw = $estoque_unidades_input['tipo_unidade_id'][$index] ?? '';
                        $tipo_unidade_id_filtered = filter_var($tipo_unidade_id_raw, FILTER_VALIDATE_INT);
                        $valor = filter_var($estoque_unidades_input['valor'][$index] ?? '', FILTER_VALIDATE_FLOAT);

                        if (empty(trim($numero_unidade))) { $errors[] = "Número da unidade #" . ($index + 1) . " é obrigatório."; continue; }
                        if ($andar === false || $andar < 1) { $errors[] = "Andar da unidade #" . ($index + 1) . " é inválido."; continue; }
                        if (empty($posicao)) { $errors[] = "Posição (Final) da unidade #" . ($index + 1) . " é obrigatória."; continue; }
                        if ($tipo_unidade_id_filtered === FALSE || $tipo_unidade_id_filtered < 0 || !isset($_SESSION['wizard_form_data']['etapa_3']['tipos_unidades'][$tipo_unidade_id_filtered])) { 
                             $errors[] = "Tipo de unidade da unidade #" . ($index + 1) . " é obrigatório.";
                             continue;
                        }
                        if ($valor === false || $valor <= 0) { $errors[] = "Valor da unidade #" . ($index + 1) . " é obrigatório e deve ser maior que zero."; continue; }
                    }
                }
                
                if (empty($errors)) {
                    $_SESSION['wizard_form_data']['etapa_4'] = [
                        'unidades_estoque' => $estoque_unidades_input
                    ];
                    $current_step++;
                    header("Location: " . BASE_URL . "admin/empreendimentos/criar.php?step=" . $current_step);
                    exit();
                }
                break;

            case 5: // Processamento da Etapa 5: Mídias
                error_log("DEBUG PHP POST: Processando Etapa 5.");
                
                $midias_validas = [];
                $current_foto_principal = '';
                if (!isset($_FILES['foto_principal']) || $_FILES['foto_principal']['error'] !== UPLOAD_ERR_OK) {
                    if (empty($foto_principal_path)) {
                        $errors[] = "A Foto Principal do empreendimento é obrigatória.";
                    }
                } else {
                    $upload_dir = '../../uploads/empreendimentos/';
                    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                    $file_extension = pathinfo($_FILES['foto_principal']['name'], PATHINFO_EXTENSION);
                    $new_file_name = uniqid('main_') . '.' . $file_extension;
                    if (move_uploaded_file($_FILES['foto_principal']['tmp_name'], $upload_dir . $new_file_name)) {
                        $foto_principal_path = 'uploads/empreendimentos/' . $new_file_name;
                        $midias_validas[] = ['caminho_arquivo' => $foto_principal_path, 'tipo' => 'foto_principal', 'descricao' => 'Foto Principal'];
                    } else { $errors[] = "Erro ao fazer upload da Foto Principal."; }
                }
                if (!empty($foto_principal_path)) {
                    $midias_validas[] = ['caminho_arquivo' => $foto_principal_path, 'tipo' => 'foto_principal', 'descricao' => 'Foto Principal'];
                }

                // Processar galeria_fotos (múltiplos arquivos)
                if (isset($_FILES['galeria_fotos']) && is_array($_FILES['galeria_fotos']['name'])) {
                    foreach ($_FILES['galeria_fotos']['name'] as $index => $name) {
                        if ($_FILES['galeria_fotos']['error'][$index] === UPLOAD_ERR_OK) {
                            $upload_dir = '../../uploads/empreendimentos/';
                            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                            $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                            $new_file_name = uniqid('gal_') . '.' . $file_extension;
                            if (move_uploaded_file($_FILES['galeria_fotos']['tmp_name'][$index], $upload_dir . $new_file_name)) {
                                $midias_validas[] = ['caminho_arquivo' => 'uploads/empreendimentos/' . $new_file_name, 'tipo' => 'galeria_foto', 'descricao' => 'Foto da Galeria'];
                            } else { $errors[] = "Erro ao fazer upload de uma foto da galeria."; }
                        } else if ($_FILES['galeria_fotos']['error'][$index] !== UPLOAD_ERR_NO_FILE) {
                            $errors[] = "Erro no upload da galeria de fotos: Código " . $_FILES['galeria_fotos']['error'][$index];
                        }
                    }
                }
                // Adicionar fotos de galeria já existentes na sessão
                $existing_midias_etapa5 = $_SESSION['wizard_form_data']['etapa_5']['midias'] ?? [];
                $existing_galeria_fotos = array_values(array_filter($existing_midias_etapa5, function($media) { return $media['tipo'] == 'galeria_foto'; }));
                foreach($existing_galeria_fotos as $media) {
                    if (!in_array($media['caminho_arquivo'], array_column($midias_validas, 'caminho_arquivo'))) {
                        $midias_validas[] = $media;
                    }
                }
                
                // Processar videos_youtube
                $videos_youtube = array_filter(array_map('trim', $_POST['videos_youtube'] ?? []));
                foreach ($videos_youtube as $url) {
                    if (empty($url)) continue;
                    if (preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|watch\?v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                        $midias_validas[] = ['caminho_arquivo' => $matches[1], 'tipo' => 'video', 'descricao' => 'Vídeo YouTube'];
                    } else {
                        $errors[] = "URL de vídeo do YouTube inválida: " . htmlspecialchars($url);
                    }
                }

                // Processar documentos (contrato e memorial)
                $doc_types = ['documento_contrato', 'documento_memorial'];
                foreach ($doc_types as $doc_type) {
                    $doc_path = $_SESSION['wizard_form_data']['etapa_5'][$doc_type] ?? '';
                    if (isset($_FILES[$doc_type]) && $_FILES[$doc_type]['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../../uploads/documentos/';
                        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                        $file_extension = pathinfo($_FILES[$doc_type]['name'], PATHINFO_EXTENSION);
                        $allowed_doc_types = ['pdf', 'doc', 'docx'];
                        if (!in_array(strtolower($file_extension), $allowed_doc_types)) {
                            $errors[] = "Tipo de arquivo inválido para " . ($doc_type == 'documento_contrato' ? 'Contrato' : 'Memorial Descritivo') . ". Apenas PDF, DOC, DOCX são permitidos.";
                            continue;
                        }
                        $new_file_name = uniqid($doc_type . '_') . '.' . $file_extension;
                        if (move_uploaded_file($_FILES[$doc_type]['tmp_name'], $upload_dir . $new_file_name)) {
                            $doc_path = 'uploads/documentos/' . $new_file_name;
                            $midias_validas[] = ['caminho_arquivo' => $doc_path, 'tipo' => $doc_type, 'descricao' => ucfirst(str_replace('_', ' ', $doc_type))];
                        } else { $errors[] = "Erro ao fazer upload do " . ($doc_type == 'documento_contrato' ? 'Contrato' : 'Memorial Descritivo') . "."; }
                    } else if (isset($_FILES[$doc_type]['error']) && $_FILES[$doc_type]['error'] !== UPLOAD_ERR_NO_FILE) {
                         $errors[] = "Erro no upload do " . ($doc_type == 'documento_contrato' ? 'Contrato' : 'Memorial Descritivo') + ": Código " + $_FILES[$doc_type]['error'];
                    }
                    if (empty($errors) && !empty($doc_path) && !in_array($doc_path, array_column($midias_validas, 'caminho_arquivo'))) {
                         $midias_validas[] = ['caminho_arquivo' => $doc_path, 'tipo' => $doc_type, 'descricao' => ucfirst(str_replace('_', ' ', $doc_type))];
                    }
                }

                // Processar imagens para cards de Explore
                $explore_card_types = ['explore_video_thumb', 'explore_gallery_thumb'];
                foreach ($explore_card_types as $explore_type) {
                    $explore_thumb_path = $_SESSION['wizard_form_data']['etapa_5'][$explore_type] ?? '';
                    if (isset($_FILES[$explore_type]) && $_FILES[$explore_type]['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../../uploads/empreendimentos/';
                        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                        $file_extension = pathinfo($_FILES[$explore_type]['name'], PATHINFO_EXTENSION);
                        $new_file_name = uniqid('exp_') . '.' . $file_extension;
                        if (move_uploaded_file($_FILES[$explore_type]['tmp_name'], $upload_dir . $new_file_name)) {
                            $explore_thumb_path = 'uploads/empreendimentos/' . $new_file_name;
                        } else { $errors[] = "Erro ao fazer upload da imagem para " . str_replace('_', ' ', $explore_type) . "."; }
                    } else if (isset($_FILES[$explore_type]['error']) && $_FILES[$explore_type]['error'] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = "Erro no upload da imagem para " + str_replace('_', ' ', $explore_type) + ": Código " + $_FILES[$explore_type]['error'];
                    }
                    if (empty($errors) && !empty($explore_thumb_path) && !in_array($explore_thumb_path, array_column($midias_validas, 'caminho_arquivo'))) {
                         $midias_validas[] = ['caminho_arquivo' => $explore_thumb_path, 'tipo' => $explore_type, 'descricao' => ucfirst(str_replace('_', ' ', $explore_type))];
                    }
                }

                if (empty($errors)) {
                    $_SESSION['wizard_form_data']['etapa_5'] = [
                        'midias' => $midias_validas
                    ];
                    $current_step++;
                    header("Location: " . BASE_URL . "admin/empreendimentos/criar.php?step=" . $current_step);
                    exit();
                }
                break;

            case 6: // Processamento da Etapa 6: Fluxo de Pagamento
                error_log("DEBUG PHP POST: Processando Etapa 6.");
                $unidade_exemplo_id_selected_idx = filter_input(INPUT_POST, 'unidade_exemplo_id', FILTER_VALIDATE_INT);
                $fluxo_pagamento_input = $_POST['fluxo_pagamento'] ?? [];
                
                $pagamento_validos = [];
                $total_percentual = 0.0;
                $has_percentual = false;
                $total_fixo = 0.0;
                $has_fixo = false;

                // Validação de qual unidade foi selecionada como exemplo
                // Verifica se o índice é um número inteiro válido (pode ser 0 ou mais) E se ele existe no array de unidades_estoque
                if ($unidade_exemplo_id_selected_idx === false || $unidade_exemplo_id_selected_idx < 0 || !isset($_SESSION['wizard_form_data']['etapa_4']['unidades_estoque']['numero'][$unidade_exemplo_id_selected_idx])) {
                    $errors[] = "Selecione uma Unidade Exemplo válida para o plano de pagamento.";
                } else {
                    $unidade_exemplo_valor = (float)($_SESSION['wizard_form_data']['etapa_4']['unidades_estoque']['valor'][$unidade_exemplo_id_selected_idx] ?? 0);
                }

                if (empty($fluxo_pagamento_input['descricao'] ?? [])) {
                    $errors[] = "É obrigatório cadastrar pelo menos uma parcela no Fluxo de Pagamento.";
                } else {
                    foreach ($fluxo_pagamento_input['descricao'] as $index => $descricao_parcela) {
                        $descricao_parcela = trim($descricao_parcela);
                        $quantas_vezes = filter_var($fluxo_pagamento_input['quantas_vezes'][$index] ?? '', FILTER_VALIDATE_INT);
                        $tipo_valor = $fluxo_pagamento_input['tipo_valor'][$index] ?? '';
                        $valor = filter_var($fluxo_pagamento_input['valor'][$index] ?? '', FILTER_VALIDATE_FLOAT);
                        $tipo_calculo = $fluxo_pagamento_input['tipo_calculo'][$index] ?? '';

                        // Validações individuais para cada campo da parcela
                        if (empty($descricao_parcela)) { $errors[] = "Descrição da parcela #" . ($index + 1) . " é obrigatória."; }
                        if ($quantas_vezes === false || $quantas_vezes < 1) { $errors[] = "Quantidade de vezes da parcela #" . ($index + 1) . " é inválida."; }
                        if (!in_array($tipo_valor, ['Valor Fixo (R$)', 'Percentual (%)'])) { $errors[] = "Tipo de valor da parcela #" . ($index + 1) . " é inválido."; }
                        if ($valor === false || $valor <= 0) { $errors[] = "Valor da parcela #" . ($index + 1) . " é inválido e deve ser maior que zero."; }
                        if (!in_array($tipo_calculo, ['Fixo', 'Proporcional'])) { $errors[] = "Tipo de cálculo da parcela #" . ($index + 1) . " é inválido."; }

                        if ($tipo_valor === 'Percentual (%)') {
                            $has_percentual = true;
                            $total_percentual += ($valor * $quantas_vezes);
                        } else {
                            $has_fixo = true;
                            $total_fixo += ($valor * $quantas_vezes);
                        }

                        $pagamento_validos[] = [
                            'descricao' => $descricao_parcela,
                            'quantas_vezes' => $quantas_vezes,
                            'tipo_valor' => $tipo_valor,
                            'valor' => $valor,
                            'tipo_calculo' => $tipo_calculo
                        ];
                    }
                }

                // Validação de totalização (100% para percentual, ou valor_total da unidade exemplo para fixo)
                $tolerance = 0.01; // 1% de tolerância para percentuais, 1 centavo para valores
                if ($has_percentual && abs($total_percentual - 100) > $tolerance) {
                    $errors[] = "A somatória dos percentuais do plano de pagamento (" . number_format($total_percentual, 2, ',', '.') . "%) não totaliza 100%.";
                } else if (!$has_percentual && $has_fixo && isset($unidade_exemplo_valor)) { // Apenas se não houver percentual e houver valor fixo
                    if (abs($total_fixo - $unidade_exemplo_valor) > $tolerance) {
                         $errors[] = "A somatória dos valores fixos do plano de pagamento (R$ " . number_format($total_fixo, 2, ',', '.') . ") não corresponde ao valor da unidade exemplo (R$ " . number_format($unidade_exemplo_valor, 2, ',', '.') . ").";
                    }
                }
                // NOVO: Adicionar erro se o plano não for nem 100% percentual nem 100% fixo (misto com percentuais que não somam 100% do total)
                if ($has_percentual && $has_fixo && isset($unidade_exemplo_valor)) {
                    $calculated_sum_from_session = 0;
                    foreach ($pagamento_validos as $p_item) {
                        if ($p_item['tipo_valor'] === 'Percentual (%)') {
                            $calculated_sum_from_session += ($p_item['valor'] / 100) * $unidade_exemplo_valor * $p_item['quantas_vezes'];
                        } else {
                            $calculated_sum_from_session += $p_item['valor'] * $p_item['quantas_vezes'];
                        }
                    }
                    if (abs($calculated_sum_from_session - $unidade_exemplo_valor) > $tolerance) {
                         $errors[] = "A somatória total do plano de pagamento (R$ " . number_format($calculated_sum_from_session, 2, ',', '.') . ") não corresponde ao valor da unidade exemplo (R$ " . number_format($unidade_exemplo_valor, 2, ',', '.') . "). Ajuste as parcelas.";
                    }
                }
                
                if (empty($errors)) {
                    $_SESSION['wizard_form_data']['etapa_6'] = [
                        'unidade_exemplo_id' => $unidade_exemplo_id_selected_idx, // Salva o índice da unidade exemplo
                        'fluxo_pagamento' => $pagamento_validos
                    ];
                    $current_step++;
                    header("Location: " . BASE_URL . "admin/empreendimentos/criar.php?step=" . $current_step);
                    exit();
                }
                break;

            case 7: // Processamento da Etapa 7: Permissões e Regras de Negócio (Final do Wizard)
                error_log("DEBUG PHP POST: Processando Etapa 7.");
                $permissoes_visualizacao = $_POST['permissoes_visualizacao'] ?? [];
                $permissao_reserva = $_POST['permissao_reserva'] ?? '';
                $limitacao_reservas_corretor = filter_input(INPUT_POST, 'limitacao_reservas_corretor', FILTER_VALIDATE_INT);
                $limitacao_reservas_imobiliaria = filter_input(INPUT_POST, 'limitacao_reservas_imobiliaria', FILTER_VALIDATE_INT);
                $prazo_expiracao_reserva = filter_input(INPUT_POST, 'prazo_expiracao_reserva', FILTER_VALIDATE_INT);
                $documentos_necessarios_etapa7 = $_POST['documentos_necessarios_etapa7'] ?? [];

                if (empty($permissoes_visualizacao)) { $errors[] = "Selecione quem pode visualizar este empreendimento."; }
                if (empty($permissao_reserva)) { $errors[] = "Selecione quem pode reservar unidades."; }
                
                $permissoes_visualizacao_json = json_encode($permissoes_visualizacao);
                $documentos_necessarios_etapa7_json = json_encode($documentos_necessarios_etapa7);

                if (empty($errors)) {
                    error_log("DEBUG PHP POST: Finalizando Wizard e Persistindo Dados.");

                    $final_data = $_SESSION['wizard_form_data'];

                    $nome_empreendimento = $final_data['etapa_1']['nome_empreendimento'] ?? null;
                    $tipo_uso = $final_data['etapa_1']['tipo_uso'] ?? null;
                    $tipo_empreendimento = $final_data['etapa_1']['tipo_empreendimento'] ?? null;
                    $descricao = $final_data['etapa_1']['descricao'] ?? null;
                    $momento_envio_documentacao = $final_data['etapa_1']['momento_envio_documentacao'] ?? null;
                    $documentos_obrigatorios_etapa1_json = json_encode($final_data['etapa_1']['documentos_obrigatorios'] ?? []);

                    $cep = $final_data['etapa_2']['cep'] ?? null;
                    $endereco = $final_data['etapa_2']['endereco'] ?? null;
                    $numero = $final_data['etapa_2']['numero'] ?? null;
                    $complemento = $final_data['etapa_2']['complemento'] ?? null;
                    $bairro = $final_data['etapa_2']['bairro'] ?? null;
                    $cidade = $final_data['etapa_2']['cidade'] ?? null;
                    $estado = $final_data['etapa_2']['estado'] ?? null;
                    $foto_localizacao_path = $final_data['etapa_2']['foto_localizacao'] ?? null;

                    $tipos_unidades_etapa3 = $final_data['etapa_3']['tipos_unidades'] ?? [];
                    $unidades_por_andar_map = $final_data['etapa_3']['unidades_por_andar'] ?? [];

                    $unidades_estoque_etapa4 = $final_data['etapa_4']['unidades_estoque'] ?? [];

                    $midias_etapa5 = $final_data['etapa_5']['midias'] ?? [];

                    $fluxo_pagamento_etapa6_json = json_encode($final_data['etapa_6']['fluxo_pagamento'] ?? []);

                    $conn = get_db_connection();
                    $conn->begin_transaction();

                    try {
                        $sql_insert_empreendimento = "
                            INSERT INTO empreendimentos (
                                nome, tipo_uso, tipo_empreendimento, descricao,
                                cep, endereco, numero, complemento, bairro, cidade, estado,
                                status, momento_envio_documentacao, documentos_obrigatorios,
                                permissoes_visualizacao, permissao_reserva, prazo_expiracao_reserva,
                                foto_localizacao, documentos_necessarios, data_cadastro, data_atualizacao
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ";
                        $stmt_empreendimento = $conn->prepare($sql_insert_empreendimento);
                        $stmt_empreendimento->bind_param(
                            "ssssssssssssssisss",
                            $nome_empreendimento, $tipo_uso, $tipo_empreendimento, $descricao,
                            $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado,
                            $momento_envio_documentacao, $documentos_obrigatorios_etapa1_json,
                            $permissoes_visualizacao_json, $permissao_reserva, $prazo_expiracao_reserva,
                            $foto_localizacao_path, $documentos_necessarios_etapa7_json
                        );
                        $stmt_empreendimento->execute();
                        $empreendimento_id = $stmt_empreendimento->insert_id;
                        $stmt_empreendimento->close();

                        if (!$empreendimento_id) {
                            throw new Exception("Erro ao inserir empreendimento.");
                        }

                        // Inserir Tipos de Unidades (tabela 'tipos_unidades')
                        $tipos_unidades_map = [];
                        foreach ($tipos_unidades_etapa3 as $index => $tipo_data) {
                            $sql_insert_tipo_unidade = "
                                INSERT INTO tipos_unidades (
                                    empreendimento_id, tipo, metragem, quartos, banheiros, vagas, foto_planta
                                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                            ";
                            $stmt_tipo_unidade = $conn->prepare($sql_insert_tipo_unidade);
                            $stmt_tipo_unidade->bind_param(
                                "isiiiss",
                                $empreendimento_id, $tipo_data['tipo'], $tipo_data['metragem'],
                                $tipo_data['quartos'], $tipo_data['banheiros'], $tipo_data['vagas'],
                                $tipo_data['foto_planta']
                            );
                            $stmt_tipo_unidade->execute();
                            $tipo_unidade_db_id = $stmt_tipo_unidade->insert_id;
                            $stmt_tipo_unidade->close();
                            if (!$tipo_unidade_db_id) {
                                throw new Exception("Erro ao inserir tipo de unidade: " . ($tipo_data['tipo'] ?? 'Desconhecido'));
                            }
                            $tipos_unidades_map[$index] = $tipo_unidade_db_id;
                        }

                        foreach ($unidades_estoque_etapa4['numero'] as $index => $numero_unidade) {
                            $andar = $unidades_estoque_etapa4['andar'][$index];
                            $posicao = $unidades_estoque_etapa4['posicao'][$index];
                            $tipo_unidade_wizard_idx = $unidades_estoque_etapa4['tipo_unidade_id'][$index];
                            $valor = $unidades_estoque_etapa4['valor'][$index];

                            if (is_null($andar) || is_null($posicao) || is_null($tipo_unidade_wizard_idx) || is_null($valor)) {
                                error_log("Skipping unit due to missing data: Index " . $index);
                                continue;
                            }

                            $tipo_unidade_real_id = $tipos_unidades_map[$tipo_unidade_wizard_idx] ?? null;
                            if (!$tipo_unidade_real_id) {
                                throw new Exception("Tipo de unidade real não encontrado no mapeamento para a unidade " . $numero_unidade . " (índice wizard: " . $tipo_unidade_wizard_idx . ").");
                            }

                            $sql_insert_unidade = "
                                INSERT INTO unidades (
                                    empreendimento_id, tipo_unidade_id, numero, andar, posicao, valor, status, informacoes_pagamento
                                ) VALUES (?, ?, ?, ?, ?, ?, 'disponivel', ?)
                            ";
                            $stmt_unidade = $conn->prepare($sql_insert_unidade);
                            $stmt_unidade->bind_param(
                                "iisiids",
                                $empreendimento_id, $tipo_unidade_real_id, $numero_unidade,
                                $andar, $posicao, $valor, $fluxo_pagamento_etapa6_json
                            );
                            $stmt_unidade->execute();
                            $unidade_db_id = $stmt_unidade->insert_id;
                            $stmt_unidade->close();
                            if (!$unidade_db_id) {
                                throw new Exception("Erro ao inserir unidade " . $numero_unidade);
                            }
                        }

                        foreach ($midias_etapa5 as $midia_data) {
                            $sql_insert_midia = "
                                INSERT INTO midias_empreendimentos (
                                    empreendimento_id, caminho_arquivo, tipo, descricao
                                ) VALUES (?, ?, ?, ?)
                            ";
                            $stmt_midia = $conn->prepare($sql_insert_midia);
                            $stmt_midia->bind_param(
                                "isss",
                                $empreendimento_id, $midia_data['caminho_arquivo'],
                                $midia_data['tipo'], $midia_data['descricao']
                            );
                            $stmt_midia->execute();
                            $midia_db_id = $stmt_midia->insert_id;
                            $stmt_midia->close();
                            if (!$midia_db_id) {
                                throw new Exception("Erro ao inserir mídia: " . ($midia_data['descricao'] ?? 'Desconhecido'));
                            }
                        }

                        $conn->commit();
                        unset($_SESSION['wizard_form_data']);
                        $_SESSION['success_message_wizard'] = "Empreendimento '" . htmlspecialchars($nome_empreendimento) . "' criado com sucesso!";
                        header("Location: " . BASE_URL . "admin/empreendimentos/index.php");
                        exit();

                    } catch (Exception $e) {
                        $conn->rollback();
                        $errors[] = "Erro ao finalizar cadastro do empreendimento: " . $e->getMessage();
                        error_log("ERRO FINAL WIZARD: " . $e->getMessage());
                    }
                }
                break;
        }
    } elseif (isset($_POST['prev_step'])) {
        $current_step--;
        if ($current_step < 1) $current_step = 1;
        header("Location: " . BASE_URL . "admin/empreendimentos/criar.php?step=" . $current_step);
        exit();
    }
}

$form_data_etapa_1 = $_SESSION['wizard_form_data']['etapa_1'] ?? [];
$form_data_etapa_2 = $_SESSION['wizard_form_data']['etapa_2'] ?? [];
$form_data_etapa_3 = $_SESSION['wizard_form_data']['etapa_3'] ?? [];
$form_data_etapa_4 = $_SESSION['wizard_form_data']['etapa_4'] ?? [];
$form_data_etapa_5 = $_SESSION['wizard_form_data']['etapa_5'] ?? [];
$form_data_etapa_6 = $_SESSION['wizard_form_data']['etapa_6'] ?? [];
$form_data_etapa_7 = $_SESSION['wizard_form_data']['etapa_7'] ?? [];

$js_andar = $form_data_etapa_3['andar'] ?? 0;
$js_unidades_por_andar = json_encode($form_data_etapa_3['unidades_por_andar'] ?? new stdClass());
$js_tipos_unidades = json_encode($form_data_etapa_3['tipos_unidades'] ?? []);
$js_unidades_estoque = json_encode($form_data_etapa_4['unidades_estoque'] ?? []); // CORRIGIDO: Array vazio
$js_midias_etapa_5 = json_encode($form_data_etapa_5['midias'] ?? []);
$js_fluxo_pagamento_etapa_6 = json_encode($form_data_etapa_6['fluxo_pagamento'] ?? []);

$page_title .= " - Etapa " . $current_step;
require_once '../../includes/header_dashboard.php';
?>

<script>
    window.numAndaresData = <?php echo $js_andar; ?>;
    window.unidadesPorAndarData = <?php echo $js_unidades_por_andar; ?>;
    window.tiposUnidadesData = <?php echo $js_tipos_unidades; ?>;
    window.unidadesEstoqueData = <?php echo $js_unidades_estoque; ?>;
    window.midiasEtapa5Data = <?php echo $js_midias_etapa_5; ?>;
    window.fluxoPagamentoEtapa6Data = <?php echo $js_fluxo_pagamento_etapa_6; ?>;
</script>

<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="message-box message-box-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message_wizard'])): ?>
        <div class="message-box message-box-success">
            <p><?php echo htmlspecialchars($_SESSION['success_message_wizard']); ?></p>
        </div>
        <?php unset($_SESSION['success_message_wizard']); ?>
    <?php endif; ?>

    <div class="wizard-navigation">
        <span class="wizard-step <?php echo ($current_step === 1) ? 'active' : ''; ?>">1. Detalhes Básicos</span>
        <span class="wizard-step <?php echo ($current_step === 2) ? 'active' : ''; ?>">2. Localização</span>
        <span class="wizard-step <?php echo ($current_step === 3) ? 'active' : ''; ?>">3. Estrutura e Tipos</span>
        <span class="wizard-step <?php echo ($current_step === 4) ? 'active' : ''; ?>">4. Montagem do Estoque</span>
        <span class="wizard-step <?php echo ($current_step === 5) ? 'active' : ''; ?>">5. Mídias</span>
        <span class="wizard-step <?php echo ($current_step === 6) ? 'active' : ''; ?>">6. Fluxo de Pagamento</span>
        <span class="wizard-step <?php echo ($current_step === 7) ? 'active' : ''; ?>">7. Permissões e Regras</span>
    </div>

    <form action="" method="POST" class="admin-form" enctype="multipart/form-data">

        <div class="form-section <?php echo ($current_step === 1) ? 'active' : ''; ?>" id="wizard-step-1">
            <h3>Informações Básicas do Empreendimento</h3>
            <div class="form-group-triple">
                <div class="form-group">
                    <label for="nome_empreendimento">Nome do Empreendimento:</label>
                    <input type="text" id="nome_empreendimento" name="nome_empreendimento" value="<?php echo htmlspecialchars($form_data_etapa_1['nome_empreendimento'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="tipo_uso">Tipo de Uso:</label>
                    <select id="tipo_uso" name="tipo_uso" required>
                        <option value="">Selecione</option>
                        <option value="Residencial" <?php echo (($form_data_etapa_1['tipo_uso'] ?? '') === 'Residencial') ? 'selected' : ''; ?>>Residencial</option>
                        <option value="Comercial" <?php echo (($form_data_etapa_1['tipo_uso'] ?? '') === 'Comercial') ? 'selected' : ''; ?>>Comercial</option>
                        <option value="Misto" <?php echo (($form_data_etapa_1['tipo_uso'] ?? '') === 'Misto') ? 'selected' : ''; ?>>Misto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo_empreendimento">Tipo de Empreendimento:</label>
                    <input type="text" id="tipo_empreendimento" name="tipo_empreendimento" value="<?php echo htmlspecialchars($form_data_etapa_1['tipo_empreendimento'] ?? ''); ?>" placeholder="Ex: Apartamento, Casa, Sala Comercial" required>
                </div>
            </div>

            <div class="form-group-cols">
                <div class="form-group form-group-col-left">
                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao" rows="10" placeholder="Detalhes completos sobre o empreendimento..."><?php echo htmlspecialchars($form_data_etapa_1['descricao'] ?? ''); ?></textarea>
                    <small>Este texto será exibido na página pública do empreendimento.</small>
                </div>
                <div class="form-group-col-right">
                    <h3>Configurações de Documentação e Reserva</h3>
                    <div class="form-group">
                        <label for="momento_envio_documentacao">Momento de Envio da Documentação:</label>
                        <select id="momento_envio_documentacao" name="momento_envio_documentacao" required>
                            <option value="">Selecione</option>
                            <option value="Na Proposta de Reserva" <?php echo (($form_data_etapa_1['momento_envio_documentacao'] ?? '') === 'Na Proposta de Reserva') ? 'selected' : ''; ?>>Na Proposta de Reserva</option>
                            <option value="Após Confirmação de Reserva" <?php echo (($form_data_etapa_1['momento_envio_documentacao'] ?? '') === 'Após Confirmação de Reserva') ? 'selected' : ''; ?>>Após Confirmação de Reserva</option>
                            <option value="Na Assinatura do Contrato" <?php echo (($form_data_etapa_1['momento_envio_documentacao'] ?? '') === 'Na Assinatura do Contrato') ? 'selected' : ''; ?>>Na Assinatura do Contrato</option>
                            <option value="Não é necessário enviar documentos" <?php echo (($form_data_etapa_1['momento_envio_documentacao'] ?? '') === 'Não é necessário enviar documentos') ? 'selected' : ''; ?>>Não é necessário enviar documentos</option>
                        </select>
                    </div>

                    <div class="form-group" id="documentos_obrigatorios_group">
                        <label for="documentos_obrigatorios">Documentos Obrigatórios (separar por vírgula):</label>
                        <textarea id="documentos_obrigatorios" name="documentos_obrigatorios" rows="3" placeholder="Ex: RG, CPF, Comprovante de Renda, Comprovante de Residência" <?php echo (($form_data_etapa_1['momento_envio_documentacao'] ?? '') === 'Não é necessário enviar documentos') ? 'disabled' : ''; ?>><?php echo htmlspecialchars(implode(', ', $form_data_etapa_1['documentos_obrigatorios'] ?? [])); ?></textarea>
                        <small>Estes documentos serão solicitados ao corretor/cliente no momento da reserva.</small>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="next_step" class="btn btn-primary">Próxima Etapa</button>
            </div>
        </div>

        <div class="form-section <?php echo ($current_step === 2) ? 'active' : ''; ?>" id="wizard-step-2" style="<?php echo ($current_step !== 2) ? 'display:none;' : ''; ?>">
            <h3>Localização do Empreendimento</h3>
            <div class="form-group">
                <label for="cep">CEP:</label>
                <input type="text" id="cep" name="cep" class="mask-cep" value="<?php echo htmlspecialchars($form_data_etapa_2['cep'] ?? ''); ?>" required>
                <small>Digite o CEP e o endereço será preenchido automaticamente.</small>
            </div>
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($form_data_etapa_2['endereco'] ?? ''); ?>" required readonly>
            </div>
            <div class="form-group">
                <label for="numero">Número:</label>
                <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($form_data_etapa_2['numero'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="complemento">Complemento (opcional):</label>
                <input type="text" id="complemento" name="complemento" value="<?php echo htmlspecialchars($form_data_etapa_2['complemento'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="bairro">Bairro:</label>
                <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($form_data_etapa_2['bairro'] ?? ''); ?>" required readonly>
            </div>
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($form_data_etapa_2['cidade'] ?? ''); ?>" required readonly>
                </div>
                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <input type="text" id="estado" name="estado" maxlength="2" value="<?php echo htmlspecialchars($form_data_etapa_2['estado'] ?? ''); ?>" required readonly>
                </div>
            </div>
            <div class="form-group">
                <label for="foto_localizacao">Foto da Localização (será exibida no modal do mapa):</label>
                <input type="file" id="foto_localizacao" name="foto_localizacao" accept="image/*">
                <?php if (!empty($form_data_etapa_2['foto_localizacao'])): ?>
                    <small>Arquivo atual: <?php echo htmlspecialchars($form_data_etapa_2['foto_localizacao']); ?></small>
                    <img src="<?php echo BASE_URL . htmlspecialchars($form_data_etapa_2['foto_localizacao']); ?>" alt="Localização atual" style="max-width: 150px; height: auto; display: block; margin-top: 10px; border-radius: var(--border-radius-sm);">
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" name="prev_step" class="btn btn-secondary">Etapa Anterior</button>
                <button type="submit" name="next_step" class="btn btn-primary">Próxima Etapa</button>
            </div>
        </div>

        <div class="form-section <?php echo ($current_step === 3) ? 'active' : ''; ?>" id="wizard-step-3" style="<?php echo ($current_step !== 3) ? 'display:none;' : ''; ?>">
            <h3>Estrutura e Tipos de Unidades</h3>
            <div class="form-group">
                <label for="andar">Quantos andares o empreendimento terá? (Excluindo térreo/subsolo se não contarem como andar de unidades)</label>
                <input type="number" id="andar" name="andar" min="1" value="<?php echo htmlspecialchars($form_data_etapa_3['andar'] ?? ''); ?>" required>
                <small>Este número definirá a quantidade de andares para configurar as unidades.</small>
            </div>

            <h4>Unidades por Andar</h4>
            <div id="unidades_por_andar_container">
                <?php
                if (!empty($form_data_etapa_3['unidades_por_andar']) && ($form_data_etapa_3['andar'] ?? 0) > 0) {
                    for ($i = 1; $i <= $form_data_etapa_3['andar']; $i++) {
                        $qty = $form_data_etapa_3['unidades_por_andar'][$i] ?? 0;
                        echo '<div class="form-group">';
                        echo '<label for="unidades_por_andar_' . htmlspecialchars($i) . '">' . htmlspecialchars($i) . 'º Andar - Quantidade de Unidades:</label>';
                        echo '<input type="number" id="unidades_por_andar_' . htmlspecialchars($i) . '" name="unidades_por_andar[' . htmlspecialchars($i) . ']" min="0" value="' . htmlspecialchars($qty) . '" required>';
                        echo '</div>';
                    }
                }
                ?>
            </div>

            <h4>Tipos de Unidades</h4>
            <div id="tipos_unidades_container">
                <?php
                if (!empty($form_data_etapa_3['tipos_unidades'])) {
                    foreach ($form_data_etapa_3['tipos_unidades'] as $index => $unidade) {
                        echo '<div class="tipo-unidade-item">';
                        echo '<div class="form-group">';
                        echo '<label>Tipo de Unidade:</label>';
                        echo '<input type="text" name="tipos_unidade[tipo][]" value="' . htmlspecialchars($unidade['tipo'] ?? '') . '" placeholder="Ex: Apartamento 2 Dorms" required>';
                        echo '</div>';
                        echo '<div class="form-group-inline">';
                        echo '<div class="form-group">';
                        echo '<label>Metragem (m²):</label>';
                        echo '<input type="number" step="0.01" name="tipos_unidade[metragem][]" value="' . htmlspecialchars($unidade['metragem'] ?? '') . '" required>';
                        echo '</div>';
                        echo '<div class="form-group">';
                        echo '<label>Quartos:</label>';
                        echo '<input type="number" min="0" name="tipos_unidade[quartos][]" value="' . htmlspecialchars($unidade['quartos'] ?? '') . '" required>';
                        echo '</div>';
                        echo '<div class="form-group">';
                        echo '<label>Banheiros:</label>';
                        echo '<input type="number" min="0" name="tipos_unidades[banheiros][]" value="' . htmlspecialchars($unidade['banheiros'] ?? '') . '" required>';
                        echo '</div>';
                        echo '<div class="form-group">';
                        echo '<label>Vagas de Garagem:</label>';
                        echo '<input type="number" min="0" name="tipos_unidade[vagas][]" value="' . htmlspecialchars($unidade['vagas'] ?? '') . '" required>';
                        echo '</div>';
                        echo '</div>'; // Fecha form-group-inline
                        echo '<div class="form-group">';
                        echo '<label>Foto da Planta:</label>';
                        echo '<input type="file" name="tipos_unidade[foto_planta][]" accept="image/*">';
                        if (!empty($unidade['foto_planta'])) {
                            echo '<small>Arquivo atual: ' . htmlspecialchars($unidade['foto_planta']) . '</small>';
                            echo '<img src="' . BASE_URL . htmlspecialchars($unidade['foto_planta']) . '" alt="Planta atual" style="max-width: 100px; height: auto; display: block; margin-top: 5px; border-radius: var(--border-radius-sm);"></div>';
                        }
                        echo '<button type="button" class="btn btn-danger btn-sm remove-tipo-unidade">Remover Tipo</button>';
                        echo '</div>'; // Fecha tipo-unidade-item
                    }
                } else {
                    // Item de tipo de unidade padrão (se não houver nenhum na sessão)
                    echo '<div class="tipo-unidade-item">';
                    echo '<div class="form-group">';
                    echo '<label>Tipo de Unidade:</label>';
                    echo '<input type="text" name="tipos_unidade[tipo][]" placeholder="Ex: Apartamento 2 Dorms" required>';
                    echo '</div>';
                    echo '<div class="form-group-inline">';
                    echo '<div class="form-group">';
                    echo '<label>Metragem (m²):</label>';
                    echo '<input type="number" step="0.01" name="tipos_unidade[metragem][]" required>';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label>Quartos:</label>';
                    echo '<input type="number" min="0" name="tipos_unidade[quartos][]" required>';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label>Banheiros:</label>';
                    echo '<input type="number" min="0" name="tipos_unidade[banheiros][]" required>';
                    echo '</div>';
                    echo '<div class="form-group">';
                    echo '<label>Vagas de Garagem:</label>';
                    echo '<input type="number" min="0" name="tipos_unidade[vagas][]" required>';
                    echo '</div>';
                    echo '</div>'; // Fecha form-group-inline
                    echo '<div class="form-group">';
                    echo '<label>Foto da Planta:</label>';
                    echo '<input type="file" name="tipos_unidade[foto_planta][]" accept="image/*">';
                    echo '</div>';
                    // Não há botão de remover para o primeiro item padrão
                    echo '</div>'; // Fecha tipo-unidade-item
                }
                ?>
            </div>
            <button type="button" id="add_tipo_unidade" class="btn btn-secondary mt-3">Adicionar Tipo de Unidade</button>

            <div class="form-actions">
                <button type="submit" name="prev_step" class="btn btn-secondary">Etapa Anterior</button>
                <button type="submit" name="next_step" class="btn btn-primary">Próxima Etapa</button>
            </div>
        </div>

        <div class="form-section <?php echo ($current_step === 4) ? 'active' : ''; ?>" id="wizard-step-4" style="<?php echo ($current_step !== 4) ? 'display:none;' : ''; ?>">
            <h3>Montagem do Estoque de Unidades</h3>
            
            <div class="batch-tools-panel">
                <h4>Ferramentas de Preenchimento em Lote</h4>
                <div class="form-group-inline">
                    <div class="form-group">
                        <label for="batch_value">Valor Padrão (R$):</label>
                        <input type="number" step="0.01" id="batch_value" placeholder="Ex: 250000.00">
                    </div>
                    <button type="button" id="apply_batch_value" class="btn btn-secondary align-self-end">Aplicar a Todas</button>
                </div>
                <div class="form-group-inline">
                    <div class="form-group">
                        <label for="batch_tipo_unidade_id">Atribuir Tipo de Planta Padrão:</label>
                        <select id="batch_tipo_unidade_id">
                            <option value="">-- Selecione um Tipo --</option>
                            <?php 
                            if (!empty($form_data_etapa_3['tipos_unidades'])) {
                                foreach ($form_data_etapa_3['tipos_unidades'] as $idx => $tipo_unidade_data) {
                                    echo '<option value="' . htmlspecialchars($idx) . '">' . htmlspecialchars($tipo_unidade_data['tipo']) . ' (' . htmlspecialchars(number_format($tipo_unidade_data['metragem'], 0, ',', '.')) . 'm²)</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <button type="button" id="apply_batch_type" class="btn btn-secondary align-self-end">Aplicar a Todas</button>
                </div>
                <div class="form-actions" style="justify-content: flex-start; margin-top: var(--spacing-lg);">
                    <button type="button" id="generate_units_btn" class="btn btn-primary">Gerar Unidades Automaticamente</button>
                    <small style="margin-left: var(--spacing-md); color: var(--color-secondary);">Baseado na Estrutura definida na Etapa 3.</small>
                </div>
            </div>

            <div class="units-stock-table-container">
                <table class="units-stock-table">
                    <thead>
                        <tr>
                            <th>Andar</th>
                            <th>Número</th>
                            <th>Final</th>
                            <th>Tipo de Unidade</th>
                            <th>Valor (R$)</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="units_stock_tbody">
                        <?php
                        if (!empty($form_data_etapa_4['unidades_estoque']['numero'] ?? [])) {
                            foreach ($form_data_etapa_4['unidades_estoque']['numero'] as $index => $numero_unidade) {
                                $andar = htmlspecialchars($form_data_etapa_4['unidades_estoque']['andar'][$index] ?? '');
                                $posicao = htmlspecialchars($form_data_etapa_4['unidades_estoque']['posicao'][$index] ?? '');
                                $tipo_unidade_id_selected = htmlspecialchars($form_data_etapa_4['unidades_estoque']['tipo_unidade_id'][$index] ?? '');
                                $valor = htmlspecialchars($form_data_etapa_4['unidades_estoque']['valor'][$index] ?? '');

                                $tipo_options_html = '<option value="">Selecione um Tipo</option>';
                                if (!empty($form_data_etapa_3['tipos_unidades'])) {
                                    foreach ($form_data_etapa_3['tipos_unidades'] as $idx => $tipo_unidade_data) {
                                        $selected = ((string)$idx == $tipo_unidade_id_selected) ? 'selected' : '';
                                        $tipo_options_html .= '<option value="' . htmlspecialchars($idx) . '" ' . $selected . '>' . htmlspecialchars($tipo_unidade_data['tipo']) . ' (' . htmlspecialchars(number_format($tipo_unidade_data['metragem'], 0, ',', '.')) . 'm²)</option>';
                                    }
                                }

                                echo '<tr class="unit-stock-row">';
                                echo '<td><input type="number" name="unidades_estoque[andar][]" value="' . $andar . '" required readonly class="unit-stock-andar-input"></td>';
                                echo '<td><input type="text" name="unidades_estoque[numero][]" value="' . $numero_unidade . '" required class="unit-stock-numero-input"></td>';
                                echo '<td><input type="text" name="unidades_estoque[posicao][]" value="' . $posicao . '" required class="unit-stock-posicao-input"></td>';
                                echo '<td><select name="unidades_estoque[tipo_unidade_id][]" required class="unit-stock-tipo-select">' . $tipo_options_html . '</select></td>';
                                echo '<td><input type="number" step="0.01" name="unidades_estoque[valor][]" value="' . $valor . '" required class="unit-stock-valor-input"></td>';
                                echo '<td><button type="button" class="btn btn-danger btn-sm remove-unit-stock-item">Remover</button></td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <button type="submit" name="prev_step" class="btn btn-secondary">Etapa Anterior</button>
                <button type="submit" name="next_step" class="btn btn-primary">Próxima Etapa</button>
            </div>
        </div>

        <div class="form-section <?php echo ($current_step === 5) ? 'active' : ''; ?>" id="wizard-step-5" style="<?php echo ($current_step !== 5) ? 'display:none;' : ''; ?>">
            <h3>Mídias do Empreendimento</h3>
            
            <div class="form-group">
                <label for="foto_principal">Foto Principal (será usada no card da listagem):</label>
                <input type="file" id="foto_principal" name="foto_principal" accept="image/*" <?php echo empty($form_data_etapa_5['midias']) || !in_array('foto_principal', array_column($form_data_etapa_5['midias'] ?? [], 'tipo')) ? 'required' : ''; ?>>
                <?php
                $current_foto_principal = array_values(array_filter($form_data_etapa_5['midias'] ?? [], function($media) { return $media['tipo'] == 'foto_principal'; }))[0]['caminho_arquivo'] ?? '';
                if (!empty($current_foto_principal)):
                ?>
                    <small>Arquivo atual: <?php echo htmlspecialchars($current_foto_principal); ?></small>
                    <img src="<?php echo BASE_URL . htmlspecialchars($current_foto_principal); ?>" alt="Foto Principal atual" style="max-width: 150px; height: auto; display: block; margin-top: 5px; border-radius: var(--border-radius-sm);">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="galeria_fotos">Fotos para Galeria (selecione múltiplas imagens):</label>
                <input type="file" id="galeria_fotos" name="galeria_fotos[]" accept="image/*" multiple>
                <div class="uploaded-files-preview">
                    <?php
                    $current_galeria_fotos = array_values(array_filter($form_data_etapa_5['midias'] ?? [], function($media) { return $media['tipo'] == 'galeria_foto'; }));
                    if (!empty($current_galeria_fotos)):
                        echo '<small>Arquivos atuais:</small><div class="file-preview-grid">';
                        foreach ($current_galeria_fotos as $img) {
                            echo '<div class="file-preview-item"><img src="' . BASE_URL . htmlspecialchars($img['caminho_arquivo']) . '" alt="Galeria" style="max-width: 80px; height: auto; border-radius: var(--border-radius-sm);"><br><button type="button" class="remove-file-preview btn btn-danger btn-sm" data-file-path="' . htmlspecialchars($img['caminho_arquivo']) . '">Remover</button></div>';
                        }
                        echo '</div>';
                    endif;
                    ?>
                </div>
            </div>

            <h4>Vídeos do YouTube</h4>
            <div id="videos_youtube_container">
                <?php
                $current_videos = array_values(array_filter($form_data_etapa_5['midias'] ?? [], function($media) { return $media['tipo'] == 'video'; }));
                if (!empty($current_videos)) {
                    foreach ($current_videos as $index => $video) {
                        echo '<div class="video-url-item form-group">';
                        echo '<label for="video_url_' . $index . '">URL do Vídeo (ID):</label>';
                        echo '<input type="text" id="video_url_' . $index . '" name="videos_youtube[]" value="' . htmlspecialchars($video['caminho_arquivo']) . '" placeholder="Ex: dQw4w9WgXcQ" class="form-control">';
                        echo '<button type="button" class="btn btn-danger btn-sm remove-video-url">Remover</button>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="video-url-item form-group">';
                    echo '<label>URL do Vídeo (ID):</label>';
                    echo '<input type="text" name="videos_youtube[]" placeholder="Ex: dQw4w9WgXcQ" class="form-control">';
                    echo '<button type="button" class="btn btn-danger btn-sm remove-video-url" style="display:none;">Remover</button>';
                    echo '</div>';
                }
                ?>
            </div>
            <button type="button" id="add_video_url" class="btn btn-secondary mt-3">Adicionar Vídeo</button>

            <h4>Documentos</h4>
            <div class="form-group-cols">
                <div class="form-group form-group-col-left">
                    <label for="documento_contrato">Contrato (PDF/DOC):</label>
                    <input type="file" id="documento_contrato" name="documento_contrato" accept=".pdf,.doc,.docx">
                    <?php
                    $current_doc_contrato = array_values(array_filter($form_data_etapa_5['midias'] ?? [], function($media) { return $media['tipo'] == 'documento_contrato'; }))[0]['caminho_arquivo'] ?? '';
                    if (!empty($current_doc_contrato)):
                    ?>
                        <small>Arquivo atual: <a href="<?php echo BASE_URL . htmlspecialchars($current_doc_contrato); ?>" target="_blank"><?php echo basename($current_doc_contrato); ?></a></small>
                    <?php endif; ?>
                </div>
                <div class="form-group form-group-col-right">
                    <label for="documento_memorial">Memorial Descritivo (PDF/DOC):</label>
                    <input type="file" id="documento_memorial" name="documento_memorial" accept=".pdf,.doc,.docx">
                    <?php
                    $current_doc_memorial = array_values(array_filter($form_data_etapa_5['midias'] ?? [], function($media) { return $media['tipo'] == 'documento_memorial'; }))[0]['caminho_arquivo'] ?? '';
                    if (!empty($current_doc_memorial)):
                    ?>
                        <small>Arquivo atual: <a href="<?php echo BASE_URL . htmlspecialchars($current_doc_memorial); ?>" target="_blank"><?php echo basename($current_doc_memorial); ?></a></small>
                    <?php endif; ?>
                </div>
            </div>

            <h4>Imagens para Cards "Explore"</h4>
            <small>Estas imagens serão usadas nos cards da seção "Explore um pouco mais" na página pública.</small>
            <div class="form-group-inline">
                <div class="form-group">
                    <label for="explore_video_thumb">Imagem Card "Vídeos":</label>
                    <input type="file" id="explore_video_thumb" name="explore_video_thumb" accept="image/*">
                    <?php
                    $current_explore_video_thumb = array_values(array_filter($form_data_etapa_5['midias'] ?? [], function($media) { return $media['tipo'] == 'explore_video_thumb'; }))[0]['caminho_arquivo'] ?? '';
                    if (!empty($current_explore_video_thumb)):
                    ?>
                        <small>Arquivo atual:</small><br><img src="<?php echo BASE_URL . htmlspecialchars($current_explore_video_thumb); ?>" alt="Vídeos Thumb" style="max-width: 100px; height: auto; display: block; margin-top: 5px; border-radius: var(--border-radius-sm);">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="explore_gallery_thumb">Imagem Card "Galeria":</label>
                    <input type="file" id="explore_gallery_thumb" name="explore_gallery_thumb" accept="image/*">
                    <?php
                    $current_explore_gallery_thumb = array_values(array_filter($form_data_etapa_5['midias'] ?? [], function($media) { return $media['tipo'] == 'explore_gallery_thumb'; }))[0]['caminho_arquivo'] ?? '';
                    if (!empty($current_explore_gallery_thumb)):
                    ?>
                        <small>Arquivo atual:</small><br><img src="<?php echo BASE_URL . htmlspecialchars($current_explore_gallery_thumb); ?>" alt="Galeria Thumb" style="max-width: 100px; height: auto; display: block; margin-top: 5px; border-radius: var(--border-radius-sm);">
                    <?php endif; ?>
                </div>
            </div>


            <div class="form-actions">
                <button type="submit" name="prev_step" class="btn btn-secondary">Etapa Anterior</button>
                <button type="submit" name="next_step" class="btn btn-primary">Próxima Etapa</button>
            </div>
        </div>

        <div class="form-section <?php echo ($current_step === 6) ? 'active' : ''; ?>" id="wizard-step-6" style="<?php echo ($current_step !== 6) ? 'display:none;' : ''; ?>">
            <h3>Fluxo de Pagamento</h3>
            
            <div class="form-group">
                <label for="unidade_exemplo_id">Selecione uma Unidade Exemplo para Definir o Plano de Pagamento:</label>
                <select id="unidade_exemplo_id" name="unidade_exemplo_id" required>
                    <option value="">-- Selecione uma Unidade --</option>
                    <?php
                    if (!empty($form_data_etapa_4['unidades_estoque']['numero'] ?? [])) {
                        foreach ($form_data_etapa_4['unidades_estoque']['numero'] as $index => $numero_unidade) {
                            $andar_unidade = $form_data_etapa_4['unidades_estoque']['andar'][$index] ?? '';
                            $valor_unidade = $form_data_etapa_4['unidades_estoque']['valor'][$index] ?? '';
                            $selected = ((string)($form_data_etapa_6['unidade_exemplo_id'] ?? '') == (string)$index) ? 'selected' : ''; 
                            echo '<option value="' . htmlspecialchars($index) . '" data-unit-value="' . htmlspecialchars($valor_unidade) . '" ' . $selected . '>';
                            echo 'Unidade ' . htmlspecialchars($numero_unidade) . ' (' . htmlspecialchars($andar_unidade) . 'º Andar) - R$ ' . htmlspecialchars(number_format($valor_unidade, 2, ',', '.'));
                            echo '</option>';
                        }
                    } else {
                        echo '<option value="">Nenhuma unidade gerada na Etapa 4.</option>';
                    }
                    ?>
                </select>
                <small>O plano de pagamento será baseado no valor desta unidade.</small>
            </div>

            <div id="payment_flow_builder_panel" style="display:none;">
                <h4>Parcelas do Plano de Pagamento</h4>
                <div class="form-actions" style="justify-content: flex-start; margin-top: var(--spacing-lg);">
                    <button type="button" id="add_parcela_btn" class="btn btn-secondary">Adicionar Parcela</button>
                </div>

                <div class="payment-flow-items-container">
                    <table class="admin-table payment-flow-table">
                        <thead>
                            <tr>
                                <th>Descrição</th>
                                <th>Quant. Vezes</th>
                                <th>Tipo de Valor</th>
                                <th>Valor/Percentual</th>
                                <th>Tipo de Cálculo</th>
                                <th>Total da Parcela</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="payment_flow_tbody">
                            <?php
                            if (!empty($form_data_etapa_6['fluxo_pagamento'])) {
                                foreach ($form_data_etapa_6['fluxo_pagamento'] as $index => $parcela) {
                                    $valor_display = ($parcela['tipo_valor'] == 'Percentual (%)') ? ($parcela['valor'] * 100) : $parcela['valor'];
                                    echo '<tr class="payment-flow-item-row">';
                                    echo '<td><input type="text" name="fluxo_pagamento[descricao][]" value="' . htmlspecialchars($parcela['descricao']) . '" required></td>';
                                    echo '<td><input type="number" name="fluxo_pagamento[quantas_vezes][]" value="' . htmlspecialchars($parcela['quantas_vezes']) . '" min="1" required></td>';
                                    echo '<td><select name="fluxo_pagamento[tipo_valor][]" required class="tipo-valor-select">';
                                    echo '<option value="Valor Fixo (R$)" ' . (($parcela['tipo_valor'] == 'Valor Fixo (R$)') ? 'selected' : '') . '>Valor Fixo (R$)</option>';
                                    echo '<option value="Percentual (%)" ' . (($parcela['tipo_valor'] == 'Percentual (%)') ? 'selected' : '') . '>Percentual (%)</option>';
                                    echo '</select></td>';
                                    echo '<td><input type="number" step="0.01" name="fluxo_pagamento[valor][]" value="' . htmlspecialchars($valor_display) . '" required class="valor-input"></td>';
                                    echo '<td><select name="fluxo_pagamento[tipo_calculo][]" required class="tipo-calculo-select">';
                                    echo '<option value="Fixo" ' . (($parcela['tipo_calculo'] == 'Fixo') ? 'selected' : '') . '>Fixo</option>';
                                    echo '<option value="Proporcional" ' . (($parcela['tipo_calculo'] == 'Proporcional') ? 'selected' : '') . '>Proporcional</option>';
                                    echo '</select></td>';
                                    echo '<td class="total-parcela-display"></td>';
                                    echo '<td><button type="button" class="btn btn-danger btn-sm remove-parcela">Remover</button></td>';
                                    echo '</tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="payment-flow-summary">
                    <p>Somatória do Plano de Pagamento: <strong id="total_plano_pagamento">R$ 0,00</strong></p>
                    <p id="total_plano_validation" class="text-danger"></p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="prev_step" class="btn btn-secondary">Etapa Anterior</button>
                <button type="submit" name="next_step" class="btn btn-primary">Próxima Etapa</button>
            </div>
        </div>

        <div class="form-section <?php echo ($current_step === 7) ? 'active' : ''; ?>" id="wizard-step-7" style="<?php echo ($current_step !== 7) ? 'display:none;' : ''; ?>">
            <h3>Permissões e Regras de Negócio</h3>
            
            <div class="form-group">
                <label>Quem pode visualizar este empreendimento?</label>
                <div>
                    <label><input type="checkbox" name="permissoes_visualizacao[]" value="Cliente Final" <?php echo (in_array('Cliente Final', $form_data_etapa_7['permissoes_visualizacao'] ?? [])) ? 'checked' : ''; ?>> Cliente Final (Visitantes)</label><br>
                    <label><input type="checkbox" name="permissoes_visualizacao[]" value="Corretor" <?php echo (in_array('Corretor', $form_data_etapa_7['permissoes_visualizacao'] ?? [])) ? 'checked' : ''; ?>> Corretores</label><br>
                    <label><input type="checkbox" name="permissoes_visualizacao[]" value="Admin" <?php echo (in_array('Admin', $form_data_etapa_7['permissoes_visualizacao'] ?? [])) ? 'checked' : ''; ?>> Administradores</label>
                </div>
            </div>

            <div class="form-group">
                <label>Quem pode reservar unidades?</label>
                <div class="radio-group">
                    <label><input type="radio" name="permissao_reserva" value="Todos" <?php echo (($form_data_etapa_7['permissao_reserva'] ?? '') === 'Todos') ? 'checked' : ''; ?> required> Todos (inclui leads se visualização for para Cliente Final)</label><br>
                    <label><input type="radio" name="permissao_reserva" value="Corretores Selecionados" <?php echo (($form_data_etapa_7['permissao_reserva'] ?? '') === 'Corretores Selecionados') ? 'checked' : ''; ?>> Corretores Selecionados</label><br>
                    <label><input type="radio" name="permissao_reserva" value="Imobiliarias Selecionadas" <?php echo (($form_data_etapa_7['permissao_reserva'] ?? '') === 'Imobiliarias Selecionadas') ? 'checked' : ''; ?>> Imobiliárias Selecionadas</label>
                </div>
            </div>

            <div class="form-group" id="corretores_selecionados_group" style="display: none;">
                <label for="corretores_selecionados">Selecione Corretores:</label>
                <select id="corretores_selecionados" name="corretores_selecionados[]" multiple>
                    </select>
            </div>

            <div class="form-group" id="imobiliarias_selecionadas_group" style="display: none;">
                <label for="imobiliarias_selecionadas">Selecione Imobiliárias:</label>
                <select id="imobiliarias_selecionadas" name="imobiliarias_selecionadas[]" multiple>
                    </select>
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label for="limitacao_reservas_corretor">Limite de Reservas por Corretor:</label>
                    <input type="number" id="limitacao_reservas_corretor" name="limitacao_reservas_corretor" min="0" value="<?php echo htmlspecialchars($form_data_etapa_7['limitacao_reservas_corretor'] ?? 0); ?>">
                </div>
                <div class="form-group">
                    <label for="limitacao_reservas_imobiliaria">Limite de Reservas por Imobiliária:</label>
                    <input type="number" id="limitacao_reservas_imobiliaria" name="limitacao_reservas_imobiliaria" min="0" value="<?php echo htmlspecialchars($form_data_etapa_7['limitacao_reservas_imobiliaria'] ?? 0); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="prazo_expiracao_reserva">Prazo de Expiração da Reserva (dias):</label>
                <input type="number" id="prazo_expiracao_reserva" name="prazo_expiracao_reserva" min="1" value="<?php echo htmlspecialchars($form_data_etapa_7['prazo_expiracao_reserva'] ?? 7); ?>">
            </div>

            <div class="form-group">
                <label>Documentos Necessários (para download público):</label>
                <div>
                    <label><input type="checkbox" name="documentos_necessarios_etapa7[]" value="RG" <?php echo (in_array('RG', $form_data_etapa_7['documentos_necessarios_etapa7'] ?? [])) ? 'checked' : ''; ?>> RG</label><br>
                    <label><input type="checkbox" name="documentos_necessarios_etapa7[]" value="CPF" <?php echo (in_array('CPF', $form_data_etapa_7['documentos_necessarios_etapa7'] ?? [])) ? 'checked' : ''; ?>> CPF</label><br>
                    <label><input type="checkbox" name="documentos_necessarios_etapa7[]" value="Comprovante de Renda" <?php echo (in_array('Comprovante de Renda', $form_data_etapa_7['documentos_necessarios_etapa7'] ?? [])) ? 'checked' : ''; ?>> Comprovante de Renda</label><br>
                    <label><input type="checkbox" name="documentos_necessarios_etapa7[]" value="Comprovante de Residência" <?php echo (in_array('Comprovante de Residência', $form_data_etapa_7['documentos_necessarios_etapa7'] ?? [])) ? 'checked' : ''; ?>> Comprovante de Residência</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="prev_step" class="btn btn-secondary">Etapa Anterior</button>
                <button type="submit" name="next_step" class="btn btn-primary">Finalizar Cadastro</button>
            </div>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>