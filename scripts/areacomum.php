<?php
// scripts/populate_areas_comuns.php - Script para criar e popular a tabela areas_comuns_catalogo

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/database.php'; // Para get_db_connection() e funções DB

echo "<h1>População do Catálogo de Áreas Comuns</h1>";
echo "<p>Este script irá criar a tabela 'areas_comuns_catalogo' (se não existir) e preenchê-la com áreas comuns pré-definidas.</p>";
echo "<hr>";

try {
    global $conn;
    $conn = get_db_connection();

    // 1. Criar a tabela areas_comuns_catalogo se ela não existir
    $sql_create_table = "
        CREATE TABLE IF NOT EXISTS areas_comuns_catalogo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL UNIQUE,
            data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    if ($conn->query($sql_create_table) === TRUE) {
        echo "<p style='color: green;'>Tabela 'areas_comuns_catalogo' criada ou já existe.</p>";
    } else {
        throw new Exception("Erro ao criar tabela 'areas_comuns_catalogo': " . $conn->error);
    }

    // Itens de áreas comuns a serem inseridos
    $areas_comuns = [
        "Piscina", "Academia", "Salão de Festas", "Churrasqueira", "Playground",
        "Portaria 24h", "Elevador", "Espaço Gourmet", "Quadra Poliesportiva",
        "Pet Place", "Coworking", "Bicicletário", "Brinquedoteca", "Sauna", "Spa"
    ];

    $conn->begin_transaction();
    $inserted_count = 0;
    foreach ($areas_comuns as $area) {
        // Verifica se o item já existe para evitar duplicatas em execuções repetidas
        $check_sql = "SELECT id FROM areas_comuns_catalogo WHERE nome = ?";
        $stmt_check = $conn->prepare($check_sql);
        if ($stmt_check) {
            $stmt_check->bind_param("s", $area);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows === 0) {
                // Item não existe, insere
                $insert_sql = "INSERT INTO areas_comuns_catalogo (nome) VALUES (?)";
                $stmt_insert = $conn->prepare($insert_sql);
                if ($stmt_insert) {
                    $stmt_insert->bind_param("s", $area);
                    if ($stmt_insert->execute()) {
                        $inserted_count++;
                    } else {
                        echo "<p style='color: red;'>Erro ao inserir '{$area}': " . $stmt_insert->error . "</p>";
                    }
                    $stmt_insert->close();
                } else {
                    echo "<p style='color: red;'>Erro ao preparar insert para '{$area}': " . $conn->error . "</p>";
                }
            } else {
                echo "<p>Área comum '{$area}' já existe no catálogo.</p>";
            }
            $stmt_check->close();
        } else {
            echo "<p style='color: red;'>Erro ao preparar check para '{$area}': " . $conn->error . "</p>";
        }
    }

    $conn->commit();
    echo "<p style='color: green; font-weight: bold;'>População do catálogo de áreas comuns finalizada. {$inserted_count} novos itens inseridos (ou já existentes).</p>";

} catch (Exception $e) {
    if ($conn) {
        $conn->rollback();
    }
    echo "<p style='color: red; font-weight: bold;'>Erro crítico: " . $e->getMessage() . "</p>";
    error_log("Erro no script populate_areas_comuns.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>