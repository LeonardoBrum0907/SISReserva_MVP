<?php
// admin/empreendimentos/criar_e_editar.php - Wizard de Cadastro/Edição de Empreendimento
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para format_currency_brl() e outras helpers

// Inicia a sessão para persistir o empreendimento_id
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/empreendimentos/criar_e_editar.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}

// Redireciona se não for um admin master
require_permission(['admin']);

$page_title = "Cadastro de Empreendimento";
$empreendimento_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$current_step = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT) ?? 1; // Permite iniciar em uma etapa específica
$unidade_id_from_url = filter_input(INPUT_GET, 'unidade_id', FILTER_VALIDATE_INT); // ID da unidade para pré-seleção na Etapa 6

// --- INICIALIZAÇÃO DE VARIÁVEIS PARA GARANTIR DEFINIÇÃO E EVITAR WARNINGS ---
$empreendimento_data = [];
$tipos_unidades_data = [];
$areas_comuns_selecionadas = []; // Nomes das áreas selecionadas
$unidades_data = [];
$corretores_disponiveis = [];
$imobiliarias_disponiveis = [];
$midias_data = []; 
$unidades_por_andar_data_js = new stdClass(); // Objeto vazio para JS, para evitar erros se não houver dados
$empreendimento_data['corretores_permitidos_ids'] = []; 
$empreendimento_data['imobiliarias_permitidas_ids'] = []; 
$fluxo_pagamento_etapa6_data = null; // Inicializado como null, será populado da unidade
// --- FIM DA INICIALIZAÇÃO ---

// Se for edição, carrega os dados existentes
if ($empreendimento_id) {
    $_SESSION['current_empreendimento_id'] = $empreendimento_id; // Garante que o ID está na sessão para as APIs

    try {
        // Carregar dados do empreendimento 
        $sql_empreendimento = "SELECT * FROM empreendimentos WHERE id = ?";
        $empreendimento_data = fetch_single($sql_empreendimento, [$empreendimento_id], "i");

        if (!$empreendimento_data) {
            header("Location: " . BASE_URL . "admin/empreendimentos/index.php?error=notfound");
            exit();
        }

        // Carregar tipos de unidades
        $sql_tipos_unidades = "SELECT id, tipo, metragem, quartos, banheiros, vagas, foto_planta FROM tipos_unidades WHERE empreendimento_id = ?";
        $tipos_unidades_data = fetch_all($sql_tipos_unidades, [$empreendimento_id], "i");

        // Carregar áreas comuns
        $sql_areas_comuns = "SELECT area_comum_id FROM empreendimentos_areas_comuns WHERE empreendimento_id = ?";
        $areas_comuns_selecionadas_raw = fetch_all($sql_areas_comuns, [$empreendimento_id], "i");
        $areas_comuns_selecionadas = array_column($areas_comuns_selecionadas_raw, 'area_comum_id');

        // Carregar unidades individuais (agora com 'informacoes_pagamento')
        // Atenção: As colunas 'andar' e 'area' são usadas conforme o mvpreserva.sql
        $sql_unidades = "SELECT id, tipo_unidade_id, numero, andar, posicao, area, multiplier, valor, informacoes_pagamento FROM unidades WHERE empreendimento_id = ?";
        $unidades_data = fetch_all($sql_unidades, [$empreendimento_id], "i");
        error_log("DEBUG criar_e_editar.php: Unidades carregadas para JS: " . print_r($unidades_data, true)); // DEBUG LOG

        // Carregar mídias
        $sql_midias = "SELECT tipo, caminho_arquivo FROM midias_empreendimentos WHERE empreendimento_id = ?";
        $midias_data = fetch_all($sql_midias, [$empreendimento_id], "i");

        // Popula $unidades_por_andar_data_js se o campo existir no DB (JSON `unidades_por_andar` na tabela `empreendimentos`)
        if (isset($empreendimento_data['unidades_por_andar']) && !empty($empreendimento_data['unidades_por_andar'])) {
            $decoded_unidades_por_andar = json_decode($empreendimento_data['unidades_por_andar'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_unidades_por_andar)) {
                $unidades_por_andar_data_js = (object) $decoded_unidades_por_andar;
            }
        }
        
        // Popula o fluxo_pagamento_etapa6_data a partir da primeira unidade (ou da unidade via URL se especificada)
        if (!empty($unidades_data)) {
            $target_unit = null;
            if ($unidade_id_from_url) {
                // Tenta encontrar a unidade específica da URL
                foreach ($unidades_data as $unit) {
                    if ($unit['id'] == $unidade_id_from_url) {
                        $target_unit = $unit;
                        break;
                    }
                }
            }
            // Se não encontrou pela URL ou não havia ID na URL, pega a primeira unidade
            if (!$target_unit) {
                $target_unit = $unidades_data[0];
            }

            if (isset($target_unit['informacoes_pagamento']) && !empty($target_unit['informacoes_pagamento'])) {
                $decoded_payment_info = json_decode($target_unit['informacoes_pagamento'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_payment_info)) {
                    $fluxo_pagamento_etapa6_data = $decoded_payment_info;
                }
            }
        }


        // Permissões de reserva
        $sql_permissoes_corretores = "SELECT corretor_id FROM empreendimentos_corretores_permitidos WHERE empreendimento_id = ?";
        $corretores_permitidos_raw = fetch_all($sql_permissoes_corretores, [$empreendimento_id], "i");
        $empreendimento_data['corretores_permitidos_ids'] = array_column($corretores_permitidos_raw, 'corretor_id');

        $sql_permissoes_imobiliarias = "SELECT imobiliaria_id FROM empreendimentos_imobiliarias_permitidas WHERE empreendimento_id = ?";
        $imobiliarias_permitidas_raw = fetch_all($sql_permissoes_imobiliarias, [$empreendimento_id], "i");
        $empreendimento_data['imobiliarias_permitidas_ids'] = array_column($imobiliarias_permitidas_raw, 'imobiliaria_id');

    } catch (Exception $e) {
        error_log("Erro ao carregar dados do empreendimento para edição: " . $e->getMessage());
    }
} 

