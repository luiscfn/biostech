# Regras do módulo Financeiro

**Projeto:** BIOSTECH web (CodeIgniter 2.1.2 · PHP 7.4.33 · PostgreSQL 16)
**Módulo:** Financeiro — contas a pagar, contas a receber, baixas e estornos,
movimentos bancários, caixa diário, cheques, balanço, cadastros de apoio,
relatórios e painel.
**Origem:** `SISBIOSD_01.mdb` / `BIOSTECH_A2003H.mdb` (Access 2003).

---

## 0. Como este documento foi levantado

- Export das tabelas legadas por `scripts/etl/export-financeiro.ps1`
  (PowerShell 32 bits + Jet OLEDB 4.0) para JSON UTF-8, conforme
  `scripts/etl/financeiro.json`.
- Carga idempotente por `scripts/etl/financeiro.php` (PDO/PostgreSQL).
- Leitura das telas e consultas do módulo no Access:
  `CONTAS_PAGAR_01/02/LOG_01`, `CONTAS_RECEBER_01..04`, `MOV_BANCARIA_01/02`,
  `MOVICAIXA1/2/3`, `BANCOS`, `AGENCIA`, `CONTA_BANCARIA`, `CARTOES`,
  `CENTRO_CUSTOS`, `NAT_OPERACAO`, `STATUS_PAGAMENTO`, `DESPESAS_FIXAS`,
  `PAGTO_CHEQUES_01` e `TB_BALANCO_FINANC_01`.
- DDL em `scripts/sql/07-financeiro.sql` e domínios em
  `scripts/sql/03-dominios-financeiro.sql`.
- Teste end-to-end em `scripts/test/financeiro.php`.

### 0.1 Nomes legados x nomes novos

| Access | PostgreSQL | Conteúdo |
|---|---|---|
| `CONTAS_PAGAR_01` | `biostech_contas_pagar` | títulos a pagar (uma linha por parcela) |
| `CONTAS_PAGAR_02` | `biostech_contas_pagar_baixas` | baixas dos títulos a pagar |
| `CONTAS_PAGAR_LOG_01` | `biostech_contas_pagar_log` | histórico de alteração por campo |
| `CONTAS_RECEBER_01` | `biostech_contas_receber` | títulos a receber ativos |
| `CONTAS_RECEBER_02` | `biostech_contas_receber_baixas` | baixas dos títulos ativos |
| `CONTAS_RECEBER_03` | `biostech_contas_receber` (`arquivada`) | títulos arquivados |
| `CONTAS_RECEBER_04` | `biostech_contas_receber_baixas` (`arquivada`) | baixas arquivadas |
| `MOV_BANCARIA_01/02` | `biostech_fin_movimentos_bancarios` | extrato bancário |
| `MOVICAIXA1` | `biostech_fin_caixa_diario` | um registro por dia de caixa |
| `MOVICAIXA2` | `biostech_fin_caixa_movimentos` | lançamentos de entrada/saída |
| `MOVICAIXA3` | — | saldo anterior (não usado; ver 6.4) |
| `BANCOS` | `biostech_fin_bancos` | bancos (código textual) |
| `AGENCIA` | `biostech_fin_agencias` | agências |
| `CONTA_BANCARIA` | `biostech_fin_contas` | contas bancárias |
| `CARTOES` | `biostech_fin_cartoes` | cartões |
| `CENTRO_CUSTOS` | `biostech_fin_centros_custo` | centros de custo |
| `NAT_OPERACAO` | `biostech_fin_naturezas_operacao` | naturezas de operação |
| `STATUS_PAGAMENTO` | `biostech_fin_status_pagamento` | status de pagamento |
| `DESPESAS_FIXAS` | `biostech_fin_despesas_fixas` | despesas fixas |
| `PAGTO_CHEQUES_01` | `biostech_fin_cheques` | cheques (tabela legada vazia) |
| `TB_BALANCO_FINANC_01` | `biostech_fin_balanco` | balanço financeiro mensal |

### 0.2 Normalizações feitas na carga (ETL)

**Contas a pagar**

- `situacao` derivada do legado: `3` quando há usuário/data de cancelamento,
  `2` quando existe baixa em `CONTAS_PAGAR_02`, senão `1` (aberta).
