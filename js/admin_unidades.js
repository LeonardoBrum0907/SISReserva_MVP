// SISReserva_MVP/js/admin_unidades.js - Lógicas específicas para a página de gestão de unidades

document.addEventListener('DOMContentLoaded', function() {
    // Garante que o script só seja executado uma vez, resolvendo o "Illegal return statement"
    if (window.adminUnidadesLoaded) {
        console.log("admin_unidades.js: Script já carregado, evitando execução duplicada.");
        return; // Este 'return' AGORA está dentro de uma função, sendo válido.
    }
    window.adminUnidadesLoaded = true; // Marca que o script foi carregado

    console.log("admin_unidades.js: DOMContentLoaded - Script iniciado para a página de unidades.");

    // --- VARIÁVEIS GLOBAIS E FUNÇÕES AUXILIARES ---
    // window.BASE_URL_JS é definida no footer_dashboard.php
    // window.currentEmpreendimentoId é definida no unidades.php (no HTML inline <script>)
    
    // Verificação de segurança para garantir que BASE_URL_JS e empreendimentoId estejam definidos
    if (typeof window.BASE_URL_JS === 'undefined' || !window.BASE_URL_JS) {
        console.error("ERRO CRÍTICO: window.BASE_URL_JS não está definida ou está vazia. Verifique includes/config.php e includes/footer_dashboard.php.");
        return; 
    }
    // As URLs das APIs serão construídas a partir da BASE_URL_JS, garantindo caminho absoluto
    const BASE_API_UNIDADES_URL = window.BASE_URL_JS + 'api/unidades/';
    const BASE_API_EMPREENDIMENTOS_URL = window.BASE_URL_JS + 'api/empreendimentos/'; // Para salvar_etapa6.php

    // empreendimentoId é lido da variável global injetada pelo PHP
    if (typeof window.currentEmpreendimentoId === 'undefined' || !window.currentEmpreendimentoId) {
        console.error("ERRO: window.currentEmpreendimentoId não está definida. Verifique admin/empreendimentos/unidades.php.");
        window.currentEmpreendimentoId = 0; // Fallback defensivo
    }
    const empreendimentoId = window.currentEmpreendimentoId; 

    // Funções de máscara e formatação (assumimos que forms.js é carregado ANTES deste script)
    // Se initCurrencyMasks não for global, este fallback será usado
    if (typeof initCurrencyMasks === 'function') {
        // forms.js já vai inicializar as máscaras para inputs com a classe 'currency-mask'
        // Mas para elementos adicionados dinamicamente, podemos precisar de uma função como applyMaskToCurrencyAndDecimalInputs
    } else { 
        // Fallback completo para máscaras de moeda se forms.js não carregar ou não exportar
        function applyCurrencyMask(input) {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length === 0) { e.target.value = ''; return; }
                let floatValue = parseFloat(value) / 100;
                e.target.value = floatValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            });
            input.addEventListener('blur', (e) => {
                if(e.target.value) {
                    e.target.value = parseFloat(e.target.value.replace(/\./g, '').replace(',', '.')).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            });
        }
        document.querySelectorAll('.currency-mask').forEach(applyCurrencyMask);
    }
    // Função auxiliar para limpar valor monetário
    function cleanCurrencyInput(value) { return parseFloat(value.replace(/[R$\s.]/g, '').replace(',', '.')); }
    // Função para formatar moeda (para exibição)
    function formatCurrency(value) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value); }
    // Função para formatar valor monetário para input (com casas decimais)
    const formatCurrencyInput = (value) => {
        if (value === null || value === undefined || value === '') return '';
        const numericValue = value.toString().replace(/\D/g, '');
        if (numericValue.length === 0) return '';
        let floatValue = parseFloat(numericValue) / 100;
        return floatValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    // Função para parsear valor monetário de string para float
    const parseCurrencyInput = (value) => {
        if (!value) return 0;
        const numericValue = value.replace(/[R$\s.]/g, '').replace(',', '.');
        return parseFloat(numericValue);
    };
    // Função auxiliar para aplicar máscaras a elementos dinâmicos
    function applyMaskToCurrencyAndDecimalInputs(container) {
        container.querySelectorAll('.currency-mask').forEach(input => {
            if (!input._maskInitialized) { // Evitar re-inicializar máscaras
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length === 0) { e.target.value = ''; return; }
                    let floatValue = parseFloat(value) / 100;
                    e.target.value = floatValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                });
                input.addEventListener('blur', (e) => { // Formatar ao perder o foco
                    if(e.target.value) {
                        e.target.value = formatCurrencyInput(parseCurrencyInput(e.target.value) * 100);
                    }
                });
                if (input.value) { // Garante que o valor inicial também seja formatado ao adicionar a linha
                    input.value = formatCurrencyInput(parseCurrencyInput(input.value) * 100);
                }
                input._maskInitialized = true;
            }
        });
        container.querySelectorAll('.currency-mask-decimal').forEach(input => {
            if (!input._maskInitialized) {
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/[^0-9,]/g, '');
                    const parts = value.split(',');
                    if (parts.length > 1) { parts[1] = parts[1].substring(0, 2); value = parts.join(','); }
                    e.target.value = value;
                });
                input.addEventListener('blur', (e) => {
                    if (e.target.value.endsWith(',')) e.target.value += '00';
                    if (e.target.value.endsWith(',0')) e.target.value += '0';
                });
                input._maskInitialized = true;
            }
        });
        container.querySelectorAll('.currency-mask-decimal-small').forEach(input => {
            if (!input._maskInitialized) {
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/[^0-9,]/g, '');
                    const parts = value.split(',');
                    if (parts.length > 1) { parts[1] = parts[1].substring(0, 2); value = parts.join(','); }
                    e.target.value = value;
                });
                input.addEventListener('blur', (e) => {
                    if (e.target.value.endsWith(',')) e.target.value += '00';
                    if (e.target.value.endsWith(',0')) e.target.value += '0';
                });
                input._maskInitialized = true;
            }
        });
    }

    // --- LÓGICA DE GERENCIAMENTO DE UNIDADES (BOTÕES SIMPLES) ---

    // Lógica para alternar status da unidade (Pausar/Bloquear/Ativar)
    document.querySelectorAll('.toggle-unit-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const unitId = this.dataset.unitId;
            const newStatus = this.dataset.newStatus; // 'pausada', 'bloqueada', 'disponivel'
            const confirmMessage = `Tem certeza que deseja mudar o status desta unidade para '${newStatus.replace('_', ' ')}'?`;

            if (confirm(confirmMessage)) {
                fetch(`${BASE_API_UNIDADES_URL}update_status.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `unit_id=${unitId}&new_status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // CORREÇÃO: Força a recarga para refletir o status e os botões
                        window.location.reload(); 
                    } else {
                        alert('Erro ao alterar status da unidade: ' + data.message);
                    }
                })
                .catch(error => { 
                    console.error('Erro na requisição AJAX:', error); 
                    alert('Ocorreu um erro ao comunicar com o servidor.'); 
                });
            }
        });
    });

    // Lógica para o botão "Editar Preço"
    document.querySelectorAll('.edit-price-btn').forEach(button => {
        button.addEventListener('click', function() {
            const unitId = this.dataset.unitId;
            const currentPrice = this.dataset.currentPrice;
            document.getElementById('editPriceUnitId').value = unitId;
            document.getElementById('newPrice').value = parseFloat(currentPrice).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); 
            $('#editPriceModal').modal('show');
        });
    });

    // Lógica para salvar o novo preço
    document.getElementById('saveNewPriceBtn').addEventListener('click', function() {
        const unitId = document.getElementById('editPriceUnitId').value;
        let newPrice = document.getElementById('newPrice').value;
        newPrice = cleanCurrencyInput(newPrice);

        if (isNaN(newPrice)) { alert('Por favor, insira um valor numérico válido para o preço.'); return; }

        fetch(`${BASE_API_UNIDADES_URL}update_price.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
            body: `unit_id=${unitId}&new_price=${newPrice}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) { alert(data.message); $('#editPriceModal').modal('hide'); window.location.reload(); } 
            else { alert('Erro ao atualizar preço: ' + data.message); }
        })
        .catch(error => { console.error('Erro na requisição AJAX:', error); alert('Ocorreu um erro ao comunicar com o servidor.'); });
    });

    // Lógica para o botão de exclusão de unidade
    document.querySelectorAll('.delete-unit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const unitId = this.dataset.unitId;
            if (confirm('Tem certeza que deseja excluir esta unidade? Esta ação é irreversível e pode afetar registros de reservas/vendas associados.')) {
                fetch(`${BASE_API_UNIDADES_URL}excluir.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
                    body: `unit_id=${unitId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) { alert(data.message); window.location.reload(); } 
                    else { alert('Erro ao excluir unidade: ' + data.message); }
                })
                .catch(error => { console.error('Erro na requisição AJAX:', error); alert('Ocorreu um erro ao comunicar com o servidor.'); });
            }
        });
    });

    // Lógica para Ajuste de Preços em Lote
    document.getElementById('saveBatchPriceUpdateBtn').addEventListener('click', function() {
        const priceMultiplier = document.getElementById('priceMultiplier').value;

        if (isNaN(priceMultiplier) || parseFloat(priceMultiplier) <= 0) { alert('Por favor, insira um multiplicador de preço válido (número maior que 0).'); return; }

        if (confirm(`Tem certeza que deseja multiplicar os preços de TODAS as unidades por ${priceMultiplier}? Esta ação é irreversível!`)) {
            fetch(`${BASE_API_UNIDADES_URL}batch_update_prices.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
                body: `empreendimento_id=${empreendimentoId}&price_multiplier=${priceMultiplier}` // empreendimentoId vem do window.currentEmpreendimentoId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) { alert(data.message); $('#batchPriceUpdateModal').modal('hide'); window.location.reload(); } 
                else { alert('Erro ao ajustar preços em lote: ' + data.message); }
            })
            .catch(error => { console.error('Erro na requisição AJAX:', error); alert('Ocorreu um erro ao comunicar com o servidor para ajuste em lote.'); });
        }
    });

    // Lógica para o botão de Imprimir Tabela
    document.getElementById('printTableBtn').addEventListener('click', function() {
        const table = document.querySelector('.admin-table');
        if (table) {
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Tabela de Unidades</title>');
            printWindow.document.write('<link rel="stylesheet" href="' + window.BASE_URL_JS + 'css/dashboard.css">'); // Usando window.BASE_URL_JS
            printWindow.document.write('<style>@media print { .admin-page-header, .admin-controls-bar, .modal-footer, .btn, .actions { display: none !important; } table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; } }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h1>' + document.querySelector('.admin-page-header h2').textContent + '</h1>');
            const kpiGrid = document.querySelector('.kpi-grid-small');
            if (kpiGrid) { printWindow.document.write('<div class="kpi-grid-small">' + kpiGrid.innerHTML + '</div>'); }
            printWindow.document.write(table.outerHTML);
            printWindow.document.close();
            printWindow.print();
        } else { alert('Tabela não encontrada para impressão.'); }
    });


    // --- LÓGICA DO CONSTRUTOR DE PLANO DE PAGAMENTO NO MODAL (BOTAO "CONDIÇÃO") ---
    let currentUnitModalValue = 0; // Valor da unidade sendo editada no modal
    let paymentConditionsModal = []; // Condições de pagamento para o modal (Estado)

    // Elementos do Modal
    const paymentFlowModalTbody = document.getElementById('payment_flow_modal_tbody');
    const addParcelaModalBtn = document.getElementById('add_parcela_modal');
    const totalPlanoModalDisplay = document.getElementById('total_plano_modal_display');
    const totalPlanoModalValidation = document.getElementById('total_plano_modal_validation');
    const paymentUnitValueModalDisplay = document.getElementById('payment_unit_value_modal_display');

    // Funções de Cálculo (Adaptadas do React para Vanilla JS)
    const calculateConditionTotalModal = (condition) => {
        const amount = parseFloat(condition.amount) || 0; // This is the numeric value directly from the input/data
        const installments = parseInt(condition.installments) || 1;
        let calculatedValuePerCondition = 0;

        if (condition.type === 'percentage') { // `condition.type` is 'percentage'
            calculatedValuePerCondition = currentUnitModalValue * amount; // `amount` is a decimal like 0.10
        } else { // `condition.type` is 'value' (for fixed R$)
            calculatedValuePerCondition = amount; // `amount` is the direct monetary value
        }
        return calculatedValuePerCondition * installments;
    };


    const calculateUsedPercentageModal = () => {
        if (currentUnitModalValue === 0) return 0;
        const totalAmountUsed = paymentConditionsModal.reduce((acc, condition) => acc + calculateConditionTotalModal(condition), 0);
        return (totalAmountUsed / currentUnitModalValue) * 100;
    };

    const calculateRemainingValueModal = () => {
        const totalAmountUsed = paymentConditionsModal.reduce((acc, condition) => acc + calculateConditionTotalModal(condition), 0);
        return Math.max(0, currentUnitModalValue - totalAmountUsed);
    };

    const calculateMaxAllowedForConditionModal = (conditionId) => {
        const otherConditionsTotal = paymentConditionsModal
            .filter(c => c.id !== conditionId)
            .reduce((acc, condition) => acc + calculateConditionTotalModal(condition), 0);
        
        return Math.max(0, currentUnitModalValue - otherConditionsTotal);
    };

    // Função para atualizar uma condição no array de `paymentConditionsModal`
    const updateConditionModal = (id, field, value) => {
        paymentConditionsModal = paymentConditionsModal.map(condition => {
            if (condition.id === id) {
                const updatedCondition = { ...condition, [field]: value };
                
                // Lógica de ajuste de valueType baseada no tipo
                if (field === 'type') {
                    if (value === 'percentage') {
                        updatedCondition.valueType = 'proportional'; 
                    } else { // Mudou para 'value' (valor fixo)
                        updatedCondition.valueType = 'literal'; // Sugere literal
                    }
                }
                
                // Lógica de "preencher o restante" / "sugerir máximo"
                // Se o campo alterado for 'type', 'installments' ou 'amount', recalculamos
                if (field === 'type' || field === 'installments' || field === 'amount') {
                    const maxAllowed = calculateMaxAllowedForConditionModal(id);
                    if (updatedCondition.type === 'percentage' || updatedCondition.valueType === 'proportional') {
                        const maxPercentage = (currentUnitModalValue > 0) ? (maxAllowed / currentUnitModalValue) * 100 : 0;
                        if (updatedCondition.amount > maxPercentage) {
                            // updatedCondition.amount = Math.round(maxPercentage * 100) / 100; // Limita ao máximo
                        }
                    } else { // Valor fixo literal
                        if (updatedCondition.amount > maxAllowed) {
                            // updatedCondition.amount = maxAllowed; // Limita ao máximo
                        }
                    }
                }
                
                return updatedCondition;
            }
            return condition;
        });
        renderPaymentConditionsModal(); // Re-renderiza após atualização
    };

    // Função para adicionar nova condição
    const addConditionModal = (data = {}) => {
        const totalUsed = paymentConditionsModal.reduce((acc, condition) => acc + calculateConditionTotalModal(condition), 0);
        const remainingValue = Math.max(0, currentUnitModalValue - totalUsed);
        const remainingPercentage = (currentUnitModalValue > 0) ? (remainingValue / currentUnitModalValue) : 0; // Return as decimal for percentage
        
        const newCondition = {
            id: data.id || Date.now(),
            description: data.description || '',
            type: data.type || 'percentage', // Padrão
            valueType: data.valueType || 'proportional', // Padrão
            installments: data.installments || 1,
            amount: data.amount !== undefined ? data.amount : (data.type === 'percentage' ? remainingPercentage.toFixed(4) : 0), // Suggest remaining
        };
        paymentConditionsModal.push(newCondition);
        renderPaymentConditionsModal();
    };


    // Função para remover condição
    const removeConditionModal = (id) => {
        if (paymentConditionsModal.length > 0) { 
            paymentConditionsModal = paymentConditionsModal.filter(condition => condition.id !== id);
            renderPaymentConditionsModal();
        }
    };
    
    // Função para renderizar as condições na UI do modal
    const renderPaymentConditionsModal = () => {
        if (!paymentFlowModalTbody) return;
        paymentFlowModalTbody.innerHTML = ''; 

        paymentConditionsModal.forEach((condition, index) => {
            const tr = document.createElement('tr');
            tr.classList.add('payment-flow-item-row');
            
            let amountInputType = 'number'; 
            let amountStep = "0.0001"; // Para porcentagens em decimal
            let amountValueDisplay = condition.amount;

            if (condition.type === 'value') { // If type is "Valor Fixo (R$)"
                amountInputType = 'text'; // Use text for currency mask
                amountValueDisplay = formatCurrencyInput(condition.amount * 100);
            }

            tr.innerHTML = `
                <td><input type="text" name="fluxo_pagamento_modal[${index}][description]" value="${condition.description || ''}" placeholder="Ex: Sinal, Mensais" class="form-control" required></td>
                <td><input type="number" name="fluxo_pagamento_modal[${index}][installments]" value="${condition.installments || 1}" min="1" class="form-control installments-input" required></td>
                <td>
                    <select name="fluxo_pagamento_modal[${index}][type]" class="form-control type-select" required>
                        <option value="percentage" ${condition.type === 'percentage' ? 'selected' : ''}>Percentual (%)</option>
                        <option value="value" ${condition.type === 'value' ? 'selected' : ''}>Valor Fixo (R$)</option>
                    </select>
                </td>
                <td>
                    <select name="fluxo_pagamento_modal[${index}][valueType]" class="form-control value-type-select" ${condition.type === 'percentage' ? 'disabled' : ''} required>
                        <option value="literal" ${condition.valueType === 'literal' ? 'selected' : ''}>Literal</option>
                        <option value="proportional" ${condition.valueType === 'proportional' ? 'selected' : ''}>Proporcional ao Valor da Unidade</option>
                    </select>
                </td>
                <td>
                    <label class="block text-sm font-medium text-gray-700 mb-1 d-none">${amountInputType === 'text' ? 'Valor (R$)' : 'Porcentagem (%)'}</label>
                    <input type="${amountInputType}" name="fluxo_pagamento_modal[${index}][amount]" value="${amountValueDisplay}" class="form-control amount-input" min="0" step="${amountStep}" required>
                </td>
                <td class="total-parcela-display">${formatCurrency(calculateConditionTotalModal(condition))}</td>
                <td class="valor-por-parcela-display">${formatCurrency(calculateConditionTotalModal(condition) / condition.installments)}</td>
                <td><button type="button" class="btn btn-danger btn-sm remove-parcela-modal">Remover</button></td>
            `;
            paymentFlowModalTbody.appendChild(tr);

            // Add event listeners for dynamic updates
            const typeSelect = tr.querySelector('.type-select');
            const valueTypeSelect = tr.querySelector('.value-type-select');
            const installmentsInput = tr.querySelector('.installments-input');
            const amountInput = tr.querySelector('.amount-input');
            const removeBtn = tr.querySelector('.remove-parcela-modal');

            typeSelect.addEventListener('change', (e) => updateConditionModal(condition.id, 'type', e.target.value));
            valueTypeSelect.addEventListener('change', (e) => updateConditionModal(condition.id, 'valueType', e.target.value));
            installmentsInput.addEventListener('input', (e) => updateConditionModal(condition.id, 'installments', Math.max(1, Number(e.target.value))));
            amountInput.addEventListener('input', (e) => {
                let val = amountInputType === 'text' ? cleanCurrencyInput(e.target.value) : Number(e.target.value);
                updateConditionModal(condition.id, 'amount', Math.max(0, val));
            });
            if (amountInputType === 'text') { amountInput.addEventListener('blur', (e) => { e.target.value = formatCurrencyInput(cleanCurrencyInput(e.target.value) * 100); }); }
            removeBtn.addEventListener('click', () => removeConditionModal(condition.id));
            
            // Aplica máscaras e listeners para campos numéricos
            applyMaskToCurrencyAndDecimalInputs(tr);
        });

        updatePaymentFlowSummaryModal(); // Atualiza o resumo
    };

    // Atualiza o resumo final do modal
    const updatePaymentFlowSummaryModal = () => {
        if (!totalPlanoModalDisplay || !totalPlanoModalValidation || !paymentUnitValueModalDisplay) return;

        const totalCalculatedValue = paymentConditionsModal.reduce((acc, condition) => acc + calculateConditionTotalModal(condition), 0);
        const totalPercentage = (currentUnitModalValue > 0) ? (totalCalculatedValue / currentUnitModalValue) * 100 : 0;
        const remainingValue = calculateRemainingValueModal();

        totalPlanoModalDisplay.textContent = formatCurrency(totalCalculatedValue);

        totalPlanoModalValidation.textContent = '';
        totalPlanoModalValidation.style.color = 'initial';

        const tolerance = 0.01; // 1 centavo de tolerância
        if (paymentConditionsModal.length === 0) {
            totalPlanoModalValidation.textContent = 'Adicione parcelas ao plano de pagamento.';
            totalPlanoModalValidation.style.color = 'red';
        } else if (Math.abs(totalPercentage - 100) > tolerance) {
            totalPlanoModalValidation.textContent = `Atenção: O plano de pagamento totaliza ${totalPercentage.toFixed(2)}%. Faltam ${formatCurrency(remainingValue)} para completar o valor da unidade.`;
            totalPlanoModalValidation.style.color = 'red';
        } else {
            totalPlanoModalValidation.textContent = 'Plano de pagamento totalizou 100% do valor da unidade. OK!';
            totalPlanoModalValidation.style.color = 'green';
        }

        const addConditionButton = document.getElementById('add_parcela_modal');
        if (addConditionButton) { 
            addConditionButton.disabled = Math.abs(totalPercentage - 100) < tolerance;
            addConditionButton.classList.toggle('bg-blue-500', Math.abs(totalPercentage - 100) > tolerance);
            addConditionButton.classList.toggle('text-white', Math.abs(totalPercentage - 100) > tolerance);
            addConditionButton.classList.toggle('hover:bg-blue-600', Math.abs(totalPercentage - 100) > tolerance);
            addConditionButton.classList.toggle('bg-gray-300', Math.abs(totalPercentage - 100) < tolerance);
            addConditionButton.classList.toggle('text-gray-500', Math.abs(totalPercentage - 100) < tolerance);
            addConditionButton.classList.toggle('cursor-not-allowed', Math.abs(totalPercentage - 100) < tolerance);
        }
    };


    // Event Listener para abrir o modal de Condição de Pagamento (Construtor)
    document.querySelectorAll('.edit-payment-btn').forEach(button => {
        button.addEventListener('click', function() {
            const unitId = this.dataset.unitId;
            const currentPaymentInfoJson = this.dataset.currentPaymentInfo;
            const unitValue = parseFloat(this.dataset.unitValue);

            document.getElementById('editPaymentUnitId').value = unitId;
            currentUnitModalValue = unitValue; // Define o valor da unidade para cálculos
            if (paymentUnitValueModalDisplay) paymentUnitValueModalDisplay.textContent = formatCurrency(currentUnitModalValue);

            // Tenta carregar as condições existentes ou inicia uma nova
            try {
                const parsedInfo = JSON.parse(currentPaymentInfoJson);
                paymentConditionsModal = Array.isArray(parsedInfo) ? parsedInfo : [];
            } catch (e) {
                console.warn("Informações de pagamento não são um JSON válido para a unidade", unitId, e);
                paymentConditionsModal = [];
            }

            if (paymentConditionsModal.length === 0 && currentUnitModalValue > 0) {
                addConditionModal(); // Adiciona uma linha vazia se não houver condições
            } else {
                renderPaymentConditionsModal(); // Renderiza as condições existentes
            }
            
            $('#editPaymentModal').modal('show'); // Abre o modal
        });
    });

    // Event listener para adicionar nova parcela no modal
    if (addParcelaModalBtn) {
        addParcelaModalBtn.addEventListener('click', () => addConditionModal());
    }

    // Event Listener para salvar o plano de pagamento (botão dentro do modal)
    document.getElementById('saveNewPaymentBtn').addEventListener('click', function() {
        // Validação final antes de salvar
        if (paymentConditionsModal.length === 0) {
            alert("Adicione pelo menos uma condição de pagamento.");
            return;
        }
        const totalPercentage = calculateUsedPercentageModal();
        const tolerance = 0.01;
        if (Math.abs(totalPercentage - 100) > tolerance) {
            alert(`Atenção: O plano de pagamento não totaliza 100% do valor da unidade (${totalPercentage.toFixed(2)}%). Por favor, ajuste as condições.`);
            return;
        }

        const unitId = document.getElementById('editPaymentUnitId').value;
        const dataToSave = JSON.stringify(paymentConditionsModal);

        fetch(`${BASE_API_UNIDADES_URL}update_payment_info.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `unit_id=${unitId}&new_payment_info=${encodeURIComponent(dataToSave)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                $('#editPaymentModal').modal('hide');
                window.location.reload();
            } else {
                alert('Erro ao salvar plano de pagamento: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro na requisição AJAX:', error);
            alert('Ocorreu um erro ao comunicar com o servidor.');
        });
    });

});