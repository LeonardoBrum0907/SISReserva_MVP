<?php
// admin/leads/dispensados.php - VERSÃO FINAL E CORRIGIDA

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

require_permission(['admin']);
$page_title = "Leads Dispensados";
$conn = get_db_connection();

// --- NOVO: LÓGICA PARA KPIs DE LEADS DISPENSADOS ---
$kpis = [
    'total_dispensados' => fetch_single("SELECT COUNT(id) as total FROM reservas WHERE status = 'dispensada'")['total'] ?? 0,
    'dispensados_mes_atual' => fetch_single("SELECT COUNT(id) as total FROM reservas WHERE status = 'dispensada' AND YEAR(data_ultima_interacao) = YEAR(CURDATE()) AND MONTH(data_ultima_interacao) = MONTH(CURDATE())")['total'] ?? 0
];

// --- CONSULTA SQL CORRIGIDA ---
// Agora usa a tabela 'reservas_clientes' para encontrar o cliente, garantindo que os leads apareçam.
$leads_dispensados = fetch_all("
    SELECT 
        r.id as lead_id, r.data_reserva, r.data_ultima_interacao,
        c.nome as cliente_nome, c.whatsapp as cliente_whatsapp,
        e.nome as empreendimento_nome, u.numero as unidade_numero
    FROM reservas r
    JOIN reservas_clientes rc ON r.id = rc.reserva_id
    JOIN clientes c ON rc.cliente_id = c.id
    JOIN unidades u ON r.unidade_id = u.id
    JOIN empreendimentos e ON u.empreendimento_id = e.id
    WHERE r.status = 'dispensada'
    ORDER BY r.data_ultima_interacao DESC
");

require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <div class="details-page-header">
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
        <a href="<?php echo BASE_URL; ?>admin/leads/index.php" class="btn btn-secondary">Voltar para Leads Pendentes</a>
    </div>

    <p class="text-secondary mt-2">Esta página lista todas as solicitações que foram marcadas como "dispensadas" pelo administrador.</p>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Total de Leads Dispensados</span>
            <span class="kpi-value"><?php echo $kpis['total_dispensados']; ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Dispensados este Mês</span>
            <span class="kpi-value"><?php echo $kpis['dispensados_mes_atual']; ?></span>
        </div>
    </div>

    <div class="admin-table-responsive mt-lg">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID Lead</th>
                    <th>Data Solicitação</th>
                    <th>Cliente</th>
                    <th>Empreendimento/Unidade</th>
                    <th>Data da Dispensa</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads_dispensados)): ?>
                    <tr><td colspan="6" class="text-center">Nenhum lead dispensado encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads_dispensados as $lead): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lead['lead_id']); ?></td>
                            <td><?php echo format_datetime_br($lead['data_reserva']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($lead['cliente_nome']); ?></strong><br>
                                <small><?php echo htmlspecialchars(format_whatsapp($lead['cliente_whatsapp'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($lead['empreendimento_nome'] . ' / Unidade ' . $lead['unidade_numero']); ?></td>
                            <td><?php echo format_datetime_br($lead['data_ultima_interacao']); ?></td>
                            <td class="admin-table-actions">
                                <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo $lead['lead_id']; ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>