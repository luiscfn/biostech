# Plano de conversão total — BIOSTECH Access → PHP/CodeIgniter

**Data:** 16/11/2026
**Projeto:** `C:\desenvolvimento\PROJETOS_PHP\biostech`
**Objetivo:** reimplementar na web **todas** as funcionalidades do BIOSTECH Access 2003
(telas, consultas, pesquisas, relatórios, inclusões, alterações, exclusões e cálculos),
com paridade funcional e dados migrados do back-end `SISBIOSD_01.mdb`.

---

## 1. Situação de partida

| Item | Situação |
|---|---|
| Base web (login, dashboard, clientes, produtos, setores, usuários, auditoria) | pronto |
| Módulo piloto Chamados / OS | **pronto** (fluxo completo testado) |
| Módulo Estoque / Produtos | **pronto (núcleo)** (entradas, saídas, inventário, estorno, catálogos, fornecedores) |
| Módulo Financeiro | **pronto** (CP/CR, baixas, movimentos, caixa, cheques, balanço, cadastros e relatórios) |
| Tabelas criadas | 27 de Chamados/OS/Estoque + 11 tabelas próprias do Estoque + 5 base + 19 financeiras + domínios |
| Dados migrados | setores, usuários, clientes, produtos, saldos, fornecedores e catálogos |
| Regras documentadas | `docs/06-regras-chamados-os.md` (RN-001..283, VD-01..72, AC-01..24, D-01..D-10) |
| Extração VBA | 107 forms e 25 relatórios **do módulo Chamados/OS** (filtro de nome) |
| Banco | schema `material_grafico`, prefixo `biostech_` (DBA ainda não liberou schema próprio) |

> A extração VBA foi filtrada por nome (`CHAMADO|ORDEM_SERVICO|ABRE_OS|...`). Os demais
> módulos exigem nova execução de `extract_vba.ps1` com outro filtro — o script e o
> inventário estão descritos no item 5.

---

## 2. Inventário dos módulos

Fonte: `docs/03-mapa-tabelas-modulos.csv` (243 tabelas, 425.471 registros no back-end).

| # | Módulo | Tabelas | Registros | Situação atual | Fase |
|---:|---|---:|---:|---|---|
| 1 | Chamados / OS | 30 | 35.803 | **concluído (piloto)** | F2 |
| 2 | Clientes / Escolas | 9 | 7.363 | parcial (clientes migrados; contratos/escolas/municípios pendentes) | F1 |
| 3 | Estoque / Produtos | 40 | 24.650 | **concluído (núcleo)**: saldos, entradas, saídas, inventário, estorno, catálogos e fornecedores | F4 |
| 4 | Orçamentos | 18 | 93.618 | **concluído**: orçamento comercial (itens, desconto/acréscimo em massa, serviços, contatos, histórico, status, impressão, geração de chamado) e dimensionamento (levantamento, cronograma, itens, serviços, geração de orçamento) | F3 |
| 5 | Financeiro | 32 | 141.663 | **concluído**: contas a pagar/receber, baixas, estornos, movimentos bancários, caixa, cheques, balanço, cadastros e relatórios | F5 |
| 6 | Compras | 8 | 19.203 | **concluído**: pedidos, itens, parcelas, fechamento/recebimento, cotações (3 fornecedores), solicitações internas, prazos e contas a pagar | F4/F6 |
| 7 | Faturamento / NF | 9 | 4.019 | pendente | F6 |
| 8 | Equipamentos | 6 | 12.076 | pendente (só a ficha usada pelo Chamado) | F1/F2 |
| 9 | Auxiliares | 14 | 2.271 | parcial (domínios importados 1:1) | contínuo |
| 10 | RH | 13 | 537 | pendente | F7 |
| 11 | Frota | 15 | 37 | pendente | F7 |
| 12 | Acessos / Permissões | 20 | 64 | parcial (admin/gestor e permissões básicas) | F1 |
| 13 | Legado / Auditoria | 24 | 83.129 | descartar ou arquivar (backups e logs antigos) | — |
| 14 | Sistema (MSys*) | 9 | 1.038 | descartar | — |

