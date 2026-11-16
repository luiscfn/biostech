# Regras do módulo Chamados / Ordens de Serviço

**Data:** 16/11/2026
**Projeto:** `C:\desenvolvimento\PROJETOS_PHP\biostech`
**Origem:** `SISBIOSD_01.mdb` (dados) + `BIOSTECH_A2003H.mdb` (front-end Access 2003)
**Status:** proposta para aprovação — **nada foi implementado**

---

## 0. Como este documento foi levantado

As regras **não estão no VBA**. O Access guarda a maior parte da lógica em **consultas salvas**,
chamadas por `DoCmd.OpenQuery`. Por isso o levantamento cruzou quatro fontes:

| Fonte | Conteúdo | Volume |
|---|---|---|
| VBA extraído (`vba/code`, `vba/forms`) | eventos, validações, mensagens, ordem das queries | 899 arquivos / 913 componentes |
| Consultas salvas (`frontend-queries.sql`) | UPDATE/INSERT/DELETE que executam a regra | 936 queries (100 relevantes) |
| Metadados das tabelas | campos, tipos, tamanhos | `backend-fields.csv` |
| Dados das tabelas de domínio | códigos e descrições reais | 15 tabelas |

Convenção: **RN-nnn** = regra de negócio; **VD-nnn** = validação/mensagem; **AC-nnn** = achado do legado.

---

## 1. Conceito central: Chamado e OS são o mesmo registro

Este é o ponto que define todo o módulo e precisa de aprovação explícita.

No legado existe **uma única tabela**, `CHAMADO_01`, e **um único formulário**, `CHAMADO_01`.
O campo `CATEGORIA` (texto) diz se aquele registro é um **Chamado** ou uma **Ordem de Serviço**:

| CATEGORIA | Significado prático |
|---|---|
| `Chamado` | registro de recepção / help desk / laboratório. Ainda não é execução técnica. |
| `Ordem Serviço` | registro de execução técnica (o "laboratório"). |

Ambos compartilham:

- a mesma numeração (`NUM_OS`), a mesma sequência e o mesmo formato;
- os mesmos subformulários (cliente, equipamento, defeitos, atendimento, intervenções,
  peças, serviços, contatos, financeiro, encerramento);
- o mesmo ciclo de status;
- o mesmo encerramento e a mesma geração de Contas a Receber.

A diferença aparece em **quatro pontos**:

1. `CHAMADO_01.Comando241` gera o **par**: de um Chamado nasce uma OS e vice-versa.
2. A tela de laboratório (`ORDEM_SERVICO_01`) só abre OS; a de recepção (`CHAMADO_01`) abre as duas.
3. Alguns relatórios/permissões são por categoria.
4. No encerramento, só `Chamado` procura orçamento dimensionado (`ORCAM_DIMEN_01`).

> **Decisão necessária (D-01):** manter o modelo de registro único com `categoria`
> (fiel ao legado, menor esforço de migração) **ou** separar `chamados` e `ordens_servico`
> em duas tabelas com numeração independente (mais "moderno", porém quebra a rastreabilidade
> 1:1 dos 2.005 registros existentes e exige reescrever todos os relatórios).
> **Recomendação:** manter registro único + `categoria`, que é o que o DDL proposto faz.

---

## 2. Entidades e relacionamentos

```
                         ┌──────────────────────┐
                         │  biostech_chamados   │  (CHAMADO_01 + 02 + 03A)
                         │  numero (PK)         │
                         │  categoria           │
                         │  cliente_id          │
                         │  status / situacao   │
                         │  fechatot            │
                         └──────────┬───────────┘
        ┌───────────────┬───────────┼───────────┬───────────────┬──────────────┐
        │ 1:1           │ 1:1       │ 1:N       │ 1:N           │ 1:N          │
┌───────▼──────┐ ┌──────▼───────┐ ┌─▼────────┐ ┌▼───────────┐ ┌─▼──────────┐
│ equipamento  │ │ atendimento  │ │interven- │ │  peças     │ │  serviços  │
│ (03 + 03B    │ │ (04 + 07)    │ │ções(05A) │ │  (06)      │ │(55/44/66)  │
│  + 11)       │ │              │ │          │ │            │ │            │
└──────────────┘ └──────────────┘ └──────────┘ └────────────┘ └────────────┘
        ┌───────────────┬───────────┬───────────────┬───────────────┐
        │ 1:N           │ 1:N       │ 1:N           │ 1:N           │
┌───────▼──────┐ ┌──────▼───────┐ ┌─▼──────────┐ ┌─▼────────────┐
│  contatos    │ │  horas       │ │  log       │ │  vínculos    │
│  (77)        │ │  (09 / 88)   │ │ (LOG_01)   │ │  (99)        │
└──────────────┘ └──────────────┘ └────────────┘ └──────────────┘

┌──────────────────┐        ┌────────────────────┐       ┌──────────────────┐
│ orçamentos       │ 1:N    │ orçamento_itens    │       │ contas_receber   │
│ (ORCAMENTO_01 +  │───────►│ (ORCAMENTO_02)     │       │ (CR_01)          │
│  01A + 01B)      │        │ orçamento_serviços │       │ 1:N baixas       │
│  num_os (FK)     │        │ (ORCAMENTO_05)     │       │ (CR_02)          │
└──────────────────┘        └────────────────────┘       └──────────────────┘
```

### 2.1 Cardinalidade real no Access

| Tabela | Relação com `CHAMADO_01` | Observação |
|---|---|---|
| `CHAMADO_02` (cliente) | 1:1 | `COD_CLI`, `COD_ESC` |
| `CHAMADO_03` (equipamento) | 1:1 | `COD_EQUIP` é o nº de série |
| `CHAMADO_03A` (textos) | 1:1 | defeito relatado/constatado, serviço, observação |
| `CHAMADO_03B` (checklist) | 1:1 | 17 campos de checklist de bancada |
| `CHAMADO_04` (atendimento) | 1:1 | 36 campos: previsão, realização, técnicos, entrega, devolução |
| `CHAMADO_04A` | — | **não existe** como tabela; é o form do atendimento |
| `CHAMADO_05` | 1:N | log de status antigo (em desuso) |
| `CHAMADO_05A` | 1:N | **intervenções** (append-only) |
| `CHAMADO_06` | 1:N | **peças** aplicadas na OS |
| `CHAMADO_06B` | — | **não existe**; é o form de totais (financeiro do encerramento) |
| `CHAMADO_07` | 1:1 | devolução do equipamento + serviço executado |
| `CHAMADO_08` | 1:N | produtos do cliente que chegaram junto (NF) |
| `CHAMADO_09` | 1:N | horas por técnico (início/fim) |
| `CHAMADO_10` / `10A` | 1:1 | plano de parcelas do encerramento / recibo |
| `CHAMADO_11` | 1:N | equipamento emprestado para backup |
| `CHAMADO_44` | 1:N | serviços **executados** |
| `CHAMADO_55` | 1:N | checklist de serviços **a executar** |
| `CHAMADO_66` | 1:N | serviços avulsos (descrição + valor) |
| `CHAMADO_77` | 1:N | contatos / follow-up |
| `CHAMADO_88` | 1:N | agenda de técnico por data |
| `CHAMADO_99` | 1:N | vínculo pai → filho (chamado ↔ OS) |
| `CHAMADO_LOG_01` | 1:N | snapshot de alteração (87 campos) |
| `ORCAMENTO_01/01A/01B/02/05` | 1:N | orçamento comercial da OS |
| `ORCAM_DIMEN_01/02` | 1:1/N | orçamento de **dimensionamento** |
| `CONTAS_RECEBER_01/02` | 1:N | títulos e baixas |

---

## 3. Domínios (valores reais do banco)

Códigos **preservados 1:1** — nenhum código será renumerado na migração.

### 3.1 `STATUS_OS` — status macro

| Código | Nome |
|---|---|
| 1 | ABERTA |
| 2 | EM ATENDIMENTO |
| 3 | PENDENTE |
| 4 | CONCLUIDA |
| 5 | ENCERRADA |
| 6 | INTERVENCAO |

`STATUS_OS_02` (5 registros) é legado — só 1..5, sem uso. `STATUS_OS_BKP` é backup.

### 3.2 `SIT_ATUAL_OS_02` — situação detalhada (15)

| Código | Nome | Uso observado |
|---|---|---|
| 1 | CONSERTO EM ANDAMENTO | |
| 2 | AGUARDA APROVAÇÃO | |
| 3 | ENVIAR ORÇAMENTO | |
| 4 | AGUARDA PEÇA | |
| 5 | EM TESTE | |
| 6 | NÃO APRESENTOU DEFEITO | **encerrável** |
| 7 | CONSERTO INVIÁVEL | **encerrável** |
| 8 | ORÇAMENTO NÃO APROVADO | **encerrável** |
| 9 | CANCELADA PELO CLIENTE | **encerrável** |
| 10 | CONSERTO CONLUIDO - OK | **encerrável** (grafia do legado) |
| 11 | AGUARDA ATENDIMENTO | situação inicial na geração cruzada |
| 12 | PENDENTE | |
| 13 | CONFIRMAR ORÇAMENTO | |
| 14 | APROVADO | |
| 15 | CONCLUIDA | **encerrável** |