- `cancelado` booleano preservado em paralelo à `situacao` (ver 3.1).
- `valor_baixado` recebe `VALOR_RECEBIDO` da baixa.
- `lancamento` cai para `DATAEMI` quando `DATALANC` é inválida.
- `origem = 'legado'`, `origem_id = número` do título.
- Baixas sem título correspondente são ignoradas e contabilizadas no resumo.
- O log legado (`CAMPO` / `CAMPO_OLD`) é convertido para JSONB no formato
  `{campo: [antes, depois]}`, mantendo `legado_id`, usuário, data e tipo
  (`A`/`C`/`I`).

**Contas a receber**

- `CONTAS_RECEBER_01` (ativas) e `CONTAS_RECEBER_03` (arquivadas) são disjuntas
  e foram unificadas; `arquivada` e `total_arquivada` identificam a origem.
- O código é a **chave primária**. O Access repete códigos em parcelas de
  `CONTAS_RECEBER_03`; a primeira ocorrência mantém o código e as demais são
  renumeradas a partir do maior código existente (6 linhas renumeradas, a partir
  de 33.719). As baixas são remapeadas pelo par `(codigo, parcela)`.
- `cancelado` é derivado de usuário/data de cancelamento.
- `vliq` é preservado; quando nulo, a aplicação calcula `valor - desconto + vdesp`.
- A FK de `chamado_numero` e o índice único `(chamado_numero, parcela)` foram
  removidos: o legado tem títulos sem chamado e mais de um título por chamado.

**Movimentos bancários**

- Base `MOV_BANCARIA_02` (73.557 linhas). Os 414 movimentos existentes apenas em
  `MOV_BANCARIA_01` foram incluídos com numeração nova, de 131.083 a 131.496.
- `MOV_BANCARIA_02` não guarda saldos; o saldo anterior/atual é reaproveitado do
  movimento de negócio equivalente em `MOV_BANCARIA_01`.
- Movimentos sem data válida são ignorados e contabilizados.

**Caixa**

- `saldo_anterior` = `MOVICAIXA1.SALDOATUAL` (saldo de abertura do dia).
- `saldo_atual` é recalculado: se o dia tem uma linha de histórico contendo
  "SALDO ANTERIOR" dentro das entradas, `fechamento = entradas - saídas`;
  caso contrário, `fechamento = abertura + entradas - saídas`.
- `total_entradas` e `total_saidas` são somados de `MOVICAIXA2`.

**Cheques e MOVICAIXA3**

- `PAGTO_CHEQUES_01` estava vazia no legado: a tabela foi criada, mas nada foi
  importado.
- `MOVICAIXA3` (`DATA`, `DATNUM`, `SALDOANTERIOR`) não é usada porque o saldo de
  abertura já vem de `MOVICAIXA1.SALDOATUAL`; usá-la duplicaria a abertura.

**Balanço**

- `TB_BALANCO_FINANC_01` é recarregada integralmente (97 linhas) a cada execução.

**Domínios**

- `documento` foi corrigido para os códigos realmente usados:
  `1` BOLETO-C.N, `4` RECIBO-C.N, `5` CORTESIA, `6` COBRADO NA O.S,
  `7` COBRADO NO C.T, `8` NOTA FISCAL, `9` SEM VALOR A RECEBER,
  `11` RECIBO-S.N e `12` BOLETO-S.N.
- Novos domínios: `status_pagamento` (1 ABERTA, 2 QUITADA, 3 EMITIDA),
  `natureza_operacao` (8 naturezas) e `periodicidade` (7 periodicidades).

---

## 1. Entidades

