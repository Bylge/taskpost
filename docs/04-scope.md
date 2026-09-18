# 04 — Scope

This page decides *what* is in. `06-build-plan.md` decides *when*, and
`09-reference-scenario.md` decides when it is **finished**.

Apply the razor (`01-principles.md` §1) to everything here except the foundations. Its
current form, restated on 2026-09-18 after the pivot removed the firm it used to cut
against:

> **Would a fifteen-person firm notice if this were missing?** If no, cut it. If Jira has
> it and they would not miss it, that is evidence *against* building it, not for.

The firm in that question is the one in `09-reference-scenario.md`. Every row in the
out-of-scope table below is answered by reading that firm's week, and none of them is
answered by judging whether the feature is a good idea in general — every feature in Jira
was once a good idea in general.

## MVP — what runs Listwa's week

**Foundations.** Built properly, not cheaply; `01-principles.md` §2 exempts them from the
razor, and the pivot did not touch that exemption even though nobody is waiting for any of
them.

- Multi-tenancy with enforced isolation
- Roles and permissions, tenant-editable, three defaults: Member, Agent, Admin
- The Action layer
- i18n, `en` + `pl`, switchable per user

**Tasks**

- Create, view, edit, comment
- Internal notes, separated from public comments and gated by `task.note`
- Attachments
- Assignment: take it yourself, or assign it to someone else — permission-gated
- Status, priority, category and **type** — four tenant-configurable dictionaries
- **Due dates.** `due_at` on the task, settable and filterable. Nothing chases one; see the
  out-of-scope table
- An activity timeline on every task

**Interface**

- **One Filament workspace**, the same one for everyone in the tenant. Permissions decide
  what a person may do in it; visibility scope decides which rows it shows them. There is
  no second interface and no per-audience surface (`03-architecture.md`)
- **Tenant creation is a console command** — `php artisan tenant:create`, taking its
  arguments explicitly. Not a panel, not a screen, not a signup form. The only person who
  would ever run it is the author, and a screen for an audience of one is a screen to
  build, translate, permission and test for no reader

**Supporting**

- Email notifications with a per-user on/off switch
- Search and filters on the task list. A search box and filters, not typeahead suggestions
  — the razor removed the suggestions and they have not earned their way back
- REST API v1, the second of the two doors (`01-principles.md` §4)

## The three cheap seams

The only deliberate exceptions to "no speculative structure" (`01-principles.md` §1). Each was
argued on its own, each is dated 2026-09-18, and the list is closed at three:

1. **One generic `Task` plus a tenant-configurable `type`** rather than an entity per kind of
   work. `type` is decorative — no logic reads it (`02-domain.md`)
2. **Comments, attachments and activity attached polymorphically** — `commentable`,
   `attachable`, `trackable` — so a container, if one ever exists, inherits them with no data
   migration (`03-architecture.md`)
3. **Date fields on the task from day one.** `due_at` is real, settable and filterable in the
   first slice; adding a date column to a populated table is a migration plus a backfill
   conversation (`02-domain.md`)

Nothing else is bought ahead of need. The nullable `project_id` column below is a separate
purchase on the same reasoning, recorded in `02-domain.md`; it does not join this list and this
list does not grow.

## `project_id` is a column. Projects are not a feature.

Read this before concluding from the schema in `02-domain.md` that projects are in scope.
**They are not.**

| What exists | What does not exist |
|---|---|
| A nullable `project_id` column on `tasks` | Any `Project` model, table or migration |
| Nothing that reads it, nothing that writes it | Any foreign key, relation, factory state or seeder row |
| | Any UI, Action, filter or API field |

The column is **not** one of the three cheap seams above — it is a separate, bounded purchase
made on the same reasoning: adding a column to an empty table is a line, and adding one to a
populated table in a system people are using is a migration plus a conversation. **Buying the
seam is not a decision to build the feature.** Finding the column in the schema is not
permission to start; projects sit in the out-of-scope table below like everything else, and
they leave it the same way everything else does — when `09-reference-scenario.md` describes
a week that needs them.

This is the failure mode the section exists to prevent: a later reader finds an unexplained
nullable column, infers a half-finished feature, and finishes it.

## Explicitly out of the MVP

Not "later, probably" — out, until `09-reference-scenario.md` describes a week that needs
them.

