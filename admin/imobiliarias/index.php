<?php
// admin/imobiliarias/index.php - Gestão de Imobiliárias (Admin Master)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../includes/alerts.php';

// Conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/imobiliarias/index.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Master
require_permission(['admin']);

$page_title = "Gestão de Imobiliárias";

$imobiliarias = [];
$errors = [];

// Parâmetros de filtro e ordenação da URL
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW); // 'ativa', 'inativa'
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';


// ======================================================================================================
// Lógica de KPIs para Gestão de Imobiliárias
// ======================================================================================================
$total_imobiliarias = 0;
$imobiliarias_ativas = 0;
$total_corretores_vinculados = 0; // NOVO KPI
$top_5_imobiliarias_vendas = []; // Para o KPI de ranking

try {
    // KPI: Total de Imobiliárias Cadastradas
    $sql_total_imob = "SELECT COUNT(id) AS total FROM imobiliarias";
    $total_imobiliarias = fetch_single($sql_total_imob)['total'] ?? 0;

    // KPI: Imobiliárias Ativas
    $sql_active_imob = "SELECT COUNT(id) AS total FROM imobiliarias WHERE ativa = TRUE";
    $imobiliarias_ativas = fetch_single($sql_active_imob)['total'] ?? 0;

    // NOVO KPI: Total de Corretores Vinculados a Imobiliárias (qualquer status, tipo corretor_imobiliaria)
    $sql_total_corretores_imob = "SELECT COUNT(id) AS total FROM usuarios WHERE imobiliaria_id IS NOT NULL AND tipo LIKE 'corretor_%'";
    $total_corretores_vinculados = fetch_single($sql_total_corretores_imob)['total'] ?? 0;

    // KPI: Top 5 Imobiliárias que Mais Venderam (no mês atual)
    $sql_top_imob = "
        SELECT
            i.nome AS imobiliaria_nome,
            COUNT(r.id) AS total_vendas
        FROM
            imobiliarias i
        JOIN
            usuarios u ON i.id = u.imobiliaria_id
        JOIN
            reservas r ON u.id = r.corretor_id
        WHERE
            r.status = 'vendida'
            AND DATE(r.data_ultima_interacao) BETWEEN ? AND ?
        GROUP BY
            i.id, i.nome
        ORDER BY
            total_vendas DESC
        LIMIT 5;
    ";
    $top_5_imobiliarias_vendas = fetch_all($sql_top_imob, [date('Y-m-01'), date('Y-m-t')], "ss");

} catch (Exception $e) {
    error_log("Erro ao carregar KPIs de imobiliárias: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar os indicadores de imobiliárias: " . $e->getMessage();
}


// ======================================================================================================
// Lógica de Busca e Filtro para a Tabela de Imobiliárias
// ======================================================================================================
try {
    // A query SQL continua selecionando todas as colunas necessárias para o filtro e ordenação,
    // mesmo que algumas não sejam exibidas na tabela.
    $sql_imobiliarias = "SELECT i.id, i.nome, i.cnpj, i.email, i.telefone, i.cidade, i.estado, i.ativa, i.data_cadastro, i.data_atualizacao, u.nome AS admin_nome FROM imobiliarias i LEFT JOIN usuarios u ON i.admin_id = u.id WHERE 1=1";
    $params = [];
    $types = "";

    if ($search_term) {
        $sql_imobiliarias .= " AND (i.nome LIKE ? OR i.cnpj LIKE ? OR i.email LIKE ? OR i.cidade LIKE ? OR u.nome LIKE ?)";
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $types .= "sssss";
    }

    if ($filter_status) {
        if ($filter_status === 'ativa') {
            $sql_imobiliarias .= " AND i.ativa = TRUE";
        } elseif ($filter_status === 'inativa') {
            $sql_imobiliarias .= " AND i.ativa = FALSE";
        }
    }

    // Adiciona ordenação
    $order_clause = "i.data_cadastro DESC"; // Padrão
    if ($sort_by) {
        $db_column_map = [
            'id' => 'i.id',
            'nome' => 'i.nome',
            'cnpj' => 'i.cnpj',
            'email' => 'i.email',
            'cidade' => 'i.cidade',
            'ativa' => 'i.ativa',
            'data_cadastro' => 'i.data_cadastro',
            'data_atualizacao' => 'i.data_atualizacao',
            'admin_nome' => 'admin_nome'
        ];
        if (isset($db_column_map[$sort_by])) {
            $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
        }
    }
    $sql_imobiliarias .= " ORDER BY {$order_clause}";

    if (empty($params)) {
        $imobiliarias = fetch_all($sql_imobiliarias);
    } else {
        $imobiliarias = fetch_all($sql_imobiliarias, $params, $types);
    }

} catch (Exception $e) {
    error_log("Erro ao carregar imobiliárias: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar a lista de imobiliárias: " . $e->getMessage();
}

require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="message-box message-box-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    // Exibir mensagens de feedback da sessão
    if (isset($_SESSION['message'])): ?>
        <div class="message-box message-box-<?php echo $_SESSION['message']['type']; ?>">
            <?php echo $_SESSION['message']['text']; ?>
        </div>
        <?php unset($_SESSION['message']);
    endif;
    ?>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Total de Imobiliárias</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_imobiliarias); ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Imobiliárias Ativas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($imobiliarias_ativas); ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Corretores Vinculados</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_corretores_vinculados); ?></span>
        </div>
        <div class="kpi-card wide-card">
            <span class="kpi-label">Top 5 Vendas no Mês (Imobiliárias)</span>
            <ul class="kpi-list">
                <?php if (!empty($top_5_imobiliarias_vendas)): ?>
                    <?php foreach ($top_5_imobiliarias_vendas as $rank => $imob_data): ?>
                        <li><?php echo ($rank + 1); ?>. <?php echo htmlspecialchars($imob_data['imobiliaria_nome']); ?>: <?php echo htmlspecialchars($imob_data['total_vendas']); ?> vendas</li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>Nenhuma venda registrada no mês.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="admin-controls-bar">
        <div class="search-box">
            <input type="text" id="imobiliariaSearch" name="search" placeholder="Buscar imobiliária..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
            <i class="fas fa-search"></i>
        </div>
        <div class="filters-box">
            <select id="imobiliariaFilterStatus" name="status">
                <option value="">Todos os Status</option>
                <option value="ativa" <?php echo ($filter_status === 'ativa') ? 'selected' : ''; ?>>Ativas</option>
                <option value="inativa" <?php echo ($filter_status === 'inativa') ? 'selected' : ''; ?>>Inativas</option>
            </select>
            <button class="btn btn-primary" id="applyImobiliariaFiltersBtn">Aplicar Filtros</button>
            <a href="criar.php" class="btn btn-success">Adicionar Nova Imobiliária</a>
        </div>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="imobiliariasTable">
            <thead>
                <tr>
                    <th data-sort-by="id">ID <i class="fas fa-sort"></i></th>
                    <th data-sort-by="nome">Nome <i class="fas fa-sort"></i></th>
                    <th data-sort-by="cidade">Cidade <i class="fas fa-sort"></i></th>
                    <th data-sort-by="estado">Estado <i class="fas fa-sort"></i></th>
                    <th data-sort-by="ativa">Status <i class="fas fa-sort"></i></th>
                    <th data-sort-by="admin_nome">Admin Responsável <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_atualizacao">Última Atualização <i class="fas fa-sort"></i></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($imobiliarias)): ?>
                    <tr><td colspan="8" style="text-align: center;">Nenhuma imobiliária encontrada com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($imobiliarias as $imob): ?>
                        <tr data-imobiliaria-id="<?php echo htmlspecialchars($imob['id']); ?>"
                            data-imobiliaria-ativa="<?php echo $imob['ativa'] ? 'ativa' : 'inativa'; ?>">
                            <td><?php echo htmlspecialchars($imob['id']); ?></td>
                            <td><?php echo htmlspecialchars($imob['nome']); ?></td>
                            <td><?php echo htmlspecialchars($imob['cidade'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($imob['estado'] ?? 'N/A'); ?></td>
                            <td><span class="status-badge status-<?php echo ($imob['ativa'] ?? 0) ? 'success' : 'danger'; ?>"><?php echo ($imob['ativa'] ?? 0) ? 'Ativa' : 'Inativa'; ?></span></td>
                            <td><?php echo htmlspecialchars($imob['admin_nome'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($imob['data_atualizacao'])); ?></td>
                            <td class="admin-table-actions">
                                <a href="editar.php?id=<?php echo htmlspecialchars($imob['id']); ?>" class="btn btn-secondary btn-sm">Editar</a>
                                <a href="detalhes.php?id=<?php echo htmlspecialchars($imob['id']); ?>" class="btn btn-info btn-sm">Detalhes</a>
                                <?php if (($imob['ativa'] ?? 0)): ?>
                                    <button class="btn btn-warning btn-sm deactivate-imobiliaria" data-id="<?php echo htmlspecialchars($imob['id']); ?>">Inativar</button>
                                <?php else: ?>
                                    <button class="btn btn-success btn-sm activate-imobiliaria" data-id="<?php echo htmlspecialchars($imob['id']); ?>">Ativar</button>
                                <?php endif; ?>
                                <button class="btn btn-danger btn-sm delete-imobiliaria" data-id="<?php echo htmlspecialchars($imob['id']); ?>">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="activateImobiliariaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ativar Imobiliária</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja ativar a imobiliária <strong id="activateImobiliariaName"></strong>?</p>
                <form id="activateImobiliariaForm" method="POST" action="">
                    <input type="hidden" name="imobiliaria_id" id="activateImobiliariaId">
                    <input type="hidden" name="action" value="activate_imobiliaria">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Ativação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deactivateImobiliariaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Inativar Imobiliária</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja inativar a imobiliária <strong id="deactivateImobiliariaName"></strong>? Corretores vinculados podem perder acesso.</p>
                <form id="deactivateImobiliariaForm" method="POST" action="">
                    <input type="hidden" name="imobiliaria_id" id="deactivateImobiliariaId">
                    <input type="hidden" name="action" value="inactivate_imobiliaria">
                    <div class="form-group">
                        <label for="motivo_inativacao">Motivo da Inativação (Opcional):</label>
                        <textarea id="motivo_inativacao" name="motivo_inativacao" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Confirmar Inativação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteImobiliariaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Excluir Imobiliária</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja EXCLUIR a imobiliária <strong id="deleteImobiliariaName"></strong>?</p>
                <p><strong>Esta ação é irreversível</strong> e removerá a imobiliária e pode afetar corretores vinculados (se não houver `ON DELETE SET NULL`).</p>
                <form id="deleteImobiliariaForm" method="POST" action="">
                    <input type="hidden" name="imobiliaria_id" id="deleteImobiliariaId">
                    <input type="hidden" name="action" value="delete_imobiliaria">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>