# Regras do módulo Compras

**Projeto:** BIOSTECH web (CodeIgniter 2.1.2 · PHP 7.4.33 · PostgreSQL 16)
**Módulo:** Compras — pedidos, cotações de preço, solicitações internas, prazos de
fornecedor, fechamento/recebimento, contas a pagar e impressão.
**Origem:** `SISBIOSD_01.mdb` / `BIOSTECH_A2003H.mdb` (Access 2003).

---

## 0. Como este documento foi levantado

- Extração VBA dos formulários do módulo (44 formulários, 63 módulos de código,
  313 consultas) para análise fora do Access.
- Export das tabelas legadas via `scripts/etl/export-compras.ps1`
  (PowerShell 32 bits + Jet OLEDB 4.0) para JSON UTF-8 e carga por
  `scripts/etl/compras.php`.
- Conferência de totais, numeração e fórmulas comparando os valores do Access com os
  gravados no PostgreSQL após a carga (2.694 pedidos, 7.316 itens, 251 parcelas,
  184 cotações, 696 itens de cotação, 1.119 solicitações, 7.071 itens de solicitação,
  44 prazos e 298 registros de log).

### 0.1 Nomes legados x nomes novos

O Access usa o mesmo prefixo `SOLICITACAO_COMPRA_*` para duas coisas diferentes:
`_01/_02` são o **pedido de compra** e `_01A/_02A` são a **solicitação interna**.

| Access | PostgreSQL | Conteúdo |
|---|---|---|
| `SOLICITACAO_COMPRA_01` | `biostech_compras_pedidos` | pedido (cabeçalho) |
| `SOLICITACAO_COMPRA_02` | `biostech_compras_itens` | itens do pedido |
| `SOLICITACAO_COMPRA_01A` | `biostech_compras_solicitacoes` | solicitação interna |
| `SOLICITACAO_COMPRA_02A` | `biostech_compras_solicitacao_itens` | itens da solicitação |
| `COTACAO_PRECOS_01` | `biostech_compras_cotacoes` | cotação (cabeçalho) |
| `COTACAO_PRECOS_02` | `biostech_compras_cotacao_itens` | itens cotados (3 fornecedores) |
| `FORNECEDOR_PRAZOS` | `biostech_fornecedor_prazos` | prazos/forma de pagamento por fornecedor |
| `SOLICITACAO_COMPRA_LOG_01` | `biostech_compras_log` (pedido) | log do pedido |
| `SOLICITACAO_COMPRA_LOG_01A` | `biostech_compras_log` (solicitação) | log da solicitação |
| `CONTAS_PAGAR_01` | `biostech_contas_pagar` | títulos a pagar gerados no fechamento |

As parcelas do pedido (`biostech_compras_parcelas`) não existem como tabela no Access:
eram digitadas em subformulário e gravadas no fechamento. Aqui passaram a ter tabela
própria para permitir consulta e reimpressão.

---

## 1. Entidades

```
biostech_compras_pedidos 1 ─── N biostech_compras_itens
                        1 ─── N biostech_compras_parcelas
                        1 ─── N biostech_contas_pagar        (origem='compra')
                        1 ─── N biostech_estoque_movimentos  (origem='compra')
                        1 ─── N biostech_compras_log

biostech_compras_cotacoes 1 ─── N biostech_compras_cotacao_itens
biostech_compras_solicitacoes 1 ─── N biostech_compras_solicitacao_itens
biostech_fornecedor_prazos    (N por fornecedor)
```

- **Pedido**: `numero` (PK, numeração `PC`), fornecedor, datas, setor, funcionário,
  local de entrega, condição de pagamento, descontos, totais, situação
  (`aberto`, `fechado`, `cancelado`), NF, fechamento e cancelamento.
- **Item**: `pedido_numero` + `produto_id` (único por pedido), quantidade,
  valor unitário, novo valor unitário, desconto por percentual ou valor, total,
  observação e `atualiza_preco_compra`.
- **Parcela**: `numero_par` 1..12, `dias`, `vencimento`, `valor`.
- **Cotação**: até três fornecedores por item, com `fornecedor_escolhido`.
- **Solicitação interna**: itens com `selecionado` (usar ao gerar pedido) e
  `exportado` (já consumido por pedido/cotação), `repor_estoque`, `data_utilizacao`
  e `periodo`.
- **Prazos de fornecedor**: `codigo` sequencial por fornecedor, forma de pagamento e
  prazo em texto livre.
