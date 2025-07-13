<?php
// includes/alerts.php

// A ordem de inclusão é crucial: config -> database -> auth -> helpers
// Pois as funções aqui podem depender de $conn (de database.php), BASE_URL (de config.php)
// e funções como fetch_all/fetch_single (de database.php).

/**
 * Função para adicionar um novo alerta na tabela 'alertas'.
 * As colunas são: id, usuario_id, titulo, mensagem, link (para URL), lido, data_criacao.
 *
 * @param int $usuario_id ID do usuário que deve receber o alerta.
 * @param string $titulo Título do alerta.
 * @param string $mensagem Mensagem do alerta.
 * @param string|null $link_key Uma chave que o frontend/get_user_alerts usará para construir a URL.
 * @return int|false O ID do alerta inserido ou false em caso de falha.
 */
function add_alert($usuario_id, $titulo, $mensagem, $link_key = null) {
    global $conn;

    if (!$conn instanceof mysqli || $conn->connect_error) {
        error_log("Erro: Conexão com o banco de dados inválida em add_alert.");
        return false;
    }

    $sql = "INSERT INTO alertas (usuario_id, titulo, mensagem, link, lido, data_criacao) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Erro na preparação da query de alerta: " . $conn->error);
        return false;
    }

    $lido_int = 0; // Alerta recém-criado é sempre não lido (0 = FALSE)
    $link_value_for_db = is_string($link_key) ? $link_key : null;

    $stmt->bind_param("isssi", $usuario_id, $titulo, $mensagem, $link_value_for_db, $lido_int);

    if (!$stmt->execute()) {
        error_log("Erro ao executar a query de alerta: " . $stmt->error);
        $stmt->close();
        return false;
    }
    $last_id = $stmt->insert_id;
    $stmt->close();
    return $last_id;
}

/**
 * Função unificada para criar alertas no sistema.
 * Esta função deve ser chamada quando um evento significativo ocorre.
 * Ela envia alertas para os usuários relevantes (usuário alvo, admins, admins de imobiliária).
 *
 * @param string $event_type Tipo do evento (ex: 'venda_concluida', 'novo_lead', 'corretor_aprovado').
 * @param string $message Mensagem principal do alerta.
 * @param int|null $target_user_id ID de um usuário específico para o qual o alerta é.
 * @param int|null $related_entity_id ID da entidade relacionada (ex: ID da reserva, ID do empreendimento, ID do usuário afetado).
 * @param string|null $related_entity_type Tipo da entidade relacionada (ex: 'reserva', 'empreendimento', 'usuario').
 * @param int|null $imobiliaria_id ID da imobiliária (se o alerta for para admin de imobiliária ou corretores dela).
 */