---

## 3. Matriz de conversão por módulo

### 3.1 Chamados / OS — concluído

- **Telas:** abertura, ficha em abas (dados, equipamento, atendimento, intervenções,
  peças, serviços, contatos, financeiro, histórico), mudança de status, encerramento,
  reabertura, cancelamento de encerramento, geração do par Chamado ↔ OS, impressão,
  pesquisa e autocomplete.
- **Cálculos:** material, serviço, mão de obra, imposto, acréscimo, fixação, desconto,
  faturado, parcelas e contas a receber.
- **Regras:** RN-001..283; ver `docs/06-regras-chamados-os.md`.

### 3.2 Clientes / Escolas

- **Tabelas:** `CLIENTES`, `CLIENTES_EQUIP`, `CLIENTE_CONTRATO`, `CLIENTE_PROBLEMA`,
  `CONTRATO_01`, `ESCOLAS_01`, `MUNICIPIOS_01`, `TIPO_CONTRATO`, `ULTIMO_CLIENTE_01`.
- **Telas:** ficha completa do cliente, equipamentos do cliente, contratos, problemas,
  escolas e municípios, histórico de atendimentos.
- **Pendências:** campos ainda não migrados (contratos, escola, município, equipamentos
  do cliente); exclusão lógica; consultas por contrato/escola.

### 3.3 Estoque / Produtos

- **Tabelas:** `PRODUTO` e variações (`PRODUTO1..3`, `PRODUTO_NOVO`, `PRODUTO_USADO`),
  `ESTOQUE_01..03`, `ESTOQUE_USADO`, `ESTOQUE_VENDAS`, `ENTRADA*`, `SAIDA*`,
  `MATERIAL_01/02`, `GRUPO`, `SUB_GRUPO`, `FINALIDADE`, `POSICAO`, `ESTANTE_01`,
  `PRATELEIRA_01`, `FERRAMENTAS_01`, `KIT_FERRAMENTA_01..04`, `PROD_FORNEC*`.
- **Telas:** entrada (compra, devolução, usado), saída (venda, uso interno, perda),
  transferência, inventário/ajuste, consulta de saldos, mínimo/máximo, kits e
  ferramentas, fornecedores do produto.
- **Regras:** saldo nunca negativo (D-04); movimentos imutáveis; custo médio; reserva;
  baixa automática pelo encerramento (já implementada para Chamados).
- **Já pronto (núcleo):** saldos e movimentos com lote, entrada/saída multi-item,
  ajuste por contagem com cabeçalho de inventário, estorno por movimento inverso,
  catálogos (grupos, subgrupos, posições, estantes, prateleiras, finalidades),
  fornecedores e vínculo produto×fornecedor, autocomplete e ficha do produto.
  Cargas: 2.188 fornecedores, 16 vínculos, 60 grupos, 468 subgrupos, 3 posições,
  37 estantes, 900 prateleiras e 21 finalidades.
- **Pendências:** transferência entre locais, reserva, custo médio ponderado, kits e
  ferramentas (`KIT_FERRAMENTA_01..04`), mínimo/máximo automático e relatórios.

### 3.4 Orçamentos — concluído

- **Tabelas:** `ORCAMENTO_01/01A/01B/01C` (unificadas em `biostech_orcamentos`),
  `ORCAMENTO_02` (itens), `ORCAMENTO_03` (serviços com valor),
  `ORCAMENTO_04` (contatos), `ORCAMENTO_05` (serviços a executar),
  `ORCAMENTO_LOG_01`; `ORCAM_DIMEN_01/02/03` (cabeçalho + levantamento +
  cronograma), `ORCAM_DIMEN_04` (cotações, vazia no legado),
  `ORCAM_DIMEN_06` (serviços), `ORCAM_DIMEN_07` (materiais, vazia no legado) e
  `ORCAM_DIMEN_LOG_01`. `ORCAMENTO_02_AM` e `ORCAM_DIMEN_05` estavam vazias e
  não foram criadas.
