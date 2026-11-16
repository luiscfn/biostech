-- ============================================================
-- BIOSTECH - modulo Estoque
--   catalogos, fornecedores, movimentos e inventario
--   marcador {schema} substituido por scripts/sql/aplicar.php
-- ============================================================

-- ------------------------------------------------------------
-- Fornecedores (origem: FORNECEDOR)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_fornecedores (
    codigo               integer PRIMARY KEY,
    razao_social         varchar(100) NOT NULL,
    nome_fantasia        varchar(100),
    inscricao_estadual   varchar(20),
    cnpj                 varchar(20),
    endereco             varchar(60),
    numero               varchar(10),
    complemento          varchar(50),
    bairro               varchar(50),
    cidade               varchar(20),
    cep                  varchar(10),
    uf                   varchar(2),
    fone                 varchar(15),
    fax                  varchar(15),
    email                varchar(50),
    contato              varchar(25),
    ponto_referencia     text,
    ativo                boolean NOT NULL DEFAULT true,
    created_at           timestamp with time zone NOT NULL DEFAULT now(),
    updated_at           timestamp with time zone
);

CREATE INDEX IF NOT EXISTS biostech_fornecedores_nome_idx
    ON {schema}.biostech_fornecedores (lower(razao_social));

-- ------------------------------------------------------------
-- Produto x fornecedor (origem: PROD_FORNEC_01)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_produto_fornecedor (
    fornecedor_codigo    integer NOT NULL,
    produto_id           integer NOT NULL,
    codigo_fornecedor    varchar(25) NOT NULL,
    nome_fornecedor      varchar(80),
    preco_venda          numeric(15,4),
    PRIMARY KEY (fornecedor_codigo, produto_id)
);

CREATE INDEX IF NOT EXISTS biostech_produto_fornecedor_prod_idx
    ON {schema}.biostech_produto_fornecedor (produto_id);

-- ------------------------------------------------------------
-- Catalogos do estoque
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_grupos (
    codigo               varchar(3) PRIMARY KEY,
    nome                 varchar(40) NOT NULL,
    percentual_venda     numeric(15,4),
    ativo                boolean NOT NULL DEFAULT true
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_subgrupos (
    grupo_codigo         varchar(3) NOT NULL,
    codigo               varchar(3) NOT NULL,
    nome                 varchar(40) NOT NULL,
    ultimo_item          varchar(3),
    ativo                boolean NOT NULL DEFAULT true,
    PRIMARY KEY (grupo_codigo, codigo)
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_posicoes (
    codigo               integer PRIMARY KEY,
    nome                 varchar(40) NOT NULL
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_estantes (
    codigo               integer PRIMARY KEY,
    nome                 varchar(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_prateleiras (
    estante_codigo       integer NOT NULL,
    codigo               integer NOT NULL,
    nome                 varchar(50) NOT NULL,
    PRIMARY KEY (estante_codigo, codigo)
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_finalidades (
    codigo               integer PRIMARY KEY,
    nome                 varchar(40) NOT NULL
);

CREATE INDEX IF NOT EXISTS biostech_estoque_subgrupos_nome_idx
    ON {schema}.biostech_estoque_subgrupos (lower(nome));

-- ------------------------------------------------------------
-- Movimentos: colunas adicionais para entradas/saidas/ajustes
-- ------------------------------------------------------------
CREATE SEQUENCE IF NOT EXISTS {schema}.biostech_estoque_lote_seq;

ALTER TABLE {schema}.biostech_estoque_movimentos
    ADD COLUMN IF NOT EXISTS lote             bigint,
    ADD COLUMN IF NOT EXISTS documento        varchar(30),
    ADD COLUMN IF NOT EXISTS fornecedor_id    integer,
    ADD COLUMN IF NOT EXISTS setor_id         integer,
    ADD COLUMN IF NOT EXISTS funcionario_id   integer,
    ADD COLUMN IF NOT EXISTS custo_unitario   numeric(15,4),
    ADD COLUMN IF NOT EXISTS motivo           varchar(80),
    ADD COLUMN IF NOT EXISTS origem           varchar(20) NOT NULL DEFAULT 'manual',
    ADD COLUMN IF NOT EXISTS estornado        boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS estornado_por    bigint,
    ADD COLUMN IF NOT EXISTS inventario_id    bigint;

CREATE INDEX IF NOT EXISTS biostech_estoque_mov_produto_idx
    ON {schema}.biostech_estoque_movimentos (produto_id, created_at DESC);

-- Ajustes de inventario passam a ser um tipo de movimento proprio.
ALTER TABLE {schema}.biostech_estoque_movimentos
    DROP CONSTRAINT IF EXISTS biostech_estoque_movimentos_tipo_ck;

ALTER TABLE {schema}.biostech_estoque_movimentos
    ADD CONSTRAINT biostech_estoque_movimentos_tipo_ck
    CHECK (tipo IN ('entrada', 'saida', 'ajuste', 'estorno'));

CREATE INDEX IF NOT EXISTS biostech_estoque_mov_tipo_idx
    ON {schema}.biostech_estoque_movimentos (tipo, created_at DESC);

CREATE INDEX IF NOT EXISTS biostech_estoque_mov_lote_idx
    ON {schema}.biostech_estoque_movimentos (lote);

-- ------------------------------------------------------------
-- Inventario / contagem
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_inventarios (
    id                   bigserial PRIMARY KEY,
    data                 date NOT NULL,
    usuario              varchar(50) NOT NULL,
    status               varchar(20) NOT NULL DEFAULT 'aberto',
    observacao           text,
    created_at           timestamp with time zone NOT NULL DEFAULT now(),
    fechado_at           timestamp with time zone
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_inventario_itens (
    inventario_id        bigint NOT NULL
                         REFERENCES {schema}.biostech_estoque_inventarios (id) ON DELETE CASCADE,
    produto_id           integer NOT NULL,
    saldo_sistema        integer NOT NULL,
    quantidade_contada   integer NOT NULL,
    diferenca            integer NOT NULL,
    PRIMARY KEY (inventario_id, produto_id)
);