| Tabela | Chave | Conteúdo |
|---|---|---|
| `biostech_contas_pagar` | `id` (serial) + único `(numero, parcela)` | títulos a pagar; `numero` é a numeração CP |
| `biostech_contas_pagar_baixas` | `id` + único `(numero, parcela)` | baixa do título (1 por parcela) |
| `biostech_contas_pagar_log` | `id` | log por campo em JSONB |
| `biostech_contas_receber` | `codigo` | títulos a receber; cada parcela tem código próprio |
| `biostech_contas_receber_baixas` | `id` + único `(conta_codigo, parcela)` | baixa do título a receber |
| `biostech_fin_movimentos_bancarios` | `id` + único `numero` | extrato bancário com saldo anterior/atual |
| `biostech_fin_caixa_diario` | `id` + único `data` | totais e fechamento por dia |
| `biostech_fin_caixa_movimentos` | `id` + único `(data, numero)` | lançamentos do caixa |
| `biostech_fin_cheques` | `id` | cheques emitidos/compensados |
| `biostech_fin_balanco` | `id` | balanço mensal gerado |
| `biostech_fin_bancos` | `codigo` (texto) | bancos |
| `biostech_fin_agencias` | `id` | agências por banco |
| `biostech_fin_contas` | `id` | contas bancárias |
| `biostech_fin_cartoes` | `codigo` | cartões |
| `biostech_fin_centros_custo` | `codigo` | centros de custo |
| `biostech_fin_naturezas_operacao` | `codigo` | naturezas de operação |
| `biostech_fin_status_pagamento` | `codigo` | status de pagamento |
| `biostech_fin_periodicidades` | `codigo` | periodicidades |
| `biostech_fin_despesas_fixas` | `codigo` | despesas fixas |

Volumes carregados: **14.465** contas a pagar, **13.959** baixas de CP,
**5.484** logs de CP, **2.328** contas a receber, **2.309** baixas de CR,
**73.971** movimentos bancários, **791** dias de caixa, **6.411** movimentos de
caixa e **97** balanços.

---

## 2. Numeração

| Série | Uso | Regra |
|---|---|---|
| `CP` | `contas_pagar.numero` | um número por título; todas as parcelas compartilham o número |
| `CR` | `contas_receber.codigo` | **um código por parcela** (a PK é o código) |
| `MB` | `movimentos_bancarios.numero` | um número por movimento |

- A numeração é atômica (`application/libraries/Numeracao.php`), com
  `INSERT ... ON CONFLICT ... RETURNING` via `pg_query_params`.
- Após o ETL a numeração é posicionada acima do maior código importado:
  `CP = 33.722`, `CR = 33.724`, `MB = 131.496`.
- Os testes end-to-end consomem a numeração; após a bateria atual:
  `CP = 33.727`, `CR = 33.729`, `MB = 131.506`.

---

## 3. Contas a pagar

### 3.1 Situação

| `situacao` | Significado | `cancelado` |
|---:|---|---|
| `1` | aberta (pode ser baixada) | `false` |
| `2` | baixada (possui baixa) | `false` |
| `3` | cancelada (não pode ser baixada) | `true` |

- `cancelado` é mantido em paralelo por compatibilidade com o legado; toda a
  aplicação consulta `situacao` e também trata `cancelado`.
- Não existe `situacao = 20` no legado; a carga e as telas usam apenas 1/2/3.
- A tela de detalhe mostra a situação e só oferece as ações válidas.

### 3.2 Criação e parcelamento

- Campos do título: fornecedor, portador/setor, emissão, lançamento, vencimento,
  valor, forma de recebimento, documento de recebimento, centro de custo,
  documento, histórico, banco/agência/conta, portador, retenção de caixa e data
  de caixa.
- `parcelas` é limitado a **1..12**; `intervalo_dias` padrão **30**.
- O valor é dividido com arredondamento para baixo em centavos; a última parcela
  absorve a diferença (`valor - base × (n-1)`), garantindo soma exata.
- Todas as parcelas usam o mesmo `numero` CP, `parcela` sequencial e
  `total_parcelas = n`.
- `lancamento` assume a data corrente quando não informado; `origem = 'manual'`.
- A criação grava um log `I` (inclusão) na parcela 1.

### 3.3 Edição e log

- A edição altera apenas os campos do título (não mexe em baixas).
- `_log_alteracoes()` compara campo a campo e grava JSONB
  `{campo: [antes, depois]}` na parcela correspondente, com usuário e data.
- O detalhe do título exibe a linha do tempo do log; valores antigos e novos
  são formatados quando escalares e serializados em JSON quando compostos.

### 3.4 Baixa

- Pré-condições: título existe, **não** está baixado (`situacao != 2`) e **não**
  está cancelado (`situacao != 3` e `cancelado = false`).
