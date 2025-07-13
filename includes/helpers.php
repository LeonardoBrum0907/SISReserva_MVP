<?php
// includes/helpers.php - Funções utilitárias diversas
if (!defined('BASE_URL')) {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    }
}

// Função para formatar valores monetários em BRL
if (!function_exists('format_currency_brl')) {
    function format_currency_brl($value) {
        if (!is_numeric($value) || $value === null) {
            return 'Sob Consulta';
        }
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}

// Função para formatar datas e horas em formato brasileiro (dd/mm/aaaa hh:mm:ss)
if (!function_exists('format_datetime_br')) {
    function format_datetime_br($datetime_str) {
        if (empty($datetime_str) || $datetime_str === '0000-00-00 00:00:00') {
            return 'N/A';
        }
        try {
            $datetime = new DateTime($datetime_str);
            return $datetime->format('d/m/Y H:i:s');
        } catch (Exception $e) {
            error_log("Erro ao formatar data: " . $datetime_str . " - " . $e->getMessage());
            return 'Data Inválida';
        }
    }
}

// Função para gerar tokens aleatórios (pode ser usada para recuperação de senha)
if (!function_exists('generate_token')) {
    function generate_token($length = 32) {
        try {
            return bin2hex(random_bytes($length));
        } catch (Exception $e) {
            error_log("Erro ao gerar token: " . $e->getMessage());
            return false;
        }
    }
}

// Função para formatar tempo decorrido
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        // Mapeamento de unidades para strings em português
        $string_map = array(
            'y' => 'ano',
            'm' => 'mês',
            'd' => 'dia',
            'h' => 'hora',
            'i' => 'minuto',
            's' => 'segundo',
        );

        $output_parts = array();

        // Verifica e adiciona anos
        if ($diff->y > 0) {
            $output_parts[] = $diff->y . ' ' . $string_map['y'] . ($diff->y > 1 ? 's' : '');
        }
        // Verifica e adiciona meses
        if ($diff->m > 0) {
            $output_parts[] = $diff->m . ' ' . $string_map['m'] . ($diff->m > 1 ? 'es' : '');
        }

        // Calcula semanas a partir do total de dias ($diff->d)
        $weeks = floor($diff->d / 7);
        if ($weeks > 0) {
            $output_parts[] = $weeks . ' ' . 'semana' . ($weeks > 1 ? 's' : '');
        }
        // Dias restantes após contabilizar as semanas completas
        $remaining_days = $diff->d % 7;
        if ($remaining_days > 0) {
            $output_parts[] = $remaining_days . ' ' . $string_map['d'] . ($remaining_days > 1 ? 's' : '');
        }

        // Verifica e adiciona horas
        if ($diff->h > 0) {
            $output_parts[] = $diff->h . ' ' . $string_map['h'] . ($diff->h > 1 ? 's' : '');
        }
        // Verifica e adiciona minutos
        if ($diff->i > 0) {
            $output_parts[] = $diff->i . ' ' . $string_map['i'] . ($diff->i > 1 ? 's' : '');
        }
        // Verifica e adiciona segundos
        if ($diff->s > 0) {
            $output_parts[] = $diff->s . ' ' . $string_map['s'] . ($diff->s > 1 ? 's' : '');
        }

        if (!$full) {
            // Pega apenas a primeira parte se $full for falso
            $output_parts = array_slice($output_parts, 0, 1);
        }

        return $output_parts ? implode(', ', $output_parts) . ' atrás' : 'agora mesmo';
    }
}

if (!function_exists('time_remaining_string')) {
    function time_remaining_string($datetime_future) {
        if (empty($datetime_future) || $datetime_future === '0000-00-00 00:00:00') {
            return 'N/A';
        }

        try {
            $now = new DateTime();
            $future = new DateTime($datetime_future);
            $diff = $now->diff($future);
        } catch (Exception $e) {
            error_log("Erro ao parsear data futura em time_remaining_string: " . $datetime_future . " - " . $e->getMessage());
            return 'Inválida';
        }

        if ($diff->invert === 1) {
            return 'Expirada';
        }

        $output_parts = [];

        if ($diff->y > 0) {
            $output_parts[] = $diff->y . ' ano' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            $output_parts[] = $diff->m . ' mês' . ($diff->m > 1 ? 'es' : '');
        }
        if ($diff->d > 0) {
            $output_parts[] = $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
        }

        if (!empty($output_parts)) {
            return implode(', ', array_slice($output_parts, 0, 1));
        }

        if ($diff->h > 0) {
            $output_parts[] = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        }
        if ($diff->i > 0 && empty($output_parts)) {
            $output_parts[] = $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
        }
        if ($diff->s > 0 && empty($output_parts)) {
            $output_parts[] = $diff->s . ' segundo' . ($diff->s > 1 ? 's' : '');
        }

        if (empty($output_parts)) {
            return 'Menos de um minuto';
        }

        return implode(', ', $output_parts);
    }
}


