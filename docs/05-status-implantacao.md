# Status da implantação — BIOSTECH web

**Data:** 16/11/2026
**Projeto:** `C:\desenvolvimento\PROJETOS_PHP\biostech`
**Framework:** CodeIgniter 2.1.2 (release de 28/01/2012) · PHP 7.4.33
**Banco:** PostgreSQL 16 — `sedsrvpostdesv.seduc.ce.gov.br:5432/desenvolvimento`

---

## 0. Como subir (resumo)

```powershell
# opção 1: duplo clique em iniciar.bat  (sobe o servidor e abre o navegador)
# opção 2: terminal / task do VS Code
php -S 127.0.0.1:8090 router.php
```

- Abrir no VS Code: **File → Open Folder** em `C:\desenvolvimento\PROJETOS_PHP\biostech`.
- Tasks prontas: **Servidor: iniciar**, **Servidor: parar**, **Teste: fumaça (HTTP)**,
  **Diagnóstico do ambiente**, **Teste: lint**, **Banco: recarregar dados do Access**.
- Diagnóstico rápido: `php scripts/diag.php` (20 verificações: PHP, extensões, banco,
  tabelas, senhas bcrypt e servidor).

---

## 1. O que está no ar

Servidor de desenvolvimento: `php -S 127.0.0.1:8090 router.php`

| Área | Rota | Situação |
|---|---|---|
| Login / logout | `/login`, `/logout` | pronto |
| Painel com indicadores e auditoria | `/dashboard` | pronto |
| Clientes (lista, busca, filtro, paginação, ficha, CRUD, ordens de serviço, orçamentos e notas fiscais do cliente) | `/clientes` | pronto |
| Fornecedores (lista, busca, CRUD, prazos, produtos, copiar para cliente) | `/fornecedores` | pronto |
| Produtos (lista, busca, ficha) | `/produtos` | pronto |
| Setores (lista, busca, CRUD) | `/setores` | pronto |
| Usuários (lista, CRUD, senha, ativar/desativar) | `/usuarios` | pronto |
| Chamados / OS (ficha, intervenções, peças, serviços, contatos, financeiro, status, encerramento, reabertura, par, impressão) | `/chamados` | **pronto (piloto)** |
| Estoque (saldos, ficha, movimentos, entradas/saídas multi-item, ajuste/inventário, estorno, catálogos, fornecedores) | `/estoque` | **pronto (núcleo)** |
| Compras (pedidos, itens, parcelas, fechamento/recebimento, cotações, solicitações internas, prazos, contas a pagar, impressão) | `/compras` | **pronto** |
| Orçamentos (comercial: cabeçalho, itens, desconto/acréscimo em massa, serviços, contatos, histórico, status, impressão, geração de chamado) | `/orcamentos` | **pronto** |
| Dimensionamento (levantamento, itens, serviços, cronograma, cotações, geração de orçamento, impressão) | `/orcamentos/dimensionamentos` | **pronto** |
| Financeiro (contas a pagar/receber, baixas, movimentos bancários, caixa, cheques, balanço, cadastros e relatórios) | `/financeiro` | **pronto** |
| Faturamento/NF, RH, Frota | — | "em breve" no menu |

---

## 2. Dados carregados

Tabelas criadas no schema **`material_grafico`**, com prefixo `biostech_`:

| Tabela | Registros | Origem no Access |
|---|---:|---|
| `biostech_setores` | 11 | `SETORES` |
| `biostech_usuarios` | 26 | `USUARIOS` |
| `biostech_clientes` | 4.773 | `CLIENTES` |
| `biostech_produtos` | 4.522 (1.950 ativos) | `PRODUTO` |
| `biostech_fornecedores` | 2.188 | `FORNECEDOR` |
| `biostech_produto_fornecedor` | 16 | `PROD_FORNEC_01` |
| `biostech_estoque_grupos` / `subgrupos` | 60 / 468 | `GRUPO`, `SUB_GRUPO` |
| `biostech_estoque_posicoes` / `estantes` / `prateleiras` | 3 / 37 / 900 | `POSICAO`, `ESTANTE_01`, `PRATELEIRA_01` |
| `biostech_estoque_finalidades` | 21 | `FINALIDADE` |
| `biostech_auditoria` | — | nova (login/logout/CRUD) |

> **Limitação de infraestrutura:** o usuário `matgrafico` não tem privilégio de
> `CREATE SCHEMA` nem `CREATE DATABASE` (verificado em `pg_namespace`/`pg_database`).
> Por isso as tabelas foram isoladas por prefixo no schema existente. O código lê
> `DB_SCHEMA` (padrão `material_grafico`); ao provisionar um schema dedicado `biostech`,
> basta rodar `DB_SCHEMA=biostech php scripts/etl/instalar.php`.

### 2.1 Módulo Chamados / OS

Criado por `scripts/sql/02-chamados-os.sql` e `scripts/sql/03-dominios-financeiro.sql`:

- **27 tabelas** `biostech_chamado*`, `biostech_contas_receber`, `biostech_orcamento*`,
  `biostech_estoque_*` e `biostech_numeracao` (ver DDL para a lista completa).
- **Domínios de domínio legados preservados 1:1**: status de OS, situação detalhada,
  tipo de garantia, prioridade, segmento, tipo de equipamento, checklist, forma de
  recebimento (8), condição de pagamento (27), documento (8), tipo de pagamento (33), etc.
