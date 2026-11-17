-- =============================================================================
-- BIOSTECH - modulo Chamados / Ordens de Servico (PostgreSQL)
--
-- PROPOSTA. Nao executar em producao antes da aprovacao de
-- docs/06-regras-chamados-os.md (decisoes D-01 a D-10).
--
-- {schema} e substituido pelo instalador (scripts/etl/instalar.php) conforme
-- a variavel de ambiente DB_SCHEMA (padrao: material_grafico).
--
-- Convencoes:
--   * codigos de dominio legados preservados 1:1 (smallint);
--   * dinheiro em numeric(15,2) - nunca float;
--   * timestamptz para auditoria;
--   * exclusao logica (excluido_em / excluido_por) - sem DELETE fisico;
--   * nomes de usuario mantidos como texto (login legado) para fidelidade
--     da migracao; o ETL converte login -> biostech_usuarios.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Dominios (origem: STATUS_OS, SIT_ATUAL_OS_02, STATUS_EQUIP, TIPO_GARANTIA,
--                    PRIORIDADE, TIPO_RD01, FORMA_RECEBIMENTO_*, CODICAO_PAGTO,
--                    TIPO_PAGTO, TIPO_EQUIPAMENTO, ...)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_dominios (
    tipo        varchar(40)  NOT NULL,
    codigo      smallint     NOT NULL,
    nome        varchar(70)  NOT NULL,
    ordem       smallint,
    ativo       boolean      NOT NULL DEFAULT true,
    created_at  timestamptz  NOT NULL DEFAULT now(),
    CONSTRAINT biostech_dominios_pk PRIMARY KEY (tipo, codigo)
);

CREATE INDEX IF NOT EXISTS biostech_dominios_tipo_idx
    ON {schema}.biostech_dominios (tipo, ordem);

-- -----------------------------------------------------------------------------
-- Numeracao (origem: TB_DATA, ULTORC, ULTCR)
--
--   OS   : competencia 'yyyy-mm' -> numero = yyyy + mm + 4 digitos
--   ORC  : sequencial global
--   CR   : sequencial global
--   RECIBO : sequencial global
--
-- Uso: UPDATE ... SET ultimo = ultimo + 1
--      WHERE tipo = ? AND competencia = ? RETURNING ultimo;
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_numeracao (
    tipo         varchar(20) NOT NULL,
    competencia  varchar(7)  NOT NULL DEFAULT '',   -- 'yyyy-mm' ou '' (global)
    ultimo       integer     NOT NULL DEFAULT 0,
    updated_at   timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_numeracao_pk PRIMARY KEY (tipo, competencia)
);

-- -----------------------------------------------------------------------------
-- Equipamentos (origem: EQUIPAMENTO)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_equipamentos (
    numero_serie      varchar(30)  PRIMARY KEY,           -- NUS_EQUIP
    cliente_id        integer      REFERENCES {schema}.biostech_clientes(id),
    tipo_codigo       smallint,  -- dominio validado pela aplicacao
    descricao         varchar(80),
    configuracao      text,
    fabricante        integer,
    nota_fiscal       varchar(10),
    data_nf           date,
    garantia_fim      date,
    ultimo_chamado    varchar(16),                          -- ULT_OS (legado Long)
    data_ultimo_chamado date,                               -- DT_ULT_OS
    data_emprestimo   date,
    data_devolucao    date,
    chamado_emprestimo varchar(16),
    patrimonio        varchar(25),
    ativo             boolean      NOT NULL DEFAULT true,
    created_at        timestamptz  NOT NULL DEFAULT now(),
    updated_at        timestamptz
);

CREATE INDEX IF NOT EXISTS biostech_equipamentos_cliente_idx
    ON {schema}.biostech_equipamentos (cliente_id);

-- -----------------------------------------------------------------------------
-- Estoque (origem: ESTOQUE_02 - QTDE_01 geral / QTDE_02 / QTDE_03 consumida)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_saldos (
    produto_id      integer      PRIMARY KEY REFERENCES {schema}.biostech_produtos(id),
    quantidade      integer      NOT NULL DEFAULT 0,      -- QTDE_01
    quantidade_consumida integer NOT NULL DEFAULT 0,      -- QTDE_03
    updated_at      timestamptz
);

-- Todo movimento de saida/estorno passa por aqui (o Access nao tinha).
CREATE TABLE IF NOT EXISTS {schema}.biostech_estoque_movimentos (
    id               bigserial   PRIMARY KEY,
    produto_id       integer     NOT NULL REFERENCES {schema}.biostech_produtos(id),
    chamado_numero   varchar(16),
    tipo             varchar(10) NOT NULL,                -- 'saida' | 'estorno' | 'entrada'
    quantidade       integer     NOT NULL,
    saldo_anterior   integer,
    saldo_posterior  integer,
    usuario          varchar(50),
    observacao       text,
    created_at       timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_estoque_movimentos_tipo_ck
        CHECK (tipo IN ('saida', 'estorno', 'entrada'))
);

CREATE INDEX IF NOT EXISTS biostech_estoque_movimentos_produto_idx
    ON {schema}.biostech_estoque_movimentos (produto_id, created_at DESC);
