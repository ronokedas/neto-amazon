-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 12/06/2026 às 06:48
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `erp_sistema`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `certificados_csn`
--

CREATE TABLE `certificados_csn` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `numero` varchar(30) NOT NULL,
  `token_assinatura` char(64) NOT NULL,
  `nome_embarcacao` varchar(200) NOT NULL,
  `numero_inscricao` varchar(80) DEFAULT NULL,
  `indicativo_chamada` varchar(80) DEFAULT NULL,
  `atividades_servicos` varchar(200) DEFAULT NULL,
  `tipo_embarcacao` varchar(200) DEFAULT NULL,
  `ano_construcao` varchar(10) DEFAULT NULL,
  `comprimento_m` decimal(8,2) DEFAULT NULL,
  `arqueacao_bruta` varchar(50) DEFAULT NULL,
  `tipo_navegacao` varchar(200) DEFAULT NULL,
  `area_navegacao` varchar(200) DEFAULT NULL,
  `fabricante_motor` varchar(300) DEFAULT NULL,
  `potencia_kw` varchar(50) DEFAULT NULL,
  `material_casco` varchar(100) DEFAULT NULL,
  `autorizado_carga` tinyint(1) DEFAULT 0,
  `qtd_passageiros` int(11) DEFAULT 0,
  `obs_passageiros` varchar(100) DEFAULT NULL,
  `relatorio_numero` varchar(100) DEFAULT NULL,
  `data_vistoria_seco` date DEFAULT NULL,
  `data_vistoria_flutuando` date DEFAULT NULL,
  `local_vistoria` varchar(200) DEFAULT NULL,
  `acessibilidade_sim` tinyint(1) DEFAULT 0,
  `acessibilidade_nao` tinyint(1) DEFAULT 1,
  `data_emissao` date NOT NULL,
  `data_validade` date NOT NULL,
  `local_emissao` varchar(100) DEFAULT 'Belém-PA',
  `assinante_nome` varchar(200) DEFAULT NULL,
  `assinante_titulo` varchar(200) DEFAULT NULL,
  `assinante_registro` varchar(100) DEFAULT NULL,
  `assinatura_imagem` longtext DEFAULT NULL,
  `assinatura_ip` varchar(45) DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT 0,
  `status` enum('rascunho','emitido','assinado','cancelado') DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `certificados_csn`
--