- **Estoque**: `biostech_estoque_saldos` e `biostech_estoque_movimentos`, carregados por
  `scripts/etl/estoque.php` (4.521 saldos, 953 produtos sem saldo ignorados,
  100.579.957 unidades).
- **Numeração atômica** (`application/libraries/Numeracao.php`): `OS` por competência
  `aaaa-mm` e `CR` global, via `INSERT ... ON CONFLICT ... RETURNING`.
- **Trilha**: `biostech_chamado_eventos` (linha do tempo) e `biostech_chamado_log`
  (alteração por campo, com JSONB antes/depois).
- **Regras**: `docs/06-regras-chamados-os.md` (RN-001..RN-283, VD-01..VD-72,
  AC-01..AC-24 e decisões D-01..D-10).

**Carga do legado (Access → BIOSTECH).** `scripts/etl/export-chamados.ps1` exporta
14 tabelas para `scripts/etl/dados/chamado_*.json` e `scripts/etl/chamados.php`
carrega no schema:

| Destino | Origem | Registros |
|---|---|---:|
| `biostech_equipamentos` | `EQUIPAMENTO` | 11.866 |
| `biostech_chamados` | `CHAMADO_01` + `02` + `03A` | 2.004 (2.006 com os nativos) |
| `biostech_chamado_equipamento` | `CHAMADO_03` | 1.892 (1.894) |
| `biostech_chamado_atendimento` | `CHAMADO_04` | 1.916 (1.918) |
| `biostech_chamado_intervencoes` | `CHAMADO_05A` | 2.973 |
| `biostech_chamado_pecas` | `CHAMADO_06` | 8.778 (8.797) |
| `biostech_chamado_servicos` | `CHAMADO_55` (previsto) + `CHAMADO_44` (executado) | 8.217 (8.227) |
| `biostech_chamado_horas` | `CHAMADO_09` | 56 |
| `biostech_chamado_planos_pagamento` / `_parcelas` | `CHAMADO_10` | 1.331 / 711 |
| `biostech_chamado_vinculos` | `CHAMADO_99` | 0 |
| `biostech_chamado_eventos` / `_log` | `CHAMADO_LOG_01` | 1.593 / 20.449 campos |

- **Clientes**: `COD_CLI` de `CHAMADO_02` casa direto com `biostech_clientes.id`
  (1.899 dos 2.004). Os 105 sem cliente válido usam o cliente de apoio
  `CLIENTE NAO IDENTIFICADO (IMPORTACAO ACCESS)` (`id` 4.907, criado pela carga).
- **Equipamento**: `CHAMADO_03.COD_EQUIP` casa com `EQUIPAMENTO.NUS_EQUIP`; o
  `cliente_id` de `biostech_equipamentos` fica `NULL` (o Access não tem esse
  vínculo — `CLIENTES_EQUIP` está vazia).
- **Técnicos**: `CODFUN`/`CODIFUNC` mapeados para `biostech_usuarios.id` por
  `funcionario_id` (menor `id`); códigos sem usuário viram `NULL` (intervenções
  usam `0`, exigido pela tabela).
- **Normalizações**: datas inválidas/negativas descartadas (abertura sem data usa
  `aaaa-mm-01` do número); devolução anterior ao atendimento vira `NULL`;
  `QTDE_PARC` fora de 1..12 vira `NULL`; valores negativos viram `0`; itens de peça
  repetidos são renumerados (`numero_item`); `produto_id` repetido no mesmo chamado
  vira `NULL`; intervenções com `(OS, data, hora)` repetida recebem +1s.
- **Idempotência**: *upsert* em chamados, equipamentos, atendimento, planos e
  parcelas; `ON CONFLICT DO NOTHING` em peças, serviços, intervenções e vínculos
  (a tabela de intervenções tem *trigger* que bloqueia `UPDATE`/`DELETE`);
  eventos/log `log_%` são apagados e recriados.
- **Fora do escopo**: `CHAMADO_031`, `03B`, `04A`, `05`, `06_AM`, `06_LAB`, `07`,
  `08`, `10A`, `11`, `66`, `77`, `88` estão vazias; 4.953 intervenções de
  `CHAMADO_05A` (OS de 2017–2020 que não existem em `CHAMADO_01`), 11 vínculos e
  10 logs órfãos foram ignorados; 1 chamado sem `NUM_OS` e 2 séries de equipamento
  duplicadas após limpeza de espaços/CRLF foram descartados.

### 2.2 Módulo Estoque

Criado por `scripts/sql/04-estoque.sql` e carregado por `scripts/etl/estoque-catalogos.php`:

- **Tabelas**: `biostech_fornecedores`, `biostech_produto_fornecedor`,
  `biostech_estoque_grupos/subgrupos/posicoes/estantes/prateleiras/finalidades`,
  `biostech_estoque_inventarios` e `biostech_estoque_inventario_itens`, além das
  colunas de movimento (`lote`, `documento`, `fornecedor_id`, `setor_id`,
  `funcionario_id`, `custo_unitario`, `motivo`, `origem`, `estornado`,
  `estornado_por`, `inventario_id`).
- **Telas**: saldos com filtros e ordenação, ficha do produto (saldo, consumido, valor,
  localização, fornecedores, contagens), movimentos com filtros e estorno, entrada e
  saída multi-item com autocomplete, ajuste por contagem, catálogos com edição inline,
  fornecedores com vínculo de produtos.