- Multa, juros e desconto são normalizados para `>= 0`.
- `valor_atualizado = valor do título + multa + juros`.
- `valor_pago` padrão = `valor + multa + juros - desconto`; pode ser informado
  manualmente e é limitado a `>= 0`.
- A baixa grava `biostech_contas_pagar_baixas` com `ON CONFLICT (numero, parcela)
  DO UPDATE` (idempotente), atualiza o título para `situacao = 2`,
  `valor_baixado`, `data_baixa` e `usuario_baixa`, e registra log `A`.
- Opções da tela:
  - **gerar movimento bancário**: cria um movimento tipo `D` (débito) na conta
    do título, com histórico "Baixa da conta a pagar N/P" e vínculo
    `conta_pagar_numero`/`conta_pagar_parcela`;
  - **gerar movimento de caixa**: cria uma saída no caixa do dia com o valor
    pago e vínculo pelo documento/forma de recebimento.
- A baixa inteira roda em transação: se o movimento bancário falhar, a baixa é
  desfeita.

### 3.5 Estorno

- Exige que exista baixa.
- Remove a(s) baixa(s) do título, limpa `valor_baixado`, `data_baixa` e
  `usuario_baixa`.
- Reabre como `situacao = 1`, exceto se o título estiver cancelado, quando volta
  para `situacao = 3`.
- Registra log `A` com `estorno_baixa` e usuário.
- O estorno **não** remove automaticamente movimento bancário ou de caixa
  gerados na baixa; esses lançamentos devem ser estornados/excluídos nas telas
  próprias (decisão consciente para não apagar movimentos financeiros sem
  rastro).

### 3.6 Cancelamento

- Exige perfil admin/gestor e POST.
- Só cancela títulos abertos; título já cancelado retorna "já estava cancelado".
- Grava `situacao = 3`, `cancelado = true`, usuário e data de cancelamento.
- Registra log `C` com motivo e usuário.
- Título cancelado não pode ser baixado.

### 3.7 Exclusão

- Exige perfil admin/gestor e POST.
- Bloqueada quando existe baixa ("Estorne a baixa antes de excluir o título").
- Remove o log da parcela e o título; a exclusão é definitiva.
- A tela só oferece exclusão para títulos **não baixados** (abertos ou
  cancelados).
- A ação é auditada em `biostech_auditoria`.

### 3.8 Comprovante

- `conta_pagar_comprovante/{id}` gera uma página de impressão com os dados do
  título e a relação de baixas (data, multa, juros, desconto, valor atualizado,
  valor pago e conta).

---

## 4. Contas a receber

### 4.1 Código por parcela

- `codigo` é a **chave primária** de `biostech_contas_receber`.
- No legado cada parcela é um título independente, com código próprio; o campo
  `parcela` apenas indexa 1..N. O mesmo vale para os títulos gerados pelo
  Chamados/OS e pelo Financeiro.
- Por isso a criação de um título com N parcelas consome **N códigos CR** e
  retorna a lista `codigos`; nunca repete o mesmo código.
- `num_parcelas` e `posicao` guardam o total e a posição da parcela.

### 4.2 Criação e parcelamento

- Campos: cliente, data de emissão, primeiro vencimento, valor, número de
  parcelas, intervalo em dias, documento, chamado/OS, tipo, forma e documento de
  recebimento, banco/agência/conta, portador, centro de custo, histórico,
  desconto especial, despesas (`vdesp`), alíquota e valor do ISS, líquido,
  retenção de caixa e número da nota fiscal.
- `parcelas` é limitado a **1..12**; `intervalo_dias` padrão **30**.
- Rateio idêntico ao CP: base truncada em centavos e última parcela com a
  diferença.
- `vliq` padrão = `valor - desconto + vdesp`.
- `data_emissao` assume a data corrente quando ausente.
- Cada parcela recebe `codigo` novo, `parcela`, `num_parcelas`, `posicao` e
  vencimento deslocado.

### 4.3 Baixa e estorno

- Pré-condições: título existe, não está cancelado e ainda não possui baixa.
- Multa, juros e desconto `>= 0`; `valor_atualizado = valor + multa + juros`.
- `valor_recebido` padrão = `valor + multa + juros - desconto`, limitado a
  `>= 0`.