// Função para sanitizar entradas do usuário
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        // CORRIGIDO: Sempre retorna uma string vazia se o dado for null
        if ($data === null) {
            return '';
        }
        // Garante que o dado é uma string antes de aplicar trim e outras funções de string.
        $data = (string) $data;
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

// Função para formatar CPF (apenas dígitos)
if (!function_exists('format_cpf')) {
    function format_cpf($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf ?? ''); // Adicionado ?? '' para segurança
        if (strlen($cpf) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
        }
        return $cpf;
    }
}

// Função para formatar WhatsApp (apenas dígitos)
if (!function_exists('format_whatsapp')) {
    function format_whatsapp($whatsapp) {
        $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp ?? ''); // Adicionado ?? '' para segurança
        if (strlen($whatsapp) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $whatsapp);
        } elseif (strlen($whatsapp) === 10) { 
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $whatsapp);
        }
        return $whatsapp;
    }
}

if (!function_exists('format_cep')) {
    function format_cep($cep) {
        $cep = preg_replace('/[^0-9]/', '', $cep ?? ''); // Adicionado ?? '' para segurança
        if (strlen($cep) === 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
        }
        return $cep;
    }
}

/**
 * Formata um CNPJ para o padrão XX.XXX.XXX/XXXX-XX.
 *
 * @param string $cnpj O CNPJ a ser formatado (apenas números).
 * @return string O CNPJ formatado ou o original se inválido.
 */
if (!function_exists('format_cnpj')) {
    function format_cnpj($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj ?? ''); // Adicionado ?? '' para segurança
        if (strlen($cnpj) === 14) {
            return substr($cnpj, 0, 2) . '.' .
                   substr($cnpj, 2, 3) . '.' .
                   substr($cnpj, 5, 3) . '/' .
                   substr($cnpj, 8, 4) . '-' .
                   substr($cnpj, 12, 2);
        }
        return $cnpj;
    }
}

/**
 * Trunca uma string para um comprimento máximo, adicionando reticências se necessário.
 *
 * @param string $string A string a ser truncada.
 * @param int $length O comprimento máximo desejado.
 * @param string $etc O caractere para usar como reticências (padrão: '...').
 * @param bool $break_words Se true, quebra palavras. Se false, tenta truncar em um espaço.
 * @return string A string truncada.
 */
if (!function_exists('truncate_string')) {
    function truncate_string($string, $length, $etc = '...', $break_words = false) {
        mb_internal_encoding("UTF-8");
        $string = (string)($string ?? ''); // Garante que a string seja tratada como UTF-8 e não seja null

        if ($length == 0) return '';
        if (mb_strlen($string) > $length) {
            $length -= mb_strlen($etc);
            if ($length < 0) return $etc;

            if ($break_words) {
                return mb_substr($string, 0, $length) . $etc;
            } else {
                $cut_string = mb_substr($string, 0, $length);
                $last_space = mb_strrpos($cut_string, ' ');
                if ($last_space === false) {
                    return $cut_string . $etc;
                }
                return mb_substr($string, 0, $last_space) . $etc;
            }
        } else {
            return $string;
        }
    }
}