- **Regras**: saldo nunca negativo (D-04); correção apenas por estorno (movimento
  inverso); movimento de Chamado/OS só é estornado pelo cancelamento do encerramento;
  ajuste gera cabeçalho de inventário + item e movimento `tipo='ajuste'`; entrada
  atualiza o custo do produto quando o custo informado é maior que zero.
- **Numeração**: `biostech_estoque_lote_seq` agrupa os itens de cada operação.

### 2.3 Módulo Compras

Criado por `scripts/sql/05-compras.sql` e carregado por `scripts/etl/compras.php`:

- **Tabelas** (10): `biostech_compras_pedidos`, `_itens`, `_parcelas`, `_cotacoes`,
  `_cotacao_itens`, `_solicitacoes`, `_solicitacao_itens`,
  `biostech_fornecedor_prazos`, `biostech_contas_pagar` e `biostech_compras_log`.
- **Dados migrados**: 2.694 pedidos (236 abertos / 2.458 fechados), 7.316 itens,
  251 parcelas, 184 cotações, 696 itens de cotação, 1.119 solicitações
  (972 abertas / 147 fechadas), 7.071 itens de solicitação, 44 prazos e 298 logs.
- **Telas**: pedidos com filtros, paginação e ficha em abas (dados, itens, parcelas,
  contas a pagar, histórico); formulário multi-item com autocomplete e cálculo de
  desconto/total; fechamento com parcelas e recebimento; cotações com três
  fornecedores por item e mapa comparativo; solicitações internas com sugestão de
  produtos abaixo do mínimo; prazos por fornecedor; três impressões.
- **Operações herdadas do Access**: gerar pedido da solicitação, importar solicitação
  para cotação, transferir item entre pedidos, excluir item, replicar pedido,
  fechar/cancelar fechamento, cancelar/reabrir pedido.
- **Integração**: o fechamento gera entrada de estoque (`origem='compra'`) e títulos
  em `biostech_contas_pagar` na mesma transação; o cancelamento estorna o estoque e
  cancela os títulos logicamente.
- **Numeração**: `PC`, `COT`, `SC` e `CP` na library `Numeracao`.
- **Regras**: `docs/08-regras-compras.md`.

### 2.4 Módulo Orçamentos

Criado por `scripts/sql/06-orcamentos.sql` e carregado por
`scripts/etl/orcamentos.php`:

- **Tabelas** (7 novas + 2 alteradas): `biostech_orcamento_contatos`,
  `biostech_orcamento_servicos_valores`, `biostech_orcamento_log`,
  `biostech_orcamento_dimensionamento_servicos`, `_materiais`, `_cotacoes` e
  `_log`; `biostech_orcamentos` recebeu `resultado`, `data_resultado` e
  `motivo_cancelamento`; `biostech_orcamentos_dimensionamento` recebeu o
  levantamento técnico e o cronograma semanal.
- **Dados migrados**: 2.163 orçamentos, 45.914 itens (renumerados 1..N por
  orçamento), 10 serviços com valor, 13.255 serviços a executar, 50 contatos,
  407 logs; 1.101 dimensionamentos, 16.449 itens, 1.029 levantamentos, 6.711
  serviços e 292 logs. `ORCAMENTO_02_AM` e `ORCAM_DIMEN_05` estavam vazias.
- **Normalização**: `ORCAMENTO_01/01A/01B/01C` unificadas em
  `biostech_orcamentos` com `01B` prevalecendo sobre `01C` campo a campo.
- **Telas**: lista com filtros e paginação; formulário do cabeçalho; ficha do
  orçamento em abas (dados, itens, serviços, serviços com valor, contatos,
  histórico); desconto/acréscimo em massa com restauração; alteração de status;
  impressão; geração de chamado. Dimensionamento com formulário de levantamento
  técnico e cronograma, abas de itens, serviços, materiais e cotações, impressão
  e geração de orçamento.
- **Integração**: o dimensionamento gera orçamento (itens com preço de venda);
  o orçamento aprovado e concluído gera chamado/OS com cópia de itens, serviços
  e equipamento.
- **Numeração**: `ORC` e `DIM` na library `Numeracao`.
- **Regras**: `docs/09-regras-orcamentos.md`.

### 2.5 Módulo Financeiro

Criado por `scripts/sql/07-financeiro.sql` e carregado por
`scripts/etl/financeiro.php` (export `export-financeiro.ps1`):

- **Tabelas** (19): `biostech_contas_pagar`, `_baixas` e `_log`;
  `biostech_contas_receber` e `_baixas`; `biostech_fin_bancos`, `_agencias`,
  `_contas`, `_cartoes`, `_centros_custo`, `_naturezas_operacao`,
  `_status_pagamento`, `_periodicidades`, `_despesas_fixas`, `_cheques`,
  `_movimentos_bancarios`, `_caixa_diario`, `_caixa_movimentos` e `_balanco`.
- **Dados migrados**: 14.465 contas a pagar, 13.959 baixas de CP, 5.484 logs,
  2.328 contas a receber, 2.309 baixas de CR, 73.971 movimentos bancários,
  791 dias de caixa, 6.411 movimentos de caixa e 97 balanços.
