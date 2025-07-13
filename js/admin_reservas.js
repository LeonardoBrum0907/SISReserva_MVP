document.addEventListener('DOMContentLoaded', function() {
    const reservasTable = document.getElementById('reservasTable');
    const searchInput = document.getElementById('reservaSearch');
    const filterStatusSelect = document.getElementById('reservaFilterStatus');

    // Elementos do Modal de Feedback
    const feedbackModal = document.getElementById('feedbackModal');
    const feedbackModalTitle = document.getElementById('feedbackModalTitle');
    const feedbackModalMessage = document.getElementById('feedbackModalMessage');
    // Adicionado seletor para pegar todos os botões de fechar, incluindo o "OK"
    const feedbackModalCloseBtns = feedbackModal ? feedbackModal.querySelectorAll('.modal-close-btn, .modal-footer .btn') : []; 

    // Função para abrir o modal de feedback
    function showFeedbackModal(title, message, isSuccess) {
        if (!feedbackModal) return;
        feedbackModalTitle.textContent = title;
        feedbackModalMessage.textContent = message;
        feedbackModal.classList.remove('modal-success', 'modal-error', 'active'); // Limpa classes e esconde antes de reativar
        if (isSuccess) {
            feedbackModal.classList.add('modal-success');
        } else {
            feedbackModal.classList.add('modal-error');
        }
        feedbackModal.classList.add('active'); // Ativa o modal
    }

    // Event listeners para fechar o modal de feedback
    feedbackModalCloseBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            feedbackModal.classList.remove('active');
            // Opcional: Recarregar a página ou atualizar a tabela após fechar o feedback,
            // se a ação original não o fez. Para consistência, o handleFormSubmit já faz isso.
            // location.reload(); 
        });
    });

    // Funções para Modais de Ação
    function openModal(modalId, reservaId = null, docId = null) {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Primeiro, garantir que o feedbackModal esteja fechado se estiver ativo
            feedbackModal.classList.remove('active');

            modal.classList.add('active');
            // Preenche o input hidden com o ID da reserva
            const inputReservaId = modal.querySelector('input[name="reserva_id"]');
            if (inputReservaId) {
                inputReservaId.value = reservaId;
            }
            // Preenche o input hidden com o ID do documento (se for modal de documento)
            const inputDocId = modal.querySelector('input[name="document_id"]');
            if (inputDocId) {
                inputDocId.value = docId;
            }

            // Atualiza display de ID para modais específicos
            const modalReservaIdDisplay = modal.querySelector('#modalReservaIdDisplay');
            if (modalReservaIdDisplay) {
                modalReservaIdDisplay.textContent = `#${reservaId}`;
            }
            const simulateSignReservaIdDisplay = modal.querySelector('#simulateSignReservaIdDisplay');
            if (simulateSignReservaIdDisplay) {
                simulateSignReservaIdDisplay.textContent = `#${reservaId}`;
            }

            // Se for modal de edição de clientes, carrega os dados
            if (modalId === 'editClientsModal') {
                loadClientsForEdit(reservaId);
            }
            
            // Para o modal de rejeição de documento, focar no campo de motivo
            if (modalId === 'rejectDocumentModal') {
                const rejectionReasonField = modal.querySelector('#rejectionReason');
                if (rejectionReasonField) {
                    rejectionReasonField.focus();
                }
            }
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            // Limpar campos de texto ao fechar, se necessário
            const rejectionReasonReserva = modal.querySelector('#rejectionReasonReserva');
            if (rejectionReasonReserva) rejectionReasonReserva.value = '';
            const cancelReasonReserva = modal.querySelector('#cancelReasonReserva');
            if (cancelReasonReserva) cancelReasonReserva.value = '';
            const rejectionReasonDoc = modal.querySelector('#rejectionReason');
            if (rejectionReasonDoc) rejectionReasonDoc.value = '';
        }
    }

    // Event Listeners para abrir modais (tanto na listagem quanto nos detalhes)
    // Para a tabela principal de reservas (index.php)
    if (reservasTable) {
        reservasTable.addEventListener('click', function(event) {
            const button = event.target.closest('button[data-reserva-id]');
            if (!button) return;

            const reservaId = button.dataset.reservaId;

            if (button.classList.contains('approve-reserva-btn')) {
                openModal('approveReservaModal', reservaId);
            } else if (button.classList.contains('reject-reserva-btn')) {
                openModal('rejectReservaModal', reservaId);
            } else if (button.classList.contains('request-docs-btn')) {
                openModal('requestDocsModal', reservaId);
            } else if (button.classList.contains('finalize-sale-btn')) {
                openModal('finalizeSaleModal', reservaId);
            } else if (button.classList.contains('cancel-reserva-btn')) {
                openModal('cancelReservaModal', reservaId);
            }
        });
    }

    // Para a página de detalhes da reserva (detalhes.php) - Botões de Ações da Reserva
    const reservationActionsFooter = document.querySelector('.reservation-actions-footer');
    if (reservationActionsFooter) {
        reservationActionsFooter.addEventListener('click', function(event) {
            const button = event.target.closest('button[data-reserva-id]');
            if (!button) return;

            const reservaId = button.dataset.reservaId;

            if (button.classList.contains('approve-reserva-btn') || button.id === 'approveReservaBtnDetails') {
                openModal('approveReservaModal', reservaId);
            } else if (button.classList.contains('reject-reserva-btn') || button.id === 'rejectReservaBtnDetails') {
                openModal('rejectReservaModal', reservaId);
            } else if (button.classList.contains('request-docs-btn') || button.id === 'requestDocsBtnDetails') {
                openModal('requestDocsModal', reservaId);
            } else if (button.classList.contains('finalize-sale-btn')) {
                openModal('finalizeSaleModal', reservaId);
            } else if (button.classList.contains('cancel-reserva-btn')) {
                openModal('cancelReservaModal', reservaId);
            } else if (button.classList.contains('simulate-sign-contract-btn')) { // NOVO
                openModal('simulateSignContractModal', reservaId);
            }
        });
    }

    // Para a página de detalhes da reserva (detalhes.php) - Botões de Documentos Enviados
    const documentTable = document.querySelector('.document-table');
    if (documentTable) {
        documentTable.addEventListener('click', function(event) {
            const button = event.target.closest('button[data-doc-id][data-reserva-id]');
            if (!button) return;

            const docId = button.dataset.docId;
            const reservaId = button.dataset.reservaId;

            if (button.classList.contains('approve-doc-btn')) {
                openModal('approveDocumentModal', reservaId, docId);
            } else if (button.classList.contains('reject-doc-btn')) {
                openModal('rejectDocumentModal', reservaId, docId);
            }
        });
    }

    // Event Listeners para fechar modais (todos)
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(event) {
            // Fecha se clicar no overlay ou no botão com classe 'modal-close-btn' dentro do modal
            if (event.target === overlay || event.target.classList.contains('modal-close-btn')) {
                closeModal(overlay.id);
            }
        });
    });

    // Impede que o clique dentro do conteúdo do modal feche o modal
    document.querySelectorAll('.modal-content').forEach(content => {
        content.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    });

    // Lógica para submeter formulários de ação via Fetch API
    function handleFormSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);

        fetch(BASE_URL_JS + 'api/reserva.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error('Server responded with non-OK status: ' + response.status + ' - ' + text);
                });
            }
            return response.json();
        })
        .then(data => {
            closeModal(form.closest('.modal-overlay').id); // Fecha o modal de ação
            if (data.success) {
                showFeedbackModal('Sucesso!', data.message, true);
                // Pequeno delay para a mensagem ser vista antes de recarregar
                setTimeout(() => location.reload(), 1500);
            } else {
                showFeedbackModal('Erro!', data.message, false);
            }
        })
        .catch(error => {
            closeModal(form.closest('.modal-overlay').id); // Fecha o modal de ação
            showFeedbackModal('Erro de Comunicação', 'Ocorreu um erro ao comunicar com o servidor. Detalhes: ' + error.message, false);
        });
    }

    // Adiciona event listeners a todos os formulários de ação de reserva/documento
    document.body.querySelectorAll('form#approveReservaForm, form#rejectReservaForm, form#requestDocsForm, form#cancelReservaForm, form#approveDocumentForm, form#rejectDocumentForm, form#editClientsForm').forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });

    // Lógica específica para o botão de finalizar venda
    const confirmFinalizeSaleBtn = document.getElementById('confirmFinalizeSaleBtn');
    if (confirmFinalizeSaleBtn) {
        confirmFinalizeSaleBtn.addEventListener('click', function() {
            const reservaId = document.getElementById('finalizeSaleReservaIdInput').value;
            const formData = new FormData();
            formData.append('action', 'finalize_sale');
            formData.append('reserva_id', reservaId);
            
            fetch(BASE_URL_JS + 'api/reserva.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Server responded with non-OK status: ' + response.status + ' - ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                closeModal('finalizeSaleModal');
                if (data.success) {
                    showFeedbackModal('Venda Finalizada!', data.message, true);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showFeedbackModal('Erro ao Finalizar Venda', data.message, false);
                }
            })
            .catch(error => {
                closeModal('finalizeSaleModal');
                showFeedbackModal('Erro de Comunicação', 'Ocorreu um erro ao finalizar a venda: ' + error.message, false);
            });
        });
    }

    // NOVO: Lógica específica para o botão de Simular Assinatura Eletrônica
    const confirmSimulateSignContractBtn = document.getElementById('confirmSimulateSignContractBtn');
    if (confirmSimulateSignContractBtn) {
        confirmSimulateSignContractBtn.addEventListener('click', function() {
            const reservaId = document.getElementById('simulateSignContractModal').querySelector('input[name="reserva_id"]').value;
            const formData = new FormData();
            formData.append('action', 'simulate_sign_contract');
            formData.append('reserva_id', reservaId);
            
            fetch(BASE_URL_JS + 'api/reserva.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Server responded with non-OK status: ' + response.status + ' - ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                closeModal('simulateSignContractModal');
                if (data.success) {
                    showFeedbackModal('Assinatura Simulada!', data.message, true);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showFeedbackModal('Erro na Simulação', data.message, false);
                }
            })
            .catch(error => {
                closeModal('simulateSignContractModal');
                showFeedbackModal('Erro de Comunicação', 'Ocorreu um erro ao simular a assinatura: ' + error.message, false);
            });
        });
    }


    // --- Lógica de Edição de Clientes (para detalhes.php) ---
    const editClientsBtn = document.getElementById('editClientsBtn');
    if (editClientsBtn) {
        editClientsBtn.addEventListener('click', function() {
            const reservaId = this.dataset.reservaId;
            openModal('editClientsModal', reservaId);
        });
    }

    const addClientBtn = document.getElementById('addClientBtn');
    const clientsContainer = document.getElementById('clientsContainer');
    let clientCounter = 0; // Para IDs únicos de novos clientes

    function addClientRow(client = null) {
        const isNew = client === null;
        // Gera um ID único para o grupo de campos do cliente
        const rowId = isNew ? 'new-client-' + clientCounter++ : 'existing-client-' + client.cliente_id;

        const div = document.createElement('div');
        div.classList.add('client-form-row');
        div.setAttribute('id', rowId); // Adiciona um ID para a linha do cliente
        div.innerHTML = `
            <h4>${isNew ? 'Novo Comprador' : 'Comprador Existente'} ${isNew ? '' : `#${client.cliente_id}`}</h4>
            <input type="hidden" name="clients[${rowId}][id]" value="${client ? client.cliente_id : 'new'}">
            <div class="form-group">
                <label for="nome_${rowId}">Nome:</label>
                <input type="text" id="nome_${rowId}" name="clients[${rowId}][nome]" value="${client ? client.nome : ''}" required>
            </div>
            <div class="form-group">
                <label for="cpf_${rowId}">CPF:</label>
                <input type="text" id="cpf_${rowId}" name="clients[${rowId}][cpf]" value="${client ? formatCPF(client.cpf) : ''}" data-mask="###.###.###-##" required>
            </div>
            <div class="form-group">
                <label for="email_${rowId}">Email:</label>
                <input type="email" id="email_${rowId}" name="clients[${rowId}][email]" value="${client ? client.email : ''}" required>
            </div>
            <div class="form-group">
                <label for="whatsapp_${rowId}">WhatsApp:</label>
                <input type="text" id="whatsapp_${rowId}" name="clients[${rowId}][whatsapp]" value="${client ? formatWhatsApp(client.whatsapp) : ''}" data-mask="(##) #####-####" required>
            </div>
            <div class="form-group">
                <label for="cep_${rowId}">CEP:</label>
                <input type="text" id="cep_${rowId}" name="clients[${rowId}][cep]" value="${client ? formatCEP(client.cep) : ''}" data-mask="#####-###" class="cep-input">
            </div>
            <div class="form-group">
                <label for="endereco_${rowId}">Endereço:</label>
                <input type="text" id="endereco_${rowId}" name="clients[${rowId}][endereco]" value="${client ? client.endereco : ''}">
            </div>
            <div class="form-group">
                <label for="numero_${rowId}">Número:</label>
                <input type="text" id="numero_${rowId}" name="clients[${rowId}][numero]" value="${client ? client.numero : ''}">
            </div>
            <div class="form-group">
                <label for="complemento_${rowId}">Complemento:</label>
                <input type="text" id="complemento_${rowId}" name="clients[${rowId}][complemento]" value="${client ? client.complemento : ''}">
            </div>
            <div class="form-group">
                <label for="bairro_${rowId}">Bairro:</label>
                <input type="text" id="bairro_${rowId}" name="clients[${rowId}][bairro]" value="${client ? client.bairro : ''}">
            </div>
            <div class="form-group">
                <label for="cidade_${rowId}">Cidade:</label>
                <input type="text" id="cidade_${rowId}" name="clients[${rowId}][cidade]" value="${client ? client.cidade : ''}">
            </div>
            <div class="form-group">
                <label for="estado_${rowId}">Estado:</label>
                <input type="text" id="estado_${rowId}" name="clients[${rowId}][estado]" value="${client ? client.estado : ''}">
            </div>
            ${clientsContainer.children.length > 0 ? '<button type="button" class="btn btn-danger btn-sm remove-client-btn"><i class="fas fa-trash"></i> Remover Comprador</button>' : ''}
            <hr>
        `;
        clientsContainer.appendChild(div);

        // Aplicar máscaras aos novos campos
        const newInputs = div.querySelectorAll('input[data-mask]');
        newInputs.forEach(input => {
            applyMask(input);
        });

        // Adicionar listener para busca de CEP nos novos campos
        const cepInput = div.querySelector('.cep-input');
        if (cepInput) {
            cepInput.addEventListener('blur', function() {
                handleCepLookup(this.value, div);
            });
        }

        // Adicionar listener para remover comprador
        const removeButton = div.querySelector('.remove-client-btn');
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                div.remove();
            });
        }
    }

    if (addClientBtn) {
        addClientBtn.addEventListener('click', () => addClientRow());
    }

    // Função para carregar clientes existentes da reserva
    function loadClientsForEdit(reservaId) {
        if (!clientsContainer) return;
        clientsContainer.innerHTML = ''; // Limpa antes de carregar
        clientCounter = 0; // Reseta o contador para novos clientes

        fetch(BASE_URL_JS + 'api/reserva.php?action=get_clients&reserva_id=' + reservaId)
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Server responded with non-OK status: ' + response.status + ' - ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.clients.length > 0) {
                    data.clients.forEach(client => {
                        addClientRow(client);
                    });
                } else {
                    addClientRow(); // Adiciona um campo de comprador vazio se não houver clientes
                }
            })
            .catch(error => {
                console.error('Erro ao carregar clientes:', error);
                showFeedbackModal('Erro', 'Não foi possível carregar os clientes da reserva: ' + error.message, false);
            });
    }

    // Máscaras de input (já deve estar em forms.js ou helpers.js)
    function applyMask(input) {
        const mask = input.dataset.mask;
        if (!mask) return;

        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, ''); // Remove tudo que não é dígito
            let maskedValue = '';
            let maskIndex = 0;
            let valueIndex = 0;

            while (maskIndex < mask.length && valueIndex < value.length) {
                if (mask[maskIndex] === '#') {
                    maskedValue += value[valueIndex];
                    valueIndex++;
                } else {
                    maskedValue += mask[maskIndex];
                }
                maskIndex++;
            }
            this.value = maskedValue;
        });
    }

    // Função para formatar CPF, WhatsApp e CEP para exibição
    function formatCPF(cpf) {
        if (!cpf) return '';
        cpf = cpf.replace(/\D/g, '');
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }

    function formatWhatsApp(whatsapp) {
        if (!whatsapp) return '';
        whatsapp = whatsapp.replace(/\D/g, '');
        if (whatsapp.length === 11) {
            return whatsapp.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        return whatsapp; // Retorna sem formatar se não tiver 11 dígitos
    }

    function formatCEP(cep) {
        if (!cep) return '';
        cep = cep.replace(/\D/g, '');
        return cep.replace(/(\d{5})(\d{3})/, '$1-$2');
    }

    // Lógica para busca de CEP (assumindo API endpoint)
    function handleCepLookup(cep, containerElement) {
        const rawCep = cep.replace(/\D/g, '');
        if (rawCep.length !== 8) return;

        fetch(BASE_URL_JS + 'api/cep.php?cep=' + rawCep)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    containerElement.querySelector('input[name$="[endereco]"]').value = data.logradouro || '';
                    containerElement.querySelector('input[name$="[bairro]"]').value = data.bairro || '';
                    containerElement.querySelector('input[name$="[cidade]"]').value = data.localidade || '';
                    containerElement.querySelector('input[name$="[estado]"]').value = data.uf || '';
                } else {
                    // console.warn('CEP não encontrado ou erro na busca:', data.message || 'Erro desconhecido.');
                    // Não alertar o usuário diretamente, apenas limpar campos ou deixar em branco
                    containerElement.querySelector('input[name$="[endereco]"]').value = '';
                    containerElement.querySelector('input[name$="[bairro]"]').value = '';
                    containerElement.querySelector('input[name$="[cidade]"]').value = '';
                    containerElement.querySelector('input[name$="[estado]"]').value = '';
                }
            })
            .catch(error => {
                console.error('Erro na busca de CEP:', error);
                // alert('Erro na busca de CEP.'); // Evitar alertas diretos para UX
            });
    }

    // Aplica máscaras e listeners de CEP aos campos existentes na página (geralmente para o primeiro carregamento)
    document.querySelectorAll('input[data-mask]').forEach(applyMask);
    document.querySelectorAll('.cep-input').forEach(input => {
        input.addEventListener('blur', function() {
            // Ao invés de `this.closest`, podemos ter um container wrapper por cliente
            // Ou, se for na página principal de cadastro/edição, ele pode preencher os campos globais.
            // Aqui, passamos o elemento pai mais próximo que contenha todos os campos de endereço
            handleCepLookup(this.value, this.closest('.client-form-row') || document); // fallback para document se não estiver em um client-form-row
        });
    });

    // --- Lógica de Pesquisa e Filtragem (Client-side) ---
    if (searchInput) {
        searchInput.addEventListener('keyup', applyFiltersAndSearch);
    }
    if (filterStatusSelect) {
        filterStatusSelect.addEventListener('change', applyFiltersAndSearch);
    }

    function applyFiltersAndSearch() {
        if (!reservasTable) return;

        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const filterStatus = filterStatusSelect ? filterStatusSelect.value : '';

        const rows = reservasTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const rowStatus = row.dataset.reservaStatus;

            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = filterStatus === '' || rowStatus === filterStatus;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // --- Lógica de Ordenação de Tabela (Client-side) ---
    let sortDirection = {};

    if (reservasTable) {
        reservasTable.querySelectorAll('th[data-sort-by]').forEach(header => {
            const columnKey = header.dataset.sortBy;
            sortDirection[columnKey] = 'asc';

            header.addEventListener('click', function() {
                const tbody = reservasTable.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));

                const isAsc = sortDirection[columnKey] === 'asc';
                sortDirection[columnKey] = isAsc ? 'desc' : 'asc';

                rows.sort((a, b) => {
                    // Mapeamento das colunas para os índices do array children
                    // Verifique se a ordem das colunas no HTML corresponde a este mapeamento
                    const columnMap = {
                        'reserva_id': 0,
                        'data_reserva': 1,
                        'empreendimento_nome': 2,
                        'unidade_numero': 3,
                        'cliente_nome': 4,
                        'corretor_nome': 5,
                        'valor_reserva': 6,
                        'status': 7,
                        'data_expiracao': 8,
                        'data_ultima_interacao': 9 // Última Interação
                    };
                    const columnIndex = columnMap[columnKey];

                    let aValue, bValue;

                    if (columnKey === 'data_reserva' || columnKey === 'data_ultima_interacao') {
                        // Para colunas de data/hora, pegamos o texto completo e o tempo para construir uma data válida
                        const dateTimeStrA = a.children[columnIndex].textContent.trim();
                        aValue = parseBrazilianDateTime(dateTimeStrA);
                        
                        const dateTimeStrB = b.children[columnIndex].textContent.trim();
                        bValue = parseBrazilianDateTime(dateTimeStrB);

                    } else if (columnKey === 'unidade_numero') {
                        aValue = parseInt(a.children[columnIndex].textContent.trim().split(' ')[0]) || 0; // Pega só o número, ex: "101 (1º Andar)"
                        bValue = parseInt(b.children[columnIndex].textContent.trim().split(' ')[0]) || 0;
                    } else if (columnKey === 'valor_reserva') {
                        aValue = parseFloat(a.children[columnIndex].textContent.replace(/[R$\s.]/g, '').replace(',', '.')) || 0;
                        bValue = parseFloat(b.children[columnIndex].textContent.replace(/[R$\s.]/g, '').replace(',', '.')) || 0;
                    } else if (columnKey === 'data_expiracao') {
                        aValue = parseRemainingTimeForSorting(a.children[columnIndex].textContent.trim());
                        bValue = parseRemainingTimeForSorting(b.children[columnIndex].textContent.trim());
                    } else {
                        aValue = a.children[columnIndex].textContent.trim().toLowerCase();
                        bValue = b.children[columnIndex].textContent.trim().toLowerCase();
                    }
                    
                    if (aValue < bValue) return isAsc ? -1 : 1;
                    if (aValue > bValue) return isAsc ? 1 : -1;
                    return 0;
                });

                // Resetar ícones de ordenação para todas as colunas
                reservasTable.querySelectorAll('th .fas.fa-sort, th .fas.fa-sort-up, th .fas.fa-sort-down').forEach(icon => {
                    icon.classList.remove('fa-sort-up', 'fa-sort-down');
                    icon.classList.add('fa-sort');
                });

                // Atualizar ícone da coluna clicada
                const icon = header.querySelector('.fas.fa-sort');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(isAsc ? 'fa-sort-up' : 'fa-sort-down');
                }

                rows.forEach(row => tbody.appendChild(row));
            });
        });
    }

    // Função auxiliar para parsear data/hora brasileira (dd/mm/yyyy hh:mm:ss) para objeto Date
    function parseBrazilianDateTime(dateTimeStr) {
        if (dateTimeStr === 'N/A') {
             return new Date(0); // Retorna uma data inválida para N/A para fins de ordenação
        }
        // Separa data e hora, depois componentes
        const parts = dateTimeStr.match(/(\d{2})\/(\d{2})\/(\d{4})[^\d]*(\d{2}):(\d{2}):(\d{2})/);
        if (parts) {
            // new Date(year, monthIndex, day, hours, minutes, seconds)
            return new Date(parts[3], parts[2] - 1, parts[1], parts[4], parts[5], parts[6]);
        }
        return new Date(0); // Retorna data inválida se o formato não corresponder
    }

    function parseRemainingTimeForSorting(timeStr) {
        if (timeStr === 'N/A') return -Infinity;
        if (timeStr === 'Expirada') return -1; // Prioriza expiradas como as "menores"

        // Regex mais robusta para pegar qualquer número seguido por unidade
        const match = timeStr.match(/(\d+)\s*(dia|hora|minuto|segundo)(s?)/i);
        if (match) {
            const value = parseInt(match[1]);
            const unit = match[2].toLowerCase();
            let totalSeconds = 0;
            switch (unit) {
                case 'dia': totalSeconds = value * 24 * 3600; break;
                case 'hora': totalSeconds = value * 3600; break;
                case 'minuto': totalSeconds = value * 60; break;
                case 'segundo': totalSeconds = value; break;
            }
            return totalSeconds;
        }
        return Infinity; // Para qualquer outro texto, trata como "muito tempo"
    }

    // --- Lógica de Impressão (adicionando para o botão "Imprimir" nos detalhes) ---
    const printReservaBtn = document.getElementById('printReservaBtn');
    if (printReservaBtn) {
        printReservaBtn.addEventListener('click', function() {
            // Abre uma nova janela/aba para imprimir apenas o conteúdo principal
            const contentToPrint = document.querySelector('.admin-content-wrapper').innerHTML;
            const printWindow = window.open('', '', 'height=800,width=800');
            printWindow.document.write('<html><head><title>Imprimir Reserva</title>');
            // Inclui os estilos necessários para a impressão
            printWindow.document.write('<link rel="stylesheet" href="' + BASE_URL_JS + 'css/style.css">');
            printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">');
            printWindow.document.write('<style>');
            printWindow.document.write('@media print {');
            printWindow.document.write('body { margin: 15mm; }'); // Margens para impressão
            printWindow.document.write('.admin-content-wrapper { width: 100%; float: none; }'); // Remover flutuações
            printWindow.document.write('h2, h3 { color: #333; text-align: center; margin-bottom: 20px; }'); // Títulos
            printWindow.document.write('.details-grid p { display: block; width: 100%; margin-bottom: 5px; }'); // Detalhes em linha
            printWindow.document.write('.details-grid strong { display: inline-block; width: 150px; text-align: right; margin-right: 10px; }'); // Alinhar labels
            printWindow.document.write('.admin-table th, .admin-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }'); // Bordas da tabela
            printWindow.document.write('.admin-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }');
            printWindow.document.write('.admin-table-actions, .form-actions, .btn { display: none; }'); // Esconder botões e ações
            printWindow.document.write('.status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; }');
            printWindow.document.write('.status-solicitada { background-color: var(--color-warning-light); color: var(--color-warning-dark); }');
            printWindow.document.write('.status-aprovada { background-color: var(--color-info-light); color: var(--color-info-dark); }');
            printWindow.document.write('.status-documentos_pendentes, .status-documentos_enviados, .status-documentos_rejeitados { background-color: var(--color-warning-light); color: var(--color-warning-dark); }');
            printWindow.document.write('.status-documentos_aprovados { background-color: var(--color-success-light); color: var(--color-success-dark); }');
            printWindow.document.write('.status-contrato_enviado, .status-aguardando_assinatura_eletronica { background-color: var(--color-secondary-light); color: var(--color-secondary-dark); }');
            printWindow.document.write('.status-vendida { background-color: var(--color-success-light); color: var(--color-success-dark); }');
            printWindow.document.write('.status-cancelada, .status-expirada, .status-dispensada { background-color: var(--color-danger-light); color: var(--color-danger-dark); }');
            printWindow.document.write('img { max-width: 100%; height: auto; display: block; margin: 10px auto; }');
            printWindow.document.write('pre { white-space: pre-wrap; word-wrap: break-word; }'); // Para auditoria
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(contentToPrint);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { // Pequeno delay para garantir que os estilos carreguem
                printWindow.print();
                printWindow.close();
            }, 500);
        });
    }
});