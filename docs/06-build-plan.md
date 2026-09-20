# 06 — Build Plan

The step-by-step sequence `04-scope.md` deferred. What gets built, in what order, and how we
know a step is finished.

**The plan ends at M9.** Nothing follows it, because what "finished" means is written down
elsewhere and does not need a milestone of its own: `09-reference-scenario.md` describes one
fifteen-person firm whose week the system must run end to end, and its eight-point list is
what the last milestone is measured against. There is no go-live after M9 because there is
nobody waiting for one.

## Working a milestone

Milestones are too big to be a unit of work. **The unit is a step**, and the execution rules
for one are in `CLAUDE.md` — read those first; this section only defines the granularity.

A step is: **one branch, one PR, one sitting, one check that either passes or doesn't.**
Steps are numbered inside their milestone — `M2.1`, `M2.2` — and worked strictly in order.

**Before a milestone starts, its step list is proposed and agreed.** Not invented while
coding. A milestone whose steps cannot be listed in advance is not understood well enough
to start, and that is information worth having before the first file is written.

The milestone's exit criterion is checked once, after its last step. Individual steps do not
get to claim the milestone is done.

Example — M2 decomposed:

| Step | Does | Check |
|---|---|---|
| M2.1 | `Tenant`, `User`, `Membership` migrations, models, factories | Factories build a full tenant in one line |
| M2.2 | `BelongsToTenant` trait, global scope, `TenantContext` | Scope applies without an explicit `where` |
| M2.3 | Tenant resolved from the actor's active membership; refusal when there is none | An actor with no active membership in the tenant is refused, not handed an empty list |
| M2.4 | Isolation test helper, plus the isolation test for `Membership` — the only tenant-owned model at M2 | Every tenant-owned model has a passing isolation test |

Four steps, four PRs, four days of small green diffs — instead of one branch that touches
everything and is impossible to review or revert.

## Rules for the plan itself

1. **A milestone is done when its exit criterion passes.** No partial credit, no "mostly
   M3". Half-finished milestones are how the foundations rot.
2. **Tests ship with the milestone, not after it.** The four categories in
   `03-architecture.md` apply to every milestone that touches a model or an Action.
3. **The API is not a milestone.** Every Action gets its endpoint in the same milestone the
   Action is written — that is what "two doors, one room" costs. M9 is only the parts that
   have no Action behind them: auth, pagination, error shape.
4. **Nothing from "out of MVP" enters without a recorded decision** in `05-open-questions.md`.
5. Sizes are relative (S/M/L), not dates, and there are no dates to hold —
   `04-scope.md` sets that policy. A size exists so that a milestone doubling it is
   noticeable, which is a trigger at the bottom of this file, not a schedule to defend.

---

# Phase 1 — Foundations

`01-principles.md` §2 — these four cannot be retrofitted. Nothing in Phase 2 starts until
Phase 1 is complete, because everything in Phase 2 is cheap afterwards and unfixable before.

## M1 — Walking skeleton · M

Fresh Laravel, PHP 8.5, PostgreSQL, Pest, Filament installed. CI running the suite on every
pull request, and a red build that actually blocks the merge.

**Dependency floor**, re-verified 2026-09-08 — Laravel 13, PHP 8.5, Filament 5, Livewire 4,
Pest 5, plus Sanctum, Pint, Larastan. Filament 5 requires Livewire 4; the two move together,
and Livewire arrives transitively with Filament rather than as a separate require.

Re-verify all of it at install and **record the resolved versions here**. Do not take a
version number from an agent's memory, including mine — this list was wrong within three
months of being written, which is exactly why the rule exists. Nothing else added without a
reason written down; see the packages we deliberately skip in `03-architecture.md`.

If PHP 8.5 blocks a package at install, drop to 8.4 and record why. Not 8.3 — though the
mechanism is softer than first recorded: Laravel 13 declares `php ^8.3` and its `symfony/*`
constraints all read `^7.4 || ^8.0`, so 8.3 resolves the older Symfony 7.4 line silently
rather than failing. The reason to be on 8.5 is support dates, not a hard floor
(`CLAUDE.md`).