`SIT_ATUAL_OS_01` (1..5) espelha `STATUS_OS` e é legado.

### 3.3 `STATUS_EQUIP` — situação do equipamento (11)

| Código | Nome |
|---|---|
| 2 | PENDENTE |
| 3 | AGUARDA APROVACAO ORCAMENTO |
| 4 | AGUARDA PECA DA AUTORIZADA |
| 5 | AGUARDANDO COTAÇÃO DE PEÇA |
| 6 | AGUARDANDO CHEGADA DE PEÇA |
| 7 | EM CONSERTO |
| 8 | CONSERTADO |
| 9 | CONSERTO INVIAVEL |
| 10 | NAO APRESENTOU DEFEITO |
| 11 | DEVOLVIDO AO CLIENTE |
| 12 | EM TRANSITO |
| 13 | EQUIPAMENTO NOVO / NAO UTILIZADO |

*(código 1 não existe)*

### 3.4 `TIPO_GARANTIA`

| Código | Nome |
|---|---|
| 1 | CONTRATO |
| 2 | ORÇAMENTO |
| 3 | GARANTIA BIOSTECH |
| 4 | GARANTIA MICROSOL |
| 5 | DIMENSIONAMENTO |

### 3.5 `PRIORIDADE`

| Código | Nome |
|---|---|
| 1 | ALTA |
| 2 | MÉDIA |
| 3 | NORMAL |

### 3.6 `TIPO_RD01` — segmento (usado em `CHAMADO_04.SEGMENTO`)

| Código | Nome |
|---|---|
| 1 | INFORMÁTICA |
| 2 | SEGURANÇA |

### 3.7 Outros domínios usados pelo módulo

| Tabela | Registros | Uso |
|---|---|---|
| `FORMA_RECEBIMENTO_01` (`COD_RECEB`) | 8 | forma de recebimento do título (8 = **SEM VALOR A RECEBER**) |
| `FORMA_RECEBIMENTO_02` (`COD_FORM_REC`/`COD_DOC_REC`) | 18 | documento fiscal |
| `CODICAO_PAGTO` | 27 | condição de pagamento |
| `TIPO_PAGTO` | 33 | tipo de pagamento |
| `STATUS_PAGAMENTO` | — | status de pagamento |
| `TIPO_EQUIPAMENTO` | 80 | tipo de equipamento |
| `SETORES` | 11 | setor (já migrado em `biostech_setores`) |

**Achado AC-01:** os códigos de `FORMA_RECEBIMENTO_01` não são contíguos e o "8" é uma
**exceção de negócio** (sem valor a receber), não uma forma de recebimento. No novo modelo
ela vira um campo booleano (`sem_valor_receber`) **além** do código preservado.

**Achado AC-02:** `CHAMADO_01.COBRANCA` (usado na validação "Informe o Tipo de Cobrança")
aponta para `CHAMADO_04A.TIPO_GARANTIA`, mas o campo existe em `CHAMADO_03.TIPO_GARANTIA`.
A validação funciona por acidente de binding.

---

## 4. Numeração

### 4.1 `NUM_OS` — `Contador(DATA)` em `gera_os`

```text
aux = DLookup("[contador]", "TB_DATA", "DATA = #yyyy/mm#")
se aux = 0:  INSERT TB_DATA (competencia, 1)      → NUM_OS = yyyy + mm + "0001"
senão:       UPDATE TB_DATA SET contador+1        → NUM_OS = yyyy + mm + Right("0000" & aux+1, 4)
```

- **RN-001** O número da OS/Chamado é `yyyy` + `mm` + sequencial de 4 dígitos,
  reiniciado a cada competência (`TB_DATA` tem 1 linha por `yyyy/mm`).
- **RN-002** O limite é 9.999 por mês (o `Right(...,4)` trunca silenciosamente).
- **RN-003** A competência usada é a da **data do chamado** (`DAT_CHA`), não a data do sistema.
- **RN-004** `TB_DATA` guarda também `TB_MOVBA` (movimento bancário) e `TB_ROTA_01`
  (rotas), com contadores independentes.

**Achado AC-03 (concorrência):** `DLookup` + `RunSQL` não é atômico; dois postos podem gerar
o mesmo número. No PostgreSQL deve ser resolvido com uma tabela de numeração e
`UPDATE ... RETURNING` dentro da mesma transação:

```sql
UPDATE biostech_numeracao
   SET ultimo = ultimo + 1
 WHERE tipo = 'OS' AND competencia = '2026-11'
RETURNING ultimo;      -- se 0 linhas: INSERT ... (tipo, competencia, 1)
```

### 4.2 Outras numerações

| Objeto | Regra legada | Proposta |
|---|---|---|
| Orçamento (`NUM_ORC`) | `ULTORC + 1` (tabela de 1 linha `ULTORC`) | `biostech_numeracao` tipo `ORC`, sequencial global |
| Conta a Receber (`CODIGO`) | `ULTCR + 1` (tabela `ULTCR`) | tipo `CR` |
| Recibo (`CHAMADO_10.NRECPAG`) | campo próprio, sequencial | tipo `RECIBO` |
| Intervenção (`CHAMADO_05A.NUMOV`) | autonumeração Access | `serial` |
| Peça (`CHAMADO_06.NUMREQ`) | **último + 1 por OS** (validado na tela) | `numero_item` por OS, gerado no servidor |

---

## 5. Ciclo de vida e fluxo de status

### 5.1 Campos de controle

| Campo | Significado |
|---|---|
| `STATUS` (1..6) | status macro |
| `SIT_ATUAL_02` (1..15) | situação detalhada |
| `FECHATOT` | `'S'` = encerrado; `'A'` = reaberto; `NULL` = aberto |
| `FECHAFIN` | `'S'` = financeiro bloqueado; `'N'` = liberado |
| `BAIXA_AUTOMATICA` | `'S'` = baixa de estoque já executada |
| `APAGA` | marca de exclusão lógica (pouco usada) |
| `OS_GERADA` / `DATADA` | número e data do registro gerado no par Chamado↔OS |
| `QTDE_PARC` | nº de parcelas do encerramento |

### 5.2 Diagrama

```
                        ┌─────────────────────────────┐
   novo registro ──────►│ STATUS=1 ABERTA             │
   (numeração Contador) │ SIT_ATUAL_02=11 (aguarda)   │
                        │ FECHATOT=NULL               │
                        └──────────────┬──────────────┘
                                       │ F_MUDA_STATUS_OS_01/02
                                       │ (2,3,4,6 — nunca 1 nem 5)
                        ┌──────────────▼──────────────┐
                        │ 2 EM ATENDIMENTO            │◄────┐
                        │ 3 PENDENTE                  │     │ nova mudança
                        │ 4 CONCLUIDA                 │─────┘
                        │ 6 INTERVENCAO               │
                        └──────────────┬──────────────┘
                                       │ encerramento (CHAMADO_07A)
                                       │ exige SIT_ATUAL_02 ∈ {6,7,8,9,10,15}
                        ┌──────────────▼──────────────┐
                        │ STATUS=5 ENCERRADA          │
                        │ FECHATOT='S'                │
                        │ DATA_ENCERRAMENTO           │
                        └──────────────┬──────────────┘
                          cancelar ◄───┤───► reabrir (ABRE_OS)
                     STATUS=4          │      FECHATOT='A'
                     FECHATOT=NULL     │      (permanece STATUS=5)
                     apaga CR          │
```

- **RN-010** A mudança de status manual (`F_MUDA_STATUS_OS_01/02`) oferece **apenas 2, 3, 4 e 6**
  (a query filtra `CODSTATUS <> 1 AND CODSTATUS <> 5`). ABERTA e ENCERRADA não são alcançáveis
  pela tela.
- **RN-011** A mudança manual grava, no mesmo ato: `STATUS`, `SIT_ATUAL_02`, responsável
  (`NOVO_RESP`), dados de atendimento (`DT_REAL_ATEND`, `HR_REAL_ATEND`, `HR_REAL_ATEND_FIM`)
  e, quando há hora inicial/final, `CHAMADO_09` (horas por técnico).
- **RN-012** `C_ATUALIZA_STATUS_OS_ORC_01` grava `STATUS = 20` — código **inexistente** em
  `STATUS_OS`. Ver AC-04.
