# Plano de conversão BIOSTECH → PHP + CodeIgniter 2.x (web)

**Data:** 16/11/2026
**Base:** `docs/01-analise-biostech-access.md`
**Alvo:** aplicação web em PHP + CodeIgniter 2.x (release de 28/01/2012, mesma linha do
`material-grafico`), com PostgreSQL.

> **Adendo (implantação real, 16/11/2026):** o usuário de banco disponível
> (`matgrafico`) não tem privilégio de `CREATE SCHEMA`/`CREATE DATABASE`. Por isso a
> primeira entrega usa o schema existente `material_grafico` com as tabelas prefixadas
> (`biostech_setores`, `biostech_usuarios`, ...). O nome do schema é configurável por
> `DB_SCHEMA`; quando o DBA provisionar um schema dedicado (`biostech`), basta rodar o
> instalador com `DB_SCHEMA=biostech` e reimportar. Ver `docs/05-status-implantacao.md`.

---

## 1. Princípios da conversão

1. **Não traduzir tela por tela.** Reimplementar o *processo*, não o formulário. O Access
   tem 619 forms porque cada variação virou uma tela; na web isso é um form com filtros.
2. **Dados primeiro, normalização depois.** Copiar o schema 1:1 para um schema `legacy`,
   subir o sistema novo lendo do `legacy`, e normalizar módulo a módulo, com *views de
   compatibilidade* no meio.
3. **Regra de negócio sai do banco.** Nada de query que lê controle de formulário. Regra
   vai para *service/domain* em PHP, testável.
4. **Legado continua vivo** durante o paralelo. Nada de "big bang".

---

## 2. Arquitetura alvo

```
┌─────────────────────── Navegador ───────────────────────┐
│  Views (CI 2.x) — Bootstrap/Twig-like, sem frames       │
└───────────────────────────┬─────────────────────────────┘
                            │ HTTPS
┌───────────────────────────▼─────────────────────────────┐
│  Apache/Nginx + PHP 7.4.33                              │
│  CodeIgniter 2.x                                        │
│   ├── Controllers  (fino: valida, chama service, view)  │
│   ├── Models       (MY_Model, query builder escapado)   │
│   ├── Libraries    (auth, rbac, pdf, export, auditoria) │
│   ├── Services     (regras: OS, orçamento, estoque,     │
│   │                 financeiro, parcelamento)           │
│   └── Helpers      (formatação, máscara, validadores)   │
└───────────────────────────┬─────────────────────────────┘
                            │ PDO / CI Database
┌───────────────────────────▼─────────────────────────────┐
│  PostgreSQL 14+                                         │
│   ├── biostech        → schema novo (normalizado)       │
│   ├── biostech_legacy → staging 1:1 do Access           │
│   └── biostech_audit  → log de auditoria                │
└─────────────────────────────────────────────────────────┘
```

### 2.1 Stack

| Item | Escolha | Observação |
|---|---|---|
| Linguagem | PHP 7.4.33 | já instalado em `C:\php\php.exe`; CI 2.x roda com patch mínimo |
| Framework | CodeIgniter 2.x (2012-01-28) | mesma base do `material-grafico` |
| Banco | **PostgreSQL 14+** (recomendado) | sequences, `numeric`, `jsonb`, window functions, `citext` |
| Alternativa | MySQL 8 / MariaDB 10.6 | viável; perde `RETURNING`, `jsonb`, alguns tipos |
| Front | Bootstrap 5 + jQuery (ou Alpine) | sem dependência de frames/navegador antigo |
| PDF | mPDF 8 ou dompdf | relatórios e recibos |
| Planilha | PhpSpreadsheet | exportações |
| Auth | sessão PHP + `password_hash` | bcrypt; reset obrigatório na virada |

> **Sobre o CI 2.x:** a linha 2.1.x tem pontos incompatíveis com PHP 7.4 (driver `mysql_*`,
> `mcrypt`, `each()` em alguns helpers). O material-grafico já roda nesse arranjo; vamos
> replicar a mesma configuração e manter um patch mínimo documentado. Se em algum momento
> houver folga para atualizar, CI 3.1.13 é o salto natural (mesma estrutura de pastas,
> muito menos atrito) — mas isso é decisão separada.

---

## 3. Banco de dados

### 3.1 Dois schemas

| Schema | Função | Regra |
|---|---|---|
| `biostech_legacy` | cópia fiel das 247 tabelas do Access | nomes e colunas **idênticos** (aspados), tipos convertidos, sem constraints |
| `biostech` | modelo novo | nomes em `snake_case`, PK/FK reais, `timestamptz`, auditoria |

