<?php
// api/empreendimentos/salvar_etapa6.php - Salva o Fluxo de Pagamento para TODAS as Unidades
// Refatorado para aplicar o plano proporcionalmente a todas as unidades do empreendimento.

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php'; // Para sanitize_input

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Erro desconhecido.'];

// Conexão com o banco de dados
global $conn;
try {
    $conn = get_db_connection();
} catch (Exception $e) {
    error_log("Erro crítico na inicialização do DB em salvar_etapa6.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro de conexão ao banco de dados.']);
    exit();
}

// Requer autenticação e permissão de admin
require_permission(['admin'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empreendimento_id = filter_input(INPUT_POST, 'empreendimento_id', FILTER_VALIDATE_INT);
    $unidade_exemplo_id = filter_input(INPUT_POST, 'unidade_exemplo_id', FILTER_VALIDATE_INT);
    $fluxo_pagamento_json = $_POST['fluxo_pagamento_json'] ?? '[]'; // JSON string do fluxo de pagamento modelo

    // Validar empreendimento_id e unidade_exemplo_id
    if (!$empreendimento_id || !$unidade_exemplo_id) {
        $response['message'] = 'ID do empreendimento ou da unidade exemplo não fornecido ou inválido.';
        echo json_encode($response);
        exit();
    }

    // Decodificar o JSON do fluxo de pagamento modelo
    $fluxo_pagamento_modelo = json_decode($fluxo_pagamento_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($fluxo_pagamento_modelo)) {
        $response['message'] = 'Dados do fluxo de pagamento em formato inválido (JSON inválido ou não é array).';
        echo json_encode($response);
        exit();
    }

    $conn->begin_transaction();

    try {
        // 1. Obter o valor da unidade exemplo para base de cálculo proporcional
        $unidade_exemplo_data = fetch_single("SELECT valor FROM unidades WHERE id = ? AND empreendimento_id = ?", [$unidade_exemplo_id, $empreendimento_id], "ii");
        if (!$unidade_exemplo_data || !isset($unidade_exemplo_data['valor'])) {
            throw new Exception("Unidade exemplo não encontrada ou valor inválido.");
        }
        $valor_unidade_exemplo = (float)$unidade_exemplo_data['valor'];

        // 2. Obter TODAS as unidades do empreendimento para aplicar o plano
        $todas_unidades_do_empreendimento = fetch_all("SELECT id, valor FROM unidades WHERE empreendimento_id = ?", [$empreendimento_id], "i");

        if (empty($todas_unidades_do_empreendimento)) {
            throw new Exception("Nenhuma unidade encontrada para o empreendimento especificado.");
        }

        // Preparar o statement para atualização em lote
        // Este é um UPDATE para cada unidade, então não é um 'batch insert'.
        // O ideal seria usar um UPDATE ... CASE ... WHEN ou um array de updates.
        // Para simplicidade e compatibilidade, faremos updates individuais dentro da transação.
        $update_stmt = $conn->prepare("UPDATE unidades SET informacoes_pagamento = ?, data_atualizacao = NOW() WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception('Falha ao preparar a query de atualização de unidades: ' . $conn->error);
        }

        foreach ($todas_unidades_do_empreendimento as $unidade_alvo) {
            $valor_unidade_alvo = (float)$unidade_alvo['valor'];
            $plano_pagamento_para_esta_unidade = [];

            foreach ($fluxo_pagamento_modelo as $item_modelo) {
                $valor_calculado = 0;
                $total_item_modelo = $item_modelo['valor'] * $item_modelo['quantas_vezes'];

                if ($item_modelo['tipo_valor'] === 'Percentual (%)') {
                    // Se o modelo é percentual, aplica o mesmo percentual à unidade alvo
                    // O valor na BD é decimal, então 0.01 = 1%, 0.1 = 10%.
                    // Certificar que $item_modelo['valor'] já é o valor real do percentual (ex: 0.1 para 10%)
                    // Caso o valor do item_modelo seja 10 (para 10%), dividir por 100
                    $percentual_aplicado = $item_modelo['valor'];
                    if ($percentual_aplicado > 1) { // Assume que valores acima de 1 são % inteiras (e.g. 10 para 10%)
                        $percentual_aplicado = $percentual_aplicado / 100;
                    }
                    $valor_calculado = $valor_unidade_alvo * $percentual_aplicado; // Ex: 100000 * 0.1 = 10000
                } else { // Valor Fixo (R$)
                    if ($item_modelo['tipo_calculo'] === 'Proporcional') {
                        // Calcula a proporção do valor fixo em relação ao valor da unidade exemplo
                        // Ex: (Valor Fixo Ex: 10000 / Valor Unidade Ex: 200000) * Valor Unidade Alvo: 250000
                        $proporcao = ($valor_unidade_exemplo != 0) ? ($item_modelo['valor'] / $valor_unidade_exemplo) : 0;
                        $valor_calculado = $valor_unidade_alvo * $proporcao;
                    } else { // Fixo (exato)
                        $valor_calculado = $item_modelo['valor'];
                    }
                }

                $plano_pagamento_para_esta_unidade[] = [
                    'descricao' => $item_modelo['descricao'],
                    'quantas_vezes' => $item_modelo['quantas_vezes'],
                    'tipo_valor' => $item_modelo['tipo_valor'],
                    // Salvar o valor calculado que é o que importa para a exibição/cálculo,
                    // ou manter o original se o frontend precisar para re-edição
                    // Mantenho o valor original e o tipo para o frontend recalcular, mas o valor do item é o que precisa ser ajustado.
                    // A instrução diz "valores sendo proporcionalmente ajustados".
                    'valor' => $valor_calculado, // Valor já ajustado
                    'tipo_calculo' => $item_modelo['tipo_calculo']
                ];
            }
            
            // Re-encode para JSON para cada unidade
            $informacoes_pagamento_final = json_encode($plano_pagamento_para_esta_unidade);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Erro ao codificar JSON para a unidade ID " . $unidade_alvo['id'] . ": " . json_last_error_msg());
            }

            // Atualiza a unidade no banco
            $update_stmt->bind_param("si", $informacoes_pagamento_final, $unidade_alvo['id']);
            if (!$update_stmt->execute()) {
                throw new Exception("Erro ao atualizar o fluxo de pagamento para a unidade ID " . $unidade_alvo['id'] . ": " . $update_stmt->error);
            }
        }
        $update_stmt->close();

        // Auditoria
        insert_data("INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) VALUES (?, ?, ?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'Atualização Empreendimento Etapa 6', 'Empreendimento', $empreendimento_id, "Fluxo de pagamento de unidades atualizado em lote.", $_SERVER['REMOTE_ADDR']],
                    "isssss");

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Fluxo de pagamento aplicado a todas as unidades com sucesso!';

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro em salvar_etapa6.php: " . $e->getMessage());
        $response['message'] = 'Erro ao salvar o fluxo de pagamento: ' . $e->getMessage();
    } finally {
        if (isset($conn) && $conn) {
            $conn->close();
        }
    }

} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);