PHP 8.5 is not in stock Ubuntu 24.04, which tops out at 8.3. It comes from `ppa:ondrej/php`.
Two package-level traps, both verified: `php8.5-opcache` **does not exist** — OPcache is
compiled into the core packages and naming it aborts the whole apt transaction — and
`ext-intl` is a hard `composer require` of `filament/support` that appears on neither
Laravel's nor Filament's stated requirements list.

**CI and local both run PostgreSQL, never SQLite.** The tempting in-memory shortcut diverges
from production on exactly the three things this system leans on: global scopes over JSON
columns, `SELECT … FOR UPDATE` behind task numbering, and constraint timing. A green suite
that proves nothing about production is worse than a slow one.

### What M1 owes a future that is not scheduled

**Deployment is unscheduled — a dated departure from the deployable-from-day-one principle
(`01-principles.md`), taken 2026-09-18 and recorded rather than glossed.** There is no host,
no VPS, no CD workflow and no paid service of any kind; the system runs locally and nowhere
else. The reason is not laziness about ops: a monthly bill on a free-time project converts
"no deadline" into a deadline, which is precisely the pressure this project exists without.
What the departure costs is that the first deploy, whenever it happens, will be a cold one
with no rehearsal behind it.

That cost is bounded by four things M1 does anyway, because each is free now and expensive
to retrofit:

| M1 does | So that later |
|---|---|
| Configuration comes from the environment, never a hardcoded path | A second environment is a file, not a refactor |
| The schema lives entirely in migrations | A fresh database is one command anywhere |
| The `/up` health route exists from the skeleton onward | Any deploy or monitor has something to ask |
| Nothing assumes the application runs on a laptop | Paths, hostnames and ports are configuration, not assumptions |

The expensive thing to retrofit is an application shaped around one developer's machine. A
CD workflow is a day's work against an application already shaped for it, which is why the
workflow is the part that waits and the shape is the part that does not.

Local setup and the four CI jobs are specified in `08-environment.md`; `composer check` and
the definition of done in `07-conventions.md`. M1 is where both stop being documents and
start being enforced. **The repo moves into the WSL2 filesystem here** — doing that once
branches are in flight is needless friction.

**Exit:** all four CI jobs green on a pull request, and a deliberately failing test turns the
build red *and* leaves the pull request unmergeable. No live-URL clause, because nothing is
deployed.

### Steps — agreed 2026-09-08, re-based on the `taskpost` repository 2026-09-18

| Step | Does | Check |
|---|---|---|
| M1.1 | Repository public + all-rights-reserved `LICENSE`; ruleset on `main` requiring a pull request | A direct push to `main` is **rejected**, and `gh api repos/Bylge/taskpost/rulesets` returns one `active` ruleset |
| M1.2 | WSL toolchain: PHP 8.5 + extensions, Composer, Node 24, git, gh, Docker | One chained command reports PHP 8.5, every required extension, and a reachable `docker` |
| M1.3 | Repo re-cloned into `~/code/taskpost`; gitignored `docs/private/` copied across by hand; Windows copy archived; session moves | `git -C ~/code/taskpost log --oneline` matches the Windows copy commit for commit; `docs/private/` is present in the clone; the Windows copy is renamed, not deleted |
| M1.4 | Compose: `postgres` and `mailpit`, with the PostgreSQL 18 volume path proven | `docker compose up -d` reports healthy, and a row survives `down` then `up` |
| M1.5 | Laravel 13 skeleton on PostgreSQL with Pest 5; SQLite eradicated | `artisan migrate` succeeds against `pgsql` and no `sqlite` reference survives anywhere |
| M1.6 | The gate: `pint.json`, `phpstan.neon`, `composer check` | `composer check` exits 0 from a clean tree and leaves it clean |
| M1.7 | Filament 5 installed as a package — no panel, no resources | `composer show --locked filament/filament` reports 5.x and `artisan about` exits 0 |
| M1.8 | Frontend toolchain and first asset build | `npm ci && npm run build` produces `public/build/manifest.json` |
| M1.9 | Resolved versions recorded back into this file | A script asserts every version recorded here matches the lockfiles |
| M1.10 | CI: `lint`, `static`, `test` against a PostgreSQL service container, then added to the ruleset as required checks | Those three jobs conclude `success` on a pull request, and the ruleset lists all three |
| M1.11 | CI: `i18n` — `en`/`pl` key parity, added as the fourth required check, red path proven | Four jobs green and all four required; a deliberately unpaired key turns `i18n` red, then is reverted |
| M1.12 | Exit proof: red blocks the merge | A failing test leaves the PR unmergeable; removing it makes it mergeable |

