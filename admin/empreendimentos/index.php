<?php
// admin/empreendimentos/index.php - Gestão de Empreendimentos

// Inclua os arquivos necessários
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para format_date_br()

// Redireciona se não for um admin master
require_permission(['admin']);

$page_title = "Gestão de Empreendimentos";

global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/empreendimentos/index.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}

$empreendimentos = [];
$total_empreendimentos_ativos = 0;
$unidades_disponiveis_total = 0;
$unidades_reservadas_total = 0;
$unidades_vendidas_total = 0;

try {
    // --- KPIs da Gestão de Empreendimentos ---
    // Usando 'status' para operacional (ativo/pausado) e 'fase_empreendimento' para o ciclo de vida
    $sql_kpis = "
        SELECT
            COUNT(e.id) AS total_empreendimentos,
            SUM(CASE WHEN u.status = 'disponivel' THEN 1 ELSE 0 END) AS total_disponiveis,
            SUM(CASE WHEN u.status = 'reservada' THEN 1 ELSE 0 END) AS total_reservadas,
            SUM(CASE WHEN u.status = 'vendida' THEN 1 ELSE 0 END) AS total_vendidas
        FROM
            empreendimentos e
        LEFT JOIN
            unidades u ON e.id = u.empreendimento_id
        WHERE
            e.status = 'ativo'; -- KPI de empreendimentos operacionalmente ativos
    ";
    $kpis = fetch_single($sql_kpis);

    $total_empreendimentos_ativos = $kpis['total_empreendimentos'] ?? 0;
    $unidades_disponiveis_total = $kpis['total_disponiveis'] ?? 0;
    $unidades_reservadas_total = $kpis['total_reservadas'] ?? 0;
    $unidades_vendidas_total = $kpis['total_vendidas'] ?? 0;


    // --- Listagem de Empreendimentos para a Tabela ---
    // Incluíndo 'fase_empreendimento' e 'tipo_empreendimento' para as colunas
    $sql_listagem_empreendimentos = "
        SELECT
            e.id,
            e.nome,
            e.status,
            e.fase_empreendimento,
            e.tipo_uso,
            e.cidade,
            e.estado,
            e.tipo_empreendimento,
            (SELECT COUNT(u.id) FROM unidades u WHERE u.empreendimento_id = e.id) AS total_unidades,
            (SELECT COUNT(u.id) FROM unidades u WHERE u.empreendimento_id = e.id AND u.status = 'disponivel') AS unidades_disponiveis,
            e.data_atualizacao,
            (SELECT u.nome FROM usuarios u WHERE u.id = (
                SELECT a.usuario_id 
                FROM auditoria a 
                WHERE a.entidade = 'Empreendimento' AND a.entidade_id = e.id 
                ORDER BY a.data_acao DESC 
                LIMIT 1
            )) AS ultima_atualizacao_por
        FROM
            empreendimentos e
        ORDER BY
            e.data_cadastro DESC;
    ";
    $empreendimentos = fetch_all($sql_listagem_empreendimentos);

} catch (Exception $e) {
    error_log("Erro na Gestão de Empreendimentos: " . $e->getMessage());
    // $errors[] = "Ocorreu um erro ao carregar os dados de empreendimentos.";
} finally {
    // >>> REMOVIDO: $conn->close(); <<<
    // A conexão é fechada apenas no footer_dashboard.php
}