- **Normalizações**: CP com `situacao` 1/2/3 derivada de baixa/cancelamento;
  CR ativas (`CONTAS_RECEBER_01`) e arquivadas (`_03`) unificadas, com 6 códigos
  duplicados renumerados a partir de 33.719 e baixas remapeadas por
  `(codigo, parcela)`; movimentos têm base `MOV_BANCARIA_02` + 414 exclusivos de
  `MOV_BANCARIA_01` renumerados de 131.083 a 131.496; caixa com fechamento
  recalculado pela regra da linha "SALDO ANTERIOR". `PAGTO_CHEQUES_01` e
  `MOVICAIXA3` não tinham dados/uso necessários.
- **Telas**: painel com indicadores; CP e CR com filtros, paginação, ficha,
  criação parcelada, edição, baixa (multa/juros/desconto, geração de movimento
  bancário e de caixa), estorno, cancelamento, exclusão e comprovante;
  movimentos bancários; caixa diário com fechamento/reabertura; cheques com
  compensação; balanço; cadastros de apoio; relatórios de CP, CR, fluxo,
  posição bancária e balanço.
- **Numeração**: `CP`, `CR` (um código por parcela) e `MB` na library
  `Numeracao`; após o ETL `CP = 33.722`, `CR = 33.724`, `MB = 131.496`.
- **Permissões**: operador baixa títulos, lança/fecha caixa e consulta;
  admin/gestor criam/editam/excluem títulos, estornam, cancelam, reabrem caixa,
  mantêm cheques/balanço/cadastros e movimentos bancários.
- **Regras**: `docs/10-regras-financeiro.md`.

### 2.6 Módulo Fornecedores

Tela migrada de `FORNECEDOR` (Access), em `/fornecedores`:

- **Tabelas**: `biostech_fornecedores` (2.188), `biostech_fornecedor_prazos`
  (44) e `biostech_produto_fornecedor` (16).
- **Vínculo com clientes**: coluna `biostech_clientes.fornecedor_codigo`
  (índice único parcial), criada por `scripts/sql/01-schema.sql`.
- **Telas**: lista com busca/ordenação/paginação; cadastro com dados
  cadastrais, situação, prazos oferecidos e produtos fornecidos.
- **Copiar para cliente**: cria o cliente a partir do fornecedor; se já existir
  cliente vinculado, atualiza; se já existir cliente com o mesmo CNPJ, apenas
  vincula (sem duplicar). O cliente passa a exibir o selo "Também fornecedor" e
  o fornecedor "Também cliente".
- **Exclusão**: bloqueada quando há pedidos de compra, cotações, contas a pagar
  ou cotações de dimensionamento; prazos e produtos são removidos em transação
  e o cliente vinculado permanece (apenas perde o vínculo).
- **Permissões**: leitura livre para usuários logados; gravação restrita a
  admin/gestor.

### 2.7 Consultas do cliente

Reproduzem as opções do `F_MODULO_CLIENTES` do Access em dois acessos:

- **Menu** (grupo *Consultas*): `OS do Cliente`, `Orcamentos do Cliente` e
  `Notas Fiscais do Cliente` abrem `/clientes/ordens`, `/clientes/orcamentos` e
  `/clientes/notas`, cada uma com busca de cliente por autocomplete
  (`/clientes/buscar_clientes`) e campo de pesquisa próprio.
- **Ficha do cliente** (`/clientes/ver/{id}`): abas *Dados*, *Ordens de servico*,
  *Orcamentos* e *Notas fiscais*.

Telas:

- **Ordens de serviço** (`/clientes/ordens/{id}`): chamados e OS do cliente com as
  colunas Numero, Abertura, **Atendimento**, **Fechamento**, Tipo, Equipamento,
  Status, Situacao atual, Total e Acoes; busca por número/série/defeito; link
  para a ficha em `/chamados/ver/{numero}`. As colunas Status/Situacao usam a
  classe `.compacta` (fonte e padding menores) e Tipo é centralizado.
  *Atendimento* vem de `biostech_chamado_atendimento.atendimento_data`;
  *Fechamento* usa `COALESCE(data_encerramento, data_fechamento, devolucao_data)`
  porque o Access só preencheu `DATA_ENCERRAMENTO` em parte dos registros.
- **Orçamentos** (`/clientes/orcamentos/{id}`): orçamentos do cliente com data,
  atenção, OS, itens, total, situação e resultado. A coluna OS aponta para a ficha
  do chamado quando ele existe; quando o Access registrou o `NUM_OS` mas o chamado
  foi apagado na origem, o número aparece como texto (sem link) na cor normal,
  vindo de `chamado_numero_legado`.
- **Notas fiscais** (`/clientes/notas/{id}`): notas agrupadas por
  `biostech_contas_receber.numero_nota_fiscal` (não existe tabela de NF no
  Access), com emissão, intervalo de vencimentos, parcelas, valor total,
  valor baixado e situação (aberta/parcial/baixada/cancelada). Cada nota abre a
  ficha `/clientes/nota/{id}?numero=...` com as parcelas e o acesso ao título no
  financeiro.

As três listas usam o partial `layouts/paginacao.php` (10 registros por página)
e a mesma busca das telas equivalentes do sistema.

---

## 3. ETL

