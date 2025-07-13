-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 10/07/2025 às 20:57
-- Versão do servidor: 8.0.42
-- Versão do PHP: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `mvpreserva`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alertas`
--

CREATE TABLE `alertas` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `titulo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lido` tinyint(1) DEFAULT '0',
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `alertas`
--

INSERT INTO `alertas` (`id`, `usuario_id`, `titulo`, `mensagem`, `link`, `lido`, `data_criacao`) VALUES
(1, 1, 'Novo Lead Recebido', 'Um novo pedido de reserva foi feito para o Empreendimento Teste Central (Unidade 101). Acesse a seção de Leads para mais detalhes.', 'admin/leads/index.php', 1, '2025-07-01 22:12:54'),
(2, 1, 'Documentação Pendente', 'Cliente da Reserva #3 (Cliente Atribuido) precisa enviar documentos. Lembre o corretor!', 'admin/documentos/index.php', 1, '2025-07-01 22:07:54'),
(3, 2, 'Reserva Aprovada!', 'Parabéns! Sua reserva #3 para a Unidade 102 foi aprovada pelo administrador.', 'corretor/reservas/index.php', 0, '2025-07-01 22:02:54'),
(4, 1, 'Alerta Crítico: Problema no Servidor', 'Ocorreu um erro inesperado no servidor de arquivos. Verifique os logs imediatamente.', NULL, 1, '2025-07-01 21:57:54'),
(5, 1, 'Atualização do Sistema', 'Uma nova versão do sistema foi lançada com melhorias de performance. (Este deve estar lido).', NULL, 1, '2025-06-30 22:17:54'),
(6, 3, 'Nova Reserva Comercial', 'Uma nova reserva foi feita para o Edifício Comercial Alfa (Unidade 301).', 'admin_imobiliaria/reservas/index.php', 1, '2025-07-01 21:47:54'),
(7, 2, 'Novo Lead Atribuído', 'Um novo lead foi atribuído a você. Por favor, entre em contato com o cliente para dar andamento na reserva.', 'info', 0, '2025-07-02 14:10:44'),
(8, 1, 'Lead Atribuído', 'Um lead foi atribuído ao corretor com sucesso.', 'info', 1, '2025-07-02 14:10:44'),
(10, 1, 'Sua Conta Foi Aprovada!', 'O usuário asdasdasd () foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=6&entity_type=usuario', 1, '2025-07-02 14:27:37'),
(11, 1, 'Sua Conta Foi Aprovada!', 'O usuário asdasdasd () foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=6&entity_type=usuario', 1, '2025-07-02 14:27:37'),
(12, 1, 'Sua Conta Foi Aprovada!', 'O usuário asdasdasd () foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=6&entity_type=usuario', 1, '2025-07-02 14:27:37'),
(14, 7, 'Notificação Importante', 'O usuário Francisco Antunes de Souza () foi inativado por Admin Master.', 'event_type=notificacao_geral&entity_id=7&entity_type=usuario', 0, '2025-07-02 14:29:52'),
(15, 1, 'Notificação Importante', 'O usuário Francisco Antunes de Souza () foi inativado por Admin Master.', 'event_type=notificacao_geral&entity_id=7&entity_type=usuario', 1, '2025-07-02 14:29:52'),
(60, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #5). Status: Solicitada.', 'info', 1, '2025-07-03 16:42:35'),
(61, 19, 'Notificação Importante', 'O usuário Teste2@gmail.com (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=19&entity_type=usuario', 0, '2025-07-03 19:28:54'),
(62, 1, 'Notificação Importante', 'O usuário Teste2@gmail.com (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=19&entity_type=usuario', 1, '2025-07-03 19:28:54'),
(63, 1, 'Notificação Importante', 'O usuário Teste2@gmail.com (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=19&entity_type=usuario', 1, '2025-07-03 19:28:54'),
(64, 1, 'Notificação Importante', 'O usuário Teste2@gmail.com (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=19&entity_type=usuario', 1, '2025-07-03 19:28:54'),
(65, 18, 'Notificação Importante', 'O usuário vinculado@gmail.com (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=18&entity_type=usuario', 0, '2025-07-03 19:28:59'),
(66, 1, 'Notificação Importante', 'O usuário vinculado@gmail.com (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=18&entity_type=usuario', 1, '2025-07-03 19:28:59'),
(67, 1, 'Notificação Importante', 'O usuário vinculado@gmail.com (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=18&entity_type=usuario', 1, '2025-07-03 19:28:59'),
(68, 1, 'Notificação Importante', 'O usuário vinculado@gmail.com (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=18&entity_type=usuario', 1, '2025-07-03 19:28:59'),
(69, 16, 'Notificação Importante', 'O usuário Francisco SHOW (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=16&entity_type=usuario', 0, '2025-07-03 21:32:57'),
(70, 1, 'Notificação Importante', 'O usuário Francisco SHOW (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=16&entity_type=usuario', 1, '2025-07-03 21:32:57'),
(71, 1, 'Notificação Importante', 'O usuário Francisco SHOW (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=16&entity_type=usuario', 1, '2025-07-03 21:32:57'),
(72, 1, 'Notificação Importante', 'O usuário Francisco SHOW (corretor_imobiliaria) foi excluído por Admin Master.', 'event_type=notificacao_geral&entity_id=16&entity_type=usuario', 1, '2025-07-03 21:32:57'),
(73, 15, 'Sua Conta Foi Aprovada!', 'O usuário Francisco Antunes de Souza (corretor_imobiliaria) foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=15&entity_type=usuario', 0, '2025-07-03 21:33:02'),
(74, 1, 'Sua Conta Foi Aprovada!', 'O usuário Francisco Antunes de Souza (corretor_imobiliaria) foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=15&entity_type=usuario', 1, '2025-07-03 21:33:02'),
(75, 1, 'Sua Conta Foi Aprovada!', 'O usuário Francisco Antunes de Souza (corretor_imobiliaria) foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=15&entity_type=usuario', 1, '2025-07-03 21:33:02'),
(76, 1, 'Sua Conta Foi Aprovada!', 'O usuário Francisco Antunes de Souza (corretor_imobiliaria) foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=15&entity_type=usuario', 1, '2025-07-03 21:33:02'),
(77, 12, 'Notificação Importante', 'O usuário Francisco (corretor_imobiliaria) foi rejeitado por Admin Master.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 0, '2025-07-03 21:39:17'),
(78, 1, 'Notificação Importante', 'O usuário Francisco (corretor_imobiliaria) foi rejeitado por Admin Master.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 1, '2025-07-03 21:39:17'),
(79, 1, 'Notificação Importante', 'O usuário Francisco (corretor_imobiliaria) foi rejeitado por Admin Master.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 1, '2025-07-03 21:39:17'),
(80, 1, 'Notificação Importante', 'O usuário Francisco (corretor_imobiliaria) foi rejeitado por Admin Master.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 1, '2025-07-03 21:39:17'),
(81, 11, 'Sua Conta Foi Aprovada!', 'O usuário Francisco Antunes de Souza (corretor_imobiliaria) foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=11&entity_type=usuario', 0, '2025-07-03 21:39:22'),
(82, 1, 'Sua Conta Foi Aprovada!', 'O usuário Francisco Antunes de Souza (corretor_imobiliaria) foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=11&entity_type=usuario', 1, '2025-07-03 21:39:22'),
(83, 1, 'Sua Conta Foi Aprovada!', 'O usuário Francisco Antunes de Souza (corretor_imobiliaria) foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=11&entity_type=usuario', 1, '2025-07-03 21:39:22'),
(84, 1, 'Sua Conta Foi Aprovada!', 'O usuário Francisco Antunes de Souza (corretor_imobiliaria) foi aprovado por Admin Master.', 'event_type=corretor_aprovado&entity_id=11&entity_type=usuario', 1, '2025-07-03 21:39:22'),
(85, 20, 'Notificação do Sistema', 'O usuário Imob Central (admin_imobiliaria) foi criado.', 'event_type=novo_usuario_criado&entity_id=20&entity_type=usuario', 0, '2025-07-03 21:44:45'),
(86, 1, 'Notificação do Sistema', 'O usuário Imob Central (admin_imobiliaria) foi criado.', 'event_type=novo_usuario_criado&entity_id=20&entity_type=usuario', 1, '2025-07-03 21:44:45'),
(87, 1, 'Notificação Importante', 'Você não é mais o administrador da imobiliária \'Imobiliária Central\'.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-03 22:52:49'),
(88, 1, 'Notificação Importante', 'Você não é mais o administrador da imobiliária \'Imobiliária Central\'.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-03 22:52:49'),
(89, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #10). Status: Solicitada.', 'info', 1, '2025-07-04 11:28:38'),
(90, 1, 'Reserva Aprovada!', 'Sua reserva #10 para a unidade 201 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=10&entity_type=reserva', 1, '2025-07-04 11:56:25'),
(91, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #10.', 'event_type=reserva_aprovada&entity_id=10&entity_type=reserva', 1, '2025-07-04 11:56:25'),
(92, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #10.', 'event_type=reserva_aprovada&entity_id=10&entity_type=reserva', 1, '2025-07-04 11:56:25'),
(93, 1, 'Documentos Aprovados!', 'Os documentos da reserva #10 da unidade 201 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=10&entity_type=reserva', 1, '2025-07-04 12:01:46'),
(94, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #10. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=10&entity_type=reserva', 1, '2025-07-04 12:01:46'),
(95, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #10. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=10&entity_type=reserva', 1, '2025-07-04 12:01:46'),
(96, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #11). Status: Solicitada.', 'info', 1, '2025-07-04 12:39:32'),
(97, 1, 'Documentos Aprovados!', 'Os documentos da reserva #11 da unidade 305 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=11&entity_type=reserva', 1, '2025-07-04 12:40:13'),
(98, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #11. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=11&entity_type=reserva', 1, '2025-07-04 12:40:13'),
(99, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #11. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=11&entity_type=reserva', 1, '2025-07-04 12:40:13'),
(100, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #12). Status: Solicitada.', 'info', 1, '2025-07-04 12:55:47'),
(101, 1, 'Documentos Aprovados!', 'Os documentos da reserva #12 da unidade 308 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=12&entity_type=reserva', 1, '2025-07-04 12:56:22'),
(102, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #12. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=12&entity_type=reserva', 1, '2025-07-04 12:56:22'),
(103, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #12. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=12&entity_type=reserva', 1, '2025-07-04 12:56:22'),
(104, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #13). Status: Solicitada.', 'info', 1, '2025-07-04 13:00:46'),
(105, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #14). Status: Solicitada.', 'info', 1, '2025-07-04 13:10:55'),
(106, 1, 'Documentos Aprovados!', 'Os documentos da reserva #14 da unidade 304 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=14&entity_type=reserva', 1, '2025-07-04 13:11:21'),
(107, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #14. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=14&entity_type=reserva', 1, '2025-07-04 13:11:21'),
(108, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #14. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=14&entity_type=reserva', 1, '2025-07-04 13:11:21'),
(109, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Corretor ImoBLeste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=21&entity_type=usuario', 1, '2025-07-04 13:40:43'),
(110, 3, 'Novo Corretor Cadastrado!', 'Um novo usuário (Corretor ImoBLeste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=21&entity_type=usuario', 0, '2025-07-04 13:40:43'),
(111, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (ImobLete - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=22&entity_type=usuario', 1, '2025-07-04 13:54:46'),
(112, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (ImobLete - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=22&entity_type=usuario', 1, '2025-07-04 13:54:46'),
(113, 3, 'Novo Corretor Cadastrado!', 'Um novo usuário (ImobLete - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=22&entity_type=usuario', 0, '2025-07-04 13:54:46'),
(114, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (ImobLete - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=22&entity_type=usuario', 1, '2025-07-04 13:54:46'),
(115, 22, 'Sua Conta Foi Aprovada!', 'O corretor ImobLete foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=22&entity_type=usuario', 0, '2025-07-04 14:02:18'),
(116, 1, 'Sua Conta Foi Aprovada!', 'O corretor ImobLete foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=22&entity_type=usuario', 1, '2025-07-04 14:02:18'),
(117, 3, 'Sua Conta Foi Aprovada!', 'O corretor ImobLete foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=22&entity_type=usuario', 0, '2025-07-04 14:02:18'),
(118, 3, 'Sua Conta Foi Aprovada!', 'O corretor ImobLete foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=22&entity_type=usuario', 0, '2025-07-04 14:02:18'),
(119, 1, 'Sua Conta Foi Aprovada!', 'O corretor ImobLete foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=22&entity_type=usuario', 1, '2025-07-04 14:02:18'),
(120, 3, 'Sua Conta Foi Aprovada!', 'O corretor ImobLete foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=22&entity_type=usuario', 0, '2025-07-04 14:02:18'),
(121, 14, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=14&entity_type=usuario', 0, '2025-07-04 14:04:19'),
(122, 1, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=14&entity_type=usuario', 1, '2025-07-04 14:04:19'),
(123, 3, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=14&entity_type=usuario', 0, '2025-07-04 14:04:19'),
(124, 3, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=14&entity_type=usuario', 0, '2025-07-04 14:04:19'),
(125, 1, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=14&entity_type=usuario', 1, '2025-07-04 14:04:19'),
(126, 3, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=14&entity_type=usuario', 0, '2025-07-04 14:04:19'),
(127, 21, 'Sua Conta Foi Aprovada!', 'O corretor Corretor ImoBLeste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=21&entity_type=usuario', 0, '2025-07-04 14:07:44'),
(128, 1, 'Sua Conta Foi Aprovada!', 'O corretor Corretor ImoBLeste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=21&entity_type=usuario', 1, '2025-07-04 14:07:44'),
(129, 3, 'Sua Conta Foi Aprovada!', 'O corretor Corretor ImoBLeste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=21&entity_type=usuario', 0, '2025-07-04 14:07:44'),
(130, 3, 'Sua Conta Foi Aprovada!', 'O corretor Corretor ImoBLeste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=21&entity_type=usuario', 0, '2025-07-04 14:07:44'),
(131, 1, 'Sua Conta Foi Aprovada!', 'O corretor Corretor ImoBLeste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=21&entity_type=usuario', 1, '2025-07-04 14:07:44'),
(132, 3, 'Sua Conta Foi Aprovada!', 'O corretor Corretor ImoBLeste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=21&entity_type=usuario', 0, '2025-07-04 14:07:44'),
(133, 12, 'Sua Conta Foi Aprovada!', 'O corretor Francisco foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:04'),
(134, 1, 'Sua Conta Foi Aprovada!', 'O corretor Francisco foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=12&entity_type=usuario', 1, '2025-07-04 14:21:04'),
(135, 3, 'Sua Conta Foi Aprovada!', 'O corretor Francisco foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:04'),
(136, 3, 'Sua Conta Foi Aprovada!', 'O corretor Francisco foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:04'),
(137, 1, 'Sua Conta Foi Aprovada!', 'O corretor Francisco foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=12&entity_type=usuario', 1, '2025-07-04 14:21:04'),
(138, 3, 'Sua Conta Foi Aprovada!', 'O corretor Francisco foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:04'),
(139, 12, 'Notificação Importante', 'O corretor Francisco foi inativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:28'),
(140, 1, 'Notificação Importante', 'O corretor Francisco foi inativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 1, '2025-07-04 14:21:28'),
(141, 3, 'Notificação Importante', 'O corretor Francisco foi inativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:28'),
(142, 3, 'Notificação Importante', 'O corretor Francisco foi inativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:28'),
(143, 1, 'Notificação Importante', 'O corretor Francisco foi inativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 1, '2025-07-04 14:21:28'),
(144, 3, 'Notificação Importante', 'O corretor Francisco foi inativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:28'),
(145, 12, 'Notificação Importante', 'O corretor Francisco foi ativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:41'),
(146, 1, 'Notificação Importante', 'O corretor Francisco foi ativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 1, '2025-07-04 14:21:41'),
(147, 3, 'Notificação Importante', 'O corretor Francisco foi ativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:41'),
(148, 3, 'Notificação Importante', 'O corretor Francisco foi ativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:41'),
(149, 1, 'Notificação Importante', 'O corretor Francisco foi ativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 1, '2025-07-04 14:21:41'),
(150, 3, 'Notificação Importante', 'O corretor Francisco foi ativado por Admin Imobiliária.', 'event_type=notificacao_geral&entity_id=12&entity_type=usuario', 0, '2025-07-04 14:21:41'),
(151, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #15). Status: Documentos pendentes.', 'info', 1, '2025-07-04 14:23:17'),
(152, 12, 'Documentos Aprovados!', 'Os documentos da reserva #15 da unidade 306 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=15&entity_type=reserva', 0, '2025-07-04 14:28:39'),
(153, 1, 'Documentos Aprovados!', 'Os documentos da reserva #15 da unidade 306 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=15&entity_type=reserva', 1, '2025-07-04 14:28:39'),
(154, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #15. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=15&entity_type=reserva', 1, '2025-07-04 14:28:39'),
(155, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #15. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=15&entity_type=reserva', 1, '2025-07-04 14:28:39'),
(160, 13, 'Notificação Importante', 'O usuário 12Corretor (corretor_imobiliaria) foi rejeitado por Admin Master.', 'event_type=notificacao_geral&entity_id=13&entity_type=usuario', 0, '2025-07-04 15:10:15'),
(161, 1, 'Notificação Importante', 'O usuário 12Corretor (corretor_imobiliaria) foi rejeitado por Admin Master.', 'event_type=notificacao_geral&entity_id=13&entity_type=usuario', 1, '2025-07-04 15:10:15'),
(162, 1, 'Notificação Importante', 'O usuário 12Corretor (corretor_imobiliaria) foi rejeitado por Admin Master.', 'event_type=notificacao_geral&entity_id=13&entity_type=usuario', 1, '2025-07-04 15:10:15'),
(163, 1, 'Notificação Importante', 'O usuário 12Corretor (corretor_imobiliaria) foi rejeitado por Admin Master.', 'event_type=notificacao_geral&entity_id=13&entity_type=usuario', 1, '2025-07-04 15:10:15'),
(164, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Joao Leste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=23&entity_type=usuario', 1, '2025-07-04 15:21:44'),
(165, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Joao Leste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=23&entity_type=usuario', 1, '2025-07-04 15:21:44'),
(166, 3, 'Novo Corretor Cadastrado!', 'Um novo usuário (Joao Leste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=23&entity_type=usuario', 0, '2025-07-04 15:21:44'),
(167, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Joao Leste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=23&entity_type=usuario', 1, '2025-07-04 15:21:44'),
(168, 23, 'Sua Conta Foi Aprovada!', 'O corretor Joao Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=23&entity_type=usuario', 0, '2025-07-04 15:23:04'),
(169, 1, 'Sua Conta Foi Aprovada!', 'O corretor Joao Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=23&entity_type=usuario', 1, '2025-07-04 15:23:04'),
(170, 3, 'Sua Conta Foi Aprovada!', 'O corretor Joao Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=23&entity_type=usuario', 0, '2025-07-04 15:23:04'),
(171, 3, 'Sua Conta Foi Aprovada!', 'O corretor Joao Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=23&entity_type=usuario', 0, '2025-07-04 15:23:04'),
(172, 1, 'Sua Conta Foi Aprovada!', 'O corretor Joao Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=23&entity_type=usuario', 1, '2025-07-04 15:23:04'),
(173, 3, 'Sua Conta Foi Aprovada!', 'O corretor Joao Leste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=23&entity_type=usuario', 0, '2025-07-04 15:23:04'),
(178, 12, 'Contrato Enviado!', 'Contrato da reserva #15 marcado como enviado manualmente. Aguarde assinatura.', 'event_type=contrato_enviado&entity_id=15&entity_type=reserva', 0, '2025-07-04 15:49:19'),
(179, 1, 'Contrato Enviado!', 'Contrato da reserva #15 marcado como enviado manualmente. Aguarde assinatura.', 'event_type=contrato_enviado&entity_id=15&entity_type=reserva', 1, '2025-07-04 15:49:19'),
(180, 1, 'Contrato Enviado!', 'Você (Desconhecido) marcou o contrato da reserva #15 como enviado.', 'event_type=contrato_enviado&entity_id=15&entity_type=reserva', 1, '2025-07-04 15:49:19'),
(181, 1, 'Contrato Enviado!', 'Você (Desconhecido) marcou o contrato da reserva #15 como enviado.', 'event_type=contrato_enviado&entity_id=15&entity_type=reserva', 1, '2025-07-04 15:49:19'),
(182, 12, 'Venda Concluída!', 'Sua venda para a reserva #15 da unidade 306 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=15&entity_type=reserva', 0, '2025-07-04 15:50:21'),
(183, 1, 'Venda Concluída!', 'Sua venda para a reserva #15 da unidade 306 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=15&entity_type=reserva', 1, '2025-07-04 15:50:21'),
(184, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #15.', 'event_type=venda_concluida&entity_id=15&entity_type=reserva', 1, '2025-07-04 15:50:21'),
(185, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #15.', 'event_type=venda_concluida&entity_id=15&entity_type=reserva', 1, '2025-07-04 15:50:21'),
(186, 1, 'Contrato Enviado!', 'Contrato da reserva #11 marcado como enviado manualmente. Aguarde assinatura.', 'event_type=contrato_enviado&entity_id=11&entity_type=reserva', 1, '2025-07-04 15:55:44'),
(187, 1, 'Contrato Enviado!', 'Você (Desconhecido) marcou o contrato da reserva #11 como enviado.', 'event_type=contrato_enviado&entity_id=11&entity_type=reserva', 1, '2025-07-04 15:55:44'),
(188, 1, 'Contrato Enviado!', 'Você (Desconhecido) marcou o contrato da reserva #11 como enviado.', 'event_type=contrato_enviado&entity_id=11&entity_type=reserva', 1, '2025-07-04 15:55:44'),
(189, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #16). Status: Documentos pendentes.', 'info', 1, '2025-07-04 16:06:55'),
(190, 23, 'Documentos Aprovados!', 'Os documentos da reserva #16 da unidade 307 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=16&entity_type=reserva', 0, '2025-07-04 16:07:45'),
(191, 1, 'Documentos Aprovados!', 'Os documentos da reserva #16 da unidade 307 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=16&entity_type=reserva', 1, '2025-07-04 16:07:45'),
(192, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #16. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=16&entity_type=reserva', 1, '2025-07-04 16:07:45'),
(193, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #16. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=16&entity_type=reserva', 1, '2025-07-04 16:07:45'),
(194, 23, 'Contrato Enviado!', 'Contrato da reserva #16 enviado manualmente. Aguarde assinatura do cliente Marcia 202.', 'event_type=contrato_enviado&entity_id=16&entity_type=reserva', 0, '2025-07-04 16:08:07'),
(195, 1, 'Contrato Enviado!', 'Contrato da reserva #16 enviado manualmente. Aguarde assinatura do cliente Marcia 202.', 'event_type=contrato_enviado&entity_id=16&entity_type=reserva', 1, '2025-07-04 16:08:07'),
(196, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #16 manualmente.', 'event_type=contrato_enviado&entity_id=16&entity_type=reserva', 1, '2025-07-04 16:08:07'),
(197, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #16 manualmente.', 'event_type=contrato_enviado&entity_id=16&entity_type=reserva', 1, '2025-07-04 16:08:07'),
(198, 23, 'Venda Concluída!', 'Sua venda para a reserva #16 da unidade 307 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=16&entity_type=reserva', 0, '2025-07-04 16:08:25'),
(199, 1, 'Venda Concluída!', 'Sua venda para a reserva #16 da unidade 307 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=16&entity_type=reserva', 1, '2025-07-04 16:08:25'),
(200, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #16.', 'event_type=venda_concluida&entity_id=16&entity_type=reserva', 1, '2025-07-04 16:08:25'),
(201, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #16.', 'event_type=venda_concluida&entity_id=16&entity_type=reserva', 1, '2025-07-04 16:08:25'),
(202, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #17). Status: Documentos pendentes.', 'info', 1, '2025-07-04 16:22:56'),
(203, 23, 'Documentos Aprovados!', 'Os documentos da reserva #17 da unidade 408 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=17&entity_type=reserva', 0, '2025-07-04 16:23:53'),
(204, 1, 'Documentos Aprovados!', 'Os documentos da reserva #17 da unidade 408 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=17&entity_type=reserva', 1, '2025-07-04 16:23:53'),
(205, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #17. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=17&entity_type=reserva', 1, '2025-07-04 16:23:53'),
(206, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #17. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=17&entity_type=reserva', 1, '2025-07-04 16:23:53'),
(207, 23, 'Contrato Enviado!', 'Contrato da reserva #17 enviado manualmente. Aguarde assinatura do cliente Francisco Antunes de Souza.', 'event_type=contrato_enviado&entity_id=17&entity_type=reserva', 0, '2025-07-04 16:24:15'),
(208, 1, 'Contrato Enviado!', 'Contrato da reserva #17 enviado manualmente. Aguarde assinatura do cliente Francisco Antunes de Souza.', 'event_type=contrato_enviado&entity_id=17&entity_type=reserva', 1, '2025-07-04 16:24:15'),
(209, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #17 manualmente.', 'event_type=contrato_enviado&entity_id=17&entity_type=reserva', 1, '2025-07-04 16:24:15'),
(210, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #17 manualmente.', 'event_type=contrato_enviado&entity_id=17&entity_type=reserva', 1, '2025-07-04 16:24:15'),
(211, 23, 'Venda Concluída!', 'Sua venda para a reserva #17 da unidade 408 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=17&entity_type=reserva', 0, '2025-07-04 16:24:36'),
(212, 1, 'Venda Concluída!', 'Sua venda para a reserva #17 da unidade 408 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=17&entity_type=reserva', 1, '2025-07-04 16:24:36'),
(213, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #17.', 'event_type=venda_concluida&entity_id=17&entity_type=reserva', 1, '2025-07-04 16:24:36'),
(214, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #17.', 'event_type=venda_concluida&entity_id=17&entity_type=reserva', 1, '2025-07-04 16:24:36'),
(215, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #18). Status: Documentos pendentes.', 'info', 1, '2025-07-04 16:52:49'),
(216, 23, 'Documentos Aprovados!', 'Os documentos da reserva #18 da unidade 399 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=18&entity_type=reserva', 0, '2025-07-04 16:53:51'),
(217, 1, 'Documentos Aprovados!', 'Os documentos da reserva #18 da unidade 399 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=18&entity_type=reserva', 1, '2025-07-04 16:53:51'),
(218, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #18. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=18&entity_type=reserva', 1, '2025-07-04 16:53:51'),
(219, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #18. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=18&entity_type=reserva', 1, '2025-07-04 16:53:51'),
(220, 23, 'Contrato Enviado!', 'Contrato da reserva #18 enviado manualmente. Aguarde assinatura do cliente Francisco Antunes de Souza.', 'event_type=contrato_enviado&entity_id=18&entity_type=reserva', 0, '2025-07-04 16:54:11'),
(221, 1, 'Contrato Enviado!', 'Contrato da reserva #18 enviado manualmente. Aguarde assinatura do cliente Francisco Antunes de Souza.', 'event_type=contrato_enviado&entity_id=18&entity_type=reserva', 1, '2025-07-04 16:54:11'),
(222, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #18 manualmente.', 'event_type=contrato_enviado&entity_id=18&entity_type=reserva', 1, '2025-07-04 16:54:11'),
(223, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #18 manualmente.', 'event_type=contrato_enviado&entity_id=18&entity_type=reserva', 1, '2025-07-04 16:54:11'),
(224, 23, 'Venda Concluída!', 'Sua venda para a reserva #18 da unidade 399 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=18&entity_type=reserva', 0, '2025-07-04 16:54:36'),
(225, 1, 'Venda Concluída!', 'Sua venda para a reserva #18 da unidade 399 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=18&entity_type=reserva', 1, '2025-07-04 16:54:36'),
(226, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #18.', 'event_type=venda_concluida&entity_id=18&entity_type=reserva', 1, '2025-07-04 16:54:36'),
(227, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #18.', 'event_type=venda_concluida&entity_id=18&entity_type=reserva', 1, '2025-07-04 16:54:36'),
(228, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Corretor Teste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=24&entity_type=usuario', 1, '2025-07-04 17:53:32'),
(229, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Corretor Teste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=24&entity_type=usuario', 1, '2025-07-04 17:53:32'),
(230, 3, 'Novo Corretor Cadastrado!', 'Um novo usuário (Corretor Teste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=24&entity_type=usuario', 0, '2025-07-04 17:53:32'),
(231, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Corretor Teste - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=24&entity_type=usuario', 1, '2025-07-04 17:53:32'),
(232, 24, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Teste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=24&entity_type=usuario&imobiliaria_id=2', 0, '2025-07-04 17:54:00'),
(233, 1, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Teste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=24&entity_type=usuario&imobiliaria_id=2', 1, '2025-07-04 17:54:00'),
(234, 3, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Teste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=24&entity_type=usuario&imobiliaria_id=2', 0, '2025-07-04 17:54:00'),
(235, 3, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Teste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=24&entity_type=usuario&imobiliaria_id=2', 0, '2025-07-04 17:54:00'),
(236, 1, 'Sua Conta Foi Aprovada!', 'O corretor Corretor Teste foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=24&entity_type=usuario&imobiliaria_id=2', 1, '2025-07-04 17:54:00'),
(237, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #19). Status: Documentos pendentes.', 'info', 1, '2025-07-04 17:55:26'),
(238, 24, 'Documentos Aprovados!', 'Os documentos da reserva #19 da unidade 389 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=19&entity_type=reserva', 0, '2025-07-04 17:56:58'),
(239, 1, 'Documentos Aprovados!', 'Os documentos da reserva #19 da unidade 389 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=19&entity_type=reserva', 1, '2025-07-04 17:56:58'),
(240, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #19. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=19&entity_type=reserva', 1, '2025-07-04 17:56:58'),
(241, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #19. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=19&entity_type=reserva', 1, '2025-07-04 17:56:58'),
(242, 24, 'Contrato Enviado!', 'Contrato da reserva #19 enviado manualmente. Aguarde assinatura do cliente Venda Corretor Francisco.', 'event_type=contrato_enviado&entity_id=19&entity_type=reserva', 0, '2025-07-04 17:57:18'),
(243, 1, 'Contrato Enviado!', 'Contrato da reserva #19 enviado manualmente. Aguarde assinatura do cliente Venda Corretor Francisco.', 'event_type=contrato_enviado&entity_id=19&entity_type=reserva', 1, '2025-07-04 17:57:18'),
(244, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #19 manualmente.', 'event_type=contrato_enviado&entity_id=19&entity_type=reserva', 1, '2025-07-04 17:57:18'),
(245, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #19 manualmente.', 'event_type=contrato_enviado&entity_id=19&entity_type=reserva', 1, '2025-07-04 17:57:18'),
(246, 24, 'Venda Concluída!', 'Sua venda para a reserva #19 da unidade 389 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=19&entity_type=reserva', 0, '2025-07-04 17:57:39'),
(247, 1, 'Venda Concluída!', 'Sua venda para a reserva #19 da unidade 389 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=19&entity_type=reserva', 1, '2025-07-04 17:57:39'),
(248, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #19.', 'event_type=venda_concluida&entity_id=19&entity_type=reserva', 1, '2025-07-04 17:57:39'),
(249, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #19.', 'event_type=venda_concluida&entity_id=19&entity_type=reserva', 1, '2025-07-04 17:57:39'),
(250, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Ulisses - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=25&entity_type=usuario', 1, '2025-07-04 19:54:53'),
(251, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Ulisses - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=25&entity_type=usuario', 1, '2025-07-04 19:54:53'),
(252, 3, 'Novo Corretor Cadastrado!', 'Um novo usuário (Ulisses - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=25&entity_type=usuario', 0, '2025-07-04 19:54:53'),
(253, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Ulisses - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=25&entity_type=usuario', 1, '2025-07-04 19:54:53'),
(254, 25, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=25&entity_type=usuario&imobiliaria_id=2', 1, '2025-07-04 19:55:24'),
(255, 1, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=25&entity_type=usuario&imobiliaria_id=2', 1, '2025-07-04 19:55:24'),
(256, 3, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=25&entity_type=usuario&imobiliaria_id=2', 0, '2025-07-04 19:55:24'),
(257, 3, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=25&entity_type=usuario&imobiliaria_id=2', 0, '2025-07-04 19:55:24'),
(258, 1, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=25&entity_type=usuario&imobiliaria_id=2', 1, '2025-07-04 19:55:24'),
(259, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #20). Status: Documentos pendentes.', 'info', 1, '2025-07-04 19:56:37'),
(260, 25, 'Documentos Aprovados!', 'Os documentos da reserva #20 da unidade 379 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=20&entity_type=reserva', 0, '2025-07-04 19:58:49'),
(261, 1, 'Documentos Aprovados!', 'Os documentos da reserva #20 da unidade 379 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=20&entity_type=reserva', 1, '2025-07-04 19:58:49'),
(262, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #20. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=20&entity_type=reserva', 1, '2025-07-04 19:58:49'),
(263, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #20. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=20&entity_type=reserva', 1, '2025-07-04 19:58:49'),
(264, 25, 'Contrato Enviado!', 'Contrato da reserva #20 enviado manualmente. Aguarde assinatura do cliente Francisco Antunes de Souza.', 'event_type=contrato_enviado&entity_id=20&entity_type=reserva', 0, '2025-07-04 19:59:49'),
(265, 1, 'Contrato Enviado!', 'Contrato da reserva #20 enviado manualmente. Aguarde assinatura do cliente Francisco Antunes de Souza.', 'event_type=contrato_enviado&entity_id=20&entity_type=reserva', 1, '2025-07-04 19:59:49'),
(266, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #20 manualmente.', 'event_type=contrato_enviado&entity_id=20&entity_type=reserva', 1, '2025-07-04 19:59:49'),
(267, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #20 manualmente.', 'event_type=contrato_enviado&entity_id=20&entity_type=reserva', 1, '2025-07-04 19:59:49'),
(268, 25, 'Venda Concluída!', 'Sua venda para a reserva #20 da unidade 379 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=20&entity_type=reserva', 0, '2025-07-04 20:00:11'),
(269, 1, 'Venda Concluída!', 'Sua venda para a reserva #20 da unidade 379 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=20&entity_type=reserva', 1, '2025-07-04 20:00:11'),
(270, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #20.', 'event_type=venda_concluida&entity_id=20&entity_type=reserva', 1, '2025-07-04 20:00:11'),
(271, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #20.', 'event_type=venda_concluida&entity_id=20&entity_type=reserva', 1, '2025-07-04 20:00:11'),
(272, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Ulisses Corretor - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=26&entity_type=usuario', 1, '2025-07-04 20:02:55'),
(273, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Ulisses Corretor - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=26&entity_type=usuario', 1, '2025-07-04 20:02:55'),
(274, 3, 'Novo Corretor Cadastrado!', 'Um novo usuário (Ulisses Corretor - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=26&entity_type=usuario', 0, '2025-07-04 20:02:55'),
(275, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Ulisses Corretor - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=26&entity_type=usuario', 1, '2025-07-04 20:02:55'),
(276, 26, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses Corretor foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=26&entity_type=usuario&imobiliaria_id=2', 0, '2025-07-04 20:03:24'),
(277, 1, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses Corretor foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=26&entity_type=usuario&imobiliaria_id=2', 1, '2025-07-04 20:03:24'),
(278, 3, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses Corretor foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=26&entity_type=usuario&imobiliaria_id=2', 0, '2025-07-04 20:03:24'),
(279, 3, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses Corretor foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=26&entity_type=usuario&imobiliaria_id=2', 0, '2025-07-04 20:03:24'),
(280, 1, 'Sua Conta Foi Aprovada!', 'O corretor Ulisses Corretor foi aprovado por Admin Imobiliária.', 'event_type=corretor_aprovado&entity_id=26&entity_type=usuario&imobiliaria_id=2', 1, '2025-07-04 20:03:24'),
(281, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova reserva foi feita para a Unidade  (Reserva #21). Status: Documentos pendentes.', 'info', 1, '2025-07-04 20:04:20'),
(282, 26, 'Documentos Aprovados!', 'Os documentos da reserva #21 da unidade 369 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=21&entity_type=reserva', 0, '2025-07-04 20:05:36'),
(283, 1, 'Documentos Aprovados!', 'Os documentos da reserva #21 da unidade 369 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=21&entity_type=reserva', 1, '2025-07-04 20:05:36'),
(284, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #21. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=21&entity_type=reserva', 1, '2025-07-04 20:05:36'),
(285, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #21. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=21&entity_type=reserva', 1, '2025-07-04 20:05:36'),
(286, 26, 'Contrato Enviado!', 'Contrato da reserva #21 enviado manualmente. Aguarde assinatura do cliente Francisco Antunes de Souza.', 'event_type=contrato_enviado&entity_id=21&entity_type=reserva', 0, '2025-07-04 20:06:12'),
(287, 1, 'Contrato Enviado!', 'Contrato da reserva #21 enviado manualmente. Aguarde assinatura do cliente Francisco Antunes de Souza.', 'event_type=contrato_enviado&entity_id=21&entity_type=reserva', 1, '2025-07-04 20:06:12'),
(288, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #21 manualmente.', 'event_type=contrato_enviado&entity_id=21&entity_type=reserva', 1, '2025-07-04 20:06:12'),
(289, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #21 manualmente.', 'event_type=contrato_enviado&entity_id=21&entity_type=reserva', 1, '2025-07-04 20:06:12'),
(290, 26, 'Venda Concluída!', 'Sua venda para a reserva #21 da unidade 369 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=21&entity_type=reserva', 0, '2025-07-04 20:06:39'),
(291, 1, 'Venda Concluída!', 'Sua venda para a reserva #21 da unidade 369 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=21&entity_type=reserva', 1, '2025-07-04 20:06:39'),
(292, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #21.', 'event_type=venda_concluida&entity_id=21&entity_type=reserva', 1, '2025-07-04 20:06:39'),
(293, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #21.', 'event_type=venda_concluida&entity_id=21&entity_type=reserva', 1, '2025-07-04 20:06:39'),
(294, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova solicitação de reserva foi feita para a Unidade  (Reserva #22). Status: Solicitada.', 'info', 1, '2025-07-05 03:01:12'),
(295, 1, 'Reserva Cancelada!', 'Sua reserva #22 para a unidade 102 foi rejeitada e cancelada. Motivo: . Contate o admin para detalhes.', 'event_type=reserva_cancelada&entity_id=22&entity_type=reserva', 1, '2025-07-05 16:00:34'),
(296, 1, 'Reserva Cancelada!', 'Você (Desconhecido) rejeitou a reserva #22.', 'event_type=reserva_cancelada&entity_id=22&entity_type=reserva', 1, '2025-07-05 16:00:34'),
(297, 1, 'Reserva Cancelada!', 'Você (Desconhecido) rejeitou a reserva #22.', 'event_type=reserva_cancelada&entity_id=22&entity_type=reserva', 1, '2025-07-05 16:00:34'),
(298, 27, 'Notificação do Sistema', 'O usuário Francisco AS (corretor_autonomo) foi criado.', 'event_type=novo_usuario_criado&entity_id=27&entity_type=usuario', 0, '2025-07-06 18:09:43'),
(299, 1, 'Notificação do Sistema', 'O usuário Francisco AS (corretor_autonomo) foi criado.', 'event_type=novo_usuario_criado&entity_id=27&entity_type=usuario', 1, '2025-07-06 18:09:43'),
(300, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco AS - Corretor autonomo) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=27&entity_type=usuario', 1, '2025-07-06 18:09:43'),
(301, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco AS - Corretor autonomo) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=27&entity_type=usuario', 1, '2025-07-06 18:09:43'),
(302, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=28&entity_type=usuario', 1, '2025-07-06 19:16:13'),
(303, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=28&entity_type=usuario', 1, '2025-07-06 19:16:13'),
(304, 3, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=28&entity_type=usuario', 0, '2025-07-06 19:16:13'),
(305, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=28&entity_type=usuario', 1, '2025-07-06 19:16:13');
INSERT INTO `alertas` (`id`, `usuario_id`, `titulo`, `mensagem`, `link`, `lido`, `data_criacao`) VALUES
(306, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=29&entity_type=usuario', 1, '2025-07-06 23:20:49'),
(307, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=29&entity_type=usuario', 1, '2025-07-06 23:20:49'),
(308, 3, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=29&entity_type=usuario', 0, '2025-07-06 23:20:49'),
(309, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor imobiliaria) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=29&entity_type=usuario', 1, '2025-07-06 23:20:49'),
(310, 1, 'Nova Reserva/Solicitação Recebida', 'Uma nova solicitação de reserva foi feita para a Unidade  (Reserva #25). Status: Solicitada.', 'info', 1, '2025-07-07 00:11:58'),
(311, 1, 'Reserva Aprovada!', 'Sua reserva #25 para a unidade 319 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=25&entity_type=reserva', 1, '2025-07-07 00:21:46'),
(312, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #25. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=25&entity_type=reserva', 1, '2025-07-07 00:21:46'),
(313, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #25. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=25&entity_type=reserva', 1, '2025-07-07 00:21:46'),
(314, 1, 'Documentos Aprovados!', 'Os documentos da reserva #25 da unidade 319 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=25&entity_type=reserva', 1, '2025-07-07 00:22:20'),
(315, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #25. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=25&entity_type=reserva', 1, '2025-07-07 00:22:20'),
(316, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #25. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=25&entity_type=reserva', 1, '2025-07-07 00:22:20'),
(317, 1, 'Notificação do Sistema', 'Imobiliária \'N/A\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=2&entity_type=imobiliaria', 1, '2025-07-07 01:11:36'),
(318, 1, 'Notificação do Sistema', 'Imobiliária \'N/A\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=2&entity_type=imobiliaria', 1, '2025-07-07 01:11:36'),
(319, 1, 'Notificação do Sistema', 'Imobiliária \'N/A\' excluída por Admin Master. Vínculos de corretores foram desfeitos.', 'event_type=imobiliaria_excluida&entity_id=2&entity_type=imobiliaria', 1, '2025-07-07 01:12:27'),
(320, 1, 'Notificação do Sistema', 'Imobiliária \'N/A\' excluída por Admin Master. Vínculos de corretores foram desfeitos.', 'event_type=imobiliaria_excluida&entity_id=2&entity_type=imobiliaria', 1, '2025-07-07 01:12:27'),
(321, 1, 'Notificação Importante', 'Imobiliária \'Editado\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:45:47'),
(322, 1, 'Notificação Importante', 'Imobiliária \'Editado\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:45:47'),
(323, 1, 'Notificação Importante', 'Imobiliária \'Editado 23\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:46:34'),
(324, 1, 'Notificação Importante', 'Imobiliária \'Editado 23\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:46:34'),
(325, 1, 'Notificação do Sistema', 'Imobiliária \'Editado 23\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:59:29'),
(326, 1, 'Notificação do Sistema', 'Imobiliária \'Editado 23\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:59:29'),
(327, 1, 'Notificação do Sistema', 'Imobiliária \'Editado 23\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:59:44'),
(328, 1, 'Notificação do Sistema', 'Imobiliária \'Editado 23\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:59:44'),
(329, 1, 'Notificação Importante', 'Imobiliária \'Imob Ativa\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:59:58'),
(330, 1, 'Notificação Importante', 'Imobiliária \'Imob Ativa\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 01:59:58'),
(331, 1, 'Notificação Importante', 'Imobiliária \'Imob Ativa2\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 02:00:27'),
(332, 1, 'Notificação Importante', 'Imobiliária \'Imob Ativa2\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=1&entity_type=imobiliaria', 1, '2025-07-07 02:00:27'),
(333, 1, 'Nova Imobiliária Cadastrada!', 'Nova imobiliária \'Prop Imob\' criada por Admin Master.', 'event_type=nova_imobiliaria&entity_id=3&entity_type=imobiliaria', 1, '2025-07-07 02:07:45'),
(334, 1, 'Nova Imobiliária Cadastrada!', 'Nova imobiliária \'Prop Imob\' criada por Admin Master.', 'event_type=nova_imobiliaria&entity_id=3&entity_type=imobiliaria', 1, '2025-07-07 02:07:45'),
(335, 30, 'Notificação do Sistema', 'O usuário Francisco Antunes de Souza (admin_imobiliaria) foi criado.', 'event_type=novo_usuario_criado&entity_id=30&entity_type=usuario', 0, '2025-07-07 02:37:03'),
(336, 1, 'Notificação do Sistema', 'O usuário Francisco Antunes de Souza (admin_imobiliaria) foi criado.', 'event_type=novo_usuario_criado&entity_id=30&entity_type=usuario', 1, '2025-07-07 02:37:03'),
(337, 1, 'Notificação Importante', 'Imobiliária \'Prop Imob 3\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=3&entity_type=imobiliaria', 1, '2025-07-08 01:45:43'),
(338, 1, 'Notificação Importante', 'Imobiliária \'Prop Imob 3\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=3&entity_type=imobiliaria', 1, '2025-07-08 01:45:43'),
(339, 1, 'Notificação do Sistema', 'Imobiliária \'Prop Imob 3\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=3&entity_type=imobiliaria', 1, '2025-07-08 01:46:01'),
(340, 1, 'Notificação do Sistema', 'Imobiliária \'Prop Imob 3\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=3&entity_type=imobiliaria', 1, '2025-07-08 01:46:01'),
(341, 1, 'Notificação do Sistema', 'Imobiliária \'Prop Imob 3\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=3&entity_type=imobiliaria', 1, '2025-07-08 01:46:04'),
(342, 1, 'Notificação do Sistema', 'Imobiliária \'Prop Imob 3\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=3&entity_type=imobiliaria', 1, '2025-07-08 01:46:04'),
(343, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Jonas Maciel - Corretor autonomo) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=31&entity_type=usuario', 1, '2025-07-08 03:19:42'),
(344, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Jonas Maciel - Corretor autonomo) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=31&entity_type=usuario', 1, '2025-07-08 03:19:42'),
(345, 1, 'Notificação do Sistema', 'Usuário criado com sucesso!', 'event_type=success', 1, '2025-07-08 10:17:15'),
(346, 23, 'Notificação do Sistema', 'O lead #12 foi atribuído a você.', 'event_type=novo_lead_atribuido&entity_id=12&entity_type=reserva', 0, '2025-07-08 15:22:12'),
(347, 1, 'Notificação do Sistema', 'O lead #12 foi atribuído a você.', 'event_type=novo_lead_atribuido&entity_id=12&entity_type=reserva', 1, '2025-07-08 15:22:12'),
(348, 1, 'Notificação do Sistema', 'Imobiliária \'Prop Imob 3\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=3&entity_type=imobiliaria', 1, '2025-07-09 00:28:36'),
(349, 1, 'Notificação do Sistema', 'Imobiliária \'Prop Imob 3\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=3&entity_type=imobiliaria', 1, '2025-07-09 00:28:36'),
(350, 1, 'Nova Imobiliária Cadastrada!', 'Nova imobiliária \'OJDFIGOJSPDGF\' criada por Admin Master.', 'event_type=nova_imobiliaria&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 00:29:14'),
(351, 1, 'Nova Imobiliária Cadastrada!', 'Nova imobiliária \'OJDFIGOJSPDGF\' criada por Admin Master.', 'event_type=nova_imobiliaria&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 00:29:14'),
(352, 1, 'Notificação Importante', 'Imobiliária \'OJDFIGOJSPDGF\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 00:29:30'),
(353, 1, 'Notificação Importante', 'Imobiliária \'OJDFIGOJSPDGF\' atualizada por Admin Master.', 'event_type=notificacao_geral&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 00:29:30'),
(354, 1, 'Notificação do Sistema', 'Imobiliária \'Prop Imob 3\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=3&entity_type=imobiliaria', 1, '2025-07-09 00:29:45'),
(355, 1, 'Notificação do Sistema', 'Imobiliária \'Prop Imob 3\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=3&entity_type=imobiliaria', 1, '2025-07-09 00:29:45'),
(356, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 00:34:01'),
(357, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 00:34:01'),
(358, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 00:34:04'),
(359, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 00:34:04'),
(360, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 01:02:14'),
(361, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 01:02:14'),
(362, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 01:02:17'),
(363, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 01:02:17'),
(364, 33, 'Notificação do Sistema', 'O usuário Francisco Antunes de Souza (corretor_autonomo) foi criado.', 'event_type=novo_usuario_criado&entity_id=33&entity_type=usuario', 0, '2025-07-09 01:02:52'),
(365, 1, 'Notificação do Sistema', 'O usuário Francisco Antunes de Souza (corretor_autonomo) foi criado.', 'event_type=novo_usuario_criado&entity_id=33&entity_type=usuario', 1, '2025-07-09 01:02:52'),
(366, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor autonomo) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=33&entity_type=usuario', 1, '2025-07-09 01:02:52'),
(367, 1, 'Novo Corretor Cadastrado!', 'Um novo usuário (Francisco Antunes de Souza - Corretor autonomo) aguarda aprovação.', 'event_type=novo_corretor_cadastro&entity_id=33&entity_type=usuario', 1, '2025-07-09 01:02:52'),
(368, 1, 'Contrato Enviado!', 'Contrato da reserva #25 enviado manualmente. Aguarde assinatura do cliente sdfsdf.', 'event_type=contrato_enviado&entity_id=25&entity_type=reserva', 1, '2025-07-09 01:06:30'),
(369, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #25 manualmente.', 'event_type=contrato_enviado&entity_id=25&entity_type=reserva', 1, '2025-07-09 01:06:30'),
(370, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #25 manualmente.', 'event_type=contrato_enviado&entity_id=25&entity_type=reserva', 1, '2025-07-09 01:06:30'),
(371, 1, 'Venda Concluída!', 'Sua venda para a reserva #25 da unidade 319 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=25&entity_type=reserva', 1, '2025-07-09 01:07:51'),
(372, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #25.', 'event_type=venda_concluida&entity_id=25&entity_type=reserva', 1, '2025-07-09 01:07:51'),
(373, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #25.', 'event_type=venda_concluida&entity_id=25&entity_type=reserva', 1, '2025-07-09 01:07:51'),
(374, 1, 'Venda Concluída!', 'Contrato da reserva #11 assinado (simulado). Venda finalizada!', 'event_type=venda_concluida&entity_id=11&entity_type=reserva', 1, '2025-07-09 01:21:38'),
(375, 1, 'Venda Concluída!', 'Você (Desconhecido) simulou a assinatura e finalizou a venda da reserva #11.', 'event_type=venda_concluida&entity_id=11&entity_type=reserva', 1, '2025-07-09 01:21:38'),
(376, 1, 'Venda Concluída!', 'Você (Desconhecido) simulou a assinatura e finalizou a venda da reserva #11.', 'event_type=venda_concluida&entity_id=11&entity_type=reserva', 1, '2025-07-09 01:21:38'),
(377, 1, 'Reserva Cancelada!', 'Sua reserva #14 para a unidade 304 foi rejeitada e cancelada. Motivo: . Contate o admin para detalhes.', 'event_type=reserva_cancelada&entity_id=14&entity_type=reserva', 1, '2025-07-09 01:43:02'),
(378, 1, 'Reserva Cancelada!', 'Você (Desconhecido) rejeitou a reserva #14.', 'event_type=reserva_cancelada&entity_id=14&entity_type=reserva', 1, '2025-07-09 01:43:02'),
(379, 1, 'Reserva Cancelada!', 'Você (Desconhecido) rejeitou a reserva #14.', 'event_type=reserva_cancelada&entity_id=14&entity_type=reserva', 1, '2025-07-09 01:43:02'),
(380, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #27). Status: Solicitada.', 'event_type=novo_lead&entity_id=27&entity_type=reserva', 1, '2025-07-09 10:35:26'),
(381, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #27). Status: Solicitada.', 'event_type=novo_lead&entity_id=27&entity_type=reserva', 1, '2025-07-09 10:35:26'),
(382, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #28). Status: Solicitada.', 'event_type=novo_lead&entity_id=28&entity_type=reserva', 1, '2025-07-09 10:38:25'),
(383, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #28). Status: Solicitada.', 'event_type=novo_lead&entity_id=28&entity_type=reserva', 1, '2025-07-09 10:38:25'),
(384, 1, 'Reserva Aprovada!', 'Sua reserva #28 para a unidade 313 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=28&entity_type=reserva', 1, '2025-07-09 10:46:30'),
(385, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #28. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=28&entity_type=reserva', 1, '2025-07-09 10:46:30'),
(386, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #28. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=28&entity_type=reserva', 1, '2025-07-09 10:46:30'),
(387, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #29). Status: Solicitada.', 'event_type=novo_lead&entity_id=29&entity_type=reserva', 1, '2025-07-09 10:53:29'),
(388, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #29). Status: Solicitada.', 'event_type=novo_lead&entity_id=29&entity_type=reserva', 1, '2025-07-09 10:53:29'),
(389, 1, 'Reserva Cancelada!', 'Sua reserva #27 para a unidade 312 foi rejeitada e cancelada. Motivo: . Contate o admin para detalhes.', 'event_type=reserva_cancelada&entity_id=27&entity_type=reserva', 1, '2025-07-09 11:08:13'),
(390, 1, 'Reserva Cancelada!', 'Você (Desconhecido) rejeitou a reserva #27.', 'event_type=reserva_cancelada&entity_id=27&entity_type=reserva', 1, '2025-07-09 11:08:13'),
(391, 1, 'Reserva Cancelada!', 'Você (Desconhecido) rejeitou a reserva #27.', 'event_type=reserva_cancelada&entity_id=27&entity_type=reserva', 1, '2025-07-09 11:08:13'),
(392, 1, 'Reserva Cancelada!', 'Sua reserva #29 para a unidade 314 foi rejeitada e cancelada. Motivo: . Contate o admin para detalhes.', 'event_type=reserva_cancelada&entity_id=29&entity_type=reserva', 1, '2025-07-09 11:08:40'),
(393, 1, 'Reserva Cancelada!', 'Você (Desconhecido) rejeitou a reserva #29.', 'event_type=reserva_cancelada&entity_id=29&entity_type=reserva', 1, '2025-07-09 11:08:40'),
(394, 1, 'Reserva Cancelada!', 'Você (Desconhecido) rejeitou a reserva #29.', 'event_type=reserva_cancelada&entity_id=29&entity_type=reserva', 1, '2025-07-09 11:08:40'),
(395, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #30). Status: Solicitada.', 'event_type=novo_lead&entity_id=30&entity_type=reserva', 1, '2025-07-09 14:14:53'),
(396, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #30). Status: Solicitada.', 'event_type=novo_lead&entity_id=30&entity_type=reserva', 1, '2025-07-09 14:14:53'),
(397, 1, 'Reserva Aprovada!', 'Sua reserva #30 para a unidade 314 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=30&entity_type=reserva', 1, '2025-07-09 14:15:26'),
(398, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #30. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=30&entity_type=reserva', 1, '2025-07-09 14:15:26'),
(399, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #30. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=30&entity_type=reserva', 1, '2025-07-09 14:15:26'),
(400, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #31). Status: Solicitada.', 'event_type=novo_lead&entity_id=31&entity_type=reserva', 1, '2025-07-09 14:45:20'),
(401, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #31). Status: Solicitada.', 'event_type=novo_lead&entity_id=31&entity_type=reserva', 1, '2025-07-09 14:45:20'),
(402, 1, 'Reserva Aprovada!', 'Sua reserva #31 para a unidade 315 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=31&entity_type=reserva', 1, '2025-07-09 14:46:57'),
(403, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #31. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=31&entity_type=reserva', 1, '2025-07-09 14:46:57'),
(404, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #31. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=31&entity_type=reserva', 1, '2025-07-09 14:46:57'),
(405, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #31 para o cliente.', 'event_type=solicitar_documentacao&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:17:34'),
(406, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #31 para o cliente.', 'event_type=solicitar_documentacao&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:17:34'),
(407, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #31 para o cliente.', 'event_type=solicitar_documentacao&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:18:26'),
(408, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #31 para o cliente.', 'event_type=solicitar_documentacao&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:18:26'),
(409, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #31 para o cliente.', 'event_type=solicitar_documentacao&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:18:51'),
(410, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #31 para o cliente.', 'event_type=solicitar_documentacao&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:18:51'),
(411, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #31 para o cliente.', 'event_type=solicitar_documentacao&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:19:22'),
(412, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #31 para o cliente.', 'event_type=solicitar_documentacao&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:19:22'),
(413, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #28 para o cliente.', 'event_type=solicitar_documentacao&entity_id=28&entity_type=reserva', 1, '2025-07-09 15:34:20'),
(414, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #28 para o cliente.', 'event_type=solicitar_documentacao&entity_id=28&entity_type=reserva', 1, '2025-07-09 15:34:20'),
(415, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #31 da unidade 107. Aguardam análise.', 'event_type=documentos_enviados&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:35:11'),
(416, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #31 da unidade 107. Aguardam análise.', 'event_type=documentos_enviados&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:35:11'),
(417, 1, 'Documentos Aprovados!', 'Os documentos da reserva #31 da unidade 315 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:35:39'),
(418, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #31. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:35:39'),
(419, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #31. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:35:39'),
(420, 1, 'Contrato Enviado!', 'Contrato da reserva #31 marcado como enviado manualmente. Aguarde assinatura.', 'event_type=contrato_enviado&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:36:04'),
(421, 1, 'Contrato Enviado!', 'Você (Desconhecido) marcou o contrato da reserva #31 como enviado.', 'event_type=contrato_enviado&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:36:04'),
(422, 1, 'Contrato Enviado!', 'Você (Desconhecido) marcou o contrato da reserva #31 como enviado.', 'event_type=contrato_enviado&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:36:04'),
(423, 1, 'Venda Concluída!', 'Sua venda para a reserva #31 da unidade 315 foi finalizada. Parabéns!', 'event_type=venda_concluida&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:36:16'),
(424, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #31.', 'event_type=venda_concluida&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:36:16'),
(425, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva #31.', 'event_type=venda_concluida&entity_id=31&entity_type=reserva', 1, '2025-07-09 15:36:16'),
(426, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #30 para o cliente.', 'event_type=solicitar_documentacao&entity_id=30&entity_type=reserva', 1, '2025-07-09 15:36:41'),
(427, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #30 para o cliente.', 'event_type=solicitar_documentacao&entity_id=30&entity_type=reserva', 1, '2025-07-09 15:36:41'),
(428, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #30 para o cliente.', 'event_type=solicitar_documentacao&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:02:37'),
(429, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #30 para o cliente.', 'event_type=solicitar_documentacao&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:02:37'),
(430, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #30 da unidade 106. Aguardam análise.', 'event_type=documentos_enviados&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:04:07'),
(431, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #30 da unidade 106. Aguardam análise.', 'event_type=documentos_enviados&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:04:07'),
(432, 1, 'Documentos Aprovados!', 'Os documentos da reserva #30 da unidade 314 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:04:39'),
(433, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #30. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:04:39'),
(434, 1, 'Documentos Aprovados!', 'Você (Desconhecido) aprovou todos os documentos da reserva #30. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:04:39'),
(435, 1, 'Contrato Enviado!', 'Contrato da reserva #30 enviado manualmente. Aguarde assinatura do cliente 12312323123 324234234.', 'event_type=contrato_enviado&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:05:11'),
(436, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #30 manualmente.', 'event_type=contrato_enviado&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:05:11'),
(437, 1, 'Contrato Enviado!', 'Você (Desconhecido) enviou o contrato da reserva #30 manualmente.', 'event_type=contrato_enviado&entity_id=30&entity_type=reserva', 1, '2025-07-09 16:05:11'),
(438, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #28 para o cliente.', 'event_type=solicitar_documentacao&entity_id=28&entity_type=reserva', 1, '2025-07-09 16:08:11'),
(439, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #28 para o cliente.', 'event_type=solicitar_documentacao&entity_id=28&entity_type=reserva', 1, '2025-07-09 16:08:11'),
(440, 1, 'Venda Concluída!', 'A venda da unidade 314 do empreendimento 4 foi finalizada!', 'event_type=venda_concluida&entity_id=30&entity_type=reserva', 1, '2025-07-09 17:00:22'),
(441, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva 30.', 'event_type=venda_concluida&entity_id=30&entity_type=reserva', 1, '2025-07-09 17:00:22'),
(442, 1, 'Venda Concluída!', 'Você (Desconhecido) finalizou a venda da reserva 30.', 'event_type=venda_concluida&entity_id=30&entity_type=reserva', 1, '2025-07-09 17:00:22'),
(443, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #32). Status: Solicitada.', 'event_type=novo_lead&entity_id=32&entity_type=reserva', 1, '2025-07-09 17:28:04'),
(444, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #32). Status: Solicitada.', 'event_type=novo_lead&entity_id=32&entity_type=reserva', 1, '2025-07-09 17:28:04'),
(445, 1, 'Reserva Aprovada!', 'Sua reserva #32 para a unidade 332 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=32&entity_type=reserva', 1, '2025-07-09 17:31:10'),
(446, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #32. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=32&entity_type=reserva', 1, '2025-07-09 17:31:10'),
(447, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #32. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=32&entity_type=reserva', 1, '2025-07-09 17:31:10'),
(448, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #32 para o cliente.', 'event_type=solicitar_documentacao&entity_id=32&entity_type=reserva', 1, '2025-07-09 17:31:20'),
(449, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #32 para o cliente.', 'event_type=solicitar_documentacao&entity_id=32&entity_type=reserva', 1, '2025-07-09 17:31:20'),
(450, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #32 da unidade 304. Aguardam análise.', 'event_type=documentos_enviados&entity_id=32&entity_type=reserva', 1, '2025-07-09 17:31:47'),
(451, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #32 da unidade 304. Aguardam análise.', 'event_type=documentos_enviados&entity_id=32&entity_type=reserva', 1, '2025-07-09 17:31:47'),
(452, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 17:34:14'),
(453, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 17:34:14'),
(454, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 17:34:17'),
(455, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=4&entity_type=imobiliaria', 1, '2025-07-09 17:34:17'),
(456, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' excluída por Admin Master. Vínculos de corretores foram desfeitos.', 'event_type=imobiliaria_excluida&entity_id=4&entity_type=imobiliaria', 1, '2025-07-10 00:36:27'),
(457, 1, 'Notificação do Sistema', 'Imobiliária \'OJDFIGOJSPDGF\' excluída por Admin Master. Vínculos de corretores foram desfeitos.', 'event_type=imobiliaria_excluida&entity_id=4&entity_type=imobiliaria', 1, '2025-07-10 00:36:27'),
(458, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #33). Status: Solicitada.', 'event_type=novo_lead&entity_id=33&entity_type=reserva', 1, '2025-07-10 10:04:39'),
(459, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #33). Status: Solicitada.', 'event_type=novo_lead&entity_id=33&entity_type=reserva', 1, '2025-07-10 10:04:39'),
(460, 1, 'Reserva Aprovada!', 'Sua reserva #33 para a unidade 400 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=33&entity_type=reserva', 1, '2025-07-10 10:06:49'),
(461, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #33. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=33&entity_type=reserva', 1, '2025-07-10 10:06:49'),
(462, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #33. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=33&entity_type=reserva', 1, '2025-07-10 10:06:49'),
(463, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #33 para o cliente.', 'event_type=solicitar_documentacao&entity_id=33&entity_type=reserva', 1, '2025-07-10 10:06:59'),
(464, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #33 para o cliente.', 'event_type=solicitar_documentacao&entity_id=33&entity_type=reserva', 1, '2025-07-10 10:06:59'),
(465, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #33 da unidade 1002. Aguardam análise.', 'event_type=documentos_enviados&entity_id=33&entity_type=reserva', 1, '2025-07-10 10:07:54'),
(466, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #33 da unidade 1002. Aguardam análise.', 'event_type=documentos_enviados&entity_id=33&entity_type=reserva', 1, '2025-07-10 10:07:54'),
(467, 1, 'Nova Reserva Criada!', 'Uma nova reserva foi feita para a Unidade  (Empreendimento: , Reserva #34). Status: Documentos pendentes.', 'event_type=nova_reserva&entity_id=34&entity_type=reserva', 1, '2025-07-10 10:45:24'),
(468, 1, 'Nova Reserva Criada!', 'Uma nova reserva foi feita para a Unidade  (Empreendimento: , Reserva #34). Status: Documentos pendentes.', 'event_type=nova_reserva&entity_id=34&entity_type=reserva', 1, '2025-07-10 10:45:24'),
(469, 23, 'Nova Reserva Criada!', 'Você efetuou uma nova reserva para a Unidade  (Empreendimento: , Reserva #34). Status: Documentos pendentes.', 'event_type=nova_reserva&entity_id=34&entity_type=reserva', 0, '2025-07-10 10:45:24'),
(470, 1, 'Nova Reserva Criada!', 'Você efetuou uma nova reserva para a Unidade  (Empreendimento: , Reserva #34). Status: Documentos pendentes.', 'event_type=nova_reserva&entity_id=34&entity_type=reserva', 1, '2025-07-10 10:45:24'),
(471, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #35). Status: Solicitada.', 'event_type=novo_lead&entity_id=35&entity_type=reserva', 1, '2025-07-10 10:46:57'),
(472, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade  (Empreendimento: , Reserva #35). Status: Solicitada.', 'event_type=novo_lead&entity_id=35&entity_type=reserva', 1, '2025-07-10 10:46:57'),
(473, 1, 'Nova Reserva Criada!', 'Uma nova reserva foi feita para a Unidade 903 (Empreendimento: Smart Living - Studios 2855, Reserva #36). Status: Documentos enviados.', 'event_type=nova_reserva&entity_id=36&entity_type=reserva', 1, '2025-07-10 10:53:26'),
(474, 1, 'Nova Reserva Criada!', 'Uma nova reserva foi feita para a Unidade 903 (Empreendimento: Smart Living - Studios 2855, Reserva #36). Status: Documentos enviados.', 'event_type=nova_reserva&entity_id=36&entity_type=reserva', 1, '2025-07-10 10:53:26'),
(475, 23, 'Nova Reserva Criada!', 'Você efetuou uma nova reserva para a Unidade 903 (Empreendimento: Smart Living - Studios 2855, Reserva #36). Status: Documentos enviados.', 'event_type=nova_reserva&entity_id=36&entity_type=reserva', 0, '2025-07-10 10:53:26'),
(476, 1, 'Nova Reserva Criada!', 'Você efetuou uma nova reserva para a Unidade 903 (Empreendimento: Smart Living - Studios 2855, Reserva #36). Status: Documentos enviados.', 'event_type=nova_reserva&entity_id=36&entity_type=reserva', 1, '2025-07-10 10:53:26'),
(477, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 802 (Empreendimento: Smart Living - Studios 2855, Reserva #37). Status: Solicitada.', 'event_type=novo_lead&entity_id=37&entity_type=reserva', 1, '2025-07-10 10:54:31'),
(478, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 802 (Empreendimento: Smart Living - Studios 2855, Reserva #37). Status: Solicitada.', 'event_type=novo_lead&entity_id=37&entity_type=reserva', 1, '2025-07-10 10:54:31'),
(479, 23, 'Documentação Necessária!', 'Documentos são necessários para a reserva #34. Acesse o link para upload e informe o cliente. Unidade: 390.', 'event_type=solicitar_documentacao&entity_id=34&entity_type=reserva', 0, '2025-07-10 11:08:38'),
(480, 1, 'Documentação Necessária!', 'Documentos são necessários para a reserva #34. Acesse o link para upload e informe o cliente. Unidade: 390.', 'event_type=solicitar_documentacao&entity_id=34&entity_type=reserva', 1, '2025-07-10 11:08:38'),
(481, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #34 para o cliente.', 'event_type=solicitar_documentacao&entity_id=34&entity_type=reserva', 1, '2025-07-10 11:08:38'),
(482, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #34 para o cliente.', 'event_type=solicitar_documentacao&entity_id=34&entity_type=reserva', 1, '2025-07-10 11:08:38'),
(483, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #34 da unidade 902. Aguardam análise.', 'event_type=documentos_enviados&entity_id=34&entity_type=reserva', 1, '2025-07-10 11:08:58'),
(484, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #34 da unidade 902. Aguardam análise.', 'event_type=documentos_enviados&entity_id=34&entity_type=reserva', 1, '2025-07-10 11:08:58'),
(485, 1, 'Reserva Aprovada!', 'Sua reserva #37 para a unidade 380 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=37&entity_type=reserva', 1, '2025-07-10 11:09:26'),
(486, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #37. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=37&entity_type=reserva', 1, '2025-07-10 11:09:26'),
(487, 1, 'Reserva Aprovada!', 'Você (Desconhecido) aprovou a reserva #37. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=37&entity_type=reserva', 1, '2025-07-10 11:09:26'),
(488, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #37 para o cliente.', 'event_type=solicitar_documentacao&entity_id=37&entity_type=reserva', 1, '2025-07-10 11:09:52'),
(489, 1, 'Documentação Necessária!', 'Você (Desconhecido) solicitou a documentação da reserva #37 para o cliente.', 'event_type=solicitar_documentacao&entity_id=37&entity_type=reserva', 1, '2025-07-10 11:09:52'),
(490, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #37 da unidade 802. Aguardam análise.', 'event_type=documentos_enviados&entity_id=37&entity_type=reserva', 1, '2025-07-10 11:10:06'),
(491, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #37 da unidade 802. Aguardam análise.', 'event_type=documentos_enviados&entity_id=37&entity_type=reserva', 1, '2025-07-10 11:10:06'),
(492, 1, 'Reserva Cancelada!', 'Sua reserva #33 para a unidade 1002 foi cancelada. Motivo: . Entre em contato com o admin para mais informações.', 'event_type=reserva_cancelada&entity_id=33&entity_type=reserva', 1, '2025-07-10 11:40:19'),
(493, 1, 'Reserva Cancelada!', 'Reserva #33 cancelada por Admin Master.', 'event_type=reserva_cancelada&entity_id=33&entity_type=reserva', 1, '2025-07-10 11:40:19'),
(494, 1, 'Reserva Cancelada!', 'Reserva #33 cancelada por Admin Master.', 'event_type=reserva_cancelada&entity_id=33&entity_type=reserva', 1, '2025-07-10 11:40:19'),
(495, 23, 'Reserva Cancelada!', 'Sua reserva #34 para a unidade 902 foi cancelada. Motivo: . Entre em contato com o admin para mais informações.', 'event_type=reserva_cancelada&entity_id=34&entity_type=reserva', 0, '2025-07-10 11:40:43'),
(496, 1, 'Reserva Cancelada!', 'Sua reserva #34 para a unidade 902 foi cancelada. Motivo: . Entre em contato com o admin para mais informações.', 'event_type=reserva_cancelada&entity_id=34&entity_type=reserva', 1, '2025-07-10 11:40:43'),
(497, 1, 'Reserva Cancelada!', 'Reserva #34 cancelada por Admin Master.', 'event_type=reserva_cancelada&entity_id=34&entity_type=reserva', 1, '2025-07-10 11:40:43'),
(498, 1, 'Reserva Cancelada!', 'Reserva #34 cancelada por Admin Master.', 'event_type=reserva_cancelada&entity_id=34&entity_type=reserva', 1, '2025-07-10 11:40:43'),
(499, 1, 'Reserva Cancelada!', 'Sua reserva #35 para a unidade 802 foi rejeitada e cancelada. Motivo: Testando. Contate o admin para detalhes.', 'event_type=reserva_cancelada&entity_id=35&entity_type=reserva', 1, '2025-07-10 12:43:55'),
(500, 1, 'Reserva Cancelada!', 'Você (Admin Master) rejeitou a reserva #35.', 'event_type=reserva_cancelada&entity_id=35&entity_type=reserva', 1, '2025-07-10 12:43:55'),
(501, 1, 'Reserva Cancelada!', 'Você (Admin Master) rejeitou a reserva #35.', 'event_type=reserva_cancelada&entity_id=35&entity_type=reserva', 1, '2025-07-10 12:43:55'),
(502, 1, 'Reserva Cancelada!', 'Sua reserva #28 para a unidade 105 foi cancelada. Motivo: . Entre em contato com o admin para mais informações.', 'event_type=reserva_cancelada&entity_id=28&entity_type=reserva', 1, '2025-07-10 13:49:45'),
(503, 1, 'Reserva Cancelada!', 'Reserva #28 cancelada por Admin Master.', 'event_type=reserva_cancelada&entity_id=28&entity_type=reserva', 1, '2025-07-10 13:49:45'),
(504, 1, 'Reserva Cancelada!', 'Reserva #28 cancelada por Admin Master.', 'event_type=reserva_cancelada&entity_id=28&entity_type=reserva', 1, '2025-07-10 13:49:45'),
(505, 1, 'Documentos Aprovados!', 'Os documentos da reserva #37 da unidade 802 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=37&entity_type=reserva', 1, '2025-07-10 14:40:49'),
(506, 1, 'Documentos Aprovados!', 'Você (Admin Master) aprovou todos os documentos da reserva #37. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=37&entity_type=reserva', 1, '2025-07-10 14:40:49'),
(507, 1, 'Documentos Aprovados!', 'Você (Admin Master) aprovou todos os documentos da reserva #37. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=37&entity_type=reserva', 1, '2025-07-10 14:40:49'),
(508, 1, 'Contrato Enviado!', 'Contrato da reserva #37 marcado como enviado manualmente. Aguarde assinatura.', 'event_type=contrato_enviado&entity_id=37&entity_type=reserva', 1, '2025-07-10 14:41:05'),
(509, 1, 'Contrato Enviado!', 'Você (Admin Master) marcou o contrato da reserva #37 como enviado.', 'event_type=contrato_enviado&entity_id=37&entity_type=reserva', 1, '2025-07-10 14:41:05'),
(510, 1, 'Contrato Enviado!', 'Você (Admin Master) marcou o contrato da reserva #37 como enviado.', 'event_type=contrato_enviado&entity_id=37&entity_type=reserva', 1, '2025-07-10 14:41:05'),
(511, 1, 'Venda Concluída!', 'A venda da unidade 802 do empreendimento Smart Living - Studios 2855 foi finalizada!', 'event_type=venda_concluida&entity_id=37&entity_type=reserva', 1, '2025-07-10 14:41:18'),
(512, 1, 'Venda Concluída!', 'Você (Admin Master) finalizou a venda da reserva 37.', 'event_type=venda_concluida&entity_id=37&entity_type=reserva', 1, '2025-07-10 14:41:18'),
(513, 1, 'Venda Concluída!', 'Você (Admin Master) finalizou a venda da reserva 37.', 'event_type=venda_concluida&entity_id=37&entity_type=reserva', 1, '2025-07-10 14:41:18'),
(514, 23, 'Documentos Rejeitados!', 'Um documento da reserva #36 da unidade 903 foi rejeitado. Verifique o motivo no sistema: RG e CPF ou CNH', 'event_type=documentos_rejeitados&entity_id=36&entity_type=reserva', 0, '2025-07-10 14:41:51'),
(515, 1, 'Documentos Rejeitados!', 'Um documento da reserva #36 da unidade 903 foi rejeitado. Verifique o motivo no sistema: RG e CPF ou CNH', 'event_type=documentos_rejeitados&entity_id=36&entity_type=reserva', 1, '2025-07-10 14:41:51'),
(516, 1, 'Documentos Rejeitados!', 'Você (Admin Master) rejeitou um documento da reserva #36.', 'event_type=documentos_rejeitados&entity_id=36&entity_type=reserva', 1, '2025-07-10 14:41:51'),
(517, 1, 'Documentos Rejeitados!', 'Você (Admin Master) rejeitou um documento da reserva #36.', 'event_type=documentos_rejeitados&entity_id=36&entity_type=reserva', 1, '2025-07-10 14:41:51'),
(518, 23, 'Documentos Rejeitados!', 'Um documento da reserva #36 da unidade 903 foi rejeitado. Verifique o motivo no sistema: Comprovante de Endereço', 'event_type=documentos_rejeitados&entity_id=36&entity_type=reserva', 0, '2025-07-10 14:41:59'),
(519, 1, 'Documentos Rejeitados!', 'Um documento da reserva #36 da unidade 903 foi rejeitado. Verifique o motivo no sistema: Comprovante de Endereço', 'event_type=documentos_rejeitados&entity_id=36&entity_type=reserva', 1, '2025-07-10 14:41:59'),
(520, 1, 'Documentos Rejeitados!', 'Você (Admin Master) rejeitou um documento da reserva #36.', 'event_type=documentos_rejeitados&entity_id=36&entity_type=reserva', 1, '2025-07-10 14:41:59'),
(521, 1, 'Documentos Rejeitados!', 'Você (Admin Master) rejeitou um documento da reserva #36.', 'event_type=documentos_rejeitados&entity_id=36&entity_type=reserva', 1, '2025-07-10 14:41:59'),
(522, 23, 'Documentação Necessária!', 'Documentos são necessários para a reserva #36. Acesse o link para upload e informe o cliente. Unidade: 903.', 'event_type=solicitar_documentacao&entity_id=36&entity_type=reserva', 0, '2025-07-10 14:42:31'),
(523, 1, 'Documentação Necessária!', 'Documentos são necessários para a reserva #36. Acesse o link para upload e informe o cliente. Unidade: 903.', 'event_type=solicitar_documentacao&entity_id=36&entity_type=reserva', 1, '2025-07-10 14:42:31'),
(524, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #36 para o cliente.', 'event_type=solicitar_documentacao&entity_id=36&entity_type=reserva', 1, '2025-07-10 14:42:31'),
(525, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #36 para o cliente.', 'event_type=solicitar_documentacao&entity_id=36&entity_type=reserva', 1, '2025-07-10 14:42:31'),
(526, 1, 'Nova Reserva Criada!', 'Uma nova reserva foi feita para a Unidade 902 (Empreendimento: Smart Living - Studios 2855, Reserva #38). Status: Solicitada.', 'event_type=nova_reserva&entity_id=38&entity_type=reserva', 1, '2025-07-10 15:19:04'),
(527, 1, 'Nova Reserva Criada!', 'Uma nova reserva foi feita para a Unidade 902 (Empreendimento: Smart Living - Studios 2855, Reserva #38). Status: Solicitada.', 'event_type=nova_reserva&entity_id=38&entity_type=reserva', 1, '2025-07-10 15:19:04'),
(528, 1, 'Reserva Aprovada!', 'Sua reserva #38 para a unidade 902 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=38&entity_type=reserva', 1, '2025-07-10 15:19:27'),
(529, 1, 'Reserva Aprovada!', 'Você (Admin Master) aprovou a reserva #38. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=38&entity_type=reserva', 1, '2025-07-10 15:19:27'),
(530, 1, 'Reserva Aprovada!', 'Você (Admin Master) aprovou a reserva #38. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=38&entity_type=reserva', 1, '2025-07-10 15:19:27'),
(531, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #38 para o cliente.', 'event_type=solicitar_documentacao&entity_id=38&entity_type=reserva', 1, '2025-07-10 15:19:31'),
(532, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #38 para o cliente.', 'event_type=solicitar_documentacao&entity_id=38&entity_type=reserva', 1, '2025-07-10 15:19:31'),
(533, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 1002 (Empreendimento: Smart Living - Studios 2855, Reserva #39). Status: Solicitada.', 'event_type=novo_lead&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:21:01'),
(534, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 1002 (Empreendimento: Smart Living - Studios 2855, Reserva #39). Status: Solicitada.', 'event_type=novo_lead&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:21:01'),
(535, 1, 'Reserva Aprovada!', 'Sua reserva #39 para a unidade 1002 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:28:39'),
(536, 1, 'Reserva Aprovada!', 'Você (Admin Master) aprovou a reserva #39. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:28:39'),
(537, 1, 'Reserva Aprovada!', 'Você (Admin Master) aprovou a reserva #39. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:28:39'),
(538, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #39 para o cliente.', 'event_type=solicitar_documentacao&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:28:43'),
(539, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #39 para o cliente.', 'event_type=solicitar_documentacao&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:28:43'),
(540, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #39 da unidade 1002. Aguardam análise.', 'event_type=documentos_enviados&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:30:43'),
(541, 1, 'Documentos de Reserva Enviados!', 'Documentos enviados para a Reserva #39 da unidade 1002. Aguardam análise.', 'event_type=documentos_enviados&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:30:43'),
(542, 1, 'Documentos Aprovados!', 'Os documentos da reserva #39 da unidade 1002 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:31:27'),
(543, 1, 'Documentos Aprovados!', 'Você (Admin Master) aprovou todos os documentos da reserva #39. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:31:27'),
(544, 1, 'Documentos Aprovados!', 'Você (Admin Master) aprovou todos os documentos da reserva #39. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:31:27'),
(545, 1, 'Contrato Enviado!', 'Contrato da reserva #39 marcado como enviado manualmente. Aguarde assinatura.', 'event_type=contrato_enviado&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:31:46'),
(546, 1, 'Contrato Enviado!', 'Você (Admin Master) marcou o contrato da reserva #39 como enviado.', 'event_type=contrato_enviado&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:31:46');
INSERT INTO `alertas` (`id`, `usuario_id`, `titulo`, `mensagem`, `link`, `lido`, `data_criacao`) VALUES
(547, 1, 'Contrato Enviado!', 'Você (Admin Master) marcou o contrato da reserva #39 como enviado.', 'event_type=contrato_enviado&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:31:46'),
(548, 1, 'Venda Concluída!', 'A venda da unidade 1002 do empreendimento Smart Living - Studios 2855 foi finalizada!', 'event_type=venda_concluida&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:31:55'),
(549, 1, 'Venda Concluída!', 'Você (Admin Master) finalizou a venda da reserva 39.', 'event_type=venda_concluida&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:31:55'),
(550, 1, 'Venda Concluída!', 'Você (Admin Master) finalizou a venda da reserva 39.', 'event_type=venda_concluida&entity_id=39&entity_type=reserva', 1, '2025-07-10 15:31:55'),
(551, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 1003 (Empreendimento: Smart Living - Studios 2855, Reserva #40). Status: Solicitada.', 'event_type=novo_lead&entity_id=40&entity_type=reserva', 1, '2025-07-10 15:32:49'),
(552, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 1003 (Empreendimento: Smart Living - Studios 2855, Reserva #40). Status: Solicitada.', 'event_type=novo_lead&entity_id=40&entity_type=reserva', 1, '2025-07-10 15:32:49'),
(553, 1, 'Nova Reserva Criada!', 'Uma nova reserva foi feita para a Unidade 1004 (Empreendimento: Smart Living - Studios 2855, Reserva #41). Status: Documentos enviados.', 'event_type=nova_reserva&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:34:25'),
(554, 1, 'Nova Reserva Criada!', 'Uma nova reserva foi feita para a Unidade 1004 (Empreendimento: Smart Living - Studios 2855, Reserva #41). Status: Documentos enviados.', 'event_type=nova_reserva&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:34:25'),
(555, 23, 'Nova Reserva Criada!', 'Você efetuou uma nova reserva para a Unidade 1004 (Empreendimento: Smart Living - Studios 2855, Reserva #41). Status: Documentos enviados.', 'event_type=nova_reserva&entity_id=41&entity_type=reserva', 0, '2025-07-10 15:34:25'),
(556, 1, 'Nova Reserva Criada!', 'Você efetuou uma nova reserva para a Unidade 1004 (Empreendimento: Smart Living - Studios 2855, Reserva #41). Status: Documentos enviados.', 'event_type=nova_reserva&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:34:25'),
(557, 23, 'Documentos Aprovados!', 'Os documentos da reserva #41 da unidade 1004 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=41&entity_type=reserva', 0, '2025-07-10 15:35:08'),
(558, 1, 'Documentos Aprovados!', 'Os documentos da reserva #41 da unidade 1004 foram aprovados. Prossiga para o contrato!', 'event_type=documentos_aprovados&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:35:08'),
(559, 1, 'Documentos Aprovados!', 'Você (Admin Master) aprovou todos os documentos da reserva #41. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:35:08'),
(560, 1, 'Documentos Aprovados!', 'Você (Admin Master) aprovou todos os documentos da reserva #41. Status da reserva atualizado.', 'event_type=documentos_aprovados&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:35:08'),
(561, 23, 'Contrato Enviado!', 'Contrato da reserva #41 marcado como enviado manualmente. Aguarde assinatura.', 'event_type=contrato_enviado&entity_id=41&entity_type=reserva', 0, '2025-07-10 15:35:32'),
(562, 1, 'Contrato Enviado!', 'Contrato da reserva #41 marcado como enviado manualmente. Aguarde assinatura.', 'event_type=contrato_enviado&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:35:32'),
(563, 1, 'Contrato Enviado!', 'Você (Admin Master) marcou o contrato da reserva #41 como enviado.', 'event_type=contrato_enviado&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:35:32'),
(564, 1, 'Contrato Enviado!', 'Você (Admin Master) marcou o contrato da reserva #41 como enviado.', 'event_type=contrato_enviado&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:35:32'),
(565, 23, 'Venda Concluída!', 'A venda da unidade 1004 do empreendimento Smart Living - Studios 2855 foi finalizada!', 'event_type=venda_concluida&entity_id=41&entity_type=reserva', 0, '2025-07-10 15:35:42'),
(566, 1, 'Venda Concluída!', 'A venda da unidade 1004 do empreendimento Smart Living - Studios 2855 foi finalizada!', 'event_type=venda_concluida&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:35:42'),
(567, 1, 'Venda Concluída!', 'Você (Admin Master) finalizou a venda da reserva 41.', 'event_type=venda_concluida&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:35:42'),
(568, 1, 'Venda Concluída!', 'Você (Admin Master) finalizou a venda da reserva 41.', 'event_type=venda_concluida&entity_id=41&entity_type=reserva', 1, '2025-07-10 15:35:42'),
(569, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 1003 (Empreendimento: Smart Living - Studios 2855, Reserva #42). Status: Solicitada.', 'event_type=novo_lead&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:36:07'),
(570, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 1003 (Empreendimento: Smart Living - Studios 2855, Reserva #42). Status: Solicitada.', 'event_type=novo_lead&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:36:07'),
(571, 1, 'Reserva Aprovada!', 'Sua reserva #42 para a unidade 1003 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:36:17'),
(572, 1, 'Reserva Aprovada!', 'Você (Admin Master) aprovou a reserva #42. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:36:17'),
(573, 1, 'Reserva Aprovada!', 'Você (Admin Master) aprovou a reserva #42. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:36:17'),
(574, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #42 para o cliente.', 'event_type=solicitar_documentacao&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:36:26'),
(575, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #42 para o cliente.', 'event_type=solicitar_documentacao&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:36:26'),
(576, 1, 'Reserva Cancelada!', 'Sua reserva #42 para a unidade 1003 foi cancelada. Motivo: . Entre em contato com o admin para mais informações.', 'event_type=reserva_cancelada&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:40:10'),
(577, 1, 'Reserva Cancelada!', 'Reserva #42 cancelada por Admin Master.', 'event_type=reserva_cancelada&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:40:10'),
(578, 1, 'Reserva Cancelada!', 'Reserva #42 cancelada por Admin Master.', 'event_type=reserva_cancelada&entity_id=42&entity_type=reserva', 1, '2025-07-10 15:40:10'),
(579, 1, 'Reserva Aprovada!', 'Sua reserva #40 para a unidade 1003 foi aprovada. Prossiga com o processo.', 'event_type=reserva_aprovada&entity_id=40&entity_type=reserva', 1, '2025-07-10 15:40:24'),
(580, 1, 'Reserva Aprovada!', 'Você (Admin Master) aprovou a reserva #40. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=40&entity_type=reserva', 1, '2025-07-10 15:40:24'),
(581, 1, 'Reserva Aprovada!', 'Você (Admin Master) aprovou a reserva #40. Status: documentos_pendentes.', 'event_type=reserva_aprovada&entity_id=40&entity_type=reserva', 1, '2025-07-10 15:40:24'),
(582, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #40 para o cliente.', 'event_type=solicitar_documentacao&entity_id=40&entity_type=reserva', 1, '2025-07-10 15:40:28'),
(583, 1, 'Documentação Necessária!', 'Você (Admin Master) solicitou a documentação da reserva #40 para o cliente.', 'event_type=solicitar_documentacao&entity_id=40&entity_type=reserva', 1, '2025-07-10 15:40:28'),
(584, 1, 'Nova Imobiliária Cadastrada!', 'Nova imobiliária \'Imobiliria XXX\' criada por Admin Master.', 'event_type=nova_imobiliaria&entity_id=5&entity_type=imobiliaria', 1, '2025-07-10 15:41:13'),
(585, 1, 'Nova Imobiliária Cadastrada!', 'Nova imobiliária \'Imobiliria XXX\' criada por Admin Master.', 'event_type=nova_imobiliaria&entity_id=5&entity_type=imobiliaria', 1, '2025-07-10 15:41:13'),
(586, 1, 'Notificação do Sistema', 'Imobiliária \'Imobiliria XXX\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=5&entity_type=imobiliaria', 1, '2025-07-10 15:41:17'),
(587, 1, 'Notificação do Sistema', 'Imobiliária \'Imobiliria XXX\' inativada por Admin Master. Motivo: Motivo não especificado.', 'event_type=imobiliaria_inativada&entity_id=5&entity_type=imobiliaria', 1, '2025-07-10 15:41:17'),
(588, 1, 'Notificação do Sistema', 'Imobiliária \'Imobiliria XXX\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=5&entity_type=imobiliaria', 1, '2025-07-10 15:41:20'),
(589, 1, 'Notificação do Sistema', 'Imobiliária \'Imobiliria XXX\' ativada por Admin Master.', 'event_type=imobiliaria_ativada&entity_id=5&entity_type=imobiliaria', 1, '2025-07-10 15:41:20'),
(590, 34, 'Notificação do Sistema', 'O usuário Admin Imob XXX (admin_imobiliaria) foi criado.', 'event_type=novo_usuario_criado&entity_id=34&entity_type=usuario', 1, '2025-07-10 15:42:29'),
(591, 1, 'Notificação do Sistema', 'O usuário Admin Imob XXX (admin_imobiliaria) foi criado.', 'event_type=novo_usuario_criado&entity_id=34&entity_type=usuario', 1, '2025-07-10 15:42:29'),
(592, 34, 'Notificação Importante', 'Você foi definido como administrador da nova imobiliária: \'Imob3\'.', 'event_type=notificacao_geral&entity_id=6&entity_type=imobiliaria', 1, '2025-07-10 15:44:33'),
(593, 1, 'Notificação Importante', 'Você foi definido como administrador da nova imobiliária: \'Imob3\'.', 'event_type=notificacao_geral&entity_id=6&entity_type=imobiliaria', 1, '2025-07-10 15:44:33'),
(594, 1, 'Nova Imobiliária Cadastrada!', 'Nova imobiliária \'Imob3\' criada por Admin Master.', 'event_type=nova_imobiliaria&entity_id=6&entity_type=imobiliaria', 1, '2025-07-10 15:44:33'),
(595, 1, 'Nova Imobiliária Cadastrada!', 'Nova imobiliária \'Imob3\' criada por Admin Master.', 'event_type=nova_imobiliaria&entity_id=6&entity_type=imobiliaria', 1, '2025-07-10 15:44:33'),
(596, 1, 'Notificação do Sistema', 'Um novo corretor (Corretor 3 - Corretor imobiliaria) foi adicionado por Admin Imob XXX (Imobiliria XXX).', 'event_type=novo_usuario_criado&entity_id=35&entity_type=usuario', 1, '2025-07-10 15:45:44'),
(597, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 904 (Empreendimento: Smart Living - Studios 2855, Reserva #43). Status: Solicitada.', 'event_type=novo_lead&entity_id=43&entity_type=reserva', 0, '2025-07-10 16:38:39'),
(598, 1, 'Novo Lead Recebido!', 'Uma nova solicitação de reserva foi feita para a Unidade 904 (Empreendimento: Smart Living - Studios 2855, Reserva #43). Status: Solicitada.', 'event_type=novo_lead&entity_id=43&entity_type=reserva', 0, '2025-07-10 16:38:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `areas_comuns_catalogo`
--

CREATE TABLE `areas_comuns_catalogo` (
  `id` int NOT NULL,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `areas_comuns_catalogo`
--

INSERT INTO `areas_comuns_catalogo` (`id`, `nome`, `data_cadastro`) VALUES
(1, 'Piscina', '2025-07-04 18:10:42'),
(2, 'Academia', '2025-07-04 18:10:42'),
(3, 'Salão de Festas', '2025-07-04 18:10:42'),
(4, 'Churrasqueira', '2025-07-04 18:10:42'),
(5, 'Playground', '2025-07-04 18:10:42'),
(6, 'Portaria 24h', '2025-07-04 18:10:42'),
(7, 'Elevador', '2025-07-04 18:10:42'),
(8, 'Espaço Gourmet', '2025-07-04 18:10:42'),
(9, 'Quadra Poliesportiva', '2025-07-04 18:10:42'),
(10, 'Pet Place', '2025-07-04 18:10:42'),
(11, 'Coworking', '2025-07-04 18:10:42'),
(12, 'Bicicletário', '2025-07-04 18:10:42'),
(13, 'Brinquedoteca', '2025-07-04 18:10:42'),
(14, 'Sauna', '2025-07-04 18:10:42'),
(15, 'Spa', '2025-07-04 18:10:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria`
--

