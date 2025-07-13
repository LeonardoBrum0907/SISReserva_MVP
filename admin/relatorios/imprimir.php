<?php
// admin/relatorios/imprimir.php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

require_permission(['admin']);

// Determina qual relatório imprimir baseado em um parâmetro 'tipo'
$tipo_relatorio = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_SPECIAL_CHARS);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório - SISReserva</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .report-container { width: 100%; margin: 0 auto; }
        h1, h2 { text-align: center; color: #2c3e50; border-bottom: 2px solid #ccc; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { text-align: center; margin-top: 30px; font-size: 0.8em; color: #777; }
        @media print {
            body { -webkit-print-color-adjust: exact; } /* Garante cores no Chrome */
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="report-container">
        <h1><img src="<?php echo BASE_URL; ?>assets/logo.png" alt="Logo" style="max-height: 50px; vertical-align: middle;"> Relatório SISReserva</h1>

        <?php if ($tipo_relatorio === 'vendas_periodo'):
            $filtro_vendas_inicio = filter_input(INPUT_GET, 'vendas_start_date', FILTER_SANITIZE_SPECIAL_CHARS);
            $filtro_vendas_fim = filter_input(INPUT_GET, 'vendas_end_date', FILTER_SANITIZE_SPECIAL_CHARS);
            $vendas_por_periodo = [];

            if ($filtro_vendas_inicio && $filtro_vendas_fim) {
                $sql_vendas = "SELECT r.id, r.data_ultima_interacao, r.valor_reserva, c.nome as cliente_nome, co.nome as corretor_nome, e.nome as empreendimento_nome, u.numero as unidade_numero FROM reservas r JOIN clientes c ON r.cliente_id = c.id JOIN usuarios co ON r.corretor_id = co.id JOIN unidades u ON r.unidade_id = u.id JOIN empreendimentos e ON u.empreendimento_id = e.id WHERE r.status = 'vendida' AND DATE(r.data_ultima_interacao) BETWEEN ? AND ? ORDER BY r.data_ultima_interacao DESC";
                $vendas_por_periodo = fetch_all($sql_vendas, [$filtro_vendas_inicio, $filtro_vendas_fim], "ss");
            }
        ?>
            <h2>Relatório de Vendas de <?php echo format_datetime_br($filtro_vendas_inicio); ?> a <?php echo format_datetime_br($filtro_vendas_fim); ?></h2>
            <table>
                <thead>
                    <tr><th>ID Venda</th><th>Data</th><th>Cliente</th><th>Corretor</th><th>Empreendimento/Unidade</th><th>Valor (R$)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas_por_periodo as $venda): ?>
                    <tr>
                        <td><?php echo $venda['id']; ?></td>
                        <td><?php echo format_datetime_br($venda['data_ultima_interacao']); ?></td>
                        <td><?php echo htmlspecialchars($venda['cliente_nome']); ?></td>
                        <td><?php echo htmlspecialchars($venda['corretor_nome']); ?></td>
                        <td><?php echo htmlspecialchars($venda['empreendimento_nome'] . ' / ' . $venda['unidade_numero']); ?></td>
                        <td><?php echo format_currency_brl($venda['valor_reserva']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <h2>Tipo de Relatório Inválido</h2>
        <?php endif; ?>

        <div class="footer">
            Relatório gerado em <?php echo date('d/m/Y H:i:s'); ?> por <?php echo htmlspecialchars($_SESSION['user_info']['nome'] ?? 'Admin'); ?>
        </div>
    </div>

</body>
</html>