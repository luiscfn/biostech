-- =============================================================================
-- BIOSTECH - Tabelas Basicas
--
-- Modulo de apoio com os itens do menu F_MODULO_TABELAS do Access:
--   A Setores e Filiais .............. biostech_setores (tabela ja existente)
--   B Tipos de Equipamentos .......... dominio tipo_equipamento
--   C Status da Ordem de Servico ..... dominio status_os
--   D Status do Equipamento .......... dominio status_equipamento
--   E Condicoes de Pagamento ......... dominio condicao_pagamento
--     Status do Orcamento ............ dominio situacao_orcamento
--     Locais de Atendimento .......... dominio local_atendimento
--     Valor Orcamento Nao Aprovado ... biostech_basico_parametros
--   F Finalidade do Orcamento ........ dominio finalidade
--   G Natureza das Notas Fiscais ..... dominio natureza_operacao
--   H Cargos e Funcoes ............... dominio funcao
--   I Permissoes de Acesso ........... biostech_basico_modulos / programas
--   K Tipos de Pagamentos ............ dominio tipo_pagamento
--
-- Os dominios ficam em biostech_dominios (tipo + codigo), a mesma tabela
-- usada por Chamados, Orcamentos e Financeiro. Assim, alterar um item aqui
-- reflete imediatamente nos modulos que o utilizam.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Campos adicionais dos dominios
--   valor     : desconto da condicao de pagamento / valor da funcao
--   observacao: anotacao livre do item
-- -----------------------------------------------------------------------------
ALTER TABLE {schema}.biostech_dominios ADD COLUMN IF NOT EXISTS valor numeric(18,4);
ALTER TABLE {schema}.biostech_dominios ADD COLUMN IF NOT EXISTS observacao text;

-- -----------------------------------------------------------------------------
-- Parametros do orcamento nao aprovado (origem: VALOR_ORC_NAPROV)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_basico_parametros (
    id                  integer       PRIMARY KEY,
    valor               numeric(18,4) NOT NULL DEFAULT 0,  -- VALOR
    prazo_ret_equip     smallint,                            -- PRAZO_RET_EQUIP
    valor_taxa_perm     numeric(18,4),                       -- VALOR_TAXA_PERM
    prazo_ini_envio_orc smallint,                            -- PRAZO_INI_ENVIO_ORC
    prazo_fim_envio_orc smallint,                            -- PRAZO_FIM_ENVIO_ORC
    dias_limite         smallint,                            -- DIAS_LIMITE
    texto_rodape        text,                                -- TEXTO_RODAPE
    updated_at          timestamptz,
    CONSTRAINT biostech_basico_parametros_id_ck CHECK (id = 1)
);

