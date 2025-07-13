document.addEventListener('DOMContentLoaded', function() {
    const usersTable = document.getElementById('usersTable');
    const userSearchInput = document.getElementById('userSearch');
    const userFilterTypeSelect = document.getElementById('userFilterType');
    const userFilterStatusSelect = document.getElementById('userFilterStatus');
    const applyUserFiltersBtn = document.getElementById('applyUserFiltersBtn');

    // --- Funções Auxiliares para Modais (globalmente disponíveis via admin.js) ---
    // window.openModal, window.closeModal, window.showConfirmationModal já são carregadas.

    // --- Lógica para Ações da Tabela (Abrir Modais e Disparar AJAX) ---
    // NOVO: Função para lidar com o fetch para as ações de usuário
    window.handleUserActionFetch = function(userId, actionType, customFormData = null) {
        const formData = customFormData || new FormData();
        if (!customFormData) {
            formData.append('user_id', userId);
            formData.append('action', actionType);
        }

        // O endpoint agora é sempre processa_usuario.php
        fetch(BASE_URL_JS + 'api/processa_usuario.php', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Se a resposta contiver um redirectUrl, redireciona
                if (data.redirectUrl) {
                    window.location.href = data.redirectUrl;
                } else {
                    // Caso contrário, apenas mostra a mensagem e recarrega a página (para aprovar/inativar etc.)
                    window.showConfirmationModal('Sucesso!', data.message, 'OK', 'btn-success', () => {
                        location.reload();
                    });
                }
            } else {
                // Se a operação falhou, exibe a mensagem de erro do backend
                window.showConfirmationModal('Erro na Operação', data.message || 'Ocorreu um erro desconhecido.', 'Fechar', 'btn-danger');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            window.showConfirmationModal('Erro de Comunicação', 'Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.', 'Fechar', 'btn-danger');
        });
    };

    // Listener delegado para cliques nos botões da tabela de usuários
    if (usersTable) {
        usersTable.addEventListener('click', function(event) {
            const target = event.target;
            // Use closest('button') para pegar o elemento botão pai
            const clickedButton = target.closest('button');
            
            // Garante que é um botão e que tem um data-id (todos os botões de ação na tabela têm)
            if (!clickedButton || !clickedButton.dataset.id) return;

            const userId = clickedButton.dataset.id;
            // Pegue o nome do usuário da célula (td) mais próxima, não do botão
            const userName = clickedButton.closest('tr').querySelector('td:nth-child(2)').textContent.trim();

            let actionType = '';
            let confirmMessageTitle = '';
            let confirmMessageBody = '';
            let confirmButtonText = '';
            let confirmButtonClass = '';
            let needsReason = false; // Flag para ações que precisam de motivo (rejeitar/inativar)

            // Mapeia a classe do botão para o tipo de ação e mensagens do modal
            if (clickedButton.classList.contains('approve-user')) {
                actionType = 'aprovar';
                confirmMessageTitle = 'Aprovar Usuário';
                confirmMessageBody = `Você tem certeza que deseja **aprovar** o usuário <strong>${userName}</strong>?`;
                confirmButtonText = 'Confirmar Aprovação'; confirmButtonClass = 'btn-success';
            } else if (clickedButton.classList.contains('reject-user')) {
                actionType = 'rejeitar';
                confirmMessageTitle = 'Rejeitar Usuário';
                confirmMessageBody = `Você tem certeza que deseja **rejeitar** o usuário <strong>${userName}</strong>? Esta ação é irreversível.`;
                confirmButtonText = 'Confirmar Rejeição'; confirmButtonClass = 'btn-danger';
                needsReason = true;
            } else if (clickedButton.classList.contains('activate-user')) {
                actionType = 'ativar';
                confirmMessageTitle = 'Ativar Usuário';
                confirmMessageBody = `Você tem certeza que deseja **ativar** o usuário <strong>${userName}</strong>? Ele terá acesso novamente.`;
                confirmButtonText = 'Confirmar Ativação'; confirmButtonClass = 'btn-success';
            } else if (clickedButton.classList.contains('deactivate-user')) {
                actionType = 'inativar';
                confirmMessageTitle = 'Inativar Usuário';
                confirmMessageBody = `Você tem certeza que deseja **inativar** o usuário <strong>${userName}</strong>? Ele perderá o acesso ao sistema.`;
                confirmButtonText = 'Confirmar Inativação'; confirmButtonClass = 'btn-warning';
                needsReason = true;
            } else if (clickedButton.classList.contains('delete-user')) {
                actionType = 'excluir';
                confirmMessageTitle = 'Excluir Usuário';
                confirmMessageBody = `Você tem certeza que deseja **EXCLUIR** o usuário <strong>${userName}</strong>? <br><strong>Esta ação é irreversível</strong> e removerá todos os dados vinculados a ele.`;
                confirmButtonText = 'Confirmar Exclusão'; confirmButtonClass = 'btn-danger';
            } else {
                return; // Não é um botão de ação reconhecido, não faz nada
            }

            if (needsReason) {
                // Para ações que precisam de motivo, abre um modal específico para coletar o motivo
                let modalId = (actionType === 'rejeitar') ? 'rejectUserModal' : 'inactivateUserModal';
                let modal = document.getElementById(modalId);
                
                if (modal && typeof window.openModal === 'function') {
                    // Preenche o ID e o nome do usuário no modal
                    modal.querySelector('input[name="user_id"]').value = userId;
                    // Certifique-se de que o span para o nome do usuário existe e tem o ID correto no HTML do modal
                    modal.querySelector('strong[id$="UserName"]').textContent = userName; 
                    
                    // Clona o formulário para remover listeners antigos e adicionar um novo
                    let form = modal.querySelector('form');
                    let oldForm = form.cloneNode(true);
                    form.parentNode.replaceChild(oldForm, form);
                    form = oldForm;

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        let reasonTextarea = this.querySelector('textarea[name="motivo_rejeicao"], textarea[name="motivo_inativacao"]');
                        let reason = reasonTextarea ? reasonTextarea.value : '';
                        
                        window.handleUserActionFetch(userId, actionType, null, reason); // Passa o motivo
                        window.closeModal(modal); // Fecha o modal de motivo após submeter
                    });
                    
                    window.openModal(modal); // Abre o modal específico
                } else {
                    console.error("Modal de motivo não encontrado ou função openModal indisponível.");
                    // Fallback para o modal genérico se o modal específico não for encontrado.
                    // ATENÇÃO: Se isso acontecer, o campo de motivo não será coletado.
                    window.showConfirmationModal(
                        confirmMessageTitle,
                        confirmMessageBody + "<br><br> (O campo de motivo não pôde ser carregado. Prossiga se for o caso de não precisar de motivo, ou contate o suporte.)",
                        confirmButtonText,
                        confirmButtonClass,
                        (confirmed) => {
                            if (confirmed) {
                                window.handleUserActionFetch(userId, actionType); 
                            }
                        }
                    );
                }
            } else {
                // Para ações sem motivo, usa o modal de confirmação genérico
                window.showConfirmationModal(
                    confirmMessageTitle,
                    confirmMessageBody,
                    confirmButtonText,
                    confirmButtonClass,
                    (confirmed) => {
                        if (confirmed) {
                            window.handleUserActionFetch(userId, actionType); // Chama a função que faz o fetch
                        }
                    }
                );
            }
        });
        
        // --- Lógica de Filtros e Ordenação (já existente no admin.js global) ---
        // Adaptação para a tabela de usuários
        const userSearchInput = document.getElementById('userSearch');
        const userFilterTypeSelect = document.getElementById('userFilterType');
        const userFilterStatusSelect = document.getElementById('userFilterStatus');
        const applyUserFiltersBtn = document.getElementById('applyUserFiltersBtn');

        if (applyUserFiltersBtn) {
            applyUserFiltersBtn.addEventListener('click', function() {
                const currentUrl = new URL(window.location.href);
                const params = currentUrl.searchParams;

                params.set('search', userSearchInput ? userSearchInput.value : '');
                params.set('type', userFilterTypeSelect ? userFilterTypeSelect.value : '');
                params.set('status', userFilterStatusSelect ? userFilterStatusSelect.value : '');
                
                window.location.href = currentUrl.origin + currentUrl.pathname + '?' + params.toString();
            });
        }
        
        // Event listeners para select de filtros para auto-aplicar
        if (userFilterTypeSelect) userFilterTypeSelect.addEventListener('change', () => applyUserFiltersBtn.click());
        if (userFilterStatusSelect) userFilterStatusSelect.addEventListener('change', () => applyUserFiltersBtn.click());
        // Event listener para o input de busca (Enter)
        if (userSearchInput) {
            userSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyUserFiltersBtn.click();
                }
            });
        }

        // Lógica de ordenação (já está em admin.js, apenas garantir que está anexada à usersTable)
        if (usersTable) { // usersTable é o ID da tabela de usuários
            usersTable.querySelectorAll('th[data-sort-by]').forEach(header => {
                header.addEventListener('click', function() {
                    const columnKey = this.dataset.sortBy;
                    const currentSortOrder = new URLSearchParams(window.location.search).get('sort_order') || 'asc';
                    const newSortOrder = (columnKey === new URLSearchParams(window.location.search).get('sort_by')) && (currentSortOrder === 'asc') ? 'desc' : 'asc';
                    
                    const currentUrl = new URL(window.location.href);
                    const params = currentUrl.searchParams;
                    params.set('sort_by', columnKey);
                    params.set('sort_order', newSortOrder);
                    
                    window.location.href = currentUrl.origin + currentUrl.pathname + '?' + params.toString();
                });
            });
            // Lógica para definir os ícones de ordenação ao carregar a página
            const currentSortBy = new URLSearchParams(window.location.search).get('sort_by');
            const currentSortOrder = new URLSearchParams(window.location.search).get('sort_order');
            if (currentSortBy && currentSortOrder) {
                const activeHeader = usersTable.querySelector(`th[data-sort-by="${currentSortBy}"]`);
                if (activeHeader) {
                    const icon = activeHeader.querySelector('.fas.fa-sort');
                    if (icon) {
                        icon.classList.remove('fa-sort');
                        icon.classList.add(currentSortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                    }
                }
            }
        }
    }


    // --- Lógica de Formulários de Edição/Criação (editar.php, criar.php) ---
    // NOVO: Funções para lidar com o toggle de campos específicos de tipo de usuário
    function toggleUserTypeSpecificFields() {
        const tipoSelect = document.getElementById('tipo');
        const imobiliariaSelectGroup = document.getElementById('imobiliaria_select_group');
        const cpfInput = document.getElementById('cpf');
        const creciInput = document.getElementById('creci');
        // Senha e Confirmar Senha só para Criar (e alterar senha em Editar)
        const senhaInput = document.getElementById('senha');
        const confirmarSenhaInput = document.getElementById('confirmar_senha');

        if (!tipoSelect) return; // Se não for página de criar/editar usuário, encerra

        const selectedType = tipoSelect.value;
        const isCorretorType = selectedType.includes('corretor');
        const isAdminImobiliariaType = selectedType === 'admin_imobiliaria';

        // Lógica para Imobiliária Select Group
        if (imobiliariaSelectGroup) {
            // CORREÇÃO: Only display if type is 'corretor_imobiliaria' OR 'admin_imobiliaria'
            imobiliariaSelectGroup.style.display = (selectedType === 'corretor_imobiliaria' || selectedType === 'admin_imobiliaria') ? 'block' : 'none';
            imobiliariaSelectGroup.querySelector('select').required = (selectedType === 'corretor_imobiliaria' || selectedType === 'admin_imobiliaria');
        }

        // Lógica para CPF e CRECI
        if (cpfInput) cpfInput.required = isCorretorType;
        if (creciInput) creciInput.required = isCorretorType;

        // Limpar valores se o campo não é mais necessário
        if (imobiliariaSelectGroup && imobiliariaSelectGroup.style.display === 'none') {
            imobiliariaSelectGroup.querySelector('select').value = '';
        }
        if (cpfInput && !isCorretorType) cpfInput.value = '';
        if (creciInput && !isCorretorType) creciInput.value = '';

        // Aplicar/reaplicar máscaras e validações
        if (cpfInput) window.applyCpfMask(cpfInput);
        if (document.getElementById('telefone')) window.applyWhatsappMask(document.getElementById('telefone'));
        if (document.getElementById('cnpj')) window.applyMask(document.getElementById('cnpj'), '##.###.###/####-##'); // Se existir (em criar/editar imobiliaria)
        if (document.getElementById('cep')) document.getElementById('cep').addEventListener('blur', (e) => window.buscarCEP(e.target.value, 'endereco', 'bairro', 'cidade', 'estado')); // Em criar/editar imobiliaria
    }

    // Inicializa os toggles ao carregar a página
    toggleUserTypeSpecificFields();

    // Adiciona listener para mudanças no select de tipo de usuário
    const tipoSelectElement = document.getElementById('tipo');
    if (tipoSelectElement) {
        tipoSelectElement.addEventListener('change', toggleUserTypeSpecificFields);
    }

    const editUserForm = document.getElementById('editUserForm');
    const createUserForm = document.getElementById('createUserForm');

    if (editUserForm) {
        editUserForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const nome = document.getElementById('nome').value.trim();
            const email = document.getElementById('email').value.trim();
            const telefone = document.getElementById('telefone').value.trim();
            const tipo = document.getElementById('tipo').value;
            const cpf = document.getElementById('cpf').value.trim();
            const creci = document.getElementById('creci').value.trim();

            if (!nome || !email || !telefone || !tipo) {
                window.showConfirmationModal('Erro de Validação', 'Por favor, preencha todos os campos obrigatórios (Nome, E-mail, Telefone, Tipo).', 'Fechar', 'btn-danger');
                return;
            }
            if (!email.includes('@') || !email.includes('.')) {
                window.showConfirmationModal('Erro de Validação', 'O formato do e-mail é inválido.', 'Fechar', 'btn-danger');
                return;
            }
            if (tipo.includes('corretor') && cpf.replace(/\D/g, '').length !== 11) {
                window.showConfirmationModal('Erro de Validação', 'O CPF deve conter 11 dígitos para corretores.', 'Fechar', 'btn-danger');
                return;
            }
            if (tipo.includes('corretor') && !creci) {
                window.showConfirmationModal('Erro de Validação', 'O CRECI é obrigatório para corretores.', 'Fechar', 'btn-danger');
                return;
            }

            window.showConfirmationModal(
                'Confirmar Edição',
                'Você tem certeza que deseja salvar as alterações para este usuário?',
                'Salvar',
                'btn-primary',
                (confirmed) => {
                    if (confirmed) {
                        const formData = new FormData(editUserForm);
                        formData.append('action', 'update_usuario');
                        window.handleUserActionFetch(null, null, formData);
                    }
                }
            );
        });
    } else if (createUserForm) {
        createUserForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const nome = document.getElementById('nome').value.trim();
            const email = document.getElementById('email').value.trim();
            const telefone = document.getElementById('telefone').value.trim();
            const tipo = document.getElementById('tipo').value;
            const senha = document.getElementById('senha').value;
            const confirmar_senha = document.getElementById('confirmar_senha').value;
            const cpf = document.getElementById('cpf').value.trim();
            const creci = document.getElementById('creci').value.trim();

            if (!nome || !email || !telefone || !tipo || !senha || !confirmar_senha) {
                window.showConfirmationModal('Erro de Validação', 'Por favor, preencha todos os campos obrigatórios.', 'Fechar', 'btn-danger');
                return;
            }
            if (!email.includes('@') || !email.includes('.')) {
                window.showConfirmationModal('Erro de Validação', 'O formato do e-mail é inválido.', 'Fechar', 'btn-danger');
                return;
            }
            if (senha !== confirmar_senha) {
                window.showConfirmationModal('Erro de Validação', 'As senhas não coincidem.', 'Fechar', 'btn-danger');
                return;
            }
            if (senha.length < 6) {
                window.showConfirmationModal('Erro de Validação', 'A senha deve ter no mínimo 6 caracteres.', 'Fechar', 'btn-danger');
                return;
            }
            if (tipo.includes('corretor') && cpf.replace(/\D/g, '').length !== 11) {
                window.showConfirmationModal('Erro de Validação', 'O CPF deve conter 11 dígitos para corretores.', 'Fechar', 'btn-danger');
                return;
            }
            if (tipo.includes('corretor') && !creci) {
                window.showConfirmationModal('Erro de Validação', 'O CRECI é obrigatório para corretores.', 'Fechar', 'btn-danger');
                return;
            }

            window.showConfirmationModal(
                'Confirmar Criação',
                'Você tem certeza que deseja criar este novo usuário?',
                'Criar',
                'btn-primary',
                (confirmed) => {
                    if (confirmed) {
                        const formData = new FormData(createUserForm);
                        formData.append('action', 'create_usuario');
                        window.handleUserActionFetch(null, null, formData);
                    }
                }
            );
        });
    }

});