- **RN-013** O encerramento é a **única** operação que leva a `STATUS=5` (`C_ATUALIZA_STATUS_OS_10A`).
- **RN-014** Cancelar encerramento volta para `STATUS=4` e `FECHATOT=NULL` — **não restaura o
  status anterior**. Quem estava em 2 ou 6 volta como CONCLUIDA. Ver AC-05.
- **RN-015** Reabrir (`ABRE_OS`) **não muda `STATUS`** (continua 5): apenas `FECHATOT='A'`.
  A tela de OS reaberta é liberada porque a trava da tela testa `FECHATOT`, não `STATUS`.

**Achado AC-04:** `STATUS=20` não existe no domínio. A query é disparada em
`F_ORCAMENTO_PESQ_03` e provavelmente deixava a OS num estado "fantasma" nos relatórios.
**Decisão necessária (D-02):** eliminar o `STATUS=20` (recomendado) ou criar o código.

**Achado AC-05:** o cancelamento de encerramento perde o status anterior. No novo modelo,
recomendo gravar `status_anterior` em `biostech_chamados` no encerramento e restaurá-lo.

---

## 6. Regras por operação

### 6.1 Abertura do registro

- **RN-100** Um registro nasce com `NUM_OS` gerado na competência de `DAT_CHA`
  (ver §4.1), `DAT_CHA` = hoje, `HOR_CHA` = agora, `CATEGORIA` = `Chamado` ou `Ordem Serviço`,
  `STATUS=1`, `SIT_ATUAL_02=11` (na geração cruzada; ver D-03).
- **RN-101** `USUARIO_CADASTRO` / `DATA_CADASTRO` recebem o usuário logado e o timestamp.
  Na geração cruzada a query `C_INCLUI_GERA_OS_01` **não** copia esses campos: um `UPDATE`
  posterior grava (só no caso Chamado→OS; o caminho OS→Chamado **esquece**).
  Ver AC-06.
- **RN-102** A gravação do registro principal pede confirmação
  ("Confirma gravação do(a) {CATEGORIA}?").
- **RN-103** `STATUS` é obrigatório.
- **RN-104** `TIPO_GARANTIA` (rótulo de tela "Tipo de Cobrança") é obrigatório.
- **RN-105** O acesso ao registro é por pesquisa (`CHAMADO_PESQ_01/02/03`). Registros com
  `FECHATOT='S'` abrem em formulário **somente leitura** (`CHAMADO_01E`).
- **RN-106** Em registro encerrado (`FECHATOT='S'`) ficam travados: `NUM_OS`, `NUM_OS_PARC`,
  `DAT_CHA`, `HOR_CHA`, `STATUS`, `ORC`; exclusão desabilitada; botão de encerramento desabilitado.
- **RN-107** `ORC` é preenchido automaticamente com o `NUM_ORC` do orçamento da OS e travado
  quando existe orçamento.

**Achado AC-06 (datas sujas):** existem no legado datas em 1899, 0202, 0209, 1976, 2106,
7201 e 7202. A normalização precisa de **quarentena**: importar como `NULL` e registrar o
valor original em `_legado_raw` para conferência manual.

**Decisão necessária (D-03):** o código não define `SIT_ATUAL_02` para registro criado
manualmente na tela (só a geração cruzada grava 11). Confirmar se o correto é 11 sempre.

### 6.2 Cliente e equipamento

- **RN-110** O registro tem **exatamente um** cliente (`CHAMADO_02.COD_CLI`).
  `COD_ESC` é o escritório/unidade do cliente.
- **RN-111** O equipamento é identificado pelo **número de série** (`CHAMADO_03.COD_EQUIP`,
  texto 30), que é a PK de `EQUIPAMENTO`. O equipamento pode não existir no cadastro.
- **RN-112** O botão de histórico de equipamento exige número de série preenchido
  ("Não existe Número de Série do Equipamento informado nesta Tela").
- **RN-113** `TIPO_GARANTIA`, `AUTORIZADA`, `VOLTAGEM`, `VOLTSAIDA`, `ACESSORIOS`,
  `LISTA_EQUIP`, `NUMPAT`, `NF_CHEGADA`, `RECOLHIDO` são do equipamento **naquele atendimento**
  (não do cadastro).
- **RN-114** Garantia Biostech: 90 dias contados de `CHAMADO_07.DT_DEVOLUCAO`
  (query `C_ULTIMO_ATEND_01`). Se o último atendimento tem devolução há menos de 90 dias,
  o equipamento está em garantia.
- **RN-115** Equipamento emprestado para backup (`CHAMADO_11` com `DATA_DEVOLUCAO` nula)
  **bloqueia** novo empréstimo: "Equipamento Encontra-se emprestado para Backup. Veja a O.S Nº …".
- **RN-116** `CHAMADO_11` registra empréstimo e devolução (datas, acessórios). Datas de outro
  ano geram alerta, não bloqueio.
- **RN-117** `CHAMADO_08` registra os produtos que chegaram com o equipamento (NF do cliente);
  o mesmo produto não pode repetir na mesma OS.
- **RN-118** Checklist de bancada (`CHAMADO_03B`): 17 flags (vírus, software, disquete, CD/DVD,
  tensão, cabos, abertura indevida, não cancela chamado, outros) + dados de rede
  (`REDE_FN`, `REDE_FT`, `REDE_NT`) e estabilizador (marca, potência, kVA).

### 6.3 Atendimento e datas

- **RN-120** `DT_REAL_ATEND` ≥ `DAT_CHA`
  ("Data do Atendimento está menor que a Data do Chamado").
- **RN-121** `DT_REAL_SOLUCAO` ≥ `DT_REAL_ATEND`
  ("Data da conclusão não pode ser MENOR que a data de atendimento").
- **RN-122** `DATA_DEVOLUCAO` ≥ `DT_REAL_ATEND` **e** ≥ `DT_REAL_SOLUCAO`.
- **RN-123** Qualquer data de atendimento/intervenção/empréstimo com ano diferente do ano
  corrente gera **alerta** (não bloqueia) — é o mecanismo que deixou entrar as datas sujas.
- **RN-124** `TEC_ABRIU_OS` (responsável pela abertura) é obrigatório no primeiro `BeforeUpdate`
  de um novo chamado, e deve ser o usuário logado
  ("Responsável está diferente do usuário logado…").
- **RN-125** `TEC_CONCLUIU` deve ter **função de técnico** no cadastro funcional
  (`FUNCIONARIOS_03.FUNCAO`), senão: "Funcionário informado não tem Função de Técnico".
- **RN-126** `TECNICO_RESP_ATEND`, `TEC_ABRIU_OS` e datas de atendimento ficam travados quando
  `FECHATOT='S'`.
- **RN-127** `PAGO_RECOLHIMENTO` só existe se `VALOR_RECOLHIMENTO` estiver preenchido (idem
  entrega); ao limpar o valor, o "pago" é apagado com aviso.
- **RN-128** `SEGMENTO` (Informática/Segurança) é obrigatório na OS de laboratório
  (`ORDEM_SERVICO_01`), não no chamado.

**Achado AC-07:** a validação de função do técnico é
`If FUNCAO < 13 And FUNCAO > 14 Then` — condição **impossível**, nunca bloqueia.
A intenção era "função diferente de 13 e 14".

### 6.4 Intervenções (`CHAMADO_05A`) — append-only

- **RN-130** Para gravar uma intervenção são obrigatórios: **data**, **hora inicial**,
  **técnico** e **descrição**.
- **RN-131** O técnico informado tem de ser o usuário logado
  (`CODIFUNC = VCODFUN`): "Técnico Informado está diferente do usuário atual".
- **RN-132** Não pode existir duas intervenções com a mesma `DATAMOV` + `HORAMOV` na mesma OS.
- **RN-133** Intervenção já preenchida fica **travada e não pode ser excluída**
  (`AllowDeletions = False`). É o registro imutável do que foi feito.
- **RN-134** A gravação pede confirmação; se o usuário recusar, o registro é desfeito (Undo).
- **RN-135** `HORA_FIM`/`RESOLVEU` são preenchidos em tela separada (`CHAMADO_05A1`).

**Achado AC-08:** a trava é só de tela. Não há proteção no banco — qualquer query pode
alterar/apagar. No novo modelo a intervenção será imutável por *trigger* ou por ausência de
`UPDATE/DELETE` no repositório.

### 6.5 Peças e estoque (`CHAMADO_06`)

- **RN-140** O mesmo produto **não pode repetir** na mesma OS.
- **RN-141** Produto com estoque zero é bloqueado: "Produto está com Estoque Zerado".
- **RN-142** `QTDE` ≤ `ESTANT` (estoque atual) — bloqueia acima:
  "Quantidade Maior que a disponível no estoque. Estoque atual --> N".
- **RN-143** `VALTOT = QTDE * VALUNIT`, recalculado na saída dos campos.
- **RN-144** O sequencial (`NUMREQ`) deve ser o último cadastrado + 1 e não pode repetir na OS.
- **RN-145** `QTDE_EXCESSIVA` (quantidade acima do estoque, liberada por exceção) só é
  editável para os funcionários **1 (VERA)** e **21 (JUNIOR)**. Regra hardcoded no VBA.