// Funções de banco de dados (fetch_single, fetch_all, insert_data, update_delete_data)
// Mantenho as mesmas implementações, presumindo que já funcionam.
// Funções para obter dados de uma única linha do banco de dados
if (!function_exists('fetch_single')) {
    function fetch_single($sql, $params = [], $types = "") {
        global $conn;
        if (!$conn) {
            throw new Exception("Conexão com o banco de dados não estabelecida.");
        }
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Erro ao preparar query: " . $conn->error);
        }
        if ($types && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
}

// Função para obter múltiplas linhas do banco de dados
if (!function_exists('fetch_all')) {
    function fetch_all($sql, $params = [], $types = "") {
        global $conn;
        if (!$conn) {
            throw new Exception("Conexão com o banco de dados não estabelecida.");
        }
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Erro ao preparar query: " . $conn->error);
        }
        if ($types && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }
}

// Função para inserir dados no banco de dados
if (!function_exists('insert_data')) {
    function insert_data($sql, $params = [], $types = "") {
        global $conn;
        if (!$conn) {
            throw new Exception("Conexão com o banco de dados não estabelecida.");
        }
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Erro ao preparar query: " . $conn->error);
        }
        if ($types && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $last_id = $stmt->insert_id;
        $stmt->close();
        return $last_id;
    }
}

// Função para atualizar ou deletar dados no banco de dados
if (!function_exists('update_delete_data')) {
    function update_delete_data($sql, $params = [], $types = "") {
        global $conn;
        if (!$conn) {
            throw new Exception("Conexão com o banco de dados não estabelecida.");
        }
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Erro ao preparar query: " . $conn->error);
        }
        if ($types && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $affected_rows;
    }
}


// Função para validar formato de data (YYYY-MM-DD)
if (!function_exists('is_valid_date')) {
    function is_valid_date($dateString) {
        if (empty($dateString)) {
            return false;
        }
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dateString)) {
            return false;
        }
        list($year, $month, $day) = explode('-', $dateString);
        return checkdate((int)$month, (int)$day, (int)$year);
    }
}

if (!function_exists('generate_unique_filename')) {
    function generate_unique_filename($original_filename) {
        $extension = pathinfo($original_filename ?? '', PATHINFO_EXTENSION); // Adicionado ?? '' para segurança
        return uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }
}

if (!function_exists('slugify')) {
    function slugify($text) {
        $text = (string)($text ?? ''); // Garante que $text é uma string e não null
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }
}

// Função para calcular o tempo restante até a expiração
if (!function_exists('calculate_time_remaining')) {
    function calculate_time_remaining($expiration_datetime_str) {
        if (!$expiration_datetime_str || $expiration_datetime_str === '0000-00-00 00:00:00') {
            return 'N/A';
        }

        try {
            $expiration_datetime = new DateTime($expiration_datetime_str);
            $now = new DateTime();

            if ($expiration_datetime < $now) {
                return 'Expirada';
            }

            $interval = $now->diff($expiration_datetime);

            if ($interval->y > 0) {
                return $interval->y . ' ano' . ($interval->y > 1 ? 's' : '');
            } elseif ($interval->m > 0) {
                return $interval->m . ' mês' . ($interval->m > 1 ? 'es' : '');
            } elseif ($interval->d > 0) {
                return $interval->d . ' dia' . ($interval->d > 1 ? 's' : '');
            } elseif ($interval->h > 0) {
                return $interval->h . ' hora' . ($interval->h > 1 ? 's' : '');
            } elseif ($interval->i > 0) {
                return $interval->i . ' minuto' . ($interval->i > 1 ? 's' : '');
            } else {
                return $interval->s . ' segundo' . ($interval->s > 1 ? 's' : '');
            }
        } catch (Exception $e) {
            error_log("Erro ao calcular tempo restante: " . $e->getMessage());
            return 'N/A';
        }
    }
}

// Função para obter a classe CSS do badge de status
if (!function_exists('get_status_badge_class')) {
    function get_status_badge_class($status) {
        $status_map = [
            'solicitada' => 'status-solicitada',
            'aprovada' => 'status-aprovada',
            'documentos_pendentes' => 'status-documentos_pendentes',
            'documentos_enviados' => 'status-documentos_enviados',
            'documentos_aprovados' => 'status-documentos_aprovados',
            'documentos_rejeitados' => 'status-documentos_rejeitados',
            'contrato_enviado' => 'status-contrato_enviado',
            'aguardando_assinatura_eletronica' => 'status-aguardando_assinatura_eletronica',
            'vendida' => 'status-vendida',
            'cancelada' => 'status-cancelada',
            'expirada' => 'status-expirada',
            'dispensada' => 'status-dispensada',
            'rejeitada' => 'status-rejeitada',
            'ativo' => 'status-ativo',
            'pausado' => 'status-pausado',
            'indisponivel' => 'status-indisponivel',
            'bloqueada' => 'status-bloqueada',
            'disponivel' => 'status-disponivel',
            'pendente' => 'status-pendente',
            'warning' => 'status-warning',
            'success' => 'status-success',
            'danger' => 'status-danger',
            'info' => 'status-info',
        ];
        return $status_map[$status] ?? 'status-info'; 
    }
}

?>