# 02 — Domain Model

> This file is the vocabulary source for the whole doc set. Every other document takes its
> nouns from here; where another doc disagrees about a name, a field or a rule, this one is
> right and the other is a bug.

## Entity map

```
Tenant (company)
 ├─ Membership (user ↔ tenant)              → role, visibility scope
 ├─ Role (tenant-defined)                   → set of permissions
 ├─ Status / Priority / Category / Type     → tenant-configurable dictionaries
 └─ Task
     ├─ Comment      (commentable)          → public comment | internal note
     ├─ Attachment   (attachable)
     └─ Activity     (trackable)            → history / audit trail

User (global identity, may belong to several tenants)
Permission (fixed catalogue, defined in code, not in DB rows the client edits)
```

The three names in brackets are morph names, not decoration: `Comment`, `Attachment` and
`Activity` attach polymorphically, and `Task` is simply the only thing they attach to today.

## Tenant

A company. The unit of isolation. Every tenant-owned table carries `tenant_id`, enforced by
a global scope — see `03-architecture.md`.

Tenants are created by `php artisan tenant:create`, which takes its arguments explicitly.
There is no screen for it, because the only person who would use one is the author.

The super admin (project owner) exists **outside** any tenant and is not a role within one.

## User and Membership

`User` is a global identity: email, password, name, locale, timezone.

`Membership` binds a user to a tenant and carries everything tenant-specific:

- `role_id` — **exactly one role**, not a set
- visibility scope
- active/inactive

One role per membership. Union-of-roles is configurability nobody has asked for, and the
pivot table is an additive migration on the day someone does.

An inactive membership resolves nothing: no Action may act on a tenant the actor holds no
**active** membership in, and that is an exit condition of M2 (`06-build-plan.md`), not a
courtesy check in a controller.

A user can belong to more than one tenant. This costs nothing now and avoids an ugly
migration later.

**The super admin is a boolean on the user record, not a membership.** The panel it used to
gate was cut on 2026-09-18 — there was nothing for it to do once tenant creation became a
console command — so today the boolean is read by nothing. It survives because it is the
gate any future cross-tenant surface would read, and because deleting a column to add it
back later is the more expensive order of operations. It never appears in tenant
authorization: that is what `03-architecture.md` means by no `if ($user->is_admin)`.

## Roles and permissions

**Permissions** are a fixed catalogue defined in code — a PHP enum, never rows a client can
edit (`03-architecture.md`). Deliberately short, because an office manager has to understand
the whole of it in one sitting:

| Permission | Meaning |
|---|---|
| `task.create` | File a task |
| `task.comment` | Comment on a task |
| `task.note` | **Write *and read* internal notes** |
| `task.take` | Assign a task to yourself |
| `task.assign` | Assign a task to someone else |
| `task.edit` | Change status, priority, category, type, due date, subject and body |
| `task.delete` | Remove a task |
| `settings.manage` | Dictionaries, tenant settings |
| `users.manage` | Invite users, assign roles, create roles |

`task.note` is the one permission that gates a *read* as well as a write, and it is
deliberate: it is the only sentence in this model that decides who may see something, and
concentrating it in one enum case is what makes it testable. Everything else about what you
may see is answered by visibility scope.

There is **no `task.view`**. Merging "what you may do" with "what you may see" is the
mistake this model exists to avoid: the moment they are one axis, every permission has to be
minted twice — once for your own rows and once for everyone's — and the catalogue that an
office manager was supposed to understand doubles.

**Roles** are tenant-defined bundles of those permissions. The client creates, renames and
deletes them freely, with one guard: a tenant must always retain at least one active
membership holding `users.manage`, or it locks itself out of its own account. Three ship as
defaults on tenant creation:

| Default role | Permissions |
|---|---|
| **Member** | `task.create`, `task.comment` |
| **Agent** | Member + `task.note`, `task.take`, `task.edit` |
| **Admin** | Everything |

These are a starting point, not a fixture. Listwa's workshop lead holds `task.assign` on top
of Agent (`09-reference-scenario.md`), which is not an exception to the defaults — it is a
tenant editing its own role, which is the entire reason roles are rows.

## Visibility scope

A **separate axis** from permissions, stored on the membership.

- `own` — only tasks I filed or am assigned to
- `all` — every task in the tenant

(`team` is deliberately absent — see `04-scope.md`.)

Since there is one surface for everyone (`03-architecture.md`), **scope is the axis that
separates a junior from a manager.** It carries weight that a permission used to carry, and
it is the first thing to get right when a membership is created.

Scope and `task.note` are the only two things in the system that filter a read, and they
filter different objects: scope decides which **tasks** the query returns, `task.note`
decides which **comments** on a visible task the query returns. Nothing else narrows a
result set, and no third mechanism may be added quietly.