1. `scripts/etl/export.ps1` — PowerShell **32 bits** + Jet OLEDB, exporta as tabelas
   listadas em `tabelas.json` para JSON UTF-8. Usar cópia do `.mdb` quando o original
   estiver aberto no Access (o Jet bloqueia a tabela).
2. `scripts/etl/instalar.php` — aplica `scripts/sql/01-schema.sql` (placeholder
   `{schema}`) e carrega os JSON via PDO. Idempotente: setores por *upsert*, demais
   tabelas truncadas e recarregadas.
3. `scripts/etl/export-chamados.ps1` + `scripts/etl/chamados.php` — exporta e carrega
   as 14 tabelas de Chamados/OS (`CHAMADO_*`, `EQUIPAMENTO`) descritas em 2.1.
4. `export-financeiro.ps1`/`financeiro.php`, `export-orcamentos.ps1`/`orcamentos.php`,
   `export-compras.ps1`/`compras.php`, `export-estoque.ps1`/`estoque.php` — demais
   módulos, no mesmo padrão (export em PowerShell 32 bits → JSON → PHP/PDO).
5. `scripts/etl/vincular-orcamentos-os.php` — completa o vínculo Orçamento ↔ OS
   depois da carga de chamados. Lê `dados/orcamentos.json` e
   `dados/dimensionamentos.json` e grava `chamado_numero` (quando a OS existe em
   `biostech_chamados`) e `chamado_numero_legado` (número original do Access, mesmo
   quando a OS foi apagada na origem). Idempotente; rodar após `chamados.php`.
6. `scripts/etl/export.ps1 -Config scripts/etl/clientes-especiais.json` +
   `scripts/etl/clientes-especiais.php` — carrega `PRODUTO_CLIENTES_ESPECIAIS_01`
   (preços especiais por cliente). Idempotente por `(cliente_id, produto_id)`;
   1.214 registros gravados (13 produtos da origem não existem na base nova e foram
   ignorados).

Conversões aplicadas na carga:

- Senhas em texto puro → `password_hash(PASSWORD_BCRYPT)`.
- `PRIVILEGIADO` `2` → `admin`; `1` → `gestor`.
- `ACESSO_ALMOX` (texto livre) → booleano.
- `ATIVO` textual (`'N'`, `'NAO'`, `'0'`, `'FALSE'` → inativo).
- Documento do cliente: `CGC_CLI` com fallback para `CPF`.
- UF em maiúsculas; textos truncados nos limites das colunas novas.

---

## 4. Validação executada

```powershell
php scripts\test\smoke.php http://127.0.0.1:8090 DIMENSIONAMENTO dimensionamento   # admin
php scripts\test\smoke.php http://127.0.0.1:8090 ANDRIUS 123123                     # gestor
```

Cobertura: `/login` (200 + token CSRF) → autenticação → `/dashboard`, `/clientes`,
`/produtos`, `/setores`, `/usuarios` → buscas (`maria`, `papel`) → criação, edição e
exclusão de setor → logout. Ambas as execuções passaram; o registro de teste é removido
no final (via HTTP para admin, via banco para gestor).

### 4.1 Teste end-to-end de Chamados/OS

Executado por `teste-fluxo.ps1` (PowerShell, `HttpWebRequest`, `KeepAlive=false`)
contra `http://127.0.0.1:8090`, cobrindo o fluxo abaixo:

1. login; 2. criação do chamado; 3. ficha; 4. intervenção; 5. peça (baixa de estoque);
6. serviço previsto; 7. conclusão do serviço; 8. contato; 9. parcelas (2x R$ 100,00);
10. status 2/1 (em atendimento); 11. status 4/10 (concluída); 12. encerramento;
13. contas a receber; 14. cancelar encerramento (CRs canceladas logicamente);
15. reencerramento; 16. reabertura (mantém `status_codigo = 5`, `reaberto = true`);
17. geração do par (Chamado ↔ Ordem de Serviço, vínculo 1:1); 18. impressão, pesquisa e
autocomplete.

Resultado: **todas as etapas ok**. Os dados do teste (`2026110002`..`2026110014`,
movimentos de estoque, CRs, numeração e equipamento `TESTE-AGENTE-01`) foram removidos
e o saldo do produto 64 restaurado (126 unidades, 2.406 consumidas).

### 4.2 Teste end-to-end do Estoque

Executado por `teste-estoque.ps1` (PowerShell) contra `http://127.0.0.1:8090`, com
produto temporário criado e removido no fim:

1. login; 2. entrada de 10 unidades com custo (saldo 0 → 10, custo atualizado);
3. saída de 3 unidades (saldo 7, consumidas 3); 4. saída de 999 bloqueada por saldo
insuficiente (saldo preservado, sem movimento extra); 5. ajuste/contagem para 8
(saldo 8, consumidas 2, inventário gravado, movimento `ajuste`); 6. estorno da saída
(saldo 11, consumidas 0, saída marcada e apontando para o estorno); 7. estorno da
entrada (saldo 1, consumidas 10); 8. estorno repetido bloqueado; 9. grupo e subgrupo
criados, listados e excluídos; 10. fornecedor criado, produto vinculado e desvinculado;
11. autocomplete de produto e de fornecedor.

Resultado: **41 verificações ok, 0 falhas**. Ao final, produto, movimentos, saldos,
inventários, catálogos `Z9T` e fornecedor de teste foram removidos; produto 64
permaneceu com 126 unidades e 2.406 consumidas.

