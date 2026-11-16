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
