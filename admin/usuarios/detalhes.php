<?php
// admin/usuarios/detalhes.php - Página de Detalhes do Usuário (Admin Master)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação (CPF, WhatsApp, data)
require_once '../../includes/alerts.php';   // Para mensagens

// Inicializar a conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/usuarios/detalhes.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

require_permission(['admin']); // Apenas Admin Master pode acessar detalhes de usuários

$page_title = "Detalhes do Usuário";

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_data = null;
$imobiliaria_nome_vinculada = 'N/A';
$errors = [];
$success_message = '';

// Variáveis para KPIs de Corretor
$total_vendas_corretor = 0;
$valor_total_vendas_corretor = 0;
$comissao_corretor_total = 0;
$total_reservas_ativas_corretor = 0;
$ultimas_reservas_corretor = [];

// Recuperar mensagens da sessão, se houverem
if (isset($_SESSION['form_messages'])) {
    $errors = array_merge($errors, $_SESSION['form_messages']['errors'] ?? []);
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

if (!$user_id) {
    $errors[] = "ID do usuário não fornecido.";
    $user_id = 0; // Para evitar erros se o ID não for válido
} else {
    try {
        // Buscar dados do usuário
        $sql_user = "
            SELECT 
                u.id, u.nome, u.email, u.cpf, u.creci, u.telefone, u.tipo, u.aprovado, u.ativo, 
                u.data_cadastro, u.data_atualizacao, u.data_aprovacao, u.imobiliaria_id,
                i.nome AS imobiliaria_nome
            FROM 
                usuarios u
            LEFT JOIN
                imobiliarias i ON u.imobiliaria_id = i.id
            WHERE 
                u.id = ?;
        ";
        $user_data = fetch_single($sql_user, [$user_id], "i");

        if (!$user_data) {
            $errors[] = "Usuário não encontrado.";
            $user_id = 0;
        } else {
            $page_title .= ": " . htmlspecialchars($user_data['nome']);
            $imobiliaria_nome_vinculada = $user_data['imobiliaria_nome'] ?? 'N/A';

            // Se for um corretor, buscar KPIs e últimas reservas/vendas
            if (strpos($user_data['tipo'], 'corretor') !== false) {
                $current_month_start = date('Y-m-01 00:00:00');
                $current_month_end = date('Y-m-t 23:59:59');

                $sql_desempenho = "
                    SELECT
                        COALESCE(COUNT(CASE WHEN r.status = 'vendida' THEN r.id END), 0) AS total_vendas,
                        COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.valor_reserva ELSE 0 END), 0) AS valor_total,
                        COALESCE(SUM(CASE WHEN r.status = 'vendida' THEN r.comissao_corretor ELSE 0 END), 0) AS comissao_total,
                        COALESCE(COUNT(CASE WHEN r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada') THEN r.id END), 0) AS total_reservas_ativas
                    FROM
                        reservas r
                    WHERE
                        r.corretor_id = ? AND r.data_ultima_interacao BETWEEN ? AND ?;
                ";
                $result_desempenho = fetch_single($sql_desempenho, [$user_id, $current_month_start, $current_month_end], "iss");

                $total_vendas_corretor = $result_desempenho['total_vendas'] ?? 0;
                $valor_total_vendas_corretor = $result_desempenho['valor_total'] ?? 0;
                $comissao_corretor_total = $result_desempenho['comissao_total'] ?? 0;
                $total_reservas_ativas_corretor = $result_desempenho['total_reservas_ativas'] ?? 0;

                // Últimas Reservas e Vendas do Corretor
                $sql_ultimas_reservas = "
                    SELECT DISTINCT
                        r.id AS reserva_id,
                        r.data_reserva,
                        r.valor_reserva,
                        r.status,
                        r.data_ultima_interacao,
                        COALESCE(cl.nome, 'N/A') AS cliente_nome,
                        e.nome AS empreendimento_nome,
                        u.numero AS unidade_numero,
                        u.andar AS unidade_andar,
                        u.posicao AS unidade_posicao
                    FROM
                        reservas r
                    LEFT JOIN
                        reservas_clientes rc ON r.id = rc.reserva_id
                    LEFT JOIN
                        clientes cl ON rc.cliente_id = cl.id
                    JOIN
                        unidades u ON r.unidade_id = u.id
                    JOIN
                        empreendimentos e ON u.empreendimento_id = e.id
                    WHERE
                        r.corretor_id = ?
                    ORDER BY
                        r.data_ultima_interacao DESC
                    LIMIT 5; -- Últimas 5 reservas/vendas
                ";
                $ultimas_reservas_corretor = fetch_all($sql_ultimas_reservas, [$user_id], "i");
            }
            
            // Histórico de Auditoria para este usuário (ações realizadas por E sobre este usuário)
            $sql_auditoria = "
                SELECT
                    a.acao,
                    a.detalhes,
                    a.data_acao,
                    COALESCE(u_acao.nome, 'Sistema/Desconhecido') AS usuario_acao_nome,
                    a.ip_origem
                FROM
                    auditoria a
                LEFT JOIN
                    usuarios u_acao ON a.usuario_id = u_acao.id
                WHERE
                    a.usuario_id = ? OR (a.entidade = 'Usuario' AND a.entidade_id = ?)
                ORDER BY
                    a.data_acao DESC
                LIMIT 10;
            ";
            $historico_auditoria = fetch_all($sql_auditoria, [$user_id, $user_id], "ii");

        }
    } catch (Exception $e) {
        error_log("Erro ao carregar detalhes do usuário: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os detalhes do usuário: " . $e->getMessage();
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

    <?php if (!empty($success_message)): ?>
        <div class="message-box message-box-success">
            <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($user_data): ?>
    <div class="details-section">
        <h3>Dados Cadastrais</h3>
        <div class="details-grid">
            <p><strong>ID:</strong> <?php echo htmlspecialchars($user_data['id']); ?></p>
            <p><strong>Nome Completo:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
            <p><strong>Telefone:</strong> <?php echo htmlspecialchars(format_whatsapp($user_data['telefone'] ?? 'N/A')); ?></p>
            <p><strong>CPF:</strong> <?php echo htmlspecialchars(format_cpf($user_data['cpf'] ?? 'N/A')); ?></p>
            <p><strong>CRECI:</strong> <?php echo htmlspecialchars($user_data['creci'] ?? 'N/A'); ?></p>
            <p><strong>Tipo de Usuário:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user_data['tipo']))); ?></p>
            <p><strong>Imobiliária Vinculada:</strong> <?php echo htmlspecialchars($imobiliaria_nome_vinculada); ?></p>
            <p><strong>Status Aprovação:</strong> <span class="status-badge status-<?php echo ($user_data['aprovado'] ?? 0) ? 'success' : 'warning'; ?>"><?php echo ($user_data['aprovado'] ?? 0) ? 'Aprovado' : 'Pendente'; ?></span></p>
            <p><strong>Status Ativo:</strong> <span class="status-badge status-<?php echo ($user_data['ativo'] ?? 0) ? 'success' : 'danger'; ?>"><?php echo ($user_data['ativo'] ?? 0) ? 'Ativo' : 'Inativo'; ?></span></p>
            <p><strong>Data de Cadastro:</strong> <?php echo htmlspecialchars(format_datetime_br($user_data['data_cadastro'])); ?></p>
            <p><strong>Última Atualização:</strong> <?php echo htmlspecialchars(format_datetime_br($user_data['data_atualizacao'])); ?></p>
            <?php if (!empty($user_data['data_aprovacao'])): ?>
            <p><strong>Data de Aprovação:</strong> <?php echo htmlspecialchars(format_datetime_br($user_data['data_aprovacao'])); ?></p>
            <?php endif; ?>
        </div>
        <div class="form-actions" style="justify-content: flex-start; margin-top: var(--spacing-xl);">
            <a href="<?php echo BASE_URL; ?>admin/usuarios/editar.php?id=<?php echo htmlspecialchars($user_data['id']); ?>" class="btn btn-secondary">Editar Usuário</a>
            <a href="<?php echo BASE_URL; ?>admin/usuarios/index.php" class="btn btn-secondary">Voltar à Lista</a>
        </div>
    </div>

    <?php if (strpos($user_data['tipo'], 'corretor') !== false): // Apenas se for corretor, mostra KPIs de desempenho ?>
    <div class="history-section mt-2xl">
        <h3>Desempenho do Corretor (Mês Atual)</h3>
        <div class="kpi-grid">
            <div class="kpi-card">
                <span class="kpi-label">Vendas Concluídas</span>
                <span class="kpi-value"><?php echo htmlspecialchars($total_vendas_corretor); ?></span>
                <small>No mês atual</small>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Valor Total Vendido</span>
                <span class="kpi-value"><?php echo format_currency_brl($valor_total_vendas_corretor); ?></span>
                <small>No mês atual</small>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Comissão Gerada</span>
                <span class="kpi-value"><?php echo format_currency_brl($comissao_corretor_total); ?></span>
                <small>No mês atual</small>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Reservas Ativas</span>
                <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_ativas_corretor); ?></span>
                <small>Em andamento</small>
            </div>
        </div>
    </div>

    <div class="history-section mt-2xl">
        <h3>Últimas Reservas e Vendas</h3>
        <?php if (!empty($ultimas_reservas_corretor)): ?>
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Empreendimento</th>
                            <th>Unidade</th>
                            <th>Cliente</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimas_reservas_corretor as $reserva): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reserva['reserva_id']); ?></td>
                                <td><?php echo htmlspecialchars(format_datetime_br($reserva['data_reserva'])); ?></td>
                                <td><?php echo htmlspecialchars($reserva['empreendimento_nome']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['unidade_numero'] . ' (' . $reserva['unidade_andar'] . 'º Andar)'); ?></td>
                                <td><?php echo htmlspecialchars($reserva['cliente_nome']); ?></td>
                                <td><span class="status-badge status-<?php echo htmlspecialchars($reserva['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva['status']))); ?></span></td>
                                <td><?php echo format_currency_brl($reserva['valor_reserva']); ?></td>
                                <td class="admin-table-actions">
                                    <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nenhuma reserva ou venda recente por este corretor.</p>
        <?php endif; ?>
    </div>
    <?php endif; // Fim do if (corretor) ?>

    <div class="history-section mt-2xl">
        <h3>Histórico de Auditoria do Usuário</h3>
        <?php if (!empty($historico_auditoria)): ?>
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Ação</th>
                            <th>Detalhes</th>
                            <th>Realizado Por</th>
                            <th>IP Origem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico_auditoria as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(format_datetime_br($log['data_acao'])); ?></td>
                                <td><?php
                                $detalhes_json = json_decode($log['detalhes'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($detalhes_json)) {
                                    echo '<pre><code>' . htmlspecialchars(json_encode($detalhes_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</code></pre>';
                                } else {
                                    echo htmlspecialchars($log['detalhes']);
                                }
                                ?></td>
                                <td><?php echo htmlspecialchars($log['usuario_acao_nome']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_origem']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nenhum histórico de auditoria encontrado para este usuário.</p>
        <?php endif; ?>
    </div>


    <?php endif; // Fim do if($user_data) ?>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>