<?php
// imobiliaria/clientes/detalhes.php - Página de Detalhes do Cliente (Admin Imobiliária)

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
    error_log("Erro crítico na inicialização do DB em imobiliaria/clientes/detalhes.php: " . $e->getMessage());
    die("<h1>Erro Crítico</h1><p>Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.</p>");
}

// Requer permissão de Admin Imobiliária
require_permission(['admin_imobiliaria']);

$page_title = "Detalhes do Cliente";

$logged_user_info = get_user_info();
$imobiliaria_id_logado = $logged_user_info['imobiliaria_id'] ?? null;

$client_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$cliente = null;
$reservas_do_cliente = [];
$vendas_do_cliente = [];

$errors = [];
$success_message = '';

// Lida com mensagens da sessão (se houverem)
if (isset($_SESSION['form_messages'])) {
    $errors = $_SESSION['form_messages']['errors'] ?? [];
    $success_message = $_SESSION['form_messages']['success'] ?? '';
    unset($_SESSION['form_messages']);
}

if (!$client_id || !$imobiliaria_id_logado) {
    $errors[] = "ID do cliente não fornecido ou imobiliária não identificada.";
    $client_id = 0;
} else {
    try {
        // Buscar IDs de todos os corretores vinculados a esta imobiliária
        $sql_corretores_imobiliaria = "SELECT id FROM usuarios WHERE imobiliaria_id = ? AND tipo LIKE 'corretor_%'";
        $corretores_vinculados = fetch_all($sql_corretores_imobiliaria, [$imobiliaria_id_logado], "i");
        
        $corretores_ids = [];
        if (!empty($corretores_vinculados)) {
            foreach ($corretores_vinculados as $corretor) {
                $corretores_ids[] = $corretor['id'];
            }
        }
        
        // Se não houver corretores vinculados, ou o cliente não estiver associado a eles, acesso negado
        if (empty($corretores_ids)) {
            $errors[] = "Nenhum corretor vinculado à sua imobiliária encontrado. Este cliente não pode ser acessado.";
            $client_id = 0;
        } else {
            $placeholders = implode(',', array_fill(0, count($corretores_ids), '?'));

            // Verificar se o cliente está associado a alguma reserva de um corretor desta imobiliária
            $sql_check_client_association = "
                SELECT COUNT(rc.cliente_id) AS total
                FROM reservas_clientes rc
                JOIN reservas r ON rc.reserva_id = r.id
                WHERE rc.cliente_id = ? AND r.corretor_id IN ({$placeholders})";
            
            $params_check = array_merge([$client_id], $corretores_ids);
            $types_check = "i" . str_repeat('i', count($corretores_ids));
            $client_association_count = fetch_single($sql_check_client_association, $params_check, $types_check)['total'];

            if ($client_association_count === 0) {
                $errors[] = "Cliente não encontrado ou não está associado a nenhuma reserva da sua imobiliária.";
                $client_id = 0;
            } else {
                // Se o cliente está associado, buscar seus dados
                $sql_cliente = "
                    SELECT
                        c.id, c.nome, c.cpf, c.email, c.whatsapp, c.cep, c.endereco, c.numero, c.complemento, c.bairro, c.cidade, c.estado, c.data_cadastro, c.data_atualizacao
                    FROM
                        clientes c
                    WHERE
                        c.id = ?;
                ";
                $cliente = fetch_single($sql_cliente, [$client_id], "i");

                if (!$cliente) {
                    // Deveria ser encontrado se o client_association_count > 0, mas para segurança
                    $errors[] = "Cliente não encontrado.";
                    $client_id = 0;
                } else {
                    $page_title .= " - " . htmlspecialchars($cliente['nome']);

                    // Buscar reservas ativas do cliente (apenas as vinculadas a corretores desta imobiliária)
                    $sql_reservas = "
                        SELECT
                            r.id AS reserva_id,
                            r.data_reserva,
                            r.valor_reserva,
                            r.status,
                            e.nome AS empreendimento_nome,
                            u.numero AS unidade_numero,
                            u.andar AS unidade_andar,
                            COALESCE(corr.nome, 'Não Atribuído') AS corretor_nome
                        FROM
                            reservas r
                        JOIN
                            reservas_clientes rc ON r.id = rc.reserva_id
                        JOIN
                            unidades u ON r.unidade_id = u.id
                        JOIN
                            empreendimentos e ON u.empreendimento_id = e.id
                        LEFT JOIN
                            usuarios corr ON r.corretor_id = corr.id
                        WHERE
                            rc.cliente_id = ? AND r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada')
                            AND r.corretor_id IN ({$placeholders})
                        ORDER BY
                            r.data_reserva DESC;
                    ";
                    $params_reservas = array_merge([$client_id], $corretores_ids);
                    $types_reservas = "i" . str_repeat('i', count($corretores_ids));
                    $reservas_do_cliente = fetch_all($sql_reservas, $params_reservas, $types_reservas);

                    // Buscar vendas concluídas do cliente (apenas as vinculadas a corretores desta imobiliária)
                    $sql_vendas = "
                        SELECT
                            r.id AS venda_id,
                            r.data_ultima_interacao AS data_venda,
                            r.valor_reserva,
                            e.nome AS empreendimento_nome,
                            u.numero AS unidade_numero,
                            u.andar AS unidade_andar,
                            COALESCE(corr.nome, 'Não Atribuído') AS corretor_nome
                        FROM
                            reservas r
                        JOIN
                            reservas_clientes rc ON r.id = rc.reserva_id
                        JOIN
                            unidades u ON r.unidade_id = u.id
                        JOIN
                            empreendimentos e ON u.empreendimento_id = e.id
                        LEFT JOIN
                            usuarios corr ON r.corretor_id = corr.id
                        WHERE
                            rc.cliente_id = ? AND r.status = 'vendida'
                            AND r.corretor_id IN ({$placeholders})
                        ORDER BY
                            r.data_ultima_interacao DESC;
                    ";
                    $params_vendas = array_merge([$client_id], $corretores_ids);
                    $types_vendas = "i" . str_repeat('i', count($corretores_ids));
                    $vendas_do_cliente = fetch_all($sql_vendas, $params_vendas, $types_vendas);

                }
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao carregar detalhes do cliente (imobiliaria): " . $e->getMessage());
        $errors[] = "Ocorreu um erro ao carregar os detalhes do cliente: " . $e->getMessage();
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

    <?php if ($cliente): ?>
    <div class="details-section">
        <h3>Dados Cadastrais do Cliente</h3>
        <div class="details-grid">
            <p><strong>Nome Completo:</strong> <?php echo htmlspecialchars($cliente['nome']); ?></p>
            <p><strong>CPF:</strong> <?php echo htmlspecialchars(format_cpf($cliente['cpf'])); ?></p>
            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
            <p><strong>WhatsApp:</strong> <?php echo htmlspecialchars(format_whatsapp($cliente['whatsapp'])); ?></p>
            <p><strong>CEP:</strong> <?php echo htmlspecialchars($cliente['cep']); ?></p>
            <p><strong>Endereço:</strong> <?php echo htmlspecialchars($cliente['endereco']); ?></p>
            <p><strong>Número:</strong> <?php echo htmlspecialchars($cliente['numero']); ?></p>
            <p><strong>Complemento:</strong> <?php echo htmlspecialchars($cliente['complemento']); ?></p>
            <p><strong>Bairro:</strong> <?php echo htmlspecialchars($cliente['bairro']); ?></p>
            <p><strong>Cidade:</strong> <?php echo htmlspecialchars($cliente['cidade']); ?></p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($cliente['estado']); ?></p>
            <p><strong>Data de Cadastro:</strong> <?php echo htmlspecialchars(format_datetime_br($cliente['data_cadastro'])); ?></p>
            <p><strong>Última Atualização:</strong> <?php echo htmlspecialchars(format_datetime_br($cliente['data_atualizacao'])); ?></p>
        </div>

        <div class="form-actions" style="justify-content: flex-start; margin-top: var(--spacing-xl);">
            <a href="<?php echo BASE_URL; ?>imobiliaria/clientes/index.php" class="btn btn-secondary">Voltar à Lista</a>
        </div>
    </div>

    <div class="history-section mt-2xl">
        <h3>Histórico de Reservas Ativas</h3>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID Reserva</th>
                        <th>Data Reserva</th>
                        <th>Empreendimento</th>
                        <th>Unidade</th>
                        <th>Corretor</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservas_do_cliente)): ?>
                        <tr><td colspan="8" style="text-align: center;">Nenhuma reserva ativa para este cliente com corretores da sua imobiliária.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reservas_do_cliente as $reserva): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reserva['reserva_id']); ?></td>
                                <td><?php echo htmlspecialchars(format_datetime_br($reserva['data_reserva'])); ?></td>
                                <td><?php echo htmlspecialchars($reserva['empreendimento_nome']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['unidade_numero'] . ' (' . $reserva['unidade_andar'] . 'º Andar)'); ?></td>
                                <td><?php echo htmlspecialchars($reserva['corretor_nome']); ?></td>
                                <td><span class="status-badge status-<?php echo htmlspecialchars($reserva['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva['status']))); ?></span></td>
                                <td><?php echo format_currency_brl($reserva['valor_reserva']); ?></td>
                                <td class="admin-table-actions">
                                    <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/detalhes.php?id=<?php echo htmlspecialchars($reserva['reserva_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="history-section mt-2xl">
        <h3>Histórico de Vendas Concluídas</h3>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID Venda</th>
                        <th>Data Venda</th>
                        <th>Empreendimento</th>
                        <th>Unidade</th>
                        <th>Corretor</th>
                        <th>Valor Final</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendas_do_cliente)): ?>
                        <tr><td colspan="7" style="text-align: center;">Nenhuma venda concluída para este cliente com corretores da sua imobiliária.</td></tr>
                    <?php else: ?>
                        <?php foreach ($vendas_do_cliente as $venda): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($venda['venda_id']); ?></td>
                                <td><?php echo htmlspecialchars(format_datetime_br($venda['data_venda'])); ?></td>
                                <td><?php htmlspecialchars($venda['empreendimento_nome']); ?></td>
                                <td><?php echo htmlspecialchars($venda['unidade_numero'] . ' (' . $venda['unidade_andar'] . 'º Andar)'); ?></td>
                                <td><?php echo htmlspecialchars($venda['corretor_nome']); ?></td>
                                <td><?php echo format_currency_brl($venda['valor_reserva']); ?></td>
                                <td class="admin-table-actions">
                                    <a href="<?php echo BASE_URL; ?>imobiliaria/reservas/detalhes.php?id=<?php echo htmlspecialchars($venda['venda_id']); ?>" class="btn btn-info btn-sm">Ver Detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; // Fim do if($cliente) ?>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>