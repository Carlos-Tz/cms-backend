# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## global rules
Check global rules in `.github/copilot-instructions.md` for general instructions on coding style, API development, Git usage, and testing. Where it conflicts with this file, this file wins.
If the users asks for write documentation, follow the instructions in `.github/document-instructions.md`.

## Git conventions
- Use conventional commits for all commits. See [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) for details.

## Project

Custom headless CMS backend (API-only, no frontend) that manages content for 4 marketing websites of the GNU Gas Natural group: `gnu`, `gnu-vehicular`, `traninn-logistic`, `tectuus`.

The content spec — which pages each site has, which sections each page carries, and which fields the admin may edit — lives in the **`sitios-web-gnu` skill** (`.claude/skills/sitios-web-gnu/`). Read it before implementing any domain feature. `prompt.md` is only a log of the phase currently in progress, not a spec.

**Language rules:** 
1. all code, identifiers, and comments in English. Conversation with the user is in Spanish.
2. API user responses are always in Spanish. 


## Architecture

Single Laravel app, single SQLite database (`database/database.sqlite`), multi-site isolation via a `site_id` column on every domain table (except `sites` and `users`). Site filtering must always happen backend-side (Eloquent scopes), never trusting client input.

Two API surfaces, registered in `bootstrap/app.php` via the `then:` closure of `withRouting()`:

- **Admin API** — `routes/admin.php`, prefix `/api/admin`, protected by JWT (`auth:api` guard, `tymon/jwt-auth`). Only `POST /api/admin/auth/login` is open. Controllers in `app/Http/Controllers/Admin/`. Single global role with full access (no per-site roles yet).
- **Public API** — `routes/public.php`, prefix `/api/public/{site}`, read-only, site resolved by slug in the route. The only public write is `POST {site}/leads`. Controllers in `app/Http/Controllers/Public/`.

All JSON responses use the shape `{ "data": ..., "meta": ... }` to leave room for pagination.

**Current phase:** scaffolding only. Routes exist, `AuthController` has real JWT login/logout/me, all other controllers are placeholders returning `{data, meta}` stubs. There are no domain migrations or models yet — only the default Laravel `users`, `cache` and `jobs` tables. Domain migrations, models, policies, and business logic come in later phases.

## Content model — predefined blueprint

**This CMS is not a page-builder.** Development predefines every page, every section on that page, its order, and every field of each section. The administrator only updates values.

- Development defines (in the blueprint, immutable through the admin API): which pages exist and their route, which sections a page carries and in what order, which fields each section type has, and which of those fields are locked.
- The admin may: edit the value of `editable` fields, add/remove/reorder **items inside a collection** (a carousel slide, a testimonial, a client logo), publish/unpublish a page, and manage domain content that lives in its own tables (`blog_posts`, `stations`, `leads`, `media`).
- The admin may not: create or delete pages, add/remove/reorder **sections**, change a section's `type`, or edit locked fields.

The line is between **data** (collection items — the admin's job) and **structure** (pages and sections — code's job).

Three layers, kept separate so field definitions aren't repeated per page: the **schema** (which fields a section type has) in `.claude/skills/sitios-web-gnu/references/section-types.md`; the **instances** (which pages exist, which sections each carries) in `references/{site}.md`; the **values** in seeders and the DB. `gnu` is already migrated to this format — the other three sites still hold only the client's raw navigation tree.

The eventual machine-readable form is `config/blueprints/{site}.php`, derived from those markdown files and consumed by seeders. It does not exist yet; do not invent a different shape for it.

## Commands

- `composer test` — run the test suite (Pest). Single test: `php artisan test --compact --filter=testName`.
- `vendor/bin/pint --dirty --format agent` — format modified PHP files (required before finalizing changes).
- `php artisan route:list --path=api` — inspect the two API surfaces.
- `php artisan jwt:secret` — regenerate `JWT_SECRET` in `.env`.
- App is served by Laravel Herd at `http://gnu-headless-cms.test` — never start a dev server.

## Conventions

- Table names: snake_case plural (`sites`, `pages`, `blog_posts`).
- Every domain table carries `site_id` FK except `sites` and `users`.
- For undocumented design decisions: pick the simplest option consistent with the `sitios-web-gnu` skill, note it with a brief code comment, and continue — don't block on minor details.
- Always use English for code, identifiers, and comments. Conversation with the user is in Spanish.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