- **Telas:** lista com filtros/paginação; formulário do cabeçalho; ficha em abas
  (dados, itens, serviços, serviços com valor, contatos, histórico); desconto e
  acréscimo em massa com restauração; status; impressão; geração de chamado/OS.
  Dimensionamento: formulário de levantamento e cronograma, abas de itens,
  serviços, materiais e cotações, impressão e geração de orçamento.
- **Cálculos:** cascata `AC_MO → AC_MO_MF → AC_MO_MF_CT → AC_MO_MF_CT_NF →
  SUBTOTAL → VALTOTAL`; percentuais prevalecem sobre valores digitados;
  `total = quantidade × valor unitário` nos itens; originais preservados para o
  desconto/acréscimo em massa.
- **Integração:** dimensionamento → orçamento (itens com `valor_venda`) e
  orçamento → chamado (cópia de itens, serviços e equipamento, com bloqueio de
  duplicidade).
- **Regras:** `docs/09-regras-orcamentos.md`.
- **Dependências:** Clientes, Produtos, Chamados.

### 3.5 Financeiro — concluído

- **Tabelas:** `CONTAS_RECEBER_01..04` (unificadas), `CONTAS_PAGAR_01/02`,
  `CONTAS_PAGAR_LOG_01`, `MOV_BANCARIA_01/02`, `MOVICAIXA1/2`,
  `BANCOS`, `AGENCIA`, `CONTA_BANCARIA`, `CARTOES`, `CENTRO_CUSTOS`,
  `NAT_OPERACAO`, `STATUS_PAGAMENTO`, `DESPESAS_FIXAS`,
  `PAGTO_CHEQUES_01` e `TB_BALANCO_FINANC_01`.
- **Telas:** painel; contas a pagar e a receber (listas, filtros, fichas,
  criação parcelada, edição, baixa com multa/juros/desconto, estorno,
  cancelamento, exclusão e comprovante); movimentos bancários; caixa diário com
  fechamento e reabertura; cheques com compensação; balanço; cadastros de apoio;
  relatórios de CP, CR, fluxo de caixa, posição bancária e balanço.
- **Cálculos:** rateio de parcelas com ajuste na última; `valor_atualizado =
  valor + multa + juros`; `valor_pago/recebido = valor + multa + juros -
  desconto`; saldo bancário `C` soma e `D` subtrai; fechamento do caixa
  `abertura + entradas - saídas` (ou `entradas - saídas` quando a abertura está
  lançada como "SALDO ANTERIOR").
- **Integração:** baixas de CP/CR podem gerar movimento bancário e lançamento de
  caixa; títulos de Compras e Chamados/OS são baixados nas mesmas telas.
- **Normalizações:** CP com situação 1/2/3; CR arquivadas unificadas com 6
  códigos renumerados; movimentos com 414 inclusões e numeração até 131.496.
- **Regras:** `docs/10-regras-financeiro.md`.

### 3.6 Compras — concluído

- **Tabelas Access:** `SOLICITACAO_COMPRA_01/01A/02/02A`, `COTACAO_PRECOS_01/02`,
  `FORNECEDOR_PRAZOS`, `SOLICITACAO_COMPRA_LOG_01/01A` e `CONTAS_PAGAR_01`
  (no Access, `_01/_02` são o pedido e `_01A/_02A` a solicitação interna).
- **Tabelas novas (10):** ver `scripts/sql/05-compras.sql`.
- **Telas:** pedidos (lista, ficha em abas, formulário multi-item, fechamento),
  cotações (3 fornecedores por item e mapa comparativo), solicitações internas
  (com sugestão de produtos abaixo do mínimo), prazos de fornecedor e três impressões.
- **Operações herdadas:** `F_PEDIDO_COMPRA_01A`, `F_CONFIRMA_COTACAO_PRECO_01`,
  `F_TRANSFERE_ITEM_PEDIDO_COMPRA_01`, `F_CANCELA_ITEM_PEDIDO_COMPRA_01`,
  `C_replica_PEDIDO_COMPRA_01`, `F_CONFIRMA_PEDIDO_COMPRA_011` e cancelamento do
  fechamento.
