# BIOSTECH — sistema de gestão (PHP + CodeIgniter 2.1.2)

Reescrita web do sistema legado **BIOSTECH** (Microsoft Access 2003), a partir dos bancos
`SISBIOSD_01.mdb` (dados) e `BIOSTECH_A2003H.mdb` (formulários, relatórios e VBA).

- **Framework:** CodeIgniter 2.1.2 (release de 28/01/2012), mesma linha do projeto
  `material-grafico`.
- **Banco:** PostgreSQL 16.
- **PHP:** 7.4 (compatível com o `material-grafico`).
- **Status:** Fase 0 + parte da Fase 1 — autenticação, painel e cadastros básicos
  (clientes, produtos, setores, usuários) sobre os dados já migrados do Access.

---

## 1. Requisitos

| Item | Versão / observação |
|---|---|
| PHP | 7.4+ com `pgsql`, `pdo_pgsql`, `mbstring`, `json`, `curl`, `openssl` |
| PostgreSQL | 12+ (desenvolvido no 16) |
| Servidor web | Apache com `mod_rewrite` **ou** o servidor embutido do PHP |
| Extensões PHP | `extension=pgsql` **e** `extension=pdo_pgsql` habilitadas no `php.ini` |

> O driver `postgre` do CodeIgniter 2.1.2 usa `pg_connect()` (extensão `pgsql`), não PDO.
> Sem `extension=pgsql` a aplicação falha com *Call to undefined function pg_connect()*.

---

## 2. Estrutura

```
biostech/
├─ .vscode/            tasks (servidor/testes), settings, extensões recomendadas
├─ application/
│  ├─ config/          config.php, database.php, routes.php, constants.php
│  ├─ controllers/     Login, Dashboard, Clientes, Produtos, Setores, Usuarios
│  ├─ core/            MY_Controller (auth, layout, paginação), MY_Model
│  ├─ helpers/         biostech_helper.php (bd_bool, data_br, moeda_br, ...)
│  ├─ libraries/       Auth (sessão nativa + bcrypt), Auditoria
│  ├─ models/          Cliente_model, Produto_model, Setor_model, Usuario_model
│  └─ views/           layouts/, login/, dashboard/, clientes/, produtos/, setores/, usuarios/
├─ assets/css/         app.css (layout próprio, responsivo)
├─ docs/               análise do Access, plano de conversão, status
├─ scripts/
│  ├─ etl/             export.ps1 (Access → JSON), tabelas.json, instalar.php
│  ├─ sql/             01-schema.sql (DDL com placeholder {schema})
│  ├─ test/            smoke.php (teste de fumaça HTTP)
│  └─ diag.php         diagnóstico de PHP, extensões, banco e servidor
├─ system/             CodeIgniter 2.1.2 (não alterar)
├─ index.php           bootstrap
├─ router.php          router do servidor embutido do PHP
├─ iniciar.bat         atalho: sobe o servidor e abre o navegador
├─ parar.bat           atalho: encerra o servidor da porta 8090
└─ .htaccess           URLs limpas no Apache
```

---

## 3. Configuração

### 3.1 Variáveis de ambiente

Todas são opcionais e caem nos valores padrão de desenvolvimento:

| Variável | Padrão | Uso |
|---|---|---|
| `CI_ENV` | `development` | `development` / `testing` / `production` |
| `DB_HOST` | `sedsrvpostdesv.seduc.ce.gov.br` | host PostgreSQL |
| `DB_PORT` | `5432` | porta |
| `DB_NAME` | `desenvolvimento` | banco |
| `DB_USER` | `matgrafico` | usuário |
| `DB_PASS` | `matgrafico` | senha |
| `DB_SCHEMA` | `material_grafico` | schema das tabelas `biostech_*` |

> **Por que `material_grafico`?** O usuário de banco disponível não tem privilégio de
> `CREATE SCHEMA`/`CREATE DATABASE`. As tabelas ficam isoladas com prefixo `biostech_`
> nesse schema. Quando o DBA criar um schema dedicado, rode o instalador com
> `DB_SCHEMA=biostech` — nenhuma alteração de código é necessária.

