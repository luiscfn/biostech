-- ============================================================
-- BIOSTECH - modulo Orcamentos (comercial + dimensionamento)
--
--   Origem Access:
--     ORCAMENTO_01, 01A, 01B, 01C, 02, 03, 04, 05, LOG_01
--     ORCAM_DIMEN_01, 02, 03, 03A, 04, 06, 07, LOG_01
--
--   Normalizacoes em relacao ao Access (ver docs/09-regras-orcamentos.md):
--     * ORCAMENTO_01C era uma copia dos dados comerciais (finalidade,
--       observacao, condicao de pagamento, prazos, assinatura). Aqui os
--       campos ficam somente em biostech_orcamentos (01B prevalece sobre
--       01C no ETL).
--     * ORCAMENTO_02_AM (vazia) e ORCAM_DIMEN_05 (vazia) nao sao criadas.
--     * os contadores legados CONTADOR_PERCENTUAL* foram substituidos por
--       VALUNIT_ORIGINAL/VALTOT_ORIGINAL em biostech_orcamento_itens.
--     * o log por campo (D-07) usa biostech_orcamento_log e
--       biostech_orcamento_dimensionamento_log, sem FK, para preservar o
--       historico mesmo apos a exclusao fisica do orcamento.
--
--   {schema} e substituido por scripts/sql/aplicar.php
-- ============================================================

-- ------------------------------------------------------------
-- Orcamento - colunas novas na tabela existente
-- ------------------------------------------------------------
ALTER TABLE {schema}.biostech_orcamentos
    ADD COLUMN IF NOT EXISTS resultado           smallint,
    ADD COLUMN IF NOT EXISTS data_resultado      date,
    ADD COLUMN IF NOT EXISTS motivo_cancelamento text;

COMMENT ON COLUMN {schema}.biostech_orcamentos.situacao IS
    'SIT_ORC_01: 1 ABERTO, 2 ENVIADO, 3 PENDENTE, 4 CONCLUIDO, 5 ENCERRADO';
COMMENT ON COLUMN {schema}.biostech_orcamentos.resultado IS
    'SIT_ORC_02: 1 APROVADO, 2 NAO APROVADO, 3 NAO ENVIADO, 4 EM ANALISE PELO CLIENTE, 5 AGUARDA APROVACAO, 6 CANCELADO PELO CLIENTE, 7 CANCELADO PELA BIOSTECH';

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'biostech_orcamentos_situacao_ck') THEN
        ALTER TABLE {schema}.biostech_orcamentos
            ADD CONSTRAINT biostech_orcamentos_situacao_ck
            CHECK (situacao IS NULL OR situacao BETWEEN 1 AND 5);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'biostech_orcamentos_resultado_ck') THEN
        ALTER TABLE {schema}.biostech_orcamentos
            ADD CONSTRAINT biostech_orcamentos_resultado_ck
            CHECK (resultado IS NULL OR resultado BETWEEN 1 AND 7);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS biostech_orcamentos_data_idx
    ON {schema}.biostech_orcamentos (data DESC);
CREATE INDEX IF NOT EXISTS biostech_orcamentos_situacao_idx
    ON {schema}.biostech_orcamentos (situacao, resultado, data DESC);
CREATE INDEX IF NOT EXISTS biostech_orcamentos_cliente_idx
    ON {schema}.biostech_orcamentos (cliente_id);
CREATE INDEX IF NOT EXISTS biostech_orcamentos_dim_idx
    ON {schema}.biostech_orcamentos (dimensionamento_numero);

-- ------------------------------------------------------------
-- Contatos / follow-up do orcamento (origem: ORCAMENTO_04)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_contatos (
    id                    bigserial PRIMARY KEY,
    orcamento_numero      integer NOT NULL
                          REFERENCES {schema}.biostech_orcamentos (numero) ON DELETE CASCADE,
    data_mov              date NOT NULL DEFAULT CURRENT_DATE,
    hora_mov              time,
    assunto               varchar(80),
    funcionario_id        integer,
    contato               varchar(50),
    descricao             text,
    data_proximo_contato  date,
    hora_proximo_contato  time,
    created_at            timestamptz NOT NULL DEFAULT now(),
    updated_at            timestamptz
);

CREATE UNIQUE INDEX IF NOT EXISTS biostech_orcamento_contatos_uk
    ON {schema}.biostech_orcamento_contatos
       (orcamento_numero, data_mov, COALESCE(hora_mov, '00:00'::time));
