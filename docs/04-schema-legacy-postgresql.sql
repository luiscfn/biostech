-- =====================================================================
-- BIOSTECH - schema de staging 1:1 (PostgreSQL)
-- Gerado automaticamente a partir de backend-fields.csv
-- Fonte: C:\SISBIOST\Dados\SISBIOSD_01.mdb
-- Gerado em: 2026-11-16 16:06
-- Tabelas: 243 | Colunas: 2770
-- =====================================================================

CREATE SCHEMA IF NOT EXISTS biostech_legacy;
SET search_path TO biostech_legacy, public;

-- ---------------------------------------------------------------------
-- ABASTECIMENTOS  (11 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ABASTECIMENTOS" CASCADE;
CREATE TABLE "ABASTECIMENTOS" (
    "COD_ABAST" integer,
    "PLACA" varchar(7),
    "DATA" date,
    "KM_INICIAL" numeric(18,4),
    "KM_FINAL" numeric(18,4),
    "TIPO_COMBUST" integer,
    "QTD_LITROS" numeric(18,4),
    "VALOR_LITRO" numeric(18,4),
    "VALOR_TOTAL" numeric(18,4),
    "FUNCIONARIO" integer,
    "OBSERVACAO" text
);

-- ---------------------------------------------------------------------
-- ACESSOALMOX  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSOALMOX" CASCADE;
CREATE TABLE "ACESSOALMOX" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer,
    "USU_ACESSO" varchar(50)
);

-- ---------------------------------------------------------------------
-- ACESSOCONTAB  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSOCONTAB" CASCADE;
CREATE TABLE "ACESSOCONTAB" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSOCT_D  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSOCT_D" CASCADE;
CREATE TABLE "ACESSOCT_D" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSOCT_D1  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSOCT_D1" CASCADE;
CREATE TABLE "ACESSOCT_D1" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSOCT_D2  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSOCT_D2" CASCADE;
CREATE TABLE "ACESSOCT_D2" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSOMV  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSOMV" CASCADE;
CREATE TABLE "ACESSOMV" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSOMV_D  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSOMV_D" CASCADE;
CREATE TABLE "ACESSOMV_D" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSORG  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSORG" CASCADE;
CREATE TABLE "ACESSORG" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSORG_D  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSORG_D" CASCADE;
CREATE TABLE "ACESSORG_D" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSORG1  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSORG1" CASCADE;
CREATE TABLE "ACESSORG1" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSORG2  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSORG2" CASCADE;
CREATE TABLE "ACESSORG2" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSORH  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSORH" CASCADE;
CREATE TABLE "ACESSORH" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSORH_D  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSORH_D" CASCADE;
CREATE TABLE "ACESSORH_D" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- ACESSORH_D1  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ACESSORH_D1" CASCADE;
CREATE TABLE "ACESSORH_D1" (
    "JUSUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "JSENHA" varchar(15),
    "CONFIRMA" varchar(15),
    "PRIVILEGIADO" integer
);

