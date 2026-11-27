-- ============================================================
-- BIOSTECH - modulo Compras
--   pedidos de compra, itens, parcelas, cotacoes de precos,
--   solicitacoes internas de compra, prazos de fornecedor,
--   contas a pagar geradas pelo fechamento e log.
--   marcador {schema} substituido por scripts/sql/aplicar.php
-- ============================================================

-- ------------------------------------------------------------
-- Pedido de compra (origem: SOLICITACAO_COMPRA_01)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_compras_pedidos (
    numero                    integer PRIMARY KEY,
    data                      date NOT NULL DEFAULT CURRENT_DATE,
    hora                      time,
    setor_id                  integer,
    funcionario_id            integer,
    fornecedor_codigo         integer,
    tipo                      smallint,
    situacao                  varchar(20) NOT NULL DEFAULT 'aberto',
    solicitacao_numero        integer,
    observacao                text,
    local_entrega             text,
    responsavel_recebimento   varchar(80),
    fone_responsavel          varchar(20),
    numero_proposta           varchar(30),
    desconto                  numeric(15,2) NOT NULL DEFAULT 0,
    desconto_percentual       numeric(7,4),
    condicao_pagamento        integer,
    prazo_entrega             integer,
    forma_recebimento         integer,
    documento_recebimento     integer,
    centro_custo              integer,
    data_vencimento           date,
    num_nf                    varchar(30),
    data_nf                   date,
    total_bruto               numeric(15,2) NOT NULL DEFAULT 0,
    total                     numeric(15,2) NOT NULL DEFAULT 0,
    conta_pagar_numero        integer,
    usuario_cadastro          varchar(50),
    data_cadastro             timestamptz NOT NULL DEFAULT now(),
    usuario_ultima_alteracao  varchar(50),
    data_ultima_alteracao     timestamptz,
    usuario_fechamento        varchar(50),
    data_fechamento           timestamptz,
    created_at                timestamptz NOT NULL DEFAULT now(),
    updated_at                timestamptz,
    CONSTRAINT biostech_compras_pedidos_situacao_ck
        CHECK (situacao IN ('aberto', 'fechado', 'cancelado'))
);

CREATE INDEX IF NOT EXISTS biostech_compras_pedidos_forn_idx
    ON {schema}.biostech_compras_pedidos (fornecedor_codigo);
CREATE INDEX IF NOT EXISTS biostech_compras_pedidos_data_idx
    ON {schema}.biostech_compras_pedidos (data DESC);
CREATE INDEX IF NOT EXISTS biostech_compras_pedidos_situacao_idx
    ON {schema}.biostech_compras_pedidos (situacao, data DESC);

-- ------------------------------------------------------------
-- Itens do pedido (origem: SOLICITACAO_COMPRA_02)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_compras_itens (
    id                       bigserial PRIMARY KEY,
    pedido_numero            integer NOT NULL
                             REFERENCES {schema}.biostech_compras_pedidos (numero) ON DELETE CASCADE,
    produto_id               integer NOT NULL,
    quantidade               numeric(15,3) NOT NULL,
    valor_unitario           numeric(15,4) NOT NULL DEFAULT 0,
    novo_valor_unitario      numeric(15,4),
    desconto_percentual      numeric(7,4),
    desconto_item            numeric(15,2) NOT NULL DEFAULT 0,
    valor_total              numeric(15,2) NOT NULL DEFAULT 0,
    observacao               text,
    atualiza_preco_compra    boolean NOT NULL DEFAULT false,
    created_at               timestamptz NOT NULL DEFAULT now(),
    updated_at               timestamptz,
    CONSTRAINT biostech_compras_itens_produto_uk UNIQUE (pedido_numero, produto_id),
    CONSTRAINT biostech_compras_itens_qtde_ck CHECK (quantidade > 0)
);

CREATE INDEX IF NOT EXISTS biostech_compras_itens_produto_idx
    ON {schema}.biostech_compras_itens (produto_id);
CREATE INDEX IF NOT EXISTS biostech_compras_itens_pedido_idx
    ON {schema}.biostech_compras_itens (pedido_numero);

-- ------------------------------------------------------------
-- Parcelas do pedido (origem: NPARCn/VPARCn/QTDIASn/DVENCn)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_compras_parcelas (
    pedido_numero            integer NOT NULL
                             REFERENCES {schema}.biostech_compras_pedidos (numero) ON DELETE CASCADE,
    numero_par               integer NOT NULL,
    dias                     integer NOT NULL DEFAULT 0,
    vencimento               date NOT NULL,
    valor                    numeric(15,2) NOT NULL,
    PRIMARY KEY (pedido_numero, numero_par)
);