- **Contas a pagar**: uma linha por parcela, `situacao` 1 = aberto, 3 = cancelado.
- **Log**: por pedido (`pedido_numero`) ou por solicitação (`solicitacao_numero`),
  com ação `I` (inclusão), `A` (alteração), `F` (fechamento), `D` (cancelamento de
  fechamento/cancelamento) e `E` (exclusão), detalhe em JSONB.

---

## 2. Numeração

| Tipo | Uso | Regra |
|---|---|---|
| `PC` | pedido de compra | sequencial global; inicia em 10.012 (maior do Access + 1) |
| `COT` | cotação de preços | sequencial global; inicia em 214 |
| `SC` | solicitação interna | sequencial global; inicia em 2.739 |
| `CP` | título a pagar | sequencial global; iniciado em 0 |

Implementação em `application/libraries/Numeracao.php` com
`INSERT ... ON CONFLICT (tipo) DO UPDATE ... RETURNING ultimo` — a reserva do número
é atômica mesmo com requisições simultâneas. Números consumidos e não usados
(exclusão, cancelamento) **não** são reaproveitados, igual ao Access.

---

## 3. Ciclo de vida do pedido

```
            novo/editar                fechar
   (nada) ───────────────► aberto ──────────────► fechado
                             ▲  │                    │
                    reabrir  │  │ cancelar           │ cancelar fechamento
                             │  ▼                    │
                          cancelado ◄───────────────┘
                             │
                        excluir (só aberto)
```

- **aberto**: permite alterar cabeçalho/itens, incluir/excluir itens, transferir item
  para outro pedido, replicar, excluir o pedido, cancelar e fechar.
- **fechado**: somente leitura; permite imprimir, cancelar o fechamento e replicar.
- **cancelado**: somente leitura; permite reabrir (volta para `aberto`) e imprimir.
- **exclusão**: só para `aberto`; remove itens e parcelas em cascata e mantém o
  registro no log (ação `E`).

---

## 4. Regras por operação

### 4.1 Pedido — cabeçalho

- Data e fornecedor são obrigatórios para gravar; o fornecedor precisa existir em
  `biostech_fornecedores` (quando informado).
- **Exceção**: pedido gerado a partir de solicitação interna nasce **sem fornecedor**
  (o fornecedor é definido na cotação). `salvar_pedido(..., $permite_sem_fornecedor =
  TRUE)`. O fechamento continua exigindo fornecedor.
- Setor e funcionário são opcionais; `tipo` e `centro_custo` vêm nulos do legado.
- `hora` vazia assume a hora atual.
- **Desconto do pedido** (`DESCONTO` do Access):

  ```
  bruto = soma(valor_total dos itens)
  se desconto_percentual > 0:  desconto = round(bruto * desconto_percentual / 100, 2)
  senão se desconto > 0:       desconto = round(desconto, 2)
  senão:                       desconto = 0
  total = max(0, bruto - desconto)
  ```

- O campo de desconto percentual do **cabeçalho** usa o nome
  `desconto_pedido_percentual` no formulário/controller para não colidir com
  `desconto_percentual[]` dos itens.

### 4.2 Item do pedido

```
base = quantidade * valor_unitario
se novo_valor_unitario > 0:
     total    = round(quantidade * novo_valor_unitario, 2)
     desconto = round(base - total, 2)
senão se desconto_percentual > 0:
     desconto = round(base * desconto_percentual / 100, 2)
     total    = round(base - desconto, 2)
senão:
     desconto = round(desconto_item, 2)
     total    = round(base - desconto, 2)
```

- `valor_unitario` vazio assume `biostech_produtos.valor_compra`.
- Produto repetido no mesmo pedido é rejeitado.
- Produto inexistente é rejeitado (`_produtos_existem`) — evita "item fantasma" que
  desapareceria das listagens por causa do `JOIN` com produtos.
- `atualiza_preco_compra` marca o item que deve atualizar o custo do produto no
  fechamento.

### 4.3 Parcelas (regra `VALPARC` do Access, adaptada)

- Quantidade de parcelas **livre**, informada pelo usuário no campo
  "Quantidade de parcelas"; a grade é gerada automaticamente. Não há limite
  de 12 parcelas e `dias`/`vencimento` podem variar livremente.
- `dias` e `vencimento` são calculados um pelo outro, tendo como base o
  **Vencimento Base** (`data_vencimento`):
  - informado `dias` → `vencimento = base + dias`;
  - informado `vencimento` → `dias = vencimento - base` (em dias).
