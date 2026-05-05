# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository provides a reusable Symfony 7.4 (PHP 8.4) application template intended to serve as a base for future projects. It contains a Docker-based development setup, common Symfony configuration, and example scaffolding (controllers, entities, templates) to accelerate new project starts. Replace project-specific references (name, routes, entities, templates, and environment variables) when creating a new project from this template.

## Docker

The entire system runs in Docker. All services — application, database, mailer, and web server — are managed via Docker Compose. Do not run PHP, Composer, or Symfony commands directly on the host.

Services in `compose.yaml`:
- `nginx` — web server, accessible at `http://localhost:8080`
- `php` — PHP 8.4-FPM app container
- `database` — PostgreSQL 16
- `mailer` — Mailpit (SMTP UI at `http://localhost:8025`)

**Start all services:**
```bash
docker compose up -d
```

All commands run inside the `php` container:
```bash
docker compose exec php <command>
```

## Symfony Console — prefer commands over manual code

**Always use `bin/console` commands to generate or configure code.** Only write code manually when no corresponding command exists.

All commands run as: `docker compose exec php php bin/console <command>`

### Code generation (`make:*`)

| Goal | Command |
|---|---|
| Controller | `make:controller` |
| Entity + repository | `make:entity` |
| CRUD completo | `make:crud` |
| Form class | `make:form` |
| Console command | `make:command` |
| Event listener/subscriber | `make:listener` |
| Message + handler (async) | `make:message` |
| Middleware Messenger | `make:messenger-middleware` |
| Stimulus controller | `make:stimulus-controller` |
| Twig component | `make:twig-component` |
| Twig extension | `make:twig-extension` |
| Validator + constraint | `make:validator` |
| Security voter | `make:voter` |
| User class | `make:user` |
| Auth (login form) | `make:security:form-login` |
| Guard authenticator | `make:auth` |
| Registration form | `make:registration-form` |
| Reset password | `make:reset-password` |
| Test class | `make:test` |
| Fixtures | `make:fixtures` |
| Scheduler | `make:schedule` |
| Serializer encoder | `make:serializer:encoder` |
| Serializer normalizer | `make:serializer:normalizer` |
| Webhook | `make:webhook` |

### Database & migrations

```bash
docker compose exec php php bin/console make:migration          # gera migration a partir das entidades
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console doctrine:migrations:status
docker compose exec php php bin/console doctrine:migrations:list
docker compose exec php php bin/console doctrine:schema:validate
docker compose exec php php bin/console dbal:run-sql "SELECT ..."
```

### Frontend (Asset Mapper / importmap)

```bash
docker compose exec php php bin/console importmap:require <package>   # adicionar pacote JS
docker compose exec php php bin/console importmap:remove <package>
docker compose exec php php bin/console importmap:update
docker compose exec php php bin/console importmap:audit
docker compose exec php php bin/console asset-map:compile             # build para produção
```

### Debug & inspeção

```bash
docker compose exec php php bin/console debug:router
docker compose exec php php bin/console debug:container
docker compose exec php php bin/console debug:autowiring
docker compose exec php php bin/console debug:twig
docker compose exec php php bin/console debug:event-dispatcher
docker compose exec php php bin/console debug:messenger
docker compose exec php php bin/console debug:firewall
docker compose exec php php bin/console debug:validator
docker compose exec php php bin/console debug:form <FormType>
```

### Lint

```bash
docker compose exec php php bin/console lint:container
docker compose exec php php bin/console lint:twig
docker compose exec php php bin/console lint:yaml config/
docker compose exec php php bin/console lint:translations
```

### Testes

```bash
docker compose exec php php bin/phpunit
docker compose exec php php bin/phpunit tests/Path/To/FooTest.php
```

### Messenger (filas)

```bash
docker compose exec php php bin/console messenger:consume async
docker compose exec php php bin/console messenger:stats
docker compose exec php php bin/console messenger:failed:show
docker compose exec php php bin/console messenger:failed:retry
```

### Cache

```bash
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/console cache:warmup
```

### Composer

```bash
docker compose exec php composer install
docker compose exec php composer require <package>
docker compose exec php composer remove <package>
```

## Architecture

**Request flow:** `public/index.php` → Symfony Kernel → Controller → Twig template

**Frontend:** Uses Symfony Asset Mapper (zero build step). Stimulus.js handles DOM interactivity; Hotwired Turbo provides AJAX navigation. Frontend dependencies are declared in `importmap.php` and controllers registered in `assets/controllers.json`.

**Data layer:** Doctrine ORM + PostgreSQL 16. Entities live in `src/Entity/`, repositories in `src/Repository/`, and migrations in `migrations/`.

**Async jobs:** Symfony Messenger with Doctrine transport (jobs stored in the database). Emails and other background tasks can be dispatched via Messenger; workers run inside Docker (see Commands above).

**AI (optional):** The template includes `symfony/ai-platform` examples to demonstrate integrations for chat or content generation; remove or adapt as needed for your project.

**Template notes:** When starting a new project from this base, update `compose.yaml`, `APP_SECRET`, `DATABASE_URL`, service names, and any README or CI configuration to reflect the new project identity.

## Environment

Key `.env` variables:
- `DATABASE_URL` — PostgreSQL connection (Docker default: `postgresql://app:!ChangeMe!@database:5432/app`)
- `MESSENGER_TRANSPORT_DSN` — defaults to Doctrine transport
- `MAILER_DSN` — `null://null` by default; dev Docker exposes Mailpit UI at `http://localhost:8025`
- `APP_SECRET` — must be set for production

Use `.env.local` for local overrides (not committed). `.env.test` configures the test database.