- Grava a baixa em `biostech_contas_receber_baixas` com
  `(conta_codigo, parcela)`, atualiza `usuario_baixa`/`data_baixa`.
- Opções da tela: gerar movimento bancário tipo `C` (crédito) e/ou entrada no
  caixa do dia, com vínculo ao chamado, forma e documento de recebimento.
- O estorno remove a baixa e limpa `usuario_baixa`/`data_baixa`, sem apagar
  movimentos bancários ou de caixa gerados (mesma decisão do CP).

### 4.4 Cancelamento e exclusão

- Cancelamento (admin/gestor, POST): grava `cancelado = true`, usuário e data;
  não altera baixas.
- Exclusão (admin/gestor, POST): bloqueada se existir baixa; remove o título.
- Título cancelado continua visível para consulta e relatórios, mas não pode ser
  baixado.

### 4.5 Comprovante

- `conta_receber_comprovante/{codigo}` imprime os dados do título e as baixas
  (data, multa, juros, desconto, valor atualizado, valor recebido).

### 4.6 Faturamento

- `faturado`, `selec_faturamento`, `numero_nota_fiscal`, `fechafin` e
  `baixa_conta` foram preservados do legado.
- O módulo Financeiro **não** emite nota fiscal; o faturamento em lote/NF é
  escopo do módulo Faturamento (ver `docs/07`).
- Títulos gerados pelo encerramento de Chamado/OS continuam sendo cancelados
  logicamente quando o encerramento é cancelado.

---

## 5. Movimentos bancários

- Tipos: `C` (crédito/entrada) e `D` (débito/saída).
- `numero` vem da série `MB` ou é informado (movimentos importados).
- `saldo_anterior` = último `saldo_atual` da mesma conta com data `<=` à data do
  movimento; se não houver, usa a soma de `C - D` até a data.
- `saldo_atual = saldo_anterior + valor` para `C`, ou
  `saldo_anterior - valor` para `D`.
- Valor deve ser maior que zero; banco e conta são textuais.
- Vínculos opcionais com CP (`conta_pagar_numero`/`parcela`) e CR
  (`conta_receber_codigo`/`parcela`).
- A edição preserva o `saldo_anterior` gravado; não recalcula em cascata os
  movimentos posteriores. Ao corrigir valores, os saldos seguintes devem ser
  reexaminados (limitação consciente; o ETL trouxe os saldos do legado).
- Exclusão é definitiva e exige admin/gestor + POST.
- A lista permite busca por número/histórico e filtros por banco, conta, tipo e
  período.
- **Posição bancária** (`relatorios?tipo=posicao`): para cada conta ativa usa o
  último `saldo_atual`; se não houver movimento, usa a soma de `C - D`.

---

## 6. Caixa

### 6.1 Dia de caixa

- `biostech_fin_caixa_diario` tem um registro por data, com `saldo_anterior`
  (abertura), `saldo_atual` (fechamento), `total_entradas`, `total_saidas`,
  `fechado`, `cheque` e `dinheiro`.
- A tela `caixa_dia/{data}` abre mesmo sem registro, permitindo lançar o
  primeiro movimento; a abertura é o fechamento do dia anterior (ou zero).

### 6.2 Lançamentos

- Cada lançamento tem data, setor, documento, histórico, valor de entrada,
  valor de saída, chamado/OS, forma e documento de recebimento.
- Pelo menos um dos valores (entrada ou saída) deve ser maior que zero; ambos
  são normalizados para `>= 0`.
- `numero` é sequencial por dia (`MAX(numero) + 1`).
- Inclusão, edição e exclusão recalculam o dia.
- Exclusão exige admin/gestor + POST.

### 6.3 Fechamento e reabertura

- **Fechar** (operador liberado, POST) recalcula os totais e grava
  `fechado = true`.
- **Reabrir** (admin/gestor, POST) grava `fechado = false`.
- Tela `caixa` lista os dias com filtros por período e situação (aberto/fechado)
  e mostra o saldo atual (último `saldo_atual`).

### 6.4 Fórmula do fechamento