-- ---------------------------------------------------------------------
-- AGENCIA  (12 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "AGENCIA" CASCADE;
CREATE TABLE "AGENCIA" (
    "CODBANCO" varchar(10) NOT NULL,
    "AGENCIA" varchar(10) NOT NULL,
    "NOME_AG" varchar(50),
    "ENDER_AG" varchar(50),
    "BAIRRO_AG" varchar(25),
    "NUMCEP_AG" varchar(8),
    "CIDADE_AG" varchar(20),
    "ESTADO_AG" varchar(2),
    "TELEF1_AG" varchar(15),
    "TELEF2_AG" varchar(15),
    "TELFAX_AG" varchar(15),
    "CONTAT_AG" varchar(25)
);

-- ---------------------------------------------------------------------
-- AJEITA_MB  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "AJEITA_MB" CASCADE;
CREATE TABLE "AJEITA_MB" (
    "NUM_MOV" numeric(18,4),
    "DATA" date,
    "CODBANCO" varchar(255),
    "AGENCIA" varchar(255),
    "CONTA" varchar(255),
    "TIPO" varchar(255),
    "VALOR" numeric(18,4),
    "SALDO_ANTERIOR" numeric(18,4),
    "SALDO_ATUAL" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ATESTADOS  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ATESTADOS" CASCADE;
CREATE TABLE "ATESTADOS" (
    "CODIFUNC" integer NOT NULL,
    "DATA" date NOT NULL,
    "INICIO" date,
    "FIM" date,
    "DIAS" integer
);

-- ---------------------------------------------------------------------
-- AUTORIZADAS  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "AUTORIZADAS" CASCADE;
CREATE TABLE "AUTORIZADAS" (
    "COD_AUTORIZADA" integer NOT NULL,
    "NOME_AUTORIZADA" varchar(80)
);

-- ---------------------------------------------------------------------
-- BACKUP_01  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "BACKUP_01" CASCADE;
CREATE TABLE "BACKUP_01" (
    "NUM_OS" varchar(16) NOT NULL,
    "DAT_SAIDA" date,
    "DATA_RETORNO" date,
    "STATUS" integer,
    "OS_MAE" varchar(16)
);

-- ---------------------------------------------------------------------
-- BACKUP_02  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "BACKUP_02" CASCADE;
CREATE TABLE "BACKUP_02" (
    "NUM_OS" varchar(16) NOT NULL,
    "COD_CLI" integer,
    "COD_EQUIP" varchar(30),
    "OBSERVACAO" text,
    "ACESSORIOS" text
);

-- ---------------------------------------------------------------------
-- BANCOS  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "BANCOS" CASCADE;
CREATE TABLE "BANCOS" (
    "CODBANCO" varchar(10) NOT NULL,
    "NOMBANCO" varchar(50)
);

-- ---------------------------------------------------------------------
-- BENEFICIOS  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "BENEFICIOS" CASCADE;
CREATE TABLE "BENEFICIOS" (
    "CODBENEF" integer NOT NULL,
    "TIPO" varchar(6),
    "NOMBENEF" varchar(60),
    "VALBENEF" numeric(18,4),
    "PERDESC" numeric(18,4),
    "DATBENEF" date
);

-- ---------------------------------------------------------------------
-- CARTOES  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CARTOES" CASCADE;
CREATE TABLE "CARTOES" (
    "CODCARTAO" integer NOT NULL,
    "NOMCARTAO" varchar(50)
);

-- ---------------------------------------------------------------------
-- CENTRO_CUSTOS  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CENTRO_CUSTOS" CASCADE;
CREATE TABLE "CENTRO_CUSTOS" (
    "COD_CUSTO" integer NOT NULL,
    "NOM_CUSTO" varchar(60)
);

-- ---------------------------------------------------------------------
-- CHAMADO_01  (32 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_01" CASCADE;
CREATE TABLE "CHAMADO_01" (
    "NUM_OS" varchar(16) NOT NULL,
    "NUM_OS_PARC" varchar(16),
    "ORC" integer,
    "DAT_CHA" date,
    "HOR_CHA" date,
    "VALMAT" numeric(18,4),
    "VALSERV" numeric(18,4),
    "VALIMPOSTO" numeric(18,4),
    "VALMOBRA" numeric(18,4),
    "VALDESC" numeric(18,4),
    "VALFATUG" numeric(18,4),
    "STATUS" integer,
    "SIT_ATUAL_01" integer,
    "SIT_ATUAL_02" integer,
    "FECHATOT" varchar(1),
    "APAGA" integer,
    "CATEGORIA" varchar(50),
    "FECHAFIN" varchar(1),
    "OS_GERADA" varchar(16),
    "DATADA" date,
    "QTDE_PARC" integer,
    "BAIXA_AUTOMATICA" varchar(1),
    "MAT_FIXACAO" numeric(18,4),
    "ACRESCIMO_CUSTO" numeric(18,4),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_FECHAMENTO" varchar(50),
    "DATA_FECHAMENTO" date,
    "USUARIO_ENCERRAMENTO" varchar(50),
    "DATA_ENCERRAMENTO" date
);

-- ---------------------------------------------------------------------
-- CHAMADO_02  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_02" CASCADE;
CREATE TABLE "CHAMADO_02" (
    "NUM_OS" varchar(16) NOT NULL,
    "COD_CLI" integer,
    "COD_ESC" integer
);

-- ---------------------------------------------------------------------
-- CHAMADO_03  (16 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_03" CASCADE;
CREATE TABLE "CHAMADO_03" (
    "NUM_OS" varchar(16) NOT NULL,
    "COD_EQUIP" varchar(30),
    "VOLTAGEM" integer,
    "TIPO_GARANTIA" integer,
    "AUTORIZADA" integer,
    "ACESSORIOS" text,
    "LISTA_EQUIP" text,
    "NUMPAT" varchar(25),
    "SOLICITANTE" varchar(50),
    "LOCAL_ATEND" varchar(50),
    "VOLTSAIDA" integer,
    "NF_CHEGADA" varchar(20),
    "OS_GERADA" varchar(16),
    "DATADA" date,
    "RECOLHIDO" varchar(1),
    "CONTATO" varchar(50)
);

-- ---------------------------------------------------------------------
-- CHAMADO_031  (15 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_031" CASCADE;
CREATE TABLE "CHAMADO_031" (
    "NUM_OS" varchar(16) NOT NULL,
    "COD_EQUIP" varchar(30),
    "VOLTAGEM" integer,
    "TIPO_GARANTIA" integer,
    "AUTORIZADA" integer,
    "ACESSORIOS" text,
    "LISTA_EQUIP" text,
    "NUMPAT" varchar(25),
    "SOLICITANTE" varchar(50),
    "LOCAL_ATEND" varchar(50),
    "VOLTSAIDA" integer,
    "NF_CHEGADA" varchar(20),
    "OS_GERADA" varchar(16),
    "DATADA" date,
    "RECOLHIDO" varchar(1)
);

-- ---------------------------------------------------------------------
-- CHAMADO_03A  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_03A" CASCADE;
CREATE TABLE "CHAMADO_03A" (
    "NUM_OS" varchar(16) NOT NULL,
    "DEF_RELATADO" text,
    "DEF_CONSTATADO" text,
    "SERVICO_EXECUTADO" text,
    "OBSERVACAO" text
);

-- ---------------------------------------------------------------------
-- CHAMADO_03B  (17 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_03B" CASCADE;
CREATE TABLE "CHAMADO_03B" (
    "NUM_OS" varchar(16) NOT NULL,
    "REDE_FN" varchar(15),
    "REDE_FT" varchar(15),
    "REDE_NT" varchar(15),
    "ESTAB" varchar(1),
    "ESTAB_MARCA" varchar(20),
    "ESTAB_POTENCIA" varchar(10),
    "ESTAB_KVA" varchar(10),
    "DEF_VIRUS" varchar(1),
    "DEF_SOFTWARE" varchar(1),
    "DEF_DISQUETE" varchar(1),
    "DEF_CD_DVD" varchar(1),
    "DEF_TENSAO_IMP" varchar(1),
    "DEF_CABOS_DESC" varchar(1),
    "DEF_NAO_CANC_CHAM" varchar(1),
    "DEF_ABERT_EQUIP" varchar(1),
    "DEF_OUTROS" varchar(50)
);

-- ---------------------------------------------------------------------
-- CHAMADO_04  (36 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_04" CASCADE;
CREATE TABLE "CHAMADO_04" (
    "NUM_OS" varchar(16) NOT NULL,
    "DT_PREV_ATEND" date,
    "HR_PREV_ATEND" date,
    "DT_REAL_ATEND" date,
    "HR_REAL_ATEND" date,
    "HR_REAL_ATEND_FIM" date,
    "DT_REAL_SOLUCAO" date,
    "HR_REAL_SOLUCAO" date,
    "DATA_DEVOLUCAO" date,
    "HORA_DEVOLUCAO" date,
    "TECNICO_RESP_ATEND" integer,
    "RESP_ENTREGA_EQUIP" varchar(50),
    "RESP_RECEBIM_EQUIP" integer,
    "TEC_ABRIU_OS" integer,
    "QUEM_DEVOLVEU" integer,
    "QUEM_RECEBEU" varchar(50),
    "PRIORIDADE" integer,
    "TEC_CONCLUIU" integer,
    "RESP_ENCERRAMENTO" integer,
    "NDATA" date,
    "OS_GERADA" varchar(16),
    "DATADA" date,
    "SEGMENTO" integer,
    "VALOR_RECOLHIMENTO" numeric(18,4),
    "VALOR_ENTREGA" numeric(18,4),
    "PAGO_RECOLHIMENTO" varchar(1),
    "PAGO_ENTREGA" varchar(1),
    "RESP_DEVOLUCAO" integer,
    "AUTORIZADA" integer,
    "GARANTIA" varchar(1),
    "RECOLHIDO" varchar(1),
    "CONTATO" varchar(50),
    "FONE_CONTATO" varchar(30),
    "SOLICITANTE" varchar(50),
    "FONE_SOLICITANTE" varchar(30),
    "LOCAL_ATEND" varchar(50)
);

-- ---------------------------------------------------------------------
-- CHAMADO_04A  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_04A" CASCADE;
CREATE TABLE "CHAMADO_04A" (
    "NUM_OS" varchar(16) NOT NULL,
    "SEQUENCIA" integer,
    "DATA" date,
    "HR_INICIO" date,
    "HR_INTERVALO" date,
    "HR_RETORNO" date,
    "HR_FIM" date
);

-- ---------------------------------------------------------------------
-- CHAMADO_05  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_05" CASCADE;
CREATE TABLE "CHAMADO_05" (
    "NUM_OS" varchar(16) NOT NULL,
    "DATAMOV" date NOT NULL,
    "HORAMOV" date NOT NULL,
    "HORAFIM" date,
    "SETOR" integer,
    "CODIFUNC" integer,
    "STATUS" integer,
    "DESCMOV" text
);

-- ---------------------------------------------------------------------
-- CHAMADO_05A  (10 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_05A" CASCADE;
CREATE TABLE "CHAMADO_05A" (
    "NUMOV" integer,
    "NUM_OS" varchar(16),
    "DATAMOV" date,
    "HORAMOV" date,
    "CODIFUNC" integer,
    "DESCMOV" text,
    "TECNICO" integer,
    "HORA_INI" date,
    "HORA_FIM" date,
    "RESOLVEU" varchar(1)
);

-- ---------------------------------------------------------------------
-- CHAMADO_06  (17 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_06" CASCADE;
CREATE TABLE "CHAMADO_06" (
    "NUM_OS" varchar(16) NOT NULL,
    "NUMREQ" integer NOT NULL,
    "CODPROD" integer NOT NULL,
    "DESPROD" varchar(100),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "VALMOBRA" numeric(18,4),
    "VALDESC" numeric(18,4),
    "VALFATUG" numeric(18,4),
    "DATADA" date,
    "QTDE_PARC" integer,
    "QTDE_ATENDIDA" integer,
    "QTDE_EXCESSIVA" integer,
    "QTDE_EXCESSIVA_ATEND" integer,
    "SAIU_ESTOQUE" varchar(1),
    "DATA_BAIXA" date
);

-- ---------------------------------------------------------------------
-- CHAMADO_06_AM  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_06_AM" CASCADE;
CREATE TABLE "CHAMADO_06_AM" (
    "NUM_OS" varchar(16) NOT NULL,
    "NUMREQ" integer NOT NULL,
    "CODPROD" integer NOT NULL,
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "VALMOBRA" numeric(18,4),
    "VALDESC" numeric(18,4),
    "VALFATUG" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- CHAMADO_06_LAB  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_06_LAB" CASCADE;
CREATE TABLE "CHAMADO_06_LAB" (
    "NUM_OS" varchar(16) NOT NULL,
    "CODPROD" integer NOT NULL,
    "DES_PROD" varchar(100),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "VALMOBRA" numeric(18,4),
    "VALDESC" numeric(18,4),
    "VALFATUG" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- CHAMADO_07  (11 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_07" CASCADE;
CREATE TABLE "CHAMADO_07" (
    "NUM_OS" varchar(16) NOT NULL,
    "DT_DEVOLUCAO" date,
    "HR_DEVOLUCAO" date,
    "RESP_DEVOLUCAO" integer,
    "RESP_RECEBIMENTO" varchar(50),
    "CPF_RESP_RECEB" varchar(15),
    "RG_RESP_RECEB" varchar(20),
    "SETOR_DESPESA" integer,
    "VOLTSAIDA" integer,
    "SERVICO_EXECUTADO" text,
    "CHECK_LIST" integer
);

-- ---------------------------------------------------------------------
-- CHAMADO_08  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_08" CASCADE;
CREATE TABLE "CHAMADO_08" (
    "NUM_OS" varchar(16) NOT NULL,
    "CODPROD" integer NOT NULL,
    "DES_PROD" varchar(100),
    "QTDE" integer,
    "NUMNF" varchar(15)
);

-- ---------------------------------------------------------------------
-- CHAMADO_09  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_09" CASCADE;
CREATE TABLE "CHAMADO_09" (
    "NUM_OS" varchar(16) NOT NULL,
    "NUM_ORDEM" integer,
    "TECNICO" integer,
    "DATA_INICIAL" date,
    "DATA_FINAL" date,
    "HORA_INICIAL" date
);

-- ---------------------------------------------------------------------
-- CHAMADO_10  (64 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_10" CASCADE;
CREATE TABLE "CHAMADO_10" (
    "NUM_OS" varchar(16) NOT NULL,
    "DATAPAG" date,
    "DATAVENC" date,
    "TIPOPAG" integer,
    "CODIFUNC" integer,
    "NRECPAG" integer,
    "COD_CC" integer,
    "COND_PAGTO" integer,
    "FORMA_REC" integer,
    "DOC_REC" integer,
    "CLIENTE_NOTA" integer,
    "QTDIAS" integer,
    "TOTPARC" integer,
    "VALTOT" numeric(18,4),
    "NPARC1" integer,
    "VPARC1" numeric(18,4),
    "QTDIAS1" integer,
    "DVENC1" date,
    "NPARC2" integer,
    "VPARC2" numeric(18,4),
    "QTDIAS2" integer,
    "DVENC2" date,
    "NPARC3" integer,
    "VPARC3" numeric(18,4),
    "QTDIAS3" integer,
    "DVENC3" date,
    "NPARC4" integer,
    "VPARC4" numeric(18,4),
    "QTDIAS4" integer,
    "DVENC4" date,
    "NPARC5" integer,
    "VPARC5" numeric(18,4),
    "QTDIAS5" integer,
    "DVENC5" date,
    "NPARC6" integer,
    "VPARC6" numeric(18,4),
    "QTDIAS6" integer,
    "DVENC6" date,
    "NPARC7" integer,
    "VPARC7" numeric(18,4),
    "QTDIAS7" integer,
    "DVENC7" date,
    "NPARC8" integer,
    "VPARC8" numeric(18,4),
    "QTDIAS8" integer,
    "DVENC8" date,
    "NPARC9" integer,
    "VPARC9" numeric(18,4),
    "QTDIAS9" integer,
    "DVENC9" date,
    "NPARC10" integer,
    "VPARC10" numeric(18,4),
    "QTDIAS10" integer,
    "DVENC10" date,
    "NPARC11" integer,
    "VPARC11" numeric(18,4),
    "QTDIAS11" integer,
    "DVENC11" date,
    "NPARC12" integer,
    "VPARC12" numeric(18,4),
    "QTDIAS12" integer,
    "DVENC12" date,
    "TEM_MAT_TRABALHO" varchar(1),
    "MATERIAL_DEVOLVIDO" varchar(1)
);

-- ---------------------------------------------------------------------
-- CHAMADO_10A  (40 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_10A" CASCADE;
CREATE TABLE "CHAMADO_10A" (
    "NUM_OS" varchar(16) NOT NULL,
    "INTERVALO_DIAS" integer,
    "QTDE_PARCELAS" integer,
    "VALOR_TOTAL" numeric(18,4),
    "NPARC1" integer,
    "VPARC1" numeric(18,4),
    "DVENC1" date,
    "NPARC2" integer,
    "VPARC2" numeric(18,4),
    "DVENC2" date,
    "NPARC3" integer,
    "VPARC3" numeric(18,4),
    "DVENC3" date,
    "NPARC4" integer,
    "VPARC4" numeric(18,4),
    "DVENC4" date,
    "NPARC5" integer,
    "VPARC5" numeric(18,4),
    "DVENC5" date,
    "NPARC6" integer,
    "VPARC6" numeric(18,4),
    "DVENC6" date,
    "NPARC7" integer,
    "VPARC7" numeric(18,4),
    "DVENC7" date,
    "NPARC8" integer,
    "VPARC8" numeric(18,4),
    "DVENC8" date,
    "NPARC9" integer,
    "VPARC9" numeric(18,4),
    "DVENC9" date,
    "NPARC10" integer,
    "VPARC10" numeric(18,4),
    "DVENC10" date,
    "NPARC11" integer,
    "VPARC11" numeric(18,4),
    "DVENC11" date,
    "NPARC12" integer,
    "VPARC12" numeric(18,4),
    "DVENC12" date
);

-- ---------------------------------------------------------------------
-- CHAMADO_11  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_11" CASCADE;
CREATE TABLE "CHAMADO_11" (
    "NUM_OS" varchar(16) NOT NULL,
    "COD_EQUIP" varchar(30),
    "DATA_EMPRESTIMO" date,
    "DATA_DEVOLUCAO" date,
    "ACESSORIOS" text
);

-- ---------------------------------------------------------------------
-- CHAMADO_44  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_44" CASCADE;
CREATE TABLE "CHAMADO_44" (
    "NUM_OS" varchar(16) NOT NULL,
    "NUMITEM" integer NOT NULL,
    "DESC_SERV" text,
    "CONCLUIDO" varchar(1),
    "DATA" date,
    "TECNICO" integer
);

-- ---------------------------------------------------------------------
-- CHAMADO_55  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_55" CASCADE;
CREATE TABLE "CHAMADO_55" (
    "NUM_OS" varchar(16) NOT NULL,
    "NUMITEM" integer NOT NULL,
    "DESC_SERV" text,
    "CONCLUIDO" varchar(1)
);

-- ---------------------------------------------------------------------
-- CHAMADO_66  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_66" CASCADE;
CREATE TABLE "CHAMADO_66" (
    "NUM_OS" varchar(16) NOT NULL,
    "NUM_ITEM" integer NOT NULL,
    "DES_ITEM" varchar(100),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- CHAMADO_77  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_77" CASCADE;
CREATE TABLE "CHAMADO_77" (
    "NUM_OS" varchar(16) NOT NULL,
    "DATAMOV" date NOT NULL,
    "HORAMOV" date NOT NULL,
    "DESCRICAO" text,
    "CODIFUNC" integer,
    "CONTATO" varchar(50),
    "DATA_PROX_CONT" date,
    "HORA_PROX_CONT" date,
    "CONCLUIDO" varchar(1)
);

-- ---------------------------------------------------------------------
-- CHAMADO_88  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_88" CASCADE;
CREATE TABLE "CHAMADO_88" (
    "NUM_OS" varchar(16) NOT NULL,
    "CODIGO" integer NOT NULL,
    "TECNICO" integer,
    "DATA_INI" date,
    "DATA_FIM" date,
    "HORA_INI" date,
    "HORA_FIM" date
);

-- ---------------------------------------------------------------------
-- CHAMADO_99  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_99" CASCADE;
CREATE TABLE "CHAMADO_99" (
    "NUM_OS" varchar(16) NOT NULL,
    "NUM_FILHO" varchar(16) NOT NULL,
    "FECHADA" varchar(1)
);

-- ---------------------------------------------------------------------
-- CHAMADO_LOG_01  (87 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CHAMADO_LOG_01" CASCADE;
CREATE TABLE "CHAMADO_LOG_01" (
    "ID" integer,
    "DATAMOV" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "NUM_OS" varchar(16) NOT NULL,
    "CATEGORIA" varchar(50),
    "CATEGORIA_OLD" varchar(50),
    "DAT_CHA" date,
    "DAT_CHA_OLD" date,
    "HOR_CHA" date,
    "HOR_CHA_OLD" date,
    "SIT_ATUAL_01" integer,
    "STATUS" integer,
    "STATUS_OLD" integer,
    "SIT_ATUAL_02" integer,
    "SIT_ATUAL_02_OLD" integer,
    "QTDE_PARC" integer,
    "QTDE_PARC_OLD" integer,
    "VALMAT" numeric(18,4),
    "VALMAT_OLD" numeric(18,4),
    "VALSERV" numeric(18,4),
    "VALSERV_OLD" numeric(18,4),
    "VALIMPOSTO" numeric(18,4),
    "VALIMPOSTO_OLD" numeric(18,4),
    "VALMOBRA" numeric(18,4),
    "VALMOBRA_OLD" numeric(18,4),
    "VALDESC" numeric(18,4),
    "VALDESC_OLD" numeric(18,4),
    "VALFATUG" numeric(18,4),
    "VALFATUG_OLD" numeric(18,4),
    "MAT_FIXACAO" numeric(18,4),
    "MAT_FIXACAO_OLD" numeric(18,4),
    "ACRESCIMO_CUSTO" numeric(18,4),
    "ACRESCIMO_CUSTO_OLD" numeric(18,4),
    "DEF_RELATADO" text,
    "DEF_RELATADO_OLD" text,
    "DEF_CONSTATADO" text,
    "DEF_CONSTATADO_OLD" text,
    "SERVICO_EXECUTADO" text,
    "SERVICO_EXECUTADO_OLD" text,
    "OBSERVACAO" text,
    "OBSERVACAO_OLD" text,
    "DT_PREV_ATEND" date,
    "DT_PREV_ATEND_OLD" date,
    "HR_PREV_ATEND" date,
    "HR_PREV_ATEND_OLD" date,
    "DT_REAL_ATEND" date,
    "DT_REAL_ATEND_OLD" date,
    "HR_REAL_ATEND" date,
    "HR_REAL_ATEND_OLD" date,
    "DT_REAL_ATEND_FIM" date,
    "DT_REAL_ATEND_FIM_OLD" date,
    "HR_REAL_ATEND_FIM" date,
    "HR_REAL_ATEND_FIM_OLD" date,
    "DT_REAL_SOLUCAO" date,
    "DT_REAL_SOLUCAO_OLD" date,
    "HR_REAL_SOLUCAO" date,
    "SIT_ATUAL_01_OLD" integer,
    "HR_REAL_SOLUCAO_OLD" date,
    "DATA_DEVOLUCAO" date,
    "DATA_DEVOLUCAO_OLD" date,
    "HORA_DEVOLUCAO" date,
    "HORA_DEVOLUCAO_OLD" date,
    "TECNICO_RESP_ATEND" integer,
    "TECNICO_RESP_ATEND_OLD" integer,
    "RESP_ENTREGA_EQUIP" varchar(50),
    "RESP_ENTREGA_EQUIP_OLD" varchar(50),
    "RESP_RECEBIM_EQUIP" integer,
    "RESP_RECEBIM_EQUIP_OLD" integer,
    "TEC_ABRIU_OS" integer,
    "TEC_ABRIU_OS_OLD" integer,
    "QUEM_DEVOLVEU" integer,
    "QUEM_DEVOLVEU_OLD" integer,
    "QUEM_RECEBEU" varchar(50),
    "QUEM_RECEBEU_OLD" varchar(50),
    "TEC_CONCLUIU" integer,
    "TEC_CONCLUIU_OLD" integer,
    "CONTATO" varchar(50),
    "CONTATO_OLD" varchar(50),
    "SOLICITANTE" varchar(50),
    "SOLICITANTE_OLD" varchar(50),
    "LOCAL_ATEND" varchar(50),
    "LOCAL_ATEND_OLD" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO_OLD" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "DATA_ULTIMA_ALTERACAO_OLD" date
);

-- ---------------------------------------------------------------------
-- CLIENTE_CONTRATO  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CLIENTE_CONTRATO" CASCADE;
CREATE TABLE "CLIENTE_CONTRATO" (
    "CODCLI" integer NOT NULL,
    "COD_CONTRATO" integer NOT NULL,
    "DATA_CADASTRO" date,
    "DATA_CANCELAMENTO" date,
    "DATA_ENCERRAMENTO" date,
    "CAPTADOR" integer
);

-- ---------------------------------------------------------------------
-- CLIENTE_PROBLEMA  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CLIENTE_PROBLEMA" CASCADE;
CREATE TABLE "CLIENTE_PROBLEMA" (
    "CODPROB" integer,
    "CODCLI" integer,
    "DATPROB" date,
    "DESPROB" varchar(50)
);

-- ---------------------------------------------------------------------
-- CLIENTES  (29 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CLIENTES" CASCADE;
CREATE TABLE "CLIENTES" (
    "CODCLI" integer NOT NULL,
    "NOMCLI" varchar(80),
    "NOME_FANTASIA" varchar(80),
    "TIPO" integer,
    "INS_EST" varchar(20),
    "CGC_CLI" varchar(20),
    "CPF" varchar(15),
    "RG" varchar(20),
    "ENDCLI" varchar(60),
    "NUMERO" varchar(10),
    "COMPLEMENTO" varchar(50),
    "BAIRRO" varchar(50),
    "CIDADE" varchar(20),
    "CEP" varchar(10),
    "UF" varchar(2),
    "FONE" varchar(15),
    "FAX" varchar(15),
    "CELULAR" varchar(15),
    "CONTATO" varchar(30),
    "EMAIL" varchar(80),
    "PONTOREF" text,
    "COD_FORN" integer,
    "CAPTADOR" integer,
    "DATA_CADASTRO" date,
    "TIPO_CAPTACAO" integer,
    "OBSERVACAO" text,
    "FORMA_PAGAMENTO" integer,
    "COND_PAGTO" integer,
    "DOC_REC" integer
);

-- ---------------------------------------------------------------------
-- CLIENTES_EQUIP  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CLIENTES_EQUIP" CASCADE;
CREATE TABLE "CLIENTES_EQUIP" (
    "CODCLI" integer NOT NULL,
    "COD_EQUIP" varchar(30) NOT NULL,
    "DAT_CAD" date
);

-- ---------------------------------------------------------------------
-- CODICAO_PAGTO  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CODICAO_PAGTO" CASCADE;
CREATE TABLE "CODICAO_PAGTO" (
    "CODPAGTO" integer NOT NULL,
    "NOMPAGTO" varchar(40),
    "DESCONTO" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- CONTA_BANCARIA  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTA_BANCARIA" CASCADE;
CREATE TABLE "CONTA_BANCARIA" (
    "CODBANCO" varchar(10) NOT NULL,
    "AGENCIA" varchar(10) NOT NULL,
    "CONTA" varchar(20) NOT NULL,
    "TIT_CONTA" varchar(80),
    "VALOR_LIMITE" numeric(18,4),
    "VALOR_REAL" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- CONTAS_PAGAR_01  (33 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_PAGAR_01" CASCADE;
CREATE TABLE "CONTAS_PAGAR_01" (
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATAEMI" date,
    "NUMDOC" varchar(20),
    "TIPO" integer,
    "FORMA_REC" integer,
    "DOC_REC" integer,
    "DATALANC" date,
    "DATAVENC" date,
    "RET_CAIXA" varchar(1),
    "DAT_CAIXA" date,
    "VALOR" numeric(18,4),
    "BANCO" varchar(10),
    "AGENCIA" varchar(25),
    "CONTA" varchar(20),
    "FORNECEDOR" integer,
    "PORTADOR" integer,
    "NUMPARC" integer,
    "POSICAO" integer,
    "HISTORICO" text,
    "COD_CC" integer,
    "SALDO_CAIXA" numeric(18,4),
    "SALDO_CONTA" numeric(18,4),
    "SELEC_FATURAMENTO" varchar(1),
    "FATURADO" varchar(1),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_BAIXA" varchar(50),
    "DATA_BAIXA" date,
    "USUARIO_CANCELAMENTO" varchar(50),
    "DATA_CANCELAMENTO" date
);

-- ---------------------------------------------------------------------
-- CONTAS_PAGAR_02  (19 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_PAGAR_02" CASCADE;
CREATE TABLE "CONTAS_PAGAR_02" (
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATA_PAGTO" date,
    "MULTA" numeric(18,4),
    "JUROS" numeric(18,4),
    "DESCONTO" numeric(18,4),
    "VALOR_ATUALIZADO" numeric(18,4),
    "VALOR_RECEBIDO" numeric(18,4),
    "SALDO_CAIXA" numeric(18,4),
    "SALDO_CONTA" numeric(18,4),
    "COD_CONTA" integer,
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_BAIXA" varchar(50),
    "DATA_BAIXA" date,
    "USUARIO_CANCELAMENTO" varchar(50),
    "DATA_CANCELAMENTO" date
);

-- ---------------------------------------------------------------------
-- CONTAS_PAGAR_LOG_01  (56 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_PAGAR_LOG_01" CASCADE;
CREATE TABLE "CONTAS_PAGAR_LOG_01" (
    "ID" integer,
    "DATA" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATAEMI" date,
    "DATAEMI_OLD" date,
    "NUMDOC" varchar(20),
    "NUMDOC_OLD" varchar(20),
    "FORMA_REC" integer,
    "FORMA_REC_OLD" integer,
    "DOC_REC" integer,
    "DOC_REC_OLD" integer,
    "DATALANC" date,
    "DATALANC_OLD" date,
    "DATAVENC" date,
    "DATAVENC_OLD" date,
    "RET_CAIXA" varchar(1),
    "RET_CAIXA_OLD" varchar(1),
    "VALOR" numeric(18,4),
    "VALOR_OLD" numeric(18,4),
    "BANCO" varchar(10),
    "BANCO_OLD" varchar(10),
    "AGENCIA" varchar(25),
    "AGENCIA_OLD" varchar(25),
    "CONTA" varchar(20),
    "CONTA_OLD" varchar(20),
    "FORNECEDOR" integer,
    "FORNECEDOR_OLD" integer,
    "PORTADOR" integer,
    "PORTADOR_OLD" integer,
    "NUMPARC" integer,
    "NUMPARC_OLD" integer,
    "POSICAO" integer,
    "POSICAO_OLD" integer,
    "HISTORICO" text,
    "HISTORICO_OLD" text,
    "COD_CC" integer,
    "COD_CC_OLD" integer,
    "USUARIO_CADASTRO" varchar(50),
    "USUARIO_CADASTRO_OLD" varchar(50),
    "DATA_CADASTRO" date,
    "DATA_CADASTRO_OLD" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO_OLD" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "DATA_ULTIMA_ALTERACAO_OLD" date,
    "USUARIO_BAIXA" varchar(50),
    "USUARIO_BAIXA_OLD" varchar(50),
    "DATA_BAIXA" date,
    "DATA_BAIXA_OLD" date,
    "USUARIO_CANCELAMENTO" varchar(50),
    "USUARIO_CANCELAMENTO_OLD" varchar(50),
    "DATA_CANCELAMENTO" date,
    "DATA_CANCELAMENTO_OLD" date
);

-- ---------------------------------------------------------------------
-- CONTAS_PAGAR_LOG_02  (19 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_PAGAR_LOG_02" CASCADE;
CREATE TABLE "CONTAS_PAGAR_LOG_02" (
    "ID" integer,
    "DATA" date,
    "USUARIO" varchar(50),
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATA_PAGTO" date,
    "MULTA" numeric(18,4),
    "JUROS" numeric(18,4),
    "DESCONTO" numeric(18,4),
    "VALOR_ATUALIZADO" numeric(18,4),
    "VALOR_RECEBIDO" numeric(18,4),
    "SALDO_CAIXA" numeric(18,4),
    "SALDO_CONTA" numeric(18,4),
    "COD_CONTA" integer,
    "USUARIO_BAIXA" varchar(50),
    "DATA_BAIXA" date,
    "USUARIO_CANCELAMENTO" varchar(50),
    "DATA_CANCELAMENTO" date,
    "TIPO_DE_ACAO" varchar(1)
);

-- ---------------------------------------------------------------------
-- CONTAS_RECEBER_01  (42 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_RECEBER_01" CASCADE;
CREATE TABLE "CONTAS_RECEBER_01" (
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATAEMI" date,
    "NUM_OS" varchar(16),
    "NUMDOC" varchar(20),
    "TIPO" integer,
    "FORMA_REC" integer,
    "DOC_REC" integer,
    "QTDIAS" integer,
    "DATAVENC" date,
    "VALOR" numeric(18,4),
    "DESC_ESP" numeric(18,4),
    "VDESP" numeric(18,4),
    "ALIQ_ISSQN" numeric(18,4),
    "VISS" numeric(18,4),
    "VLIQ" numeric(18,4),
    "BANCO" varchar(50),
    "AGENCIA" varchar(25),
    "CONTA" varchar(20),
    "CLIENTE" integer,
    "PORTADOR" integer,
    "NUMPARC" integer,
    "POSICAO" integer,
    "HISTORICO" text,
    "COD_CC" integer,
    "RET_CAIXA" varchar(1),
    "SELEC_FATURAMENTO" varchar(1),
    "FATURADO" varchar(1),
    "NUM_NOTA_FISCAL" varchar(30),
    "FECHAFIN" varchar(1),
    "BAIXA_CONTA" varchar(1),
    "ARQUIVADA" varchar(1),
    "TOT_ARQ" integer,
    "CLIENTE_NOTA" integer,
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_BAIXA" varchar(50),
    "DATA_BAIXA" date,
    "USUARIO_CANCELAMENTO" varchar(50),
    "DATA_CANCELAMENTO" date
);

-- ---------------------------------------------------------------------
-- CONTAS_RECEBER_02  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_RECEBER_02" CASCADE;
CREATE TABLE "CONTAS_RECEBER_02" (
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATA_PAGTO" date,
    "MULTA" numeric(18,4),
    "JUROS" numeric(18,4),
    "DESCONTO" numeric(18,4),
    "VALOR_ATUALIZADO" numeric(18,4),
    "VALOR_RECEBIDO" numeric(18,4),
    "ARQUIVADA" varchar(1)
);

-- ---------------------------------------------------------------------
-- CONTAS_RECEBER_03  (31 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_RECEBER_03" CASCADE;
CREATE TABLE "CONTAS_RECEBER_03" (
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATAEMI" date,
    "NUM_OS" varchar(16),
    "NUMDOC" varchar(20),
    "TIPO" integer,
    "QTDIAS" integer,
    "DATAVENC" date,
    "VALOR" numeric(18,4),
    "DESC_ESP" numeric(18,4),
    "VDESP" numeric(18,4),
    "ALIQ_ISSQN" numeric(18,4),
    "VISS" numeric(18,4),
    "VLIQ" numeric(18,4),
    "BANCO" varchar(50),
    "AGENCIA" varchar(25),
    "CONTA" varchar(20),
    "CLIENTE" integer,
    "PORTADOR" integer,
    "NUMPARC" integer,
    "POSICAO" integer,
    "HISTORICO" text,
    "COD_CC" integer,
    "RET_CAIXA" varchar(1),
    "SELEC_FATURAMENTO" varchar(1),
    "FATURADO" varchar(1),
    "NUM_NOTA_FISCAL" varchar(30),
    "FECHAFIN" varchar(1),
    "BAIXA_CONTA" varchar(1),
    "ARQUIVADA" varchar(1),
    "CLIENTE_NOTA" integer
);

-- ---------------------------------------------------------------------
-- CONTAS_RECEBER_04  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_RECEBER_04" CASCADE;
CREATE TABLE "CONTAS_RECEBER_04" (
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATA_PAGTO" date,
    "MULTA" numeric(18,4),
    "JUROS" numeric(18,4),
    "DESCONTO" numeric(18,4),
    "VALOR_ATUALIZADO" numeric(18,4),
    "VALOR_RECEBIDO" numeric(18,4),
    "ARQUIVADA" varchar(1)
);

-- ---------------------------------------------------------------------
-- CONTAS_RECEBER_LOG_01  (78 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_RECEBER_LOG_01" CASCADE;
CREATE TABLE "CONTAS_RECEBER_LOG_01" (
    "ID" integer,
    "DATA" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATAEMI" date,
    "NUM_OS" varchar(16),
    "NUMDOC" varchar(20),
    "TIPO" integer,
    "FORMA_REC" integer,
    "DOC_REC" integer,
    "QTDIAS" integer,
    "DATAVENC" date,
    "VALOR" numeric(18,4),
    "DESC_ESP" numeric(18,4),
    "VDESP" numeric(18,4),
    "ALIQ_ISSQN" numeric(18,4),
    "VISS" numeric(18,4),
    "VLIQ" numeric(18,4),
    "BANCO" varchar(50),
    "AGENCIA" varchar(25),
    "CONTA" varchar(20),
    "CLIENTE" integer,
    "PORTADOR" integer,
    "NUMPARC" integer,
    "POSICAO" integer,
    "HISTORICO" text,
    "COD_CC" integer,
    "RET_CAIXA" varchar(1),
    "SELEC_FATURAMENTO" varchar(1),
    "FATURADO" varchar(1),
    "NUM_NOTA_FISCAL" varchar(30),
    "FECHAFIN" varchar(1),
    "BAIXA_CONTA" varchar(1),
    "ARQUIVADA" varchar(1),
    "TOT_ARQ" integer,
    "CLIENTE_NOTA" integer,
    "DATAEMI_OLD" date,
    "NUM_OS_OLD" varchar(16),
    "NUMDOC_OLD" varchar(20),
    "TIPO_OLD" varchar(255),
    "FORMA_REC_OLD" varchar(255),
    "DOC_REC_OLD" varchar(255),
    "QTDIAS_OLD" varchar(255),
    "DATAVENC_OLD" date,
    "VALOR_OLD" varchar(255),
    "DESC_ESP_OLD" varchar(255),
    "VDESP_OLD" varchar(255),
    "ALIQ_ISSQN_OLD" varchar(255),
    "VISS_OLD" varchar(255),
    "VLIQ_OLD" varchar(255),
    "BANCO_OLD" varchar(50),
    "AGENCIA_OLD" varchar(25),
    "CONTA_OLD" varchar(20),
    "CLIENTE_OLD" varchar(255),
    "PORTADOR_OLD" varchar(255),
    "NUMPARC_OLD" varchar(255),
    "POSICAO_OLD" varchar(255),
    "HISTORICO_OLD" text,
    "COD_CC_OLD" varchar(255),
    "RET_CAIXA_OLD" varchar(1),
    "SELEC_FATURAMENTO_OLD" varchar(1),
    "FATURADO_OLD" varchar(1),
    "NUM_NOTA_FISCAL_OLD" varchar(30),
    "FECHAFIN_OLD" varchar(1),
    "BAIXA_CONTA_OLD" varchar(1),
    "ARQUIVADA_OLD" varchar(1),
    "TOT_ARQ_OLD" varchar(255),
    "CLIENTE_NOTA_OLD" varchar(255),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_BAIXA" varchar(50),
    "DATA_BAIXA" date,
    "USUARIO_CANCELAMENTO" varchar(50),
    "DATA_CANCELAMENTO" date
);

-- ---------------------------------------------------------------------
-- CONTAS_RECEBER_LOG_02  (28 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTAS_RECEBER_LOG_02" CASCADE;
CREATE TABLE "CONTAS_RECEBER_LOG_02" (
    "ID" integer,
    "DATA" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "CODIGO" integer NOT NULL,
    "PARCELA" integer NOT NULL,
    "DATA_PAGTO" date,
    "MULTA" numeric(18,4),
    "JUROS" numeric(18,4),
    "DESCONTO" numeric(18,4),
    "VALOR_ATUALIZADO" numeric(18,4),
    "VALOR_RECEBIDO" numeric(18,4),
    "ARQUIVADA" varchar(1),
    "DATA_PAGTO_OLD" date,
    "MULTA_OLD" numeric(18,4),
    "JUROS_OLD" numeric(18,4),
    "DESCONTO_OLD" numeric(18,4),
    "VALOR_ATUALIZADO_OLD" numeric(18,4),
    "VALOR_RECEBIDO_OLD" numeric(18,4),
    "ARQUIVADA_OLD" varchar(1),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_BAIXA" varchar(50),
    "DATA_BAIXA" date,
    "USUARIO_CANCELAMENTO" varchar(50),
    "DATA_CANCELAMENTO" date
);

-- ---------------------------------------------------------------------
-- CONTRATO_01  (10 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CONTRATO_01" CASCADE;
CREATE TABLE "CONTRATO_01" (
    "NUM_CONTRATO" integer NOT NULL,
    "TIPO_CONTRATO" integer,
    "NOME_CONTRATO" varchar(100),
    "DATA_ABERTURA" date,
    "VALOR_CHAMADO" numeric(18,4),
    "VALOR_MENSAL" numeric(18,4),
    "TEMPO_ATENDIMENTO" numeric(18,4),
    "QTDE_ATEND_COBERTO" integer,
    "VALOR_EXCESSO" numeric(18,4),
    "CONTRATO_ATIVO" integer
);

-- ---------------------------------------------------------------------
-- Cópia de MOV_BANCARIA_02  (13 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "Cópia de MOV_BANCARIA_02" CASCADE;
CREATE TABLE "Cópia de MOV_BANCARIA_02" (
    "NUM_MOV" integer,
    "DATA" date NOT NULL,
    "CODBANCO" varchar(10),
    "AGENCIA" varchar(25),
    "CONTA" varchar(20),
    "TIPO" varchar(1),
    "VALOR" numeric(18,4),
    "HISTORICO" text,
    "COD_CP" integer,
    "PARCELA_CP" integer,
    "COD_CR" integer,
    "PARCELA_CR" integer,
    "SALDO_ATUAL" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- COTACAO_PRECOS_01  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "COTACAO_PRECOS_01" CASCADE;
CREATE TABLE "COTACAO_PRECOS_01" (
    "COD_COTACAO" integer,
    "DATA_COTACAO" date,
    "NUM_OS" varchar(16),
    "SOLICITANTE" integer,
    "FUNC_COTOU" integer,
    "OBSERVACAO" text
);

-- ---------------------------------------------------------------------
-- COTACAO_PRECOS_02  (11 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "COTACAO_PRECOS_02" CASCADE;
CREATE TABLE "COTACAO_PRECOS_02" (
    "COD_COTACAO" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "DESPROD" varchar(100),
    "QTDE" numeric(18,4),
    "FORNEC_01" integer,
    "PRECO_01" numeric(18,4),
    "FORNEC_02" integer,
    "PRECO_02" numeric(18,4),
    "FORNEC_03" integer,
    "PRECO_03" numeric(18,4),
    "FORNEC_ESCOLHIDO" integer
);

-- ---------------------------------------------------------------------
-- CP_01  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "CP_01" CASCADE;
CREATE TABLE "CP_01" (
    "CODPAGTO" integer NOT NULL,
    "NOMPAGTO" varchar(40)
);

-- ---------------------------------------------------------------------
-- DEPADM_01  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "DEPADM_01" CASCADE;
CREATE TABLE "DEPADM_01" (
    "COD_DEPADM" numeric(18,4),
    "NOM_DEPADM" varchar(255)
);

-- ---------------------------------------------------------------------
-- DEPENDENTES  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "DEPENDENTES" CASCADE;
CREATE TABLE "DEPENDENTES" (
    "CODIFUNC" integer NOT NULL,
    "CODIDEPE" integer NOT NULL,
    "NOME" varchar(50),
    "DTNAS" date,
    "GRAU" integer
);

-- ---------------------------------------------------------------------
-- DESPESAS_FIXAS  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "DESPESAS_FIXAS" CASCADE;
CREATE TABLE "DESPESAS_FIXAS" (
    "CODDESPESA" integer NOT NULL,
    "NOMEDESPESA" varchar(80)
);

-- ---------------------------------------------------------------------
-- ENT_PROD_VENDAS_01  (13 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ENT_PROD_VENDAS_01" CASCADE;
CREATE TABLE "ENT_PROD_VENDAS_01" (
    "NUMREQ" integer NOT NULL,
    "DATA" date,
    "HORA" date,
    "SETOR" integer,
    "FUNC" integer,
    "COD_FORN" integer,
    "SOLIC_COMPRA" integer,
    "OBSERV_GERAL" text,
    "REPOR_ESTOQUE" varchar(1),
    "REPOR_VENDA" varchar(1),
    "ATENDER_PROP_APROV" varchar(1),
    "NUM_PROPOSTA" integer,
    "NUM_NF_ENTRADA" varchar(15)
);

-- ---------------------------------------------------------------------
-- ENT_PROD_VENDAS_02  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ENT_PROD_VENDAS_02" CASCADE;
CREATE TABLE "ENT_PROD_VENDAS_02" (
    "NUMREQ" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "DES_PROD" varchar(100),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "OBS_PROD" varchar(80),
    "VAL_VENDA" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ENTRADA_PROD_USADO_01  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ENTRADA_PROD_USADO_01" CASCADE;
CREATE TABLE "ENTRADA_PROD_USADO_01" (
    "NUMREQ" integer NOT NULL,
    "DATA" date,
    "FUNC" integer,
    "ORIGEM" integer,
    "NUM_OS" varchar(16),
    "FECHAMENTO" varchar(1),
    "OBSERV_GERAL" text
);

-- ---------------------------------------------------------------------
-- ENTRADA_PROD_USADO_02  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ENTRADA_PROD_USADO_02" CASCADE;
CREATE TABLE "ENTRADA_PROD_USADO_02" (
    "NUMREQ" integer NOT NULL,
    "ITEM" integer NOT NULL,
    "COD_PROD" integer,
    "DES_PROD" varchar(100),
    "QTDE" integer,
    "OBS_PROD" varchar(80)
);

-- ---------------------------------------------------------------------
-- ENTRADAPROD_01  (13 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ENTRADAPROD_01" CASCADE;
CREATE TABLE "ENTRADAPROD_01" (
    "NUMREQ" integer NOT NULL,
    "DATA" date,
    "HORA" date,
    "SETOR" integer,
    "FUNC" integer,
    "COD_FORN" integer,
    "SOLIC_COMPRA" integer,
    "OBSERV_GERAL" text,
    "REPOR_ESTOQUE" varchar(1),
    "REPOR_VENDA" varchar(1),
    "ATENDER_PROP_APROV" varchar(1),
    "NUM_PROPOSTA" integer,
    "FECHAMENTO" varchar(1)
);

-- ---------------------------------------------------------------------
-- ENTRADAPROD_02  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ENTRADAPROD_02" CASCADE;
CREATE TABLE "ENTRADAPROD_02" (
    "NUMREQ" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "DES_PROD" varchar(100),
    "COD_FORN" integer,
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "OBS_PROD" varchar(80)
);

-- ---------------------------------------------------------------------
-- EQUIP_BACKUP_01  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "EQUIP_BACKUP_01" CASCADE;
CREATE TABLE "EQUIP_BACKUP_01" (
    "COD_EMP" integer,
    "COD_CLI" integer,
    "COD_EQUIP" varchar(30),
    "DATA_EMPRESTIMO" date,
    "DATA_DEVOLUCAO" date,
    "ACESSORIOS" text
);

-- ---------------------------------------------------------------------
-- EQUIPAMENTO  (14 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "EQUIPAMENTO" CASCADE;
CREATE TABLE "EQUIPAMENTO" (
    "NUS_EQUIP" varchar(30) NOT NULL,
    "TIPO_EQUIP" integer,
    "DES_EQUIP" varchar(50),
    "CONFIGURACAO" text,
    "FABRICANTE" integer,
    "NUM_NF" varchar(10),
    "DATA_NF" date,
    "DT_FIM_GARANTIA" date,
    "ULT_OS" integer,
    "DT_ULT_OS" date,
    "DATA_EMPRESTIMO" date,
    "DATA_DEVOLUCAO" date,
    "NUM_OS_EMPRESTIMO" varchar(16),
    "NUMPAT" varchar(25)
);

-- ---------------------------------------------------------------------
-- EQUIPAMENTO_BACKUP_01  (14 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "EQUIPAMENTO_BACKUP_01" CASCADE;
CREATE TABLE "EQUIPAMENTO_BACKUP_01" (
    "NUS_EQUIP" varchar(30) NOT NULL,
    "TIPO_EQUIP" integer,
    "DES_EQUIP" varchar(50),
    "CONFIGURACAO" text,
    "FABRICANTE" integer,
    "NUM_NF" varchar(10),
    "DATA_NF" date,
    "DT_FIM_GARANTIA" date,
    "ULT_OS" integer,
    "DT_ULT_OS" date,
    "DATA_EMPRESTIMO" date,
    "DATA_DEVOLUCAO" date,
    "NUM_OS_EMPRESTIMO" varchar(16),
    "NUMPAT" varchar(25)
);

-- ---------------------------------------------------------------------
-- ESCOLAS_01  (16 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ESCOLAS_01" CASCADE;
CREATE TABLE "ESCOLAS_01" (
    "COD_ESC" integer,
    "COD_MEC" varchar(255),
    "NOME" varchar(255),
    "COD_MUNIC" numeric(18,4),
    "NOME_MUNIC" varchar(255),
    "UF" varchar(255),
    "NOM_BAIRRO" varchar(255),
    "ENDER_ESCOLA" varchar(255),
    "NUM_ENDER" varchar(255),
    "CEP" varchar(255),
    "DDD" varchar(255),
    "FONE" varchar(255),
    "FAX" varchar(255),
    "EMAIL" varchar(255),
    "COD_DEPADM" numeric(18,4),
    "NOM_DEPADM" varchar(255)
);

-- ---------------------------------------------------------------------
-- ESTANTE_01  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ESTANTE_01" CASCADE;
CREATE TABLE "ESTANTE_01" (
    "COD_ESTANTE" integer,
    "NOME_ESTANTE" varchar(50)
);

-- ---------------------------------------------------------------------
-- ESTCIVIL  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ESTCIVIL" CASCADE;
CREATE TABLE "ESTCIVIL" (
    "CODIGO" integer NOT NULL,
    "DESCRICAO" varchar(20)
);

-- ---------------------------------------------------------------------
-- ESTOQUE_01  (10 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ESTOQUE_01" CASCADE;
CREATE TABLE "ESTOQUE_01" (
    "COD_FORN" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "COD_PROD_FORN" varchar(20) NOT NULL,
    "DESPROD" varchar(100),
    "QTDE_01" numeric(18,4),
    "QTDE_02" numeric(18,4),
    "QTDE_03" numeric(18,4),
    "QTDE_04" numeric(18,4),
    "QTDE_05" numeric(18,4),
    "QTDE_06" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ESTOQUE_02  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ESTOQUE_02" CASCADE;
CREATE TABLE "ESTOQUE_02" (
    "COD_PROD" integer NOT NULL,
    "DESPROD" varchar(100),
    "QTDE_01" numeric(18,4),
    "QTDE_02" numeric(18,4),
    "QTDE_03" numeric(18,4),
    "QTDE_04" numeric(18,4),
    "QTDE_05" numeric(18,4),
    "QTDE_06" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ESTOQUE_03  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ESTOQUE_03" CASCADE;
CREATE TABLE "ESTOQUE_03" (
    "COD_FORN" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "COD_PROD_FORN" varchar(20) NOT NULL,
    "DESPROD" varchar(100),
    "QTDE_01" numeric(18,4),
    "QTDE_02" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ESTOQUE_USADO  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ESTOQUE_USADO" CASCADE;
CREATE TABLE "ESTOQUE_USADO" (
    "COD_PROD" integer NOT NULL,
    "DESPROD" varchar(100),
    "QTDE_01" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ESTOQUE_VENDAS  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ESTOQUE_VENDAS" CASCADE;
CREATE TABLE "ESTOQUE_VENDAS" (
    "COD_PROD" integer NOT NULL,
    "QTDE_01" numeric(18,4),
    "DES_PROD" varchar(100)
);

-- ---------------------------------------------------------------------
-- FERIAS  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FERIAS" CASCADE;
CREATE TABLE "FERIAS" (
    "CODIFUNC" integer NOT NULL,
    "PAQUINI" date NOT NULL,
    "PAQUFIM" date NOT NULL,
    "PCONINI" date,
    "PCONFIM" date,
    "INICIO" date,
    "FIM" date,
    "DIAS" integer
);

-- ---------------------------------------------------------------------
-- FERRAMENTAS_01  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FERRAMENTAS_01" CASCADE;
CREATE TABLE "FERRAMENTAS_01" (
    "COD_MAT" integer NOT NULL,
    "NOM_MAT" varchar(100),
    "UNI_MAT" varchar(15),
    "GRU_MAT" varchar(3),
    "SUB_MAT" varchar(3)
);

-- ---------------------------------------------------------------------
-- FINALIDADE  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FINALIDADE" CASCADE;
CREATE TABLE "FINALIDADE" (
    "CODFINAL" integer NOT NULL,
    "NOMFINAL" varchar(40)
);

-- ---------------------------------------------------------------------
-- FORMA_RECEBIMENTO_01  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FORMA_RECEBIMENTO_01" CASCADE;
CREATE TABLE "FORMA_RECEBIMENTO_01" (
    "COD_RECEB" integer NOT NULL,
    "NOM_RECEB" varchar(40)
);

-- ---------------------------------------------------------------------
-- FORMA_RECEBIMENTO_02  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FORMA_RECEBIMENTO_02" CASCADE;
CREATE TABLE "FORMA_RECEBIMENTO_02" (
    "COD_FORM_REC" integer NOT NULL,
    "COD_DOC_REC" integer NOT NULL,
    "NOM_DOC_REC" varchar(40)
);

-- ---------------------------------------------------------------------
-- FORNECEDOR  (17 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FORNECEDOR" CASCADE;
CREATE TABLE "FORNECEDOR" (
    "CODFOR" integer NOT NULL,
    "NOMFOR" varchar(100),
    "NOMFANT" varchar(100),
    "INS_EST" varchar(20),
    "CGC_FOR" varchar(20),
    "ENDFOR" varchar(60),
    "NUMERO" varchar(10),
    "COMPLEMENTO" varchar(50),
    "BAIRRO" varchar(50),
    "CIDADE" varchar(20),
    "CEP" varchar(10),
    "UF" varchar(2),
    "FONE" varchar(15),
    "FAX" varchar(15),
    "EMAIL" varchar(50),
    "CONTATO" varchar(25),
    "PONTO_REF" text
);

-- ---------------------------------------------------------------------
-- FORNECEDOR_PRAZOS  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FORNECEDOR_PRAZOS" CASCADE;
CREATE TABLE "FORNECEDOR_PRAZOS" (
    "CODFOR" integer NOT NULL,
    "COD_PRAZO" integer,
    "FORMA_PAGTO" varchar(50),
    "PRAZO" varchar(50)
);

-- ---------------------------------------------------------------------
-- FORNEPAGAR  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FORNEPAGAR" CASCADE;
CREATE TABLE "FORNEPAGAR" (
    "CODFOR" integer NOT NULL,
    "NOMFOR" varchar(80)
);

-- ---------------------------------------------------------------------
-- FUNCIONARIOS_01  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FUNCIONARIOS_01" CASCADE;
CREATE TABLE "FUNCIONARIOS_01" (
    "CODIFUNC" integer NOT NULL,
    "NOMEFUNC" varchar(80),
    "NOMABREV" varchar(20),
    "ATIVO" varchar(1)
);

-- ---------------------------------------------------------------------
-- FUNCIONARIOS_02  (19 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FUNCIONARIOS_02" CASCADE;
CREATE TABLE "FUNCIONARIOS_02" (
    "CODIFUNC" integer NOT NULL,
    "ENDE" varchar(70),
    "NUMERO" varchar(10),
    "BAIRRO" varchar(35),
    "CEP" varchar(10),
    "FONE" varchar(12),
    "CELULAR" varchar(12),
    "RG" varchar(25),
    "CPF" varchar(16),
    "PIS" varchar(20),
    "NUM_C_PROF" varchar(15),
    "SERIE_C_PROF" varchar(10),
    "RESERVISTA" varchar(30),
    "TITULO" varchar(20),
    "BANCO" varchar(70),
    "AGENCIA" varchar(20),
    "CONTA" varchar(20),
    "OBSERVACAO" text,
    "Caminho_Imagem" varchar(255)
);

-- ---------------------------------------------------------------------
-- FUNCIONARIOS_03  (16 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FUNCIONARIOS_03" CASCADE;
CREATE TABLE "FUNCIONARIOS_03" (
    "CODIFUNC" integer NOT NULL,
    "FUNCAO" integer,
    "SETOR" integer,
    "CATEGORIA" varchar(2),
    "ADMISSAO" date,
    "ASSICART" date,
    "SALARIO_CARTEIRA" numeric(18,4),
    "GRATIFICACAO" numeric(18,4),
    "QTDE_PASSAGEM" integer,
    "PASSCARD" numeric(18,4),
    "PLANO_SAUDE" integer,
    "DATA_ADESAO" date,
    "CANCELAMENTO_PLANO" date,
    "EMPREGO_ANT" varchar(70),
    "DEMISSAO" date,
    "MOTIVO" text
);

-- ---------------------------------------------------------------------
-- FUNCIONARIOS_04  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FUNCIONARIOS_04" CASCADE;
CREATE TABLE "FUNCIONARIOS_04" (
    "CODIFUNC" integer NOT NULL,
    "DATA_NASC" date,
    "ESTADO_CIVEL" integer,
    "CONJUGE" varchar(80),
    "PAI" varchar(70),
    "MAE" varchar(70),
    "QTDE_FILHOS" integer
);

-- ---------------------------------------------------------------------
-- FUNCIONARIOS_05  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FUNCIONARIOS_05" CASCADE;
CREATE TABLE "FUNCIONARIOS_05" (
    "CODIFUNC" integer NOT NULL,
    "CODBENEF" integer NOT NULL,
    "QTDE" integer,
    "DATA_ADESSAO" date
);

-- ---------------------------------------------------------------------
-- FUNCOES  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "FUNCOES" CASCADE;
CREATE TABLE "FUNCOES" (
    "CODFUNCAO" integer NOT NULL,
    "NOMEFUNCAO" varchar(70),
    "VALOR" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- GRAUPAR  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "GRAUPAR" CASCADE;
CREATE TABLE "GRAUPAR" (
    "CODATEN" integer NOT NULL,
    "DESATEN" varchar(20)
);

-- ---------------------------------------------------------------------
-- GRUPO  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "GRUPO" CASCADE;
CREATE TABLE "GRUPO" (
    "COD_GRUPO" varchar(3) NOT NULL,
    "NOM_GRUPO" varchar(40),
    "PERC_VENDA" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- KIT_FERRAMENTA_01  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "KIT_FERRAMENTA_01" CASCADE;
CREATE TABLE "KIT_FERRAMENTA_01" (
    "COD_KIT" integer NOT NULL,
    "DES_KIT" varchar(100),
    "OBSERVACAO" text
);

-- ---------------------------------------------------------------------
-- KIT_FERRAMENTA_02  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "KIT_FERRAMENTA_02" CASCADE;
CREATE TABLE "KIT_FERRAMENTA_02" (
    "COD_KIT" integer NOT NULL,
    "COD_MAT" integer NOT NULL,
    "QTDE" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- KIT_FERRAMENTA_03  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "KIT_FERRAMENTA_03" CASCADE;
CREATE TABLE "KIT_FERRAMENTA_03" (
    "COD_KIT" integer NOT NULL,
    "COD_MAT" integer NOT NULL,
    "QTDE" numeric(18,4),
    "DATA_SUBST" date,
    "OBSERV" text
);

-- ---------------------------------------------------------------------
-- KIT_FERRAMENTA_04  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "KIT_FERRAMENTA_04" CASCADE;
CREATE TABLE "KIT_FERRAMENTA_04" (
    "COD_KIT" integer NOT NULL,
    "NUM_ORDEM" integer,
    "CODIFUNC" integer,
    "DATA_ENTREGA" date,
    "DATA_DEVOLUCAO" date
);

-- ---------------------------------------------------------------------
-- MAPA_ROTA_01  (11 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MAPA_ROTA_01" CASCADE;
CREATE TABLE "MAPA_ROTA_01" (
    "DATA_ROTA" date NOT NULL,
    "COD_ROTA" integer,
    "TECNICO" integer,
    "NUM_ORDEM" integer,
    "HORA_SAIDA_EMPRESA" date,
    "HORA_CHEGADA_CLIENTE" date,
    "HORA_SAIDA_CLIENTE" date,
    "SERVICO_EXECUTADO" text,
    "CLIENTE" integer,
    "NUM_OS" varchar(16),
    "DEVOLVIDO" varchar(1)
);

-- ---------------------------------------------------------------------
-- MATERIAL_01  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MATERIAL_01" CASCADE;
CREATE TABLE "MATERIAL_01" (
    "COD_MAT" integer NOT NULL,
    "NOM_MAT" varchar(100),
    "UNI_MAT" varchar(15),
    "GRU_MAT" varchar(3),
    "SUB_MAT" varchar(3),
    "QTD_MAT_EXISTENTE" integer,
    "QTD_MAT_SAIU" integer
);

-- ---------------------------------------------------------------------
-- MATERIAL_02  (13 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MATERIAL_02" CASCADE;
CREATE TABLE "MATERIAL_02" (
    "COD_SAIDA" integer NOT NULL,
    "ITEM" integer NOT NULL,
    "COD_MAT" integer,
    "ALMOXARIFE_ENTREGOU" integer,
    "TECNICO_RECEBEU" integer,
    "DATA_ENTREGA" date,
    "QTDE_ENTREGUE" integer,
    "TECNICO_DEVOLVEU" integer,
    "DATA_DEVOLUCAO" date,
    "QTDE_DEVOLVIDA" integer,
    "ALMOXARIFE_RECEBEU" integer,
    "OBSERVACAO" varchar(100),
    "PRAZO_DEV" date
);

-- ---------------------------------------------------------------------
-- MATERIAL_02A  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MATERIAL_02A" CASCADE;
CREATE TABLE "MATERIAL_02A" (
    "COD_SAIDA" integer NOT NULL,
    "NUM_OS" varchar(50),
    "BAIXA_AUTOMATICA" varchar(1)
);

-- ---------------------------------------------------------------------
-- MENSAGEM_01  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MENSAGEM_01" CASCADE;
CREATE TABLE "MENSAGEM_01" (
    "COD_MENSAGEM" integer,
    "DAT_MENSAGEM" date,
    "HOR_MENSAGEM" date,
    "REMETENTE" integer,
    "DESTINATARIO" integer,
    "MENSAGEM" text
);

-- ---------------------------------------------------------------------
-- MENSAGEM_02  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MENSAGEM_02" CASCADE;
CREATE TABLE "MENSAGEM_02" (
    "COD_MENSAGEM" integer NOT NULL,
    "DAT_MENSAGEM" date,
    "HOR_MENSAGEM" date,
    "REMETENTE" integer,
    "DESTINATARIO" integer,
    "MENSAGEM" text
);

-- ---------------------------------------------------------------------
-- MES_ANO  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MES_ANO" CASCADE;
CREATE TABLE "MES_ANO" (
    "ID" integer,
    "ANO" integer,
    "MES" integer
);

-- ---------------------------------------------------------------------
-- MOV_BANCARIA_01  (16 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MOV_BANCARIA_01" CASCADE;
CREATE TABLE "MOV_BANCARIA_01" (
    "NUM_MOV" integer,
    "DATA" date NOT NULL,
    "CODBANCO" varchar(10) NOT NULL,
    "AGENCIA" varchar(25) NOT NULL,
    "CONTA" varchar(20) NOT NULL,
    "TIPO" varchar(1),
    "VALOR" numeric(18,4),
    "HISTORICO" text,
    "COD_CP" integer,
    "PARCELA_CP" integer,
    "COD_CR" integer,
    "PARCELA_CR" integer,
    "SALDO_ANTERIOR" numeric(18,4),
    "SALDO_ATUAL" numeric(18,4),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date
);

-- ---------------------------------------------------------------------
-- MOV_BANCARIA_02  (18 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MOV_BANCARIA_02" CASCADE;
CREATE TABLE "MOV_BANCARIA_02" (
    "NUM_MOV" integer,
    "DATA" date NOT NULL,
    "CODBANCO" varchar(10),
    "AGENCIA" varchar(25),
    "CONTA" varchar(20),
    "TIPO" varchar(1),
    "VALOR" numeric(18,4),
    "HISTORICO" text,
    "COD_CP" integer,
    "PARCELA_CP" integer,
    "COD_CR" integer,
    "PARCELA_CR" integer,
    "SALDO_ANTERIOR" numeric(18,4),
    "SALDO_ATUAL" numeric(18,4),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date
);

-- ---------------------------------------------------------------------
-- MOV_BANCARIA_LOG_01  (38 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MOV_BANCARIA_LOG_01" CASCADE;
CREATE TABLE "MOV_BANCARIA_LOG_01" (
    "ID" integer,
    "DATALT" date,
    "USUARIO" varchar(50),
    "NUMOV" integer,
    "DATA" date NOT NULL,
    "DATA_OLD" date NOT NULL,
    "CODBANCO" varchar(10) NOT NULL,
    "CODBANCO_OLD" varchar(10) NOT NULL,
    "AGENCIA" varchar(25) NOT NULL,
    "AGENCIA_OLD" varchar(25) NOT NULL,
    "CONTA" varchar(20) NOT NULL,
    "CONTA_OLD" varchar(20) NOT NULL,
    "TIPO" varchar(1),
    "TIPO_OLD" varchar(1),
    "VALOR" numeric(18,4),
    "VALOR_OLD" numeric(18,4),
    "HISTORICO" text,
    "HISTORICO_OLD" text,
    "COD_CP" integer,
    "COD_CP_OLD" integer,
    "PARCELA_CP" integer,
    "PARCELA_CP_OLD" integer,
    "COD_CR" integer,
    "COD_CR_OLD" integer,
    "PARCELA_CR" integer,
    "PARCELA_CR_OLD" integer,
    "SALDO_ANTERIOR" numeric(18,4),
    "SALDO_ANTERIOR_OLD" numeric(18,4),
    "SALDO_ATUAL" numeric(18,4),
    "SALDO_ATUAL_OLD" numeric(18,4),
    "USUARIO_CADASTRO" varchar(50),
    "USUARIO_CADASTRO_OLD" varchar(50),
    "DATA_CADASTRO" date,
    "DATA_CADASTRO_OLD" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO_OLD" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "DATA_ULTIMA_ALTERACAO_OLD" date
);

-- ---------------------------------------------------------------------
-- MOVICAIXA_LOG_01  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MOVICAIXA_LOG_01" CASCADE;
CREATE TABLE "MOVICAIXA_LOG_01" (
    "DATA" date NOT NULL,
    "DATANUM" numeric(18,4),
    "SALDOATUAL" numeric(18,4),
    "FECHADO" varchar(1),
    "CHEQUE" numeric(18,4),
    "DINHEIRO" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- MOVICAIXA_LOG_02  (31 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MOVICAIXA_LOG_02" CASCADE;
CREATE TABLE "MOVICAIXA_LOG_02" (
    "ID" integer,
    "DATAMOV" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "NUMOV" integer,
    "DATA" date,
    "DATANUM" numeric(18,4),
    "SETOR" integer,
    "SETOR_OLD" integer,
    "DOCUMENTO" varchar(10),
    "DOCUMENTO_OLD" varchar(10),
    "HISTÓRICO" text,
    "HISTÓRICO_OLD" text,
    "VALOR_ENTRADA" numeric(18,4),
    "VALOR_ENTRADA_OLD" numeric(18,4),
    "VALOR_SAIDA" numeric(18,4),
    "VALOR_SAIDA_OLD" numeric(18,4),
    "NUM_OS" varchar(16),
    "NUM_OS_OLD" varchar(16),
    "FORMA_REC" integer,
    "FORMA_REC_OLD" integer,
    "DOC_REC" integer,
    "DOC_REC_OLD" integer,
    "USUARIO_CADASTRO" varchar(50),
    "USUARIO_CADASTRO_OLD" varchar(50),
    "DATA_CADASTRO" date,
    "DATA_CADASTRO_OLD" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO_OLD" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "DATA_ULTIMA_ALTERACAO_OLD" date
);

-- ---------------------------------------------------------------------
-- MOVICAIXA1  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MOVICAIXA1" CASCADE;
CREATE TABLE "MOVICAIXA1" (
    "DATA" date NOT NULL,
    "DATANUM" numeric(18,4),
    "SALDOATUAL" numeric(18,4),
    "FECHADO" varchar(1),
    "CHEQUE" numeric(18,4),
    "DINHEIRO" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- MOVICAIXA2  (15 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MOVICAIXA2" CASCADE;
CREATE TABLE "MOVICAIXA2" (
    "DATA" date NOT NULL,
    "NUMOV" integer NOT NULL,
    "DATANUM" numeric(18,4),
    "SETOR" integer,
    "DOCUMENTO" varchar(10),
    "HISTÓRICO" text,
    "VALOR_ENTRADA" numeric(18,4),
    "VALOR_SAIDA" numeric(18,4),
    "NUM_OS" varchar(16),
    "FORMA_REC" integer,
    "DOC_REC" integer,
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date
);

-- ---------------------------------------------------------------------
-- MOVICAIXA3  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MOVICAIXA3" CASCADE;
CREATE TABLE "MOVICAIXA3" (
    "DATA" date NOT NULL,
    "DATNUM" numeric(18,4),
    "SALDOANTERIOR" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- MSysAccessStorage  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MSysAccessStorage" CASCADE;
CREATE TABLE "MSysAccessStorage" (
    "DateCreate" date,
    "DateUpdate" date,
    "Id" integer,
    "Lv" bytea,
    "Name" varchar(128),
    "ParentId" integer,
    "Type" integer
);

-- ---------------------------------------------------------------------
-- MSysAccessXML  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MSysAccessXML" CASCADE;
CREATE TABLE "MSysAccessXML" (
    "Id" integer,
    "LValue" bytea,
    "ObjectGuid" uuid,
    "ObjectName" varchar(65),
    "Property" varchar(65),
    "Value" varchar(255)
);

-- ---------------------------------------------------------------------
-- MSysIMEXColumns  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MSysIMEXColumns" CASCADE;
CREATE TABLE "MSysIMEXColumns" (
    "Attributes" integer,
    "DataType" integer,
    "FieldName" varchar(64),
    "IndexType" smallint,
    "SkipColumn" boolean,
    "SpecID" integer,
    "Start" integer,
    "Width" integer
);

-- ---------------------------------------------------------------------
-- MSysIMEXSpecs  (13 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MSysIMEXSpecs" CASCADE;
CREATE TABLE "MSysIMEXSpecs" (
    "DateDelim" varchar(2),
    "DateFourDigitYear" boolean,
    "DateLeadingZeros" boolean,
    "DateOrder" integer,
    "DecimalPoint" varchar(2),
    "FieldSeparator" varchar(2),
    "FileType" integer,
    "SpecID" integer,
    "SpecName" varchar(64),
    "SpecType" smallint,
    "StartRow" integer,
    "TextDelim" varchar(2),
    "TimeDelim" varchar(2)
);

-- ---------------------------------------------------------------------
-- MSysNameMap  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MSysNameMap" CASCADE;
CREATE TABLE "MSysNameMap" (
    "GUID" uuid,
    "Id" integer,
    "Name" varchar(65),
    "NameMap" bytea,
    "Type" integer
);

-- ---------------------------------------------------------------------
-- MUNICIPIOS_01  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "MUNICIPIOS_01" CASCADE;
CREATE TABLE "MUNICIPIOS_01" (
    "COD_MUNIC" numeric(18,4),
    "NOME_MUNIC" varchar(255)
);

-- ---------------------------------------------------------------------
-- NAT_OPERACAO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "NAT_OPERACAO" CASCADE;
CREATE TABLE "NAT_OPERACAO" (
    "CODOPER" integer NOT NULL,
    "NOMOPER" varchar(40)
);

-- ---------------------------------------------------------------------
-- NF_CSF_01  (16 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "NF_CSF_01" CASCADE;
CREATE TABLE "NF_CSF_01" (
    "NF_CHEGADA" varchar(10),
    "DATA_CHEGADA" date,
    "QTDE_CHEGADA" integer,
    "COD_PROD" integer,
    "COD_PROD_FORN" varchar(25),
    "NOME_PRODUTO" varchar(100),
    "COD_FORN" integer,
    "NOME_FORN" varchar(100),
    "CAT_FORN" varchar(50),
    "NF_SAIDA" varchar(10),
    "DATA_SAIDA" date,
    "QTDE_SAIDA" integer,
    "NF_FATURA" varchar(10),
    "DATA_FATURA" date,
    "QTDE_FATURA" integer,
    "NUM_OC" integer
);

-- ---------------------------------------------------------------------
-- NF_ENTPROD_01  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "NF_ENTPROD_01" CASCADE;
CREATE TABLE "NF_ENTPROD_01" (
    "NUM_NF" varchar(10) NOT NULL,
    "DATA" date,
    "DATA_LANC" date,
    "FORNEC_PROD" integer,
    "COD_FORN" integer,
    "PCOD_FORN" integer,
    "NATOPER" integer,
    "OBSERV" text,
    "NUM_OS" varchar(16)
);

-- ---------------------------------------------------------------------
-- NF_ENTPROD_02  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "NF_ENTPROD_02" CASCADE;
CREATE TABLE "NF_ENTPROD_02" (
    "NUM_NF" varchar(10) NOT NULL,
    "COD_PROD" integer NOT NULL,
    "DES_PROD" varchar(100),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- NF_SERVICOS_B01  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "NF_SERVICOS_B01" CASCADE;
CREATE TABLE "NF_SERVICOS_B01" (
    "NUM_NF" varchar(10) NOT NULL,
    "COD_CLI" integer,
    "MES_REF_SERVICO" varchar(15),
    "DATA_PREST_SERV" date,
    "DATA_EMISSAO_NF" date,
    "NATOPER" integer
);

-- ---------------------------------------------------------------------
-- NF_SERVICOS_B02  (44 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "NF_SERVICOS_B02" CASCADE;
CREATE TABLE "NF_SERVICOS_B02" (
    "NUM_NF" varchar(10) NOT NULL,
    "QTDE_01" integer,
    "DESC_01" varchar(65),
    "DESC_01A" varchar(65),
    "DESC_01B" varchar(65),
    "VALUNIT_01" numeric(18,4),
    "VALTOT_01" numeric(18,4),
    "VALUNIT_01A" numeric(18,4),
    "VALTOT_01A" numeric(18,4),
    "VALUNIT_01B" numeric(18,4),
    "VALTOT_01B" numeric(18,4),
    "QTDE_02" integer,
    "DESC_02" varchar(65),
    "DESC_02A" varchar(65),
    "DESC_02B" varchar(65),
    "VALUNIT_02" numeric(18,4),
    "VALTOT_02" numeric(18,4),
    "VALUNIT_02A" numeric(18,4),
    "VALTOT_02A" numeric(18,4),
    "VALUNIT_02B" numeric(18,4),
    "VALTOT_02B" numeric(18,4),
    "QTDE_03" integer,
    "DESC_03" varchar(65),
    "DESC_03A" varchar(65),
    "DESC_03B" varchar(65),
    "VALUNIT_03" numeric(18,4),
    "VALTOT_03" numeric(18,4),
    "VALUNIT_03A" numeric(18,4),
    "VALTOT_03A" numeric(18,4),
    "VALUNIT_03B" numeric(18,4),
    "VALTOT_03B" numeric(18,4),
    "QTDE_04" integer,
    "DESC_04" varchar(65),
    "DESC_04A" varchar(65),
    "VALUNIT_04" numeric(18,4),
    "VALTOT_04" numeric(18,4),
    "VALUNIT_04A" numeric(18,4),
    "VALTOT_04A" numeric(18,4),
    "BASE_CALC_ISSQN" numeric(18,4),
    "ALIQUOTA_ISSQN" numeric(18,4),
    "VALOR_ISSQN" numeric(18,4),
    "VALOR_NF" numeric(18,4),
    "OBSERVACAO" text,
    "NUM_SELO" varchar(30)
);

-- ---------------------------------------------------------------------
-- NF_SERVICOS_B03  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "NF_SERVICOS_B03" CASCADE;
CREATE TABLE "NF_SERVICOS_B03" (
    "NUM_NF" varchar(10) NOT NULL,
    "BASE_CALC_ISSQN" numeric(18,4),
    "ALIQUOTA_ISSQN" numeric(18,4),
    "VALOR_ISSQN" numeric(18,4),
    "VALOR_NF" numeric(18,4),
    "OBSERVACAO" text,
    "NUM_SELO" varchar(30)
);

-- ---------------------------------------------------------------------
-- ORCAM_DIMEN_01  (22 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAM_DIMEN_01" CASCADE;
CREATE TABLE "ORCAM_DIMEN_01" (
    "NUM_PLA" integer NOT NULL,
    "DATA" date,
    "NUM_OS" varchar(16),
    "NUM_ORC" integer,
    "COD_CLI" integer,
    "CONTATO" varchar(30),
    "TECNICO" integer,
    "LOCAL_INST" text,
    "TIPO_CONTATO" varchar(15),
    "SETOR" varchar(30),
    "EMAIL" varchar(100),
    "QTDE_TECNICOS" integer,
    "QTD_COMERCIAIS" numeric(18,4),
    "QTD_NAO_COMERCIAIS" numeric(18,4),
    "SOLICITANTE" varchar(50),
    "USUARIO" varchar(50),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_FECHAMENTO" varchar(50),
    "DATA_FECHAMENTO" date
);

-- ---------------------------------------------------------------------
-- ORCAM_DIMEN_02  (15 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAM_DIMEN_02" CASCADE;
CREATE TABLE "ORCAM_DIMEN_02" (
    "NUM_PLA" integer NOT NULL,
    "NUMITEM" integer NOT NULL,
    "CODPROD" integer,
    "DESCRICAO" varchar(80),
    "DESPROD" varchar(100),
    "QTDE" integer,
    "VALOR_COMPRA" numeric(18,4),
    "VALOR_GASTO" numeric(18,4),
    "TOTAL_COMPRA" numeric(18,4),
    "VALOR_VENDA" numeric(18,4),
    "TOTAL_VENDA" numeric(18,4),
    "GRUPO" varchar(3),
    "SUB_GRUPO" varchar(3),
    "PG" varchar(3),
    "PSG" varchar(3)
);

-- ---------------------------------------------------------------------
-- ORCAM_DIMEN_03  (50 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAM_DIMEN_03" CASCADE;
CREATE TABLE "ORCAM_DIMEN_03" (
    "NUM_PLA" integer NOT NULL,
    "QTDE_TECNICOS" integer,
    "QTDE_DIAS" integer,
    "QUE_FUNCIONA" text,
    "QUER_FAZER" text,
    "DIFICULDADES" text,
    "MAT_PRECISA" text,
    "PROVIDENCIAR" text,
    "OBSERV" text,
    "ALTURA" numeric(18,4),
    "CUMPRIM_MURO" numeric(18,4),
    "LADO_DIREITO" numeric(18,4),
    "LADO_ESQUERDO" numeric(18,4),
    "FRENTE" numeric(18,4),
    "FUNDO" numeric(18,4),
    "REBOCADO" varchar(1),
    "CERCA_ARAME" varchar(1),
    "TELHADO" varchar(1),
    "ARVORE" varchar(1),
    "CINTA_CIMENTO" varchar(1),
    "PEGA_LADRAO" varchar(1),
    "PERC_MAT_FIXACAO" numeric(18,4),
    "MAT_FIXACAO" numeric(18,4),
    "PERC_MAO_OBRA" numeric(18,4),
    "MAO_OBRA" numeric(18,4),
    "PERC_NOTA_FISCAL" numeric(18,4),
    "NOTA" numeric(18,4),
    "PERC_ACRESCIMO_CUSTO" numeric(18,4),
    "ACRESCIMO_CUSTO" numeric(18,4),
    "SEGUNDA" integer,
    "HORA_SEG_01" boolean,
    "HORA_SEG_02" boolean,
    "TERCA" integer,
    "HORA_TER_01" boolean,
    "HORA_TER_02" boolean,
    "QUARTA" integer,
    "HORA_QUA_01" boolean,
    "HORA_QUA_02" boolean,
    "QUINTA" integer,
    "HORA_QUI_01" boolean,
    "HORA_QUI_02" boolean,
    "SEXTA" integer,
    "HORA_SEX_01" boolean,
    "HORA_SEX_02" boolean,
    "SABADO" integer,
    "HORA_SAB_01" boolean,
    "HORA_SAB_02" boolean,
    "DOMINGO" integer,
    "HORA_DOM_01" boolean,
    "HORA_DOM_02" boolean
);

-- ---------------------------------------------------------------------
-- ORCAM_DIMEN_03A  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAM_DIMEN_03A" CASCADE;
CREATE TABLE "ORCAM_DIMEN_03A" (
    "NUM_PLA" integer NOT NULL,
    "DIFICULDADES" text,
    "MAT_PRECISA" text,
    "OBSERV" text
);

-- ---------------------------------------------------------------------
-- ORCAM_DIMEN_04  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAM_DIMEN_04" CASCADE;
CREATE TABLE "ORCAM_DIMEN_04" (
    "NUM_PLA" integer NOT NULL,
    "CODPROD" integer NOT NULL,
    "CODFOR" integer NOT NULL,
    "DES_PROD" varchar(100),
    "DATA" date,
    "PRECO_UNIT" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ORCAM_DIMEN_05  (1 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAM_DIMEN_05" CASCADE;
CREATE TABLE "ORCAM_DIMEN_05" (
    "NUM_PLA" integer NOT NULL
);

-- ---------------------------------------------------------------------
-- ORCAM_DIMEN_06  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAM_DIMEN_06" CASCADE;
CREATE TABLE "ORCAM_DIMEN_06" (
    "NUM_PLA" integer NOT NULL,
    "NUMITEM" integer NOT NULL,
    "DESC_SERV" text,
    "CONCLUIDO" varchar(1)
);

-- ---------------------------------------------------------------------
-- ORCAM_DIMEN_07  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAM_DIMEN_07" CASCADE;
CREATE TABLE "ORCAM_DIMEN_07" (
    "NUM_PLA" integer NOT NULL,
    "NUMITEM" integer NOT NULL,
    "DESC_MAT" text,
    "QTDE" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ORCAM_DIMEN_LOG_01  (38 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAM_DIMEN_LOG_01" CASCADE;
CREATE TABLE "ORCAM_DIMEN_LOG_01" (
    "ID" integer,
    "DATAMOV" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "NUM_PLA" integer,
    "NUM_PLA_OLD" integer,
    "DATA" date,
    "DATA_OLD" date,
    "NUM_OS" varchar(16),
    "NUM_OS_OLD" varchar(16),
    "NUM_ORC" integer,
    "NUM_ORC_OLD" integer,
    "COD_CLI" integer,
    "COD_CLI_OLD" integer,
    "CONTATO" varchar(30),
    "CONTATO_OLD" varchar(30),
    "TECNICO" integer,
    "TECNICO_OLD" integer,
    "LOCAL_INST" text,
    "LOCAL_INST_OLD" text,
    "TIPO_CONTATO" varchar(15),
    "TIPO_CONTATO_OLD" varchar(15),
    "SETOR" varchar(30),
    "SETOR_OLD" varchar(30),
    "EMAIL" varchar(100),
    "EMAIL_OLD" varchar(100),
    "QTDE_TECNICOS" integer,
    "QTDE_TECNICOS_OLD" integer,
    "QTD_COMERCIAIS" integer,
    "QTD_COMERCIAIS_OLD" integer,
    "QTD_NAO_COMERCIAIS" integer,
    "QTD_NAO_COMERCIAIS_OLD" integer,
    "SOLICITANTE" varchar(50),
    "SOLICITANTE_OLD" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO_OLD" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "DATA_ULTIMA_ALTERACAO_OLD" date
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_01  (17 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_01" CASCADE;
CREATE TABLE "ORCAMENTO_01" (
    "NUM_ORC" integer NOT NULL,
    "DATA" date,
    "APROVADO" boolean,
    "NAPROVADO" boolean,
    "NUM_OS" varchar(16),
    "ATENCAO" varchar(70),
    "DATA_APROVACAO" date,
    "DATA_ENVIO" date,
    "NUM_ORC_DIMEN" integer,
    "SIT_ORC_01" integer,
    "SIT_ORC_02" integer,
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_FECHAMENTO" varchar(50),
    "DATA_FECHAMENTO" date
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_01A  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_01A" CASCADE;
CREATE TABLE "ORCAMENTO_01A" (
    "NUM_ORC" integer NOT NULL,
    "COD_CLI" integer,
    "COD_EQUIP" varchar(30),
    "NUM_NF_CHEGADA" varchar(20)
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_01B  (31 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_01B" CASCADE;
CREATE TABLE "ORCAMENTO_01B" (
    "NUM_ORC" integer NOT NULL,
    "FINALIDADE" integer,
    "OBSERVACAO" text,
    "CODPAG" integer,
    "ENTREGA" integer,
    "GARANTIA" integer,
    "VALIDADE" integer,
    "VALMAT" numeric(18,4),
    "VALMOBRA" numeric(18,4),
    "VALSERV" numeric(18,4),
    "VALIMPOSTO" numeric(18,4),
    "VALDESC" numeric(18,4),
    "VALTOTAL" numeric(18,4),
    "DESCPERC" numeric(18,4),
    "PERCIMP" numeric(18,4),
    "ASSINA" varchar(50),
    "VALID_PECAS" integer,
    "MAT_FIXACAO" numeric(18,4),
    "PERC_MAO_OBRA" numeric(18,4),
    "PERC_SERVICO" numeric(18,4),
    "PERC_NOTA_FISCAL" numeric(18,4),
    "PERC_MAT_FIXACAO" numeric(18,4),
    "ACRESCIMO_CUSTO" numeric(18,4),
    "PERC_ACRESCIMO_CUSTO" numeric(18,4),
    "PERC_DESCONTO_ITEM" numeric(18,4),
    "PERC_ACRESCIMO_ITEM" numeric(18,4),
    "VALMAT_ORIGINAL" numeric(18,4),
    "CONTADOR_PERCENTUAL" integer,
    "VALOR_MAXIMO_CLIENTE" numeric(18,4),
    "VALOR_CUSTOS_INDIRETOS" numeric(18,4),
    "CONTADOR_PERCENTUAL_02" integer
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_01C  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_01C" CASCADE;
CREATE TABLE "ORCAMENTO_01C" (
    "NUM_ORC" integer NOT NULL,
    "FINALIDADE" integer,
    "OBSERVACAO" text,
    "CODPAG" integer,
    "ENTREGA" integer,
    "GARANTIA" integer,
    "VALIDADE" integer,
    "ASSINA" varchar(50),
    "VALID_PECAS" integer
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_02  (11 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_02" CASCADE;
CREATE TABLE "ORCAMENTO_02" (
    "CODIGO" integer,
    "NUM_ORC" integer NOT NULL,
    "CODPROD" integer NOT NULL,
    "DESPROD" varchar(100),
    "DESCRICAO" varchar(100),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "NUMITEM" integer,
    "VALUNIT_ORIGINAL" numeric(18,4),
    "VALTOT_ORIGINAL" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_02_AM  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_02_AM" CASCADE;
CREATE TABLE "ORCAMENTO_02_AM" (
    "NUM_ORC" integer NOT NULL,
    "CODPROD" integer NOT NULL,
    "DESCRICAO" varchar(80),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "NUMITEM" integer
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_03  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_03" CASCADE;
CREATE TABLE "ORCAMENTO_03" (
    "NUM_ORC" integer NOT NULL,
    "NUM_ITEM" integer NOT NULL,
    "DES_ITEM" text,
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_04  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_04" CASCADE;
CREATE TABLE "ORCAMENTO_04" (
    "NUM_ORC" integer NOT NULL,
    "DATAMOV" date NOT NULL,
    "HORAMOV" date NOT NULL,
    "ASSUNTO" varchar(80),
    "CODIFUNC" integer,
    "CONTATO" varchar(50),
    "DESCRICAO" text,
    "DATA_PROX_CONT" date,
    "HORA_PROX_CONT" date
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_05  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_05" CASCADE;
CREATE TABLE "ORCAMENTO_05" (
    "NUM_ORC" integer NOT NULL,
    "NUMITEM" integer NOT NULL,
    "DESC_SERV" text,
    "CONCLUIDO" varchar(1)
);

-- ---------------------------------------------------------------------
-- ORCAMENTO_LOG_01  (31 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ORCAMENTO_LOG_01" CASCADE;
CREATE TABLE "ORCAMENTO_LOG_01" (
    "ID" integer,
    "DATAMOV" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "NUM_ORC" integer NOT NULL,
    "DATA" date,
    "DATA_OLD" date,
    "APROVADO" boolean,
    "APROVADO_OLD" boolean,
    "NAPROVADO" boolean,
    "NAPROVADO_OLD" boolean,
    "NUM_OS" varchar(16),
    "NUM_OS_OLD" varchar(16),
    "ATENCAO" varchar(70),
    "ATENCAO_OLD" varchar(70),
    "DATA_APROVACAO" date,
    "DATA_APROVACAO_OLD" date,
    "DATA_ENVIO" date,
    "DATA_ENVIO_OLD" date,
    "SIT_ORC_01" integer,
    "SIT_ORC_01_OLD" integer,
    "SIT_ORC_02" integer,
    "SIT_ORC_02_OLD" integer,
    "USUARIO_CADASTRO" varchar(50),
    "USUARIO_CADASTRO_OLD" varchar(50),
    "DATA_CADASTRO" date,
    "DATA_CADASTRO_OLD" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO_OLD" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "DATA_ULTIMA_ALTERACAO_OLD" date
);

-- ---------------------------------------------------------------------
-- PAGTO_CHEQUES_01  (10 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PAGTO_CHEQUES_01" CASCADE;
CREATE TABLE "PAGTO_CHEQUES_01" (
    "NUMCHEQUE" varchar(15) NOT NULL,
    "NUMBANCO" varchar(10),
    "AGENCIA" varchar(15),
    "CONTA" varchar(15),
    "VALOR" numeric(18,4),
    "FAVORECIDO" varchar(70),
    "DATAEMISSAO" date,
    "DATAVENCIMENTO" date,
    "DATACOMPESACAO" date,
    "OBSERV" text
);

-- ---------------------------------------------------------------------
-- PERMISSOES_MODULO_01  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PERMISSOES_MODULO_01" CASCADE;
CREATE TABLE "PERMISSOES_MODULO_01" (
    "COD_MODULO" integer NOT NULL,
    "NOM_MODULO" varchar(80)
);

-- ---------------------------------------------------------------------
-- PERMISSOES_MODULO_02  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PERMISSOES_MODULO_02" CASCADE;
CREATE TABLE "PERMISSOES_MODULO_02" (
    "COD_MODULO" integer NOT NULL,
    "COD_PROGRAMA" integer NOT NULL,
    "NOM_PROGRAMA" varchar(100),
    "NOM_FORM" varchar(80)
);

-- ---------------------------------------------------------------------
-- PERMISSOES_SISTEMA_01  (11 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PERMISSOES_SISTEMA_01" CASCADE;
CREATE TABLE "PERMISSOES_SISTEMA_01" (
    "NOME_FORM" varchar(60) NOT NULL,
    "USUARIO" varchar(15) NOT NULL,
    "PERM_CADASTRO" varchar(1),
    "PERM_ALTERACAO" varchar(1),
    "PERM_EXCLUSAO" varchar(1),
    "PERM_CONSULTA" varchar(1),
    "PERM_ALT_STATUS_OS" varchar(1),
    "PERM_ALT_DADOS_FINANC" varchar(1),
    "PERM_FECHAR_OS" varchar(1),
    "COD_MODULO" integer,
    "COD_PROGRAMA" integer
);

-- ---------------------------------------------------------------------
-- PERMISSOES_SISTEMA_02  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PERMISSOES_SISTEMA_02" CASCADE;
CREATE TABLE "PERMISSOES_SISTEMA_02" (
    "NOME_FORM" varchar(60) NOT NULL,
    "USUARIO" varchar(15) NOT NULL,
    "PERM_CADASTRO" varchar(1),
    "PERM_ALTERACAO" varchar(1),
    "PERM_EXCLUSAO" varchar(1),
    "PERM_CONSULTA" varchar(1),
    "PERM_ALT_STATUS_OS" varchar(1),
    "PERM_ALT_DADOS_FINANC" varchar(1),
    "PERM_FECHAR_OS" varchar(1)
);

-- ---------------------------------------------------------------------
-- PLANO_SAUDE  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PLANO_SAUDE" CASCADE;
CREATE TABLE "PLANO_SAUDE" (
    "CODPLAN" integer NOT NULL,
    "EMPRESA" varchar(80),
    "NOMPLAN" varchar(80),
    "VALOR" numeric(18,4),
    "VENCIMENTO" date,
    "COTAFUNC" numeric(18,4),
    "COTAEMP" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- POSICAO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "POSICAO" CASCADE;
CREATE TABLE "POSICAO" (
    "CODPOS" integer NOT NULL,
    "NOMPOS" varchar(40)
);

-- ---------------------------------------------------------------------
-- PRATELEIRA_01  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRATELEIRA_01" CASCADE;
CREATE TABLE "PRATELEIRA_01" (
    "COD_ESTANTE" integer NOT NULL,
    "COD_PRATELEIRA" integer,
    "NOME_PRATELEIRA" varchar(50)
);

-- ---------------------------------------------------------------------
-- PRIORIDADE  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRIORIDADE" CASCADE;
CREATE TABLE "PRIORIDADE" (
    "CODNIVEL" integer NOT NULL,
    "DESNIVEL" varchar(50)
);

-- ---------------------------------------------------------------------
-- PROD_FORNEC_01  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PROD_FORNEC_01" CASCADE;
CREATE TABLE "PROD_FORNEC_01" (
    "COD_FORN" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "COD_PFORN" varchar(25) NOT NULL,
    "NOME_PFORN" varchar(80),
    "PRECO_VENDA" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- PROD_FORNEC_AM_01  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PROD_FORNEC_AM_01" CASCADE;
CREATE TABLE "PROD_FORNEC_AM_01" (
    "COD_FORN" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "COD_PFORN" varchar(25) NOT NULL,
    "NOME_PFORN" varchar(80),
    "PRECO_VENDA" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- PRODUTO  (52 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO" CASCADE;
CREATE TABLE "PRODUTO" (
    "COD_PROD" integer NOT NULL,
    "COD_ANT" varchar(10),
    "DES_PROD" varchar(100),
    "nova_desc_prod" varchar(100),
    "EXCLUIR" varchar(50),
    "UNI_PROD" varchar(10),
    "EST_MINIMO" integer,
    "VALOR_COMPRA" numeric(18,4),
    "VALOR_RECEBER_COBRA" numeric(18,4),
    "VALOR_VENDA" numeric(18,4),
    "ULT_NF_ENTRADA" varchar(10),
    "DT_ULT_NF_ENTRADA" date,
    "QTD_ULT_ENTRADA" integer,
    "ULT_VALOR_COMPRA_NF" numeric(18,4),
    "ULT_NF_SAIDA" varchar(10),
    "DT_ULT_NF_SAIDA" date,
    "QTD_ULT_SAIDA" integer,
    "ULT_VALOR_VENDA_NF" numeric(18,4),
    "ULT_REQ_ENTRADA" integer,
    "DT_ULT_REQ_ENTRADA" date,
    "QTD_REQ_ENTRADA" numeric(18,4),
    "ULT_VALOR_COMPRA_REQ" numeric(18,4),
    "ULT_REQ_SAIDA" integer,
    "DT_ULT_REQ_SAIDA" date,
    "QTD_REQ_SAIDA" integer,
    "ULT_VALOR_VENDA_REQ" numeric(18,4),
    "E1" numeric(18,4),
    "PERC_LUCRO" numeric(18,4),
    "GRUPO" varchar(3),
    "SUB_GRUPO" varchar(3),
    "ITEM" varchar(3),
    "DIGITO" integer,
    "COD_COMPLETO" varchar(15),
    "ESTANTE" integer,
    "PRATELEIRA" integer,
    "TIPO" varchar(25),
    "CAPACIDADE" varchar(25),
    "TAMANHO" varchar(20),
    "Caminho_Imagem" varchar(255),
    "APLICACAO" text,
    "MARCA" varchar(50),
    "COR" varchar(30),
    "ATIVO" varchar(1),
    "SITUACAO" integer,
    "VALOR_COMPRA_DESCONTO" numeric(18,4),
    "VALOR_VENDA_DESCONTO" numeric(18,4),
    "ULTIMO_PEDIDO_COMPRA" integer,
    "VALOR_VENDA_DESCNTO" numeric(18,4),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date
);

-- ---------------------------------------------------------------------
-- PRODUTO_ARQ_MORTO_01  (42 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO_ARQ_MORTO_01" CASCADE;
CREATE TABLE "PRODUTO_ARQ_MORTO_01" (
    "COD_PROD" integer NOT NULL,
    "COD_ANT" varchar(10),
    "DES_PROD" varchar(100),
    "nova_desc_prod" varchar(100),
    "EXCLUIR" varchar(50),
    "UNI_PROD" varchar(10),
    "EST_MINIMO" integer,
    "VALOR_COMPRA" numeric(18,4),
    "VALOR_RECEBER_COBRA" numeric(18,4),
    "VALOR_VENDA" numeric(18,4),
    "ULT_NF_ENTRADA" varchar(10),
    "DT_ULT_NF_ENTRADA" date,
    "QTD_ULT_ENTRADA" integer,
    "ULT_VALOR_COMPRA_NF" numeric(18,4),
    "ULT_NF_SAIDA" varchar(10),
    "DT_ULT_NF_SAIDA" date,
    "QTD_ULT_SAIDA" integer,
    "ULT_VALOR_VENDA_NF" numeric(18,4),
    "ULT_REQ_ENTRADA" integer,
    "DT_ULT_REQ_ENTRADA" date,
    "QTD_REQ_ENTRADA" numeric(18,4),
    "ULT_VALOR_COMPRA_REQ" numeric(18,4),
    "ULT_REQ_SAIDA" integer,
    "DT_ULT_REQ_SAIDA" date,
    "QTD_REQ_SAIDA" integer,
    "ULT_VALOR_VENDA_REQ" numeric(18,4),
    "E1" numeric(18,4),
    "PERC_LUCRO" numeric(18,4),
    "GRUPO" varchar(3),
    "SUB_GRUPO" varchar(3),
    "ITEM" varchar(3),
    "DIGITO" integer,
    "COD_COMPLETO" varchar(15),
    "ESTANTE" integer,
    "PRATELEIRA" integer,
    "TIPO" varchar(25),
    "CAPACIDADE" varchar(25),
    "TAMANHO" varchar(20),
    "Caminho_Imagem" varchar(255),
    "APLICACAO" text,
    "MARCA" varchar(50),
    "COR" varchar(30)
);

-- ---------------------------------------------------------------------
-- PRODUTO_CLIENTES_ESPECIAIS_01  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO_CLIENTES_ESPECIAIS_01" CASCADE;
CREATE TABLE "PRODUTO_CLIENTES_ESPECIAIS_01" (
    "CODCLI" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "PERC_LUCRO" numeric(18,4),
    "VALOR_VENDA" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- PRODUTO_FORNECEDOR  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO_FORNECEDOR" CASCADE;
CREATE TABLE "PRODUTO_FORNECEDOR" (
    "COD_FORN" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "COD_PROD_FORN" varchar(25) NOT NULL,
    "DES_PROD" varchar(100),
    "EST_MINIMO_FORN" integer,
    "VAL_VENDA_FORN" numeric(18,4),
    "PRAZO_DEV" integer,
    "E1" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- PRODUTO_FORNECEDOR_AM_01  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO_FORNECEDOR_AM_01" CASCADE;
CREATE TABLE "PRODUTO_FORNECEDOR_AM_01" (
    "COD_FORN" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "COD_PROD_FORN" varchar(25) NOT NULL,
    "DES_PROD" varchar(100),
    "EST_MINIMO_FORN" integer,
    "VAL_VENDA_FORN" numeric(18,4),
    "PRAZO_DEV" integer,
    "E1" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- PRODUTO_LOG_01  (43 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO_LOG_01" CASCADE;
CREATE TABLE "PRODUTO_LOG_01" (
    "ID" integer,
    "DATA" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "COD_PROD" integer NOT NULL,
    "DES_PROD" varchar(100),
    "DES_PROD_OLD" varchar(100),
    "UNI_PROD" varchar(10),
    "UNI_PROD_OLD" varchar(10),
    "VALOR_COMPRA" numeric(18,4),
    "VALOR_COMPRA_OLD" numeric(18,4),
    "VALOR_VENDA" numeric(18,4),
    "VALOR_VENDA_OLD" numeric(18,4),
    "PERC_LUCRO" numeric(18,4),
    "PERC_LUCRO_OLD" numeric(18,4),
    "GRUPO" varchar(3),
    "GRUPO_OLD" varchar(3),
    "SUB_GRUPO" varchar(3),
    "SUB_GRUPO_OLD" varchar(3),
    "ESTANTE" integer,
    "ESTANTE_OLD" integer,
    "PRATELEIRA" integer,
    "PRATELEIRA_OLD" integer,
    "TIPO" varchar(25),
    "TIPO_OLD" varchar(25),
    "CAPACIDADE" varchar(25),
    "CAPACIDADE_OLD" varchar(25),
    "TAMANHO" varchar(20),
    "TAMANHO_OLD" varchar(20),
    "APLICACAO" text,
    "APLICACAO_OLD" text,
    "MARCA" varchar(50),
    "MARCA_OLD" varchar(50),
    "COR" varchar(30),
    "COR_OLD" varchar(30),
    "ATIVO" varchar(1),
    "ATIVO_OLD" varchar(1),
    "SITUACAO" integer,
    "SITUACAO_OLD" integer,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO_OLD" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "DATA_ULTIMA_ALTERACAO_OLD" date
);

-- ---------------------------------------------------------------------
-- PRODUTO_NOVO  (33 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO_NOVO" CASCADE;
CREATE TABLE "PRODUTO_NOVO" (
    "COD_PROD" integer NOT NULL,
    "COD_ANT" varchar(10),
    "DES_PROD" varchar(100),
    "UNI_PROD" varchar(10),
    "EST_MINIMO" integer,
    "VALOR_COMPRA" numeric(18,4),
    "VALOR_RECEBER_COBRA" numeric(18,4),
    "VALOR_VENDA" numeric(18,4),
    "ULT_NF_ENTRADA" varchar(10),
    "DT_ULT_NF_ENTRADA" date,
    "QTD_ULT_ENTRADA" integer,
    "ULT_VALOR_COMPRA_NF" numeric(18,4),
    "ULT_NF_SAIDA" varchar(10),
    "DT_ULT_NF_SAIDA" date,
    "QTD_ULT_SAIDA" integer,
    "ULT_VALOR_VENDA_NF" numeric(18,4),
    "ULT_REQ_ENTRADA" integer,
    "DT_ULT_REQ_ENTRADA" date,
    "QTD_REQ_ENTRADA" numeric(18,4),
    "ULT_VALOR_COMPRA_REQ" numeric(18,4),
    "ULT_REQ_SAIDA" integer,
    "DT_ULT_REQ_SAIDA" date,
    "QTD_REQ_SAIDA" integer,
    "ULT_VALOR_VENDA_REQ" numeric(18,4),
    "E1" numeric(18,4),
    "PERC_LUCRO" numeric(18,4),
    "GRUPO" varchar(3),
    "SUB_GRUPO" varchar(3),
    "ITEM" varchar(3),
    "DIGITO" integer,
    "COD_COMPLETO" varchar(15),
    "ESTANTE" integer,
    "PRATELEIRA" integer
);

-- ---------------------------------------------------------------------
-- PRODUTO_USADO  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO_USADO" CASCADE;
CREATE TABLE "PRODUTO_USADO" (
    "COD_PROD" integer NOT NULL,
    "DES_PROD" varchar(100),
    "UNI_PROD" varchar(10),
    "GRUPO" varchar(3),
    "SUB_GRUPO" varchar(3),
    "ESTANTE" integer,
    "PRATELEIRA" integer,
    "ATIVO" varchar(1)
);

-- ---------------------------------------------------------------------
-- PRODUTO1  (48 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO1" CASCADE;
CREATE TABLE "PRODUTO1" (
    "COD_PROD" integer NOT NULL,
    "COD_ANT" varchar(10),
    "DES_PROD" varchar(100),
    "nova_desc_prod" varchar(100),
    "EXCLUIR" varchar(50),
    "UNI_PROD" varchar(10),
    "EST_MINIMO" integer,
    "VALOR_COMPRA" numeric(18,4),
    "VALOR_RECEBER_COBRA" numeric(18,4),
    "VALOR_VENDA" numeric(18,4),
    "ULT_NF_ENTRADA" varchar(10),
    "DT_ULT_NF_ENTRADA" date,
    "QTD_ULT_ENTRADA" integer,
    "ULT_VALOR_COMPRA_NF" numeric(18,4),
    "ULT_NF_SAIDA" varchar(10),
    "DT_ULT_NF_SAIDA" date,
    "QTD_ULT_SAIDA" integer,
    "ULT_VALOR_VENDA_NF" numeric(18,4),
    "ULT_REQ_ENTRADA" integer,
    "DT_ULT_REQ_ENTRADA" date,
    "QTD_REQ_ENTRADA" numeric(18,4),
    "ULT_VALOR_COMPRA_REQ" numeric(18,4),
    "ULT_REQ_SAIDA" integer,
    "DT_ULT_REQ_SAIDA" date,
    "QTD_REQ_SAIDA" integer,
    "ULT_VALOR_VENDA_REQ" numeric(18,4),
    "E1" numeric(18,4),
    "PERC_LUCRO" numeric(18,4),
    "GRUPO" varchar(3),
    "SUB_GRUPO" varchar(3),
    "ITEM" varchar(3),
    "DIGITO" integer,
    "COD_COMPLETO" varchar(15),
    "ESTANTE" integer,
    "PRATELEIRA" integer,
    "TIPO" varchar(25),
    "CAPACIDADE" varchar(25),
    "TAMANHO" varchar(20),
    "Caminho_Imagem" varchar(255),
    "APLICACAO" text,
    "MARCA" varchar(50),
    "COR" varchar(30),
    "ATIVO" varchar(1),
    "SITUACAO" integer,
    "VALOR_COMPRA_DESCONTO" numeric(18,4),
    "VALOR_VENDA_DESCONTO" numeric(18,4),
    "ULTIMO_PEDIDO_COMPRA" integer,
    "VALOR_VENDA_DESCNTO" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- PRODUTO2  (41 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO2" CASCADE;
CREATE TABLE "PRODUTO2" (
    "COD_PROD" integer,
    "COD_ANT" varchar(10),
    "DES_PROD" varchar(100),
    "DPROD" varchar(100),
    "UNI_PROD" varchar(10),
    "EST_MINIMO" integer,
    "VALOR_COMPRA" numeric(18,4),
    "VALOR_RECEBER_COBRA" numeric(18,4),
    "VALOR_VENDA" numeric(18,4),
    "ULT_NF_ENTRADA" varchar(10),
    "DT_ULT_NF_ENTRADA" date,
    "QTD_ULT_ENTRADA" integer,
    "ULT_VALOR_COMPRA_NF" numeric(18,4),
    "ULT_NF_SAIDA" varchar(10),
    "DT_ULT_NF_SAIDA" date,
    "QTD_ULT_SAIDA" integer,
    "ULT_VALOR_VENDA_NF" numeric(18,4),
    "ULT_REQ_ENTRADA" integer,
    "DT_ULT_REQ_ENTRADA" date,
    "QTD_REQ_ENTRADA" numeric(18,4),
    "ULT_VALOR_COMPRA_REQ" numeric(18,4),
    "ULT_REQ_SAIDA" integer,
    "DT_ULT_REQ_SAIDA" date,
    "QTD_REQ_SAIDA" integer,
    "ULT_VALOR_VENDA_REQ" numeric(18,4),
    "E1" numeric(18,4),
    "PERC_LUCRO" numeric(18,4),
    "GRUPO" varchar(3),
    "SUB_GRUPO" varchar(3),
    "ITEM" varchar(3),
    "DIGITO" integer,
    "COD_COMPLETO" varchar(15),
    "ESTANTE" integer,
    "PRATELEIRA" integer,
    "TIPO" varchar(25),
    "CAPACIDADE" varchar(25),
    "TAMANHO" varchar(20),
    "VALOR_COMPRA_DESCONTO" numeric(18,4),
    "VALOR_VENDA_DESCONTO" numeric(18,4),
    "ULTIMO_PEDIDO_COMPRA" integer,
    "VALOR_VENDA_DESCNTO" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- PRODUTO3  (48 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "PRODUTO3" CASCADE;
CREATE TABLE "PRODUTO3" (
    "COD_PROD" integer NOT NULL,
    "COD_ANT" varchar(10),
    "DES_PROD" varchar(100),
    "nova_desc_prod" varchar(100),
    "EXCLUIR" varchar(50),
    "UNI_PROD" varchar(10),
    "EST_MINIMO" integer,
    "VALOR_COMPRA" numeric(18,4),
    "VALOR_RECEBER_COBRA" numeric(18,4),
    "VALOR_VENDA" numeric(18,4),
    "ULT_NF_ENTRADA" varchar(10),
    "DT_ULT_NF_ENTRADA" date,
    "QTD_ULT_ENTRADA" integer,
    "ULT_VALOR_COMPRA_NF" numeric(18,4),
    "ULT_NF_SAIDA" varchar(10),
    "DT_ULT_NF_SAIDA" date,
    "QTD_ULT_SAIDA" integer,
    "ULT_VALOR_VENDA_NF" numeric(18,4),
    "ULT_REQ_ENTRADA" integer,
    "DT_ULT_REQ_ENTRADA" date,
    "QTD_REQ_ENTRADA" numeric(18,4),
    "ULT_VALOR_COMPRA_REQ" numeric(18,4),
    "ULT_REQ_SAIDA" integer,
    "DT_ULT_REQ_SAIDA" date,
    "QTD_REQ_SAIDA" integer,
    "ULT_VALOR_VENDA_REQ" numeric(18,4),
    "E1" numeric(18,4),
    "PERC_LUCRO" numeric(18,4),
    "GRUPO" varchar(3),
    "SUB_GRUPO" varchar(3),
    "ITEM" varchar(3),
    "DIGITO" integer,
    "COD_COMPLETO" varchar(15),
    "ESTANTE" integer,
    "PRATELEIRA" integer,
    "TIPO" varchar(25),
    "CAPACIDADE" varchar(25),
    "TAMANHO" varchar(20),
    "Caminho_Imagem" varchar(255),
    "APLICACAO" text,
    "MARCA" varchar(50),
    "COR" varchar(30),
    "ATIVO" varchar(1),
    "SITUACAO" integer,
    "VALOR_COMPRA_DESCONTO" numeric(18,4),
    "VALOR_VENDA_DESCONTO" numeric(18,4),
    "ULTIMO_PEDIDO_COMPRA" integer,
    "VALOR_VENDA_DESCNTO" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- RECIBOS_01  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "RECIBOS_01" CASCADE;
CREATE TABLE "RECIBOS_01" (
    "NUMRECIBO" integer NOT NULL,
    "NUM_OS" varchar(16),
    "DATA" date,
    "COD_CLI" integer,
    "VALOR" numeric(18,4),
    "VALOR_DESC" numeric(18,4),
    "REFERENTE" text,
    "NUM_ORDSER" text
);

-- ---------------------------------------------------------------------
-- SAIDA_PROD_USADO_01  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SAIDA_PROD_USADO_01" CASCADE;
CREATE TABLE "SAIDA_PROD_USADO_01" (
    "NUMREQ" integer NOT NULL,
    "DATA" date,
    "FUNC" integer,
    "ORIGEM" integer,
    "NUM_OS" varchar(16),
    "FECHAMENTO" varchar(1),
    "OBSERV_GERAL" text
);

-- ---------------------------------------------------------------------
-- SAIDA_PROD_USADO_02  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SAIDA_PROD_USADO_02" CASCADE;
CREATE TABLE "SAIDA_PROD_USADO_02" (
    "NUMREQ" integer NOT NULL,
    "ITEM" integer NOT NULL,
    "COD_PROD" integer,
    "DES_PROD" varchar(100),
    "QTDE" integer,
    "OBS_PROD" varchar(80)
);

-- ---------------------------------------------------------------------
-- SAIDAPROD_01  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SAIDAPROD_01" CASCADE;
CREATE TABLE "SAIDAPROD_01" (
    "NUMREQ" integer NOT NULL,
    "DATA" date,
    "HORA" date,
    "SETOR" integer,
    "FUNC" integer,
    "FORN" integer,
    "NUM_OS" varchar(16),
    "STATUS_REQ" varchar(7)
);

-- ---------------------------------------------------------------------
-- SAIDAPROD_02  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SAIDAPROD_02" CASCADE;
CREATE TABLE "SAIDAPROD_02" (
    "NUMREQ" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "DES_PROD" varchar(100),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "CATEGORIA" varchar(20),
    "DEVOLUCAO" integer
);

-- ---------------------------------------------------------------------
-- SERVICOS_GERAIS_01  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SERVICOS_GERAIS_01" CASCADE;
CREATE TABLE "SERVICOS_GERAIS_01" (
    "COD_SERVICO" integer,
    "PLACA" varchar(7),
    "DATA" date,
    "LOCAL" varchar(80),
    "ATENDENTE" varchar(50),
    "KM_INICIAL" numeric(18,4),
    "FUNCIONARIO" integer,
    "OBSERVACAO" text
);

-- ---------------------------------------------------------------------
-- SERVICOS_GERAIS_02  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SERVICOS_GERAIS_02" CASCADE;
CREATE TABLE "SERVICOS_GERAIS_02" (
    "COD_SERVICO" integer NOT NULL,
    "ITEM" integer NOT NULL,
    "DESC_SERVICO" varchar(80),
    "QUANTIDADE" integer,
    "VALOR_UNIT_SERV" numeric(18,4),
    "VALOR_TOTAL_SERV" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- SETORES  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SETORES" CASCADE;
CREATE TABLE "SETORES" (
    "CODSETOR" integer NOT NULL,
    "NOMSETOR" varchar(70)
);

-- ---------------------------------------------------------------------
-- SIT_ATUAL_OS_01  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SIT_ATUAL_OS_01" CASCADE;
CREATE TABLE "SIT_ATUAL_OS_01" (
    "COD_SIT_01" integer NOT NULL,
    "NOM_SIT_01" varchar(70)
);

-- ---------------------------------------------------------------------
-- SIT_ATUAL_OS_02  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SIT_ATUAL_OS_02" CASCADE;
CREATE TABLE "SIT_ATUAL_OS_02" (
    "COD_SIT_02" integer NOT NULL,
    "NOM_SIT_02" varchar(70)
);

-- ---------------------------------------------------------------------
-- SIT_ORCAMENTO_01  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SIT_ORCAMENTO_01" CASCADE;
CREATE TABLE "SIT_ORCAMENTO_01" (
    "COD_SIT_01" integer NOT NULL,
    "NOM_SIT_01" varchar(70)
);

-- ---------------------------------------------------------------------
-- SIT_ORCAMENTO_02  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SIT_ORCAMENTO_02" CASCADE;
CREATE TABLE "SIT_ORCAMENTO_02" (
    "COD_SIT_02" integer NOT NULL,
    "NOM_SIT_02" varchar(70)
);

-- ---------------------------------------------------------------------
-- SITUACAO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SITUACAO" CASCADE;
CREATE TABLE "SITUACAO" (
    "COD_SIT" integer NOT NULL,
    "DES_SIT" varchar(50)
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_01  (90 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_01" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_01" (
    "NUMREQ" numeric(18,4) NOT NULL,
    "DATA" date,
    "HORA" date,
    "SETOR" integer,
    "FUNC" integer,
    "COD_FORN" integer,
    "COD_SOLIC_COMPRA" integer,
    "SOLIC_COMPRA" integer,
    "OBSERV_GERAL" text,
    "REPOR_ESTOQUE" varchar(1),
    "REPOR_VENDA" varchar(1),
    "ATENDER_PROP_APROV" varchar(1),
    "NUM_PROPOSTA" integer,
    "DESCONTO" numeric(18,4),
    "CODPAG" integer,
    "ENTREGA" integer,
    "FORN_PROD" integer,
    "NUM_NF_FORNEC" varchar(15),
    "DATA_NF_FORNEC" date,
    "FECHAMENTO" varchar(1),
    "DATAVENC" date,
    "TIPO" integer,
    "COD_CC" integer,
    "FORMA_REC" integer,
    "DOC_REC" integer,
    "COD_CP" integer,
    "DESC_PERC" numeric(18,4),
    "LOCAL_ENTREGA" text,
    "RESP_RECEBIMENTO" varchar(100),
    "FONE_RESP_REC" varchar(20),
    "USUARIO" varchar(50),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_FECHAMENTO" varchar(50),
    "DATA_FECHAMENTO" date,
    "QTDIAS" integer,
    "TOTPARC" integer,
    "VALTOT" numeric(18,4),
    "NPARC1" integer,
    "VPARC1" numeric(18,4),
    "QTDIAS1" integer,
    "DVENC1" date,
    "NPARC2" integer,
    "VPARC2" numeric(18,4),
    "QTDIAS2" integer,
    "DVENC2" date,
    "NPARC3" integer,
    "VPARC3" numeric(18,4),
    "QTDIAS3" integer,
    "DVENC3" date,
    "NPARC4" integer,
    "VPARC4" numeric(18,4),
    "QTDIAS4" integer,
    "DVENC4" date,
    "NPARC5" integer,
    "VPARC5" numeric(18,4),
    "QTDIAS5" integer,
    "DVENC5" date,
    "NPARC6" integer,
    "VPARC6" numeric(18,4),
    "QTDIAS6" integer,
    "DVENC6" date,
    "NPARC7" integer,
    "VPARC7" numeric(18,4),
    "QTDIAS7" integer,
    "DVENC7" date,
    "NPARC8" integer,
    "VPARC8" numeric(18,4),
    "QTDIAS8" integer,
    "DVENC8" date,
    "NPARC9" integer,
    "VPARC9" numeric(18,4),
    "QTDIAS9" integer,
    "DVENC9" date,
    "NPARC10" integer,
    "VPARC10" numeric(18,4),
    "QTDIAS10" integer,
    "DVENC10" date,
    "NPARC11" integer,
    "VPARC11" numeric(18,4),
    "QTDIAS11" integer,
    "DVENC11" date,
    "NPARC12" integer,
    "VPARC12" numeric(18,4),
    "QTDIAS12" integer,
    "DVENC12" date,
    "FORMA_REC_02" integer,
    "DOC_REC_02" integer
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_01_AAAAA  (30 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_01_AAAAA" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_01_AAAAA" (
    "NUMREQ" numeric(18,4) NOT NULL,
    "DATA" date,
    "HORA" date,
    "SETOR" integer,
    "FUNC" integer,
    "COD_FORN" integer,
    "SOLIC_COMPRA" integer,
    "OBSERV_GERAL" text,
    "REPOR_ESTOQUE" varchar(1),
    "REPOR_VENDA" varchar(1),
    "ATENDER_PROP_APROV" varchar(1),
    "NUM_PROPOSTA" integer,
    "DESCONTO" numeric(18,4),
    "CODPAG" integer,
    "ENTREGA" integer,
    "FORN_PROD" integer,
    "NUM_NF_FORNEC" varchar(15),
    "DATA_NF_FORNEC" date,
    "FECHAMENTO" varchar(1),
    "DATAVENC" date,
    "TIPO" integer,
    "COD_CC" integer,
    "FORMA_REC" integer,
    "DOC_REC" integer,
    "COD_CP" integer,
    "DESC_PERC" numeric(18,4),
    "LOCAL_ENTREGA" text,
    "RESP_RECEBIMENTO" varchar(100),
    "FONE_RESP_REC" varchar(20),
    "USUARIO" varchar(50)
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_01A  (18 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_01A" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_01A" (
    "NUMREQ" numeric(18,4) NOT NULL,
    "DATA" date,
    "HORA" date,
    "SETOR" integer,
    "FUNC" integer,
    "FECHAMENTO" varchar(1),
    "EXPORTADO" varchar(1),
    "COTADO" varchar(1),
    "LOCAL_ENTREGA" text,
    "RESP_RECEBIMENTO" varchar(100),
    "FONE_RESP_REC" varchar(20),
    "COD_PED_COMPRA" integer,
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "USUARIO_FECHAMENTO" varchar(50),
    "DATA_FECHAMENTO" date
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_02  (15 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_02" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_02" (
    "NUMREQ" numeric(18,4) NOT NULL,
    "COD_PROD" integer NOT NULL,
    "DESPROD" varchar(100),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "NEW_VALUNIT" numeric(18,4),
    "DESC_PERC" numeric(18,4),
    "DESC_ITEM" numeric(18,4),
    "VALTOT" numeric(18,4),
    "OBS_PROD" varchar(80),
    "ATUALIZA_PCOMPRA" varchar(1),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_02_AM  (7 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_02_AM" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_02_AM" (
    "NUMREQ" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "DESC_ITEM" numeric(18,4),
    "VALTOT" numeric(18,4),
    "OBS_PROD" varchar(80)
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_02A  (14 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_02A" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_02A" (
    "NUMREQ" numeric(18,4) NOT NULL,
    "COD_PROD" integer NOT NULL,
    "DES_PROD" varchar(100),
    "QTDE" integer,
    "SELECIONADO" varchar(1),
    "EXPORTADO" varchar(1),
    "COTADO" varchar(1),
    "REP_EST" varchar(1),
    "DATA_UTILIZACAO" date,
    "PERIODO" varchar(20),
    "USUARIO_CADASTRO" varchar(50),
    "DATA_CADASTRO" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_02A_AM  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_02A_AM" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_02A_AM" (
    "NUMREQ" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "QTDE" integer,
    "SELECIONADO" varchar(1),
    "EXPORTADO" varchar(1)
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_LOG_01  (31 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_LOG_01" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_LOG_01" (
    "ID" integer,
    "DATAMOV" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "NUMREQ" numeric(18,4) NOT NULL,
    "DATA" date,
    "DATA_OLD" date,
    "SETOR" integer,
    "SETOR_OLD" integer,
    "SOLIC_COMPRA" integer,
    "SOLIC_COMPRA_OLD" integer,
    "COD_FORN" integer,
    "COD_FORN_OLD" integer,
    "CODPAG" integer,
    "CODPAG_OLD" integer,
    "ENTREGA" integer,
    "ENTREGA_OLD" integer,
    "LOCAL_ENTREGA" text,
    "LOCAL_ENTREGA_OLD" text,
    "NUM_NF_FORNEC" varchar(15),
    "NUM_NF_FORNEC_OLD" varchar(15),
    "DATA_NF_FORNEC" date,
    "DATA_NF_FORNEC_OLD" date,
    "RESP_RECEBIMENTO" varchar(100),
    "RESP_RECEBIMENTO_OLD" varchar(100),
    "OBSERV_GERAL" text,
    "OBSERV_GERAL_OLD" text,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO_OLD" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "DATA_ULTIMA_ALTERACAO_OLD" date
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_LOG_01A  (27 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_LOG_01A" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_LOG_01A" (
    "ID" integer,
    "DATAMOV" date,
    "USUARIO" varchar(50),
    "TIPOMOV" varchar(1),
    "NUMREQ" numeric(18,4),
    "DATA" date,
    "DATA_OLD" date,
    "SETOR" integer,
    "SETOR_OLD" integer,
    "FUNC" integer,
    "FUNC_OLD" integer,
    "LOCAL_ENTREGA" text,
    "LOCAL_ENTREGA_OLD" text,
    "RESP_RECEBIMENTO" varchar(100),
    "RESP_RECEBIMENTO_OLD" varchar(100),
    "USUARIO_CADASTRO" varchar(50),
    "USUARIO_CADASTRO_OLD" varchar(50),
    "DATA_CADASTRO" date,
    "DATA_CADASTRO_OLD" date,
    "USUARIO_ULTIMA_ALTERACAO" varchar(50),
    "USUARIO_ULTIMA_ALTERACAO_OLD" varchar(50),
    "DATA_ULTIMA_ALTERACAO" date,
    "DATA_ULTIMA_ALTERACAO_OLD" date,
    "USUARIO_FECHAMENTO" varchar(50),
    "USUARIO_FECHAMENTO_OLD" varchar(50),
    "DATA_FECHAMENTO" date,
    "DATA_FECHAMENTO_OLD" date
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_PVENDA_01  (13 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_PVENDA_01" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_PVENDA_01" (
    "NUMREQ" integer NOT NULL,
    "DATA" date,
    "HORA" date,
    "SETOR" integer,
    "FUNC" integer,
    "COD_FORN" integer,
    "SOLIC_COMPRA" integer,
    "OBSERV_GERAL" text,
    "REPOR_ESTOQUE" varchar(1),
    "REPOR_VENDA" varchar(1),
    "ATENDER_PROP_APROV" varchar(1),
    "NUM_PROPOSTA" integer,
    "USUARIO" varchar(50)
);

-- ---------------------------------------------------------------------
-- SOLICITACAO_COMPRA_PVENDA_02  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SOLICITACAO_COMPRA_PVENDA_02" CASCADE;
CREATE TABLE "SOLICITACAO_COMPRA_PVENDA_02" (
    "NUMREQ" integer NOT NULL,
    "COD_PROD" integer NOT NULL,
    "COD_FORN" integer,
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "OBS_PROD" varchar(80),
    "DES_PROD" varchar(100)
);

-- ---------------------------------------------------------------------
-- STATUS_EQUIP  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "STATUS_EQUIP" CASCADE;
CREATE TABLE "STATUS_EQUIP" (
    "CODSTATUS" integer NOT NULL,
    "NOMSTATUS" varchar(70)
);

-- ---------------------------------------------------------------------
-- STATUS_OS  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "STATUS_OS" CASCADE;
CREATE TABLE "STATUS_OS" (
    "CODSTATUS" integer NOT NULL,
    "NOMSTATUS" varchar(70)
);

-- ---------------------------------------------------------------------
-- STATUS_OS_02  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "STATUS_OS_02" CASCADE;
CREATE TABLE "STATUS_OS_02" (
    "CODSTATUS" integer NOT NULL,
    "NOMSTATUS" varchar(70)
);

-- ---------------------------------------------------------------------
-- STATUS_OS_BKP  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "STATUS_OS_BKP" CASCADE;
CREATE TABLE "STATUS_OS_BKP" (
    "CODSTATUS" integer NOT NULL,
    "NOMSTATUS" varchar(70)
);

-- ---------------------------------------------------------------------
-- STATUS_PAGAMENTO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "STATUS_PAGAMENTO" CASCADE;
CREATE TABLE "STATUS_PAGAMENTO" (
    "COD_STATUS" integer NOT NULL,
    "NOM_STATUS" varchar(50)
);

-- ---------------------------------------------------------------------
-- SUB_GRUPO  (4 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "SUB_GRUPO" CASCADE;
CREATE TABLE "SUB_GRUPO" (
    "COD_GRUPO" varchar(3) NOT NULL,
    "COD_SUB_GRUPO" varchar(3) NOT NULL,
    "NOM_SUB_GRUPO" varchar(40),
    "ULTIMO_ITEM" varchar(3)
);

-- ---------------------------------------------------------------------
-- TB_BALANCO_FINANC_01  (21 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TB_BALANCO_FINANC_01" CASCADE;
CREATE TABLE "TB_BALANCO_FINANC_01" (
    "DATA_INICIO" date,
    "DATA_FIM" date,
    "MES_ANO" varchar(7),
    "BRUTO_FATURADO" numeric(18,4),
    "ISS_FATURADO" numeric(18,4),
    "MULTA_FATURADO" numeric(18,4),
    "JUROS_FATURADO" numeric(18,4),
    "DESCONTO_FATURADO" numeric(18,4),
    "LIQUIDO_FATURADO" numeric(18,4),
    "BRUTO_FATURAR" numeric(18,4),
    "ISS_FATURAR" numeric(18,4),
    "LIQUIDO_FATURAR" numeric(18,4),
    "VALOR_PRINC_PAGO" numeric(18,4),
    "JUROS_PAGO" numeric(18,4),
    "MULTA_PAGO" numeric(18,4),
    "DESCONTO_PAGO" numeric(18,4),
    "BRUTO_PAGO" numeric(18,4),
    "VALOR_PRINC_PAGAR" numeric(18,4),
    "JUROS_PAGAR" numeric(18,4),
    "MULTA_PAGAR" numeric(18,4),
    "BRUTO_PAGAR" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- TB_DATA  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TB_DATA" CASCADE;
CREATE TABLE "TB_DATA" (
    "Data" date NOT NULL,
    "Contador" integer
);

-- ---------------------------------------------------------------------
-- TB_FPC01  (47 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TB_FPC01" CASCADE;
CREATE TABLE "TB_FPC01" (
    "NUMREQ" integer,
    "DATA" date,
    "HORA" date,
    "SETOR" integer,
    "NOMSETOR" varchar(70),
    "FUNC" integer,
    "FUNC_02" varchar(20),
    "SOLIC_COMPRA" integer,
    "OBSERV_GERAL" text,
    "REPOR_ESTOQUE" varchar(1),
    "REPOR_VENDA" varchar(1),
    "ATENDER_PROP_APROV" varchar(1),
    "COD_FORN" integer,
    "NOMFOR" varchar(100),
    "NOMFANT" varchar(100),
    "INS_EST" varchar(20),
    "CGC_FOR" varchar(20),
    "ENDFOR" varchar(60),
    "NUMERO" varchar(10),
    "COMPLEMENTO" varchar(50),
    "BAIRRO" varchar(50),
    "CIDADE" varchar(20),
    "CEP" varchar(10),
    "UF" varchar(2),
    "FONE" varchar(15),
    "FAX" varchar(15),
    "EMAIL" varchar(50),
    "CONTATO" varchar(25),
    "COD_PROD" integer,
    "DES_PROD" varchar(100),
    "UNI_PROD" varchar(10),
    "QTDE" integer,
    "VALUNIT" numeric(18,4),
    "VALTOT" numeric(18,4),
    "OBS_PROD" varchar(80),
    "NUM_PROPOSTA" integer,
    "DESCONTO" numeric(18,4),
    "CODPAG" integer,
    "ENTREGA" integer,
    "VUNIT" numeric(18,4),
    "PLUCRO" numeric(18,4),
    "V_VENDA" numeric(18,4),
    "FORMA_REC" integer,
    "DOC_REC" integer,
    "COD_CP" integer,
    "ATUPRECO" varchar(1),
    "NEW_VALUNIT" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- TB_MOVBA  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TB_MOVBA" CASCADE;
CREATE TABLE "TB_MOVBA" (
    "Data" date NOT NULL,
    "Contador" integer
);

-- ---------------------------------------------------------------------
-- TIPO_CAPTACAO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TIPO_CAPTACAO" CASCADE;
CREATE TABLE "TIPO_CAPTACAO" (
    "COD_TIPO_CAP" integer NOT NULL,
    "NOM_TIPO_CAP" varchar(50)
);

-- ---------------------------------------------------------------------
-- TIPO_COMBUSTIVEL  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TIPO_COMBUSTIVEL" CASCADE;
CREATE TABLE "TIPO_COMBUSTIVEL" (
    "CODTIPO" integer NOT NULL,
    "NOMTIPO" varchar(50)
);

-- ---------------------------------------------------------------------
-- TIPO_CONTRATO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TIPO_CONTRATO" CASCADE;
CREATE TABLE "TIPO_CONTRATO" (
    "COD_TIPO" integer NOT NULL,
    "NOM_TIPO" varchar(50)
);

-- ---------------------------------------------------------------------
-- TIPO_EQUIPAMENTO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TIPO_EQUIPAMENTO" CASCADE;
CREATE TABLE "TIPO_EQUIPAMENTO" (
    "CODTIPO" integer NOT NULL,
    "NOMTIPO" varchar(40)
);

-- ---------------------------------------------------------------------
-- TIPO_GARANTIA  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TIPO_GARANTIA" CASCADE;
CREATE TABLE "TIPO_GARANTIA" (
    "CODTIPO" integer NOT NULL,
    "NOMTIPO" varchar(40)
);

-- ---------------------------------------------------------------------
-- TIPO_PAGTO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TIPO_PAGTO" CASCADE;
CREATE TABLE "TIPO_PAGTO" (
    "CODPAGTO" integer NOT NULL,
    "NOMPAGTO" varchar(40)
);

-- ---------------------------------------------------------------------
-- TIPO_RD01  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TIPO_RD01" CASCADE;
CREATE TABLE "TIPO_RD01" (
    "CODTIPO" integer NOT NULL,
    "NOMTIPO" varchar(40)
);

-- ---------------------------------------------------------------------
-- TIPO_VENDA  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TIPO_VENDA" CASCADE;
CREATE TABLE "TIPO_VENDA" (
    "COD_TIPO" integer NOT NULL,
    "NOM_TIPO" varchar(50)
);

-- ---------------------------------------------------------------------
-- TRANSACAO_01  (3 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TRANSACAO_01" CASCADE;
CREATE TABLE "TRANSACAO_01" (
    "CODTRANS" integer NOT NULL,
    "NOMTRANS" varchar(100),
    "FORTRANS" varchar(30)
);

-- ---------------------------------------------------------------------
-- TROCA_OLEO  (10 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "TROCA_OLEO" CASCADE;
CREATE TABLE "TROCA_OLEO" (
    "COD_TROCA" integer,
    "PLACA" varchar(7),
    "DATA" date,
    "KM_ATUAL_TROCA" numeric(18,4),
    "KM_PROX_TROCA" numeric(18,4),
    "MARCA_OLEO" varchar(30),
    "QTD_LITROS" numeric(18,4),
    "VALOR_UNIT" numeric(18,4),
    "VALOR_TOTAL" numeric(18,4),
    "OBSERVACAO" text
);

-- ---------------------------------------------------------------------
-- ULTCR  (1 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ULTCR" CASCADE;
CREATE TABLE "ULTCR" (
    "ULTCR" integer
);

-- ---------------------------------------------------------------------
-- ULTIMO_CLIENTE_01  (1 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ULTIMO_CLIENTE_01" CASCADE;
CREATE TABLE "ULTIMO_CLIENTE_01" (
    "ULTCLI" integer
);

-- ---------------------------------------------------------------------
-- ULTIMO_PRODUTO  (1 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "ULTIMO_PRODUTO" CASCADE;
CREATE TABLE "ULTIMO_PRODUTO" (
    "ULTPROD" integer
);

-- ---------------------------------------------------------------------
-- USUARIO_ATUAL  (5 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "USUARIO_ATUAL" CASCADE;
CREATE TABLE "USUARIO_ATUAL" (
    "USUARIO" varchar(15),
    "NOMUSU" varchar(50),
    "PRIVILEGIADO" integer,
    "SETOR" integer,
    "CODFUN" integer
);

-- ---------------------------------------------------------------------
-- USUARIOS  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "USUARIOS" CASCADE;
CREATE TABLE "USUARIOS" (
    "USUARIO" varchar(15) NOT NULL,
    "NOMUSU" varchar(50),
    "SENHA" varchar(15),
    "CONFIRMA" varchar(50),
    "PRIVILEGIADO" integer,
    "SETOR" integer,
    "CODFUN" integer,
    "ACESSO_ALMOX" varchar(50)
);

-- ---------------------------------------------------------------------
-- VALOR_ORC_NAPROV  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VALOR_ORC_NAPROV" CASCADE;
CREATE TABLE "VALOR_ORC_NAPROV" (
    "CODIGO" integer NOT NULL,
    "VALOR" numeric(18,4),
    "PRAZO_RET_EQUIP" integer,
    "VALOR_TAXA_PERM" numeric(18,4),
    "PRAZO_INI_ENVIO_ORC" integer,
    "PRAZO_FIM_ENVIO_ORC" integer,
    "DIAS_LIMITE" integer,
    "TEXTO_RODAPE" text
);

-- ---------------------------------------------------------------------
-- VEICULOS  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS" CASCADE;
CREATE TABLE "VEICULOS" (
    "PLACA" varchar(7) NOT NULL,
    "COR" varchar(50),
    "DESCRICAO" varchar(50),
    "NUM_DUT" varchar(50),
    "NUM_CHASSI" varchar(50),
    "PRAZO_REVISAO" date,
    "PRAZO_TROCA_OLEO" integer,
    "PRAZO_LAVAGEM" date,
    "PRAZO_EMPLACAMENTO" date
);

-- ---------------------------------------------------------------------
-- VEICULOS_DOCUMENTACAO_01  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_DOCUMENTACAO_01" CASCADE;
CREATE TABLE "VEICULOS_DOCUMENTACAO_01" (
    "COD_DOC" integer,
    "DATA" date,
    "PLACA" varchar(7),
    "TIPO" integer,
    "VALOR" numeric(18,4),
    "SITUACAO" integer,
    "DESCRICAO" text,
    "RELAT_ANO" integer
);

-- ---------------------------------------------------------------------
-- VEICULOS_OCORRENCIA_01  (8 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_OCORRENCIA_01" CASCADE;
CREATE TABLE "VEICULOS_OCORRENCIA_01" (
    "COD_OCORRENCIA" integer,
    "DATA" date,
    "PLACA" varchar(7),
    "TECNICO" integer,
    "TIPO" integer,
    "VALOR" numeric(18,4),
    "SITUACAO" integer,
    "DESCRICAO" text
);

-- ---------------------------------------------------------------------
-- VEICULOS_OFICINAS  (12 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_OFICINAS" CASCADE;
CREATE TABLE "VEICULOS_OFICINAS" (
    "COD_OFIC" integer,
    "NOM_OFIC" varchar(100),
    "CGC_OFIC" varchar(20),
    "END_OFIC" varchar(60),
    "NUM_OFIC" varchar(10),
    "COMP_OFIC" varchar(50),
    "BAI_OFIC" varchar(50),
    "CID_OFIC" varchar(20),
    "CEP_OFIC" varchar(10),
    "FONE_OFIC" varchar(40),
    "EMAIL_OFIC" varchar(50),
    "CONTATO_OFIC" varchar(25)
);

-- ---------------------------------------------------------------------
-- VEICULOS_REVISAO_01  (9 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_REVISAO_01" CASCADE;
CREATE TABLE "VEICULOS_REVISAO_01" (
    "COD_REVISAO" integer,
    "PLACA" varchar(7),
    "DATA" date,
    "KM_ATUAL_REV" numeric(18,4),
    "KM_PROX_REV" numeric(18,4),
    "VALOR_REV" numeric(18,4),
    "OBSERVACAO" text,
    "PRAZO_REVISAO" integer,
    "PRAZO_TROCA_OLEO" integer
);

-- ---------------------------------------------------------------------
-- VEICULOS_REVISAO_02  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_REVISAO_02" CASCADE;
CREATE TABLE "VEICULOS_REVISAO_02" (
    "COD_REVISAO" integer NOT NULL,
    "ITEM" integer NOT NULL,
    "DESC_SERVICO" varchar(80),
    "QUANTIDADE" integer,
    "VALOR_UNIT_SERV" numeric(18,4),
    "VALOR_TOTAL_SERV" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- VEICULOS_SERV_GERAIS_01  (12 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_SERV_GERAIS_01" CASCADE;
CREATE TABLE "VEICULOS_SERV_GERAIS_01" (
    "COD_SERVICO" integer,
    "TIPO" integer,
    "PLACA" varchar(7),
    "DATA" date,
    "KM_ATUAL" numeric(18,4),
    "KM_PROX_SERV" numeric(18,4),
    "LOCAL" integer,
    "ATENDENTE" varchar(50),
    "FUNCIONARIO" integer,
    "OBSERVACAO" text,
    "PRAZO_REVISAO" integer,
    "PRAZO_TROCA_OLEO" integer
);

-- ---------------------------------------------------------------------
-- VEICULOS_SERV_GERAIS_02  (6 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_SERV_GERAIS_02" CASCADE;
CREATE TABLE "VEICULOS_SERV_GERAIS_02" (
    "COD_SERVICO" integer NOT NULL,
    "ITEM" integer NOT NULL,
    "DESC_SERVICO" varchar(80),
    "QUANTIDADE" integer,
    "VALOR_UNIT_SERV" numeric(18,4),
    "VALOR_TOTAL_SERV" numeric(18,4)
);

-- ---------------------------------------------------------------------
-- VEICULOS_SIT_PAGTO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_SIT_PAGTO" CASCADE;
CREATE TABLE "VEICULOS_SIT_PAGTO" (
    "COD_TIPO" integer NOT NULL,
    "NOM_TIPO" varchar(50)
);

-- ---------------------------------------------------------------------
-- VEICULOS_TIPO_DOC  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_TIPO_DOC" CASCADE;
CREATE TABLE "VEICULOS_TIPO_DOC" (
    "COD_TIPO" integer NOT NULL,
    "NOM_TIPO" varchar(50)
);

-- ---------------------------------------------------------------------
-- VEICULOS_TIPO_OCORRENCIA  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_TIPO_OCORRENCIA" CASCADE;
CREATE TABLE "VEICULOS_TIPO_OCORRENCIA" (
    "COD_TIPO" integer NOT NULL,
    "NOM_TIPO" varchar(50)
);

-- ---------------------------------------------------------------------
-- VEICULOS_TIPO_SERVICO  (2 colunas)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS "VEICULOS_TIPO_SERVICO" CASCADE;
CREATE TABLE "VEICULOS_TIPO_SERVICO" (
    "COD_TIPO" integer NOT NULL,
    "NOM_TIPO" varchar(50)
);

-- =====================================================================
-- Fim do DDL
-- =====================================================================

