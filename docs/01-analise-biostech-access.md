# Análise dos sistemas Access do BIOSTECH

**Data:** 16/11/2026
**Objeto:** `C:\SISBIOST\Dados\SISBIOSD_01.mdb` (dados) e `C:\SISBIOST\BIOSTECH_A2003H.mdb` (aplicação)
**Objetivo:** levantar o sistema legado para reimplementação web em PHP + CodeIgniter 2.x (release de 28/01/2012)

---

## 1. Sumário executivo

O BIOSTECH é um ERP em Access 2003 (formato `.mdb`), dividido em **front-end** (formulários,
relatórios, consultas e VBA) e **back-end** (apenas tabelas com os dados), ligados por
*linked tables*. Não é um sistema pequeno: são **619 formulários**, **300 relatórios**,
**936 consultas** e **11 módulos VBA**, sobre **247 tabelas** e **2.770 campos**.

A boa notícia é que o desenho de dados é simples e legível (chaves numéricas, nomes
descritivos, tabelas "cabeçalho + itens"). A má notícia é que **as regras de negócio estão
espalhadas em três lugares**: consultas de ação no próprio `.mdb` (451 INSERT/UPDATE/DELETE),
código VBA embutido nos formulários, e macros. Nada disso é acessível por SQL puro.

A conversão é viável em fases, começando por um *staging* 1:1 (cópia fiel do schema) que
permite "levantar" o sistema novo em cima dos dados reais desde a primeira semana, e só
depois normalizando módulo a módulo.

**Esforço estimado:** ~20 semanas (5 meses) para 1 desenvolvedor, entregando os módulos por
fases; 12–14 semanas com 2 desenvolvedores em paralelo a partir da fase 2.

---

## 2. Inventário dos arquivos

| Arquivo | Tamanho | Papel | Conteúdo |
|---|---|---|---|
| `BIOSTECH_A2003H.mdb` | 176 MB | Front-end | 619 forms, 300 relatórios, 11 módulos VBA, 6 macros, 936 queries, 238 tabelas vinculadas + 8 tabelas de sistema |
| `SISBIOSD_01.mdb` | 139 MB | Back-end | 247 tabelas, 2.770 campos, 391 índices, 45 relacionamentos, 425.471 registros |

O front-end praticamente não guarda dados: as 238 tabelas "vinculadas" apontam para
`SISBIOSD_01.mdb`. Por isso, **para migrar dados basta o back-end**; o front-end serve como
especificação funcional (telas + SQL das queries + VBA).

### 2.1 Como os metadados foram extraídos

O back-end bloqueia leitura direta de `MSysObjects`, então a extração foi feita via **DAO**
(`DAO.DBEngine.36`, PowerShell 32 bits), que dá acesso a `TableDefs`, `QueryDefs`,
`Relations` e propriedades de campo. Os artefatos gerados estão em
`C:\Users\seduc\AppData\Local\Temp\opencode\mdb\`:

- `backend-tables.csv`, `backend-fields.csv`, `backend-indexes.csv`, `backend-relations.csv`
- `frontend-tables.csv`, `frontend-fields.csv`, `frontend-queries.csv`, `frontend-queries.sql`
- `frontend-objects.csv` (forms/relatórios/módulos/macros)
- `modules\module_*.txt` (código VBA dos 11 módulos)

---

## 3. Arquitetura atual

```
BIOSTECH_A2003H.mdb  (front-end, 176 MB)
 ├── 619 forms .................. interface e regras (VBA de evento)
 ├── 300 relatórios ............. impressão (bandas, agrupamentos, expressões)
 ├── 936 queries ................ 484 SELECT, 217 APPEND, 138 UPDATE, 96 DELETE, 1 MAKE TABLE
 ├── 11 módulos VBA ............. funções utilitárias e globais
 ├── 6 macros
 └── 238 linked tables ─────────┐
                                ▼
              SISBIOSD_01.mdb  (back-end, 139 MB)
               └── 247 tabelas, 2.770 campos, 425.471 registros
