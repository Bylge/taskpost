# 00 — Overview

> Living document. Facts about how small firms actually work are folded in as they are
> learned; nothing here is final. What it may never contain is money, legal exposure, or a
> named firm or person — that material lives in `docs/private/`, which is gitignored, and a
> public doc may point at it and nothing more.

## What TaskPost is

A multi-tenant system for running a small firm's work. One place where anything that has to
get done is written down, picked up, worked and closed: a fix, a bug, a task, a request, a
deadline.

**Not a helpdesk and not a bug tracker.** Both are shapes this could have taken and both
were rejected on 2026-09-17, for the same reason: each names the work before the firm does.
A fifteen-person joinery's week contains a broken tail-lift, a box of hinges running out, a
fitting date the customer cannot move, and a drawer front that catches two weeks after the
install. No product built around *incidents* or around *defects* holds all four without at
least one of them being filed somewhere it does not belong — and the moment one kind of work
lives outside the system, the chat group is back, because that is where the rest of it went.

So there is one entity — `Task` — and a tenant-configurable `type` dictionary that supplies
the firm's own word for each kind. The word is decorative on purpose: no rule reads it, so a
joinery may call a row `Poprawka` and a studio may call one `Revision` without either being
wrong (`02-domain.md`).

## The problem it addresses

The problem is not "this firm lacks a system". It is **work scattered across a chat group,
phone calls and personal inboxes**, and the failure mode is undramatic: nothing is lost
loudly. Things are remembered by one person, who is on holiday.

Three properties scattering destroys, in the order they hurt:

| Lost | What it looks like |
|---|---|
| **Memory** | A decision made in a chat group scrolls off the screen in a day and is unfindable in a week. The postponed thing — the part on three weeks' lead time — is the one that vanishes completely, because nothing nags and everybody assumes somebody ordered it |
| **State** | Nobody can answer "where is that" without ringing somebody. The answer exists, in one head, currently in a van |
| **Ownership** | Work mentioned to three people is work assigned to none. Work mentioned to one person is work that stops when they are away |

The claim is deliberately narrow: TaskPost replaces none of those channels for **talking**,
only for **remembering**. The reference firm in `09-reference-scenario.md` keeps its
messenger, which is good at "are you five minutes away" and hopeless at everything above.

## Where it came from

Two lineages, neither of them a codebase to port.

**V1 was a school project** — Laravel, Blade, Livewire, single-tenant, hardcoded
Admin/Agent/Client roles. It worked, and it was never built to be run in production. It
survives as a reference for domain decisions only: which statuses existed, which fields
mattered, which turned out to be useless. No line of it is carried forward.

**An earlier iteration of this project** planned the same multi-tenant system around a
single, narrower noun: a *reported fault*. Everything a firm does was expected to arrive as
somebody reporting that something was wrong. That was widened on **2026-09-17**, because the
noun does not survive contact with a real week — a supplier order, a fitting date and a
customer's change of mind are none of them faults, and a firm forced to pretend they are
goes back to the chat group for the rest of its work. The domain, the surfaces and the name
all changed with it.

What did **not** change is worth naming, because it is most of the value: the layering, the
tenancy model, the internationalisation decision, the permission model and the reasoning
behind each survived the pivot untouched. This was a rewrite of documents, not of thinking.

## Who it is for

Firms of up to roughly **thirty people** — one office, one workshop or one site, everybody
within shouting distance of everybody else. The razor is written against fifteen
(`01-principles.md` §1), and the reference firm is fifteen, because that is where the
product's assumptions are most clearly true: a busy week is perhaps twenty-five new tasks
and a quiet one is eight, which is a volume a human reading a filtered list handles
correctly, and which makes most automation machinery built ahead of its problem.

A material fact about the target, and a design constraint rather than a detail: **the
majority of the people are not at a desk.** In the reference firm, eleven of fifteen are at
a machine, in somebody's flat, or in a van. A shared written record is worth something to them
precisely because they cannot be reached by walking over.

## Competitive position

In the owner's framing: **a tool for small firms where Slack, Jira and Teams are just too
big.** Each of the three is excellent and each is aimed past this firm.

| What they reach for instead | Why it does not fit fifteen people |
|---|---|
| **Slack**, or any messenger | Superb at talking and structurally incapable of remembering. Search finds a message, not a state. There is no answer to "what is open" because nothing is open |
| **Jira** | Priced and designed for teams that have a backlog, a workflow designer and somebody whose job includes administering it. The configuration costs more attention than the work being tracked |
| **Teams** | Arrives with an administration surface that assumes an IT department, and is bought for the chat anyway, which returns the firm to the row above |

The wedge is **small, cheap, and set up by the firm itself** — an office manager configuring
statuses, roles and people in twenty minutes with no developer present, which is an exit
condition of M7 (`06-build-plan.md`), not an aspiration. Feature parity with any of the three
is neither achievable nor desirable, and `01-principles.md` §1 exists to make drifting
toward it uncomfortable.

## Goals

Rewritten honestly on 2026-09-17, in priority order. The previous ordering assumed an
audience the project no longer has, and pretending otherwise would corrupt every scope
decision downstream.

