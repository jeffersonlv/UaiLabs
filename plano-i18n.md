# Plano de Internacionalização (i18n) — UaiLabs

## Decisões de arquitetura

- **Formato:** JSON com texto nativo como chave (`lang/pt.json`, `lang/en.json`, `lang/es.json`)
- **Chave = texto PT:** `__('Salvar')` → se tradução ausente, exibe `Salvar` — nunca quebra
- **Locale por usuário:** campo `users.locale` (ex: `pt`, `en`, `es`) — cada usuário vê seu idioma
- **Fallback:** `pt` sempre — qualquer chave sem tradução cai no texto legível em português
- **Gestão:** superadmin gerencia arquivos de tradução via `/admin/translations`
- **Seleção pelo usuário:** página de perfil (`/profile`) — dropdown de idioma

---

## O que traduzir

- Botões, labels, títulos, alerts, mensagens de sucesso/erro estáticas
- Textos de UI do sistema (não conteúdo do banco)

## O que NÃO traduzir

- Dados do banco (nomes de empresas, categorias, usuários)
- Logs de auditoria (sempre PT)
- Mensagens de validação do Laravel (arquivo separado: `lang/{locale}/validation.php`)
- Nomes de rotas e keys de módulo

---

## Fases

### Fase 0 — Levantamento
- [ ] Varrer todos os Blades em `resources/views/`
- [ ] Catalogar strings estáticas por view (botões, labels, alerts, títulos)
- [ ] Identificar strings com variáveis (ex: `Bem-vindo, {{ $name }}` → `__('Bem-vindo, :name', ['name' => $name])`)
- [ ] Gerar lista final antes de tocar em qualquer arquivo

### Fase 1 — Estrutura base (sem alterar views)
- [ ] Criar `lang/pt.json` — key = value = texto PT (trivial, zero risco)
- [ ] Criar `lang/en.json` — mesmas chaves, valores em inglês
- [ ] Criar `lang/es.json` — mesmas chaves, valores em espanhol
- [ ] Configurar `config/app.php`: `locale = 'pt'`, `fallback_locale = 'pt'`
- [ ] Commit e deploy — nenhuma view alterada ainda

### Fase 2 — Migration e middleware
- [ ] Migration: adicionar `users.locale` (varchar 5, nullable, default `pt`)
- [ ] Criar `app/Http/Middleware/SetLocale.php`
  - Lê `auth()->user()->locale` → `App::setLocale()`
  - Fallback para `config('app.locale')` se null ou não autenticado
- [ ] Registrar no `app/Http/Kernel.php` no grupo `web`
- [ ] Commit e deploy — locale funciona, views ainda em hardcoded (sem efeito visível)

### Fase 3 — Substituição incremental por módulo
Ordem do menor risco para o maior:
1. [ ] `layouts/app.blade.php` — navbar, sidebar, dropdown de usuário
2. [ ] `auth/login.blade.php` — tabs, botões, labels
3. [ ] Alerts globais (flash messages em `layouts/app.blade.php`)
4. [ ] `dashboard` 
5. [ ] Módulo rotinas (checklist, categories, subcategories, activities)
6. [ ] Módulo ponto (time-entries, work-schedules)
7. [ ] Módulo compras (purchase-requests)
8. [ ] Módulo shifts
9. [ ] Módulo estoque
10. [ ] Support requests
11. [ ] Admin / superadmin

**Regra:** cada módulo = 1 commit separado. Nunca alterar lógica PHP, só strings Blade.

### Fase 4 — Seleção de idioma pelo usuário
- [ ] Criar/expandir página de perfil (`/profile`) com dropdown de locale
- [ ] Salvar `users.locale` via PATCH `/profile/locale`
- [ ] Exibir apenas locales com arquivo `lang/{locale}.json` existente
- [ ] Link "Perfil" no dropdown de usuário na navbar

### Fase 5 — UI superadmin para gerenciar traduções
- [ ] Rota `/admin/translations` — lista `lang/en.json` e `lang/es.json` editáveis
- [ ] Formulário bulk: chave PT (readonly) | valor EN | valor ES
- [ ] Save escreve os arquivos JSON + invalida cache
- [ ] Validação de JSON antes de salvar (evita arquivo corrompido)

---

## Onde o usuário escolhe o idioma

**Página:** `/profile` — perfil do usuário (a criar, atualmente só existe `/password/edit`)

A página de perfil vai reunir:
- Redefinir senha (já existe em `/password/edit`, migrar para cá)
- Idioma preferido — dropdown: Português 🇧🇷 | English 🇺🇸 | Español 🇪🇸
- Futuramente: foto, preferências de notificação

O locale é salvo em `users.locale` e aplicado pelo `SetLocale` middleware a cada request.

---

## Risco

| Cenário | Impacto | Mitigação |
|---|---|---|
| Chave ausente em `en.json` | Exibe texto em PT | Aceitável — não quebra |
| `app.locale` errado | Usuário vê PT em vez de EN | Sem erro, só idioma errado |
| Middleware de locale falha | Laravel usa locale padrão | Try/catch + fallback |
| JSON malformado | Exceção no boot | Validar JSON antes de salvar/push |
| String com variável mal migrada | Exibe `:name` literal | Coberto no levantamento da Fase 0 |

**Risco estimado de quebrar a aplicação: < 1%**
O único risco real é JSON malformado — mitigado com `json_decode` + validação antes de qualquer escrita.

---

## Locales iniciais

| Código | Idioma | Arquivo |
|---|---|---|
| `pt` | Português 🇧🇷 | `lang/pt.json` |
| `en` | English 🇺🇸 | `lang/en.json` |
| `es` | Español 🇪🇸 | `lang/es.json` |