CREATE INDEX IF NOT EXISTS biostech_estoque_movimentos_chamado_idx
    ON {schema}.biostech_estoque_movimentos (chamado_numero);

-- -----------------------------------------------------------------------------
-- Chamados / Ordens de Servico (origem: CHAMADO_01 + CHAMADO_02 + CHAMADO_03A)
--
-- Um registro e Chamado OU Ordem de Servico, conforme `categoria` (D-01).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamados (
    numero                varchar(16)  PRIMARY KEY,        -- NUM_OS
    numero_par            varchar(16),                     -- NUM_OS_PARC (D-09)
    categoria             varchar(20)  NOT NULL,           -- 'Chamado' | 'Ordem Serviço'
    cliente_id            integer      NOT NULL REFERENCES {schema}.biostech_clientes(id),
    escritorio_id         integer,                         -- COD_ESC
    orcamento_numero      integer,                         -- ORC
    data_abertura         date         NOT NULL,           -- DAT_CHA
    hora_abertura         time,                            -- HOR_CHA
    status_codigo         smallint     NOT NULL DEFAULT 1, -- STATUS
    situacao_codigo       smallint,                        -- SIT_ATUAL_02
    status_anterior       smallint,                        -- novo (AC-05)
    encerrado             boolean      NOT NULL DEFAULT false,  -- FECHATOT = 'S'
    reaberto              boolean      NOT NULL DEFAULT false,  -- FECHATOT = 'A'
    financeiro_bloqueado  boolean      NOT NULL DEFAULT false,  -- FECHAFIN
    baixa_estoque_feita   boolean      NOT NULL DEFAULT false,  -- BAIXA_AUTOMATICA
    valor_material        numeric(15,2) NOT NULL DEFAULT 0,  -- VALMAT
    valor_servico         numeric(15,2) NOT NULL DEFAULT 0,  -- VALSERV
    valor_mao_obra        numeric(15,2) NOT NULL DEFAULT 0,  -- VALMOBRA
    valor_imposto         numeric(15,2) NOT NULL DEFAULT 0,  -- VALIMPOSTO
    acrescimo_custo       numeric(15,2) NOT NULL DEFAULT 0,  -- ACRESCIMO_CUSTO
    material_fixacao      numeric(15,2) NOT NULL DEFAULT 0,  -- MAT_FIXACAO
    valor_desconto        numeric(15,2) NOT NULL DEFAULT 0,  -- VALDESC
    valor_faturado        numeric(15,2) NOT NULL DEFAULT 0,  -- VALFATUG
    qtd_parcelas          smallint,                          -- QTDE_PARC
    numero_gerado         varchar(16),                       -- OS_GERADA
    data_geracao          date,                              -- DATADA
    defeito_relatado      text,                              -- CHAMADO_03A
    defeito_constatado    text,
    servico_executado     text,
    observacao            text,
    usuario_cadastro      varchar(50),
    data_cadastro         timestamptz  NOT NULL DEFAULT now(),
    usuario_alteracao     varchar(50),
    data_alteracao        timestamptz,
    usuario_fechamento    varchar(50),
    data_fechamento       timestamptz,
    usuario_encerramento  varchar(50),
    data_encerramento     timestamptz,
    excluido_em           timestamptz,
    excluido_por          varchar(50),

    CONSTRAINT biostech_chamados_categoria_ck
        CHECK (categoria IN ('Chamado', 'Ordem Serviço')),
    CONSTRAINT biostech_chamados_status_ck
        CHECK (status_codigo BETWEEN 1 AND 6),           -- AC-04: sem STATUS 20
    CONSTRAINT biostech_chamados_valores_ck
        CHECK (valor_material >= 0 AND valor_servico >= 0 AND valor_mao_obra >= 0
               AND valor_imposto >= 0 AND acrescimo_custo >= 0
               AND material_fixacao >= 0 AND valor_desconto >= 0),
    CONSTRAINT biostech_chamados_parcelas_ck
        CHECK (qtd_parcelas IS NULL OR qtd_parcelas BETWEEN 1 AND 12)
);

CREATE INDEX IF NOT EXISTS biostech_chamados_cliente_idx
    ON {schema}.biostech_chamados (cliente_id, data_abertura DESC);
CREATE INDEX IF NOT EXISTS biostech_chamados_status_idx
    ON {schema}.biostech_chamados (status_codigo, situacao_codigo);
CREATE INDEX IF NOT EXISTS biostech_chamados_abertura_idx
    ON {schema}.biostech_chamados (data_abertura DESC);
CREATE UNIQUE INDEX IF NOT EXISTS biostech_chamados_numero_gerado_uk
    ON {schema}.biostech_chamados (numero_gerado)
    WHERE numero_gerado IS NOT NULL AND excluido_em IS NULL;   -- D-06: par 1:1