-- ------------------------------------------------------------
-- Cotacao de precos (origem: COTACAO_PRECOS_01/02)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_compras_cotacoes (
    codigo                    integer PRIMARY KEY,
    data_cotacao              date NOT NULL DEFAULT CURRENT_DATE,
    num_os                    varchar(16),
    solicitante_id            integer,
    funcionario_id            integer,
    observacao                text,
    solicitacao_numero        integer,
    usuario_cadastro          varchar(50),
    data_cadastro             timestamptz NOT NULL DEFAULT now(),
    usuario_ultima_alteracao  varchar(50),
    data_ultima_alteracao     timestamptz,
    created_at                timestamptz NOT NULL DEFAULT now(),
    updated_at                timestamptz
);

CREATE INDEX IF NOT EXISTS biostech_compras_cotacoes_data_idx
    ON {schema}.biostech_compras_cotacoes (data_cotacao DESC);

CREATE TABLE IF NOT EXISTS {schema}.biostech_compras_cotacao_itens (
    id                       bigserial PRIMARY KEY,
    cotacao_codigo           integer NOT NULL
                             REFERENCES {schema}.biostech_compras_cotacoes (codigo) ON DELETE CASCADE,
    produto_id               integer NOT NULL,
    quantidade               numeric(15,3) NOT NULL,
    fornecedor_1             integer,
    preco_1                  numeric(15,4),
    fornecedor_2             integer,
    preco_2                  numeric(15,4),
    fornecedor_3             integer,
    preco_3                  numeric(15,4),
    fornecedor_escolhido     integer,
    numero_pedido            integer,
    created_at               timestamptz NOT NULL DEFAULT now(),
    updated_at               timestamptz,
    CONSTRAINT biostech_compras_cotacao_itens_uk UNIQUE (cotacao_codigo, produto_id),
    CONSTRAINT biostech_compras_cotacao_itens_qtde_ck CHECK (quantidade > 0)
);

CREATE INDEX IF NOT EXISTS biostech_compras_cotacao_itens_prod_idx
    ON {schema}.biostech_compras_cotacao_itens (produto_id);

-- ------------------------------------------------------------
-- Solicitacao interna de compra (origem: SOLICITACAO_COMPRA_01A/02A)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_compras_solicitacoes (
    numero                    integer PRIMARY KEY,
    data                      date NOT NULL DEFAULT CURRENT_DATE,
    hora                      time,
    setor_id                  integer,
    funcionario_id            integer,
    situacao                  varchar(20) NOT NULL DEFAULT 'aberta',
    local_entrega             text,
    responsavel_recebimento   varchar(80),
    fone_responsavel          varchar(20),
    pedido_numero             integer,
    cotacao_codigo            integer,
    observacao                text,
    usuario_cadastro          varchar(50),
    data_cadastro             timestamptz NOT NULL DEFAULT now(),
    usuario_ultima_alteracao  varchar(50),
    data_ultima_alteracao     timestamptz,
    usuario_fechamento        varchar(50),
    data_fechamento           timestamptz,
    created_at                timestamptz NOT NULL DEFAULT now(),
    updated_at                timestamptz,
    CONSTRAINT biostech_compras_solicitacoes_situacao_ck
        CHECK (situacao IN ('aberta', 'fechada', 'cancelada'))
);

CREATE INDEX IF NOT EXISTS biostech_compras_solicitacoes_data_idx
    ON {schema}.biostech_compras_solicitacoes (data DESC);

CREATE TABLE IF NOT EXISTS {schema}.biostech_compras_solicitacao_itens (
    id                       bigserial PRIMARY KEY,
    solicitacao_numero       integer NOT NULL
                             REFERENCES {schema}.biostech_compras_solicitacoes (numero) ON DELETE CASCADE,
    produto_id               integer NOT NULL,
    quantidade               numeric(15,3) NOT NULL,
    repor_estoque            boolean NOT NULL DEFAULT false,
    data_utilizacao          date,
    periodo                  varchar(20),
    selecionado              boolean NOT NULL DEFAULT false,
    exportado                boolean NOT NULL DEFAULT false,
    created_at               timestamptz NOT NULL DEFAULT now(),
    updated_at               timestamptz,
    CONSTRAINT biostech_compras_solicitacao_itens_uk UNIQUE (solicitacao_numero, produto_id),
    CONSTRAINT biostech_compras_solicitacao_itens_qtde_ck CHECK (quantidade > 0)
);

