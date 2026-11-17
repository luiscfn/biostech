# Regras do módulo Orçamentos

**Projeto:** BIOSTECH web (CodeIgniter 2.1.2 · PHP 7.4.33 · PostgreSQL 16)
**Módulo:** Orçamentos — orçamento comercial (cabeçalho, itens, serviços, contatos,
histórico, status, impressão, geração de chamado) e dimensionamento técnico
(levantamento, itens, serviços, cronograma, geração de orçamento).
**Origem:** `SISBIOSD_01.mdb` / `BIOSTECH_A2003H.mdb` (Access 2003).

---

## 0. Como este documento foi levantado

- Extração VBA dos formulários do módulo
  (`F_ORCAMENTO_01`, `F_ORC_DIMEN_05`, `F_ORCAMENTO_PESQ_01`) para
  `%TEMP%\opencode\vba-orc`, com análise fora do Access.
- Leitura das consultas legadas (`C_INCLUI_ORCAMENTO_DIMEN_01..06`,
  `C_INCLUI_GERA_CTO_01..08A`, consultas de totalização da tela 031)
  em `%TEMP%\opencode\mdb\frontend-queries.sql`.
- Export das tabelas legadas via `scripts/etl/export-orcamentos.ps1`
  (PowerShell 32 bits + Jet OLEDB 4.0) para JSON UTF-8 e carga por
  `scripts/etl/orcamentos.php`.

### 0.1 Nomes legados x nomes novos

| Access | PostgreSQL | Conteúdo |
|---|---|---|
| `ORCAMENTO_01` + `ORCAMENTO_01A` + `ORCAMENTO_01B` + `ORCAMENTO_01C` | `biostech_orcamentos` | cabeçalho comercial |
| `ORCAMENTO_02` | `biostech_orcamento_itens` | itens de produto |
| `ORCAMENTO_03` | `biostech_orcamento_servicos_valores` | serviços com valor (somam `VALSERV`) |
| `ORCAMENTO_04` | `biostech_orcamento_contatos` | contatos / follow-up |
| `ORCAMENTO_05` | `biostech_orcamento_servicos` | serviços a executar (checklist) |
| `ORCAMENTO_LOG_01` | `biostech_orcamento_log` | histórico por campo |
| `ORCAM_DIMEN_01` | `biostech_orcamentos_dimensionamento` | cabeçalho + levantamento + cronograma |
| `ORCAM_DIMEN_02` | `biostech_orcamento_dimensionamento_itens` | itens do dimensionamento |
| `ORCAM_DIMEN_03` | colunas de `biostech_orcamentos_dimensionamento` | levantamento técnico |
| `ORCAM_DIMEN_04` | `biostech_orcamento_dimensionamento_cotacoes` | cotações de fornecedor |
| `ORCAM_DIMEN_06` | `biostech_orcamento_dimensionamento_servicos` | serviços a executar |
| `ORCAM_DIMEN_07` | `biostech_orcamento_dimensionamento_materiais` | materiais previstos |
| `ORCAM_DIMEN_LOG_01` | `biostech_orcamento_dimensionamento_log` | histórico por campo |

### 0.2 Normalizações feitas na carga (ETL)

- **`ORCAMENTO_01C` era cópia dos dados comerciais de `01B`.** As quatro tabelas
  `01`, `01A`, `01B` e `01C` foram consolidadas na tabela única
  `biostech_orcamentos`, aplicando a precedência **`01B` > `01C` campo a campo**.
  Por isso `CODPAG`, `ENTREGA`, `GARANTIA`, `VALIDADE`, `ASSINATURA` e
  `VALID_PECAS` aparecem apenas em `01C` (todos nulos em `01B`) e foram
  recuperados daí.
- **`ORCAMENTO_02_AM` e `ORCAM_DIMEN_05` estavam vazias** e não geraram tabela.
  `ORCAM_DIMEN_04` e `ORCAM_DIMEN_07` são criadas (cotações e materiais), mas a
  carga resultou vazia — mantidas para uso futuro.
- **`NUMITEM` duplicado ou nulo em `ORCAMENTO_02`** (175 duplicidades e 252 nulos)
  foi renumerado sequencialmente 1..N por orçamento no ETL. A constraint
  `UNIQUE (orcamento_numero, numero_item)` passa a garantir a unicidade.