1. **Learning to build production-grade software.** This is the driver. Not a working demo —
   tenancy that holds under a test designed to break it, a domain layer that outlives its
   interface, translations enforced by CI, migrations that could be run against real data.
   The point is that the engineering is real even though the users are not.
2. **A portfolio piece** that demonstrates production engineering rather than coursework.
   It is a consequence of goal 1 done properly, not a separate programme of work.
3. **Income, opportunistically.** If somebody eventually pays for it, good. Nothing is
   planned around it, no decision is taken to make it likelier, and the commercial questions
   that would have to be answered first stay in `docs/private/`, unanswered and unpushed.

Two consequences, both recorded rather than discovered later:

- **There is no deadline**, which is the condition this project runs under and the reason
  deployment is unscheduled (`01-principles.md` §9). Deployment is kept *possible* — config
  from the environment, the schema in migrations, a `/up` route, nothing assuming a laptop —
  because each of those is free now and expensive later. It is not *scheduled*, and no paid
  service of any kind is bought.
- **There is no user to be wrong in front of.** That was the instrument that would have
  caught a bad feature, and it is gone. `09-reference-scenario.md` replaces it with an
  invented firm, which is a weaker instrument for a reason it states in its own second
  section, and `05-open-questions.md` carries that as the project's top risk.

## Non-goals

- Feature parity with Slack, Jira, Teams or any established work-tracking product
- Self-serve SaaS signup, billing, subscriptions
- A marketing site, content, SEO
- Anything that requires the author to do sales
- A deployment on somebody else's server before there is a reason for one
- Chat, channels, direct messages. Discussion is task-scoped only; competing with a
  messenger on messaging is the one fight this product does not pick (`02-domain.md`)

## Who is building it

One developer, in free time, holding full technical responsibility and every decision.

That is a design constraint, not a footnote. There is no second reviewer, no colleague who
notices a shortcut, and no deadline forcing a step to be closed — so the discipline is
written down instead, in `CLAUDE.md` and `07-conventions.md`, and "done" is defined
mechanically so it is never a judgement call made by the person who wants to move on.

## Definition of "it worked"

**`09-reference-scenario.md` is the definition.** Read it before arguing about scope; it is
the only document in the set that can answer "do we need this" without an opinion.

It describes one invented fifteen-person joinery — Listwa — and the week it has to get
through: a tail-lift that fails on the ring road, hinges running low, a kitchen that must be
fitted before the customer moves in, a drawer front that catches, an extraction filter
waiting three weeks on a part, and a change of mind the customer withdraws. It worked when
the eight conditions in that file's *What done means* hold **at once**, not individually.
The load-bearing ones:

- Each of its six situations is carried out by the person named, with the permissions and
  visibility scope listed, through the workspace, with no developer present and no seeder.
- The office manager builds the whole tenant configuration herself — statuses mapped to
  fixed types, priorities, categories, types, roles, and six people with their scopes.
- A fitter files from a phone on a bad connection, attaches a photograph, and comments.
- A showroom assistant holding visibility scope `all` and no `task.note` **cannot retrieve
  an internal note**, proven by a test at query level rather than by looking at the page.
- The same lifecycle is drivable through `/api/v1` with `curl`, producing rows
  indistinguishable from the ones the workspace produces.
- The whole thing renders in `en` and `pl` with no literal strings, and the tenant's own
  dictionary names are never translated.

They are bulleted rather than numbered deliberately: this is a subset, and numbering it
would invite a reader to cite "condition 5" here and land on a different one there.

Anything beyond that list is a feature and gets no protection from the razor. Anything on it
that fails is not an improvement to argue about later — it is the MVP not being done.

**And the honest caveat**, which belongs here rather than only in the file it concerns: a
written scenario is a weaker instrument than a firm using the thing. It was invented by the
person who will build the system, so every need in it is a need the author already believed
in. It cannot be strengthened by editing; it can only be replaced, by somebody real.

## Removed on 2026-09-18

Two positions this document used to hold were real decisions with reasons. They are recorded
rather than dropped silently, because a reader who remembers them needs to know they were
retired deliberately and what replaced them.

**The cross-company desk.** Planning carried two shapes of the same product: an internal
desk, where whoever files the work and whoever does it belong to the same firm, and a
cross-company one, where a firm runs a desk for other companies' staff. The second — recorded
during planning as *Case B* — is cut. It was never built, and the route by which a second
company would have arrived closed with the 2026-09-17 pivot: with nobody on the other side of
such a desk there is no workflow to model, and nothing to check a design against. Returning
it later is a nullable client-company column on a task, which `02-domain.md` records as
cheap; the table, the picker and the duplicate-merging screen behind it are what would make
it a feature rather than a column, and none of that is bought today.

**"It worked = daily use by a real firm for three months."** Gone because its subject is
gone, not because a written scenario is better. It was the stronger instrument of the two,
and saying so is the point of recording it: `09-reference-scenario.md` is a substitute, and
`05-open-questions.md` holds the risk that it flatters the author rather than testing him.
