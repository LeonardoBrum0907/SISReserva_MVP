<?php

// Configurações de Banco de Dados - via variáveis de ambiente
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'mvpreserva');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Configurações da Aplicação
define('BASE_URL', getenv('BASE_URL') ?: 'https://leobrum.run/');
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'development');

// Define o caminho absoluto para a pasta 'includes'
define('INC_ROOT', __DIR__);

define('APP_NAME', 'SISReserva');
//Define ID do admin master
define('ADMIN_MASTER_USER_ID', 1); // Supondo que o ID do seu Admin Master é 1. Ajuste se for diferente.

// Configurações de Email - via variáveis de ambiente
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.hostinger.com');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');

// Configurações de Segurança
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'chave-padrao-desenvolvimento');
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'chave-padrao-desenvolvimento');

// Configurações de Upload
define('MAX_UPLOAD_SIZE', getenv('MAX_UPLOAD_SIZE') ?: '50M');
define('UPLOAD_PATH', getenv('UPLOAD_PATH') ?: 'uploads/');

// Configurações de Log
define('LOG_LEVEL', getenv('LOG_LEVEL') ?: 'debug');
define('LOG_PATH', getenv('LOG_PATH') ?: 'logs/');

// Configurações de Erro PHP baseadas no ambiente
if (ENVIRONMENT === 'production') {
    // Produção: DESLIGA exibição de erros no navegador
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Desenvolvimento: LIGA exibição de erros
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// LIGA o log de erros em arquivo para todos os ambientes
ini_set('log_errors', 1);
ini_set('error_log', INC_ROOT . '/../' . LOG_PATH . 'php_errors.log');