- **`QTDE <= 0`**: 1 item comercial foi ajustado para 1 (quantidade mínima); nos
  itens de dimensionamento (9 ocorrências) o zero foi preservado.
- **Datas ausentes**: 36 orçamentos sem `DATA` (34 também sem `DATA_CADASTRO`)
  receberam `1901-01-01`, o menor valor aceito pelo Access; datas com ano < 1901
  viram `NULL`.
- **`SIT_ORC_01` nula** em 143 registros permanece `NULL` (o legado não informava
  situação). Orçamentos novos já nascem com `situacao = 1` e `resultado = 3`.
- **`APROVADO` / `NAPROVADO`** legados são todos `False`; os booleanos continuam
  existindo e passam a ser derivados de `resultado` (1 = aprovado, 2 = reprovado).
- **`FINALIDADE`** não tem valores não nulos no legado; o domínio `finalidade`
  (1..21) foi carregado a partir da tabela de domínios do Access.
- **Log**: `ORCAMENTO_LOG_01` e `ORCAM_DIMEN_LOG_01` foram carregados em tabelas
  próprias **sem FK**, com `legado_id` e índice único parcial para a carga ser
  idempotente. O histórico sobrevive à exclusão física do orçamento.
- **Totais defasados no legado**: o Access não gravava `VALTOTAL` ao alterar os
  itens (1.939 orçamentos com itens ficaram com total zerado) e há orçamentos
  que existem apenas em `ORCAMENTO_01`, sem a linha comercial com `VALMAT`. O ETL
  reconstrói **apenas o que está faltando**: quando `valor_material` ou
  `valor_servico` está zerado e há itens/serviços, usa a soma dos itens; quando
  `valor_total` está zerado, aplica
  `material + mão de obra + serviço + imposto - desconto`. Os valores gravados
  no legado (inclusive totais negociados manualmente) são preservados. Na última
  carga: **1.992 totais reconstruídos** (soma final de R$ 105.352.741,39).

---

## 1. Entidades

```
biostech_orcamentos 1 ─── N biostech_orcamento_itens
                    1 ─── N biostech_orcamento_servicos_valores
                    1 ─── N biostech_orcamento_servicos
                    1 ─── N biostech_orcamento_contatos
                    1 ─── N biostech_orcamento_log            (sem FK)

biostech_orcamentos_dimensionamento 1 ─── N biostech_orcamento_dimensionamento_itens
                                    1 ─── N biostech_orcamento_dimensionamento_servicos
                                    1 ─── N biostech_orcamento_dimensionamento_materiais
                                    1 ─── N biostech_orcamento_dimensionamento_cotacoes
                                    1 ─── N biostech_orcamento_dimensionamento_log (sem FK)

biostech_orcamentos.dimensionamento_numero ──► biostech_orcamentos_dimensionamento.numero
```

- **Orçamento**: `numero` (PK, numeração `ORC`), `data`, `cliente_id`,
  `chamado_numero` (OS vinculada), `dimensionamento_numero`, `equipamento_serie`,
  `nota_fiscal_chegada`, `atencao`, `finalidade`, `observacao`,
  `condicao_pagamento`, `entrega`, `garantia`, `validade`, `validade_pecas`,
  `assinatura`, situação, resultado, datas de envio/aprovação, valores
  (material, mão de obra, serviço, imposto, desconto, total), percentuais,
  `material_fixacao`, `acrescimo_custo`, `valor_custos_indiretos`,
  `valor_maximo_cliente`, `valor_material_original`, `aprovado`, `nao_aprovado`,
  `motivo_cancelamento`, auditoria.
- **Item**: `numero_item`, `produto_id`, `descricao`, `descricao_produto`,
  `quantidade`, `valor_unitario`, `valor_total` e os pares
  `valor_unitario_original` / `valor_total_original` (usados pelo desconto e
  acréscimo em massa).
- **Serviço com valor**: `numero_item`, `descricao`, `quantidade`,
  `valor_unitario`, `valor_total` — entra na soma de `VALSERV`.
- **Serviço a executar**: `numero_item`, `descricao`, `concluido`.
- **Contato**: `data_mov`, `hora_mov`, `assunto`, `funcionario_id`, `contato`,
  `descricao`, `data_proximo_contato`, `hora_proximo_contato`.