- Rateio (a sobra vai sempre para a última parcela, que fica com o maior valor):

  ```
  centavos   = round(total * 100)
  base       = floor(centavos / n)          (para as n-1 primeiras)
  ultima     = centavos - base * (n - 1)    (maior valor)
  ```

- A última parcela precisa ficar com valor positivo.
- `numero_par` é `integer`, sem restrição de quantidade no banco.

### 4.4 Fechamento (`F_CONFIRMA_PEDIDO_COMPRA_011`)

Pré-requisitos: pedido `aberto`, com fornecedor e pelo menos um item.

1. Atualiza o cabeçalho: situação `fechado`, NF, data da NF, **Vencimento Base**,
   condição de pagamento, forma de recebimento, usuário e data do fechamento.
   `data_compra`, documento, centro de custo e prazo de entrega não são mais
   informados na tela: seus valores já gravados no pedido são mantidos.
2. Regrava as parcelas (apaga as anteriores e insere as normalizadas).
3. Gera **uma entrada de estoque por item**, agrupadas pelo mesmo `lote`, com
   `origem='compra'`, `origem_id=<numero do pedido>`, `documento=<num_nf>` e
   `motivo='Compra'`.
4. Atualiza os dados de compra do produto (`_atualizar_produto`):
   - `valor_compra` = novo valor unitário (se houver) ou valor unitário do item;
   - `valor_compra_desconto` = valor unitário menos o desconto rateado por unidade;
   - `ultimo_pedido_compra` = número do pedido;
   - `data_ultima_entrada` = data da compra;
   - `ultima_nf_entrada` = NF informada (mantém a anterior quando vazia);
   - `percentual_lucro` = derivado de `valor_venda / preco_compra` quando não informado;
   - `valor_venda` recalculado sobre o novo custo quando há percentual de lucro.
5. Quando **gerar contas a pagar** está marcado, reserva um número `CP` e cria um
   título por parcela (`situacao = 1`, `origem='compra'`, `origem_id=<pedido>`),
   gravando o número em `conta_pagar_numero`.
6. Registra o log de fechamento (ação `F`) com data da compra, NF, quantidade de
   parcelas, número do título e lote do estoque.

Tudo em uma única transação. `Estoque_model::entrada()` é chamado com
`$transacao = FALSE` para não abrir transação aninhada (CI 2.1.2 não suporta
savepoints aninhados).

### 4.5 Cancelar o fechamento

- Somente pedido `fechado`; exige motivo.
- Estorna **todos** os movimentos de estoque da compra ainda não estornados
  (movimento inverso, `origem='estorno'`, mesmo documento).
- Cancela logicamente os títulos (`situacao = 3`), sem apagar.
- Apaga as parcelas, volta o pedido para `aberto` e limpa usuário/data de fechamento,
  `conta_pagar_numero`, gravando motivo/usuário/data do cancelamento.
- Log com ação `D`, quantidade de estornos e títulos cancelados.

> `Estoque_model::estornar()` trunca o `motivo` em 80 caracteres (limite da coluna).
> O texto completo fica em `observacao` (texto livre).

### 4.6 Cancelar, reabrir, replicar

- **Cancelar** (`F_CANCELA_PEDIDO_COMPRA`): só `aberto`; exige motivo; grava
  `situacao='cancelado'`, motivo, usuário e data. Não mexe em estoque nem em títulos
  (não existem em pedido aberto).
- **Reabrir**: só `cancelado`; volta para `aberto` e limpa motivo/usuário/data de
  cancelamento. Log ação `A`.
- **Replicar** (`C_replica_PEDIDO_COMPRA_01`): cria um novo pedido `aberto` com data
  atual, mesmo fornecedor/setor/funcionário/entrega e todos os itens, sinalizando na
  observação "Replicado do pedido N". Não copia NF, parcelas nem título.

### 4.7 Transferir e excluir item

- `F_TRANSFERE_ITEM_PEDIDO_COMPRA_01`: move um item entre dois pedidos **abertos**;
  recalcula os totais dos dois; rejeita se o produto já existir no destino.
- `F_CANCELA_ITEM_PEDIDO_COMPRA_01`: exclui o item de pedido **aberto** e recalcula
  o total. Os dois registram log em ambos os pedidos (entrada/saída).

### 4.8 Cotação de preços (3 fornecedores)

- Cabeçalho: data, OS opcional, solicitante, responsável pela cotação (obrigatório),
  observação e vínculo com a solicitação interna.