- **Integrações:** fechamento gera entrada de estoque e títulos a pagar na mesma
  transação; cancelamento estorna estoque e cancela títulos logicamente; atualiza
  custo, último pedido, última NF e preço de venda do produto.
- **Numeração:** `PC`, `COT`, `SC` e `CP` (library `Numeracao`).
- **Regras:** `docs/08-regras-compras.md`.
- **Pendências:** gerar pedido diretamente da cotação aprovada; 17 dos 20 relatórios do
  módulo; baixa financeira dos títulos (módulo Financeiro); recebimento parcial por NF.

### 3.7 Faturamento / NF

- **Tabelas:** `NF_CSF_01`, `NF_ENTPROD_01/02`, `NF_SERVICOS_B01..03`, `RECIBOS_01`,
  `SERVICOS_GERAIS_01/02`.
- **Telas:** emissão de NF de produto e serviço, recibo, faturamento em lote, cancelamento,
  reimpressão, integração com contas a receber.
- **Observação:** confirmar se a emissão fiscal continua no sistema atual ou será apenas
  registro/impressão; não reimplementar SEFAZ sem decisão formal.

### 3.8 Equipamentos

- **Tabelas:** `EQUIPAMENTO`, `EQUIPAMENTO_BACKUP_01`, `EQUIP_BACKUP_01`,
  `TIPO_EQUIPAMENTO`, `STATUS_EQUIP`, `TROCA_OLEO`.
- **Telas:** cadastro por número de série, histórico de chamados, garantia, empréstimo,
  troca de óleo, situação.
- **Já pronto:** `biostech_equipamentos` e vínculo com o Chamado.

### 3.9 Auxiliares

- **Tabelas:** `FORNECEDOR`, `FORNECEDOR_PRAZOS`, `AUTORIZADAS`, `TIPO_GARANTIA`,
  `TIPO_CAPTACAO`, `TIPO_VENDA`, `SITUACAO`, `ESTCIVIL`, `MENSAGEM_01/02`, etc.
- **Ação:** manter os domínios em `biostech_dominios` com o código legado 1:1 e criar as
  telas de manutenção que o Access oferece (incluir/alterar/desativar).

### 3.10 RH

- **Tabelas:** `FUNCIONARIOS_01..05`, `FUNCOES`, `SETORES`, `DEPENDENTES`, `FERIAS`,
  `ATESTADOS`, `BENEFICIOS`, `PLANO_SAUDE`, `DEPADM_01`.
- **Telas:** ficha do funcionário, dependentes, férias, atestados, benefícios, plano de
  saúde, relatórios.
- **Dependências:** Acessos/Permissões (usuário ↔ funcionário já existe).

### 3.11 Frota

- **Tabelas:** `VEICULOS`, `VEICULOS_*`, `ABASTECIMENTOS`, `MAPA_ROTA_01`,
  `TIPO_COMBUSTIVEL`.
- **Telas:** cadastro de veículos, documentação, revisões, ocorrências, serviços gerais,
  abastecimentos, mapa de rota, situação de pagamento.

### 3.12 Acessos / Permissões

- **Tabelas:** `USUARIOS`, `PERMISSOES_SISTEMA_01/02`, `PERMISSOES_MODULO_01/02`,
  `ACESSO*`, `USUARIO_ATUAL`.
- **Telas:** usuários, perfis, permissões por módulo/tela/ação, acesso ao almoxarifado,
  troca de senha, bloqueio por tentativas.
- **Pendência:** D-08 (5 perfis) precisa de validação com o responsável.

### 3.13 Legado / Auditoria

- Tabelas de backup e logs antigos (`BACKUP_*`, `*_LOG_*`, `*_BKP`, `TRANSACAO_01`).
- **Decisão:** não migrar para as tabelas de negócio; se houver valor histórico, carregar
  em `biostech_legacy` (staging) apenas para consulta.

### 3.14 Sistema (MSys*)