- **RN-146** `VNIVEL = 2` não vê `VALUNIT`, `VALTOT` nem o total de peças.
- **RN-147** Excluir peça **não** devolve ao estoque: as queries de devolução estão comentadas
  no código (`C_EXCLUI_SAI_PROD_01A` é chamada, mas os UPDATEs de estoque dentro dela estão
  comentados). Ver AC-09.

**Baixa de estoque — dois caminhos diferentes:**

| | Manual (`Comando286`) | No encerramento (`CHAMADO_07A`) |
|---|---|---|
| Permissão | exige usuário em `ACESSOALMOX` | nenhuma |
| Bloqueio `VNIVEL=2` | sim | não |
| Já baixado (`BAIXA_AUTOMATICA='S'`) | bloqueia | não verifica |
| Estoque suficiente | **exige** `QTDE_01 >= QTDE` | **não verifica** (checagem comentada) |
| Efeito | `QTDE_01 -= QTDE`, `QTDE_03 += QTDE` | idem |
| Marca | `QTDE_ATENDIDA`, `SAIU_ESTOQUE='S'`, `DATA_BAIXA` | idem |
| Flag final | — | `BAIXA_AUTOMATICA='S'` |

- **RN-148** A baixa manual é idempotente por OS (`BAIXA_AUTOMATICA`), mas a automática
  **pode deixar estoque negativo**. Decisão necessária (D-04).
- **RN-149** `QTDE_03` é o "estoque consumido/saídas".

**Achado AC-09:** excluir peça não devolve ao estoque. Se a peça já saiu
(`SAIU_ESTOQUE='S'`), o saldo fica errado. No novo modelo, exclusão de peça baixada será
proibida (estorno explícito, com movimento).

**Achado AC-10:** o laço de baixa no encerramento percorre `NUMREQ` de `INICIO` a
`FINAL` **incrementalmente** (`INICIO = INICIO + 1`), assumindo sequência sem buracos.
Com peça excluída no meio, a baixa para antes da última peça.

### 6.6 Serviços

Três tabelas com o mesmo formato, papéis distintos:

| Tabela | Papel |
|---|---|
| `CHAMADO_55` | checklist do que **deve** ser feito (`CONCLUIDO` S/N) |
| `CHAMADO_44` | o que **foi** feito (recebe cópia de `55` com data e técnico) |
| `CHAMADO_66` | serviço avulso com quantidade e valor |

- **RN-150** Ao marcar `CONCLUIDO='S'` em `CHAMADO_55` o sistema pergunta
  "Transferir para o campo Serviço Executado?"; se sim copia para `CHAMADO_44`
  (gravando `DATA` e `TECNICO`) e **apaga** de `CHAMADO_55` (`C_INCLUI_CHAMADO_44` +
  `C_EXCLUI_CHAMADO_55`); se não, volta para `'N'`.
- **RN-151** `NUMITEM`/`NUM_ITEM` é obrigatório e sequencial por OS.
- **RN-152** Em `CHAMADO_66`: `QTDE` e `VALUNIT` > 0, `VALTOT = QTDE * VALUNIT`.
- **RN-153** O total de serviços (`CHAMADO_66.VALTOT`) alimenta `VALSERV` no encerramento.

**Achado AC-11:** o checklist `55` é apagado ao concluir, então não há histórico do que
estava previsto. No novo modelo, `biostech_chamado_servicos` terá `situacao`
(previsto/executado) e **não** apagará nada.

### 6.7 Contatos / follow-up (`CHAMADO_77`)

- **RN-160** Contato exige: data, hora, descrição, responsável, contato e próximo contato.
- **RN-161** O responsável deve ser o usuário logado.
- **RN-162** Não pode repetir data+hora na mesma OS.
- **RN-163** `DATA_PROX_CONT` ≥ hoje e `HORA_PROX_CONT` ≥ agora.
- **RN-164** `CONCLUIDO='S'` encerra o follow-up.

### 6.8 Horas por técnico (`CHAMADO_09` / `CHAMADO_88`)

- **RN-170** `CHAMADO_09`: `TECNICO`, `DATA_INICIAL`, `DATA_FINAL`, `HORA_INICIAL`.
  Alimentado pela mudança de status e finalizado (`C_FINALIZA_DATA_CHAMADO_09_0x`).
- **RN-171** `CHAMADO_88` (agenda): não pode haver duas entradas com a mesma `DATA_INI`
  para o mesmo técnico ("Data de Início já cadastrada para este técnico").
- **RN-172** `CHAMADO_88` exige `CODIGO` (sequencial) antes de `DATA_FIM`.

### 6.9 Orçamento (`ORCAMENTO_01`)

- **RN-180** Pré-requisitos: `NUM_OS` preenchido, `VALMAT + VALMOBRA > 0`, **nenhum**
  orçamento existente para a OS (checado no campo `ORC` **e** em `ORCAMENTO_01`).
- **RN-181** Numeração `ULTORC + 1`; `DATA` = hoje; `ATENCAO` = nome do cliente.
- **RN-182** Estrutura: `01` (cabeçalho), `01A` (cliente + equipamento + NF),
  `01B` (valores, percentuais, prazos, garantia, validade, assinatura),
  `02` (itens/peças), `05` (serviços).
- **RN-183** Ao final, `CHAMADO_01.ORC` recebe o número e fica travado.
- **RN-184** O orçamento pode ser **aprovado** (`APROVADO`/`NAPROVADO`, `DATA_APROVACAO`,
  `DATA_ENVIO`) ou ficar pendente.
- **RN-185** `ORCAMENTO_01B` guarda os percentuais que compõem o preço:
  `PERC_MAO_OBRA`, `PERC_SERVICO`, `PERC_NOTA_FISCAL`, `PERC_MAT_FIXACAO`,
  `PERC_ACRESCIMO_CUSTO`, `PERC_DESCONTO_ITEM`, `PERC_ACRESCIMO_ITEM`,
  `VALOR_CUSTOS_INDIRETOS`, `VALOR_MAXIMO_CLIENTE`, `VALMAT_ORIGINAL`.
  Os valores `_ORIGINAL` preservam o custo antes do markup.
- **RN-186** Orçamento **dimensionado** (`ORCAM_DIMEN_01/02`) é outro produto: planta de
  dimensionamento com itens comerciais e não comerciais, valores de compra e venda.
  É referenciado por `ORCAMENTO_01.NUM_ORC_DIMEN`.
- **RN-187** **Regra crítica do encerramento:** se `TOTAL > 0` e a OS não tem orçamento
  (nem dimensionado, para categoria Chamado, nem comum), o sistema **oferece** gerar um
  orçamento já **APROVADO** para permitir o encerramento; se o usuário recusar,
  **o encerramento é cancelado**.

**Achado AC-12:** no encerramento o orçamento é criado por `C_INCLUI_ORCAMENTO_01C/01A/03/04/02`
— um conjunto **diferente** do botão "Gerar Orçamento" (`01/01A/01B/02/05`). O caminho do
encerramento **não cria `01B`** (valores comerciais) nem `05` (serviços), e
`C_INCLUI_ORCAMENTO_01B` **não existe** nas queries salvas.

### 6.10 Parcelas (`CHAMADO_10` / `10A`)

- **RN-190** 1 a 12 parcelas (`TOTPARC`); fora disso bloqueia.
- **RN-191** A 1ª parcela pode ter **0 dias** de prazo; as demais **≥ 1 dia**.
- **RN-192** Prazos (`QTDIASn`) e vencimentos (`DVENCn`) **não podem retroceder** em relação
  à parcela anterior.
- **RN-193** `DVENCn = DATAVENC + QTDIASn` (calculado).
- **RN-194** `VALPARC` distribui `VALTOT / QTDPARC` com arredondamento de 2 casas; a diferença
  de centavos vai **para mais na 1ª** parcela e **para menos na última**
  (função `VALPARC` em `funcoes_aux`).
- **RN-195** `DATAVENC` (emissão) não pode ser menor nem maior que hoje — na prática exige
  **hoje**.
- **RN-196** `DVENC` ≥ `DATAPAG`.
- **RN-197** `DOC_REC = 9` (SEM VALOR A RECEBER) só é permitido com total zero.
- **RN-198** Se a soma das parcelas divergir do total, o sistema **recalcula** e avisa.
- **RN-199** `CHAMADO_10` guarda também flags `TEM_MAT_TRABALHO` e `MATERIAL_DEVOLVIDO`.

### 6.11 Encerramento (`CHAMADO_07A`) — a operação mais crítica

Sequência **exata** de validações e efeitos:

**Pré-condições (bloqueiam):**

