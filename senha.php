<?php
// sistema_imobiliario/generate_password_hash.php

$password_to_hash = 'admin123';
$hashed_password = password_hash($password_to_hash, PASSWORD_DEFAULT);

echo "Senha original: " . $password_to_hash . "\n";
echo "Hash gerado: " . $hashed_password . "\n";
echo "\nCopie este hash e atualize a senha do usuário 'admin@sistema.com' na tabela 'usuarios' do seu banco de dados.";
?>