`09-reference-scenario.md` carries the case that proves the axes are separate: a showroom
assistant with scope `all` and nothing but `task.create` and `task.comment`. She sees every
task in the firm because a customer on the phone asks about work she did not order; she may
still not assign one, change its status or set a date. One axis cannot express her.

## Task

| Field | Notes |
|---|---|
| `tenant_id` | Always, `NOT NULL` |
| `number` | Human-readable per-tenant sequence (`#128`), not the DB id |
| `requester_id` | User who filed it |
| `assignee_id` | Nullable |
| `status_id` | FK to the tenant's status dictionary |
| `priority_id` | FK to the tenant's priority dictionary |
| `category_id` | Nullable FK |
| `type_id` | Nullable FK |
| `project_id` | Nullable. **A seam with no table behind it** — see below |
| `subject`, `body` | Free text, tenant data, never translated |
| `due_at` | Nullable |
| timestamps | `created_at`, `updated_at`, `first_responded_at`, `closed_at` |

One entity covers a broken tail-lift, a box of hinges, a fitting date and a customer
complaint. Splitting those into separate entities was considered and rejected: they share
every field, every permission and every lifecycle, and the only thing that differs is a word
the firm uses for them. That word is `type_id`.

### Numbering

`tenants.next_task_number`, incremented under `SELECT … FOR UPDATE` inside the same
transaction as the insert, with a unique constraint on `(tenant_id, number)` as the backstop.
Not `max(number) + 1` — two people filing at once is not a rare event in a system whose whole
pitch is that everyone files in one place, and the failure it produces is two tasks sharing a
number, which is the one identifier the firm says out loud on the phone.

### `due_at`

A real, settable, filterable field from the first slice. Dates are one of the three cheap
seams (`04-scope.md`): adding a date column to a populated table is a migration plus a
backfill conversation, and adding it to an empty one is a line.

**Nothing chases it.** No reminder is sent, no escalation fires, no job scans for overdue
rows — none of that exists, and its absence is deliberate rather than pending. What catches a
date is a person opening a filter for tasks due this week. The tell that a reminder has
earned its place is a missed date that a filter was open in front of; until then it would be
machinery built ahead of the problem it solves.

### `project_id` — a purchased seam, not a feature

A nullable column. **No referenced table, no foreign key, no `Project` model, no relation, no
UI, no Action, no factory state, no seeder row.** Nothing reads it and nothing writes it.

This is a dated departure from "no speculative structure" (`01-principles.md` §1),
taken 2026-09-18, and the departure is bounded precisely because a column is the only form
the seam takes. What it costs: one nullable `bigint` on a table that has no rows yet, which is
nothing. What it buys: if a container — a project, a job, a kitchen — ever becomes real, it
arrives as an `UPDATE` over existing tasks rather than as a schema change to a populated
`tasks` table in a system people are using.

The line to hold: the day `project_id` acquires a model it also acquires a foreign key, a
`tenant_id` of its own and an isolation test. Anything short of all three is a half-built
feature, which is worse than either state.

### `first_responded_at`

Set once, by `PostComment`, on the first **public comment** authored by someone who is not
the requester. Internal notes never set it. It has no consumer today; it is recorded now
because it is unrecoverable later, and it is the one number any future conversation about
response times starts from.

`closed_at` is recorded on the same reasoning: the moment a task reached `done` or
`cancelled` is not reconstructable from the timeline once the timeline is trimmed. It is set
by `ChangeStatus` on entry to either status type and cleared on leaving one, including by
the reopen rule inside `PostComment`.

## What attaches to a task

`Comment`, `Attachment` and `Activity` attach **polymorphically** (`commentable`,
`attachable`, `trackable`), and each still carries its own `tenant_id NOT NULL`.

The reason is the second of the three cheap seams: if a container ever exists, it inherits
comments, files and history for free, with no data migration and no second set of tables that
drift apart. Polymorphism bought for a hypothetical is usually a mistake; this one is cheap
because it is chosen before any row exists, and because the alternative — `task_id` columns
everywhere — is the version that costs a migration on live data.

The costs, recorded so nobody is surprised by them: there is no database-level foreign key on
the morph side, so `tenant_id NOT NULL` plus the global scope is the whole integrity story,
and the morph map is pinned to short string aliases in a service provider rather than to class
names, so renaming a class never rewrites what history says happened.

### Comment

Two kinds, distinguished by `is_internal`:

- **Public comment** — visible to anyone who can see the task
- **Internal note** — visible only to holders of `task.note`

**This boundary is the single most important correctness property in the system.** It is
enforced at the **query level, never in a view.** A note filtered by a Blade conditional has
still been loaded, still sits in the Livewire payload on the page, and is still returned by
`/api/v1` where there is no view at all. The test that proves it asserts on what the query
returns for a membership without `task.note`, and it is one of the four mandatory test
categories (`03-architecture.md`).