1. **RN-200** Pelo menos **1 intervenção** (`CHAMADO_05A`) — "Deve haver pelo menos uma intervenção".
2. **RN-201** `SIT_ATUAL_02 ∈ {6, 7, 8, 9, 10, 15}` — "Situação Atual do(a) {CATEGORIA} incoerente
   com o Encerramento".
3. **RN-202** Se `FORMA_REC <> 8`: `TOTAL > 0` — "Informe o Valor do Pagamento na aba do Financeiro".
4. **RN-203** `DATA_CONCLUSAO` e `HORA_CONCLUSAO` obrigatórias.
5. **RN-204** Se `FORMA_REC <> 8`: ao menos a **1ª parcela** informada.
6. **RN-205** Se `TOTAL > 0` e não há orçamento: oferece gerar (aprovado) ou cancelar (RN-187).

**Efeitos (em transação):**

7. **RN-206** Se `FORMA_REC <> 8`: se **não** existe CR para a OS, cria **uma linha por parcela**
   em `CONTAS_RECEBER_01` (queries `CT01..CT12`). Se já existe, **não cria nada**
   (a comparação de valores está comentada).
8. **RN-207** Baixa de estoque de **todas** as peças da OS, sem verificar saldo (RN-148).
9. **RN-208** `FECHATOT='S'`, `USUARIO_ENCERRAMENTO`, `DATA_ENCERRAMENTO`
   (`C_FECHA_OS_01`).
10. **RN-209** `STATUS=5` (`C_ATUALIZA_STATUS_OS_10A`).
11. **RN-210** `EQUIPAMENTO.ULT_OS = NUM_OS`, `DT_ULT_OS = DAT_CHA`
    (`C_ATUALIZA_ULTIMA_OS_01`).
12. **RN-211** `ULTCR` recebe o último código usado (`C_ATUALIZA_ULTCR_CT01`).
13. **RN-212** Mensagem final diferencia "ENVIADO PARA O CONTAS A RECEBER" de
    "NÃO ENVIADO PARA O CONTAS A RECEBER".

**`TOTAL` do encerramento** (form `CHAMADO_06BA`):

```
TOTAL = VALMAT + VALSERV + VALMOBRA + VALIMPOSTO + ACRESCIMO_CUSTO − VALDESC
```

- **RN-213** `VALMAT` = total de peças (`CHAMADO_06.TOTGER`), `VALSERV` = serviços
  (`CHAMADO_66.TOTGER`), `VALMOBRA` = mão de obra digitada.
- **RN-214** Nenhum valor pode ser negativo.
- **RN-215** Se a forma de recebimento não permite valor financeiro (forma 8), os campos
  de valor são bloqueados ("Esta forma de recebimento não permite valor financeiro").

**Achado AC-13:** as validações usam `Me.TOTAL`/`Me.TOT_PARC` de **subformulários**
(`CHAMADO_06B`), que só têm valor se o subformulário estiver aberto e atualizado.
Em produção isso gera falso "Informe o Valor do Pagamento" quando o usuário encerra por
outro caminho. No novo modelo o total será **calculado no servidor**.

**Achado AC-14:** o encerramento **não grava log** em `CHAMADO_LOG_01` (só a tela principal
grava). Não há rastro de quem encerrou além de `USUARIO_ENCERRAMENTO`.

### 6.12 Contas a Receber

- **RN-220** `CODIGO = ULTCR + 1`, uma linha **por parcela**, com `PARCELA` = 1..n.
- **RN-221** `NUMPARC` = total de parcelas; `POSICAO = 1` (parcela corrente).
- **RN-222** `HISTORICO` é texto montado com: parcela/total, valor, nº da OS, nº do orçamento
  e nome do cliente.
- **RN-223** `CLIENTE` = `CHAMADO_02.COD_CLI`; `CLIENTE_NOTA` = cliente de faturamento
  (pode ser outro).
- **RN-224** `PORTADOR` = segmento (Informática/Segurança).
- **RN-225** `FORMA_REC` e `DOC_REC` vêm do plano de parcelas.
- **RN-226** `DATAEMI` = hoje; `DATAVENC` = vencimento calculado; `VALOR` = valor da parcela.
- **RN-227** Título quitado só pode ser cancelado se **não** houver baixa em
  `CONTAS_RECEBER_02` — o cancelamento de encerramento é **bloqueado** nesse caso
  ("Conta a Receber relativa a este(a) …").
- **RN-228** Cancelar encerramento **apaga** os títulos da OS (`C_ABRE_OS_01B`), sem rastro.
  Ver AC-15.

**Achado AC-15:** exclusão física de títulos financeiros. No novo modelo será
**cancelamento lógico** (`data_cancelamento`, `usuario_cancelamento`), preservando histórico.

### 6.13 Cancelar encerramento e reabrir

**Cancelar encerramento** (pesquisa `CHAMADO_PESQ_02`, `Comando276`):

- **RN-230** Só se `STATUS = 5` ("{CATEGORIA} não está Encerrado(a)").
- **RN-231** Bloqueia se existir título **quitado** para a OS.
- **RN-232** Pede confirmação; executa `C_ABRE_OS_01A` (`STATUS=4`, `FECHATOT=NULL`) e
  `C_ABRE_OS_01B` (apaga CR).

**Reabrir** (`ABRE_OS`, exige usuário/senha de `ACESSOCONTAB`):

- **RN-233** `FECHATOT='A'`; avisa: "Liberada. Ao ser encerrada novamente, **NÃO irá para o
  Contas a Receber**".
- **RN-234** O aviso é verdadeiro por acidente: como o título antigo foi apagado, o novo
  encerramento criaria outro — mas o fluxo real depende de `FORMA_REC`.

**Achado AC-16:** não existe **uma** operação de reabertura; existem duas com semânticas
diferentes (cancelar encerramento × reabrir), e nenhuma restaura o status anterior.
**Decisão necessária (D-05):** unificar em uma operação `reabrir` com motivo obrigatório,
registro em log e escolha de restaurar ou não o status anterior.

### 6.14 Geração cruzada Chamado ↔ OS

- **RN-240** `Comando241` gera o par a partir do registro atual, com `OS_AUX = Contador(hoje)`.
- **RN-241** Chamado → OS: categoria `Ordem Serviço`; OS → Chamado: categoria `Chamado`.
- **RN-242** O novo registro recebe: `DAT_CHA` = **hoje**, `HOR_CHA` = agora, `STATUS=1`,
  `SIT_ATUAL_02=11`, `OS_GERADA` = número de origem, `DATADA` = `DAT_CHA` da origem.
- **RN-243** Copiam-se: cliente (`CHAMADO_02`), equipamento (`CHAMADO_03` — todos os campos;
  na geração de OS o `RECOLHIDO` é forçado para `'S'`), e o bloco de atendimento
  (`CHAMADO_04`: técnico que abriu, segmento, valores de recolhimento/entrega, autorização,
  garantia, solicitante, contatos, local).
- **RN-244** Grava o vínculo em `CHAMADO_99` (`NUM_OS` = origem, `NUM_FILHO` = novo).
- **RN-245** `OS_GERADA`/`DATADA` são gravados também em `CHAMADO_03` e `CHAMADO_04`.
- **RN-246** Abre a tela do registro criado ao final.
- **RN-247** `C_INCLUI_GERA_OS_05` (tabela `CHAMADO_01A`) e `C_INCLUI_GERA_CT_05` **não são
  chamadas** e a tabela `CHAMADO_01A` **não existe**. Código morto (AC-17).
- **RN-248** O par não é uma via de mão única: qualquer um dos dois pode gerar o outro,
  e nada impede gerar duas vezes (não há verificação de `OS_GERADA` já preenchido).

**Achado AC-18:** é possível gerar OS duplicada para o mesmo chamado, criando dois filhos.
**Decisão necessária (D-06):** permitir 1:1 (recomendado, com bloqueio) ou N:1
(um chamado com várias OS, para casos de retorno).

### 6.15 OS filhas (`CHAMADO_99`)

- **RN-250** `CHAMADO_99(NUM_OS, NUM_FILHO)` liga origem e derivado.
- **RN-251** `C_VERIFICA_OS_FILHOS_01` conta filhas com `STATUS <> 5`; é usada para impedir
  encerrar/cancelar a mãe enquanto houver filha aberta.
- **RN-252** `CHAMADO_99.FECHADA` **nunca é lida nem escrita** — coluna inerte (AC-19).
- **RN-253** A exclusão do registro apaga vínculos apenas do lado `NUM_OS`; se o registro é
  filho, o vínculo do pai permanece órfão (AC-20).

### 6.16 Exclusão

- **RN-260** Exclusão só é possível com `FECHATOT <> 'S'` (a tela desabilita; o form de
  atendimento também bloqueia).
