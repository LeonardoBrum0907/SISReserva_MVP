<?php
// empreendimento.php - Página de Detalhes do Empreendimento
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/helpers.php';

global $conn;
try {
   $conn = get_db_connection();
} catch (Exception $e) {
   error_log("Erro crítico na inicialização do DB em empreendimento.php: " . $e->getMessage());
   die("<h1>Erro</h1><p>Não foi possível carregar os detalhes do empreendimento. Por favor, tente novamente mais tarde.</p>");
}

$empreendimento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($empreendimento_id === 0) {
   header("Location: " . BASE_URL . "index.php");
   exit();
}

$empreendimento = null;
$midias = [];
$unidades_por_andar = [];
$unidades_raw = [];
$total_andares = 0;
$areas_comuns = [];
$posicoes_globais = [];

try {
   // 1. Buscar detalhes do empreendimento
   $sql_empreendimento = "
        SELECT
            e.id,
            e.nome,
            e.descricao,
            e.tipo_uso,
            e.tipo_empreendimento,
            e.endereco,
            e.numero,
            e.complemento,
            e.bairro,
            e.cidade,
            e.estado,
            e.cep,
            e.status,
            e.foto_localizacao,
            e.momento_envio_documentacao,
            e.documentos_obrigatorios,
            e.permissoes_visualizacao,
            e.permissao_reserva,
            e.prazo_expiracao_reserva,
            e.data_lancamento,
            e.previsao_entrega
        FROM
            empreendimentos e
        WHERE
            e.id = ?;
    ";
   $empreendimento = fetch_single($sql_empreendimento, [$empreendimento_id], "i");

   if (!$empreendimento) {
      header("Location: " . BASE_URL . "index.php?error=empreendimento_nao_encontrado");
      exit();
   }

   // Garante que chaves essenciais existam no array $empreendimento com valores padrão se não vierem do DB
   $empreendimento['diferenciais'] = $empreendimento['diferenciais'] ?? 'Diferenciais não informados.';
   $empreendimento['area_total_terreno'] = $empreendimento['area_total_terreno'] ?? 'Área não informada.';
   $empreendimento['construtor_arquiteto'] = $empreendimento['construtor_arquiteto'] ?? 'Não informado.';
   $empreendimento['tamanhos_unidades'] = $empreendimento['tamanhos_unidades'] ?? 'Não informado.';

   // 2. Buscar mídias do empreendimento
   $sql_midias = "
        SELECT
            me.caminho_arquivo,
            me.tipo,
            me.descricao
        FROM
            midias_empreendimentos me
        WHERE
            me.empreendimento_id = ?
        ORDER BY
            CASE
                WHEN me.tipo = 'foto_principal' THEN 1
                WHEN me.tipo = 'galeria_foto' THEN 2
                WHEN me.tipo = 'video' THEN 3
                WHEN me.tipo = 'documento_contrato' THEN 4
                WHEN me.tipo = 'documento_memorial' THEN 5
                ELSE 8
            END, me.data_upload ASC;
    ";
   $midias_raw = fetch_all($sql_midias, [$empreendimento_id], "i");

   $foto_principal = null;
   $galeria_fotos = [];
   $videos_youtube_ids = [];
   $documentos_link = [];

   foreach ($midias_raw as $midia) {
      if ($midia['tipo'] === 'foto_principal') {
         $foto_principal = $midia['caminho_arquivo'];
      } elseif ($midia['tipo'] === 'galeria_foto') {
         $galeria_fotos[] = $midia['caminho_arquivo'];
      } elseif ($midia['tipo'] === 'video') {
         $videos_youtube_ids[] = $midia['caminho_arquivo'];
      } elseif ($midia['tipo'] === 'documento_contrato') {
         $documentos_link['contrato'] = $midia['caminho_arquivo'];
      } elseif ($midia['tipo'] === 'documento_memorial') {
         $documentos_link['memorial_descritivo'] = $midia['caminho_arquivo'];
      }
   }

   // CORRIGIDO: URL de embed do Google Maps para o formato padrão do Google Maps Embed API
   $endereco_completo_map = urlencode($empreendimento['endereco'] . ', ' . $empreendimento['numero'] . ' - ' . $empreendimento['bairro'] . ', ' . $empreendimento['cidade'] . ' - ' . $empreendimento['estado'] . ', ' . $empreendimento['cep']);
   $mapa_google_embed = "http://googleusercontent.com/maps/embed/v1/place?q=" . $endereco_completo_map; // Pode adicionar &key=SUA_CHAVE_API se necessário

   // Preparar os dados do empreendimento para o JavaScript
   $empreendimento_public_data = [
      'id' => $empreendimento['id'],
      'nome' => $empreendimento['nome'],
      'descricao' => $empreendimento['descricao'],
      'foto_principal' => $foto_principal,
      'galeria_fotos' => array_map(fn($path) => BASE_URL . htmlspecialchars($path), $galeria_fotos),
      'videos_youtube_ids' => $videos_youtube_ids,
      'documentos_link' => [
         'contrato' => isset($documentos_link['contrato']) ? BASE_URL . htmlspecialchars($documentos_link['contrato']) : null,
         'memorial' => isset($documentos_link['memorial_descritivo']) ? BASE_URL . htmlspecialchars($documentos_link['memorial_descritivo']) : null,
      ],
      'foto_localizacao' => $empreendimento['foto_localizacao'] ? BASE_URL . htmlspecialchars($empreendimento['foto_localizacao']) : null,
      'mapa_google_embed' => $mapa_google_embed,
      'momento_envio_documentacao' => $empreendimento['momento_envio_documentacao'],
      // Garante que documentos_obrigatorios seja um array. json_decode pode retornar null em caso de JSON inválido.
      'documentos_obrigatorios' => is_array(json_decode($empreendimento['documentos_obrigatorios'] ?? '[]', true)) ? json_decode($empreendimento['documentos_obrigatorios'] ?? '[]', true) : [],
      'tipo_empreendimento' => $empreendimento['tipo_empreendimento'],
      'tipo_uso' => $empreendimento['tipo_uso'],
      'BASE_URL' => BASE_URL
   ];

   // 3. Buscar UNIDADES INDIVIDUAIS (para os cards de "Disponibilidade")
   $sql_unidades = "
        SELECT
            u.id AS unidade_id,
            u.numero,
            u.andar,
            u.posicao,
            u.valor,
            u.status,
            u.informacoes_pagamento,
            tu.id AS tipo_unidade_id,
            tu.tipo AS tipo_unidade_nome,
            tu.metragem,
            tu.quartos,
            tu.banheiros,
            tu.vagas,
            tu.foto_planta
        FROM
            unidades u
        JOIN
            tipos_unidades tu ON u.tipo_unidade_id = tu.id
        WHERE
            u.empreendimento_id = ?
        ORDER BY
            u.andar ASC, u.posicao ASC;
    ";
   $unidades_raw = fetch_all($sql_unidades, [$empreendimento_id], "i");

   $menor_valor_disponivel = null;
   $menor_metragem = null;
   $maior_metragem = null;

   $andares_existentes = [];
   $posicoes_unicas = [];

   if (!empty($unidades_raw)) {
      foreach ($unidades_raw as $unit) {
         if ($unit['status'] === 'disponivel') {
            if ($menor_valor_disponivel === null || $unit['valor'] < $menor_valor_disponivel) {
               $menor_valor_disponivel = $unit['valor'];
            }
         }

         $metragem_atual = (float)$unit['metragem'];
         if ($menor_metragem === null || $metragem_atual < $menor_metragem) {
            $menor_metragem = $metragem_atual;
         }
         if ($maior_metragem === null || $metragem_atual > $maior_metragem) {
            $maior_metragem = $metragem_atual;
         }

         $andar_key = (int)$unit['andar'];
         $posicao_key = (string)$unit['posicao'];

         if (!isset($unidades_por_andar[$andar_key])) {
            $unidades_por_andar[$andar_key] = [];
            $andares_existentes[] = $andar_key;
         }
         $unidades_por_andar[$andar_key][$posicao_key] = $unit;

         if (!in_array($posicao_key, $posicoes_unicas)) {
            $posicoes_unicas[] = $posicao_key;
         }
      }
      sort($posicoes_unicas);
      $posicoes_globais = $posicoes_unicas;
   } else {
      $menor_valor_disponivel = 0;
      $menor_metragem = 0;
      $maior_metragem = 0;
      $posicoes_globais = ['01'];
   }

   $range_metragens = '';
   if ($menor_metragem !== null && $maior_metragem !== null && $menor_metragem > 0) {
      if ($menor_metragem == $maior_metragem) {
         $range_metragens = number_format($menor_metragem, 0, ',', '.') . 'm²';
      } else {
         $range_metragens = 'A partir de ' . number_format($menor_metragem, 0, ',', '.') . 'm² a ' . number_format($maior_metragem, 0, ',', '.') . 'm²';
      }
   } else {
      $range_metragens = 'Não há unidades disponíveis';
   }

   if (!empty($unidades_por_andar)) {
      krsort($unidades_por_andar);
      $total_andares = count($unidades_por_andar);
   } else {
      $total_andares = 0;
   }

   $sql_areas_comuns = "
        SELECT
            ac.nome
        FROM
            empreendimentos_areas_comuns eac
        JOIN
            areas_comuns_catalogo ac ON eac.area_comum_id = ac.id
        WHERE
            eac.empreendimento_id = ?
        ORDER BY
            ac.nome ASC;
    ";
   $areas_comuns = fetch_all($sql_areas_comuns, [$empreendimento_id], "i");
} catch (Exception $e) {
   error_log("Erro ao carregar empreendimento: " . $e->getMessage());
   die("<h1>Erro</h1><p>Não foi possível carregar os detalhes do empreendimento. Por favor, tente novamente mais tarde.</p>");
}