Twelve steps, worked in order.

**M1.1 is re-run from scratch on 2026-09-18.** It passed once already, against the previous
repository; that repository is being deleted and recreated rather than rewritten, because
its git history carried material that belongs in `docs/private/` and force-pushing does not
make a published commit unreachable by SHA. The step is therefore not "already done": the
`LICENSE`, the ruleset and the rejected-direct-push proof are all redone against
`Bylge/taskpost` and re-recorded. Nothing else in the table changes.

**M1.1 passed on 2026-09-18**, both halves proved rather than reasoned about. Ruleset:
`gh api repos/Bylge/taskpost/rulesets` returns exactly one entry, `enforcement: active`,
scoped to `~DEFAULT_BRANCH`, carrying `pull_request` (squash the only allowed merge method),
`non_fast_forward` and `deletion`, with `bypass_actors: []` and `current_user_can_bypass:
"never"` — nobody, including the owner, holds an exemption. Rejected push: an empty commit
pushed to `main` was declined with `GH013: Repository rule violations found` / *Changes must
be made through a pull request*, and discarded locally afterwards, leaving `main` level with
`origin/main`. A clean tree pushes as a no-op and proves nothing, which is why the proof
needs a throwaway commit.

**M1.2 passed on 2026-09-18.** Resolved on Ubuntu 24.04.1 under WSL2: PHP 8.5.10 CLI from
`ondrej-ubuntu-php-noble`, Composer 2.10.3, Node v24.21.0, gh 2.100.0, git 2.43.0, Docker
29.6.2 reachable through Docker Desktop's WSL integration. `intl` and `pdo_pgsql` are both
present, and OPcache is compiled in as this milestone's notes predicted — it reports as
`Zend OPcache`, so a check grepping for a bare `opcache` line will call it missing and be
wrong. The Docker Desktop WSL-integration toggle was the one item needing the owner, and
was switched on for the `Ubuntu` distro; no interactive sudo turned out to be needed,
because the toolchain was already installed.

**M1.3 passed on 2026-09-18.** The clone lives at `~/code/taskpost`, `docs/private/` is
present in it with both files byte-identical to the Windows originals, and the Windows copy
was renamed to `Documents/GitHub/taskpost-windows-archived` rather than deleted. The session
moved with it: this entry was written from the Linux clone.

The table's first check asks for a log matching the Windows copy *commit for commit*, and
that is no longer literally attainable — nor should it be. M1.1's ruleset allows squash as
the only merge method, so PR #1 replaced the Windows-side `9e1167e` with `92a7f43` on `main`;
the two histories diverge by SHA the moment anything merges. What the check was reaching for
is that nothing was lost in the move, and that holds by stronger evidence than matching
SHAs: both HEADs resolve to the identical tree `0b38333`, and a content diff between them is
empty. Same bytes, different commit identity. The row is left as written — the discrepancy is
an artefact of squash merging, not a defect in the move.

**M1.4 passed on 2026-09-18.** `compose.yaml` runs `postgres` (18.6) and `mailpit` (v1.31.1),
both with healthchecks, and both report healthy about six seconds after `docker compose up
-d`. The persistence check was run with the committed file and no override: a row written
through native PHP over `pdo_pgsql`, then `docker compose down`, then `up`, and the row read
back with its original timestamp intact.

