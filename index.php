<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/helpers.php';

try {
   global $conn;
   $conn = get_db_connection();
} catch (Exception $e) {
   error_log("Erro crítico na inicialização do DB em index.php: " . $e->getMessage());
   die("<h1>Ops...</h1><p>Tivemos um probleminha por aqui, tente mais tarde =)</p>");
}

$page_title = "Brognoli Online by Suite Place";

$empreendimentos = [];
$total_empreendimentos = 0;

try {
   $sql_count = "SELECT COUNT(id) AS total FROM empreendimentos WHERE status = 'ativo'";
   $result_count = fetch_single($sql_count);
   if ($result_count) {
      $total_empreendimentos = $result_count['total'];
   }

   $sql_empreendimentos = "
        SELECT
            e.id,
            e.nome,
            e.endereco,
            e.numero,
            e.bairro,
            e.cidade,
            e.estado,
            e.cep, 
            e.tipo_uso,
            e.tipo_empreendimento,
            e.status,
            (SELECT MIN(u.valor) FROM unidades u WHERE u.empreendimento_id = e.id AND u.status = 'disponivel') AS menor_valor_disponivel,
            (SELECT MIN(tu.quartos) FROM tipos_unidades tu WHERE tu.empreendimento_id = e.id) AS min_quartos,
            (SELECT MAX(tu.quartos) FROM tipos_unidades tu WHERE tu.empreendimento_id = e.id) AS max_quartos,
            (SELECT MIN(tu.banheiros) FROM tipos_unidades tu WHERE tu.empreendimento_id = e.id) AS min_banheiros,
            (SELECT MAX(tu.banheiros) FROM tipos_unidades tu WHERE tu.empreendimento_id = e.id) AS max_banheiros,
            (SELECT MIN(tu.vagas) FROM tipos_unidades tu WHERE tu.empreendimento_id = e.id) AS min_vagas,
            (SELECT MAX(tu.vagas) FROM tipos_unidades tu WHERE tu.empreendimento_id = e.id) AS max_vagas,
            (SELECT MIN(tu.metragem) FROM tipos_unidades tu WHERE tu.empreendimento_id = e.id) AS min_metragem,
            (SELECT MAX(tu.metragem) FROM tipos_unidades tu WHERE tu.empreendimento_id = e.id) AS max_metragem,
            (SELECT me.caminho_arquivo FROM midias_empreendimentos me WHERE me.empreendimento_id = e.id AND me.tipo = 'foto_principal' LIMIT 1) AS foto_principal,
            (SELECT me.caminho_arquivo FROM midias_empreendimentos me WHERE me.empreendimento_id = e.id AND me.tipo = 'galeria_foto' ORDER BY me.data_upload ASC LIMIT 1 OFFSET 0) AS galeria_foto_1,
            (SELECT me.caminho_arquivo FROM midias_empreendimentos me WHERE me.empreendimento_id = e.id AND me.tipo = 'galeria_foto' ORDER BY me.data_upload ASC LIMIT 1 OFFSET 1) AS galeria_foto_2
         FROM
            empreendimentos e
        WHERE
            e.status IN ('ativo', 'lancamento', 'em_construcao', 'pronto_para_morar_entregue') -- Filtro de status para exibição pública
        ORDER BY
            e.data_cadastro DESC;
    ";
   $empreendimentos = fetch_all($sql_empreendimentos);
} catch (Exception $e) {
   error_log("Erro ao buscar empreendimentos: " . $e->getMessage());
   die("<h1>Ops...</h1><p>Tivemos um probleminha por aqui, tente mais tarde =)</p>");
}

require_once 'includes/header_public.php';
?>

<section class="hero-section">
   <div class="hero-content">
      <div class="empreendimentos-counter">
         <p><span class="counter-value"><?php echo htmlspecialchars($total_empreendimentos); ?></span> empreendimentos disponíveis</p>
      </div>
      <div class="filter-area">
         <button class="btn btn-primary" id="filter-toggle">Filtrar <i class="fas fa-filter"></i></button>
      </div>
   </div>