### 4.3 Teste end-to-end de Compras

Executado por `php scripts/test/compras.php` (PHP + `curl` + `PDO`) contra
`http://127.0.0.1:8090`:

1. login e perfil; 2. criação de pedido com 2 itens (bruto 21,00); 3. edição com 10%
de desconto (total 18,90); 4. fechamento com 2 parcelas, 2 títulos a pagar e 2
movimentos de estoque; 5. saldo, custo, último pedido e última NF do produto;
6. abas e impressão do pedido fechado; 7. cancelamento do fechamento (títulos
cancelados, movimentos estornados, saldo restaurado); 8. replicação, exclusão de itens,
transferência e devolução de item; 9. cancelamento, reabertura e exclusão do pedido;
10. solicitação interna → cotação → pedido gerado; 11. prazos de fornecedor.

Resultado: **100 verificações ok, 0 falhas**. O teste remove os pedidos, cotações e
solicitações criados e restaura produtos e saldos ao final.

Verificações complementares:

- `php -l` em todos os arquivos da aplicação (fora `system/`): **0 erros**.
- `php scripts/diag.php`: **20 verificações ok, 0 problemas** (PHP 7.4.33, `pgsql`,
  `pdo_pgsql`, `mbstring`, `json`, `curl`, `openssl`, conexão, 5 tabelas, 26 senhas
  bcrypt, servidor ativo).
- Página aberta no navegador (Chromium): `/dashboard` com 4 cartões, 12 eventos,
  CSS e favicon carregados, **0 erros de console**.
- Paginação real: 4.773 clientes e 4.522 produtos (25 por página).
- Busca sem acento/caixa: `cabeca` retorna 30 produtos (`CABEÇA ...`).

### 4.4 Teste end-to-end do Financeiro

Executado por `php scripts/test/financeiro.php` (PHP + `curl` + `PDO`) contra
`http://127.0.0.1:8090`:

1. login e perfil; 2. listas, buscas, filtros e paginação de CP/CR; 3. criação de
CP com 2 parcelas (total 100,00), edição, baixa, comprovante, estorno,
cancelamento e exclusão; 4. criação de CR com 2 parcelas e **códigos distintos**,
baixa da parcela 1, comprovante, estorno, cancelamento e exclusão das duas
parcelas; 5. banco, agência e conta bancária; 6. movimentos bancários de crédito
e débito com edição/exclusão; 7. caixa (lançamento, edição, fechamento,
reabertura e exclusão); 8. cheque (emissão, compensação e exclusão); 9. geração e
exclusão de balanço; 10. cadastros de bancos, centros de custo, naturezas,
status, periodicidades, cartões, agências e despesas fixas; 11. limpeza dos dados
de teste.

Resultado: **201 verificações ok, 0 falhas**. O teste remove títulos, baixas,
movimentos, caixa, cheques, balanços e cadastros criados e restaura a numeração
consumida.

---

## 5. Correções feitas nesta etapa