- **RN-261** Exclusão em cascata de 17 tabelas:
  `99, 88, 77, 11, 10, 09, 08, 07, 06A, 05, 04, 04A, 03B, 03A, 03, 02, 01`.
- **RN-262** Pede confirmação: "Todos os dados deste registro serão apagados. Confirma?".

**Achado AC-21 (grave):** a cascata **não apaga** `CHAMADO_06` (peças), `CHAMADO_05A`
(intervenções), `CHAMADO_44/55/66` (serviços), `ORCAMENTO_*` nem `CONTAS_RECEBER_*`.
Excluir uma OS deixa peças, serviços e financeiro órfãos. No novo modelo: exclusão lógica
(`excluido_em`, `excluido_por`) e nenhum `DELETE` físico.

### 6.17 Log de alteração (`CHAMADO_LOG_01`)

- **RN-270** Gravado em `Form_AfterUpdate`, com `TIPOMOV='A'`.
- **RN-271** É um **snapshot completo**: 87 colunas, sendo cada campo relevante duplicado
  com sufixo `_OLD` (valor anterior) + o valor atual.
- **RN-272** Inclui `DATAMOV`, `USUARIO`, `NUM_OS`, `CATEGORIA`/`CATEGORIA_OLD`.
- **RN-273** Só a tela principal (`CHAMADO_01`) e o atendimento (`CHAMADO_04A`) disparam o log;
  mudança de status, encerramento, peças, serviços e intervenções **não** geram log.
- **RN-274** `USUARIO_ULTIMA_ALTERACAO`/`DATA_ULTIMA_ALTERACAO` são atualizados por `UPDATE`
  direto antes de gravar o log.

**Achado AC-22:** o log é um retrato, não um diff; para reconstruir a linha do tempo é
preciso comparar snapshots. **Decisão necessária (D-07):** manter o snapshot (fidelidade)
ou adotar log por campo (`campo`, `valor_anterior`, `valor_novo`). Recomendação: log
por campo + snapshot opcional em JSONB, que serve para auditoria e é consultável.

### 6.18 Impressão

- **RN-280** Impressão de OS exige registro selecionado
  ("Não existe O.S/C.T selecionado para impressão").
- **RN-281** Dois formatos: `R_ORDEM_SERVICO_02` (tela/PDF) e `R_ORDEM_SERVICO_02B` (Excel).
- **RN-282** Relatório de OS encerrada usa a variante somente leitura.
- **RN-283** Recibo (`CHAMADO_10A`) só depois de gerado ("Não existe Recibo gerado para esta OS").

### 6.19 Permissões e acessos

| Controle | Efeito |
|---|---|
| `VNIVEL = 1` | libera relatórios e consultas gerenciais |
| `VNIVEL = 2` | bloqueia baixa de estoque, relatórios e pesquisa gerencial |
| `VCODFUN = 61` | bloqueado em telas específicas |
| `ACESSOALMOX.USU_ACESSO` | libera baixa manual de estoque |
| `ACESSOCONTAB` (usuário+senha) | libera reabrir OS e desbloquear financeiro |
| `VCODFUN ∈ {1, 21}` | libera campo "quantidade excessiva" |

**Achado AC-23 (importante):** no legado **1 = liberado** e **2 = restrito**. No novo sistema
o ETL mapeia `PRIVILEGIADO=2` para `admin` e `1` para `gestor` — **inverso**. Isso precisa ser
decidido antes de implementar o módulo.

**Decisão necessária (D-08):** definir os perfis do módulo:
`admin` (tudo), `gestor` (relatórios, sem baixa de estoque?), `tecnico` (só suas OS),
`recepcao` (abre chamado, sem valores), `almoxarife` (baixa de estoque).
Recomendação: criar `operador`/`tecnico` e **não** reaproveitar a semântica 1/2 do Access.

---

## 7. Tabela de validações e mensagens

Mensagens preservadas do legado (texto ajustado só na ortografia). Entre parênteses, a regra.

| # | Momento | Mensagem | Bloqueia |
|---|---|---|---|
| VD-01 | gravar OS/CT | Confirma gravação do(a) {categoria}? | não |
| VD-02 | gravar OS/CT | Informe o Status | sim |
| VD-03 | gravar OS/CT | Informe o Tipo de Cobrança: Orçamento, Contrato, Garantia Biostech ou Garantia Microsol | sim |
| VD-04 | abrir atendimento | Informe o Responsável pela abertura do(a) {categoria} | sim |
| VD-05 | atendimento | Data do Atendimento está menor que a Data do Chamado | sim |
| VD-06 | atendimento | Data da conclusão não pode ser MENOR que a data de atendimento | sim |
| VD-07 | atendimento | Data da Devolução não pode ser MENOR que a data de atendimento | sim |
| VD-08 | atendimento | Data da Devolução não pode ser MENOR que a data de solução | sim |
| VD-09 | qualquer data | O ano está diferente do ano atual. VERIFIQUE SE ESTÁ CORRETO | não (alerta) |
| VD-10 | atendimento | Responsável está diferente do usuário logado | sim |
| VD-11 | atendimento | Funcionário informado não tem Função de Técnico | sim |
| VD-12 | excluir atendimento | Ordem de Serviço Fechada. Dados não podem ser apagados | sim |
| VD-13 | equipamento | Não existe Número de Série do Equipamento informado nesta Tela | sim |
| VD-14 | empréstimo | Equipamento Encontra-se emprestado para Backup. Veja a O.S Nº {n} | sim |
| VD-15 | intervenção | Informe a Data da Intervenção | sim |
| VD-16 | intervenção | Informe a Hora Inicial da Intervenção | sim |
| VD-17 | intervenção | Informe o responsável pela Intervenção | sim |
| VD-18 | intervenção | Técnico Informado está diferente do usuário atual | sim |
| VD-19 | intervenção | Intervenção já cadastrada nesta Data e Hora | sim |
| VD-20 | intervenção | Confirma gravação da Intervenção? | não |
| VD-21 | peça | Produto já cadastrado nesta Ordem de Serviço | sim |
| VD-22 | peça | Produto está com Estoque Zerado | sim |
| VD-23 | peça | Estoque está Zerado. Estoque atual --> {n} | sim |
| VD-24 | peça | Quantidade Maior que a disponível no estoque. Estoque atual --> {n} | sim |
| VD-25 | peça | Sequencial deve ser o último cadastrado + 1 | sim |
| VD-26 | peça | Sequencial já cadastrado nesta Ordem de Serviço | sim |
| VD-27 | peça | Somente a VERA ou o JUNIOR pode liberar o campo de Quantidade Excessiva | (trava campo) |
| VD-28 | peça | Informe o produto ou tecle ESC para cancelar | sim |
| VD-29 | serviço | Informe Número do Item | sim |
| VD-30 | serviço | Informe a Descrição do Serviço | sim |
| VD-31 | serviço | Transferência efetuada com sucesso / não efetuada | não |
| VD-32 | contato | Informe a Data do contato | sim |
| VD-33 | contato | Contato já cadastrado nesta Data e Hora | sim |
| VD-34 | contato | Data não pode ser menor que a data de hoje | sim |
| VD-35 | contato | Hora não pode ser menor que a Hora atual | sim |
| VD-36 | contato | Responsável pelo Contato está diferente do usuário logado | sim |
| VD-37 | orçamento | Não pode ser gerado orçamento sem número de OS | sim |
| VD-38 | orçamento | Não existe custos para esta OS. Não pode ser gerado orçamento | sim |
| VD-39 | orçamento | Já existe orçamento para esta OS | sim |
| VD-40 | orçamento | Orçamento gerado com sucesso. Foi gerado o orçamento: {n} | não |
| VD-41 | parcelas | Informe a Quantidade de Parcelas | sim |
| VD-42 | parcelas | Quantidade de Parcelas deve ser entre 1 e 12 | sim |
| VD-43 | parcelas | Prazo de dias da {n}ª Parcela Inválido ou Data de Vencimento Não Informada | sim |
| VD-44 | parcelas | Prazo de dias ou Data de Vencimento da {n}ª Parcela NÃO pode ser Menor que o da {n-1}ª | sim |
| VD-45 | parcelas | Data de Emissão está MENOR/MAIOR que a data de hoje | (alerta) |
| VD-46 | parcelas | Data de Vencimento está MENOR que a Data de Pagamento | sim |
| VD-47 | parcelas | Valor das Parcelas diferente do Valor Total dos Serviços. Parcelas serão Recalculadas | não |
| VD-48 | parcelas | Valor está maior do que ZERO. Não pode ser SEM VALOR A RECEBER | sim |
| VD-49 | parcelas | Valor Zerado ou Menor que Zero. Não pode Parcelar para Gerar Contas a Receber | sim |
| VD-50 | encerramento | Deve haver pelo menos uma intervenção | sim |
| VD-51 | encerramento | Situação Atual do(a) {categoria} incoerente com o Encerramento | sim |
| VD-52 | encerramento | Informe o Valor do Pagamento na aba do Financeiro | sim |
| VD-53 | encerramento | Informe a Data e Hora da Conclusão | sim |
| VD-54 | encerramento | Para cadastrar no Contas a Receber pelo menos a 1ª parcela deve ser informada | sim |
| VD-55 | encerramento | Não existe Orçamento gerado para esta OS/CT. Gera Orçamento? | sim/não |
| VD-56 | encerramento | Orçamento gerado com sucesso. Orçamento ficou com STATUS APROVADO | não |
| VD-57 | encerramento | Orçamento não foi gerado. Encerramento CANCELADO | sim |
| VD-58 | encerramento | Confirma ENCERRAMENTO definitivo do(a) {categoria}? | não |
| VD-59 | encerramento | Encerramento efetuado com sucesso. ENVIADO / NÃO ENVIADO PARA O CONTAS A RECEBER | não |
| VD-60 | baixa estoque | Acesso Negado / Acesso Negado a Baixa de Estoque | sim |
| VD-61 | baixa estoque | Baixa de Estoque dos produtos desta O.S/C.T já foi executada | sim |
| VD-62 | baixa estoque | Existem Produtos que não foi dado Baixa porque estão com Estoque Zerado ou Menor que a Quantidade Solicitada | não |
| VD-63 | baixa estoque | Operação Não Confirmada. Baixa Não Foi Executada | não |
| VD-64 | cancelar encerr. | {categoria} não está Encerrado(a) | sim |
| VD-65 | cancelar encerr. | Conta a Receber relativa a este(a) {categoria} já foi quitada | sim |
| VD-66 | reabrir | Informe o Usuário / Informe a Senha | sim |
| VD-67 | reabrir | Senha Inválida ou não cadastrada | sim |
| VD-68 | reabrir | Liberada. Ao ser encerrada novamente, NÃO irá para o Contas a Receber | não |
| VD-69 | exclusão | Todos os dados deste registro serão apagados. Confirma? | não |
| VD-70 | impressão | Não existe O.S/C.T selecionado para impressão | sim |
| VD-71 | acesso | Acesso Negado / Acesso Negado. Utilize a Ordem de Serviço - Laboratório Técnico | sim |
| VD-72 | financeiro | Dados Bloqueados/Desbloqueados com Sucesso · Senha Inválida | não |

