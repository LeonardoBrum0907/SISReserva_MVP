document.addEventListener('DOMContentLoaded', function() {
    // ESTE ARQUIVO É PROJETADO PARA SER AUTO-SUFICIENTE PARA A PÁGINA DE DOCUMENTOS.
    // AS FUNÇÕES GENÉRICAS AQUI SÃO DUPLICATAS DE ADMIN.JS PARA GARANTIR A INDEPENDÊNCIA.

    // BASE_URL_JS deve ser definida globalmente no footer_dashboard.php
    if (typeof BASE_URL_JS === 'undefined') {
        console.error("admin_documentos.js: BASE_URL_JS não está definida. Verifique o footer_dashboard.php.");
        // Fallback defensivo para evitar quebrar o JS completamente
        window.BASE_URL_JS = '/'; 
    }

    // --- FUNÇÕES AUXILIARES GENÉRICAS (COPIADAS PARA GARANTIR INDEPENDÊNCIA) ---
    function parseBrazilianDateTime(dateTimeStr) {
        if (dateTimeStr === 'N/A' || !dateTimeStr) { return new Date(0); }
        const parts = dateTimeStr.match(/(\d{2})\/(\d{2})\/(\d{4})[^\d]*(\d{2}):(\d{2}):(\d{2})/);
        if (parts) { return new Date(parseInt(parts[3]), parseInt(parts[2]) - 1, parseInt(parts[1]), parseInt(parts[4]), parseInt(parts[5]), parseInt(parts[6])); }
        return new Date(0);
    }

    function parseRemainingTimeForSorting(timeStr) {
        if (timeStr === 'N/A') return -Infinity;
        if (timeStr === 'Expirada') return -1;
        const match = timeStr.match(/(\d+)\s*(ano|mês|semana|dia|hora|minuto|segundo)(s?)/i);
        if (match) {
            const value = parseInt(match[1]);
            const unit = match[2].toLowerCase();
            let totalSeconds = 0;
            switch (unit) {
                case 'ano': totalSeconds = value * 365 * 24 * 3600; break;
                case 'mês': totalSeconds = value * 30 * 24 * 3600; break;
                case 'semana': totalSeconds = value * 7 * 24 * 3600; break;
                case 'dia': totalSeconds = value * 24 * 3600; break;
                case 'hora': totalSeconds = value * 3600; break;
                case 'minuto': totalSeconds = value * 60; break;
                case 'segundo': totalSeconds = value; break;
            }
            return totalSeconds;
        }
        return Infinity;
    }

    function htmlspecialchars(str) {
        if (typeof str !== 'string' && typeof str !== 'number') return '';
        str = String(str);
        var map = { '&': '&', '<': '<', '>': '>', '"': '"', "'": '\'' };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // --- Funções de Modal (Duplicadas para self-containment) ---
    function openModal(modalElement) {
        if (modalElement) {
            modalElement.classList.remove('hidden');
            modalElement.style.display = 'block';
            modalElement.classList.add('active');
            document.body.classList.add('modal-open');
            console.log(`admin_documentos.js: Modal ${modalElement.id} aberto.`);
        }
    }

    function closeModal(modalElement) {
        if (modalElement) {
            modalElement.classList.remove('active');
            modalElement.style.display = 'none';
            modalElement.classList.add('hidden');
            document.body.classList.remove('modal-open');
            console.log(`admin_documentos.js: Modal ${modalElement.id} fechado.`);
        }
    }

    function closeAllModals() { // Usado para fechar todos antes de abrir um novo
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            closeModal(modal);
        });
    }
    
    // --- Função para exibir feedback (Duplicada para self-containment) ---
    const feedbackModal = document.getElementById('feedbackModal'); // Assume feedbackModal existe em index.php
    const feedbackModalTitle = feedbackModal ? document.getElementById('feedbackModalTitle') : null;
    const feedbackModalMessage = feedbackModal ? document.getElementById('feedbackModalMessage') : null;
    const feedbackModalCloseBtns = feedbackModal ? feedbackModal.querySelectorAll('.modal-close-btn, .modal-footer .btn') : []; 
    feedbackModalCloseBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            closeModal(feedbackModal);
        });
    });

    function showFeedbackModal(title, message, isSuccess) {
        if (!feedbackModal) return;
        if (feedbackModalTitle) feedbackModalTitle.textContent = title;
        if (feedbackModalMessage) feedbackModalMessage.textContent = message;
        feedbackModal.classList.remove('modal-success', 'modal-error');
        if (isSuccess) {
            feedbackModal.classList.add('modal-success');
        } else {
            feedbackModal.classList.add('modal-error');
        }
        openModal(feedbackModal);
    }

    // --- Função Genérica de Submissão de Formulário via AJAX (Duplicada para self-containment) ---
    function handleFormSubmission(formId, modalElement) {
        const form = document.getElementById(formId);
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Processando...';
                }

                // BASE_URL_JS deve ser definida globalmente no footer_dashboard.php
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
                    closeModal(modalElement);
                    showFeedbackModal(data.success ? 'Sucesso!' : 'Erro!', data.message, data.success);
                    if (data.success) {
                        setTimeout(() => location.reload(), 1500);
                    }
                })
                .catch(error => {
                    closeModal(modalElement);
                    showFeedbackModal('Erro de Comunicação', 'Ocorreu um erro ao comunicar com o servidor. Detalhes: ' + error.message, false);
                })
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = submitButton.dataset.originalText || 'Confirmar';
                    }
                });
            });
        }
    }

    // --- Funções de Tabela (Busca e Ordenação Genéricas - Duplicadas para self-containment) ---
    function applyFiltersAndSort(tableId, searchInputId, filterSelectId = null) {
        const table = document.getElementById(tableId);
        const searchInput = document.getElementById(searchInputId);
        const filterSelect = filterSelectId ? document.getElementById(filterSelectId) : null;

        if (!table) {
            console.warn(`admin_documentos.js: Tabela com ID '${tableId}' não encontrada. Pulando applyFiltersAndSort.`);
            return;
        }

        let sortDirection = {};
        let currentSortColumn = null;
        let currentSortDirection = 'asc';

        function performFilteringAndSorting() {
            const tbody = table.querySelector('tbody');
            let originalRows = Array.from(tbody.querySelectorAll('tr'));
            
            console.log(`admin_documentos.js: DEBUG ${tableId}: Contagem inicial de originalRows:`, originalRows.length);
            if (originalRows.length > 0) {
                console.log(`admin_documentos.js: DEBUG ${tableId}: Atributos da primeira linha:`, originalRows[0].dataset);
            }

            let filteredRows = [...originalRows];

            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            if (searchTerm) {
                console.log(`admin_documentos.js: DEBUG ${tableId}: Aplicando busca por termo: "${searchTerm}"`);
                filteredRows = filteredRows.filter(row => row.textContent.toLowerCase().includes(searchTerm));
            }

            const filterValue = filterSelect ? filterSelect.value : '';
            if (filterValue) {
                console.log(`admin_documentos.js: DEBUG ${tableId}: Aplicando filtro de seleção: ${filterSelectId} = "${filterValue}"`);
                filteredRows = filteredRows.filter(row => {
                    const rowStatus = row.dataset.documentStatus || '';
                    return rowStatus === filterValue;
                });
                console.log(`admin_documentos.js: DEBUG ${tableId}: Contagem de linhas após filtro:`, filteredRows.length);
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

                        if (currentSortColumn.includes('id') || currentSortColumn.includes('numero')) {
                            aValue = parseFloat(aValue.replace(/[^0-9,-]+/g, '').replace(',', '.'));
                            bValue = parseFloat(bValue.replace(/[^0-9,-]+/g, '').replace(',', '.'));
                        } else if (currentSortColumn.includes('data')) {
                            aValue = parseBrazilianDateTime(aValue);
                            bValue = parseBrazilianDateTime(bValue);
                        } else {
                            aValue = aValue.toLowerCase();
                            bValue = bValue.toLowerCase();
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
        
        table.querySelectorAll('th[data-sort-by]').forEach(header => {
            const columnKey = header.dataset.sortBy;
            sortDirection[columnKey] = 'asc';

            header.addEventListener('click', function() {
                const tbody = table.querySelector('tbody');
                const allRows = Array.from(tbody.querySelectorAll('tr'));
                const rows = allRows.filter(row => row.style.display !== 'none');

                const isAsc = sortDirection[columnKey] === 'asc';
                sortDirection[columnKey] = isAsc ? 'desc' : 'asc';
                currentSortColumn = columnKey;
                currentSortDirection = sortDirection[columnKey];

                rows.sort((a, b) => {
                    const columnMap = { // Mapeamento específico para a tabela de documentos
                        'document_id': 0,
                        'reserva_id': 1,
                        'nome_documento': 2,
                        'cliente_nome': 3,
                        'empreendimento_nome': 4,
                        'unidade_numero': 5,
                        'data_upload': 6,
                        'status': 7
                    };
                    const columnIndex = columnMap[columnKey];

                    let aValue, bValue;

                    if (columnKey === 'data_upload') {
                        aValue = parseBrazilianDateTime(a.children[columnIndex].textContent.trim());
                        bValue = parseBrazilianDateTime(b.children[columnIndex].textContent.trim());
                    } else if (columnKey === 'document_id' || columnKey === 'reserva_id' || columnKey === 'unidade_numero') {
                        aValue = parseInt(a.children[columnIndex].textContent.trim().replace(/[^0-9]/g, '')) || 0;
                        bValue = parseInt(b.children[columnIndex].textContent.trim().replace(/[^0-9]/g, '')) || 0;
                    } else {
                        aValue = a.children[columnIndex].textContent.trim().toLowerCase();
                        bValue = b.children[columnIndex].textContent.trim().toLowerCase();
                    }
                    
                    if (aValue < bValue) return currentSortDirection === 'asc' ? -1 : 1;
                    if (aValue > bValue) return currentSortDirection === 'asc' ? 1 : -1;
                    return 0;
                });

                table.querySelectorAll('th .fas.fa-sort, th .fas.fa-sort-up, th .fas.fa-sort-down').forEach(icon => {
                    icon.classList.remove('fa-sort-up', 'fa-sort-down');
                    icon.classList.add('fa-sort');
                });

                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(currentSortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                }

                while (tbody.firstChild) {
                    tbody.removeChild(tbody.firstChild);
                }
                rows.forEach(row => tbody.appendChild(row));
            });
        });

        // Inicializa filtragem e ordenação (necessário para a tabela de documentos)
        performFilteringAndSorting(); 
    }

    // ===============================================
    // LÓGICA ESPECÍFICA DE ADMIN/DOCUMENTOS/INDEX.PHP
    // ===============================================
    const documentosTable = document.getElementById('documentosTable');
    
    // Anexa listeners de clique aos botões de aprovar/rejeitar documentos
    if (documentosTable) {
        document.addEventListener('click', function(event) {
            const button = event.target.closest('.approve-document-btn, .reject-document-btn');
            if (!button) return;

            console.log("admin_documentos.js: Botão de documento clicado! Target:", button); // NOVO LOG
            console.log("admin_documentos.js: Dataset do botão clicado:", button.dataset); // NOVO LOG

            const docId = button.dataset.documentId;
            const reservaId = button.dataset.reservaId;
            const docNome = button.dataset.nome;
            const docStatusAtual = button.dataset.statusAtual;
            const docMotivoRejeicao = button.dataset.motivoRejeicao;

            console.log(`admin_documentos.js: docId: ${docId}, reservaId: ${reservaId}, Status Atual: ${docStatusAtual}`); // NOVO LOG

            // Referências aos elementos do modal (apenas se os modais existem na página)
            const approveDocumentIdDisplay = document.getElementById('approveDocumentIdDisplay');
            const approveDocumentReservaIdDisplay = document.getElementById('approveDocumentReservaIdDisplay');
            const approveDocumentIdInput = document.getElementById('approveDocumentIdInput');
            const approveDocumentReservaIdInput = document.getElementById('approveDocumentReservaIdInput');

            const rejectDocumentIdDisplay = document.getElementById('rejectDocumentIdDisplay');
            const rejectDocumentReservaIdDisplay = document.getElementById('rejectDocumentReservaIdDisplay');
            const rejectDocumentIdInput = document.getElementById('rejectDocumentIdInput');
            const rejectDocumentReservaIdInput = document.getElementById('rejectDocumentReservaIdInput');
            const rejectionReasonTextarea = document.getElementById('rejectionReasonDoc');

            // Preenche os campos do modal com os dados do documento clicado
            if (approveDocumentIdDisplay) approveDocumentIdDisplay.textContent = docId;
            if (approveDocumentReservaIdDisplay) approveDocumentReservaIdDisplay.textContent = reservaId;
            if (approveDocumentIdInput) approveDocumentIdInput.value = docId;
            if (approveDocumentReservaIdInput) approveDocumentReservaIdInput.value = reservaId;

            if (rejectDocumentIdDisplay) rejectDocumentIdDisplay.textContent = docId;
            if (rejectDocumentReservaIdDisplay) rejectDocumentReservaIdDisplay.textContent = reservaId;
            if (rejectDocumentIdInput) rejectDocumentIdInput.value = docId;
            if (rejectDocumentReservaIdInput) rejectDocumentReservaIdInput.value = reservaId;
            if (rejectionReasonTextarea) rejectionReasonTextarea.value = docMotivoRejeicao || '';

            // Lógica para abrir o modal correto (aprovação ou rejeição)
            if (button.classList.contains('approve-document-btn')) {
                console.log("admin_documentos.js: Clicado em APROVAR. Abrindo approveDocumentModal."); // NOVO LOG
                openModal(document.getElementById('approveDocumentModal'));
            } else if (button.classList.contains('reject-document-btn')) {
                console.log("admin_documentos.js: Clicado em REJEITAR. Abrindo rejectDocumentModal."); // NOVO LOG
                openModal(document.getElementById('rejectDocumentModal'));
                // Assegura que o select de status no modal de rejeição reflita "rejeitado" (se houver)
                const modalStatusSelect = document.getElementById('modalStatus'); // ID do select no modal de analise de documentos
                if (modalStatusSelect) {
                    modalStatusSelect.value = 'rejeitado';
                    modalStatusSelect.dispatchEvent(new Event('change')); 
                }
            }
        });

        // Lógica para o select de status dentro do modal de Análise de Documento (aprovar/rejeitar)
        // Este select define qual ação (aprovar/rejeitar) o formulário do modal irá enviar
        const modalStatusSelect = document.getElementById('modalStatus');
        const motivoRejeicaoGroup = document.getElementById('motivoRejeicaoGroup');
        const modalMotivoRejeicaoTextarea = document.getElementById('rejectionReasonDoc');
        const rejectDocumentForm = document.getElementById('rejectDocumentForm');
        const approveDocumentForm = document.getElementById('approveDocumentForm');

        if (modalStatusSelect && motivoRejeicaoGroup && modalMotivoRejeicaoTextarea) {
            modalStatusSelect.addEventListener('change', function() {
                const selectedAction = this.value;
                if (selectedAction === 'rejeitado') {
                    motivoRejeicaoGroup.style.display = 'block';
                    modalMotivoRejeicaoTextarea.setAttribute('required', 'required');
                    if (rejectDocumentForm) {
                        rejectDocumentForm.action = BASE_URL_JS + 'api/reserva.php';
                        rejectDocumentForm.querySelector('input[name="action"]').value = 'reject_document';
                    }
                } else { // 'aprovado'
                    motivoRejeicaoGroup.style.display = 'none';
                    modalMotivoRejeicaoTextarea.removeAttribute('required');
                    if (approveDocumentForm) {
                        approveDocumentForm.action = BASE_URL_JS + 'api/reserva.php';
                        approveDocumentForm.querySelector('input[name="action"]').value = 'approve_document';
                    }
                }
            });
            // Dispara no carregamento para definir o estado inicial (ocultar/mostrar motivo)
            modalStatusSelect.dispatchEvent(new Event('change'));
        }

        // Anexa handleFormSubmission aos forms de aprovar/rejeitar documentos nesta página
        if (approveDocumentForm) {
            handleFormSubmission(approveDocumentForm.id, document.getElementById('approveDocumentModal'));
        }
        if (rejectDocumentForm) {
            handleFormSubmission(rejectDocumentForm.id, document.getElementById('rejectDocumentModal'));
        }
    }
});