</section>

<section class="empreendimentos-list">
   <div class="grid-layout">
      <?php if (empty($empreendimentos)): ?>
         <p style="text-align: center; grid-column: 1 / -1;">Não encontrarmos nenhum empreendimento ativo no momento.</p>
      <?php else: ?>
         <?php foreach ($empreendimentos as $emp):
            $menor_valor = format_currency_brl($emp['menor_valor_disponivel']);
            $status = $emp['status'];

            $fa_quartos = (isset($emp['min_quartos']) && $emp['min_quartos'] !== null)
               ? ($emp['min_quartos'] == $emp['max_quartos'] ? htmlspecialchars($emp['min_quartos']) : htmlspecialchars($emp['min_quartos'] . ' a ' . $emp['max_quartos']))
               : 'N/A';
            $fa_banheiros = (isset($emp['min_banheiros']) && $emp['min_banheiros'] !== null)
               ? ($emp['min_banheiros'] == $emp['max_banheiros'] ? htmlspecialchars($emp['min_banheiros']) : htmlspecialchars($emp['min_banheiros'] . ' a ' . $emp['max_banheiros']))
               : 'N/A';
            $fa_vagas = (isset($emp['min_vagas']) && $emp['min_vagas'] !== null)
               ? ($emp['min_vagas'] == $emp['max_vagas'] ? htmlspecialchars($emp['min_vagas']) : htmlspecialchars($emp['min_vagas'] . ' a ' . $emp['max_vagas']))
               : 'N/A';
            $fa_metragem = (isset($emp['min_metragem']) && $emp['min_metragem'] !== null)
               ? ($emp['min_metragem'] == $emp['max_metragem'] ? htmlspecialchars(number_format($emp['min_metragem'], 0, ',', '.')) : htmlspecialchars(number_format($emp['min_metragem'], 0, ',', '.') . ' a ' . number_format($emp['max_metragem'], 0, ',', '.')))
               : 'N/A';

            $foto_path = !empty($emp['foto_principal']) ? BASE_URL . htmlspecialchars($emp['foto_principal']) : BASE_URL . 'assets/images/placeholder.jpg';
            $galeria_foto_1_path = !empty($emp['galeria_foto_1']) ? BASE_URL . htmlspecialchars($emp['galeria_foto_1']) : BASE_URL . 'assets/images/placeholder.jpg';
            $galeria_foto_2_path = !empty($emp['galeria_foto_2']) ? BASE_URL . htmlspecialchars($emp['galeria_foto_2']) : BASE_URL . 'assets/images/placeholder.jpg';
         ?>
            <a href="<?php echo BASE_URL; ?>empreendimento.php?id=<?php echo htmlspecialchars($emp['id']); ?>" class="empreendimento-card desktop">
               <div class="card-text-content">
                  <div class="caracteristicas-compactas-wrapper">
                     <span class="status"><?php echo $status; ?></span>
                     <div class="caracteristicas-compactas">
                        <?php if ($fa_quartos !== 'N/A'): ?>
                           <span><i class="fas fa-bed"></i> <?php echo $fa_quartos; ?></span>
                        <?php endif; ?>
                        <?php if ($fa_banheiros !== 'N/A'): ?>
                           <span><i class="fas fa-shower"></i> <?php echo $fa_banheiros; ?></span>
                        <?php endif; ?>
                        <?php if ($fa_vagas !== 'N/A'): ?>
                           <span><i class="fas fa-car"></i> <?php echo $fa_vagas; ?></span>
                        <?php endif; ?>
                     </div>
                  </div>
                  <h2><?php echo htmlspecialchars($emp['nome']); ?></h2>
                  <div class="endereco-wrapper">
                     <p class="endereco"><?php echo htmlspecialchars($emp['endereco'] . (!empty($emp['numero']) ? ', ' . $emp['numero'] : '') . ' - ' . $emp['bairro']); ?></p>
                     <p class="endereco"><?php
                                          $cep_info = '';
                                          if (isset($emp['cep']) && !empty($emp['cep'])) {
                                             $cep_info = htmlspecialchars($emp['cep']) . ' - ';
                                          }
                                          echo $cep_info . htmlspecialchars($emp['cidade']) . '/' . htmlspecialchars($emp['estado']);
                                          ?></p>
                  </div>
                  <?php if ($fa_metragem !== 'N/A'): ?>
                     <p class="metragem">Metragem: <?php echo $fa_metragem; ?>m²</p>
                  <?php endif; ?>
                  <p class="preco"><?php echo ($menor_valor === 'Sob Consulta' || $menor_valor === 'R$ 0,00') ? 'Sob Consulta' : 'A partir de ' . $menor_valor; ?></p>
               </div>
               <div class="card-thumbnail-content">
                  <img src="<?php echo $foto_path; ?>" alt="Foto Principal do Empreendimento <?php echo htmlspecialchars($emp['nome']); ?>">
               </div>
               <div class="card-image-content">
                  <img src="<?php echo $galeria_foto_1_path; ?>" alt="Foto Principal do Empreendimento <?php echo htmlspecialchars($emp['nome']); ?>">
                  <img src="<?php echo $galeria_foto_2_path; ?>" alt="Foto Principal do Empreendimento <?php echo htmlspecialchars($emp['nome']); ?>">
               </div>
            </a>

            <a href="<?php echo BASE_URL; ?>empreendimento.php?id=<?php echo htmlspecialchars($emp['id']); ?>" class="empreendimento-card mobile" style="background-color: var(--color-background-primar);">
               <div class="card-image-overlay">
                  <img src="<?php echo $foto_path; ?>" alt="Foto Principal do Empreendimento <?php echo htmlspecialchars($emp['nome']); ?>">
                  <span class="status-flag" style="background: var(--gradient-success);">Ativo</span>
               </div>
               <div class="card-bottom-content" style="padding: var(--spacing-md);">
                  <h2><?php echo htmlspecialchars($emp['nome']); ?></h2>
                  <p class="endereco"><?php echo htmlspecialchars($emp['endereco'] . (!empty($emp['numero']) ? ', ' . $emp['numero'] : '') . ' - ' . $emp['bairro']); ?></p>
                  <p class="endereco"><?php
                                       $cep_info = '';
                                       if (isset($emp['cep']) && !empty($emp['cep'])) {
                                          $cep_info = htmlspecialchars($emp['cep']) . ' - ';
                                       }
                                       echo $cep_info . htmlspecialchars($emp['cidade']) . '/' . htmlspecialchars($emp['estado']);
                                       ?></p>
                  <div class="caracteristicas-compactas">
                     <?php if ($fa_quartos !== 'N/A'): ?>
                        <span><i class="fas fa-bed"></i> <?php echo $fa_quartos; ?></span>
                     <?php endif; ?>
                     <?php if ($fa_banheiros !== 'N/A'): ?>
                        <span><i class="fas fa-shower"></i> <?php echo $fa_banheiros; ?></span>
                     <?php endif; ?>
                     <?php if ($fa_vagas !== 'N/A'): ?>
                        <span><i class="fas fa-car"></i> <?php echo $fa_vagas; ?></span>
                     <?php endif; ?>
                  </div>

                  <?php if ($fa_metragem !== 'N/A'): ?>
                     <p class="metragem">Metragem: <?php echo $fa_metragem; ?>m²</p>
                  <?php endif; ?>
                  <p class="preco"><?php echo ($menor_valor === 'Sob Consulta' || $menor_valor === 'R$ 0,00') ? 'Sob Consulta' : 'A partir de ' . $menor_valor; ?></p>
               </div>
            </a>
         <?php endforeach; ?>
      <?php endif; ?>
   </div>
</section>

<?php
// Inclui o rodapé público
require_once 'includes/footer_public.php';
?>