---

## 8. Achados do legado (resumo)

| # | Achado | Impacto | Tratamento proposto |
|---|---|---|---|
| AC-01 | `FORMA_REC=8` é exceção, não forma | confunde título | campo `sem_valor_receber` |
| AC-02 | `COBRANCA` aponta para campo de outra tabela | validação acidental | campo único `tipo_garantia` |
| AC-03 | numeração por `DLookup`+`UPDATE` | números duplicados | `UPDATE … RETURNING` |
| AC-04 | `STATUS=20` fora do domínio | estado fantasma | eliminar |
| AC-05 | cancelar encerramento não restaura status | status errado | gravar/restaurar `status_anterior` |
| AC-06 | datas 1899/0202/2106/7201… | relatórios e garantia errados | quarentena na migração |
| AC-07 | `FUNCAO < 13 And FUNCAO > 14` | nunca bloqueia | validar `funcao IN (13,14)` |
| AC-08 | intervenção imutável só na tela | adulteração | imutável no banco |
| AC-09 | excluir peça não devolve estoque | saldo errado | estorno explícito |
| AC-10 | baixa percorre `NUMREQ` incremental | peças não baixadas | percorrer o conjunto |
| AC-11 | checklist `55` é apagado | perde o previsto | manter com situação |
| AC-12 | encerramento cria orçamento incompleto (sem `01B`/`05`) | orçamento inconsistente | um único serviço de orçamento |
| AC-13 | total lido de subformulário | falso bloqueio | total no servidor |
| AC-14 | encerramento não gera log | sem rastro | log obrigatório |
| AC-15 | cancelar encerramento apaga CR | perda de histórico | cancelamento lógico |
| AC-16 | duas operações de reabertura | confusão | uma operação |
| AC-17 | `CHAMADO_01A`, `05B`, `06B`, `04A` não existem | código morto | ignorar |
| AC-18 | geração cruzada duplicável | OS duplicada | bloquear 1:1 |
| AC-19 | `CHAMADO_99.FECHADA` inerte | — | não migrar |
| AC-20 | exclusão deixa vínculo órfão | dados sujos | FK + cascata lógica |
| AC-21 | exclusão não apaga peças/serviços/CR | órfãos | exclusão lógica |
| AC-22 | log por snapshot | difícil consultar | log por campo + JSONB |
| AC-23 | `PRIVILEGIADO` 1/2 invertido no ETL | permissão trocada | D-08 |
| AC-24 | `NUM_OS_PARC` sem semântica documentada | campo misterioso | ver D-09 |

**Decisão necessária (D-09):** `NUM_OS_PARC` é copiado na geração cruzada e usado na pesquisa
por "HELP_DESK". Parece ser o **protocolo do help desk** compartilhado pelo par.
Confirmar com o usuário antes de migrar (ou descartar).

---

## 9. Proposta de modelo PostgreSQL

Padrão do projeto: schema configurável (`DB_SCHEMA`), prefixo `biostech_`, `integer`/`bigint`
para códigos, `numeric(15,2)` para dinheiro, `timestamptz` para auditoria, `boolean` nativo.
DDL completo em `scripts/sql/02-chamados-os.sql`.

### 9.1 Tabelas

| Nova tabela | Origem | Observação |
|---|---|---|
| `biostech_dominios` | 15 tabelas de domínio | `tipo`, `codigo`, `nome`, `ordem`, `ativo` |
| `biostech_chamados` | `CHAMADO_01` + `02` + `03A` | núcleo |
| `biostech_chamado_equipamento` | `CHAMADO_03` + `03B` + `11` | 1:1 |
| `biostech_chamado_atendimento` | `CHAMADO_04` + `07` | 1:1 |
| `biostech_chamado_intervencoes` | `CHAMADO_05A` | append-only |
| `biostech_chamado_pecas` | `CHAMADO_06` | |
| `biostech_chamado_materiais_cliente` | `CHAMADO_08` | |
| `biostech_chamado_servicos` | `55` + `44` + `66` | `tipo` = previsto/executado/avulso |
| `biostech_chamado_contatos` | `CHAMADO_77` | |
| `biostech_chamado_horas` | `09` + `88` | |
| `biostech_chamado_parcelas` | `CHAMADO_10`/`10A` | normalizado (1 linha por parcela) |
| `biostech_chamado_vinculos` | `CHAMADO_99` | `pai`, `filho` |
| `biostech_chamado_log` | `CHAMADO_LOG_01` | por campo + JSONB |
| `biostech_orcamentos` | `ORCAMENTO_01` + `01A` + `01B` | |
| `biostech_orcamento_itens` | `ORCAMENTO_02` | |
| `biostech_orcamento_servicos` | `ORCAMENTO_05` | |
| `biostech_orcamentos_dimensionamento` | `ORCAM_DIMEN_01` | |
| `biostech_orcamento_dimensionamento_itens` | `ORCAM_DIMEN_02` | |
| `biostech_contas_receber` | `CR_01` | |
| `biostech_contas_receber_baixas` | `CR_02` | |
| `biostech_equipamentos` | `EQUIPAMENTO` | |
| `biostech_estoque_saldos` | `ESTOQUE_02` | `qtd_geral`, `qtd_consumida` |
| `biostech_estoque_movimentos` | — (novo) | toda baixa/estorno |
| `biostech_numeracao` | `TB_DATA`, `ULTORC`, `ULTCR` | `tipo`, `competencia`, `ultimo` |

### 9.2 Decisões de modelagem já tomadas na proposta

1. **Códigos preservados** (`status_codigo`, `situacao_codigo`, `tipo_garantia_codigo`…)
   com `nome` desnormalizado quando o domínio é estável (`status_nome` por *join*).
   Vantagem: migração 1:1 e relatórios idênticos.
2. **Categoria como enum textual** `'Chamado' | 'Ordem Serviço'` (mantém os valores
   originais, sem acento modificado).
3. **Dinheiro em `numeric(15,2)`**, nunca `float`. O Access usava `Double`; a migração
   arredonda para 2 casas e registra a diferença em `_legado_raw` quando houver.
4. **Parcelas normalizadas**: `biostech_chamado_parcelas(chamado_numero, numero, valor,
   dias, vencimento)` — 1 linha por parcela. O Access tinha 12 grupos de colunas
   (`NPARC1..12`, `VPARC1..12`, `QTDIAS1..12`, `DVENC1..12`).
5. **Peças com rastreio de estoque**: `quantidade`, `quantidade_atendida`,
   `saiu_estoque`, `data_baixa`, `baixa_automatica`, + `movimento_id` (FK para
   `biostech_estoque_movimentos`), garantindo que toda baixa tenha movimento e possa
   ser estornada.