// Carregar catálogo de áreas comuns para a Etapa 3
$areas_comuns_catalogo = [];
try {
    $sql_catalogo = "SELECT id, nome FROM areas_comuns_catalogo ORDER BY nome";
    $areas_comuns_catalogo = fetch_all($sql_catalogo);
} catch (Exception $e) {
    error_log("Erro ao carregar catálogo de áreas comuns: " . $e->getMessage());
}

// Carregar todos os corretores e imobiliárias para os selects de permissão (Etapa 7)
try {
    $corretores_disponiveis = fetch_all("SELECT id, nome, creci FROM usuarios WHERE tipo LIKE 'corretor_%' AND ativo = TRUE ORDER BY nome ASC");
    $imobiliarias_disponiveis = fetch_all("SELECT id, nome FROM imobiliarias WHERE ativa = TRUE ORDER BY nome ASC");
} catch (Exception $e) {
    error_log("Erro ao carregar corretores/imobiliárias para permissões: " . $e->getMessage());
}


require_once '../../includes/header_dashboard.php';

// --- INÍCIO DOS LOGS DE DEBUG NO PHP ---
error_log("DEBUG PHP: corretoresDisponiveis antes de JSON encode: " . print_r($corretores_disponiveis, true));
error_log("DEBUG PHP: imobiliariasDisponiveis antes de JSON encode: " . print_r($imobiliarias_disponiveis, true));
// --- FIM DOS LOGS DE DEBUG NO PHP ---
?>

<style>
    /* Estilos básicos para o wizard (removidos os estilos de cores e tamanhos duplicados do dashboard.css) */
    .wizard-container {
        background-color: var(--color-background-primary);
        padding: 30px;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
        max-width: 900px;
        margin: 30px auto;
    }
    .wizard-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .wizard-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
    }
    .wizard-steps::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 2px;
        background-color: var(--color-primary-light);
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        z-index: 0;
    }
    .wizard-step {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    .wizard-step .circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--color-primary-light);
        color: var(--color-text-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }
    .wizard-step.active .circle {
        background-color: var(--color-accent-warm);
        color: var(--color-text-light);
    }
    .wizard-step.completed .circle {
        background-color: var(--color-success);
        color: var(--color-text-light);
    }
    .wizard-step .label {
        font-size: 0.9em;
        color: var(--color-primary);
    }
    .wizard-step.active .label {
        color: var(--color-accent-warm-dark);
        font-weight: bold;
    }

    .step-section { 
        display: none; /* Controlado por JS */
    }
    .step-section.active {
        display: block;
    }
    /* Estilos de form-group, input, textarea, select, error-message, btn, add-row-btn já estão no dashboard.css */
    /* Dynamic table styles are also in dashboard.css */
    /* Ações do wizard */
    .wizard-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
        gap: 10px;
    }
    .wizard-actions .btn {
        padding: 12px 25px;
        font-size: 1em;
        cursor: pointer;
        border-radius: var(--border-radius-md);
        transition: background-color 0.3s ease;
    }
    /* Cores dos botões do wizard vêm do dashboard.css */
    .wizard-actions .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .info-box {
        background-color: var(--color-info); /* Use a cor info do seu design system */
        color: var(--color-text-light);
        padding: 15px;
        border-radius: var(--border-radius-md);
        margin-bottom: 20px;
        border: 1px solid var(--color-info);
    }
    .info-box strong {
        color: var(--color-text-light);
    }
    .form-group-row {
        display: flex;
        gap: var(--spacing-md);
        align-items: flex-end;
        margin-bottom: var(--spacing-md);
    }
    .form-group-row .form-group {
        flex: 1;
        margin-bottom: 0;
    }
    .form-group-row .action-column {
        flex-shrink: 0;
    }
    .img-preview, .img-gallery-preview {
        max-width: 150px;
        height: auto;
        display: block;
        margin-top: 10px;
        border-radius: var(--border-radius-sm);
        border: 1px solid var(--color-border);
    }
    /* Estilos para o painel do construtor de pagamento (Etapa 6) */
    #payment_flow_builder_panel {
        background-color: var(--color-background-secondary);
        padding: var(--spacing-lg);
        border-radius: var(--border-radius-md);
        margin-top: var(--spacing-md);
        border: 1px solid var(--color-border);
    }
    .payment-info-summary p {
        margin-bottom: 5px;
    }
    .payment-info-summary .text-red { color: var(--color-danger); }
    .payment-info-summary .text-green { color: var(--color-success); }
    
