# 01 — Principles

These are the rules that settle arguments. When a decision is unclear, the answer is
whichever option satisfies more of these. Where a principle has been departed from, the
departure is written into the principle itself with its date and its cost — never removed,
because a rule that quietly stopped applying is worse than one that never existed.

## 1. The razor

If a feature can be removed and the system still does its job, remove it. Ship the most
bare-bones version that works. Features are added when someone actually needs them, not
because they seem obviously useful.

The test to apply:

> **Would a fifteen-person firm notice if this were missing?** If no, cut it. If Jira has it
> and they would not miss it, that is evidence *against* building it, not for.

**The edge was replaced on 2026-09-18**, and the replacement is not cosmetic. The razor used
to cut against what a specific firm did not need — a real one, whose involvement ended with
the 2026-09-17 pivot (`00-overview.md`). An appeal to an absent authority settles nothing,
and a razor that cannot be applied stops being applied, which is how a product acquires a
workflow designer. So the firm is written down instead: `09-reference-scenario.md` describes
one invented fifteen-person joinery whose week the system must run end to end, and a proposed
feature is checked against **that week**, not against whether it is generally a good idea.
Every feature in Jira was once generally a good idea.

The substitution is weaker than what it replaces, because an invented firm agrees with its
author and an observed one surprises him. That is recorded as the top risk in
`05-open-questions.md` rather than papered over, and it comes with one hard rule: a situation
added to the scenario *during* an argument about a feature is not evidence.

**Corollary — correct over complete.** Features are built to work *correctly*, not to be
finished. A working simple version beats a half-finished sophisticated one.

**Corollary — no speculative structure.** The razor's execution form: no stub, no
placeholder file, no abstraction for a step that has not started, no config for a feature
that is out of scope. Building ahead of need is the same mistake as building what is not
needed, discovered later and costing more. The deliberate exceptions — the three cheap seams
in `04-scope.md` — are dated and bounded precisely because they are exceptions.

The four foundations in §2 are exempt from all of this.

## 2. Foundations are exempt from the razor

Four things cannot be retrofitted cheaply. They are built properly from day one
regardless of cost:

- **Multi-tenancy** — retrofitting tenant scoping means auditing every query in the app
- **Translation keys (i18n)** — retrofitting means touching every view and every string
- **The action layer** — retrofitting means untangling logic from three UI frameworks
- **The permission model** — retrofitting means rewriting every authorization check

Everything else is a feature and gets no such protection.

## 3. One source of truth for business rules

Every operation that changes state lives in exactly one Action class. Filament resources,
Livewire components and API controllers are entry points: they validate input, call the
Action, and render the result. They never contain rules.

This is what makes the API real rather than decorative, and it is what makes the UI
replaceable. It is also the only reason §7 is affordable.

## 4. Two doors, one room

The UI talks to the domain directly. The API talks to the domain directly. The UI never
routes through the API. They are parallel entry points to the same Actions, and neither
depends on the other.

## 5. Configurable on top of fixed semantics

Clients define their own roles, statuses, priorities and categories. But every
client-defined status maps onto a fixed internal type (`new`, `open`, `waiting`, `done`,
`cancelled`), and every client-defined role is a bundle of permissions from a fixed
catalogue.

Without the fixed layer underneath, nothing in the system can reason about tasks — no
counters, no filters, no future SLA, no reports. Free-form configuration on top, stable
meaning below.

### The `Type` dictionary carries no fixed layer

`Type` — the fourth dictionary, holding a firm's own word for a kind of work — has no
internal constants, and **no rule anywhere reads `type_id`**. It filters and it displays.
`02-domain.md` records this as an exception to this principle; it is more usefully read as
the principle stated precisely.

The fixed layer exists so that **logic** can reason about a value. Status has one because
`ChangeStatus`, the reopen rule, the default-status flags and every list filter branch on
the type. Nothing branches on type. A fixed layer beneath it would therefore be ceremony
every tenant pays for at setup time — mapping their own words onto ours in exchange for
nothing — and its absence is what lets a joinery name a row `Poprawka` while a studio names
one `Revision`, neither of them wrong.

Recorded 2026-09-18, with the condition that reverses it: **the first rule that wants to
branch on type.** On that day the layer arrives as an additive nullable column plus a mapping
pass over a handful of rows per tenant, which is cheap exactly because dictionaries are small
(`02-domain.md`).

## 6. Buy the work where the interface is not the product