- Cada item guarda quantidade, até três pares fornecedor/preço e o
  `fornecedor_escolhido` (validado contra os três informados).
- Produto repetido ou inexistente é rejeitado.
- Impressão em mapa comparativo, destacando a coluna escolhida e somando o total das
  propostas escolhidas.
- Exclusão remove cabeçalho e itens (cascata).

### 4.9 Solicitação interna

- Cabeçalho: data/hora, setor, solicitante, local de entrega, responsável e telefone.
- Itens: produto, quantidade, `repor_estoque`, `data_utilizacao`, `periodo`,
  `selecionado` e `exportado`. Produto repetido/inexistente é rejeitado.
- **Fechar** apenas muda `situacao` para `fechada` (não gera pedido).
- **Gerar pedido** (`F_PEDIDO_COMPRA_01A`):
  - usa somente itens `selecionado` (quando "somente marcados") e não `exportado`;
  - cria pedido `aberto` **sem fornecedor**, vinculando `solicitacao_numero`;
  - valor unitário = `valor_compra` atual do produto;
  - marca os itens usados como `exportado`;
  - fecha a solicitação (`pedido_numero` preenchido).
- **Importar para cotação** (`F_CONFIRMA_COTACAO_PRECO_01`): cria cotação nova com os
  itens da solicitação e vincula `cotacao_codigo`. Se já existir cotação vinculada,
  devolve a existente (idempotente).

### 4.10 Prazos de fornecedor

- Um fornecedor pode ter vários prazos; `codigo` é sequencial por fornecedor
  (`max(codigo)+1` quando não informado).
- A tela permite incluir, editar (recarrega no formulário) e excluir.
- Usado como referência na negociação; não bloqueia o pedido.

### 4.11 Impressão

- **Pedido**: dados do fornecedor, entrega, itens, totais, parcelas e observações,
  com linhas de assinatura. Mostra a NF quando o pedido está fechado.
- **Cotação**: mapa comparativo dos três fornecedores.
- **Solicitação**: itens com saldo, quantidade, utilização, período, repor e marcado.
- Todos em HTML/CSS prontos para impressão (sem dependência externa).

---

## 5. Paridade com o Access

| Objeto Access | Recurso web |
|---|---|
| `F_PEDIDO_COMPRA_01A` | `POST compras/solicitacao_gerar_pedido/(:num)` |
| `F_CONFIRMA_COTACAO_PRECO_01` | `POST compras/importar_solicitacao/(:num)` |
| `F_TRANSFERE_ITEM_PEDIDO_COMPRA_01` | `POST compras/transferir_item/(:num)` |
| `F_CANCELA_ITEM_PEDIDO_COMPRA_01` | `POST compras/excluir_item/(:num)/(:num)` |
| `C_replica_PEDIDO_COMPRA_01` | `POST compras/replicar/(:num)` |
| `F_CONFIRMA_PEDIDO_COMPRA_011` | `GET/POST compras/fechar/(:num)` |
| cancelamento do fechamento | `POST compras/cancelar_fechamento/(:num)` |
| cancelamento/reabertura do pedido | `POST compras/cancelar/(:num)` · `compras/reabrir/(:num)` |
| consultas e pesquisas de pedido | `GET compras`, `compras/ver/(:num)` (abas dados/itens/parcelas/contas/log) |
| consulta de cotação | `GET compras/cotacoes`, `compras/cotacao/(:num)` |
| consulta de solicitação | `GET compras/solicitacoes`, `compras/solicitacao/(:num)` |
| prazos por fornecedor | `GET compras/prazos/(:num)` |
| relatórios de pedido/cotação/solicitação | `compras/imprimir_pedido`, `imprimir_cotacao`, `imprimir_solicitacao` |

---

## 6. Validações e mensagens

