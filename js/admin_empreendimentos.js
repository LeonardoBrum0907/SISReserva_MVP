// js/admin_empreendimentos.js - Lógicas para o Formulário Único de Empreendimentos (VERSÃO CONSOLIDADA E FINAL)
(function() { // Wrap the entire script in an IIFE
    if (window.adminEmpreendimentosLoaded) {
        console.log("admin_empreendimentos.js: Script já carregado, evitando execução duplicada.");
        return;
    }
    window.adminEmpreendimentosLoaded = true; // Mark that the script has loaded
    console.log("admin_empreendimentos.js: DOMContentLoaded - Script iniciado para formulário único.");

    // --- VARIÁVEIS DO WIZARD (DECLARADAS APENAS UMA VEZ AQUI NO TOPO) ---
    const form = document.getElementById('empreendimento-wizard-form');
    if (!form) {
        console.log("admin_empreendimentos.js: Formulário 'empreendimento-wizard-form' não encontrado. Encerrando script para evitar erros.");
        return;
    }
    const steps = document.querySelectorAll('.step-section');
    const stepCircles = document.querySelectorAll('.wizard-step');
    const prevBtn = document.getElementById('prev-step-btn');
    const nextBtn = document.getElementById('next-step-btn');
    const submitBtn = document.getElementById('submit-wizard-btn');
    const empreendimentoIdInput = document.getElementById('empreendimento_id');
    const currentStepInput = document.getElementById('current_step_input');

    // --- GLOBAL HELPER FUNCTIONS (INCLUDED INLINE FOR ROBUSTNESS) ---
    function showMessage(type, message) {
        const messageBox = document.createElement('div');
        messageBox.classList.add('message-box');
        messageBox.classList.add(`message-box-${type}`);
        messageBox.innerHTML = `<p>${message}</p>`;
        // Remove qualquer message-box existente do mesmo tipo antes de adicionar um novo
        document.querySelectorAll(`.message-box-${type}`).forEach(box => box.remove());
        form.prepend(messageBox); // Adiciona antes do formulário
        setTimeout(() => messageBox.remove(), 5000); // Remove após 5 segundos
    }

    function showErrors(errors) {
        if (!errors || Object.keys(errors).length === 0) return;
        
        let errorMessage = 'Por favor, corrija os seguintes erros:<ul>';
        Object.keys(errors).forEach(key => {
            errorMessage += `<li>${errors[key]}</li>`;
            const errorElement = document.getElementById(`error-${key}`);
            if (errorElement) {
                errorElement.textContent = errors[key];
                errorElement.style.display = 'block';
                // Adiciona uma classe para destacar o campo com erro
                const inputElement = document.getElementById(key);
                if (inputElement) inputElement.classList.add('input-error');
            }
        });
        errorMessage += '</ul>';
        showMessage('error', errorMessage);
    }

    function clearErrors() {
        document.querySelectorAll('.error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        document.querySelectorAll('.message-box-error').forEach(el => el.remove());
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function formatCurrency(value) {
        if (typeof value !== 'number' || isNaN(value)) {
            return 'R$ 0,00';
        }
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    }

    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatNumber(value, decimals = 0) {
        if (typeof value !== 'number' || isNaN(value)) {
            return '0';
        }
        return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value);
    }

    // --- WIZARD NAVIGATION AND STEP MANAGEMENT ---
    let currentStep = window.currentStep || 1; // Start from PHP passed current step

    function showStep(stepNumber) {
        clearErrors();
        steps.forEach((step, index) => {
            if (index + 1 === stepNumber) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });

        stepCircles.forEach((circle, index) => {
            if (index + 1 === stepNumber) {
                circle.classList.add('active');
            } else {
                circle.classList.remove('active');
            }
            if (index + 1 < stepNumber) {
                circle.classList.add('completed');
            } else {
                circle.classList.remove('completed');
            }
        });

        currentStep = stepNumber;
        currentStepInput.value = stepNumber; // Update hidden input for PHP

        // Update button visibility
        prevBtn.style.display = stepNumber === 1 ? 'none' : 'inline-block';
        nextBtn.style.display = stepNumber === steps.length ? 'none' : 'inline-block';
        submitBtn.style.display = stepNumber === steps.length ? 'inline-block' : 'none';

        // Initialize step-specific logic
        initializeStep(stepNumber);
    }

    // --- STEP-SPECIFIC INITIALIZATION ---
    function initializeStep(stepNumber) {
        // Assegura que todos os listeners antigos sejam removidos para evitar duplicações
        // ou que os listeners sejam adicionados de forma idempotente.
        // Uma abordagem mais robusta seria usar `removeEventListener` antes de `addEventListener`
        // ou usar um sistema de delegação de eventos para elementos dinâmicos.
        // Para este contexto, tentamos garantir listeners únicos com flags ou verificação de existência.

        switch (stepNumber) {
            case 1:
                initStep1();
                break;
            case 2:
                initStep2();
                break;
            case 3:
                initStep3();
                break;
            case 4:
                initStep4();
                break;
            case 5:
                initStep5();
                break;
            case 6:
                initStep6();
                break;
            case 7:
                initStep7();
                break;
        }
    }

    // Placeholder init functions (to be filled or already exist)
    function initStep1() {
        // Lógica para mostrar/esconder documentos obrigatórios
        const momentoEnvioDocSelect = document.getElementById('momento_envio_documentacao');
        const documentosObrigatoriosGroup = document.getElementById('documentos_obrigatorios_group');

        function toggleDocumentosObrigatorios() {
            if (momentoEnvioDocSelect.value === 'Na Proposta de Reserva') {
                documentosObrigatoriosGroup.style.display = 'block';
                document.getElementById('documentos_obrigatorios').setAttribute('required', 'true');
            } else {
                documentosObrigatoriosGroup.style.display = 'none';
                document.getElementById('documentos_obrigatorios').removeAttribute('required');
            }
        }

        // Garante que o listener seja adicionado apenas uma vez
        if (!momentoEnvioDocSelect.dataset.listenerAdded) {
            momentoEnvioDocSelect.addEventListener('change', toggleDocumentosObrigatorios);
            momentoEnvioDocSelect.dataset.listenerAdded = 'true';
        }
        toggleDocumentosObrigatorios(); // Chama na inicialização para estado inicial
    }
    
    function initStep2() {
        const addTipoUnidadeBtn = document.getElementById('add_tipo_unidade');
        const tiposUnidadesContainer = document.getElementById('tipos_unidades_container').querySelector('tbody');

        // Function to create a new type unit row
        function createTipoUnidadeRow(data = {}) {
            const newRow = tiposUnidadesContainer.insertRow();
            newRow.classList.add('tipo-unidade-item-row');
            const rowIndex = tiposUnidadesContainer.rows.length; // Use current length for a unique index if needed for file input names

            // Ensure unique name for file input if backend expects it, e.g. "tipos_unidade_fotos_0", "tipos_unidade_fotos_1"
            // The backend expects 'tipos_unidade_fotos_{index}', so we need to pass a unique index
            // Data passed to PHP for files is usually via FormData.
            // For now, the existing 'tipos_unidade_fotos_{rowIndex}' is kept.
            // If the user wants to upload new files dynamically, a separate upload mechanism should be used.
            // For this wizard, the assumption is that the file input name needs to be unique for the server.
            // However, the `salvar_etapa2.php` expects `foto_planta` directly in the JSON,
            // so this file input's primary purpose is for preview. The actual upload logic (if any)
            // would need to happen before this step is submitted, and the resulting URL/path
            // would be put into `data.foto_planta`.
            // Given the complexity, we'll keep the input name unique for robustness,
            // but rely on `salvar_etapa2.php` fetching `foto_planta` from JSON.

            const fotoPlantaPreview = data.foto_planta ? `<small>Atual: <img src="${window.BASE_URL}${htmlspecialchars(data.foto_planta)}" style="max-width: 50px; vertical-align: middle; margin-left: 5px;"></small>` : '';

            newRow.innerHTML = `
                <td><input type="hidden" name="tipos_unidade[id][]" value="${data.id || ''}"><input type="text" name="tipos_unidade[tipo][]" value="${htmlspecialchars(data.tipo || '')}" required class="form-control"></td>
                <td><input type="number" step="0.01" name="tipos_unidade[metragem][]" value="${htmlspecialchars(data.metragem || '')}" required class="form-control"></td>
                <td><input type="number" min="0" name="tipos_unidade[quartos][]" value="${htmlspecialchars(data.quartos || '')}" required class="form-control"></td>
                <td><input type="number" min="0" name="tipos_unidade[banheiros][]" value="${htmlspecialchars(data.banheiros || '')}" required class="form-control"></td>
                <td><input type="number" min="0" name="tipos_unidade[vagas][]" value="${htmlspecialchars(data.vagas || '')}" required class="form-control"></td>
                <td>
                    <input type="file" name="tipos_unidade_fotos_${rowIndex}" accept="image/*" class="form-control-file">
                    <input type="hidden" name="tipos_unidade[foto_planta][]" value="${htmlspecialchars(data.foto_planta || '')}" class="existing-foto-planta">
                    ${fotoPlantaPreview}
                </td>
                <td><button type="button" class="btn btn-danger btn-sm remove-tipo-unidade"><i class="fas fa-trash"></i></button></td>
            `;

            newRow.querySelector('.remove-tipo-unidade').addEventListener('click', function() {
                newRow.remove();
            });
            // This is a simplified approach. For true dynamic file uploads,
            // each file input would need its own AJAX upload handler,
            // and the returned URL would update the hidden 'foto_planta' input.
            // For now, relies on the backend to handle the `_FILES` array directly if it can.
            // The current `salvar_etapa2.php` expects `foto_planta` directly in the JSON.
            // So this file input is primarily for user selection/preview.
            // To make this fully functional for *new uploads*, you'd need a separate upload API.
            newRow.querySelector('input[type="file"]').addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    // This is where you would typically upload the file via AJAX
                    // and then update the hidden input with the returned path.
                    // For now, just a visual confirmation of selection.
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.maxWidth = '50px';
                        img.style.marginLeft = '5px';
                        img.style.verticalAlign = 'middle';
                        const existingPreview = newRow.querySelector('small img');
                        if (existingPreview) existingPreview.remove();
                        newRow.querySelector('small')?.remove(); // Remove old text preview
                        event.target.parentNode.append(img);
                    };
                    reader.readAsDataURL(file);
                    // Crucial: for client-side only, you would store the temporary URL
                    // but for persisting, you need a server upload first.
                    // For now, the existing hidden input will keep old value until replaced by server response logic.
                    // THIS IS A KNOWN LIMITATION WITHOUT DEDICATED AJAX UPLOAD PER FILE INPUT.
                }
            });
        }

        // Populate existing types on load
        if (window.tiposUnidadesData && window.tiposUnidadesData.length > 0) {
            tiposUnidadesContainer.innerHTML = ''; // Clear any placeholder row
            window.tiposUnidadesData.forEach(type => createTipoUnidadeRow(type));
        } else {
            // Add a default row if no data exists
            if (tiposUnidadesContainer.rows.length === 0) {
                createTipoUnidadeRow();
            }
        }

        // Ensure listener is added only once
        if (!addTipoUnidadeBtn.dataset.listenerAdded) {
            addTipoUnidadeBtn.addEventListener('click', () => createTipoUnidadeRow());
            addTipoUnidadeBtn.dataset.listenerAdded = 'true';
        }
    }

    function initStep3() {
        // Nothing complex for JS here, checkboxes are static in PHP.
        // If there were dynamic additions, they would go here.
    }

    function initStep4() {
        const andarInput = document.getElementById('andar');
        const unidadesPorAndarContainer = document.getElementById('unidades_por_andar_container');
        const generateUnitsBtn = document.getElementById('generate_units_btn');
        const unitsStockTbody = document.getElementById('units_stock_tbody');
        const addUnitStockRowManualBtn = document.getElementById('add_unit_stock_row_manual');
        const batchValueInput = document.getElementById('batch_value');
        const applyBatchValueBtn = document.getElementById('apply_batch_value');
        const batchTipoUnidadeSelect = document.getElementById('batch_tipo_unidade_id');
        const applyBatchTypeBtn = document.getElementById('apply_batch_type');
        const displayPrecoM2Sugerido = document.getElementById('display-preco-m2-sugerido');

        // Update display of suggested price
        if (window.empreendimentoData.preco_por_m2_sugerido) {
            displayPrecoM2Sugerido.textContent = formatCurrency(parseFloat(window.empreendimentoData.preco_por_m2_sugerido));
        }
        
        // Populate batch_tipo_unidade_id select
        batchTipoUnidadeSelect.innerHTML = '<option value="">-- Selecione um Tipo --</option>';
        if (window.tiposUnidadesData && window.tiposUnidadesData.length > 0) {
            window.tiposUnidadesData.forEach((type, index) => {
                const option = document.createElement('option');
                option.value = index; // Use index as value for frontend mapping
                option.dataset.tipoUnidadeId = type.id; // Store actual DB ID
                option.textContent = `${htmlspecialchars(type.tipo)} (${htmlspecialchars(formatNumber(type.metragem))}m²)`;
                batchTipoUnidadeSelect.appendChild(option);
            });
        }

        function generateUnidadesPorAndarInputs() {
            unidadesPorAndarContainer.innerHTML = '';
            const numAndares = parseInt(andarInput.value);
            if (numAndares > 0) {
                for (let i = 1; i <= numAndares; i++) {
                    const div = document.createElement('div');
                    div.classList.add('form-group');
                    div.innerHTML = `
                        <label for="unidades_por_andar_${i}">${i}º Andar - Quantidade de Unidades:</label>
                        <input type="number" id="unidades_por_andar_${i}" name="unidades_por_andar[${i}]" min="0" value="${window.unidadesPorAndarData[i] || 0}" class="form-control">
                    `;
                    unidadesPorAndarContainer.appendChild(div);
                }
            }
        }
        // Ensure listener is added only once
        if (!andarInput.dataset.listenerAdded) {
            andarInput.addEventListener('change', generateUnidadesPorAndarInputs);
            andarInput.dataset.listenerAdded = 'true';
        }
        generateUnidadesPorAndarInputs(); // Call on init

        // Function to create a new unit stock row
        function createUnitStockRow(data = {}) {
            const newRow = unitsStockTbody.insertRow();
            newRow.classList.add('unit-stock-row');
            newRow.innerHTML = `
                <td>
                    <input type="hidden" name="unidades_estoque[id][]" value="${data.id || ''}">
                    <select name="unidades_estoque[tipo_unidade_id][]" required class="form-control unit-stock-tipo-select">
                        <option value="">Selecione um Tipo</option>
                        ${window.tiposUnidadesData.map((type, index) => `<option value="${index}" ${data.tipo_unidade_id === type.id ? 'selected' : ''}>${htmlspecialchars(type.tipo)} (${htmlspecialchars(formatNumber(type.metragem))}m²)</option>`).join('')}
                    </select>
                </td>
                <td><input type="text" name="unidades_estoque[numero][]" value="${htmlspecialchars(data.numero || '')}" required class="form-control"></td>
                <td><input type="number" name="unidades_estoque[andar][]" value="${htmlspecialchars(data.andar || '')}" required class="form-control"></td>
                <td><input type="text" name="unidades_estoque[posicao][]" value="${htmlspecialchars(data.posicao || '')}" class="form-control"></td>
                <td><input type="number" step="0.01" name="unidades_estoque[area][]" value="${htmlspecialchars(data.area || '')}" required class="form-control"></td>
                <td><input type="number" step="0.01" name="unidades_estoque[multiplier][]" value="${htmlspecialchars(data.multiplier || 1)}" required class="form-control"></td>
                <td class="suggested-value-display">${formatCurrency(0)}</td>
                <td><input type="text" name="unidades_estoque[valor][]" value="${htmlspecialchars(data.valor || '')}" required class="form-control currency-mask unit-final-value"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-unit-stock-item"><i class="fas fa-trash"></i></button></td>
            `;

            newRow.querySelector('.remove-unit-stock-item').addEventListener('click', () => newRow.remove());

            // Add event listeners to update suggested value based on multiplier and m2
            const areaInput = newRow.querySelector('input[name="unidades_estoque[area][]"]');
            const multiplierInput = newRow.querySelector('input[name="unidades_estoque[multiplier][]"]');
            const suggestedValueDisplay = newRow.querySelector('.suggested-value-display');
            const finalValueInput = newRow.querySelector('.unit-final-value');

            function updateSuggestedValue() {
                const area = parseFloat(areaInput.value || 0);
                const multiplier = parseFloat(multiplierInput.value || 1);
                const precoM2 = parseFloat(window.empreendimentoData.preco_por_m2_sugerido || 0);
                let suggestedValue = area * precoM2 * multiplier;
                suggestedValueDisplay.textContent = formatCurrency(suggestedValue);
                // If final value is empty, pre-fill with suggested
                if (!finalValueInput.value || parseFloat(finalValueInput.value.replace(/\./g, '').replace(',', '.')) === 0) {
                    finalValueInput.value = formatCurrency(suggestedValue).replace('R$', '').trim();
                }
            }
            // Ensure listeners are added only once
            if (!areaInput.dataset.listenerAdded) {
                areaInput.addEventListener('input', updateSuggestedValue);
                areaInput.dataset.listenerAdded = 'true';
            }
            if (!multiplierInput.dataset.listenerAddedForArea) { // Use different dataset key
                multiplierInput.addEventListener('input', updateSuggestedValue);
                multiplierInput.dataset.listenerAddedForArea = 'true';
            }
            updateSuggestedValue(); // Call on init for existing rows
        }

        // Populate existing units on load
        if (window.unidadesEstoqueData && window.unidadesEstoqueData.length > 0) {
            unitsStockTbody.innerHTML = ''; // Clear any placeholder row
            window.unidadesEstoqueData.forEach(unit => createUnitStockRow(unit));
        }

        // Ensure listener is added only once
        if (!generateUnitsBtn.dataset.listenerAdded) {
            generateUnitsBtn.addEventListener('click', function() {
                unitsStockTbody.innerHTML = ''; // Clear current units
                const numAndares = parseInt(andarInput.value);
                const unidadesPorAndar = {};
                document.querySelectorAll('#unidades_por_andar_container input').forEach(input => {
                    unidadesPorAndar[parseInt(input.id.split('_').pop())] = parseInt(input.value);
                });

                if (numAndares > 0 && !isNaN(numAndares)) {
                    for (let andar = 1; andar <= numAndares; andar++) {
                        const numUnidadesNesteAndar = unidadesPorAndar[andar] || 0;
                        for (let pos = 1; pos <= numUnidadesNesteAndar; pos++) {
                            const unitNumber = `${andar}${String(pos).padStart(2, '0')}`;
                            createUnitStockRow({
                                numero: unitNumber,
                                andar: andar,
                                posicao: String(pos).padStart(2, '0'),
                                area: '', // User will fill
                                multiplier: 1, // Default
                                valor: '' // User will fill or batch apply
                            });
                        }
                    }
                } else {
                    showMessage('error', 'Número de andares inválido ou não definido para gerar unidades.');
                }
            });
            generateUnitsBtn.dataset.listenerAdded = 'true';
        }

        // Add unit manually
        if (!addUnitStockRowManualBtn.dataset.listenerAdded) {
            addUnitStockRowManualBtn.addEventListener('click', () => createUnitStockRow());
            addUnitStockRowManualBtn.dataset.listenerAdded = 'true';
        }

        // Batch apply value
        if (!applyBatchValueBtn.dataset.listenerAdded) {
            applyBatchValueBtn.addEventListener('click', function() {
                const value = parseFloat(batchValueInput.value.replace(/\./g, '').replace(',', '.') || 0);
                if (isNaN(value) || value <= 0) {
                    showMessage('error', 'Por favor, insira um valor válido para aplicar em lote.');
                    return;
                }
                document.querySelectorAll('.unit-final-value').forEach(input => {
                    input.value = formatCurrency(value).replace('R$', '').trim(); // Format for input
                });
            });
            applyBatchValueBtn.dataset.listenerAdded = 'true';
        }

        // Batch apply type
        if (!applyBatchTypeBtn.dataset.listenerAdded) {
            applyBatchTypeBtn.addEventListener('click', function() {
                const selectedIndex = batchTipoUnidadeSelect.value;
                if (selectedIndex === '') {
                    showMessage('error', 'Por favor, selecione um tipo de planta para aplicar em lote.');
                    return;
                }
                document.querySelectorAll('.unit-stock-tipo-select').forEach(select => {
                    select.value = selectedIndex;
                });
            });
            applyBatchTypeBtn.dataset.listenerAdded = 'true';
        }
    }

    function initStep5() {
        // Função para exibir previews de arquivos existentes
        function displayExistingFilePreview(container, filePath, isImage = true, fileType = '') {
            if (filePath && container) {
                const previewItem = document.createElement('div');
                previewItem.classList.add('file-preview-item');
                
                if (isImage) {
                    const img = document.createElement('img');
                    img.src = `${window.BASE_URL}${htmlspecialchars(filePath)}`;
                    img.classList.add('img-preview');
                    img.alt = 'Preview';
                    previewItem.appendChild(img);
                } else {
                    const link = document.createElement('a');
                    link.href = `${window.BASE_URL}${htmlspecialchars(filePath)}`;
                    link.target = '_blank';
                    link.textContent = htmlspecialchars(filePath.split('/').pop());
                    previewItem.appendChild(link);
                }
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.classList.add('btn', 'btn-danger', 'btn-sm', 'remove-file-preview');
                removeBtn.dataset.filepath = htmlspecialchars(filePath);
                removeBtn.dataset.filetype = htmlspecialchars(fileType); // Store type for backend filtering
                removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
                removeBtn.addEventListener('click', function() {
                    previewItem.remove();
                    // Mark for deletion in hidden structure or global list to be sent to backend
                    // For now, rely on backend to compare what's sent in JSON vs what's in DB
                });
                previewItem.appendChild(removeBtn);
                container.appendChild(previewItem);
            }
        }

        // Clear existing previews before populating to avoid duplicates on re-init
        const fotoPrincipalPreview = document.getElementById('foto_principal_preview');
        if (fotoPrincipalPreview) fotoPrincipalPreview.innerHTML = '';
        const galeriaFotosPreview = document.getElementById('galeria_fotos_preview');
        if (galeriaFotosPreview) galeriaFotosPreview.innerHTML = '';
        const videosYoutubeContainer = document.getElementById('videos_youtube_container');
        if (videosYoutubeContainer) videosYoutubeContainer.innerHTML = '';

        // Foto Principal
        if (window.midiasData) {
            const mainPhoto = window.midiasData.find(m => m.tipo === 'foto_principal');
            if (mainPhoto && fotoPrincipalPreview) {
                displayExistingFilePreview(fotoPrincipalPreview, mainPhoto.caminho_arquivo, true, 'foto_principal');
            }
        }

        // Galeria de Fotos
        if (window.midiasData && galeriaFotosPreview) {
            window.midiasData.filter(m => m.tipo === 'galeria_foto').forEach(m => {
                displayExistingFilePreview(galeriaFotosPreview, m.caminho_arquivo, true, 'galeria_foto');
            });
        }
        
        // Videos do YouTube
        if (window.midiasData && videosYoutubeContainer) {
            const youtubeVideos = window.midiasData.filter(m => m.tipo === 'video');
            if (youtubeVideos.length > 0) {
                youtubeVideos.forEach(m => {
                    addVideoUrlInput(m.caminho_arquivo);
                });
            } else {
                addVideoUrlInput(); // Add one empty input if no videos
            }
        }

        function addVideoUrlInput(url = '') {
            const div = document.createElement('div');
            div.classList.add('form-group-row');
            div.innerHTML = `
                <div class="form-group flex-grow">
                    <label>URL do Vídeo (ID)</label>
                    <input type="text" name="videos_youtube[]" value="${htmlspecialchars(url)}" class="form-control" placeholder="Ex: dQw4w9WgXcQ">
                </div>
                <div class="form-group action-column">
                    <button type="button" class="btn btn-danger btn-sm remove-video-url"><i class="fas fa-trash"></i></button>
                </div>
            `;
            // Ensure listener is added only once per element
            const removeBtn = div.querySelector('.remove-video-url');
            if (removeBtn && !removeBtn.dataset.listenerAdded) {
                removeBtn.addEventListener('click', () => div.remove());
                removeBtn.dataset.listenerAdded = 'true';
            }
            videosYoutubeContainer.appendChild(div);
        }
        const addVideoUrlBtn = document.getElementById('add_video_url');
        if (addVideoUrlBtn && !addVideoUrlBtn.dataset.listenerAdded) {
            addVideoUrlBtn.addEventListener('click', () => addVideoUrlInput());
            addVideoUrlBtn.dataset.listenerAdded = 'true';
        }

        // Preview for newly selected files (foto_principal, galeria_fotos, documentos)
        document.getElementById('foto_principal')?.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (fotoPrincipalPreview) {
                        fotoPrincipalPreview.innerHTML = `<div class="file-preview-item"><img src="${e.target.result}" class="img-preview" alt="Preview"></div>`;
                    }
                };
                reader.readAsDataURL(file);
            } else if (fotoPrincipalPreview) {
                fotoPrincipalPreview.innerHTML = ''; // Clear preview if no file selected
            }
        });

        document.getElementById('galeria_fotos')?.addEventListener('change', function() {
            if (galeriaFotosPreview) galeriaFotosPreview.innerHTML = ''; // Clear current previews for new selection
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    displayExistingFilePreview(galeriaFotosPreview, e.target.result, true); // Use base64 for new previews
                };
                reader.readAsDataURL(file);
            });
        });
        // You might want similar logic for documento_contrato and documento_memorial previews
        
        // Populate existing documents if available
        const documentoContratoPreview = document.getElementById('documento_contrato')?.nextElementSibling;
        if (documentoContratoPreview && window.midiasData) {
            const contratoDoc = window.midiasData.find(m => m.tipo === 'documento_contrato');
            if (contratoDoc) {
                // This assumes the PHP already renders the link, so we just add remove functionality if needed
                // Or you could build the link and button dynamically here
            }
        }
        const documentoMemorialPreview = document.getElementById('documento_memorial')?.nextElementSibling;
        if (documentoMemorialPreview && window.midiasData) {
             const memorialDoc = window.midiasData.find(m => m.tipo === 'documento_memorial');
            if (memorialDoc) {
                // Similar to contratoDoc
            }
        }
    }

    // --- CORREÇÃO E MELHORIA DA LÓGICA DA ETAPA 6 ---
    function initStep6() {
        const unidadeExemploSelect = document.getElementById('unidade_exemplo_id');
        const paymentFlowBuilderPanel = document.getElementById('payment_flow_builder_panel');
        const paymentFlowTbody = document.getElementById('payment_flow_tbody');
        const addParcelaBtn = document.getElementById('add_parcela');

        // 1. Popular o select de unidade exemplo (se ainda não populado pelo PHP)
        // Isso foi movido para o PHP para garantir a pré-população na carga da página,
        // mas o JS ainda pode chamar populateUnidadeExemploSelect() se a etapa for ativada dinamicamente.
        // populateUnidadeExemploSelect(); // Comentado pois o PHP agora preenche

        // 2. Listener para mudança na seleção da unidade exemplo
        // Garante que o listener seja adicionado apenas uma vez
        if (unidadeExemploSelect && !unidadeExemploSelect.dataset.listenerAdded) {
            unidadeExemploSelect.addEventListener('change', function() {
                const selectedIndex = this.value;
                if (selectedIndex !== '') {
                    const selectedUnit = window.unidadesEstoqueData[selectedIndex];
                    document.getElementById('payment_unit_value_display').textContent = formatCurrency(selectedUnit.valor);
                    paymentFlowBuilderPanel.style.display = 'block';
                    loadPaymentPlanForSelectedUnit(selectedUnit.informacoes_pagamento);
                } else {
                    document.getElementById('payment_unit_value_display').textContent = formatCurrency(0);
                    paymentFlowBuilderPanel.style.display = 'none';
                    paymentFlowTbody.innerHTML = ''; // Limpa as parcelas
                    document.getElementById('total_plano_display').textContent = formatCurrency(0);
                    document.getElementById('total_plano_validation').textContent = '';
                }
                updatePaymentPlanSummary(); // Garante que o resumo seja atualizado
            });
            unidadeExemploSelect.dataset.listenerAdded = 'true';
        }

        // Dispara o evento change ao inicializar a etapa, caso já haja uma unidade selecionada via PHP
        if (unidadeExemploSelect && unidadeExemploSelect.value !== '') {
            unidadeExemploSelect.dispatchEvent(new Event('change'));
        }

        // 3. Listener para adicionar nova parcela (garante um único listener)
        if (addParcelaBtn && !addParcelaBtn.dataset.listenerAdded) { // Evita múltiplos listeners
            addParcelaBtn.addEventListener('click', function() {
                addPaymentRow(); // Adiciona APENAS uma linha
                updatePaymentPlanSummary(); // Recalcula após adicionar nova linha
            });
            addParcelaBtn.dataset.listenerAdded = 'true';
        }

        // Função para adicionar uma linha de parcela
        function addPaymentRow(data = {}) {
            const newRow = paymentFlowTbody.insertRow();
            newRow.classList.add('payment-flow-item-row');
            
            // Valor padrão para o campo input (multiplicar por 100 se for percentual para exibição)
            const inputValue = (data.tipo_valor === 'Percentual (%)' && data.valor !== undefined) ? (data.valor * 100) : (data.valor || '');

            newRow.innerHTML = `
                <td><input type="text" name="fluxo_pagamento[descricao][]" value="${htmlspecialchars(data.descricao || '')}" required class="form-control"></td>
                <td><input type="number" name="fluxo_pagamento[quantas_vezes][]" value="${data.quantas_vezes || 1}" min="1" required class="form-control"></td>
                <td>
                    <select name="fluxo_pagamento[tipo_valor][]" required class="form-control tipo-valor-select">
                        <option value="Valor Fixo (R$)" ${data.tipo_valor === 'Valor Fixo (R$)' ? 'selected' : ''}>Valor Fixo (R$)</option>
                        <option value="Percentual (%)" ${data.tipo_valor === 'Percentual (%)' ? 'selected' : ''}>Percentual (%)</option>
                    </select>
                </td>
                <td>
                    <select name="fluxo_pagamento[tipo_calculo][]" required class="form-control tipo-calculo-select">
                        <option value="Fixo" ${data.tipo_calculo === 'Fixo' ? 'selected' : ''}>Fixo</option>
                        <option value="Proporcional" ${data.tipo_calculo === 'Proporcional' ? 'selected' : ''}>Proporcional</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" name="fluxo_pagamento[valor][]" value="${inputValue}" required class="form-control valor-input"></td>
                <td class="total-condicao-display">${formatCurrency(0)}</td>
                <td class="valor-por-parcela-display">${formatCurrency(0)}</td>
                <td><button type="button" class="btn btn-danger btn-sm remove-parcela"><i class="fas fa-trash"></i></button></td>
            `;

            // Anexar listeners a esta nova linha
            const removeBtn = newRow.querySelector('.remove-parcela');
            if (removeBtn && !removeBtn.dataset.listenerAdded) {
                removeBtn.addEventListener('click', function() {
                    newRow.remove();
                    updatePaymentPlanSummary();
                });
                removeBtn.dataset.listenerAdded = 'true';
            }

            newRow.querySelectorAll('input, select').forEach(input => {
                if (!input.dataset.listenerAdded) { // Add flag to prevent duplicate listeners
                    input.addEventListener('input', updatePaymentPlanSummary);
                    input.addEventListener('change', updatePaymentPlanSummary);
                    input.dataset.listenerAdded = 'true';
                }
            });
        }

        // Função para carregar um plano de pagamento existente (ao editar)
        function loadPaymentPlanForSelectedUnit(paymentInfoJson) {
            paymentFlowTbody.innerHTML = ''; // Limpa as linhas atuais

            let paymentData = [];
            try {
                paymentData = JSON.parse(paymentInfoJson);
                if (!Array.isArray(paymentData)) paymentData = [];
            } catch (e) {
                console.error("Erro ao parsear informacoes_pagamento JSON:", e);
                paymentData = [];
            }

            if (paymentData.length > 0) {
                paymentData.forEach(item => addPaymentRow(item));
            } else {
                addPaymentRow(); // Adiciona uma linha vazia se não houver plano salvo
            }
            updatePaymentPlanSummary(); // Atualiza o resumo após carregar
        }

        // Função para recalcular o total do plano e atualizar a validação
        function updatePaymentPlanSummary() {
            const selectedUnitOption = unidadeExemploSelect.options[unidadeExemploSelect.selectedIndex];
            if (!selectedUnitOption) {
                console.warn("Nenhuma unidade exemplo selecionada.");
                document.getElementById('total_plano_display').textContent = formatCurrency(0);
                document.getElementById('total_plano_validation').textContent = '';
                return;
            }
            const selectedUnitValue = parseFloat(selectedUnitOption.dataset.unitValue || 0);

            let currentTotalAmount = 0;
            const tolerance = 0.01; // Para comparação de floats

            document.querySelectorAll('#payment_flow_tbody .payment-flow-item-row').forEach(row => {
                const tipoValorSelect = row.querySelector('.tipo-valor-select');
                const valorInput = row.querySelector('.valor-input');
                const quantasVezesInput = row.querySelector('input[name="fluxo_pagamento[quantas_vezes][]"]');

                const valorRaw = parseFloat(valorInput.value || 0);
                const quantasVezes = parseInt(quantasVezesInput.value || 1);

                let totalCondicao = 0;
                let valorPorParcela = 0;

                if (tipoValorSelect.value === 'Percentual (%)') {
                    const percentualReal = valorRaw / 100; // Converte 10 para 0.1
                    valorPorParcela = selectedUnitValue * percentualReal;
                    totalCondicao = valorPorParcela * quantasVezes;
                } else { // Valor Fixo (R$)
                    valorPorParcela = valorRaw;
                    totalCondicao = valorRaw * quantasVezes;
                }
                currentTotalAmount += totalCondicao;

                // Atualiza o "Total Condição" e "Valor por Parcela" para cada linha
                row.querySelector('.total-condicao-display').textContent = formatCurrency(totalCondicao);
                row.querySelector('.valor-por-parcela-display').textContent = formatCurrency(valorPorParcela);
            });

            document.getElementById('total_plano_display').textContent = formatCurrency(currentTotalAmount);

            const validationMessage = document.getElementById('total_plano_validation');
            if (Math.abs(currentTotalAmount - selectedUnitValue) > tolerance) {
                validationMessage.textContent = `A somatória (${formatCurrency(currentTotalAmount)}) não totaliza o valor da unidade (${formatCurrency(selectedUnitValue)}). Saldo restante: ${formatCurrency(selectedUnitValue - currentTotalAmount)}.`;
                validationMessage.classList.remove('text-green');
                validationMessage.classList.add('text-red');
            } else {
                validationMessage.textContent = `Plano de pagamento totaliza ${formatCurrency(currentTotalAmount)}.`;
                validationMessage.classList.remove('text-red');
                validationMessage.classList.add('text-green');
            }
        }
    }

    function initStep7() {
        const permissaoReservaRadios = document.querySelectorAll('input[name="permissao_reserva_tipo"]');
        const corretoresSelecaoGroup = document.getElementById('corretores_selecao_group');
        const imobiliariasSelecaoGroup = document.getElementById('imobiliarias_selecao_group');
        const corretoresPermitidosSelect = document.getElementById('corretores_permitidos');
        const imobiliariasPermitidasSelect = document.getElementById('imobiliarias_permitidas');

        function togglePermissaoReservaGroups() {
            const selectedValue = document.querySelector('input[name="permissao_reserva_tipo"]:checked')?.value;
            corretoresSelecaoGroup.style.display = 'none';
            imobiliariasSelecaoGroup.style.display = 'none';

            if (selectedValue === 'Corretores Selecionados') {
                corretoresSelecaoGroup.style.display = 'block';
            } else if (selectedValue === 'Imobiliarias Selecionadas') {
                imobiliariasSelecaoGroup.style.display = 'block';
            }
        }

        permissaoReservaRadios.forEach(radio => {
            if (!radio.dataset.listenerAdded) {
                radio.addEventListener('change', togglePermissaoReservaGroups);
                radio.dataset.listenerAdded = 'true';
            }
        });
        togglePermissaoReservaGroups(); // Initial call

        // Populate corretores select
        if (corretoresPermitidosSelect && window.corretoresDisponiveis) {
            let optionsHtml = '';
            const preSelectedCorretores = Array.isArray(window.empreendimentoData?.corretores_permitidos_ids) ? window.empreendimentoData.corretores_permitidos_ids : [];
            console.log("DEBUG: window.corretoresDisponiveis loaded:", window.corretoresDisponiveis);
            window.corretoresDisponiveis.forEach(corretor => {
                if (!corretor || typeof corretor.id === 'undefined' || corretor.id === null) {
                    console.warn("DEBUG: Invalid or missing ID for corretor object found:", corretor);
                    return;
                }
                const corretorIdInt = parseInt(String(corretor.id));
                const selected = preSelectedCorretores.includes(corretorIdInt) ? 'selected' : '';
                optionsHtml += `<option value=\"${corretor.id}\" ${selected}>${htmlspecialchars(corretor.nome || '')} (${htmlspecialchars(corretor.creci || 'N/A')})</option>`;
            });
            corretoresPermitidosSelect.innerHTML = optionsHtml;
        }

        // Populate imobiliarias select
        if (imobiliariasPermitidasSelect && window.imobiliariasDisponiveis) {
            let optionsHtml = '';
            const preSelectedImobiliarias = Array.isArray(window.empreendimentoData?.imobiliarias_permitidas_ids) ? window.empreendimentoData.imobiliarias_permitidas_ids : [];
            console.log("DEBUG: window.imobiliariasDisponiveis loaded:", window.imobiliariasDisponiveis);
            window.imobiliariasDisponiveis.forEach(imobiliaria => {
                if (!imobiliaria || typeof imobiliaria.id === 'undefined' || imobiliaria.id === null) {
                    console.warn("DEBUG: Invalid or missing ID real estate object found:", imobiliaria);
                    return;
                }
                const imobiliariaIdInt = parseInt(String(imobiliaria.id));
                const selected = preSelectedImobiliarias.includes(imobiliariaIdInt) ? 'selected' : '';
                optionsHtml += `<option value=\"${imobiliaria.id}\" ${selected}>${htmlspecialchars(imobiliaria.nome || '')}</option>`;
            });
            imobiliariasPermitidasSelect.innerHTML = optionsHtml;
        }
    }

    // --- GENERAL INITIALIZATION ---
    showStep(currentStep); // Show the initial step based on PHP variable

    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    nextBtn.addEventListener('click', async () => {
        clearErrors();
        const formData = new FormData(form);
        const empreendimentoId = empreendimentoIdInput.value;
        const submitUrl = `${window.BASE_URL}api/empreendimentos/salvar_etapa${currentStep}.php`;

        const dataToSend = {};
        for (let [key, value] of formData.entries()) {
            // Handle arrays (e.g., checkboxes or dynamically added fields)
            if (key.endsWith('[]')) {
                const baseKey = key.slice(0, -2);
                if (!dataToSend[baseKey]) {
                    dataToSend[baseKey] = [];
                }
                dataToSend[baseKey].push(value);
            } else {
                dataToSend[key] = value;
            }
        }

        // Special handling for JSON fields and file uploads by step
        if (currentStep === 1) {
            // Docs obrigatorios are from textarea, so they come as a single string. Convert to array.
            const docsObrigatoriosRaw = dataToSend.documentos_obrigatorios;
            if (typeof docsObrigatoriosRaw === 'string' && docsObrigatoriosRaw.trim() !== '') {
                dataToSend.documentos_obrigatorios = JSON.stringify(docsObrigatoriosRaw.split(',').map(s => s.trim()).filter(s => s));
            } else {
                dataToSend.documentos_obrigatorios = '[]';
            }
            dataToSend.preco_por_m2_sugerido = dataToSend.preco_por_m2_sugerido ? parseFloat(dataToSend.preco_por_m2_sugerido.replace(/\./g, '').replace(',', '.')) : null;
        } else if (currentStep === 2) {
            // Reestrutura tipos_unidade para JSON e trata foto_planta
            const tiposUnidadeData = [];
            document.querySelectorAll('#tipos_unidades_container tbody tr').forEach(row => {
                const id = row.querySelector('input[name="tipos_unidade[id][]"]')?.value;
                const tipo = row.querySelector('input[name="tipos_unidade[tipo][]"]')?.value;
                const metragem = row.querySelector('input[name="tipos_unidade[metragem][]"]')?.value;
                const quartos = row.querySelector('input[name="tipos_unidade[quartos][]"]')?.value;
                const banheiros = row.querySelector('input[name="tipos_unidade[banheiros][]"]')?.value;
                const vagas = row.querySelector('input[name="tipos_unidade[vagas][]"]')?.value;
                const fotoPlantaInput = row.querySelector('input[type="file"]'); // The file input
                const existingFotoPlantaHidden = row.querySelector('.existing-foto-planta'); // The hidden input with current path

                let fotoPlantaPath = existingFotoPlantaHidden ? existingFotoPlantaHidden.value : '';

                // If a new file is selected, this is where you'd upload it via AJAX
                // and get the new path. For now, if a file is selected, it's NOT sent
                // with this main FormData. The backend `salvar_etapa2.php` will only
                // process paths found in the JSON or null.
                // This is a known limitation that requires a separate upload API for dynamic file inputs.
                // For a full solution, this section would involve an await fetch to a dedicated upload endpoint.
                // For now, it will only save the hidden path, or null if removed.
                
                tiposUnidadeData.push({
                    id: id,
                    tipo: tipo,
                    metragem: parseFloat(metragem || 0),
                    quartos: parseInt(quartos || 0),
                    banheiros: parseInt(banheiros || 0),
                    vagas: parseInt(vagas || 0),
                    foto_planta: fotoPlantaPath // Pass the path from hidden input
                });
            });
            dataToSend.tipos_unidade_json = JSON.stringify(tiposUnidadeData);
            // Remove os campos individuais do FormData para não duplicar
            Object.keys(dataToSend).filter(k => k.startsWith('tipos_unidade[')).forEach(key => delete dataToSend[key]);
            // Remove os arquivos de input type="file" do FormData, pois eles são tratados via JSON com o caminho
            // Isso previne que FormData tente enviar files with types_unidade_fotos_X
            for (let i = 0; i < formData.length; i++) {
                if (formData[i] instanceof File && formData[i].name.startsWith('tipos_unidade_fotos_')) {
                    formData.delete(formData[i].name);
                }
            }


        } else if (currentStep === 3) {
            dataToSend.areas_comuns_selecionadas_json = JSON.stringify(dataToSend.areas_comuns_selecionadas || []);
        } else if (currentStep === 4) {
            // Collect units stock data from the dynamic table
            const unidadesEstoque = [];
            document.querySelectorAll('#units_stock_tbody .unit-stock-row').forEach(row => {
                const tipoUnidadeSelect = row.querySelector('.unit-stock-tipo-select');
                const selectedTipoIndex = tipoUnidadeSelect ? parseInt(tipoUnidadeSelect.value) : null;
                const tipoUnidadeIdReal = selectedTipoIndex !== null && window.tiposUnidadesData[selectedTipoIndex] ? window.tiposUnidadesData[selectedTipoIndex].id : null;

                unidadesEstoque.push({
                    id: row.querySelector('input[name="unidades_estoque[id][]"]')?.value,
                    tipo_unidade_id: tipoUnidadeIdReal, // Pass the actual DB ID
                    numero: row.querySelector('input[name="unidades_estoque[numero][]"]')?.value,
                    andar: row.querySelector('input[name="unidades_estoque[andar][]"]')?.value,
                    posicao: row.querySelector('input[name="unidades_estoque[posicao][]"]')?.value,
                    area: row.querySelector('input[name="unidades_estoque[area][]"]')?.value,
                    multiplier: row.querySelector('input[name="unidades_estoque[multiplier][]"]')?.value,
                    valor: row.querySelector('input[name="unidades_estoque[valor][]"]')?.value.replace(/\./g, '').replace(',', '.') // Convert to float string
                });
            });
            dataToSend.unidades_json = JSON.stringify(unidadesEstoque);

            // Collect unidades_por_andar map
            const unidadesPorAndar = {};
            document.querySelectorAll('#unidades_por_andar_container input').forEach(input => {
                const andarKey = parseInt(input.id.split('_').pop());
                const numUnits = parseInt(input.value);
                if (!isNaN(andarKey) && !isNaN(numUnits)) {
                    unidadesPorAndar[andarKey] = numUnits;
                }
            });
            dataToSend.unidades_por_andar_json = JSON.stringify(unidadesPorAndar);
            dataToSend.num_andares = document.getElementById('andar').value; // Total de andares do empreendimento

        } else if (currentStep === 5) {
            // Para a Etapa 5, os arquivos são enviados diretamente via FormData,
            // e os caminhos de mídias existentes/vídeos são enviados via JSON.
            // O backend 'salvar_etapa5.php' é robusto para lidar com ambos.

            // Coleta mídias existentes (apenas os caminhos/tipos) do DOM
            const currentMidiasInDOM = [];
            document.querySelectorAll('#foto_principal_preview .file-preview-item img, #galeria_fotos_preview .file-preview-item img').forEach(img => {
                const path = img.src.replace(window.BASE_URL, '');
                let type = 'galeria_foto'; // Default, refine based on parent ID
                if (img.closest('#foto_principal_preview')) type = 'foto_principal';
                currentMidiasInDOM.push({ caminho_arquivo: path, tipo: type });
            });
            // Adicione também vídeos e documentos que não são files input mas podem ter sido carregados
            if (window.midiasData) {
                window.midiasData.filter(m => m.tipo === 'video' || m.tipo === 'documento_contrato' || m.tipo === 'documento_memorial' || m.tipo === 'explore_video_thumb' || m.tipo === 'explore_gallery_thumb').forEach(m => {
                    // Adiciona apenas se já não está na lista de DOM (para evitar duplicação com uploads)
                    if (!currentMidiasInDOM.some(item => item.caminho_arquivo === m.caminho_arquivo && item.tipo === m.tipo)) {
                        currentMidiasInDOM.push({ caminho_arquivo: m.caminho_arquivo, tipo: m.tipo });
                    }
                });
            }
            dataToSend.midias_existentes_json = JSON.stringify(currentMidiasInDOM);


            // Coleta vídeos do YouTube das inputs
            const videosYoutubeUrls = [];
            document.querySelectorAll('#videos_youtube_container input[name="videos_youtube[]"]').forEach(input => {
                if (input.value.trim() !== '') {
                    videosYoutubeUrls.push(input.value.trim());
                }
            });
            dataToSend.videos_youtube_json = JSON.stringify(videosYoutubeUrls);

            // Cria um novo FormData para enviar arquivos e dados JSON.
            // Isso é necessário porque `FormData` é melhor para lidar com `multipart/form-data`.
            const sendFormData = new FormData();
            // Adiciona os dados coletados manualmente (JSONs, etc.)
            for (const key in dataToSend) {
                // `midias_existentes_json` e `videos_youtube_json` já estão stringified
                sendFormData.append(key, dataToSend[key]);
            }
            // Adiciona os arquivos diretamente do formulário original
            // Percorre o FormData original para extrair os arquivos.
            formData.forEach((value, key) => {
                if (value instanceof File) {
                    sendFormData.append(key, value);
                }
            });
            dataToSend = sendFormData; // Substitui dataToSend pelo FormData completo

        } else if (currentStep === 6) {
            const fluxoPagamento = [];
            document.querySelectorAll('#payment_flow_tbody .payment-flow-item-row').forEach(row => {
                const descricao = row.querySelector('input[name="fluxo_pagamento[descricao][]"]')?.value;
                const quantasVezes = row.querySelector('input[name="fluxo_pagamento[quantas_vezes][]"]')?.value;
                const tipoValor = row.querySelector('select[name="fluxo_pagamento[tipo_valor][]"]')?.value;
                const tipoCalculo = row.querySelector('select[name="fluxo_pagamento[tipo_calculo][]"]')?.value;
                const valor = row.querySelector('input[name="fluxo_pagamento[valor][]"]')?.value.replace(/\./g, '').replace(',', '.'); // Remove máscara para enviar float

                fluxoPagamento.push({
                    descricao: descricao,
                    quantas_vezes: parseInt(quantasVezes || 0),
                    tipo_valor: tipoValor,
                    tipo_calculo: tipoCalculo,
                    valor: parseFloat(valor || 0) // Converte para float para o backend
                });
            });
            dataToSend.fluxo_pagamento_json = JSON.stringify(fluxoPagamento);
            // Pega o real unit ID da unidade exemplo selecionada
            dataToSend.unidade_exemplo_id = document.getElementById('unidade_exemplo_id').selectedOptions[0].dataset.unitId;

        } else if (currentStep === 7) {
            dataToSend.permissoes_visualizacao_json = JSON.stringify(dataToSend.permissoes_visualizacao || []);
            dataToSend.corretores_permitidos_json = JSON.stringify(dataToSend.corretores_permitidos || []);
            dataToSend.imobiliarias_permitidas_json = JSON.stringify(dataToSend.imobiliarias_permitidas || []);
            // Documentos necessários da Etapa 7 (checkboxes)
            // Estão sendo coletados como um array de strings por FormData.
            // Converta para JSON string para o backend.
            if (dataToSend.documentos_necessarios_etapa7 && Array.isArray(dataToSend.documentos_necessarios_etapa7)) {
                dataToSend.documentos_necessarios_json = JSON.stringify(dataToSend.documentos_necessarios_etapa7);
            } else {
                dataToSend.documentos_necessarios_json = '[]';
            }
            // Remove o campo original para evitar duplicação.
            delete dataToSend.documentos_necessarios_etapa7; 
        }
        
        let fetchOptions = {
            method: 'POST',
        };

        // Se o corpo é uma instância de FormData, o fetch ajusta o Content-Type automaticamente
        if (!(dataToSend instanceof FormData)) {
            fetchOptions.headers = { 'Content-Type': 'application/json' };
            fetchOptions.body = JSON.stringify(dataToSend);
        } else {
            fetchOptions.body = dataToSend;
        }

        try {
            const response = await fetch(submitUrl, fetchOptions);
            const data = await response.json();

            if (!response.ok) { // Check for HTTP errors
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            if (data.success) {
                showMessage('success', data.message);
                if (currentStep < steps.length) {
                    // Update empreendimentoId in hidden input if it's a new creation
                    if (data.empreendimento_id && !empreendimentoId) {
                        empreendimentoIdInput.value = data.empreendimento_id;
                        window.empreendimentoData.id = data.empreendimento_id; // Update JS global
                    }
                    showStep(currentStep + 1);
                }
            } else {
                showErrors(data.errors || { general: data.message || 'Ocorreu um erro ao salvar os dados.' });
            }
        } catch (error) {
            console.error('Erro ao salvar dados:', error);
            showMessage('error', `Erro ao salvar dados: ${error.message || 'Ocorreu um erro de comunicação.'}`);
        }
    });

    submitBtn.addEventListener('click', async () => {
        // This is similar to nextBtn logic for the final step.
        // Re-use nextBtn logic but with final submission redirect.
        clearErrors();
        const formData = new FormData(form);
        const empreendimentoId = empreendimentoIdInput.value;
        const submitUrl = `${window.BASE_URL}api/empreendimentos/salvar_etapa${currentStep}.php`;

        const dataToSend = {};
        for (let [key, value] of formData.entries()) {
            if (key.endsWith('[]')) {
                const baseKey = key.slice(0, -2);
                if (!dataToSend[baseKey]) {
                    dataToSend[baseKey] = [];
                }
                dataToSend[baseKey].push(value);
            } else {
                dataToSend[key] = value;
            }
        }

        // Special handling for JSON fields and file uploads by step
        if (currentStep === 1) {
            const docsObrigatoriosRaw = dataToSend.documentos_obrigatorios;
            if (typeof docsObrigatoriosRaw === 'string' && docsObrigatoriosRaw.trim() !== '') {
                dataToSend.documentos_obrigatorios = JSON.stringify(docsObrigatoriosRaw.split(',').map(s => s.trim()).filter(s => s));
            } else {
                dataToSend.documentos_obrigatorios = '[]';
            }
            dataToSend.preco_por_m2_sugerido = dataToSend.preco_por_m2_sugerido ? parseFloat(dataToSend.preco_por_m2_sugerido.replace(/\./g, '').replace(',', '.')) : null;
        } else if (currentStep === 2) {
            const tiposUnidadeData = [];
            document.querySelectorAll('#tipos_unidades_container tbody tr').forEach(row => {
                const id = row.querySelector('input[name="tipos_unidade[id][]"]')?.value;
                const tipo = row.querySelector('input[name="tipos_unidade[tipo][]"]')?.value;
                const metragem = row.querySelector('input[name="tipos_unidade[metragem][]"]')?.value;
                const quartos = row.querySelector('input[name="tipos_unidade[quartos][]"]')?.value;
                const banheiros = row.querySelector('input[name="tipos_unidade[banheiros][]"]')?.value;
                const vagas = row.querySelector('input[name="tipos_unidade[vagas][]"]')?.value;
                const existingFotoPlantaHidden = row.querySelector('.existing-foto-planta');

                let fotoPlantaPath = existingFotoPlantaHidden ? existingFotoPlantaHidden.value : '';
                
                tiposUnidadeData.push({
                    id: id,
                    tipo: tipo,
                    metragem: parseFloat(metragem || 0),
                    quartos: parseInt(quartos || 0),
                    banheiros: parseInt(banheiros || 0),
                    vagas: parseInt(vagas || 0),
                    foto_planta: fotoPlantaPath
                });
            });
            dataToSend.tipos_unidade_json = JSON.stringify(tiposUnidadeData);
            Object.keys(dataToSend).filter(k => k.startsWith('tipos_unidade[')).forEach(key => delete dataToSend[key]);

        } else if (currentStep === 3) {
            dataToSend.areas_comuns_selecionadas_json = JSON.stringify(dataToSend.areas_comuns_selecionadas || []);
        } else if (currentStep === 4) {
            const unidadesEstoque = [];
            document.querySelectorAll('#units_stock_tbody .unit-stock-row').forEach(row => {
                const tipoUnidadeSelect = row.querySelector('.unit-stock-tipo-select');
                const selectedTipoIndex = tipoUnidadeSelect ? parseInt(tipoUnidadeSelect.value) : null;
                const tipoUnidadeIdReal = selectedTipoIndex !== null && window.tiposUnidadesData[selectedTipoIndex] ? window.tiposUnidadesData[selectedTipoIndex].id : null;

                unidadesEstoque.push({
                    id: row.querySelector('input[name="unidades_estoque[id][]"]')?.value,
                    tipo_unidade_id: tipoUnidadeIdReal,
                    numero: row.querySelector('input[name="unidades_estoque[numero][]"]')?.value,
                    andar: row.querySelector('input[name="unidades_estoque[andar][]"]')?.value,
                    posicao: row.querySelector('input[name="unidades_estoque[posicao][]"]')?.value,
                    area: row.querySelector('input[name="unidades_estoque[area][]"]')?.value,
                    multiplier: row.querySelector('input[name="unidades_estoque[multiplier][]"]')?.value,
                    valor: row.querySelector('input[name="unidades_estoque[valor][]"]')?.value.replace(/\./g, '').replace(',', '.')
                });
            });
            dataToSend.unidades_json = JSON.stringify(unidadesEstoque);

            const unidadesPorAndar = {};
            document.querySelectorAll('#unidades_por_andar_container input').forEach(input => {
                const andarKey = parseInt(input.id.split('_').pop());
                const numUnits = parseInt(input.value);
                if (!isNaN(andarKey) && !isNaN(numUnits)) {
                    unidadesPorAndar[andarKey] = numUnits;
                }
            });
            dataToSend.unidades_por_andar_json = JSON.stringify(unidadesPorAndar);
            dataToSend.num_andares = document.getElementById('andar').value;

        } else if (currentStep === 5) {
            const tempFormData = new FormData(form);

            const currentMidiasInDOM = [];
            document.querySelectorAll('#foto_principal_preview .file-preview-item img, #galeria_fotos_preview .file-preview-item img').forEach(img => {
                const path = img.src.replace(window.BASE_URL, '');
                let type = 'galeria_foto';
                if (img.closest('#foto_principal_preview')) type = 'foto_principal';
                currentMidiasInDOM.push({ caminho_arquivo: path, tipo: type });
            });
            if (window.midiasData) {
                window.midiasData.filter(m => m.tipo === 'video' || m.tipo === 'documento_contrato' || m.tipo === 'documento_memorial' || m.tipo === 'explore_video_thumb' || m.tipo === 'explore_gallery_thumb').forEach(m => {
                    if (!currentMidiasInDOM.some(item => item.caminho_arquivo === m.caminho_arquivo && item.tipo === m.tipo)) {
                        currentMidiasInDOM.push({ caminho_arquivo: m.caminho_arquivo, tipo: m.tipo });
                    }
                });
            }
            dataToSend.midias_existentes_json = JSON.stringify(currentMidiasInDOM);


            const videosYoutubeUrls = [];
            document.querySelectorAll('#videos_youtube_container input[name="videos_youtube[]"]').forEach(input => {
                if (input.value.trim() !== '') {
                    videosYoutubeUrls.push(input.value.trim());
                }
            });
            dataToSend.videos_youtube_json = JSON.stringify(videosYoutubeUrls);

            const sendFormData = new FormData();
            for (const key in dataToSend) {
                sendFormData.append(key, dataToSend[key]);
            }
            tempFormData.forEach((value, key) => {
                if (value instanceof File) {
                    sendFormData.append(key, value);
                }
            });
            dataToSend = sendFormData;

        } else if (currentStep === 6) {
            const fluxoPagamento = [];
            document.querySelectorAll('#payment_flow_tbody .payment-flow-item-row').forEach(row => {
                const descricao = row.querySelector('input[name="fluxo_pagamento[descricao][]"]')?.value;
                const quantasVezes = row.querySelector('input[name="fluxo_pagamento[quantas_vezes][]"]')?.value;
                const tipoValor = row.querySelector('select[name="fluxo_pagamento[tipo_valor][]"]')?.value;
                const tipoCalculo = row.querySelector('select[name="fluxo_pagamento[tipo_calculo][]"]')?.value;
                const valor = row.querySelector('input[name="fluxo_pagamento[valor][]"]')?.value.replace(/\./g, '').replace(',', '.');

                fluxoPagamento.push({
                    descricao: descricao,
                    quantas_vezes: parseInt(quantasVezes || 0),
                    tipo_valor: tipoValor,
                    tipo_calculo: tipoCalculo,
                    valor: parseFloat(valor || 0)
                });
            });
            dataToSend.fluxo_pagamento_json = JSON.stringify(fluxoPagamento);
            dataToSend.unidade_exemplo_id = document.getElementById('unidade_exemplo_id').selectedOptions[0].dataset.unitId;
        } else if (currentStep === 7) {
            dataToSend.permissoes_visualizacao_json = JSON.stringify(dataToSend.permissoes_visualizacao || []);
            dataToSend.corretores_permitidos_json = JSON.stringify(dataToSend.corretores_permitidos || []);
            dataToSend.imobiliarias_permitidas_json = JSON.stringify(dataToSend.imobiliarias_permitidas || []);
            
            if (dataToSend.documentos_necessarios_etapa7 && Array.isArray(dataToSend.documentos_necessarios_etapa7)) {
                dataToSend.documentos_necessarios_json = JSON.stringify(dataToSend.documentos_necessarios_etapa7);
            } else {
                dataToSend.documentos_necessarios_json = '[]';
            }
            delete dataToSend.documentos_necessarios_etapa7; 
        }

        let fetchOptions = {
            method: 'POST',
        };

        if (!(dataToSend instanceof FormData)) {
            fetchOptions.headers = { 'Content-Type': 'application/json' };
            fetchOptions.body = JSON.stringify(dataToSend);
        } else {
            fetchOptions.body = dataToSend;
        }

        try {
            const response = await fetch(submitUrl, fetchOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            if (data.success) {
                showMessage('success', data.message);
                // Redireciona para a página de listagem de empreendimentos após finalizar
                setTimeout(() => {
                    window.location.href = `${window.BASE_URL}admin/empreendimentos/index.php`;
                }, 1000);
            } else {
                showErrors(data.errors || { general: data.message || 'Ocorreu um erro ao finalizar o cadastro.' });
            }
        } catch (error) {
            console.error('Erro ao finalizar cadastro:', error);
            showMessage('error', `Erro ao finalizar cadastro: ${error.message || 'Ocorreu um erro de comunicação.'}`);
        }
    });

})();