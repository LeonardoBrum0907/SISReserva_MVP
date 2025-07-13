// js/admin.js - Lógicas JavaScript para o Painel Administrativo
document.addEventListener('DOMContentLoaded', () => {
    // ===============================================
    // DEFINIÇÕES GLOBAIS E FUNÇÕES AUXILIARES
    // (MOVIDAS PARA O TOPO DO ESCOPO DOMContentLoaded PARA GARANTIR ACESSIBILIDADE E INTEGRIDADE)
    // ===============================================
    const BASE_URL = '/SISReserva_MVP/';
    // Dados passados do PHP para o JavaScript (garantir que estejam disponíveis no window)
    let numAndaresData = window.numAndaresData || 0;
    let unidadesPorAndarData = window.unidadesPorAndarData || {};
    let tiposUnidadesData = window.tiposUnidadesData || [];
    let unidadesEstoqueData = window.unidadesEstoqueData || {};
    let midiasEtapa5Data = window.midiasEtapa5Data || [];
    let fluxoPagamentoEtapa6Data = window.fluxoPagamentoEtapa6Data || null;
    // Variáveis do Wizard (acessíveis globalmente dentro deste DOMContentLoaded)
    const wizardSections = document.querySelectorAll('.admin-form .form-section');
    const wizardStepsNav = document.querySelectorAll('.wizard-navigation .wizard-step');
    const urlParams = new URLSearchParams(window.location.search);
    let currentStepFromUrl = parseInt(urlParams.get('step')) - 1;
    // Função principal para atualizar a UI do wizard
    function updateWizardUI(currentStepIndex) {
        if (isNaN(currentStepIndex) || currentStepIndex < 0 || currentStepIndex >= wizardSections.length) {
            currentStepIndex = 0;
        }
        wizardSections.forEach((section, index) => {
            const inputs = section.querySelectorAll('input, select, textarea, button');
            if (index === currentStepIndex) {
                section.style.display = 'block';
                inputs.forEach(input => {
                    if (input.dataset.originalRequired !== undefined) {
                        if (input.dataset.originalRequired === 'true') {
                            input.setAttribute('required', 'required');
                        }
                        input.removeAttribute('disabled');
                    }
                });
            } else {
                section.style.display = 'none';
                inputs.forEach(input => {
                    if (input.hasAttribute('required')) {
                        input.dataset.originalRequired = 'true';
                    } else {
                        input.dataset.originalRequired = 'false';
                    }
                    input.removeAttribute('required'); // Remove 'required' para não validar campos ocultos
                    input.setAttribute('disabled', 'disabled'); // DESABILITA o campo para que não seja validado
                });
            }
        });
        wizardStepsNav.forEach((stepNav, index) => {
            if (index === currentStepIndex) {
                stepNav.classList.add('active');
            } else {
                stepNav.classList.remove('active');
            }
        });
    }
    // Funções de Máscara (definidas no topo para acessibilidade geral)
    function applyMask(input, maskPattern) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            let maskedValue = '';
            let k = 0;
            for (let i = 0; i < maskPattern.length; i++) {
                if (k >= value.length) break;
                if (maskPattern[i] === '#') {
                    maskedValue += value[k++];
                } else {
                    maskedValue += maskPattern[i];
                }
            }
            e.target.value = maskedValue;
        });
    }
    function applyCpfMask(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            let maskedValue = value.replace(/(\d{3})(\d)/, '$1.$2');
            maskedValue = maskedValue.replace(/(\d{3})(\d)/, '$1.$2');
            maskedValue = maskedValue.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = maskedValue;
        });
    }
    function applyWhatsappMask(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            let maskedValue = '';
            if (value.length > 10) {
                maskedValue = value.replace(/^(\d\d)(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 5) {
                maskedValue = value.replace(/^(\d\d)(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                maskedValue = value.replace(/^(\d\d)(\d{0,5}).*/, '($1) $2');
            } else {
                maskedValue = value.replace(/^(\d*)/, '($1');
            }
            e.target.value = maskedValue;
        });
    }
    // Função para gerar inputs de "Unidades por Andar" (Etapa 3)
    const numAndaresInput = document.getElementById('num_andares');
    const unidadesPorAndarContainer = document.getElementById('unidades_por_andar_container');
    function generateUnidadesPorAndarFields() {
        if (!numAndaresInput || !unidadesPorAndarContainer) return;
        const numAndares = parseInt(numAndaresInput.value);
        unidadesPorAndarContainer.innerHTML = '';
        if (!isNaN(numAndares) && numAndares > 0) {
            for (let i = 1; i <= numAndares; i++) {
                const div = document.createElement('div');
                div.classList.add('form-group');
                const initialQty = parseInt(unidadesPorAndarData[i]) || 0;
                div.innerHTML = `
                    <label for="unidades_por_andar_${i}">${i}º Andar - Quantidade de Unidades:</label>
                    <input type="number" id="unidades_por_andar_${i}" name="unidades_por_andar[${i}]" min="0" value="${initialQty}" required>
                `;
                unidadesPorAndarContainer.appendChild(div);
            }
            updateWizardUI(currentStepFromUrl);
        }
    }
    // Função para adicionar uma nova linha de Tipo de Unidade (Etapa 3)
    const tiposUnidadesContainer = document.getElementById('tipos_unidades_container');
    const addTipoUnidadeBtn = document.getElementById('add_tipo_unidade');
    function addTipoUnidadeRow(initialData = {}) {
        if (!tiposUnidadesContainer) return;
        const div = document.createElement('div');
        div.classList.add('tipo-unidade-item');
        div.innerHTML = `
            <div class="form-group">
                <label>Tipo de Unidade:</label>
                <input type="text" name="tipos_unidade[tipo][]" value="${initialData.tipo || ''}" placeholder="Ex: Apartamento 2 Dorms" required>
            </div>
            <div class="form-group-inline">
                <div class="form-group">
                    <label>Metragem (m²):</label>
                    <input type="number" step="0.01" name="tipos_unidade[metragem][]" value="${initialData.metragem || ''}" required>
                </div>
                <div class="form-group">
                    <label>Quartos:</label>
                    <input type="number" min="0" name="tipos_unidade[quartos][]" value="${initialData.quartos || ''}" required>
                </div>
                <div class="form-group">
                    <label>Banheiros:</label>
                    <input type="number" min="0" name="tipos_unidade[banheiros][]" value="${initialData.banheiros || ''}" required>
                </div>
                <div class="form-group">
                    <label>Vagas de Garagem:</label>
                    <input type="number" min="0" name="tipos_unidade[vagas][]" value="${initialData.vagas || ''}" required>
                </div>
            </div>
            <div class="form-group">
                <label>Foto da Planta:</label>
                <input type="file" name="tipos_unidade[foto_planta][]" accept="image/*">
                ${initialData.foto_planta ? `<small>Arquivo atual: ${initialData.foto_planta}</small><img src="${BASE_URL}${initialData.foto_planta}" alt="Planta atual" style="max-width: 100px; height: auto; display: block; margin-top: 5px; border-radius: var(--border-radius-sm);">` : ''}
            </div>
            <button type="button" class="btn btn-danger btn-sm remove-tipo-unidade">Remover Tipo</button>
        `;
        tiposUnidadesContainer.appendChild(div);
        div.querySelector('.remove-tipo-unidade').addEventListener('click', (e) => {
            e.target.closest('.tipo-unidade-item').remove();
            updateWizardUI(currentStepFromUrl);
        });
        updateWizardUI(currentStepFromUrl);
    }
    // Função para adicionar uma nova linha de Unidade no Estoque (Etapa 4)
    const unitsStockTbody = document.getElementById('units_stock_tbody');
    const generateUnitsBtn = document.getElementById('generate_units_btn');
    const applyBatchValueBtn = document.getElementById('apply_batch_value');
    const batchValueInput = document.getElementById('batch_value');
    const applyBatchTypeBtn = document.getElementById('apply_batch_type');
    const batchTipoUnidadeSelect = document.getElementById('batch_tipo_unidade_id');
    function addUnitStockRow(initialData = {}) {
        if (!unitsStockTbody) return;
        const tr = document.createElement('tr');
        tr.classList.add('unit-stock-row');
        let tipoUnidadeOptionsHtml = '<option value="">Selecione um Tipo</option>';
        tiposUnidadesData.forEach((tipo, idx) => {
            // Use tipo.id if available, otherwise fallback to index for new types
            const selected = (initialData.tipo_unidade_id == (tipo.id || idx)) ? 'selected' : '';
            tipoUnidadeOptionsHtml += `<option value="${tipo.id || idx}" ${selected}>${tipo.tipo} (${tipo.metragem}m²)</option>`;
        });
        tr.innerHTML = `
            <td><input type="number" name="unidades_estoque[andar][]" value="${initialData.andar || ''}" required readonly class="unit-stock-andar-input"></td>
            <td><input type="text" name="unidades_estoque[numero][]" value="${initialData.numero || ''}" required class="unit-stock-numero-input"></td>
            <td><input type="text" name="unidades_estoque[posicao][]" value="${initialData.posicao || ''}" required class="unit-stock-posicao-input"></td>
            <td><select name="unidades_estoque[tipo_unidade_id][]" required class="unit-stock-tipo-select">${tipoUnidadeOptionsHtml}</select></td>
            <td><input type="number" step="0.01" name="unidades_estoque[valor][]" value="${initialData.valor || ''}" required class="unit-stock-valor-input"></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-unit-stock-item">Remover</button></td>
        `;
        unitsStockTbody.appendChild(tr);
        tr.querySelector('.remove-unit-stock-item').addEventListener('click', (e) => {
            e.target.closest('.unit-stock-row').remove();
            updateWizardUI(currentStepFromUrl);
        });
        updateWizardUI(currentStepFromUrl);
    }
    // Funções de Máscara (duplicadas no código original, mantendo uma única versão no topo)
    // Removidas as duplicatas aqui para evitar redefinições. As definições no topo já são globais.
    // Função para adicionar uma nova linha de Vídeo (Etapa 5)
    const addVideoUrlBtn = document.getElementById('add_video_url');
    const videosYoutubeContainer = document.getElementById('videos_youtube_container');
    function addVideoUrlRow(initialUrl = '') {
        if (!videosYoutubeContainer) return;
        const div = document.createElement('div');
        div.classList.add('video-url-item');
        let videoEmbedHtml = '';
        if (initialUrl) {
            const videoIdMatch = initialUrl.match(/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
            if (videoIdMatch && videoIdMatch[1]) {
                videoEmbedHtml = `<div class="video-preview" style="margin-top: 10px; max-width: 300px;"><iframe width="100%" height="auto" src="https://www.youtube.com/embed/${videoIdMatch[1]}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>`;
            } else {
                videoEmbedHtml = `<small style="color: orange;">URL inválida ou não é do YouTube.</small>`;
            }
        }
        div.innerHTML = `
            <div class="form-group">
                <label>URL do Vídeo (YouTube):</label>
                <input type="url" name="midias[videos][]" value="${initialUrl}" placeholder="https://www.youtube.com/watch?v=VIDEO_ID" required>
                ${videoEmbedHtml}
            </div>
            <button type="button" class="btn btn-danger btn-sm remove-video-url">Remover Vídeo</button>
        `;
        videosYoutubeContainer.appendChild(div);
        div.querySelector('.remove-video-url').addEventListener('click', (e) => {
            e.target.closest('.video-url-item').remove();
            updateWizardUI(currentStepFromUrl);
        });
        // Add event listener to update preview when URL changes
        div.querySelector('input[name="midias[videos][]"]').addEventListener('input', (e) => {
            const input = e.target;
            const currentUrl = input.value;
            const previewContainer = input.nextElementSibling; // This assumes preview is directly after input
            if (previewContainer && previewContainer.classList.contains('video-preview')) {
                previewContainer.remove(); // Remove old preview
            }
            if (currentUrl) {
                const videoIdMatch = currentUrl.match(/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                if (videoIdMatch && videoIdMatch[1]) {
                    const newPreview = document.createElement('div');
                    newPreview.classList.add('video-preview');
                    newPreview.style.cssText = 'margin-top: 10px; max-width: 300px;';
                    newPreview.innerHTML = `<iframe width="100%" height="auto" src="https://www.youtube.com/embed/${videoIdMatch[1]}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
                    input.parentNode.insertBefore(newPreview, input.nextSibling);
                } else {
                    const invalidUrlMessage = document.createElement('small');
                    invalidUrlMessage.style.color = 'orange';
                    invalidUrlMessage.textContent = 'URL inválida ou não é do YouTube.';
                    input.parentNode.insertBefore(invalidUrlMessage, input.nextSibling);
                }
            }
        });
        updateWizardUI(currentStepFromUrl);
    }
    // Função para adicionar uma nova linha de Fluxo de Pagamento (Etapa 6)
    const addParcelaBtn = document.getElementById('add_parcela');
    const paymentFlowTbody = document.getElementById('payment_flow_tbody');
    const unidadeExemploSelect = document.getElementById('unidade_exemplo_id');
    const totalPlanoPagamentoSpan = document.getElementById('total_plano_pagamento');
    const totalPlanoValidationP = document.getElementById('total_plano_validation');
    const paymentFlowBuilderPanel = document.getElementById('payment_flow_builder_panel');
    function addParcelaRow(initialData = {}) {
        if (!paymentFlowTbody) return;
        const tr = document.createElement('tr');
        tr.classList.add('payment-flow-item-row');
        const tipoValorOptions = `
            <option value="Percentual (%)" ${initialData.tipo_valor === 'Percentual (%)' ? 'selected' : ''}>Percentual (%)</option>
            <option value="Valor Fixo (R$)" ${initialData.tipo_valor === 'Valor Fixo (R$)' ? 'selected' : ''}>Valor Fixo (R$)</option>
        `;
        tr.innerHTML = `
            <td><input type="text" name="fluxo_pagamento[nome][]" value="${initialData.nome || ''}" placeholder="Entrada, Mensal, Anual..." required></td>
            <td><select name="fluxo_pagamento[tipo_valor][]" class="tipo-valor-select" required>${tipoValorOptions}</select></td>
            <td><input type="number" step="0.01" name="fluxo_pagamento[valor][]" value="${initialData.valor || ''}" required class="valor-parcela-input"></td>
            <td><input type="number" min="1" name="fluxo_pagamento[quantas_vez][]" value="${initialData.quantas_vez || 1}" required class="quantas-vezes-input"></td>
            <td><input type="date" name="fluxo_pagamento[data_vencimento][]" value="${initialData.data_vencimento || ''}"></td>
            <td><span class="total-parcela-display">R$ 0,00</span></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-parcela">Remover</button></td>
        `;
        paymentFlowTbody.appendChild(tr);
        const tipoValorSelect = tr.querySelector('.tipo-valor-select');
        const valorInput = tr.querySelector('.valor-parcela-input');
        const quantasVezesInput = tr.querySelector('.quantas-vezes-input');
        // Listener para tipo de valor e valor da parcela
        [tipoValorSelect, valorInput, quantasVezesInput, unidadeExemploSelect].forEach(input => {
            if (input) {
                input.addEventListener('input', () => {
                    updatePaymentFlowSummary();
                    // Lógica para preencher o restante do valor se for a última parcela e tipo fixo
                    const allParcelaRows = document.querySelectorAll('#payment_flow_tbody .payment-flow-item-row');
                    if (tipoValorSelect.value === 'Valor Fixo (R$)' && allParcelaRows[allParcelaRows.length - 1] === tr) {
                        const selectedOption = unidadeExemploSelect.options[unidadeExemploSelect.selectedIndex];
                        const unitValue = parseFloat(selectedOption.dataset.unitValue) || 0;
                        let totalCalculatedValueExcludingCurrent = 0;
                        allParcelaRows.forEach(row => {
                            if (row !== tr) {
                                const rowTipoValorSelect = row.querySelector('select[name="fluxo_pagamento[tipo_valor][]"]');
                                const rowValorInput = row.querySelector('input[name="fluxo_pagamento[valor][]"]');
                                const rowQuantasVezesInput = row.querySelector('input[name="fluxo_pagamento[quantas_vez][]"]');
                                const rowTipoValor = rowTipoValorSelect.value;
                                const rowValor = parseFloat(rowValorInput.value) || 0;
                                const rowQuantasVezes = parseInt(rowQuantasVezesInput.value) || 0;
                                if (rowTipoValor === 'Percentual (%)') {
                                    totalCalculatedValueExcludingCurrent += (rowValor / 100) * unitValue * rowQuantasVezes;
                                } else {
                                    totalCalculatedValueExcludingCurrent += rowValor * rowQuantasVezes;
                                }
                            }
                        });
                        const currentParcelValue = (parseFloat(valorInput.value) || 0) * (parseInt(quantasVezesInput.value) || 1);
                        const remaining = unitValue - totalCalculatedValueExcludingCurrent;
                        const remainingFixed = remaining / (parseInt(quantasVezesInput.value) || 1);
                        if (Math.abs(remaining - currentParcelValue) < 0.01) { // Check if current value is already close to remaining
                            // Do nothing, assume user set it correctly
                        } else {
                            if (remainingFixed > 0) {
                                input.value = remainingFixed.toFixed(2);
                            } else if (remainingFixed <= 0) {
                                input.value = 0;
                            }
                        }
                    }
                    updatePaymentFlowSummary();
                });
            }
        });
        // Listener para remover parcela
        tr.querySelector('.remove-parcela').addEventListener('click', (e) => {
            e.target.closest('.payment-flow-item-row').remove();
            updatePaymentFlowSummary(); // Recalcula após remover
            updateWizardUI(currentStepFromUrl);
        });
        updatePaymentFlowSummary(); // Recalcula o total ao adicionar a linha
        updateWizardUI(currentStepFromUrl); // Atualiza UI para a nova linha
    }
    function updatePaymentFlowSummary() {
        if (!unidadeExemploSelect || !totalPlanoPagamentoSpan || !totalPlanoValidationP || !unidadesEstoqueData) return;
        const selectedOption = unidadeExemploSelect.options[unidadeExemploSelect.selectedIndex];
        const unitValue = parseFloat(selectedOption.dataset.unitValue) || 0;
        if (unidadeExemploSelect.value === "" || unitValue === 0) {
            paymentFlowBuilderPanel.style.display = 'none';
            totalPlanoPagamentoSpan.textContent = 'R$ 0,00';
            totalPlanoValidationP.textContent = 'Selecione uma unidade exemplo com valor válido.';
            totalPlanoValidationP.style.color = 'red';
            return;
        }
        paymentFlowBuilderPanel.style.display = 'block';
        let totalCalculatedValue = 0;
        let totalPercentualSum = 0;
        let hasPercentualParcel = false;
        let hasFixedParcel = false;
        const allParcelaRows = document.querySelectorAll('#payment_flow_tbody .payment-flow-item-row');
        allParcelaRows.forEach(row => {
            const tipoValorSelect = row.querySelector('select[name="fluxo_pagamento[tipo_valor][]"]');
            const valorInput = row.querySelector('input[name="fluxo_pagamento[valor][]"]');
            const quantasVezesInput = row.querySelector('input[name="fluxo_pagamento[quantas_vez][]"]');
            const totalParcelaDisplay = row.querySelector('.total-parcela-display');
            const tipoValor = tipoValorSelect.value;
            const valor = parseFloat(valorInput.value) || 0;
            const quantasVezes = parseInt(quantasVezesInput.value) || 0;
            let parcelaCalculada = 0;
            if (tipoValor === 'Percentual (%)') {
                hasPercentualParcel = true;
                totalPercentualSum += valor * quantasVezes;
                parcelaCalculada = (valor / 100) * unitValue * quantasVezes;
            } else { // Valor Fixo (R$)
                hasFixedParcel = true;
                parcelaCalculada = valor * quantasVezes;
            }
            totalParcelaDisplay.textContent = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parcelaCalculada);
            totalCalculatedValue += parcelaCalculada;
        });
        totalPlanoPagamentoSpan.textContent = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(totalCalculatedValue);
        totalPlanoValidationP.textContent = '';
        totalPlanoValidationP.style.color = 'initial';
        const tolerance = 0.02; // Tolerância para comparação de floats (0.02 = 2 centavos ou 0.02%)
        if (allParcelaRows.length === 0) {
            totalPlanoValidationP.textContent = 'Adicione parcelas ao plano de pagamento.';
            totalPlanoValidationP.style.color = 'red';
        } else if (hasPercentualParcel && !hasFixedParcel) { // Plano puramente percentual
            const remainingPercent = 100 - totalPercentualSum;
            if (Math.abs(remainingPercent) > tolerance) {
                totalPlanoValidationP.textContent = `Soma dos %: ${totalPercentualSum.toFixed(2)}%. Faltam ${remainingPercent.toFixed(2)}%.`;
                totalPlanoValidationP.style.color = 'red';
            } else {
                totalPlanoValidationP.textContent = 'Plano percentual totalizou 100%. OK!';
                totalPlanoValidationP.style.color = 'green';
            }
        } else { // Plano com valores fixos ou misto
            const remainingValue = unitValue - totalCalculatedValue;
            if (Math.abs(remainingValue) > tolerance) {
                totalPlanoValidationP.textContent = `Total das parcelas: ${new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(totalCalculatedValue)}. Faltam ${new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(remainingValue)}.`;
                totalPlanoValidationP.style.color = 'red';
            } else {
                totalPlanoValidationP.textContent = 'Plano de pagamento totalizou o valor da unidade. OK!';
                totalPlanoValidationP.style.color = 'green';
            }
        }
    }
    // --- Lógica para alternar visibilidade da senha (campos de senha com .password-input-wrapper) ---
    document.querySelectorAll('.password-input-wrapper').forEach(wrapper => {
        const passwordInput = wrapper.querySelector('input[type="password"]');
        const toggleButton = wrapper.querySelector('.toggle-password-visibility');
        if (passwordInput && toggleButton) {
            toggleButton.addEventListener('click', () => {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                toggleButton.querySelector('i').classList.toggle('fa-eye');
                toggleButton.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
    });
    // --- Aplicação das Máscaras ---
    const cpfInputs = document.querySelectorAll('.mask-cpf');
    if (cpfInputs.length > 0) {
        cpfInputs.forEach(input => {
            applyCpfMask(input);
        });
    }
    const whatsappInputs = document.querySelectorAll('.mask-whatsapp');
    if (whatsappInputs.length > 0) {
        whatsappInputs.forEach(input => {
            applyWhatsappMask(input);
        });
    }
    const cepInputs = document.querySelectorAll('.mask-cep');
    if (cepInputs.length > 0) {
        cepInputs.forEach(input => {
            applyMask(input, '#####-###');
        });
    }
    // --- Lógica para seleção dinâmica de tipo de corretor no cadastro (auth/cadastro.php) ---
    const tipoCorretorSelect = document.getElementById('tipo_corretor_cadastro');
    const imobiliariaSelectGroup = document.getElementById('imobiliaria_select_group');
    if (tipoCorretorSelect && imobiliariaSelectGroup) {
        function toggleImobiliariaSelect() {
            if (tipoCorretorSelect.value === 'corretor_imobiliaria') {
                imobiliariaSelectGroup.style.display = 'block';
                imobiliariaSelectGroup.querySelector('select').setAttribute('required', 'required');
            } else {
                imobiliariaSelectGroup.style.display = 'none';
                imobiliariaSelectGroup.querySelector('select').removeAttribute('required');
            }
        }
        tipoCorretorSelect.addEventListener('change', toggleImobiliariaSelect);
        toggleImobiliariaSelect(); // Chama na inicialização para definir o estado correto
    }
    // ===============================================
    // INICIALIZAÇÃO DE DADOS E LISTENERS PARA O WIZARD
    // ===============================================
    // Etapa 3: Andares e Unidades por Andar
    if (numAndaresInput) {
        numAndaresInput.addEventListener('change', generateUnidadesPorAndarFields);
        if (numAndaresData > 0) { // Se já houver dados de andares, gera os campos
            numAndaresInput.value = numAndaresData; // Garante que o input reflita o dado
            generateUnidadesPorAndarFields();
        }
    }
    // Etapa 3: Tipos de Unidade
    if (addTipoUnidadeBtn) {
        addTipoUnidadeBtn.addEventListener('click', () => addTipoUnidadeRow());
        if (tiposUnidadesData.length > 0) {
            tiposUnidadesData.forEach(data => addTipoUnidadeRow(data));
        } else {
            addTipoUnidadeRow(); // Adiciona uma linha vazia por padrão se não houver dados
        }
    }
    // Etapa 4: Estoque de Unidades
    if (generateUnitsBtn && unitsStockTbody) {
        // Popula o select de tipo de unidade para o preenchimento em massa
        if (batchTipoUnidadeSelect && tiposUnidadesData.length > 0) {
            let batchTipoOptionsHtml = '<option value="">Selecione um Tipo</option>';
            tiposUnidadesData.forEach((tipo, idx) => {
                batchTipoOptionsHtml += `<option value="${tipo.id || idx}">${tipo.tipo} (${tipo.metragem}m²)</option>`;
            });
            batchTipoUnidadeSelect.innerHTML = batchTipoOptionsHtml;
        }
        // Carrega unidades existentes
        if (unidadesEstoqueData.length > 0) {
            unidadesEstoqueData.forEach(data => addUnitStockRow(data));
        }
        // Listener para o botão de gerar unidades
        generateUnitsBtn.addEventListener('click', () => {
            unitsStockTbody.innerHTML = ''; // Limpa antes de gerar
            const numAndares = parseInt(numAndaresInput.value) || 0;
            if (numAndares > 0) {
                for (let andar = 1; andar <= numAndares; andar++) {
                    const numUnidades = parseInt(document.getElementById(`unidades_por_andar_${andar}`).value) || 0;
                    for (let i = 1; i <= numUnidades; i++) {
                        addUnitStockRow({ andar: andar, numero: i });
                    }
                }
            }
        });
        // Listener para aplicar valor em massa
        if (applyBatchValueBtn && batchValueInput) {
            applyBatchValueBtn.addEventListener('click', () => {
                const value = parseFloat(batchValueInput.value);
                if (!isNaN(value)) {
                    document.querySelectorAll('.unit-stock-valor-input').forEach(input => {
                        input.value = value.toFixed(2);
                    });
                } else {
                    alert('Por favor, insira um valor numérico válido para o preenchimento em massa.');
                }
            });
        }
        // Listener para aplicar tipo em massa
        if (applyBatchTypeBtn && batchTipoUnidadeSelect) {
            applyBatchTypeBtn.addEventListener('click', () => {
                const selectedTypeId = batchTipoUnidadeSelect.value;
                if (selectedTypeId !== "") {
                    document.querySelectorAll('.unit-stock-tipo-select').forEach(select => {
                        select.value = selectedTypeId;
                    });
                } else {
                    alert('Por favor, selecione um tipo de unidade para o preenchimento em massa.');
                }
            });
        }
    }
    // Etapa 5: Mídias (Vídeos)
    if (addVideoUrlBtn) {
        addVideoUrlBtn.addEventListener('click', () => addVideoUrlRow());
        if (midiasEtapa5Data.length > 0) {
            midiasEtapa5Data.forEach(url => addVideoUrlRow(url));
        }
    }
    // Etapa 6: Fluxo de Pagamento
    if (unidadeExemploSelect) {
        // Popula o select de unidade exemplo com base nas unidades do estoque
        if (unidadesEstoqueData.length > 0) {
            let unidadeExemploOptionsHtml = '<option value="">Selecione uma Unidade Exemplo</option>';
            unidadesEstoqueData.forEach((unit, idx) => {
                // Usar um ID único para a unidade, se disponível, ou um índice
                const unitId = unit.id || `unit-${idx}`;
                unidadeExemploOptionsHtml += `<option value="${unitId}" data-unit-value="${unit.valor || 0}">Andar ${unit.andar}, Unidade ${unit.numero} (R$ ${parseFloat(unit.valor || 0).toFixed(2)})</option>`;
            });
            unidadeExemploSelect.innerHTML = unidadeExemploOptionsHtml;
        }
        unidadeExemploSelect.addEventListener('change', updatePaymentFlowSummary);
        // Se houver um fluxo de pagamento inicial, tente selecionar a unidade exemplo correspondente
        if (fluxoPagamentoEtapa6Data && fluxoPagamentoEtapa6Data.unidade_exemplo_id) {
            unidadeExemploSelect.value = fluxoPagamentoEtapa6Data.unidade_exemplo_id;
        }
    }
    if (addParcelaBtn) {
        addParcelaBtn.addEventListener('click', () => addParcelaRow());
        if (fluxoPagamentoEtapa6Data && fluxoPagamentoEtapa6Data.parcelas && fluxoPagamentoEtapa6Data.parcelas.length > 0) {
            fluxoPagamentoEtapa6Data.parcelas.forEach(data => addParcelaRow(data));
        } else {
            // Adiciona uma linha vazia por padrão se não houver dados e já houver uma unidade exemplo selecionada
            if (unidadeExemploSelect && unidadeExemploSelect.value !== "") {
                addParcelaRow();
            }
        }
    }
    // Chama a função de resumo do fluxo de pagamento na inicialização para exibir o estado atual
    updatePaymentFlowSummary();
    // Atualiza a UI do wizard para o passo inicial ou o passo da URL
    updateWizardUI(currentStepFromUrl);


    // ===============================================
    // LÓGICA DE GESTÃO DE ALERTAS (admin/alertas/index.php)
    // ===============================================
    const alertsTable = document.getElementById('alertsTable');
    const alertSearchInput = document.getElementById('alertSearch');
    const alertFilterStatusSelect = document.getElementById('alertFilterStatus');
    const alertFilterTypeSelect = document.getElementById('alertFilterType');

    if (alertsTable) {
        function filterAlerts() {
            const searchTerm = alertSearchInput.value.toLowerCase();
            const filterStatus = alertFilterStatusSelect.value;
            const filterType = alertFilterTypeSelect.value;

            document.querySelectorAll('#alertsTable tbody tr').forEach(row => {
                // Acessa as células da linha (td)
                const titleCell = row.querySelector('td:nth-child(1)');
                const messageCell = row.querySelector('td:nth-child(2)');
                const typeCell = row.querySelector('td:nth-child(3)');
                const statusCell = row.querySelector('td:nth-child(5)'); // Coluna de Status (Lido/Não Lido)

                // Verifica se as células existem antes de acessar textContent
                const title = titleCell ? titleCell.textContent.trim().toLowerCase() : '';
                const message = messageCell ? messageCell.textContent.trim().toLowerCase() : '';
                const isRead = row.dataset.alertRead; // 'read' or 'unread'
                const type = row.dataset.alertType; // 'info', 'warning', etc.

                const matchesSearch = message.includes(searchTerm) || title.includes(searchTerm);
                const matchesStatus = filterStatus === "" || filterStatus === isRead;
                const matchesType = filterType === "" || filterType === type;

                if (matchesSearch && matchesStatus && matchesType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function sortTable(columnIndex, dataType) {
            const tbody = alertsTable.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = alertsTable.dataset.sortOrder === 'asc';
            const sortedColumn = alertsTable.dataset.sortedColumn;

            rows.sort((a, b) => {
                let aValue, bValue;

                // Garante que a célula exista antes de tentar acessar seu conteúdo
                const aCell = a.children[columnIndex];
                const bCell = b.children[columnIndex];

                if (!aCell || !bCell) return 0; // Se a célula não existe, não compare

                if (dataType === 'text') {
                    aValue = aCell.textContent.trim().toLowerCase();
                    bValue = bCell.textContent.trim().toLowerCase();
                } else if (dataType === 'date') {
                    // Para datas como 'dd/mm/yyyy hh:mm:ss', converter para YYYY-MM-DD HH:MM:SS para comparação
                    const parseDate = (dateString) => {
                        const [datePart, timePart] = dateString.split(' ');
                        const [day, month, year] = datePart.split('/');
                        return new Date(`${year}-${month}-${day}T${timePart}`);
                    };
                    aValue = parseDate(aCell.textContent.trim());
                    bValue = parseDate(bCell.textContent.trim());
                } else if (dataType === 'status') { // Custom sort for read/unread (unread first)
                    aValue = a.dataset.alertRead === 'unread' ? 0 : 1;
                    bValue = b.dataset.alertRead === 'unread' ? 0 : 1;
                } else if (dataType === 'type') { // Sort by type text
                    aValue = aCell.textContent.trim().toLowerCase();
                    bValue = bCell.textContent.trim().toLowerCase();
                }
                
                if (aValue < bValue) {
                    return isAsc ? -1 : 1;
                }
                if (aValue > bValue) {
                    return isAsc ? 1 : -1;
                }
                return 0;
            });

            // Toggle sort order for next click on the same column
            if (sortedColumn === columnIndex.toString()) {
                alertsTable.dataset.sortOrder = isAsc ? 'desc' : 'asc';
            } else {
                alertsTable.dataset.sortOrder = 'asc';
                alertsTable.dataset.sortedColumn = columnIndex.toString(); // Converte para string
            }

            // Remove existing sort icons and add new one
            alertsTable.querySelectorAll('th i.fas.fa-sort-up, th i.fas.fa-sort-down').forEach(icon => {
                icon.classList.remove('fa-sort-up', 'fa-sort-down');
                icon.classList.add('fa-sort');
            });
            const currentHeader = alertsTable.querySelector(`th[data-sort-by]:nth-child(${columnIndex + 1})`);
            if (currentHeader) {
                const icon = currentHeader.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(isAsc ? 'fa-sort-up' : 'fa-sort-down');
                }
            }

            rows.forEach(row => tbody.appendChild(row));
        }

        // Event listeners for search and filter
        alertSearchInput.addEventListener('keyup', filterAlerts);
        alertFilterStatusSelect.addEventListener('change', filterAlerts);
        alertFilterTypeSelect.addEventListener('change', filterAlerts);

        // Event listeners for table headers for sorting
        alertsTable.querySelectorAll('th[data-sort-by]').forEach((header, index) => {
            header.addEventListener('click', () => {
                const sortBy = header.dataset.sortBy;
                let dataType = 'text'; // Default
                if (sortBy === 'created_at') dataType = 'date';
                if (sortBy === 'is_read') dataType = 'status';
                if (sortBy === 'type') dataType = 'type'; // Handle type column specifically if needed
                sortTable(index, dataType);
            });
        });

        // Event listeners for "Mark as Read" buttons (dynamic event delegation)
        alertsTable.addEventListener('click', async (e) => {
            if (e.target.classList.contains('mark-read-btn')) {
                const button = e.target;
                const alertId = button.dataset.alertId;

                try {
                    const response = await fetch(`${BASE_URL}api/mark_alert_read.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `alert_id=${alertId}`
                    });
                    const result = await response.json();

                    if (result.success) {
                        const row = button.closest('tr');
                        if (row) { // Garante que a linha existe
                            // ATENÇÃO: A COLUNA DO STATUS é a 5ª (índice 4)
                            const statusCell = row.querySelector('td:nth-child(5) .status-badge'); // Seleciona a badge na 5ª TD
                            if (statusCell) {
                                statusCell.textContent = 'Lido';
                                statusCell.classList.remove('status-danger');
                                statusCell.classList.add('status-info');
                                row.dataset.alertRead = 'read'; // Atualiza o data-attribute da linha
                            }
                            button.remove(); // Remove o botão "Marcar como Lido"
                            // Opcional: Recarregar o contador de alertas no header
                            // window.location.reload(); 
                        }
                    } else {
                        alert(result.message || 'Erro ao marcar alerta como lido.');
                    }
                } catch (error) {
                    console.error('Erro ao marcar alerta como lido:', error);
                    alert('Erro de conexão ao marcar alerta como lido.');
                }
            }
        });

        // Chama a filtragem/ordenação inicial ao carregar a página
        filterAlerts();
    }


    // ===============================================
    // LÓGICA DE GESTÃO DE LEADS (admin/leads/index.php)
    // ===============================================
    const leadsTable = document.getElementById('leadsTable');
    const leadSearchInput = document.getElementById('leadSearch');

    const assignCorretorModal = document.getElementById('assignCorretorModal');
    const dispenseLeadModal = document.getElementById('dispenseLeadModal');
    const closeModals = document.querySelectorAll('.close-modal');

    const assignReservaIdInput = document.getElementById('assignReservaId');
    const leadClienteNomeSpan = document.getElementById('leadClienteNome');
    const leadEmpreendimentoNomeSpan = document.getElementById('leadEmpreendimentoNome');
    const leadUnidadeNumeroSpan = document.getElementById('leadUnidadeNumero');

    const dispenseReservaIdInput = document.getElementById('dispenseReservaId');

    // Funções para abrir/fechar modais
    function openModal(modal) {
        if (modal) modal.style.display = 'block';
    }

    function closeModal(modal) {
        if (modal) modal.style.display = 'none';
    }

    closeModals.forEach(btn => {
        btn.addEventListener('click', () => {
            closeModal(assignCorretorModal);
            closeModal(dispenseLeadModal);
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target == assignCorretorModal) {
            closeModal(assignCorretorModal);
        }
        if (event.target == dispenseLeadModal) {
            closeModal(dispenseLeadModal);
        }
    });

    // Event listeners para os botões da tabela de leads
    if (leadsTable) {
        leadsTable.addEventListener('click', (e) => {
            // Botão "Atribuir Corretor"
            if (e.target.classList.contains('assign-lead-btn')) {
                const leadId = e.target.dataset.leadId;
                const clienteNome = e.target.dataset.leadNomeCliente;
                const empreendimentoNome = e.target.dataset.leadEmpreendimento;
                const unidadeNumero = e.target.dataset.leadUnidade;

                if (assignReservaIdInput) assignReservaIdInput.value = leadId;
                if (leadClienteNomeSpan) leadClienteNomeSpan.textContent = clienteNome;
                if (leadEmpreendimentoNomeSpan) leadEmpreendimentoNomeSpan.textContent = empreendimentoNome;
                if (leadUnidadeNumeroSpan) leadUnidadeNumeroSpan.textContent = unidadeNumero;

                openModal(assignCorretorModal);
            }

            // Botão "Dispensar"
            if (e.target.classList.contains('dispense-lead-btn')) {
                const leadId = e.target.dataset.leadId;
                if (dispenseReservaIdInput) dispenseReservaIdInput.value = leadId;
                openModal(dispenseLeadModal);
            }
        });

        // Lógica de busca e ordenação para a tabela de leads
        function filterLeads() {
            const searchTerm = leadSearchInput.value.toLowerCase();
            document.querySelectorAll('#leadsTable tbody tr').forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        leadSearchInput.addEventListener('keyup', filterLeads);

        // Lógica de ordenação (reutilizando a função sortTable, adaptada para leadsTable)
        leadsTable.querySelectorAll('th[data-sort-by]').forEach((header, index) => {
            header.addEventListener('click', () => {
                const sortBy = header.dataset.sortBy;
                let dataType = 'text';
                if (sortBy === 'data_reserva') dataType = 'date';
                // Adicione outros tipos de dados se necessário (ex: number para ID)
                sortTableLeads(index, dataType);
            });
        });

        function sortTableLeads(columnIndex, dataType) {
            const tbody = leadsTable.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = leadsTable.dataset.sortOrder === 'asc';
            const sortedColumn = leadsTable.dataset.sortedColumn;

            rows.sort((a, b) => {
                let aValue, bValue;

                const aCell = a.children[columnIndex];
                const bCell = b.children[columnIndex];

                if (!aCell || !bCell) return 0;

                if (dataType === 'text') {
                    aValue = aCell.textContent.trim().toLowerCase();
                    bValue = bCell.textContent.trim().toLowerCase();
                } else if (dataType === 'date') {
                    const parseDate = (dateString) => {
                        const [datePart, timePart] = dateString.split(' ');
                        const [day, month, year] = datePart.split('/');
                        return new Date(`${year}-${month}-${day}T${timePart}`);
                    };
                    aValue = parseDate(aCell.textContent.trim());
                    bValue = parseDate(bCell.textContent.trim());
                } else if (dataType === 'number') {
                    aValue = parseFloat(aCell.textContent.trim());
                    bValue = parseFloat(bCell.textContent.trim());
                }

                if (aValue < bValue) {
                    return isAsc ? -1 : 1;
                }
                if (aValue > bValue) {
                    return isAsc ? 1 : -1;
                }
                return 0;
            });

            if (sortedColumn === columnIndex.toString()) {
                leadsTable.dataset.sortOrder = isAsc ? 'desc' : 'asc';
            } else {
                leadsTable.dataset.sortOrder = 'asc';
                leadsTable.dataset.sortedColumn = columnIndex.toString();
            }

            leadsTable.querySelectorAll('th i.fas.fa-sort-up, th i.fas.fa-sort-down').forEach(icon => {
                icon.classList.remove('fa-sort-up', 'fa-sort-down');
                icon.classList.add('fa-sort');
            });
            const currentHeader = leadsTable.querySelector(`th[data-sort-by]:nth-child(${columnIndex + 1})`);
            if (currentHeader) {
                const icon = currentHeader.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(isAsc ? 'fa-sort-up' : 'fa-sort-down');
                }
            }

            rows.forEach(row => tbody.appendChild(row));
        }

        filterLeads(); // Chama a filtragem inicial ao carregar
    }


    // ===============================================
    // LÓGICA DE GESTÃO DE RESERVAS (admin/reservas/index.php)
    // ===============================================
    const reservasTable = document.getElementById('reservasTable');
    const reservaSearchInput = document.getElementById('reservaSearch');
    const reservaFilterStatusSelect = document.getElementById('reservaFilterStatus');

    const approveReservaModal = document.getElementById('approveReservaModal');
    const rejectReservaModal = document.getElementById('rejectReservaModal');
    const requestDocsModal = document.getElementById('requestDocsModal');
    const finalizeSaleModal = document.getElementById('finalizeSaleModal'); 
    const cancelReservaModal = document.getElementById('cancelReservaModal'); 

    const approveReservaIdInput = document.getElementById('approveReservaId');
    const rejectReservaIdInput = document.getElementById('rejectReservaId');
    const requestDocsReservaIdInput = document.getElementById('requestDocsReservaId');
    const finalizeSaleReservaIdInput = document.getElementById('finalizeSaleReservaId'); 
    const cancelReservaIdInput = document.getElementById('cancelReservaId'); 

    // Funções genéricas para abrir/fechar modais
    function openModal(modalElement) {
        if (modalElement) modalElement.style.display = 'active';
    }

    function closeModal(modalElement) {
        if (modalElement) modalElement.style.display = 'active';
    }

    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'active';
        });
    }

    // Adiciona listeners para todos os botões de fechar modais
    document.querySelectorAll('.modal .close-modal').forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });

    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) { // Se o clique foi no overlay do modal
            closeAllModals();
        }
    });


    if (reservasTable) {
        reservasTable.addEventListener('click', (e) => {
            const button = e.target.closest('button'); // Pega o botão clicado ou null
            if (!button) return; // Não é um botão

            const reservaId = button.dataset.reservaId;
            if (!reservaId) return; // Não tem ID de reserva

            // Aprovar Reserva
            if (button.classList.contains('approve-reserva-btn')) {
                approveReservaIdInput.value = reservaId;
                openModal(approveReservaModal);
            }
            // Rejeitar Reserva
            else if (button.classList.contains('reject-reserva-btn')) {
                rejectReservaIdInput.value = reservaId;
                openModal(rejectReservaModal);
            }
            // Solicitar Documentação
            else if (button.classList.contains('request-docs-btn')) {
                requestDocsReservaIdInput.value = reservaId;
                openModal(requestDocsModal);
            }
            // Finalizar Venda (NOVO)
            else if (button.classList.contains('finalize-sale-btn')) {
                finalizeSaleReservaIdInput.value = reservaId;
                openModal(finalizeSaleModal);
            }
            // Para 'Analisar Docs' e 'Enviar Contrato' que são <a> tags, a navegação já funciona pelo href.
        });

        // Lógica de busca e ordenação para a tabela de reservas
        function filterReservas() {
            const searchTerm = reservaSearchInput.value.toLowerCase();
            const filterStatus = reservaFilterStatusSelect.value.toLowerCase();

            document.querySelectorAll('#reservasTable tbody tr').forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const reservaStatus = row.dataset.reservaStatus.toLowerCase();

                const matchesSearch = searchTerm === "" || rowText.includes(searchTerm);
                const matchesStatus = filterStatus === "" || reservaStatus === filterStatus;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        reservaSearchInput.addEventListener('keyup', filterReservas);
        reservaFilterStatusSelect.addEventListener('change', filterReservas);

        // Lógica de ordenação (reutilizando a função sortTable, adaptada para reservasTable)
        reservasTable.querySelectorAll('th[data-sort-by]').forEach((header, index) => {
            header.addEventListener('click', () => {
                const sortBy = header.dataset.sortBy;
                let dataType = 'text'; // Default
                if (sortBy === 'data_reserva' || sortBy === 'data_ultima_interacao') dataType = 'date';
                if (sortBy === 'reserva_id' || sortBy === 'valor_reserva') dataType = 'number';
                // Adapta a função de ordenação genérica para esta tabela
                sortTableReservas(index, dataType);
            });
        });

        function sortTableReservas(columnIndex, dataType) {
            const tbody = reservasTable.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = reservasTable.dataset.sortOrder === 'asc';
            const sortedColumn = reservasTable.dataset.sortedColumn;

            rows.sort((a, b) => {
                let aValue, bValue;

                const aCell = a.children[columnIndex];
                const bCell = b.children[columnIndex];

                if (!aCell || !bCell) return 0;

                if (dataType === 'text') {
                    aValue = aCell.textContent.trim().toLowerCase();
                    bValue = bCell.textContent.trim().toLowerCase();
                } else if (dataType === 'date') {
                    const parseDate = (dateString) => {
                        const [datePart, timePart] = dateString.split(' ');
                        const [day, month, year] = datePart.split('/');
                        return new Date(`${year}-${month}-${day}T${timePart}`);
                    };
                    aValue = parseDate(aCell.textContent.trim());
                    bValue = parseDate(bCell.textContent.trim());
                } else if (dataType === 'number') {
                    // Para valores monetários, remover R$ e . e , para float
                    aValue = parseFloat(aCell.textContent.trim().replace('R$', '').replace(/\./g, '').replace(',', '.'));
                    bValue = parseFloat(bCell.textContent.trim().replace('R$', '').replace(/\./g, '').replace(',', '.'));
                }

                if (aValue < bValue) {
                    return isAsc ? -1 : 1;
                }
                if (aValue > bValue) {
                    return isAsc ? 1 : -1;
                }
                return 0;
            });

            if (sortedColumn === columnIndex.toString()) {
                reservasTable.dataset.sortOrder = isAsc ? 'desc' : 'asc';
            } else {
                reservasTable.dataset.sortOrder = 'asc';
                reservasTable.dataset.sortedColumn = columnIndex.toString();
            }

            reservasTable.querySelectorAll('th i.fas.fa-sort-up, th i.fas.fa-sort-down').forEach(icon => {
                icon.classList.remove('fa-sort-up', 'fa-sort-down');
                icon.classList.add('fa-sort');
            });
            const currentHeader = reservasTable.querySelector(`th[data-sort-by]:nth-child(${columnIndex + 1})`);
            if (currentHeader) {
                const icon = currentHeader.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(isAsc ? 'fa-sort-up' : 'fa-sort-down');
                }
            }

            rows.forEach(row => tbody.appendChild(row));
        }

        filterReservas(); // Chama a filtragem inicial ao carregar
    }

    // ===============================================
    // LÓGICA DE GESTÃO DE DOCUMENTOS (admin/documentos/index.php)
    // ===============================================
    const documentosTable = document.getElementById('documentosTable'); // ID da tabela
    const documentSearchInput = document.getElementById('documentSearch');
    const documentFilterStatusSelect = document.getElementById('documentFilterStatus');
    const documentFilterTypeSelect = document.getElementById('documentFilterType'); // Seletor de tipo de documento

    const analyzeDocsModal = document.getElementById('analyzeDocsModal');
    const analyzeDocsReservaIdDisplay = document.getElementById('analyzeDocsReservaIdDisplay');
    const goToReservaDetailsLink = document.getElementById('goToReservaDetailsLink');
    // Adicione modais e inputs para aprovar/rejeitar documentos individuais aqui, se necessário

    if (documentosTable) {
        // Event listeners para os botões da tabela de documentos (Ex: 'Analisar Docs')
        documentosTable.addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            const reservaId = button.dataset.reservaId;
            if (!reservaId) return;

            if (button.classList.contains('analyze-docs-btn')) {
                analyzeDocsReservaIdDisplay.textContent = reservaId;
                goToReservaDetailsLink.href = `${BASE_URL}admin/reservas/detalhes.php?id=${reservaId}#documentos`; // Link para a seção de documentos na página de detalhes
                openModal(analyzeDocsModal);
            }
        });

        // Lógica de busca e ordenação para a tabela de documentos
        function filterDocuments() {
            const searchTerm = documentSearchInput.value.toLowerCase();
            const filterStatus = documentFilterStatusSelect.value.toLowerCase();
            const filterType = documentFilterTypeSelect.value.toLowerCase();

            document.querySelectorAll('#documentosTable tbody tr').forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const reservaStatus = row.dataset.reservaStatus.toLowerCase(); // Status da reserva
                const docType = row.dataset.documentType ? row.dataset.documentType.toLowerCase() : ''; // Tipo de documento, se você adicionar um data-document-type no HTML da linha

                const matchesSearch = searchTerm === "" || rowText.includes(searchTerm);
                const matchesStatus = filterStatus === "" || reservaStatus === filterStatus;
                const matchesType = filterType === "" || docType === filterType;

                if (matchesSearch && matchesStatus && matchesType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        documentSearchInput.addEventListener('keyup', filterDocuments);
        documentFilterStatusSelect.addEventListener('change', filterDocuments);
        documentFilterTypeSelect.addEventListener('change', filterDocuments); // Adicione se houver filtro por tipo de documento

        // Lógica de ordenação (adaptada para documentosTable)
        documentosTable.querySelectorAll('th[data-sort-by]').forEach((header, index) => {
            header.addEventListener('click', () => {
                const sortBy = header.dataset.sortBy;
                let dataType = 'text';
                if (sortBy === 'data_reserva') dataType = 'date';
                if (sortBy === 'reserva_id' || sortBy === 'pendentes_count' || sortBy === 'rejeitados_count') dataType = 'number';
                // Adapta a função de ordenação genérica para esta tabela
                sortTableDocuments(index, dataType);
            });
        });

        function sortTableDocuments(columnIndex, dataType) {
            const tbody = documentosTable.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = documentosTable.dataset.sortOrder === 'asc';
            const sortedColumn = documentosTable.dataset.sortedColumn;

            rows.sort((a, b) => {
                let aValue, bValue;
                const aCell = a.children[columnIndex];
                const bCell = b.children[columnIndex];
                if (!aCell || !bCell) return 0;

                if (dataType === 'text') {
                    aValue = aCell.textContent.trim().toLowerCase();
                    bValue = bCell.textContent.trim().toLowerCase();
                } else if (dataType === 'date') {
                    const parseDate = (dateString) => {
                        const [datePart, timePart] = dateString.split(' ');
                        const [day, month, year] = datePart.split('/');
                        return new Date(`${year}-${month}-${day}T${timePart}`);
                    };
                    aValue = parseDate(aCell.textContent.trim());
                    bValue = parseDate(bCell.textContent.trim());
                } else if (dataType === 'number') {
                    aValue = parseFloat(aCell.textContent.trim());
                    bValue = parseFloat(bCell.textContent.trim());
                }

                if (aValue < bValue) {
                    return isAsc ? -1 : 1;
                }
                if (aValue > bValue) {
                    return isAsc ? 1 : -1;
                }
                return 0;
            });

            if (sortedColumn === columnIndex.toString()) {
                documentosTable.dataset.sortOrder = isAsc ? 'desc' : 'asc';
            } else {
                documentosTable.dataset.sortOrder = 'asc';
                documentosTable.dataset.sortedColumn = columnIndex.toString();
            }

            documentosTable.querySelectorAll('th i.fas.fa-sort-up, th i.fas.fa-sort-down').forEach(icon => {
                icon.classList.remove('fa-sort-up', 'fa-sort-down');
                icon.classList.add('fa-sort');
            });
            const currentHeader = documentosTable.querySelector(`th[data-sort-by]:nth-child(${columnIndex + 1})`);
            if (currentHeader) {
                const icon = currentHeader.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(isAsc ? 'fa-sort-up' : 'fa-sort-down');
                }
            }

            rows.forEach(row => tbody.appendChild(row));
        }

        filterDocuments(); // Chama a filtragem inicial ao carregar
    }

    // ===============================================
    // LÓGICA DE AÇÕES NA PÁGINA DE DETALHES DA RESERVA (admin/reservas/detalhes.php)
    // ===============================================
    const detalhesReservaPage = document.querySelector('.admin-content-wrapper'); // Seleciona o container principal da página de detalhes

    if (detalhesReservaPage && window.location.pathname.includes('admin/reservas/detalhes.php')) {
        // Modais já definidos no HTML de detalhes.php
        const approveDocumentModal = document.getElementById('approveDocumentModal');
        const rejectDocumentModal = document.getElementById('rejectDocumentModal');
        const cancelReservaModal = document.getElementById('cancelReservaModal'); 

        // Inputs escondidos dos modais
        const approveDocumentIdInput = document.getElementById('approveDocumentId');
        const approveDocumentReservaIdInput = document.getElementById('approveDocumentReservaId');
        const rejectDocumentIdInput = document.getElementById('rejectDocumentId');
        const rejectDocumentReservaIdInput = document.getElementById('rejectDocumentReservaId');
        const rejectionReasonTextarea = document.getElementById('rejectionReason');
        const cancelReservaIdInput = document.getElementById('cancelReservaId'); 

        // Event listeners para os botões de ação na página de detalhes
        detalhesReservaPage.addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            const reservaId = new URLSearchParams(window.location.search).get('id'); // ID da reserva da URL
            const docId = button.dataset.docId; // ID do documento, se aplicável ao botão clicado

            // Lógica para Aprovar Documento
            if (button.classList.contains('approve-doc-btn')) {
                if (!docId || !reservaId) return;
                approveDocumentIdInput.value = docId;
                approveDocumentReservaIdInput.value = reservaId;
                openModal(approveDocumentModal);
            }
            // Lógica para Rejeitar Documento
            else if (button.classList.contains('reject-doc-btn')) {
                if (!docId || !reservaId) return;
                rejectDocumentIdInput.value = docId;
                rejectDocumentReservaIdInput.value = reservaId;
                rejectionReasonTextarea.value = ''; // Limpa o campo de motivo
                openModal(rejectDocumentModal);
            }
            // Lógica para Cancelar Reserva
            else if (button.classList.contains('cancel-reserva-btn')) {
                if (!reservaId) return;
                cancelReservaIdInput.value = reservaId;
                openModal(cancelReservaModal);
            }
        });

        // Formulário de Aprovação de Documento
        const approveDocumentForm = document.getElementById('approveDocumentForm');
        if (approveDocumentForm) {
            approveDocumentForm.addEventListener('submit', async (e) => {
                e.preventDefault(); 
                const formData = new FormData(approveDocumentForm);
                try {
                    const response = await fetch(`${BASE_URL}admin/reservas/detalhes.php`, {
                        method: 'POST',
                        body: formData
                    });
                    const resultText = await response.text(); // Lê como texto primeiro
                    try {
                        const result = JSON.parse(resultText); // Tenta parsear como JSON
                        if (result.success) {
                            alert(result.message);
                        } else {
                            alert(result.message || 'Erro ao aprovar documento.');
                        }
                    } catch (jsonError) {
                        console.error('Erro de JSON parse:', jsonError);
                        console.error('Resposta do servidor (texto):', resultText);
                        alert('Erro inesperado na resposta do servidor. Verifique o console.');
                    }
                    window.location.reload(); // Recarrega a página para refletir as mudanças

                } catch (error) {
                    console.error('Erro na requisição AJAX:', error);
                    alert('Erro de conexão ao aprovar documento.');
                }
            });
        }

        // Formulário de Rejeição de Documento
        const rejectDocumentForm = document.getElementById('rejectDocumentForm');
        if (rejectDocumentForm) {
            rejectDocumentForm.addEventListener('submit', async (e) => {
                e.preventDefault(); 
                const formData = new FormData(rejectDocumentForm);
                try {
                    const response = await fetch(`${BASE_URL}admin/reservas/detalhes.php`, {
                        method: 'POST',
                        body: formData
                    });
                     const resultText = await response.text();
                    try {
                        const result = JSON.parse(resultText);
                        if (result.success) {
                            alert(result.message);
                        } else {
                            alert(result.message || 'Erro ao rejeitar documento.');
                        }
                    } catch (jsonError) {
                        console.error('Erro de JSON parse:', jsonError);
                        console.error('Resposta do servidor (texto):', resultText);
                        alert('Erro inesperado na resposta do servidor. Verifique o console.');
                    }
                    window.location.reload(); 

                } catch (error) {
                    console.error('Erro na requisição AJAX:', error);
                    alert('Erro de conexão ao rejeitar documento.');
                }
            });
        }

        // Formulário de Cancelamento de Reserva
        const cancelReservaForm = document.getElementById('cancelReservaForm');
        if (cancelReservaForm) {
            cancelReservaForm.addEventListener('submit', async (e) => {
                e.preventDefault(); 
                const formData = new FormData(cancelReservaForm);
                try {
                    const response = await fetch(`${BASE_URL}admin/reservas/detalhes.php`, {
                        method: 'POST',
                        body: formData
                    });
                    const resultText = await response.text();
                    try {
                        const result = JSON.parse(resultText);
                        if (result.success) {
                            alert(result.message);
                            window.location.href = `${BASE_URL}admin/reservas/index.php`; // Redireciona após cancelar
                        } else {
                            alert(result.message || 'Erro ao cancelar reserva.');
                        }
                    } catch (jsonError) {
                        console.error('Erro de JSON parse:', jsonError);
                        console.error('Resposta do servidor (texto):', resultText);
                        alert('Erro inesperado na resposta do servidor. Verifique o console.');
                    }

                } catch (error) {
                    console.error('Erro na requisição AJAX:', error);
                    alert('Erro de conexão ao cancelar reserva.');
                }
            });
        }
    } // Fim do if (detalhesReservaPage)


    // --- LÓGICA DE GESTÃO DE VENDAS E MODAIS (APENAS SE OS ELEMENTOS EXISTIREM) ---
    const finalizeSaleModal = document.getElementById('finalizeSaleModal');
    const modalReservaIdSpan = document.getElementById('modalReservaId');
    const confirmFinalizeSaleBtn = document.getElementById('confirmFinalizeSaleBtn');
    let currentReservationId = null;

    // A lógica só será executada se o modal de finalização de venda existir na página atual
    if (finalizeSaleModal && modalReservaIdSpan && confirmFinalizeSaleBtn) {
        // Abertura do modal usando delegação de eventos
        document.addEventListener('click', function(event) {
            // Verifica se o clique foi no botão "Finalizar Venda"
            if (event.target.classList.contains('finalize-sale-btn')) {
                currentReservationId = event.target.dataset.id;
                modalReservaIdSpan.textContent = currentReservationId; // Define o ID da reserva no SPAN
                finalizeSaleModal.classList.add('active'); // Ativa a classe 'active' para mostrar o modal
            }
        });

        // Fechamento do modal ao clicar nos botões de fechar ou no overlay
        finalizeSaleModal.querySelectorAll('.modal-close-btn').forEach(button => {
            button.addEventListener('click', () => {
                finalizeSaleModal.classList.remove('active'); // Remove a classe 'active' para esconder o modal
                currentReservationId = null; // Limpa o ID da reserva ao fechar
            });
        });

        // Fechamento ao clicar no overlay (fundo escuro)
        finalizeSaleModal.addEventListener('click', (e) => {
            if (e.target === finalizeSaleModal) { // Se o clique foi exatamente no overlay
                finalizeSaleModal.classList.remove('active');
                currentReservationId = null;
            }
        });

        // Evento de confirmação da finalização da venda
        confirmFinalizeSaleBtn.addEventListener('click', function() {
            if (currentReservationId) {
                fetch(BASE_URL_JS + 'api/vendas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=finalize_sale&reserva_id=${currentReservationId}`
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        window.location.reload(); // Recarrega a página se a operação foi bem-sucedida
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição AJAX de finalização de venda:', error);
                    alert('Ocorreu um erro ao processar a solicitação de finalização de venda.');
                })
                .finally(() => {
                    finalizeSaleModal.classList.remove('active'); // Sempre fecha o modal
                    currentReservationId = null; // Sempre limpa o ID
                });
            }
        });
    }
