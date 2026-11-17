-- =============================================================================
-- BIOSTECH - Modulo Financeiro
--
-- Estrutura de apoio (cadastros, movimentos bancarios, caixa e balanco) e
-- extensao das tabelas de titulos ja existentes:
--   biostech_contas_pagar    (origem CONTAS_PAGAR_01)
--   biostech_contas_receber  (origem CONTAS_RECEBER_01 + CONTAS_RECEBER_03)
--
-- Observacoes de compatibilidade com o Access (SISBIOSD_01.mdb):
--  - bancos, agencias, contas, centros de custo e cartoes preservam os codigos
--    legados (texto quando o Access usava texto: '237', '237/2', 'dm', ...);
--  - datas invalidas do legado (00:00:00, anos 0202/2602/7201) sao gravadas
--    como NULL;
--  - POSICAO do legado e sempre 2, mesmo em titulo de parcela unica; por isso
--    o CHECK posicao <= num_parcelas foi removido;
--  - o codigo de CONTAS_RECEBER e a chave do titulo (nao o par codigo/parcela);
--    os 6 registros duplicados de CONTAS_RECEBER_03 sao renumerados no ETL.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Cadastros de apoio
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_bancos (
    codigo       varchar(20)  PRIMARY KEY,
    nome         varchar(120) NOT NULL,
    ativo        boolean      NOT NULL DEFAULT true,
    created_at   timestamptz  NOT NULL DEFAULT now(),
    updated_at   timestamptz
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_agencias (
    id           bigserial    PRIMARY KEY,
    banco_codigo varchar(20)  NOT NULL,
    agencia      varchar(25)  NOT NULL,
    nome         varchar(120),
    endereco     varchar(120),
    bairro       varchar(60),
    cep          varchar(10),
    cidade       varchar(60),
    uf           varchar(2),
    telefone1    varchar(30),
    telefone2    varchar(30),
    fax          varchar(30),
    contato      varchar(80),
    ativo        boolean      NOT NULL DEFAULT true,
    created_at   timestamptz  NOT NULL DEFAULT now(),
    updated_at   timestamptz,
    CONSTRAINT biostech_fin_agencias_uk UNIQUE (banco_codigo, agencia)
);

CREATE INDEX IF NOT EXISTS biostech_fin_agencias_banco_idx
    ON {schema}.biostech_fin_agencias (banco_codigo);

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_contas (
    id           bigserial    PRIMARY KEY,
    banco_codigo varchar(20)  NOT NULL,
    agencia      varchar(25)  NOT NULL,
    conta        varchar(25)  NOT NULL,
    titular      varchar(120),
    valor_limite numeric(15,2),
    valor_real   numeric(15,2),
    ativo        boolean      NOT NULL DEFAULT true,
    created_at   timestamptz  NOT NULL DEFAULT now(),
    updated_at   timestamptz,
    CONSTRAINT biostech_fin_contas_uk UNIQUE (banco_codigo, agencia, conta)
);

CREATE INDEX IF NOT EXISTS biostech_fin_contas_banco_idx
    ON {schema}.biostech_fin_contas (banco_codigo);

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_cartoes (
    codigo     integer      PRIMARY KEY,
    nome       varchar(80)  NOT NULL,
    ativo      boolean      NOT NULL DEFAULT true,
    created_at timestamptz  NOT NULL DEFAULT now(),
    updated_at timestamptz
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_centros_custo (
    codigo     integer      PRIMARY KEY,
    nome       varchar(120) NOT NULL,
    ativo      boolean      NOT NULL DEFAULT true,
    created_at timestamptz  NOT NULL DEFAULT now(),
    updated_at timestamptz
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_naturezas_operacao (
    codigo     integer      PRIMARY KEY,
    nome       varchar(120) NOT NULL,
    ativo      boolean      NOT NULL DEFAULT true,
    created_at timestamptz  NOT NULL DEFAULT now(),
    updated_at timestamptz
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_status_pagamento (
    codigo     integer      PRIMARY KEY,
    nome       varchar(80)  NOT NULL,
    ativo      boolean      NOT NULL DEFAULT true,
    created_at timestamptz  NOT NULL DEFAULT now(),
    updated_at timestamptz
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_periodicidades (
    codigo     integer      PRIMARY KEY,
    nome       varchar(80)  NOT NULL,
    ativo      boolean      NOT NULL DEFAULT true,
    created_at timestamptz  NOT NULL DEFAULT now(),
    updated_at timestamptz
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_despesas_fixas (
    codigo           integer      PRIMARY KEY,
    nome             varchar(120) NOT NULL,
    natureza_codigo  integer,
    periodicidade    integer,
    centro_custo     integer,
    fornecedor_codigo integer,
    valor            numeric(15,2) NOT NULL DEFAULT 0,
    dia_vencimento   smallint,
    ativo            boolean      NOT NULL DEFAULT true,
    observacao       text,
    created_at       timestamptz  NOT NULL DEFAULT now(),
    updated_at       timestamptz
);

-- -----------------------------------------------------------------------------
-- 2. Cheques
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_cheques (
    id              bigserial    PRIMARY KEY,
    numero          varchar(20),
    banco_codigo    varchar(20),
    agencia         varchar(25),
    conta           varchar(25),
    valor           numeric(15,2) NOT NULL DEFAULT 0,
    favorecido      varchar(120),
    data_emissao    date,
    data_vencimento date,
    data_compensacao date,
    situacao        smallint     NOT NULL DEFAULT 1,  -- 1 emitido, 2 compensado, 3 cancelado
    observacao      text,
    created_at      timestamptz  NOT NULL DEFAULT now(),
    updated_at      timestamptz,
    CONSTRAINT biostech_fin_cheques_situacao_ck CHECK (situacao IN (1, 2, 3))
);

CREATE INDEX IF NOT EXISTS biostech_fin_cheques_banco_idx
    ON {schema}.biostech_fin_cheques (banco_codigo, conta);

CREATE INDEX IF NOT EXISTS biostech_fin_cheques_data_idx
    ON {schema}.biostech_fin_cheques (data_emissao);

-- -----------------------------------------------------------------------------
-- 3. Movimentos bancarios (origem: MOV_BANCARIA_02 + exclusivos de _01)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_movimentos_bancarios (
    id                     bigserial    PRIMARY KEY,
    numero                 integer      NOT NULL,
    data                   date         NOT NULL,
    banco_codigo           varchar(20),
    agencia                varchar(25),
    conta                  varchar(25),
    tipo                   char(1),                 -- C credito / D debito
    valor                  numeric(15,2) NOT NULL DEFAULT 0,
    historico              text,
    conta_pagar_numero     integer,
    conta_pagar_parcela    smallint,
    conta_receber_codigo   integer,
    conta_receber_parcela  smallint,
    saldo_anterior         numeric(15,2),
    saldo_atual            numeric(15,2),
    usuario_cadastro       varchar(50),
    data_cadastro          timestamptz,
    usuario_alteracao      varchar(50),
    data_alteracao         timestamptz,
    created_at             timestamptz  NOT NULL DEFAULT now(),
    updated_at             timestamptz,
    CONSTRAINT biostech_fin_movimentos_bancarios_numero_uk UNIQUE (numero),
    CONSTRAINT biostech_fin_movimentos_bancarios_tipo_ck CHECK (tipo IS NULL OR tipo IN ('C', 'D'))
);

CREATE INDEX IF NOT EXISTS biostech_fin_mov_banc_data_idx
    ON {schema}.biostech_fin_movimentos_bancarios (data);

CREATE INDEX IF NOT EXISTS biostech_fin_mov_banc_conta_idx
    ON {schema}.biostech_fin_movimentos_bancarios (banco_codigo, conta);

CREATE INDEX IF NOT EXISTS biostech_fin_mov_banc_cp_idx
    ON {schema}.biostech_fin_movimentos_bancarios (conta_pagar_numero, conta_pagar_parcela);

CREATE INDEX IF NOT EXISTS biostech_fin_mov_banc_cr_idx
    ON {schema}.biostech_fin_movimentos_bancarios (conta_receber_codigo, conta_receber_parcela);

-- -----------------------------------------------------------------------------
-- 4. Caixa diario e movimentos (origem: MOVICAIXA1 e MOVICAIXA2)
--    MOVICAIXA1.SALDOATUAL = saldo de fechamento do dia; a abertura e o
--    fechamento do dia anterior. A linha "saldo anterior" fica embutida em
--    MOVICAIXA2 como primeira entrada do dia.
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_caixa_diario (
    data           date         PRIMARY KEY,
    data_num       integer,
    saldo_anterior numeric(15,2) NOT NULL DEFAULT 0,
    saldo_atual    numeric(15,2) NOT NULL DEFAULT 0,
    total_entradas numeric(15,2) NOT NULL DEFAULT 0,
    total_saidas   numeric(15,2) NOT NULL DEFAULT 0,
    fechado        boolean,
    cheque         numeric(15,2),
    dinheiro       numeric(15,2),
    created_at     timestamptz  NOT NULL DEFAULT now(),
    updated_at     timestamptz
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_caixa_movimentos (
    id                     bigserial    PRIMARY KEY,
    data                   date         NOT NULL,
    numero                 smallint     NOT NULL,
    data_num               integer,
    setor                  integer,
    documento              varchar(30),
    historico              text,
    valor_entrada          numeric(15,2) NOT NULL DEFAULT 0,
    valor_saida            numeric(15,2) NOT NULL DEFAULT 0,
    chamado_numero         varchar(16),
    forma_recebimento      smallint,
    documento_recebimento  smallint,
    usuario_cadastro       varchar(50),
    data_cadastro          timestamptz,
    usuario_alteracao      varchar(50),
    data_alteracao         timestamptz,
    created_at             timestamptz  NOT NULL DEFAULT now(),
    updated_at             timestamptz,
    CONSTRAINT biostech_fin_caixa_movimentos_uk UNIQUE (data, numero)
);

CREATE INDEX IF NOT EXISTS biostech_fin_caixa_mov_data_idx
    ON {schema}.biostech_fin_caixa_movimentos (data);

ALTER TABLE {schema}.biostech_fin_caixa_diario
    ADD COLUMN IF NOT EXISTS saldo_anterior numeric(15,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS total_entradas numeric(15,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS total_saidas   numeric(15,2) NOT NULL DEFAULT 0;

-- -----------------------------------------------------------------------------
-- 5. Balanco financeiro (origem: TB_BALANCO_FINANC_01 / CRIA_BF_01)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS {schema}.biostech_fin_balanco (
    id                 bigserial    PRIMARY KEY,
    data_inicio        date,
    data_fim           date,
    mes_ano            varchar(7),
    bruto_faturado     numeric(15,2),
    iss_faturado       numeric(15,2),
    multa_faturado     numeric(15,2),
    juros_faturado     numeric(15,2),
    desconto_faturado  numeric(15,2),
    liquido_faturado   numeric(15,2),
    bruto_faturar      numeric(15,2),
    iss_faturar        numeric(15,2),
    liquido_faturar    numeric(15,2),
    valor_princ_pago   numeric(15,2),
    juros_pago         numeric(15,2),
    multa_pago         numeric(15,2),
    desconto_pago      numeric(15,2),
    bruto_pago         numeric(15,2),
    valor_princ_pagar  numeric(15,2),
    juros_pagar        numeric(15,2),
    multa_pagar        numeric(15,2),
    bruto_pagar        numeric(15,2),
    created_at         timestamptz  NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS biostech_fin_balanco_mes_idx
    ON {schema}.biostech_fin_balanco (mes_ano);

-- -----------------------------------------------------------------------------
-- 6. Baixas de contas a pagar (origem: CONTAS_PAGAR_02)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS {schema}.biostech_contas_pagar_baixas (
    id               bigserial    PRIMARY KEY,
    conta_pagar_id   bigint       NOT NULL
                     REFERENCES {schema}.biostech_contas_pagar(id) ON DELETE CASCADE,
    numero           integer      NOT NULL,
    parcela          smallint     NOT NULL,
    data_pagamento   date,
    multa            numeric(15,2) NOT NULL DEFAULT 0,
    juros            numeric(15,2) NOT NULL DEFAULT 0,
    desconto         numeric(15,2) NOT NULL DEFAULT 0,
    valor_atualizado numeric(15,2) NOT NULL DEFAULT 0,
    valor_pago       numeric(15,2) NOT NULL DEFAULT 0,
    saldo_caixa      numeric(15,2),
    saldo_conta      numeric(15,2),
    conta_codigo     integer,
    arquivada        boolean      NOT NULL DEFAULT false,
    created_at       timestamptz  NOT NULL DEFAULT now(),
    CONSTRAINT biostech_contas_pagar_baixas_uk UNIQUE (numero, parcela),
    CONSTRAINT biostech_contas_pagar_baixas_valores_ck
        CHECK (multa >= 0 AND juros >= 0 AND desconto >= 0
               AND valor_atualizado >= 0 AND valor_pago >= 0)
);

CREATE INDEX IF NOT EXISTS biostech_contas_pagar_baixas_conta_idx
    ON {schema}.biostech_contas_pagar_baixas (conta_pagar_id);

-- -----------------------------------------------------------------------------
-- 7. Log de alteracoes de contas a pagar (origem: CONTAS_PAGAR_LOG_01)
--    CAMPO / CAMPO_OLD sao convertidos em um unico JSONB: {campo: [antes, depois]}.
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS {schema}.biostech_contas_pagar_log (
    id                bigserial    PRIMARY KEY,
    legado_id         integer,
    conta_pagar_numero integer,
    parcela           smallint,
    data              timestamptz,
    usuario           varchar(50),
    tipo_mov          char(1),                -- A alteracao / C cancelamento / I inclusao
    dados             jsonb,
    created_at        timestamptz  NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS biostech_contas_pagar_log_numero_idx
    ON {schema}.biostech_contas_pagar_log (conta_pagar_numero, parcela);

-- -----------------------------------------------------------------------------
-- 8. Extensoes em biostech_contas_pagar
-- -----------------------------------------------------------------------------

ALTER TABLE {schema}.biostech_contas_pagar
    ADD COLUMN IF NOT EXISTS tipo                    integer,
    ADD COLUMN IF NOT EXISTS banco                   varchar(20),
    ADD COLUMN IF NOT EXISTS agencia                 varchar(25),
    ADD COLUMN IF NOT EXISTS conta                   varchar(25),
    ADD COLUMN IF NOT EXISTS portador                smallint,
    ADD COLUMN IF NOT EXISTS posicao                 smallint,
    ADD COLUMN IF NOT EXISTS ret_caixa               boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS data_caixa              date,
    ADD COLUMN IF NOT EXISTS saldo_caixa             numeric(15,2),
    ADD COLUMN IF NOT EXISTS saldo_conta             numeric(15,2),
    ADD COLUMN IF NOT EXISTS selec_faturamento       boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS faturado                boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS usuario_alteracao       varchar(50),
    ADD COLUMN IF NOT EXISTS data_alteracao          timestamptz,
    ADD COLUMN IF NOT EXISTS cancelado               boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS usuario_cancelamento    varchar(50),
    ADD COLUMN IF NOT EXISTS data_cancelamento       timestamptz;

ALTER TABLE {schema}.biostech_contas_pagar
    ALTER COLUMN vencimento DROP NOT NULL,
    ALTER COLUMN lancamento DROP NOT NULL;

CREATE INDEX IF NOT EXISTS biostech_contas_pagar_banco_idx
    ON {schema}.biostech_contas_pagar (banco, conta);

CREATE INDEX IF NOT EXISTS biostech_contas_pagar_situacao_idx
    ON {schema}.biostech_contas_pagar (situacao, vencimento);

-- -----------------------------------------------------------------------------
-- 9. Extensoes em biostech_contas_receber
-- -----------------------------------------------------------------------------

ALTER TABLE {schema}.biostech_contas_receber
    ADD COLUMN IF NOT EXISTS usuario_baixa  varchar(50),
    ADD COLUMN IF NOT EXISTS data_baixa     timestamptz,
    ADD COLUMN IF NOT EXISTS data_caixa     date;

ALTER TABLE {schema}.biostech_contas_receber
    ALTER COLUMN data_emissao    DROP NOT NULL,
    ALTER COLUMN data_vencimento DROP NOT NULL,
    ALTER COLUMN posicao         DROP NOT NULL;

ALTER TABLE {schema}.biostech_contas_receber
    DROP CONSTRAINT IF EXISTS biostech_contas_receber_posicao_ck;

-- O legado referencia OS que nao foram importadas para biostech_chamados e
-- repete chamado/parcela em titulos diferentes; a referencia fica informativa.
ALTER TABLE {schema}.biostech_contas_receber
    DROP CONSTRAINT IF EXISTS biostech_contas_receber_chamado_numero_fkey;

DROP INDEX IF EXISTS {schema}.biostech_contas_receber_chamado_parcela_uk;

CREATE INDEX IF NOT EXISTS biostech_contas_receber_chamado_parcela_idx
    ON {schema}.biostech_contas_receber (chamado_numero, parcela);

CREATE INDEX IF NOT EXISTS biostech_contas_receber_cliente_idx
    ON {schema}.biostech_contas_receber (cliente_id);

-- -----------------------------------------------------------------------------
-- 10. Relaxamento das baixas de contas a receber
-- -----------------------------------------------------------------------------

ALTER TABLE {schema}.biostech_contas_receber_baixas
    ALTER COLUMN data_pagamento DROP NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS biostech_contas_receber_baixas_conta_parcela_uk
    ON {schema}.biostech_contas_receber_baixas (conta_codigo, parcela);

-- -----------------------------------------------------------------------------
-- 11. Correcao/ampliacao dos dominios financeiros
--     FORMA_RECEBIMENTO_02 mapeia o codigo do documento (COD_DOC_REC):
--       1 BOLETO-C.N | 4 RECIBO-C.N | 5 CORTESIA | 6 COBRADO NA O.S
--       7 COBRADO NO C.T | 8 NOTA FISCAL | 9 SEM VALOR | 11 RECIBO-S.N
--       12 BOLETO-S.N
-- -----------------------------------------------------------------------------

DELETE FROM {schema}.biostech_dominios WHERE tipo = 'documento';

INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem) VALUES
    ('documento', 1,  'BOLETO - C.N',        1),
    ('documento', 4,  'RECIBO - C.N',        4),
    ('documento', 5,  'CORTESIA',            5),
    ('documento', 6,  'COBRADO NA O.S',      6),
    ('documento', 7,  'COBRADO NO C.T',      7),
    ('documento', 8,  'NOTA FISCAL',         8),
    ('documento', 9,  'SEM VALOR A RECEBER', 9),
    ('documento', 11, 'RECIBO - S.N',       11),
    ('documento', 12, 'BOLETO - S.N',       12),

    ('status_pagamento', 1, 'ABERTA',   1),
    ('status_pagamento', 2, 'QUITADA',  2),
    ('status_pagamento', 3, 'EMITIDA',  3),

    ('natureza_operacao', 1, 'REMESSA DE PRODUTOS EM GARANTIA',      1),
    ('natureza_operacao', 2, 'REMESSA PARA CONSERTO',                2),
    ('natureza_operacao', 3, 'MANUTENCAO',                           3),
    ('natureza_operacao', 4, 'DEVOLUCAO REMESSA EM GARANTIA',        4),
    ('natureza_operacao', 5, 'DEVOLUCAO REMESSA PARA REPARO',        5),
    ('natureza_operacao', 6, 'REMESSA DE PRODUTOS SEM GARANTIA',     6),
    ('natureza_operacao', 7, 'COMPRAS',                              7),
    ('natureza_operacao', 8, 'PRESTACAO DE SERVICOS',                8),

    ('periodicidade', 1, 'A VISTA',            1),
    ('periodicidade', 2, 'SEMANAL',            2),
    ('periodicidade', 3, 'QUINZENAL',          3),
    ('periodicidade', 4, 'MENSAL',             4),
    ('periodicidade', 5, 'ENTRADA + SEMANAL',  5),
    ('periodicidade', 6, 'ENTRADA + QUINZENAL',6),
    ('periodicidade', 7, 'ENTRADA + MENSAL',   7)
ON CONFLICT (tipo, codigo) DO NOTHING;
