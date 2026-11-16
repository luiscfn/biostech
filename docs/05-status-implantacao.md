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
| Clientes (lista, busca, filtro, paginação, ficha, CRUD) | `/clientes` | pronto |
| Produtos (lista, busca, ficha) | `/produtos` | pronto |
| Setores (lista, busca, CRUD) | `/setores` | pronto |
| Usuários (lista, CRUD, senha, ativar/desativar) | `/usuarios` | pronto |
| Chamados / OS (ficha, intervenções, peças, serviços, contatos, financeiro, status, encerramento, reabertura, par, impressão) | `/chamados` | **pronto (piloto)** |
| Estoque (saldos, ficha, movimentos, entradas/saídas multi-item, ajuste/inventário, estorno, catálogos, fornecedores) | `/estoque` | **pronto (núcleo)** |
| Orçamentos, Compras, Financeiro, Faturamento, RH, Frota | — | "em breve" no menu |

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

---

## 3. ETL

1. `scripts/etl/export.ps1` — PowerShell **32 bits** + Jet OLEDB, exporta as tabelas
   listadas em `tabelas.json` para JSON UTF-8. Usar cópia do `.mdb` quando o original
   estiver aberto no Access (o Jet bloqueia a tabela).
2. `scripts/etl/instalar.php` — aplica `scripts/sql/01-schema.sql` (placeholder
   `{schema}`) e carrega os JSON via PDO. Idempotente: setores por *upsert*, demais
   tabelas truncadas e recarregadas.

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

Verificações complementares:

- `php -l` em todos os arquivos da aplicação (fora `system/`): **0 erros**.
- `php scripts/diag.php`: **20 verificações ok, 0 problemas** (PHP 7.4.33, `pgsql`,
  `pdo_pgsql`, `mbstring`, `json`, `curl`, `openssl`, conexão, 5 tabelas, 26 senhas
  bcrypt, servidor ativo).
- Página aberta no navegador (Chromium): `/dashboard` com 4 cartões, 12 eventos,
  CSS e favicon carregados, **0 erros de console**.
- Paginação real: 4.773 clientes e 4.522 produtos (25 por página).
- Busca sem acento/caixa: `cabeca` retorna 30 produtos (`CABEÇA ...`).

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

---

## 6. Pendências e próximos passos

1. **Schema dedicado** `biostech` — depende do DBA (sem `CREATE SCHEMA` hoje).
2. **Staging 1:1 do legado** (`biostech_legacy`) — o DDL completo está em
   `docs/04-schema-legacy-postgresql.sql` (243 tabelas).
3. **Próximos módulos** — matriz completa em `docs/07-plano-conversao-total.md`.
   Chamados/OS e o núcleo do Estoque estão prontos. Ordem sugerida: Orçamentos,
   Financeiro, Compras, Faturamento/NF, Clientes/Escolas (complementos), Equipamentos,
   RH, Frota, Acessos/Permissões.
4. **Normalização de dados sujos** — datas herdadas inválidas (1899, 0202, 0209, 1976,
   2106, 7201, 7202) precisam de quarentena antes de relatórios.
5. **Validação dos perfis** com os usuários do legado (o VBA do formulário `USUARIO` não
   foi recuperado; o mapeamento atual segue a indicação do responsável).
6. **Relatórios** — 25 relatórios Access a reimplementar em HTML/PDF (ver `docs/07`).
7. **Deploy** — Apache com `mod_rewrite` + variáveis de ambiente (`CI_ENV=production`,
   `DB_*`), HTTPS e backup do banco.
