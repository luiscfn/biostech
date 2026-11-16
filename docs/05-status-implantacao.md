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
| Chamados / OS, Orçamentos, Estoque, Compras, Financeiro | — | "em breve" no menu |

---

## 2. Dados carregados

Tabelas criadas no schema **`material_grafico`**, com prefixo `biostech_`:

| Tabela | Registros | Origem no Access |
|---|---:|---|
| `biostech_setores` | 11 | `SETORES` |
| `biostech_usuarios` | 26 | `USUARIOS` |
| `biostech_clientes` | 4.773 | `CLIENTES` |
| `biostech_produtos` | 4.522 (1.950 ativos) | `PRODUTO` |
| `biostech_auditoria` | — | nova (login/logout/CRUD) |

> **Limitação de infraestrutura:** o usuário `matgrafico` não tem privilégio de
> `CREATE SCHEMA` nem `CREATE DATABASE` (verificado em `pg_namespace`/`pg_database`).
> Por isso as tabelas foram isoladas por prefixo no schema existente. O código lê
> `DB_SCHEMA` (padrão `material_grafico`); ao provisionar um schema dedicado `biostech`,
> basta rodar `DB_SCHEMA=biostech php scripts/etl/instalar.php`.

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

---

## 6. Pendências e próximos passos

1. **Schema dedicado** `biostech` — depende do DBA (sem `CREATE SCHEMA` hoje).
2. **Staging 1:1 do legado** (`biostech_legacy`) — o DDL completo está em
   `docs/04-schema-legacy-postgresql.sql` (243 tabelas).
3. **Chamados / OS** (módulo piloto) — extrair o VBA dos formulários do
   `BIOSTECH_A2003H.mdb` para capturar as regras (os 11 módulos padrão não cobrem as
   regras de tela).
4. **Estoque** — entradas/saídas, saldos, inventário; **Compras** e **Financeiro** na
   sequência.
5. **Normalização de dados sujos** — datas herdadas inválidas (1899, 0202, 0209, 1976,
   2106, 7201, 7202) precisam de quarentena antes de relatórios.
6. **Validação dos perfis** com os usuários do legado (o VBA do formulário `USUARIO` não
   foi recuperado; o mapeamento atual segue a indicação do responsável).
7. **Deploy** — Apache com `mod_rewrite` + variáveis de ambiente (`CI_ENV=production`,
   `DB_*`), HTTPS e backup do banco.