function create_alert($event_type, $message, $target_user_id = null, $related_entity_id = null, $related_entity_type = null, $imobiliaria_id = null) {
    global $conn;

    if (!$conn) {
        error_log("Erro: Conexão com o banco de dados não estabelecida em create_alert.");
        return false;
    }

    $link_key = "event_type={$event_type}";
    if ($related_entity_id) {
        $link_key .= "&entity_id={$related_entity_id}";
    }
    if ($related_entity_type) {
        $link_key .= "&entity_type={$related_entity_type}";
    }
    // Adiciona o ID da imobiliária ao link_key se relevante para o filtro futuro ou contexto
    if ($imobiliaria_id) {
        $link_key .= "&imobiliaria_id={$imobiliaria_id}";
    }


    $title = "Notificação do Sistema";
    switch ($event_type) {
        case 'venda_concluida':         $title = "Venda Concluída!"; break;
        case 'novo_lead':               $title = "Novo Lead Recebido!"; break;
        case 'novo_corretor_cadastro':  $title = "Novo Corretor Cadastrado!"; break;
        case 'corretor_aprovado':       $title = "Sua Conta Foi Aprovada!"; break;
        case 'reserva_aprovada':        $title = "Reserva Aprovada!"; break;
        case 'solicitar_documentacao':  $title = "Documentação Necessária!"; break;
        case 'documentos_enviados':     $title = "Documentos de Reserva Enviados!"; break;
        case 'documentos_aprovados':    $title = "Documentos Aprovados!"; break;
        case 'documentos_rejeitados':   $title = "Documentos Rejeitados!"; break;
        case 'contrato_enviado':        $title = "Contrato Enviado!"; break;
        case 'reserva_cancelada':       $title = "Reserva Cancelada!"; break;
        case 'lead_atribuido':          $title = "Lead Atribuído!"; break;
        case 'lead_assumido_admin':     $title = "Lead Assumido por Admin!"; break;
        case 'lead_dispensado':         $title = "Lead Dispensado!"; break;
        case 'lead_atendido_corretor':  $title = "Lead Atendido!"; break;
        case 'lead_dispensado_corretor':$title = "Lead Dispensado!"; break;
        case 'notificacao_geral':       $title = "Notificação Importante"; break;
        case 'nova_imobiliaria':        $title = "Nova Imobiliária Cadastrada!"; break;
        case 'status_usuario_alterado': $title = "Status de Usuário Alterado!"; break; // Para alterações de ativo/inativo
        case 'nova_reserva':            $title = "Nova Reserva Criada!"; break; // Quando o corretor cria
    }

    // Alerta para o usuário específico
    if ($target_user_id) {
        add_alert($target_user_id, $title, $message, $link_key);
    }

    // Alertas para Administradores (Admin Master) - sempre para o ADMIN_MASTER_USER_ID
    if (defined('ADMIN_MASTER_USER_ID')) {
        add_alert(ADMIN_MASTER_USER_ID, $title, $message, $link_key);
    } else {
        error_log("ADMIN_MASTER_USER_ID não definido. Alerta para Admin Master não enviado.");
    }
    
    // Alertas para Administradores da Imobiliária (se aplicável, e se não for o próprio admin logado)
    if ($imobiliaria_id) { // Este parâmetro $imobiliaria_id está na assinatura de create_alert
        // Buscar o admin_id da imobiliária para enviar o alerta
        $admin_imobiliaria_data = fetch_single("SELECT admin_id FROM imobiliarias WHERE id = ?", [$imobiliaria_id], "i");
        if ($admin_imobiliaria_data && $admin_imobiliaria_data['admin_id']) {
            // Não enviar alerta duplicado se o target_user_id já for o admin da imobiliária
            if ($target_user_id !== $admin_imobiliaria_data['admin_id']) {
                 add_alert($admin_imobiliaria_data['admin_id'], $title, $message, $link_key);
            }
        }
    }
    return true;
}

/**
 * Função auxiliar interna para formatar um único alerta e construir seu link de detalhes.
 * Usada por get_user_alerts.
 *
 * @param array $alert_row Array associativo de um alerta vindo do DB.
 * @return array Alerta formatado com 'link' e 'type' para exibição.
 */