| Problema | Correção |
|---|---|
| `Call to undefined function pg_connect()` | habilitado `extension=pgsql` no `php.ini` |
| `invalid input syntax for type boolean: ""` na carga | booleanos enviados como `'true'`/`'false'` |
| `column "ativo" is of type boolean but expression is of type integer` | `MY_Model` normaliza booleanos em `inserir`/`atualizar` |
| Botões "Editar/Excluir" não apareciam | `MY_Controller::render()` passou a enviar `$usuario` às views antes de renderizar o conteúdo |
| Todos os 26 usuários como `admin` | mapeamento `PRIVILEGIADO` corrigido (`2`=admin, `1`=gestor) |
| Smoke test deixava setor de teste no banco | teste passou a excluir/limpar no final |
| `Numeracao` não gravava (`query()` retorna `TRUE` no CI 2.1.2) | trocado por `pg_query_params` + `RETURNING` |
| `RETURNING` da movimentação de estoque ignorado | passou a usar `currval` da sequência |
| `inteiro_ou_nulo(FALSE)` gravava `0` | normalização de `FALSE` para `NULL` |
| Datas ISO quebravam `strtotime` | `SET datestyle TO 'ISO'` em `MY_Controller`/`MY_Model` |
| Endpoints com formulário só de token caíam no redirect | `MY_Controller::eh_post()` no lugar de `input->post()` como booleano |
| Teste PowerShell gerava `/chamados/ver/=servicos` (400) | `"$numero?aba"` é lido como variável `$numero?aba`; usar `${numero}?` |
| `ativo` booleano recusava `1` no INSERT de fornecedor | cast `?::boolean` no `Estoque_model` |
| `input->post()` devolve `FALSE` e virava código `0` | helper `_vazio()` no `Estoque_model` |
| Constraint `tipo_ck` não aceitava `ajuste` | recriada em `scripts/sql/04-estoque.sql` |
| Autocomplete não achava código em maiúsculas | `lower(p.codigo) LIKE ?` |
| `strtoupper` corrompia UTF-8 nos catálogos | `mb_strtoupper` |
| Desconto do cabeçalho e dos itens colidiam em `desconto_percentual` | cabeçalho renomeado para `desconto_pedido_percentual` |
| Pedido gerado da solicitação era recusado por falta de fornecedor | `salvar_pedido(..., $permite_sem_fornecedor = TRUE)` |
| Itens apontando para produto inexistente sumiam das listagens | `_produtos_existem()` valida os ids antes de gravar |
| Estorno de compra falhava com `value too long for varchar(80)` no `motivo` | `Estoque_model::estornar()` trunca o motivo em 80 caracteres |
| Teste funcional escolhia produto de código `0` | seleção restrita a `id > 0` |
| CP consultava `cl.fone` em clientes (coluna inexistente) | trocado para `cl.telefone` |
| Baixas de CP consultavam `fin_contas.nome` (coluna inexistente) | `COALESCE(titular, banco/agência/conta)` |
| Tela de CP usava `valor_recebido` em vez de `valor_pago` | corrigido no total e na coluna |
| Cadastro de banco enviava `codigo` numérico e o banco é textual | campo texto, `codigo::text` e lista sem cast |
| Conta bancária recusava `valor_limite`/`valor_real` em pt-BR | conversão decimal no `salvar_cadastro` |
| Cadastro de despesa fixa gravava valor/ativo/códigos como texto | conversão por tipo antes do INSERT |
| Exclusão de CP só aparecia para título aberto | liberada também para cancelado (mantido bloqueio com baixa) |
| CR com N parcelas repetia o mesmo `codigo` (violava a PK) | gera um código CR por parcela e retorna a lista `codigos` |
| Teste de CR usava valor de 200,00 na baixa da parcela de 100,00 | baixa e comprovante ajustados para 100,00 |
| Carga de chamados gerava milhares de `mb_strtolower(): Unknown encoding "26"` | limite de 26 caracteres aplicado em `texto()` antes do `mb_strtolower(..., 'UTF-8')` |
| `printf(): Too few arguments` no resumo do log | formato do resumo ajustado aos argumentos |
| Peças renumeradas caíam em conflito e 18 linhas sumiam | `max(array_keys(...))` em vez de `max(...)` sobre os valores booleanos |
| Carga do log fazia um `INSERT` por evento/campo e travava | eventos e log gravados em lote (`jsonb` por cast, ids via `nextval`) |
| Consulta de OS do cliente não tinha data de atendimento nem de fechamento | join em `biostech_chamado_atendimento` e `COALESCE(data_encerramento, data_fechamento, devolucao_data)` na listagem (`Chamado_model::listar`) |
| Prefixo `R$` repetido em todos os valores da interface | removido do helper `moeda_br()`, do helper local do dashboard, dos totais em JS (`compras.js`, `autocomplete.js`) e dos spans de total em compras/estoque. Mantido apenas em textos históricos importados do Access (observações) e nos labels de formulário como unidade |
| Coluna OS da consulta de orçamentos sempre vazia | `orcamentos.php` rodou antes da carga de chamados e anulava o `NUM_OS` por integridade; `$chamados` passou a ser lido também de `dados/chamado_01.json` e o script `vincular-orcamentos-os.php` refez os vínculos (872 OS existentes; 287 apontavam para chamados apagados na origem) |
| OS apagada na origem sumia do orçamento | nova coluna `chamado_numero_legado` guarda o número original do Access e a tela o exibe como texto; `chamado_numero` continua com FK para `biostech_chamados` |
| Ficha da OS não mostrava o orçamento de origem | card "Orcamento" na aba Dados de `/chamados/ver`, usando `biostech_chamados.orcamento_numero` (já carregado do campo `ORC` do Access) |
| Formulário de cliente com campos em 2 colunas e Fax | reorganizado em linhas: nome/fantasia; tipo, CPF/CNPJ, IE, RG; endereço, número, complemento; bairro, cidade, UF, CEP; telefone, celular, e-mail, contato. Campo Fax removido da tela; o valor legado continua preservado por um `input hidden` |
| Botões com cores diferentes (Voltar/Cancelar brancos) | padrão único em `app.css`: `.botao` e `.botao.secundario` usam o mesmo azul `#0b3d6b` (hover `#14558f`) com texto branco; somente as ações destrutivas (`Excluir`, `Remover`, `Estornar`, `Cancelar`…) mantêm o vermelho `#b42318` via `.botao.perigo`. Os botões dos relatórios de impressão (`.acoes button`) também foram padronizados |
| Botão "Inserir/Excluir como Cliente Especial" do Access não existia | nova tabela `biostech_produto_clientes_especiais` (cliente × produto, `perc_lucro` e `valor_venda`); a ficha do cliente ganhou os botões **Inserir como cliente especial** (cria preços para todos os produtos ativos com 15% sobre o custo) e **Excluir como cliente especial**, além da aba **Preços especiais** para incluir/editar/remover produto por produto. O autocomplete de itens de orçamento, dimensionamento e peças de OS passou a devolver o preço especial do cliente quando existir (mostra "preço especial" na lista) |
| Listagem de orçamentos com 25 registros e filtros espalhados | paginação em **8 registros**; filtros em duas linhas: busca, cliente, situação, resultado e **Pesquisar** na primeira; OS, **Dimensionamento** e as duas datas na segunda. Coluna "Itens" trocada por **Orc dim**, com link para o dimensionamento de origem |
| Ficha do orçamento com campos fora de ordem | "Dimensionamento" renomeado para **Orc. Dim**; **Atenção** incluída na mesma linha do cliente; **Material** movido para a linha de **Mão de obra**. Na aba Dados, **Atenção** foi removida e **Data de envio** e **Data de aprovação** ficaram na linha de **Cadastro**, antes dele |
| Composição de valores espalhada em 6 colunas e títulos discretos | valores em duas linhas de 5: material, mão de obra, material de fixação, acréscimo de custos e imposto/nota fiscal; serviços com valor, custos indiretos, desconto, valor máximo do cliente e valor total. Títulos **Composição de valores**, **Observações** e **Ações** com faixa destacada (`.secao-titulo.destaque`), **Gerar chamado** apenas em negrito, e 16px entre o título Observações e o texto |
| Abas com contagem no título, coluna "#" e ações empilhadas nas tabelas da ficha | títulos das abas sem a quantidade de registros (`Itens`, `Servicos a executar`, ...); coluna **#** renomeada para **Item**; campo de descrição do serviço passou a ocupar toda a célula; botões **Salvar** e **Excluir** ficaram lado a lado (`.acoes-tabela`, flex) nas abas Itens, Serviços a executar e Serviços com valor |
| Data de aprovação caindo para a segunda linha na tela de status | os quatro campos (situação, resultado, data de envio e data de aprovação) ficaram na mesma linha: situação ocupa 3 colunas, **resultado 4** e o par de datas 5, com as duas datas num bloco flex de 6px (`.campos-data`) |
| Aba de itens fora do padrão e tabela longa | aba movida para depois de **Serviços a executar** e renomeada **Peças e Materiais**; colunas na ordem **Item, Código, Descrição do produto, Qtde, Valor unit., Valor total** (sem "Unit. original"/"Total original" e sem o prefixo "codigo" no valor); bloco **Incluir item** destacado acima da tabela; tabela com rolagem vertical de 6 linhas e rodapé **Total do material** fixo (`.tabela-rolagem`, `position:sticky`) |
| Formulário de inclusão de item com campos desproporcionais | campo **Complemento** removido (o item passa a usar a própria descrição, com fallback para a descrição do produto); **Produto** reduzido para o tamanho de Unidade e **Descrição do item** ampliada para 8 colunas; Unidade, Quantidade, Valor unitário, Total e o botão **Incluir item** na mesma linha, nessa ordem (2+2+3+3+2 colunas) |
| Percentual em massa sem como voltar apenas o desconto ou apenas o acréscimo | cada bloco ganhou um botão **Restaurar** ao lado do aplicar (`formaction` para `orcamentos/restaurar_percentual/{numero}/{tipo}`, com `title` "Restaura o valor original antes do desconto/acrescimo"). O model recalcula os itens a partir do valor original, mantendo o outro percentual quando ativo, e limpa só o percentual restaurado. Os campos de percentual foram reduzidos a 150px e os botões ficam na mesma linha do campo (`.campo-linha`); os três blocos usam `.percentual-massa` (flex: percentuais dividem o espaço e o bloco **Restaurar valores originais** fica com 290px, com o título e o botão na mesma linha e a ajuda embaixo) |
| Aba Serviços com valor com a tabela antes do cadastro e campos grandes | cadastro movido para cima, em bloco destacado (`.bloco-inclusao`, título **Incluir servico com valor**); **Descrição** em linha própria e **Quantidade** (2), **Valor unitário** (4), **Total** (4) e o botão **Incluir servico** (2) na mesma linha. A tabela passou a usar `.tabela-rolagem`, com 6 registros visíveis, rolagem vertical e rodapé **Total dos servicos** fixo (`position:sticky`) |
| Aba Contatos com a tabela antes do cadastro e campos espalhados | cadastro movido para cima, em bloco destacado (**Registrar contato**): **Data**, **Hora** e **Assunto** na primeira linha; **Funcionario**, **Pessoa de contato** e **Proximo contato** (data + hora no mesmo campo, `.campos-data`) na segunda; **Descricao** e o botão abaixo. Data/hora receberam o mesmo visual dos demais campos nos blocos de inclusão. A tabela mostra 6 registros e passa a rolar verticalmente a partir do sétimo, com cabeçalho fixo |