-- -----------------------------------------------------------------------------
-- Equipamento do chamado (origem: CHAMADO_03 + CHAMADO_03B)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_equipamento (
    chamado_numero       varchar(16) PRIMARY KEY
                         REFERENCES {schema}.biostech_chamados(numero),
    equipamento_serie    varchar(30) REFERENCES {schema}.biostech_equipamentos(numero_serie),
    voltagem             integer,
    tipo_garantia_codigo smallint,  -- dominio validado pela aplicacao
    autorizada           integer,
    acessorios           text,
    lista_equipamento    text,
    patrimonio           varchar(25),
    solicitante          varchar(50),
    local_atendimento    varchar(50),
    voltagem_saida       integer,
    nota_fiscal_chegada  varchar(20),
    recolhido            boolean NOT NULL DEFAULT false,
    contato              varchar(50),
    -- checklist de bancada (CHAMADO_03B)
    rede_fn              varchar(15),
    rede_ft              varchar(15),
    rede_nt              varchar(15),
    estabilizador        boolean,
    estab_marca          varchar(20),
    estab_potencia       varchar(10),
    estab_kva            varchar(10),
    def_virus            boolean,
    def_software         boolean,
    def_disquete         boolean,
    def_cd_dvd           boolean,
    def_tensao_imp       boolean,
    def_cabos_desc       boolean,
    def_nao_canc_chamado boolean,
    def_abert_equip      boolean,
    def_outros           varchar(50)
);

