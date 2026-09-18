# 09 — Reference Scenario

> Living document. The firm below is fictional. It is revised when a decision proves it
> too thin to settle an argument — never to justify a feature someone already wants.

## What this file is

TaskPost has no client, no user and no deadline. Nothing is being observed, so nothing
can be appealed to when a feature is proposed. This document supplies the missing
opponent: **one invented fifteen-person firm whose week the system must be able to run
end to end.** When someone asks whether to build feature X, the answer is found by
reading this firm's week and checking whether it needs X — not by judging whether X is
generally a good idea, because every feature in Jira was once generally a good idea.

It also defines *done*. The milestone plan in `06-build-plan.md` ends at M9; what M9
being finished actually means is that everything below can be carried out in the system
by the people described, with nothing kept in a phone or a notebook on the side.

## It is invented, and that costs something

**This firm does not exist. Nobody watched it work. It was written on 2026-09-18 by the
author of this system, which means every one of its needs is a need the author already
believed in.** That is a weaker instrument than a workflow someone described from their
own week, and weaker again than one observed directly. A described workflow surprises
you; an invented one agrees with you.

The specific failure to expect: a situation below reads as evidence *for* something that
was going to be built anyway, because it was written by the person who wanted it. The
partial defence is that this file is written first and cited later, never extended to
accommodate a feature under discussion. **A situation added during an argument about a
feature is not evidence, and is to be rejected as such.**

This is recorded as the project's top open risk in `05-open-questions.md`. It is not
resolved here and cannot be — only replaced, by somebody real using the system.

## The firm

**Listwa** makes fitted furniture — kitchens, wardrobes, a run of shelving — for private
households. A customer books a measuring visit, gets a quotation, and some weeks later a
crew arrives and installs. Fifteen people, one workshop unit on an industrial estate
outside a mid-sized city, one small showroom at the front of it. Customers are
households, one job at a time.

| Where they are | Who | Count |
|---|---|---|
| Desk | Owner, office manager, showroom and orders | 3 |
| Workshop floor | Workshop lead, four joiners, one finisher | 6 |
| Out of the office | Two fitting crews of two, one driver | 5 |
| Part time, two days a week | Bookkeeper | 1 |

**Eleven of the fifteen are not at a desk**, and that is the whole reason a written shared
record is worth anything here. The joiners are at machines with dusty hands, the fitters
are in somebody's flat, the driver is in a van. Today the firm runs on a WhatsApp group,
two phone numbers and a whiteboard by the spray booth, and the failure mode is not
dramatic: things are not lost loudly, they are remembered by one person who is on
holiday.

## A normal week

Monday starts with fifteen minutes around the workshop lead's screen: what is due, what
is stuck, what came in over the weekend. Three or four installs run during the week, each
a crew out for one or two days. The showroom takes maybe a dozen customer calls a day, of
which one or two turn into something somebody has to do. The workshop consumes materials
and occasionally breaks. Nothing here is high volume: **a busy week is perhaps twenty-five
new items, and a quiet one is eight.** That number is the reason most of `04-scope.md`'s
deferred list is deferred — at this volume, a human reading a filtered list is not a
workaround, it is the correct amount of machinery.

## The people

Six of the fifteen matter for design decisions. All fictional.

| Name | Role at Listwa | Membership role | Visibility scope |
|---|---|---|---|
| Renata | Owner; measures and quotes, half her week on the road | Admin | `all` |
| Marta | Office manager; orders, suppliers, the van, the diary | Admin | `all` |
| Ola | Showroom and orders; answers the phone | Member | `all` |
| Paweł | Workshop lead; decides what the floor does today | Agent | `all` |
| Kuba | Lead fitter; on site four days in five | Agent | `own` |
| Grzegorz | Finishing and spraying; on the floor, not at a desk | Member | `own` |

**Ola is the case that proves the two axes are separate.** She sees every task in the
tenant, because a customer on the phone asks about work she did not order and she has to
answer. She may still only create tasks and comment on them: she cannot assign, cannot
change a status, cannot set a due date. Scope answers what she sees; permissions answer
what she may do; `02-domain.md` keeps them apart for exactly this person.

What each needs, and what each would never touch:

- **Renata** needs to see everything with a date on it and to open a task from a
  customer's kitchen on her phone. She would never use a filter she had to build; if it
  is not on the default list she rings Marta.
- **Marta** needs the supplier and van work in one place, and she is the one who sets due
  dates and cleans up the dictionaries. She would never use the API, and she should never
  need to ask a developer for anything — that is M7's exit condition in one sentence.
- **Ola** needs to type what a customer just said, fast, while they are still on the
  phone, and to find a task by number when they ring back. She would never work a task.
- **Paweł** needs a list of what is unassigned and what is waiting, and he needs internal
  notes his customers' contact at the showroom does not see. He would never file a task
  for something he is about to do himself in ten minutes.
- **Kuba** needs, from a phone, on a bad connection: what is assigned to him, a photo
  attached to it, and a comment. He would never open a settings screen in his life.
