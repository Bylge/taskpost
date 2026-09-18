# 03 — Architecture

## Layers

```
        ┌────────────────────┐      ┌────────────────────┐
        │  Filament          │      │  REST API          │
        │  workspace  (  /  )│      │  /api/v1           │
        └─────────┬──────────┘      └─────────┬──────────┘
                  └───────────┬───────────────┘
                              ▼
                     ┌─────────────────┐
                     │     Actions     │   all business rules
                     └────────┬────────┘
                              ▼
                     ┌─────────────────┐
                     │ Models / Domain │
                     └────────┬────────┘
                              ▼
                        PostgreSQL
```

**Two entry points, one domain.** The UI does **not** call the API — both call Actions
directly. This is the "two doors, one room" principle from `01-principles.md`. Until
2026-09-18 there was a third box: the hand-built reporter portal. It went that day, together
with the super-admin panel, which the Filament box used to cover alongside the workspace.
Both are recorded under **Surfaces** below.

An Action is a single-purpose class with one public `handle()` method:
`App\Actions\Task\CreateTask`, `AssignTask`, `ChangeStatus`, `PostComment`. Input is
validated by the caller; authorization and rules live in the Action.

### The Action contract

Every Action, without exception:

1. **Authorizes first.** Throws on failure — it never returns a boolean the caller might
   forget to check.
2. **Takes a readonly DTO** once it needs more than two inputs (`CreateTaskData`).
   Positional arguments rot as parameters accumulate.
3. **Runs inside one transaction**, covering the write and its `Activity` rows.
4. **Writes its own `Activity`.** Never a model observer: an observer cannot see who did it
   or why, and half the value of the timeline is the actor. One row per field that actually
   moved, so an Action changing several at once — `UpdateTask` is the only one — writes
   several, and a field submitted unchanged writes none (`02-domain.md`).
5. **Dispatches notifications after commit** (`->afterCommit()`). A job that starts before
   the transaction lands reads a row that does not exist yet, intermittently, in production
   only.
6. **Returns the affected model.** Not a bool, not an array.

### Authorization lives in one place, checked in two

`Membership::hasPermission()` is the single source. It is consulted twice, for different
reasons, and both are required:

- **Policies** — so Filament and Blade can hide what you cannot do. Cosmetic. They delegate
  to `hasPermission()`; they never contain a rule of their own.
- **The Action** — so it is actually enforced. The API has no buttons to hide, and a hidden
  button is not a security control.

An Action that trusts its caller's check is a bug even when every current caller checks.

## Multi-tenancy

**Shared database, `tenant_id` column, global scope.** Not schema-per-tenant, not
database-per-tenant — both multiply migration and backup work beyond what one developer
should carry.

- Every tenant-owned model uses a `BelongsToTenant` trait applying a global scope and
  auto-filling `tenant_id` on create
- **Tenant resolution is by membership**: the tenant is the one the logged-in user's active
  membership points at. Not by host — subdomain routing is deferred (see **Hosts and
  routing**)
- **Tenant isolation is tested.** Every tenant-owned model gets a test asserting that
  tenant A cannot read or write tenant B's rows. This is not optional coverage.

Nothing queries across tenants today. The super-admin panel used to be the single place
allowed to, by explicitly removing the scope; it was cut on 2026-09-18 and
`withoutGlobalScope()` now has no legitimate caller anywhere in the application. The day a
cross-tenant surface exists it is one surface gated by the super-admin boolean on the user
record (`02-domain.md`), not a scope removal scattered through queries.

A single-tenant install is simply an install with one tenant. There is no separate code
path, and no decision about self-hosting has to be made before anything ships.

The global scope is the enforcement layer. Whether Filament's own tenancy support is
layered on top of it is still open (`05-open-questions.md`) and changes nothing below it.

### The database

PostgreSQL everywhere — local, CI and the test suite. Not SQLite for tests: a global scope
over joined queries, the `SELECT … FOR UPDATE` in the task-numbering path (`02-domain.md`)
and JSON column behaviour all differ enough that a green suite on SQLite proves less than
it appears to. Parity is cheap now and unrecoverable later (`08-environment.md`).

### Tenant context outside HTTP