**The PostgreSQL 18 volume path, proven both ways.** 18 stores its cluster in
`/var/lib/postgresql/18/docker` and the image declares its `VOLUME` at the parent, so the
named volume is mounted at `/var/lib/postgresql` — confirmed by `SHOW data_directory` inside
the running container. The pre-18 convention, a volume at `/var/lib/postgresql/data`, was
tried deliberately as a negative control: it **does not lose data quietly**, it refuses to
boot, exiting 1 with an error pointing at `docker-library/postgres#1259`. That is worth
recording precisely because the expectation going in was silent loss; the failure is loud,
so this trap costs an error message rather than a database.

**PostgreSQL is published on 55432, not 5432** — see `08-environment.md`, which owns the
reason.

**M1.5 passed on 2026-09-20.** Laravel 13.32.0 on PHP 8.5.10, from `laravel/laravel`
v13.10.1, migrating against the Compose PostgreSQL — nine tables from the skeleton's three
migrations. Pest 5.2.1 with `pest-plugin-laravel` 5.0.1 runs three tests green, and `/up`
answers 200 over real HTTP rather than only through the test client.

**The skeleton ships an agent trap.** Laravel 13 now includes its own `CLAUDE.md` and
`AGENTS.md`, both Laravel Boost bootstrap instructions telling an agent to
`composer require laravel/boost`. Copied in blindly, the first would have overwritten this
project's `CLAUDE.md` and the second would have added a competing instruction file. Neither
was taken. The install was done by generating the skeleton elsewhere and copying it in with
those two and the three other colliding root files held back — `create-project` refuses a
non-empty directory anyway, and this repository was not empty.

**Pest 5 displaces the skeleton's PHPUnit.** `pestphp/pest` 5.2.1 requires
`phpunit/phpunit` ^13.3.4 while the skeleton pins ^12.5.12 directly, so the direct
requirement was removed rather than argued with. PHPUnit 13.3.4 is installed transitively,
underneath Pest, which is where it belongs.

**SQLite lived in six places, not one:** `.env.example`; a `post-create-project-cmd` line in
`composer.json` touching `database/database.sqlite`; both the `sqlite` connection and the
`'default'` fallback in `config/database.php`; two more fallbacks in `config/queue.php`;
`database/.gitignore`, whose only line was `*.sqlite*`, so the file went entirely; and
`phpunit.xml`. All six are gone, and the word now survives outside `docs/` only in prose
explaining why it is refused. The `mysql`, `mariadb` and `sqlsrv` connections were left
alone — SQLite was removed because it is the specific shortcut that would make a green suite
meaningless (`08-environment.md`), not merely because it is unused.

**An empty `tests/Unit` would have turned CI red at M1.10.** Git does not track an empty
directory, so on a fresh clone PHPUnit cannot find the directory its `Unit` testsuite names
and aborts the whole run — `Test directory ".../tests/Unit" not found` — before one test
executes. The suite entry was removed rather than propped up with a placeholder file, and it
returns in the same commit as the first test that belongs in it (`07-conventions.md`). This
was found by deleting the directory locally and running the suite the way a fresh clone sees
it, which is the only way it surfaces before CI does.

**M1.6 passed on 2026-09-20.** `composer check` exits 0 from a clean tree and leaves it
clean — Pint 1.32.1, Larastan 3.12.2 on PHPStan 2.2.14, then the Pest suite. All three were
also proved to go **red**: a deleted `declare(strict_types=1)` fails Pint, `return 42;` from a
`: string` method fails Larastan with `return.type`, and a flipped assertion fails Pest, each
exiting 1 and each reverted. A gate that cannot fail is worse than no gate — the same
argument `08-environment.md` makes about the `i18n` job.

**Level max over the whole skeleton cost two fixes, not a baseline.** Analysing `app`,
`config`, `database`, `routes` and `tests` produced exactly three errors, and both underlying
causes were real:

- `config/filesystems.php` passed `env('APP_URL')` straight into `rtrim()`. `env()` can
  return a bool — `Env` casts `true`, `(false)`, `null` and `empty` — and the
  `declare(strict_types=1)` Pint had just added turns what used to be a silent coercion into
  a `TypeError`. The gate caught a crash path this same step had created.
- `$this->get('/up')` inside a Pest closure is invisible to static analysis, because `$this`
  is bound to the test case only at runtime. `pest-plugin-laravel` exposes a typed global for
  each of these, so the fix is `Pest\Laravel\get()`. Recorded in `07-conventions.md`, because
  every test from M2 onward meets it.