O sistema novo começa lendo de `biostech_legacy` via **views de compatibilidade**
(`vw_chamado_01`, etc.). Conforme cada módulo é reimplementado, suas tabelas passam para
`biostech` e a view é reescrita apontando para o novo modelo — **sem quebrar as telas já
entregues**.

### 3.2 Mapa de tipos Access → PostgreSQL

| Access | PostgreSQL | Observação |
|---|---|---|
| Text(n), n ≤ 255 | `varchar(n)` | manter tamanho original no staging |
| Text(n), n > 255 / Memo | `text` | |
| Byte | `smallint` | |
| Integer | `integer` | |
| Long | `integer` / `bigint` | se for contador/ID, `integer` |
| BigInt | `bigint` | |
| Single / Double | `numeric(18,4)` | valores e quantidades; nunca `float` |
| Currency | `numeric(15,2)` | dinheiro |
| Date | `date` (staging: `text` + coluna `*_raw`) | datas inválidas vão para quarentena |
| Time | `time` | |
| Boolean | `boolean` | `True/False` → `true/false` |
| GUID | `uuid` | raro |
| Binary / LongBinary | `bytea` | evitar; preferir caminho de arquivo |
| AutoNumber | `integer GENERATED BY DEFAULT AS IDENTITY` | |

### 3.3 Convenções do schema novo

- Tabelas no singular ou plural consistente; prefixo por domínio: `os_`, `orc_`, `est_`,
  `fin_`, `cmp_`, `cad_`, `rh_`, `frota_`, `seg_`.
- PK sempre `id` (bigserial) + `legacy_id` (código original) + índice único.
- Toda tabela de movimento: `created_at`, `created_by`, `updated_at`, `updated_by`,
  `deleted_at` (soft delete).
- Dinheiro: `numeric(15,2)`. Quantidade: `numeric(15,4)`.
- Datas de negócio: `date`; carimbos: `timestamptz`.
- `CHECK` para situações/status (ou tabela de domínio com FK).
- Sem `ENUM` do PostgreSQL para domínios que o usuário edita (usar tabela).

### 3.4 Exemplo de normalização (Chamados/OS)

```
legacy:  CHAMADO_01 (32 campos) + CHAMADO_02 (itens) + CHAMADO_03 (serviços)
         + CHAMADO_05A (materiais) + CHAMADO_06 (movimento) + CHAMADO_55 (histórico)

novo:    os_ordem_servico        (cabeçalho: numero, cliente_id, status, datas, totais)
         os_item_servico         (serviço, qtde, valor, técnico)
         os_item_material        (produto, qtde, valor, baixa_estoque_id)
         os_historico            (status_anterior, status_novo, usuario, data, observacao)
         os_anexo                (arquivo, tipo, usuario, data)
```

---

## 4. ETL Access → PostgreSQL

Pipelinede 5 etapas, cada uma idempotente e auditável:

```
1. EXPORT      DAO/OLEDB (PowerShell 32 bits) → CSV UTF-8 por tabela
2. LOAD        COPY (PostgreSQL) → biostech_legacy.<tabela>
3. SANITIZE    normalização de datas, booleanos, textos; tabela de rejeitos
4. VALIDATE    contagens, somatórios, órfãos, duplicatas → relatório de divergência
5. PROMOTE     carga no schema novo (módulo a módulo) + views de compatibilidade
```

### 4.1 Exportação (etapa 1)

Reaproveitar o padrão já validado nesta análise (`extract_dao.ps1`): PowerShell 32 bits,
`DAO.DBEngine.36`, abrindo o `.mdb` em modo somente leitura. Regras de export:

- **UTF-8 sem BOM**, `\n` como quebra de linha, `"` escapado (`""`), nulos como vazio.
- Datas exportadas em ISO (`yyyy-mm-dd`) quando válidas; inválidas vão para coluna
  `dt_raw` (texto).
- Memo exportado integralmente (sem truncar).
- Binários (`LongBinary`) exportados para arquivo separado com manifesto.

### 4.2 Geração do DDL de staging (etapa 2)

A partir de `backend-fields.csv` (2.770 linhas, com tipo e tamanho) gerar
`01-schema-legacy.sql` automaticamente:

```
backend-fields.csv  ──(script)──►  01-schema-legacy.sql  ──►  psql -f
```

Isso garante fidelidade e permite regenerar a qualquer momento. Nenhuma coluna é omitida.

### 4.3 Quarentena de dados (etapa 3)

