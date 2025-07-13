// js/main.js
// Lógicas globais para o site público (menu sanduíche, modais, galerias de fotos)

document.addEventListener('DOMContentLoaded', () => {
   console.log('main.js DOMContentLoaded iniciado.'); // Log de início do main.js

   // Funções auxiliares gerais para o main.js
   function htmlspecialchars(str) {
       if (typeof str !== 'string' && typeof str !== 'number') return '';
       str = String(str);
       var map = {
           '&': '&',
           '<': '<',
           '>': '>',
           '"': '"',
           "'": '\''
       };
       return str.replace(/[&<>"']/g, function(m) { return map[m]; });
   }

   function formatCurrency(value) {
       return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
   }

   function formatNumber(value, decimals = 0) {
       return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value);
   }

   // Funções padronizadas para abrir/fechar modais usando style.display e classe hidden
   function openModal(modalElement) {
       if (modalElement) {
           modalElement.classList.remove('hidden'); // Garante que a classe hidden seja removida
           modalElement.style.display = 'block';
           document.body.classList.add('modal-open');
           console.log(`Modal ${modalElement.id} aberto.`); // Log de abertura do modal
       }
   }

   function closeModal(modalElement) {
       if (modalElement) {
           modalElement.style.display = 'none';
           modalElement.classList.add('hidden'); // Garante que a classe hidden seja adicionada
           document.body.classList.remove('modal-open');
           console.log(`Modal ${modalElement.id} fechado.`); // Log de fechamento do modal
       }
   }

   // Reutilizando a função showConfirmationModal
   if (typeof window.showConfirmationModal !== 'function') {
       window.showConfirmationModal = function (title, message, confirmText, confirmClass, callback) {
           let confirmationModal = document.getElementById('genericConfirmationModal');
           if (!confirmationModal) {
               confirmationModal = document.createElement('div');
               confirmationModal.id = 'genericConfirmationModal';
               confirmationModal.classList.add('modal-overlay');
               confirmationModal.innerHTML = `
                  <div class="modal-content">
                      <div class="modal-header">
                          <h3 id="genericConfirmationTitle"></h3>
                      </div>
                      <div class="modal-body">
                          <p id="genericConfirmationMessage"></p>
                      </div>
                      <div class="modal-footer">
                          <button type="button" id="genericConfirmButton"></button>
                      </div>
                  </div>
              `;
               document.body.appendChild(confirmationModal);
               confirmationModal.addEventListener('click', function (e) {
                   if (e.target === this || e.target.classList.contains('close-button')) {
                       closeModal(confirmationModal);
                   }
               });
           }

           const titleElement = document.getElementById('genericConfirmationTitle');
           const messageElement = document.getElementById('genericConfirmationMessage');
           const confirmButton = document.getElementById('genericConfirmButton');

           titleElement.textContent = title;
           messageElement.innerHTML = message;
           confirmButton.textContent = confirmText;
           confirmButton.className = `btn ${confirmClass}`;

           const oldConfirmButton = confirmButton.cloneNode(true);
           confirmButton.parentNode.replaceChild(oldConfirmButton, confirmButton);
           const newConfirmButton = document.getElementById('genericConfirmButton');

           newConfirmButton.addEventListener('click', () => {
               callback(true);
               closeModal(confirmationModal);
           });

           openModal(confirmationModal);
       };
   }

   // Adiciona um listener genérico para botões com a classe 'close-modal'
   document.querySelectorAll('.close-modal').forEach(button => {
       button.addEventListener('click', () => {
           const modal = button.closest('.modal, .modal-overlay');
           if (modal) {
               closeModal(modal);
           }
       });
   });


   // ===============================================
   // 1. LÓGICA DO MENU SANDUÍCHE PÚBLICO
   // ===============================================
   const mainSidebar = document.getElementById('mainSidebar');
   const mainSidebarOverlay = document.getElementById('mainSidebarOverlay');
   const publicMenuToggle = document.querySelector('.public-menu-toggle');
   const closeDashboardMenu = document.querySelector('.close-dashboard-menu');

   if (mainSidebar && mainSidebarOverlay && publicMenuToggle && closeDashboardMenu) {
       publicMenuToggle.addEventListener('click', function () {
           mainSidebar.classList.add('active');
           mainSidebarOverlay.classList.add('active');
           console.log('Menu sanduíche aberto.');
       });

       closeDashboardMenu.addEventListener('click', function () {
           mainSidebar.classList.remove('active');
           mainSidebarOverlay.classList.remove('active');
           console.log('Menu sanduíche fechado (botão X).');
       });

       mainSidebarOverlay.addEventListener('click', function () {
           mainSidebar.classList.remove('active');
           mainSidebarOverlay.classList.remove('active');
           console.log('Menu sanduíche fechado (overlay).');
       });
   } else {
       console.warn('Elementos do menu sanduíche público não foram encontrados no DOM. Verifique o header_public.php e os IDs/classes.');
   }


   // ===============================================
   // 2. LÓGICA DA PÁGINA empreendimento.php (Modais de Galeria, Vídeo, Localização, Detalhes de Unidade)
   // ===============================================

   const modalReserva = document.getElementById('modalReserva');
   const mediaModalGlobal = document.getElementById('mediaModal');
   const fullScreenGalleryModal = document.querySelector('.modal-galeria');

   const carousel = document.querySelector('.carousel');
   const prevBtnCarousel = document.querySelector('.carousel-nav.prev');
   const nextBtnCarousel = document.querySelector('.carousel-nav.next');

   const mediaModalContentBody = mediaModalGlobal ? mediaModalGlobal.querySelector('.modal-midia-body') : null;
   const mediaModalTitle = mediaModalGlobal ? mediaModalGlobal.querySelector('.modal-midia-title') : null;
   const mediaModalCloseBtn = mediaModalGlobal ? mediaModalGlobal.querySelector('.modal-midia-close') : null;

   const fullScreenGalleryImg = fullScreenGalleryModal ? fullScreenGalleryModal.querySelector('.modal-content-galeria img') : null;
   const fullScreenGalleryCloseBtn = fullScreenGalleryModal ? fullScreenGalleryModal.querySelector('.modal-close-galeria') : null;
   const fullScreenGalleryPrevBtn = fullScreenGalleryModal ? fullScreenGalleryModal.querySelector('.modal-nav-galeria.modal-prev') : null;
   const fullScreenGalleryNextBtn = fullScreenGalleryModal ? fullScreenGalleryModal.querySelector('.modal-nav-galeria.modal-next') : null;
   let currentFullScreenGalleryIndex = 0;

   const formReserva = document.getElementById('formReserva');
   const btnConfirmReserva = document.getElementById('btnConfirmReserva');
   const modalUnidadeNumero = document.getElementById('modalUnidadeNumero');
   const modalEmpreendimentoNome = document.getElementById('modalEmpreendimentoNome');
   const modalTipoUnidade = document.getElementById('modalTipoUnidade');
   const modalMetragem = document.getElementById('modalMetragem');
   const modalAndar = document.getElementById('modalAndar');
   const modalPosicao = document.getElementById('modalPosicao');
   const modalValor = document.getElementById('modalValor');
   const modalFotoPlanta = document.getElementById('modalFotoPlanta');
   const modalInformacoesPagamento = document.getElementById('modalInformacoesPagamento');
   const documentosUploadSection = document.getElementById('documentosUploadSection');
   const documentosObrigatoriosList = document.getElementById('documentosObrigatoriosList');

   const modalValorTotalUnidade = document.getElementById('modalValorTotalUnidade');

   const reservaStep1 = document.getElementById('reservaStep1');
   const reservaStep2 = document.getElementById('reservaStep2');
   const reservaStep3 = document.getElementById('reservaStep3');
   const btnAdvanceToStep2 = document.getElementById('btnAdvanceToStep2');
   const btnBackToStep1 = document.getElementById('btnBackToStep1');
   const btnCloseReservationModal = document.getElementById('btnCloseReservationModal');
   const successTitle = document.getElementById('successTitle');
   const successMessage = document.getElementById('successMessage');
   const successDetails = document.getElementById('successDetails');
   const enderecoSection = document.getElementById('enderecoSection');

   // LOG DE DADOS INJETADOS DO PHP (para depuração)
   console.log('main.js loaded. empreendimentoPublicData:', window.empreendimentoPublicData);
   console.log('main.js loaded. logged_user_info_public:', window.logged_user_info_public);

   if (document.querySelector('.unit-card-item') || carousel || modalReserva) {
       // Lógica do Carrossel (Seção Galeria Principal)
       const carouselImages = carousel ? Array.from(carousel.querySelectorAll('.gallery-item')) : [];
       const totalCarouselItems = carouselImages.length;

       const scrollAmount = () => {
           if (carouselImages.length === 0) return 0;
           const style = getComputedStyle(carouselImages[0]);
           const marginRight = parseInt(style.marginRight || '0');
           return carouselImages[0].offsetWidth + marginRight;
       };

       if (prevBtnCarousel && nextBtnCarousel && carousel && totalCarouselItems > 0) {
           prevBtnCarousel.addEventListener('click', () => {
               carousel.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
               console.log('Carousel: scroll anterior.');
           });
           nextBtnCarousel.addEventListener('click', () => {
               carousel.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
               console.log('Carousel: scroll próximo.');
           });
       }

       // Abrir modal de galeria em tela cheia ao clicar na imagem do carrossel
       carouselImages.forEach((img, index) => {
           img.addEventListener('click', () => {
               console.log('Clicado na imagem do carrossel. Imagem clicada src:', img.src);
               // Verifica se empreendimentoPublicData.galeria_fotos está definido
               if (window.empreendimentoPublicData && window.empreendimentoPublicData.galeria_fotos) {
                   if (fullScreenGalleryModal) {
                       showFullScreenGalleryModal(window.empreendimentoPublicData.galeria_fotos, index);
                   } else {
                       console.warn('Modal de galeria em tela cheia (.modal-galeria) não encontrado no DOM.');
                       alert('Funcionalidade de galeria em tela cheia não disponível.');
                   }
               } else {
                   console.error("window.empreendimentoPublicData.galeria_fotos está undefined. Verifique a injeção de dados no PHP.");
                   alert("Erro ao carregar fotos da galeria. Tente novamente mais tarde.");
               }
           });
       });

       // Função para Abrir o Modal de Galeria em Tela Cheia
       function showFullScreenGalleryModal(imagesArray, startIndex = 0) {
           console.log('showFullScreenGalleryModal chamado com imagesArray:', imagesArray, 'startIndex:', startIndex);
           if (!fullScreenGalleryModal || !fullScreenGalleryImg || !imagesArray || imagesArray.length === 0) {
               alert("Nenhuma imagem para exibir na galeria.");
               return;
           }
           currentFullScreenGalleryIndex = startIndex;
           fullScreenGalleryImg.src = imagesArray[currentFullScreenGalleryIndex];
           openModal(fullScreenGalleryModal);
       }

       // Navegação do modal de galeria em tela cheia
       if (fullScreenGalleryPrevBtn && fullScreenGalleryNextBtn && fullScreenGalleryImg) {
           fullScreenGalleryPrevBtn.addEventListener('click', () => {
               if (!window.empreendimentoPublicData || !window.empreendimentoPublicData.galeria_fotos) {
                   console.warn("Navegação da galeria: empreendimentoPublicData.galeria_fotos está undefined.");
                   return;
               }
               currentFullScreenGalleryIndex = (currentFullScreenGalleryIndex - 1 + window.empreendimentoPublicData.galeria_fotos.length) % window.empreendimentoPublicData.galeria_fotos.length;
               fullScreenGalleryImg.src = window.empreendimentoPublicData.galeria_fotos[currentFullScreenGalleryIndex];
               console.log('Galeria full screen: imagem anterior.');
           });
           fullScreenGalleryNextBtn.addEventListener('click', () => {
               if (!window.empreendimentoPublicData || !window.empreendimentoPublicData.galeria_fotos) {
                   console.warn("Navegação da galeria: empreendimentoPublicData.galeria_fotos está undefined.");
                   return;
               }
               currentFullScreenGalleryIndex = (currentFullScreenGalleryIndex + 1) % window.empreendimentoPublicData.galeria_fotos.length;
               fullScreenGalleryImg.src = window.empreendimentoPublicData.galeria_fotos[currentFullScreenGalleryIndex];
               console.log('Galeria full screen: próxima imagem.');
           });
       }

       // Fechar modal de galeria em tela cheia
       if (fullScreenGalleryCloseBtn) {
           fullScreenGalleryCloseBtn.addEventListener('click', () => {
               closeModal(fullScreenGalleryModal);
               console.log('Galeria full screen: fechado.');
           });
       }
       if (fullScreenGalleryModal) {
           fullScreenGalleryModal.addEventListener('click', e => {
               if (e.target === fullScreenGalleryModal) {
                   closeModal(fullScreenGalleryModal);
                   console.log('Galeria full screen: fechado (overlay).');
               }
           });
       }


       // FUNCIONALIDADE DOS MODAIS DE MÍDIAS (PÍLULAS DE AÇÃO)
       const mediaButtons = document.querySelectorAll('.onclick-btn-midia');
       if (mediaButtons.length > 0 && mediaModalGlobal && mediaModalContentBody && mediaModalTitle && mediaModalCloseBtn) {
           mediaButtons.forEach(button => {
               button.addEventListener('click', () => {
                   const modalType = button.dataset.modalType;
                   const documentType = button.dataset.documentType;
                   let titleText = '';

                   console.log('Botão de mídia clicado. Tipo:', modalType, 'Doc Tipo:', documentType);

                   mediaModalContentBody.innerHTML = ''; // Limpa conteúdo anterior

                   // Verifica se window.empreendimentoPublicData está definido antes de acessar suas propriedades
                   if (!window.empreendimentoPublicData) {
                       console.error("window.empreendimentoPublicData está undefined. Não é possível carregar conteúdo da mídia.");
                       mediaModalContentBody.textContent = 'Erro ao carregar conteúdo. Tente novamente mais tarde.';
                       mediaModalTitle.textContent = 'Erro';
                       openModal(mediaModalGlobal);
                       return;
                   }

                   switch (modalType) {
                       case 'fotos':
                           titleText = 'Galeria de Fotos';
                           if (window.empreendimentoPublicData.galeria_fotos && window.empreendimentoPublicData.galeria_fotos.length > 0) {
                               const galleryGrid = document.createElement('div');
                               galleryGrid.style.display = 'flex';
                               galleryGrid.style.flexWrap = 'wrap';
                               galleryGrid.style.gap = '10px';
                               galleryGrid.style.height = '100%';
                               galleryGrid.style.width = '100%';
                               window.empreendimentoPublicData.galeria_fotos.forEach((imgSrc, index) => {
                                   const imgElement = document.createElement('img');
                                   imgElement.src = imgSrc;
                                   imgElement.style.width = '300px';
                                   imgElement.style.height = '300px';
                                   imgElement.style.objectFit = 'cover';
                                   imgElement.style.cursor = 'pointer';
                                   imgElement.addEventListener('click', () => showFullScreenGalleryModal(window.empreendimentoPublicData.galeria_fotos, index));
                                   galleryGrid.appendChild(imgElement);
                               });
                               mediaModalContentBody.appendChild(galleryGrid);
                           } else {
                               mediaModalContentBody.textContent = 'Nenhuma foto de galeria disponível.';
                           }
                           break;
                       case 'video':
                           titleText = 'Vídeos';
                           if (window.empreendimentoPublicData.videos_youtube_ids && window.empreendimentoPublicData.videos_youtube_ids.length > 0) {
                               window.empreendimentoPublicData.videos_youtube_ids.forEach(videoId => {
                                   const iframe = document.createElement('iframe');
                                   iframe.width = '100%';
                                   iframe.height = '315';
                                   iframe.src = `https://www.youtube.com/embed/${videoId}`;
                                   iframe.frameBorder = '0';
                                   iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                                   iframe.allowFullscreen = true;
                                   mediaModalContentBody.appendChild(iframe);
                               });
                           } else {
                               mediaModalContentBody.textContent = 'Nenhum vídeo disponível.';
                           }
                           break;
                       case 'localizacao':
                           titleText = 'Localização';
                           if (window.empreendimentoPublicData.mapa_google_embed) {
                               const iframe = document.createElement('iframe');
                               iframe.width = '100%';
                               iframe.height = '450';
                               iframe.src = window.empreendimentoPublicData.mapa_google_embed;
                               iframe.frameBorder = '0';
                               iframe.allowFullscreen = true;
                               iframe.loading = 'lazy';
                               mediaModalContentBody.appendChild(iframe);
                           } else {
                               mediaModalContentBody.textContent = 'Nenhuma informação de localização disponível.';
                           }
                           break;
                       case 'documento':
                           if (window.empreendimentoPublicData.documentos_link) { // Verifica se documentos_link está definido
                               if (documentType === 'contrato' && window.empreendimentoPublicData.documentos_link.contrato) {
                                   titleText = 'Contrato';
                                   mediaModalContentBody.innerHTML = `<p><a href="${window.empreendimentoPublicData.documentos_link.contrato}" target="_blank" class="btn btn-primary">Visualizar Contrato</a></p>`;
                               } else if (documentType === 'memorial' && window.empreendimentoPublicData.documentos_link.memorial) {
                                   titleText = 'Memorial Descritivo';
                                   mediaModalContentBody.innerHTML = `<p><a href="${window.empreendimentoPublicData.documentos_link.memorial}" target="_blank" class="btn btn-primary">Visualizar Memorial</a></p>`;
                               } else {
                                   titleText = 'Documento';
                                   mediaModalContentBody.textContent = 'Nenhum documento disponível para este tipo.';
                               }
                           } else {
                               console.warn("window.empreendimentoPublicData.documentos_link está undefined.");
                               mediaModalContentBody.textContent = 'Nenhum documento disponível.';
                           }
                           break;
                       case 'detalhes':
                           titleText = 'Detalhes do Empreendimento';
                           if (window.empreendimentoPublicData.descricao) {
                               mediaModalContentBody.innerHTML = `<p>${htmlspecialchars(window.empreendimentoPublicData.descricao)}</p>`;
                           } else {
                               mediaModalContentBody.textContent = 'Nenhum detalhe disponível.';
                           }
                           break;
                       default:
                           titleText = 'Informação';
                           mediaModalContentBody.textContent = 'Conteúdo não especificado.';
                           break;
                   }
                   mediaModalTitle.textContent = titleText;
                   openModal(mediaModalGlobal);
               });
           });

           mediaModalCloseBtn.addEventListener('click', () => {
               closeModal(mediaModalGlobal);
           });

           mediaModalGlobal.addEventListener('click', e => {
               if (e.target === mediaModalGlobal) {
                   closeModal(mediaModalGlobal);
               }
           });
       } else {
           console.warn('Elementos do modal de mídia ou botões .onclick-btn-midia não foram encontrados no DOM. Verifique IDs/classes e o console para mais erros.');
       }

       // Lógica para abrir o Modal de Reserva ao clicar em um card de unidade
       const unitCards = document.querySelectorAll('.unit-card-item.btn-view-details');
       if (modalReserva && unitCards.length > 0) {
           unitCards.forEach(card => {
               card.addEventListener('click', () => {
                   console.log('Card de unidade clicado. ID:', card.dataset.unidadeId);
                   
                   // Verifica se os datasets existem antes de tentar parsear
                   if (!card.dataset.unidade || !card.dataset.empreendimento) {
                       console.error("Dados da unidade ou empreendimento ausentes no dataset do card.");
                       alert("Erro ao carregar detalhes da unidade. Por favor, recarregue a página.");
                       return;
                   }

                   const unidadeData = JSON.parse(card.dataset.unidade);
                   const empreendimentoData = JSON.parse(card.dataset.empreendimento);
                   const isLoggedIn = card.dataset.isLoggedIn === 'true';

                   // LOG DE STATUS E DADOS DO USUÁRIO LOGADO
                   console.log('Unit card click: isLoggedIn=', isLoggedIn, 'logged_user_info_public=', window.logged_user_info_public);

                   // Preenche os dados no modal
                   if (modalUnidadeNumero) modalUnidadeNumero.textContent = unidadeData.numero;
                   if (modalEmpreendimentoNome) modalEmpreendimentoNome.textContent = empreendimentoData.nome;
                   if (modalTipoUnidade) modalTipoUnidade.textContent = unidadeData.tipo_unidade_nome;
                   if (modalMetragem) modalMetragem.textContent = unidadeData.metragem;
                   if (modalAndar) modalAndar.textContent = unidadeData.andar;
                   if (modalPosicao) modalPosicao.textContent = unidadeData.posicao;
                   if (modalValor) modalValor.textContent = formatCurrency(unidadeData.valor);
                   if (modalFotoPlanta) {
                        modalFotoPlanta.src = unidadeData.foto_planta;
                        console.log('Planta URL no modal:', unidadeData);
                   }
                   
                   if (modalInformacoesPagamento) {
                       if (unidadeData.informacoes_pagamento && Object.keys(unidadeData.informacoes_pagamento).length > 0) {
                           let pagamentoHtml = '<ul>';
                           for (const key in unidadeData.informacoes_pagamento) {
                               if (Object.hasOwnProperty.call(unidadeData.informacoes_pagamento, key)) {
                                   pagamentoHtml += `<li><strong>${htmlspecialchars(key)}:</strong> ${htmlspecialchars(unidadeData.informacoes_pagamento[key])}</li>`;
                               }
                           }
                           pagamentoHtml += '</ul>';
                           modalInformacoesPagamento.innerHTML = pagamentoHtml;
                       } else {
                           modalInformacoesPagamento.textContent = 'Informações de pagamento não disponíveis.';
                       }
                   }

                   if (modalValorTotalUnidade) modalValorTotalUnidade.textContent = formatCurrency(unidadeData.valor);

                   // Preenche campos hidden do formulário
                   if (document.getElementById('formUnidadeId')) document.getElementById('formUnidadeId').value = unidadeData.id;
                   if (document.getElementById('formEmpreendimentoId')) document.getElementById('formEmpreendimentoId').value = empreendimentoData.id;
                   if (document.getElementById('formValorReserva')) document.getElementById('formValorReserva').value = unidadeData.valor;
                   
                   if (document.getElementById('formDocumentosObrigatoriosJson')) {
                       // Certifica-se de que empreendimentoData.documentos_obrigatorios é um array
                       const docsObrigatorios = Array.isArray(empreendimentoData.documentos_obrigatorios) ? empreendimentoData.documentos_obrigatorios : [];
                       document.getElementById('formDocumentosObrigatoriosJson').value = JSON.stringify(docsObrigatorios);
                   }

                   // Lógica para mostrar/esconder seções baseadas no login e tipo de usuário
                   if (isLoggedIn && window.logged_user_info_public && (window.logged_user_info_public.type === 'corretor_autonomo' || window.logged_user_info_public.type === 'corretor_imobiliaria')) {
                       console.log('Usuário logado é corretor. Exibindo campos completos.');
                       if (enderecoSection) enderecoSection.style.display = 'block';
                       // Preencher campos de endereço se o usuário for corretor
                       if (document.getElementById('nome_cliente')) document.getElementById('nome_cliente').value = window.logged_user_info_public.name || '';
                       if (document.getElementById('cpf_cliente')) document.getElementById('cpf_cliente').value = window.logged_user_info_public.cpf || '';
                       if (document.getElementById('email_cliente')) document.getElementById('email_cliente').value = window.logged_user_info_public.email || '';
                       if (document.getElementById('whatsapp_cliente')) document.getElementById('whatsapp_cliente').value = window.logged_user_info_public.whatsapp || '';

                       if (document.getElementById('cep_cliente')) document.getElementById('cep_cliente').value = window.logged_user_info_public.cep || '';
                       if (document.getElementById('endereco_cliente')) document.getElementById('endereco_cliente').value = window.logged_user_info_public.endereco || '';
                       if (document.getElementById('numero_cliente')) document.getElementById('numero_cliente').value = window.logged_user_info_public.numero || '';
                       if (document.getElementById('complemento_cliente')) document.getElementById('complemento_cliente').value = window.logged_user_info_public.complemento || '';
                       if (document.getElementById('bairro_cliente')) document.getElementById('bairro_cliente').value = window.logged_user_info_public.bairro || '';
                       if (document.getElementById('cidade_cliente')) document.getElementById('cidade_cliente').value = window.logged_user_info_public.cidade || '';
                       if (document.getElementById('estado_cliente')) document.getElementById('estado_cliente').value = window.logged_user_info_public.estado || '';

                       // Mostrar seção de documentos e preencher lista
                       if (documentosUploadSection) documentosUploadSection.style.display = 'block';
                       if (documentosObrigatoriosList && Array.isArray(empreendimentoData.documentos_obrigatorios)) {
                           try {
                               const docs = empreendimentoData.documentos_obrigatorios;
                               documentosObrigatoriosList.innerHTML = '';
                               docs.forEach((docName) => {
                                   if (typeof docName === 'string' && docName.trim() !== '') {
                                       const docSlug = docName.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_+$/g, '');
                                       const docItem = document.createElement('div');
                                       docItem.classList.add('document-upload-item');
                                       docItem.innerHTML = `
                                           <label for="doc_${docSlug}">${htmlspecialchars(docName)}:</label>
                                           <input type="file" id="doc_${docSlug}" name="doc_${docSlug}" required>
                                       `;
                                       documentosObrigatoriosList.appendChild(docItem);
                                   } else {
                                       console.warn('Documento obrigatório inválido encontrado (não é string ou está vazio):', docName);
                                   }
                               });
                           } catch (e) {
                               console.error('Erro ao processar documentos obrigatórios:', e);
                               documentosObrigatoriosList.innerHTML = '<p>Erro ao carregar documentos.</p>';
                           }
                       } else {
                           console.warn("empreendimentoData.documentos_obrigatorios não é um array ou está vazio:", empreendimentoData.documentos_obrigatorios);
                           documentosObrigatoriosList.innerHTML = '<p>Nenhum documento obrigatório configurado.</p>';
                       }
                   } else {
                       // Se não logado ou logado mas não corretor
                       if (enderecoSection) enderecoSection.style.display = 'none';
                       if (documentosUploadSection) documentosUploadSection.style.display = 'none';
                       console.log('Usuário não logado ou não é corretor. Escondendo campos de endereço e documentos.');
                   }

                   // Reinicia o wizard para o Step 1
                   reservaStep1.classList.remove('hidden');
                   reservaStep2.classList.add('hidden');
                   reservaStep3.classList.add('hidden');

                   openModal(modalReserva);
               });
           });
       } else {
           console.warn('Modal de reserva ou cards de unidade não encontrados no DOM.');
       }

       // Lógica do Wizard de Reserva (mantida como na versão anterior)
       if (btnAdvanceToStep2 && btnBackToStep1 && btnConfirmReserva && btnCloseReservationModal) {
           btnAdvanceToStep2.addEventListener('click', () => {
               console.log('Avançar para o Step 2 clicado.');
               reservaStep1.classList.add('hidden');
               reservaStep2.classList.remove('hidden');
               
               if (document.getElementById('formMomentoEnvioDocumentacao')) {
                   const unidadeId = document.getElementById('formUnidadeId').value;
                   const unidadeCard = document.querySelector(`.unit-card-item[data-unidade-id="${unidadeId}"]`);
                   if (unidadeCard) {
                       const empreendimentoData = JSON.parse(unidadeCard.dataset.empreendimento);
                       document.getElementById('formMomentoEnvioDocumentacao').value = empreendimentoData.momento_envio_documentacao || 'Na Proposta de Reserva';
                       console.log('Momento de envio da documentação definido para:', document.getElementById('formMomentoEnvioDocumentacao').value);
                   } else {
                       console.warn('Card da unidade não encontrado para definir momento_envio_documentacao. Usando valor padrão.');
                       document.getElementById('formMomentoEnvioDocumentacao').value = 'Na Proposta de Reserva';
                   }
               }
           });

           btnBackToStep1.addEventListener('click', () => {
               console.log('Voltar para o Step 1 clicado.');
               reservaStep2.classList.add('hidden');
               reservaStep1.classList.remove('hidden');
           });

           formReserva.addEventListener('submit', async (e) => {
               e.preventDefault();
               console.log('Formulário de reserva submetido.');

               const requiredFields = formReserva.querySelectorAll('[required]');
               let allFieldsValid = true;
               requiredFields.forEach(field => {
                   if (field.type !== 'file' && !field.value.trim()) {
                       field.classList.add('is-invalid');
                       allFieldsValid = false;
                   } else {
                       field.classList.remove('is-invalid');
                   }
               });

               if (documentosUploadSection && documentosUploadSection.style.display === 'block') {
                   const docInputs = documentosObrigatoriosList.querySelectorAll('input[type="file"][required]');
                   docInputs.forEach(input => {
                       if (input.files.length === 0) {
                           input.classList.add('is-invalid');
                           allFieldsValid = false;
                       } else {
                           input.classList.remove('is-invalid');
                       }
                   });
               }

               if (!allFieldsValid) {
                   alert('Por favor, preencha todos os campos obrigatórios e anexe os documentos necessários.');
                   console.error('Validação do formulário falhou.');
                   return;
               }

               btnConfirmReserva.disabled = true;
               btnConfirmReserva.textContent = 'Enviando...';

               const formData = new FormData(formReserva);
               console.log('Dados do formulário para envio:', Object.fromEntries(formData.entries()));

               try {
                   const response = await fetch(BASE_URL_JS + 'api/public_reserve.php', {
                       method: 'POST',
                       body: formData
                   });

                   const result = await response.json();
                   console.log('Resposta da API de reserva:', result);

                   if (result.success) {
                       if (successTitle) successTitle.textContent = result.title || 'Parabéns!';
                       if (successMessage) successMessage.textContent = result.message || 'Sua solicitação foi enviada com sucesso.';
                       if (successDetails) {
                           if (result.upload_link) {
                               successDetails.innerHTML = `Um link para upload de documentos foi enviado para o seu e-mail. Você também pode acessá-lo diretamente aqui: <a href="${result.upload_link}" target="_blank">Enviar Documentos</a>`;
                           } else {
                               successDetails.textContent = '';
                           }
                       }

                       reservaStep2.classList.add('hidden');
                       reservaStep3.classList.remove('hidden');

                       const unidadeId = formData.get('unidade_id');
                       console.log('Tentando atualizar card para Unidade ID:', unidadeId);
                       const unidadeCard = document.querySelector(`.unit-card-item[data-unidade-id="${unidadeId}"]`);
                       if (unidadeCard) {
                           unidadeCard.classList.remove('status-available');
                           unidadeCard.classList.add('status-reserved');
                           const statusText = unidadeCard.querySelector('.unit-card-status-text');
                           if (statusText) statusText.textContent = 'Reservada';
                           unidadeCard.disabled = true;
                           unidadeCard.classList.remove('btn-view-details');
                           console.log('Card da unidade atualizado para "Reservada".');
                       } else {
                           console.warn('Card da unidade não encontrado no DOM para atualização.');
                       }

                   } else {
                       alert('Erro ao processar a reserva: ' + (result.message || 'Erro desconhecido.'));
                       console.error('Erro na reserva (API respondeu com erro):', result.error || result.message);
                   }
               } catch (error) {
                   alert('Ocorreu um erro na comunicação com o servidor. Por favor, tente novamente.');
                   console.error('Erro de rede ou servidor (API não retornou JSON válido ou erro de conexão):', error);
               } finally {
                   btnConfirmReserva.disabled = false;
                   btnConfirmReserva.textContent = 'Confirmar Reserva';
               }
           });

           btnCloseReservationModal.addEventListener('click', () => {
               closeModal(modalReserva);
               formReserva.reset();
               console.log('Modal de reserva fechado e formulário resetado.');
           });
       } else {
           console.warn('Elementos do wizard de reserva não foram encontrados no DOM.');
       }

       // Lógica para o campo de CEP (mantida como na versão anterior)
       const cepInput = document.getElementById('cep_cliente');
       if (cepInput) {
           cepInput.addEventListener('blur', async () => {
               const cep = cepInput.value.replace(/\D/g, '');
               if (cep.length === 8) {
                   try {
                       const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                       const data = await response.json();
                       if (!data.erro) {
                           document.getElementById('endereco_cliente').value = data.logradouro || '';
                           document.getElementById('bairro_cliente').value = data.bairro || '';
                           document.getElementById('cidade_cliente').value = data.localidade || '';
                           document.getElementById('estado_cliente').value = data.uf || '';
                           console.log('CEP encontrado:', data);
                       } else {
                           alert('CEP não encontrado.');
                           console.warn('Erro ao buscar CEP: CEP não encontrado.');
                       }
                   } catch (error) {
                       console.error('Erro ao buscar CEP na API ViaCEP:', error);
                       alert('Erro ao buscar CEP. Verifique sua conexão.');
                   }
               }
           });
       }

       // Aplicação de máscaras de entrada (mantida como na versão anterior)
       document.querySelectorAll('[data-mask-type="cpf"]').forEach(input => {
           input.addEventListener('input', (e) => {
               let value = e.target.value.replace(/\D/g, '');
               if (value.length > 11) value = value.substring(0, 11);
               value = value.replace(/(\d{3})(\d)/, '$1.$2');
               value = value.replace(/(\d{3})(\d)/, '$1.$2');
               value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
               e.target.value = value;
           });
       });

       document.querySelectorAll('[data-mask-type="whatsapp"]').forEach(input => {
           input.addEventListener('input', (e) => {
               let value = e.target.value.replace(/\D/g, '');
               if (value.length > 11) value = value.substring(0, 11);
               if (value.length > 10) {
                   value = value.replace(/^(\d\d)(\d{5})(\d{4}).*/, '($1) $2-$3');
               } else if (value.length > 6) {
                   value = value.replace(/^(\d\d)(\d{4})(\d{0,4}).*/, '($1) $2-$3');
               } else if (value.length > 2) {
                   value = value.replace(/^(\d\d)(\d{0,5})/, '($1) $2');
               } else {
                   value = value.replace(/^(\d*)/, '($1');
               }
               e.target.value = value;
           });
       });

       document.querySelectorAll('[data-mask-type="cep"]').forEach(input => {
           input.addEventListener('input', (e) => {
               let value = e.target.value.replace(/\D/g, '');
               if (value.length > 8) value = value.substring(0, 8);
               value = value.replace(/^(\d{5})(\d)/, '$1-$2');
               e.target.value = value;
           });
       });

   } else {
       console.warn('A página não parece ser uma página de empreendimento ou elementos essenciais não foram encontrados. Algumas funcionalidades podem não estar ativas.');
   }

   // ===============================================
   // 3. LÓGICA DE SCROLL POR CLIQUE NO ANDARES-WRAPPER
   // ===============================================
   function initDragToScrollAndares() {
       const andaresWrapper = document.querySelector('.andares-wrapper');
       if (!andaresWrapper) {
           console.warn('Elemento .andares-wrapper não encontrado para implementar scroll por clique.');
           return;
       }

       let isDown = false;
       let startX;
       let scrollLeft;
       let startY;
       let scrollTop;

       console.log('Inicializando scroll por clique no .andares-wrapper');

       // Mouse events
       andaresWrapper.addEventListener('mousedown', (e) => {
           isDown = true;
           andaresWrapper.classList.add('grabbing');
           startX = e.pageX - andaresWrapper.offsetLeft;
           scrollLeft = andaresWrapper.scrollLeft;
           startY = e.pageY - andaresWrapper.offsetTop;
           scrollTop = andaresWrapper.scrollTop;
           e.preventDefault(); // Previne seleção de texto
       });

       andaresWrapper.addEventListener('mouseleave', () => {
           isDown = false;
           andaresWrapper.classList.remove('grabbing');
       });

       andaresWrapper.addEventListener('mouseup', () => {
           isDown = false;
           andaresWrapper.classList.remove('grabbing');
       });

       andaresWrapper.addEventListener('mousemove', (e) => {
           if (!isDown) return;
           e.preventDefault();
           const x = e.pageX - andaresWrapper.offsetLeft;
           const y = e.pageY - andaresWrapper.offsetTop;
           const walkX = (x - startX) * 2; // Multiplicador de velocidade horizontal
           const walkY = (y - startY) * 2; // Multiplicador de velocidade vertical
           andaresWrapper.scrollLeft = scrollLeft - walkX;
           andaresWrapper.scrollTop = scrollTop - walkY;
       });

       // Touch events para dispositivos móveis
       andaresWrapper.addEventListener('touchstart', (e) => {
           isDown = true;
           andaresWrapper.classList.add('grabbing');
           const touch = e.touches[0];
           startX = touch.pageX - andaresWrapper.offsetLeft;
           scrollLeft = andaresWrapper.scrollLeft;
           startY = touch.pageY - andaresWrapper.offsetTop;
           scrollTop = andaresWrapper.scrollTop;
       });

       andaresWrapper.addEventListener('touchend', () => {
           isDown = false;
           andaresWrapper.classList.remove('grabbing');
       });

       andaresWrapper.addEventListener('touchmove', (e) => {
           if (!isDown) return;
           e.preventDefault();
           const touch = e.touches[0];
           const x = touch.pageX - andaresWrapper.offsetLeft;
           const y = touch.pageY - andaresWrapper.offsetTop;
           const walkX = (x - startX) * 2;
           const walkY = (y - startY) * 2;
           andaresWrapper.scrollLeft = scrollLeft - walkX;
           andaresWrapper.scrollTop = scrollTop - walkY;
       });

       console.log('Scroll por clique configurado com sucesso no .andares-wrapper');
   }

   // Inicializar a funcionalidade de scroll por clique
   initDragToScrollAndares();
});