function _format_single_alert($alert_row) {
    $detail_link = '';
    $user_info_logged = get_user_info(); // Quem está vendo o alerta
    $viewer_user_type = $user_info_logged['type'] ?? 'guest';

    // CORREÇÃO: Inicializa $link_params como array vazio para evitar passar null/string vazia para parse_str
    $link_params = [];
    if (!empty($alert_row['link'])) { // Verifica se a string 'link' não é nula ou vazia
        parse_str($alert_row['link'], $link_params);
    }
    
    $event_type = $link_params['event_type'] ?? '';
    $entity_id = $link_params['entity_id'] ?? null;
    $entity_type = $link_params['entity_type'] ?? null;
    $imobiliaria_id_alert_context = $link_params['imobiliaria_id'] ?? null; // ID da imobiliária no contexto do alerta

    // Lógica para construir o link final com base no TIPO DE USUÁRIO VISUALIZADOR e no TIPO DE EVENTO
    switch ($viewer_user_type) {
        case 'admin': // Admin Master pode ver tudo
            switch ($event_type) {
                case 'novo_lead':
                case 'reserva_aprovada':
                case 'solicitar_documentacao':
                case 'documentos_enviados': // Se for documento, levar para detalhes da reserva
                case 'documentos_aprovados':
                case 'documentos_rejeitados':
                case 'contrato_enviado': // Se for contrato, levar para detalhes da reserva
                case 'venda_concluida':
                case 'reserva_cancelada':
                case 'nova_reserva':
                case 'lead_atribuido':
                case 'lead_assumido_admin':
                case 'lead_dispensado':
                    $detail_link = BASE_URL . "admin/reservas/detalhes.php?id=" . $entity_id;
                    break;
                case 'novo_corretor_cadastro':
                case 'corretor_aprovado':
                case 'status_usuario_alterado': // Geral para ativação/inativação de usuário
                    $detail_link = BASE_URL . "admin/usuarios/editar.php?id=" . $entity_id;
                    break;
                case 'nova_imobiliaria':
                    $detail_link = BASE_URL . "admin/imobiliarias/editar.php?id=" . $entity_id;
                    break;
                default: // Fallback para tipos não mapeados explicitamente (pode ser um alerta geral)
                    $detail_link = BASE_URL . "admin/alertas/index.php";
                    break;
            }
            break;

        case 'admin_imobiliaria': // Admin da Imobiliária (links dentro do seu escopo)
            switch ($event_type) {
                case 'novo_lead_atribuido': // Lead atribuído ao corretor da sua equipe
                case 'lead_atendido_corretor':
                case 'lead_dispensado_corretor':
                case 'reserva_aprovada':
                case 'solicitar_documentacao':
                case 'documentos_enviados':
                case 'documentos_aprovados':
                case 'documentos_rejeitados':
                case 'contrato_enviado':
                case 'venda_concluida':
                case 'reserva_cancelada':
                case 'nova_reserva': // Quando um corretor da equipe cria
                    $detail_link = BASE_URL . "imobiliaria/reservas/detalhes.php?id=" . $entity_id;
                    break;
                case 'novo_corretor_cadastro':
                case 'corretor_aprovado':
                case 'status_usuario_alterado':
                    $detail_link = BASE_URL . "imobiliaria/corretores/detalhes.php?id=" . $entity_id;
                    break;
                default:
                    $detail_link = BASE_URL . "imobiliaria/index.php"; // Página principal da Imobiliária
                    break;
            }
            break;

        case 'corretor_autonomo': // Corretor Autônomo ou de Imobiliária
        case 'corretor_imobiliaria':
            switch ($event_type) {
                case 'novo_lead_atribuido':
                case 'lead_atendido_corretor':
                case 'lead_dispensado_corretor':
                case 'reserva_aprovada':
                case 'solicitar_documentacao':
                case 'documentos_enviados':
                case 'documentos_aprovados':
                case 'documentos_rejeitados':
                case 'contrato_enviado':
                case 'venda_concluida':
                case 'reserva_cancelada':
                case 'nova_reserva':
                    $detail_link = BASE_URL . "corretor/reservas/detalhes.php?id=" . $entity_id;
                    break;
                case 'status_usuario_alterado': // Se a própria conta do corretor teve o status alterado
                    $detail_link = BASE_URL . "corretor/perfil/index.php";
                    break;
                default:
                    $detail_link = BASE_URL . "corretor/index.php"; // Página principal do Corretor
                    break;
            }
            break;

        default: // Visitante ou tipo não reconhecido
            $detail_link = BASE_URL . "index.php";
            break;
    }

    // Determinar o 'type' visual para o badge CSS (cores)
    $type_display = 'info';
    if (strpos($event_type, 'rejeitado') !== false || strpos($event_type, 'cancelada') !== false || $event_type === 'dispensada' || strpos($event_type, 'inativado') !== false) {
        $type_display = 'danger'; // Alertas negativos
    } elseif (strpos($event_type, 'pendente') !== false || strpos($event_type, 'cadastro') !== false || strpos($event_type, 'solicitar') !== false || strpos($event_type, 'aguardando') !== false) {
        $type_display = 'warning'; // Alertas de atenção/pendência
    } elseif (strpos($event_type, 'aprovado') !== false || strpos($event_type, 'concluida') !== false || strpos($event_type, 'enviados') !== false || strpos($event_type, 'ativado') !== false || strpos($event_type, 'criado') !== false) {
        $type_display = 'success'; // Alertas de sucesso/conclusão
    }

    return [
        'id' => $alert_row['id'],
        'usuario_id' => $alert_row['usuario_id'],
        'title' => $alert_row['titulo'],
        'message' => $alert_row['mensagem'],
        'link' => $detail_link, // Link FINAL para o frontend
        'is_read' => (bool)$alert_row['lido'],
        'created_at' => $alert_row['data_criacao'],
        'event_type' => $event_type, // Adicionado para filtros no frontend e texto do tipo
        'type' => $type_display, // Tipo visual para o badge (cor)
    ];
}


