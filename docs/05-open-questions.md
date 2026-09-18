# 05 — Open Questions

Everything not yet decided. Check here before assuming an answer exists — and check
**Recently closed** at the bottom before reopening one, because the 2026-09-17 pivot moved
most of this page's former contents into it.

## The reference scenario is invented — the top risk

**The firm in `09-reference-scenario.md` does not exist.** It was written on 2026-09-18 by
the author of this system, which means every need it expresses is a need the author already
believed in. The whole domain model now rests on it: four dictionaries and no fifth, two
visibility scopes and no third, a decorative `type`, a `due_at` that nothing chases, one
entity rather than three. Each of those was argued against that firm's week, and that firm
agreed with every one of them, because the person making the argument wrote it.

**This is one step further from ground truth than the project stood before the pivot.**
Until 2026-09-17 the model rested on a workflow somebody had described from their own week
— already weaker than one observed directly, and the reason an observation milestone
existed at all. It now rests on a week nobody has lived. A described workflow occasionally
surprises you; an invented one never does, and the surprises are the entire value.

The partial defence is recorded in `09-reference-scenario.md` and is worth repeating here
because it is the only structural protection there is: the scenario is written first and
cited later, never extended while a feature is under discussion. **A situation added to it
during an argument about a feature is not evidence, and is rejected as such.** That guards
against the scenario being bent to fit. It does nothing whatever about it being wrong from
the start.

**What would close it is cheap, and it has not happened.** Twenty minutes with any real
small firm — a joinery, a garage, a letting agent, anyone with ten to twenty people whose
work arrives by phone — asking one question: show me how a job arrives and what you do with
it until it is finished. No commitment, no product mentioned, half an hour at most. **It is
not scheduled and there is no milestone for it**: M0 was deleted on 2026-09-18 because
there is nobody being observed, which removed the work without removing the question
(`06-build-plan.md`).

It cannot be resolved on this page and it will not be resolved by more thinking. It is
replaced, not answered — by somebody real using the system.

## Product

**AI inside the product.** "AI-heavy" was said about AI-assisted *development*, which is
`CLAUDE.md`'s territory. Whether TaskPost should ever triage, summarise or suggest a reply
is genuinely unanswered — and firmly out of the MVP either way. The case against: at eight
to twenty-five items a week there is nothing to triage, and a human reads the whole list in
a minute. The case for: condensing a long task and its comments before handing it to a crew
is a real chore that scales with nothing. Neither is settled, and nothing is built,
stubbed or configured for either.

## Open, with a milestone that decides them

Each of these has a decision point. None blocks anything before it.

**Choosing a tenant when a user holds more than one active membership.** Subdomains are
deferred, so the host no longer answers this (`03-architecture.md`); the tenant resolves
from the membership, and a user with two of them has to pick. A switcher in the workspace
shell, a chooser after login, and a last-used tenant on the user record are all defensible
and all cheap. **Decided at M3**, when the shell is built. Nobody holds two memberships
today and the second tenant that would create one does not exist, so this is a design
decision taken with no traffic behind it — which is exactly why it is written down rather
than chosen in passing.

**Whether Filament's built-in tenancy is layered over the global scope.** The global scope
enforces regardless; that is `03-architecture.md`'s contract and Filament does not get to
weaken it. The open part is whether Filament's own tenancy is switched on as well, for the
navigation and record-scoping it brings, or left off on the grounds that two mechanisms
doing one job are two mechanisms to keep honest. **Decided at M3**, when the panel is
wired.

**Attachment size limit and MIME allowlist.** A number and a list, not a design question. A
fitter photographing a hydraulic line from a phone on a bad connection sets the ceiling; a
workshop that will never upload an executable sets the list. **Decided at M6**, with the
attachment slice.

**Which engine replaces the `database` queue driver.** `database` is correct through M7 —
one machine, no load, one fewer service to run. `08-environment.md` records Valkey over
Redis on licence grounds for the day it changes. **Decided at M8**, when notifications make
the queue load-bearing.

