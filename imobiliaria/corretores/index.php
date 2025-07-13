<?php
// imobiliaria/corretores/index.php - Gestão de Corretores pela Imobiliária

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatar_cpf, format_whatsapp, format_datetime_br

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/corretores/index.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Meus Corretores";

$logged_user_info = get_user_info();
$imobiliaria_id = null;
$errors = [];

// Lógica para obter o ID da imobiliária vinculada ao admin_imobiliaria logado
if ($logged_user_info && $logged_user_info['type'] === 'admin_imobiliaria') {
    $imobiliaria_data = fetch_single("SELECT id FROM imobiliarias WHERE admin_id = ?", [$logged_user_info['id']], "i");
    if ($imobiliaria_data) {
        $imobiliaria_id = $imobiliaria_data['id'];
    } else {
        // Se a imobiliária do admin logado não for encontrada, redireciona com erro.
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Nenhuma imobiliária encontrada vinculada ao seu usuário. Por favor, contate o suporte.'];
        header("Location: " . BASE_URL . "imobiliaria/index.php");
        exit();
    }
} else {
    // Isso não deveria ser alcançado devido ao require_permission, mas é um fallback defensivo.
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Acesso negado. Tipo de usuário inválido para esta área.'];
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$corretores = [];
$total_corretores_pendentes = 0;
$total_corretores_aprovados = 0;
$total_corretores_inativos = 0;

// Parâmetros de filtro e ordenação da URL
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW); // Use FILTER_UNSAFE_RAW para strings
$filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW); // 'pendente', 'aprovado', 'inativo'
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';