Filament is used for the whole workspace: list views, filters, CRUD, the task page,
settings, user management, and its own login served at `/`. It is mature, consistent, and
saves an enormous amount of work in precisely the places where a custom design adds nothing.

**Nothing is hand-built.** This principle used to carry an exception: a second, hand-built
surface of about three screens, for non-technical people who opened it twice a month, where
Filament's density and restrictiveness would actively have hurt. That second surface was
deleted on 2026-09-18, and the exception went with it — it was conditional on there being a
group of users with a different relationship to the product, and there is one surface for
everyone in the tenant now (`03-architecture.md`).

What the merge costs, recorded because it is real and did not disappear with the surface:
the infrequent, non-technical, not-at-a-desk user still exists. In
`09-reference-scenario.md` he stands at a shared terminal by the spray booth and needs to
report that something is out or broken **in under a minute**, and a fitter needs to file
from a phone on a bad connection. Filament now has to be good enough for both. That is a
constraint on how the create form is configured, and the scenario is where it is checked —
not a reason to reopen a second surface, which cost two of everything and one more place for
a rule to hide.

## 7. Filament must stay evictable

Filament is a UI dependency, not an architecture. That means: **no business logic in
resources, no domain concept that exists only as a Filament construct, no data model shaped
by what Filament finds convenient.**

**On 2026-09-18 the price changed, not the rule.** With two surfaces, eviction cost "views
and nothing else" — one of the two was hand-built and would have survived. With one surface,
eviction costs **the entire interface**: every screen anybody uses is a Filament resource,
page or form.

The conclusion is the opposite of the intuitive one. A dependency this expensive to remove
is not grounds for relaxing the rule; it is what makes this the most load-bearing of the
ten. If domain logic has leaked into a resource, eviction means rewriting the screens **and**
recovering the rules from them, with no test that ever ran the rule on its own to tell you
what it was. So the domain's tests exercise Actions directly rather than through a panel,
and a rule that can only be reached by driving a form is a bug in this principle, not a
shortcut.

## 8. English in the code, Polish in the interface

All code, identifiers, comments, commits and documentation are English. Polish exists
only as a locale — a translation file. There is no bilingual code.

Client-created content (a status named "W trakcie", a category, a task body) is data,
not translatable text. The system never attempts to translate tenant data.

## 9. Deployment is possible, not scheduled

**This principle previously read "Deployable from day one": one command, from the first
week, with daily updates while a real firm used it. That is false as of 2026-09-18.** It is
rewritten rather than deleted, because it was cited elsewhere and a reader who remembers it
needs to know it was retired on purpose.

The position now: **no host, no VPS, no CD milestone, no paid service of any kind. The
application runs locally.** The reason is recorded because it is a good one, not because it
is convenient: a monthly bill on a free-time project converts "no deadline" into a deadline,
and no deadline is the condition this project exists under (`00-overview.md`). Paying for a
server would reintroduce, at the worst possible point, exactly the pressure the project was
restructured to remove.

What the departure costs — stated so that nobody rediscovers it as a surprise:

- No production environment means no evidence. The first real deploy will find what local
  development never does: migrations against data somebody cares about, queue workers that
  die quietly, a mail provider that rejects, a file store that is not the local disk.
- Nothing is proven under concurrency, a slow connection or a cold cache.
- "It works" means "it works on one machine", which is a weaker claim than the one this
  project is being built in order to be able to make.

What is kept for free, because each is a line now and an audit later. M1 owes all four
(`06-build-plan.md`):

| Kept | Why it cannot wait |
|---|---|
| Configuration comes from the environment | Retrofitting means hunting every literal in the codebase, and missing one |
| The schema lives in migrations | A schema that exists only in one local database cannot be moved anywhere |
| A `/up` route exists | Trivial to add now; the first thing any monitor, proxy or orchestrator asks for |
| Nothing assumes a laptop | An absolute path, a hardcoded host, a synchronous queue standing in for a real one — each is cheap to avoid and expensive to find |

The condition that revives the old principle: **a reason to be reachable by somebody who is
not the author.** Until then deployment sits on the unscheduled list with everything else
waiting for a reason to exist (`06-build-plan.md`), with no stub, no config flag and no
placeholder file standing in for it (§1).

## 10. Documentation is for us

These docs assume a technical reader who has the full context. No simplification, no
tutorials. Non-technical material, if it is ever needed, is a separate artefact written
for a separate purpose.