-- -----------------------------------------------------------------------------
-- Emprestimo de equipamento para backup (origem: CHAMADO_11) - 1:N
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_emprestimos (
    id               bigserial   PRIMARY KEY,
    chamado_numero   varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    equipamento_serie varchar(30) NOT NULL
                     REFERENCES {schema}.biostech_equipamentos(numero_serie),
    data_emprestimo  date        NOT NULL,
    data_devolucao   date,
    acessorios       text,
    created_at       timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS biostech_chamado_emprestimos_chamado_idx
    ON {schema}.biostech_chamado_emprestimos (chamado_numero);
CREATE INDEX IF NOT EXISTS biostech_chamado_emprestimos_equip_idx
    ON {schema}.biostech_chamado_emprestimos (equipamento_serie)
    WHERE data_devolucao IS NULL;

-- -----------------------------------------------------------------------------
-- Atendimento (origem: CHAMADO_04) + devolucao (origem: CHAMADO_07) - 1:1
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_atendimento (
    chamado_numero         varchar(16) PRIMARY KEY
                           REFERENCES {schema}.biostech_chamados(numero),
    previsao_data          date,
    previsao_hora          time,
    atendimento_data       date,
    atendimento_hora       time,
    atendimento_hora_fim   time,
    solucao_data           date,
    solucao_hora           time,
    devolucao_data         date,                            -- DATA_DEVOLUCAO / DT_DEVOLUCAO
    devolucao_hora         time,
    tecnico_atendimento_id integer,                         -- TECNICO_RESP_ATEND
    tecnico_abertura_id    integer,                          -- TEC_ABRIU_OS
    tecnico_conclusao_id   integer,                          -- TEC_CONCLUIU
    responsavel_encerramento_id integer,                     -- RESP_ENCERRAMENTO
    responsavel_entrega    varchar(50),
    responsavel_recebimento integer,
    quem_devolveu          integer,
    quem_recebeu           varchar(50),
    prioridade_codigo      smallint,  -- dominio validado pela aplicacao
    segmento_codigo        smallint,  -- dominio validado pela aplicacao
    valor_recolhimento     numeric(15,2) NOT NULL DEFAULT 0,
    valor_entrega          numeric(15,2) NOT NULL DEFAULT 0,
    pago_recolhimento      boolean NOT NULL DEFAULT false,
    pago_entrega           boolean NOT NULL DEFAULT false,
    autorizada             integer,
    garantia               boolean NOT NULL DEFAULT false,
    recolhido              boolean NOT NULL DEFAULT false,
    contato                varchar(50),
    fone_contato           varchar(30),
    solicitante            varchar(50),
    fone_solicitante       varchar(30),
    local_atendimento      varchar(50),
    -- devolucao (CHAMADO_07)
    devolucao_responsavel_id integer,
    devolucao_responsavel_nome varchar(50),
    devolucao_cpf          varchar(15),
    devolucao_rg           varchar(20),
    setor_despesa_id       integer,
    voltagem_saida         integer,
    servico_executado_devolucao text,
    checklist_codigo       integer,
    CONSTRAINT biostech_chamado_atendimento_valores_ck
        CHECK (valor_recolhimento >= 0 AND valor_entrega >= 0),
    CONSTRAINT biostech_chamado_atendimento_datas_ck
        CHECK (devolucao_data IS NULL OR atendimento_data IS NULL
               OR devolucao_data >= atendimento_data)
);

CREATE INDEX IF NOT EXISTS biostech_chamado_atendimento_tecnico_idx
    ON {schema}.biostech_chamado_atendimento (tecnico_atendimento_id);

-- -----------------------------------------------------------------------------
-- Intervencoes (origem: CHAMADO_05A) - APPEND-ONLY (RN-130..135)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_intervencoes (
    id              bigserial   PRIMARY KEY,
    chamado_numero  varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    data_movimento  date        NOT NULL,
    hora_movimento  time        NOT NULL,
    hora_inicio     time,
    hora_fim        time,
    tecnico_id      integer     NOT NULL,                   -- CODIFUNC (usuario logado)
    descricao       text        NOT NULL,
    resolveu        boolean     NOT NULL DEFAULT false,
    created_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_chamado_intervencoes_uk
        UNIQUE (chamado_numero, data_movimento, hora_movimento)   -- RN-132
);

CREATE INDEX IF NOT EXISTS biostech_chamado_intervencoes_chamado_idx
    ON {schema}.biostech_chamado_intervencoes (chamado_numero, data_movimento, hora_movimento);

CREATE OR REPLACE FUNCTION {schema}.biostech_intervencao_imutavel()
RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'Intervencao e imutavel (chamado %, id %)',
        COALESCE(OLD.chamado_numero, NEW.chamado_numero),
        COALESCE(OLD.id, NEW.id);
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS biostech_chamado_intervencoes_no_update
    ON {schema}.biostech_chamado_intervencoes;
CREATE TRIGGER biostech_chamado_intervencoes_no_update
    BEFORE UPDATE OR DELETE ON {schema}.biostech_chamado_intervencoes
    FOR EACH ROW EXECUTE FUNCTION {schema}.biostech_intervencao_imutavel();

-- -----------------------------------------------------------------------------
-- Pecas aplicadas (origem: CHAMADO_06)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_pecas (
    id                  bigserial   PRIMARY KEY,
    chamado_numero      varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    numero_item         integer     NOT NULL,               -- NUMREQ
    produto_id          integer     REFERENCES {schema}.biostech_produtos(id),
    descricao_produto   varchar(120),
    quantidade          integer     NOT NULL,
    valor_unitario      numeric(15,2) NOT NULL DEFAULT 0,
    valor_total         numeric(15,2) NOT NULL DEFAULT 0,
    valor_mao_obra      numeric(15,2) NOT NULL DEFAULT 0,
    valor_desconto      numeric(15,2) NOT NULL DEFAULT 0,
    quantidade_atendida integer     NOT NULL DEFAULT 0,
    quantidade_excessiva integer    NOT NULL DEFAULT 0,
    quantidade_excessiva_atendida integer NOT NULL DEFAULT 0,
    saiu_estoque        boolean     NOT NULL DEFAULT false,
    data_baixa          date,
    movimento_id        bigint      REFERENCES {schema}.biostech_estoque_movimentos(id),
    excluido_em         timestamptz,
    excluido_por        varchar(50),
    created_at          timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_chamado_pecas_qtd_ck
        CHECK (quantidade > 0 AND quantidade_atendida >= 0),
    CONSTRAINT biostech_chamado_pecas_valores_ck
        CHECK (valor_unitario >= 0 AND valor_total >= 0
               AND valor_mao_obra >= 0 AND valor_desconto >= 0)
);

CREATE UNIQUE INDEX IF NOT EXISTS biostech_chamado_pecas_item_uk
    ON {schema}.biostech_chamado_pecas (chamado_numero, numero_item)
    WHERE excluido_em IS NULL;
CREATE UNIQUE INDEX IF NOT EXISTS biostech_chamado_pecas_produto_uk
    ON {schema}.biostech_chamado_pecas (chamado_numero, produto_id)
    WHERE excluido_em IS NULL AND produto_id IS NOT NULL;   -- RN-140
CREATE INDEX IF NOT EXISTS biostech_chamado_pecas_chamado_idx
    ON {schema}.biostech_chamado_pecas (chamado_numero);

-- -----------------------------------------------------------------------------
-- Materiais do cliente (origem: CHAMADO_08)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_materiais_cliente (
    id             bigserial   PRIMARY KEY,
    chamado_numero varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    produto_id     integer     REFERENCES {schema}.biostech_produtos(id),
    descricao      varchar(120),
    quantidade     integer     NOT NULL,
    nota_fiscal    varchar(15),
    created_at     timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_chamado_materiais_cliente_uk
        UNIQUE (chamado_numero, produto_id)
);

-- -----------------------------------------------------------------------------
-- Servicos (origem: CHAMADO_55 previsto, CHAMADO_44 executado, CHAMADO_66 avulso)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_servicos (
    id             bigserial   PRIMARY KEY,
    chamado_numero varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    numero_item    integer     NOT NULL,
    tipo           varchar(10) NOT NULL,                    -- 'previsto' | 'executado' | 'avulso'
    descricao      text        NOT NULL,
    quantidade     integer     NOT NULL DEFAULT 1,
    valor_unitario numeric(15,2) NOT NULL DEFAULT 0,
    valor_total    numeric(15,2) NOT NULL DEFAULT 0,
    concluido      boolean     NOT NULL DEFAULT false,
    data_execucao  date,
    tecnico_id     integer,
    created_at     timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_chamado_servicos_tipo_ck
        CHECK (tipo IN ('previsto', 'executado', 'avulso')),
    CONSTRAINT biostech_chamado_servicos_qtd_ck CHECK (quantidade > 0)
);

CREATE UNIQUE INDEX IF NOT EXISTS biostech_chamado_servicos_item_uk
    ON {schema}.biostech_chamado_servicos (chamado_numero, tipo, numero_item);
CREATE INDEX IF NOT EXISTS biostech_chamado_servicos_chamado_idx
    ON {schema}.biostech_chamado_servicos (chamado_numero, tipo);

-- -----------------------------------------------------------------------------
-- Contatos / follow-up (origem: CHAMADO_77)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_contatos (
    id                bigserial   PRIMARY KEY,
    chamado_numero    varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    data_movimento    date        NOT NULL,
    hora_movimento    time        NOT NULL,
    descricao         text        NOT NULL,
    responsavel_id    integer,
    contato           varchar(50),
    proximo_contato_data date,
    proximo_contato_hora time,
    concluido         boolean     NOT NULL DEFAULT false,
    created_at        timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_chamado_contatos_uk
        UNIQUE (chamado_numero, data_movimento, hora_movimento)   -- RN-162
);

-- -----------------------------------------------------------------------------
-- Horas por tecnico (origem: CHAMADO_09 e CHAMADO_88)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_horas (
    id             bigserial   PRIMARY KEY,
    chamado_numero varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    tecnico_id     integer,
    data_inicial   date        NOT NULL,
    hora_inicial   time,
    data_final     date,
    hora_final     time,
    origem         varchar(10) NOT NULL DEFAULT 'chamado',  -- 'chamado' | 'agenda'
    created_at     timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_chamado_horas_origem_ck
        CHECK (origem IN ('chamado', 'agenda')),
    CONSTRAINT biostech_chamado_horas_periodo_ck
        CHECK (data_final IS NULL OR data_final >= data_inicial)
);

CREATE UNIQUE INDEX IF NOT EXISTS biostech_chamado_horas_agenda_uk
    ON {schema}.biostech_chamado_horas (tecnico_id, data_inicial)
    WHERE origem = 'agenda';                                  -- RN-171

-- -----------------------------------------------------------------------------
-- Plano de pagamento do encerramento (origem: CHAMADO_10 / CHAMADO_10A)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_planos_pagamento (
    chamado_numero          varchar(16) PRIMARY KEY
                            REFERENCES {schema}.biostech_chamados(numero),
    data_emissao            date,
    data_vencimento_base    date,
    tipo_pagamento          integer,
    funcionario_id          integer,
    codigo_centro_custo     integer,
    condicao_pagamento_codigo smallint,  -- dominio validado pela aplicacao
    forma_recebimento_codigo  smallint,  -- dominio validado pela aplicacao
    documento_codigo        smallint,  -- dominio validado pela aplicacao
    cliente_nota_id         integer REFERENCES {schema}.biostech_clientes(id),
    valor_total             numeric(15,2) NOT NULL DEFAULT 0,
    quantidade_parcelas     smallint NOT NULL DEFAULT 1,
    tem_material_trabalho   boolean NOT NULL DEFAULT false,
    material_devolvido      boolean NOT NULL DEFAULT false,
    numero_recibo           integer,
    created_at              timestamptz NOT NULL DEFAULT now(),
    updated_at              timestamptz,
    CONSTRAINT biostech_chamado_planos_pagamento_qtd_ck
        CHECK (quantidade_parcelas BETWEEN 1 AND 12),          -- RN-190
    CONSTRAINT biostech_chamado_planos_pagamento_valor_ck
        CHECK (valor_total >= 0)
);

-- Parcelas normalizadas (substitui NPARC1..12 / VPARC1..12 / QTDIAS1..12 / DVENC1..12)
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_parcelas (
    chamado_numero  varchar(16) NOT NULL
                    REFERENCES {schema}.biostech_chamados(numero),
    numero          smallint    NOT NULL,
    valor           numeric(15,2) NOT NULL DEFAULT 0,
    dias            integer     NOT NULL DEFAULT 0,
    vencimento      date,
    created_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_chamado_parcelas_pk PRIMARY KEY (chamado_numero, numero),
    CONSTRAINT biostech_chamado_parcelas_numero_ck CHECK (numero BETWEEN 1 AND 12),
    CONSTRAINT biostech_chamado_parcelas_dias_ck
        CHECK ((numero = 1 AND dias >= 0) OR (numero > 1 AND dias >= 1)),  -- RN-191
    CONSTRAINT biostech_chamado_parcelas_valor_ck CHECK (valor >= 0)
);

-- -----------------------------------------------------------------------------
-- Vinculos chamado <-> OS (origem: CHAMADO_99)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_vinculos (
    pai_numero   varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    filho_numero varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    created_at   timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_chamado_vinculos_pk PRIMARY KEY (pai_numero, filho_numero),
    CONSTRAINT biostech_chamado_vinculos_distinto_ck CHECK (pai_numero <> filho_numero)
);

-- -----------------------------------------------------------------------------
-- Eventos e log de alteracao (origem: CHAMADO_LOG_01)
--   Evento = o que aconteceu (abertura, status, encerramento, reabertura...)
--   Log    = campo a campo (valor anterior / novo)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_eventos (
    id             bigserial   PRIMARY KEY,
    chamado_numero varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    tipo           varchar(30) NOT NULL,
    descricao      text,
    usuario        varchar(50),
    dados          jsonb,
    created_at     timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS biostech_chamado_eventos_chamado_idx
    ON {schema}.biostech_chamado_eventos (chamado_numero, created_at DESC);

CREATE TABLE IF NOT EXISTS {schema}.biostech_chamado_log (
    id             bigserial   PRIMARY KEY,
    chamado_numero varchar(16) NOT NULL REFERENCES {schema}.biostech_chamados(numero),
    evento_id      bigint      REFERENCES {schema}.biostech_chamado_eventos(id),
    campo          varchar(60) NOT NULL,
    valor_anterior text,
    valor_novo     text,
    usuario        varchar(50),
    created_at     timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS biostech_chamado_log_chamado_idx
    ON {schema}.biostech_chamado_log (chamado_numero, created_at DESC);

-- -----------------------------------------------------------------------------
-- Orcamentos (origem: ORCAMENTO_01 + 01A + 01B)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamentos (
    numero                 integer     PRIMARY KEY,
    chamado_numero         varchar(16) REFERENCES {schema}.biostech_chamados(numero),
    data                   date        NOT NULL DEFAULT CURRENT_DATE,
    situacao               smallint,
    aprovado               boolean     NOT NULL DEFAULT false,
    nao_aprovado           boolean     NOT NULL DEFAULT false,
    atencao                varchar(70),
    data_aprovacao         date,
    data_envio             date,
    dimensionamento_numero integer,
    cliente_id             integer     REFERENCES {schema}.biostech_clientes(id),
    equipamento_serie      varchar(30),
    nota_fiscal_chegada    varchar(20),
    finalidade             integer,
    observacao             text,
    condicao_pagamento     integer,
    entrega                integer,
    garantia               integer,
    validade               integer,
    validade_pecas         integer,
    assinatura             varchar(50),
    valor_material         numeric(15,2) NOT NULL DEFAULT 0,
    valor_mao_obra         numeric(15,2) NOT NULL DEFAULT 0,
    valor_servico          numeric(15,2) NOT NULL DEFAULT 0,
    valor_imposto          numeric(15,2) NOT NULL DEFAULT 0,
    valor_desconto         numeric(15,2) NOT NULL DEFAULT 0,
    valor_total            numeric(15,2) NOT NULL DEFAULT 0,
    valor_material_original numeric(15,2) NOT NULL DEFAULT 0,
    material_fixacao       numeric(15,2) NOT NULL DEFAULT 0,
    acrescimo_custo        numeric(15,2) NOT NULL DEFAULT 0,
    valor_custos_indiretos numeric(15,2) NOT NULL DEFAULT 0,
    valor_maximo_cliente   numeric(15,2) NOT NULL DEFAULT 0,
    percentual_desconto    numeric(9,4),
    percentual_imposto     numeric(9,4),
    percentual_mao_obra    numeric(9,4),
    percentual_servico     numeric(9,4),
    percentual_nota_fiscal numeric(9,4),
    percentual_mat_fixacao numeric(9,4),
    percentual_acrescimo_custo numeric(9,4),
    percentual_desconto_item   numeric(9,4),
    percentual_acrescimo_item  numeric(9,4),
    usuario_cadastro       varchar(50),
    data_cadastro          timestamptz NOT NULL DEFAULT now(),
    usuario_alteracao      varchar(50),
    data_alteracao         timestamptz,
    usuario_fechamento     varchar(50),
    data_fechamento        timestamptz,
    CONSTRAINT biostech_orcamentos_valores_ck
        CHECK (valor_material >= 0 AND valor_mao_obra >= 0 AND valor_servico >= 0
               AND valor_imposto >= 0 AND valor_desconto >= 0 AND valor_total >= 0)
);

CREATE INDEX IF NOT EXISTS biostech_orcamentos_chamado_idx
    ON {schema}.biostech_orcamentos (chamado_numero);

-- -----------------------------------------------------------------------------
-- Itens do orcamento (origem: ORCAMENTO_02)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_itens (
    id             bigserial   PRIMARY KEY,
    orcamento_numero integer   NOT NULL REFERENCES {schema}.biostech_orcamentos(numero)
                               ON DELETE CASCADE,
    numero_item    integer     NOT NULL,
    produto_id     integer     REFERENCES {schema}.biostech_produtos(id),
    descricao      varchar(100),
    descricao_produto varchar(100),
    quantidade     integer     NOT NULL,
    valor_unitario numeric(15,2) NOT NULL DEFAULT 0,
    valor_total    numeric(15,2) NOT NULL DEFAULT 0,
    valor_unitario_original numeric(15,2) NOT NULL DEFAULT 0,
    valor_total_original    numeric(15,2) NOT NULL DEFAULT 0,
    CONSTRAINT biostech_orcamento_itens_uk UNIQUE (orcamento_numero, numero_item),
    CONSTRAINT biostech_orcamento_itens_qtd_ck CHECK (quantidade > 0)
);

-- -----------------------------------------------------------------------------
-- Servicos do orcamento (origem: ORCAMENTO_05)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_servicos (
    id               bigserial PRIMARY KEY,
    orcamento_numero integer   NOT NULL REFERENCES {schema}.biostech_orcamentos(numero)
                               ON DELETE CASCADE,
    numero_item      integer   NOT NULL,
    descricao        text      NOT NULL,
    concluido        boolean   NOT NULL DEFAULT false,
    CONSTRAINT biostech_orcamento_servicos_uk
        UNIQUE (orcamento_numero, numero_item)
);

-- -----------------------------------------------------------------------------
-- Orcamento de dimensionamento (origem: ORCAM_DIMEN_01 / ORCAM_DIMEN_02)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamentos_dimensionamento (
    numero           integer     PRIMARY KEY,
    data             date        NOT NULL DEFAULT CURRENT_DATE,
    chamado_numero   varchar(16),
    orcamento_numero integer REFERENCES {schema}.biostech_orcamentos(numero),
    cliente_id       integer     REFERENCES {schema}.biostech_clientes(id),
    contato          varchar(30),
    tecnico_id       integer,
    local_instalacao text,
    tipo_contato     varchar(15),
    setor            varchar(30),
    email            varchar(100),
    qtd_tecnicos     integer,
    qtd_comerciais   numeric(15,2) NOT NULL DEFAULT 0,
    qtd_nao_comerciais numeric(15,2) NOT NULL DEFAULT 0,
    solicitante      varchar(50),
    usuario          varchar(50),
    usuario_cadastro varchar(50),
    data_cadastro    timestamptz NOT NULL DEFAULT now(),
    usuario_alteracao varchar(50),
    data_alteracao   timestamptz,
    usuario_fechamento varchar(50),
    data_fechamento  timestamptz
);

CREATE TABLE IF NOT EXISTS {schema}.biostech_orcamento_dimensionamento_itens (
    id                  bigserial PRIMARY KEY,
    dimensionamento_numero integer NOT NULL
                        REFERENCES {schema}.biostech_orcamentos_dimensionamento(numero)
                        ON DELETE CASCADE,
    numero_item         integer NOT NULL,
    produto_id          integer REFERENCES {schema}.biostech_produtos(id),
    descricao           varchar(80),
    descricao_produto   varchar(100),
    quantidade          integer NOT NULL,
    valor_compra        numeric(15,2) NOT NULL DEFAULT 0,
    valor_gasto         numeric(15,2) NOT NULL DEFAULT 0,
    total_compra        numeric(15,2) NOT NULL DEFAULT 0,
    valor_venda         numeric(15,2) NOT NULL DEFAULT 0,
    total_venda         numeric(15,2) NOT NULL DEFAULT 0,
    grupo               varchar(3),
    subgrupo            varchar(3),
    pg                  varchar(3),
    psg                 varchar(3),
    CONSTRAINT biostech_orcamento_dim_itens_uk
        UNIQUE (dimensionamento_numero, numero_item)
);

-- -----------------------------------------------------------------------------
-- Contas a Receber (origem: CONTAS_RECEBER_01)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_contas_receber (
    codigo                 integer     PRIMARY KEY,     -- ULTCR + 1
    parcela                smallint    NOT NULL,
    data_emissao           date        NOT NULL,
    chamado_numero         varchar(16) REFERENCES {schema}.biostech_chamados(numero),
    numero_documento       varchar(20),
    tipo                   integer,
    forma_recebimento_codigo smallint,  -- dominio validado pela aplicacao
    documento_codigo       smallint,  -- dominio validado pela aplicacao
    dias                   integer,
    data_vencimento        date        NOT NULL,
    valor                  numeric(15,2) NOT NULL DEFAULT 0,
    desconto_especial      numeric(15,2) NOT NULL DEFAULT 0,
    vdesp                  numeric(15,2) NOT NULL DEFAULT 0,
    aliq_issqn             numeric(9,4),
    viss                   numeric(15,2) NOT NULL DEFAULT 0,
    vliq                   numeric(15,2) NOT NULL DEFAULT 0,
    banco                  varchar(50),
    agencia                varchar(25),
    conta                  varchar(20),
    cliente_id             integer     REFERENCES {schema}.biostech_clientes(id),
    portador_codigo        smallint,  -- dominio validado pela aplicacao
    num_parcelas           smallint    NOT NULL DEFAULT 1,
    posicao                smallint    NOT NULL DEFAULT 1,
    historico              text,
    codigo_centro_custo    integer,
    ret_caixa              boolean     NOT NULL DEFAULT false,
    selec_faturamento      boolean     NOT NULL DEFAULT false,
    faturado               boolean     NOT NULL DEFAULT false,
    numero_nota_fiscal     varchar(30),
    fechafin               boolean     NOT NULL DEFAULT false,
    baixa_conta            boolean     NOT NULL DEFAULT false,
    arquivada              boolean     NOT NULL DEFAULT false,
    total_arquivada        integer,
    cliente_nota_id        integer     REFERENCES {schema}.biostech_clientes(id),
    usuario_cadastro       varchar(50),
    data_cadastro          timestamptz NOT NULL DEFAULT now(),
    usuario_alteracao      varchar(50),
    data_alteracao         timestamptz,
    usuario_cancelamento   varchar(50),
    data_cancelamento      timestamptz,
    cancelado              boolean     NOT NULL DEFAULT false,
    CONSTRAINT biostech_contas_receber_parcela_ck CHECK (parcela BETWEEN 1 AND 12),
    CONSTRAINT biostech_contas_receber_posicao_ck
        CHECK (posicao BETWEEN 1 AND 12 AND posicao <= num_parcelas),
    CONSTRAINT biostech_contas_receber_valores_ck
        CHECK (valor >= 0 AND vdesp >= 0 AND viss >= 0 AND vliq >= 0)
);

CREATE INDEX IF NOT EXISTS biostech_contas_receber_chamado_idx
    ON {schema}.biostech_contas_receber (chamado_numero);
CREATE INDEX IF NOT EXISTS biostech_contas_receber_vencimento_idx
    ON {schema}.biostech_contas_receber (data_vencimento)
    WHERE NOT cancelado;
CREATE UNIQUE INDEX IF NOT EXISTS biostech_contas_receber_chamado_parcela_uk
    ON {schema}.biostech_contas_receber (chamado_numero, parcela)
    WHERE NOT cancelado;

-- -----------------------------------------------------------------------------
-- Baixas de contas a receber (origem: CONTAS_RECEBER_02)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS {schema}.biostech_contas_receber_baixas (
    id               bigserial   PRIMARY KEY,
    conta_codigo     integer     NOT NULL
                     REFERENCES {schema}.biostech_contas_receber(codigo),
    parcela          smallint    NOT NULL,
    data_pagamento   date        NOT NULL,
    multa            numeric(15,2) NOT NULL DEFAULT 0,
    juros            numeric(15,2) NOT NULL DEFAULT 0,
    desconto         numeric(15,2) NOT NULL DEFAULT 0,
    valor_atualizado numeric(15,2) NOT NULL DEFAULT 0,
    valor_recebido   numeric(15,2) NOT NULL DEFAULT 0,
    arquivada        boolean     NOT NULL DEFAULT false,
    created_at       timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT biostech_contas_receber_baixas_valores_ck
        CHECK (multa >= 0 AND juros >= 0 AND desconto >= 0
               AND valor_atualizado >= 0 AND valor_recebido >= 0)
);

CREATE INDEX IF NOT EXISTS biostech_contas_receber_baixas_conta_idx
    ON {schema}.biostech_contas_receber_baixas (conta_codigo);

-- =============================================================================
-- SEED dos dominios usados pelo modulo (codigos preservados)
-- =============================================================================
INSERT INTO {schema}.biostech_dominios (tipo, codigo, nome, ordem) VALUES
    ('status_os', 1, 'ABERTA',           1),
    ('status_os', 2, 'EM ATENDIMENTO',   2),
    ('status_os', 3, 'PENDENTE',         3),
    ('status_os', 4, 'CONCLUIDA',        4),
    ('status_os', 5, 'ENCERRADA',        5),
    ('status_os', 6, 'INTERVENCAO',      6),

    ('situacao_os', 1,  'CONSERTO EM ANDAMENTO',      1),
    ('situacao_os', 2,  'AGUARDA APROVAÇÃO',          2),
    ('situacao_os', 3,  'ENVIAR ORÇAMENTO',           3),
    ('situacao_os', 4,  'AGUARDA PEÇA',               4),
    ('situacao_os', 5,  'EM TESTE',                   5),
    ('situacao_os', 6,  'NÃO APRESENTOU DEFEITO',     6),
    ('situacao_os', 7,  'CONSERTO INVIÁVEL',          7),
    ('situacao_os', 8,  'ORÇAMENTO NÃO APROVADO',     8),
    ('situacao_os', 9,  'CANCELADA PELO CLIENTE',     9),
    ('situacao_os', 10, 'CONSERTO CONLUIDO - OK',    10),
    ('situacao_os', 11, 'AGUARDA ATENDIMENTO',       11),
    ('situacao_os', 12, 'PENDENTE',                  12),
    ('situacao_os', 13, 'CONFIRMAR ORÇAMENTO',       13),
    ('situacao_os', 14, 'APROVADO',                  14),
    ('situacao_os', 15, 'CONCLUIDA',                 15),

    ('status_equipamento', 2,  'PENDENTE',                      2),
    ('status_equipamento', 3,  'AGUARDA APROVACAO ORCAMENTO',  3),
    ('status_equipamento', 4,  'AGUARDA PECA DA AUTORIZADA',   4),
    ('status_equipamento', 5,  'AGUARDANDO COTAÇÃO DE PEÇA',   5),
    ('status_equipamento', 7,  'PENDENTE POR INTERVENÇÃO',     7),
    ('status_equipamento', 8,  'CONSERTO INVIÁVEL',            8),
    ('status_equipamento', 9,  'EM SERVIÇO',                   9),
    ('status_equipamento', 10, 'EM TESTE',                    10),
    ('status_equipamento', 11, 'SERVIÇO NÃO APROVADO',        11),
    ('status_equipamento', 12, 'CONCLUIDO',                   12),
    ('status_equipamento', 13, 'FUNCIONANDO - OK',            13),

    ('tipo_garantia', 1, 'CONTRATO',           1),
    ('tipo_garantia', 2, 'ORÇAMENTO',          2),
    ('tipo_garantia', 3, 'GARANTIA BIOSTECH',  3),
    ('tipo_garantia', 4, 'GARANTIA MICROSOL',  4),
    ('tipo_garantia', 5, 'DIMENSIONAMENTO',    5),

    ('prioridade', 1, 'ALTA',   1),
    ('prioridade', 2, 'MÉDIA',  2),
    ('prioridade', 3, 'NORMAL', 3),

    ('segmento', 1, 'INFORMÁTICA', 1),
    ('segmento', 2, 'SEGURANÇA',   2)
ON CONFLICT (tipo, codigo) DO NOTHING;