6. **Exclusão lógica** em todas as tabelas do módulo (`excluido_em`, `excluido_por`),
   com *unique partial index* `WHERE excluido_em IS NULL`.
7. **Imutabilidade** de `biostech_chamado_intervencoes`: sem `UPDATE`/`DELETE` no
   repositório + *trigger* que rejeita alteração.
8. **Log**: para cada update, N linhas em `biostech_chamado_log`
   (`campo`, `valor_anterior`, `valor_novo`) + um registro de evento em
   `biostech_chamado_eventos` (`tipo` = abertura/status/encerramento/reabertura…).

### 9.3 Estrutura resumida de `biostech_chamados`

```sql
CREATE TABLE {schema}.biostech_chamados (
    numero                varchar(16)  PRIMARY KEY,   -- NUM_OS
    numero_par            varchar(16),                -- NUM_OS_PARC (D-09)
    categoria             varchar(20)  NOT NULL,      -- 'Chamado' | 'Ordem Serviço'
    cliente_id            integer      NOT NULL,
    escritorio_id         integer,
    orcamento_numero      integer,                    -- ORC
    data_abertura         date         NOT NULL,      -- DAT_CHA
    hora_abertura         time,
    status_codigo         smallint     NOT NULL DEFAULT 1,
    situacao_codigo       smallint,                   -- SIT_ATUAL_02
    status_anterior       smallint,                   -- novo (AC-05)
    encerrado             boolean      NOT NULL DEFAULT false, -- FECHATOT = 'S'
    reaberto              boolean      NOT NULL DEFAULT false, -- FECHATOT = 'A'
    financeiro_bloqueado  boolean      NOT NULL DEFAULT false, -- FECHAFIN
    baixa_estoque_feita   boolean      NOT NULL DEFAULT false,
    valor_material        numeric(15,2) NOT NULL DEFAULT 0,
    valor_servico         numeric(15,2) NOT NULL DEFAULT 0,
    valor_mao_obra        numeric(15,2) NOT NULL DEFAULT 0,
    valor_imposto         numeric(15,2) NOT NULL DEFAULT 0,
    acrescimo_custo       numeric(15,2) NOT NULL DEFAULT 0,
    material_fixacao      numeric(15,2) NOT NULL DEFAULT 0,
    valor_desconto        numeric(15,2) NOT NULL DEFAULT 0,
    valor_faturado        numeric(15,2) NOT NULL DEFAULT 0,
    qtd_parcelas          smallint,
    numero_gerado         varchar(16),                -- OS_GERADA
    data_geracao          date,                       -- DATADA
    defeito_relatado      text,
    defeito_constatado    text,
    servico_executado     text,
    observacao            text,
    usuario_cadastro      varchar(50),
    data_cadastro         timestamptz  NOT NULL DEFAULT now(),
    usuario_alteracao     varchar(50),
    data_alteracao        timestamptz,
    usuario_encerramento  varchar(50),
    data_encerramento     timestamptz,
    usuario_fechamento    varchar(50),
    data_fechamento       timestamptz,
    excluido_em           timestamptz,
    excluido_por          varchar(50)
);
```

### 9.4 Garantias no banco (o que o Access não tinha)

| Regra | Implementação |
|---|---|
| RN-130/133 intervenção append-only | trigger `BEFORE UPDATE OR DELETE` |
| RN-201 situação encerrável | `CHECK (situacao_codigo IN (6,7,8,9,10,15))` só quando `encerrado` |
| RN-214 valores ≥ 0 | `CHECK` em todas as colunas monetárias |
| RN-221 parcelas 1..12 | `CHECK (numero BETWEEN 1 AND 12)` |
| RN-140 peça não repete | `UNIQUE (chamado_numero, produto_id) WHERE excluido_em IS NULL` |
| RN-101 numeração | `UPDATE … RETURNING` + `UNIQUE` em `numero` |
| RN-260/262 exclusão | sem `DELETE`; apenas `excluido_em` |

---

## 10. Telas e rotas propostas (CI 2.1.2)

| Tela | Rota | Equivale a |
|---|---|---|
| Pesquisa de chamados/OS | `/chamados` | `CHAMADO_PESQ_01` |
| Ficha do chamado/OS | `/chamados/{numero}` | `CHAMADO_01` |
| Nova OS / novo chamado | `/chamados/novo?categoria=…` | `CHAMADO_01` (novo) |
| Editar ficha | `/chamados/{numero}/editar` | `CHAMADO_01` |
| Intervenções | `/chamados/{numero}/intervencoes` | `CHAMADO_05A` |
| Peças | `/chamados/{numero}/pecas` | `CHAMADO_06` |
| Serviços | `/chamados/{numero}/servicos` | `CHAMADO_44/55/66` |
| Contatos | `/chamados/{numero}/contatos` | `CHAMADO_77` |
| Mudar status | `/chamados/{numero}/status` | `F_MUDA_STATUS_OS_01/02` |
| Encerrar | `/chamados/{numero}/encerrar` | `CHAMADO_07A` |
| Cancelar encerramento | `/chamados/{numero}/cancelar-encerramento` | `CHAMADO_PESQ_02` |
| Reabrir | `/chamados/{numero}/reabrir` | `ABRE_OS` |
| Gerar par Chamado↔OS | `/chamados/{numero}/gerar` | `Comando241` |
| Baixa de estoque | `/chamados/{numero}/baixar-estoque` | `Comando286` |
| Orçamento | `/orcamentos/{numero}` | `F_ORCAMENTO_01` |
| Imprimir OS | `/chamados/{numero}/imprimir` | `R_ORDEM_SERVICO_02` |

**Layout da ficha:** abas (Geral · Equipamento · Atendimento · Intervenções · Peças ·
Serviços · Contatos · Financeiro · Encerramento · Histórico), substituindo os 10
subformulários do Access. O botão Encerrar fica no topo, desabilitado quando `encerrado`.

---

## 11. Pendências e decisões necessárias

| # | Decisão | Opções | Recomendação |
|---|---|---|---|
| D-01 | Chamado e OS: uma tabela ou duas? | única + `categoria` / duas tabelas | **única + categoria** |
| D-02 | `STATUS=20` | eliminar / criar | **eliminar** |
| D-03 | Situação inicial de registro manual | 11 sempre / usuário escolhe | **11 + editável** |
| D-04 | Baixa automática pode negativar estoque? | sim / bloquear | **bloquear e avisar** |
| D-05 | Reabertura | unificar / manter 2 | **unificar com motivo** |
| D-06 | Geração cruzada | 1:1 / N:1 | **1:1 com bloqueio** |
| D-07 | Log | snapshot / por campo | **por campo + JSONB** |
| D-08 | Perfis do módulo | admin/gestor/tecnico/recepcao/almoxarife | **5 perfis** |
| D-09 | `NUM_OS_PARC` | migrar / descartar | **confirmar com o usuário** |
| D-10 | Escopo da 1ª entrega | ficha + intervenções + encerramento / módulo completo | **ficha + status + encerramento** |

### 11.1 Perguntas abertas para o usuário

1. Quem encerra uma OS hoje: recepção, laboratório ou ambos?
2. O que significa, na prática, `NUM_OS_PARC` ("HELP_DESK" na pesquisa)?
3. Um chamado pode ter mais de uma OS? (retorno, garantia, peça em falta)
4. Qual o fluxo correto ao cancelar encerramento: voltar para CONCLUIDA (como hoje) ou para
   o status de antes do encerramento?
5. O `STATUS=20` era usado por alguém? Ainda aparece em relatórios antigos?
6. Quais relatórios do módulo são realmente usados? (para priorizar a impressão)
7. Quem pode ver valores (`VALUNIT`/total) hoje? (`VNIVEL=2` não vê)
8. O orçamento dimensionado continua existindo no processo ou é legado?

---

## 12. Próximos passos

1. **Aprovar este documento** (ou marcar as decisões D-01..D-10).
2. Gerar `scripts/sql/02-chamados-os.sql` com o DDL proposto.
3. Estender o ETL (`scripts/etl/`) para migrar `CHAMADO_*`, `ORCAMENTO_*`,
   `CONTAS_RECEBER_*`, `EQUIPAMENTO` e `ESTOQUE_*`, com quarentena de datas (AC-06).
4. Implementar, nesta ordem: pesquisa → ficha → intervenções/peças → status →
   encerramento → orçamento/parcelas/CR.
5. Só então: impressão, relatórios gerenciais e permissões finas.

---

**Documento gerado a partir de:** `vba/code/Form_CHAMADO_*`, `frontend-queries.sql`
(100 queries), `backend-fields.csv`, `dominios.txt`, `campos_chamados*.txt`,
`mensagens.txt`, `forms_resumo.txt`.
