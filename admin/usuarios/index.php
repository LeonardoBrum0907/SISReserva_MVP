<?php
// api/usuarios/index.php - Gestão de Usuários (Admin Master)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../includes/alerts.php'; // Para usar create_alert

// --- AQUI É ONDE VOCÊ DEVE ADICIONAR O CÓDIGO DA CONEXÃO ---
try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em api/usuarios/index.php: " . $e->getMessage());
    die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente mais tarde.");
}
// --- FIM DO CÓDIGO DE CONEXÃO ---

// Requer permissão de Admin Master
require_permission(['admin']);

$page_title = "Gestão de Usuários";

// Obter informações do usuário logado (para auditoria)
$logged_user_info = get_user_info();
$current_user_id = $logged_user_info['id'] ?? null;
$current_user_name = $logged_user_info['nome'] ?? 'Sistema';
$current_user_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';


$users = [];
$errors = [];

// Parâmetros de filtro e ordenação da URL
$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$filter_type = filter_input(INPUT_GET, 'type', FILTER_UNSAFE_RAW); // 'admin', 'corretor_autonomo', etc.
$filter_status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW); // 'aprovado', 'pendente', 'ativo', 'inativo'
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_UNSAFE_RAW);
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_UNSAFE_RAW) === 'desc' ? 'DESC' : 'ASC';


// ======================================================================================================
// Lógica de KPIs para Gestão de Usuários
// ======================================================================================================
$total_usuarios = 0;
$usuarios_pendentes_aprovacao = 0;
$usuarios_ativos_total = 0;
$corretores_ativos = 0;
$admins_ativos = 0;