**Larastan boots the real application.** A runtime error inside a provider's `boot()` does not
come back as an analysis error — it aborts PHPStan with *Application bootstrap failed* and a
stack trace. Worth knowing before it is mistaken for a broken tool, and worth having: the
`static` job therefore also catches anything that stops the application booting at all.

Pint reformatted 24 files, and neither tool leaves anything behind — no cache file, no `tmp/`
directory in the repository — which is what makes the "leaves it clean" half of the check
pass rather than merely the "exits 0" half.

**M1.7 passed on 2026-09-20.** `filament/filament` v5.8.2 sits in `require`, `artisan about`
exits 0 and reports Filament v5.8.2 beside Livewire v4.4.5, and `composer check` is still
green with Filament in the tree. No panel, no provider, no resource — `filament:install` was
never run.

**Nothing was published into the repository.** The install changed `composer.json` and
`composer.lock` and touched nothing else: no `config/filament.php`, no
`app/Providers/Filament/`, no `public/js/filament`. That is hard rule 6 holding at its
cheapest — today, evicting Filament costs two lines in a manifest.

**Livewire 4 arrived transitively, as this milestone predicted.** `filament/support` requires
`livewire/livewire ^4.1` and v4.4.5 resolved. It is not a direct requirement of this project
and should not be made one; the two move together.

**Filament 5 hard-requires 2FA libraries.** `pragmarx/google2fa` 9.1.0,
`pragmarx/google2fa-qrcode` 4.0.0 and `chillerlan/php-qrcode` 5.0.5 are in the lock file now,
pulled in by `filament/filament` itself rather than chosen here. 2FA stays on the unscheduled
list and nothing enables it: plan rule 4 governs features entering the MVP, not a
dependency's dependencies, so this is recorded rather than decided. The practical consequence
is only that the library is already present on the day 2FA stops being unscheduled.

**Larastan boots Filament too**, so the `static` gate passing after this install is not a
formality. Larastan bootstraps the real application, which means every Filament service
provider boots during analysis; a package that could not boot without a panel would have
turned the gate red here rather than at M3.

Production packages went from 76 to 109. The other thirty-odd are what Filament builds on —
`blade-ui-kit/blade-icons`, `kirschbaum-development/eloquent-power-joins`, `spatie/invade`,
`symfony/html-sanitizer` and the rest. **Found work, filed rather than fixed:** `composer.json`
still declares `php: ^8.3` from the skeleton while this project has decided on 8.5, which is
issue #6 against M1.9.

**M1.8 passed on 2026-09-20.** `npm ci && npm run build`, from a wiped `node_modules`,
produces `public/build/manifest.json` — Node v24.21.0, npm 11.19.0, Vite 8.3.0, Tailwind 4,
92 packages audited, 0 vulnerabilities. The skeleton ships **no** `package-lock.json`, and
`npm ci` refuses to run without one, so the lockfile was generated and committed; that is the
whole of what this step adds to the repository. Build output stays gitignored.

**The asset build makes network calls.** `vite.config.js` uses the `bunny()` font helper, so
the build downloads Instrument Sans from `fonts.bunny.net` and emits the woff and woff2 files
into `public/build/assets/`. A runner with no outbound network, or a bunny.net outage, fails
the build outright rather than degrading. Recorded here because M1.10 is the step that would
otherwise find out the hard way.

**A missing manifest does not break the welcome page — and that is not a general property.**
Laravel 13's `welcome.blade.php` guards its own call,
`@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))`,
and inlines a prebuilt Tailwind stylesheet otherwise. That is why `/` answered 200 at M1.5
with nothing built, and it is a property of that one view rather than of Blade. Any view
calling `@vite` **without** that guard throws when the manifest is absent, so the first such
view — the panel layout at M3 — is where the `test` job either builds assets first or the
view is written to tolerate their absence.