The predictable failure of this design: a queued job, a console command or the scheduler
runs with no request to resolve a tenant from. The global scope then either filters
everything out or, worse, writes a row belonging to nobody.

- `tenant_id` is `NOT NULL` on every tenant-owned table. The database refuses the ambiguous
  write rather than storing it.
- **`Queue::createPayloadUsing()` stamps `tenant_id` onto every job payload** at dispatch,
  and job middleware restores the context before `handle()` runs. Stamping centrally rather
  than per-job matters: the job someone writes in a hurry six months from now is stamped
  too, and remembering to add a property is not a control.
- **Context is torn down after every job**, pass or fail. A worker is a long-lived process
  handling many tenants in sequence; context left standing leaks into the next job on that
  worker, which produces a cross-tenant write that no test of a single job will ever catch.
- Console commands that touch tenant data take a tenant argument. There is no "current
  tenant" default outside a request — which is why `tenant:create` takes its arguments
  explicitly rather than prompting for a context that does not exist.
- Two tests, not one: a job dispatched under tenant A still resolves tenant A when it runs,
  **and** a job for tenant B running immediately after one for tenant A on the same worker
  sees only B.

## Surfaces

| Surface | Built with | Audience |
|---|---|---|
| Workspace | Filament, served at `/` | Everyone holding an active membership in the tenant |
| REST API | Laravel + Sanctum, `/api/v1` | Scripts and any future integration |

**One workspace for everyone.** A joiner filing that the hinges are low and a workshop lead
planning the week use the same panel; what differs between them is visibility scope and
permissions, never the interface (`02-domain.md`, `09-reference-scenario.md`). The task
conversation view is a custom Filament page inside that panel — same navigation, same
styling, no second shell.

### Removed on 2026-09-18

Recorded rather than silently dropped, because each was a decision with a reason.

| Removed | Why |
|---|---|
| The hand-built reporter portal (Livewire + Blade, three screens) | Two interfaces for fifteen people is two things to build, translate, test and explain. One panel, with scope deciding what a person sees, does the same job. The whole three-screens design question goes with it |
| The super-admin panel | Nothing left for it to do once tenant creation became a console command. The super-admin boolean on the user record survives as the gate any future cross-tenant surface reads (`02-domain.md`) |
| `workspace.access` | It existed only to choose between those two interfaces (`02-domain.md`) |

Deleting the portal is what makes the razor's answer to "which interface does this person
get" free: there is no question. Everything that used to be answered by which panel you
landed in is now answered by two axes that already had to exist.

### Tenant creation

`php artisan tenant:create`, taking its arguments explicitly — no prompts and no inferred
context. No screen: the only person who would ever open one is the author, and a command is
consistent with the rule above that anything touching tenant data outside a request names
what it is acting on.

### Hosts and routing

One host, and every path on it is the same application.

| Path | Serves |
|---|---|
| `/` | The Filament workspace, behind login |
| `/api/v1` | REST API, tenant resolved from the token's membership exactly as the UI resolves it from the session's |
| `/up` | Health route. Costs nothing now, and a future deployment needs it |

**Subdomain routing is deferred**, decided 2026-09-18. No wildcard DNS, no hosts-file
entries, no reserved host for a panel that no longer exists. The tenant comes from the
user's active membership, which is sufficient while the number of real tenants is one and
re-addable the day it is two — resolution is a single line in a middleware, and every query
below it already goes through the global scope either way.

What that defers, and what returns with it:

- **The per-host session rule.** The session cookie was never to be shared across
  subdomains — no wildcard `SESSION_DOMAIN` — because a wildcard cookie puts every tenant
  host, including one later handed to a client, inside a single session boundary. That
  reasoning is correct and is recorded here rather than deleted. There is one host now, so
  the question is **moot rather than answered**; the rule returns unchanged with subdomains.
- **The 403 on a foreign tenant.** When tenants were addressable by host, a logged-in user
  requesting one they held no active membership in had to get **403** — not a login screen
  and not an empty list, because the difference between the three leaks whether that tenant
  exists. With no tenant address to probe, a foreign row is simply invisible to the global
  scope and the response is a 404. The rule returns with the addresses.

### More than one active membership