```
tem_abertura = existe histórico com "SALDO ANTERIOR" nas entradas do dia
fechamento   = tem_abertura
               ? entradas - saidas
               : saldo_anterior + entradas - saidas
```

- O legado lança a primeira linha do dia como "SALDO ANTERIOR" **dentro das
  entradas**; nesse caso a abertura já está embutida e não pode ser somada de
  novo.
- `MOVICAIXA3` não é usada: `MOVICAIXA1.SALDOATUAL` já é a abertura.

---

## 7. Cheques

- Situação: `1` emitido (em aberto) e `2` compensado.
- Campos: número, banco, agência, conta, valor, favorecido, emissão,
  vencimento, compensação e observação.
- Cadastro/edição/exclusão exigem admin/gestor; exclusão exige POST.
- **Compensar** grava `situacao = 2` e `data_compensacao` (admin/gestor, POST).
- Lista com busca por número/favorecido e filtros por situação e banco.
- A tabela legada `PAGTO_CHEQUES_01` estava vazia; os cheques existentes são os
  cadastrados na aplicação.

---

## 8. Balanço

- A tela lista os balanços gerados com filtro por período.
- **Gerar** (admin/gestor, POST) recebe `inicio` e `fim` e grava uma linha em
  `biostech_fin_balanco`:

| Campo | Cálculo |
|---|---|
| `bruto_faturado` | soma de `valor` das CR não canceladas com `data_emissao` no período |
| `iss_faturado` | soma de `viss` das mesmas CR |
| `desconto_faturado` | soma de `desconto_especial` das mesmas CR |
| `liquido_faturado` | `bruto_faturado - iss_faturado` |
| `bruto_faturar` | soma de `valor` das CR não canceladas, não faturadas, com vencimento no período |
| `iss_faturar` | soma de `viss` das mesmas CR |
| `liquido_faturar` | `bruto_faturar - iss_faturar` |
| `bruto_pago` | soma de `valor_pago` das baixas de CP no período, excluindo títulos cancelados |
| `juros_pago` / `multa_pago` / `desconto_pago` | somas correspondentes das baixas |
| `valor_princ_pago` | `bruto_pago - juros_pago - multa_pago + desconto_pago` |
| `bruto_pagar` | soma de `valor` das CP abertas (`situacao = 1`) com vencimento no período |
| `valor_princ_pagar` | igual a `bruto_pagar` |
| `juros_pagar` / `multa_pagar` | `0` (não há previsão de encargos em aberto no legado) |
| `multa_faturado` / `juros_faturado` | `0` (preservados para paridade de colunas) |

- `mes_ano` é gravado como `MM/AAAA`.
- **Excluir** (admin/gestor, POST) remove o balanço.
- `relatorios?tipo=balanco` lista os balanços do período.

---

## 9. Cadastros de apoio

| Tipo na URL | Tabela | Chave | Campos editáveis |
|---|---|---|---|
| `bancos` | `biostech_fin_bancos` | `codigo` **texto** | código, nome |
| `agencias` | `biostech_fin_agencias` | `id` | banco, agência, nome, endereço, bairro, CEP, cidade, UF, telefones, fax, contato |
| `contas` | `biostech_fin_contas` | `id` | banco, agência, conta, titular, valor limite, valor real |
| `cartoes` | `biostech_fin_cartoes` | `codigo` inteiro | código, nome |
| `centros` | `biostech_fin_centros_custo` | `codigo` inteiro | código, nome |
| `naturezas` | `biostech_fin_naturezas_operacao` | `codigo` inteiro | código, nome |
| `status` | `biostech_fin_status_pagamento` | `codigo` inteiro | código, nome |
| `periodicidades` | `biostech_fin_periodicidades` | `codigo` inteiro | código, nome |
| `despesas` | `biostech_fin_despesas_fixas` | `codigo` inteiro | código, nome, natureza, periodicidade, centro de custo, fornecedor, valor, dia de vencimento, observação, ativo |

Regras comuns:

- `bancos.codigo` é **textual** no legado (`dm`, `DMINS`, `237`, `237/2`); a
  comparação usa `codigo::text = ?` e o formulário usa campo texto.