CREATE INDEX IF NOT EXISTS biostech_orcamento_contatos_pend_idx
    ON {schema}.biostech_orcamento_contatos (data_proximo_contato)
    WHERE data_proximo_contato IS NOT NULL;

-- ------------------------------------------------------------
-- Servicos com valor (origem: ORCAMENTO_03)
--   A tela 031 soma estes itens como "servicos" (VALSERV).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_servicos_valores (
    id                bigserial PRIMARY KEY,
    orcamento_numero  integer NOT NULL
                      REFERENCES {schema}.biostech_orcamentos (numero) ON DELETE CASCADE,
    numero_item       integer NOT NULL,
    descricao         text NOT NULL,
    quantidade        integer NOT NULL DEFAULT 1,
    valor_unitario    numeric(15,2) NOT NULL DEFAULT 0,
    valor_total       numeric(15,2) NOT NULL DEFAULT 0,
    created_at        timestamptz NOT NULL DEFAULT now(),
    updated_at        timestamptz,
    CONSTRAINT biostech_orcamento_servicos_valores_uk
        UNIQUE (orcamento_numero, numero_item),
    CONSTRAINT biostech_orcamento_servicos_valores_qtd_ck
        CHECK (quantidade > 0)
);

CREATE INDEX IF NOT EXISTS biostech_orcamento_servicos_valores_orc_idx
    ON {schema}.biostech_orcamento_servicos_valores (orcamento_numero);