if ($imobiliaria_id) {
    try {
        // KPIs para a imobiliária (todos os corretores vinculados a ela)
        $sql_count_pendentes = "SELECT COUNT(id) AS total FROM usuarios WHERE imobiliaria_id = ? AND aprovado = FALSE AND ativo = TRUE";
        $result = fetch_single($sql_count_pendentes, [$imobiliaria_id], "i");
        $total_corretores_pendentes = $result['total'] ?? 0;

        $sql_count_aprovados = "SELECT COUNT(id) AS total FROM usuarios WHERE imobiliaria_id = ? AND aprovado = TRUE AND ativo = TRUE";
        $result = fetch_single($sql_count_aprovados, [$imobiliaria_id], "i");
        $total_corretores_aprovados = $result['total'] ?? 0;

        $sql_count_inativos = "SELECT COUNT(id) AS total FROM usuarios WHERE imobiliaria_id = ? AND ativo = FALSE";
        $result = fetch_single($sql_count_inativos, [$imobiliaria_id], "i");
        $total_corretores_inativos = $result['total'] ?? 0;

        // Construção da query de listagem de corretores
        $sql_corretores = "SELECT id, nome, email, cpf, creci, telefone, tipo, aprovado, ativo, data_cadastro, data_atualizacao FROM usuarios WHERE imobiliaria_id = ?";
        $params = [$imobiliaria_id];
        $types = "i";

        if ($search_term) {
            $sql_corretores .= " AND (nome LIKE ? OR email LIKE ? OR cpf LIKE ? OR creci LIKE ? OR telefone LIKE ?)";
            $params[] = '%' . $search_term . '%';
            $params[] = '%' . $search_term . '%';
            $params[] = '%' . $search_term . '%';
            $params[] = '%' . $search_term . '%';
            $params[] = '%' . $search_term . '%';
            $types .= "sssss";
        }

        if ($filter_status) {
            if ($filter_status === 'pendente') {
                $sql_corretores .= " AND aprovado = FALSE AND ativo = TRUE";
            } elseif ($filter_status === 'aprovado') {
                $sql_corretores .= " AND aprovado = TRUE AND ativo = TRUE";
            } elseif ($filter_status === 'inativo') {
                $sql_corretores .= " AND ativo = FALSE";
            }
        }

        // Adiciona ordenação
        $order_clause = "nome ASC"; // Padrão
        if ($sort_by) {
            $db_column_map = [
                'id' => 'id',
                'nome' => 'nome',
                'email' => 'email',
                'cpf' => 'cpf',
                'creci' => 'creci',
                'tipo' => 'tipo',
                'aprovado' => 'aprovado',
                'ativo' => 'ativo',
                'data_cadastro' => 'data_cadastro',
            ];
            if (isset($db_column_map[$sort_by])) {
                $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
            }
        }
        $sql_corretores .= " ORDER BY {$order_clause}";

        $corretores = fetch_all($sql_corretores, $params, $types);

    } catch (Exception $e) {
        error_log("Erro ao carregar corretores da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar seus corretores: " . $e->getMessage();
    }
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
        <?php unset($_SESSION['message']); // Limpa a mensagem após exibir
    endif;
    ?>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Corretores Pendentes</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_corretores_pendentes); ?></span>
            <a href="?status=pendente" class="kpi-action-link">Ver Pendentes</a>
        </div>
        <div class="kpi-card kpi-card-success">
            <span class="kpi-label">Corretores Aprovados</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_corretores_aprovados); ?></span>
            <a href="?status=aprovado" class="kpi-action-link">Ver Aprovados</a>
        </div>
        <div class="kpi-card kpi-card-danger">
            <span class="kpi-label">Corretores Inativos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_corretores_inativos); ?></span>
            <a href="?status=inativo" class="kpi-action-link">Ver Inativos</a>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Total de Corretores</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_corretores_pendentes + $total_corretores_aprovados + $total_corretores_inativos); ?></span>
        </div>
    </div>


    <div class="admin-controls-bar">
        <div class="search-box">
            <input type="text" id="corretorSearch" name="search" placeholder="Buscar corretor..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
            <i class="fas fa-search"></i>
        </div>
        <div class="filters-box">
            <select id="corretorFilterStatus" name="status">
                <option value="">Todos os Status</option>
                <option value="pendente" <?php echo ($filter_status === 'pendente') ? 'selected' : ''; ?>>Pendentes</option>
                <option value="aprovado" <?php echo ($filter_status === 'aprovado') ? 'selected' : ''; ?>>Aprovados</option>
                <option value="inativo" <?php echo ($filter_status === 'inativo') ? 'selected' : ''; ?>>Inativos</option>
            </select>
            <button class="btn btn-primary" id="applyCorretorFiltersBtn">Aplicar Filtros</button>
            <a href="criar.php" class="btn btn-success">Adicionar Novo Corretor</a>
        </div>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="corretoresTable">
            <thead>
                <tr>
                    <th data-sort-by="id">ID <i class="fas fa-sort"></i></th>
                    <th data-sort-by="nome">Nome <i class="fas fa-sort"></i></th>
                    <th data-sort-by="email">Email <i class="fas fa-sort"></i></th>
                    <th data-sort-by="cpf">CPF <i class="fas fa-sort"></i></th>
                    <th data-sort-by="creci">CRECI <i class="fas fa-sort"></i></th>
                    <th data-sort-by="tipo">Tipo <i class="fas fa-sort"></i></th>
                    <th data-sort-by="aprovado">Aprovação <i class="fas fa-sort"></i></th>
                    <th data-sort-by="ativo">Ativo <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_cadastro">Cadastro <i class="fas fa-sort"></i></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($corretores)): ?>
                    <tr><td colspan="10" style="text-align: center;">Nenhum corretor encontrado para esta imobiliária com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($corretores as $corretor): ?>
                        <tr data-corretor-id="<?php echo htmlspecialchars($corretor['id']); ?>"
                            data-corretor-aprovado="<?php echo $corretor['aprovado'] ? 'aprovado' : 'pendente'; ?>"
                            data-corretor-ativo="<?php echo $corretor['ativo'] ? 'ativo' : 'inativo'; ?>">
                            <td><?php echo htmlspecialchars($corretor['id']); ?></td>
                            <td><?php echo htmlspecialchars($corretor['nome']); ?></td>
                            <td><?php echo htmlspecialchars($corretor['email']); ?></td>
                            <td><?php echo htmlspecialchars(format_cpf($corretor['cpf'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($corretor['creci'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $corretor['tipo']))); ?></td>
                            <td><span class="status-badge status-<?php echo $corretor['aprovado'] ? 'success' : 'warning'; ?>"><?php echo $corretor['aprovado'] ? 'Aprovado' : 'Pendente'; ?></span></td>
                            <td><span class="status-badge status-<?php echo $corretor['ativo'] ? 'success' : 'danger'; ?>"><?php echo $corretor['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($corretor['data_cadastro'])); ?></td>
                            <td class="admin-table-actions">
                                <a href="detalhes.php?id=<?php echo htmlspecialchars($corretor['id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                <a href="editar.php?id=<?php echo htmlspecialchars($corretor['id']); ?>" class="btn btn-secondary btn-sm">Editar</a>
                                <?php if (!$corretor['aprovado']): ?>
                                    <button class="btn btn-success btn-sm approve-realtor" data-id="<?php echo htmlspecialchars($corretor['id']); ?>" data-nome="<?php echo htmlspecialchars($corretor['nome']); ?>">Aprovar</button>
                                    <button class="btn btn-danger btn-sm reject-realtor" data-id="<?php echo htmlspecialchars($corretor['id']); ?>" data-nome="<?php echo htmlspecialchars($corretor['nome']); ?>">Rejeitar</button>
                                <?php elseif ($corretor['ativo']): ?>
                                    <button class="btn btn-warning btn-sm deactivate-realtor" data-id="<?php echo htmlspecialchars($corretor['id']); ?>" data-nome="<?php echo htmlspecialchars($corretor['nome']); ?>">Inativar</button>
                                <?php else: ?>
                                    <button class="btn btn-success btn-sm activate-realtor" data-id="<?php echo htmlspecialchars($corretor['id']); ?>" data-nome="<?php echo htmlspecialchars($corretor['nome']); ?>">Ativar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="approveCorretorModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Aprovar Corretor</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja aprovar o corretor <strong id="approveCorretorName"></strong>?</p>
                <form id="approveCorretorForm" method="POST" action="<?php echo BASE_URL; ?>imobiliaria/processa_corretor.php">
                    <input type="hidden" name="id" id="approveCorretorId"> <input type="hidden" name="action" value="aprovar"> 
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Aprovação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="rejectCorretorModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rejeitar Corretor</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja rejeitar o corretor <strong id="rejectCorretorName"></strong>? Esta ação é irreversível.</p>
                <form id="rejectCorretorForm" method="POST" action="<?php echo BASE_URL; ?>imobiliaria/processa_corretor.php">
                    <input type="hidden" name="id" id="rejectCorretorId"> <input type="hidden" name="action" value="rejeitar"> 
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Rejeição</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deactivateCorretorModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Inativar Corretor</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja inativar o corretor <strong id="deactivateCorretorName"></strong>? Ele perderá o acesso ao sistema.</p>
                <form id="deactivateCorretorForm" method="POST" action="<?php echo BASE_URL; ?>imobiliaria/processa_corretor.php">
                    <input type="hidden" name="id" id="deactivateCorretorId"> <input type="hidden" name="action" value="inativar"> 
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Confirmar Inativação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="activateCorretorModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ativar Corretor</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja ativar o corretor <strong id="activateCorretorName"></strong>? Ele terá acesso novamente.</p>
                <form id="activateCorretorForm" method="POST" action="<?php echo BASE_URL; ?>imobiliaria/processa_corretor.php">
                    <input type="hidden" name="id" id="activateCorretorId"> <input type="hidden" name="action" value="ativar"> 
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Ativação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>