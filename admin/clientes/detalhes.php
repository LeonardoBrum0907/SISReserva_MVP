<?php
// admin/clientes/detalhes.php - VERSÃO FINAL COMPLETA E FUNCIONAL

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

require_permission(['admin']);
$page_title = "Detalhes do Cliente";
$conn = get_db_connection();

$client_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$client_id) {
    // Redireciona para a listagem se não houver ID
    header("Location: " . BASE_URL . "admin/clientes/index.php");
    exit();
}

// 1. BUSCAR DADOS DO CLIENTE
$cliente = fetch_single("SELECT * FROM clientes WHERE id = ?", [$client_id], "i");
if (!$cliente) {
    // Redireciona se o cliente não for encontrado
    $_SESSION['message'] = create_alert('error', 'Cliente não encontrado.');
    header("Location: " . BASE_URL . "admin/clientes/index.php");
    exit();
}

$page_title .= ": " . htmlspecialchars($cliente['nome']);

// 2. BUSCAR KPIs ESPECÍFICOS DO CLIENTE
$kpis_cliente = [
    'reservas_ativas' => fetch_single("SELECT COUNT(DISTINCT r.id) as total FROM reservas r JOIN reservas_clientes rc ON r.id = rc.reserva_id WHERE rc.cliente_id = ? AND r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada')", [$client_id], "i")['total'] ?? 0,
    'vendas_concluidas' => fetch_single("SELECT COUNT(DISTINCT r.id) as total FROM reservas r JOIN reservas_clientes rc ON r.id = rc.reserva_id WHERE rc.cliente_id = ? AND r.status = 'vendida'", [$client_id], "i")['total'] ?? 0,
    'vgv_cliente' => fetch_single("SELECT SUM(r.valor_reserva) as total FROM reservas r JOIN reservas_clientes rc ON r.id = rc.reserva_id WHERE rc.cliente_id = ? AND r.status = 'vendida'", [$client_id], "i")['total'] ?? 0.00
];

// 3. BUSCAR CLIENTES VINCULADOS (outros compradores nas mesmas reservas)
$clientes_vinculados = fetch_all("SELECT DISTINCT c2.id, c2.nome FROM reservas_clientes rc1 JOIN reservas_clientes rc2 ON rc1.reserva_id = rc2.reserva_id AND rc1.cliente_id != rc2.cliente_id JOIN clientes c2 ON rc2.cliente_id = c2.id WHERE rc1.cliente_id = ?", [$client_id], "i");

// 4. BUSCAR CORRETORES VINCULADOS
$corretores_vinculados = fetch_all("SELECT DISTINCT u.id, u.nome, u.tipo, i.nome as imobiliaria_nome FROM usuarios u JOIN reservas r ON u.id = r.corretor_id JOIN reservas_clientes rc ON r.id = rc.reserva_id LEFT JOIN imobiliarias i ON u.imobiliaria_id = i.id WHERE rc.cliente_id = ? AND r.corretor_id IS NOT NULL", [$client_id], "i");

// 5. BUSCAR HISTÓRICOS DE RESERVAS E VENDAS
$reservas_ativas = fetch_all("SELECT r.id, r.status, r.data_reserva, e.nome AS empreendimento_nome, u.numero AS unidade_numero, COALESCE(co.nome, 'Lead (Sem Corretor)') as corretor_nome FROM reservas r JOIN reservas_clientes rc ON r.id = rc.reserva_id JOIN unidades u ON r.unidade_id = u.id JOIN empreendimentos e ON u.empreendimento_id = e.id LEFT JOIN usuarios co ON r.corretor_id = co.id WHERE rc.cliente_id = ? AND r.status NOT IN ('vendida', 'cancelada', 'expirada', 'dispensada') ORDER BY r.data_reserva DESC", [$client_id], "i");
$vendas_concluidas = fetch_all("SELECT r.id, r.valor_reserva, r.data_ultima_interacao, e.nome AS empreendimento_nome, u.numero AS unidade_numero, COALESCE(co.nome, 'Corretor Removido') as corretor_nome FROM reservas r JOIN reservas_clientes rc ON r.id = rc.reserva_id JOIN unidades u ON r.unidade_id = u.id JOIN empreendimentos e ON u.empreendimento_id = e.id LEFT JOIN usuarios co ON r.corretor_id = co.id WHERE rc.cliente_id = ? AND r.status = 'vendida' ORDER BY r.data_ultima_interacao DESC", [$client_id], "i");

