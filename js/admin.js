// js/admin.js - Lógicas JavaScript para o Painel Administrativo

document.addEventListener('DOMContentLoaded', () => {

    if (typeof BASE_URL_JS === 'undefined') {
        console.error("Erro: BASE_URL_JS não está definida. Verifique includes/header_dashboard.php.");
        window.BASE_URL_JS = '/';
    }

    // ===============================================
    // 1. FUNÇÕES AUXILIARES GLOBAIS (Reutilizáveis em várias páginas)
    // ===============================================

    // --- Função Global para Marcar TODOS os Alertas como Lidos ---
    window.markAllAlertsAsRead = function() {
        fetch(`${BASE_URL_JS}api/alert_actions.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=mark_all_read` // Nova ação para o backend
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload(); // Recarrega a página para atualizar o contador e a lista
            } else {
                alert('Erro ao marcar todos os alertas como lidos: ' + data.message);
            }
        })
        .catch(error => { console.error('Erro AJAX:', error); alert('Erro de rede ao marcar todos os alertas.'); });
    };

    // --- Funções de Máscara ---
    window.applyMask = function(input, maskPattern) { // Globalizada
        if (!input) return;
        input.removeEventListener('input', input._maskHandler);

        const maskHandler = (e) => {
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
        };
        input._maskHandler = maskHandler;
        input.addEventListener('input', maskHandler);
        maskHandler({ target: input });
    };

    window.applyCpfMask = function(input) { // Globalizada
        if (!input) return;
        input.removeEventListener('input', input._cpfMaskHandler);

        const cpfMaskHandler = (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            let maskedValue = value.replace(/(\d{3})(\d)/, '$1.$2');
            maskedValue = maskedValue.replace(/(\d{3})(\d)/, '$1.$2');
            maskedValue = maskedValue.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = maskedValue;
        };
        input._cpfMaskHandler = cpfMaskHandler;
        input.addEventListener('input', cpfMaskHandler);
        cpfMaskHandler({ target: input });
    };

    window.applyWhatsappMask = function(input) { // Globalizada
        if (!input) return;
        input.removeEventListener('input', input._whatsappMaskHandler);

        const whatsappMaskHandler = (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            let maskedValue = '';
            if (value.length > 10) {
                maskedValue = value.replace(/^(\d\d)(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 5) {
                maskedValue = value.replace(/^(\d\d)(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                maskedValue = value.replace(/^(\d*)/, '($1');
            } else {
                maskedValue = value.replace(/^(\d*)/, '($1');
            }
            e.target.value = maskedValue;
        };
        input._whatsappMaskHandler = whatsappMaskHandler;
        input.addEventListener('input', whatsappMaskHandler);
        whatsappMaskHandler({ target: input });
    };

    // --- Funções de Modal (CORRIGIDO PARA CONSISTÊNCIA E ROBUSTEZ) ---
    window.openModal = function(modalElement) { // Globalizada
        if (modalElement) {
            modalElement.classList.remove('hidden'); // Remove hidden caso esteja presente
            modalElement.style.display = 'block'; // Garante que esteja visível
            modalElement.classList.add('active'); // Adiciona a classe active para transições/estilos
            document.body.classList.add('modal-open'); // Adiciona classe ao body para controlar scroll
        }
    };

    window.closeModal = function(modalElement) { // Globalizada
        if (modalElement) {
            modalElement.classList.remove('active'); // Remove a classe active
            modalElement.style.display = 'none'; // Esconde o modal
            modalElement.classList.add('hidden'); // Adiciona hidden de volta se for o caso (para CSS)
            document.body.classList.remove('modal-open'); // Remove classe do body
        }
    };

    window.closeAllModals = function() { // Globalizada
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.classList.remove('active');
            modal.style.display = 'none';
            modal.classList.add('hidden');
        });
        document.body.classList.remove('modal-open');
    };

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (target.classList.contains('modal-close-btn')) {
            window.closeModal(target.closest('.modal-overlay'));
        } else if (target.classList.contains('modal-overlay')) {
            window.closeModal(target);
        }
    });

    // --- Função Genérica de Confirmação Via Modal ---
    window.showConfirmationModal = function(title, message, confirmText, confirmClass, callback) {
        let confirmationModal = document.getElementById('genericConfirmationModal');
        if (!confirmationModal) {
            confirmationModal = document.createElement('div');
            confirmationModal.id = 'genericConfirmationModal';
            confirmationModal.classList.add('modal-overlay');
            confirmationModal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="genericConfirmationTitle"></h3>
                        <button type="button" class="modal-close-btn">×</button>
                    </div>
                    <div class="modal-body">
                        <p id="genericConfirmationMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="button" id="genericConfirmButton"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(confirmationModal);
        }

        const titleElement = document.getElementById('genericConfirmationTitle');
        const messageElement = document.getElementById('genericConfirmationMessage');
        const confirmButton = document.getElementById('genericConfirmButton');

        titleElement.textContent = title;
        messageElement.innerHTML = message;
        confirmButton.textContent = confirmText;
        confirmButton.className = `btn ${confirmClass}`;

        const oldConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(oldConfirmButton, confirmButton);
        const newConfirmButton = document.getElementById('genericConfirmButton');

        newConfirmButton.addEventListener('click', () => {
            callback(true);
            window.closeModal(confirmationModal);
        });

        window.openModal(confirmationModal);
    };

    // --- Função para buscar CEP (globalmente acessível) ---
    window.buscarCEP = function(cepValue, enderecoId, bairroId, cidadeId, estadoId) {
        const cep = cepValue.replace(/\D/g, '');
        if (cep.length !== 8) return;

        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (!data.erro) {
                    if (document.getElementById(enderecoId)) document.getElementById(enderecoId).value = data.logradouro || '';
                    if (document.getElementById(bairroId)) document.getElementById(bairroId).value = data.bairro || '';
                    if (document.getElementById(cidadeId)) document.getElementById(cidadeId).value = data.localidade || '';
                    if (document.getElementById(estadoId)) document.getElementById(estadoId).value = data.uf || '';
                } else {
                    alert('CEP não encontrado.');
                    if (document.getElementById(enderecoId)) document.getElementById(enderecoId).value = '';
                    if (document.getElementById(bairroId)) document.getElementById(bairroId).value = '';
                    if (document.getElementById(cidadeId)) document.getElementById(cidadeId).value = '';
                    if (document.getElementById(estadoId)) document.getElementById(estadoId).value = '';
                }
            })
            .catch(error => {
                console.error('Erro ao buscar CEP:', error);
                alert('Ocorreu um erro ao buscar o CEP. Tente novamente.');
            });
    };

    // --- Funções de Tabela (Busca e Ordenação Genéricas) ---
    window.applyFiltersAndSort = function(tableId, searchInputId, filterSelectId = null) {
        const table = document.getElementById(tableId);
        const searchInput = document.getElementById(searchInputId);
        const filterSelect = filterSelectId ? document.getElementById(filterSelectId) : null;

        if (!table) {
            console.warn(`DEBUG: Tabela com ID '${tableId}' não encontrada. Pulando applyFiltersAndSort.`);
            return;
        }

        let sortDirection = {};
        let currentSortColumn = null;
        let currentSortDirection = 'asc';

        function performFilteringAndSorting() {
            const tbody = table.querySelector('tbody');
            let originalRows = Array.from(tbody.querySelectorAll('tr'));
            
            console.log(`DEBUG ${tableId}: Contagem inicial de originalRows:`, originalRows.length);
            if (originalRows.length > 0) {
                console.log(`DEBUG ${tableId}: Atributos da primeira linha:`, originalRows[0].dataset);
            }

            let filteredRows = [...originalRows];

            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            if (searchTerm) {
                console.log(`DEBUG ${tableId}: Aplicando busca por termo: "${searchTerm}"`);
                filteredRows = filteredRows.filter(row => row.textContent.toLowerCase().includes(searchTerm));
            }

            const filterValue = filterSelect ? filterSelect.value : '';
            if (filterValue) {
                console.log(`DEBUG ${tableId}: Aplicando filtro de seleção: ${filterSelectId} = "${filterValue}"`);
                filteredRows = filteredRows.filter(row => {
                    if (tableId === 'corretoresTable') {
                        const corretorAprovadoStatus = row.dataset.corretorAprovado || '';
                        const corretorAtivoStatus = row.dataset.corretorAtivo || '';

                        if (filterValue === 'pendente') {
                            return corretorAprovadoStatus === 'pendente';
                        } else if (filterValue === 'aprovado') {
                            return corretorAprovadoStatus === 'aprovado' && corretorAtivoStatus === 'ativo';
                        } else if (filterValue === 'inativo') {
                            return corretorAtivoStatus === 'inativo';
                        }
                        return true; 
                    } else {
                        const rowStatus = row.dataset.reservaStatus || row.dataset.userStatus || row.dataset.documentStatus || row.dataset.imobiliariaAtiva || row.dataset.leadStatus || '';
                        return rowStatus === filterValue;
                    }
                });
                console.log(`DEBUG ${tableId}: Contagem de linhas após filtro:`, filteredRows.length);
            }

            if (currentSortColumn) {
                const headers = table.querySelectorAll('th[data-sort-by]');
                let columnIndex = -1;
                headers.forEach((header, idx) => {
                    if (header.dataset.sortBy === currentSortColumn) {
                        columnIndex = idx;
                    }
                });

                if (columnIndex !== -1) {
                    filteredRows.sort((a, b) => {
                        let aValue = a.children[columnIndex].textContent.trim();
                        let bValue = b.children[columnIndex].textContent.trim();

                        if (currentSortColumn.includes('valor') || currentSortColumn.includes('id') || currentSortColumn.includes('numero')) {
                            aValue = parseFloat(aValue.replace(/[^0-9,-]+/g, '').replace(',', '.'));
                            bValue = parseFloat(bValue.replace(/[^0-9,-]+/g, '').replace(',', '.'));
                        } else if (currentSortColumn.includes('data')) {
                            const parseDateBR = (dateStr) => {
                                if (dateStr === 'N/A' || !dateStr) return new Date(0);
                                const parts = dateStr.split(' ');
                                const dateParts = parts[0].split('/');
                                const timeParts = parts[1] ? parts[1].split(':') : ['00', '00', '00'];
                                return new Date(`${dateParts[2]}-${dateParts[1]}-${dateParts[0]}T${timeParts[0]}:${timeParts[1]}:${timeParts[2]}`);
                            };
                            aValue = parseDateBR(aValue);
                            bValue = parseDateBR(bValue);
                        }
                        
                        if (aValue < bValue) return currentSortDirection === 'asc' ? -1 : 1;
                        if (aValue > bValue) return currentSortDirection === 'asc' ? 1 : -1;
                        return 0;
                    });
                }
            }

            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            if (filteredRows.length === 0) {
                 const noResultsRow = document.createElement('tr');
                 noResultsRow.innerHTML = `<td colspan="${table.querySelectorAll('th').length}" style="text-align: center;">Nenhum item encontrado com os filtros aplicados.</td>`;
                 tbody.appendChild(noResultsRow);
            } else {
                 filteredRows.forEach(row => tbody.appendChild(row));
            }
        }

        if (searchInput) searchInput.addEventListener('keyup', performFilteringAndSorting);
        if (filterSelect) filterSelect.addEventListener('change', performFilteringAndSorting);
        
        const applyCorretorFiltersBtn = document.getElementById('applyCorretorFiltersBtn');
        if (applyCorretorFiltersBtn && tableId === 'corretoresTable') {
            applyCorretorFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault(); 
                performFilteringAndSorting();
            });
        }

        table.querySelectorAll('th[data-sort-by]').forEach(header => {
            const columnKey = header.dataset.sortBy;
            sortDirection[columnKey] = 'asc';

            header.addEventListener('click', function() {
                const isAsc = sortDirection[columnKey] === 'asc';
                currentSortDirection = isAsc ? 'desc' : 'asc';
                currentSortColumn = columnKey;
                sortDirection[columnKey] = currentSortDirection;

                table.querySelectorAll('th .fas.fa-sort, th .fas.fa-sort-up, th .fas.fa-sort-down').forEach(icon => {
                    icon.classList.remove('fa-sort-up', 'fa-sort-down');
                    icon.classList.add('fa-sort');
                });
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(currentSortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                }
                performFilteringAndSorting();
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        const initialSearchTerm = urlParams.get('search');
        const initialFilterStatus = urlParams.get('status');
        const initialSortBy = urlParams.get('sort_by');
        const initialSortOrder = urlParams.get('sort_order');

        if (searchInput && initialSearchTerm) searchInput.value = initialSearchTerm;
        if (filterSelect && initialFilterStatus) filterSelect.value = initialFilterStatus;
        
        if (initialSortBy && initialSortOrder) {
            currentSortColumn = initialSortBy;
            currentSortDirection = initialSortOrder;
            const initialHeader = table.querySelector(`th[data-sort-by="${initialSortBy}"]`);
            if (initialHeader) {
                const icon = initialHeader.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(initialSortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                }
            }
        } else {
            const defaultSortHeader = table.querySelector('th[data-sort-by="data_cadastro"]');
            if (defaultSortHeader) {
                currentSortColumn = defaultSortHeader.dataset.sortBy;
                currentSortDirection = 'desc';
                const icon = defaultSortHeader.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add('fa-sort-down');
                }
            }
        }

        performFilteringAndSorting(); 
    };

    // --- Função Global para Marcar Alerta como Lido ---
    window.markAlertAsRead = function(alertId) {
        fetch(`${BASE_URL_JS}api/alert_actions.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=mark_read&alert_id=${alertId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload(); 
            } else {
                alert('Erro ao marcar alerta como lido: ' + data.message);
            }
        })
        .catch(error => { console.error('Erro AJAX:', error); alert('Erro de rede ao marcar alerta.'); });
    };

    // --- Função Global para Atualizar Status de Corretor (IMOBILIARIA) ---
    window.updateRealtorStatus = function(realtorId, action) {
        fetch(BASE_URL_JS + 'imobiliaria/processa_corretor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `realtor_id=${realtorId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            window.showConfirmationModal('Resultado da Ação', data.message, 'OK', data.success ? 'btn-primary' : 'btn-danger', () => {
                if (data.success) { window.location.reload(); }
            });
        })
        .catch(error => {
            window.showConfirmationModal('Erro de Rede', 'Ocorreu um erro ao processar a solicitação.', 'Fechar', 'btn-danger', () => {
                console.error('Erro AJAX:', error);
            });
        });
    };

    // --- Função Global para Finalizar Venda (ADMIN) ---
    window.finalizeSaleAction = function(reservaId) {
        window.showConfirmationModal(
            'Confirmar Finalização de Venda',
            `Você tem certeza que deseja FINALIZAR a venda da reserva <strong>${reservaId}</strong>? Esta ação é irreversível e a unidade será marcada como 'Vendida'.`,
            'Finalizar Venda',
            'btn-success',
            (confirmed) => {
                if (confirmed) {
                    const formData = new FormData();
                    formData.append('action', 'finalize_sale');
                    formData.append('reserva_id', reservaId);
                    formData.append('is_ajax', 'true');

                    fetch(BASE_URL_JS + 'api/reserva.php', {
                        method: 'POST',
                        body: formData,
                        headers: {'X-Requested-With': 'XMLHttpRequest'}
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Erro na requisição AJAX:', error);
                        alert('Ocorreu um erro ao finalizar a venda. Tente novamente.');
                    });
                }
            }
        );
    };

    // --- Função Genérica para Submissão de Formulário via AJAX ---
    window.handleFormSubmission = function(formId, modalElement) {
        const form = document.getElementById(formId);
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('is_ajax', 'true');
                
                const targetUrl = form.getAttribute('action'); 

                if (!targetUrl || typeof targetUrl !== 'string') {
                    console.error(`Erro: Atributo 'action' do formulário (ID: ${formId}) está vazio, nulo ou não é uma string válida:`, targetUrl);
                    alert('Erro interno: URL de envio do formulário inválida.');
                    return;
                }

                fetch(targetUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.error(`Resposta não-JSON do servidor para ${targetUrl}:`, text);
                            throw new Error("Resposta inesperada do servidor (não JSON). Verifique os logs do PHP.");
                        });
                    }
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.closeModal(modalElement);
                        window.location.reload();
                    } else {
                        alert('Erro: ' + (data.message || 'Ocorreu um erro desconhecido.'));
                    }
                })
                .catch(error => {
                    console.error(`Erro na requisição AJAX para ${targetUrl}:`, error);
                    alert('Ocorreu um erro ao processar sua solicitação. Verifique o console para detalhes.');
                });
            });
        }
    };

    // ===============================================
    // 2. INICIALIZAÇÕES COMUNS ÀS PÁGINAS DO DASHBOARD
    // ===============================================

    const mainSidebar = document.getElementById('mainSidebar');
    const mainSidebarOverlay = document.getElementById('mainSidebarOverlay');
    const menuToggleButtonsCommon = document.querySelectorAll('.menu-toggle');

    if (mainSidebar && mainSidebarOverlay && menuToggleButtonsCommon.length > 0) {
        menuToggleButtonsCommon.forEach(button => {
            button.addEventListener('click', function() {
                mainSidebar.classList.toggle('active');
                mainSidebarOverlay.classList.toggle('active');
            });
        });
        mainSidebarOverlay.addEventListener('click', function() {
            mainSidebar.classList.remove('active');
            mainSidebarOverlay.classList.remove('active');
        });
    }

    document.querySelectorAll('.cpf-mask').forEach(input => window.applyCpfMask(input));
    document.querySelectorAll('.whatsapp-mask').forEach(input => window.applyWhatsappMask(input));
    document.querySelectorAll('.cep-mask').forEach(input => window.applyMask(input, '#####-###'));

    document.querySelectorAll('.password-input-wrapper').forEach(wrapper => {
        const passwordInput = wrapper.querySelector('input[type="password"], input[type="text"]');
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

    // --- LÓGICA DE GESTÃO DE ALERTAS ---
    const alertsTable = document.getElementById('alertsTable');
    if (alertsTable) {
        const alertSearchInput = document.getElementById('alertSearch');
        const markAllAsReadBtn = document.getElementById('markAllAsReadBtn');

        alertsTable.addEventListener('click', async (e) => {
            const button = e.target.closest('.mark-read-btn');
            if (button) {
                const alertId = button.dataset.alertId;
                window.markAlertAsRead(alertId);
            }
        });

        if (markAllAsReadBtn) {
            markAllAsReadBtn.addEventListener('click', async () => {
                window.showConfirmationModal(
                    'Marcar Todos os Alertas como Lidos?',
                    'Você tem certeza que deseja marcar TODOS os seus alertas não lidos como lidos? Esta ação não pode ser desfeita.',
                    'Marcar Todos como Lidos',
                    'btn-warning',
                    (confirmed) => {
                        if (confirmed) {
                            window.markAllAlertsAsRead();
                        }
                    }
                );
            });
        }

        if (alertSearchInput) {
            alertSearchInput.addEventListener('keyup', () => {
                window.applyFiltersAndSort('alertsTable', 'alertSearch'); 
            });
        }
        
        window.applyFiltersAndSort('alertsTable', 'alertSearch');
    }

    // --- LÓGICA DE GESTÃO DE CORRETORES ---
    const corretoresTable = document.getElementById('corretoresTable');
    const isImobiliariaCorretorCreatePage = window.location.pathname.includes('imobiliaria/corretores/criar.php');

    if (corretoresTable || isImobiliariaCorretorCreatePage) {
        document.addEventListener('click', function(event) {
            const target = event.target;
            const clickedButton = target.closest('.approve-realtor, .reject-realtor, .activate-realtor, .deactivate-realtor');
            
            if (!clickedButton) return;

            const realtorId = clickedButton.dataset.id;
            const realtorName = clickedButton.dataset.nome;

            if (!realtorId || !realtorName) {
                console.error("ID ou nome do corretor não encontrado no data-attribute do botão.");
                return;
            }

            let actionType = '';
            let confirmMessageTitle = '';
            let confirmMessageBody = '';
            let confirmButtonText = '';
            let confirmButtonClass = '';
            let targetModalId = '';

            if (clickedButton.classList.contains('approve-realtor')) {
                actionType = 'aprovar';
                confirmMessageTitle = 'Aprovar Corretor';
                confirmMessageBody = `Você tem certeza que deseja aprovar o corretor <strong>${realtorName}</strong>?`;
                confirmButtonText = 'Confirmar Aprovação'; confirmButtonClass = 'btn-success';
                targetModalId = 'approveCorretorModal';
            } else if (clickedButton.classList.contains('reject-realtor')) {
                actionType = 'rejeitar';
                confirmMessageTitle = 'Rejeitar Corretor';
                confirmMessageBody = `Você tem certeza que deseja rejeitar o corretor <strong>${realtorName}</strong>? Esta ação é irreversível.`;
                confirmButtonText = 'Confirmar Rejeição'; confirmButtonClass = 'btn-danger';
                targetModalId = 'rejectCorretorModal';
            } else if (clickedButton.classList.contains('activate-realtor')) {
                actionType = 'ativar';
                confirmMessageTitle = 'Ativar Corretor';
                confirmMessageBody = `Você tem certeza que deseja ativar o corretor <strong>${realtorName}</strong>? Ele terá acesso novamente.`;
                confirmButtonText = 'Confirmar Ativação'; confirmButtonClass = 'btn-success';
                targetModalId = 'activateCorretorModal';
            } else if (clickedButton.classList.contains('deactivate-realtor')) {
                actionType = 'inativar';
                confirmMessageTitle = 'Inativar Corretor';
                confirmMessageBody = `Você tem certeza que deseja inativar o corretor <strong>${realtorName}</strong>? Ele perderá o acesso ao sistema.`;
                confirmButtonText = 'Confirmar Inativação'; confirmButtonClass = 'btn-warning';
                targetModalId = 'deactivateCorretorModal';
            } else {
                return;
            }

            window.showConfirmationModal(
                confirmMessageTitle,
                confirmMessageBody,
                confirmButtonText,
                confirmButtonClass,
                (confirmed) => {
                    if (confirmed) {
                        const modalForm = document.getElementById(targetModalId)?.querySelector('form');
                        if (modalForm) {
                            modalForm.querySelector('input[name="id"]').value = realtorId;
                            modalForm.querySelector('input[name="action"]').value = actionType;
                            
                            modalForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));

                            window.closeModal(document.getElementById('genericConfirmationModal'));
                        } else {
                            console.error("Formulário do modal não encontrado:", targetModalId);
                        }
                    }
                }
            );
        });

        if (corretoresTable) {
            window.applyFiltersAndSort('corretoresTable', 'corretorSearch', 'corretorFilterStatus');
            const corretorFilterStatusSelect = document.getElementById('corretorFilterStatus');
            if (corretorFilterStatusSelect) {
                corretorFilterStatusSelect.addEventListener('change', () => window.applyFiltersAndSort('corretoresTable', 'corretorSearch', 'corretorFilterStatus'));
            }
        } else if (isImobiliariaCorretorCreatePage) {
            const tipoRadioButtons = document.querySelectorAll('input[name="tipo"]');
            const imobiliariaInfoGroup = document.getElementById('imobiliaria_info_group');
            const cpfInput = document.getElementById('cpf');
            const telefoneInput = document.getElementById('telefone');

            function toggleImobiliariaFields() {
                const selectedType = document.querySelector('input[name="tipo"]:checked')?.value;
                if (imobiliariaInfoGroup) {
                    imobiliariaInfoGroup.style.display = (selectedType === 'corretor_imobiliaria') ? 'block' : 'none';
                }
            }
            tipoRadioButtons.forEach(radio => radio.addEventListener('change', toggleImobiliariaFields));
            toggleImobiliariaFields();

            window.applyCpfMask(cpfInput);
            window.applyWhatsappMask(telefoneInput);
        }

        window.handleFormSubmission('approveCorretorForm', document.getElementById('approveCorretorModal'));
        window.handleFormSubmission('rejectCorretorForm', document.getElementById('rejectCorretorModal'));
        window.handleFormSubmission('activateCorretorForm', document.getElementById('activateCorretorModal'));
        window.handleFormSubmission('deactivateCorretorForm', document.getElementById('deactivateCorretorModal'));

    }


    // --- LÓGICA DE GESTÃO DE LEADS (admin/leads/index.php) ---
    const leadsTable = document.getElementById('leadsTable');
    if (leadsTable) {
        const assignCorretorModal = document.getElementById('assignCorretorModal');
        const assignReservaIdInput = document.getElementById('assignReservaId');
        const leadClienteNomeSpan = document.getElementById('leadClienteNome');
        const leadEmpreendimentoNomeSpan = document.getElementById('leadEmpreendimentoNome');
        const leadUnidadeNumeroSpan = document.getElementById('leadUnidadeNumero');
        const assignCorretorForm = document.getElementById('assignCorretorForm');

        window.applyFiltersAndSort('leadsTable', 'leadSearch', 'leadFilterStatus');

        const applyLeadsFiltersBtn = document.getElementById('applyLeadsFiltersBtn');
        if (applyLeadsFiltersBtn) {
            applyLeadsFiltersBtn.addEventListener('click', function() {
                const leadsFiltersForm = document.getElementById('leadsFiltersForm');
                if (leadsFiltersForm) leadsFiltersForm.submit();
            });
        }

        document.addEventListener('click', (e) => {
            const target = e.target;
            const clickedButton = target.closest('.assign-lead-btn, .take-lead-admin-btn, .dispense-lead-btn');
            
            if (!clickedButton) return;

            const leadId = clickedButton.dataset.leadId;
            const clienteNome = clickedButton.dataset.leadNomeCliente;
            const empreendimentoNome = clickedButton.dataset.leadEmpreendimento;
            const unidadeNumero = clickedButton.dataset.leadUnidade;

            let confirmMessageTitle = '';
            let confirmMessageBody = '';
            let confirmButtonText = '';
            let confirmButtonClass = '';
            let apiAction = '';

            if (clickedButton.classList.contains('assign-lead-btn')) {
                if (assignCorretorModal && assignReservaIdInput && leadClienteNomeSpan && leadEmpreendimentoNomeSpan && leadUnidadeNumeroSpan) {
                    assignReservaIdInput.value = leadId;
                    leadClienteNomeSpan.textContent = clienteNome;
                    leadEmpreendimentoNomeSpan.textContent = empreendimentoNome;
                    leadUnidadeNumeroSpan.textContent = unidadeNumero;
                    window.openModal(assignCorretorModal);
                }
            } else if (clickedButton.classList.contains('take-lead-admin-btn')) {
                confirmMessageTitle = 'Atender Lead como Admin';
                confirmMessageBody = `Você tem certeza que deseja assumir o atendimento do lead para o cliente <strong>${clienteNome}</strong> (ID: ${leadId})? Esta ação irá atribuir o lead a você.`;
                confirmButtonText = 'Sim, Atender Lead'; confirmButtonClass = 'btn-success';
                apiAction = 'take_lead_admin';
                
                window.showConfirmationModal(
                    confirmMessageTitle,
                    confirmMessageBody,
                    confirmButtonText,
                    confirmButtonClass,
                    (confirmed) => {
                        if (confirmed) {
                            const formData = new FormData();
                            formData.append('action', apiAction);
                            formData.append('reserva_id', leadId);
                            formData.append('is_ajax', 'true');

                            fetch(BASE_URL_JS + 'api/reserva.php', {
                                method: 'POST',
                                body: formData,
                                headers: {'X-Requested-With': 'XMLHttpRequest'}
                            })
                            .then(response => response.json())
                            .then(data => {
                                alert(data.message);
                                if (data.success) { window.location.reload(); }
                            })
                            .catch(error => { console.error('Erro AJAX:', error); alert('Erro ao atender lead.'); });
                        }
                    }
                );
            } else if (clickedButton.classList.contains('dispense-lead-btn')) {
                confirmMessageTitle = 'Dispensar Lead';
                confirmMessageBody = `Você tem certeza que deseja dispensar o lead para o cliente <strong>${clienteNome}</strong> (ID: ${leadId})? Esta ação irá marcá-lo como cancelado/dispensado.`;
                confirmButtonText = 'Sim, Dispensar Lead'; confirmButtonClass = 'btn-warning';
                apiAction = 'dispensar_lead';
                
                window.showConfirmationModal(
                    confirmMessageTitle,
                    confirmMessageBody,
                    confirmButtonText,
                    confirmButtonClass,
                    (confirmed) => {
                        if (confirmed) {
                            const formData = new FormData();
                            formData.append('action', apiAction);
                            formData.append('reserva_id', leadId);
                            formData.append('is_ajax', 'true');

                            fetch(BASE_URL_JS + 'api/reserva.php', {
                                method: 'POST',
                                body: formData,
                                headers: {'X-Requested-With': 'XMLHttpRequest'}
                            })
                            .then(response => response.json())
                            .then(data => {
                                alert(data.message);
                                if (data.success) { window.location.reload(); }
                            })
                            .catch(error => { console.error('Erro AJAX:', error); alert('Erro ao dispensar lead.'); });
                        }
                    }
                );
            }
        });

        if (assignCorretorForm) {
            window.handleFormSubmission('assignCorretorForm', assignCorretorModal);
        }
    }


    // --- LÓGICA DE GESTÃO DE VENDAS (admin/vendas/index.php) ---
    const vendasTable = document.getElementById('vendasTable');
    if (vendasTable) {
        window.applyFiltersAndSort('vendasTable', 'vendasSearch'); 
        
        const applyVendasFiltersBtn = document.getElementById('applyVendasFiltersBtn');
        if (applyVendasFiltersBtn) {
            applyVendasFiltersBtn.addEventListener('click', function() {
                const vendasFiltersForm = document.getElementById('vendasFiltersForm');
                if (vendasFiltersForm) vendasFiltersForm.submit();
            });
        }

        document.querySelectorAll('.export-report-btn[data-report-type="vendas"]').forEach(button => {
            button.addEventListener('click', function() {
                const form = document.getElementById('vendasFiltersForm');
                const formData = new FormData(form);
                let queryString = new URLSearchParams(formData).toString();
                queryString += '&export=csv'; 
                window.location.href = `${BASE_URL_JS}admin/vendas/index.php?${queryString}`;
            });
        });

        document.querySelectorAll('.print-venda-btn').forEach(button => {
            button.addEventListener('click', function() {
                const vendaId = this.dataset.vendaId;
                const printWindow = window.open(`${BASE_URL_JS}admin/reservas/detalhes.php?id=${vendaId}`, '_blank');
                printWindow.onload = function() {
                    setTimeout(() => {
                        if (printWindow) printWindow.print();
                    }, 500); 
                };
            });
        });
    }


    // --- LÓGICA DE GESTÃO DE DOCUMENTOS (admin/documentos/index.php) ---
    const documentosTable = document.getElementById('documentosTable');
    if (documentosTable) {
        window.applyFiltersAndSort('documentosTable', 'documentosSearch', 'statusFilter');

        const applyDocumentosFiltersBtn = document.getElementById('applyDocumentosFiltersBtn');
        if (applyDocumentosFiltersBtn) {
            applyDocumentosFiltersBtn.addEventListener('click', function() {
                const documentosFiltersForm = document.getElementById('documentosFiltersForm');
                if (documentosFiltersForm) documentosFiltersForm.submit();
            });
        }

        const analisarDocumentoModal = document.getElementById('analisarDocumentoModal');
        const modalDocumentoIdInput = document.getElementById('modalDocumentoId');
        const modalDocumentoReservaIdInput = document.getElementById('modalDocumentoReservaId');
        const modalDocumentoNomeSpan = document.getElementById('modalDocumentoNome');
        const modalDocumentoTipoSpan = document.getElementById('modalDocumentoTipo');
        const modalStatusSelect = document.getElementById('modalStatus');
        const motivoRejeicaoGroup = document.getElementById('motivoRejeicaoGroup');
        const modalMotivoRejeicaoTextarea = document.getElementById('modalMotivoRejeicao');
        const formAnalisarDocumento = document.getElementById('formAnalisarDocumento');
        const actionInput = formAnalisarDocumento?.querySelector('input[name="action"]');


        document.addEventListener('click', (e) => {
            const button = e.target.closest('.btn-analisar-documento, .approve-document-btn, .reject-document-btn'); // Adicionado .approve-document-btn, .reject-document-btn
            if (!button) return;

            const docId = button.dataset.documentId; // Corrigido para documentId
            const docNome = button.dataset.nome;
            const docTipo = button.dataset.tipo; // Se tiver esse dado
            const docStatusAtual = button.dataset.documentStatus; // Corrigido para documentStatus
            const docMotivoRejeicao = button.dataset.motivoRejeicao;
            const docReservaId = button.dataset.reservaId;

            if (modalDocumentoIdInput) modalDocumentoIdInput.value = docId;
            if (modalDocumentoReservaIdInput) modalDocumentoReservaIdInput.value = docReservaId;
            if (modalDocumentoNomeSpan) modalDocumentoNomeSpan.textContent = docNome;
            if (modalDocumentoTipoSpan) modalDocumentoTipoSpan.textContent = docTipo || 'Não especificado'; // Garante valor padrão
            
            // Define o status no select do modal
            if (modalStatusSelect) {
                // Se o status atual é 'rejeitado', pré-seleciona 'rejeitado' para edição. Caso contrário, pré-seleciona 'aprovado'.
                modalStatusSelect.value = (docStatusAtual === 'rejeitado') ? 'rejeitado' : 'aprovado';
                modalStatusSelect.dispatchEvent(new Event('change')); // Dispara o evento para atualizar a visibilidade do motivo da rejeição
            }
            if (modalMotivoRejeicaoTextarea) {
                modalMotivoRejeicaoTextarea.value = docMotivoRejeicao || ''; // Limpa se não houver motivo
            }

            window.openModal(analisarDocumentoModal);
        });

        if (modalStatusSelect && motivoRejeicaoGroup && modalMotivoRejeicaoTextarea && actionInput) {
            modalStatusSelect.addEventListener('change', function() {
                if (this.value === 'rejeitado') {
                    motivoRejeicaoGroup.style.display = 'block';
                    modalMotivoRejeicaoTextarea.setAttribute('required', 'required');
                    actionInput.value = 'reject_document';
                } else {
                    motivoRejeicaoGroup.style.display = 'none';
                    modalMotivoRejeicaoTextarea.removeAttribute('required');
                    actionInput.value = 'approve_document';
                }
            });
            modalStatusSelect.dispatchEvent(new Event('change'));
        }

        if (formAnalisarDocumento) {
            window.handleFormSubmission('formAnalisarDocumento', analisarDocumentoModal);
        }
    }

    // --- LÓGICA DA PÁGINA DE DETALHES DA RESERVA ---
    if (window.location.pathname.includes('admin/reservas/detalhes.php') || window.location.pathname.includes('imobiliaria/reservas/detalhes.php') || window.location.pathname.includes('corretor/reservas/detalhes.php')) {
        const reservaIdFromUrl = new URLSearchParams(window.location.search).get('id');
        if (!reservaIdFromUrl) {
            console.error("ID da reserva não encontrado na URL da página de detalhes.");
            return;
        }

        const approveDocumentModal = document.getElementById('approveDocumentModal');
        const rejectDocumentModal = document.getElementById('rejectDocumentModal');
        const cancelReservaModal = document.getElementById('cancelReservaModal');
        const editClientsModal = document.getElementById('editClientsModal');

        const approveDocumentIdInput = document.getElementById('approveDocumentIdInput'); // Corrigido ID
        const approveDocumentReservaIdInput = document.getElementById('approveDocumentReservaIdInput'); // Corrigido ID
        const rejectDocumentIdInput = document.getElementById('rejectDocumentIdInput'); // Corrigido ID
        const rejectDocumentReservaIdInput = document.getElementById('rejectDocumentReservaIdInput'); // Corrigido ID
        const rejectionReasonTextarea = document.getElementById('rejectionReason'); // Corrigido ID para o modal principal
        const cancelReservaIdInput = document.getElementById('cancelReservaId');
        
        const editClientsBtn = document.getElementById('editClientsBtn');
        const editClientsReservaIdInput = document.getElementById('editClientsReservaId');
        const clientsContainer = document.getElementById('clientsContainer');
        const addClientBtn = document.getElementById('addClientBtn');

        // Mapear forms dos modais para handleFormSubmission
        const formsToHandle = [
            { id: 'approveDocumentForm', modal: approveDocumentModal },
            { id: 'rejectDocumentForm', modal: rejectDocumentModal },
            { id: 'cancelReservaForm', modal: cancelReservaModal },
            { id: 'editClientsForm', modal: editClientsModal }
        ];

        formsToHandle.forEach(item => {
            const form = document.getElementById(item.id);
            if (form) {
                window.handleFormSubmission(item.id, item.modal);
            }
        });


        document.addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            let confirmMessageTitle = '';
            let confirmMessageBody = '';
            let confirmButtonText = '';
            let confirmButtonClass = '';
            let targetModal = null;

            if (button.classList.contains('approve-doc-btn')) {
                confirmMessageTitle = 'Aprovar Documento';
                confirmMessageBody = `Você tem certeza que deseja aprovar este documento (ID: ${button.dataset.docId})?`;
                confirmButtonText = 'Confirmar Aprovação'; confirmButtonClass = 'btn-success';
                targetModal = approveDocumentModal;
                if (approveDocumentIdInput) approveDocumentIdInput.value = button.dataset.docId;
                if (approveDocumentReservaIdInput) approveDocumentReservaIdInput.value = reservaIdFromUrl;
                // Atualiza o display no modal
                const approveDocIdDisplay = approveDocumentModal.querySelector('#approveDocumentIdDisplay');
                const approveReservaIdDisplay = approveDocumentModal.querySelector('#approveDocumentReservaIdDisplay');
                if (approveDocIdDisplay) approveDocIdDisplay.textContent = button.dataset.docId;
                if (approveReservaIdDisplay) approveReservaIdDisplay.textContent = reservaIdFromUrl;

            } else if (button.classList.contains('reject-doc-btn')) {
                confirmMessageTitle = 'Rejeitar Documento';
                confirmMessageBody = `Você tem certeza que deseja rejeitar este documento (ID: ${button.dataset.docId})?<br>Por favor, insira o motivo da rejeição no campo abaixo.`;
                confirmButtonText = 'Confirmar Rejeição'; confirmButtonClass = 'btn-danger';
                targetModal = rejectDocumentModal;
                if (rejectDocumentIdInput) rejectDocumentIdInput.value = button.dataset.docId;
                if (rejectDocumentReservaIdInput) rejectDocumentReservaIdInput.value = reservaIdFromUrl;
                if (rejectionReasonTextarea) rejectionReasonTextarea.value = button.dataset.motivoRejeicao || ''; // Preenche se houver motivo pré-existente
                 // Atualiza o display no modal
                const rejectDocIdDisplay = rejectDocumentModal.querySelector('#rejectDocumentIdDisplay');
                const rejectReservaIdDisplay = rejectDocumentModal.querySelector('#rejectDocumentReservaIdDisplay');
                if (rejectDocIdDisplay) rejectDocIdDisplay.textContent = button.dataset.docId;
                if (rejectReservaIdDisplay) rejectReservaIdDisplay.textContent = reservaIdFromUrl;
            } 
            else if (button.classList.contains('cancel-reserva-btn')) {
                confirmMessageTitle = 'Cancelar Reserva';
                confirmMessageBody = `Você tem certeza que deseja **cancelar** esta reserva (ID: ${reservaIdFromUrl})? Esta ação não pode ser desfeita e a unidade voltará a ficar disponível.`;
                confirmButtonText = 'Confirmar Cancelamento'; confirmButtonClass = 'btn-danger';
                targetModal = cancelReservaModal;
                if (cancelReservaIdInput) cancelReservaIdInput.value = reservaIdFromUrl;
            }
            else if (button.classList.contains('finalize-sale-btn')) {
                window.finalizeSaleAction(reservaIdFromUrl);
                return;
            }
            else if (button.classList.contains('simulate-sign-contract-btn')) {
                const simulateSignReservaIdInput = document.getElementById('simulateSignReservaIdInput');
                if (simulateSignReservaIdInput) simulateSignReservaIdInput.value = reservaIdFromUrl;
                window.openModal(document.getElementById('simulateSignContractModal'));
                return;
            }
            else {
                return;
            }

            if (targetModal) {
                 window.openModal(targetModal);
            }
        });
        
        if (editClientsBtn && editClientsModal) {
            editClientsBtn.addEventListener('click', function() {
                if (editClientsReservaIdInput) editClientsReservaIdInput.value = reservaIdFromUrl;
                
                fetch(`${BASE_URL_JS}api/reserva.php?action=get_clients&reserva_id=${reservaIdFromUrl}`, {
                    method: 'GET',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.clients) {
                        if (clientsContainer) clientsContainer.innerHTML = '';
                        let currentClientIdx = 0;
                        if (data.clients.length > 0) {
                            data.clients.forEach(client => {
                                addClientFieldDetails(client, currentClientIdx);
                                currentClientIdx++;
                            });
                        } else {
                            addClientFieldDetails(null, currentClientIdx);
                        }
                        clientIndexDetails = currentClientIdx;
                    } else {
                        alert('Erro ao carregar clientes: ' + (data.message || 'Ocorreu um erro desconhecido.'));
                        if (clientsContainer) clientsContainer.innerHTML = '';
                        clientIndexDetails = 0;
                        addClientFieldDetails(null, 0);
                    }
                    window.openModal(editClientsModal);
                })
                .catch(error => {
                    console.error('Erro ao buscar clientes:', error);
                    alert('Erro ao carregar clientes. Tente novamente.');
                    if (clientsContainer) clientsContainer.innerHTML = '';
                    clientIndexDetails = 0;
                    addClientFieldDetails(null, 0);
                    window.openModal(editClientsModal);
                });
            });
        }

        let clientIndexDetails = 0;

        function addClientFieldDetails(clientData = null, indexToUse = null) {
            const currentIdx = indexToUse !== null ? indexToUse : clientIndexDetails;
            const div = document.createElement('div');
            div.classList.add('client-form-group', 'mb-3', 'p-3', 'border', 'rounded');
            div.innerHTML = `
                <h4>Comprador ${currentIdx + 1}</h4>
                <div class="form-group">
                    <label for="client_name_${currentIdx}">Nome:</label>
                    <input type="text" id="client_name_${currentIdx}" name="clients[${currentIdx}][nome]" class="form-control" value="${clientData ? htmlspecialchars(clientData.nome) : ''}" required>
                    <input type="hidden" name="clients[${currentIdx}][id]" value="${clientData ? htmlspecialchars(clientData.cliente_id) : 'new'}">
                </div>
                <div class="form-group">
                    <label for="client_cpf_${currentIdx}">CPF:</label>
                    <input type="text" id="client_cpf_${currentIdx}" name="clients[${currentIdx}][cpf]" class="form-control cpf-mask" value="${clientData ? htmlspecialchars(clientData.cpf) : ''}" data-mask="###.###.###-##" required>
                </div>
                <div class="form-group">
                    <label for="client_email_${currentIdx}">Email:</label>
                    <input type="email" id="client_email_${currentIdx}" name="clients[${currentIdx}][email]" class="form-control" value="${clientData ? htmlspecialchars(clientData.email) : ''}" required>
                </div>
                <div class="form-group">
                    <label for="client_whatsapp_${currentIdx}">WhatsApp:</label>
                    <input type="text" id="client_whatsapp_${currentIdx}" name="clients[${currentIdx}][whatsapp]" class="form-control whatsapp-mask" value="${clientData ? htmlspecialchars(clientData.whatsapp) : ''}" data-mask="(##) #####-####" required>
                </div>
                <div class="form-group">
                    <label for="client_cep_${currentIdx}">CEP:</label>
                    <input type="text" id="client_cep_${currentIdx}" name="clients[${currentIdx}][cep]" class="form-control cep-mask" value="${clientData ? htmlspecialchars(clientData.cep) : ''}" onblur="window.buscarCEP(this.value, 'client_endereco_${currentIdx}', 'client_bairro_${currentIdx}', 'client_cidade_${currentIdx}', 'client_estado_${currentIdx}')">
                </div>
                <div class="form-group">
                    <label for="client_endereco_${currentIdx}">Endereço:</label>
                    <input type="text" id="client_endereco_${currentIdx}" name="clients[${currentIdx}][endereco]" class="form-control" value="${clientData ? htmlspecialchars(clientData.endereco) : ''}">
                </div>
                <div class="form-group-inline">
                    <div class="form-group">
                        <label for="client_numero_${currentIdx}">Número:</label>
                        <input type="text" id="client_numero_${currentIdx}" name="clients[${currentIdx}][numero]" class="form-control" value="${clientData ? htmlspecialchars(clientData.numero) : ''}">
                    </div>
                    <div class="form-group">
                        <label for="client_complemento_${currentIdx}">Complemento:</label>
                        <input type="text" id="client_complemento_${currentIdx}" name="clients[${currentIdx}][complemento]" class="form-control" value="${clientData ? htmlspecialchars(clientData.complemento) : ''}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="client_bairro_${currentIdx}">Bairro:</label>
                    <input type="text" id="client_bairro_${currentIdx}" name="clients[${currentIdx}][bairro]" class="form-control" value="${clientData ? htmlspecialchars(clientData.bairro) : ''}">
                </div>
                <div class="form-group-inline">
                    <div class="form-group">
                        <label for="client_cidade_${currentIdx}">Cidade:</label>
                        <input type="text" id="client_cidade_${currentIdx}" name="clients[${currentIdx}][cidade]" class="form-control" value="${clientData ? htmlspecialchars(clientData.cidade) : ''}">
                    </div>
                    <div class="form-group">
                        <label for="client_estado_${currentIdx}">Estado:</label>
                        <input type="text" id="client_estado_${currentIdx}" name="clients[${currentIdx}][estado]" class="form-control" value="${clientData ? htmlspecialchars(clientData.estado) : ''}">
                    </div>
                </div>
                ${clientsContainer.children.length > 0 ? '<button type="button" class="btn btn-danger btn-sm remove-client-btn mt-2">Remover Comprador</button>' : ''}
            `;
            clientsContainer?.appendChild(div);

            window.applyCpfMask(div.querySelector(`#client_cpf_${currentIdx}`));
            window.applyWhatsappMask(div.querySelector(`#client_whatsapp_${currentIdx}`));
            window.applyMask(div.querySelector(`#client_cep_${currentIdx}`), '#####-###');
            
            if (clientData) {
                div.querySelector('.remove-client-btn')?.addEventListener('click', function() {
                    if (confirm('Tem certeza que deseja remover este comprador da reserva?')) {
                        div.remove();
                    }
                });
            }

            if (indexToUse === null) {
                clientIndexDetails++;
            }
        }

        if (addClientBtn) addClientBtn.addEventListener('click', () => addClientRow());

        // --- Lógica de Impressão (adicionando para o botão "Imprimir" nos detalhes) ---
        const printReservaBtn = document.getElementById('printReservaBtn');
        if (printReservaBtn) {
            printReservaBtn.addEventListener('click', function() {
                const contentToPrint = document.querySelector('.admin-content-wrapper').innerHTML;
                const printWindow = window.open('', '', 'height=800,width=800');
                printWindow.document.write('<html><head><title>Imprimir Reserva</title>');
                printWindow.document.write('<link rel="stylesheet" href="' + BASE_URL_JS + 'css/style.css">');
                printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">');
                printWindow.document.write('<style>');
                printWindow.document.write('@media print {');
                printWindow.document.write('body { margin: 15mm; }');
                printWindow.document.write('.admin-content-wrapper { width: 100%; float: none; }');
                printWindow.document.write('h2, h3 { color: #333; text-align: center; margin-bottom: 20px; }');
                printWindow.document.write('.details-grid p { display: block; width: 100%; margin-bottom: 5px; }');
                printWindow.document.write('.details-grid strong { display: inline-block; width: 150px; text-align: right; margin-right: 10px; }');
                printWindow.document.write('.admin-table th, .admin-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }');
                printWindow.document.write('.admin-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }');
                printWindow.document.write('.admin-table-actions, .form-actions, .btn { display: none; }');
                printWindow.document.write('.status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; }');
                printWindow.document.write('.status-solicitada { background-color: var(--color-warning-light); color: var(--color-warning-dark); }');
                printWindow.document.write('.status-aprovada { background-color: var(--color-info-light); color: var(--color-info-dark); }');
                printWindow.document.write('.status-documentos_pendentes, .status-documentos_enviados, .status-documentos_rejeitados { background-color: var(--color-warning-light); color: var(--color-warning-dark); }');
                printWindow.document.write('.status-documentos_aprovados { background-color: var(--color-success-light); color: var(--color-success-dark); }');
                printWindow.document.write('.status-contrato_enviado, .status-aguardando_assinatura_eletronica { background-color: var(--color-secondary-light); color: var(--color-secondary-dark); }');
                printWindow.document.write('.status-vendida { background-color: var(--color-success-light); color: var(--color-success-dark); }');
                printWindow.document.write('.status-cancelada, .status-expirada, .status-dispensada { background-color: var(--color-danger-light); color: var(--color-danger-dark); }');
                printWindow.document.write('img { max-width: 100%; height: auto; display: block; margin: 10px auto; }');
                printWindow.document.write('pre { white-space: pre-wrap; word-wrap: break-word; }');
                printWindow.document.write('</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write(contentToPrint);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            });
        }

        function htmlspecialchars(str) {
            if (typeof str !== 'string') return str;
            var map = {
                '&': '&', // Correção aqui: Deve ser &
                '<': '<',
                '>': '>',
                '"': '"',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    }


    const ctx = document.getElementById('salesReservesChart');
    if (ctx) {
        const labels = window.chartLabels ? JSON.parse(window.chartLabels) : [];
        const salesData = window.chartSalesData ? JSON.parse(window.chartSalesData) : [];
        const reservesData = window.chartReservesData ? JSON.parse(window.chartReservesData) : [];

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Vendas',
                    data: salesData,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                }, {
                    label: 'Reservas',
                    data: reservesData,
                    borderColor: 'rgb(255, 159, 64)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Vendas e Reservas (Últimos 7 Dias)' },
                    legend: { display: true }
                },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // --- LÓGICA DA PÁGINA DE RELATÓRIOS (admin/relatorios/index.php) ---
    if (window.location.pathname.includes('admin/relatorios/index.php')) {
        const vendasPeriodoCtx = document.getElementById('vendasPeriodoChart');
        if (vendasPeriodoCtx && window.vendasPeriodoChartLabels && window.vendasPeriodoChartData) {
            new Chart(vendasPeriodoCtx, {
                type: 'line',
                data: {
                    labels: window.vendasPeriodoChartLabels,
                    datasets: [{
                        label: 'Número de Vendas',
                        data: window.vendasPeriodoChartData,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Vendas Concluídas por Dia no Período'
                        },
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        const statusUnidadesCtx = document.getElementById('statusUnidadesChart');
        if (statusUnidadesCtx && window.statusUnidadesChartLabels && window.statusUnidadesChartDataDisponivel && window.statusUnidadesChartDataReservada && window.statusUnidadesChartDataVendida) {
            new Chart(statusUnidadesCtx, {
                type: 'bar',
                data: {
                    labels: window.statusUnidadesChartLabels,
                    datasets: [
                        {
                            label: 'Disponíveis',
                            data: window.statusUnidadesChartDataDisponivel,
                            backgroundColor: 'rgba(40, 167, 69, 0.8)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Reservadas',
                            data: window.statusUnidadesChartDataReservada,
                            backgroundColor: 'rgba(255, 193, 7, 0.8)',
                            borderColor: 'rgba(255, 193, 7, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Vendidas',
                            data: window.statusUnidadesChartDataVendida,
                            backgroundColor: 'rgba(220, 53, 69, 0.8)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribuição de Status das Unidades por Empreendimento'
                        },
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        document.querySelectorAll('.export-report-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reportType = this.dataset.reportType;
                const form = this.closest('form');
                const formData = new FormData(form);
                let queryString = new URLSearchParams(formData).toString();
                
                queryString += '&export=csv'; 

                window.location.href = `${BASE_URL_JS}admin/relatorios/index.php?${queryString}`;
            });
        });
    }

    // --- LÓGICA DE GESTÃO DE CLIENTES (admin/clientes/index.php) ---
    const clientesTable = document.getElementById('clientesTable');
    if (clientesTable) {
        const clienteSearchInput = document.getElementById('clienteSearch');
        const clienteFilterReservaSelect = document.getElementById('clienteFilterReserva');
        const clienteFilterVendaSelect = document.getElementById('clienteFilterVenda');
        const applyClienteFiltersBtn = document.getElementById('applyClienteFiltersBtn');

        window.applyFiltersAndSort('clientesTable', 'clienteSearch');
        
        if (applyClienteFiltersBtn) {
            applyClienteFiltersBtn.addEventListener('click', function(e) {
                this.closest('form')?.submit();
            });
        }

        if (clienteFilterReservaSelect) {
            clienteFilterReservaSelect.addEventListener('change', function() {
                if (applyClienteFiltersBtn) applyClienteFiltersBtn.click();
            });
        }
        if (clienteFilterVendaSelect) {
            clienteFilterVendaSelect.addEventListener('change', function() {
                if (applyClienteFiltersBtn) applyClienteFiltersBtn.click();
            });
        }
        if (clienteSearchInput) {
            clienteSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (applyClienteFiltersBtn) applyClienteFiltersBtn.click();
                }
            });
        }

    }

    // --- LÓGICA DA PÁGINA DE DETALHES DO CLIENTE (admin/clientes/detalhes.php) ---
    if (window.location.pathname.includes('admin/clientes/detalhes.php')) {
        const editClientDetailsForm = document.getElementById('editClientDetailsForm');
        const clientDetailsDisplay = document.getElementById('clientDetailsDisplay');
        const editModeFields = document.querySelector('.edit-mode-fields');

        const enableEditClientBtn = document.getElementById('enableEditClientBtn');
        const saveClientDetailsBtn = document.getElementById('saveClientDetailsBtn');
        const cancelEditClientBtn = document.getElementById('cancelEditClientBtn');

        const clientNomeEditInput = document.getElementById('client_nome_edit');
        const clientCpfEditInput = document.getElementById('client_cpf_edit');
        const clientEmailEditInput = document.getElementById('client_email_edit');
        const clientWhatsappEditInput = document.getElementById('client_whatsapp_edit');
        const clientCepEditInput = document.getElementById('client_cep_edit');
        const clientEnderecoEditInput = document.getElementById('client_endereco_edit');
        const clientNumeroEditInput = document.getElementById('client_numero_edit');
        const clientComplementoEditInput = document.getElementById('client_complemento_edit');
        const clientBairroEditInput = document.getElementById('client_bairro_edit');
        const clientCidadeEditInput = document.getElementById('client_cidade_edit');
        const clientEstadoEditInput = document.getElementById('client_estado_edit');
        
        function applyMasksToEditFields() {
            if (clientCpfEditInput) window.applyCpfMask(clientCpfEditInput);
            if (clientWhatsappEditInput) window.applyWhatsappMask(clientWhatsappEditInput);
            if (clientCepEditInput) window.applyMask(clientCepEditInput, '#####-###');
        }

        function toggleEditMode(enable) {
            if (enable) {
                if (clientDetailsDisplay) clientDetailsDisplay.style.display = 'none';
                if (editModeFields) editModeFields.style.display = 'grid';

                if (enableEditClientBtn) enableEditClientBtn.style.display = 'none';
                if (saveClientDetailsBtn) saveClientDetailsBtn.style.display = 'inline-block';
                if (cancelEditClientBtn) cancelEditClientBtn.style.display = 'inline-block';

                applyMasksToEditFields();
            } else {
                if (clientDetailsDisplay) clientDetailsDisplay.style.display = 'grid';
                if (editModeFields) editModeFields.style.display = 'none';

                if (enableEditClientBtn) enableEditClientBtn.style.display = 'inline-block';
                if (saveClientDetailsBtn) saveClientDetailsBtn.style.display = 'none';
                if (cancelEditClientBtn) cancelEditClientBtn.style.display = 'none';

                window.location.reload(); 
            }
        }

        if (enableEditClientBtn) {
            enableEditClientBtn.addEventListener('click', () => toggleEditMode(true));
        }

        if (cancelEditClientBtn) {
            cancelEditClientBtn.addEventListener('click', () => toggleEditMode(false));
        }

        if (editClientDetailsForm) {
            editClientDetailsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('is_ajax', 'true');

                const targetUrl = this.getAttribute('action');

                fetch(targetUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            console.error("Resposta não-JSON do servidor (admin/clientes/detalhes.php POST):", text);
                            throw new Error("Resposta inesperada do servidor (não JSON). Verifique os logs do PHP.");
                        });
                    }
                })
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        if (clientDetailsDisplay) {
                            document.getElementById('display_nome').textContent = clientNomeEditInput.value;
                            document.getElementById('display_cpf').textContent = clientCpfEditInput.value;
                            document.getElementById('display_email').textContent = clientEmailEditInput.value;
                            document.getElementById('display_whatsapp').textContent = clientWhatsappEditInput.value;
                            document.getElementById('display_cep').textContent = clientCepEditInput.value;
                            document.getElementById('display_endereco').textContent = clientEnderecoEditInput.value;
                            document.getElementById('display_numero').textContent = clientNumeroEditInput.value;
                            document.getElementById('display_complemento').textContent = clientComplementoEditInput.value;
                            document.getElementById('display_bairro').textContent = clientBairroEditInput.value;
                            document.getElementById('display_cidade').textContent = clientCidadeEditInput.value;
                            document.getElementById('display_estado').textContent = clientEstadoEditInput.value;
                            window.location.reload();
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição AJAX (admin/clientes/detalhes.php):', error);
                    alert('Ocorreu um erro ao salvar as alterações. Tente novamente.');
                });
            });
        }
    }


    // --- LÓGICA DE GESTÃO DE CONTRATOS (admin/contratos/index.php) ---
    const contratosTable = document.getElementById('contratosTable');
    if (contratosTable) {
        window.applyFiltersAndSort('contratosTable', 'contratosSearch', 'contratosFilterStatus');

        const applyContratosFiltersBtn = document.getElementById('applyContratosFiltersBtn');
        if (applyContratosFiltersBtn) {
            applyContratosFiltersBtn.addEventListener('click', function() {
                const contratosFiltersForm = document.getElementById('contratosFiltersForm');
                if (contratosFiltersForm) contratosFiltersForm.submit();
            });
        }

        const uploadContractModal = document.getElementById('uploadContractModal');
        const modalContractReservaIdDisplay = document.getElementById('modalContractReservaIdDisplay');
        const modalUploadContractReservaId = document.getElementById('modalUploadContractReservaId');
        const modalUploadContractClienteEmail = document.getElementById('modalUploadContractClienteEmail');
        const modalUploadContractClienteNome = document.getElementById('modalUploadContractClienteNome');
        const modalUploadContractClienteNomeHidden = document.getElementById('modalUploadContractClienteNomeHidden');
        const contractFileField = document.getElementById('contractFile');
        const sendMethodManualRadio = document.getElementById('sendMethodManual');
        const sendMethodClickSignRadio = document.getElementById('sendMethodClickSign');
        const formUploadContract = document.getElementById('formUploadContract');


        document.addEventListener('click', (e) => {
            const button = e.target.closest('.upload-and-send-contract-btn');
            if (!button) return;

            const reservaId = button.dataset.reservaId;
            const clienteEmail = button.dataset.clienteEmail;
            const clienteNome = button.dataset.clienteNome;

            if (modalContractReservaIdDisplay) modalContractReservaIdDisplay.textContent = reservaId;
            if (modalUploadContractReservaId) modalUploadContractReservaId.value = reservaId;
            if (modalUploadContractClienteEmail) modalUploadContractClienteEmail.value = clienteEmail;
            if (modalUploadContractClienteNome) modalUploadContractClienteNome.textContent = clienteNome;
            if (modalUploadContractClienteNomeHidden) modalUploadContractClienteNomeHidden.value = clienteNome;


            if (sendMethodManualRadio) sendMethodManualRadio.checked = true;
            if (contractFileField) {
                contractFileField.value = '';
                contractFileField.required = true;
            }
            if (sendMethodClickSignRadio) {
                sendMethodClickSignRadio.checked = false;
            }
            
            window.openModal(uploadContractModal);
        });

        document.addEventListener('click', (e) => {
            const button = e.target.closest('.mark-contract-sent-btn');
            if (!button) return;

            const reservaId = button.dataset.reservaId;
            
            window.showConfirmationModal(
                'Marcar Contrato como Enviado?',
                `Você tem certeza que deseja marcar o contrato da Reserva <strong>${reservaId}</strong> como 'Enviado' (processo manual)? O sistema não fará upload de arquivo.`,
                'Confirmar',
                'btn-primary',
                (confirmed) => {
                    if (confirmed) {
                        const formData = new FormData();
                        formData.append('action', 'mark_contract_sent');
                        formData.append('reserva_id', reservaId);
                        formData.append('is_ajax', 'true');

                        fetch(BASE_URL_JS + 'api/reserva.php', {
                            method: 'POST',
                            body: formData,
                            headers: {'X-Requested-With': 'XMLHttpRequest'}
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) { window.location.reload(); }
                        })
                        .catch(error => { console.error('Erro AJAX:', error); alert('Erro ao marcar contrato como enviado.'); });
                    }
                }
            );
        });

        document.addEventListener('click', (e) => {
            const button = e.target.closest('.simulate-sign-contract-btn');
            if (!button) return;

            const reservaId = button.dataset.reservaId;
            
            window.showConfirmationModal(
                'Simular Assinatura Finalizada?',
                `Você tem certeza que deseja SIMULAR a assinatura do contrato da Reserva <strong>${reservaId}</strong>? Esta ação moverá a reserva para 'Vendida'.`,
                'Confirmar Simulação',
                'btn-success',
                (confirmed) => {
                    if (confirmed) {
                        const formData = new FormData();
                        formData.append('action', 'simulate_sign_contract');
                        formData.append('reserva_id', reservaId);
                        formData.append('is_ajax', 'true');

                        fetch(BASE_URL_JS + 'api/reserva.php', {
                            method: 'POST',
                            body: formData,
                            headers: {'X-Requested-With': 'XMLHttpRequest'}
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) { window.location.reload(); }
                        })
                        .catch(error => { console.error('Erro AJAX:', error); alert('Erro ao simular assinatura.'); });
                    }
                }
            );
        });

        if (sendMethodManualRadio && sendMethodClickSignRadio && contractFileField) {
            function toggleContractFileVisibility() {
                const actionInput = formUploadContract?.querySelector('input[name="action"]');

                if (sendMethodManualRadio.checked) {
                    contractFileField.style.display = 'block';
                    contractFileField.required = true;
                    if (actionInput) actionInput.value = 'send_contract';
                } else if (sendMethodClickSignRadio.checked) {
                    contractFileField.style.display = 'block';
                    contractFileField.required = false;
                    if (actionInput) actionInput.value = 'send_contract';
                }
            }
            sendMethodManualRadio.addEventListener('change', toggleContractFileVisibility);
            sendMethodClickSignRadio.addEventListener('change', toggleContractFileVisibility);

            toggleContractFileVisibility();
        }

        if (formUploadContract) {
            window.handleFormSubmission('formUploadContract', uploadContractModal);
        }
    }

    // --- LÓGICA DO WIZARD DE CRIAÇÃO DE EMPREENDIMENTOS ---
    if (window.location.pathname.includes('admin/empreendimentos/criar.php') || window.location.pathname.includes('admin/empreendimentos/editar.php')) {
        const numAndaresInput = document.getElementById('num_andares');
        const unidadesPorAndarContainer = document.getElementById('unidades_por_andar_container');
        const tiposUnidadesContainer = document.getElementById('tipos_unidades_container');
        const addTipoUnidadeBtn = document.getElementById('add_tipo_unidade');
        const unitsStockTbody = document.getElementById('units_stock_tbody');
        const generateUnitsBtn = document.getElementById('generate_units_btn');
        const applyBatchValueBtn = document.getElementById('apply_batch_value');
        const batchValueInput = document.getElementById('batch_value');
        const applyBatchTypeBtn = document.getElementById('apply_batch_type');
        const batchTipoUnidadeSelect = document.getElementById('batch_tipo_unidade_id');
        const addVideoUrlBtn = document.getElementById('add_video_url');
        const videosYoutubeContainer = document.getElementById('videos_youtube_container');
        const addParcelaBtn = document.getElementById('add_parcela');
        const paymentFlowTbody = document.getElementById('payment_flow_tbody');
        const unidadeExemploSelect = document.getElementById('unidade_exemplo_id');
        const totalPlanoPagamentoSpan = document.getElementById('total_plano_pagamento');
        const totalPlanoValidationP = document.getElementById('total_plano_validation');
        const paymentFlowBuilderPanel = document.getElementById('payment_flow_builder_panel');
        const momentoEnvioDocumentacaoSelect = document.getElementById('momento_envio_documentacao');
        const documentosObrigatoriosGroup = document.getElementById('documentos_obrigatorios_group');


        let numAndaresData = window.numAndaresData || 0;
        let unidadesPorAndarData = window.unidadesPorDarData || {};
        let tiposUnidadesData = window.tiposUnidadesData || [];
        let unidadesEstoqueData = window.unidadesEstoqueData || [];
        let midiasEtapa5Data = window.midiasEtapa5Data || [];
        let fluxoPagamentoEtapa6Data = window.fluxoPagamentoEtapa6Data || null;

        const wizardSections = document.querySelectorAll('.admin-form .form-section');
        const wizardStepsNav = document.querySelectorAll('.wizard-navigation .wizard-step');
        let currentStepFromUrl = parseInt(new URLSearchParams(window.location.search).get('step')) - 1;


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
                        input.removeAttribute('required');
                        input.setAttribute('disabled', 'disabled');
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
                    ${initialData.foto_planta ? `<small>Arquivo atual: <a href="${BASE_URL_JS}${initialData.foto_planta}" target="_blank">${initialData.foto_planta.split('/').pop()}</a></small><img src="${BASE_URL_JS}${initialData.foto_planta}" alt="Planta atual" style="max-width: 100px; height: auto; display: block; margin-top: 5px; border-radius: var(--border-radius-sm);">` : ''}
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
        function addUnitStockRow(initialData = {}) {
            if (!unitsStockTbody) return;
            const tr = document.createElement('tr');
            tr.classList.add('unit-stock-row');
            let tipoUnidadeOptionsHtml = '<option value="">Selecione um Tipo</option>';
            if (window.tiposUnidadesData && window.tiposUnidadesData.length > 0) {
                window.tiposUnidadesData.forEach((tipo, idx) => {
                    const valueToMatch = initialData.tipo_unidade_id ? initialData.tipo_unidade_id : (typeof initialData.tipo_unidade_idx !== 'undefined' ? initialData.tipo_unidade_idx : null);
                    const selected = (valueToMatch == (tipo.id || idx)) ? 'selected' : '';
                    tipoUnidadeOptionsHtml += `<option value="${tipo.id || idx}" ${selected}>${tipo.tipo} (${tipo.metragem}m²)</option>`;
                });
            }

            tr.innerHTML = `
                <td><input type="number" name="unidades_estoque[andar][]" value="${initialData.andar || ''}" required readonly class="form-control unit-stock-andar-input"></td>
                <td><input type="text" name="unidades_estoque[numero][]" value="${initialData.numero || ''}" required class="form-control unit-stock-numero-input"></td>
                <td><input type="text" name="unidades_estoque[posicao][]" value="${initialData.posicao || ''}" required class="form-control unit-stock-posicao-input"></td>
                <td><select name="unidades_estoque[tipo_unidade_id][]" required class="form-control unit-stock-tipo-select">${tipoUnidadeOptionsHtml}</select></td>
                <td><input type="number" step="0.01" name="unidades_estoque[valor][]" value="${initialData.valor || ''}" required class="form-control unit-stock-valor-input"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-unit-stock-item">Remover</button></td>
            `;
            unitsStockTbody.appendChild(tr);
            tr.querySelector('.remove-unit-stock-item').addEventListener('click', (e) => {
                e.target.closest('.unit-stock-row').remove();
                updateWizardUI(currentStepFromUrl);
            });
            updateWizardUI(currentStepFromUrl);
        }
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
                    <input type="url" name="midias[videos][]" value="${initialUrl}" placeholder="https://www.youtube.com/watch?v=..." class="form-control" required>
                    ${videoEmbedHtml}
                </div>
                <button type="button" class="btn btn-danger btn-sm remove-video-url">Remover Vídeo</button>
            `;
            videosYoutubeContainer.appendChild(div);
            div.querySelector('.remove-video-url').addEventListener('click', (e) => {
                e.target.closest('.video-url-item').remove();
                updateWizardUI(currentStepFromUrl);
            });
            div.querySelector('input[name="midias[videos][]"]').addEventListener('input', (e) => {
                const input = e.target;
                const currentUrl = input.value;
                let existingPreview = input.parentNode.querySelector('.video-preview');
                if (existingPreview) existingPreview.remove();
                let existingMessage = input.parentNode.querySelector('small[style*="orange"]');
                if (existingMessage) existingMessage.remove();

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
        function addParcelaRow(initialData = {}) {
            if (!paymentFlowTbody) return;
            const tr = document.createElement('tr');
            tr.classList.add('payment-flow-item-row');
            tr.innerHTML = `
                <td><input type="text" name="fluxo_pagamento[descricao][]" value="${initialData.descricao || ''}" placeholder="Ex: Sinal, Mensais" class="form-control" required></td>
                <td><input type="number" name="fluxo_pagamento[quantas_vez][]" value="${initialData.quantas_vez || 1}" min="1" class="form-control" required></td>
                <td>
                    <select name="fluxo_pagamento[tipo_valor][]" class="form-control tipo-valor-select" required>
                        <option value="Valor Fixo (R$)" ${initialData.tipo_valor === 'Valor Fixo (R$)' ? 'selected' : ''}>Valor Fixo (R$)</option>
                        <option value="Percentual (%)" ${initialData.tipo_valor === 'Percentual (%)' ? 'selected' : ''}>Percentual (%)</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" name="fluxo_pagamento[valor][]" value="${initialData.valor || ''}" class="form-control valor-parcela-input" required></td>
                <td>
                    <select name="fluxo_pagamento[tipo_calculo][]" class="form-control" required>
                        <option value="Fixo" ${initialData.tipo_calculo === 'Fixo' ? 'selected' : ''}>Fixo</option>
                        <option value="Proporcional" ${initialData.tipo_calculo === 'Proporcional' ? 'selected' : ''}>Proporcional</option>
                    </select>
                </td>
                <td class="total-parcela-display"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-parcela">Remover</button></td>
            `;
            paymentFlowTbody.appendChild(tr);

            const valorInput = tr.querySelector('.valor-parcela-input');
            const quantasVezesInput = tr.querySelector('input[name="fluxo_pagamento[quantas_vez][]"]');
            const tipoValorSelect = tr.querySelector('.tipo-valor-select');

            [valorInput, quantasVezesInput, tipoValorSelect].forEach(input => {
                input.addEventListener('input', updatePaymentFlowSummary);
                input.addEventListener('change', updatePaymentFlowSummary);
            });
            
            tr.querySelector('.remove-parcela').addEventListener('click', (e) => {
                e.target.closest('.payment-flow-item-row').remove();
                updatePaymentFlowSummary();
            });

            updatePaymentFlowSummary();
            updateWizardUI(currentStepFromUrl);
        }
        function updatePaymentFlowSummary() {
            if (!unidadeExemploSelect || !totalPlanoPagamentoSpan || !totalPlanoValidationP || !window.unidadesEstoqueData) return;
            
            const selectedOption = unidadeExemploSelect.options[unidadeExemploSelect.selectedIndex];
            const unitValue = parseFloat(selectedOption?.dataset.unitValue) || 0;

            if (unidadeExemploSelect.value === "" || unitValue === 0) {
                if(paymentFlowBuilderPanel) paymentFlowBuilderPanel.style.display = 'none';
                totalPlanoPagamentoSpan.textContent = 'R$ 0,00';
                totalPlanoValidationP.textContent = 'Selecione uma unidade exemplo com valor válido para construir o plano de pagamento.';
                totalPlanoValidationP.style.color = 'red';
                return;
            }
            if(paymentFlowBuilderPanel) paymentFlowBuilderPanel.style.display = 'block';

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

            const tolerance = 0.02;

            if (allParcelaRows.length === 0) {
                totalPlanoValidationP.textContent = 'Adicione parcelas ao plano de pagamento.';
                totalPlanoValidationP.style.color = 'red';
            } else if (hasPercentualParcel && !hasFixedParcel) {
                const remainingPercent = 100 - totalPercentualSum;
                if (Math.abs(remainingPercent) > tolerance) {
                    totalPlanoValidationP.textContent = `Soma dos percentuais: ${totalPercentualSum.toFixed(2)}%. Faltam ${remainingPercent.toFixed(2)}%.`;
                    totalPlanoValidationP.style.color = 'red';
                } else {
                    totalPlanoValidationP.textContent = 'Plano percentual totalizou 100%. OK!';
                    totalPlanoValidationP.style.color = 'green';
                }
            } else {
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
        
        function toggleDocumentosObrigatorios() {
            if (momentoEnvioDocumentacaoSelect.value === 'Na Proposta de Reserva') { //
                documentosObrigatoriosGroup.style.display = 'block';
            } else {
                documentosObrigatoriosGroup.style.display = 'none';
            }
        }


        if (numAndaresInput) {
            numAndaresInput.addEventListener('change', generateUnidadesPorAndarFields);
            if (window.numAndaresData > 0) {
                numAndaresInput.value = window.numAndaresData;
                generateUnidadesPorAndarFields();
            }
        }
        if (addTipoUnidadeBtn) {
            addTipoUnidadeBtn.addEventListener('click', () => addTipoUnidadeRow());
            if (window.tiposUnidadesData && window.tiposUnidadesData.length > 0) {
                window.tiposUnidadesData.forEach(data => addTipoUnidadeRow(data));
            } else {
                addTipoUnidadeRow();
            }
        }
        if (generateUnitsBtn && unitsStockTbody) {
            if (batchTipoUnidadeSelect && window.tiposUnidadesData && window.tiposUnidadesData.length > 0) {
                let batchTipoOptionsHtml = '<option value="">Selecione um Tipo</option>';
                window.tiposUnidadesData.forEach((tipo, idx) => {
                    batchTipoOptionsHtml += `<option value="${tipo.id || idx}">${tipo.tipo} (${tipo.metragem}m²)</option>`;
                });
                batchTipoUnidadeSelect.innerHTML = batchTipoOptionsHtml;
            }
            if (window.unidadesEstoqueData && window.unidadesEstoqueData.length > 0) {
                window.unidadesEstoqueData.forEach(data => addUnitStockRow(data));
            }
            generateUnitsBtn.addEventListener('click', () => {
                unitsStockTbody.innerHTML = '';
                const numAndares = parseInt(numAndaresInput.value) || 0;
                if (numAndares > 0) {
                    for (let andar = 1; andar <= numAndares; andar++) {
                        const unidadesInput = document.getElementById(`unidades_por_andar_${andar}`);
                        const numUnidades = unidadesInput ? parseInt(unidadesInput.value) || 0 : 0;
                        for (let i = 1; i <= numUnidades; i++) {
                            const numeroUnidade = String(andar) + String(i).padStart(2, '0');
                            const posicaoUnidade = String(i).padStart(2, '0');
                            addUnitStockRow({ andar: andar, numero: numeroUnidade, posicao: posicaoUnidade });
                        }
                    }
                } else {
                    alert('Por favor, defina o número de andares e unidades por andar primeiro.');
                }
            });
            if (applyBatchValueBtn && batchValueInput) {
                applyBatchValueBtn.addEventListener('click', () => {
                    const value = parseFloat(batchValueInput.value);
                    if (!isNaN(value)) {
                        document.querySelectorAll('.unit-stock-valor-input').forEach(input => {
                            input.value = value.toFixed(2);
                        });
                    } else { alert('Por favor, insira um valor numérico válido para o preenchimento em massa.'); }
                });
            }
            if (applyBatchTypeBtn && batchTipoUnidadeSelect) {
                applyBatchTypeBtn.addEventListener('click', () => {
                    const selectedTypeId = batchTipoUnidadeSelect.value;
                    if (selectedTypeId !== "") {
                        document.querySelectorAll('.unit-stock-tipo-select').forEach(select => {
                            select.value = selectedTypeId;
                        });
                    } else { alert('Por favor, selecione um tipo de unidade para o preenchimento em massa.'); }
                });
            }
        }
        if (addVideoUrlBtn) {
            addVideoUrlBtn.addEventListener('click', () => addVideoUrlRow());
            if (window.midiasEtapa5Data && window.midiasEtapa5Data.length > 0) {
                window.midiasEtapa5Data.forEach(url => addVideoUrlRow(url));
            }
        }
        if (addParcelaBtn) {
            addParcelaBtn.addEventListener('click', () => addParcelaRow());
            if (window.fluxoPagamentoEtapa6Data && window.fluxoPagamentoEtapa6Data.parcelas && window.fluxoPagamentoEtapa6Data.parcelas.length > 0) {
                window.fluxoPagamentoEtapa6Data.parcelas.forEach(data => addParcelaRow(data));
            } else {
                if (unidadeExemploSelect && unidadeExemploSelect.value !== "") { addParcelaRow(); }
            }
        }
        if (document.getElementById('total_plano_pagamento')) updatePaymentFlowSummary();
        if (wizardSections.length > 0) updateWizardUI(currentStepFromUrl);

        if (momentoEnvioDocumentacaoSelect) {
            momentoEnvioDocumentacaoSelect.addEventListener('change', toggleDocumentosObrigatorios); //
            toggleDocumentosObrigatorios(); // Executa ao carregar a página para definir o estado inicial
        }
    }
});