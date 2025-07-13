// js/admin_clientes.js - VERSÃO FINAL COM BUSCA CEP CORRIGIDA

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== FUNÇÕES AUXILIARES CONSOLIDADAS =====
    
    function openModal(modalElement) { if (modalElement) modalElement.classList.add('active'); }
    function closeModal(modalElement) { if (modalElement) modalElement.classList.remove('active'); }

    function showConfirmationModal(title, message, confirmText, confirmClass, callback) {
        let modal = document.getElementById('genericConfirmationModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'genericConfirmationModal';
            modal.classList.add('modal-overlay');
            modal.innerHTML = `<div class="modal-content"><div class="modal-header"><h3 class="modal-title"></h3><button type="button" class="modal-close-btn">&times;</button></div><div class="modal-body"><p class="modal-body-content"></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary modal-cancel-btn">Cancelar</button><button type="button" class="btn modal-confirm-btn"></button></div></div>`;
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

    function applyMask(input, maskPattern) {
        if(!input) return;
        function mask(e) {
            let value = e.target.value.replace(/\D/g, '');
            let maskedValue = '';
            let k = 0;
            for (let i = 0; i < maskPattern.length && k < value.length; i++) {
                maskedValue += maskPattern[i] === '#' ? value[k++] : maskPattern[i];
            }
            e.target.value = maskedValue;
        }
        input.addEventListener('input', mask);
    }

    // ===== FUNÇÃO buscarCEP CORRIGIDA =====
    function buscarCEP(cepValue, enderecoId, bairroId, cidadeId, estadoId) {
        const cep = cepValue.replace(/\D/g, '');
        if (cep.length !== 8) return;

        const apiEndpoint = `${BASE_URL_JS}api/cep.php?cep=${cep}`;

        fetch(apiEndpoint)
            .then(res => res.json())
            .then(response => {
                // Acessa os dados dentro do objeto 'data' da nossa API
                if (response.success && response.data) {
                    const addressData = response.data;
                    document.getElementById(enderecoId).value = addressData.logradouro;
                    document.getElementById(bairroId).value = addressData.bairro;
                    document.getElementById(cidadeId).value = addressData.localidade;
                    document.getElementById(estadoId).value = addressData.uf;
                } else {
                    alert(response.message || "CEP não encontrado.");
                }
            }).catch(err => {
                console.error("Erro ao buscar CEP via API local:", err);
                alert("Não foi possível buscar o CEP.");
            });
    }
    // =======================================

    // --- Lógica para a página de DETALHES ---
    const editClientForm = document.getElementById('editClientForm');
    if (editClientForm) {
        const displayModeDiv = document.getElementById('display-mode');
        const editModeDiv = document.getElementById('edit-mode');
        const btnEditar = document.getElementById('btnEditarCliente');
        const btnSalvar = document.getElementById('btnSalvarCliente');
        const btnCancelar = document.getElementById('btnCancelarEdicao');

        function toggleEditMode(isEditing) {
            displayModeDiv.style.display = isEditing ? 'none' : 'block';
            editModeDiv.style.display = isEditing ? 'grid' : 'none';
            btnEditar.style.display = isEditing ? 'none' : 'inline-block';
            btnSalvar.style.display = isEditing ? 'inline-block' : 'none';
            btnCancelar.style.display = isEditing ? 'inline-block' : 'none';
        }

        btnEditar.addEventListener('click', () => toggleEditMode(true));
        btnCancelar.addEventListener('click', () => toggleEditMode(false));

        // Aplicação de Máscaras e Busca CEP
        applyMask(editModeDiv.querySelector('#edit_cpf'), '###.###.###-##');
        applyMask(editModeDiv.querySelector('#edit_whatsapp'), '(##) #####-####');
        const cepInput = editModeDiv.querySelector('#edit_cep');
        if (cepInput) {
            applyMask(cepInput, '#####-###');
            // A chamada aqui usa os IDs corretos dos campos de edição
            cepInput.addEventListener('blur', () => buscarCEP(cepInput.value, 'edit_endereco', 'edit_bairro', 'edit_cidade', 'edit_estado'));
        }
        
        editClientForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const apiEndpoint = BASE_URL_JS + 'api/processa_cliente.php';
            fetch(apiEndpoint, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .then(data => {
                if (data.success) {
                    showConfirmationModal('Sucesso!', data.message, 'OK', 'btn-success', () => window.location.reload());
                } else {
                    showConfirmationModal('Erro ao Salvar', data.message, 'Fechar', 'btn-danger');
                }
            })
            .catch(err => {
                console.error('Erro na requisição:', err);
                showConfirmationModal('Erro de Comunicação', 'Não foi possível salvar os dados.', 'Fechar', 'btn-danger');
            });
        });
    }
});