require_once '../../includes/header_dashboard.php';
?>

<div class="admin-content-wrapper">
    <div class="details-page-header">
        <h2><?php echo $page_title; ?></h2>
        <a href="<?php echo BASE_URL; ?>admin/clientes/index.php" class="btn btn-secondary">Voltar à Lista</a>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card"><span class="kpi-label">Reservas Ativas</span><span class="kpi-value"><?php echo $kpis_cliente['reservas_ativas']; ?></span></div>
        <div class="kpi-card"><span class="kpi-label">Vendas Concluídas</span><span class="kpi-value"><?php echo $kpis_cliente['vendas_concluidas']; ?></span></div>
        <div class="kpi-card"><span class="kpi-label">Valor Total Comprado (R$)</span><span class="kpi-value"><?php echo format_currency_brl($kpis_cliente['vgv_cliente']); ?></span></div>
    </div>
    
    <div class="details-columns-container mt-lg">
        <div class="details-main-column">
            <div class="details-section">
                <form id="editClientForm" method="POST">
                    <input type="hidden" name="action" value="update_client">
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                    
                    <div class="section-header">
                        <h3>Dados Cadastrais</h3>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" id="btnEditarCliente"><i class="fas fa-edit"></i> Editar</button>
                            <button type="submit" class="btn btn-success" id="btnSalvarCliente" style="display: none;"><i class="fas fa-save"></i> Salvar</button>
                            <button type="button" class="btn btn-secondary" id="btnCancelarEdicao" style="display: none;">Cancelar</button>
                        </div>
                    </div>

                    <div id="display-mode" class="details-grid-display">
                        <p><strong>Nome:</strong> <span data-field="nome"><?php echo htmlspecialchars($cliente['nome']); ?></span></p>
                        <p><strong>CPF:</strong> <span data-field="cpf"><?php echo htmlspecialchars(format_cpf($cliente['cpf'])); ?></span></p>
                        <p><strong>E-mail:</strong> <span data-field="email"><?php echo htmlspecialchars($cliente['email']); ?></span></p>
                        <p><strong>WhatsApp:</strong> <span data-field="whatsapp"><?php echo htmlspecialchars(format_whatsapp($cliente['whatsapp'])); ?></span></p>
                        <p><strong>CEP:</strong> <span data-field="cep"><?php echo htmlspecialchars($cliente['cep']); ?></span></p>
                        <p><strong>Endereço:</strong> <span data-field="endereco_completo"><?php echo htmlspecialchars(implode(', ', array_filter([$cliente['endereco'], $cliente['numero'], $cliente['bairro'], $cliente['cidade'] . '-' . $cliente['estado']]))); ?></span></p>
                    </div>

                    <div id="edit-mode" class="details-grid" style="display: none;">
                        <div class="form-group"><label for="edit_nome">Nome</label><input type="text" id="edit_nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required></div>
                        <div class="form-group"><label for="edit_cpf">CPF</label><input type="text" id="edit_cpf" name="cpf" class="form-control mask-cpf" value="<?php echo htmlspecialchars($cliente['cpf']); ?>" required></div>
                        <div class="form-group"><label for="edit_email">E-mail</label><input type="email" id="edit_email" name="email" class="form-control" value="<?php echo htmlspecialchars($cliente['email']); ?>" required></div>
                        <div class="form-group"><label for="edit_whatsapp">WhatsApp</label><input type="text" id="edit_whatsapp" name="whatsapp" class="form-control mask-whatsapp" value="<?php echo htmlspecialchars($cliente['whatsapp']); ?>"></div>
                        <div class="form-group"><label for="edit_cep">CEP</label><input type="text" id="edit_cep" name="cep" class="form-control mask-cep" value="<?php echo htmlspecialchars($cliente['cep']); ?>"></div>
                        <div class="form-group"><label for="edit_endereco">Endereço</label><input type="text" id="edit_endereco" name="endereco" class="form-control" value="<?php echo htmlspecialchars($cliente['endereco']); ?>"></div>
                        <div class="form-group"><label for="edit_numero">Número</label><input type="text" id="edit_numero" name="numero" class="form-control" value="<?php echo htmlspecialchars($cliente['numero']); ?>"></div>
                        <div class="form-group"><label for="edit_complemento">Complemento</label><input type="text" id="edit_complemento" name="complemento" class="form-control" value="<?php echo htmlspecialchars($cliente['complemento']); ?>"></div>
                        <div class="form-group"><label for="edit_bairro">Bairro</label><input type="text" id="edit_bairro" name="bairro" class="form-control" value="<?php echo htmlspecialchars($cliente['bairro']); ?>"></div>
                        <div class="form-group"><label for="edit_cidade">Cidade</label><input type="text" id="edit_cidade" name="cidade" class="form-control" value="<?php echo htmlspecialchars($cliente['cidade']); ?>"></div>
                        <div class="form-group"><label for="edit_estado">Estado</label><input type="text" id="edit_estado" name="estado" class="form-control" value="<?php echo htmlspecialchars($cliente['estado']); ?>"></div>
                    </div>
                </form>
            </div>
        </div>
        <div class="details-side-column">
            <div class="details-section">
                <h3>Clientes Vinculados</h3>
                <?php if (!empty($clientes_vinculados)): ?>
                    <ul class="linked-items-list">
                        <?php foreach ($clientes_vinculados as $vinculado): ?>
                            <li><i class="fas fa-user-friends"></i> <a href="detalhes.php?id=<?php echo $vinculado['id']; ?>"><?php echo htmlspecialchars($vinculado['nome']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?><p>Nenhum outro cliente vinculado.</p><?php endif; ?>
            </div>
            <div class="details-section mt-lg">
                <h3>Corretor(es) Vinculado(s)</h3>
                <?php if (!empty($corretores_vinculados)): ?>
                    <ul class="linked-items-list">
                        <?php foreach ($corretores_vinculados as $corretor): ?>
                            <li><i class="fas fa-user-tie"></i> <a href="../usuarios/detalhes.php?id=<?php echo $corretor['id']; ?>"><?php echo htmlspecialchars($corretor['nome']); ?></a>
                                <small><?php echo ($corretor['tipo'] === 'corretor_imobiliaria' && !empty($corretor['imobiliaria_nome'])) ? "(".htmlspecialchars($corretor['imobiliaria_nome']).")" : "(Autônomo)"; ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?><p>Nenhum corretor vinculado.</p><?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="history-section mt-2xl">
        <h3>Histórico de Reservas Ativas (<?php echo count($reservas_ativas); ?>)</h3>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Data</th><th>Empreendimento/Unidade</th><th>Status</th><th>Corretor</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php if (empty($reservas_ativas)): ?>
                        <tr><td colspan="6" class="text-center">Nenhuma reserva ativa.</td></tr>
                    <?php else: foreach ($reservas_ativas as $reserva): ?>
                        <tr>
                           <td><?php echo $reserva['id']; ?></td>
                            <td><?php echo format_datetime_br($reserva['data_reserva']); ?></td>
                            <td><?php echo htmlspecialchars($reserva['empreendimento_nome'] . ' / ' . $reserva['unidade_numero']); ?></td>
                            <td><span class="status-badge status-<?php echo $reserva['status']; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $reserva['status']))); ?></span></td>
                            <td><?php echo htmlspecialchars($reserva['corretor_nome']); ?></td>
                            <td><a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo $reserva['id']; ?>" class="btn btn-info btn-sm">Ver Reserva</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="history-section mt-2xl">
        <h3>Histórico de Vendas Concluídas (<?php echo count($vendas_concluidas); ?>)</h3>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead><tr><th>ID Venda</th><th>Data da Venda</th><th>Empreendimento/Unidade</th><th>Valor (R$)</th><th>Corretor</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php if (empty($vendas_concluidas)): ?>
                        <tr><td colspan="6" class="text-center">Nenhuma venda concluída para este cliente.</td></tr>
                    <?php else: foreach ($vendas_concluidas as $venda): ?>
                        <tr>
                            <td><?php echo $venda['id']; ?></td>
                            <td><?php echo format_datetime_br($venda['data_ultima_interacao']); ?></td>
                            <td><?php echo htmlspecialchars($venda['empreendimento_nome'] . ' / ' . $venda['unidade_numero']); ?></td>
                            <td><?php echo format_currency_brl($venda['valor_reserva']); ?></td>
                            <td><?php echo htmlspecialchars($venda['corretor_nome']); ?></td>
                            <td><a href="<?php echo BASE_URL; ?>admin/reservas/detalhes.php?id=<?php echo $venda['id']; ?>" class="btn btn-info btn-sm">Ver Venda</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer_dashboard.php'; ?>