| Situação | Mensagem |
|---|---|
| data do pedido vazia | "Informe a data do pedido." |
| fornecedor vazio (pedido manual) | "Informe o fornecedor do pedido." |
| fornecedor inexistente | "Fornecedor nao encontrado." |
| produto repetido | "O produto N esta repetido no pedido." |
| produto inexistente | "Um ou mais produtos informados nao existem." |
| editar/cancelar/excluir pedido não aberto | "Somente pedidos abertos podem ser alterados/cancelados/excluídos." |
| fechar sem fornecedor | "Informe o fornecedor antes de fechar o pedido." |
| fechar sem item | "Inclua pelo menos um item antes de fechar o pedido." |
| total zero no fechamento | "O pedido nao possui valor para gerar parcelas." |
| mais de 12 parcelas | "O pedido permite no maximo 12 parcelas." |
| dias/vencimento decrescente | "O prazo/vencimento da parcela N nao pode ser menor que o da anterior." |
| cancelar fechamento sem motivo | "Informe o motivo do cancelamento do fechamento." |
| transferir para o mesmo pedido | "Os pedidos de origem e destino sao iguais." |
| produto já no destino | "O produto ja existe no pedido de destino." |
| solicitação já atendida | "A solicitacao ja foi atendida ou nao esta aberta." |
| solicitação sem item pendente | "Nenhum item pendente para gerar o pedido." |
| cotação sem responsável | "Informe o funcionario que fez a cotacao." |

Erros de banco não são exibidos ao usuário final: o controller converte a falha da
transação em mensagem de flash e mantém o banco consistente (rollback).

---

## 7. Permissões

- **Leitura** (`index`, `ver`, abas, impressões, autocompletes): qualquer usuário
  autenticado.
- **Escrita** (pedido, item, fechamento, cancelamento, cotação, solicitação, prazos):
  `admin` e `gestor` (`exigir_perfil`).
- Toda operação de escrita registra auditoria (`biostech_auditoria`) além do log do
  módulo.

---

## 8. Integração com Estoque e Financeiro

- **Estoque**: o fechamento é o único ponto que movimenta saldo por compra
  (`origem='compra'`). O cancelamento do fechamento estorna pelo movimento inverso,
  nunca por edição de saldo. O estorno de compra é permitido; o de Chamado/OS não
  (precisa passar pelo cancelamento do encerramento).
- **Financeiro**: o módulo grava títulos a pagar mínimos (`biostech_contas_pagar`).
  Baixa, juros/multa, caixa e banco pertencem ao módulo Financeiro (pendente).
- **Produtos**: preço de compra, desconto, último pedido, data da última entrada e
  última NF são atualizados no fechamento — é o que alimenta a margem usada em
  Orçamentos/Chamados.

---

## 9. Testes e validação

- `scripts/test/compras.php` — teste funcional completo contra
  `http://127.0.0.1:8090` (PHP + PDO), com **100 verificações** e **0 falhas**:

  1. login e perfil;
  2. criação de pedido com 2 itens (totais 21,00);
  3. edição com desconto de 10% (total 18,90);
  4. fechamento: 2 parcelas, 2 títulos, 2 movimentos de estoque, saldo +1 no produto,
     custo/último pedido/última NF atualizados;
  5. abas de ficha e impressão do pedido fechado;
  6. cancelamento do fechamento: pedido volta a aberto, títulos cancelados (3),
     movimentos estornados, 2 estornos e saldo restaurado;
  7. replicação; exclusão de itens; transferência de item e devolução;
  8. cancelamento, reabertura e exclusão do pedido;
  9. solicitação interna com 2 itens → cotação (2 itens) → pedido gerado (2 itens,
     solicitação fechada e itens exportados);
  10. inclusão e exclusão de prazo de fornecedor.

  Ao final o teste remove tudo o que criou e restaura produtos e saldos.

- Teste de leitura HTTP (navegação): listagens, buscas, paginação, ficha, 4 abas,
  formulários, cotações, solicitações, prazos, 3 impressões e 2 autocompletes — todas
  as páginas em 200, sem erro de banco/PHP.

---

## 10. Pendências conhecidas

1. **Cotação → pedido automático**: o Access permitia aprovar a cotação e gerar o
   pedido já com o fornecedor escolhido. Aqui o pedido pode ser gerado pela
   solicitação e o fornecedor é definido no cabeçalho; falta o atalho "gerar pedido da
   cotação" (próxima etapa).
2. **Relatórios Access (20)**: apenas os três principais foram reimplementados em HTML
   (pedido, cotação, solicitação). Faltam mapa de cotações por período, compras por
   fornecedor, compras por produto e curva de preços.
3. **Financeiro**: baixa de títulos, juros/multa/desconto, caixa e banco dependem do
   módulo Financeiro.
4. **Recebimento parcial**: hoje o fechamento dá entrada em todos os itens de uma vez.
   O Access tinha conferência item a item (`NF_ENTPROD`), que pertence ao módulo
   Faturamento/NF.
5. **Anexos**: nenhum formulário do Access guardava anexo; se surgir necessidade de
   anexar proposta/NF digitalizada, será uma tabela nova.