- **Dimensionamento**: `numero` (PK, numeração `DIM`), `data`, `chamado_numero`,
  `cliente_id`, `contato`, `tecnico_id`, `local_instalacao`, `tipo_contato`,
  `setor`, `email`, quantidades de técnicos/comerciais/não comerciais,
  `solicitante`, `orcamento_numero` (preenchido ao gerar o orçamento),
  levantamento técnico (textos, medidas e flags), percentuais/valores e
  cronograma semanal (flags e horas de início/fim por dia).
- **Item do dimensionamento**: `numero_item`, `produto_id`, `descricao`,
  `descricao_produto`, `quantidade`, `valor_compra`, `valor_gasto`,
  `total_compra`, `valor_venda`, `total_venda`, `grupo`, `subgrupo`, `pg`, `psg`.

---

## 2. Numeração

| Tipo | Uso | Regra |
|---|---|---|
| `ORC` | orçamento comercial | sequencial global; inicia no maior `NUM_ORC` do Access + 1 |
| `DIM` | dimensionamento | sequencial global; inicia no maior `NUM_PLA` do Access + 1 |

`application/libraries/Numeracao.php` faz `INSERT ... ON CONFLICT (tipo, competencia)
DO UPDATE ... RETURNING ultimo` via `pg_query_params`, reservando o número de forma
atômica. Números consumidos e não usados (exclusão) **não** são reaproveitados,
igual ao Access.

---

## 3. Situação e resultado

Duas dimensões independentes, com os códigos legados preservados 1:1 no domínio
`biostech_dominios`:

**`situacao` — `SIT_ORC_01`**

| Código | Nome |
|---|---|
| 1 | ABERTO |
| 2 | ENVIADO |
| 3 | PENDENTE |
| 4 | CONCLUIDO |
| 5 | ENCERRADO |

**`resultado` — `SIT_ORC_02`**

| Código | Nome |
|---|---|
| 1 | APROVADO |
| 2 | NAO APROVADO |
| 3 | NAO ENVIADO |
| 4 | EM ANALISE PELO CLIENTE |
| 5 | AGUARDA APROVACAO |
| 6 | CANCELADO PELO CLIENTE |
| 7 | CANCELADO PELA BIOSTECH |

Regras:

- Orçamento novo: `situacao = 1` (ABERTO) e `resultado = 3` (NAO ENVIADO).
- `resultado` 6 ou 7 exige `motivo_cancelamento` preenchido.
- Ao gravar `resultado = 1` ou `2`, o booleano correspondente
  (`aprovado` / `nao_aprovado`) é sincronizado, `data_resultado` é registrada e,
  no caso de aprovação, `data_aprovacao` é preenchida quando vazia.
- `situacao = 4` (CONCLUIDO) habilita a geração de chamado, junto com
  `resultado = 1` e as demais exigências (ver §7).
- Constraints de banco: `situacao BETWEEN 1 AND 5` e `resultado BETWEEN 1 AND 7`.

---

## 4. Fórmulas do comercial

A tela 031 do Access calcula em cascata:

```
AC_MO          = (VALMAT > 0 e VALMOBRA > 0) ? VALMAT + VALMOBRA
                                             : (VALMAT = 0 e VALMOBRA > 0 ? VALMOBRA : VALMAT)
AC_MO_MF       = MAT_FIXACAO    > 0 ? AC_MO        + MAT_FIXACAO    : AC_MO
AC_MO_MF_CT    = ACRESCIMO_CUSTO> 0 ? AC_MO_MF     + ACRESCIMO_CUSTO: AC_MO_MF
AC_MO_MF_CT_NF = VALIMPOSTO     > 0 ? AC_MO_MF_CT  + VALIMPOSTO     : AC_MO_MF_CT

SUBTOTAL = AC_MO_MF_CT_NF
VALTOTAL = max(0, SUBTOTAL + VALSERV - VALDESC)
```

Onde `VALMAT` é a soma de `biostech_orcamento_itens.valor_total`, `VALSERV` é a
soma de `biostech_orcamento_servicos_valores.valor_total` e `VALDESC` é o desconto
do cabeçalho.

`application/models/Orcamentos_model.php::recalcular()` reimplementa essa cascata
e grava `valor_material`, `valor_mao_obra`, `valor_servico`, `valor_imposto`,
`valor_desconto` e `valor_total`. É chamado depois de qualquer alteração em itens,
serviços com valor ou no cabeçalho.

