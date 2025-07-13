// js/admin_imobiliarias.js - VERSÃO FINAL UNIFICADA COM MODAIS PERSONALIZADOS E SUPORTE PARA FORMULÁRIOS E AÇÕES RÁPIDAS

document.addEventListener('DOMContentLoaded', function() {
    console.log("admin_imobiliarias.js UNIFICADO CARREGADO!");

    // ==== MODAL UNIVERSAL =====
    function openModal(modalElement) {
        if (modalElement) modalElement.classList.add('active');
    }
    function closeModal(modalElement) {
        if (modalElement) modalElement.classList.remove('active');
    }
    function showConfirmationModal(title, message, confirmText, confirmClass, callback) {
        let modal = document.getElementById('genericConfirmationModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'genericConfirmationModal';
            modal.classList.add('modal-overlay');
            modal.innerHTML =
                `<div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title"></h3>
                        <button type="button" class="modal-close-btn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p class="modal-body-content"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-cancel-btn">Cancelar</button>
                        <button type="button" class="btn modal-confirm-btn"></button>
                    </div>
                </div>`;
            document.body.appendChild(modal);
            modal.querySelector('.modal-close-btn').addEventListener('click', () => closeModal(modal));
            modal.querySelector('.modal-cancel-btn').addEventListener('click', () => closeModal(modal));
        }
        modal.querySelector('.modal-title').textContent = title;
        modal.querySelector('.modal-body-content').innerHTML = message;
        const confirmButton = modal.querySelector('.modal-confirm-btn');
        confirmButton.textContent = confirmText;
        confirmButton.className = `btn modal-confirm-btn ${confirmClass}`;
        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
        newConfirmButton.addEventListener('click', () => {
            if (typeof callback === 'function') callback(true);
            closeModal(modal);
        }, { once: true });
        openModal(modal);
    }

    // ==== MÁSCARAS DE INPUT =====
    function applyMask(input, maskPattern) {
        if (!input) return;
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            let masked = '';
            let k = 0;
            for (let i = 0; i < maskPattern.length && k < value.length; i++) {
                masked += maskPattern[i] === '#' ? value[k++] : maskPattern[i];
            }
            e.target.value = masked;
        });
    }

    // ==== BUSCA CEP AUTO-PREENCHER =====
    function buscarCEP(cepValue) {
        const cep = cepValue.replace(/\D/g, '');
        if (cep.length !== 8) return;
        fetch(`${BASE_URL_JS}api/cep.php?cep=${cep}`)
            .then(res => res.json())
            .then(response => {
                if (response.success && response.data) {
                    document.getElementById('endereco').value = response.data.logradouro || '';
                    document.getElementById('bairro').value = response.data.bairro || '';
                    document.getElementById('cidade').value = response.data.localidade || '';
                    document.getElementById('estado').value = response.data.uf || '';
                } else {
                    showConfirmationModal('Erro de CEP', response.message || "CEP não encontrado.", 'Fechar', 'btn-danger');
                }
            }).catch(() => {
                showConfirmationModal('Erro de Rede', 'Não foi possível buscar o CEP.', 'Fechar', 'btn-danger');
            });
    }

    // ==== CENTRAL DE ENVIO PARA A API (ACEITA FORMDATA OU OBJETO LITERAL) =====
    function handleImobiliariaActionFetch(formOrObj) {
        const apiEndpoint = `${BASE_URL_JS}api/imobiliaria.php`;
        let fetchOptions = { method: 'POST' };
        // Detecta se é FormData ou objeto literal
        if (window.FormData && formOrObj instanceof FormData) {
            fetchOptions.body = formOrObj;
        } else {
            // Se for objeto literal (para as ações rápidas), converte para FormData
            const fd = new FormData();
            for (let key in formOrObj) fd.append(key, formOrObj[key]);
            fetchOptions.body = fd;
        }
        fetch(apiEndpoint, fetchOptions)
            .then(res => res.ok ? res.json() : Promise.reject(res))
            .then(data => {
                if (data.success) {
                    showConfirmationModal('Sucesso!', data.message, 'OK', 'btn-success', () => {
                        window.location.href = `${BASE_URL_JS}admin/imobiliarias/index.php`;
                    });
                } else {
                    showConfirmationModal('Erro!', data.message || 'Ocorreu um erro desconhecido.', 'Fechar', 'btn-danger');
                }
            })
            .catch(err => {
                console.error('Erro na requisição:', err);
                showConfirmationModal('Erro de Comunicação', 'Não foi possível completar a ação. Verifique sua conexão.', 'Fechar', 'btn-danger');
            });
    }

    // ==== LÓGICA DA TABELA DE LISTAGEM (AÇÕES RÁPIDAS) =====
    const imobiliariasTable = document.getElementById('imobiliariasTable');
    if (imobiliariasTable) {
        imobiliariasTable.addEventListener('click', function(e) {
            const button = e.target.closest('button.btn');
            if (!button || !button.dataset.id) return;
            const id = button.dataset.id;
            const name = button.closest('tr').querySelector('td:nth-child(2)').textContent.trim();
            let params = {};
            if (button.classList.contains('activate-imobiliaria')) {
                params = {
                    action: 'activate_imobiliaria',
                    title: 'Ativar Imobiliária',
                    message: `Deseja reativar a imobiliária <strong>${name}</strong>?`,
                    btnClass: 'btn-success',
                    btnText: 'Sim, Ativar'
                };
            } else if (button.classList.contains('deactivate-imobiliaria')) {
                params = {
                    action: 'inactivate_imobiliaria',
                    title: 'Inativar Imobiliária',
                    message: `Deseja inativar a imobiliária <strong>${name}</strong>?`,
                    btnClass: 'btn-warning',
                    btnText: 'Sim, Inativar'
                };
            } else if (button.classList.contains('delete-imobiliaria')) {
                params = {
                    action: 'delete_imobiliaria',
                    title: 'Excluir Imobiliária',
                    message: `Atenção! Deseja <strong>EXCLUIR</strong> a imobiliária <strong>${name}</strong>? Esta ação é irreversível.`,
                    btnClass: 'btn-danger',
                    btnText: 'Sim, Excluir'
                };
            } else { return; }
            showConfirmationModal(params.title, params.message, params.btnText, params.btnClass, (confirmed) => {
                if (confirmed) {
                    handleImobiliariaActionFetch({ action: params.action, imobiliaria_id: id });
                }
            });
        });
    }

    // ==== LÓGICA DOS FORMULÁRIOS (CRIAR/EDITAR) =====
    function initializeFormLogic(form) {
        if (!form) return;
        applyMask(form.querySelector('#cep'), '#####-###');
        applyMask(form.querySelector('#cnpj'), '##.###.###/####-##');
        applyMask(form.querySelector('#telefone'), '(##) #####-####');
        const cepInput = form.querySelector('#cep');
        if (cepInput) cepInput.addEventListener('blur', () => buscarCEP(cepInput.value));
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const actionText = form.id === 'createImobiliariaForm' ? 'criar esta nova imobiliária' : 'salvar as alterações';
            showConfirmationModal('Confirmar', `Deseja realmente ${actionText}?`, 'Sim, Salvar', 'btn-primary', (confirmed) => {
                if (confirmed) handleImobiliariaActionFetch(new FormData(form));
            });
        });
    }
    initializeFormLogic(document.getElementById('createImobiliariaForm'));
    initializeFormLogic(document.getElementById('editImobiliariaForm'));
});