```

Características relevantes:

- **Não há integridade referencial real**: apenas 45 relacionamentos declarados para 247 tabelas.
  Órfãos são esperados.
- **Sem transações/controle de concorrência** decente: numeração de OS, movimento bancário e
  baixa de estoque usam contadores em VBA (`Contador`, `movbanc`, `Contarota`).
- **Sem trilha de auditoria padronizada**: existe `*_LOG_*` em alguns módulos, não em todos.
- **Permissões por tela**, gravadas em 20 tabelas `ACESSO*`/`PERMISSOES_*` com 4–6 campos cada.

---

## 4. Volumetria

| Métrica | Valor |
|---|---|
| Tabelas | 247 (80 vazias) |
| Campos | 2.770 |
| Registros | 425.471 |
| Maior tabela | `MOV_BANCARIA_02` — 73.557 |
| Tabelas com > 10 mil registros | 9 |
| Tabelas com 1–10 mil registros | 30 |
| Módulos funcionais identificados | 13 |

O volume é **pequeno para padrões web** (meio milhão de linhas). Não há necessidade de
particionamento, sharding ou banco NoSQL — qualquer PostgreSQL/MySQL modesto atende com
folga. O desafio é **regra de negócio e tela**, não volume.

### 4.1 Top 20 tabelas

| Tabela | Registros | Campos |
|---|---:|---:|
| `MOV_BANCARIA_02` | 73.557 | 18 |
| `Cópia de MOV_BANCARIA_02` | 71.170 | 13 |
| `ORCAMENTO_02` | 45.914 | 11 |
| `MOV_BANCARIA_01` | 26.783 | 16 |
| `ORCAM_DIMEN_02` | 16.449 | 15 |
| `CONTAS_PAGAR_01` | 14.465 | 33 |
| `CONTAS_PAGAR_02` | 13.959 | 19 |
| `ORCAMENTO_05` | 13.255 | 4 |
| `EQUIPAMENTO` | 11.868 | 14 |
| `CHAMADO_06` | 8.778 | 17 |
| `CHAMADO_05A` | 7.930 | 10 |
| `SOLICITACAO_COMPRA_02` | 7.358 | 15 |
| `SOLICITACAO_COMPRA_02A` | 7.091 | 14 |
| `ORCAM_DIMEN_06` | 6.711 | 4 |
| `MOVICAIXA2` | 6.411 | 15 |
| `CHAMADO_55` | 5.732 | 4 |
| `CONTAS_PAGAR_LOG_01` | 5.484 | 56 |
| `ESTOQUE_02` | 5.474 | 8 |
| `CLIENTES` | 4.773 | 29 |
| `PRODUTO` | 4.522 | 52 |

---

## 5. Mapa de módulos

O mapeamento completo tabela → módulo está em `docs/03-mapa-tabelas-modulos.csv`.
Resumo:

| Módulo | Tabelas | Registros | Tabelas principais |
|---|---:|---:|---|
| Financeiro (CP/CR/mov. bancária/caixa) | 32 | 141.663 | `CONTAS_PAGAR_01/02`, `CONTAS_RECEBER_01..04`, `MOV_BANCARIA_01/02`, `MOVICAIXA1/2` |
| Orçamentos (dimensional) | 18 | 93.618 | `ORCAMENTO_01..05`, `ORCAM_DIMEN_01..07` |
| Legado / auditoria | 24 | 83.129 | `Cópia de MOV_BANCARIA_02`, `PRODUTO1/2/3`, `*_LOG_*`, `*_AM`, `*_BKP` |
| Chamados / OS | 30 | 35.803 | `CHAMADO_01`, `CHAMADO_02..99`, `CHAMADO_05A`, `CHAMADO_06`, `CHAMADO_55` |
| Estoque / Produtos | 40 | 24.650 | `PRODUTO`, `ESTOQUE_01/02`, `GRUPO`, `SUB_GRUPO`, `ESTANTE_01`, `PRATELEIRA_01` |
| Compras | 8 | 19.203 | `SOLICITACAO_COMPRA_01/02/02A`, `COTACAO_PRECOS_01/02` |
| Equipamentos | 6 | 12.076 | `EQUIPAMENTO`, `EQUIPAMENTO_BACKUP_01`, `TIPO_EQUIPAMENTO` |
| Clientes / Escolas | 9 | 7.363 | `CLIENTES`, `ESCOLAS_01`, `MUNICIPIOS_01` |
| Faturamento / NF / Recibos | 9 | 4.019 | `RECIBOS_01`, `NF_*`, `NF_SERVICOS_*` |
| Auxiliares (domínios) | 14 | 2.271 | `SITUACAO`, `PRIORIDADE`, `FINALIDADE`, `MES_ANO`, `TB_DATA` |
| Sistema (descartar) | 9 | 1.038 | `MSys*` |
| RH | 13 | 537 | `FUNCIONARIOS_01..05`, `DEPENDENTES`, `FUNCOES`, `SETORES` |
| Acessos / Permissões | 20 | 64 | `USUARIOS`, `ACESSO*`, `PERMISSOES_MODULO_01` |
| Frota | 15 | 37 | `VEICULOS`, `VEICULOS_*`, `ABASTECIMENTOS` |

### 5.1 Convenção de nomes (importante)

Os sufixos `_01`, `_02`, `_03`… **não são anos**. São **partições estruturais**:

- `_01` → cabeçalho / entidade principal
- `_02`, `_03`, `_04`… → itens, detalhes, versões ou tabelas de apoio
- `A`, `B`, `C` → variantes (impressão, cópia, versão paralela)
- `E` (em formulários) → variante "edição/consulta"

Exemplo: `CHAMADO_01` (OS) → `CHAMADO_02/03/04` (itens/serviços), `CHAMADO_05A` (materiais),
`CHAMADO_06` (movimentação de material), `CHAMADO_55` (histórico), `CHAMADO_10` (64 campos!).

Isso precisa ser respeitado na modelagem nova: não são "tabelas do ano 2001".

---

## 6. Regras de negócio embutidas

### 6.1 Consultas de ação (451 no total)

| Tipo | Qtd | Papel |
|---|---:|---|
| SELECT | 484 | relatórios, telas, pesquisa |
| APPEND (INSERT) | 217 | inclusão de itens, cópias, populações |
| UPDATE | 138 | baixa de estoque, situação, totais, contadores |
| DELETE | 96 | cancelamento, limpeza de temporárias |
| MAKE TABLE | 1 | `CRIA_BF_01` (tabela de balanço financeiro) |

Muitas queries referenciam controles de formulário (`[Formulários]![F_...]![campo]`), ou seja,
**só funcionam dentro da tela**. Na conversão viram parâmetros de aplicação.

### 6.2 Funções VBA reaproveitáveis

| Módulo | Funções | Uso na conversão |
|---|---|---|
| `funcoes_aux` | `VALPARC`, `DTVENC`, `DTVENC_ANTERIOR`, `TestaCPF`, `TestaCGC`, `DigitoPROD`, `QuantidadeDiasMes`, `UltimoDiaMes`, `dtpagjuro`, `SERVEXEC` | parcelamento, vencimentos, validação de documento, dígito de produto |
| `gera_os` | `Contador`, `movbanc`, `Contarota` | numeração de OS, movimento bancário e rota → substituir por sequence/transação |
| `vcalutilits` | `AddDiasComercial`, `ContaDiasUteis`, `PrimeiroProxMes`, `ExtensoData` | prazos em dias úteis |
| `Definições` | `Inteiro_Extenso`, `Dinheiro_Extenso`, `HORASTRABALHADAS`, `DS` | recibo/cheque por extenso,espelho de ponto |
| `Texto` | `TiraAcento`, `TrataTexto`, `Enquadra` | normalização de texto |
| `Extenso` | `PassaExtenso` | valor por extenso |
| `Globais` | ~70 variáveis globais (`TELA`, `USUARIO`, `VNIVEL`, `VSETOR`…) | estado de sessão → sessão PHP |
| `Codigo_Drives/Diretorios/Explorer/Caminho_Arquivo` | APIs Windows | **descartar** (não existe no web) |

> `Globais` é o retrato do modelo de estado: o sistema carrega usuário, nível, setor e o
> "contexto da tela atual" em variáveis globais. No web isso vira sessão + request.

### 6.3 Padrões de dados que revelam regra

- `ESTOQUE_02.QTDE_01..QTDE_06` — **seis saldos por produto**, não um. A baixa de estoque
  subtrai de `QTDE_01` **e** `QTDE_03` ao mesmo tempo (`C_ATUALIZA_ESTOQUE_PROD_TEMP_02`),
  o que sugere *saldo físico* e *saldo disponível/comprometido*. A semântica exata está no
  VBA dos formulários `F_SAI_PROD_TEMP_01/02` e `BAIXA_ESTOQUE_*`; **precisa ser confirmada
  antes de normalizar**.
- `ESTOQUE_01` repete os 6 saldos **por fornecedor** (`COD_FORN`), o que é um segundo eixo
  de controle (estoque por fornecedor).
- `CHAMADO_01.NUM_OS` é **texto** (`Text(16)`), não numérico — numeração com prefixo/ano.
- `CHAMADO_01` guarda 8 valores monetários separados (`VALMAT`, `VALSERV`, `VALIMPOSTO`,
  `VALMOBRA`, `VALDESC`, `VALFATUG`, `MAT_FIXACAO`, `ACRESCIMO_CUSTO`) e 8 carimbos de
  usuário/data (cadastro, alteração, fechamento, encerramento).
- `PRODUTO` tem 52 campos, dos quais ~20 são "última compra / última saída / último pedido"
  — desnormalização clássica que deve virar consulta derivada.
- `SOLICITACAO_COMPRA_01` tem 90 campos e `CHAMADO_LOG_01` 87: são tabelas "largas" que
  misturam cabeçalho, itens e histórico.

---

## 7. Qualidade dos dados

| Problema | Evidência | Impacto |
|---|---|---|
| **Datas inválidas** | `CONTAS_PAGAR_01` inicia em 19/06/**0202**; `CONTAS_RECEBER_01` vai até 11/10/**7201**; `ORCAM_DIMEN_01` 29/01/**1976** a 19/10/**7202**; `RECIBOS_01` 05/02/**0209**; `SOLICITACAO_COMPRA_01` 30/12/**1899** a 28/07/**2106** | ETL precisa *quarentena*: registrar e corrigir/nular, nunca falhar o lote |
| **80 tabelas vazias** | `CHAMADO_07/08/11`, `ORCAM_DIMEN_04/05/07`, `PLANO_SAUDE`, `FERIAS`… | Avaliar descarte |
| **Duplicatas** | `PRODUTO`, `PRODUTO1`, `PRODUTO2`, `PRODUTO3`; `Cópia de MOV_BANCARIA_02` (71 mil linhas); `SOLICITACAO_COMPRA_02` × `_02A` | ~85 mil registros redundantes |
| **Sem FKs efetivas** | 45 relações para 247 tabelas | Órfãos; toda carga precisa de validação |
| **Campos "flag" como texto** | `APAGA`, `FECHATOT`, `BAIXA_AUTOMATICA`, `ATIVO`, `EXCLUIR` (Text(1)/Text) | Normalizar para `boolean` |
| **Nomes reservados** | colunas `DATA`, `VALOR`, `TIPO`, `CODIGO`, `DESCRICAO`, `SITUACAO` | Exigem aspas no PostgreSQL; renomear no schema novo |
| **Acentos em nome de tabela** | `Cópia de MOV_BANCARIA_02` | ETL sempre em UTF-8 |
| **Senhas** | `USUARIOS.SENHA` em texto (26 usuários cadastrados) | Reset obrigatório na virada |
| **Permissões fragmentadas** | 20 tabelas `ACESSO*`/`PERMISSOES_*` com 1–3 linhas cada | Redesenhar como RBAC único |

---

## 8. Riscos

| # | Risco | Severidade | Mitigação |
|---|---|---|---|
| R1 | Escopo: 619 forms + 300 relatórios não cabem numa entrega | Alta | Fatiar por módulo; congelar telas legadas não migradas |
| R2 | Regra real está no VBA de formulário, não nas queries | Alta | Exportar o código de todos os forms (`SaveAsText(3, …)`) e extrair regras antes de codificar cada módulo |
| R3 | Data inválida quebra ETL e relatório | Média | Camada de quarentena + views de saneamento |
| R4 | Duplicatas (`PRODUTO1/2/3`, `Cópia de …`) com dados divergentes | Média | Comparar contagens/última data antes de decidir descarte; manter staging |
| R5 | CodeIgniter 2.x + PHP 7.4 tem incompatibilidades (drivers `mysql_*`, `mcrypt`, sessão) | Média | Fixar PHP 7.4.33 (já instalado), patch mínimo no core, evitar `mcrypt`; ou avaliar CI 2.2.6 |
| R6 | Numeração de OS/movimento concorrente | Média | Substituir contadores VBA por `SEQUENCE` + transação com `FOR UPDATE` |
| R7 | Relatórios com layout legal/fiscal | Média | Biblioteca de PDF (dompdf/mPDF) + templates reproduzindo o layout |
| R8 | Perda de histórico durante paralelo | Alta | Rodar legado e novo em paralelo por 1–2 meses; carga incremental final |

---

## 9. O que descartar (proposta)

- Tabelas `MSys*` (9) — sistema.
- `Cópia de MOV_BANCARIA_02` (71.170) — duplicata literal; confirmar antes.
- `PRODUTO1`, `PRODUTO2`, `PRODUTO3` — versões antigas do cadastro de produto.
- `*_AM`, `*_BKP`, `*_ARQ_MORTO`, `BACKUP_01/02`, `EQUIP_BACKUP_01`, `EQUIPAMENTO_BACKUP_01`.
- `TRANSACAO_01`, `TESTE*`, `LUKE`, `Consulta1/2`, `T1`.
- Módulos VBA de sistema de arquivos (`Codigo_Drives`, `Codigo_Diretorios`,
  `Codigo_Explorer_Form`, `Codigo_Caminho_Arquivo`) — sem equivalente web.

Manter `*_LOG_*` como **histórico/auditoria** (útil para rastrear o que mudou no legado).

---

## 10. Conclusão

1. **Migrar só o back-end** é suficiente para os dados; o front-end é especificação.
2. O modelo é **simples e legível**, mas **desnormalizado e sem integridade** — a conversão
   precisa de staging + normalização incremental, não de tradução automática.
3. O esforço está concentrado em **telas e regras**, não em volume.
4. O caminho de menor risco é: **staging 1:1 → views de compatibilidade → módulo piloto →
   fases**. Assim o sistema novo já nasce com dados reais e relatórios equivalentes.

O plano de execução está em `docs/02-plano-conversao-php-ci.md`.
