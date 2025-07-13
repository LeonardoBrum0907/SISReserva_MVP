<?php
// admin/imobiliarias/detalhes.php - Página de Detalhes da Imobiliária (Admin Master)

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para formatação (CPF, WhatsApp, CNPJ, data)
require_once '../../includes/alerts.php'; // Para mensagens

// Inicializar a conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em admin/imobiliarias/detalhes.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

require_permission(['admin']); // Apenas Admin Master pode acessar detalhes de imobiliárias

$page_title = "Detalhes da Imobiliária";

$imobiliaria_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$imobiliaria_data = null;
$corretores_vinculados = [];
$historico_auditoria = [];
$errors = [];
$success_message = '';

// Recuperar mensagens da sessão, se houverem
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

// --- DEBUG: Logar o ID da imobiliária recebido da URL ---
error_log("DEBUG detalhes.php: Imobiliaria ID from URL: " . ($imobiliaria_id ?? 'NULL'));


if (!$imobiliaria_id) {
    $errors[] = "ID da imobiliária não fornecido.";
    error_log("DEBUG detalhes.php: Invalid or missing Imobiliaria ID. Redirecting.");
    $imobiliaria_id = 0; // Para evitar erros se o ID não for válido
} else {
    try {
        // Buscar detalhes da imobiliária
        $sql_imobiliaria = "
            SELECT
                i.id, i.nome, i.cnpj, i.email, i.telefone, i.endereco, i.numero, i.complemento, i.bairro, i.cidade, i.estado, i.cep, i.ativa, i.data_cadastro, i.data_atualizacao, i.motivo_rejeicao, i.motivo_inativacao,
                COALESCE(u.nome, 'N/A') AS admin_responsavel_nome,
                u.id AS admin_responsavel_id
            FROM
                imobiliarias i
            LEFT JOIN
                usuarios u ON i.admin_id = u.id
            WHERE
                i.id = ?;
        ";
        $imobiliaria_data = fetch_single($sql_imobiliaria, [$imobiliaria_id], "i");

        // --- DEBUG: Logar o resultado da consulta ao banco de dados ---
        error_log("DEBUG detalhes.php: Result of fetch_single for imobiliaria_id {$imobiliaria_id}: " . print_r($imobiliaria_data, true));


        if (!$imobiliaria_data) {
            $errors[] = "Imobiliária não encontrada.";
            error_log("DEBUG detalhes.php: Imobiliaria not found in DB for ID {$imobiliaria_id}. Redirecting.");
            $imobiliaria_id = 0;
        } else {
            $page_title .= ": " . htmlspecialchars($imobiliaria_data['nome']);

            // Fetch Administrators vinculados a esta imobiliária (se o tipo for 'admin_imobiliaria')
            $sql_admins_imobiliaria = "SELECT id, nome, email FROM usuarios WHERE imobiliaria_id = ? AND tipo = 'admin_imobiliaria' ORDER BY nome ASC";
            $admins_vinculados = fetch_all($sql_admins_imobiliaria, [$imobiliaria_id], "i");


            // Buscar corretores vinculados a esta imobiliária (qualquer tipo 'corretor_%')
            $sql_corretores = "
                SELECT
                    id, nome, email, cpf, creci, tipo, aprovado, ativo, data_cadastro
                FROM
                    usuarios
                WHERE
                    imobiliaria_id = ? AND tipo LIKE 'corretor_%'
                ORDER BY nome ASC;
            ";
            $corretores_vinculados = fetch_all($sql_corretores, [$imobiliaria_id], "i");
            
            // Obter IDs de todos os corretores vinculados a esta imobiliária para usar em filtros
            $corretores_ids_da_imobiliaria = [];
            if (!empty($corretores_vinculados)) {
                foreach ($corretores_vinculados as $corretor) {
                    $corretores_ids_da_imobiliaria[] = $corretor['id'];
                }
            }
            $imobiliaria_filter_clause = " AND 1=0"; // Default
            $imobiliaria_filter_params = [];
            $imobiliaria_filter_types = "";

            if (!empty($corretores_ids_da_imobiliaria)) {
                $placeholders = implode(',', array_fill(0, count($corretores_ids_da_imobiliaria), '?'));
                $imobiliaria_filter_clause = " AND r.corretor_id IN ({$placeholders})";
                $imobiliaria_filter_params = $corretores_ids_da_imobiliaria;
                $imobiliaria_filter_types = str_repeat('i', count($corretores_ids_da_imobiliaria));
            }


            // --- KPIs de Desempenho da Imobiliária ---
            $total_corretores_ativos = 0;
            $total_reservas_equipe_ativas = 0;
            $total_vendas_concluidas = 0;
            $valor_total_vendas_mes = 0;
            $comissao_total_imobiliaria_mes = 0;

            if (!empty($corretores_ids_da_imobiliaria)) { // Só calcula se tiver corretores
                // KPI: Total de Corretores Ativos
                $sql_corretores_ativos = "SELECT COUNT(id) AS total FROM usuarios WHERE imobiliaria_id = ? AND aprovado = TRUE AND ativo = TRUE AND tipo LIKE 'corretor_%'";
                $result_corretores = fetch_single($sql_corretores_ativos, [$imobiliaria_id], "i");
                $total_corretores_ativos = $result_corretores['total'] ?? 0;

                // KPI: Total de Reservas Atribuídas e Em Andamento
                $sql_reservas_atribuidas = "
                    SELECT COUNT(r.id) AS total
                    FROM reservas r
                    WHERE r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada') {$imobiliaria_filter_clause}
                ";
                $result_reservas_atribuidas = fetch_single($sql_reservas_atribuidas, $imobiliaria_filter_params, $imobiliaria_filter_types);
                $total_reservas_equipe_ativas = $result_reservas_atribuidas['total'] ?? 0;

                // KPI: Total de Vendas Concluídas
                $sql_vendas_concluidas = "
                    SELECT COUNT(r.id) AS total
                    FROM reservas r
                    WHERE r.status = 'vendida' {$imobiliaria_filter_clause}
                ";
                $result_vendas_concluidas = fetch_single($sql_vendas_concluidas, $imobiliaria_filter_params, $imobiliaria_filter_types);
                $total_vendas_concluidas = $result_vendas_concluidas['total'] ?? 0;

                // KPI: Valor Total de Vendas e Comissão no Mês Atual
                $current_month_start = date('Y-m-01 00:00:00');
                $current_month_end = date('Y-m-t 23:59:59');
                $sql_valor_comissao_mes = "
                    SELECT COALESCE(SUM(r.valor_reserva), 0) AS total_valor_mes,
                           COALESCE(SUM(r.comissao_imobiliaria), 0) AS total_comissao_imobiliaria_mes
                    FROM reservas r
                    WHERE r.status = 'vendida'
                    AND r.data_ultima_interacao >= ? AND r.data_ultima_interacao <= ? {$imobiliaria_filter_clause}
                ";
                $params_valor_comissao_mes = array_merge([$current_month_start, $current_month_end], $imobiliaria_filter_params);
                $types_valor_comissao_mes = "ss" . $imobiliaria_filter_types;
                $result_valor_comissao_mes = fetch_single($sql_valor_comissao_mes, $params_valor_comissao_mes, $types_valor_comissao_mes);
                $valor_total_vendas_mes = $result_valor_comissao_mes['total_valor_mes'] ?? 0;
                $comissao_total_imobiliaria_mes = $result_valor_comissao_mes['total_comissao_imobiliaria_mes'] ?? 0;
            }


            // --- Listagem de Reservas Ativas da Imobiliária ---
            $reservas_da_imobiliaria = [];
            if (!empty($corretores_ids_da_imobiliaria)) {
                $sql_reservas_imobiliaria = "
                    SELECT
                        r.id AS reserva_id,
                        r.data_reserva,
                        r.valor_reserva,
                        r.status,
                        r.data_expiracao,
                        COALESCE(cl.nome, 'N/A') AS cliente_nome,
                        COALESCE(corr.nome, 'Não Atribuído') AS corretor_nome,
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
                        usuarios corr ON r.corretor_id = corr.id
                    JOIN
                        unidades u ON r.unidade_id = u.id
                    JOIN
                        empreendimentos e ON u.empreendimento_id = e.id
                    WHERE
                        r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada')
                        AND r.corretor_id IN ({$placeholders})
                    ORDER BY r.data_reserva DESC
                    LIMIT 5; -- Últimas 5 reservas
                ";
                $reservas_da_imobiliaria = fetch_all($sql_reservas_imobiliaria, $imobiliaria_filter_params, $imobiliaria_filter_types);
            }

            // --- Listagem de Vendas da Imobiliária ---
            $vendas_da_imobiliaria = [];
            if (!empty($corretores_ids_da_imobiliaria)) {
                $sql_vendas_imobiliaria = "
                    SELECT
                        r.id AS venda_id,
                        r.data_ultima_interacao AS data_venda,
                        r.valor_reserva,
                        r.comissao_imobiliaria,
                        COALESCE(cl.nome, 'N/A') AS cliente_nome,
                        COALESCE(corr.nome, 'Não Atribuído') AS corretor_nome,
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
                        usuarios corr ON r.corretor_id = corr.id
                    JOIN
                        unidades u ON r.unidade_id = u.id
                    JOIN
                        empreendimentos e ON u.empreendimento_id = e.id
                    WHERE
                        r.status = 'vendida'
                        AND r.corretor_id IN ({$placeholders})
                    ORDER BY r.data_ultima_interacao DESC
                    LIMIT 5; -- Últimas 5 vendas
                ";
                $vendas_da_imobiliaria = fetch_all($sql_vendas_imobiliaria, $imobiliaria_filter_params, $imobiliaria_filter_types);
            }


            // Buscar Histórico de Auditoria para esta imobiliária (ações sobre a imobiliária OU de corretores dela)
            $sql_auditoria = "
                SELECT
                    a.acao,
                    a.detalhes,
                    a.data_acao,
                    COALESCE(u.nome, 'Sistema/Desconhecido') AS usuario_acao_nome,
                    a.ip_origem
                FROM
                    auditoria a
                LEFT JOIN
                    usuarios u ON a.usuario_id = u.id
                WHERE
                    (a.entidade = 'Imobiliaria' AND a.entidade_id = ?)
                    OR (a.entidade = 'Usuario' AND a.entidade_id IN (SELECT id FROM usuarios WHERE imobiliaria_id = ?))
                    OR (a.entidade = 'Reserva' AND a.entidade_id IN (SELECT id FROM reservas WHERE corretor_id IN (SELECT id FROM usuarios WHERE imobiliaria_id = ?)))
                ORDER BY
                    a.data_acao DESC
                LIMIT 10; -- Limita o histórico para não sobrecarregar
            ";
            $historico_auditoria = fetch_all($sql_auditoria, [$imobiliaria_id, $imobiliaria_id, $imobiliaria_id], "iii");

        }
    } catch (Exception $e) {
        error_log("Erro ao carregar detalhes da imobiliária: " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os detalhes da imobiliária: " . $e->getMessage();
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

    <?php if ($imobiliaria_data): ?>
    <div class="details-section">
        <h3>Dados da Imobiliária</h3>
        <div class="details-grid">
            <p><strong>ID:</strong> <?php echo htmlspecialchars($imobiliaria_data['id']); ?></p>
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($imobiliaria_data['nome']); ?></p>
            <p><strong>CNPJ:</strong> <?php echo htmlspecialchars(format_cnpj($imobiliaria_data['cnpj'])); ?></p>
            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($imobiliaria_data['email']); ?></p>
            <p><strong>Telefone:</strong> <?php echo htmlspecialchars(format_whatsapp($imobiliaria_data['telefone'])); ?></p>
            <p><strong>CEP:</strong> <?php echo htmlspecialchars(format_cep($imobiliaria_data['cep'])); ?></p>
            <p class="full-width"><strong>Endereço:</strong> <?php echo htmlspecialchars($imobiliaria_data['endereco'] . (!empty($imobiliaria_data['numero']) ? ', ' . $imobiliaria_data['numero'] : '') . (!empty($imobiliaria_data['complemento']) ? ' (' . $imobiliaria_data['complemento'] . ')' : '') . ' - ' . ($imobiliaria_data['bairro'] ?? 'N/A') . ', ' . $imobiliaria_data['cidade'] . '/' . $imobiliaria_data['estado']); ?></p>
            <p><strong>Status:</strong> <span class="status-badge status-<?php echo ($imobiliaria_data['ativa']) ? 'success' : 'danger'; ?>"><?php echo ($imobiliaria_data['ativa']) ? 'Ativa' : 'Inativa'; ?></span></p>
            <?php if (!empty($imobiliaria_data['motivo_rejeicao'])): ?>
                <p class="full-width"><strong>Motivo Rejeição:</strong> <?php echo htmlspecialchars($imobiliaria_data['motivo_rejeicao']); ?></p>
            <?php endif; ?>
            <?php if (!empty($imobiliaria_data['motivo_inativacao'])): ?>
                <p class="full-width"><strong>Motivo Inativação:</strong> <?php echo htmlspecialchars($imobiliaria_data['motivo_inativacao']); ?></p>
            <?php endif; ?>
            <p><strong>Admin Responsável:</strong> 
                <?php if ($imobiliaria_data['admin_responsavel_id'] !== null): ?>
                    <a href="<?php echo BASE_URL; ?>admin/usuarios/editar.php?id=<?php echo htmlspecialchars($imobiliaria_data['admin_responsavel_id']); ?>">
                        <?php echo htmlspecialchars($imobiliaria_data['admin_responsavel_nome']); ?>
                    </a>
                <?php else: ?>
                    <?php echo htmlspecialchars($imobiliaria_data['admin_responsavel_nome']); ?>
                <?php endif; ?>
            </p>
            <p><strong>Data Cadastro:</strong> <?php echo htmlspecialchars(format_datetime_br($imobiliaria_data['data_cadastro'])); ?></p>
            <p><strong>Última Atualização:</strong> <?php echo htmlspecialchars(format_datetime_br($imobiliaria_data['data_atualizacao'])); ?></p>
        </div>

        <div class="form-actions" style="justify-content: flex-start; margin-top: var(--spacing-xl);">
            <a href="<?php echo BASE_URL; ?>admin/imobiliarias/editar.php?id=<?php echo htmlspecialchars($imobiliaria_data['id']); ?>" class="btn btn-secondary">Editar Imobiliária</a>
            <a href="<?php echo BASE_URL; ?>admin/imobiliarias/index.php" class="btn btn-secondary">Voltar à Lista</a>
        </div>
    </div>

    <div class="kpi-grid mt-2xl">
        <div class="kpi-card">
            <span class="kpi-label">Corretores Ativos</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_corretores_ativos); ?></span>
            <small>Vinculados a esta imobiliária</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Reservas Ativas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_reservas_equipe_ativas); ?></span>
            <small>Atribuídas aos seus corretores</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Vendas Concluídas</span>
            <span class="kpi-value"><?php echo htmlspecialchars($total_vendas_concluidas); ?></span>
            <small>Concretizadas por seus corretores</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Vendas Mês Atual</span>
            <span class="kpi-value"><?php echo format_currency_brl($valor_total_vendas_mes); ?></span>
            <small>Geração da imobiliária no mês</small>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Comissão Imobiliária Mês</span>
            <span class="kpi-value"><?php echo format_currency_brl($comissao_total_imobiliaria_mes); ?></span>
            <small>Valor recebido no mês</small>
        </div>
    </div>


    <div class="history-section mt-2xl">
        <h3>Corretores Vinculados</h3>
        <?php if (!empty($corretores_vinculados)): ?>
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>CPF</th>
                            <th>CRECI</th>
                            <th>Tipo</th>
                            <th>Aprovado</th>
                            <th>Ativo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($corretores_vinculados as $corretor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($corretor['id']); ?></td>
                                <td><?php echo htmlspecialchars($corretor['nome']); ?></td>
                                <td><?php echo htmlspecialchars($corretor['email']); ?></td>
                                <td><?php echo htmlspecialchars(format_cpf($corretor['cpf'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars($corretor['creci'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $corretor['tipo']))); ?></td>
                                <td><span class="status-badge status-<?php echo ($corretor['aprovado']) ? 'success' : 'warning'; ?>"><?php echo ($corretor['aprovado']) ? 'Sim' : 'Não'; ?></span></td>
                                <td><span class="status-badge status-<?php echo ($corretor['ativo']) ? 'success' : 'danger'; ?>"><?php echo ($corretor['ativo']) ? 'Sim' : 'Não'; ?></span></td>
                                <td class="admin-table-actions">
                                    <a href="<?php echo BASE_URL; ?>admin/usuarios/editar.php?id=<?php echo htmlspecialchars($corretor['id']); ?>" class="btn btn-info btn-sm">Ver/Editar Usuário</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nenhum corretor vinculado a esta imobiliária.</p>
        <?php endif; ?>
    </div>

    <div class="history-section mt-2xl">
        <h3>Últimas Reservas</h3>
        <?php if (!empty($reservas_da_imobiliaria)): ?>
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Reserva</th>
                            <th>Data</th>
                            <th>Empreendimento</th>
                            <th>Unidade</th>
                            <th>Cliente</th>
                            <th>Corretor</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Expira</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservas_da_imobiliaria as $reserva): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reserva['reserva_id']); ?></td>
                                <td><?php echo htmlspecialchars(format_datetime_br($reserva['data_reserva'])); ?></td>
                                <td><?php echo htmlspecialchars($reserva['empreendimento_nome']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['unidade_numero'] . ' (' . $reserva['unidade_andar'] . 'º Andar)'); ?></td>
                                <td><?php echo htmlspecialchars($reserva['cliente_nome']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['corretor_nome']); ?></td>
                                <td><?php echo format_currency_brl($reserva['valor_reserva']); ?></td>
                                <td><span class="status-badge status-<?php echo htmlspecialchars($reserva['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva['status']))); ?></span></td>
                                <td><?php echo htmlspecialchars(time_remaining_string($reserva['data_expiracao'])); ?></td>
                                <td class="admin-table-actions">
                                    <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nenhuma reserva recente pelos corretores vinculados a esta imobiliária.</p>
        <?php endif; ?>
    </div>

    <div class="history-section mt-2xl">
        <h3>Últimas Vendas</h3>
        <?php if (!empty($vendas_da_imobiliaria)): ?>
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Venda</th>
                            <th>Data Venda</th>
                            <th>Empreendimento</th>
                            <th>Unidade</th>
                            <th>Cliente</th>
                            <th>Corretor</th>
                            <th>Valor Venda</th>
                            <th>Comissão Imobiliária</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendas_da_imobiliaria as $venda): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($venda['venda_id']); ?></td>
                                <td><?php echo htmlspecialchars(format_datetime_br($venda['data_venda'])); ?></td>
                                <td><?php echo htmlspecialchars($venda['empreendimento_nome']); ?></td>
                                <td><?php echo htmlspecialchars($venda['unidade_numero'] . ' (' . $venda['unidade_andar'] . 'º Andar)'); ?></td>
                                <td><?php echo htmlspecialchars($venda['cliente_nome']); ?></td>
                                <td><?php echo htmlspecialchars($venda['corretor_nome']); ?></td>
                                <td><?php echo format_currency_brl($venda['valor_reserva']); ?></td>
                                <td><?php echo format_currency_brl($venda['comissao_imobiliaria']); ?></td>
                                <td class="admin-table-actions">
                                    <a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo htmlspecialchars($venda['venda_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nenhuma venda recente pelos corretores vinculados a esta imobiliária.</p>
        <?php endif; ?>
    </div>

    <div class="history-section mt-2xl">
        <h3>Histórico de Auditoria da Imobiliária</h3>
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
                                <td><?php echo htmlspecialchars($log['acao']); ?></td>
                                <td><?php echo htmlspecialchars($log['detalhes']); ?></td>
                                <td><?php echo htmlspecialchars($log['usuario_acao_nome']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_origem']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nenhum histórico de auditoria encontrado para esta imobiliária.</p>
        <?php endif; ?>
    </div>

    <?php endif; // Fim do if($imobiliaria_data) ?>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>