- Os demais códigos são inteiros; o formulário usa `type=number`.
- Criação faz *upsert*: se a chave já existe, atualiza; senão insere.
- Em edição a chave é somente leitura e não é alterada.
- Valores monetários são convertidos de `1.234,56` para `1234.56`; booleanos
  (`ativo`) são convertidos para `true`/`false`.
- `agencias` e `contas` usam chave serial; os demais usam o código informado.
- Exclusão verifica uso e é bloqueada com mensagem específica quando o registro
  está referenciado:

| Cadastro | Bloqueia se usado em |
|---|---|
| `bancos` | agência, conta bancária, movimento, cheque |
| `contas` | movimento, cheque |
| `centros` | conta a pagar, conta a receber, despesa fixa |
| `naturezas` | despesa fixa |
| `periodicidades` | despesa fixa |
| `status` | — (sem referências no módulo) |
| `cartoes` | — |
| `agencias` / `despesas` | validação direta de referências |

- Todas as operações de cadastro exigem admin/gestor; exclusão exige POST.
- `cadastros` lista os tipos com contagem de registros.

---

## 10. Painel, relatórios e consultas

### 10.1 Painel (`/financeiro`)

Mostra:

- CP abertas: quantidade, valor e valor vencido;
- CP pagas no mês corrente (soma de `valor_baixado`);
- CR abertas: quantidade, valor e valor vencido (sem baixa e não canceladas);
- saldo atual do caixa;
- últimos dias de caixa.

### 10.2 Relatórios (`/financeiro/relatorios`)

| `tipo` | Conteúdo |
|---|---|
| `cp` | contas a pagar por vencimento, com filtro de situação |
| `cr` | contas a receber por vencimento, com filtro de situação |
| `fluxo` | fluxo de caixa: baixas de CP (D) e CR (C) no período |
| `posicao` | posição bancária por conta ativa |
| `balanco` | balanços gerados no período |

- Período padrão: primeiro ao último dia do mês corrente.
- Os relatórios usam as mesmas consultas das listas (`_where_cp`/`_where_cr`),
  garantindo paridade de filtros.
- A impressão é HTML com `@media print`, sem dependência de servidor de PDF.

### 10.3 Consultas e filtros

- CP: busca por número/documento/histórico/fornecedor; filtros por fornecedor,
  situação (`aberta`, `vencido`, `baixada`, `cancelada` ou numérica), emissão,
  vencimento e centro de custo.
- CR: busca por código/documento/histórico/cliente; filtros por cliente,
  situação (`aberta`, `vencido`, `baixada`, `cancelada`), emissão, vencimento,
  faturado e centro de custo.
- Movimentos: busca por número/histórico; filtros por banco, conta, tipo e
  período.
- Caixa: filtros por período e fechado/aberto.
- Cheques: busca por número/favorecido; filtros por situação e banco.

---

## 11. Permissões, CSRF e auditoria

| Ação | admin | gestor | operador |
|---|:---:|:---:|:---:|
| Consultar painel, listas, fichas, comprovantes e relatórios | sim | sim | sim |
| Baixar CP/CR | sim | sim | sim |
| Lançar/editar movimento de caixa | sim | sim | sim |
| Fechar caixa | sim | sim | sim |
| Criar/editar/excluir CP e CR | sim | sim | não |
| Estornar baixa de CP/CR | sim | sim | não |
| Cancelar/excluir CP e CR | sim | sim | não |
| Criar/editar/excluir movimentos bancários | sim | sim | não |
| Excluir/editar movimento de caixa | sim | sim | não |
| Reabrir caixa | sim | sim | não |
| Criar/editar/excluir/compensar cheques | sim | sim | não |
| Gerar/excluir balanço | sim | sim | não |
| Cadastros de apoio | sim | sim | não |

- Ações destrutivas e de escrita usam POST + token CSRF (`biostech_csrf`).
- `MY_Controller::eh_post()` é usado porque `input->post('token')` pode retornar
  `FALSE` e ser confundido com ausência de POST.
- Toda ação relevante registra auditoria (`biostech_auditoria`) com
  `criar`/`alterar`/`excluir` e o identificador do registro.
- O log de CP é independente da auditoria: guarda o detalhe campo a campo.

---

## 12. Tabelas e DDL

