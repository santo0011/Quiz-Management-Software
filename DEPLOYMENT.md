# Deployment & Database Safety

**Priority: code updates must never affect existing live data** — Students, Exams,
Questions, Results, Academic Sessions, Grades, Subjects, Branches, everything.
This document exists so that priority survives contact with a terminal.

## Deploying an update (production)

```
composer run deploy
```

This runs, in order: `composer install --no-dev`, `php artisan migrate --force`
(safe/additive only — see below), `npm run build`, then warms the config/route/view
caches. It does **not** seed, truncate, or reset anything. Nothing in it can delete
a row.

If you deploy by hand instead of via that script, the equivalent safe sequence is:

```
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## First-time LOCAL/dev setup only

```
composer run setup:local
```

This seeds a default Super Admin account (`avsbera.gpt1@gmail.com` / `123456`) via
`db:seed --force`. It is meant for a brand-new, empty **local** database.
**Never run this against production** — even though the seeder itself is now
written to be non-destructive (see below), running unfamiliar setup scripts
against a live database is exactly the kind of mistake this document exists to
prevent.

## Commands that are BLOCKED in production

The application refuses to run these when `APP_ENV=production`, even with
`--force` — see `AppServiceProvider::blockDestructiveArtisanCommandsInProduction()`:

| Command | What it does |
|---|---|
| `migrate:fresh` | Drops **every** table, then re-runs all migrations from scratch |
| `migrate:refresh` | Rolls back **all** migrations (running every `down()`, which drops columns/tables) then re-migrates |
| `migrate:reset` | Rolls back **all** migrations |
| `db:wipe` | Drops every table, view, and type in the database |

Any of these against production means every Student, Exam, Question, and Result
is gone. There is no `--force` override for the block — if production data
genuinely needs to be reset, do it manually and deliberately outside of this
application (and take a backup first, on purpose, as its own explicit step).

Laravel's own confirmation prompt already covers this outside of production-named
environments; this block exists specifically because `--force` (routine in any
deploy script) silently skips that prompt.

## Commands that are safe to run anytime, including production

- `php artisan migrate --force` — every migration in `database/migrations/` only
  adds tables/columns or widens/relaxes existing ones (see the audit below). None
  of them drop a table or column, delete rows, or truncate anything in their
  `up()` method. Migrations also only ever run **once** — Laravel tracks which
  ones already applied, so re-deploying never re-runs an old migration against
  data it already touched.
- `php artisan config:cache` / `route:cache` / `view:cache` (and their `:clear`
  counterparts) — these only touch compiled PHP cache files, never the database.
- `php artisan queue:restart`, `php artisan pail`, etc. — operational commands,
  no schema/data impact.

## Commands that are destructive by design and need a deliberate, human decision

These aren't blocked outright (they're legitimate tools), but treat each one as
a one-way door. Read the warning, confirm you're pointed at the right database,
and take a backup first:

- `php artisan migrate:rollback` — runs the `down()` of recent migrations, which
  (by design) drops whatever that migration added. Only ever needed to undo a
  migration that hasn't "gone live" with real data in the columns/tables it
  touches yet.
- `php artisan db:seed` — the shipped `DatabaseSeeder` is idempotent
  (`firstOrCreate`, not `updateOrCreate`) and only ever touches one Super Admin
  row by email, so re-running it is safe. A *new* seeder you add later might not
  be — audit any new seeder the same way before it can run with `--force` in an
  automated script.
- Any raw `DB::table(...)->delete()/truncate()`, `Model::truncate()`, or manual
  SQL `DROP`/`DELETE`/`TRUNCATE` run through `tinker` or a one-off script.
  There is no framework-level guard against these because they're not Artisan
  commands — the guard above only intercepts named commands. If you ever write
  one, put a big warning comment above it and run it against a backup first.

## Migration review (why forward migrations are safe today)

Every migration in `database/migrations/` was audited for this task:

- No `up()` method contains `dropColumn`, `dropIfExists`, `Schema::drop`,
  `truncate`, or a `delete()`/`DELETE` statement. All such calls exist only in
  `down()` methods, which run solely on an explicit `migrate:rollback` —
  never during a normal `migrate` deploy.
- Column-type changes (`->change()`) only widen or relax constraints:
  `nullable()` (branch_id on several tables), `text` → `longText` (question
  text, to fit larger CKEditor content) — none of these can truncate or drop
  existing values.
- A few migrations transform existing row *content* in place (never delete
  rows): sanitizing/escaping stored HTML, and one historical migration that
  rounds `questions.marks` to the nearest integer when that column changed
  from decimal to integer. These are one-time, already-applied, additive-safe
  transforms, not deletions.

If you add a new migration, keep following this pattern: `up()` only creates or
additively alters; anything that removes a column/table belongs in `down()`
only, and any migration that rewrites existing row content must be reviewed for
data loss the same way.

## Local development / tests are already isolated from production

- `phpunit.xml` hard-codes `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` for
  every test run, overriding whatever `.env` says. Running `php artisan test` or
  `composer test` can never touch the real database, including any test using
  `RefreshDatabase`.
- `.env` / `.env.example` default to `APP_ENV=local`. Set `APP_ENV=production`
  on the actual production server so the guard above is active there.