INSERT INTO `certificados_csn` (`id`, `numero`, `token_assinatura`, `nome_embarcacao`, `numero_inscricao`, `indicativo_chamada`, `atividades_servicos`, `tipo_embarcacao`, `ano_construcao`, `comprimento_m`, `arqueacao_bruta`, `tipo_navegacao`, `area_navegacao`, `fabricante_motor`, `potencia_kw`, `material_casco`, `autorizado_carga`, `qtd_passageiros`, `obs_passageiros`, `relatorio_numero`, `data_vistoria_seco`, `data_vistoria_flutuando`, `local_vistoria`, `acessibilidade_sim`, `acessibilidade_nao`, `data_emissao`, `data_validade`, `local_emissao`, `assinante_nome`, `assinante_titulo`, `assinante_registro`, `assinatura_imagem`, `assinatura_ip`, `assinatura_em`, `assinado`, `status`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('0c22ca75-6be7-483c-a516-89439eaff47c', 'AM-CSN-4/26', 'aefe671fbb11155b92eedad397779f0819d47daf5b54667e70e1b4f6e3e10516', 'Barco Sao joao', 'PA-3456-AMa', '', 'navegadoeeee', 'navio', '2022', 22.00, '12', 'MAR ABERTO', 'Apoio Marítimo,Área 1', '', '', 'aço', 1, 23, 'homens todos', '', '2026-06-10', '2026-07-12', 'seco', 0, 1, '2026-06-12', '2026-07-12', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'rascunho', 0, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-12 01:30:19', '2026-06-12 01:31:56'),
('312e2864-3764-4195-b074-1fa3677d4ae1', 'AM-CSN-6/26', '63aef47efad47edd6a379af7d87cf5e4c5bbdc0638ace1c2644d93b16897ea22', 'Barco Sao joao2', '', '', '', '', '', NULL, '', '', '', '', '', '', 0, 0, '', '', NULL, NULL, '', 0, 1, '2026-06-12', '2026-06-12', 'Belém-PA', '', '', '', NULL, NULL, NULL, 0, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-12 01:34:39', '2026-06-12 01:34:39'),
('4f674d65-7197-48f9-a9ca-1248ace6445b', 'AM-CSN-3/26', 'ea25f2f8869930189dc1e9982b110de3ce39baf19bf37a83385f82afd0eb3bce', 'Barco Sao joao', 'PA-3456-AMa', '', 'navegadoeeee', 'navio', '2022', 22.00, '12', 'MAR ABERTO', 'Apoio Marítimo,Área 1', '', '', 'aço', 1, 23, 'homens todos', '', '2026-06-10', '2026-07-12', 'seco', 0, 1, '2026-06-12', '2026-07-12', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'rascunho', 0, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-12 01:30:12', '2026-06-12 01:31:58'),
('7eb8d1b7-71bc-4b7c-a59b-199f2dd7ffe8', 'AM-CSN-5/26', 'e2d0c7b327099210de7980df096d4d4ad9eccd9605483bfff5f035d191c20577', 'Barco Sao joao', 'PA-3456-AMa', '', 'navegadoeeee', 'navio', '2022', 22.00, '12', 'MAR ABERTO', 'Apoio Marítimo,Área 1', '', '', 'aço', 1, 23, 'homens todos', '', '2026-06-10', '2026-07-12', 'seco', 0, 1, '2026-06-12', '2026-07-12', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'rascunho', 0, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-12 01:31:23', '2026-06-12 01:31:54'),
('a67f7b9c-5e5f-47d3-900c-ddb26f35ea01', 'AM-CSN-2/26', '49b4980f5ea901999f2bdfc35d192402866023e8abe2df4b01df850a409c6b06', 'Barco Sao joao', 'PA-3456-AMa', '', 'navegadoeeee', 'navio', '2022', 22.00, '12', 'MAR ABERTO', 'Apoio Marítimo,Área 1', '', '', 'aço', 1, 23, 'homens todos', '', '2026-06-10', '2026-07-12', 'seco', 0, 1, '2026-06-12', '2026-07-12', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'rascunho', 0, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-12 01:30:09', '2026-06-12 01:32:00'),
('c80b8a8e-ee82-49bf-80cc-325ef3c59517', 'AM-CSN-1/26', 'df5c2e83eda41be64c0dd7e824bfc02ccdab52efb0080c3e34576dd99bc490ed', 'Barco Sao joao', 'PA-3456-AMa', '', 'navegadoeeee', 'navio', '2022', 22.00, '12', 'MAR ABERTO', 'Apoio Marítimo,Área 1', '', '', 'aço', 1, 23, 'homens todos', '', '2026-06-10', '2026-07-12', 'seco', 0, 1, '2026-06-12', '2026-07-12', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAZAAAACWCAYAAADwkd5lAAAQAElEQVR4AeydV6g8SRWH25wz5ow5gYoJAyq+mMAcMIOKYsCsKIKKmPBFxBddTJhzQlEfzGDGiGl1FcMaWdOa4/nGqbvlzNy5E6q7q3q+pc6t6uruqtNf/bd+XV1dPWfv/E8CEpCABCSwAwEFZAdoniIBCUhAAl2ngPivQAJjEbBeCTROQAFpvAF1XwISkMBYBBSQschbrwQkIIHGCTQsII2T130JSEACjRNQQBpvQN2XgAQkMBYBBWQs8tYrgYYJ6LoEIKCAQEGTgAQkIIGtCSggWyPzBAlIQAISgIACAoWhzfokIAEJTICAAjKBRvQSJCABCYxBQAEZg7p1SkACYxGw3oIEFJCCMC1KAhKQwCERUEAOqbW9VglIQAIFCSggBWEeQlFeowQkIIFEQAFJJIwlIAEJSGArAgrIVrg8WAISkMBYBOqrVwGpr030SAISkEATBBSQJppJJyUgAQnUR0ABqa9N9KgfApYqAQkUJqCAFAZqcRKQgAQOhYACcigt7XVKQAISKExgYwEpXK/FSUACEpBA4wQUkMYbUPclIAEJjEVAARmLvPVKYGMCHiiBOgkoIHW2i15JQAISqJ6AAlJ9E+mgBCQggToJHIKA1EleryQgAQk0TkABabwBdV8CEpDAWAQUkLHIW68EDoGA1zhpAgrIpJvXi5OABCTQHwEFpD+2liwBCUhg0gQUkKqbV+ckIAEJ1EtAAam3bfRMAhKQQNUEFJCqm0fnJCCBsQhY78kEFJCTGXmEBCQgAQmsIKCArIBilgQkIAEJnExAATmZkUfsQsBzJCCByRNQQCbfxF6gBCQggX4IKCD9cLXUugh8Ntz5c9i3wm4RZpDAlAkMdm0KyGCorWhEAojG+aL+64TdPcwgAQkUIKCAFIBoEVUT+ETm3ZmRfmWYQQISKEBAASkA0SKqJYB43Hbu3b8jvkvYD8PWBndKQAKbEVBANuPkUe0ReG+4nMSD+Y9zxPanwgwSkEAhAgpIIZAWUx2Bu809+mvEFwgzSEAChQmUF5DCDlqcBHYg8LDsnDtlaZMSkEBBAgpIQZgWVQ2B52SeMA+SbZqUgARKEVBASpFsp5zPhKv/DPtX2I/CeMU1oiKB9RaU+58ojUnrv0R8j7Ahw2ujsquEEbhW4kMxr1MCgxJQQJZx0+n8PbL/EUZHyzP0MyLNNunfRZpJ2edH3ErIr+lW4TQTyrT9lSNdal3Ez6IsxIhyI9mdLf6cN+ztYS8LGyIwcZ4eX9FOtxmiUuuQwKESSP+zH+r1L173TyKDDvZcEZ8zjI72PBFfLIxt0heJNIvSnh0xd9h0VAgKIrMYJ9EZUmwQC4SPkcDfwkdGAvk1RVbHCAFfPxYbpdZFXC7KSoHy8YFtuD0hErC6dcR9BUYdaeL8N1EJbRaRQQIS6IuAApKRjeQVwgh0gMQYHd+3I4FQ/CLi74TR+UbUcYedBIUOC2HJYzpPRAexQUzozOnYX8jJPRjigVggfLTtuaMORgIRddT760icGna1MN5MukPEJdZFUG8UNQuM0m4XKUT41RHDLaIZq1NI9GTvycq9aZY2KQEJ9ESATqanopsslgnX34fndIARzcJp8fe6YQjDZSPmcxh0vu+K9K/Cvhv20zBEZjGm80SAYneHmNCZw/yZkVFKRHgM9fMoD3FCPCJ5NMLgTpxRCHMd14gdlwq7ZlgJ0YhiZoE7/1vOUl3HqANWab3FIyL/RmFJYK4d6fuHlQ7Me9xwXih1cb3zTSMJSKAvAnRmfZXdYrm3D6cvGpY6wEh23MUTL9q9I+PSYXSKV4yYjnMxRnTOH/sQmz9GnIdH5xs7pOko6bDpLC8T5yNOEc3C5+MvInfJiBklXTXikqIRxc3CB+Mv5aa6qZftyD4K+Pfgo62ue1GWLpF03qMExdHL0IEWCSggJ7caj55OPmr9EYjNheOQd4SlgFDt8obS96OANNrgUVVszgJ+/ilSp4c9I2yIkK+xYDSWC0VePyLy4XkGI5bHz9P7RpSV5j0Y7SHY+5bp+RKQwIYEFJDVoOiY0p5fpkSB+L5RxhfDCNy184iH9DbG/AXncg5zNczHMEnNSOmCkXn5sHwEFZu9BEZAyY+PRw2MxhZHH5F9FJ50lOq6p2fpfZLpjSvK4FEZsSYBCQxEQAFZDfr9WXa+KC3L3jl5sziTjj+i2auuxNtYEiDOoRxGI7w9xvaQls97PHyDinn54PXz43hZ4bnz9K4RIp/a5nlRCKOciAwSkMBQBBSQ1aSvP8/+RsSfDisd0p37Lm8LIUC83YRPtB+jDh6NPZWMgSwffXw06lw38ojdR4ERA4sNyaDzvyeJHe112Xl5Oss2KQEJ9EmADqjP8lstm7t6fP8qfwpb6kApdnFinbxNjEdfb4wDefsqoo65kJdGgt+7OG4eInYXC/no43FblvqU7HgWGe4yD8RbV+lLu++L8hx9BATDSAQOuFoFZHXj0yGz58b8KWys1k5FMvGb0tvGCAWL994UJ/4hjMCbV6XfcqLc3HjVOY2gthl9pDIQ0LfNN+CMGM43N4oQD0YyHIxgllpJT3maBCSwBQEFZD0s3mxaf8R2e1ljks5gfcgT08Ye8YPiXBYzfiFiApPovBGFqOxyd08ZxxmPrtKdP+tLth19pHJZC8IryGz/mD8bGuKVxINRx4U2PM/DJCCBHggoIKuhpkdYdFKrj9g8l06Xb2uxEpxXeTmTye/rRaLk21L3i/JSYP0HnWvpuZH06ArxYH3JpnMfya88ZvTRdV135zxzTZqRRxIvFkiytmXN4e6SgAT6JqCArCacuLB6e/UR63OTaDCCuVUcymc9Upmx2bHSfZ/Ot1vxH2L38shnRfxXIibQSZecG0nCSmdO+ftYGjFdKQp5TNi6kI88eOyHQK473n0SkMAABPJObYDqmquCtRXbOM3zfUYbSTT4fAnnM/rgeT2T5txFlxx5UH4y1oOwIp65m8W5kdfEQft8zJAV3whSFNPR6RPvYzzGSuc/KiVWxIgVzNjFyMPFgpDQJFCAwL5FKCDrCTKCWH9E1zHa4Hk+d+dMkDPa4BxEI328kJEMj5R4hNWXeFBnbmlu5C3zTMSMr+/u8ml11lykFd88vtp17mPuyixixDRLxB8eh0W0FBCPNOfhyGMJjxkSGJeAArLMP1+Ut24VOr9/wVwGow3uzNObSZTIBDmi0cfHCyl/G3tAHJzWjSBujFIQRj6vErs2Ch/IjuLbX6Ufv/GBx2dldZBkxJPEA7Fx5AEVTQIVEVBAlhuDVdLkMqLIV1jzeArBSMYrtByXW9rHiOR7sYOYz5sjKGkf5b449g0ZeFUWEUlvgTEaYQ0G/uEb+4/z50OxIy2s5JrozCOrSPhyVsoLIv2SMEI+4uGxVZ0T5niqSeCACSggxzf+12NXfqfN46nIWhsYhWA8rqKTZmTC74Hkj2jYz8cOT+q411a0w05Egk+ev3l+Ln7gH77l36ma755FfLo+fTCR+Zv0GGu2s8Cfm0QZXwpL4ZGR4PP0iFYkZ2GX1fqzE/0jAQn0S0ABWebLSIFc1lEQJ+OVWOY12E7HEPMxQ47lx6Z4Ayr9JgjP7FM++ziX0QfnY3Tcr4oEHXtEgwRGDw+Mmt4ZhiBENAtMvHMt+JdGR4y4+JTL7ID4c9cwvmcVUdGQJsgplMdU+IhwsU19bJPWJCCBygiMKCCVkTjLHe7M2bo6fzLjS7qMKtgPtxSz+puFfPzYFB1x+k0QOsOUzz7O5Y6fzjsVSxn8uFTaHiq+T1TEhD6/UxLJo4A/jI4+FzmMuPA3kh1C0tfkPwK86Ad18kNd+doW8jQJSKAiAnSEFblThSvciePIN/nTg9F5M0pJRfMZ9JQeOma+h1FHuuZUPx9sTGketZV+dJXKTvFDUiJi/GGbyXoeI0aWQQISqJGAArLcKtyFk8vogbgPu1ZWKCOYfX+dMCtuoyRvNzGquHkczb+BdM2x2bGO5Q2R+FoYnxm5Y8T5XFBsFg+MQlj8yAsHPGKj/uKVWOBZBExJoAQBOo8S5UypDF5x5Xr6+BIv5WJ0mPko5Mlk9mSIBXfyCAPGaIP1FenNKvKYa2CehhXyTPo/NHxhwp0J7b4eXUUV/xdY/Hi+yHlrmEECEmiAgAKy3Ejpbvz8y7uK5jBXkgrkVwZXTaYzkY2g8bptMu7Sfxsnkk/61Ejz8US2OYY89rPgL4nFDeIY1oBgkTwKrGVhwhpjnuaTR3tMSEACEjiBgAKyDIjJbnJZ3Ea8bOVzaIdVr9IykY0/TGYnY4Rw0XCBfNJM9vNtKLY5hjz2L36G5Yw4B2FhoeTTI41QMgdyWqQNEpCABLYmQMe19UkTP4HXbbnEH/CnZ3t3Vj6jgMdm2yR5dTi9DswrwRiPmnhdmHzSH4kDma9gO9+/KBaXiOMuHsZ3rPjAYiQNEpCABHYnoIAss0tM9vnw4HKpq3PuFdl8siOijhHBKyKRVmNHsuPV4fQ6MJP6GI+aeF2YfNJMcjNfwXa+X7GAoDY1Al5PRQRSZ1mRS6O7ss1nxks4y48+5SORp0Whq+ZDItsgAQlIoB4CCshyW+SfGedDhMtHlM9hJJJKZSRySmwwGa6QBAiDBCRQJwEFZLld+HRGmgcZcoKZ+Y7cGybDS/zkbV5m54YEJCCBUgQUkNUkeZuJPaxNIB7CmO9ARFj5nerjcyMpbSwBCUigKgIKyPrmWHwVdv3R++9FRPL1J7xm62Os/blaggQqIDA9FxSQ9W165vrdve9lPiT/TZLeK7QCCUhAApsSUEBWk+JREt+BGmsOgvpZ13F6uMfXcSMySEACEqiLgAKyuj14lDTkd6AWvaB+1nVcPnYM9S2qqKrqoHMSkEBlBBSQyhpEdyQgAQm0QkABaaWl9FMCEpDAWASOqVcBOQaM2RKQgAQksJ6AArKej3slIAEJSOAYAgrIMWDMlkA5ApYkgWkSUECm2a5elQQkIIHeCSggvSO2AglIQALTJNCCgEyTvFclAQlIoHECCkjjDaj7EpCABMYioICMRd56JdACAX2UwBoCCsgaOO6SgAQkIIHjCSggx7NxjwQkIAEJrCGggKyBs/8uS5CABCQwXQIKyHTb1iuTgAQk0CsBBaRXvBYuAQmMRcB6+yeggPTP2BokIAEJTJKAAjLJZvWiJCABCfRPQAHpn3GbNei1BCQggRMIKCAnAHK3BCQgAQmsJqCArOZirgQkIIGxCDRTrwLSTFPpqAQkIIG6CCggdbWH3khAAhJohoAC0kxT6eimBDxOAhIYhoACMgxna5GABCQwOQIKyOSa1AuSgAQkMAyBZQEZpl5rkYAEJCCBxgkoII03oO5LQAISGIuAAjIWeeuVwDIBcyTQFAEFpKnm0lkJSEAC9RBQQOppCz2RgAQk0BSBSQlIU+R1VgISkEDjBBSQxhtQ9yUgAQmMRUABGYu89UpgUgS8mEMkoIAcYqt7zRKQgAQKEFBACkC0CAlIdT7xjQAAAZlJREFUQAKHSEABqaPV9UICEpBAcwQUkOaaTIclIAEJ1EFAAamjHfRCAhIYi4D17kxAAdkZnSdKQAISOGwCCshht79XLwEJSGBnAgrIzug88X8E/CsBCRwqAQXkUFve65aABCSwJwEFZE+Ani4BCUhgLAJj16uAjN0C1i8BCUigUQIKSKMNp9sSkIAExiaggIzdAtY/HgFrloAE9iKggOyFz5MlIAEJHC4BBeRw294rl4AEJLAXgT0EZK96PVkCEpCABBonoIA03oC6LwEJSGAsAgrIWOStVwJ7EPBUCdRAQAGpoRX0QQISkECDBBSQBhtNlyUgAQnUQOAwBaQG8vogAQlIoHECCkjjDaj7EpCABMYioICMRd56JXCYBLzqCRFQQCbUmF6KBCQggSEJKCBD0rYuCUhAAhMioIA01pi6KwEJSKAWAgpILS2hHxKQgAQaI6CANNZguisBCYxFwHoXCSggi0TcloAEJCCBjQgoIBth8iAJSEACElgkoIAsEnG7LwKWKwEJTIyAAjKxBvVyJCABCQxF4L8AAAD//wk0WJkAAAAGSURBVAMA6nPRPO5blOIAAAAASUVORK5CYII=', '::1', '2026-06-12 06:32:29', 1, 'assinado', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-12 01:27:47', '2026-06-12 01:32:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `csn_convalidacoes`
--

CREATE TABLE `csn_convalidacoes` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `certificado_id` char(36) NOT NULL,
  `numero_vistoria` varchar(50) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `local_data` varchar(200) DEFAULT NULL,
  `vistoriador` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `csn_convalidacoes`
--

INSERT INTO `csn_convalidacoes` (`id`, `certificado_id`, `numero_vistoria`, `data_inicio`, `data_fim`, `local_data`, `vistoriador`) VALUES
('0001ba2f-843c-4cca-8947-80d8393aad71', 'a67f7b9c-5e5f-47d3-900c-ddb26f35ea01', '1ª VIST. ANUAL', '2026-06-12', '2027-06-12', 'belem', 'Rono Vistoriador'),
('02dda0d3-649f-4148-b5e8-03cf5001c5be', 'c80b8a8e-ee82-49bf-80cc-325ef3c59517', '1ª VIST. ANUAL', '2026-06-12', '2027-06-12', 'belem', 'Rono Vistoriador'),
('04046c0f-a93f-4d7a-a934-97d8c3587b54', '4f674d65-7197-48f9-a9ca-1248ace6445b', '4ª VIST. ANUAL', NULL, NULL, '', ''),
('052552df-2b4c-40f0-b771-9adf45400ed7', '312e2864-3764-4195-b074-1fa3677d4ae1', '2ª VIST. ANUAL', NULL, NULL, '', ''),
('06572857-8d90-4a19-968a-a37da126bf5e', '0c22ca75-6be7-483c-a516-89439eaff47c', '3ª VIST. ANUAL', NULL, NULL, '', ''),
('1863efa0-c27e-4349-a569-53848ed4f3f8', '7eb8d1b7-71bc-4b7c-a59b-199f2dd7ffe8', '3ª VIST. ANUAL', NULL, NULL, '', ''),
('2e6cf13d-cae7-4ccf-b950-18210a6933fd', '312e2864-3764-4195-b074-1fa3677d4ae1', '1ª VIST. ANUAL', NULL, NULL, '', ''),
('3c95ba1b-2c42-40da-9f2e-7b19d41d6377', '0c22ca75-6be7-483c-a516-89439eaff47c', '2ª VIST. ANUAL', '2027-06-12', '2029-06-12', 'tucurui', 'Savio Souza'),
('43f8e37d-d8bc-4c0e-9019-09f1fd0c4fd9', '7eb8d1b7-71bc-4b7c-a59b-199f2dd7ffe8', '2ª VIST. ANUAL', '2027-06-12', '2029-06-12', 'tucurui', 'Savio Souza'),
('467015d0-49d8-404f-bf77-be0a46d093d2', '312e2864-3764-4195-b074-1fa3677d4ae1', '4ª VIST. ANUAL', NULL, NULL, '', ''),
('477f3f5b-3afd-48a2-9b16-cd376e6d6f02', '0c22ca75-6be7-483c-a516-89439eaff47c', '4ª VIST. ANUAL', NULL, NULL, '', ''),
('55686fac-ea85-4d06-8822-64cfdb0cd2ad', 'a67f7b9c-5e5f-47d3-900c-ddb26f35ea01', '2ª VIST. ANUAL', '2027-06-12', '2029-06-12', 'tucurui', 'Savio Souza'),
('5c961ba8-2908-486a-84f5-f3c84e5fa8d2', '4f674d65-7197-48f9-a9ca-1248ace6445b', '1ª VIST. ANUAL', '2026-06-12', '2027-06-12', 'belem', 'Rono Vistoriador'),
('5dd4005e-7423-4f15-b078-0a533908cede', '7eb8d1b7-71bc-4b7c-a59b-199f2dd7ffe8', '4ª VIST. ANUAL', NULL, NULL, '', ''),
('7091d518-b76b-4188-a9ff-c673af6a6394', 'c80b8a8e-ee82-49bf-80cc-325ef3c59517', '4ª VIST. ANUAL', NULL, NULL, '', ''),
('72cd487c-f2e1-4bd3-ad1f-925db138c248', '312e2864-3764-4195-b074-1fa3677d4ae1', '3ª VIST. ANUAL', NULL, NULL, '', ''),
('74a571ce-b328-4c41-95b0-604f55e9bcc4', '0c22ca75-6be7-483c-a516-89439eaff47c', '1ª VIST. ANUAL', '2026-06-12', '2027-06-12', 'belem', 'Rono Vistoriador'),
('866dc3f3-53fc-4c72-af72-aab64b17fa64', '4f674d65-7197-48f9-a9ca-1248ace6445b', '2ª VIST. ANUAL', '2027-06-12', '2029-06-12', 'tucurui', 'Savio Souza'),
('883836cc-8cd9-4641-ae04-2fdccfd81da4', 'c80b8a8e-ee82-49bf-80cc-325ef3c59517', '2ª VIST. ANUAL', '2027-06-12', '2029-06-12', 'tucurui', 'Savio Souza'),
('9b9261da-411c-41fa-a251-f36d30f8677b', '7eb8d1b7-71bc-4b7c-a59b-199f2dd7ffe8', '1ª VIST. ANUAL', '2026-06-12', '2027-06-12', 'belem', 'Rono Vistoriador'),
('a11d72e1-4664-400e-9be7-da609b5782c6', 'a67f7b9c-5e5f-47d3-900c-ddb26f35ea01', '3ª VIST. ANUAL', NULL, NULL, '', ''),
('d2df4937-3502-47e4-a3ef-7518fbe981d6', 'c80b8a8e-ee82-49bf-80cc-325ef3c59517', '3ª VIST. ANUAL', NULL, NULL, '', ''),
('e1df0c8f-2bdd-4456-b21c-ea9dbbe99003', 'a67f7b9c-5e5f-47d3-900c-ddb26f35ea01', '4ª VIST. ANUAL', NULL, NULL, '', ''),
('f982539b-bed1-48fa-9e1a-9f9636efdc24', '4f674d65-7197-48f9-a9ca-1248ace6445b', '3ª VIST. ANUAL', NULL, NULL, '', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `csn_distribuicao_passageiros`
--

CREATE TABLE `csn_distribuicao_passageiros` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `certificado_id` char(36) NOT NULL,
  `local_nome` varchar(150) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `csn_distribuicao_passageiros`
--

INSERT INTO `csn_distribuicao_passageiros` (`id`, `certificado_id`, `local_nome`, `quantidade`) VALUES
('7f886e6e-e8b0-4eba-8dfc-54f086a9004f', '312e2864-3764-4195-b074-1fa3677d4ae1', 'dentro', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `nome` varchar(200) NOT NULL,
  `tipo_pessoa` enum('PF','PJ') NOT NULL DEFAULT 'PF',
  `cpf_cnpj` varchar(18) DEFAULT NULL,
  `perfil` enum('armador','proprietario','despachante') NOT NULL DEFAULT 'proprietario',
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `status` enum('ATIVO','INATIVO') NOT NULL DEFAULT 'ATIVO',
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_embarcacoes`
--

CREATE TABLE `clientes_embarcacoes` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `cliente_id` char(36) NOT NULL,
  `embarcacao_id` char(36) NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `embarcacoes`
--

CREATE TABLE `embarcacoes` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `nome` varchar(150) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `registro` varchar(80) DEFAULT NULL,
  `proprietario` varchar(150) DEFAULT NULL,
  `ano` int(11) DEFAULT NULL,
  `cliente_id` char(36) DEFAULT NULL,
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `comprimento_casco` decimal(8,2) DEFAULT NULL,
  `comprimento_lpp` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `boca_maxima` decimal(8,2) DEFAULT NULL,
  `material_casco` varchar(100) DEFAULT NULL,
  `tipo_servico` varchar(100) DEFAULT NULL,
  `tipo_navegacao` varchar(200) DEFAULT NULL,
  `area_navegacao` varchar(200) DEFAULT NULL,
  `arqueacao_bruta` varchar(50) DEFAULT NULL,
  `numero_inscricao` varchar(80) DEFAULT NULL,
  `porto_inscricao` varchar(100) DEFAULT NULL,
  `indicativo_chamada` varchar(80) DEFAULT NULL,
  `numero_tripulantes` int(11) DEFAULT 0,
  `numero_passageiros_n1` int(11) DEFAULT 0,
  `numero_passageiros_n2` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `embarcacoes`
--

INSERT INTO `embarcacoes` (`id`, `nome`, `tipo`, `registro`, `proprietario`, `ano`, `observacoes`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('35b8b0d5-e795-4472-a561-dd26e4de5f0a', 'navio estrela', 'navio', 'reg-3742329', 'Any Souza', 2021, NULL, 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 21:51:53', '2026-06-11 21:51:53'),
('8a721b6f-ff09-4b4d-95dd-f02b3101fdd8', 'barco do breu2', 'navio', 'reg-374232', 'Any Souza', 2022, NULL, 0, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 21:48:56', '2026-06-11 21:49:23'),
('d376b341-93f3-4e69-9ee2-b925f23bc110', 'navio do guama', 'navio', 'reg-3742322', 'Any Souza', 2020, NULL, 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 21:50:17', '2026-06-11 21:50:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro_lancamentos`
--

CREATE TABLE `financeiro_lancamentos` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `tipo` enum('RECEITA','DESPESA') NOT NULL,
  `descricao` varchar(300) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data` date NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `financeiro_lancamentos`
--

INSERT INTO `financeiro_lancamentos` (`id`, `tipo`, `descricao`, `valor`, `data`, `categoria`, `observacoes`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('58e7bb4b-4fec-4b30-bd33-b7cc9c5c0f66', 'DESPESA', 'emprestimo', 200.00, '2026-06-26', 'devo', '', '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 22:49:21', '2026-06-11 22:49:41'),
('c2d13fa1-9852-4af1-a551-d67f1506b31f', 'RECEITA', 'aluguel casa arquimedes ataides', 550.00, '2026-06-12', 'Operacional', '', '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 22:48:59', '2026-06-11 22:48:59');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sequenciais_documentos`
--

CREATE TABLE `sequenciais_documentos` (
  `id` int(11) NOT NULL,
  `tipo_documento` varchar(50) NOT NULL,
  `ano` int(4) NOT NULL,
  `ultimo_numero` int(11) NOT NULL DEFAULT 0,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_atividade`
--

CREATE TABLE `logs_atividade` (
  `id` int(11) NOT NULL,
  `usuario_id` varchar(36) DEFAULT NULL,
  `acao` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `logs_atividade`
--

INSERT INTO `logs_atividade` (`id`, `usuario_id`, `acao`, `descricao`, `ip`, `criado_em`) VALUES
(1, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_criado', 'Certificado 4ª VIST. ANUAL - Barco Sao joao', '::1', '2026-06-12 06:31:23'),
(2, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_excluido', 'Certificado ID: 7eb8d1b7-71bc-4b7c-a59b-199f2dd7ffe8', '::1', '2026-06-12 06:31:54'),
(3, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_excluido', 'Certificado ID: 0c22ca75-6be7-483c-a516-89439eaff47c', '::1', '2026-06-12 06:31:56'),
(4, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_excluido', 'Certificado ID: 4f674d65-7197-48f9-a9ca-1248ace6445b', '::1', '2026-06-12 06:31:58'),
(5, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_excluido', 'Certificado ID: a67f7b9c-5e5f-47d3-900c-ddb26f35ea01', '::1', '2026-06-12 06:32:00'),
(6, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_editado', 'Certificado 1ª VIST. ANUAL - Barco Sao joao', '::1', '2026-06-12 06:32:14'),
(7, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_assinado', 'Certificado AM-CSN-1/26 assinado por Rosano Souza Capitao via link público', '::1', '2026-06-12 06:32:29'),
(8, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_criado', 'Certificado 4ª VIST. ANUAL - Barco Sao joao2', '::1', '2026-06-12 06:34:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pessoas`
--

CREATE TABLE `pessoas` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `tipo_pessoa` enum('PF','PJ') NOT NULL DEFAULT 'PF',
  `nome_completo` varchar(200) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `sexo` enum('M','F','OUTRO') DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pessoas`
--

INSERT INTO `pessoas` (`id`, `tipo_pessoa`, `nome_completo`, `cpf`, `cnpj`, `rg`, `telefone`, `email`, `sexo`, `endereco`, `observacoes`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('1015cf10-6fac-43b0-bd88-6cfc8d3b3700', 'PF', 'Rosano Silva De Souza', '38303451863', NULL, NULL, '91989340275', 'ronokedas@gmail.com', NULL, 'passagem 7b casa', '', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 22:00:37', '2026-06-11 22:00:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `cargo` enum('ADMIN','VISTORIADOR') NOT NULL DEFAULT 'VISTORIADOR',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `cargo`, `ativo`, `criado_em`, `atualizado_em`) VALUES
('74e02f95-fbe6-42f3-bedf-f8535e4d13aa', 'Rosano Souza', 'ronokedas@sistema.com', '$2y$10$WkA/uXzt0FgkNZSgpV5IXuwgOYrjultBGwQMszFKOofoXqqTvG1aO', 'VISTORIADOR', 1, '2026-06-11 21:44:56', '2026-06-11 21:44:56'),
('95eb5557-65e8-11f1-85ef-047c16b568a3', 'Administrador', 'admin@sistema.com', '$2y$10$PtP9eBzyM.NyQylekCVaWO7DxvrLil.MwqyhPIs0zQN2s4T6frP9m', 'ADMIN', 1, '2026-06-11 19:55:04', '2026-06-11 20:35:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vistorias`
--

CREATE TABLE `vistorias` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `embarcacao_id` char(36) NOT NULL,
  `pessoa_id` char(36) NOT NULL,
  `data_vistoria` date NOT NULL,
  `status` enum('PENDENTE','APROVADA','REPROVADA','CANCELADA') DEFAULT 'PENDENTE',
  `observacoes` text DEFAULT NULL,
  `resultado` text DEFAULT NULL,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `vistorias`
--

INSERT INTO `vistorias` (`id`, `embarcacao_id`, `pessoa_id`, `data_vistoria`, `status`, `observacoes`, `resultado`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('22919667-36f9-4d8c-8893-b8b821a527b0', 'd376b341-93f3-4e69-9ee2-b925f23bc110', '1015cf10-6fac-43b0-bd88-6cfc8d3b3700', '2026-06-11', 'PENDENTE', '', NULL, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 22:10:23', '2026-06-11 22:10:23');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `certificados_csn`
--
ALTER TABLE `certificados_csn`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD UNIQUE KEY `token_assinatura` (`token_assinatura`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `csn_convalidacoes`
--
ALTER TABLE `csn_convalidacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certificado_id` (`certificado_id`);

--
-- Índices de tabela `csn_distribuicao_passageiros`
--
ALTER TABLE `csn_distribuicao_passageiros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certificado_id` (`certificado_id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `clientes_embarcacoes`
--
ALTER TABLE `clientes_embarcacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cliente_embarcacao` (`cliente_id`, `embarcacao_id`),
  ADD KEY `embarcacao_id` (`embarcacao_id`);

--
-- Índices de tabela `embarcacoes`
--
ALTER TABLE `embarcacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registro` (`registro`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `financeiro_lancamentos`
--
ALTER TABLE `financeiro_lancamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `sequenciais_documentos`
--
ALTER TABLE `sequenciais_documentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo_ano` (`tipo_documento`, `ano`);

--
-- Índices de tabela `logs_atividade`
--
ALTER TABLE `logs_atividade`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pessoas`
--
ALTER TABLE `pessoas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `vistorias`
--
ALTER TABLE `vistorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `embarcacao_id` (`embarcacao_id`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `sequenciais_documentos`
--
ALTER TABLE `sequenciais_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_atividade`
--
ALTER TABLE `logs_atividade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `certificados_csn`
--
ALTER TABLE `certificados_csn`
  ADD CONSTRAINT `certificados_csn_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `csn_convalidacoes`
--
ALTER TABLE `csn_convalidacoes`
  ADD CONSTRAINT `csn_convalidacoes_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados_csn` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `csn_distribuicao_passageiros`
--
ALTER TABLE `csn_distribuicao_passageiros`
  ADD CONSTRAINT `csn_distribuicao_passageiros_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados_csn` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `clientes_embarcacoes`
--
ALTER TABLE `clientes_embarcacoes`
  ADD CONSTRAINT `clientes_embarcacoes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clientes_embarcacoes_ibfk_2` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `embarcacoes`
--
ALTER TABLE `embarcacoes`
  ADD CONSTRAINT `embarcacoes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `embarcacoes_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `financeiro_lancamentos`
--
ALTER TABLE `financeiro_lancamentos`
  ADD CONSTRAINT `financeiro_lancamentos_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pessoas`
--
ALTER TABLE `pessoas`
  ADD CONSTRAINT `pessoas_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `vistorias`
--
ALTER TABLE `vistorias`
  ADD CONSTRAINT `vistorias_ibfk_1` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`),
  ADD CONSTRAINT `vistorias_ibfk_2` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoas` (`id`),
  ADD CONSTRAINT `vistorias_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