**One build warning is left standing.** `laravel:fonts` reports that optimised font fallbacks
need the optional `fontaine` package. Adding a dependency to improve layout shift on a welcome
page that M3 deletes fails the razor, and `optimizedFallbacks: false` would edit a config file
this step scoped out. Left as it is, to be decided at M3, when fonts first matter to anybody.

**Why the required status checks arrive at M1.10 and M1.11 rather than M1.1.** A ruleset that
requires a check no workflow produces leaves every pull request permanently unmergeable —
M1.1 would wedge the milestone it opens. So M1.1 turns on the pull-request requirement alone,
and each check becomes required in the step that creates the job behind it. The gating is not
weakened and does not slip out of the milestone; it is attached to the thing it gates. M1.12
proves the whole mechanism, which is where the exit criterion is actually met.

**Public, not paid.** Merge gating needs rulesets, which are free on a public repository and
a paid feature on a private one. The repository is public under an all-rights-reserved
`LICENSE` — readable, not open source, and no commercial right is granted. Everything that
must not be published lives in `docs/private/`, which is gitignored and never travels with a
clone. The side benefits are unlimited Actions minutes and secret-scanning push protection,
both of which this plan leans on.

M1.2 still needs things only the owner can supply: a sudo password typed interactively, and
the Docker Desktop WSL-integration toggle.

## M2 — Tenancy and identity · M

`Tenant`, `User`, `Membership`. The `BelongsToTenant` trait: global scope plus `tenant_id`
auto-fill. **The tenant resolves from the actor's active membership, not from the host** —
subdomain routing is unscheduled, so there is no wildcard DNS, no hosts-file enumeration and
no per-host session rule to get right here. Factories that make a second tenant free to
create, because otherwise nobody writes the isolation tests.

**M2 dropped from L to M on 2026-09-18**, and subdomain resolution was most of the weight
that left. What replaces it is smaller and stricter: an inactive membership resolves nothing,
and no Action may act on a tenant the actor holds no active membership in
(`02-domain.md`).

**Exit:** two tenants exist with overlapping data; the isolation test for every tenant-owned
model passes; no Action reaches a tenant the actor holds no active membership in.

## M3 — Permissions and the workspace shell · M

The fixed permission catalogue in code. `Role` as a tenant-owned bundle, three defaults —
Member, Agent, Admin — seeded on tenant creation. `Membership::hasPermission()` as the single
check everything reads. Visibility scope (`own` / `all`) applied at query level, because it
is the axis that separates a junior from a manager now that there is one surface.

**One shell, not three.** A single Filament workspace panel served at `/`, using Filament's
own login (`03-architecture.md`). Empty of resources at this point — M3 builds the two
mechanisms the shell will gate with, the permission check behind Filament's
navigation-visibility and page-authorization hooks and the visibility-scope query object; M5
puts the first resource in it, which is the first thing either mechanism has to act on.

`php artisan tenant:create` lands here too, taking its arguments explicitly. It is the only
way a tenant comes into existence, which is why the super-admin panel has nothing left to do.

**Exit:** `Membership::hasPermission()` answers the fixed catalogue correctly for a
membership built from each of the three default roles; the panel at `/` admits an actor
holding an active membership and refuses everyone else; `tenant:create` builds a working
tenant with its three default roles from the CLI.

The two clauses that need a surface are checked where that surface arrives, not here — scope
`own` against tasks at M5, and the gated settings entry at M7. M3 is done when the mechanisms
exist and are unit-tested; nothing is stubbed early to make a screen-level check runnable a
milestone ahead of the screen (`01-principles.md` §1).

## M4 — Internationalisation · S

`en` and `pl` translation files, per-user locale and timezone, UTC storage, locale-aware
date formatting decided once. A check in CI that fails on literal user-facing strings in
Blade.

Small, and permanently expensive to skip — `01-principles.md` §2.

**Exit:** the entire Phase 1 skeleton renders in both languages with no literal strings, and
the CI check catches a deliberately hardcoded one.

---

# Phase 2 — Product

## M5 — First vertical slice — `CreateTask` · M

`CreateTask` end to end: workspace form → Action → task with an allocated number → visible in
the list. Plus the `POST /api/v1/tasks` endpoint calling the same Action. Status, priority,
category and type dictionaries seeded with defaults.