- **Grzegorz** needs to report that something is out or broken, in under a minute, from a
  shared terminal by the booth. He would never read a task list.

## What they configured

Twenty minutes of setup by Marta, unchanged since:

| Dictionary | Their rows |
|---|---|
| Status | `Nowe` (new) · `W robocie` (open) · `Czeka` (waiting) · `Zrobione` (done) · `Odwołane` (cancelled) |
| Priority | `Normalny` · `Pilny` |
| Category | Warsztat · Montaż · Biuro · Transport |
| Type | Usterka · Prośba · Termin · Poprawka · Zakup |

Their names are tenant data and are never translated; every rule in the system reads the
fixed status type underneath (`01-principles.md` §5). Their types are furniture words,
not software words, which is the argument for `type` being decorative: **no rule reads
it, so a joinery may name it whatever a joinery says.** Two priorities, because a third
would mean arguing about which of two urgent things is more urgent.

## The situations

Six things that happen at Listwa, and what each becomes. These mappings are the usable
part of this document; the narratives exist so the mappings can be checked against
something.

### 1. The tail-lift fails on the ring road

Thursday, half past seven. Kuba is in the van with a wardrobe for a delivery across town
and the tail-lift will not come down. He photographs the hydraulic line, files it from
his phone in the layby, and rings Marta anyway — the point is not that he stops ringing,
it is that when Marta is with a supplier at eleven the record still exists.

- **Task** — type `Usterka`, category Transport, priority `Pilny`, no due date
- **Status path** — `Nowe` (new) → `W robocie` (open) → `Zrobione` (done)
- **Reported by** Kuba; **taken by** Marta with `task.take`, then she books the garage
- **Permissions** — Kuba: `task.create`, `task.comment`. Marta: `task.take`, `task.edit`
- **Visibility** — Kuba's scope is `own` and he is the requester, so it stays on his list
  while it runs. Attachment on the task, not in a chat thread nobody can search later.

### 2. The hinges are down to a box and a half

Grzegorz, at the shared terminal, forty seconds: hinges are low, order more before
Tuesday's carcasses. This is the most common thing that happens at Listwa and the least
interesting, which is the reason it must cost almost nothing to file.

- **Task** — type `Zakup`, category Warsztat, priority `Normalny`, no due date
- **Status path** — `Nowe` → `W robocie` → `Zrobione`
- **Reported by** Grzegorz; **worked by** Marta, who places the order and closes it with
  a comment naming the supplier and the expected day
- **Permissions** — Grzegorz: `task.create` only. Marta: `task.edit`
- **Visibility** — Grzegorz's scope is `own`; he sees it because he filed it, which is
  precisely as much of the system as he wants.

### 3. The kitchen has to be in before they move in

Ola takes the call: the customer completes on the 14th and the kitchen must be fitted
before the furniture van arrives. She files it while the customer is still talking. She
cannot set the date — she is a Member — so Marta reads the new list an hour later and
sets `due_at` to the 12th, two days of slack deliberately. Paweł works backwards from
that date when he plans the floor on Monday.

- **Task** — type `Termin`, category Montaż, priority `Normalny`, **due date set**
- **Status path** — `Nowe` → `W robocie` → `Zrobione`
- **Reported by** Ola; **date set by** Marta via `SetDueDate`; **worked by** Paweł
- **Permissions** — Ola: `task.create`. Marta: `task.edit`. Paweł: `task.edit`,
  `task.assign`
- **Visibility** — Ola's scope is `all`, so when the customer rings on the 9th to ask,
  she answers without asking anybody

**Nothing chases this date.** No reminder is sent and no escalation fires, because
neither exists. What catches it is Paweł opening a filter for tasks due this week, every
Monday, in front of everyone. If that turns out not to be enough, the fix is a
conversation about a reminder with a reason attached, not a reminder built in advance of
one.

### 4. A drawer front that catches, handed to the crew

A customer rings the showroom: two weeks after the install, a drawer front catches on the
carcass. Ola files what the customer said. Paweł reads it, adds an internal note —
the customer was difficult about the handles on the day and this is the second call —
and assigns it to Kuba, who fits it in on his way back from Wednesday's job and marks it
done. Two days later the customer rings again: it still catches. Ola replies on the same
task, and **because she is the requester and the status type is `done`, the task reopens
itself** to the tenant's default open status rather than sitting closed while everyone
assumes somebody noticed.

- **Task** — type `Poprawka`, category Montaż, priority `Normalny`, no due date
- **Status path** — `Nowe` → `W robocie` → `Zrobione` → **reopened** → `W robocie` →
  `Zrobione`
- **Reported by** Ola; **assigned by** Paweł with `task.assign`; **worked by** Kuba
- **Permissions** — Paweł: `task.note`, `task.assign`. Kuba: `task.comment`, `task.edit`.
  Ola: `task.comment`
- **Visibility** — Ola sees the task and every public comment on it. **She does not see
  Paweł's internal note**, and not because a view hides it: she lacks `task.note`, so the
  query never returns it. That boundary is the system's most important correctness
  property and it has a test that fails loudly.