-- ------------------------------------------------------------
-- Log por campo do orcamento (origem: ORCAMENTO_LOG_01)
--   Sem FK: o historico sobrevive a exclusao fisica do orcamento.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_log (
    id                bigserial PRIMARY KEY,
    legado_id         integer,
    orcamento_numero  integer NOT NULL,
    data_mov          timestamptz NOT NULL DEFAULT now(),
    usuario           varchar(50),
    tipo_mov          char(1) NOT NULL DEFAULT 'A',
    campo             varchar(60),
    valor_anterior    text,
    valor_novo        text,
    dados             jsonb,
    created_at        timestamptz NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX IF NOT EXISTS biostech_orcamento_log_legado_uk
    ON {schema}.biostech_orcamento_log (legado_id)
    WHERE legado_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS biostech_orcamento_log_orc_idx
    ON {schema}.biostech_orcamento_log (orcamento_numero, data_mov DESC);
CREATE INDEX IF NOT EXISTS biostech_orcamento_log_data_idx
    ON {schema}.biostech_orcamento_log (data_mov DESC);

-- ------------------------------------------------------------
-- Dimensionamento - levantamento tecnico (origem: ORCAM_DIMEN_03)
-- ------------------------------------------------------------
ALTER TABLE {schema}.biostech_orcamentos_dimensionamento
    ADD COLUMN IF NOT EXISTS qtd_dias                integer,
    ADD COLUMN IF NOT EXISTS que_funciona            text,
    ADD COLUMN IF NOT EXISTS quer_fazer              text,
    ADD COLUMN IF NOT EXISTS dificuldades            text,
    ADD COLUMN IF NOT EXISTS material_precisa        text,
    ADD COLUMN IF NOT EXISTS providenciar            text,
    ADD COLUMN IF NOT EXISTS observacao              text,
    ADD COLUMN IF NOT EXISTS altura                  numeric(15,2),
    ADD COLUMN IF NOT EXISTS comprimento_muro        numeric(15,2),
    ADD COLUMN IF NOT EXISTS lado_direito            numeric(15,2),
    ADD COLUMN IF NOT EXISTS lado_esquerdo           numeric(15,2),
    ADD COLUMN IF NOT EXISTS frente                  numeric(15,2),
    ADD COLUMN IF NOT EXISTS fundo                   numeric(15,2),
    ADD COLUMN IF NOT EXISTS rebocado                boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS cerca_arame             boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS telhado                 boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS arvore                  boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS cinta_cimento           boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS pega_ladrao             boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS percentual_mat_fixacao  numeric(9,4),
    ADD COLUMN IF NOT EXISTS material_fixacao        numeric(15,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS percentual_mao_obra      numeric(9,4),
    ADD COLUMN IF NOT EXISTS mao_obra                numeric(15,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS percentual_nota_fiscal   numeric(9,4),
    ADD COLUMN IF NOT EXISTS nota_fiscal             numeric(15,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS percentual_acrescimo_custo numeric(9,4),
    ADD COLUMN IF NOT EXISTS acrescimo_custo         numeric(15,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS segunda                 boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS segunda_inicio          numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS segunda_fim             numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS terca                   boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS terca_inicio            numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS terca_fim               numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS quarta                  boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS quarta_inicio           numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS quarta_fim              numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS quinta                  boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS quinta_inicio           numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS quinta_fim              numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS sexta                   boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS sexta_inicio            numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS sexta_fim               numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS sabado                  boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS sabado_inicio           numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS sabado_fim              numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS domingo                 boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS domingo_inicio          numeric(5,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS domingo_fim             numeric(5,2) NOT NULL DEFAULT 0;

CREATE INDEX IF NOT EXISTS biostech_orcamentos_dim_cliente_idx
    ON {schema}.biostech_orcamentos_dimensionamento (cliente_id);
CREATE INDEX IF NOT EXISTS biostech_orcamentos_dim_data_idx
    ON {schema}.biostech_orcamentos_dimensionamento (data DESC);

-- ------------------------------------------------------------
-- Servicos a executar do dimensionamento (origem: ORCAM_DIMEN_06)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_dimensionamento_servicos (
    id                     bigserial PRIMARY KEY,
    dimensionamento_numero integer NOT NULL
                           REFERENCES {schema}.biostech_orcamentos_dimensionamento (numero) ON DELETE CASCADE,
    numero_item            integer NOT NULL,
    descricao              text NOT NULL,
    concluido              boolean NOT NULL DEFAULT false,
    created_at             timestamptz NOT NULL DEFAULT now(),
    updated_at             timestamptz,
    CONSTRAINT biostech_orcamento_dim_servicos_uk
        UNIQUE (dimensionamento_numero, numero_item)
);

-- ------------------------------------------------------------
-- Materiais previstos do dimensionamento (origem: ORCAM_DIMEN_07)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_dimensionamento_materiais (
    id                     bigserial PRIMARY KEY,
    dimensionamento_numero integer NOT NULL
                           REFERENCES {schema}.biostech_orcamentos_dimensionamento (numero) ON DELETE CASCADE,
    numero_item            integer NOT NULL,
    descricao              text NOT NULL,
    quantidade             numeric(15,2) NOT NULL DEFAULT 0,
    created_at             timestamptz NOT NULL DEFAULT now(),
    updated_at             timestamptz,
    CONSTRAINT biostech_orcamento_dim_materiais_uk
        UNIQUE (dimensionamento_numero, numero_item)
);

-- ------------------------------------------------------------
-- Cotacoes de fornecedores do dimensionamento (origem: ORCAM_DIMEN_04)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_dimensionamento_cotacoes (
    id                     bigserial PRIMARY KEY,
    dimensionamento_numero integer NOT NULL
                           REFERENCES {schema}.biostech_orcamentos_dimensionamento (numero) ON DELETE CASCADE,
    produto_id             integer REFERENCES {schema}.biostech_produtos (id),
    fornecedor_codigo      integer,
    descricao_produto      varchar(100),
    data                   date NOT NULL DEFAULT CURRENT_DATE,
    preco_unitario         numeric(15,2) NOT NULL DEFAULT 0,
    created_at             timestamptz NOT NULL DEFAULT now(),
    updated_at             timestamptz
);

CREATE UNIQUE INDEX IF NOT EXISTS biostech_orcamento_dim_cotacoes_uk
    ON {schema}.biostech_orcamento_dimensionamento_cotacoes
       (dimensionamento_numero, COALESCE(produto_id, 0), COALESCE(fornecedor_codigo, 0));
CREATE INDEX IF NOT EXISTS biostech_orcamento_dim_cotacoes_prod_idx
    ON {schema}.biostech_orcamento_dimensionamento_cotacoes (produto_id);

-- ------------------------------------------------------------
-- Log por campo do dimensionamento (origem: ORCAM_DIMEN_LOG_01)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_dimensionamento_log (
    id                     bigserial PRIMARY KEY,
    legado_id              integer,
    dimensionamento_numero integer NOT NULL,
    data_mov               timestamptz NOT NULL DEFAULT now(),
    usuario                varchar(50),
    tipo_mov               char(1) NOT NULL DEFAULT 'A',
    campo                  varchar(60),
    valor_anterior         text,
    valor_novo             text,
    dados                  jsonb,
    created_at             timestamptz NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX IF NOT EXISTS biostech_orcamento_dim_log_legado_uk
    ON {schema}.biostech_orcamento_dimensionamento_log (legado_id)
    WHERE legado_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS biostech_orcamento_dim_log_dim_idx
    ON {schema}.biostech_orcamento_dimensionamento_log (dimensionamento_numero, data_mov DESC);
CREATE INDEX IF NOT EXISTS biostech_orcamento_dim_log_data_idx
    ON {schema}.biostech_orcamento_dimensionamento_log (data_mov DESC);

-- ------------------------------------------------------------
-- Dominios do modulo (codigos legados preservados 1:1)
-- ------------------------------------------------------------
INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem) VALUES
    ('situacao_orcamento', 1, 'ABERTO',             1),
    ('situacao_orcamento', 2, 'ENVIADO',            2),
    ('situacao_orcamento', 3, 'PENDENTE',           3),
    ('situacao_orcamento', 4, 'CONCLUIDO',          4),
    ('situacao_orcamento', 5, 'ENCERRADO',          5),

    ('resultado_orcamento', 1, 'APROVADO',                    1),
    ('resultado_orcamento', 2, 'NAO APROVADO',                2),
    ('resultado_orcamento', 3, 'NAO ENVIADO',                 3),
    ('resultado_orcamento', 4, 'EM ANALISE PELO CLIENTE',     4),
    ('resultado_orcamento', 5, 'AGUARDA APROVACAO',           5),
    ('resultado_orcamento', 6, 'CANCELADO PELO CLIENTE',      6),
    ('resultado_orcamento', 7, 'CANCELADO PELA BIOSTECH',     7),

    ('finalidade', 1,  'MANUTENCAO CORRETIVA COM TROCA DE PECAS',   1),
    ('finalidade', 2,  'MANUTENCAO CORRETIVA SEM TROCA DE PECAS',   2),
    ('finalidade', 3,  'MANUTENCAO PREVENTIVA',                     3),
    ('finalidade', 4,  'PROPOSTA DE VENDA',                         4),
    ('finalidade', 5,  'REVISAO, FORMATACAO, BACKUP, CONFIGURACAO', 5),
    ('finalidade', 6,  'REVISAO, FORMATACAO, CONFIG. SEM BACKUP',   6),
    ('finalidade', 7,  'RECUPERACAO DE DADOS',                      7),
    ('finalidade', 8,  'CONSERTO INVIABEL',                         8),
    ('finalidade', 9,  'SISTEMA DE SEGURANCA ELETRONICA DIGITAL',   9),
    ('finalidade', 10, 'INSTALACAO DE CERCA ELETRICA',             10),
    ('finalidade', 11, 'INSTALACAO DE ALARMES/SENSORES',           11),
    ('finalidade', 12, 'INSTALACAO DE REDE',                       12),
    ('finalidade', 13, 'VENDA',                                    13),
    ('finalidade', 14, 'INSTALACAO DE INTERFONE',                  14),
    ('finalidade', 15, 'INSTALACAO AUTOMACAO DE MOTOR DE PORTAO',  15),
    ('finalidade', 16, 'INSTALACAO DE INFRA ESTRUTURA',            16),
    ('finalidade', 17, 'INSTALACAO DE CERCA ESPIRAL',              17),
    ('finalidade', 18, 'INSTALACAO DE SUPER CERCA',                18),
    ('finalidade', 19, 'INSTALACAO DE TELEFONIA',                  19),
    ('finalidade', 20, 'CHAMADO TECNICO',                          20),
    ('finalidade', 21, 'EMPRESTIMO BANCARIO',                      21)
ON CONFLICT (tipo, codigo) DO NOTHING;

-- ------------------------------------------------------------
-- Numeracao: sequenciais ORC (orcamentos) e DIM (dimensionamentos)
-- ------------------------------------------------------------
INSERT INTO {schema}.biostech_numeracao (tipo, competencia, ultimo, updated_at)
SELECT 'ORC', '',
       COALESCE((SELECT MAX(numero) FROM {schema}.biostech_orcamentos), 0),
       now()
ON CONFLICT (tipo, competencia) DO NOTHING;

INSERT INTO {schema}.biostech_numeracao (tipo, competencia, ultimo, updated_at)
SELECT 'DIM', '',
       COALESCE((SELECT MAX(numero) FROM {schema}.biostech_orcamentos_dimensionamento), 0),
       now()
ON CONFLICT (tipo, competencia) DO NOTHING;