This milestone's real output is **the pattern** — Action contract, authorization split,
`Activity` write, transaction boundary, test shape. Every later Action copies it, so it is
worth getting slowly right.

Numbering is the part to get right first, not last: `SELECT … FOR UPDATE` on
`tenants.next_task_number` inside the insert's transaction, with the unique
`(tenant_id, number)` constraint as the backstop (`02-domain.md`). Two people filing at once
is not a rare event in a system whose whole pitch is that everyone files in one place.

**Exit:** a task created through the workspace and one created through the API are
indistinguishable in the database; a membership with scope `own` sees only tasks it filed or
is assigned to in the workspace list, and a colleague's task is absent from the query, not
merely hidden in the view. All four test categories exist for this slice.

## M6 — Task lifecycle · L

`PostComment` (public comment and internal note), `AssignTask`, `TakeTask`, `ChangeStatus`,
`SetDueDate`, `UpdateTask`. The `Activity` timeline. Attachments through the authorized
controller route. Endpoints alongside, in the same milestone as the Actions.

The largest milestone and the one carrying the system's most important correctness property.
It also carries the three rules that fire on their own: `first_responded_at` set once by the
first public comment from someone who is not the requester; `closed_at` set by `ChangeStatus`
on entry to a `done` or `cancelled` status and cleared on leaving one, so closure is a column
rather than something reconstructed from the timeline; and the reopen rule — a public comment
from the requester on a task whose status type is `done` moves it to the tenant's
`is_default_open` status and clears `closed_at`, while status type `cancelled` does not reopen
(`09-reference-scenario.md`, situations 4 and 6).

**Exit:** the internal-comment leak test passes at query level, not view level — a membership
with scope `all` and no `task.note` fetching a task through both the workspace and the API
receives zero internal comments. Full lifecycle drivable from either door.

## M7 — Configuration · M

Dictionary CRUD with the lifecycle rules from `02-domain.md` — hard delete only while nothing
references the row, `active = false` forever after — plus role CRUD, user invitations and
tenant settings. Filament, mostly. The one guard that is not CRUD: a tenant must always keep
at least one active membership holding `users.manage`.

**Exit:** an office manager sets up a tenant from scratch — statuses mapped to fixed types,
priorities, categories, types, roles, and the people with their visibility scopes — without a
developer and without a seeder; and a membership without `settings.manage` has no settings
entry in the navigation and cannot reach the route by typing it either.

## M8 — Notifications · M

Queue worker and scheduler, run locally. Two notifications: assigned to me, new comment on a
task I filed. Per-user on/off.

**The mail-provider dependency is discharged, 2026-09-18.** This milestone previously waited
on the choice of a transactional provider; with no host and no real recipients, it needs a
mailer that reaches Mailpit and nothing more. A real provider, with SPF and DKIM on a real
domain, is on the unscheduled list below and is not a prerequisite for anything in this plan.

What M8 must get right is not delivery but context: a job carries its tenant in the payload
and the context is torn down between jobs, so a queued notification cannot leak across a
boundary the request-time global scope was protecting (`03-architecture.md`).

**Exit:** both emails land in Mailpit, dispatched after commit, with the right tenant inside
the job. The whole exit is satisfiable on the machine that runs the milestone, which rule 1
requires and the previous version of this milestone did not have.

## M9 — API surface and finding work · M

Sanctum tenant-scoped tokens, cursor pagination, the error shape, rate limits. Task list
filters and search in the workspace — by number, by text in `subject`, by status type, by
`due_at` this week.

**Exit:** the full lifecycle from M6 drivable by `curl` alone, against a documented
`/api/v1`. A three-month-old task is found in under ten seconds by someone who remembers only
a customer's surname or a number (`09-reference-scenario.md`, "what done means" point 8).

---

## Deleted milestones

**Milestone numbers are identity.** They are cited from other docs and from git history, so
they are never renumbered and never reused. The three below were removed on 2026-09-18 when
the project pivoted; the gaps in the sequence stay, and this section is why they are there.
M2 through M9 keep the numbers they had.