### Attachment

A file on a task: photograph of a broken part, a supplier quotation, a measurement. Storage,
MIME validation and authorized delivery are `03-architecture.md`'s problem; the model's only
contribution is that an attachment belongs to a tenant and hangs off an `attachable`.

### Activity

Append-only history. Renders as the timeline on the task page and doubles as the audit trail.
`ActivityType` is a fixed enum, backed by string, and its cases are the whole of it:

| Case | Written by | `old_value` / `new_value` |
|---|---|---|
| `created` | `CreateTask` | Both `null` — there is no prior state |
| `status_changed` | `ChangeStatus`, and the reopen rule inside `PostComment` | The status ids, as strings |
| `assignee_changed` | `TakeTask`, `AssignTask` | The user ids, as strings; `old_value` is `null` on first assignment |
| `priority_changed` | `UpdateTask` | The priority ids, as strings |
| `category_changed` | `UpdateTask` | The category ids, as strings; either side is `null`, the column being nullable |
| `type_changed` | `UpdateTask` | The type ids, as strings; either side is `null`, the column being nullable |
| `subject_changed` | `UpdateTask` | The old and the new subject, which are short enough to keep |
| `body_changed` | `UpdateTask` | Both `null`. A body is too long for an audit column, and the timeline's job is to say that it moved and who moved it, not to hold a diff |
| `due_date_set` | `SetDueDate` | ISO-8601 timestamps; either side is `null` when the date is being set or cleared |
| `comment_added` | `PostComment` | Both `null` — the comment row carries the content |

`old_value` and `new_value` are **nullable** text columns. Three cases populate neither, and
that is the normal state of the column rather than a gap to be filled later with a summary
string: the timeline reads a `created`, `body_changed` or `comment_added` row for its type and
its actor alone.

Kept deliberately simple: actor, activity type, old value, new value, timestamp. **Not a
generic event-sourcing system** — the timeline records what happened, it is not the source
the current state is rebuilt from. Each row is written by the Action that caused it, never by
a model observer, for the reason given in `03-architecture.md`: an observer cannot see who
did it, and the actor is half the value of a timeline.

Named `Activity` rather than `Event` because `Event` collides both with Laravel's own event
system and with the "event planning" sense the word would carry if a calendar is ever built.

## Status, Priority, Category, Type

Four tenant-configurable dictionaries, identical in shape: name, colour, sort order, active
flag. The names are tenant data in whatever language the client typed, and the system never
translates them (`01-principles.md` §8).

### Fixed status types

Every **status** additionally carries a fixed `type` the client cannot change:

`new` · `open` · `waiting` · `done` · `cancelled`

A tenant may create "Czeka na części" and map it to `waiting`. The UI shows their name; every
piece of logic in the system reads the type. This is `01-principles.md` §5 in its original
form: free-form configuration on top, stable meaning below.

`waiting` earns its place separately from `open`: the postponed thing is the one a small firm
actually loses, and without a type for it a blocked task is either permanently nagging or
quietly closed.

### `Type` is decorative, and that is an exception

`Type` (bug, fix, request, deadline, purchase, …) carries **no fixed-semantics layer**. There
are no internal type constants, and **no rule anywhere reads `type_id`** — it exists for
filtering and display.

That is a deliberate exception to `01-principles.md` §5, taken 2026-09-18. The fixed layer
exists so the system can reason about a value; nothing reasons about this one, so the layer
would be ceremony that every tenant pays for at setup time — mapping their own words onto our
words for no benefit. It is also what lets a joinery call a row `Poprawka` and a studio call
one `Revision` without either of them being wrong.

Adding the layer later is **an additive nullable column plus a mapping pass over a handful of
rows per tenant**, which is cheap precisely because dictionaries are small. The tell that the
day has come is the first rule that wants to branch on type.

### Lifecycle of a dictionary row

Applies to all four. Hard delete is allowed only while nothing references the row. Once a task
has used it, the only operation is `active = false`: it disappears from every picker, and
existing tasks keep it. A task may never point at a dictionary row that no longer exists, and
history may never be rewritten to say something that did not happen.

### Defaults

Each tenant flags exactly one status `is_default` (type `new`) and one `is_default_open`
(type `open`). New tasks get the first. The second exists for the rule below. Neither may be
deleted or deactivated while flagged.

### Reopening

A public comment from the requester on a task whose status type is `done` moves it to the
tenant's `is_default_open` status. This is a fixed rule inside `PostComment`, not the first
automation rule and not a configurable one — "somebody answered and nobody noticed" is a
failure this product exists to prevent, and leaving it to a human to spot reintroduces it
exactly where it hurts.

Status type `cancelled` does **not** reopen. The difference between *finished* and
*abandoned* is worth modelling, and a comment on an abandoned task is a record, not a
reopening (`09-reference-scenario.md`, situations 4 and 6).

