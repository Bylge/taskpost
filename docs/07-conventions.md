# 07 — Conventions

How the code is written. `03-architecture.md` decides the shape of the system; this decides
the shape of a file.

**If a tool can enforce a rule, it is not prose here.** Everything below is either enforced
by Pint, Larastan or a test, or it is a naming decision no tool can make. Style opinions
that are neither do not belong in this document.

## The gate

`composer check` runs three things, in this order, and all three must pass before anything
is pushed:

| Tool | Setting |
|---|---|
| **Pint** | `laravel` preset, plus `declare_strict_types`. Not negotiable per-file |
| **Larastan** | **Level max, no baseline.** A greenfield project that generates a baseline on day one has a baseline forever |
| **Pest** | The full suite, against PostgreSQL |

Level max hurts for about a week and then stops hurting. Adopting it later never happens.

## Layout

Flat Laravel, not DDD modules. One bounded context and a fifteen-person firm do not justify
`src/Domain/Tasks/`. If a genuine second context ever appears, that is the moment to split,
and not before.

```
app/
  Actions/{Aggregate}/VerbNoun.php   CreateTask, AssignTask, PostComment
  Data/                             readonly DTOs
  Enums/                            Permission, StatusType, ActivityType
  Models/
  Policies/                         delegate to hasPermission(), never a rule of their own
  Support/                          TenantContext, BelongsToTenant
  Filament/  Http/                  entry points only — no rules (01-principles.md)

resources/views/
  components/                       Livewire 4 components live here, not app/Livewire
  layouts/
```

**`resources/views/pages/` is gone.** It held the full-page components of the hand-built
reporter portal, which was cut on 2026-09-18 (`02-domain.md`, `03-architecture.md`). There
is one surface now, so everything a person sees lives inside the Filament panel: resources,
custom Filament pages under `app/Filament/Pages/`, and Livewire components in either. A
full-page Blade view outside the panel is the tell that a second interface is growing back,
and it is reviewed as such.

`Http/` stays thin: form requests validate, controllers call an Action, API resources
serialise. A controller with an `if` about domain state is a bug.

**Livewire 4 components are single-file by default** — PHP, Blade and any scoped CSS in one
file, which is what `make:livewire` now produces. Convert to multi-file
(`livewire:convert --mfc`) when a component passes roughly 150 lines. Inside the panel a
component is usually one widget, one form section or one timeline, so expect most of them to
stay single-file for a long time.

If a component is large enough that the multi-file question feels close, check first whether
logic has leaked in that belongs in an Action. Usually it has.

## Naming

| Kind | Convention | Example |
|---|---|---|
| Model | Singular | `NotificationPreference` |
| Table | Plural snake | `notification_preferences` |
| Action | `VerbNoun`, one public `handle()` | `AssignTask` |
| DTO | `…Data`, `final readonly` | `CreateTaskData` |
| Enum | Singular, backed by string | `StatusType` |
| Job | Verb first | `SendTaskAssignedMail` |
| Test | Mirrors the class under test | `tests/Feature/Task/AssignTaskTest.php` |
| Route name | Dotted, resourceful | `tasks.show`, `api.v1.tasks.store` |

Three rules the tools won't catch:

- **`final` by default.** Drop it only when something actually extends the class and you
  meant it to.
- **Return types everywhere.** `mixed` in a signature needs a comment saying why.
- **Enums over string constants, and over a boolean that will grow a third state.**

## Translation keys

The hard rule in `CLAUDE.md` — no user-facing string in code — only survives if the key
convention is decided before the first view. It is decided here.

- **One file per surface:** `lang/{locale}/task.php`, `user.php`, `settings.php`,
  `common.php`
- **Shape:** `file.context.item` — `task.status.updated`, `task.form.subject_label`
- **Three levels maximum.** A fourth means the file should have been split
- **Always `__()`.** No `@lang`, no inline default as a second argument — a default is a
  hardcoded string wearing a hat
- **Keys name the role, not the copy.** `task.form.subject_label`, never
  `task.form.what_needs_doing`. Rewording the Polish must never rename a key
- **`en` is authoritative. A key present in `en` and missing in `pl` fails CI.** Silent
  fallback to English is how half a product ends up untranslated without anyone noticing
- **Tenant data is never a key** (`01-principles.md`). Status names, categories, types and
  task bodies are data in whatever language the client typed

The M4 lint is a Pest test: it scans Blade, Livewire and Filament resources for literal
user-facing strings. It joins the `i18n` CI job, which has checked `en`/`pl` key parity
since M1 (`08-environment.md`).

## Tests

The four mandatory categories are in `03-architecture.md`. These are the mechanics:

- **Feature tests by default.** `tests/Unit` only for pure logic that touches no database —
  and it does not exist yet. Git does not track an empty directory, and a `<testsuite>`
  pointing at a directory PHPUnit cannot find aborts the whole run on a fresh clone, so the
  `Unit` suite is added to `phpunit.xml` in the same commit as the first test that needs it
  (`06-build-plan.md` M1.5)
- **Never mock the database, never mock an Action inside a feature test.** The thing being
  proven is that the real pieces fit together
- **Every model gets a factory**, and making a second tenant with overlapping data is one
  line — because an isolation test nobody can write cheaply is an isolation test nobody
  writes
- **A shared helper asserts scoping** so per-model isolation tests stay one-liners
- **Names describe behaviour:** `it('refuses to assign a task without task.assign')`
- **`Pest\Laravel\get()`, never `$this->get()`.** Inside a Pest closure `$this` is bound to
  the test case only at runtime, so Larastan at level max cannot see it and the call fails
  the `static` gate. `pest-plugin-laravel` exposes a typed global for each one — `get()`,
  `post()`, `actingAs()`, `assertAuthenticated()` — brought in with `use function` (M1.6)

Every Action ships with three tests minimum: happy path, permission denied, tenant
isolation. Anything touching comments adds a fourth — the **internal-comment leak test**,
which asserts that a membership without `task.note` never gets an internal comment back
from the query itself, never merely from the rendered page (`02-domain.md`).

## Commits and branches

- **Conventional Commits**, short subset: `feat` `fix` `refactor` `test` `docs` `chore`
- Imperative mood, English, subject ≤ 72 characters
- Branch per change, short-lived, rebased on main: `feat/task-assignment`
- **One logical change per PR.** A PR carrying a migration and a UI redesign gets split
- Squash merge, so main reads as one commit per change

## Comments

Explain *why*. The *what* is the code, and a comment restating the line gets deleted.

Non-obvious domain rules get a comment pointing at the doc that decided them — the reopen
rule in `PostComment` reads as an accident to anyone who has not read `02-domain.md`.

## Definition of done

No reviewer exists, so the gate is mechanical. A change is done when:

1. `composer check` passes locally
2. CI is green, all jobs
3. The Action's three tests exist and are named for behaviour
4. No new user-facing string lacks keys in **both** locales
5. `.env.example` carries any new variable, added in the same commit
6. Any decision that changed is written into the doc that owns it, in the same PR