### 4.1 Percentuais (precedência sobre o valor digitado)

Quando o percentual é informado, ele **prevalece** sobre o valor em reais
correspondente, que é recalculado:

```
mao_obra     = perc_mao_obra        * VALMAT        / 100
mat_fixacao  = perc_mat_fixacao     * AC_MO         / 100
acrescimo    = perc_acrescimo_custo * AC_MO_MF      / 100
imposto      = perc_nota_fiscal     * AC_MO_MF_CT   / 100
desconto     = perc_desconto        * SUBTOTAL      / 100
```

Arredondamento em **2 casas decimais** (meio para cima, via `round()` do PHP).

Na tela, `assets/js/orcamentos.js` implementa a mesma precedência de forma
otimista: informar o percentual limpa o valor em R$ e vice-versa. O valor
definitivo é sempre o do servidor.

---

## 5. Itens — desconto e acréscimo em massa

As ações "aplicar percentual" e "restaurar valores" (`aplicar_percentual` e
`restaurar_valores` no controller) operam sobre todos os itens de uma vez:

- **Primeiro uso**: `valor_unitario_original` / `valor_total_original` recebem o
  valor corrente do item e nunca mais são sobrescritos. É essa coluna que
  garante a idempotência (aplicar 10% duas vezes não acumula).
- **Aplicar desconto**: `valor = original × (1 − perc/100)`;
  grava `percentual_desconto_item` no cabeçalho.
- **Aplicar acréscimo**: `valor = original × (1 + perc/100)`;
  grava `percentual_acrescimo_item` no cabeçalho.
- **Restaurar**: devolve `valor_unitario` / `valor_total` a partir dos originais
  e limpa `percentual_desconto_item` / `percentual_acrescimo_item`.
- Percentual válido: 0 a 100. Orçamento sem itens é recusado.

Histórico: cada operação registra no log a ação e o percentual aplicado.

---

## 6. Itens, serviços e contatos (tela do orçamento)

A listagem (`/orcamentos`) é só do cabeçalho; itens, serviços, serviços com
valor e contatos são geridos nas abas da ficha (`/orcamentos/ver/{numero}`):

| Aba | Ações |
|---|---|
| Itens | incluir (produto + quantidade + valor unitário), alterar, excluir, desconto/acréscimo em massa, restaurar |
| Serviços | incluir/alterar/excluir "serviços a executar" (checklist com `concluido`) |
| Serviços com valor | incluir/alterar/excluir serviços que somam `VALSERV` |
| Contatos | incluir/excluir contatos de follow-up (data/hora, próximo contato) |
| Histórico | leitura do log por campo |
| Dados | cabeçalho, status, impressão e geração de chamado |

Regras de integridade:

- `quantidade > 0` no comercial (o Access aceitava zero/negativo em alguns casos;
  aqui é bloqueado).
- `valor_total` do item = `quantidade × valor_unitario` quando não informado;
  o valor digitado é respeitado.
- `produto_id` é opcional: item pode ser texto livre. Se informado, a descrição
  do produto é copiada para `descricao_produto`.
- Exclusão do orçamento é **física** e cascateia itens/serviços/contatos.
  Fica bloqueada quando existe `chamado_numero` vinculado.
- Após qualquer alteração nos itens/serviços com valor, o cabeçalho é recalculado.

O formulário de cada linha usa `form="item-N"` para associar inputs que estão
fora do `<form>` (limitação do HTML com tabelas). O `<form>` é gerado por
`form_open(..., array('id' => 'item-N'))` na célula de ações.

---

## 7. Geração de chamado a partir do orçamento

Equivalente ao `Comando241_Click` do `F_ORCAMENTO_01`. Pré-requisitos
(`pode_gerar_chamado`):

1. `chamado_numero` do orçamento vazio (ainda não gerou).
2. `situacao >= 4` (CONCLUIDO ou ENCERRADO).
3. `resultado = 1` (APROVADO).
4. `data_envio` e `data_aprovacao` preenchidas.
5. `cliente_id` preenchido.
6. Não existir `biostech_chamados.orcamento_numero = numero`.

Ao gerar (transação única):

