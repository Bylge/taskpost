# 08 — Environment, CI and Deployment

Where the code runs, how it gets tested, and what happens when something has to be undone.
The deployment section exists to record that there is nothing to describe yet, and why that
is a decision rather than an omission.

## Local — WSL2 + Docker Compose

Decided, and the reason changed on 2026-09-18 without the decision changing. It used to be
"the host is Linux, so local should be". There is no host now (see **Deployment**), so the
reason is the one environment that does exist: **CI runs Ubuntu, and a test suite that
passes on a case-insensitive filesystem against a different PostgreSQL build is not evidence
about anything.** `03-architecture.md` refuses SQLite for the same reason — the thing under
test is the stack, not an approximation of it.

**The repository lives inside the WSL2 filesystem** — `~/code/taskpost`, not `/mnt/c/...`.
Cross-filesystem access is slow enough to be felt on every Composer install and every test
run, and file watching for Vite is unreliable across the boundary. This one has a cost
today: the docs currently sit on the Windows side and move at M1.3 (`06-build-plan.md`).

**`docs/private/` does not travel with a clone.** It is gitignored, so any fresh checkout —
the M1.3 move into WSL included — arrives without the files `CLAUDE.md` sends agents to
read. Copy the directory across by hand and confirm it is there before deleting the copy you
took it from. It exists in exactly one working copy and nowhere else, so **a clone is not a
backup of it**, and the accepted cost of keeping the repository publishable
(`docs/private/README.md`) is that nothing else backs it up either.

**Git starts now, not at M1.** The repository — `github.com/Bylge/taskpost` — is created on
the Windows side while the docs still live there, so planning changes are versioned and
revertible from the first commit. History travels with the folder, so the M1.3 move into
WSL costs nothing.

**That repository is a new one, and its history starts at the rewrite** — 2026-09-18. The
earlier repository was not renamed and its commits were not carried over, because material
that `docs/private/` exists to keep out of git had reached its published history and a
force-push does not unpublish a commit on a public repository. **M1.1 is therefore re-run
in full** against the new remote, not assumed to carry over (`06-build-plan.md`).

**Compose runs backing services only** — `postgres` and `mailpit`. PHP, Artisan, the queue
worker and Vite run natively in WSL2.

**No cache or queue container until M8** — decided 2026-09-08. `CACHE_STORE=database` and
`QUEUE_CONNECTION=database` until something actually dispatches a job or reads a cache,
which is M8; the razor applied to infrastructure. When M8 needs one it is **Valkey**, not
Redis: Redis has been tri-licensed since 8.0 with AGPLv3 the only OSI-approved option and a
network-use clause that matters for hosted multi-tenant software, while Valkey is BSD-3 and
protocol-identical — a one-line change of image name, nothing on the Laravel side.
Containerising the app locally buys parity we already get from the OS and costs a rebuild on
every change.

**The wildcard-subdomain section was deleted on 2026-09-18**: tenants resolve from the
user's active membership rather than from the host (`03-architecture.md`), so local setup
has no DNS step, no `/etc/hosts` entries and no reserved hostname at all. It comes back, in
this file, on the day subdomain routing does.

**Required:** PHP 8.5 with `pdo_pgsql`, `intl`, `fileinfo`, plus the Laravel baseline.
`intl` is not optional — `filament/support` requires `ext-intl` at composer time, so a
missing one fails the install rather than a feature. `php8.5-opcache` does not exist as a
package; naming it aborts the apt transaction. PHP 8.5 comes from `ppa:ondrej/php`; stock
Ubuntu 24.04 has 8.3. Composer, Node LTS, Docker Desktop with the WSL2 backend.

**Seeders build a multi-tenant world, not a single tenant.** Two tenants with overlapping
data, one user per default role in each, one user who is a member of both, and tasks
carrying internal comments. If local looks single-tenant, isolation bugs stay invisible
until the day a second real tenant exists — which is the one day they must never appear.

## CI — GitHub Actions

Runs on every pull request and on push to main. Four jobs, parallel:

| Job | Does |
|---|---|
| `lint` | `pint --test` |
| `static` | Larastan, level max |
| `test` | Pest, against a PostgreSQL service container on the same major as local Compose (`06-build-plan.md` M1.4) |
| `i18n` | `en`/`pl` key parity from M1; the literal-string lint joins it at M4 |

No version matrix. One PHP version — the one local runs, and the one any future host would
therefore run. Testing combinations we will never ship is work that buys nothing.

