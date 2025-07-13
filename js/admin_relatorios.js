document.addEventListener('DOMContentLoaded', function() {
    // Funções auxiliares para inicializar gráficos
    function initializeVendasPeriodoChart() {
        const ctx = document.getElementById('vendasPeriodoChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line', // Gráfico de linha para tendências ao longo do tempo
            data: {
                labels: window.vendasPeriodoChartLabels,
                datasets: [{
                    label: 'Número de Vendas',
                    data: window.vendasPeriodoChartData,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    fill: true, // Preenche a área abaixo da linha
                    tension: 0.3 // Suaviza a linha
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Permite que o container controle o tamanho
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantidade de Vendas'
                        },
                        ticks: {
                            precision: 0 // Apenas números inteiros para quantidade
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Data'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw;
                            }
                        }
                    }
                }
            }
        });
    }

    function initializeStatusUnidadesChart() {
        const ctx = document.getElementById('statusUnidadesChart');
        if (!ctx) return;

        // Se houver apenas um empreendimento, usa gráfico de pizza/donut
        // Se houver múltiplos, usa gráfico de barras empilhadas ou agrupadas
        const chartLabels = window.statusUnidadesChartLabels;
        const dataDisponivel = window.statusUnidadesChartDataDisponivel;
        const dataReservada = window.statusUnidadesChartDataReservada;
        const dataVendida = window.statusUnidadesChartDataVendida;

        if (chartLabels.length === 0) { // Se não há dados para o gráfico, não o inicialize
        console.warn("Nenhum dado de status de unidades disponível para o gráfico.");
        return;
        }

        if (chartLabels.length === 1) { // Gráfico de Pizza/Donut para um único empreendimento
            new Chart(ctx, {
                type: 'doughnut', // ou 'pie'
                data: {
                    labels: ['Disponíveis', 'Reservadas', 'Vendidas'],
                    datasets: [{
                        label: chartLabels[0] + ' - Unidades',
                        data: [dataDisponivel[0], dataReservada[0], dataVendida[0]],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)', // Verde para Disponíveis (Success)
                            'rgba(255, 193, 7, 0.8)', // Laranja para Reservadas (Warning)
                            'rgba(220, 53, 69, 0.8)'  // Vermelho para Vendidas (Danger)
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Status de Unidades para ' + chartLabels[0]
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    if (label) {
                                        let sum = 0;
                                        let dataArr = context.dataset.data;
                                        for (let i = 0; i < dataArr.length; i++) {
                                            sum += dataArr[i];
                                        }
                                        let percentage = (context.raw / sum * 100).toFixed(2) + '%';
                                        return label + ': ' + context.raw + ' (' + percentage + ')';
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } else if (chartLabels.length > 1) { // Gráfico de Barras Agrupadas para múltiplos empreendimentos
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Disponíveis',
                            data: dataDisponivel,
                            backgroundColor: 'rgba(40, 167, 69, 0.8)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Reservadas',
                            data: dataReservada,
                            backgroundColor: 'rgba(255, 193, 7, 0.8)',
                            borderColor: 'rgba(255, 193, 7, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Vendidas',
                            data: dataVendida,
                            backgroundColor: 'rgba(220, 53, 69, 0.8)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: false, // Desempilhado para agrupar por status
                            title: {
                                display: true,
                                text: 'Empreendimento'
                            }
                        },
                        y: {
                            stacked: false, // Desempilhado
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Número de Unidades'
                            },
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Status de Unidades por Empreendimento'
                        }
                    }
                }
            });
        }
    }

    const btnImprimirVendas = document.getElementById('imprimirVendasPeriodo');

    if (btnImprimirVendas) {
        btnImprimirVendas.addEventListener('click', function() {
            // ===== CORREÇÃO APLICADA AQUI =====
            const dataInicio = document.getElementById('vendas_start_date').value;
            const dataFim = document.getElementById('vendas_end_date').value;
            // ===================================

            if (!dataInicio || !dataFim) {
                alert('Por favor, selecione as datas de início e fim para gerar a impressão.');
                return;
            }

            // ===== CORREÇÃO APLICADA AQUI =====
            const printUrl = `${BASE_URL_JS}admin/relatorios/imprimir.php?tipo=vendas_periodo&vendas_start_date=${dataInicio}&vendas_end_date=${dataFim}`;
            // ===================================
            
            window.open(printUrl, '_blank');
        });
    }

    // Inicializar todos os gráficos
    initializeVendasPeriodoChart();
    initializeStatusUnidadesChart();

    // Lógica para exportar CSV
    document.querySelectorAll('.export-report-btn').forEach(button => {
        button.addEventListener('click', function() {
            const reportType = this.dataset.reportType;
            const form = this.closest('form');
            const params = new URLSearchParams();

            // Adiciona um parâmetro 'export=csv'
            params.append('export', 'csv');
            params.append('report', reportType);

            // Coleta os dados dos filtros do formulário
            form.querySelectorAll('input, select').forEach(input => {
                if (input.name && input.value) {
                    params.append(input.name, input.value);
                }
            });

            // Abre a nova URL para download
            window.open('?' + params.toString(), '_blank');
        });
    });
});