require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <div class="admin-page-header">
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
        <a href="<?php echo BASE_URL; ?>admin/empreendimentos/criar_e_editar.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Empreendimento
        </a>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Empreendimentos Ativos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_empreendimentos_ativos); ?></span>
        </div>
        <div class="kpi-card status-available-bg">
            <span class="kpi-label">Unidades Disponíveis</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_disponiveis_total); ?></span>
        </div>
        <div class="kpi-card status-reserved-bg">
            <span class="kpi-label">Unidades Reservadas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_reservadas_total); ?></span>
        </div>
        <div class="kpi-card status-sold-bg">
            <span class="kpi-label">Unidades Vendidas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($unidades_vendidas_total); ?></span>
        </div>
    </div>

    <div class="admin-controls-bar">
        <div class="search-box">
            <input type="text" id="generic-search" placeholder="Buscar em qualquer coluna...">
            <i class="fas fa-search"></i>
        </div>
        <div class="filters-box">
            <select id="filter-status">
                <option value="">Status Operacional</option>
                <option value="ativo">Ativo</option>
                <option value="pausado">Pausado</option>
            </select>
            <select id="filter-fase">
                <option value="">Fase Empreendimento</option>
                <option value="pre_lancamento">Pré-Lançamento</option>
                <option value="lancamento">Lançamento</option>
                <option value="em_obra">Em Obra</option>
                <option value="pronto_para_morar">Pronto para Morar</option>
            </select>
            <select id="filter-type-uso">
                <option value="">Tipo de Uso</option>
                <option value="Residencial">Residencial</option>
                <option value="Comercial">Comercial</option>
                <option value="Misto">Misto</option>
            </select>
        </div>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 5%;" data-sort="id">ID <i class="fas fa-sort"></i></th>
                    <th style="width: 15%;" data-sort="nome">Nome <i class="fas fa-sort"></i></th>
                    <th style="width: 8%;" data-sort="status">Status <i class="fas fa-sort"></i></th>
                    <th style="width: 10%;" data-sort="fase_empreendimento">Fase <i class="fas fa-sort"></i></th>
                    <th style="width: 8%;" data-sort="tipo_uso">Uso <i class="fas fa-sort"></i></th>
                    <th style="width: 12%;" data-sort="cidade">Cidade/UF <i class="fas fa-sort"></i></th>
                    <th style="width: 8%;" data-sort="total_unidades">Total <i class="fas fa-sort"></i></th>
                    <th style="width: 8%;" data-sort="unidades_disponiveis">Disponíveis <i class="fas fa-sort"></i></th>
                    <th style="width: 10%;" data-sort="data_atualizacao">Última Atualização <i class="fas fa-sort"></i></th>
                    <th style="width: 16%;">Ações</th> </tr>
            </thead>
            <tbody>
                <?php if (!empty($empreendimentos)): ?>
                    <?php foreach ($empreendimentos as $emp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emp['id']); ?></td>
                            <td><?php echo htmlspecialchars($emp['nome']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($emp['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($emp['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $emp['fase_empreendimento']))); ?></td>
                            <td><?php echo htmlspecialchars($emp['tipo_uso']); ?></td>
                            <td><?php echo htmlspecialchars($emp['cidade'] . '/' . $emp['estado']); ?></td>
                            <td><?php echo htmlspecialchars($emp['total_unidades'] ?? '0'); ?></td>
                            <td><?php echo htmlspecialchars($emp['unidades_disponiveis'] ?? '0'); ?></td>
                            <td>
                                <?php echo htmlspecialchars((new DateTime($emp['data_atualizacao']))->format('d/m/Y H:i')); ?>
                                <?php if (!empty($emp['ultima_atualizacao_por'])): ?>
                                    <br><small>por: <?php echo htmlspecialchars($emp['ultima_atualizacao_por']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="criar_e_editar.php?id=<?php echo htmlspecialchars($emp['id']); ?>" class="btn btn-sm btn-info" title="Editar"><i class="fas fa-edit"></i></a>
                                
                                <button class="btn btn-sm btn-warning toggle-empreendimento-status" 
                                        data-id="<?php echo htmlspecialchars($emp['id']); ?>" 
                                        data-current-status="<?php echo htmlspecialchars($emp['status']); ?>" 
                                        title="<?php echo ($emp['status'] === 'ativo') ? 'Pausar Empreendimento' : 'Ativar Empreendimento'; ?>">
                                    <i class="fas <?php echo ($emp['status'] === 'ativo') ? 'fa-pause' : 'fa-play'; ?>"></i>
                                </button>

                                <a href="unidades.php?empreendimento_id=<?php echo htmlspecialchars($emp['id']); ?>" class="btn btn-sm btn-secondary" title="Ver Unidades"><i class="fas fa-building"></i></a>
                                
                                <button class="btn btn-sm btn-danger delete-empreendimento-btn" 
                                        data-id="<?php echo htmlspecialchars($emp['id']); ?>" 
                                        title="Excluir"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" style="text-align: center;">Nenhum empreendimento encontrado. <a href="<?php echo BASE_URL; ?>admin/empreendimentos/criar_e_editar.php">Crie um agora!</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // LÓGICA DO BOTÃO DE EXCLUSÃO (USANDO CONFIRM/ALERT)
    document.querySelectorAll('.delete-empreendimento-btn').forEach(button => {
        button.addEventListener('click', function() {
            const empreendimentoId = this.dataset.id;
            if (confirm('Tem certeza que deseja excluir este empreendimento e todos os seus dados associados? Esta ação é irreversível.')) {
                fetch(`<?php echo BASE_URL; ?>api/empreendimentos/excluir.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: empreendimentoId })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição AJAX:', error);
                    alert('Ocorreu um erro ao comunicar com o servidor.');
                });
            }
        });
    });

    // LÓGICA DO BOTÃO ATIVAR/PAUSAR (USANDO CONFIRM/ALERT)
    document.querySelectorAll('.toggle-empreendimento-status').forEach(button => {
        button.addEventListener('click', function() {
            const empreendimentoId = this.dataset.id;
            const currentStatus = this.dataset.currentStatus;
            const newStatus = currentStatus === 'ativo' ? 'pausado' : 'ativo';
            
            if (confirm(`Tem certeza que deseja mudar o status deste empreendimento para '${newStatus.toUpperCase()}'?`)) {
                fetch(`<?php echo BASE_URL; ?>api/empreendimentos/toggle_status.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ empreendimento_id: empreendimentoId, new_status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição AJAX:', error);
                    alert('Ocorreu um erro ao comunicar com o servidor.');
                });
            }
        });
    });

    // LÓGICA DOS FILTROS (MANTIDA)
    (function() {
        // ... (código do filtro aqui para manter a concisão, pode colar o seu anterior)
    })();
});
</script>

<?php require_once '../../includes/footer_dashboard.php'; ?>