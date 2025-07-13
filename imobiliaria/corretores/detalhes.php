<?php
// imobiliaria/corretores/detalhes.php - Página de Detalhes do Corretor (Admin Imobiliária)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação (CPF, WhatsApp, data)
require_once '../../includes/alerts.php';   // Para mensagens

// --- Conexão com o Banco de Dados ---
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em imobiliaria/corretores/detalhes.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Detalhes do Corretor";

$logged_user_info = get_user_info();
$admin_imobiliaria_id = $logged_user_info['id'];
$imobiliaria_id_logado = $logged_user_info['imobiliaria_id'] ?? null;

$corretor_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$corretor_data = null;
$errors = [];
$success_message = '';

// Lida com mensagens da sessão (após uma possível ação via AJAX/redirecionamento)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

if (!$corretor_id || !$imobiliaria_id_logado) {
    $errors[] = "ID do corretor não fornecido ou imobiliária não identificada.";
    $corretor_id = 0;
} else {
    try {
        // Buscar detalhes do corretor, garantindo que ele pertença à imobiliária logada
        $sql_corretor = "
            SELECT 
                u.id, u.nome, u.email, u.cpf, u.creci, u.telefone, u.tipo, u.aprovado, u.ativo, 
                u.data_cadastro, u.data_atualizacao, u.data_aprovacao,
                i.nome AS imobiliaria_nome
            FROM 
                usuarios u
            LEFT JOIN
                imobiliarias i ON u.imobiliaria_id = i.id
            WHERE 
                u.id = ? AND u.imobiliaria_id = ? AND u.tipo IN ('corretor_autonomo', 'corretor_imobiliaria');
        ";
        $corretor_data = fetch_single($sql_corretor, [$corretor_id, $imobiliaria_id_logado], "ii");

        if (!$corretor_data) {
            $errors[] = "Corretor não encontrado ou não pertence à sua imobiliária.";
            $corretor_id = 0;
        } else {
            $page_title .= ": " . htmlspecialchars($corretor_data['nome']);

            // --- KPIs de Desempenho do Corretor (para o período atual, ex: Mês) ---
            $total_vendas_corretor = 0;
            $valor_total_vendas_corretor = 0;
            $comissao_corretor_total = 0;
            $total_reservas_ativas_corretor = 0;

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
            $result_desempenho = fetch_single($sql_desempenho, [$corretor_id, $current_month_start, $current_month_end], "iss");

            $total_vendas_corretor = $result_desempenho['total_vendas'] ?? 0;
            $valor_total_vendas_corretor = $result_desempenho['valor_total'] ?? 0;
            $comissao_corretor_total = $result_desempenho['comissao_total'] ?? 0;
            $total_reservas_ativas_corretor = $result_desempenho['total_reservas_ativas'] ?? 0;

            // --- Últimas Reservas e Vendas do Corretor ---
            $sql_ultimas_reservas = "
                SELECT
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
            $ultimas_reservas_corretor = fetch_all($sql_ultimas_reservas, [$corretor_id], "i");

        }
    } catch (Exception $e) {
        error_log("Erro ao carregar detalhes do corretor (imobiliaria): " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os detalhes do corretor: " . $e->getMessage();
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

    <?php if ($corretor_data): ?>
    <div class="details-section">
        <h3>Dados Cadastrais do Corretor</h3>
        <div class="details-grid">
            <p><strong>Nome Completo:</strong> <?php echo htmlspecialchars($corretor_data['nome']); ?></p>
            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($corretor_data['email']); ?></p>
            <p><strong>Telefone:</strong> <?php echo htmlspecialchars(format_whatsapp($corretor_data['telefone'] ?? 'N/A')); ?></p>
            <p><strong>CPF:</strong> <?php echo htmlspecialchars(format_cpf($corretor_data['cpf'] ?? 'N/A')); ?></p>
            <p><strong>CRECI:</strong> <?php echo htmlspecialchars($corretor_data['creci'] ?? 'N/A'); ?></p>
            <p><strong>Tipo de Corretor:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $corretor_data['tipo']))); ?></p>
            <p><strong>Imobiliária:</strong> <?php echo htmlspecialchars($corretor_data['imobiliaria_nome'] ?? 'N/A'); ?></p>
            <p><strong>Status Aprovação:</strong> <span class="status-badge status-<?php echo ($corretor_data['aprovado'] ?? 0) ? 'success' : 'warning'; ?>"><?php echo ($corretor_data['aprovado'] ?? 0) ? 'Aprovado' : 'Pendente'; ?></span></p>
            <p><strong>Status Ativo:</strong> <span class="status-badge status-<?php echo ($corretor_data['ativo'] ?? 0) ? 'success' : 'danger'; ?>"><?php echo ($corretor_data['ativo'] ?? 0) ? 'Ativo' : 'Inativo'; ?></span></p>
            <p><strong>Data de Cadastro:</strong> <?php echo htmlspecialchars(format_datetime_br($corretor_data['data_cadastro'])); ?></p>
            <p><strong>Última Atualização:</strong> <?php echo htmlspecialchars(format_datetime_br($corretor_data['data_atualizacao'])); ?></p>
            <?php if (!empty($corretor_data['data_aprovacao'])): ?>
            <p><strong>Data de Aprovação:</strong> <?php echo htmlspecialchars(format_datetime_br($corretor_data['data_aprovacao'])); ?></p>
            <?php endif; ?>
        </div>

        <h3 class="mt-2xl">Desempenho no Mês Atual</h3>
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

        <h3 class="mt-2xl">Últimas Reservas e Vendas</h3>
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
                    <?php if (empty($ultimas_reservas_corretor)): ?>
                        <tr><td colspan="8" style="text-align: center;">Nenhuma reserva ou venda recente por este corretor.</td></tr>
                    <?php else: ?>
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
                                    <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes (Admin Master)</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-actions mt-2xl">
            <a href="<?php echo BASE_URL; ?>imobiliaria/corretores/editar.php?id=<?php echo htmlspecialchars($corretor_data['id']); ?>" class="btn btn-secondary">Editar Corretor</a>
            <?php if (!$corretor_data['aprovado']): ?>
                <button class="btn btn-success approve-realtor" data-id="<?php echo htmlspecialchars($corretor_data['id']); ?>" data-nome="<?php echo htmlspecialchars($corretor_data['nome']); ?>">Aprovar Corretor</button>
                <button class="btn btn-danger reject-realtor" data-id="<?php echo htmlspecialchars($corretor_data['id']); ?>" data-nome="<?php echo htmlspecialchars($corretor_data['nome']); ?>">Rejeitar Corretor</button>
            <?php elseif ($corretor_data['ativo']): ?>
                <button class="btn btn-warning deactivate-realtor" data-id="<?php echo htmlspecialchars($corretor_data['id']); ?>" data-nome="<?php echo htmlspecialchars($corretor_data['nome']); ?>">Inativar Corretor</button>
            <?php else: ?>
                <button class="btn btn-success activate-realtor" data-id="<?php echo htmlspecialchars($corretor_data['id']); ?>" data-nome="<?php echo htmlspecialchars($corretor_data['nome']); ?>">Ativar Corretor</button>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>imobiliaria/corretores/index.php" class="btn btn-secondary">Voltar à Lista</a>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>