- reserva o número de OS (`Numeracao::numero_os`);
- cria `biostech_chamados` com `categoria = 'Chamado'`,
  `orcamento_numero = numero`, `cliente_id`, `data_abertura = hoje`,
  `situacao_codigo = 16` (aberto) e demais campos do orçamento;
- cria o equipamento do chamado (`equipamento_serie`, `nota_fiscal_chegada`);
- copia os **itens** para `biostech_chamado_pecas` e os **serviços** para
  `biostech_chamado_servicos`;
- grava `chamado_numero` no orçamento e registra no log.

Regras para impedir duplicidade: além do vínculo no orçamento, a consulta de
existência em `biostech_chamados` por `orcamento_numero` bloqueia a segunda
geração mesmo se o vínculo for apagado manualmente.

---

## 8. Dimensionamento técnico

### 8.1 Levantamento (colunas do cabeçalho)

Textos: `que_funciona`, `quer_fazer`, `dificuldades`, `material_precisa`,
`providenciar`, `observacao`. Medidas: `altura`, `comprimento_muro`,
`lado_direito`, `lado_esquerdo`, `frente`, `fundo`. Flags: `rebocado`,
`cerca_arame`, `telhado`, `arvore`, `cinta_cimento`, `pega_ladrao`.
Prazo: `qtd_dias`.

### 8.2 Cronograma

Flags `segunda`..`domingo` e, para cada dia, `*_inicio` e `*_fim` em horas
decimais (ex.: `8.5` = 08:30). Nenhum cálculo é feito no servidor além de
validar faixas; o cronograma é informativo e impresso na ficha.

### 8.3 Itens

`total_compra = quantidade × valor_compra` e
`total_venda = quantidade × valor_venda`, calculados no servidor.
`valor_gasto` é o custo acessório do item (frete/serviço) e não entra nos totais.
`grupo` / `subgrupo` / `pg` / `psg` são códigos de classificação herdados do
estoque.

### 8.4 Geração de orçamento (`C_INCLUI_ORCAMENTO_DIMEN_01..06`)

Pré-requisitos (`pode_gerar_orcamento`):

1. Dimensionamento existe.
2. `orcamento_numero` ainda vazio.
3. `cliente_id` preenchido.
4. Existe pelo menos um item.
5. Todos os itens têm `valor_venda > 0`.

Ao gerar (transação única):

- reserva o número `ORC`;
- cria o orçamento com `dimensionamento_numero`, cliente, `atencao` (contato do
  dimensionamento), `data` de hoje, `situacao = 1`, `resultado = 3`;
- copia os itens para `biostech_orcamento_itens` com
  `valor_unitario = valor_venda` e `valor_total = total_venda`, e define os
  campos `*_original`;
- copia os serviços a executar para `biostech_orcamento_servicos`;
- recalcula os totais do orçamento;
- grava `orcamento_numero` no dimensionamento e registra no log.

A segunda tentativa é bloqueada pelo vínculo já gravado.

### 8.5 Cotações e materiais

`biostech_orcamento_dimensionamento_cotacoes` (produto + fornecedor + preço) e
`biostech_orcamento_dimensionamento_materiais` (descrição + quantidade) existem
para completar o módulo. As tabelas legadas correspondentes estavam vazias
(`ORCAM_DIMEN_04` e `ORCAM_DIMEN_07`), então as telas mostram o estado vazio com
a estrutura pronta. A carga preservou os dados existentes (zero linhas).

---

## 9. Histórico (log por campo)

Duas tabelas: `biostech_orcamento_log` e
`biostech_orcamento_dimensionamento_log`. Ambas sem FK, com
`legado_id` + índice único parcial para idempotência, e:

| Coluna | Conteúdo |
|---|---|
| `data_mov` | data e hora do evento |
| `usuario` | login de quem alterou |
| `tipo_mov` | `C` = criação, `A` = alteração, `E`/`D` conforme a ação |
| `campo` | nome do campo alterado (NULL quando é evento composto) |
| `valor_anterior` / `valor_novo` | texto dos valores |
| `dados` | JSONB com o evento completo (snapshot, ação, percentual etc.) |

`_log_diff()` compara o registro antes e depois e gera **uma linha por campo
alterado**, mais uma linha de evento composto quando aplicável (criação,
exclusão, desconto em massa). A ficha exibe o histórico em ordem decrescente,
com o nome do usuário (`usuario_nome` quando o login existe em
`biostech_usuarios`, com fallback para o login bruto).