/**
 * Função para obter alertas para um usuário específico.
 * Retorna alertas formatados com a URL de detalhes correta.
 *
 * @param int $usuario_id ID do usuário para quem buscar os alertas.
 * @param bool $include_read Se true, retorna também alertas lidos.
 * @param int $limit Limite de resultados.
 * @param int $offset Offset para paginação.
 * @return array Lista de alertas formatados.
 */
function get_user_alerts($usuario_id, $include_read = false, $limit = 10, $offset = 0) {
    global $conn;

    if (!$conn instanceof mysqli || $conn->connect_error) {
        error_log("Erro: Conexão com o banco de dados inválida em get_user_alerts.");
        return [];
    }

    $sql = "SELECT id, usuario_id, titulo, mensagem, link, lido, data_criacao FROM alertas WHERE usuario_id = ?";
    $params = [$usuario_id];
    $types = "i";

    if (!$include_read) {
        $sql .= " AND lido = 0"; // 'lido' é tinyint, 0 para FALSE
    }
    $sql .= " ORDER BY data_criacao DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $raw_alerts = fetch_all($sql, $params, $types);

    $formatted_alerts = [];
    foreach ($raw_alerts as $alert_row) {
        $formatted_alerts[] = _format_single_alert($alert_row); // Usa a nova função auxiliar
    }
    return $formatted_alerts;
}


/**
 * Função para marcar um alerta como lido.
 * As colunas no DB são: id, usuario_id, lido.
 * @param int $alert_id O ID do alerta a ser marcado.
 * @param int $usuario_id O ID do usuário que está marcando o alerta (para segurança).
 * @return bool True se o alerta foi marcado como lido, false caso contrário.
 */
function mark_alert_as_read($alert_id, $usuario_id) {
    global $conn;

    if (!$conn instanceof mysqli || $conn->connect_error) {
        error_log("Erro: Conexão com o banco de dados inválida em mark_alert_as_read.");
        return false;
    }

    $sql = "UPDATE alertas SET lido = 1 WHERE id = ? AND usuario_id = ? AND lido = 0";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Erro na preparação da query de marcar alerta: " . $conn->error);
        return false;
    }
    $stmt->bind_param("ii", $alert_id, $usuario_id);

    if (!$stmt->execute()) {
        error_log("Erro ao executar a query de marcar alerta como lido: " . $stmt->error);
        $stmt->close();
        return false;
    }
    $success = $stmt->affected_rows > 0;
    $stmt->close();
    return $success;
}

/**
 * Função para obter o número de alertas não lidos para um usuário.
 * As colunas no DB são: id, usuario_id, lido.
 * @param int $usuario_id ID do usuário.
 * @return int O número de alertas não lidos.
 */
function count_unread_alerts($usuario_id) {
    global $conn;

    if (!$conn instanceof mysqli || $conn->connect_error) {
        error_log("Erro: Conexão com o banco de dados inválida em count_unread_alerts.");
        return 0;
    }

    $sql = "SELECT COUNT(id) AS total FROM alertas WHERE usuario_id = ? AND lido = 0";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Erro na preparação da query de contar alertas: " . $conn->error);
        return 0;
    }
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['total'] ?? 0;
}