| Verificação | Ação |
|---|---|
| Data fora de `1900-01-01 .. 2100-12-31` | `NULL` + registro em `legacy_rejeito` |
| Data nula onde o negócio exige | `NULL` + alerta (não bloqueia) |
| Texto com caractere de controle | limpar |
| Booleanos `S/N`, `True/False`, `1/0`, vazio | normalizar |
| Duplicata de PK | manter primeira, registrar demais |

### 4.4 Validação (etapa 4) — critérios de aceite da carga

- Contagem por tabela **igual** à do Access (ou diferença explicada).
- Somatório dos 8 maiores valores monetários por tabela confere (tolerância 0,01).
- Zero registros com data impossível no schema novo.
- Relatório de órfãos por relação declarada (`backend-relations.csv`).
- Lista de tabelas descartadas com justificativa e backup do CSV original.

---

## 5. Estrutura do projeto

```
biostech/
├── application/
│   ├── config/            (database, routes, autoload, config)
│   ├── controllers/
│   │   ├── Auth.php
│   │   ├── Dashboard.php
│   │   ├── Cadastros/     (Clientes, Escolas, Produtos, Fornecedores, Equipamentos)
│   │   ├── Os/            (OrdemServico, Chamado, Historico)
│   │   ├── Orcamentos/
│   │   ├── Estoque/
│   │   ├── Compras/
│   │   ├── Financeiro/    (ContasPagar, ContasReceber, MovimentoBancario, Caixa)
│   │   └── Relatorios/
│   ├── models/
│   ├── services/          (regras de negócio — o coração)
│   ├── libraries/         (Auth, Rbac, Auditoria, Pdf, Excel)
│   ├── helpers/
│   ├── views/
│   │   ├── layouts/
│   │   └── <modulo>/
│   └── migrations/        (versionamento do schema novo)
├── assets/
├── system/                (CI 2.x — não editar, exceto patch documentado)
├── docs/                  (esta análise, plano, dicionário, decisões)
├── scripts/etl/           (export, load, sanitize, validate)
└── tests/                 (PHPUnit — regras de negócio)
```

---

## 6. Segurança e permissões

- Login por `USUARIO`, senha com `password_hash` (bcrypt). **Reset obrigatório** na virada
  (senhas atuais são texto puro).
- RBAC novo: `perfil` → `permissao` (recurso + ação: `os.criar`, `os.aprovar`,
  `fin.pagar`, `est.ajustar`…). As 20 tabelas `ACESSO*` viram **uma** estrutura.
- Trilha de auditoria em tabela única (`biostech_audit.evento`): quem, quando, o quê,
  valores antes/depois (jsonb) — para OS, orçamento, financeiro e estoque.
- Sessão com `httponly`, `secure`, regeneração de ID no login, expiração por inatividade.
- CSRF em todos os formulários; saída sempre escapada nas views.
- Regra de acesso por setor (`VSETOR`) preservada como escopo de dados.

---

## 7. Roadmap por fases

Estimativas para **1 desenvolvedor**. Fases 2–5 podem paralelizar com 2 devs.

### F0 — Fundação (2 semanas)

- Repositório, CI 2.x configurado, layout base, menu, tema.
- Autenticação, RBAC, auditoria, tratamento de erro, log.
- Schema `legacy` gerado + ETL rodando de ponta a ponta (dados reais).
- Padrões: `MY_Model`, service layer, migrations, testes, code style.
- **Entregável:** login funcionando e consulta de clientes lendo do staging.

### F1 — Cadastros base (3 semanas)

- Clientes/Escolas, Fornecedores, Produtos (grupo/subgrupo/estante/prateleira),
  Equipamentos, Funcionários, Usuários.
- Pesquisa com filtros, paginação, exportação, histórico de alteração.
- **Entregável:** cadastros completos substituindo `F_CLIENTES_*`, `F_PRODUTO_*`,
  `F_FORNECEDOR_*`, `F_EQUIPAMENTO_*`, `F_FUNCIONARIO_*`.

### F2 — Chamados / OS (4 semanas) ← **módulo piloto**

- Abertura de chamado, OS, itens de serviço/material, histórico, situação, fechamento.
- Numeração por sequence (substitui `Contador`).
- Impressão de OS (PDF) e vínculo com equipamento/cliente.
- **Entregável:** ciclo completo de OS. É o piloto porque concentra o maior número de
  regras e valida a arquitetura de ponta a ponta.

### F3 — Orçamentos (4 semanas)

- Orçamento dimensional (`ORCAM_DIMEN_01..07`), itens, aprovação/recusa, versões.
- Conversão orçamento → OS.
- **Entregável:** orçamento com aprovação e geração de OS.