-- -----------------------------------------------------------------------------
-- Permissoes de acesso (origem: PERMISSOES_MODULO_01 / PERMISSOES_MODULO_02)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_basico_modulos (
    codigo      integer      PRIMARY KEY,               -- COD_MODULO
    nome        varchar(80)  NOT NULL,                  -- NOM_MODULO
    ordem       smallint,
    ativo       boolean      NOT NULL DEFAULT true,
    created_at  timestamptz  NOT NULL DEFAULT now(),
    updated_at  timestamptz
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_basico_programas (
    id             serial       PRIMARY KEY,
    modulo_codigo  integer      NOT NULL REFERENCES {schema}.biostech_basico_modulos(codigo) ON DELETE CASCADE,
    codigo         integer      NOT NULL,               -- COD_PROGRAMA
    nome           varchar(100) NOT NULL,               -- NOM_PROGRAMA
    formulario     varchar(80),                         -- NOM_FORM
    ordem          smallint,
    ativo          boolean      NOT NULL DEFAULT true,
    created_at     timestamptz  NOT NULL DEFAULT now(),
    updated_at     timestamptz,
    CONSTRAINT biostech_basico_programas_uk UNIQUE (modulo_codigo, codigo)
);

CREATE INDEX IF NOT EXISTS biostech_basico_programas_modulo_idx
    ON {schema}.biostech_basico_programas (modulo_codigo, ordem, codigo);

-- -----------------------------------------------------------------------------
-- Dominios das Tabelas Basicas (codigos preservados 1:1 do Access)
-- -----------------------------------------------------------------------------
INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('tipo_equipamento', 2, 'MONITOR', 1, true),
    ('tipo_equipamento', 3, 'IMPRESSORA', 2, true),
    ('tipo_equipamento', 6, 'CAMERA', 3, true),
    ('tipo_equipamento', 7, 'CERCA ELETRICA', 4, true),
    ('tipo_equipamento', 8, 'TECLADO', 5, true),
    ('tipo_equipamento', 9, 'MOUSE', 6, true),
    ('tipo_equipamento', 10, 'MODULO ISOLADOR', 7, true),
    ('tipo_equipamento', 11, 'NOTEBOOK', 8, true),
    ('tipo_equipamento', 12, 'FAX', 9, true),
    ('tipo_equipamento', 13, 'SCANNER', 10, true),
    ('tipo_equipamento', 20, 'FONTE PARA IMPRESSORA', 11, true),
    ('tipo_equipamento', 21, 'LEITOR DE DIGITAL', 12, true),
    ('tipo_equipamento', 22, 'CENTRAL DE CHOQUE', 13, true),
    ('tipo_equipamento', 23, 'HD', 14, true),
    ('tipo_equipamento', 24, 'GRAVADORA', 15, true),
    ('tipo_equipamento', 25, 'MODEM', 16, true),
    ('tipo_equipamento', 26, 'MESA CONTROLADORA', 17, true),
    ('tipo_equipamento', 27, 'INTERFONE', 18, true),
    ('tipo_equipamento', 28, 'SWITCH', 19, true),
    ('tipo_equipamento', 29, 'CENTRAL TELEFONICA', 20, true),
    ('tipo_equipamento', 30, 'PLACA DE IMPRESSORA HP', 21, true),
    ('tipo_equipamento', 31, 'FONTE DE SWIT DELINK', 22, true),
    ('tipo_equipamento', 32, 'FONTE DE TELEFONE', 23, true),
    ('tipo_equipamento', 33, 'CERCA ESPIRAL', 24, true),
    ('tipo_equipamento', 34, 'CARTUCHO', 25, true),
    ('tipo_equipamento', 35, 'FONTE PARA NOTEBBOK', 26, true),
    ('tipo_equipamento', 36, 'MOTOR DE PORTÃO', 27, true),
    ('tipo_equipamento', 37, 'PLACA DE MOTOR', 28, true),
    ('tipo_equipamento', 38, 'THINK CLIENT', 29, true),
    ('tipo_equipamento', 39, 'PLACA MAE', 30, true),
    ('tipo_equipamento', 40, 'FONTE', 31, true),
    ('tipo_equipamento', 41, 'AMPLIFICADOR', 32, true),
    ('tipo_equipamento', 42, 'DATASHOW', 33, true),
    ('tipo_equipamento', 43, 'CABO DE VIDEO', 34, true),
    ('tipo_equipamento', 44, 'KIT DE FIM DE CURSO P/PPA EUROS', 35, true),
    ('tipo_equipamento', 45, 'PLACA DE VIDEO', 36, true),
    ('tipo_equipamento', 46, 'CENTRAL DE ALARME', 37, true),
    ('tipo_equipamento', 47, 'TONNER', 38, true),
    ('tipo_equipamento', 48, 'PLACA DE CAPTURA', 39, true),
    ('tipo_equipamento', 49, 'FILTRO DE LINHA 6T', 40, true),
    ('tipo_equipamento', 50, 'GPS', 41, true),
    ('tipo_equipamento', 51, 'FRAGMENTADORA', 42, true),
    ('tipo_equipamento', 52, 'DUAL QUAD', 43, true),
    ('tipo_equipamento', 53, 'SINALIZADOR DE PORTA', 44, true),
    ('tipo_equipamento', 54, 'DISCADORA', 45, true),
    ('tipo_equipamento', 55, 'RECEPTOR', 46, true),
    ('tipo_equipamento', 56, 'MEMORIA', 47, true),
    ('tipo_equipamento', 57, 'DVD PORTATIL', 48, true),
    ('tipo_equipamento', 58, 'CABO DE CFTV', 49, true),
    ('tipo_equipamento', 59, 'ROTEADOR D-LINK', 50, true),
    ('tipo_equipamento', 60, 'ROTEADOR THOMSON', 51, true),
    ('tipo_equipamento', 61, 'PROJETOR', 52, true),
    ('tipo_equipamento', 62, 'LINHA TELEFONICA', 53, true),
    ('tipo_equipamento', 64, 'JOYSTIC', 54, true),
    ('tipo_equipamento', 65, 'TIPO NÃO INFORMADO', 55, true),
    ('tipo_equipamento', 66, 'REDES', 56, true),
    ('tipo_equipamento', 67, 'INFRA ESTRUTURA', 57, true),
    ('tipo_equipamento', 68, 'WEB CAN', 58, true),
    ('tipo_equipamento', 69, 'TELEFONE', 59, true),
    ('tipo_equipamento', 70, 'ESTUFA', 60, true),
    ('tipo_equipamento', 71, 'MICRO', 61, true),
    ('tipo_equipamento', 72, 'CAIXA DE SOM', 62, true),
    ('tipo_equipamento', 73, 'TV(PAINEL DE SENHA)', 63, true),
    ('tipo_equipamento', 74, 'CAMELO', 64, true),
    ('tipo_equipamento', 75, 'TRANSFORMADOR', 65, true),
    ('tipo_equipamento', 76, 'ACCESS POINT', 66, true),
    ('tipo_equipamento', 77, 'CITRIX ACCESS GATEWAY', 67, true),
    ('tipo_equipamento', 78, 'CABO DE CONTROLE', 68, true),
    ('tipo_equipamento', 79, 'SENSOR DE PRESENCA', 69, true),
    ('tipo_equipamento', 80, 'IPOD', 70, true),
    ('tipo_equipamento', 82, 'TV', 71, true),
    ('tipo_equipamento', 83, 'PLACA DA CENTRAL GCP 10.000', 72, true),
    ('tipo_equipamento', 84, 'MASTER SUX', 73, true),
    ('tipo_equipamento', 85, 'MONITOR  IBM  6633-OAE', 74, true),
    ('tipo_equipamento', 93, 'MODULO  ISOLADOR ESTABILIZADOR  G3', 75, true),
    ('tipo_equipamento', 101, 'NO-BREAK', 76, true),
    ('tipo_equipamento', 102, 'ESTABILIZADOR', 77, true),
    ('tipo_equipamento', 103, 'NO-BREAK STAY1200', 78, true),
    ('tipo_equipamento', 104, 'TIPO NÃO INFORMADO', 79, true),
    ('tipo_equipamento', 105, 'DVR STAND ALONE', 80, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('status_os', 1, 'ABERTA', 1, true),
    ('status_os', 2, 'EM ATENDIMENTO', 2, true),
    ('status_os', 3, 'PENDENTE', 3, true),
    ('status_os', 4, 'CONCLUIDA', 4, true),
    ('status_os', 5, 'ENCERRADA', 5, true),
    ('status_os', 6, 'INTERVENCAO', 6, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('status_equipamento', 2, 'PENDENTE', 1, true),
    ('status_equipamento', 3, 'AGUARDA APROVACAO ORCAMENTO', 2, true),
    ('status_equipamento', 4, 'AGUARDA PECA DA AUTORIZADA', 3, true),
    ('status_equipamento', 5, 'AGUARDANDO COTAÇÃO DE PEÇA', 4, true),
    ('status_equipamento', 7, 'PENDENTE POR INTERVENÇÃO', 5, true),
    ('status_equipamento', 8, 'CONSERTO INVIÁVEL', 6, true),
    ('status_equipamento', 9, 'EM SERVIÇO', 7, true),
    ('status_equipamento', 10, 'EM TESTE', 8, true),
    ('status_equipamento', 11, 'SERVIÇO NÃO APROVADO', 9, true),
    ('status_equipamento', 12, 'CONCLUIDO', 10, true),
    ('status_equipamento', 13, 'FUNCIONANDO - OK', 11, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('condicao_pagamento', 1, 'A VISTA', 1, true),
    ('condicao_pagamento', 2, 'ENTRADA/30 DIAS', 2, true),
    ('condicao_pagamento', 3, '30 DIAS', 3, true),
    ('condicao_pagamento', 4, 'FATURADA', 4, true),
    ('condicao_pagamento', 5, 'A RECEBER', 5, true),
    ('condicao_pagamento', 6, '15/30 DIAS', 6, true),
    ('condicao_pagamento', 7, '45 DIAS', 7, true),
    ('condicao_pagamento', 9, '15/30/60 DIAS', 8, true),
    ('condicao_pagamento', 10, 'ENTRADA/30/60 DIAS', 9, true),
    ('condicao_pagamento', 11, 'ENTRADA/30/60/90 DIAS', 10, true),
    ('condicao_pagamento', 12, '30/60/90 DIAS', 11, true),
    ('condicao_pagamento', 13, '30/60 DIAS', 12, true),
    ('condicao_pagamento', 14, '30/60/90/120 DIAS', 13, true),
    ('condicao_pagamento', 15, 'ENTRADA/30/60/90/120 DIAS', 14, true),
    ('condicao_pagamento', 16, '20/35 DIAS', 15, true),
    ('condicao_pagamento', 17, 'ENTRADA/15 DIAS', 16, true),
    ('condicao_pagamento', 18, '15 dias', 17, true),
    ('condicao_pagamento', 19, '20 dias', 18, true),
    ('condicao_pagamento', 20, '25 dias', 19, true),
    ('condicao_pagamento', 22, '07 DIAS', 20, true),
    ('condicao_pagamento', 23, '05 dias', 21, true),
    ('condicao_pagamento', 24, '15/30/45 DIAS', 22, true),
    ('condicao_pagamento', 25, '10 DIAS', 23, true),
    ('condicao_pagamento', 27, '28 dias', 24, true),
    ('condicao_pagamento', 28, '10/30 DIAS', 25, true),
    ('condicao_pagamento', 29, 'ENTRADA/20 DIAS', 26, true),
    ('condicao_pagamento', 30, 'ENTRADA/20/40 DIAS', 27, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('situacao_orcamento', 1, 'ABERTO', 1, true),
    ('situacao_orcamento', 2, 'ENVIADO', 2, true),
    ('situacao_orcamento', 3, 'PENDENTE', 3, true),
    ('situacao_orcamento', 4, 'CONCLUIDO', 4, true),
    ('situacao_orcamento', 5, 'ENCERRADO', 5, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('resultado_orcamento', 1, 'APROVADO', 1, true),
    ('resultado_orcamento', 2, 'NAO APROVADO', 2, true),
    ('resultado_orcamento', 3, 'NÃO ENVIADO', 3, true),
    ('resultado_orcamento', 4, 'EM ANALISE PELO CLIENTE', 4, true),
    ('resultado_orcamento', 5, 'AGUARDA APROVACAO', 5, true),
    ('resultado_orcamento', 6, 'CANCELADO PELO CLIENTE', 6, true),
    ('resultado_orcamento', 7, 'CANCELADO PELA BIOSTECH', 7, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('finalidade', 1, 'MANUTENCAO CORRETIVA COM TROCA DE PECAS', 1, true),
    ('finalidade', 2, 'MANUTENCAO CORRETIVA SEM TROCA DE PECAS', 2, true),
    ('finalidade', 3, 'MANUTENCAO PREVENTIVA', 3, true),
    ('finalidade', 4, 'PROPOSTA DE VENDA', 4, true),
    ('finalidade', 5, 'REVISÃO,FORMATAÇÃO,BACK'' UP,CONFIGURAÇÃO', 5, true),
    ('finalidade', 6, 'REVISÃO,FORMATAÇÃO,CONFIG. SEM BACK'' UP', 6, true),
    ('finalidade', 7, 'RECUPERAÇÃO DE DADOS', 7, true),
    ('finalidade', 8, 'CONSERTO INVIÁVEL', 8, true),
    ('finalidade', 9, 'SISTEMA DE SEGURANÇA ELETRONICA DIGITAL', 9, true),
    ('finalidade', 10, 'INSTALAÇÃO DE  CERCA ELETRICA', 10, true),
    ('finalidade', 11, 'INSTALAÇÃO DE ALARMES/SENSORES', 11, true),
    ('finalidade', 12, 'INSTALAÇÃO DE REDE', 12, true),
    ('finalidade', 13, 'VENDA', 13, true),
    ('finalidade', 14, 'INSTALAÇÃO DE INTERFONE', 14, true),
    ('finalidade', 15, 'INSTALAÇÃO  AUTOMAÇÃO DE MOTOR DE PORTÃO', 15, true),
    ('finalidade', 16, 'INSTALAÇÃO DE INFRA ESTRUTURA', 16, true),
    ('finalidade', 17, 'INSTALAÇÃO DE CERCA ESPIRAL', 17, true),
    ('finalidade', 18, 'INSTALAÇÃO DE SUPER CERCA', 18, true),
    ('finalidade', 19, 'INSTALAÇÃO DE TELEFONIA', 19, true),
    ('finalidade', 20, 'CHAMADO TÉCNICO', 20, true),
    ('finalidade', 21, 'EMPRESTIMO BANCARIO', 21, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('funcao', 1, 'DIRETOR GERAL', 1, true),
    ('funcao', 2, 'DIRETOR ADMINISTRATIVO', 2, true),
    ('funcao', 3, 'DIRETOR COMERCIAL', 3, true),
    ('funcao', 4, 'GERENTE', 4, true),
    ('funcao', 5, 'RECEPCIONISTA', 5, true),
    ('funcao', 6, 'AUXILIAR ADMINISTRATIVO', 6, true),
    ('funcao', 7, 'LOGISTICA', 7, true),
    ('funcao', 8, 'SERVICOS GERAIS', 8, true),
    ('funcao', 9, 'ESTOQUISTA', 9, true),
    ('funcao', 10, 'TECNICO DE CAMPO', 10, true),
    ('funcao', 11, 'TECNICO', 11, true),
    ('funcao', 12, 'TECNICO DE MICRO', 12, true),
    ('funcao', 13, 'TECNICO DE MONITOR', 13, true),
    ('funcao', 14, 'TECNICO DE ESTABILIZAR / NO-BREAK', 14, true),
    ('funcao', 15, 'ASSISTENTE FINANCEIRO', 15, true),
    ('funcao', 16, 'CONTADOR', 16, true),
    ('funcao', 17, 'TECNICO CONTABIL', 17, true),
    ('funcao', 18, 'ENTREGADOR', 18, true),
    ('funcao', 19, 'GERENTE DE LABORATORIO', 19, true),
    ('funcao', 20, 'COORDENADOR TECNICO', 20, true),
    ('funcao', 24, 'LOGISTICA', 21, true),
    ('funcao', 25, 'TE', 22, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('tipo_pagamento', 2, 'CHEQUE DO DIA', 1, true),
    ('tipo_pagamento', 3, 'CHEQUE PRE-DATADO', 2, true),
    ('tipo_pagamento', 4, 'CARTAO DE CREDITO', 3, true),
    ('tipo_pagamento', 5, 'CARTAO DE DEBITO', 4, true),
    ('tipo_pagamento', 6, 'BOLETO BANCARIO', 5, true),
    ('tipo_pagamento', 7, 'DEPOSITO BANCARIO', 6, true),
    ('tipo_pagamento', 8, 'TRANSF. BANCARIA', 7, true),
    ('tipo_pagamento', 10, 'FATURA', 8, true),
    ('tipo_pagamento', 11, 'DÉBITO AUTOMÁTICO', 9, true),
    ('tipo_pagamento', 12, 'PROMISSORIA', 10, true),
    ('tipo_pagamento', 13, 'DUPLICATA', 11, true),
    ('tipo_pagamento', 15, 'RECIBO', 12, true),
    ('tipo_pagamento', 17, 'DOC', 13, true),
    ('tipo_pagamento', 19, 'SAQUE', 14, true),
    ('tipo_pagamento', 20, 'OUTRO TIPO', 15, true),
    ('tipo_pagamento', 21, 'CORTESIA', 16, true),
    ('tipo_pagamento', 22, 'DEVOLVIDO SEM CONSERTO', 17, true),
    ('tipo_pagamento', 23, 'NAO APRESENTOU DEFEITO', 18, true),
    ('tipo_pagamento', 24, 'SEM VALOR A RECEBER', 19, true),
    ('tipo_pagamento', 26, 'GARANTIA BIOSTECH - RETORNO', 20, true),
    ('tipo_pagamento', 27, 'GARANTIA DO FABRICANTE - EXTENDIDA', 21, true),
    ('tipo_pagamento', 28, 'CONTRATO MANUTENÇÃO - BIOSTECH', 22, true),
    ('tipo_pagamento', 29, 'GARANTIA BIOSTECH', 23, true),
    ('tipo_pagamento', 30, 'GARANTIA DO FABRICANTE - NORMAL', 24, true),
    ('tipo_pagamento', 31, 'COBRADO NA O.S', 25, true),
    ('tipo_pagamento', 32, 'COBRADO NO C.T', 26, true),
    ('tipo_pagamento', 33, 'BOLETO C/NOTA', 27, true),
    ('tipo_pagamento', 34, 'RECIBO C/NOTA', 28, true),
    ('tipo_pagamento', 35, 'FATURA C/NOTA', 29, true),
    ('tipo_pagamento', 36, 'ORÇAMENTO NÃO APROVADO', 30, true),
    ('tipo_pagamento', 37, 'DEPOSITO BANCARIO C/NOTA', 31, true),
    ('tipo_pagamento', 38, 'DESCONTO EM FOLHA', 32, true),
    ('tipo_pagamento', 39, 'CONSERTO INVIAVEL', 33, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('natureza_operacao', 1, 'REMESSA DE PRODUTOS EM GARANTIA', 1, true),
    ('natureza_operacao', 2, 'REMESSA PARA CONSERTO', 2, true),
    ('natureza_operacao', 3, 'MANUTENCAO', 3, true),
    ('natureza_operacao', 4, 'DEVOLUCAO REMESSA EM GARANTIA', 4, true),
    ('natureza_operacao', 5, 'DEVOLUCAO REMESSA PARA REPARO', 5, true),
    ('natureza_operacao', 6, 'REMESSA DE PRODUTOS SEM GARANTIA', 6, true),
    ('natureza_operacao', 7, 'COMPRAS', 7, true),
    ('natureza_operacao', 8, 'PRESTACAO DE SERVICOS', 8, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

-- Locais de atendimento: o Access nao possui tabela propria; a lista foi
-- levantada dos valores distintos de CHAMADO_03.LOCAL_ATEND.
INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem, ativo) VALUES
    ('local_atendimento', 1, 'COORDENAÇÃO E FARMACIA VIDA IMAGEM', 1, true),
    ('local_atendimento', 2, 'RH- 2º ANDAR', 2, true),
    ('local_atendimento', 3, 'SALA DE GERENCIA E SALA DE ANALISTAS', 3, true)
ON CONFLICT (tipo, codigo) DO UPDATE
    SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

-- O codigo 6 de status_equipamento foi um registro especulativo da carga inicial
-- de Chamados; o STATUS_EQUIP do Access nao o possui e nada o referencia.
DELETE FROM {schema}.biostech_dominios WHERE tipo = 'status_equipamento' AND codigo = 6;

-- Parametros iniciais (origem: VALOR_ORC_NAPROV)
INSERT INTO {schema}.biostech_basico_parametros
    (id, valor, prazo_ret_equip, valor_taxa_perm, prazo_ini_envio_orc,
     prazo_fim_envio_orc, dias_limite, texto_rodape)
VALUES (1, 25, NULL, NULL, NULL, NULL, NULL, 'Informamos que a Biostech tem o prazo de até 48 a 72 horas para o envio de orçamento após o diagnóstico do equipamento. Todos os orçamentos são enviados e registrados via email ou wathsapp, e seu recebimento pelo cliente deverá ser confirmado pelo técnico da Biostech. Após confirmação do recebimento do orçamento o cliente tem o prazo de até 72 horas para a aprovação. Caso o orçamento não seja aprovado será cobrado o valor de R$ 25,00 por cada equipamento não aprovado. 
Após a conclusão dos serviços o cliente tem prazo de 10 dias para a retirada do equipamento, decorrido este prazo será cobrada uma taxa de permanência diária estipulada pela Biostech. A não retirada do equipamento no prazo de 90 dias após conclusão dos serviços causa a perda do equipamento para a Biostech que poderá utilizar o mesmo para se ressarcir dos custos adquiridos com o serviço. Para informações de orçamento, chamado técnico e ordem de serviço ligar para o telefone: (85) 3257-4585

BIOSTECH - Av. Visconde do Rio Branco, 3659 - Joaquim Távora - Fortaleza - Ceará - CEP 60055-172 - Fone (85) 3257.4585 - E-mail: financeiro@biostech.com.br')
ON CONFLICT (id) DO NOTHING;

-- Modulos de permissao (origem: PERMISSOES_MODULO_01)
INSERT INTO {schema}.biostech_basico_modulos (codigo, nome, ordem, ativo) VALUES
    (1, 'Administrativo', 1, true),
    (2, 'Chamado Técnico (Campo)', 2, true),
    (3, 'Clientes', 3, true),
    (4, 'Financeiro', 4, true),
    (5, 'Equipamentos', 5, true),
    (6, 'Backup Biostec', 6, true),
    (7, 'Fornecedores', 7, true),
    (8, 'Orçamentos', 8, true),
    (9, 'Ordem de Serviço', 9, true),
    (10, 'Almoxarifado', 10, true),
    (11, 'Recursos Humanos', 11, true),
    (12, 'Tabelas Básicas', 12, true),
    (13, 'Veiculos', 13, true),
    (14, 'Relatórios Gerenciais', 14, true),
    (15, 'Relatórios Gerais', 15, true)
ON CONFLICT (codigo) DO UPDATE SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem;

-- -----------------------------------------------------------------------------
-- Conferencia
-- -----------------------------------------------------------------------------
SELECT tipo, count(*) AS itens FROM {schema}.biostech_dominios
 WHERE tipo IN ('tipo_equipamento', 'status_os', 'status_equipamento',
               'condicao_pagamento', 'situacao_orcamento', 'resultado_orcamento',
               'local_atendimento', 'finalidade', 'natureza_operacao',
               'funcao', 'tipo_pagamento')
 GROUP BY tipo ORDER BY tipo;