### 5. The extraction filter, which is waiting on a part

Paweł files that the dust extraction is losing draw and the filter cartridge needs
replacing. The part is a three-week lead time from one supplier. There is nothing to do
about it for three weeks, and it is exactly the kind of thing that a WhatsApp group loses
completely, because it scrolls off the screen and everyone assumes somebody ordered it.

- **Task** — type `Usterka`, category Warsztat, priority `Normalny`, no due date
- **Status path** — `Nowe` → `W robocie` → `Czeka` (waiting) → `W robocie` → `Zrobione`
- **Reported by** Paweł; **worked by** Marta (ordering) and Paweł (fitting it)
- **Permissions** — Paweł: `task.edit` to move it to `Czeka`, `task.comment` to record
  the order reference
- **Visibility** — scope `all` for both; it lives on the Monday list of everything in
  `Czeka`, which is a filter on the fixed status type and is read out loud each week

`waiting` earns its place in the fixed status set here. Without it this is either open
and permanently nagging, or done and quietly lost. **The postponed thing is the one a
small firm actually loses**, and the whole product exists for it.

### 6. The extra shelf the customer thought better of

Wednesday afternoon, a customer asks to add a shelf to the wardrobe run that is already
cut. Ola files it, Paweł opens it and starts pricing the extra board. Thursday morning
the customer rings back: leave it, they have measured their boxes and it will not help.
Marta cancels it with a comment saying who called and when.

- **Task** — type `Prośba`, category Warsztat, priority `Normalny`, no due date
- **Status path** — `Nowe` → `W robocie` → `Odwołane` (cancelled)
- **Reported by** Ola; **cancelled by** Marta
- **Permissions** — Marta: `task.edit`. `task.delete` exists in the catalogue and Admin
  holds it, though nothing implements it (`06-build-plan.md`, unscheduled); **nobody at
  Listwa would use it either**, because the value of the record is the answer to "did we ever
  quote that shelf, and what happened"
- **Visibility** — scope `all`. A later comment by Ola on this task does **not** reopen
  it: the reopen rule ignores status type `cancelled`, which is the difference between
  "finished" and "abandoned" being worth modelling separately

## What this firm does not need

The razor's evidence base. Each of these is defensible in general and unnecessary here,
and that gap is the point (`01-principles.md` §1, `04-scope.md`).

| Not built | Why Listwa would not notice |
|---|---|
| **Calendar** | Three or four installs a week live in Marta's diary and Paweł's head. A due date on a task, filterable, covers what the system needs to know about time. A second calendar that must be kept in step with the first one is worse than no calendar. |
| **Checklists** | An install has perhaps six steps and the crew has done four hundred of them. A checklist would be filled in afterwards to look complete, which is a record of nothing. |
| **Chat** | The firm has WhatsApp and it works for "are you five minutes away". What it fails at is memory, which is what task-scoped comments are for. Competing with a messenger on messaging is the one fight this product does not pick. |
| **Projects** | A kitchen is one job with one crew and one date. The container would hold exactly one task most of the time. The nullable column is bought as a seam; nothing else is. |
| **SLA** | There is no promise to breach — no contract, no response-time commitment, a customer who rings when it matters. `first_responded_at` is recorded because it is unrecoverable later, and read by nothing. |
| **Reports** | Fifteen people. Renata knows how the month went because she was in the van for half of it. A count she cannot act on differently is a screen nobody opens twice. |
| **Email intake** | Every situation above starts with a phone call or a person on the floor, not an email. Intake means parsing, threading, address mapping and a new class of spam, in exchange for a channel this firm does not use for work. |

## What done means

The MVP is finished when **Listwa's week runs end to end inside TaskPost, with nothing
that matters kept in a phone, a notebook or a WhatsApp group.** Concretely, all of the
following are true at once:

1. Every one of the six situations above can be carried out by the person named, with the
   permissions and visibility scope listed, through the workspace, with no developer
   present and no seeder.
2. Marta can create the whole tenant's configuration herself: statuses mapped to fixed
   types, priorities, categories, types, roles, and the six people with their scopes.
3. Kuba can file situation 1 from a phone, attach a photo, and comment on it.
4. Ola, holding scope `all` and no `task.note`, cannot retrieve Paweł's internal note —
   proven by a test at query level, not by an inspection of the page.
5. Situation 4's reopen happens on its own, and situation 6's cancellation does not.
6. The same lifecycle is drivable through `/api/v1` with `curl`, and produces rows
   indistinguishable from the ones the workspace produces.
7. The whole thing renders in `en` and `pl` with no literal strings, and Listwa's own
   dictionary names are never translated.
8. A task from three months ago is found in under ten seconds by someone who remembers
   only a customer's surname or a number.

Anything beyond that list is a feature, and gets no protection from the razor. Anything
on it that fails is not an improvement to argue about later — it is the MVP not being
done. The milestone that delivers each of these is in `06-build-plan.md`; what "done"
means mechanically, per step, is in `07-conventions.md`.
