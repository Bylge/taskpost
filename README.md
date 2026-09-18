# TaskPost

Multi-tenant system for running a small firm's work — fixes, bugs, tasks, requests and
deadlines in one shared record, instead of scattered across a messenger group, two phone
numbers and a whiteboard. Aimed at firms of up to about thirty people, where Slack, Jira and
Teams are more product than the problem needs. Everything lives in a tenant, everyone works in
one workspace, and what a person may see and what they may do are kept as separate questions.

**Status: planning. There is no application code in this repository yet** — nothing is
installed, scaffolded or runnable. What exists is the design, and it is deliberately being
finished before the first line of PHP.

## Intended stack

Laravel 13 · PHP 8.5 · Livewire 4 · Filament 5 · Blade · Tailwind · PostgreSQL · Pest 5

Version numbers are re-verified at install rather than taken on trust; the reasoning behind
each choice is recorded in `CLAUDE.md`.

## What is in here

`docs/` is the substance of this repository right now: the domain model and its permission
design, the architecture and the layer rules it holds to, what is deliberately out of scope
and why, the milestone plan, and a written reference scenario — one fictional fifteen-person
firm whose week the system has to run end to end, against which every scope decision is
argued.

## Licence

All rights reserved — see `LICENSE`. This repository is published to be read, not to be
used: it is not open source, and no licence to use, copy, modify, host or deploy the software
is granted.
