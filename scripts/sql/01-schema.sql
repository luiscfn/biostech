-- =============================================================================
-- BIOSTECH - schema da aplicacao (PostgreSQL)
--
-- {schema} e substituido pelo instalador (scripts/etl/instalar.php) conforme
-- a variavel de ambiente DB_SCHEMA (padrao: material_grafico).
--
-- Quando o DBA provisionar um schema dedicado (ex.: "biostech"), basta rodar o
-- instalador apontando DB_SCHEMA=biostech que as tabelas sao criadas la.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Setores (origem: SETORES - SISBIOSD_01.mdb)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_setores (
    id          integer      PRIMARY KEY,
    nome        varchar(70)  NOT NULL,
    ativo       boolean      NOT NULL DEFAULT true,
    created_at  timestamptz  NOT NULL DEFAULT now(),
    updated_at  timestamptz
);

-- -----------------------------------------------------------------------------
-- Usuarios (origem: USUARIOS)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_usuarios (
    id                   serial       PRIMARY KEY,
    login                varchar(30)  NOT NULL,
    nome                 varchar(100) NOT NULL,
    senha_hash           varchar(255) NOT NULL,
    perfil               varchar(20)  NOT NULL DEFAULT 'operador',
    setor_id             integer      REFERENCES {schema}.biostech_setores(id),
    funcionario_id       integer,
    acesso_almoxarifado  boolean      NOT NULL DEFAULT false,
    ativo                boolean      NOT NULL DEFAULT true,
    senha_provisoria     boolean      NOT NULL DEFAULT true,
    ultimo_acesso        timestamptz,
    created_at           timestamptz  NOT NULL DEFAULT now(),
    updated_at           timestamptz,
    CONSTRAINT biostech_usuarios_login_uk UNIQUE (login),
    CONSTRAINT biostech_usuarios_perfil_ck CHECK (perfil IN ('admin', 'gestor', 'operador'))
);

-- -----------------------------------------------------------------------------
-- Clientes (origem: CLIENTES)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_clientes (
    id           integer      PRIMARY KEY,
    nome         varchar(120) NOT NULL,
    fantasia     varchar(120),
    tipo         smallint,
    fornecedor_codigo integer,
    cpf_cnpj     varchar(20),
    ie           varchar(20),
    rg           varchar(20),
    endereco     varchar(80),
    numero       varchar(10),
    complemento  varchar(50),
    bairro       varchar(50),
    cidade       varchar(60),
    uf           varchar(2),
    cep          varchar(10),
    telefone     varchar(20),
    celular      varchar(20),
    fax          varchar(20),
    contato      varchar(40),
    email        varchar(120),
    observacao   text,
    ativo        boolean      NOT NULL DEFAULT true,
    created_at   timestamptz  NOT NULL DEFAULT now(),
    updated_at   timestamptz
);

CREATE INDEX IF NOT EXISTS biostech_clientes_nome_idx ON {schema}.biostech_clientes (nome);
CREATE INDEX IF NOT EXISTS biostech_clientes_doc_idx  ON {schema}.biostech_clientes (cpf_cnpj);

-- Vinculo "fornecedor copiado para cliente" (tela Fornecedores).
-- O ALTER mantem compatibilidade com bancos criados antes desta coluna.
ALTER TABLE {schema}.biostech_clientes
    ADD COLUMN IF NOT EXISTS fornecedor_codigo integer;

CREATE INDEX IF NOT EXISTS biostech_clientes_fornecedor_idx
    ON {schema}.biostech_clientes (fornecedor_codigo);

CREATE UNIQUE INDEX IF NOT EXISTS biostech_clientes_fornecedor_uk
    ON {schema}.biostech_clientes (fornecedor_codigo)
    WHERE fornecedor_codigo IS NOT NULL;

-- -----------------------------------------------------------------------------
-- Produtos (origem: PRODUTO)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_produtos (
    id               integer      PRIMARY KEY,
    codigo           varchar(15),
    descricao        varchar(120) NOT NULL,
    unidade          varchar(10),
    estoque_minimo   integer      NOT NULL DEFAULT 0,
    valor_compra     numeric(15,2) NOT NULL DEFAULT 0,
    valor_venda      numeric(15,2) NOT NULL DEFAULT 0,
    grupo            varchar(3),
    subgrupo         varchar(3),
    estante          integer,
    prateleira       integer,
    marca            varchar(50),
    aplicacao        text,
    situacao         integer,
    ativo            boolean      NOT NULL DEFAULT true,
    created_at       timestamptz  NOT NULL DEFAULT now(),
    updated_at       timestamptz
);

CREATE INDEX IF NOT EXISTS biostech_produtos_descricao_idx ON {schema}.biostech_produtos (descricao);
CREATE INDEX IF NOT EXISTS biostech_produtos_grupo_idx     ON {schema}.biostech_produtos (grupo, subgrupo);

-- -----------------------------------------------------------------------------
-- Clientes especiais (origem: PRODUTO_CLIENTES_ESPECIAIS_01)
--
-- Tabela de precos por cliente. A existencia de pelo menos uma linha e o que
-- marca o cliente como "cliente especial" (o Access nao tem flag em CLIENTES).
-- perc_lucro e o markup aplicado sobre o custo; valor_venda e o preco fechado.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_produto_clientes_especiais (
    cliente_id   integer       NOT NULL
                 REFERENCES {schema}.biostech_clientes (id) ON DELETE CASCADE,
    produto_id   integer       NOT NULL
                 REFERENCES {schema}.biostech_produtos (id) ON DELETE CASCADE,
    perc_lucro   numeric(7,2),
    valor_venda  numeric(15,2),
    created_at   timestamptz   NOT NULL DEFAULT now(),
    updated_at   timestamptz,
    PRIMARY KEY (cliente_id, produto_id)
);

CREATE INDEX IF NOT EXISTS biostech_produto_clientes_especiais_produto_idx
    ON {schema}.biostech_produto_clientes_especiais (produto_id);

-- -----------------------------------------------------------------------------
-- Auditoria
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_auditoria (
    id             bigserial   PRIMARY KEY,
    usuario_id     integer,
    usuario_login  varchar(30),
    acao           varchar(20) NOT NULL,
    entidade       varchar(40) NOT NULL,
    entidade_id    text,
    detalhe        jsonb,
    ip             inet,
    created_at     timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS biostech_auditoria_entidade_idx
    ON {schema}.biostech_auditoria (entidade, created_at DESC);
