// js/admin_leads.js - VERSÃO COMPLETA E FINAL

document.addEventListener('DOMContentLoaded', function() {
    const leadsTable = document.getElementById('leadsTable');
    // Se a tabela de leads não existir nesta página, não faz nada.
    if (!leadsTable) {
        return;
    }

    const assignModal = document.getElementById('assignCorretorModal');
    const assignForm = document.getElementById('assignCorretorForm');
    
    // Função de modal autossuficiente para evitar erros de callback
    function showConfirmationModal(title, message, confirmText, confirmClass, callback) {
        let modal = document.getElementById('genericConfirmationModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'genericConfirmationModal';
            modal.classList.add('modal-overlay');
            modal.innerHTML = `<div class="modal-content"><div class="modal-header"><h3 class="modal-title"></h3><button type="button" class="modal-close-btn">&times;</button></div><div class="modal-body"><p class="modal-body-content"></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary modal-cancel-btn">Cancelar</button><button type="button" class="btn modal-confirm-btn"></button></div></div>`;
            document.body.appendChild(modal);
            modal.querySelector('.modal-close-btn').addEventListener('click', () => modal.classList.remove('active'));
            modal.querySelector('.modal-cancel-btn').addEventListener('click', () => modal.classList.remove('active'));
        }

        modal.querySelector('.modal-title').textContent = title;
        modal.querySelector('.modal-body-content').innerHTML = message;
        const confirmButton = modal.querySelector('.modal-confirm-btn');
        confirmButton.textContent = confirmText;
        confirmButton.className = `btn modal-confirm-btn ${confirmClass}`;

        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);

        newConfirmButton.addEventListener('click', () => {
            if (typeof callback === 'function') {
                callback(true);
            }
            modal.classList.remove('active');
        }, { once: true });

        modal.classList.add('active');
    }
    
    function openModal(modalElement) { if (modalElement) modalElement.classList.add('active'); }

    // Listener principal para os botões de ação na tabela de leads
    leadsTable.addEventListener('click', function(e) {
        const button = e.target.closest('button.btn'); // Garante que pegamos o botão
        if (!button || !button.dataset.leadId) return;

        const leadId = button.dataset.leadId;

        if (button.classList.contains('assign-lead-btn')) {
            const clienteNome = button.dataset.clienteNome;
            document.getElementById('assign_lead_id').value = leadId;
            document.getElementById('modalClienteNome').textContent = clienteNome;
            openModal(assignModal);
        } 
        else if (button.classList.contains('attend-lead-btn')) {
            showConfirmationModal('Atender Lead?', 'Deseja assumir o atendimento deste lead? Ele será atribuído a você (Admin).', 'Sim, Atender', 'btn-success', () => {
                handleLeadAction({ action: 'attend_lead', lead_id: leadId });
            });
        } else if (button.classList.contains('dismiss-lead-btn')) {
            showConfirmationModal('Dispensar Lead?', 'Tem certeza que deseja dispensar este lead? Ele será removido da lista de pendentes.', 'Sim, Dispensar', 'btn-danger', () => {
                handleLeadAction({ action: 'dismiss_lead', lead_id: leadId });
            });
        }
    });

    // Listener para o envio do formulário do modal de atribuição
    if (assignForm) {
        assignForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleLeadAction(new FormData(this));
        });
    }

    // Função central para enviar ações para a API
    function handleLeadAction(data) {
        const isFormData = data instanceof FormData;
        const formData = isFormData ? data : new FormData();
        
        if (!isFormData) {
            for (const key in data) {
                formData.append(key, data[key]);
            }
        }

        const apiEndpoint = `${BASE_URL_JS}api/processa_lead.php`;

        fetch(apiEndpoint, {
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (!res.ok) {
                 return res.json().then(err => Promise.reject(err));
            }
            return res.json();
        })
        .then(result => {
            if (result.success) {
                showConfirmationModal('Sucesso!', result.message, 'OK', 'btn-success', () => {
                    location.reload();
                });
            } else {
                showConfirmationModal('Erro!', result.message || 'Ocorreu um erro desconhecido.', 'Fechar', 'btn-danger');
            }
        })
        .catch(err => {
            console.error('Erro na requisição:', err);
            const errorMessage = err.message || 'Não foi possível se comunicar com o servidor.';
            showConfirmationModal('Erro de Rede', errorMessage, 'Fechar', 'btn-danger');
        });
    }
});