try {
    // KPI: Total de Usuários Cadastrados
    $sql_total_users = "SELECT COUNT(id) AS total FROM usuarios";
    $total_usuarios = fetch_single($sql_total_users)['total'] ?? 0;

    // KPI: Usuários Pendentes de Aprovação
    $sql_pending_users = "SELECT COUNT(id) AS total FROM usuarios WHERE aprovado = FALSE";
    $usuarios_pendentes_aprovacao = fetch_single($sql_pending_users)['total'] ?? 0;

    // KPI: Usuários Ativos (geral)
    $sql_active_users = "SELECT COUNT(id) AS total FROM usuarios WHERE ativo = TRUE";
    $usuarios_ativos_total = fetch_single($sql_active_users)['total'] ?? 0;

    // KPI: Corretores Ativos
    $sql_active_brokers = "SELECT COUNT(id) AS total FROM usuarios WHERE ativo = TRUE AND tipo LIKE 'corretor_%'";
    $corretores_ativos = fetch_single($sql_active_brokers)['total'] ?? 0;

    // KPI: Admins Ativos
    $sql_active_admins = "SELECT COUNT(id) AS total FROM usuarios WHERE ativo = TRUE AND tipo IN ('admin', 'admin_imobiliaria')";
    $admins_ativos = fetch_single($sql_active_admins)['total'] ?? 0;

} catch (Exception $e) {
    error_log("Erro ao carregar KPIs de usuários: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar os indicadores de usuários: " . $e->getMessage();
}


// ======================================================================================================
// Lógica de Busca e Filtro para a Tabela de Usuários
// ======================================================================================================

try {
    $sql_users = "SELECT u.id, u.nome, u.email, u.cpf, u.creci, u.telefone, u.tipo, u.aprovado, u.ativo, u.data_cadastro, u.data_aprovacao, i.nome AS imobiliaria_nome FROM usuarios u LEFT JOIN imobiliarias i ON u.imobiliaria_id = i.id WHERE 1=1";
    $params = [];
    $types = "";

    if ($search_term) {
        $sql_users .= " AND (u.nome LIKE ? OR u.email LIKE ? OR u.cpf LIKE ? OR u.creci LIKE ?)";
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
        $types .= "ssss";
    }

    if ($filter_type) {
        $sql_users .= " AND u.tipo = ?";
        $params[] = $filter_type;
        $types .= "s";
    }

    if ($filter_status) {
        if ($filter_status === 'aprovado') {
            $sql_users .= " AND u.aprovado = TRUE AND u.ativo = TRUE";
        } elseif ($filter_status === 'pendente') {
            $sql_users .= " AND u.aprovado = FALSE"; // Não precisa de ativo=TRUE aqui, pois se não está aprovado, está pendente
        } elseif ($filter_status === 'ativo') { // Filtro para qualquer usuário 'ativo'
            $sql_users .= " AND u.ativo = TRUE";
        } elseif ($filter_status === 'inativo') {
            $sql_users .= " AND u.ativo = FALSE";
        }
    }

    // Adiciona ordenação
    $order_clause = "u.data_cadastro DESC"; // Padrão
    if ($sort_by) {
        $db_column_map = [
            'id' => 'u.id',
            'nome' => 'u.nome',
            'email' => 'u.email',
            'tipo' => 'u.tipo',
            'imobiliaria_nome' => 'imobiliaria_nome',
            'aprovado' => 'u.aprovado',
            'ativo' => 'u.ativo',
            'data_cadastro' => 'u.data_cadastro',
        ];
        if (isset($db_column_map[$sort_by])) {
            $order_clause = $db_column_map[$sort_by] . " " . $sort_order;
        }
    }
    $sql_users .= " ORDER BY {$order_clause}";

    if (empty($params)) {
        $users = fetch_all($sql_users);
    } else {
        $users = fetch_all($sql_users, $params, $types);
    }

} catch (Exception $e) {
    error_log("Erro ao carregar usuários: " . $e->getMessage());
    $errors[] = "Ocorreu um erro ao carregar a lista de usuários: " . $e->getMessage();
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
            <span class="kpi-label">Total de Usuários</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_usuarios); ?></span>
        </div>
        <div class="kpi-card kpi-card-urgent">
            <span class="kpi-label">Pendentes de Aprovação</span>
            <span class="kpi-value"><?php echo htmlspecialchars($usuarios_pendentes_aprovacao); ?></span>
            <small>Aguardando sua ação</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Usuários Ativos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($usuarios_ativos_total); ?></span>
            <small>Total de usuários com acesso</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Corretores Ativos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($corretores_ativos); ?></span>
            <small>Prontos para vender</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Admins Ativos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($admins_ativos); ?></span>
            <small>Gerenciando o sistema</small>
        </div>
    </div>


    <div class="admin-controls-bar">
        <div class="search-box">
            <input type="text" id="userSearch" name="search" placeholder="Buscar usuário..." value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
            <i class="fas fa-search"></i>
        </div>
        <div class="filters-box">
            <select id="userFilterType" name="type">
                <option value="">Todos os Tipos</option>
                <option value="admin" <?php echo ($filter_type === 'admin') ? 'selected' : ''; ?>>Admin Master</option>
                <option value="admin_imobiliaria" <?php echo ($filter_type === 'admin_imobiliaria') ? 'selected' : ''; ?>>Admin Imobiliária</option>
                <option value="corretor_autonomo" <?php echo ($filter_type === 'corretor_autonomo') ? 'selected' : ''; ?>>Corretor Autônomo</option>
                <option value="corretor_imobiliaria" <?php echo ($filter_type === 'corretor_imobiliaria') ? 'selected' : ''; ?>>Corretor Imobiliária</option>
            </select>
            <select id="userFilterStatus" name="status">
                <option value="">Todos os Status</option>
                <option value="aprovado" <?php echo ($filter_status === 'aprovado') ? 'selected' : ''; ?>>Aprovados</option>
                <option value="pendente" <?php echo ($filter_status === 'pendente') ? 'selected' : ''; ?>>Pendentes</option>
                <option value="ativo" <?php echo ($filter_status === 'ativo') ? 'selected' : ''; ?>>Ativos (Geral)</option>
                <option value="inativo" <?php echo ($filter_status === 'inativo') ? 'selected' : ''; ?>>Inativos</option>
            </select>
            <button class="btn btn-primary" id="applyUserFiltersBtn">Aplicar Filtros</button>
            <a href="criar.php" class="btn btn-success">Adicionar Novo Usuário</a>
        </div>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table" id="usersTable">
            <thead>
                <tr>
                    <th data-sort-by="id">ID <i class="fas fa-sort"></i></th>
                    <th data-sort-by="nome">Nome <i class="fas fa-sort"></i></th>
                    <th data-sort-by="email">Email <i class="fas fa-sort"></i></th>
                    <th data-sort-by="tipo">Tipo <i class="fas fa-sort"></i></th>
                    <th data-sort-by="imobiliaria_nome">Imobiliária <i class="fas fa-sort"></i></th>
                    <th data-sort-by="aprovado">Aprovação <i class="fas fa-sort"></i></th>
                    <th data-sort-by="ativo">Ativo <i class="fas fa-sort"></i></th>
                    <th data-sort-by="data_cadastro">Cadastro <i class="fas fa-sort"></i></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="9" style="text-align: center;">Nenhum usuário encontrado com os filtros aplicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr data-user-id="<?php echo htmlspecialchars($user['id']); ?>"
                            data-user-aprovado="<?php echo $user['aprovado'] ? 'aprovado' : 'pendente'; ?>"
                            data-user-ativo="<?php echo $user['ativo'] ? 'ativo' : 'inativo'; ?>">
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['nome']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['tipo']))); ?></td>
                            <td><?php echo htmlspecialchars($user['imobiliaria_nome'] ?? 'N/A'); ?></td>
                            <td><span class="status-badge status-<?php echo ($user['aprovado'] ?? 0) ? 'success' : 'warning'; ?>"><?php echo ($user['aprovado'] ?? 0) ? 'Aprovado' : 'Pendente'; ?></span></td>
                            <td><span class="status-badge status-<?php echo ($user['ativo'] ?? 0) ? 'success' : 'danger'; ?>"><?php echo ($user['ativo'] ?? 0) ? 'Ativo' : 'Inativo'; ?></span></td>
                            <td><?php echo htmlspecialchars(format_datetime_br($user['data_cadastro'])); ?></td>
                            <td class="admin-table-actions">
                                <a href="editar.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-secondary btn-sm">Editar</a>
                                <a href="detalhes.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-info btn-sm">Detalhes</a>
                                <?php if (!($user['aprovado'] ?? 0)): // Se não aprovado (pendente) ?>
                                    <button class="btn btn-success btn-sm approve-user" data-id="<?php echo htmlspecialchars($user['id']); ?>">Aprovar</button>
                                    <button class="btn btn-danger btn-sm reject-user" data-id="<?php echo htmlspecialchars($user['id']); ?>">Rejeitar</button>
                                <?php elseif (($user['ativo'] ?? 0)): // Se aprovado E ativo, pode inativar ?>
                                    <button class="btn btn-warning btn-sm deactivate-user" data-id="<?php echo htmlspecialchars($user['id']); ?>">Inativar</button>
                                <?php else: // Se aprovado E inativo, pode ativar ?>
                                    <button class="btn btn-success btn-sm activate-user" data-id="<?php echo htmlspecialchars($user['id']); ?>">Ativar</button>
                                <?php endif; ?>
                                <button class="btn btn-danger btn-sm delete-user" data-id="<?php echo htmlspecialchars($user['id']); ?>">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="rejectUserModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rejeitar Usuário</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja **rejeitar** o usuário <strong id="rejectUserName"></strong>? Esta ação é irreversível.</p>
                <form id="rejectUserForm" method="POST" action="<?php echo BASE_URL; ?>api/processa_usuario.php">
                    <input type="hidden" name="user_id" id="rejectUserId">
                    <input type="hidden" name="action" value="rejeitar">
                    <div class="form-group mt-3">
                        <label for="motivo_rejeicao">Motivo da Rejeição (Obrigatório):</label>
                        <textarea id="motivo_rejeicao" name="motivo_rejeicao" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Rejeição</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="inactivateUserModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Inativar Usuário</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja **inativar** o usuário <strong id="inactivateUserName"></strong>? Ele perderá o acesso ao sistema.</p>
                <form id="inactivateUserForm" method="POST" action="<?php echo BASE_URL; ?>api/processa_usuario.php">
                    <input type="hidden" name="user_id" id="inactivateUserId">
                    <input type="hidden" name="action" value="inativar">
                    <div class="form-group mt-3">
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

    <div id="approveUserModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Aprovar Usuário</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja aprovar o usuário <strong id="approveUserName"></strong>?</p>
                <form id="approveUserForm" method="POST" action="<?php echo BASE_URL; ?>api/processa_usuario.php">
                    <input type="hidden" name="user_id" id="approveUserId">
                    <input type="hidden" name="action" value="aprovar">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close-btn">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Aprovação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteUserModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Excluir Usuário</h3>
                <button type="button" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja EXCLUIR o usuário <strong id="deleteUserName"></strong>?</p>
                <p><strong>Esta ação é irreversível</strong> e removerá o usuário, seus alertas e *todas as reservas e clientes vinculados a ele* se as regras de chave estrangeira permitirem.</p>
                <form id="deleteUserForm" method="POST" action="<?php echo BASE_URL; ?>api/processa_usuario.php">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="action" value="excluir">
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