$page_title = htmlspecialchars($empreendimento['nome']);

require_once 'includes/header_public.php';

?>
<script>
   <?php
   // Tenta codificar empreendimento_public_data para JSON
   $encoded_empreendimento_public_data = json_encode($empreendimento_public_data);
   if ($encoded_empreendimento_public_data === false) {
      error_log('ERRO PHP: json_encode para empreendimento_public_data falhou! Motivo: ' . json_last_error_msg());
      // Se a codificação falhar, injeta um objeto vazio para evitar erro no JS
      echo 'window.empreendimentoPublicData = {}; console.error("Erro ao codificar empreendimentoPublicData no PHP. Verifique logs do servidor.");';
   } else {
      echo 'window.empreendimentoPublicData = ' . $encoded_empreendimento_public_data . ';';
   }

   // Tenta codificar logged_user_info_public para JSON
   $encoded_logged_user_info_public = json_encode($logged_user_info_public);
   if ($encoded_logged_user_info_public === false) {
      error_log('ERRO PHP: json_encode para logged_user_info_public falhou! Motivo: ' . json_last_error_msg());
      // Se a codificação falhar, injeta um objeto vazio para evitar erro no JS
      echo 'window.logged_user_info_public = {}; console.error("Erro ao codificar logged_user_info_public no PHP. Verifique logs do servidor.");';
   } else {
      echo 'window.logged_user_info_public = ' . $encoded_logged_user_info_public . ';';
   }
   ?>
   // Logs de console para depuração: O QUE FOI INJETADO DIRETAMENTE DO PHP
   console.log('PHP Injected Data: empreendimentoPublicData=', window.empreendimentoPublicData);
   console.log('PHP Injected Data: logged_user_info_public=', window.logged_user_info_public);

   // CORRIGIDO: Declaração de BASE_URL_JS como propriedade de window para evitar conflitos de 'const'
   window.BASE_URL_JS = "<?php echo BASE_URL; ?>";
