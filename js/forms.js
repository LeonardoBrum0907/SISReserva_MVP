// js/forms.js
// Lógicas de validação e interatividade para formulários públicos e de cadastro/criação de usuário.

document.addEventListener('DOMContentLoaded', () => {

    // BASE_URL_JS é definida globalmente em includes/header_public.php.
    // Use-a diretamente em todas as chamadas fetch.
    if (typeof BASE_URL_JS === 'undefined') {
        console.error("Erro: BASE_URL_JS não está definida. Verifique includes/header_public.php.");
        window.BASE_URL_JS = '/'; // Fallback defensivo para evitar quebrar o JS
    }

    // ===============================================
    // FUNÇÕES AUXILIARES GLOBAIS (PARA TODOS OS FORMULÁRIOS QUE AS USAM)
    // ===============================================

    // --- Funções de Máscara ---
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

    // NOVO: Máscara para CNPJ
    function applyCnpjMask(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 14) value = value.slice(0, 14);
            let maskedValue = value.replace(/^(\d{2})(\d)/, '$1.$2');
            maskedValue = maskedValue.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            maskedValue = maskedValue.replace(/\.(\d{3})(\d)/, '.$1/$2');
            maskedValue = maskedValue.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = maskedValue;
        });
    }

    // NOVO: Máscara para Telefone (pode ser adaptada para fixo/celular)
    function applyPhoneMask(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            let maskedValue = '';
            // (XX) XXXX-XXXX ou (XX) XXXXX-XXXX
            if (value.length > 10) { // Celular 9 dígitos
                maskedValue = value.replace(/^(\d\d)(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 6) { // Fixo 8 dígitos
                maskedValue = value.replace(/^(\d\d)(\d{4})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                maskedValue = value.replace(/^(\d*)/, '($1');
            } else {
                maskedValue = value.replace(/^(\d*)/, '($1');
            }
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
                maskedValue = value.replace(/^(\d*)/, '($1');
            } else {
                maskedValue = value.replace(/^(\d*)/, '($1');
            }
            e.target.value = maskedValue;
        });
    }

    // --- Lógica para alternar visibilidade da senha (para formulários públicos) ---
    document.querySelectorAll('.toggle-password-visibility').forEach(button => {
        button.addEventListener('click', () => {
            const passwordInput = button.previousElementSibling;
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            button.querySelector('i').classList.toggle('fa-eye');
            button.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    // --- Funções auxiliares de validação (AGORA NO ESCOPO GLOBAL DE forms.js) ---
    function displayValidationMessage(element, isValid, message) {
        if (element) {
            element.textContent = message;
            element.style.color = isValid ? 'green' : 'red';
            element.style.display = message ? 'block' : 'none';
        }
    }

    // E-mail (Correspondência e Unicidade)
    let emailUniqueTimeout; // Declarado aqui para ser acessível por checkEmailUniqueness
    function checkEmailsMatch(emailInput, confirmEmailInput, emailMatchStatus, confirmEmailMatchStatus) {
        if (emailInput.value !== confirmEmailInput.value && confirmEmailInput.value !== '') {
            displayValidationMessage(confirmEmailMatchStatus, false, 'E-mails não coincidem!');
            displayValidationMessage(emailMatchStatus, false, 'E-mails não coincidem!');
        } else if (emailInput.value === confirmEmailInput.value && emailInput.value !== '') {
            displayValidationMessage(confirmEmailMatchStatus, true, 'E-mails coincidem.');
            displayValidationMessage(emailMatchStatus, true, 'E-mails coincidem.');
        } else {
            displayValidationMessage(confirmEmailMatchStatus, true, '');
            displayValidationMessage(emailMatchStatus, true, '');
        }
    }

    function checkEmailUniqueness(emailInput, emailUniqueStatus, currentId = null, entityType = 'usuarios') {
        clearTimeout(emailUniqueTimeout);
        emailUniqueTimeout = setTimeout(() => {
            const email = emailInput.value;
            if (email.length > 0 && email.includes('@') && email.includes('.')) {
                let url = `${BASE_URL_JS}api/check_uniqueness.php?field=email&value=${encodeURIComponent(email)}&entity_type=${entityType}`;
                if (currentId) {
                    url += `&current_id=${currentId}`;
                }
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.is_unique) {
                            displayValidationMessage(emailUniqueStatus, false, 'E-mail já cadastrado!');
                            emailInput.setCustomValidity('E-mail já cadastrado');
                        } else {
                            displayValidationMessage(emailUniqueStatus, true, 'E-mail disponível.');
                            emailInput.setCustomValidity('');
                        }
                    })
                    .catch(error => { console.error('Erro na verificação de e-mail:', error); emailInput.setCustomValidity('Erro na verificação de e-mail.'); });
            } else {
                displayValidationMessage(emailUniqueStatus, true, '');
                emailInput.setCustomValidity('');
            }
        }, 500);
    }

    // Senha (Força e Correspondência)
    function checkPasswordStrength(passwordInput, passwordStrength, passwordRules) {
        const password = passwordInput.value;
        let score = 0;
        let message = '';
        let isValid = true;
        
        const minLength = 6;
        const hasLowercase = /[a-z]/.test(password);
        const hasUppercase = /[A-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[^a-zA-Z0-9]/.test(password);

        if (password.length >= minLength) score++;
        if (hasLowercase) score++;
        if (hasUppercase) score++;
        if (hasNumber) score++;
        if (hasSpecial) score++;

        if(passwordRules) {
            passwordRules.querySelector('li:nth-child(1)').style.color = (password.length >= minLength) ? 'green' : 'red';
            passwordRules.querySelector('li:nth-child(2)').style.color = hasUppercase ? 'green' : 'red';
            passwordRules.querySelector('li:nth-child(3)').style.color = hasLowercase ? 'green' : 'red';
            passwordRules.querySelector('li:nth-child(4)').style.color = hasNumber ? 'green' : 'red';
            passwordRules.querySelector('li:nth-child(5)').style.color = hasSpecial ? 'green' : 'red';
        }

        if (password.length === 0) {
            message = ''; isValid = true;
        } else if (password.length < minLength) {
            message = `Mínimo ${minLength} caracteres.`; isValid = false;
        } else if (score < 3) {
            message = 'Senha fraca.'; isValid = false;
        } else if (score < 5) {
            message = 'Senha moderada.'; isValid = true;
        } else {
            message = 'Senha forte.'; isValid = true;
        }
        displayValidationMessage(passwordStrength, isValid, message);
        
        passwordRules.style.display = (password.length > 0 && score < 5) ? 'block' : 'none'; // Mostra se tem texto E não é forte
    }

    function checkPasswordsMatch(passwordInput, confirmPasswordInput, confirmPasswordMatchStatus) {
        if (passwordInput.value !== confirmPasswordInput.value && confirmPasswordInput.value !== '') {
            displayValidationMessage(confirmPasswordMatchStatus, false, 'Senhas não coincidem!');
        } else if (passwordInput.value === confirmPasswordInput.value && passwordInput.value !== '') {
            displayValidationMessage(confirmPasswordMatchStatus, true, 'Senhas coincidem.');
        } else {
            displayValidationMessage(confirmPasswordMatchStatus, true, '');
        }
    }

    // CPF (Unicidade via AJAX)
    let cpfUniqueTimeout;
    function checkCpfUniqueness(cpfInput, cpfUniqueStatus, selectedType, currentId = null, entityType = 'usuarios') {
        clearTimeout(cpfUniqueTimeout);
        cpfUniqueTimeout = setTimeout(() => {
            const cpf = cpfInput.value.replace(/\D/g, '');
            
            if (cpf.length === 11 && (selectedType === 'corretor_autonomo' || selectedType === 'corretor_imobiliaria')) {
                let url = `${BASE_URL_JS}api/check_uniqueness.php?field=cpf&value=${encodeURIComponent(cpf)}&entity_type=${entityType}`;
                if (currentId) {
                    url += `&current_id=${currentId}`;
                }
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.is_unique) {
                            displayValidationMessage(cpfUniqueStatus, false, 'CPF já cadastrado!');
                            cpfInput.setCustomValidity('CPF já cadastrado');
                        } else {
                            displayValidationMessage(cpfUniqueStatus, true, 'CPF disponível.');
                            cpfInput.setCustomValidity('');
                        }
                    })
                    .catch(error => { console.error('Erro na verificação de CPF:', error); cpfInput.setCustomValidity('Erro na verificação de CPF.'); });
            } else {
                displayValidationMessage(cpfUniqueStatus, true, '');
                cpfInput.setCustomValidity('');
            }
        }, 500);
    }

    // NOVO: CNPJ (Unicidade via AJAX)
    let cnpjUniqueTimeout;
    function checkCnpjUniqueness(cnpjInput, cnpjUniqueStatus, currentId = null, entityType = 'imobiliarias') {
        clearTimeout(cnpjUniqueTimeout);
        cnpjUniqueTimeout = setTimeout(() => {
            const cnpj = cnpjInput.value.replace(/\D/g, '');
            
            if (cnpj.length === 14) {
                let url = `${BASE_URL_JS}api/check_uniqueness.php?field=cnpj&value=${encodeURIComponent(cnpj)}&entity_type=${entityType}`;
                if (currentId) {
                    url += `&current_id=${currentId}`;
                }
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.is_unique) {
                            displayValidationMessage(cnpjUniqueStatus, false, 'CNPJ já cadastrado!');
                            cnpjInput.setCustomValidity('CNPJ já cadastrado');
                        } else {
                            displayValidationMessage(cnpjUniqueStatus, true, 'CNPJ disponível.');
                            cnpjInput.setCustomValidity('');
                        }
                    })
                    .catch(error => { console.error('Erro na verificação de CNPJ:', error); cnpjInput.setCustomValidity('Erro na verificação de CNPJ.'); });
            } else {
                displayValidationMessage(cnpjUniqueStatus, true, '');
                cnpjInput.setCustomValidity('');
            }
        }, 500);
    }


    // ===============================================
    // LÓGICA ESPECÍFICA DO FORMULÁRIO DE CADASTRO PÚBLICO (auth/cadastro.php)
    // ===============================================
    const cadastroForm = document.getElementById('cadastro-form');
    if (cadastroForm) {
        // --- Elementos do Formulário ---
        const tipoUsuarioRadios = document.querySelectorAll('input[name="tipo"]');
        const imobiliariaSelectGroup = document.getElementById('imobiliaria_select_group');
        const imobiliariaSelect = document.getElementById('imobiliaria_id');
        const corretorInfoGroup = document.getElementById('corretor_info_group');
        const creciGroup = document.getElementById('creci_group');
        const creciInput = document.getElementById('creci');

        const emailInput = document.getElementById('email');
        const confirmEmailInput = document.getElementById('confirmar_email');
        const emailMatchStatus = document.getElementById('email_match_status');
        const emailUniqueStatus = document.getElementById('email_unique_status');

        const passwordInput = document.getElementById('senha');
        const confirmPasswordInput = document.getElementById('confirmar_senha');
        const passwordStrength = document.getElementById('password_strength');
        const passwordRules = document.getElementById('password_rules');
        const confirmPasswordMatchStatus = document.getElementById('confirm_password_match_status');

        const cpfInput = document.getElementById('cpf');
        const cpfUniqueStatus = document.getElementById('cpf_unique_status');
        const telefoneInput = document.getElementById('telefone'); // Declarado aqui


        // --- Lógica de Visibilidade de Campos (Radio Buttons) ---
        function toggleFormFieldsBasedOnUserTypeWrapper() {
            const selectedType = document.querySelector('input[name="tipo"]:checked')?.value;

            if (selectedType === 'corretor_imobiliaria') {
                imobiliariaSelectGroup.style.display = 'block';
                imobiliariaSelect.setAttribute('required', 'required');
            } else {
                imobiliariaSelectGroup.style.display = 'none';
                imobiliariaSelect.removeAttribute('required');
                imobiliariaSelect.value = '';
            }

            if (selectedType === 'corretor_autonomo' || selectedType === 'corretor_imobiliaria') {
                corretorInfoGroup.style.display = 'flex';
                cpfInput.setAttribute('required', 'required');
                creciGroup.style.display = 'block';
                creciInput.setAttribute('required', 'required');
            } else {
                corretorInfoGroup.style.display = 'none';
                cpfInput.removeAttribute('required');
                cpfInput.value = '';

                creciGroup.style.display = 'none';
                creciInput.removeAttribute('required');
                creciInput.value = '';
            }
        }

        // --- Atribuição de Listeners ---
        tipoUsuarioRadios.forEach(radio => radio.addEventListener('change', toggleFormFieldsBasedOnUserTypeWrapper));
        toggleFormFieldsBasedOnUserTypeWrapper(); // Chama na carga inicial

        emailInput.addEventListener('input', () => { checkEmailsMatch(emailInput, confirmEmailInput, emailMatchStatus, confirmEmailMatchStatus); checkEmailUniqueness(emailInput, emailUniqueStatus); });
        confirmEmailInput.addEventListener('input', () => { checkEmailsMatch(emailInput, confirmEmailInput, emailMatchStatus, confirmEmailMatchStatus); });

        passwordInput.addEventListener('input', () => { checkPasswordStrength(passwordInput, passwordStrength, passwordRules); checkPasswordsMatch(passwordInput, confirmPasswordInput, confirmPasswordMatchStatus); });
        confirmPasswordInput.addEventListener('input', () => { checkPasswordsMatch(passwordInput, confirmPasswordInput, confirmPasswordMatchStatus); });

        cpfInput.addEventListener('input', () => { checkCpfUniqueness(cpfInput, cpfUniqueStatus, document.querySelector('input[name="tipo"]:checked')?.value); });

        if (cpfInput) applyCpfMask(cpfInput);
        if (telefoneInput) applyWhatsappMask(telefoneInput);
    }


    // ===============================================
    // LÓGICA ESPECÍFICA DO FORMULÁRIO DE CRIAÇÃO DE CORRETOR (imobiliaria/corretores/criar.php)
    // ===============================================
    const criarCorretorForm = document.getElementById('criar-corretor-form'); // ID do formulário
    if (criarCorretorForm) {
        // Elementos do formulário (similares ao cadastro público)
        const tipoCorretorRadios = document.querySelectorAll('input[name="tipo"]'); // Radio buttons
        const imobiliariaInfoGroup = document.getElementById('imobiliaria_info_group');
        const nomeCorretorInput = document.getElementById('nome');
        const emailInput = document.getElementById('email');
        const confirmEmailInput = document.getElementById('confirmar_email');
        const emailMatchStatus = document.getElementById('email_match_status');
        const emailUniqueStatus = document.getElementById('email_unique_status');

        const passwordInput = document.getElementById('senha');
        const confirmPasswordInput = document.getElementById('confirmar_senha');
        const passwordStrength = document.getElementById('password_strength');
        const passwordRules = document.getElementById('password_rules');
        const confirmPasswordMatchStatus = document.getElementById('confirm_password_match_status');

        const cpfInput = document.getElementById('cpf');
        const creciInput = document.getElementById('creci');
        const telefoneInput = document.getElementById('telefone');
        const corretorInfoGroup = document.getElementById('corretor_info_group');
        const creciGroup = document.getElementById('creci_group');


        // --- Lógica de Visibilidade de Campos (Radio Buttons para Criar Corretor) ---
        function toggleCorretorFormFieldsBasedOnUserType() {
            const selectedType = document.querySelector('input[name="tipo"]:checked')?.value;

            if (selectedType === 'corretor_imobiliaria') {
                imobiliariaInfoGroup.style.display = 'block';
            } else {
                imobiliariaInfoGroup.style.display = 'none';
            }
            
            // CPF e CRECI são sempre obrigatórios para corretores aqui
            corretorInfoGroup.style.display = 'flex';
            cpfInput.setAttribute('required', 'required');
            creciGroup.style.display = 'block';
            creciInput.setAttribute('required', 'required');
        }


        // --- Atribuição de Listeners para Criar Corretor ---
        tipoCorretorRadios.forEach(radio => radio.addEventListener('change', toggleCorretorFormFieldsBasedOnUserType));
        toggleCorretorFormFieldsBasedOnUserType(); // Define estado inicial

        emailInput.addEventListener('input', () => { checkEmailsMatch(emailInput, confirmEmailInput, emailMatchStatus, emailUniqueStatus); checkEmailUniqueness(emailInput, emailUniqueStatus); });
        confirmEmailInput.addEventListener('input', () => { checkEmailsMatch(emailInput, confirmEmailInput, emailMatchStatus, emailUniqueStatus); });

        passwordInput.addEventListener('input', () => { checkPasswordStrength(passwordInput, passwordStrength, passwordRules); checkPasswordsMatch(passwordInput, confirmPasswordInput, confirmPasswordMatchStatus); });
        confirmPasswordInput.addEventListener('input', () => { checkPasswordsMatch(passwordInput, confirmPasswordInput, confirmPasswordMatchStatus); });

        cpfInput.addEventListener('input', () => { checkCpfUniqueness(cpfInput, cpfUniqueStatus, document.querySelector('input[name="tipo"]:checked')?.value); });

        if (cpfInput) applyCpfMask(cpfInput);
        if (telefoneInput) applyWhatsappMask(telefoneInput);
    }

    // --- NOVO: Lógica para aplicar máscaras a campos genéricos no carregamento da página ---
    // Isso garante que campos em editar.php e criar.php (que usam forms.js) também recebam máscaras.
    document.querySelectorAll('.mask-cpf').forEach(input => applyCpfMask(input));
    document.querySelectorAll('.mask-cnpj').forEach(input => applyCnpjMask(input)); // Adicionado CNPJ
    document.querySelectorAll('.mask-telefone').forEach(input => applyPhoneMask(input)); // Adicionado Telefone
    document.querySelectorAll('.mask-whatsapp').forEach(input => applyWhatsappMask(input));
    document.querySelectorAll('.mask-cep').forEach(input => applyMask(input, '#####-###')); // Já existia, garantindo
});