| Feature | Why it is out |
|---|---|
| Chat, channels, direct messages | Discussion is task-scoped only. Listwa has a messenger and it works for "are you five minutes away"; what it fails at is memory, which is what comments on a task are for. Competing with a messenger on messaging is the one fight this product does not pick. Cut 2026-09-18 |
| Announcements, a company noticeboard | The same fight in a quieter shirt. Fifteen people who share a workshop are told things by being told, and the one thing that must outlive the telling is already a task |
| Calendar | Three or four installs a week live in Marta's diary and Paweł's head. A due date plus a filter covers what the system needs to know about time, and a second calendar that has to be kept in step with the first one is worse than no calendar |
| Checklists | An install has perhaps six steps and the crew has done four hundred of them. A checklist gets filled in afterwards to look complete, which is a record of nothing |
| Projects as a real feature | A kitchen is one job, one crew, one date; the container would hold exactly one task most of the time. The nullable column is a bounded purchase, and nothing else is — see the `project_id` section above |
| Recurrence | The weekly things live in a diary and always have. What a generator and a series-edit policy would cost the model is in `02-domain.md` |
| Reminders and escalation on `due_at` | Nothing chases a date; a person opening a filter for this week does, every Monday, in front of everyone. The tell that a reminder has earned its place is a missed date that a filter was open in front of |
| Email → task, reply-by-email | The largest single piece of work in a helpdesk: parsing, threading, address mapping and a new class of spam. Every situation in `09-reference-scenario.md` starts with a phone call or a person on the floor, not an email |
| SLA | There is no promise to breach. `first_responded_at` is recorded because it is unrecoverable later, and it is read by nothing (`02-domain.md`) |
| Reports and analytics | Fifteen people. The owner knows how the month went because she spent half of it in the van. A count nobody would act on differently is a screen nobody opens twice |
| Custom fields | Four dictionaries cover what fifteen people sort by, and nobody has named a fifth axis. What the schemaless bag would cost the model is in `02-domain.md` |
| Automation rules, canned responses | Configurability with no evidence behind it. The one automatic rule that exists — reopen on the requester's comment — is fixed inside `PostComment` precisely so that it is not the first row of a rules engine |
| Teams / departments | A second visibility axis for a firm where `own` and `all` already separate a fitter from a manager |
| Merge, split, linked tasks | Volume-driven features. A busy week is twenty-five items; a duplicate is a comment saying "same as #128" and one of the two closed |
| CSAT surveys | A household that has just had a drawer front adjusted does not want a survey, and fifteen people could not act on the score if they had it |
| Billing, subscriptions, self-serve signup | Nobody pays for this and nobody signs up for it. Tenants are created by a console command |
| 2FA, SSO | Not asked for by anyone. Auth is built so either can be added without pain |
| In-app notification centre | Email is enough, and it is already switchable per user. This is polish |
| **The super-admin panel** | Cut 2026-09-18. Once tenant creation became a console command there was nothing left for it to do. The boolean on the user record survives as the gate any future cross-tenant surface would read (`02-domain.md`) |
| **Subdomain routing** | Deferred 2026-09-18. The tenant resolves from the user's active membership, not from the host: no wildcard DNS, no hosts-file entries, no reserved host, no per-host session rule. Re-addable the day a second tenant is real |
| **Deployment, CD and every paid service** | Unscheduled 2026-09-18. The reason is not technical; see **Timeline** |
| Marketing site | Not a product problem |

Model-level cuts — things that would be a column, a table or a relation rather than a
feature — are listed in `02-domain.md` and are not repeated here: a client company on the
task, tags, and parent/child links. Where a cut appears in both lists, such as custom
fields or merge, `02-domain.md` owns the schema argument and this page owns the razor.

## Cut from the MVP on 2026-09-18

Recorded rather than silently dropped, because each of these was on the list above until
that date and each was removed for a reason that should outlive it.

| Was in the MVP | Now | Why |
|---|---|---|
| The hand-built reporter portal — report a problem, my reports, one report | Deleted | One surface for everyone. Three screens for one audience were three screens to design, translate, test and keep in step with the workspace, and the split audience that justified them no longer exists (`03-architecture.md`) |
| The super-admin panel | Deleted | `php artisan tenant:create` does the only thing it did |
| One-command deploy | Unscheduled | No host, no target, nothing to deploy to. See **Timeline** |
| `Ticket` as the entity | `Task` | The domain was too narrow. A ticket is a reported fault; this system runs a small firm's work — fixes, bugs, requests, purchases, deadlines — and one entity covers all of it because the only thing that differs is a word (`02-domain.md`) |

## Later, in rough order of likelihood

Each with the thing that would trigger it. None is scheduled, none has a stub, and none has
a config flag waiting for it — that is `01-principles.md` §1 applied to execution.

1. **Projects, or whatever the container turns out to be.** The seam is already paid for.
   Triggered by a week in which one customer job genuinely holds several tasks that people
   keep cross-referencing by number
2. **A calendar or week view.** Dates already exist and nothing renders them together yet.
   Cheapest of the lot, because it is a read over `due_at` rather than a new entity — which
   is also why it must not arrive as a second place where dates are *kept*
3. **Reminders on a due date.** Triggered by a missed date that a filter was open in front
   of, which is the only evidence that the filter was not enough
4. **Checklists.** Triggered by a repeatable job whose steps are actually forgotten, not
   one whose steps everybody knows
5. **Reports.** Once there are months of data and a number somebody would act on
   differently
6. **Email intake.** The day somebody forwards a request instead of filing one. This
   receded with the pivot: the product is now used by people inside one firm, most of whom
   are not at a desk and none of whom email each other about the work
7. **SLA.** If anyone ever promises a response time to anyone else. It receded for the same
   reason — there is nobody to promise it to
8. **2FA.** The first time a security policy requires it

## Sequencing intent

Unchanged in substance by the pivot. The order is constrained by one fact: the four
foundations are cheapest at the beginning and most expensive at any other time.

So the first stretch of work is skeleton, not features — tenancy, identity, permissions,
i18n, the Action layer. A boring application that does almost nothing, but does it
correctly for two tenants in two languages. Features are fast once that exists and
impossible to add cleanly if it does not, which is why the foundations keep their exemption
even now that no user is waiting for them.

The step-by-step plan is `06-build-plan.md`.

## Timeline

**No deadline.** There is no client, no user and nobody waiting. This is a free-time
project whose driver is building production-grade software properly, so the schedule bends
and the scope does not grow to fill it.

**The absence of a paid host is deliberate, and it is what keeps that true.** A monthly
bill on a free-time project converts "no deadline" into a deadline: something is running,
something is being paid for every month, and the work acquires a reason to hurry that has
nothing to do with the work. Deployment is therefore unscheduled rather than late, and
`06-build-plan.md` records the milestones deleted for that reason with their dates.

What the skeleton still owes a deployment that may never be scheduled — configuration read
from the environment, the schema living in migrations, a `/up` route, nothing that assumes
the application runs on a laptop — is owed because it is free now and expensive later, not
because a date exists. That is the whole of the concession; there is no stub, no CD
configuration and no placeholder for the rest of it.