A user may belong to several tenants (`02-domain.md`), and nothing above says which one
resolves when two are active. **This is open, not solved here.** The shape it will take is
one of two: a picker after login that sets the active tenant on the session, or Filament's
own tenancy switching layered over the global scope. Both are compatible with everything in
this document, which is why the decision can wait. It is recorded in
`05-open-questions.md` and decided at M3, where the workspace shell is built and the
question first has to produce a screen.

## The polymorphic seam

`Comment`, `Attachment` and `Activity` attach **polymorphically** — morph names
`commentable`, `attachable`, `trackable` — and `Task` is the only thing they attach to
today (`02-domain.md`). This is a dated departure from "no speculative structure", taken
2026-09-18, and it is written down with its cost rather than presented as free.

**What it buys.** If a container ever becomes real — a project, a job, a kitchen — it
arrives as a new morph target: a table, a `tenant_id`, an isolation test, and comments,
files and history already work on it. The alternative, `task_id` columns on three tables,
buys the same feature as a migration across populated tables in a system people are using,
which is the expensive order of operations.

**What it costs**, stated plainly:

- **No composite foreign key across the morph.** The database cannot express "this
  `commentable_id` refers to a row in whichever table `commentable_type` names", so there
  is no referential integrity on that side. `tenant_id NOT NULL` plus the global scope is
  the whole integrity story, and it is the reason both are non-negotiable here.
- **Larastan at level max is fussier about morph targets.** A `MorphTo` returns a union the
  analyser cannot narrow on its own, so the relations need explicit generic annotations and
  the occasional assertion. That is a real tax on every morph relation written, paid at
  level max deliberately rather than by lowering the level.
- **Isolation is unaffected.** Each of the three tables carries its own `tenant_id NOT
  NULL` and uses `BelongsToTenant`, so each gets the same mandatory isolation test as any
  other tenant-owned model. Polymorphism changes what a row points at, never which tenant
  owns it.

The morph map is pinned to short string aliases in a service provider rather than to class
names, so renaming a class never rewrites what history says happened.

## REST API

- Versioned: `/api/v1`
- Token auth via Laravel Sanctum; tokens are tenant-scoped
- Covers what the UI covers, because both call the same Actions
- Surface: authenticate, list tasks, read task, create task, comment on a task, change
  status, assign, set a due date — `/api/v1/tasks` with comments nested under
  `/api/v1/tasks/{task}/comments`
- JSON only, standard HTTP semantics, cursor pagination
- One error shape everywhere — `{ "message": …, "errors": { field: […] } }`, Laravel's
  validation envelope, used for every non-2xx including authorization failures

The API is a first-class citizen from the first week, not something bolted on later. It is
the proof that the Action layer is real rather than decorative, and it is the cheapest
possible integration story for any future client. **A task created through the API and one
created in the workspace are indistinguishable in the database** — that is M5's exit
condition, and it is only true because neither door has rules of its own.

## Internationalisation

- All system strings are translation keys. `en` and `pl` from day one
- Locale is per user, switchable, stored on the user record
- **Tenant-created content is never translated.** Status names, categories, types and task
  bodies are data in whatever language the client typed
- Timezone per user; all timestamps stored UTC
- Date and number formatting follows locale, decided once at the start

## Notifications

Queued mail. The queue driver is `database`, and a supervised worker plus the scheduler
arrive with it at M8 (`06-build-plan.md`). Which driver the queue uses is a config
decision, not an architectural one; the licence-grounded preference for Valkey over Redis,
should a real broker ever be needed, is recorded in `08-environment.md`.

Mail is delivered to **Mailpit** locally, and that is the whole of it. **There is no
transactional provider and no production domain, because there is no deployment.** The
standard stands as a requirement for the day mail becomes real rather than as something
pending: a real transactional provider with SPF and DKIM configured, because `mail()` and
unauthenticated SMTP are not acceptable for a system people rely on. Unscheduled.

Notification preferences are per user, per tenant, and deliberately minimal
(`02-domain.md`).

## Storage

Attachments on the local filesystem behind Laravel's storage abstraction, so an
S3-compatible backend is a config change. Uploads are validated by MIME type and size,
stored outside the web root, and served through an authorized controller route — never by
direct URL.