### F4 — Estoque / Compras (4 semanas)

- Saldo por produto e por fornecedor (definir semântica dos 6 saldos), entradas/saídas,
  baixa por OS, requisição, cotação, pedido de compra.
- **Entregável:** estoque confiável com rastreabilidade de movimento.

### F5 — Financeiro (5 semanas)

- Contas a pagar/receber, parcelamento (`VALPARC`, `DTVENC`), movimento bancário,
  caixa, conciliação, centros de custo, balanço.
- **Entregável:** financeiro operacional + relatórios gerenciais.

### F6 — Faturamento, RH, Frota e relatórios finais (3 semanas)

- Recibos, NF/serviços, atestados/férias, veículos.
- Migração dos 300 relatórios por prioridade (os ~40 mais usados primeiro).
- **Entregável:** paridade funcional e corte.

**Total:** ~25 semanas sequenciais; **~16–18 semanas** com paralelismo a partir de F2.

### 7.1 Estratégia de relatórios

300 relatórios não se reimplementam todos. Classificar em:

| Classe | Qtd estimada | Tratamento |
|---|---|---|
| Operacionais usados no dia a dia | ~40 | reimplementar em PDF/tela |
| Consultas gerenciais | ~80 | tela com filtros + exportação Excel |
| Espelhos/duplicatas/versões | ~120 | descartar (consolidar) |
| Legais/fiscais | ~20 | reimplementar com layout fiel |
| Mortos (sem uso) | ~40 | descartar com aval do usuário |

Critério: frequência de uso + existência de query `C_*` correspondente + confirmação do
usuário-chave.

---

## 8. Paralelo e corte

1. **Carga inicial** no staging + validação (F0).
2. **Carga incremental** diária do legado para o staging enquanto o paralelo durar
   (captura o que ainda é lançado no Access).
3. **Paralelo por módulo**: módulo entregue → usuários operam no novo → legado vira
   somente leitura naquele domínio.
4. **Corte final**: congelar Access, carga delta final, validar, liberar geral.
5. **Pós-corte**: manter `.mdb` e CSVs arquivados por 12 meses (exigência fiscal).

---

## 9. Critérios de aceite (por módulo)

- [ ] Todas as operações do módulo no legado têm equivalente no novo.
- [ ] Consultas de ação do módulo (INSERT/UPDATE/DELETE) foram mapeadas 1:1 para services.
- [ ] Relatórios do módulo com paridade visual e de totais.
- [ ] Regras sensíveis cobertas por teste automatizado (parcelamento, numeração, baixa).
- [ ] Auditoria registrando criar/alterar/excluir.
- [ ] Permissões por perfil validadas.
- [ ] Carga de dados do módulo validada (contagem + somatórios).
- [ ] Treinamento e manual rápido entregues.

---

## 10. Decisões pendentes

| # | Decisão | Opções | Recomendação |
|---|---|---|---|
| D1 | SGBD | PostgreSQL / MySQL 8 / SQL Server | **PostgreSQL 14+** |
| D2 | Módulo piloto | Chamados/OS / Cadastros / Financeiro | **Chamados/OS** (após F1) |
| D3 | Local do projeto | `C:\desenvolvimento\PROJETOS_PHP\biostech` / outro | junto dos demais projetos PHP |
| D4 | Escopo da 1ª entrega | F0–F2 / F0–F3 / tudo | **F0–F2** (12 semanas) |
| D5 | Migrar `PRODUTO1/2/3` e `Cópia de MOV_BANCARIA_02`? | sim / não / comparar antes | **comparar antes**, descartar se idênticas |
| D6 | Semântica dos 6 saldos de estoque | físico/disponível/reservado? | levantar no VBA de `F_SAI_PROD_TEMP_*` |
| D7 | CI 2.x puro ou CI 3.1.13 | fidelidade / manutenção | manter 2.x nesta fase |

---

## 11. Próximos passos imediatos

1. **Confirmar D1–D4** (banco, piloto, diretório, escopo).
2. **Exportar o VBA dos formulários** (`SaveAsText(3, …)`) dos módulos prioritários e
   extrair as regras — hoje é o maior "buraco" de conhecimento.
3. **Gerar o DDL de staging** a partir de `backend-fields.csv` e rodar a carga completa.
4. **Relatório de divergências** (contagens, datas inválidas, órfãos, duplicatas).
5. **Criar o esqueleto** do projeto CI + auth + layout + um CRUD piloto (Clientes).
