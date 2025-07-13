<?php
// api/vendas.php - Endpoint API para gestão de vendas (Admin Master)

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php'; // Para require_permission()
require_once '../includes/alerts.php'; // Para gerar alertas, se necessário

header('Content-Type: application/json'); // Resposta sempre em JSON

// Requer permissão de Admin Master para esta ação, e informa que é uma requisição AJAX 
require_permission(['admin'], true);

$response = ['success' => false, 'message' => 'Requisição inválida.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $reserva_id = filter_input(INPUT_POST, 'reserva_id', FILTER_VALIDATE_INT);

    if (!$reserva_id) {
        $response['message'] = 'ID da reserva inválido.';
        echo json_encode($response);
        exit();
    }

    try {
        // Obter informações do usuário logado para auditoria
        $logged_user_info = get_user_info();
        $user_id = $logged_user_info['id'];
        $user_name = $logged_user_info['name'];
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        switch ($action) {
            case 'finalize_sale':
                // 1. Verificar o status atual da reserva e obter o ID da unidade
                $reserva = fetch_single("SELECT id, status, unidade_id, corretor_id, empreendimento_id FROM reservas WHERE id = ?", [$reserva_id], "i");

                if (!$reserva) {
                    $response['message'] = 'Reserva não encontrada.';
                    break;
                }

                if ($reserva['status'] !== 'contrato_enviado') {
                    $response['message'] = 'Esta reserva não está no status "Contrato Enviado" para ser finalizada.';
                    break;
                }

                // 2. Atualizar o status da reserva para 'vendida' 
                $affected_rows_reserva = update_delete_data(
                    "UPDATE reservas SET status = 'vendida', data_atualizacao = NOW(), usuario_atualizacao_id = ? WHERE id = ?",
                    [$user_id, $reserva_id],
                    "ii"
                );

                if ($affected_rows_reserva === 0) {
                    // Pode ser que o status já estivesse 'vendida' ou houve outro problema
                    $response['message'] = 'Nenhuma alteração feita na reserva. Pode já estar vendida.';
                    break;
                }

                // 3. Atualizar o status da unidade para 'vendida' 
                $affected_rows_unidade = update_delete_data(
                    "UPDATE unidades SET status = 'vendida', data_atualizacao = NOW(), usuario_atualizacao_id = ? WHERE id = ?",
                    [$user_id, $reserva['unidade_id']],
                    "ii"
                );

                // Registrar a ação no log de auditoria 
                insert_data(
                    "INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, data_acao, ip_origem, detalhes) VALUES (?, ?, ?, ?, NOW(), ?, ?)",
                    [$user_id, 'Finalizar Venda', 'Reserva', $reserva_id, $user_ip, 'Reserva ' . $reserva_id . ' e Unidade ' . $reserva['unidade_id'] . ' marcadas como vendidas.'],
                    "isissis"
                );

                // Gerar alerta para o Corretor e Admin da Imobiliária 
                create_alert('venda_concluida', 'A venda da unidade ' . $reserva['unidade_id'] . ' do empreendimento ' . $reserva['empreendimento_id'] . ' foi finalizada!', $reserva['corretor_id'], $reserva['empreendimento_id']);
                // TODO: Enviar e-mail para o corretor e admin da imobiliária (requer integração de e-mail)

                $response = ['success' => true, 'message' => 'Venda finalizada com sucesso!'];
                break;

            default:
                $response['message'] = 'Ação não suportada.';
                break;
        }

    } catch (Exception $e) {
        error_log("Erro no processamento da venda (API): " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()];
    }
}

echo json_encode($response);
exit();
?>