CREATE INDEX IF NOT EXISTS biostech_compras_solicitacao_itens_prod_idx
    ON {schema}.biostech_compras_solicitacao_itens (produto_id);

-- ------------------------------------------------------------
-- Prazos por fornecedor (origem: FORNECEDOR_PRAZOS)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_fornecedor_prazos (
    fornecedor_codigo        integer NOT NULL,
    codigo                   integer NOT NULL,
    forma_pagamento          varchar(40),
    prazo                    varchar(60),
    created_at               timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (fornecedor_codigo, codigo)
);

-- ------------------------------------------------------------
-- Contas a pagar geradas pelo fechamento do pedido
--   situacao: 1 = aberto, 2 = baixado, 3 = cancelado
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_contas_pagar (
    id                       bigserial PRIMARY KEY,
    numero                   integer NOT NULL,
    parcela                  smallint NOT NULL,
    total_parcelas           smallint NOT NULL DEFAULT 1,
    fornecedor_codigo        integer,
    portador_setor           integer,
    emissao                  date,
    lancamento               date NOT NULL DEFAULT CURRENT_DATE,
    vencimento               date NOT NULL,
    valor                    numeric(15,2) NOT NULL,
    forma_recebimento        smallint,
    documento_recebimento    smallint,
    centro_custo             integer,
    documento                varchar(30),
    situacao                 smallint NOT NULL DEFAULT 1,
    historico                text,
    origem                   varchar(20) NOT NULL DEFAULT 'compra',
    origem_id                text,
    usuario_cadastro         varchar(50),
    data_cadastro            timestamptz NOT NULL DEFAULT now(),
    usuario_baixa            varchar(50),
    data_baixa               timestamptz,
    valor_baixado            numeric(15,2),
    created_at               timestamptz NOT NULL DEFAULT now(),
    updated_at               timestamptz,
    CONSTRAINT biostech_contas_pagar_num_uk UNIQUE (numero, parcela),
    CONSTRAINT biostech_contas_pagar_situacao_ck CHECK (situacao IN (1, 2, 3))
);

CREATE INDEX IF NOT EXISTS biostech_contas_pagar_venc_idx
    ON {schema}.biostech_contas_pagar (vencimento);
CREATE INDEX IF NOT EXISTS biostech_contas_pagar_forn_idx
    ON {schema}.biostech_contas_pagar (fornecedor_codigo);
CREATE INDEX IF NOT EXISTS biostech_contas_pagar_origem_idx
    ON {schema}.biostech_contas_pagar (origem, origem_id);

-- ------------------------------------------------------------
-- Log de alteracoes do pedido (origem: SOLICITACAO_COMPRA_LOG_01/01A)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_compras_log (
    id                       bigserial PRIMARY KEY,
    pedido_numero            integer,
    solicitacao_numero       integer,
    acao                     varchar(10),
    usuario                  varchar(50),
    data_mov                 timestamptz,
    detalhe                  jsonb,
    created_at               timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS biostech_compras_log_pedido_idx
    ON {schema}.biostech_compras_log (pedido_numero, data_mov DESC);

-- ------------------------------------------------------------
-- Dados de compra no cadastro do produto (origem: PRODUTO)
-- ------------------------------------------------------------
ALTER TABLE {schema}.biostech_produtos
    ADD COLUMN IF NOT EXISTS percentual_lucro      numeric(15,4),
    ADD COLUMN IF NOT EXISTS ultimo_pedido_compra  integer,
    ADD COLUMN IF NOT EXISTS data_ultima_entrada   date,
    ADD COLUMN IF NOT EXISTS ultima_nf_entrada     varchar(30),
    ADD COLUMN IF NOT EXISTS valor_compra_desconto numeric(15,2);

-- Colunas de cancelamento (bases ja criadas antes desta versao).
ALTER TABLE {schema}.biostech_compras_pedidos
    ADD COLUMN IF NOT EXISTS motivo_cancelamento  text,
    ADD COLUMN IF NOT EXISTS usuario_cancelamento varchar(50),
    ADD COLUMN IF NOT EXISTS data_cancelamento    timestamptz;

ALTER TABLE {schema}.biostech_compras_solicitacoes
    ADD COLUMN IF NOT EXISTS motivo_cancelamento  text,
    ADD COLUMN IF NOT EXISTS usuario_cancelamento varchar(50),
    ADD COLUMN IF NOT EXISTS data_cancelamento    timestamptz;