| Milestone | Was | Deleted | Why |
|---|---|---|---|
| **M0** | Ground truth — observe a real firm's workflow | 2026-09-18 | There is no firm to observe. The question it existed to answer is now answered by `09-reference-scenario.md`, an invented firm rather than an observed one |
| **M10** | Hardening and go-live | 2026-09-18 | No host to harden and no go-live. Backups, restore rehearsal, error tracking and uptime monitoring move to the unscheduled list, where each waits on a host existing |
| **M11** | Deployment | 2026-09-18 | Deployment is unscheduled — see the dated departure under M1. The ordering note that said M11 ran before M10 goes with it |

**What M0's deletion costs, stated plainly.** The domain model now rests on an invented
scenario rather than on a workflow anybody described from their own week, and an invented one
agrees with you where a described one surprises you. That is recorded as the project's top
open risk in `05-open-questions.md` and as a caveat inside `09-reference-scenario.md` itself.
It is not resolved by anything in this plan — only by somebody real using the system, which
is the first trigger below.

**What M10's deletion costs:** nothing is backed up and nothing is watched, which is correct
while the only data is local seed data and wrong the moment it is not. The trigger for that
is also below.

## Unscheduled

Each of these is a real thing that may one day be built, and none of them has a milestone,
a size or a position in the order. They are listed so that "not now" is a recorded state
rather than an oversight.

| Unscheduled | Waiting on |
|---|---|
| Deployment and CD | A reason to pay for a host — see M1's dated departure |
| A real transactional mail provider | A recipient who is not Mailpit |
| Subdomain routing | A second tenant that is real rather than a fixture |
| The super-admin panel | Something for it to do that `tenant:create` does not already do |
| Task deletion (`task.delete`) | An Admin holding a task that should never have existed. The permission ships at M3; the operation does not, and no Action, route or endpoint implements it (`02-domain.md`) |
| Backups, restore rehearsal, monitoring | Data that would hurt to lose, which means a host |
| Projects / containers | The nullable `project_id` column filling with something. The column is bought; the feature is not (`02-domain.md`) |
| Calendar | A date that a filter failed to catch |
| Checklists | Work with steps that somebody actually forgets |
| Announcements | A message that is not about one task |
| Email intake | A firm whose work arrives by email |
| SLA | A promise that can be breached |
| Reports | A number somebody would act on differently |
| Custom fields | A fifth axis with a name |
| 2FA | An account worth attacking |

**The standing rule for everything on this list: no stub, no placeholder file, no config
flag, no abstraction and no "just in case" column before its milestone starts.** This is the
razor (`01-principles.md` §1) applied to execution rather than to features, and the failure
it prevents is the one where half a feature exists, is untested, is never finished, and is
still in the way two years later. The three cheap seams in `04-scope.md` are the whole of the
exception, they were each argued for individually, and the list is closed.

## Triggers to stop and re-plan

- **A real firm sees the system and the reference scenario turns out to be wrong.** This is
  the expected outcome, not the surprising one — `09-reference-scenario.md` was invented by
  the person who wanted the features in it. Re-plan before the next milestone begins, not
  mid-step, and revise the scenario before revising the plan: the plan is downstream of it.
- **Any milestone doubles its size estimate.** Cut scope inside it rather than let it run;
  that is the razor applied to work rather than features. If the cut is not possible, the
  milestone was mis-decomposed and its step list is re-agreed out loud before more code.
- **Somebody actually wants to use it.** Deployment stops being unscheduled and becomes
  urgent, and M10's deleted contents — backups, a tested restore, monitoring — come back with
  it. The monthly bill is worth paying at the moment there is a user, and not one day before.
- **A second tenant becomes real.** Membership-based resolution was chosen because one tenant
  cannot tell the difference (M2). Two real tenants, especially two that want their own
  hostname, brings subdomain routing back as a decision with a reason rather than as
  architecture bought in advance.
- **A rule wants to branch on `type_id`.** `Type` is decorative by decision
  (`02-domain.md`); the first piece of logic that wants to read it is the tell that the
  fixed-semantics layer has earned its place. Additive in schema, but a decision to record
  rather than a change to slip into a step.