- DDL: `scripts/sql/07-financeiro.sql`.
- Domínios: `scripts/sql/03-dominios-financeiro.sql` e o bloco de correção em
  `07-financeiro.sql` (documento, status, natureza e periodicidade).
- Constantes: `application/config/constants.php` (`T_CONTAS_*`, `T_FIN_*`).
- O schema é configurável por `DB_SCHEMA` (padrão `material_grafico`) e todas as
  tabelas usam o prefixo `biostech_`.
- `biostech_contas_pagar` recebeu colunas para banco/agência/conta, portador,
  posição, retenção de caixa, faturamento, cancelamento e baixa.
- `biostech_contas_receber` recebeu `arquivada`, `total_arquivada`,
  `cliente_nota_id`, `fechafin`, `baixa_conta` e campos de baixa/cancelamento.
- `biostech_contas_receber_baixas` usa `arquivada` para separar baixas de
  títulos ativos e arquivados.

---

## 13. Testes

- Script: `scripts/test/financeiro.php` (PHP + cURL + PDO) contra
  `http://127.0.0.1:8090`.
- Cobertura:
  1. login e perfis;
  2. listas, filtros, buscas e paginação de CP e CR;
  3. criação de CP com 2 parcelas, edição, baixa, estorno, cancelamento e
     exclusão;
  4. criação de CR com 2 parcelas (**códigos distintos**), baixa (parcela 1),
     comprovante, estorno, cancelamento e exclusão das duas parcelas;
  5. banco, agência, conta bancária, movimentos bancários (crédito/débito,
     edição e exclusão);
  6. caixa (lançamento, edição, fechamento, reabertura, exclusão);
  7. cheques (emissão, compensação, exclusão);
  8. balanço (geração e exclusão);
  9. cadastros: bancos, centros, naturezas, status, periodicidades, cartões,
     agências e despesas fixas (inclusão, alteração e exclusão);
  10. limpeza dos dados de teste.
- Resultado atual: **201 verificações ok, 0 falhas**.
- `php -l` nos 27 arquivos do módulo: **0 erros**.
- `scripts/diag.php`: **20 verificações ok, 0 problemas**.
- `scripts/test/smoke.php`: **todas as verificações ok**.

---

## 14. Diferenças conscientes em relação ao Access

1. **CP e CR em tabelas únicas**: o legado separa títulos ativos e arquivados;
   aqui `arquivada` identifica a origem. As consultas do dia a dia ignoram
   arquivados, mas o histórico permanece.
2. **CR com código por parcela**: o Access repete o código em parcelas
   arquivadas; a PK nova é o código, então as duplicatas foram renumeradas e as
   baixas remapeadas.
3. **Situação de CP normalizada** em 1/2/3 com `cancelado` em paralelo. Não há
   `situacao = 20`.
4. **Baixa idempotente**: `ON CONFLICT (numero, parcela)` atualiza a baixa em vez
   de duplicar, cobrindo reprocessamentos.
5. **Estorno não apaga movimento bancário/caixa**: a baixa é desfeita, mas os
   lançamentos gerados precisam ser estornados nas telas próprias. Isso evita
   perda silenciosa de rastro financeiro.
6. **Movimento bancário editado não recalcula em cascata** os saldos seguintes;
   o `saldo_anterior` gravado é preservado. Correções em massa devem ser feitas
   com conferência dos saldos.
7. **Caixa calculado**: `saldo_atual` é fechamento recalculado, não o valor
   bruto do Access. A regra da linha "SALDO ANTERIOR" preserva o resultado
   legado.
8. **MOVICAIXA3 ignorada**: a abertura já está em `MOVICAIXA1.SALDOATUAL`.
9. **Cheques legados vazios**: a tabela foi criada e o CRUD está disponível, mas
   não havia dados para importar.
10. **Balanço**: campos de multa/juros em aberto não são alimentados pelo
    gerador (legado também não os separava); `valor_princ_pagar = bruto_pagar`.
11. **Faturamento/NF não faz parte deste módulo**: os campos legados foram
    preservados, mas a emissão de NF e o faturamento em lote ficam para o módulo
    Faturamento.
12. **Sem PDF no servidor**: os comprovantes e relatórios usam impressão do
    navegador (`@media print`), como nos demais módulos.