- Tabelas internas do Access. **Descartar.**

---

## 4. Regras transversais

1. **Domínios** — todo código legado é preservado (`biostech_dominios`), com descrição,
   ordem e flag `ativo`. Nunca renumerar.
2. **Numeração** — `Numeracao` atômica por tipo/competência. Novos tipos a criar:
   `ORC`, `CP`, `NF`, `REC`, `SC` (solicitação de compra), `PC` (pedido de compra).
3. **Auditoria** — `biostech_auditoria` para login/logout e CRUD; `biostech_chamado_log`
   para alteração por campo. Replicar o padrão de log por campo nos módulos sensíveis
   (financeiro, estoque, orçamento).
4. **Exclusão** — preferir cancelamento/inativação com motivo a `DELETE` físico.
   Intervenções e movimentos de estoque são imutáveis (trigger).
5. **Perfis** — D-08 pendente; hoje `admin` e `gestor`, com checagem por rota.
6. **Datas** — `SET datestyle TO 'ISO'` em toda conexão (herdado de `MY_Controller` e
   `MY_Model`); datas inválidas do legado vão para quarentena.
7. **Estoque** — saldo nunca negativo (D-04); baixa automática somente no encerramento;
   estorno ao cancelar encerramento.
8. **Par Chamado ↔ OS** — vínculo 1:1 com bloqueio (D-06); geração cruzada.
9. **Reabertura** — mantém `status_codigo` e usa `reaberto` (RN-015); cancelamento de
   encerramento é a operação que restaura o status anterior.
10. **Encoding** — UTF-8 em PHP, HTML e JSON; PostgreSQL em UTF-8.
11. **Segurança** — CSRF do CI 2.1.2, `eh_post()` para formulários só com token,
    `password_hash`/`password_verify`, escape de saída com `html_escape`.

---

## 5. Método de trabalho por módulo

Checklist obrigatório (repetir para cada um dos 14 módulos):

1. **Extração** — rodar `extract_vba.ps1` com filtro de nomes do módulo e exportar as
   consultas (`DAO.QueryDefs`) para SQL textual.
2. **Regras** — criar `docs/NN-regras-<modulo>.md` com RN (regras), VD (validações),
   AC (achados/divergências) e decisões.
3. **Modelagem** — DDL incremental em `scripts/sql/NN-<modulo>.sql`, preservando códigos
   legados; aplicar com `scripts/sql/aplicar.php`.
4. **ETL** — `scripts/etl/<modulo>.php` a partir de JSON exportado por
   `scripts/etl/export.ps1` (PowerShell 32 bits + Jet OLEDB).
5. **Backend** — model (`application/models/`), controller e rotas; transações e numeração
   atômica.
6. **Frontend** — views com o layout existente, abas quando a tela do Access tiver abas,
   autocomplete, máscaras e validação.
7. **Relatórios** — reimplementar os relatórios do módulo em HTML para impressão
   (e PDF via impressão do navegador), com os mesmos agrupamentos e totais.
8. **Teste end-to-end** — script PowerShell no padrão `teste-fluxo.ps1`, cobrindo inclusão,
   alteração, exclusão/cancelamento, cálculos, pesquisas e relatórios.
9. **Limpeza** — remover dados do teste e restaurar saldos/numeração.
10. **Documentação e commit** — atualizar `docs/05` e este plano; commit local (sem push).

---

## 6. Ordem de execução

| Fase | Módulos | Entrega |
|---|---|---|
| F1 | Acessos/Permissões, Clientes/Escolas, Equipamentos | cadastros completos e permissões |
| F2 | Chamados/OS | **concluído** |
| F3 | Orçamentos | **concluído** — orçamento comercial e dimensionamento |
| F4 | Estoque/Produtos, Compras | **Estoque concluído (núcleo)**; **Compras concluído** |
| F5 | Financeiro | **concluído** — CP/CR, baixas, caixa, banco, cheques, balanço e relatórios |
| F6 | Faturamento/NF, Compras (recebimento) | NF, recibos, faturamento em lote |
| F7 | RH, Frota | fichas, férias, benefícios, veículos e abastecimentos |
| F8 | Relatórios gerais, limpeza e corte | paridade final e desligamento do Access |