## Authentication and security

- Session auth for the workspace, Sanctum tokens for the API
- Rate limiting on login, password reset and the API
- Password reset, email verification, user invitations
- **2FA is unscheduled.** The requirement on the auth flow is unchanged — it must not make
  2FA painful to add — and it is now a question of configuring a package's login rather
  than of rewriting ours
- Authorization always goes through the permission catalogue — no `if ($user->is_admin)`
  anywhere

### Filament's own login

**Filament's login is used**, decided 2026-09-18. One panel, one destination, so there is
nothing left for a login page to decide.

Deleted with it, and recorded rather than dropped:

| Removed | Why |
|---|---|
| The hand-built login page on the tenant host | It existed so that one login could serve two interfaces. There is one interface |
| The shell rule (`workspace.access` → `/app`, otherwise `/`) | It chose between those two destinations. Deleted with the second one |
| The separate login on the super-admin host | Deleted with the panel and with the host |

The argument that produced the hand-built page — two login pages for one company is a
support burden nobody signed up for — is satisfied more cheaply than it was: there is one
page because there is one door.

## Packages we deliberately do not use

Both of these solve our problem in general and would cost more than they save here. Written
down so nobody helpfully installs one later.

**No tenancy package** (`stancl/tenancy`, `spatie/laravel-multitenancy`). They are built
around database- and schema-per-tenant, which this document already rejected. Our
enforcement layer is a trait, a global scope and a context singleton — small enough to read
in one sitting, which matters more than features for the thing that stops tenant A seeing
tenant B.

**No permission package** (`spatie/laravel-permission`). It models globally-scoped roles and
guard names; ours are tenant-owned rows over a catalogue fixed in code. Bending it to that
shape costs more than the alternative:

- `Permission` is a PHP enum. That *is* the catalogue `02-domain.md` requires — it cannot
  drift into client-editable rows, because it is not rows.
- `roles.permissions` is a JSON array of enum values, tenant-owned.
- `Membership::hasPermission(Permission $p)` is the one function everything reads.

## Deployment

**There is none.** Decided 2026-09-18: no host, no VPS, no CD milestone, no paid service of
any kind. The application runs locally and nowhere else, and that is not a state waiting to
be fixed.

The reason, recorded in the owner's terms: **a monthly bill on a free-time project converts
"no deadline" into a deadline**, which is the one pressure this project deliberately exists
without. A server that costs money every month is a server that has to be justified, and
the justification becomes a schedule.

This is a dated departure from "deployable from day one" (`01-principles.md`), and the
thing that principle was protecting is kept for free instead of being abandoned. What the
application owes a future deployment, because each is cheap now and expensive later:

| Owed | Why it is free now |
|---|---|
| Configuration comes from the environment | `.env` and nothing hardcoded is how Laravel works anyway |
| The schema lives in migrations | There is no other way the schema is allowed to exist |
| A `/up` route exists | One route, returning 200 |
| Nothing assumes the application runs on a laptop | No absolute paths, no `localhost` in code, no shelling out to something only a developer has |

Nothing beyond those four is built, configured or stubbed for a deployment that has no
date. The mechanics — scripted or imaged, staging or not, scheduled backups — are
`08-environment.md`'s and are decided when there is a host to decide them about. **Rollback
is the exception**, because its trigger is data existing rather than a host existing:
`08-environment.md` decides it now — code rolls back, schema is forward-only, destructive
changes are split across two releases, and a verified backup is taken immediately before any
migration that touches data that matters.

## Testing

Pest. The tests that must exist:

1. **Tenant isolation per model** — tenant A can neither read nor write tenant B's rows
2. **Permission enforcement per Action** — the Action throws, not the button being hidden
3. **Internal comments never reach a user without `task.note`** — asserted on what the
   query returns, never on what a view renders. A note filtered in Blade has still been
   loaded, still sits in the Livewire payload, and is still returned by `/api/v1` where
   there is no view at all (`02-domain.md`)
4. **Happy path for each Action**

Coverage targets are not a goal; those four categories are. Category 3 changed gate on
2026-09-18 — it read `workspace.access` before the portal was cut — and the property, the
level it is enforced at, and the test are otherwise unchanged.