CREATE TABLE `auditoria` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `acao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidade` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidade_id` int DEFAULT NULL,
  `tabela` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registro_id` int DEFAULT NULL,
  `data_acao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `detalhes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_origem` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `acao`, `entidade`, `entidade_id`, `tabela`, `registro_id`, `data_acao`, `detalhes`, `ip_origem`) VALUES
(1, 1, 'Login bem-sucedido', '', NULL, 'usuarios', 1, '2025-07-01 22:07:54', NULL, NULL),
(2, 1, 'Criação de Empreendimento', '', NULL, 'empreendimentos', 1, '2025-07-01 22:07:54', NULL, NULL),
(3, 2, 'Atualização de Reserva', '', NULL, 'reservas', 3, '2025-07-01 22:07:54', NULL, NULL),
(4, 1, 'Finalizar Venda (Simulado)', 'Reserva', 5, NULL, NULL, '2025-07-03 20:48:39', 'Venda simulada para a reserva ID 5. Unidade ID 8 marcada como vendida.', '127.0.0.1'),
(5, 1, 'Inativar imobiliaria Imobiliária', '0', 2, NULL, NULL, '2025-07-03 22:27:49', 'Imobiliária \'EDTADO\' inativada com sucesso.', '::1'),
(6, 1, 'Ativar imobiliaria Imobiliária', '0', 2, NULL, NULL, '2025-07-03 22:30:57', 'Imobiliária \'EDTADO\' ativada com sucesso.', '::1'),
(7, 1, 'Aprovar Reserva', 'Reserva', 10, NULL, NULL, '2025-07-04 11:56:25', '0', '::1'),
(8, 1, 'Aprovar Documento', 'Documento_Reserva', 6, NULL, NULL, '2025-07-04 12:01:39', '0', '::1'),
(9, 1, 'Aprovar Documento', 'Documento_Reserva', 7, NULL, NULL, '2025-07-04 12:01:46', '0', '::1'),
(10, 1, 'Aprovar Documento', 'Documento_Reserva', 8, NULL, NULL, '2025-07-04 12:40:07', '0', '::1'),
(11, 1, 'Aprovar Documento', 'Documento_Reserva', 9, NULL, NULL, '2025-07-04 12:40:11', '0', '::1'),
(12, 1, 'Aprovar Documento', 'Documento_Reserva', 10, NULL, NULL, '2025-07-04 12:40:13', '0', '::1'),
(13, 1, 'Aprovar Documento', 'Documento_Reserva', 11, NULL, NULL, '2025-07-04 12:56:09', '0', '::1'),
(14, 1, 'Aprovar Documento', 'Documento_Reserva', 12, NULL, NULL, '2025-07-04 12:56:16', '0', '::1'),
(15, 1, 'Aprovar Documento', 'Documento_Reserva', 13, NULL, NULL, '2025-07-04 12:56:22', '0', '::1'),
(16, 1, 'Aprovar Documento', 'Documento_Reserva', 14, NULL, NULL, '2025-07-04 13:11:15', '0', '::1'),
(17, 1, 'Aprovar Documento', 'Documento_Reserva', 15, NULL, NULL, '2025-07-04 13:11:18', '0', '::1'),
(18, 1, 'Aprovar Documento', 'Documento_Reserva', 16, NULL, NULL, '2025-07-04 13:11:21', '0', '::1'),
(19, 1, 'Aprovar Documento', 'Documento_Reserva', 17, NULL, NULL, '2025-07-04 14:28:34', '0', '::1'),
(20, 1, 'Aprovar Documento', 'Documento_Reserva', 18, NULL, NULL, '2025-07-04 14:28:39', '0', '::1'),
(21, 1, 'Marcar Contrato como Enviado (Manual)', 'Reserva', 15, NULL, NULL, '2025-07-04 15:49:19', 'Contrato da Reserva #15 marcado como enviado manualmente por Desconhecido.', '::1'),
(22, 1, 'Finalizar Venda', 'Reserva', 15, NULL, NULL, '2025-07-04 15:50:21', 'Venda para a reserva #15 finalizada por Desconhecido. Unidade #306 marcada como \'vendida\'.', '::1'),
(23, 1, 'Marcar Contrato como Enviado (Manual)', 'Reserva', 11, NULL, NULL, '2025-07-04 15:55:44', 'Contrato da Reserva #11 marcado como enviado manualmente por Desconhecido.', '::1'),
(24, 1, 'Aprovar Documento', 'Documento_Reserva', 19, NULL, NULL, '2025-07-04 16:07:42', 'Documento \'RG e CPF ou CNH\' da reserva #16 aprovado por Desconhecido.', '::1'),
(25, 1, 'Aprovar Documento', 'Documento_Reserva', 20, NULL, NULL, '2025-07-04 16:07:45', 'Documento \'Comprovante de Endereço\' da reserva #16 aprovado por Desconhecido.', '::1'),
(26, 1, 'Enviar Contrato', 'Reserva', 16, NULL, NULL, '2025-07-04 16:08:07', 'Contrato da Reserva #16 enviado por Desconhecido via manual. Caminho: uploads/contratos/contrato_16_6867fc672d4f2.pdf.', '::1'),
(27, 1, 'Finalizar Venda', 'Reserva', 16, NULL, NULL, '2025-07-04 16:08:25', 'Venda para a reserva #16 finalizada por Desconhecido. Unidade #307 marcada como \'vendida\'.', '::1'),
(28, 1, 'Aprovar Documento', 'Documento_Reserva', 21, NULL, NULL, '2025-07-04 16:23:47', 'Documento \'RG e CPF ou CNH\' da reserva #17 aprovado por Desconhecido.', '::1'),
(29, 1, 'Aprovar Documento', 'Documento_Reserva', 22, NULL, NULL, '2025-07-04 16:23:53', 'Documento \'Comprovante de Endereço\' da reserva #17 aprovado por Desconhecido.', '::1'),
(30, 1, 'Enviar Contrato', 'Reserva', 17, NULL, NULL, '2025-07-04 16:24:15', 'Contrato da Reserva #17 enviado por Desconhecido via manual. Caminho: uploads/contratos/contrato_17_6868002f0b225.pdf.', '::1'),
(31, 1, 'Finalizar Venda', 'Reserva', 17, NULL, NULL, '2025-07-04 16:24:36', 'Venda para a reserva #17 finalizada por Desconhecido. Unidade #408 marcada como \'vendida\'.', '::1'),
(32, 1, 'Aprovar Documento', 'Documento_Reserva', 23, NULL, NULL, '2025-07-04 16:53:47', 'Documento \'RG e CPF ou CNH\' da reserva #18 aprovado por Desconhecido.', '::1'),
(33, 1, 'Aprovar Documento', 'Documento_Reserva', 24, NULL, NULL, '2025-07-04 16:53:51', 'Documento \'Comprovante de Endereço\' da reserva #18 aprovado por Desconhecido.', '::1'),
(34, 1, 'Enviar Contrato', 'Reserva', 18, NULL, NULL, '2025-07-04 16:54:11', 'Contrato da Reserva #18 enviado por Desconhecido via manual. Caminho: uploads/contratos/contrato_18_6868073337472.pdf.', '::1'),
(35, 1, 'Finalizar Venda', 'Reserva', 18, NULL, NULL, '2025-07-04 16:54:36', 'Venda para a reserva #18 finalizada por Desconhecido. Unidade #399 marcada como \'vendida\'.', '::1'),
(36, 1, 'Aprovar Documento', 'Documento_Reserva', 25, NULL, NULL, '2025-07-04 17:56:56', 'Documento \'RG e CPF ou CNH\' da reserva #19 aprovado por Desconhecido.', '::1'),
(37, 1, 'Aprovar Documento', 'Documento_Reserva', 26, NULL, NULL, '2025-07-04 17:56:58', 'Documento \'Comprovante de Endereço\' da reserva #19 aprovado por Desconhecido.', '::1'),
(38, 1, 'Enviar Contrato', 'Reserva', 19, NULL, NULL, '2025-07-04 17:57:18', 'Contrato da Reserva #19 enviado por Desconhecido via manual. Caminho: uploads/contratos/contrato_19_686815fe8fcce.pdf.', '::1'),
(39, 1, 'Finalizar Venda', 'Reserva', 19, NULL, NULL, '2025-07-04 17:57:39', 'Venda para a reserva #19 finalizada por Desconhecido. Unidade #389 marcada como \'vendida\'.', '::1'),
(40, 1, 'Aprovar Documento', 'Documento_Reserva', 27, NULL, NULL, '2025-07-04 19:58:47', 'Documento \'RG e CPF ou CNH\' da reserva #20 aprovado por Desconhecido.', '::1'),
(41, 1, 'Aprovar Documento', 'Documento_Reserva', 28, NULL, NULL, '2025-07-04 19:58:49', 'Documento \'Comprovante de Endereço\' da reserva #20 aprovado por Desconhecido.', '::1'),
(42, 1, 'Enviar Contrato', 'Reserva', 20, NULL, NULL, '2025-07-04 19:59:49', 'Contrato da Reserva #20 enviado por Desconhecido via manual. Caminho: uploads/contratos/contrato_20_686832b58863a.pdf.', '::1'),
(43, 1, 'Finalizar Venda', 'Reserva', 20, NULL, NULL, '2025-07-04 20:00:11', 'Venda para a reserva #20 finalizada por Desconhecido. Unidade #379 marcada como \'vendida\'.', '::1'),
(44, 1, 'Aprovar Documento', 'Documento_Reserva', 29, NULL, NULL, '2025-07-04 20:05:29', 'Documento \'RG e CPF ou CNH\' da reserva #21 aprovado por Desconhecido.', '::1'),
(45, 1, 'Aprovar Documento', 'Documento_Reserva', 30, NULL, NULL, '2025-07-04 20:05:36', 'Documento \'Comprovante de Endereço\' da reserva #21 aprovado por Desconhecido.', '::1'),
(46, 1, 'Enviar Contrato', 'Reserva', 21, NULL, NULL, '2025-07-04 20:06:12', 'Contrato da Reserva #21 enviado por Desconhecido via manual. Caminho: uploads/contratos/contrato_21_68683434d9fa9.pdf.', '::1'),
(47, 1, 'Finalizar Venda', 'Reserva', 21, NULL, NULL, '2025-07-04 20:06:39', 'Venda para a reserva #21 finalizada por Desconhecido. Unidade #369 marcada como \'vendida\'.', '::1'),
(48, 1, 'cadastro', 'empreendimentos', 5, NULL, NULL, '2025-07-05 11:37:44', 'Empreendimento \'Francisco\' cadastrado na etapa 1.', NULL),
(49, 1, 'atualizacao', 'empreendimentos', 5, NULL, NULL, '2025-07-05 11:44:36', 'Empreendimento \'Francisco\' atualizado na etapa 1.', NULL),
(50, 1, 'atualizacao', 'empreendimentos', 5, NULL, NULL, '2025-07-05 11:55:08', 'Empreendimento \'Francisco\' atualizado na etapa 1.', NULL),
(51, 1, 'cadastro', 'empreendimentos', 6, NULL, NULL, '2025-07-05 14:46:59', 'Empreendimento \'Casa 604 Norte\' cadastrado na etapa 1.', NULL),
(52, 1, 'cadastro', 'empreendimentos', 7, NULL, NULL, '2025-07-05 15:03:13', 'Empreendimento \'Francisco Antunes de Souza\' cadastrado na etapa 1.', NULL),
(53, 1, 'cadastro', 'empreendimentos', 8, NULL, NULL, '2025-07-05 15:04:50', 'Empreendimento \'asdasdasdasd\' cadastrado na etapa 1.', NULL),
(54, 1, 'cadastro', 'empreendimentos', 9, NULL, NULL, '2025-07-05 15:45:17', 'Empreendimento \'Teste\' cadastrado na etapa 1.', NULL),
(55, 1, 'cadastro', 'empreendimentos', 10, NULL, NULL, '2025-07-05 15:53:46', 'Empreendimento \'efaafra\' cadastrado na etapa 1.', NULL),
(56, 1, 'cadastro', 'empreendimentos', 11, NULL, NULL, '2025-07-05 15:57:14', 'Empreendimento \'hgjhdfgh\' cadastrado na etapa 1.', NULL),
(57, 1, 'Rejeitar Reserva', 'Reserva', 22, NULL, NULL, '2025-07-05 16:00:34', 'Reserva #22 rejeitada e cancelada por Desconhecido. Motivo: \'\'. Unidade #102 voltou a ser \'disponivel\' (se aplicável).', '::1'),
(58, 1, 'Atualização Empreendimento Etapa 1', 'Empreendimento', 7, NULL, NULL, '2025-07-06 13:04:36', 'Empreendimento \'Francisco Antunes de Souza\' atualizado na etapa 1.', '::1'),
(59, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 12, NULL, NULL, '2025-07-06 17:54:46', 'Empreendimento \'Atrio Urba\' cadastrado na etapa 1.', '::1'),
(60, 1, 'Atualização Empreendimento Etapa 2', 'Empreendimento', 12, NULL, NULL, '2025-07-06 17:55:32', 'Tipos de unidade do empreendimento atualizados na etapa 2.', '::1'),
(61, 1, 'Atualização Empreendimento Etapa 3', 'Empreendimento', 12, NULL, NULL, '2025-07-06 17:55:43', 'Áreas comuns do empreendimento atualizadas na etapa 3.', '::1'),
(62, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 13, NULL, NULL, '2025-07-06 19:48:30', 'Empreendimento \'Atrio Urban\' cadastrado na etapa 1.', '::1'),
(63, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 14, NULL, NULL, '2025-07-06 20:02:33', 'Empreendimento \'Francisco\' cadastrado na etapa 1.', '::1'),
(64, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 15, NULL, NULL, '2025-07-06 20:51:47', 'Empreendimento \'Casa 604 Norte\' cadastrado na etapa 1.', '::1'),
(65, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 16, NULL, NULL, '2025-07-06 21:02:11', 'Empreendimento \'FRANCISCO ANTUNES DE SOUZA\' cadastrado na etapa 1.', '::1'),
(66, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 17, NULL, NULL, '2025-07-06 21:18:06', 'Empreendimento \'tESTE23\' cadastrado na etapa 1.', '::1'),
(67, 1, 'Atualização Empreendimento Etapa 2', 'Empreendimento', 17, NULL, NULL, '2025-07-06 21:18:55', 'Tipos de unidade do empreendimento atualizados na etapa 2.', '::1'),
(68, 1, 'Atualização Empreendimento Etapa 3', 'Empreendimento', 17, NULL, NULL, '2025-07-06 21:20:36', 'Áreas comuns do empreendimento atualizadas na etapa 3.', '::1'),
(69, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 18, NULL, NULL, '2025-07-06 21:48:35', 'Empreendimento \'qwerqwerqwr\' cadastrado na etapa 1.', '::1'),
(70, 1, 'Atualização Empreendimento Etapa 2', 'Empreendimento', 18, NULL, NULL, '2025-07-06 21:48:46', 'Tipos de unidade do empreendimento atualizados na etapa 2.', '::1'),
(71, 1, 'Atualização Empreendimento Etapa 3', 'Empreendimento', 18, NULL, NULL, '2025-07-06 21:49:09', 'Áreas comuns do empreendimento atualizadas na etapa 3.', '::1'),
(72, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 19, NULL, NULL, '2025-07-06 22:05:14', 'Empreendimento \'werwer\' cadastrado na etapa 1.', '::1'),
(73, 1, 'Atualização Empreendimento Etapa 2', 'Empreendimento', 19, NULL, NULL, '2025-07-06 22:05:27', 'Tipos de unidade do empreendimento atualizados na etapa 2.', '::1'),
(74, 1, 'Atualização Empreendimento Etapa 3', 'Empreendimento', 19, NULL, NULL, '2025-07-06 22:05:32', 'Áreas comuns do empreendimento atualizadas na etapa 3.', '::1'),
(75, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 20, NULL, NULL, '2025-07-06 22:44:11', 'Empreendimento \'asdfasdf\' cadastrado na etapa 1.', '::1'),
(76, 1, 'Atualização Empreendimento Etapa 2', 'Empreendimento', 20, NULL, NULL, '2025-07-06 22:51:10', 'Tipos de unidade do empreendimento atualizados na etapa 2.', '::1'),
(77, 1, 'Atualização Empreendimento Etapa 3', 'Empreendimento', 20, NULL, NULL, '2025-07-06 22:51:17', 'Áreas comuns do empreendimento atualizadas na etapa 3.', '::1'),
(78, 1, 'Atualização Empreendimento Etapa 3', 'Empreendimento', 20, NULL, NULL, '2025-07-06 22:51:40', 'Áreas comuns do empreendimento atualizadas na etapa 3.', '::1'),
(79, 1, 'Aprovar Reserva', 'Reserva', 25, NULL, NULL, '2025-07-07 00:21:46', 'Reserva #25 aprovada por Desconhecido. Unidade #319 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(80, 1, 'Aprovar Documento', 'Documento_Reserva', 31, NULL, NULL, '2025-07-07 00:22:16', 'Documento \'RG e CPF ou CNH\' da reserva #25 aprovado por Desconhecido.', '::1'),
(81, 1, 'Aprovar Documento', 'Documento_Reserva', 32, NULL, NULL, '2025-07-07 00:22:20', 'Documento \'Comprovante de Endereço\' da reserva #25 aprovado por Desconhecido.', '::1'),
(82, 1, 'Enviar Contrato', 'Reserva', 25, NULL, NULL, '2025-07-09 01:06:30', 'Contrato da Reserva #25 enviado por Desconhecido via manual. Caminho: uploads/contratos/contrato_25_686dc09644be5.pdf.', '::1'),
(83, 1, 'Finalizar Venda', 'Reserva', 25, NULL, NULL, '2025-07-09 01:07:51', 'Venda para a reserva #25 finalizada por Desconhecido. Unidade #319 marcada como \'vendida\'.', '::1'),
(84, 1, 'Simular Assinatura Contrato', 'Reserva', 11, NULL, NULL, '2025-07-09 01:21:38', 'Assinatura do contrato da Reserva #11 simulada por Desconhecido. Venda finalizada.', '::1'),
(85, 1, 'Rejeitar Reserva', 'Reserva', 14, NULL, NULL, '2025-07-09 01:43:02', 'Reserva #14 rejeitada e cancelada por Desconhecido. Motivo: \'\'. Unidade #304 voltou a ser \'disponivel\' (se aplicável).', '::1'),
(86, 1, 'Aprovar Reserva', 'Reserva', 28, NULL, NULL, '2025-07-09 10:46:29', 'Reserva #28 aprovada por Desconhecido. Unidade #313 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(87, 1, 'Rejeitar Reserva', 'Reserva', 27, NULL, NULL, '2025-07-09 11:08:13', 'Reserva #27 rejeitada e cancelada por Desconhecido. Motivo: \'\'. Unidade #312 voltou a ser \'disponivel\' (se aplicável).', '::1'),
(88, 1, 'Rejeitar Reserva', 'Reserva', 29, NULL, NULL, '2025-07-09 11:08:40', 'Reserva #29 rejeitada e cancelada por Desconhecido. Motivo: \'\'. Unidade #314 voltou a ser \'disponivel\' (se aplicável).', '::1'),
(89, 1, 'Aprovar Reserva', 'Reserva', 30, NULL, NULL, '2025-07-09 14:15:26', 'Reserva #30 aprovada por Desconhecido. Unidade #314 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(90, 1, 'Aprovar Reserva', 'Reserva', 31, NULL, NULL, '2025-07-09 14:46:57', 'Reserva #31 aprovada por Desconhecido. Unidade #315 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(91, 1, 'Solicitar Documentação', 'Reserva', 31, NULL, NULL, '2025-07-09 15:17:34', 'Documentação solicitada por Desconhecido para a reserva #31. Status atualizado para \'documentos_pendentes\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=f647efa71f4c31704d79260d10242ec77c4dc020cbad63ba5112f94c4977d0bb. Email enviado para cliente.', '::1'),
(92, 1, 'Solicitar Documentação', 'Reserva', 31, NULL, NULL, '2025-07-09 15:18:26', 'Documentação solicitada por Desconhecido para a reserva #31. Status atualizado para \'documentos_pendentes\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=a369276b6ecae01023281bfe7fc21e9192d087da77f97308cd8e7b51f9234f8c. Email enviado para cliente.', '::1'),
(93, 1, 'Solicitar Documentação', 'Reserva', 31, NULL, NULL, '2025-07-09 15:18:51', 'Documentação solicitada por Desconhecido para a reserva #31. Status atualizado para \'documentos_pendentes\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=11ffd0859a05af65d6816a7551856f2ef618c5b0e4b096ae884c0056ef773c07. Email enviado para cliente.', '::1'),
(94, 1, 'Solicitar Documentação', 'Reserva', 31, NULL, NULL, '2025-07-09 15:19:22', 'Documentação solicitada por Desconhecido para a reserva #31. Status atualizado para \'documentos_pendentes\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=a38c48bec58d80a7e14eafe16c138c3fea4b9350100fe1680422bc14afc8c970. Email enviado para cliente.', '::1'),
(95, 1, 'Solicitar Documentação', 'Reserva', 28, NULL, NULL, '2025-07-09 15:34:20', 'Documentação solicitada por Desconhecido para a reserva #28. Status atualizado para \'documentos_pendentes\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=a7c5cdc2b9ee7dad478b385d5c37527bf8a0ed90f912f4f672562ed7fe76c773. Email enviado para cliente.', '::1'),
(96, 1, 'Aprovar Documento', 'Documento_Reserva', 39, NULL, NULL, '2025-07-09 15:35:36', 'Documento \'RG e CPF ou CNH\' da reserva #31 aprovado por Desconhecido.', '::1'),
(97, 1, 'Aprovar Documento', 'Documento_Reserva', 40, NULL, NULL, '2025-07-09 15:35:39', 'Documento \'Comprovante de Endereço\' da reserva #31 aprovado por Desconhecido.', '::1'),
(98, 1, 'Marcar Contrato como Enviado (Manual)', 'Reserva', 31, NULL, NULL, '2025-07-09 15:36:04', 'Contrato da Reserva #31 marcado como enviado manualmente por Desconhecido.', '::1'),
(99, 1, 'Finalizar Venda', 'Reserva', 31, NULL, NULL, '2025-07-09 15:36:16', 'Venda para a reserva #31 finalizada por Desconhecido. Unidade #315 marcada como \'vendida\'.', '::1'),
(100, 1, 'Solicitar Documentação', 'Reserva', 30, NULL, NULL, '2025-07-09 15:36:41', 'Documentação solicitada por Desconhecido para a reserva #30. Status atualizado para \'documentos_pendentes\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=7214d4217fc1d8aef9284ef8cdac8075b67fb3d2ee824135c70975a447c9cdde. Email enviado para cliente.', '::1'),
(101, 1, 'Solicitar Documentação', 'Reserva', 30, NULL, NULL, '2025-07-09 16:02:37', 'Documentação solicitada por Desconhecido para a reserva #30. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=3b821927c040aeb69a71114f3985d980062b4ce226436ab976b9777854a11acd. Email enviado para cliente.', '::1'),
(102, 1, 'Aprovar Documento', 'Documento_Reserva', 41, NULL, NULL, '2025-07-09 16:04:34', 'Documento \'RG e CPF ou CNH\' da reserva #30 aprovado por Desconhecido.', '::1'),
(103, 1, 'Aprovar Documento', 'Documento_Reserva', 42, NULL, NULL, '2025-07-09 16:04:39', 'Documento \'Comprovante de Endereço\' da reserva #30 aprovado por Desconhecido.', '::1'),
(104, 1, 'Enviar Contrato', 'Reserva', 30, NULL, NULL, '2025-07-09 16:05:11', 'Contrato da Reserva #30 enviado por Desconhecido via manual. Caminho: uploads/contratos/contrato_30_686e9337d5696.pdf.', '::1'),
(105, 1, 'Solicitar Documentação', 'Reserva', 28, NULL, NULL, '2025-07-09 16:08:11', 'Documentação solicitada por Desconhecido para a reserva #28. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=bac20652065a93f7159df5f22c6d8af85406faa84c47c7ef03ff227104493995. Email enviado para cliente.', '::1'),
(106, 1, 'Finalizar Venda', 'Reserva', 30, NULL, NULL, '2025-07-09 17:00:22', 'Reserva #30 e Unidade #314 marcadas como vendidas por Desconhecido.', '::1'),
(107, 1, 'Cadastro Empreendimento Etapa 1', 'Empreendimento', 21, NULL, NULL, '2025-07-09 17:09:08', 'Empreendimento \'Teste Leo\' cadastrado na etapa 1.', '::1'),
(108, 1, 'Atualização Empreendimento Etapa 2', 'Empreendimento', 21, NULL, NULL, '2025-07-09 17:10:42', 'Tipos de unidade do empreendimento atualizados na etapa 2.', '::1'),
(109, 1, 'Atualização Empreendimento Etapa 3', 'Empreendimento', 21, NULL, NULL, '2025-07-09 17:11:42', 'Áreas comuns do empreendimento atualizadas na etapa 3.', '::1'),
(110, 1, 'Aprovar Reserva', 'Reserva', 32, NULL, NULL, '2025-07-09 17:31:10', 'Reserva #32 aprovada por Desconhecido. Unidade #332 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(111, 1, 'Solicitar Documentação', 'Reserva', 32, NULL, NULL, '2025-07-09 17:31:20', 'Documentação solicitada por Desconhecido para a reserva #32. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=90606d6a4ecf7d2c020d6ffd6acc7eaaf2d73c743bd65bd9fc6c2312312eb006. Email enviado para cliente.', '::1'),
(112, 1, 'Aprovar Reserva', 'Reserva', 33, NULL, NULL, '2025-07-10 10:06:49', 'Reserva #33 aprovada por Desconhecido. Unidade #400 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(113, 1, 'Solicitar Documentação', 'Reserva', 33, NULL, NULL, '2025-07-10 10:06:59', 'Documentação solicitada por Desconhecido para a reserva #33. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=65ecf925b7b6aca5b0ba9b01a4ce2750245493cb8d9b3ecbf2378152f0ef0e33. Email enviado para cliente.', '::1'),
(114, 1, 'Solicitar Documentação', 'Reserva', 34, NULL, NULL, '2025-07-10 11:08:38', 'Documentação solicitada por Desconhecido para a reserva #34. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=6cf9a2e42654e9710793fde2e3712718ab5c0f768e0b6657212454bc84f96f58. Email enviado para corretor.', '::1'),
(115, 1, 'Aprovar Reserva', 'Reserva', 37, NULL, NULL, '2025-07-10 11:09:26', 'Reserva #37 aprovada por Desconhecido. Unidade #380 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(116, 1, 'Solicitar Documentação', 'Reserva', 37, NULL, NULL, '2025-07-10 11:09:52', 'Documentação solicitada por Desconhecido para a reserva #37. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=465f1085b1753a25b70989eccb1c8bd815249dac08a4bc6f765053ca68f07bfc. Email enviado para cliente.', '::1'),
(117, 1, 'Cancelar Reserva', 'Reserva', 33, NULL, NULL, '2025-07-10 11:40:19', 'Reserva #33 cancelada por Admin Master. Motivo: \'\'. Unidade #1002 voltou a ser \'disponivel\' (se aplicável).', '::1'),
(118, 1, 'Cancelar Reserva', 'Reserva', 34, NULL, NULL, '2025-07-10 11:40:43', 'Reserva #34 cancelada por Admin Master. Motivo: \'\'. Unidade #902 voltou a ser \'disponivel\' (se aplicável).', '::1'),
(119, 1, 'Rejeitar Reserva', 'Reserva', 35, NULL, NULL, '2025-07-10 12:43:55', 'Reserva #35 rejeitada e cancelada por Admin Master. Motivo: \'Testando\'. Unidade #802 voltou a ser \'disponivel\' (se aplicável).', '::1'),
(120, 1, 'Cancelar Reserva', 'Reserva', 28, NULL, NULL, '2025-07-10 13:49:45', 'Reserva #28 cancelada por Admin Master. Motivo: \'\'. Unidade #105 voltou a ser \'disponivel\' (se aplicável).', '::1'),
(121, 1, 'Cliente da Reserva Atualizado', 'Cliente', 20, NULL, NULL, '2025-07-10 13:50:39', 'Dados do cliente Editado (ID: 20) atualizados na reserva #36 por Admin Master.', '::1'),
(122, 1, 'Cliente da Reserva Atualizado', 'Cliente', 20, NULL, NULL, '2025-07-10 13:50:39', 'Dados do cliente Editado (ID: 20) atualizados na reserva #36 por Admin Master.', '::1'),
(123, 1, 'Aprovar Documento', 'Documento_Reserva', 51, NULL, NULL, '2025-07-10 14:40:44', 'Documento \'RG e CPF ou CNH\' da reserva #37 aprovado por Admin Master.', '::1'),
(124, 1, 'Aprovar Documento', 'Documento_Reserva', 52, NULL, NULL, '2025-07-10 14:40:49', 'Documento \'Comprovante de Endereço\' da reserva #37 aprovado por Admin Master.', '::1'),
(125, 1, 'Marcar Contrato como Enviado (Manual)', 'Reserva', 37, NULL, NULL, '2025-07-10 14:41:05', 'Contrato da Reserva #37 marcado como enviado manualmente por Admin Master.', '::1'),
(126, 1, 'Finalizar Venda', 'Reserva', 37, NULL, NULL, '2025-07-10 14:41:18', 'Reserva #37 e Unidade #802 marcadas como vendidas por Admin Master.', '::1'),
(127, 1, 'Rejeitar Documento', 'Documento_Reserva', 49, NULL, NULL, '2025-07-10 14:41:51', 'Documento \'RG e CPF ou CNH\' da reserva #36 rejeitado por Admin Master. Motivo: \'Testando\'.', '::1'),
(128, 1, 'Rejeitar Documento', 'Documento_Reserva', 50, NULL, NULL, '2025-07-10 14:41:59', 'Documento \'Comprovante de Endereço\' da reserva #36 rejeitado por Admin Master. Motivo: \'Testando 2\'.', '::1'),
(129, 1, 'Solicitar Documentação', 'Reserva', 36, NULL, NULL, '2025-07-10 14:42:31', 'Documentação solicitada por Admin Master para a reserva #36. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=8a528cf9784a79ab1f6692d183eaf53dce111332c680651c34b0574ffae2b74a. Email enviado para corretor.', '::1'),
(130, 1, 'Aprovar Reserva', 'Reserva', 38, NULL, NULL, '2025-07-10 15:19:27', 'Reserva #38 aprovada por Admin Master. Unidade #902 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(131, 1, 'Solicitar Documentação', 'Reserva', 38, NULL, NULL, '2025-07-10 15:19:31', 'Documentação solicitada por Admin Master para a reserva #38. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=f078602e971e5306b19ebd2e23658230927397d2689086808c6a660fadbbf1d8. Email enviado para cliente.', '::1'),
(132, 1, 'Aprovar Reserva', 'Reserva', 39, NULL, NULL, '2025-07-10 15:28:39', 'Reserva #39 aprovada por Admin Master. Unidade #1002 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(133, 1, 'Solicitar Documentação', 'Reserva', 39, NULL, NULL, '2025-07-10 15:28:43', 'Documentação solicitada por Admin Master para a reserva #39. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=984a266c6f8f9d3fa218b458ac0949e77086e5634e45d737cad1bbb973a6cd43. Email enviado para cliente.', '::1'),
(134, 1, 'Aprovar Documento', 'Documento_Reserva', 53, NULL, NULL, '2025-07-10 15:31:23', 'Documento \'RG e CPF ou CNH\' da reserva #39 aprovado por Admin Master.', '::1'),
(135, 1, 'Aprovar Documento', 'Documento_Reserva', 54, NULL, NULL, '2025-07-10 15:31:27', 'Documento \'Comprovante de Endereço\' da reserva #39 aprovado por Admin Master.', '::1'),
(136, 1, 'Marcar Contrato como Enviado (Manual)', 'Reserva', 39, NULL, NULL, '2025-07-10 15:31:46', 'Contrato da Reserva #39 marcado como enviado manualmente por Admin Master.', '::1'),
(137, 1, 'Finalizar Venda', 'Reserva', 39, NULL, NULL, '2025-07-10 15:31:55', 'Reserva #39 e Unidade #1002 marcadas como vendidas por Admin Master.', '::1'),
(138, 1, 'Aprovar Documento', 'Documento_Reserva', 55, NULL, NULL, '2025-07-10 15:35:05', 'Documento \'RG e CPF ou CNH\' da reserva #41 aprovado por Admin Master.', '::1'),
(139, 1, 'Aprovar Documento', 'Documento_Reserva', 56, NULL, NULL, '2025-07-10 15:35:08', 'Documento \'Comprovante de Endereço\' da reserva #41 aprovado por Admin Master.', '::1'),
(140, 1, 'Marcar Contrato como Enviado (Manual)', 'Reserva', 41, NULL, NULL, '2025-07-10 15:35:32', 'Contrato da Reserva #41 marcado como enviado manualmente por Admin Master.', '::1'),
(141, 1, 'Finalizar Venda', 'Reserva', 41, NULL, NULL, '2025-07-10 15:35:42', 'Reserva #41 e Unidade #1004 marcadas como vendidas por Admin Master.', '::1'),
(142, 1, 'Aprovar Reserva', 'Reserva', 42, NULL, NULL, '2025-07-10 15:36:17', 'Reserva #42 aprovada por Admin Master. Unidade #1003 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(143, 1, 'Solicitar Documentação', 'Reserva', 42, NULL, NULL, '2025-07-10 15:36:26', 'Documentação solicitada por Admin Master para a reserva #42. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=6d77d4c80d28e0483b244325f717376ba39876fd2818973a0e74d2b8b172c107. Email enviado para cliente.', '::1'),
(144, 1, 'Cancelar Reserva', 'Reserva', 42, NULL, NULL, '2025-07-10 15:40:10', 'Reserva #42 cancelada por Admin Master. Motivo: \'\'. Unidade #1003 voltou a ser \'disponivel\' (se aplicável).', '::1'),
(145, 1, 'Aprovar Reserva', 'Reserva', 40, NULL, NULL, '2025-07-10 15:40:24', 'Reserva #40 aprovada por Admin Master. Unidade #1003 marcada como \'reservada\'. Novo status: documentos_pendentes.', '::1'),
(146, 1, 'Solicitar Documentação', 'Reserva', 40, NULL, NULL, '2025-07-10 15:40:28', 'Documentação solicitada por Admin Master para a reserva #40. Status atualizado para \'documentos_solicitados\'. Link gerado: http://localhost/SISReserva_MVP/public/documentos.php?token=3f3c104feb05f286b667330371633dffb31d6506c8dd6588803cd24bfa6dfc10. Email enviado para cliente.', '::1');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int NOT NULL,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cep` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `cpf`, `email`, `whatsapp`, `cep`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `data_cadastro`, `data_atualizacao`) VALUES
(1, 'Cliente Lead 1', '111.222.333-44', 'cliente1@example.com', '63988887777', NULL, 'Rua Alfa', '1', NULL, 'Bairro A', 'Palmas', 'TO', '2025-07-01 22:17:54', '2025-07-01 22:17:54'),
(2, 'Cliente Lead 2', '555.666.777-88', 'cliente2@example.com', '63999998888', NULL, 'Rua Beta', '2', NULL, 'Bairro B', 'Palmas', 'TO', '2025-07-01 22:17:54', '2025-07-01 22:17:54'),
(3, 'Cliente Atribuido', '999.888.777-66', 'cliente_atribuido@example.com', '63977776666', NULL, 'Rua Gama', '3', NULL, 'Bairro C', 'Palmas', 'TO', '2025-07-01 22:17:54', '2025-07-01 22:17:54'),
(4, 'sdfsdf', '123.123.123-12', 'franciscoas@uft.edu.br', '(63) 99226-4887', NULL, 'Quadra ARNE 71 Alameda', '3', 'werwer', 'werwer', 'Palmas', 'TO', '2025-07-01 22:17:54', '2025-07-07 00:11:58'),
(6, 'Francisco', '820.280.380-20', 'xiko10@gmail.com', '(63) 99226-4887', NULL, '123123dfws', '12312', '123123', '123123', '123123', '12', '2025-07-03 16:42:35', '2025-07-03 16:42:35'),
(12, 'Reserva Suite', '123.457.894-12', 'reserva201corrtor@gmail.com', '11111111111111', NULL, 'Quadra ARNE 71 Alameda', '3', 'QI 08', 'PLANO DIRETOR NORTE', 'Palmas', 'TO', '2025-07-04 14:23:17', '2025-07-04 14:23:17'),
(13, 'Marcia 202', '123.457.487-99', 'marcia@corretor.com.br', '(11) 97845-7845', NULL, 'Quadra ARNE 71 Alameda', '3', 'QI 08', 'PLANO DIRETOR NORTE', 'Palmas', 'TO', '2025-07-04 16:06:55', '2025-07-04 16:06:55'),
(14, '2234OI', '12478412445', 'francisco@teste.com', '11978457845', '77006886', 'Quadra ARNO 21 Alameda 13', '334', 'QI 08', 'Plano Diretor Norte', 'Palmas', 'TO', '2025-07-04 17:55:26', '2025-07-09 01:05:40'),
(17, 'Francisco Antunes de Souza', '12312312312', 'francisasdfsadcoas@uft.edu.br', '63992264887', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-09 10:35:26', '2025-07-10 15:36:07'),
(18, 'Teste FLUXO COrretor', '48726685712', 'cliente231@teste.com', '82214654465', '77006886', 'Quadra ARNO 21 Alameda 13', '12', '', 'Plano Diretor Norte', 'Palmas', 'TO', '2025-07-10 10:45:24', '2025-07-10 10:45:24'),
(19, 'Francisco Antunes de Souza', '21354235345', 'franciscoas1212@uft.edu.br', '45435323453', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-10 10:46:57', '2025-07-10 10:46:57'),
(20, 'Editado', '59496231321', 'marcelo@roberto.com', '11897498461', '14021658', 'Alameda dos Jardins', '12', '', 'Jardim Botânico', 'Ribeirão Preto', 'SP', '2025-07-10 10:53:26', '2025-07-10 13:50:39'),
(21, 'Francisco Antunes de Souza', '32745674567', '2342342oas@uft.edu.br', '63992264887', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-10 10:54:31', '2025-07-10 10:54:31'),
(22, 'Lead1', '49584654615', '564654@gmail.com', '54871131232', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-10 15:19:04', '2025-07-10 15:19:04'),
(23, 'Francisco Antunes de Souza', '43233441513', 'fr1231231anciscoas@uft.edu.br', '12312312312', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-10 15:21:01', '2025-07-10 15:21:01'),
(24, 'ijsfhjsadufu9i', '91231238109', 'sadfasdfsajoaoleste@teste.com', '58897985974', '77006886', 'Quadra ARNO 21 Alameda 13', '12', '', 'Plano Diretor Norte', 'Palmas', 'TO', '2025-07-10 15:34:25', '2025-07-10 15:34:25'),
(25, 'Francisco Antunes de Souza', '23423423423', 'qweqw@gmail.com', '33214324234', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-10 16:38:39', '2025-07-10 16:38:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_reserva`
--

CREATE TABLE `documentos_reserva` (
  `id` int NOT NULL,
  `reserva_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `nome_documento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caminho_arquivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pendente','aprovado','rejeitado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `motivo_rejeicao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `data_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_analise` timestamp NULL DEFAULT NULL,
  `usuario_analise_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `documentos_reserva`
--

INSERT INTO `documentos_reserva` (`id`, `reserva_id`, `cliente_id`, `nome_documento`, `caminho_arquivo`, `status`, `motivo_rejeicao`, `data_upload`, `data_analise`, `usuario_analise_id`) VALUES
(1, 1, 1, 'RG_ClienteLead1.pdf', 'uploads/documentos_reservas/reserva1/RG_Cliente1.pdf', 'pendente', NULL, '2025-07-01 20:17:54', NULL, NULL),
(2, 1, 1, 'CPF_ClienteLead1.pdf', 'uploads/documentos_reservas/reserva1/CPF_Cliente1.pdf', 'pendente', NULL, '2025-07-01 20:18:54', NULL, NULL),
(3, 3, 3, 'ComprovanteRenda_ClienteAtribuido.pdf', 'uploads/documentos_reservas/reserva3/ComprovanteRenda_Cliente3.pdf', 'aprovado', NULL, '2025-07-01 19:17:54', NULL, NULL),
(4, 3, 3, 'ComprovanteResidencia_ClienteAtribuido.pdf', 'uploads/documentos_reservas/reserva3/CompResidencia_Cliente3.pdf', 'rejeitado', 'Assinatura ilegível.', '2025-07-01 19:18:54', NULL, NULL),
(5, 3, 3, 'CNH_ClienteAtribuido.pdf', 'uploads/documentos_reservas/reserva3/CNH_Cliente3.pdf', 'pendente', NULL, '2025-07-01 19:19:54', NULL, NULL),
(6, 10, 4, 'RG', 'uploads/documentos_reservas/reserva10/RG_6867bae6aa0ab.jpg', 'aprovado', NULL, '2025-07-04 11:28:38', '2025-07-04 12:01:39', 1),
(7, 10, 4, 'CPF', 'uploads/documentos_reservas/reserva10/CPF_6867bae6aa3d1.jpg', 'aprovado', NULL, '2025-07-04 11:28:38', '2025-07-04 12:01:46', 1),
(8, 11, 4, 'RG', 'uploads/documentos_reservas/reserva11/RG_6867cb841b4c1.pdf', 'aprovado', NULL, '2025-07-04 12:39:32', '2025-07-04 12:40:07', 1),
(9, 11, 4, 'CPF', 'uploads/documentos_reservas/reserva11/CPF_6867cb841b762.pdf', 'aprovado', NULL, '2025-07-04 12:39:32', '2025-07-04 12:40:11', 1),
(10, 11, 4, 'Comprovante de Renda', 'uploads/documentos_reservas/reserva11/Comprovante de Renda_6867cb841b8ea.pdf', 'aprovado', NULL, '2025-07-04 12:39:32', '2025-07-04 12:40:13', 1),
(11, 12, 4, 'RG', 'uploads/documentos_reservas/reserva12/RG_6867cf53cf118.pdf', 'aprovado', NULL, '2025-07-04 12:55:47', '2025-07-04 12:56:09', 1),
(12, 12, 4, 'CPF', 'uploads/documentos_reservas/reserva12/CPF_6867cf53d1bbe.pdf', 'aprovado', NULL, '2025-07-04 12:55:47', '2025-07-04 12:56:16', 1),
(13, 12, 4, 'Comprovante de Renda', 'uploads/documentos_reservas/reserva12/Comprovante de Renda_6867cf53d1dde.pdf', 'aprovado', NULL, '2025-07-04 12:55:47', '2025-07-04 12:56:22', 1),
(14, 14, 4, 'RG', 'uploads/documentos_reservas/reserva14/RG_6867d2df550a6.jpg', 'aprovado', NULL, '2025-07-04 13:10:55', '2025-07-04 13:11:15', 1),
(15, 14, 4, 'CPF', 'uploads/documentos_reservas/reserva14/CPF_6867d2df55299.jpg', 'aprovado', NULL, '2025-07-04 13:10:55', '2025-07-04 13:11:18', 1),
(16, 14, 4, 'Comprovante de Renda', 'uploads/documentos_reservas/reserva14/Comprovante de Renda_6867d2df5545d.jpg', 'aprovado', NULL, '2025-07-04 13:10:55', '2025-07-04 13:11:21', 1),
(17, 15, 12, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva15/RG e CPF ou CNH_6867e3d53fe29.pdf', 'aprovado', NULL, '2025-07-04 14:23:17', '2025-07-04 14:28:34', 1),
(18, 15, 12, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva15/Comprovante de Endereço_6867e3d54000a.pdf', 'aprovado', NULL, '2025-07-04 14:23:17', '2025-07-04 14:28:39', 1),
(19, 16, 13, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva16/RG e CPF ou CNH_6867fc1fd0ab5.pdf', 'aprovado', NULL, '2025-07-04 16:06:55', '2025-07-04 16:07:42', 1),
(20, 16, 13, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva16/Comprovante de Endereço_6867fc1fd0da6.pdf', 'aprovado', NULL, '2025-07-04 16:06:55', '2025-07-04 16:07:45', 1),
(21, 17, 4, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva17/RG e CPF ou CNH_6867ffe04bbbb.pdf', 'aprovado', NULL, '2025-07-04 16:22:56', '2025-07-04 16:23:47', 1),
(22, 17, 4, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva17/Comprovante de Endereço_6867ffe04be16.pdf', 'aprovado', NULL, '2025-07-04 16:22:56', '2025-07-04 16:23:53', 1),
(23, 18, 4, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva18/RG e CPF ou CNH_686806e1c8ecf.pdf', 'aprovado', NULL, '2025-07-04 16:52:49', '2025-07-04 16:53:47', 1),
(24, 18, 4, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva18/Comprovante de Endereço_686806e1c9106.pdf', 'aprovado', NULL, '2025-07-04 16:52:49', '2025-07-04 16:53:51', 1),
(25, 19, 14, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva19/RG e CPF ou CNH_6868158e6086e.pdf', 'aprovado', NULL, '2025-07-04 17:55:26', '2025-07-04 17:56:56', 1),
(26, 19, 14, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva19/Comprovante de Endereço_6868158e60b83.pdf', 'aprovado', NULL, '2025-07-04 17:55:26', '2025-07-04 17:56:58', 1),
(27, 20, 4, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva20/RG e CPF ou CNH_686831f51af87.pdf', 'aprovado', NULL, '2025-07-04 19:56:37', '2025-07-04 19:58:47', 1),
(28, 20, 4, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva20/Comprovante de Endereço_686831f51b746.pdf', 'aprovado', NULL, '2025-07-04 19:56:37', '2025-07-04 19:58:49', 1),
(29, 21, 4, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva21/RG e CPF ou CNH_686833c487b91.pdf', 'aprovado', NULL, '2025-07-04 20:04:20', '2025-07-04 20:05:29', 1),
(30, 21, 4, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva21/Comprovante de Endereço_686833c4881a7.pdf', 'aprovado', NULL, '2025-07-04 20:04:20', '2025-07-04 20:05:36', 1),
(31, 25, 4, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva25/RG e CPF ou CNH_686b10ce35953.png', 'aprovado', NULL, '2025-07-07 00:11:58', '2025-07-07 00:22:16', 1),
(32, 25, 4, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva25/Comprovante de Endereço_686b10ce3616c.png', 'aprovado', NULL, '2025-07-07 00:11:58', '2025-07-07 00:22:20', 1),
(33, 27, 17, 'RG e CPF ou CNH', '', 'pendente', NULL, '2025-07-09 10:35:26', NULL, NULL),
(34, 27, 17, 'Comprovante de Endereço', '', 'pendente', NULL, '2025-07-09 10:35:26', NULL, NULL),
(35, 28, 17, 'RG e CPF ou CNH', '', 'pendente', NULL, '2025-07-09 10:38:25', NULL, NULL),
(36, 28, 17, 'Comprovante de Endereço', '', 'pendente', NULL, '2025-07-09 10:38:25', NULL, NULL),
(37, 29, 17, 'RG e CPF ou CNH', '', 'pendente', NULL, '2025-07-09 10:53:29', NULL, NULL),
(38, 29, 17, 'Comprovante de Endereço', '', 'pendente', NULL, '2025-07-09 10:53:29', NULL, NULL),
(39, 31, 17, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva_31/rg-e-cpf-ou-cnh_686e8c2f497ce.png', 'aprovado', NULL, '2025-07-09 15:35:11', '2025-07-09 15:35:36', 1),
(40, 31, 17, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva_31/comprovante-de-endereco_686e8c2f4ab23.png', 'aprovado', NULL, '2025-07-09 15:35:11', '2025-07-09 15:35:39', 1),
(41, 30, 17, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva_30/rg-e-cpf-ou-cnh_686e92f7874ca.png', 'aprovado', NULL, '2025-07-09 16:04:07', '2025-07-09 16:04:34', 1),
(42, 30, 17, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva_30/comprovante-de-endereco_686e92f788ea1.jpg', 'aprovado', NULL, '2025-07-09 16:04:07', '2025-07-09 16:04:39', 1),
(43, 32, 17, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva_32/rg-e-cpf-ou-cnh_686ea783409b0.png', 'pendente', NULL, '2025-07-09 17:31:47', NULL, NULL),
(44, 32, 17, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva_32/comprovante-de-endereco_686ea78341e71.png', 'pendente', NULL, '2025-07-09 17:31:47', NULL, NULL),
(45, 33, 17, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva_33/rg-e-cpf-ou-cnh_686f90fae02a6.jpg', 'pendente', NULL, '2025-07-10 10:07:54', NULL, NULL),
(46, 33, 17, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva_33/comprovante-de-endereco_686f90fae2337.png', 'pendente', NULL, '2025-07-10 10:07:54', NULL, NULL),
(47, 34, 18, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva_34/rg-e-cpf-ou-cnh_686f9f4ae556b.png', 'pendente', NULL, '2025-07-10 11:08:58', NULL, NULL),
(48, 34, 18, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva_34/comprovante-de-endereco_686f9f4aebf46.png', 'pendente', NULL, '2025-07-10 11:08:58', NULL, NULL),
(49, 36, 20, 'RG e CPF ou CNH', '', 'rejeitado', 'Testando', '2025-07-10 10:53:26', '2025-07-10 14:41:51', 1),
(50, 36, 20, 'Comprovante de Endereço', '', 'rejeitado', 'Testando 2', '2025-07-10 10:53:26', '2025-07-10 14:41:59', 1),
(51, 37, 21, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva_37/rg-e-cpf-ou-cnh_686f9f8e745f1.jpg', 'aprovado', NULL, '2025-07-10 11:10:06', '2025-07-10 14:40:44', 1),
(52, 37, 21, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva_37/comprovante-de-endereco_686f9f8e7a1be.jpg', 'aprovado', NULL, '2025-07-10 11:10:06', '2025-07-10 14:40:49', 1),
(53, 39, 23, 'RG e CPF ou CNH', 'uploads/documentos_reservas/reserva_39/rg-e-cpf-ou-cnh_686fdca3aa8a5.png', 'aprovado', NULL, '2025-07-10 15:30:43', '2025-07-10 15:31:23', 1),
(54, 39, 23, 'Comprovante de Endereço', 'uploads/documentos_reservas/reserva_39/comprovante-de-endereco_686fdca3ab7f1.png', 'aprovado', NULL, '2025-07-10 15:30:43', '2025-07-10 15:31:27', 1),
(55, 41, 24, 'RG e CPF ou CNH', '', 'aprovado', NULL, '2025-07-10 15:34:25', '2025-07-10 15:35:05', 1),
(56, 41, 24, 'Comprovante de Endereço', '', 'aprovado', NULL, '2025-07-10 15:34:25', '2025-07-10 15:35:08', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_upload_tokens`
--

CREATE TABLE `documentos_upload_tokens` (
  `id` int NOT NULL,
  `reserva_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_expiracao` datetime NOT NULL,
  `utilizado` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `documentos_upload_tokens`
--

INSERT INTO `documentos_upload_tokens` (`id`, `reserva_id`, `cliente_id`, `token`, `data_criacao`, `data_expiracao`, `utilizado`) VALUES
(3, 5, 6, '046bd42a1d9bb2fdf86e2884cdafbcc5691c3124d043331998749548982f26ad', '2025-07-03 16:42:35', '2025-07-10 18:42:35', 0),
(4, 13, 4, 'a4b0a8622b7bf6dae467ca0c84d3bf5ce64d188b5933be4559c1441577cfd975', '2025-07-04 13:00:46', '2025-07-11 15:00:46', 0),
(5, 19, 14, '062388038232a0417bb1dc663f9ad4e346f87765634309a739794a986300ef9f', '2025-07-04 17:55:37', '2025-07-11 19:55:37', 0),
(6, 20, 4, '79c08f422e34b6cc7c2aac7adb713a9fe7d4a756d57c6619fe1eaaa20a044733', '2025-07-04 19:56:45', '2025-07-11 19:56:45', 0),
(7, 22, 4, 'c51a5eaf6ebfbeeb9e1879fcf2890f20df99fba34c014f5cfa91f64aa3309cad', '2025-07-05 03:01:12', '2025-07-12 03:01:12', 0),
(8, 30, 17, '4562d6417f87f7f0b41fc383260c48d4988990bd0cba6d272d118e21d99b335a', '2025-07-09 14:14:53', '2025-07-16 14:14:53', 0),
(9, 31, 17, '97086fc863b843926229b3cee4998c64e9aa85e7a8d5beb4ee29d58278b1ee4c', '2025-07-09 14:45:20', '2025-07-16 14:45:20', 0),
(10, 31, 17, 'f647efa71f4c31704d79260d10242ec77c4dc020cbad63ba5112f94c4977d0bb', '2025-07-09 15:17:34', '2025-07-16 15:17:34', 0),
(11, 31, 17, 'a369276b6ecae01023281bfe7fc21e9192d087da77f97308cd8e7b51f9234f8c', '2025-07-09 15:18:26', '2025-07-16 15:18:26', 0),
(12, 31, 17, '11ffd0859a05af65d6816a7551856f2ef618c5b0e4b096ae884c0056ef773c07', '2025-07-09 15:18:51', '2025-07-16 15:18:51', 0),
(13, 31, 17, 'a38c48bec58d80a7e14eafe16c138c3fea4b9350100fe1680422bc14afc8c970', '2025-07-09 15:19:22', '2025-07-16 15:19:22', 1),
(14, 28, 17, 'a7c5cdc2b9ee7dad478b385d5c37527bf8a0ed90f912f4f672562ed7fe76c773', '2025-07-09 15:34:20', '2025-07-16 15:34:20', 0),
(15, 30, 17, '7214d4217fc1d8aef9284ef8cdac8075b67fb3d2ee824135c70975a447c9cdde', '2025-07-09 15:36:41', '2025-07-16 15:36:41', 0),
(20, 30, 17, '3b821927c040aeb69a71114f3985d980062b4ce226436ab976b9777854a11acd', '2025-07-09 16:02:37', '2025-07-16 16:02:37', 1),
(21, 28, 17, 'bac20652065a93f7159df5f22c6d8af85406faa84c47c7ef03ff227104493995', '2025-07-09 16:08:11', '2025-07-16 16:08:11', 0),
(22, 32, 17, '889a5ddbd8179c8ac48f35db2f33051e9f9366d1f818c00b4b58f17085924378', '2025-07-09 17:28:04', '2025-07-16 17:28:04', 0),
(23, 32, 17, '90606d6a4ecf7d2c020d6ffd6acc7eaaf2d73c743bd65bd9fc6c2312312eb006', '2025-07-09 17:31:20', '2025-07-16 17:31:20', 1),
(24, 33, 17, '0cc525bd81a39682f9099a567d95dfe0e79e455efe02d439c4b8368089e04658', '2025-07-10 10:04:39', '2025-07-17 10:04:39', 0),
(25, 33, 17, '65ecf925b7b6aca5b0ba9b01a4ce2750245493cb8d9b3ecbf2378152f0ef0e33', '2025-07-10 10:06:59', '2025-07-17 10:06:59', 1),
(26, 34, 18, '8e3bd1e6302dbe9d097da18bc105d544fa339f52594e975a9b68ab12bdf863a2', '2025-07-10 10:46:27', '2025-07-17 10:46:27', 0),
(27, 35, 19, '13a6c0df2f7f22a1c8793100ce3467b82dbcf9220e37fdada5ce078ace13ec31', '2025-07-10 10:46:57', '2025-07-17 10:46:57', 0),
(28, 37, 21, 'e748d4e973ba054f9dedd010ce33e953ec7276d5e4adaf6f92360f93583cf8c0', '2025-07-10 10:54:31', '2025-07-17 10:54:31', 0),
(29, 34, 18, '6cf9a2e42654e9710793fde2e3712718ab5c0f768e0b6657212454bc84f96f58', '2025-07-10 11:08:38', '2025-07-17 11:08:38', 1),
(30, 37, 21, '465f1085b1753a25b70989eccb1c8bd815249dac08a4bc6f765053ca68f07bfc', '2025-07-10 11:09:52', '2025-07-17 11:09:52', 1),
(31, 36, 20, '8a528cf9784a79ab1f6692d183eaf53dce111332c680651c34b0574ffae2b74a', '2025-07-10 14:42:31', '2025-07-17 14:42:31', 0),
(32, 38, 22, '165f2bad2ec1925eaedc2d99d920742805713d7825ec6bdabdbfc8887c0152ac', '2025-07-10 15:19:04', '2025-07-17 15:19:04', 0),
(33, 38, 22, 'f078602e971e5306b19ebd2e23658230927397d2689086808c6a660fadbbf1d8', '2025-07-10 15:19:31', '2025-07-17 15:19:31', 0),
(34, 39, 23, '08ff175ec3c198f14aec01261a94e185d882bf6740946723522ff3cf13d07d07', '2025-07-10 15:21:01', '2025-07-17 15:21:01', 0),
(35, 39, 23, '984a266c6f8f9d3fa218b458ac0949e77086e5634e45d737cad1bbb973a6cd43', '2025-07-10 15:28:43', '2025-07-17 15:28:43', 1),
(36, 40, 17, 'df3f09d482934641d41cc415f1483d68ed4e3db2d16e446fc6a5a7c70eb79d0b', '2025-07-10 15:32:49', '2025-07-17 15:32:49', 0),
(37, 42, 17, '76dc6ecd5128b0abc33a578a69a8840c75dfcb62397990cb93bf08f11f1be06c', '2025-07-10 15:36:07', '2025-07-17 15:36:07', 0),
(38, 42, 17, '6d77d4c80d28e0483b244325f717376ba39876fd2818973a0e74d2b8b172c107', '2025-07-10 15:36:26', '2025-07-17 15:36:26', 0),
(39, 40, 17, '3f3c104feb05f286b667330371633dffb31d6506c8dd6588803cd24bfa6dfc10', '2025-07-10 15:40:28', '2025-07-17 15:40:28', 0),
(40, 43, 25, 'fc5e0f4df1fa6934fd358c71dc8a5e619abb88aee16366be09647aefc33d2ee5', '2025-07-10 16:38:39', '2025-07-17 16:38:39', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `empreendimentos`
--

CREATE TABLE `empreendimentos` (
  `id` int NOT NULL,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_uso` enum('Residencial','Comercial','Misto') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_empreendimento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fase_empreendimento` enum('pre_lancamento','lancamento','em_obra','pronto_para_morar') NOT NULL DEFAULT 'lancamento',
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cep` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `endereco` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cidade` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_localizacao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ativo','pausado') NOT NULL DEFAULT 'ativo',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `momento_envio_documentacao` enum('Na Proposta de Reserva','Após Confirmação de Reserva','Na Assinatura do Contrato') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Após Confirmação de Reserva',
  `documentos_obrigatorios` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `permissoes_visualizacao` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Cliente Final',
  `permissao_reserva` enum('Todos','Corretores Selecionados','Imobiliarias Selecionadas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Todos',
  `limitacao_reservas_corretor` int DEFAULT NULL,
  `limitacao_reservas_imobiliaria` int DEFAULT NULL,
  `prazo_expiracao_reserva` int DEFAULT NULL,
  `outras_areas_comuns` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Outras áreas comuns descritas pelo usuário (Etapa 3)',
  `documentos_necessarios` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `preco_por_m2_sugerido` decimal(20,2) DEFAULT NULL,
  `data_lancamento` date DEFAULT NULL COMMENT 'Data de lançamento do empreendimento (Etapa 1)',
  `previsao_entrega` date DEFAULT NULL COMMENT 'Previsão de entrega do empreendimento (Etapa 1)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empreendimentos`
--

INSERT INTO `empreendimentos` (`id`, `nome`, `tipo_uso`, `tipo_empreendimento`, `fase_empreendimento`, `descricao`, `cep`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `foto_localizacao`, `status`, `data_cadastro`, `data_atualizacao`, `momento_envio_documentacao`, `documentos_obrigatorios`, `permissoes_visualizacao`, `permissao_reserva`, `limitacao_reservas_corretor`, `limitacao_reservas_imobiliaria`, `prazo_expiracao_reserva`, `outras_areas_comuns`, `documentos_necessarios`, `preco_por_m2_sugerido`, `data_lancamento`, `previsao_entrega`) VALUES
(1, 'Empreendimento Teste Central', 'Residencial', 'Apartamento', 'lancamento', 'Descrição de um empreendimento de teste para o SISReserva.', '77000-000', 'Rua Principal', '100', NULL, 'Centro', 'Palmas', 'TO', 'uploads/empreendimentos/main_placeholder.jpg', 'ativo', '2025-07-01 18:23:23', '2025-07-05 03:00:47', 'Após Confirmação de Reserva', '[\"RG\",\"CPF\"]', '[\"Cliente Final\",\"Corretor\",\"Admin\"]', 'Todos', NULL, NULL, 7, NULL, '[]', NULL, NULL, NULL),
(2, 'Edifício Comercial Alfa', 'Comercial', 'Salas Comerciais', 'lancamento', 'Modernas salas comerciais no coração da cidade.', '77000-100', 'Rua Comercial', '500', NULL, 'Setor Comercial', 'Palmas', 'TO', 'uploads/empreendimentos/comercial_placeholder.jpg', 'ativo', '2025-07-01 22:17:54', '2025-07-01 22:17:54', 'Na Assinatura do Contrato', '[\"Contrato Social\",\"Alvará\"]', '[\"Corretor\",\"Admin\"]', 'Corretores Selecionados', NULL, NULL, 15, NULL, '[]', NULL, NULL, NULL),
(3, 'Residencial Central Park', 'Residencial', 'Apartamento', 'lancamento', 'Um novo empreendimento de teste com foco no fluxo de documentação. Ideal para verificar a funcionalidade de envio de documentos junto com a proposta de reserva.', '77001-000', 'Avenida das Avenidas', '1500', NULL, 'Plano Diretor Sul', 'Palmas', 'TO', 'uploads/empreendimentos/main_placeholder.jpg', 'ativo', '2025-07-04 12:38:28', '2025-07-04 13:27:57', 'Na Proposta de Reserva', '[\"RG e CPF ou CNH\", \"Comprovante de Endereço\"]', '[\"Cliente Final\", \"Corretor\", \"Admin\"]', 'Todos', NULL, NULL, 7, NULL, '[]', NULL, NULL, NULL),
(4, 'Smart Living - Studios 2855', 'Residencial', 'Apartamento', 'lancamento', 'O Edifício Infinity Towers representa o ápice da arquitetura moderna e do conforto urbano. Localizado em uma área privilegiada de Palmas, este empreendimento misto oferece unidades residenciais e comerciais de alto padrão, ideal para quem busca qualidade de vida e conveniência. Com uma infraestrutura completa, que inclui piscina aquecida, academia de última geração, salão de festas elegante, espaços de coworking e segurança 24 horas, o Infinity Towers redefine o conceito de moradia e investimento. Cada detalhe foi pensado para proporcionar uma experiência única, desde os acabamentos de luxo até a vista panorâmica da cidade. Perfeito para famílias, jovens profissionais e empresas que buscam um ambiente dinâmico e sofisticado. Explore as diversas opções de plantas e encontre o espaço ideal para você ou seu negócio neste marco arquitetônico.', '77060000', 'Avenida Tocantins', '1234', 'Bloco A', 'Centro', 'Palmas', 'TO', 'uploads/empreendimentos/loc_infinity.jpg', 'ativo', '2025-07-04 16:17:41', '2025-07-04 16:21:05', 'Na Proposta de Reserva', '[\"RG e CPF ou CNH\",\"Comprovante de Endereço\"]', '[\"Cliente Final\",\"Corretor\",\"Admin\"]', 'Todos', NULL, NULL, 7, NULL, '0', NULL, NULL, NULL),
(21, 'Teste Leo', 'Residencial', 'SCP', 'lancamento', 'tESTE lEO', '14021658', 'Alameda dos Jardins', '123', '', 'Jardim Botânico', 'Ribeirão Preto', 'SP', NULL, 'ativo', '2025-07-09 17:09:08', '2025-07-09 17:11:42', 'Na Proposta de Reserva', '[\"RG\",\"CPF\",\"Comprovante de Residencia\"]', 'Cliente Final', 'Todos', NULL, NULL, NULL, '', NULL, 10000.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `empreendimentos_areas_comuns`
--

CREATE TABLE `empreendimentos_areas_comuns` (
  `empreendimento_id` int NOT NULL,
  `area_comum_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empreendimentos_areas_comuns`
--

INSERT INTO `empreendimentos_areas_comuns` (`empreendimento_id`, `area_comum_id`) VALUES
(21, 2),
(21, 4),
(21, 7),
(21, 11),
(21, 12),
(21, 13);

-- --------------------------------------------------------

--
-- Estrutura para tabela `empreendimentos_corretores_permitidos`
--

CREATE TABLE `empreendimentos_corretores_permitidos` (
  `empreendimento_id` int NOT NULL,
  `corretor_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `empreendimentos_imobiliarias_permitidas`
--

CREATE TABLE `empreendimentos_imobiliarias_permitidas` (
  `empreendimento_id` int NOT NULL,
  `imobiliaria_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `imobiliarias`
--

CREATE TABLE `imobiliarias` (
  `id` int NOT NULL,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ativa` tinyint(1) DEFAULT '1',
  `motivo_rejeicao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `motivo_inativacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `admin_id` int DEFAULT NULL,
  `numero` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `imobiliarias`
--

INSERT INTO `imobiliarias` (`id`, `nome`, `cnpj`, `email`, `telefone`, `endereco`, `cidade`, `estado`, `cep`, `data_cadastro`, `data_atualizacao`, `ativa`, `motivo_rejeicao`, `motivo_inativacao`, `admin_id`, `numero`, `complemento`, `bairro`) VALUES
(1, 'Imob Ativa2', '00000000000100', 'contato@imobcentral.com.br', '11988784578', 'Quadra ARNO 21 Alameda 13', 'Palmas', 'TO', '77006886', '2025-07-01 18:23:23', '2025-07-07 02:00:27', 1, NULL, 'Motivo não especificado.', NULL, '23', '', 'Plano Diretor Norte'),
(3, 'Prop Imob 3', '45471210000000', 'prop@sisreserva.com.br', '11987457845', 'Quadra ARNO 21 Alameda 13', 'Palmas', 'TO', '77006886', '2025-07-07 02:07:45', '2025-07-09 00:29:45', 1, NULL, 'Motivo não especificado.', NULL, '1', '', 'Plano Diretor Norte'),
(5, 'Imobiliria XXX', '12323415234523', 'imob@teste.com.br', '12434532452', 'Alameda dos Jardins', 'Ribeirão Preto', 'SP', '14021658', '2025-07-10 15:41:13', '2025-07-10 15:41:20', 1, NULL, 'Motivo não especificado.', NULL, '123', '', 'Jardim Botânico'),
(6, 'Imob3', '23945829034579', '3@teste.com.br', '23411417234', 'Alameda dos Jardins', 'Ribeirão Preto', 'SP', '14021658', '2025-07-10 15:44:33', '2025-07-10 15:44:33', 1, NULL, NULL, 34, '123', '', 'Jardim Botânico');

-- --------------------------------------------------------

--
-- Estrutura para tabela `midias_empreendimentos`
--

CREATE TABLE `midias_empreendimentos` (
  `id` int NOT NULL,
  `empreendimento_id` int NOT NULL,
  `caminho_arquivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('foto_principal','galeria_foto','video','documento_contrato','documento_memorial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `midias_empreendimentos`
--

INSERT INTO `midias_empreendimentos` (`id`, `empreendimento_id`, `caminho_arquivo`, `tipo`, `descricao`, `data_upload`) VALUES
(1, 1, 'uploads/empreendimentos/harmony_principal.jpg', 'foto_principal', NULL, '2025-06-30 15:09:36'),
(2, 2, 'uploads/empreendimentos/harmony_principal.jpg', 'foto_principal', NULL, '2025-06-30 15:10:42'),
(3, 4, 'uploads/empreendimentos/main_infinity.jpg', 'foto_principal', 'Foto Principal do Empreendimento', '2025-07-04 16:17:41'),
(4, 4, 'uploads/empreendimentos/galeria_infinity_1.jpg', 'galeria_foto', 'Fachada do Edifício', '2025-07-04 16:17:41'),
(5, 4, 'uploads/empreendimentos/galeria_infinity_2.jpg', 'galeria_foto', 'Área de Lazer', '2025-07-04 16:17:41'),
(6, 4, 'uploads/empreendimentos/galeria_infinity_3.jpg', 'galeria_foto', 'Academia', '2025-07-04 16:17:41'),
(7, 4, 'uploads/empreendimentos/galeria_infinity_4.jpg', 'galeria_foto', 'Piscina Panorâmica', '2025-07-04 16:17:41'),
(8, 4, 'uploads/empreendimentos/galeria_infinity_5.jpg', 'galeria_foto', 'Espaço Gourmet', '2025-07-04 16:17:41'),
(9, 4, 'uploads/contratos/modelo_contrato_geral.pdf', 'documento_contrato', 'Contrato Padrão do Empreendimento', '2025-07-04 16:17:41'),
(10, 4, 'uploads/documentos/memorial_descritivo_geral.pdf', 'documento_memorial', 'Memorial Descritivo Completo', '2025-07-04 16:17:41'),
(11, 4, 'dQw4w9WgXcQ', 'video', 'Vídeo Institucional - YouTube', '2025-07-04 16:17:41'),
(12, 4, 'uploads/empreendimentos/thumb_video_infinity.jpg', '', 'Thumbnail Vídeos Explore', '2025-07-04 16:17:41'),
(13, 4, 'uploads/empreendimentos/thumb_gallery_infinity.jpg', '', 'Thumbnail Galeria Explore', '2025-07-04 16:17:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiracao` datetime NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `reservas`
--

CREATE TABLE `reservas` (
  `id` int NOT NULL,
  `empreendimento_id` int NOT NULL,
  `unidade_id` int NOT NULL,
  `cliente_principal_id` int DEFAULT NULL,
  `corretor_id` int DEFAULT NULL,
  `data_reserva` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_expiracao` datetime DEFAULT NULL,
  `valor_reserva` decimal(15,2) NOT NULL,
  `status` enum('solicitada','aprovada','documentos_pendentes','documentos_enviados','documentos_aprovados','documentos_rejeitados','contrato_enviado','aguardando_assinatura_eletronica','vendida','cancelada','expirada','dispensada','documentos_solicitados') COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `motivo_cancelamento` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `caminho_contrato_final` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_ultima_interacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `usuario_ultima_interacao` int DEFAULT NULL,
  `comissao_corretor` decimal(15,2) DEFAULT NULL,
  `comissao_imobiliaria` decimal(15,2) DEFAULT NULL,
  `link_documentos_upload` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `reservas`
--

INSERT INTO `reservas` (`id`, `empreendimento_id`, `unidade_id`, `cliente_principal_id`, `corretor_id`, `data_reserva`, `data_expiracao`, `valor_reserva`, `status`, `observacoes`, `motivo_cancelamento`, `caminho_contrato_final`, `data_ultima_interacao`, `usuario_ultima_interacao`, `comissao_corretor`, `comissao_imobiliaria`, `link_documentos_upload`) VALUES
(1, 1, 101, 1, 2, '2025-07-01 22:17:54', NULL, 150000.00, 'aprovada', 'Solicitação de reserva via site público.', NULL, NULL, '2025-07-03 19:41:26', 1, NULL, NULL, NULL),
(2, 1, 201, 2, 10, '2025-06-30 22:17:54', NULL, 250000.00, 'solicitada', 'Solicitação de reserva via site público (mais antiga).', NULL, NULL, '2025-07-04 11:52:56', 1, NULL, NULL, NULL),
(3, 1, 102, 3, 2, '2025-06-29 22:17:54', NULL, 145000.00, 'vendida', 'Reserva já atribuída ao corretor de teste.', NULL, NULL, '2025-07-03 20:48:00', 1, NULL, NULL, NULL),
(4, 2, 301, 4, 2, '2025-07-01 21:17:54', NULL, 300000.00, 'solicitada', 'Reserva para sala comercial.', NULL, NULL, '2025-07-03 19:41:26', 2, NULL, NULL, NULL),
(5, 2, 303, 6, NULL, '2025-07-03 16:42:35', '2025-07-18 18:42:35', 800000.00, 'vendida', '1231231', NULL, NULL, '2025-07-03 20:47:51', NULL, NULL, NULL, NULL),
(10, 1, 201, NULL, NULL, '2025-07-04 11:28:38', '2025-07-11 13:28:38', 250000.00, 'aprovada', '', NULL, NULL, '2025-07-04 11:56:25', 1, NULL, NULL, NULL),
(11, 3, 305, NULL, NULL, '2025-07-04 12:39:32', '2025-07-11 14:39:32', 445000.00, 'vendida', '', NULL, NULL, '2025-07-09 01:21:38', 1, NULL, NULL, NULL),
(12, 3, 308, NULL, 23, '2025-07-04 12:55:47', '2025-07-11 14:55:47', 470000.00, 'aprovada', '', NULL, NULL, '2025-07-08 15:22:12', NULL, NULL, NULL, NULL),
(13, 2, 301, NULL, NULL, '2025-07-04 13:00:46', '2025-07-19 15:00:46', 300000.00, 'dispensada', '', NULL, NULL, '2025-07-08 15:26:18', NULL, NULL, NULL, NULL),
(14, 3, 304, NULL, NULL, '2025-07-04 13:10:55', '2025-07-11 15:10:55', 450000.00, 'cancelada', '', '', NULL, '2025-07-09 01:43:02', 1, NULL, NULL, NULL),
(15, 3, 306, NULL, 12, '2025-07-04 14:23:17', '2025-07-11 16:23:17', 460000.00, 'vendida', '', NULL, NULL, '2025-07-04 15:50:21', 1, NULL, NULL, NULL),
(16, 3, 307, NULL, 23, '2025-07-04 16:06:55', '2025-07-11 18:06:55', 455000.00, 'vendida', 'Teste de envio de Observações', NULL, 'uploads/contratos/contrato_16_6867fc672d4f2.pdf', '2025-07-04 16:08:25', 1, NULL, NULL, NULL),
(17, 4, 408, NULL, 23, '2025-07-04 16:22:56', '2025-07-11 18:22:56', 235000.00, 'vendida', '', NULL, 'uploads/contratos/contrato_17_6868002f0b225.pdf', '2025-07-04 16:24:36', 1, NULL, NULL, NULL),
(18, 4, 399, NULL, 23, '2025-07-04 16:52:49', '2025-07-11 18:52:49', 162500.00, 'vendida', '', NULL, 'uploads/contratos/contrato_18_6868073337472.pdf', '2025-07-04 16:54:36', 1, NULL, NULL, NULL),
(19, 4, 389, NULL, 24, '2025-07-04 17:55:26', '2025-07-11 19:55:26', 161500.00, 'vendida', '', NULL, 'uploads/contratos/contrato_19_686815fe8fcce.pdf', '2025-07-04 17:57:39', 1, NULL, NULL, NULL),
(20, 4, 379, NULL, 25, '2025-07-04 19:56:37', '2025-07-11 19:56:37', 160500.00, 'vendida', '', NULL, 'uploads/contratos/contrato_20_686832b58863a.pdf', '2025-07-04 20:00:11', 1, NULL, NULL, NULL),
(21, 4, 369, NULL, 26, '2025-07-04 20:04:20', '2025-07-11 20:04:20', 159500.00, 'vendida', '', NULL, 'uploads/contratos/contrato_21_68683434d9fa9.pdf', '2025-07-04 20:06:39', 1, NULL, NULL, NULL),
(22, 1, 102, NULL, NULL, '2025-07-05 03:01:12', '2025-07-12 03:01:12', 145000.00, 'cancelada', '', '', NULL, '2025-07-05 16:00:34', 1, NULL, NULL, NULL),
(25, 4, 319, NULL, NULL, '2025-07-07 00:11:58', '2025-07-14 00:11:58', 154500.00, 'vendida', '', NULL, 'uploads/contratos/contrato_25_686dc09644be5.pdf', '2025-07-09 01:07:51', 1, NULL, NULL, NULL),
(27, 4, 312, NULL, NULL, '2025-07-09 10:35:26', '2025-07-16 10:35:26', 477000.00, 'cancelada', '', '', NULL, '2025-07-09 11:08:13', 1, NULL, NULL, NULL),
(28, 4, 313, NULL, NULL, '2025-07-09 10:38:25', '2025-07-16 10:38:25', 153500.00, 'cancelada', '', '', NULL, '2025-07-10 13:49:45', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=bac20652065a93f7159df5f22c6d8af85406faa84c47c7ef03ff227104493995'),
(29, 4, 314, NULL, NULL, '2025-07-09 10:53:29', '2025-07-16 10:53:29', 226000.00, 'cancelada', '', '', NULL, '2025-07-09 11:08:40', 1, NULL, NULL, NULL),
(30, 4, 314, NULL, NULL, '2025-07-09 14:14:53', '2025-07-16 14:14:53', 226000.00, 'vendida', '', NULL, 'uploads/contratos/contrato_30_686e9337d5696.pdf', '2025-07-09 17:00:22', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=3b821927c040aeb69a71114f3985d980062b4ce226436ab976b9777854a11acd'),
(31, 4, 315, NULL, NULL, '2025-07-09 14:45:20', '2025-07-16 14:45:20', 344750.00, 'vendida', 'dfsgsdfgsdfgsdfgsdfg', NULL, NULL, '2025-07-09 15:36:16', 1, NULL, NULL, NULL),
(32, 4, 332, NULL, NULL, '2025-07-09 17:28:04', '2025-07-16 17:28:04', 479000.00, 'documentos_enviados', 'Teste.', NULL, NULL, '2025-07-09 17:31:47', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=90606d6a4ecf7d2c020d6ffd6acc7eaaf2d73c743bd65bd9fc6c2312312eb006'),
(33, 4, 400, NULL, NULL, '2025-07-10 10:04:39', '2025-07-17 10:04:39', 235000.00, 'cancelada', '', '', NULL, '2025-07-10 11:40:19', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=65ecf925b7b6aca5b0ba9b01a4ce2750245493cb8d9b3ecbf2378152f0ef0e33'),
(34, 4, 390, NULL, 23, '2025-07-10 10:45:24', '2025-07-17 10:45:24', 234000.00, 'cancelada', 'Teste de envio de Observações', '', NULL, '2025-07-10 11:40:43', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=6cf9a2e42654e9710793fde2e3712718ab5c0f768e0b6657212454bc84f96f58'),
(35, 4, 380, NULL, NULL, '2025-07-10 10:46:57', '2025-07-17 10:46:57', 233000.00, 'cancelada', '', 'Testando', NULL, '2025-07-10 12:43:55', 1, NULL, NULL, NULL),
(36, 4, 391, NULL, 23, '2025-07-10 10:53:26', '2025-07-17 10:53:26', 352750.00, 'documentos_solicitados', 'Teste', NULL, NULL, '2025-07-10 14:42:31', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=8a528cf9784a79ab1f6692d183eaf53dce111332c680651c34b0574ffae2b74a'),
(37, 4, 380, NULL, NULL, '2025-07-10 10:54:31', '2025-07-17 10:54:31', 233000.00, 'vendida', '', NULL, NULL, '2025-07-10 14:41:18', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=465f1085b1753a25b70989eccb1c8bd815249dac08a4bc6f765053ca68f07bfc'),
(38, 4, 390, NULL, NULL, '2025-07-10 15:19:04', '2025-07-17 15:19:04', 234000.00, 'documentos_solicitados', 'É um facto estabelecido de que um leitor é distraído pelo conteúdo legível de uma página quando analisa a sua mancha gráfica. Logo, o uso de Lorem Ipsum leva a uma distribuição mais ou menos normal de letras, ao contrário do uso de &quot;Conteúdo aqui, conteúdo aqui&quot;, tornando-o texto legível. Muitas ferramentas de publicação electrónica e editores de páginas web usam actualmente o Lorem Ipsum como o modelo de texto usado por omissão, e uma pesquisa por &quot;lorem ipsum&quot; irá encontrar muitos websites ainda na sua infância. Várias versões têm evoluído ao longo dos anos, por vezes por acidente, por vezes propositadamente (como no caso do humor).', NULL, NULL, '2025-07-10 15:19:31', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=f078602e971e5306b19ebd2e23658230927397d2689086808c6a660fadbbf1d8'),
(39, 4, 400, NULL, NULL, '2025-07-10 15:21:01', '2025-07-17 15:21:01', 235000.00, 'vendida', '', NULL, NULL, '2025-07-10 15:31:55', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=984a266c6f8f9d3fa218b458ac0949e77086e5634e45d737cad1bbb973a6cd43'),
(40, 4, 401, NULL, NULL, '2025-07-10 15:32:49', '2025-07-17 15:32:49', 353750.00, 'documentos_solicitados', 'O Lorem Ipsum é um texto modelo da indústria tipográfica e de impressão. O Lorem Ipsum tem vindo a ser o texto padrão usado por estas indústrias desde o ano de 1500, quando uma misturou os caracteres de um texto para criar um espécime de livro. Este texto não só sobreviveu 5 séculos, mas também o salto para a tipografia electrónica, mantendo-se essencialmente inalterada. Foi popularizada nos anos 60 com a disponibilização das folhas de Letraset, que continham passagens com Lorem Ipsum, e mais recentemente com os programas de publicação como o Aldus PageMaker que incluem versões do Lorem Ipsum.', NULL, NULL, '2025-07-10 15:40:28', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=3f3c104feb05f286b667330371633dffb31d6506c8dd6588803cd24bfa6dfc10'),
(41, 4, 402, NULL, 23, '2025-07-10 15:34:25', '2025-07-17 15:34:25', 486000.00, 'vendida', 'É um facto estabelecido de que um leitor é distraído pelo conteúdo legível de uma página quando analisa a sua mancha gráfica. Logo, o uso de Lorem Ipsum leva a uma distribuição mais ou menos normal de letras, ao contrário do uso de &quot;Conteúdo aqui, conteúdo aqui&quot;, tornando-o texto legível. Muitas ferramentas de publicação electrónica e editores de páginas web usam actualmente o Lorem Ipsum como o modelo de texto usado por omissão, e uma pesquisa por &quot;lorem ipsum&quot; irá encontrar muitos websites ainda na sua infância. Várias versões têm evoluído ao longo dos anos, por vezes por acidente, por vezes propositadamente (como no caso do humor).', NULL, NULL, '2025-07-10 15:35:42', 1, NULL, NULL, NULL),
(42, 4, 401, NULL, NULL, '2025-07-10 15:36:07', '2025-07-17 15:36:07', 353750.00, 'cancelada', '', '', NULL, '2025-07-10 15:40:10', 1, NULL, NULL, 'http://localhost/SISReserva_MVP/public/documentos.php?token=6d77d4c80d28e0483b244325f717376ba39876fd2818973a0e74d2b8b172c107'),
(43, 4, 392, NULL, NULL, '2025-07-10 16:38:39', '2025-07-17 16:38:39', 485000.00, 'solicitada', '', NULL, NULL, '2025-07-10 16:38:39', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `reservas_clientes`
--

CREATE TABLE `reservas_clientes` (
  `reserva_id` int NOT NULL,
  `cliente_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `reservas_clientes`
--

INSERT INTO `reservas_clientes` (`reserva_id`, `cliente_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(10, 4),
(11, 4),
(12, 4),
(13, 4),
(14, 4),
(17, 4),
(18, 4),
(20, 4),
(21, 4),
(22, 4),
(25, 4),
(5, 6),
(15, 12),
(16, 13),
(19, 14),
(27, 17),
(28, 17),
(29, 17),
(30, 17),
(31, 17),
(32, 17),
(33, 17),
(40, 17),
(42, 17),
(34, 18),
(35, 19),
(36, 20),
(37, 21),
(38, 22),
(39, 23),
(41, 24),
(43, 25);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_unidades`
--

CREATE TABLE `tipos_unidades` (
  `id` int NOT NULL,
  `empreendimento_id` int NOT NULL,
  `tipo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `metragem` decimal(10,2) NOT NULL,
  `quartos` int NOT NULL,
  `banheiros` int NOT NULL,
  `vagas` int NOT NULL,
  `foto_planta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipos_unidades`
--

INSERT INTO `tipos_unidades` (`id`, `empreendimento_id`, `tipo`, `metragem`, `quartos`, `banheiros`, `vagas`, `foto_planta`, `data_cadastro`, `data_atualizacao`) VALUES
(1, 1, 'Studio Padrão', 35.00, 1, 1, 0, 'uploads/plantas/planta_placeholder.jpg', '2025-07-01 22:17:54', '2025-07-01 22:17:54'),
(2, 1, 'Apartamento 2 Quartos', 60.00, 2, 2, 1, 'uploads/plantas/planta_placeholder.jpg', '2025-07-01 22:17:54', '2025-07-01 22:17:54'),
(3, 2, 'Sala Padrão', 40.00, 0, 1, 1, 'uploads/plantas/sala_comercial_placeholder.jpg', '2025-07-01 22:17:54', '2025-07-01 22:17:54'),
(4, 2, 'Loja Térrea', 120.00, 0, 2, 2, 'uploads/plantas/loja_terrea_placeholder.jpg', '2025-07-01 22:17:54', '2025-07-01 22:17:54'),
(5, 3, 'Apartamento Padrão', 85.50, 3, 2, 2, 'uploads/plantas/planta_placeholder.jpg', '2025-07-04 12:38:28', '2025-07-04 12:38:28'),
(6, 4, 'Studio Compacto', 30.00, 1, 1, 0, 'uploads/plantas/planta_studio.jpg', '2025-07-04 16:17:41', '2025-07-04 16:17:41'),
(7, 4, 'Apartamento 1 Dorm', 45.00, 1, 1, 1, 'uploads/plantas/planta_1dorm.jpg', '2025-07-04 16:17:41', '2025-07-04 16:17:41'),
(8, 4, 'Apartamento 2 Dorms', 68.00, 2, 2, 1, 'uploads/plantas/planta_2dorms.jpg', '2025-07-04 16:17:41', '2025-07-04 16:17:41'),
(9, 4, 'Apartamento 3 Dorms', 95.00, 3, 3, 2, 'uploads/plantas/planta_3dorms.jpg', '2025-07-04 16:17:41', '2025-07-04 16:17:41'),
(16, 21, 'Studio', 51.00, 2, 2, 1, NULL, '2025-07-09 17:10:42', '2025-07-09 17:10:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `unidades`
--

CREATE TABLE `unidades` (
  `id` int NOT NULL,
  `empreendimento_id` int NOT NULL,
  `tipo_unidade_id` int NOT NULL,
  `numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `andar` int NOT NULL,
  `posicao` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area` decimal(10,2) DEFAULT NULL COMMENT 'Área real da unidade em m² (Etapa 4)',
  `multiplier` decimal(5,2) DEFAULT NULL COMMENT 'Multiplicador de valor específico da unidade (Etapa 4)',
  `valor` decimal(15,2) NOT NULL,
  `status` enum('disponivel','reservada','vendida','pausada','bloqueada') NOT NULL DEFAULT 'disponivel',
  `informacoes_pagamento` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `usuario_atualizacao_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `unidades`
--

INSERT INTO `unidades` (`id`, `empreendimento_id`, `tipo_unidade_id`, `numero`, `andar`, `posicao`, `area`, `multiplier`, `valor`, `status`, `informacoes_pagamento`, `data_cadastro`, `data_atualizacao`, `usuario_atualizacao_id`) VALUES
(101, 1, 1, '101', 1, 'Frente', NULL, NULL, 350000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Valor Fixo (R$)\",\"valor\":15000,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":24,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.03,\"tipo_calculo\":\"Proporcional\"}]', '2025-07-01 22:17:54', '2025-07-06 23:58:15', NULL),
(102, 1, 1, '102', 1, 'Lateral', NULL, NULL, 985040.10, 'pausada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Valor Fixo (R$)\",\"valor\":14500,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":24,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.03,\"tipo_calculo\":\"Proporcional\"}]', '2025-07-01 22:17:54', '2025-07-06 23:30:26', NULL),
(201, 1, 2, '201', 2, 'Frente', NULL, NULL, 250000.00, 'reservada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Valor Fixo (R$)\",\"valor\":25000,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":36,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.02,\"tipo_calculo\":\"Proporcional\"}]', '2025-07-01 22:17:54', '2025-07-04 11:56:25', NULL),
(301, 2, 3, '301', 3, 'Nascente', NULL, NULL, 300000.00, 'reservada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Valor Fixo (R$)\",\"valor\":30000,\"tipo_calculo\":\"Fixo\"}]', '2025-07-01 22:17:54', '2025-07-04 13:00:46', NULL),
(302, 2, 3, '302', 3, 'Poente', NULL, NULL, 290000.00, 'reservada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Valor Fixo (R$)\",\"valor\":29000,\"tipo_calculo\":\"Fixo\"}]', '2025-07-01 22:17:54', '2025-07-01 22:17:54', NULL),
(303, 2, 4, 'Loja 01', 0, 'Térreo', NULL, NULL, 800000.00, 'reservada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Valor Fixo (R$)\",\"valor\":80000,\"tipo_calculo\":\"Fixo\"}]', '2025-07-01 22:17:54', '2025-07-03 16:42:35', NULL),
(304, 3, 5, '101', 1, 'Frente', NULL, NULL, 450000.00, 'disponivel', '[\r\n    {\"descricao\": \"Sinal\", \"quantas_vezes\": 1, \"tipo_valor\": \"Valor Fixo (R$)\", \"valor\": 30000.00, \"tipo_calculo\": \"Fixo\"},\r\n    {\"descricao\": \"Parcelas Mensais\", \"quantas_vezes\": 60, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.005, \"tipo_calculo\": \"Proporcional\"},\r\n    {\"descricao\": \"Financiamento\", \"quantas_vezes\": 1, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.70, \"tipo_calculo\": \"Proporcional\"}\r\n]', '2025-07-04 12:38:28', '2025-07-09 01:43:02', NULL),
(305, 3, 5, '102', 1, 'Lateral Esquerda', NULL, NULL, 985000.00, 'vendida', '[\r\n    {\"descricao\": \"Sinal\", \"quantas_vezes\": 1, \"tipo_valor\": \"Valor Fixo (R$)\", \"valor\": 30000.00, \"tipo_calculo\": \"Fixo\"},\r\n    {\"descricao\": \"Parcelas Mensais\", \"quantas_vezes\": 60, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.005, \"tipo_calculo\": \"Proporcional\"},\r\n    {\"descricao\": \"Financiamento\", \"quantas_vezes\": 1, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.70, \"tipo_calculo\": \"Proporcional\"}\r\n]', '2025-07-04 12:38:28', '2025-07-09 01:21:38', NULL),
(306, 3, 5, '201', 2, 'Frente', NULL, NULL, 460000.00, 'vendida', '[\r\n    {\"descricao\": \"Sinal\", \"quantas_vezes\": 1, \"tipo_valor\": \"Valor Fixo (R$)\", \"valor\": 30000.00, \"tipo_calculo\": \"Fixo\"},\r\n    {\"descricao\": \"Parcelas Mensais\", \"quantas_vezes\": 60, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.005, \"tipo_calculo\": \"Proporcional\"},\r\n    {\"descricao\": \"Financiamento\", \"quantas_vezes\": 1, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.70, \"tipo_calculo\": \"Proporcional\"}\r\n]', '2025-07-04 12:38:28', '2025-07-04 15:50:21', NULL),
(307, 3, 5, '202', 2, 'Lateral Direita', NULL, NULL, 455000.00, 'vendida', '[\r\n    {\"descricao\": \"Sinal\", \"quantas_vezes\": 1, \"tipo_valor\": \"Valor Fixo (R$)\", \"valor\": 30000.00, \"tipo_calculo\": \"Fixo\"},\r\n    {\"descricao\": \"Parcelas Mensais\", \"quantas_vezes\": 60, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.005, \"tipo_calculo\": \"Proporcional\"},\r\n    {\"descricao\": \"Financiamento\", \"quantas_vezes\": 1, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.70, \"tipo_calculo\": \"Proporcional\"}\r\n]', '2025-07-04 12:38:28', '2025-07-04 16:08:25', NULL),
(308, 3, 5, '301', 3, 'Frente', NULL, NULL, 470000.00, 'reservada', '[\r\n    {\"descricao\": \"Sinal\", \"quantas_vezes\": 1, \"tipo_valor\": \"Valor Fixo (R$)\", \"valor\": 30000.00, \"tipo_calculo\": \"Fixo\"},\r\n    {\"descricao\": \"Parcelas Mensais\", \"quantas_vezes\": 60, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.005, \"tipo_calculo\": \"Proporcional\"},\r\n    {\"descricao\": \"Financiamento\", \"quantas_vezes\": 1, \"tipo_valor\": \"Percentual (%)\", \"valor\": 0.70, \"tipo_calculo\": \"Proporcional\"}\r\n]', '2025-07-04 12:38:28', '2025-07-04 12:55:47', NULL),
(310, 4, 7, '102', 1, '2', NULL, NULL, 854210.00, 'pausada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-09 17:22:59', NULL),
(312, 4, 9, '104', 1, '4', NULL, NULL, 477000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-09 11:08:13', NULL),
(313, 4, 6, '105', 1, '5', NULL, NULL, 153500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-10 13:49:45', NULL),
(314, 4, 7, '106', 1, '6', NULL, NULL, 226000.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-09 17:00:22', 1),
(315, 4, 8, '107', 1, '7', NULL, NULL, 344750.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-09 15:36:16', NULL),
(316, 4, 9, '108', 1, '8', NULL, NULL, 477000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(317, 4, 6, '109', 1, '9', NULL, NULL, 153500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(318, 4, 7, '110', 1, '10', NULL, NULL, 226000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(319, 4, 6, '201', 2, '1', NULL, NULL, 154500.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-09 01:07:51', NULL),
(320, 4, 7, '202', 2, '2', NULL, NULL, 227000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(321, 4, 8, '203', 2, '3', NULL, NULL, 345750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(322, 4, 9, '204', 2, '4', NULL, NULL, 478000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(323, 4, 6, '205', 2, '5', NULL, NULL, 154500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(324, 4, 7, '206', 2, '6', NULL, NULL, 227000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(325, 4, 8, '207', 2, '7', NULL, NULL, 345750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(326, 4, 9, '208', 2, '8', NULL, NULL, 478000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(327, 4, 6, '209', 2, '9', NULL, NULL, 154500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(328, 4, 7, '210', 2, '10', NULL, NULL, 227000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(329, 4, 6, '301', 3, '1', NULL, NULL, 155500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(330, 4, 7, '302', 3, '2', NULL, NULL, 228000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(331, 4, 8, '303', 3, '3', NULL, NULL, 346750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(332, 4, 9, '304', 3, '4', NULL, NULL, 479000.00, 'reservada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-09 17:31:10', NULL),
(333, 4, 6, '305', 3, '5', NULL, NULL, 155500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(334, 4, 7, '306', 3, '6', NULL, NULL, 228000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(335, 4, 8, '307', 3, '7', NULL, NULL, 346750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(336, 4, 9, '308', 3, '8', NULL, NULL, 479000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(337, 4, 6, '309', 3, '9', NULL, NULL, 155500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(338, 4, 7, '310', 3, '10', NULL, NULL, 228000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(339, 4, 6, '401', 4, '1', NULL, NULL, 156500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(340, 4, 7, '402', 4, '2', NULL, NULL, 229000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(341, 4, 8, '403', 4, '3', NULL, NULL, 347750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(342, 4, 9, '404', 4, '4', NULL, NULL, 480000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(343, 4, 6, '405', 4, '5', NULL, NULL, 156500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(344, 4, 7, '406', 4, '6', NULL, NULL, 229000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(345, 4, 8, '407', 4, '7', NULL, NULL, 347750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(346, 4, 9, '408', 4, '8', NULL, NULL, 480000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(347, 4, 6, '409', 4, '9', NULL, NULL, 156500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(348, 4, 7, '410', 4, '10', NULL, NULL, 229000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(349, 4, 6, '501', 5, '1', NULL, NULL, 157500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(350, 4, 7, '502', 5, '2', NULL, NULL, 230000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(351, 4, 8, '503', 5, '3', NULL, NULL, 348750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(352, 4, 9, '504', 5, '4', NULL, NULL, 481000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(353, 4, 6, '505', 5, '5', NULL, NULL, 157500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(354, 4, 7, '506', 5, '6', NULL, NULL, 230000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(355, 4, 8, '507', 5, '7', NULL, NULL, 348750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(356, 4, 9, '508', 5, '8', NULL, NULL, 481000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(357, 4, 6, '509', 5, '9', NULL, NULL, 157500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(358, 4, 7, '510', 5, '10', NULL, NULL, 230000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(359, 4, 6, '601', 6, '1', NULL, NULL, 158500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(360, 4, 7, '602', 6, '2', NULL, NULL, 231000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(361, 4, 8, '603', 6, '3', NULL, NULL, 349750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(362, 4, 9, '604', 6, '4', NULL, NULL, 482000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(363, 4, 6, '605', 6, '5', NULL, NULL, 158500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(364, 4, 7, '606', 6, '6', NULL, NULL, 231000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(365, 4, 8, '607', 6, '7', NULL, NULL, 349750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(366, 4, 9, '608', 6, '8', NULL, NULL, 482000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(367, 4, 6, '609', 6, '9', NULL, NULL, 158500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(368, 4, 7, '610', 6, '10', NULL, NULL, 231000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(369, 4, 6, '701', 7, '1', NULL, NULL, 159500.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 20:06:39', NULL),
(370, 4, 7, '702', 7, '2', NULL, NULL, 232000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(371, 4, 8, '703', 7, '3', NULL, NULL, 350750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(372, 4, 9, '704', 7, '4', NULL, NULL, 483000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(373, 4, 6, '705', 7, '5', NULL, NULL, 159500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(374, 4, 7, '706', 7, '6', NULL, NULL, 232000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(375, 4, 8, '707', 7, '7', NULL, NULL, 350750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(376, 4, 9, '708', 7, '8', NULL, NULL, 483000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(377, 4, 6, '709', 7, '9', NULL, NULL, 159500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(378, 4, 7, '710', 7, '10', NULL, NULL, 232000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(379, 4, 6, '801', 8, '1', NULL, NULL, 160500.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 20:00:11', NULL),
(380, 4, 7, '802', 8, '2', NULL, NULL, 233000.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-10 14:41:18', 1),
(381, 4, 8, '803', 8, '3', NULL, NULL, 351750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(382, 4, 9, '804', 8, '4', NULL, NULL, 484000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(383, 4, 6, '805', 8, '5', NULL, NULL, 160500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(384, 4, 7, '806', 8, '6', NULL, NULL, 233000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(385, 4, 8, '807', 8, '7', NULL, NULL, 351750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(386, 4, 9, '808', 8, '8', NULL, NULL, 484000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(387, 4, 6, '809', 8, '9', NULL, NULL, 160500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(388, 4, 7, '810', 8, '10', NULL, NULL, 233000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(389, 4, 6, '901', 9, '1', NULL, NULL, 161500.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 17:57:39', NULL),
(390, 4, 7, '902', 9, '2', NULL, NULL, 234000.00, 'reservada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-10 15:19:27', NULL),
(391, 4, 8, '903', 9, '3', NULL, NULL, 352750.00, 'reservada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-10 10:53:26', NULL),
(392, 4, 9, '904', 9, '4', NULL, NULL, 485000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(393, 4, 6, '905', 9, '5', NULL, NULL, 161500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(394, 4, 7, '906', 9, '6', NULL, NULL, 234000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(395, 4, 8, '907', 9, '7', NULL, NULL, 352750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(396, 4, 9, '908', 9, '8', NULL, NULL, 485000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(397, 4, 6, '909', 9, '9', NULL, NULL, 161500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(398, 4, 7, '910', 9, '10', NULL, NULL, 234000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(399, 4, 6, '1001', 10, '1', NULL, NULL, 162500.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:54:36', NULL),
(400, 4, 7, '1002', 10, '2', NULL, NULL, 235000.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-10 15:31:55', 1);
INSERT INTO `unidades` (`id`, `empreendimento_id`, `tipo_unidade_id`, `numero`, `andar`, `posicao`, `area`, `multiplier`, `valor`, `status`, `informacoes_pagamento`, `data_cadastro`, `data_atualizacao`, `usuario_atualizacao_id`) VALUES
(401, 4, 8, '1003', 10, '3', NULL, NULL, 353750.00, 'reservada', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-10 15:40:24', NULL),
(402, 4, 9, '1004', 10, '4', NULL, NULL, 486000.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-10 15:35:42', 1),
(403, 4, 6, '1005', 10, '5', NULL, NULL, 162500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(404, 4, 7, '1006', 10, '6', NULL, NULL, 235000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(405, 4, 8, '1007', 10, '7', NULL, NULL, 353750.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(406, 4, 9, '1008', 10, '8', NULL, NULL, 486000.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(407, 4, 6, '1009', 10, '9', NULL, NULL, 162500.00, 'disponivel', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:17:41', NULL),
(408, 4, 7, '1010', 10, '10', NULL, NULL, 235000.00, 'vendida', '[{\"descricao\":\"Sinal\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.1,\"tipo_calculo\":\"Fixo\"},{\"descricao\":\"Mensais\",\"quantas_vezes\":48,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.015,\"tipo_calculo\":\"Proporcional\"},{\"descricao\":\"Chaves\",\"quantas_vezes\":1,\"tipo_valor\":\"Percentual (%)\",\"valor\":0.18,\"tipo_calculo\":\"Fixo\"}]', '2025-07-04 16:17:41', '2025-07-04 16:24:36', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creci` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('admin','corretor_autonomo','corretor_imobiliaria','admin_imobiliaria') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `imobiliaria_id` int DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `aprovado` tinyint(1) DEFAULT '0',
  `ativo` tinyint(1) DEFAULT '1',
  `foto_perfil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `cpf`, `creci`, `tipo`, `status`, `imobiliaria_id`, `data_cadastro`, `data_atualizacao`, `data_aprovacao`, `aprovado`, `ativo`, `foto_perfil`, `telefone`) VALUES
(1, 'Admin Master', 'admin@sisreserva.com.br', '$2y$10$yGjlyp2R398q2GoA3djRYOm5wzvuQoLFlhvx5ptzkfqWY4z8jIaIO', NULL, NULL, 'admin', 'ativo', NULL, '2025-07-01 18:12:11', '2025-07-02 16:13:13', NULL, 1, 1, NULL, NULL),
(2, 'Corretor Editado', 'corretor@sisreserva.com.br', '$2y$10$GFS2Iuy29kvJdTWeXpUF4ehyDo.f8VtciByHzbr4uCasIfT5sehH.', '11111111111', '11111111', 'corretor_autonomo', 'ativo', NULL, '2025-07-01 18:23:23', '2025-07-09 12:20:17', '2025-07-02 15:19:43', 1, 1, NULL, ''),
(3, 'Admin Imobiliária', 'imobiliaria@sisreserva.com.br', '$2y$10$rZq5eqev7vg.KL6T7sV3eumgk0KAjX9zaUjjbQ1ebYAmccIcwLgzS', NULL, NULL, 'corretor_autonomo', 'ativo', NULL, '2025-07-01 22:17:54', '2025-07-07 01:12:27', NULL, 1, 1, NULL, NULL),
(10, 'Teste Novo Usuario', 'teste@teste.com', '$2y$10$FcGncd7sxsUjN20wNBpkC.z56.PzoRv3gPvO.o55jyrt7PCgZ8G62', '11231231231', '12312313', 'corretor_autonomo', 'pendente', NULL, '2025-07-02 16:14:54', '2025-07-02 16:14:54', NULL, 1, 1, NULL, '63992264887'),
(11, 'Francisco Antunes de Souza', 'francisco@imobiliaria.com', '$2y$10$SgAOD.Unx0uhlaR6yTKjP.R5CmJRyUufLMGB5j74ZaJRspI8512H.', '01578912312', '11115487', 'corretor_imobiliaria', 'pendente', 1, '2025-07-02 17:03:53', '2025-07-08 02:45:20', '2025-07-03 21:39:22', 1, 0, NULL, '63992264887'),
(12, 'Francisco Editado', 'suiteplace11@gmail.com', '$2y$10$oGSOSmY6Q6Td1jZE3QvoRuoUKvytuAYnNNaY2/PlAAZcGpXujbIfO', '75085678712', '5813212', 'corretor_autonomo', 'pendente', NULL, '2025-07-02 17:05:33', '2025-07-07 01:12:27', '2025-07-04 14:21:04', 1, 1, NULL, '63992264887'),
(13, '12Corretor', '12@teste.com', '$2y$10$dpeivaqwK2475Qujg/lA2eJRRzFBr./jbRgW/xlWEBhjoMQk7Yr6G', '05614874201', '1165445', 'corretor_imobiliaria', 'pendente', 1, '2025-07-02 17:07:07', '2025-07-04 15:10:15', NULL, 0, 0, NULL, '63992264887'),
(14, 'Corretor Leste', 'leste@teste.com', '$2y$10$HJnlxtr2PRlZRzAc71cliu4LfCOQrjLsOezAPKhgIOJqloTbJvNUC', '43568976433', '123123fd', 'corretor_autonomo', 'pendente', NULL, '2025-07-02 17:09:27', '2025-07-08 02:54:29', '2025-07-04 14:04:19', 1, 0, NULL, '63992264887'),
(15, 'Francisco EDITADO', 'edit@uft.edu.br', '$2y$10$kp1lmsEBEl1l/jiklHqLSujaWQY8vrxrvE07bw05jqbODVoXE.pEe', '53676323889', '4654646', 'corretor_autonomo', 'pendente', NULL, '2025-07-02 17:20:31', '2025-07-07 01:12:27', '2025-07-03 21:33:02', 1, 1, NULL, '63992264887'),
(20, 'Imob Central', 'franciscoas@uft.edu.br', '$2y$10$xeQ3f/zrhVZuDAJe1f1jaeiRIinIiiFuxVjrQ7z89N5yUgr3N39eW', '13241231231', '23123123123123123132', 'admin_imobiliaria', 'pendente', 1, '2025-07-03 21:44:45', '2025-07-03 21:44:45', NULL, 1, 1, NULL, '12312321312'),
(21, 'Corretor ImoBLeste', 'imobleste@teste.com', '$2y$10$c89OTK7M6eNu5VI8DgodQO25kVHrAOUQADQBAl8tbT20MrVjbmRsm', '12312312312', '1313', 'corretor_autonomo', 'pendente', NULL, '2025-07-04 13:40:43', '2025-07-07 01:12:27', '2025-07-04 14:07:44', 1, 1, NULL, '63992264887'),
(22, 'Editado pela Imobiaria Teste', 'imobleste@gmail.com', '$2y$10$1Af1Kwpn90KE8WFqgEy4XeqBi31.2Y7ZcfQXswUU4g3d82kZsVsxK', '12487065010', '111111111111', 'corretor_autonomo', 'pendente', NULL, '2025-07-04 13:54:46', '2025-07-07 01:12:27', '2025-07-04 14:02:18', 1, 1, NULL, '63992264887'),
(23, 'Joao Leste', 'joaoleste@teste.com', '$2y$10$uF3QAk90rWvzVkc/tfiucuU5GuySjXawltl2QGZ/L9djUmswoF3se', '12359878445', '234234', 'corretor_autonomo', 'pendente', NULL, '2025-07-04 15:21:44', '2025-07-07 01:12:27', '2025-07-04 15:23:04', 1, 1, NULL, '63992264887'),
(24, 'Teste1234234', 'corretor2@teste.com', '$2y$10$MivYgzcMAvOjdYo4KwVgOOFrmP7Lc0yGA8YyMnESi6/YKL0yozJl6', '14523562435', '1234', 'corretor_autonomo', 'pendente', NULL, '2025-07-04 17:53:32', '2025-07-09 16:45:54', '2025-07-04 17:54:00', 1, 0, NULL, '12323123123'),
(25, 'Ulisses', 'leste@leste.com', '$2y$10$7/9bsCY7ZjIwlMUTFkOZjOBK44ym02nGgzZz52AlJlvM.iMYmKw4u', '78985621338', '1248', 'corretor_autonomo', 'pendente', NULL, '2025-07-04 19:54:53', '2025-07-08 02:42:07', '2025-07-04 19:55:24', 1, 0, NULL, '63992264887'),
(26, 'Ulisses Editado', 'ulisses@editado.com', '$2y$10$48FfeVsNn.IRMfkUBQ0fWeSnz8tWBi2Ow3V1mTNPLYKl9oZc/dH2C', '54786534878', '245', 'corretor_autonomo', 'pendente', NULL, '2025-07-04 20:02:55', '2025-07-08 02:42:00', '2025-07-04 20:03:24', 1, 0, NULL, '63992264887'),
(27, 'Francisco Editado', 'franc123123123iscoas@uft.edu.br', '$2y$10$i4yZDyUeNQO12ZZD59jcnu7ByOX7aQG6YXpTnMpD1Ga1aUzu5X2JW', '98978946132', '23423', 'corretor_autonomo', 'pendente', NULL, '2025-07-06 18:09:43', '2025-07-08 10:39:36', NULL, 1, 0, NULL, '11111111111'),
(32, 'EDITADOMARCUS', 'marcussarkis@gmail.com', '$2y$10$RSr/zHFtSHilON1oV5qA8ucthjUSoXSW2lhqjf9p65NYNP.HgoMd2', '33245621421', '9087234', 'admin', 'pendente', NULL, '2025-07-08 10:17:15', '2025-07-09 16:46:29', '2025-07-08 10:17:15', 1, 0, NULL, '11999999999'),
(33, 'Francisco Antunes de Souza', 'franccxcvzxcviscoas@uft.edu.br', '$2y$10$oayhtyYxuSNdAygGFLiqmO8FZ41pOcLvsqO2U0c34N3lYcyN3alDG', '54367890876', '46543', 'corretor_autonomo', 'pendente', NULL, '2025-07-09 01:02:52', '2025-07-09 16:46:10', '2025-07-09 01:05:11', 1, 1, NULL, '87891321113'),
(34, 'Admin Imob XXX', 'admin@imob.com.br', '$2y$10$cvd3HakTXuQRqW.GH79aPeX4ATGs98xh/ou7xvKxMhcxmMfSRd8iC', '98461321654', '2316545', 'admin_imobiliaria', 'pendente', 6, '2025-07-10 15:42:29', '2025-07-10 15:44:33', '2025-07-10 15:42:34', 1, 1, NULL, '11545494987'),
(35, 'Corretor 3', '4@teste.com.br', '$2y$10$wNN/lGpi6u84zUTPNsCzeuOx82qDDgTE6xwY0f3qTF73/oHKzuKf.', '14123412341', '1231243', 'corretor_imobiliaria', 'pendente', 6, '2025-07-10 15:45:44', '2025-07-10 15:47:45', '2025-07-10 18:45:44', 1, 1, NULL, '11245434523');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_alerta_usuario` (`usuario_id`);

--
-- Índices de tabela `areas_comuns_catalogo`
--
ALTER TABLE `areas_comuns_catalogo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_auditoria_usuario` (`usuario_id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `documentos_reserva`
--
ALTER TABLE `documentos_reserva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_documentos_reserva_reserva` (`reserva_id`),
  ADD KEY `fk_documentos_reserva_cliente` (`cliente_id`),
  ADD KEY `fk_documentos_reserva_usuario_analise` (`usuario_analise_id`);

--
-- Índices de tabela `documentos_upload_tokens`
--
ALTER TABLE `documentos_upload_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_upload_token_reserva` (`reserva_id`),
  ADD KEY `fk_upload_token_cliente` (`cliente_id`);

--
-- Índices de tabela `empreendimentos`
--
ALTER TABLE `empreendimentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `empreendimentos_areas_comuns`
--
ALTER TABLE `empreendimentos_areas_comuns`
  ADD PRIMARY KEY (`empreendimento_id`,`area_comum_id`),
  ADD KEY `area_comum_id` (`area_comum_id`);

--
-- Índices de tabela `empreendimentos_corretores_permitidos`
--
ALTER TABLE `empreendimentos_corretores_permitidos`
  ADD PRIMARY KEY (`empreendimento_id`,`corretor_id`),
  ADD KEY `corretor_id` (`corretor_id`);

--
-- Índices de tabela `empreendimentos_imobiliarias_permitidas`
--
ALTER TABLE `empreendimentos_imobiliarias_permitidas`
  ADD PRIMARY KEY (`empreendimento_id`,`imobiliaria_id`),
  ADD KEY `imobiliaria_id` (`imobiliaria_id`);

--
-- Índices de tabela `imobiliarias`
--
ALTER TABLE `imobiliarias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_imobiliaria_admin` (`admin_id`);

--
-- Índices de tabela `midias_empreendimentos`
--
ALTER TABLE `midias_empreendimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_midia_empreendimento` (`empreendimento_id`);

--
-- Índices de tabela `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`);

--
-- Índices de tabela `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reserva_empreendimento` (`empreendimento_id`),
  ADD KEY `fk_reserva_unidade` (`unidade_id`),
  ADD KEY `fk_reserva_corretor` (`corretor_id`),
  ADD KEY `fk_reserva_usuario_interacao` (`usuario_ultima_interacao`),
  ADD KEY `fk_reserva_cliente_principal` (`cliente_principal_id`);

--
-- Índices de tabela `reservas_clientes`
--
ALTER TABLE `reservas_clientes`
  ADD PRIMARY KEY (`reserva_id`,`cliente_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `tipos_unidades`
--
ALTER TABLE `tipos_unidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tipo_unidade_empreendimento` (`empreendimento_id`);

--
-- Índices de tabela `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `empreendimento_id` (`empreendimento_id`,`numero`),
  ADD KEY `fk_unidade_tipo_unidade` (`tipo_unidade_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `fk_usuario_imobiliaria` (`imobiliaria_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=599;

--
-- AUTO_INCREMENT de tabela `areas_comuns_catalogo`
--
ALTER TABLE `areas_comuns_catalogo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `documentos_reserva`
--
ALTER TABLE `documentos_reserva`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de tabela `documentos_upload_tokens`
--
ALTER TABLE `documentos_upload_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de tabela `empreendimentos`
--
ALTER TABLE `empreendimentos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `imobiliarias`
--
ALTER TABLE `imobiliarias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `midias_empreendimentos`
--
ALTER TABLE `midias_empreendimentos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `tipos_unidades`
--
ALTER TABLE `tipos_unidades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=409;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `documentos_reserva`
--
ALTER TABLE `documentos_reserva`
  ADD CONSTRAINT `fk_documentos_reserva_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_documentos_reserva_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_documentos_reserva_usuario_analise` FOREIGN KEY (`usuario_analise_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `documentos_upload_tokens`
--
ALTER TABLE `documentos_upload_tokens`
  ADD CONSTRAINT `fk_upload_token_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_upload_token_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `empreendimentos_areas_comuns`
--
ALTER TABLE `empreendimentos_areas_comuns`
  ADD CONSTRAINT `empreendimentos_areas_comuns_ibfk_1` FOREIGN KEY (`empreendimento_id`) REFERENCES `empreendimentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `empreendimentos_areas_comuns_ibfk_2` FOREIGN KEY (`area_comum_id`) REFERENCES `areas_comuns_catalogo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `empreendimentos_corretores_permitidos`
--
ALTER TABLE `empreendimentos_corretores_permitidos`
  ADD CONSTRAINT `empreendimentos_corretores_permitidos_ibfk_1` FOREIGN KEY (`empreendimento_id`) REFERENCES `empreendimentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `empreendimentos_corretores_permitidos_ibfk_2` FOREIGN KEY (`corretor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `empreendimentos_imobiliarias_permitidas`
--
ALTER TABLE `empreendimentos_imobiliarias_permitidas`
  ADD CONSTRAINT `empreendimentos_imobiliarias_permitidas_ibfk_1` FOREIGN KEY (`empreendimento_id`) REFERENCES `empreendimentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `empreendimentos_imobiliarias_permitidas_ibfk_2` FOREIGN KEY (`imobiliaria_id`) REFERENCES `imobiliarias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `imobiliarias`
--
ALTER TABLE `imobiliarias`
  ADD CONSTRAINT `fk_imobiliaria_admin` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `midias_empreendimentos`
--
ALTER TABLE `midias_empreendimentos`
  ADD CONSTRAINT `fk_midia_empreendimento` FOREIGN KEY (`empreendimento_id`) REFERENCES `empreendimentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reserva_cliente_principal` FOREIGN KEY (`cliente_principal_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reserva_corretor` FOREIGN KEY (`corretor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reserva_empreendimento` FOREIGN KEY (`empreendimento_id`) REFERENCES `empreendimentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reserva_unidade` FOREIGN KEY (`unidade_id`) REFERENCES `unidades` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reserva_usuario_interacao` FOREIGN KEY (`usuario_ultima_interacao`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `reservas_clientes`
--
ALTER TABLE `reservas_clientes`
  ADD CONSTRAINT `reservas_clientes_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_clientes_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tipos_unidades`
--
ALTER TABLE `tipos_unidades`
  ADD CONSTRAINT `fk_tipo_unidade_empreendimento` FOREIGN KEY (`empreendimento_id`) REFERENCES `empreendimentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `unidades`
--
ALTER TABLE `unidades`
  ADD CONSTRAINT `fk_unidade_empreendimento` FOREIGN KEY (`empreendimento_id`) REFERENCES `empreendimentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_unidade_tipo_unidade` FOREIGN KEY (`tipo_unidade_id`) REFERENCES `tipos_unidades` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_imobiliaria` FOREIGN KEY (`imobiliaria_id`) REFERENCES `imobiliarias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