Em produção, defina `CI_ENV=production` (desliga exibição de erros e o `db_debug`).

### 3.2 `php.ini`

```ini
extension=pgsql
extension=pdo_pgsql
```

---

## 4. Instalação

### 4.1 Estrutura e carga inicial

O ETL lê o `.mdb` via **Jet OLEDB**, que só existe no PowerShell de **32 bits**:

```powershell
# 1) exporta as tabelas do Access para JSON UTF-8
& "$env:WINDIR\SysWOW64\WindowsPowerShell\v1.0\powershell.exe" `
    -NoProfile -ExecutionPolicy Bypass `
    -File "scripts\etl\export.ps1" `
    -Mdb "C:\SISBIOST\Dados\SISBIOSD_01.mdb"

# 2) cria as tabelas e carrega os dados
php scripts\etl\instalar.php
```

- As tabelas/colunas exportadas estão em `scripts/etl/tabelas.json`.
- O instalador é **idempotente**: `setores` faz *upsert*; `usuarios`, `clientes` e
  `produtos` são truncados e recarregados.
- Se o `.mdb` estiver aberto no Access, use uma cópia (`-Mdb <copia.mdb>`), pois o Jet
  bloqueia a tabela.

### 4.2 Abrir no Visual Studio Code

1. Abra o VS Code.
2. **File → Open Folder…** e escolha `C:\desenvolvimento\PROJETOS_PHP\biostech`
   (ou, no terminal: `code "C:\desenvolvimento\PROJETOS_PHP\biostech"`).
3. Aceite as extensões recomendadas (`.vscode/extensions.json`):
   - **PHP Intelephense** (autocomplete/navegação)
   - **PHP Debug** (opcional, só funciona com Xdebug instalado)
   - **PowerShell**
4. A pasta já vem com configuração pronta em `.vscode/`:
   - `tasks.json` — iniciar/parar servidor, teste de fumaça, lint, diagnóstico, banco.
   - `settings.json` — caminho do PHP (`C:\php\php.exe`), exclusões de `system/` e logs.
   - `launch.json` — depuração Xdebug (opcional, ver observação abaixo).

### 4.3 Rodar a aplicação para testar

**Opção A — pelo VS Code (recomendada)**

1. `Ctrl+Shift+P` → **Tasks: Run Task** → **Servidor: iniciar (http://127.0.0.1:8090)**.
2. Abra o navegador em <http://127.0.0.1:8090>.
3. Entre com `DIMENSIONAMENTO` / `dimensionamento` (admin) ou `ANDRIUS` / `123123` (gestor).
4. Para parar: `Ctrl+C` no terminal da task, ou rode a task **Servidor: parar**.
5. Para validar tudo de uma vez: task **Teste: fumaça (HTTP)** (o servidor precisa estar ativo).
6. Para checar ambiente/banco: task **Diagnóstico do ambiente** (`php scripts/diag.php`).

**Opção B — duplo clique**

- `iniciar.bat` sobe o servidor e abre o navegador.
- `parar.bat` encerra o processo da porta 8090.

**Opção C — terminal**

```powershell
cd C:\desenvolvimento\PROJETOS_PHP\biostech
php -S 127.0.0.1:8090 router.php
```

> **Depuração (breakpoints):** exige Xdebug. Verifique com `php -m | findstr xdebug`.
> Se não houver, o `launch.json` fica apenas como configuração futura; use as tasks e o
> `php -l` para validar o código.

### 4.4 Apache (produção)

Aponte o *DocumentRoot* para a pasta do projeto (ou publique-a em um alias). O `.htaccess`
já reescreve as URLs para `index.php` e bloqueia listagem de diretórios. `mod_rewrite`
precisa estar habilitado.

---

## 5. Acesso

Usuários vieram da tabela `USUARIOS` do Access, com as senhas originais convertidas para
**bcrypt** no ETL. O campo `PRIVILEGIADO` do legado foi mapeado assim:

| `PRIVILEGIADO` | Perfil | Qtd. | Poderes |
|---|---|---|---|
| `2` | **admin** | 6 | tudo: usuários, senhas, exclusões |
| `1` (e demais) | **gestor** | 20 | cadastros e consultas, sem gestão de usuários nem exclusão de setores |

| Login | Senha | Perfil |
|---|---|---|
| `DIMENSIONAMENTO` | `dimensionamento` | admin |
| `DIONEY` | `123456` | admin |
| `ANDRIUS` | `123123` | gestor |

Perfis disponíveis:

- **admin** — tudo, inclusive excluir setores e gerenciar usuários/senhas.
- **gestor** — cadastros, sem exclusão de setores e sem gestão de usuários.
- **operador** — consulta e cadastros operacionais (reservado para ajustes futuros).

---

## 6. Rotas principais

| Rota | Tela |
|---|---|
| `/login`, `/logout` | autenticação |
| `/dashboard` | painel com totais e auditoria |
| `/clientes`, `/clientes/novo`, `/clientes/ver/{id}`, `/clientes/editar/{id}` | clientes |
| `/produtos`, `/produtos/ver/{id}` | catálogo |
| `/setores`, `/setores/novo`, `/setores/editar/{id}` | setores |
| `/usuarios`, `/usuarios/novo`, `/usuarios/editar/{id}`, `/usuarios/senha/{id}` | usuários |

---

## 7. Decisões técnicas

- **Sessão:** a library `Session` do CI 2.1.2 depende de `mcrypt`, removido no PHP 7.4.
  A autenticação usa a library própria `Auth` com `session_start()` nativo, cookie
  `HttpOnly` + `SameSite=Lax`.
- **Senhas:** `password_hash(PASSWORD_BCRYPT)`. Se houver senha legada em texto puro no
  banco, o login valida por comparação e regrava o hash automaticamente.
- **CSRF:** ativo em todos os formulários (`form_open`), token `biostech_csrf`.
- **SQL:** placeholders `?` e nomes de tabela por constantes (`T_CLIENTES`, ...);
  booleanos do PostgreSQL voltam como `'t'/'f'` — use o helper `bd_bool()`.
- **Busca:** `translate(lower(...)) LIKE` para ignorar acentuação e caixa.
- **Auditoria:** tabela `biostech_auditoria` registra login/logout e CRUDs.
- **Paginação:** `limit/offset` com parcial `layouts/paginacao.php`.

---

## 8. Testes

```powershell
# com o servidor rodando na porta 8090
php scripts\test\smoke.php http://127.0.0.1:8090 DIMENSIONAMENTO dimensionamento
```

O teste percorre login → painel → listagens → pesquisas → CRUD de setor → logout, e
remove o setor criado no final. Sai com código 0 quando tudo passa. Com um usuário
`gestor` (ex.: `ANDRIUS`), o passo de exclusão é pulado e a limpeza é feita direto no
banco.

---

## 9. Documentação

- `docs/01-analise-biostech-access.md` — inventário dos bancos, tabelas, formulários, VBA.
- `docs/02-plano-conversao-php-ci.md` — plano por fases e mapeamento de módulos.
- `docs/03-mapa-tabelas-modulos.csv` — tabela → módulo/fase.
- `docs/04-schema-legacy-postgresql.sql` — DDL 1:1 do legado (243 tabelas).
- `docs/05-status-implantacao.md` — o que está pronto, dados carregados, próximos passos.

---

## 10. Próximos passos

1. **Chamados / Ordens de Serviço** (módulo piloto): depende de exportar o VBA dos
   formulários do `BIOSTECH_A2003H.mdb` para extrair as regras.
2. Estoque (entradas/saídas, saldos) e Compras.
3. Financeiro e relatórios.
4. Normalização do legado em staging 1:1 (`biostech_legacy`) e quarentena das datas
   inválidas herdadas (1899, 0202, 7201, ...).
5. Schema dedicado `biostech` quando o DBA provisionar.
