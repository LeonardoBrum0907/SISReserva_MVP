<?php
// scripts/create_test_enterprise.php - Script para criar um Empreendimento para Teste de Carga

// ESTE SCRIPT DEVE SER USADO APENAS PARA DESENVOLVIMENTO/TESTES!
// REMOVA-O OU PROTEJA-O EM PRODUÇÃO!

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/helpers.php'; // Para insert_data, etc.

echo "<h1>Criação de Empreendimento para Teste de Carga</h1>";
echo "<p>Este script irá criar o empreendimento 'Edifício Infinity Towers - Teste de Carga' com 100 unidades.</p>";
echo "<p style='color: red; font-weight: bold;'>AVISO: Pode demorar um pouco devido à quantidade de unidades!</p>";
echo "<hr>";

try {
    global $conn;
    $conn = get_db_connection();
} catch (Exception $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Iniciar Transação Global
$conn->begin_transaction();

try {
    $empreendimento_nome = "Edifício Infinity Towers - Teste de Carga";

    // Verificar se o empreendimento já existe
    $existing_emp = fetch_single("SELECT id FROM empreendimentos WHERE nome = ?", [$empreendimento_nome], "s");
    if ($existing_emp) {
        echo "<p style='color: orange;'>Empreendimento '{$empreendimento_nome}' já existe (ID: {$existing_emp['id']}). Pulando criação.</p>";
        $empreendimento_id = $existing_emp['id'];
        // Se o empreendimento já existe, podemos pular a criação dele e de seus tipos/unidades se for o caso.
        // Para um teste de carga, podemos até querer deletar e recriar. Mas por segurança, vamos pular.
        $conn->rollback(); // Reverte a transação pois nada novo será inserido para este empreendimento
        exit();
    }

    // 1. Inserir Empreendimento
    $descricao_longa = "O Edifício Infinity Towers representa o ápice da arquitetura moderna e do conforto urbano. Localizado em uma área privilegiada de Palmas, este empreendimento misto oferece unidades residenciais e comerciais de alto padrão, ideal para quem busca qualidade de vida e conveniência. Com uma infraestrutura completa, que inclui piscina aquecida, academia de última geração, salão de festas elegante, espaços de coworking e segurança 24 horas, o Infinity Towers redefine o conceito de moradia e investimento. Cada detalhe foi pensado para proporcionar uma experiência única, desde os acabamentos de luxo até a vista panorâmica da cidade. Perfeito para famílias, jovens profissionais e empresas que buscam um ambiente dinâmico e sofisticado. Explore as diversas opções de plantas e encontre o espaço ideal para você ou seu negócio neste marco arquitetônico.";
    $cep_teste = "77060000"; // CEP de Palmas - Jardim Aureny III
    $endereco_teste = "Avenida Tocantins";
    $numero_teste = "1234";
    $bairro_teste = "Centro";
    $cidade_teste = "Palmas";
    $estado_teste = "TO";

    $documentos_obrigatorios = json_encode(["RG", "CPF", "Comprovante de Renda", "Comprovante de Residência", "Certidão de Nascimento/Casamento"]);
    $permissoes_visualizacao = json_encode(["Cliente Final", "Corretor", "Admin"]);
    $documentos_necessarios = json_encode(["RG", "CPF", "Comprovante de Renda"]); // Para download público

    $empreendimento_id = insert_data(
        "INSERT INTO empreendimentos (nome, tipo_uso, tipo_empreendimento, descricao, cep, endereco, numero, complemento, bairro, cidade, estado, foto_localizacao, status, momento_envio_documentacao, documentos_obrigatorios, permissoes_visualizacao, permissao_reserva, prazo_expiracao_reserva, documentos_necessarios, data_cadastro, data_atualizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', ?, ?, ?, ?, ?, ?, NOW(), NOW())",
        [
            $empreendimento_nome, 'Residencial', 'Apartamento', $descricao_longa,
            $cep_teste, $endereco_teste, $numero_teste, 'Bloco A', $bairro_teste, $cidade_teste, $estado_teste,
            'uploads/empreendimentos/loc_infinity.jpg', 'Na Proposta de Reserva', $documentos_obrigatorios,
            $permissoes_visualizacao, 'Todos', 7, $documentos_necessarios
        ],
        "sssssssssssssssssi"
    );

    if (!$empreendimento_id) {
        throw new Exception("Erro ao inserir empreendimento.");
    }
    echo "<p style='color: green;'>Empreendimento '{$empreendimento_nome}' criado com ID: {$empreendimento_id}</p>";

    // 2. Inserir Mídias
    $midias = [
        ['caminho_arquivo' => 'uploads/empreendimentos/main_infinity.jpg', 'tipo' => 'foto_principal', 'descricao' => 'Foto Principal do Empreendimento'],
        ['caminho_arquivo' => 'uploads/empreendimentos/galeria_infinity_1.jpg', 'tipo' => 'galeria_foto', 'descricao' => 'Fachada do Edifício'],
        ['caminho_arquivo' => 'uploads/empreendimentos/galeria_infinity_2.jpg', 'tipo' => 'galeria_foto', 'descricao' => 'Área de Lazer'],
        ['caminho_arquivo' => 'uploads/empreendimentos/galeria_infinity_3.jpg', 'tipo' => 'galeria_foto', 'descricao' => 'Academia'],
        ['caminho_arquivo' => 'uploads/empreendimentos/galeria_infinity_4.jpg', 'tipo' => 'galeria_foto', 'descricao' => 'Piscina Panorâmica'],
        ['caminho_arquivo' => 'uploads/empreendimentos/galeria_infinity_5.jpg', 'tipo' => 'galeria_foto', 'descricao' => 'Espaço Gourmet'],
        ['caminho_arquivo' => 'uploads/contratos/modelo_contrato_geral.pdf', 'tipo' => 'documento_contrato', 'descricao' => 'Contrato Padrão do Empreendimento'],
        ['caminho_arquivo' => 'uploads/documentos/memorial_descritivo_geral.pdf', 'tipo' => 'documento_memorial', 'descricao' => 'Memorial Descritivo Completo'],
        ['caminho_arquivo' => 'dQw4w9WgXcQ', 'tipo' => 'video', 'descricao' => 'Vídeo Institucional - YouTube'], // Rick Astley
        ['caminho_arquivo' => 'uploads/empreendimentos/thumb_video_infinity.jpg', 'tipo' => 'explore_video_thumb', 'descricao' => 'Thumbnail Vídeos Explore'],
        ['caminho_arquivo' => 'uploads/empreendimentos/thumb_gallery_infinity.jpg', 'tipo' => 'explore_gallery_thumb', 'descricao' => 'Thumbnail Galeria Explore'],
    ];

    foreach ($midias as $midia) {
        $midia_id = insert_data(
            "INSERT INTO midias_empreendimentos (empreendimento_id, caminho_arquivo, tipo, descricao) VALUES (?, ?, ?, ?)",
            [$empreendimento_id, $midia['caminho_arquivo'], $midia['tipo'], $midia['descricao']],
            "isss"
        );
        if (!$midia_id) {
            echo "<p style='color: red;'>Erro ao inserir mídia: " . htmlspecialchars($midia['descricao']) . "</p>";
        }
    }
    echo "<p style='color: green;'>Mídias inseridas com sucesso.</p>";


    // 3. Inserir Tipos de Unidades
    $tipos_unidades_data = [
        ['tipo' => 'Studio Compacto', 'metragem' => 30.50, 'quartos' => 1, 'banheiros' => 1, 'vagas' => 0, 'foto_planta' => 'uploads/plantas/planta_studio.jpg'],
        ['tipo' => 'Apartamento 1 Dorm', 'metragem' => 45.00, 'quartos' => 1, 'banheiros' => 1, 'vagas' => 1, 'foto_planta' => 'uploads/plantas/planta_1dorm.jpg'],
        ['tipo' => 'Apartamento 2 Dorms', 'metragem' => 68.75, 'quartos' => 2, 'banheiros' => 2, 'vagas' => 1, 'foto_planta' => 'uploads/plantas/planta_2dorms.jpg'],
        ['tipo' => 'Apartamento 3 Dorms', 'metragem' => 95.20, 'quartos' => 3, 'banheiros' => 3, 'vagas' => 2, 'foto_planta' => 'uploads/plantas/planta_3dorms.jpg'],
    ];

    $tipos_unidades_map = [];
    foreach ($tipos_unidades_data as $index => $tipo_data) {
        $tipo_unidade_db_id = insert_data(
            "INSERT INTO tipos_unidades (empreendimento_id, tipo, metragem, quartos, banheiros, vagas, foto_planta) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$empreendimento_id, $tipo_data['tipo'], $tipo_data['metragem'], $tipo_data['quartos'], $tipo_data['banheiros'], $tipo_data['vagas'], $tipo_data['foto_planta']],
            "isiiiss"
        );
        if (!$tipo_unidade_db_id) {
            throw new Exception("Erro ao inserir tipo de unidade: " . htmlspecialchars($tipo_data['tipo']));
        }
        $tipos_unidades_map[$index] = $tipo_unidade_db_id;
    }
    echo "<p style='color: green;'>Tipos de unidades inseridos com sucesso.</p>";

    // 4. Definir Fluxo de Pagamento (Exemplo)
    $fluxo_pagamento_exemplo = [
        ['descricao' => 'Sinal', 'quantas_vezes' => 1, 'tipo_valor' => 'Percentual (%)', 'valor' => 0.10, 'tipo_calculo' => 'Fixo'], // 10%
        ['descricao' => 'Mensais', 'quantas_vezes' => 48, 'tipo_valor' => 'Percentual (%)', 'valor' => 0.015, 'tipo_calculo' => 'Proporcional'], // 1.5% * 48 = 72%
        ['descricao' => 'Chaves', 'quantas_vezes' => 1, 'tipo_valor' => 'Percentual (%)', 'valor' => 0.18, 'tipo_calculo' => 'Fixo'], // 18% (10 + 72 + 18 = 100%)
    ];
    $fluxo_pagamento_json = json_encode($fluxo_pagamento_exemplo);


    // 5. Inserir Unidades (10 andares x 10 unidades/andar = 100 unidades)
    $num_andares = 10;
    $unidades_por_andar = 10;
    $unidades_inseridas_count = 0;

    for ($andar = 1; $andar <= $num_andares; $andar++) {
        for ($posicao_idx = 1; $posicao_idx <= $unidades_por_andar; $posicao_idx++) {
            $numero_unidade = $andar * 100 + $posicao_idx; // Ex: 101, 205
            $posicao_final = str_pad($posicao_idx, 2, '0', STR_PAD_LEFT); // Ex: "01", "05"

            // Atribui tipo de unidade em rotação
            $tipo_unidade_idx = ($posicao_idx - 1) % count($tipos_unidades_data);
            $tipo_unidade_id = $tipos_unidades_map[$tipo_unidade_idx];
            $tipo_unidade_info = $tipos_unidades_data[$tipo_unidade_idx];

            // Calcula valor base da unidade (simulação)
            $valor_base = $tipo_unidade_info['metragem'] * 5000; // Ex: R$5000/m²
            $valor_unidade = $valor_base + ($andar * 1000); // Valor aumenta com o andar

            $unidade_id_db = insert_data(
                "INSERT INTO unidades (empreendimento_id, tipo_unidade_id, numero, andar, posicao, valor, status, informacoes_pagamento) VALUES (?, ?, ?, ?, ?, ?, 'disponivel', ?)",
                [$empreendimento_id, $tipo_unidade_id, $numero_unidade, $andar, $posicao_final, $valor_unidade, $fluxo_pagamento_json],
                "iisiids"
            );

            if (!$unidade_id_db) {
                echo "<p style='color: red;'>Erro ao inserir unidade {$numero_unidade} no {$andar}º andar.</p>";
            } else {
                $unidades_inseridas_count++;
            }
        }
    }
    echo "<p style='color: green;'>{$unidades_inseridas_count} unidades inseridas com sucesso.</p>";

    $conn->commit(); // Confirma todas as operações da transação
    echo "<p style='color: green; font-weight: bold;'>Empreendimento '{$empreendimento_nome}' e todas as suas unidades criadas com sucesso!</p>";

} catch (Exception $e) {
    $conn->rollback(); // Reverte todas as operações em caso de erro
    echo "<p style='color: red; font-weight: bold;'>Erro crítico durante a criação do empreendimento: " . $e->getMessage() . "</p>";
    error_log("Erro no script create_test_enterprise.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>