> Dependências: Orçamentos e Chamados usam Clientes/Produtos; Compras geram Estoque e
> Financeiro; Faturamento consome Contas a Receber; RH alimenta Acessos.

---

## 7. Relatórios

Foram extraídos **25 relatórios** do módulo Chamados/OS:

- `R_ANALISE_ATENDIMENTO_01A/01B`
- `R_AUDITORIA_OS_01A/01B`, `R_AUDITORIA_OS_SEM_VALOR_01A`
- `R_ORDEM_SERVICO_01/02/02A/02B`
- `R_POSICAO_OS_01`, `R_QTDE_OS_01`
- `REL_CHAMADO_GERAL_03_A_02`, `REL_CHAMADO_L1*`
- `REL_ORCAMENTO_03SA/03SAD/03SD/SP_03/SP_03D_CHAMADO`
- `REL_ORDEM_SERVICO_A1/A1_G1/GERAL_03_A_NOVO/L1_NOVO`

Estratégia: uma view PHP por relatório, com filtros equivalentes aos parâmetros do
Access, saída HTML `@media print` e opção de PDF pelo navegador. Nos demais módulos, os
relatórios serão levantados na extração de cada um (o front-end tem ~300 relatórios).

No módulo Compras foram levantados **20 relatórios**; já convertidos: pedido de compra,
cotação (mapa comparativo) e solicitação interna. Faltam os relatórios por período,
fornecedor, produto e curva de preços.

No módulo Orçamentos já foram convertidas as duas impressões (orçamento comercial e
dimensionamento). Os relatórios `REL_ORCAMENTO_03*` do Chamados/OS reaproveitam os
mesmos dados e entram no lote de relatórios gerais (F8).

---

## 8. Critérios de aceite por módulo

- Todas as telas do Access correspondente têm rota e tela web equivalentes.
- Inclusão, alteração, exclusão/cancelamento e consulta funcionam com as mesmas regras.
- Cálculos conferem centavo a centavo com o Access para uma amostra de registros reais.
- Pesquisas e filtros retornam os mesmos conjuntos (com ordenação equivalente).
- Relatórios do módulo impressos com os mesmos agrupamentos, totais e quebras.
- Dados migrados conferem em contagem e somatórios.
- `php -l` sem erros, `diag.php` sem problemas e teste end-to-end 100% ok.
- Nenhum dado de teste permanece no banco.
- Documentação de regras e status atualizada; commit local feito.

---

## 9. Riscos e mitigação

| Risco | Mitigação |
|---|---|
| 619 forms / 300 relatórios / 936 queries no front-end | conversão incremental por módulo, com filtro de extração |
| Regras escondidas em VBA e consultas de ação | extrair VBA + `QueryDefs` antes de codificar; documentar RN/AC |
| Dados sujos (datas 1899/0202/1976/2106/7201/7202, textos livres) | quarentena e normalização na carga |
| Numeração concorrente | `INSERT ... ON CONFLICT ... RETURNING` por tipo/competência |
| Divergência de cálculo financeiro | testes de paridade com amostra real antes de cada entrega |
| Dependência do DBA para schema próprio | prefixo `biostech_` e `DB_SCHEMA` configurável |
| Perfis de acesso indefinidos (D-08) | validar com o responsável antes de F1 |
| Emissão fiscal (NF) | decidir entre registro/impressão e integração SEFAZ antes de F6 |

---

## 10. Próximos passos imediatos

1. **Commit local** do módulo Financeiro (sem push).
2. **Compras — complementos**: gerar pedido da cotação aprovada, recebimento parcial
   por NF e os relatórios restantes do módulo.
3. **Orçamentos — complementos**: cotações de fornecedor do dimensionamento e
   relatórios gerais (`REL_ORCAMENTO_03*`) no lote de F8.
4. Pendências do Estoque: transferência, reserva, custo médio, kits/ferramentas e relatórios.