**The `i18n` job is split across two milestones** — decided 2026-09-08. Key parity ships at
M1 as a real Pest test; the literal-string lint ships at M4, where `07-conventions.md`
already puts it, because the skeleton's welcome page is wall-to-wall literals and the lint
would fail on day one. M1 proves the job's *red* path with a throwaway unpaired key: a
parity check over zero locale directories is a green that cannot go red, which is worse than
no job at all.

**Main is branch-protected: all four jobs green, or no merge.** **There is no reviewer — one
person works on this — so branch protection is the entire review process**, which is exactly
why it does not get bypassed for a small change. Enforced with a repository **ruleset**,
which is free on a public repository; that is why the repository is public under an
all-rights-reserved `LICENSE` (`06-build-plan.md` M1.1) rather than on a paid plan. Each job
becomes a *required* check in the step that creates it, M1.10 and M1.11: a ruleset demanding
a check no workflow produces would leave every pull request unmergeable. Branches must also
be up to date with `main` before merging, so nothing merges on a green run that no longer
describes the code.

## Deployment — unscheduled

**There is no deploy target and no date for one.** No host, no VPS, no Actions deployment
secrets, no build-in-CI-and-ship, no CD milestone. The former M10 (hardening and go-live)
and M11 (deployment) were deleted on 2026-09-18 (`06-build-plan.md`), and the CD
specification that used to sit in this section went with them.

**The reason, recorded because it will be questioned later: a monthly bill on a free-time
project converts "no deadline" into a deadline.** That pressure is the thing this project
exists without, and paying for a server nobody uses buys it back. When a real user appears,
the bill has a reason and this section gets written.

What M1 still owes that future, because each is free now and expensive to retrofit:

| Kept | Why it cannot wait |
|---|---|
| Configuration comes from the environment | A constant hardcoded in week one is found by grep in year one, and by a bug report before that |
| The schema lives in migrations | The alternative is a database whose shape exists only on one laptop |
| A `/up` route | One line now; the first thing any check, script or monitor calls later |
| Nothing assumes a laptop | No absolute paths, storage through the filesystem abstraction, queue through a connection name |

What is deliberately **not** kept, because it is speculative structure
(`01-principles.md` §1): no deploy script, no Dockerfile, no `.env.production`, no
host-specific config, no secret placeholders. The earlier decisions about *how* a deploy
would work — scripted rather than Docker, assets built in CI, the worker restarted, the
release health-checked — are deleted with the milestone rather than carried as a plan,
because a specification nobody executes rots quietly and is trusted anyway.

`.env` is never in git; `.env.example` always is, and `07-conventions.md` makes updating it
part of done. That holds today, for local and CI, whatever happens to deployment.

## Rollback

The part that has to be decided before there is data to lose rather than after. There is no
launch date to hang it on, so **the trigger is the first moment data somebody cares about
exists — which, with no users, may well be the owner's own.** Nobody announces that day,
which is why the rules are written now rather than on it.

**Code rolls back.** Check out the previous commit. Always available, fast, boring.

**Schema does not.** Migrations are forward-only from that moment (`CLAUDE.md`), so a
migration that turns out wrong is corrected by another migration, never by
`migrate:rollback` against data that matters.

Two rules follow, and they are the whole point of this section:

- **Destructive changes are split across two releases.** Release N stops writing the column;
  release N+1 drops it. Never the same release. This is what makes "code rolls back"
  actually true — a rollback into a schema that no longer has the column is not a rollback
- **A verified backup is taken immediately before any migration that touches data that
  matters.** Not last night's. The one from four minutes ago. Locally that is a `pg_dump`
  into a file whose restore has been tried at least once

Until that day the database is disposable: seeded data only, `migrate:fresh` is the normal
tool, and rollback is not a question. **The failure mode these rules exist to prevent is not
a bad migration — it is nobody noticing that the disposable phase ended.**

## Environments

**Local, and only local.** One machine, WSL2, seeded data. CI is ephemeral and is a test
harness rather than an environment anybody uses. There is no staging and no production, and
adding either is a decision this document records when it happens, not a gap to be filled.

## Health and observability

`/up` exists from M1 and costs a line. Nothing calls it yet; it is kept because it is what a
deploy check, a monitor or a load balancer would call, and adding it later means editing
routes during an incident.

**Error tracking and uptime monitoring are unscheduled with deployment** (see above). They
are not chosen, not stubbed and not configured: no DSN sits in `.env.example` waiting, and
nothing in the application reports to a third party. With one machine and one user, the
error report is the stack trace on the screen.