</script>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
<section class="empreendimento-details-header" style="display: none;">
   <div class="container-fluid">
      <?php
      $banner_image_path = !empty($foto_principal) ? BASE_URL . htmlspecialchars($foto_principal) : BASE_URL . 'assets/images/placeholder_banner.jpg';
      ?>
      <div class="banner-empreendimento" style="background-image: url('<?php echo $banner_image_path; ?>');">
         <div class="banner-overlay">
            <div class="header-info-overlay">
               <span class="status-flag" style="background: var(--gradient-success);">
                  <?php echo htmlspecialchars(ucfirst($empreendimento['status'])); ?>
               </span>
               <p class="type-info"><?php echo htmlspecialchars($empreendimento['tipo_uso'] . ' - ' . $empreendimento['tipo_empreendimento']); ?></p>
               <h1><?php echo htmlspecialchars($empreendimento['nome']); ?></h1>
            </div>
         </div>
      </div>
   </div>
   <div class="container header-summary">
      <div class="summary-details">
         <p class="price-start">A partir de <strong><?php echo format_currency_brl($menor_valor_disponivel); ?></strong></p>
         <p class="location-address">
            <?php echo htmlspecialchars($empreendimento['endereco'] . (!empty($empreendimento['numero']) ? ', ' . $empreendimento['numero'] : '')); ?>
            <br>
            <?php echo htmlspecialchars($empreendimento['bairro'] . ' - ' . $empreendimento['cidade'] . '/' . $empreendimento['estado']); ?>
         </p>
      </div>
   </div>
</section>

<section class="secao-titulo">
   <div class="secao-titulo-container">
      <div class="conteudo-esquerdo">
         <div class="localizacao">
            <?php echo htmlspecialchars($empreendimento['bairro'] . ' - ' . $empreendimento['cidade'] . '/' . $empreendimento['estado']); ?>
         </div>
         <div class="titulo-container">
            <h1 class="titulo-principal"><?php echo htmlspecialchars($empreendimento['nome']); ?></h1>
            <span class="flag-lancamento" style="background: var(--gradient-success);">
               <?php echo htmlspecialchars(ucfirst($empreendimento['status'])); ?>
            </span>
         </div>
      </div>

      <div class="conteudo-direito">
         <div class="preco-label">Unidades a partir de</div>
         <div class="preco-valor"><strong><?php echo format_currency_brl($menor_valor_disponivel); ?></strong></div>
      </div>
   </div>