Como não há FK, excluir o orçamento **não** apaga o histórico — ele continua
consultável (inclusive pela própria ficha durante o processo).

---

## 10. Impressão

- `/orcamentos/imprimir/{numero}`: cabeçalho com dados do cliente, itens,
  serviços com valor, serviços a executar, totais, condições (entrega, garantia,
  validade, validade das peças, condição de pagamento) e assinatura.
- `/orcamentos/dimensionamento_imprimir/{numero}`: levantamento técnico,
  cronograma, itens com totais de compra/venda e serviços.

Ambas geram HTML autocontido (nav por `window.print()`), sem dependência de
biblioteca de PDF.

---

## 11. Autocomplete

Dois endpoints JSON:

- `GET /orcamentos/buscar_clientes?termo=...` → `id, nome, fantasia, cpf_cnpj,
  cidade, uf` (somente clientes ativos).
- `GET /orcamentos/buscar_produtos?termo=...` → `id, codigo, descricao,
  unidade, valor_venda, valor_compra`.

Reaproveitam o componente global `window.BiostechAutocomplete`
(`assets/js/autocomplete.js`), que suporta `data-preco`, `data-custo`,
`data-estoque`, `data-unidade` e o evento `autocomplete:selecionar`. O formulário
de itens usa `data-descricao` para copiar a descrição do produto escolhido
(interceptando `box.biostechAoSelecionar`).

---

## 12. Tabelas e DDL

`scripts/sql/06-orcamentos.sql` (idempotente, aplicado por
`scripts/sql/aplicar.php`):

- `ALTER TABLE biostech_orcamentos`: colunas `resultado`, `data_resultado`,
  `motivo_cancelamento`; constraints de faixa; índices por data, situação/cliente
  e dimensionamento.
- `ALTER TABLE biostech_orcamentos_dimensionamento`: colunas de levantamento
  técnico, medidas, flags, percentuais/valores e cronograma semanal.
- `CREATE TABLE` das 7 tabelas novas (contatos, serviços com valor, log,
  serviços/materiais/cotações/log do dimensionamento).
- Domínios `situacao_orcamento`, `resultado_orcamento` e `finalidade`.
- `biostech_numeracao` com `ORC` e `DIM`.

O ETL (`scripts/etl/orcamentos.php`) é idempotente: usa upsert por chave natural,
`legado_id` no log e renumeração controlada dos itens.

---

## 13. Testes

`scripts/test/orcamentos.php` cobre, contra o servidor em execução:

1. Listagem e filtros.
2. Criação do orçamento (situação/resultado iniciais, campos de condição).
3. Inclusão e alteração de item (total e recálculo do cabeçalho).
4. Desconto em massa e restauração (origem preservada).
5. Serviço a executar e serviço com valor (soma de `VALSERV` e total).
6. Contato / follow-up.
7. Histórico.
8. Status (concluído + aprovado → booleanos e datas).
9. Impressão.
10. Geração de chamado (vínculo, cópia de itens/serviços/equipamento).
11. Dimensionamento: criação, levantamento, item, serviço.
12. Dimensionamento → orçamento (cópia e bloqueio de duplicidade).
13. Autocomplete (JSON de clientes e produtos).
14. Exclusão de item e do orçamento + limpeza dos dados de teste.

Uso:

```powershell
C:\php\php.exe scripts\test\orcamentos.php http://127.0.0.1:8090 DIMENSIONAMENTO dimensionamento
```

---

## 14. Diferenças conscientes em relação ao Access

| Access | BIOSTECH web | Motivo |
|---|---|---|
| Tabelas `01/01A/01B/01C` separadas | tabela única `biostech_orcamentos` | elimina duplicidade e inconsistência entre cópias |
| `DLookup` + `UPDATE` para numerar | `INSERT ... ON CONFLICT ... RETURNING` | numeração atômica, sem números repetidos |
| Log em texto livre | log por campo + JSONB | consulta e auditoria |
| Exclusão de orçamento apagava o histórico | histórico preservado (sem FK) | rastreabilidade |
| `SIT_ORC_01`/`02` como texto | códigos numéricos + domínio | integridade referencial |
| Percentual e valor concorrentes | percentual prevalece explicitamente | evita divergência entre telas |
