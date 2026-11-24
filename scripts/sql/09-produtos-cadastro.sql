-- ============================================================
-- BIOSTECH - campos do cadastro de produtos (tela F_PRODUTO)
--   marcador {schema} substituido por scripts/sql/aplicar.php
-- ============================================================

ALTER TABLE {schema}.biostech_produtos
    ADD COLUMN IF NOT EXISTS cor                varchar(30),
    ADD COLUMN IF NOT EXISTS referencia         varchar(30),
    ADD COLUMN IF NOT EXISTS tamanho            varchar(20),
    ADD COLUMN IF NOT EXISTS capacidade         varchar(25),
    ADD COLUMN IF NOT EXISTS item               varchar(3),
    ADD COLUMN IF NOT EXISTS valor_ultima_compra numeric(15,2);

COMMENT ON COLUMN {schema}.biostech_produtos.cor IS 'Cor do produto (legado PRODUTO1.COR)';
COMMENT ON COLUMN {schema}.biostech_produtos.referencia IS 'Referencia do produto';
COMMENT ON COLUMN {schema}.biostech_produtos.tamanho IS 'Tamanho (legado PRODUTO1.TAMANHO)';
COMMENT ON COLUMN {schema}.biostech_produtos.capacidade IS 'Capacidade (legado PRODUTO1.CAPACIDADE)';
COMMENT ON COLUMN {schema}.biostech_produtos.item IS 'Sub-sub-grupo (legado PRODUTO1.ITEM)';
COMMENT ON COLUMN {schema}.biostech_produtos.valor_ultima_compra IS 'Valor da ultima compra (legado ULT_VALOR_COMPRA_NF)';