</section>

<section class="carousel-section">
   <div class="carousel-container">
      <button class="carousel-nav prev" aria-label="Imagem anterior">
         < </button>
            <div class="carousel" tabindex="0" aria-roledescription="carousel">
               <?php if (!empty($galeria_fotos)): ?>
                  <?php foreach ($galeria_fotos as $foto_path): ?>
                     <img src="<?php echo BASE_URL . htmlspecialchars($foto_path); ?>" alt="Foto da galeria" class="gallery-item" data-full-src="<?php echo BASE_URL . htmlspecialchars($foto_path); ?>">
                  <?php endforeach; ?>
               <?php else: ?>
                  <p>Nenhuma foto de galeria disponível.</p>
               <?php endif; ?>
            </div>
            <button class="carousel-nav next" aria-label="Próxima imagem">></button>
   </div>
</section>

<div class="modal-galeria hidden" role="dialog" aria-modal="true" aria-labelledby="modal-title" tabindex="-1">
   <button class="modal-close-galeria" aria-label="Fechar modal">×</button>
   <button class="modal-nav-galeria modal-prev" aria-label="Imagem anterior no modal">
      << /button>
         <div class="modal-content-galeria">
            <img src="" alt="" />
            <p class="modal-caption-galeria"></p>
         </div>
         <button class="modal-nav-galeria modal-next" aria-label="Próxima imagem no modal">></button>
</div>

<section class="secao-midias">
   <div class="secao-midias-container">
      <div class="midias-botoes-wrapper">
         <?php if (!empty($galeria_fotos) || !empty($videos_youtube_ids) || !empty($documentos_link['contrato']) || !empty($documentos_link['memorial_descritivo']) || !empty($empreendimento['foto_localizacao'])): ?>
            <button class="btn-midia onclick-btn-midia" data-modal-type="fotos">
               <span class="material-icons">photo</span>
               <span class="btn-midia-texto">Fotos</span>
            </button>
            <button class="btn-midia onclick-btn-midia" data-modal-type="video">
               <span class="material-icons">video_library</span>
               <span class="btn-midia-texto">Vídeo</span>
            </button>
            <button class="btn-midia onclick-btn-midia" data-modal-type="localizacao">
               <span class="material-icons">place</span>
               <span class="btn-midia-texto">Localização</span>
            </button>
            <?php if (isset($documentos_link['contrato']) && !empty($documentos_link['contrato'])): ?>
               <button class="btn-midia onclick-btn-midia" data-modal-type="documento" data-document-type="contrato">
                  <span class="material-icons">book</span>
                  <span class="btn-midia-texto">Contrato</span>
               </button>
            <?php endif; ?>
            <?php if (isset($documentos_link['memorial_descritivo']) && !empty($documentos_link['memorial_descritivo'])): ?>
               <button class="btn-midia onclick-btn-midia" data-modal-type="documento" data-document-type="memorial">
                  <span class="material-icons">description</span>
                  <span class="btn-midia-texto">Memorial Descritivo</span>
               </button>
            <?php endif; ?>
         <?php endif; ?>
      </div>
   </div>
</section>

<div class="modal-midia hidden" id="mediaModal" role="dialog" aria-modal="true" aria-labelledby="mediaModalTitle" tabindex="-1">
   <div class="modal-midia-content">
      <button class="modal-midia-close" aria-label="Fechar modal">×</button>
      <h2 class="modal-midia-title" id="mediaModalTitle">Título do Modal</h2>
      <div class="modal-midia-body">
         <p>Conteúdo do modal aparecerá aqui...</p>
      </div>
   </div>
</div>

<section class="secao-suite-place container">
   <div class="suite-place-container">
      <div class="suite-place-texto">
         <h2>Precisa de ajuda para escolher a melhor unidade?</h2>
         <p>Fale agora com um de nossos parceiros.</p>
      </div>
      <div class="suite-place-botao-wrapper">
         <a href="https://api.whatsapp.com/send/?phone=5563981221028&text&type=phone_number&app_absent=0" class="btn-suite-place">Fale com a gente</a>
      </div>
   </div>
</section>

<section class="description-section container">
   <h2>Sobre o Empreendimento</h2>
   <p><?php echo nl2br(htmlspecialchars($empreendimento['descricao'])); ?></p>
</section>