</style>

<div class="admin-content-wrapper">
    <div class="wizard-container">
        <div class="wizard-header">
            <h2><?php echo htmlspecialchars($page_title); ?></h2>
        </div>

        <div class="wizard-steps">
            <div class="wizard-step" data-step="1">
                <div class="circle">1</div>
                <div class="label">Dados Básicos</div>
            </div>
            <div class="wizard-step" data-step="2">
                <div class="circle">2</div>
                <div class="label">Tipos de Unidade</div>
            </div>
            <div class="wizard-step" data-step="3">
                <div class="circle">3</div>
                <div class="label">Áreas Comuns</div>
            </div>
            <div class="wizard-step" data-step="4">
                <div class="circle">4</div>
                <div class="label">Estoque de Unidades</div>
            </div>
            <div class="wizard-step" data-step="5">
                <div class="circle">5</div>
                <div class="label">Mídias</div>
            </div>
            <div class="wizard-step" data-step="6">
                <div class="circle">6</div>
                <div class="label">Fluxo de Pagamento</div>
            </div>
            <div class="wizard-step" data-step="7">
                <div class="circle">7</div>
                <div class="label">Regras e Permissões</div>
            </div>
        </div>

        <form id="empreendimento-wizard-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="empreendimento_id" name="empreendimento_id" value="<?php echo htmlspecialchars($empreendimento_id ?? ''); ?>">
            <input type="hidden" id="current_step_input" name="current_step" value="<?php echo htmlspecialchars($current_step); ?>">

            <div class="step-section" id="step-1">
                <h3>Etapa 1: Dados Básicos do Empreendimento</h3>
                <div class="form-group">
                    <label for="nome">Nome do Empreendimento <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Residencial Flores do Campo" maxlength="255" required value="<?php echo htmlspecialchars($empreendimento_data['nome'] ?? ''); ?>">
                    <span class="error-message" id="error-nome"></span>
                </div>
                <div class="form-group">
                    <label for="tipo_empreendimento">Modelo societário <span class="required">*</span></label>
                    <input type="text" id="tipo_empreendimento" name="tipo_empreendimento" class="form-control" placeholder="Ex: SCP, SPE (preço de custo), ..." maxlength="100" required value="<?php echo htmlspecialchars($empreendimento_data['tipo_empreendimento'] ?? ''); ?>">
                    <span class="error-message" id="error-tipo_empreendimento"></span>
                </div>
                <div class="form-group">
                    <label for="tipo_uso">Tipo de Uso <span class="required">*</span></label>
                    <select id="tipo_uso" name="tipo_uso" class="form-control" required>
                        <option value="">Selecione</option>
                        <option value="Residencial" <?php echo (isset($empreendimento_data['tipo_uso']) && $empreendimento_data['tipo_uso'] == 'Residencial') ? 'selected' : ''; ?>>Residencial</option>
                        <option value="Comercial" <?php echo (isset($empreendimento_data['tipo_uso']) && $empreendimento_data['tipo_uso'] == 'Comercial') ? 'selected' : ''; ?>>Comercial</option>
                        <option value="Misto" <?php echo (isset($empreendimento_data['tipo_uso']) && $empreendimento_data['tipo_uso'] == 'Misto') ? 'selected' : ''; ?>>Misto</option>
                    </select>
                    <span class="error-message" id="error-tipo_uso"></span>
                </div>
                <div class="form-group">
                    <label for="cep">CEP <span class="required">*</span></label>
                    <input type="text" id="cep" name="cep" class="form-control cep-mask" placeholder="99999-999" maxlength="9" required value="<?php echo htmlspecialchars($empreendimento_data['cep'] ?? ''); ?>" onblur="window.buscarCEP(this.value, 'endereco', 'bairro', 'cidade', 'estado')">
                    <span class="error-message" id="error-cep"></span>
                </div>
                <div class="form-group">
                    <label for="endereco">Endereço <span class="required">*</span></label>
                    <input type="text" id="endereco" name="endereco" class="form-control" placeholder="Rua das Acácias" maxlength="255" required value="<?php echo htmlspecialchars($empreendimento_data['endereco'] ?? ''); ?>">
                    <span class="error-message" id="error-endereco"></span>
                </div>
                <div class="form-group">
                    <label for="numero">Número <span class="required">*</span></label>
                    <input type="text" id="numero" name="numero" class="form-control" placeholder="123" maxlength="20" required value="<?php echo htmlspecialchars($empreendimento_data['numero'] ?? ''); ?>">
                    <span class="error-message" id="error-numero"></span>
                </div>
                <div class="form-group">
                    <label for="complemento">Complemento</label>
                    <input type="text" id="complemento" name="complemento" class="form-control" placeholder="Apto 101, Bloco B" maxlength="100" value="<?php echo htmlspecialchars($empreendimento_data['complemento'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="bairro">Bairro <span class="required">*</span></label>
                    <input type="text" id="bairro" name="bairro" class="form-control" placeholder="Centro" maxlength="100" required value="<?php echo htmlspecialchars($empreendimento_data['bairro'] ?? ''); ?>">
                    <span class="error-message" id="error-bairro"></span>
                </div>
                <div class="form-group">
                    <label for="cidade">Cidade <span class="required">*</span></label>
                    <input type="text" id="cidade" name="cidade" class="form-control" placeholder="São Paulo" maxlength="100" required value="<?php echo htmlspecialchars($empreendimento_data['cidade'] ?? ''); ?>">
                    <span class="error-message" id="error-cidade"></span>
                </div>
                <div class="form-group">
                    <label for="estado">Estado <span class="required">*</span></label>
                    <select id="estado" name="estado" class="form-control" required>
                        <option value="">Selecione</option>
                        <?php
                        $estados_br = ["AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA", "MT", "MS", "MG", "PA", "PB", "PR", "PE", "PI", "RJ", "RN", "RS", "RO", "RR", "SC", "SP", "SE", "TO"];
                        foreach ($estados_br as $uf) {
                            $selected = (isset($empreendimento_data['estado']) && $empreendimento_data['estado'] == $uf) ? 'selected' : '';
                            echo "<option value=\"{$uf}\" {$selected}>{$uf}</option>";
                        }
                        ?>
                    </select>
                    <span class="error-message" id="error-estado"></span>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição <span class="required">*</span></label>
                    <textarea id="descricao" name="descricao" class="form-control" placeholder="Descrição detalhada do empreendimento para a página pública." maxlength="5000" required><?php echo htmlspecialchars($empreendimento_data['descricao'] ?? ''); ?></textarea>
                    <span class="error-message" id="error-descricao"></span>
                </div>
                <div class="form-group">
                    <label for="status">Status Operacional <span class="required">*</span></label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="">Selecione o Status Operacional</option>
                        <option value="ativo" <?php echo (isset($empreendimento_data['status']) && $empreendimento_data['status'] == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                        <option value="pausado" <?php echo (isset($empreendimento_data['status']) && $empreendimento_data['status'] == 'pausado') ? 'selected' : ''; ?>>Pausado</option>
                    </select>
                    <span class="error-message" id="error-status"></span>
                </div>

                <div class="form-group">
                    <label for="fase_empreendimento">Fase do Empreendimento <span class="required">*</span></label>
                    <select id="fase_empreendimento" name="fase_empreendimento" class="form-control" required>
                        <option value="">Selecione a Fase</option>
                        <option value="pre_lancamento" <?php echo (isset($empreendimento_data['fase_empreendimento']) && $empreendimento_data['fase_empreendimento'] == 'pre_lancamento') ? 'selected' : ''; ?>>Pré-Lançamento</option>
                        <option value="lancamento" <?php echo (isset($empreendimento_data['fase_empreendimento']) && $empreendimento_data['fase_empreendimento'] == 'lancamento') ? 'selected' : ''; ?>>Lançamento</option>
                        <option value="em_obra" <?php echo (isset($empreendimento_data['fase_empreendimento']) && $empreendimento_data['fase_empreendimento'] == 'em_obra') ? 'selected' : ''; ?>>Em Obra</option>
                        <option value="pronto_para_morar" <?php echo (isset($empreendimento_data['fase_empreendimento']) && $empreendimento_data['fase_empreendimento'] == 'pronto_para_morar') ? 'selected' : ''; ?>>Pronto para Morar</option>
                    </select>
                    <span class="error-message" id="error-fase_empreendimento"></span>
                </div>
                <div class="form-group">
                    <label for="preco_por_m2_sugerido">Preço base do M² (R$) <span class="required">*</span></label>
                    <input type="text" id="preco_por_m2_sugerido" name="preco_por_m2_sugerido" class="form-control currency-mask" placeholder="8.500,00" required value="<?php echo htmlspecialchars($empreendimento_data['preco_por_m2_sugerido'] ?? ''); ?>">
                    <span class="error-message" id="error-preco_por_m2_sugerido"></span>
                </div>
                <div class="form-group">
                    <label for="data_lancamento">Data de Lançamento</label>
                    <input type="date" id="data_lancamento" name="data_lancamento" class="form-control" value="<?php echo htmlspecialchars($empreendimento_data['data_lancamento'] ?? ''); ?>">
                    <span class="error-message" id="error-data_lancamento"></span>
                </div>
                <div class="form-group">
                    <label for="previsao_entrega">Previsão de Entrega</label>
                    <input type="date" id="previsao_entrega" name="previsao_entrega" class="form-control" value="<?php echo htmlspecialchars($empreendimento_data['previsao_entrega'] ?? ''); ?>">
                    <span class="error-message" id="error-previsao_entrega"></span>
                </div>
                <div class="form-group">
                    <label for="momento_envio_documentacao">Momento de Envio da Documentação <span class="required">*</span></label>
                    <select id="momento_envio_documentacao" name="momento_envio_documentacao" class="form-control" required>
                        <option value="">Selecione</option>
                        <option value="Na Proposta de Reserva" <?php echo (isset($empreendimento_data['momento_envio_documentacao']) && $empreendimento_data['momento_envio_documentacao'] == 'Na Proposta de Reserva') ? 'selected' : ''; ?>>Na Proposta de Reserva</option>
                        <option value="Após Confirmação de Reserva" <?php echo (isset($empreendimento_data['momento_envio_documentacao']) && $empreendimento_data['momento_envio_documentacao'] == 'Após Confirmação de Reserva') ? 'selected' : ''; ?>>Após Confirmação de Reserva</option>
                        <option value="Na Assinatura do Contrato" <?php echo (isset($empreendimento_data['momento_envio_documentacao']) && $empreendimento_data['momento_envio_documentacao'] == 'Na Assinatura do Contrato') ? 'selected' : ''; ?>>Na Assinatura do Contrato</option>
                    </select>
                    <span class="error-message" id="error-momento_envio_documentacao"></span>
                </div>
                <div class="form-group" id="documentos_obrigatorios_group" style="display: none;">
                    <label for="documentos_obrigatorios">Documentos Obrigatórios para Reserva (separados por vírgula) <span class="required">*</span></label>
                    <textarea id="documentos_obrigatorios" name="documentos_obrigatorios" class="form-control" placeholder="Ex: RG, CPF, Comprovante de Renda, Certidão de Casamento"><?php
                        if (isset($empreendimento_data['documentos_obrigatorios']) && !empty($empreendimento_data['documentos_obrigatorios'])) {
                            $docs_array = json_decode($empreendimento_data['documentos_obrigatorios'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($docs_array)) {
                                echo htmlspecialchars(implode(', ', $docs_array));
                            }
                        }
                    ?></textarea>
                    <span class="error-message" id="error-documentos_obrigatorios"></span>
                    <small>Estes documentos serão exigidos para reservas e poderão ser enviados pelos corretores ou clientes.</small>
                </div>
            </div>

            <div class="step-section" id="step-2">
                <h3>Etapa 2: Tipos de Unidade</h3>
                <p>Defina os tipos genéricos de unidades que o empreendimento oferece.</p>
                <table class="dynamic-table" id="tipos_unidades_container">
                    <thead>
                        <tr>
                            <th>Tipo de Unidade <span class="required">*</span></th>
                            <th>Metragem (m²) <span class="required">*</span></th>
                            <th>Quartos <span class="required">*</span></th>
                            <th>Banheiros <span class="required">*</span></th>
                            <th>Vagas <span class="required">*</span></th>
                            <th>Foto Planta</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody >
                        </tbody>
                </table>
                <button type="button" id="add_tipo_unidade" class="add-row-btn"><i class="fas fa-plus"></i> Adicionar Tipo de Unidade</button>
                <span class="error-message" id="error-tipos_unidade"></span>
            </div>

            <div class="step-section" id="step-3">
                <h3>Etapa 3: Áreas Comuns e Lazer</h3>
                <p>Selecione as áreas comuns e de lazer disponíveis no empreendimento.</p>
                <div class="form-group">
                    <label>Selecione as Áreas Comuns e de Lazer</label>
                    <div class="checkbox-group">
                        <?php 
                        // CORREÇÃO: Evitar duplicação de checkboxes, renderizar apenas o que vem do PHP
                        foreach ($areas_comuns_catalogo as $area): 
                            $checked = in_array($area['id'], $areas_comuns_selecionadas) ? 'checked' : ''; ?>
                            <label>
                                <input type="checkbox" name="areas_comuns_selecionadas[]" value="<?php echo htmlspecialchars($area['id']); ?>" <?php echo $checked; ?>>
                                <?php echo htmlspecialchars($area['nome']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="outras_areas_comuns">Outras Áreas Comuns (Opcional)</label>
                    <textarea id="outras_areas_comuns" name="outras_areas_comuns" class="form-control" placeholder="Descreva outras áreas não listadas acima, separadas por vírgula." maxlength="1000"><?php echo htmlspecialchars($empreendimento_data['outras_areas_comuns'] ?? ''); ?></textarea>
                    <span class="error-message" id="error-outras_areas_comuns"></span>
                </div>
            </div>

            <div class="step-section" id="step-4">
                <h3>Etapa 4: Plantas e Valores (Montagem do Estoque)</h3>
                <div class="info-box">
                    <strong>Preço base do M²:</strong> <span id="display-preco-m2-sugerido"><?php echo htmlspecialchars(format_currency_brl($empreendimento_data['preco_por_m2_sugerido'] ?? 0)); ?></span>
                </div>

                <div class="form-group-row">
                    <div class="form-group flex-grow">
                        <label for="andar">Número Total de Andares</label>
                        <input type="number" id="andar" name="andar" class="form-control" min="0" value="<?php echo htmlspecialchars($empreendimento_data['andar'] ?? ''); ?>">
                    </div>
                    <div class="form-group flex-grow" id="unidades_por_andar_container">
                        </div>
                    <div class="form-group action-column">
                        <button type="button" id="generate_units_btn" class="btn btn-info btn-sm">Gerar Unidades por Andar</button>
                    </div>
                </div>
                
                <h4>Ferramentas de Lote:</h4>
                <div class="form-group-row">
                    <div class="form-group">
                        <label for="batch_value">Preencher Valor (R$)</label>
                        <input type="text" id="batch_value" class="form-control currency-mask" placeholder="Ex: 250.000,00">
                    </div>
                    <div class="form-group action-column">
                        <button type="button" id="apply_batch_value" class="btn btn-secondary btn-sm">Aplicar a Todas</button>
                    </div>
                </div>
                <div class="form-group-row">
                    <div class="form-group">
                        <label for="batch_tipo_unidade_id">Atribuir Tipo de Planta</label>
                        <select id="batch_tipo_unidade_id" class="form-control">
                            </select>
                    </div>
                    <div class="form-group action-column">
                        <button type="button" id="apply_batch_type" class="btn btn-secondary btn-sm">Aplicar a Todas</button>
                    </div>
                </div>

                <table class="dynamic-table units-stock-table">
                    <thead>
                        <tr>
                            <th>Tipo <span class="required">*</span></th>
                            <th>Número <span class="required">*</span></th>
                            <th>Andar <span class="required">*</span></th>
                            <th>Posição (Final)</th>
                            <th>Área Privativa (m²) <span class="required">*</span></th>
                            <th>Multiplicador <span class="required">*</span></th>
                            <th>Valor Sugerido (R$)</th>
                            <th>Valor Final de Venda (R$) <span class="required">*</span></th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody id="units_stock_tbody">
                        </tbody>
                </table>
                <button type="button" id="add_unit_stock_row_manual" class="add-row-btn"><i class="fas fa-plus"></i> Adicionar Unidade Manualmente</button>
                <span class="error-message" id="error-unidades"></span>
            </div>

            <div class="step-section" id="step-5">
                <h3>Etapa 5: Mídias</h3>
                <div class="form-group">
                    <label for="foto_principal">Foto Principal do Empreendimento <span class="required">*</span></label>
                    <input type="file" id="foto_principal" name="foto_principal" class="form-control" accept="image/*" <?php echo (isset($empreendimento_data['foto_principal']) && empty($empreendimento_data['foto_principal'])) ? 'required' : ''; ?>>
                    <div id="foto_principal_preview">
                        <?php if (isset($empreendimento_data['foto_principal']) && !empty($empreendimento_data['foto_principal'])): ?>
                            <img src="<?php echo BASE_URL . htmlspecialchars($empreendimento_data['foto_principal']); ?>" alt="Foto Principal Atual" class="img-preview">
                        <?php endif; ?>
                    </div>
                    <span class="error-message" id="error-foto_principal"></span>
                </div>
                <div class="form-group">
                    <label for="galeria_fotos">Galeria de Fotos (Múltiplas)</label>
                    <input type="file" id="galeria_fotos" name="galeria_fotos[]" class="form-control" accept="image/*" multiple>
                    <div id="galeria_fotos_preview">
                        </div>
                </div>
                <div class="form-group">
                    <label>Vídeos do YouTube</label>
                    <div id="videos_youtube_container">
                        </div>
                    <button type="button" id="add_video_url" class="btn btn-info btn-sm mt-2"><i class="fas fa-plus"></i> Adicionar URL de Vídeo</button>
                </div>
                <div class="form-group">
                    <label for="documento_contrato">Documento de Contrato Padrão (PDF)</label>
                    <input type="file" id="documento_contrato" name="documento_contrato" class="form-control" accept=".pdf">
                    <?php if (isset($empreendimento_data['documento_contrato']) && !empty($empreendimento_data['documento_contrato'])): ?>
                        <small>Arquivo atual: <a href="<?php echo BASE_URL . htmlspecialchars($empreendimento_data['documento_contrato']); ?>" target="_blank"><?php echo htmlspecialchars(basename($empreendimento_data['documento_contrato'])); ?></a></small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="documento_memorial">Memorial Descritivo (PDF)</label>
                    <input type="file" id="documento_memorial" name="documento_memorial" class="form-control" accept=".pdf">
                    <?php if (isset($empreendimento_data['documento_memorial']) && !empty($empreendimento_data['documento_memorial'])): ?>
                        <small>Arquivo atual: <a href="<?php echo BASE_URL . htmlspecialchars($empreendimento_data['documento_memorial']); ?>" target="_blank"><?php echo htmlspecialchars(basename($empreendimento_data['documento_memorial'])); ?></a></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="step-section" id="step-6">
                <h3>Etapa 6: Fluxo de Pagamento</h3>
                <div class="info-box">
                    <p>Defina a estrutura de pagamento para suas unidades. As porcentagens e valores serão automaticamente ajustados para cada unidade com base no seu valor total. Primeiro, selecione uma unidade exemplo para construir o plano.</p>
                </div>
                <div class="form-group">
                    <label for="unidade_exemplo_id">Selecione uma Unidade Exemplo <span class="required">*</span></label>
                    <select id="unidade_exemplo_id" name="unidade_exemplo_id" class="form-control" required>
                        <?php
                        // Popula a lista de unidades disponíveis na Etapa 6
                        if (!empty($unidades_data)) {
                            foreach ($unidades_data as $index => $unidade) {
                                $selected = '';
                                if ($unidade_id_from_url && $unidade['id'] == $unidade_id_from_url) {
                                    $selected = 'selected';
                                } else if (!$unidade_id_from_url && $index == 0) {
                                    // Se nenhuma unidade for especificada pela URL, selecione a primeira por padrão
                                    $selected = 'selected';
                                }
                                echo '<option value="' . htmlspecialchars($index) . '" data-unit-id="' . htmlspecialchars($unidade['id']) . '" data-unit-value="' . htmlspecialchars($unidade['valor']) . '" ' . $selected . '>';
                                echo 'Unidade ' . htmlspecialchars($unidade['numero']) . ' (Andar: ' . htmlspecialchars($unidade['andar']) . ') - R$ ' . htmlspecialchars(number_format($unidade['valor'], 2, ',', '.'));
                                echo '</option>';
                            }
                        } else {
                            echo '<option value="">Nenhuma unidade disponível</option>';
                        }
                        ?>
                    </select>
                    <span class="error-message" id="error-unidade_exemplo_id"></span>
                </div>

                <div id="payment_flow_builder_panel" style="display: none;">
                    <p class="mt-3"><strong>Valor da Unidade Exemplo:</strong> <span id="payment_unit_value_display"><?php echo format_currency_brl(0); ?></span></p>
                    <table class="dynamic-table payment-flow-table mt-3">
                        <thead>
                            <tr>
                                <th>Descrição <span class="required">*</span></th>
                                <th>Parcelas <span class="required">*</span></th>
                                <th>Tipo de Valor <span class="required">*</span></th>
                                <th>Cálculo <span class="required">*</span></th>
                                <th>Valor / % <span class="required">*</span></th>
                                <th>Total Condição</th>
                                <th>Valor por Parcela</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="payment_flow_tbody">
                            </tbody>
                    </table>
                    <button type="button" id="add_parcela" class="add-row-btn"><i class="fas fa-plus"></i> Adicionar Condição</button>
                    <div class="payment-info-summary mt-3">
                        <p><strong>Total do Plano:</strong> <span id="total_plano_display">R$ 0,00</span></p>
                        <p id="total_plano_validation" class="text-red"></p>
                    </div>
                </div>
            </div>

            <div class="step-section" id="step-7">
                <h3>Etapa 7: Permissões e Regras de Negócio</h3>
                <div class="form-group">
                    <label>Quem pode visualizar este empreendimento?</label>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="permissoes_visualizacao[]" value="Cliente Final" <?php echo (isset($empreendimento_data['permissoes_visualizacao']) && (strpos($empreendimento_data['permissoes_visualizacao'], 'Cliente Final') !== false)) ? 'checked' : ''; ?>>
                            Cliente Final (Visitantes)
                        </label>
                        <label>
                            <input type="checkbox" name="permissoes_visualizacao[]" value="Corretores" <?php echo (isset($empreendimento_data['permissoes_visualizacao']) && (strpos($empreendimento_data['permissoes_visualizacao'], 'Corretores') !== false)) ? 'checked' : ''; ?>>
                            Apenas Usuários Logados (Corretores/Admins)
                        </label>
                    </div>
                    <span class="error-message" id="error-permissoes_visualizacao"></span>
                </div>

                <div class="form-group">
                    <label>Quem pode fazer reservas neste empreendimento?</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="permissao_reserva_tipo" value="Todos" <?php echo (isset($empreendimento_data['permissao_reserva']) && $empreendimento_data['permissao_reserva'] == 'Todos') ? 'checked' : ''; ?> required>
                            Todos (Visitantes e Corretores)
                        </label>
                        <label>
                            <input type="radio" name="permissao_reserva_tipo" value="Corretores Selecionados" <?php echo (isset($empreendimento_data['permissao_reserva']) && $empreendimento_data['permissao_reserva'] == 'Corretores Selecionados') ? 'checked' : ''; ?> required>
                            Corretores Selecionados
                        </label>
                        <label>
                            <input type="radio" name="permissao_reserva_tipo" value="Imobiliarias Selecionadas" <?php echo (isset($empreendimento_data['permissao_reserva']) && $empreendimento_data['permissao_reserva'] == 'Imobiliarias Selecionadas') ? 'checked' : ''; ?> required>
                            Imobiliárias Selecionadas
                        </label>
                    </div>
                    <span class="error-message" id="error-permissao_reserva_tipo"></span>
                </div>

                <div id="corretores_imobiliarias_selecao" style="display: none;" class="mt-3">
                    <div class="form-group" id="corretores_selecao_group" style="display: none;">
                        <label for="corretores_permitidos">Selecione os Corretores Permitidos</label>
                        <select id="corretores_permitidos" name="corretores_permitidos[]" class="form-control" multiple size="5">
                            </select>
                        <small>Segure CTRL/CMD para selecionar múltiplos.</small>
                    </div>
                    <div class="form-group" id="imobiliarias_selecao_group" style="display: none;">
                        <label for="imobiliarias_permitidas">Selecione as Imobiliárias Permitidas</label>
                        <select id="imobiliarias_permitidas" name="imobiliarias_permitidas[]" class="form-control" multiple size="5">
                            </select>
                        <small>Segure CTRL/CMD para selecionar múltiplas.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="prazo_expiracao_reserva">Prazo de Expiração da Reserva (dias)</label>
                    <input type="number" id="prazo_expiracao_reserva" name="prazo_expiracao_reserva" class="form-control" min="1" max="90" value="<?php echo htmlspecialchars($empreendimento_data['prazo_expiracao_reserva'] ?? 7); ?>">
                    <small>Número de dias que uma reserva dura antes de expirar automaticamente.</small>
                </div>
            </div>

            <div class="wizard-actions">
                <button type="button" id="prev-step-btn" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</button>
                <button type="button" id="next-step-btn" class="btn btn-primary">Salvar e Continuar <i class="fas fa-arrow-right"></i></button>
                <button type="submit" id="submit-wizard-btn" class="btn btn-primary" style="display: none;"><i class="fas fa-save"></i> Finalizar Cadastro</button>
            </div>
        </form>
    </div>
</div>



<script>
    // Variáveis PHP passadas para o JavaScript
    window.empreendimentoData = <?php echo json_encode($empreendimento_data); ?>;
    window.currentStep = <?php echo json_encode($current_step); ?>;
    window.tiposUnidadesData = <?php echo json_encode($tipos_unidades_data); ?>;
    window.unidadesEstoqueData = <?php echo json_encode($unidades_data); ?>;
    window.midiasData = <?php echo json_encode($midias_data); ?>;
    // AGORA: Passa o 'informacoes_pagamento' da unidade (se existir) para o JS
    window.fluxoPagamentoEtapa6Data = <?php echo json_encode($fluxo_pagamento_etapa6_data); ?>; 
    window.selectedAreasComuns = <?php echo json_encode($areas_comuns_selecionadas); ?>;
    window.corretoresDisponiveis = <?php echo json_encode($corretores_disponiveis); ?>;
    window.imobiliariasDisponiveis = <?php echo json_encode($imobiliarias_disponiveis); ?>;
    window.numAndaresEmpreendimento = <?php echo json_encode($empreendimento_data['andar'] ?? 0); ?>;
    window.unidadesPorAndarData = <?php echo json_encode($unidades_por_andar_data_js); ?>; 
    window.unidadeIdFromURL = <?php echo json_encode($unidade_id_from_url); ?>;
</script>

<?php require_once '../../includes/footer_dashboard.php'; ?>