## Actions over this model

The contract every Action obeys — authorize first and throw, readonly DTO past two inputs,
one transaction, writes its own `Activity`, notifies after commit, returns the model — is
`03-architecture.md`'s. What belongs here is which Actions exist and what each one is allowed
to touch. They live under `App\Actions\Task\`.

| Action | Permission | What it does to the model |
|---|---|---|
| `CreateTask` | `task.create` | Allocates `number`, sets the `is_default` status |
| `PostComment` | `task.comment`; `task.note` for an internal note | May set `first_responded_at`; applies the reopen rule, which clears `closed_at` |
| `TakeTask` | `task.take` | Sets `assignee_id` to the actor |
| `AssignTask` | `task.assign` | Sets `assignee_id` to someone else |
| `ChangeStatus` | `task.edit` | Moves `status_id`; sets `closed_at` on entry to a `done` or `cancelled` status and clears it on leaving one |
| `SetDueDate` | `task.edit` | Sets or clears `due_at` |
| `UpdateTask` | `task.edit` | Sets `priority_id`, `category_id`, `type_id`, `subject`, `body`. Never `status_id`, `assignee_id`, `due_at` or `number` — those have their own Actions. One `Activity` row per field actually changed, none for a field submitted unchanged |

Configuration — dictionaries, roles, memberships — gets its own Actions at M7
(`06-build-plan.md`). There is no `DeleteTask` Action listed above, and there is no task
deletion anywhere in this plan. `task.delete` exists for an Admin clearing a mistake, which
is not part of the lifecycle and which no milestone builds: the enum case ships at M3 read
by nothing, exactly like the super-admin boolean under **User and Membership**, and the
operation behind it sits on `06-build-plan.md`'s unscheduled list. Until that entry leaves
the list, nothing in the workspace and nothing under `/api/v1` destroys a task. When it
does, it arrives as a `DeleteTask` Action in the table above, with soft versus hard decided
out loud — the dictionary lifecycle rule's "history may never be rewritten" points at soft.
`UpdateTask` is the one Action that may change several fields in a call, because the form it
backs is one form; it still writes one `Activity` per changed field, so the timeline says
which field moved rather than that "the task was edited".

## Notification preferences

Per-user, per-tenant, minimal: an on/off switch for email on the events that matter —
assigned to me, new comment on a task I filed. Anything more granular is a later feature and
has to argue for itself.

## Removed on 2026-09-18

Recorded rather than silently dropped, because each was a decision with a reason and the
reason changed:

| Removed | Why |
|---|---|
| `workspace.access` | It existed only to choose between two interfaces. There is one surface now, so the permission had no question left to answer. Visibility scope inherited its job |
| The shell rule | The rule that said `workspace.access` decided which interface a user landed in. Deleted with the second interface |
| The **Reporter** default role | Renamed **Member** and reduced to `task.create` + `task.comment`. The word described a portal that no longer exists |
| The super-admin panel | Cut; the boolean on the user record survives, see **User and Membership** above |
| "Internal notes are visible to `workspace.access`" | Replaced by "visible to holders of `task.note`". The property and its test are unchanged; only the gate moved |

## Deliberately not in the model

The feature-level cuts and the firm that justifies them are in `09-reference-scenario.md`.
These are the *model-level* ones — things that would be columns, tables or relations, and are
not:

| Not modelled | Why |
|---|---|
| **A client company on the task** | The reference firm's customers are households, one job at a time, and what anyone searches by is a surname in `subject`. A nullable FK later is cheap; the table, the picker and the duplicate-merging screen that follow it are not |
| **Custom fields** | Four dictionaries cover what fifteen people sort by. Custom fields mean a schemaless bag, a form builder and a query the database cannot plan — paid in advance for a fifth axis nobody has named |
| **Tags** | Category and type already classify. A third, free-form axis becomes forty synonyms in a month, after which no filter over it is trustworthy and nobody can say when it stopped being |
| **Parent/child links** | Twenty-five items in a busy week do not form trees. A subtask is a comment, or it is a second task, and both are readable by someone who was not in the room |
| **Merge** | At this volume a duplicate is a comment saying "same as #128" and one of them closed. Merge means rewriting history so that a thing that happened on one task appears to have happened on another, which the dictionary lifecycle rule already forbids everywhere else |
| **Recurrence** | A recurring task is a generator, a policy for what happens when the series is edited, and a decision about the instance somebody already completed. The weekly things live in a diary and always have |
| **Chat, channels, direct messages** | Discussion is task-scoped only. The firm has a messenger and it works for "are you five minutes away"; what it fails at is memory, which is what comments on a task are for. Competing with a messenger on messaging is the one fight this product does not pick |

Each of these is defensible in general. None of them is defensible against
`09-reference-scenario.md`, and that gap is the whole method.