<section class="availability-cards-section">
   <h2>Tipos & Unidades</h2>

   <div class="andares-wrapper">
      <?php if (!empty($unidades_por_andar)): ?>
         <?php foreach ($unidades_por_andar as $andar => $unidades_do_andar): ?>
            <div class="andar-row">
               <div class="andar-info-card">
                  <span class="andar-number-text"><?php echo htmlspecialchars($andar); ?>º</span>
                  <span class="andar-number-text">Andar</span>
               </div>

               <div class="unidades-por-final-grid">
                  <?php foreach ($posicoes_globais as $posicao): ?>
                     <?php
                     $unidade_slot = $unidades_do_andar[$posicao] ?? null;

                     $unit_status_class = 'status-empty';
                     $is_disabled = 'disabled';
                     $unit_number = '-';
                     $unit_type_name = '';
                     $unit_metragem = '';
                     $unit_quartos = '';
                     $unit_banheiros = '';
                     $unit_vagas = '';
                     $unit_value = '';
                     $unit_foto_planta_full_path = '';
                     $unit_data_attr = '';

                     if ($unidade_slot) {
                        switch ($unidade_slot['status']) {
                           case 'disponivel':
                              $unit_status_class = 'status-available';
                              $is_disabled = '';
                              break;
                           case 'reservada':
                              $unit_status_class = 'status-reserved';
                              $is_disabled = 'disabled';
                              break;
                           case 'vendida':
                              $unit_status_class = 'status-sold';
                              $is_disabled = 'disabled';
                              break;
                           case 'pausada':
                              $unit_status_class = 'status-paused';
                              $is_disabled = 'disabled';
                              break;
                           case 'bloqueada':
                              $unit_status_class = 'status-blocked';
                              $is_disabled = 'disabled';
                              break;
                           default:
                              $unit_status_class = 'status-unavailable';
                              $is_disabled = 'disabled';
                              break;
                        }
                        $unit_number = htmlspecialchars($unidade_slot['numero']);
                        $unit_type_name = htmlspecialchars($unidade_slot['tipo_unidade_nome']);
                        $unit_metragem = htmlspecialchars(number_format($unidade_slot['metragem'], 0, ',', '.'));
                        $unit_quartos = htmlspecialchars($unidade_slot['quartos']);
                        $unit_banheiros = htmlspecialchars($unidade_slot['banheiros']);
                        $unit_vagas = htmlspecialchars($unidade_slot['vagas']);
                        $unit_value = htmlspecialchars($unidade_slot['valor']);
                        $unit_foto_planta_full_path = BASE_URL . htmlspecialchars($unidade_slot['foto_planta']);

                        $informacoes_pagamento_decoded = json_decode($unidade_slot['informacoes_pagamento'] ?? '[]', true);
                        $unidade_slot['informacoes_pagamento'] = $informacoes_pagamento_decoded;

                        $unit_data_json = [
                           'id' => $unidade_slot['unidade_id'],
                           'numero' => $unidade_slot['numero'],
                           'andar' => $unidade_slot['andar'],
                           'posicao' => $unidade_slot['posicao'],
                           'valor' => $unidade_slot['valor'],
                           'status' => $unidade_slot['status'],
                           'informacoes_pagamento' => $unidade_slot['informacoes_pagamento'],
                           'tipo_unidade_nome' => $unidade_slot['tipo_unidade_nome'],
                           'metragem' => $unidade_slot['metragem'],
                           'quartos' => $unidade_slot['quartos'],
                           'banheiros' => $unidade_slot['banheiros'],
                           'vagas' => $unidade_slot['vagas'],
                           'foto_planta' => $unit_foto_planta_full_path
                        ];

                        $unit_data_attr = "data-unidade-id=\"{$unidade_slot['unidade_id']}\" " .
                           "data-unidade='" . htmlspecialchars(json_encode($unit_data_json), ENT_QUOTES, 'UTF-8') . "' " .
                           "data-empreendimento='" . htmlspecialchars(json_encode($empreendimento_public_data), ENT_QUOTES, 'UTF-8') . "' " .
                           "data-is-logged-in='" . ($is_logged_in ? 'true' : 'false') . "'";
                     }
                     ?>
                     <button class="unit-card-item <?php echo $unit_status_class; ?> <?php echo ($unit_status_class === 'status-available') ? 'btn-view-details' : ''; ?>" <?php echo $is_disabled; ?> <?php echo $unit_data_attr; ?>>
                        <div class="unit-card-content">
                           <?php if ($unidade_slot): ?>
                              <span class="unit-card-type"><?php echo $unit_type_name; ?></span>
                              <!-- <span class="unit-card-status-text"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $unidade_slot['status']))); ?></span> -->

                              <div>
                                 <span class="unit-card-number">N°<?php echo $unit_number; ?> - </span>
                                 <span class="unit-card-number"><?php echo $unit_metragem; ?>m²</span>
                              </div>

                              <div class="unit-card-valor">
                                 <span class="unit-card-valor-value"><?php echo format_currency_brl($unit_value); ?></span>
                              </div>

                              <?php if ($unit_quartos > 0 || $unit_banheiros > 0 || $unit_vagas > 0): ?>
                                 <div class="unit-card-details">
                                    <?php if ($unit_quartos > 0): ?>
                                       <div class="unit-card-detail">
                                          <span class="material-icons">bed</span>
                                          <span class="unit-card-detail-text"><?php echo $unit_quartos; ?></span>
                                       </div>
                                    <?php endif; ?>
                                    <?php if ($unit_banheiros > 0): ?>
                                       <div class="unit-card-detail">
                                          <span class="material-icons">bathtub</span>
                                          <span class="unit-card-detail-text"><?php echo $unit_banheiros; ?></span>
                                       </div>
                                    <?php endif; ?>
                                    <?php if ($unit_vagas > 0): ?>
                                       <div class="unit-card-detail">
                                          <span class="material-icons">directions_car</span>
                                          <span class="unit-card-detail-text"><?php echo $unit_vagas; ?></span>
                                       </div>
                                    <?php endif; ?>
                                 </div>
                              <?php endif; ?>
                           <?php else: ?>
                              <span class="unit-card-number">Final <?php echo htmlspecialchars($posicao); ?></span>
                              <span class="unit-card-status-text status-empty-text">Vazio</span>
                           <?php endif; ?>
                        </div>
                     </button>
                  <?php endforeach; ?>
               </div>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <p style="text-align: center; padding: 20px;">Nenhuma unidade encontrada para este empreendimento.</p>
      <?php endif; ?>
   </div>

   <div class="status-legend">
      <div class="legend-item">
         <div class="legend-color-box status-available" style="background: var(--color-success); height: 15px; width: 15px; border-radius: 5px;"></div>
         <span class="legend-text">Disponível</span>
      </div>
      <div class="legend-item">
         <div class="legend-color-box" style="background: var(--color-warning); height: 15px; width: 15px; border-radius: 5px;"></div>
         <span class="legend-text">Reservada</span>
      </div>
      <div class="legend-item">
         <div class="legend-color-box" style="background: var(--color-background-secondary); height: 15px; width: 15px; border: 1px solid var(--color-secondary); border-radius: 5px;"></div>
         <span class="legend-text">Vendida</span>
      </div>
      <div class="legend-item">
         <div class="legend-color-box" style="background: var(--color-background-secondary); height: 15px; width: 15px; border: 1px dashed var(--color-secondary); opacity: 0.5; border-radius: 5px;"></div>
         <span class="legend-text">Pausada/bloqueada</span>
      </div>
   </div>