## Unscheduled, waiting on a reason to exist

**Error tracking and uptime monitoring.** Both are needed before real users and neither is
needed before code. They used to hang off a go-live date; there is no go-live and no host,
so they are unscheduled alongside deployment (`04-scope.md`). They are recorded here for
one purpose: the day a deployment is scheduled, these are scheduled with it, rather than
discovered the week after.

## Commercial and legal

**Moved out of git on 2026-09-08, and still out.** Those questions live in `docs/private/`,
which is gitignored — readable locally, never pushed. They are still open and still
unanswered; they are simply not published. The repository is public for merge gating
(`06-build-plan.md`), and that stays cheap only while this separation holds
(`docs/private/README.md`).

Nothing technical moved out with them. If a commercial or legal answer ever constrains the
architecture, **the constraint is recorded here in engineering terms and the reasoning
stays private.** A public doc may point at `docs/private/`; it may not restate it.

## Recently closed

Recorded so nobody reopens a settled question by accident. This is the most useful part of
the page: the pivot closed more questions than it opened, and a closed question that is not
written down comes back as a conversation six weeks later. Full reasoning lives in the
linked doc — the rows are pointers, not the argument.

| Question | Decision | Where |
|---|---|---|
| The product's name, and the domain question behind it | **TaskPost**; repository `taskpost`; `taskpost.pl` where an example host is needed. Retires the open name question, 2026-09-18 | `00-overview.md` |
| The core entity | One `Task`. It covers a fault, a purchase, a fitting date and a complaint; they share every field, permission and lifecycle, and the only thing that differs is the word the firm uses | `02-domain.md` |
| Whether `type` gets a fixed-semantics layer | No. `type` is decorative — filter and display only, no rule reads it. A dated exception to `01-principles.md` §5; the layer is an additive column if a rule ever wants to branch on it | `02-domain.md` |
| How many interfaces | One. A single Filament workspace for everyone in the tenant, 2026-09-18. The three-screens design question went with the second interface | `03-architecture.md` |
| Login | Filament's own, one panel served at `/`. The hand-built login existed to send people to one of two destinations, and there is one | `03-architecture.md` |
| `workspace.access` | Deleted 2026-09-18. It only ever chose between two interfaces. Visibility scope inherited its job and is now the axis separating a junior from a manager | `02-domain.md` |
| Who may see an internal comment | Holders of `task.note`. The property and its query-level test are unchanged; only the gate moved | `02-domain.md` |
| Default roles | Member / Agent / Admin. The **Reporter** default was retired with the portal it was named after | `02-domain.md` |
| Whether multi-tenancy survives a project with no second tenant | Yes, in full — trait, global scope, `tenant_id NOT NULL`, tenant stamped into job payloads, context torn down between jobs, an isolation test per tenant-owned model. It is a foundation and exempt from the razor | `03-architecture.md` |
| Subdomain routing | Deferred 2026-09-18. The tenant resolves from the active membership, not the host: no wildcard DNS, no hosts-file entries, no reserved host | `03-architecture.md` |
| Sessions shared across subdomains | Moot — there are no subdomains to share one across | `03-architecture.md` |
| The super-admin panel | Cut 2026-09-18; `php artisan tenant:create` does its only job. The boolean on the user record survives as the gate a future cross-tenant surface would read | `02-domain.md` |
| Deadlines | In. `due_at` on the task, settable and filterable, from the first slice. Reminders and escalation are out — nothing chases a date, a person with a filter does | `02-domain.md` |
| What is bought against a future container | Exactly three seams: the generic `Task` plus `type`, comments/attachments/activity attached polymorphically, and date fields from day one. Nothing beyond them | `02-domain.md` |
| `project_id` | A nullable column and nothing else — no model, no table, no foreign key, no UI, no Action. The column is bought; the feature is not, and the column is not evidence that it is | `02-domain.md`, `04-scope.md` |
| Chat, channels, direct messages | Never built here. Discussion is task-scoped only; competing with a messenger on messaging is the one fight this product does not pick | `02-domain.md` |
| What "done" means | Listwa's week running end to end inside TaskPost — eight conditions, all true at once. It replaces a duration-based definition that no longer had anybody to run for | `09-reference-scenario.md` |
| Where the system runs, and who holds the backups | Closed 2026-09-18: there is no host and nothing to back up. Deployment is unscheduled rather than late, because a monthly bill on a free-time project invents the deadline the project exists without | `04-scope.md` |
| Transactional mail provider, SPF and DKIM | Closed on the same date for the same reason. Mail lands in Mailpit locally; a provider is a paid service | `03-architecture.md`, `06-build-plan.md` |
| Design of the hand-built screens | Closed 2026-09-18: there are none. Filament panels ship stock, per-tenant theming is a feature nobody has asked for, email is Laravel's default markdown mail, and Filament's own components carry the accessibility floor that three hand-built screens would have had to be given deliberately | `03-architecture.md` |
| Roles per membership | Exactly one, not a set. A pivot table is an additive migration on the day somebody needs a union | `02-domain.md` |
| How the super admin is modelled | A boolean on the user record, not a membership. Read by nothing today, and never by tenant authorization | `02-domain.md` |
| Per-tenant task numbering | A counter on the tenant row under `SELECT … FOR UPDATE`, with unique `(tenant_id, number)` as the backstop. Not `max(number) + 1` | `02-domain.md` |
| The status of a new task | The tenant flags exactly one `is_default` status of fixed type `new` | `02-domain.md` |
| Deleting a dictionary row that is in use | Deactivate only; hard delete allowed while nothing references it | `02-domain.md` |
| A requester commenting on a closed task | Auto-reopens to the tenant's `is_default_open` status. Status type `cancelled` does not reopen — abandoned is not finished | `02-domain.md` |
| Policies or Actions for authorization | Both. Policies hide, Actions enforce, one shared check underneath | `03-architecture.md` |
| Who writes an `Activity` row | The Action that caused it, never a model observer: an observer cannot see who did it, and the actor is half the value of a timeline | `03-architecture.md` |
| Tenant context inside queued jobs | An explicit `tenant_id` in the payload plus job middleware, with `NOT NULL` in the schema as the backstop | `03-architecture.md` |
| Tenancy and permission packages | Neither. A trait plus a global scope; a PHP enum plus a JSON column | `03-architecture.md` |
| Test database | PostgreSQL everywhere, never SQLite | `06-build-plan.md` |
| Local development environment | WSL2 plus Docker Compose for backing services, repository inside WSL | `08-environment.md` |
| Cache and queue containers at M1 | None. `database` drivers until M8 | `08-environment.md` |
| How changes reach `main` | Branch and pull request; four CI jobs gate the merge; squash | `07-conventions.md`, `08-environment.md` |
| Static analysis strictness | Larastan level max, no baseline | `07-conventions.md` |
| Translation key convention | `file.context.item`; a key missing from `pl` fails CI | `07-conventions.md` |
| Stack versions | Laravel 13, PHP 8.5, Filament 5, Livewire 4, Pest 5 — re-verified 2026-09-08, re-verified again at install | `CLAUDE.md` |
| Pest major version | Pest 5, not 4 — taken while the repository still has no tests to migrate | `CLAUDE.md` |
| The unit of work | The step, not the milestone: one branch, one pull request, one sitting, agreed in advance | `06-build-plan.md` |
| Whether to run an observation milestone first | M0 deleted 2026-09-18 — there is nobody to observe. Its question is now the top item on this page, and the invented scenario is the answer standing in for it | `06-build-plan.md` |
| Commercial and legal notes | Moved to gitignored `docs/private/` on 2026-09-08 so the repository could be public | `05-open-questions.md` |
