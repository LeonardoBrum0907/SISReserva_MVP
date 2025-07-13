document.addEventListener('DOMContentLoaded', function() {
    const vendasTable = document.getElementById('vendasTable');
    const vendasFiltersForm = document.getElementById('vendasFiltersForm');
    const applyVendasFiltersBtn = document.getElementById('applyVendasFiltersBtn');
    const exportVendasCsvBtn = document.getElementById('exportVendasCsvBtn'); // O botão de exportar CSV no HTML

    // --- Lógica de Ordenação de Tabela (Client-side) ---
    let sortDirection = {}; // Armazena a direção de ordenação para cada coluna

    if (vendasTable) { // Adiciona esta verificação
        vendasTable.querySelectorAll('th[data-sort-by]').forEach(header => {
            const columnKey = header.dataset.sortBy;
            sortDirection[columnKey] = 'asc'; // Inicializa todos como ascendente

            header.addEventListener('click', function() {
                const tbody = vendasTable.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));

                const isAsc = sortDirection[columnKey] === 'asc';
                
                // Inverte a direção para a próxima vez
                sortDirection[columnKey] = isAsc ? 'desc' : 'asc';

                rows.sort((a, b) => {
                    const aValue = a.querySelector(`td:nth-child(${getColumnIndex(columnKey)})`).textContent.trim();
                    const bValue = b.querySelector(`td:nth-child(${getColumnIndex(columnKey)})`).textContent.trim();

                    // Tratamento de tipos de dados para ordenação correta
                    if (columnKey === 'venda_id' || columnKey === 'valor_reserva' || columnKey === 'comissao_corretor' || columnKey === 'comissao_imobiliaria') {
                        // Trata números, removendo caracteres não numéricos para valores de moeda
                        const numA = parseFloat(aValue.replace(/[R$\s.,]/g, '').replace(',', '.')) || 0;
                        const numB = parseFloat(bValue.replace(/[R$\s.,]/g, '').replace(',', '.')) || 0;
                        return isAsc ? numA - numB : numB - numA;
                    } else if (columnKey === 'data_venda') {
                        // Trata datas (assumindo formato DD/MM/YYYY HH:MM:SS de format_datetime_br)
                        const dateA = parseBrazilianDateTime(aValue);
                        const dateB = parseBrazilianDateTime(bValue);
                        return isAsc ? dateA - dateB : dateB - dateA;
                    } else if (columnKey === 'unidade_numero') {
                        // Para unidades como "101 (1º Andar)", extrai apenas o número
                        const numA = parseInt(aValue.match(/^(\d+)/)?.[1] || 0);
                        const numB = parseInt(bValue.match(/^(\d+)/)?.[1] || 0);
                        return isAsc ? numA - numB : numB - numA;
                    }
                    else {
                        // Ordenação de string padrão
                        return isAsc ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
                    }
                });

                // Remove ícones de ordenação de todas as colunas
                vendasTable.querySelectorAll('th .fas.fa-sort, th .fas.fa-sort-up, th .fas.fa-sort-down').forEach(icon => {
                    icon.classList.remove('fa-sort-up', 'fa-sort-down');
                    icon.classList.add('fa-sort');
                });

                // Adiciona o ícone correto à coluna clicada
                const icon = header.querySelector('.fas.fa-sort');
                if (icon) {
                    icon.classList.remove('fa-sort');
                    icon.classList.add(isAsc ? 'fa-sort-up' : 'fa-sort-down');
                }

                rows.forEach(row => tbody.appendChild(row));
            });
        });
    }

    // Função auxiliar para obter o índice da coluna (usada pela ordenação)
    function getColumnIndex(columnKey) {
        // Assume que as colunas são fixas, ou mapeia dinamicamente
        const headers = Array.from(vendasTable.querySelectorAll('th'));
        for (let i = 0; i < headers.length; i++) {
            if (headers[i].dataset.sortBy === columnKey) {
                return i + 1; // Índices CSS são 1-based
            }
        }
        return -1;
    }

    // Função auxiliar para parsear data/hora brasileira para objeto Date (usada pela ordenação)
    function parseBrazilianDateTime(dateTimeStr) {
        // Ex: "03/07/2025 17:16:57" -> [03, 07, 2025, 17, 16, 57]
        const parts = dateTimeStr.match(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/);
        if (parts) {
            // Date(year, month, day, hours, minutes, seconds)
            return new Date(parts[3], parts[2] - 1, parts[1], parts[4], parts[5], parts[6]);
        }
        // Fallback para datas sem tempo ou outros formatos, se necessário
        const dateParts = dateTimeStr.split('/');
        if (dateParts.length === 3) {
            return new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
        }
        return new Date(dateTimeStr); // Tenta parsear como está
    }

    // --- Lógica de Filtros (Submissão do Formulário) ---
    if (vendasFiltersForm && applyVendasFiltersBtn) { // Adiciona esta verificação
        // O formulário já tem method="GET", então o submit padrão já recarrega a página com os filtros na URL.
        // Podemos usar um listener no botão para garantir que o formulário seja submetido.
        applyVendasFiltersBtn.addEventListener('click', function() {
            vendasFiltersForm.submit(); // Garante a submissão do formulário
        });

        // Event listener para o campo de busca (pode submeter o form ao pressionar Enter)
        const vendasSearchInput = document.getElementById('vendasSearch');
        if (vendasSearchInput) { // Adiciona esta verificação
            vendasSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Evita recarregar a página antes que o formulário seja submetido
                    vendasFiltersForm.submit();
                }
            });
        }
    }


    // --- Lógica do Botão Imprimir Venda ---
    document.querySelectorAll('.print-venda-btn').forEach(button => {
        if (button) { // Adiciona esta verificação
            button.addEventListener('click', function() {
                const vendaId = this.dataset.vendaId;
                if (vendaId) {
                    // Abre a página de detalhes da reserva em uma nova aba,
                    // que terá um botão "Imprimir" mais robusto.
                    window.location.href = `${BASE_URL}admin/reservas/detalhes.php?id=${vendaId}`;
                }
            });
        }
    });

    // --- Lógica para o botão de exportar CSV ---
    if (exportVendasCsvBtn && vendasFiltersForm) { // Adiciona esta verificação
        exportVendasCsvBtn.addEventListener('click', function() {
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.set('export', 'csv'); // Adiciona o parâmetro de exportação
            
            // Reutiliza os filtros do formulário
            const formInputs = vendasFiltersForm.querySelectorAll('input[name], select[name]');
            formInputs.forEach(input => {
                if (input.value) {
                    currentParams.set(input.name, input.value);
                } else {
                    currentParams.delete(input.name); // Remove se o filtro estiver vazio
                }
            });

            // Abre a nova URL para download do CSV
            window.open(`${BASE_URL}admin/vendas/index.php?${currentParams.toString()}`, '_blank');
        });
    } else {
        // console.warn("Elemento 'exportVendasCsvBtn' ou 'vendasFiltersForm' não encontrado nesta página. Isso é esperado em páginas que não gerenciam vendas.");
    }
});