</section>

<section class="secao-explore container">
   <div class="explore-container">
      <h2 class="explore-titulo">Explore um pouco mais o <?php echo htmlspecialchars($empreendimento['nome']); ?></h2>
      <div class="explore-cards">
         <div class="explore-card onclick-btn-midia" data-modal-type="video">
            <div class="card-conteudo">
               <img src="<?php echo !empty($empreendimento_public_data['explore_video_thumb']) ? htmlspecialchars($empreendimento_public_data['explore_video_thumb']) : BASE_URL . 'assets/images/explore-videos-placeholder.jpg'; ?>" alt="Ícone Vídeos" class="card-imagem">
               <div class="card-texto">Vídeos</div>
            </div>
         </div>

         <div class="explore-card onclick-btn-midia" data-modal-type="fotos">
            <div class="card-conteudo">
               <img src="<?php echo !empty($empreendimento_public_data['explore_gallery_thumb']) ? htmlspecialchars($empreendimento_public_data['explore_gallery_thumb']) : BASE_URL . 'assets/images/explore-galeria-placeholder.jpg'; ?>" alt="Ícone Galeria" class="card-imagem">
               <div class="card-texto">Galeria</div>
            </div>
         </div>
      </div>
   </div>
</section>

<section class="secao-ficha-tecnica container">
   <div class="ficha-container">
      <div class="ficha-card">
         <h2 class="ficha-titulo">Ficha técnica, <br>& alguns detalhes</h2>
         <p class="ficha-descricao">
            <?php echo nl2br(htmlspecialchars($empreendimento['descricao'])); ?>
         </p>

         <div class="ficha-bloco-informacoes">
            <div class="ficha-coluna">
               <?php if ($empreendimento['previsao_entrega']): ?>
                  <div class="ficha-item">
                     <div class="ficha-item-titulo">Previsão de Entrega</div>
                     <div class="ficha-item-valor"><?php echo date('d/m/Y', strtotime($empreendimento['previsao_entrega'])); ?></div>
                  </div>
               <?php endif; ?>

               <div class="ficha-item">
                  <div class="ficha-item-titulo">Nº de Pavimentos</div>
                  <div class="ficha-item-valor"><?php echo htmlspecialchars($total_andares . ' andares'); ?></div>
               </div>

               <div class="ficha-item">
                  <div class="ficha-item-titulo">Áreas Comuns</div>
                  <div class="ficha-item-valor"><?php echo nl2br(htmlspecialchars(implode(', ', array_column($areas_comuns, 'nome')))); ?>.</div>
               </div>
            </div>

            <div class="ficha-coluna">
               <div class="ficha-item">
                  <div class="ficha-item-titulo">Unidades</div>
                  <div class="ficha-item-valor"><?php echo htmlspecialchars($range_metragens); ?></div>
               </div>

               <div class="ficha-item">
                  <div class="ficha-item-titulo">Uso Misto</div>
                  <div class="ficha-item-valor"><?php echo htmlspecialchars($empreendimento['tipo_uso']); ?> e <?php echo htmlspecialchars($empreendimento['tipo_empreendimento']); ?></div>
               </div>

               <div class="ficha-item">
                  <div class="ficha-item-titulo">Padrão</div>
                  <div class="ficha-item-valor"><?php echo htmlspecialchars('Alto padrão e design refinado'); ?></div>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>