---

## 6. Pendências e próximos passos

1. **Schema dedicado** `biostech` — depende do DBA (sem `CREATE SCHEMA` hoje).
2. **Staging 1:1 do legado** (`biostech_legacy`) — o DDL completo está em
   `docs/04-schema-legacy-postgresql.sql` (243 tabelas).
3. **Próximos módulos** — matriz completa em `docs/07-plano-conversao-total.md`.
   Chamados/OS, o núcleo do Estoque, Compras, Orçamentos e Financeiro estão
   prontos. Ordem sugerida: Faturamento/NF, Clientes/Escolas (complementos),
   Equipamentos, RH, Frota, Acessos/Permissões.
4. **Normalização de dados sujos** — datas herdadas inválidas (1899, 0202, 0209, 1976,
   2106, 7201, 7202) precisam de quarentena antes de relatórios.
5. **Validação dos perfis** com os usuários do legado (o VBA do formulário `USUARIO` não
   foi recuperado; o mapeamento atual segue a indicação do responsável).
6. **Relatórios** — 25 relatórios Access a reimplementar em HTML/PDF (ver `docs/07`).
7. **Deploy** — Apache com `mod_rewrite` + variáveis de ambiente (`CI_ENV=production`,
   `DB_*`), HTTPS e backup do banco.