<section class="secao-chamada container">
   <div class="chamada-container">
      <div class="chamada-card">
         <div class="chamada-conteudo">
            <h2 class="chamada-titulo">Decida como conhecer seu futuro lar ou investimento.</h2>
            <p class="chamada-intro">Decida como conhecer seu futuro lar ou investimento:</p>

            <div class="chamada-lista">
               <p><span class="chamada-destaque">Online:</span> Em nossa plataforma digital.</p>
               <p><span class="chamada-destaque">Por Vídeo:</span> Converse com nossos especialistas.</p>
               <p><span class="chamada-destaque">Presencial:</span> Visite nosso estande.</p>
               <p><span class="chamada-destaque">Onde Estiver:</span> Nós vamos até você.</p>
            </div>

            <p class="chamada-final">Alta arquitetura e exclusividade, com total conveniência para você.</p>
         </div>

         <div class="chamada-imagem">
            <img src="<?php echo BASE_URL; ?>assets/images/chamada-placeholder.jpg" alt="Imagem ilustrativa da chamada" class="imagem-chamada">
         </div>
      </div>
   </div>
</section>

<div id="modalReserva" class="modal">
   <div class="modal-content-reserva">
      <div class="modal-header">
         <h2 id="modalReservaTitle">Reservar Unidade <span id="modalUnidadeNumero"></span></h2>
         <button type="button" class="close-button close-modal" aria-label="Fechar">×</button>
      </div>

      <div class="modal-body">
         <div id="reservaStep1" class="reserva-step">
            <div class="info-section-split">
               <div class="info-section info-text-column">
                  <h3>Detalhes da Unidade</h3>
                  <div class="info-grid">
                     <p><strong>Empreendimento:</strong> <span id="modalEmpreendimentoNome"></span></p>
                     <p><strong>Tipo de Unidade:</strong> <span id="modalTipoUnidade"></span> (<span id="modalMetragem"></span>m²)</p>
                     <p><strong>Andar:</strong> <span id="modalAndar"></span>º - <strong>Posição:</strong> <span id="modalPosicao"></span></p>
                     <p><strong>Valor:</strong> <span id="modalValor"></span></p>
                  </div>

                  <h3>Informações de Pagamento</h3>
                  <div id="modalInformacoesPagamento"></div>
                  <p class="total-unidade"><strong>Valor Total da Unidade:</strong> <span id="modalValorTotalUnidade"></span></p>
               </div>
               <div class="info-section info-image-column">
                  <img id="modalFotoPlanta" src="" alt="Planta da Unidade" class="planta-preview">
               </div>
            </div>
            <div class="form-actions">
               <button type="button" class="btn btn-primary" id="btnAdvanceToStep2">
                  <?php echo $is_logged_in ? 'Efetuar Reserva' : 'Solicitar Reserva'; ?>
               </button>
               <button type="button" class="btn btn-secondary close-modal">Cancelar</button>
            </div>
         </div>

         <div id="reservaStep2" class="reserva-step hidden">
            <form id="formReserva" class="reservation-form" enctype="multipart/form-data">
               <h3>Dados do Comprador</h3>

               <input type="hidden" name="unidade_id" id="formUnidadeId">
               <input type="hidden" name="empreendimento_id" id="formEmpreendimentoId">
               <input type="hidden" name="valor_reserva" id="formValorReserva">
               <input type="hidden" name="momento_envio_documentacao" id="formMomentoEnvioDocumentacao">
               <input type="hidden" name="documentos_obrigatorios_json" id="formDocumentosObrigatoriosJson">

               <div class="form-section">
                  <div class="form-row">
                     <div class="form-group">
                        <label for="nome_cliente">Nome Completo</label>
                        <input type="text" id="nome_cliente" name="nome_cliente" required>
                     </div>
                     <div class="form-group">
                        <label for="cpf_cliente">CPF</label>
                        <input type="text" id="cpf_cliente" name="cpf_cliente" placeholder="000.000.000-00" data-mask-type="cpf" required>
                     </div>
                  </div>

                  <div class="form-row">
                     <div class="form-group">
                        <label for="email_cliente">Email</label>
                        <input type="email" id="email_cliente" name="email_cliente" required>
                     </div>
                     <div class="form-group">
                        <label for="whatsapp_cliente">WhatsApp</label>
                        <input type="text" id="whatsapp_cliente" name="whatsapp_cliente" placeholder="(XX) XXXXX-XXXX" data-mask-type="whatsapp" required>
                     </div>
                  </div>
               </div>

               <?php if ($is_logged_in && is_array($logged_user_info_public) && ($logged_user_info_public['type'] === 'corretor_autonomo' || $logged_user_info_public['type'] === 'corretor_imobiliaria')): ?>
                  <div class="form-section" id="enderecoSection">
                     <h3>Endereço</h3>
                     <div class="form-row">
                        <div class="form-group">
                           <label for="cep_cliente">CEP</label>
                           <input type="text" id="cep_cliente" name="cep_cliente" placeholder="XXXXX-XXX" data-mask-type="cep" class="cep-input" required>
                        </div>
                        <div class="form-group flex-2">
                           <label for="endereco_cliente">Endereço</label>
                           <input type="text" id="endereco_cliente" name="endereco_cliente" readonly required>
                        </div>
                     </div>

                     <div class="form-row">
                        <div class="form-group">
                           <label for="numero_cliente">Número</label>
                           <input type="text" id="numero_cliente" name="numero_cliente" required>
                        </div>
                        <div class="form-group">
                           <label for="complemento_cliente">Complemento (Opcional)</label>
                           <input type="text" id="complemento_cliente" name="complemento_cliente">
                        </div>
                     </div>

                     <div class="form-row">
                        <div class="form-group">
                           <label for="bairro_cliente">Bairro</label>
                           <input type="text" id="bairro_cliente" name="bairro_cliente" readonly required>
                        </div>
                        <div class="form-group">
                           <label for="cidade_cliente">Cidade</label>
                           <input type="text" id="cidade_cliente" name="cidade_cliente" readonly required>
                        </div>
                        <div class="form-group">
                           <label for="estado_cliente">Estado (UF)</label>
                           <input type="text" id="estado_cliente" name="estado_cliente" maxlength="2" readonly required>
                        </div>
                     </div>
                  </div>

                  <div id="documentosUploadSection" class="form-section">
                     <h4>Documentos Obrigatórios para Reserva</h4>
                     <p>Por favor, faça o upload dos documentos listados abaixo:</p>
                     <div id="documentosObrigatoriosList"></div>
                  </div>
               <?php else: ?>
                  <div id="enderecoSection" style="display: none;"></div>
                  <div id="documentosUploadSection" style="display: none;">
                     <div id="documentosObrigatoriosList"></div>
                  </div>
               <?php endif; ?>

               <div class="form-section">
                  <div class="form-group">
                     <label for="observacoes_reserva">Observações (Opcional)</label>
                     <textarea id="observacoes_reserva" name="observacoes_reserva" rows="3"></textarea>
                  </div>
               </div>

               <div class="form-actions">
                  <button type="button" class="btn btn-secondary" id="btnBackToStep1">Voltar</button>
                  <button type="submit" class="btn btn-primary" id="btnConfirmReserva">Confirmar Reserva</button>
               </div>
            </form>
         </div>

         <div id="reservaStep3" class="reserva-step hidden">
            <div class="success-message-container">
               <i class="fas fa-check-circle success-icon"></i>
               <h3 id="successTitle">Parabéns! Sua Reserva Foi Solicitada!</h3>
               <p id="successMessage">Obrigado pelo contato, em poucos minutos um de nossos corretores entrará em contato.</p>
               <p class="small-text-info" id="successDetails"></p>
               <button type="button" class="btn btn-primary" id="btnCloseReservationModal">Fechar</button>
            </div>
         </div>
      </div>
   </div>
</div>

<div id="modalPlanta" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="modalPlantaTitle" tabindex="-1">
   <div class="modal-content">
      <button class="modal-close-planta close-modal" aria-label="Fechar">×</button>
      <h2 id="modalPlantaTitle" class="modal-title" style="display: none;">Planta Ampliada</h2>
      <img id="imagemModalPlantaAmpliada" src="" alt="Planta Ampliada" style="max-width: 100%; height: auto; display: block; margin: auto;">
   </div>
</div>
<?php
require_once 'includes/footer_public.php';
?>