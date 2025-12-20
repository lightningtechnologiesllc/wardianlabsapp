# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture Overview

This is a Symfony 7.3 application using **Hexagonal Architecture** (Ports & Adapters) with a modular, domain-driven design. The codebase is organized into bounded contexts under `lib/modules/src/`:

### Bounded Contexts

- **Admin**: Administrative functionality for managing tenants and system configuration
- **Frontend**: Member management, Discord/Stripe integration, and user-facing features
- **Shared**: Cross-cutting concerns shared between contexts (Domain entities, Infrastructure utilities)
- **Twig**: Live Components for interactive UI

### Layer Structure (Hexagonal Architecture)

Each bounded context follows this structure:

- **Domain/**: Pure business logic, entities, value objects, repositories (interfaces), and domain services
  - Contains aggregates like `Member`, value objects like `DiscordId`, `EmailAddress`
  - Repository interfaces define contracts without implementation details
- **Application/**: Use cases, command/query handlers, and application services
  - Orchestrates domain logic and coordinates between domain and infrastructure
  - Examples: `CreateNewMemberHandler`, `LinkAccountsUseCase`
- **Infrastructure/**: Technical implementations (Doctrine repositories, HTTP clients, external API integrations)
  - `Persistence/Doctrine/`: Doctrine ORM repositories implementing domain repository interfaces
  - `Provider/`: External service integrations (Discord API, Stripe API)
- **Ui/Adapter/Http/**: Controllers and HTTP adapters

### Core Library (`lib/core/`)

Shared foundational types used across all modules:

- **Types/Identifier/**: Base identifier types (`Ulid`, `ObjectId`, `StringId`)
- **Types/Collection/**: Collection wrapper for type-safe arrays
- **Types/Aggregate/**: `AggregateRoot` base class
- **Messaging/**: Domain event infrastructure

### Multi-Tenancy

The application is multi-tenant:
- `TenantId` (in `Shared/Domain/Tenant/`) identifies tenants
- `TenantProvider` implementations extract tenant context from requests
- `RepositoryTenantProvider`: Loads tenant config from database via `DoctrineTenantRepository`

### External Integrations

- **Discord**: OAuth authentication + Bot API for role management
  - `ApiDiscordBotManagerProvider` handles Discord Bot API calls
  - Discord concepts: `GuildId`, `DiscordId` (user/guild), `DiscordRole`, `DiscordRoles`
- **Stripe**: OAuth for connected accounts + subscription management
  - `HttpStripeProvider` for Stripe API
  - `StripeSubscription`, `StripeSubscriptions` domain models

## Development Commands

### Docker Environment

All commands run inside Docker containers. The app service is the main PHP container.

**Start environment:**
```bash
make start
```
This builds containers, installs dependencies (composer + yarn), and compiles assets.

**Stop environment:**
```bash
make stop
```

**View logs:**
```bash
make logs
```

### Dependencies

**Composer (PHP):**
```bash
make composer-install
make composer-update
make composer-require module=package/name
```

**Yarn (JavaScript):**
```bash
make yarn-install    # Install JS dependencies
make yarn-dev        # Build assets for development
make watch           # Watch for changes and rebuild
```

### Testing

**Run all PHPUnit tests:**
```bash
make test
# or
make phpunit-tests
```

**Run specific test suites:**
```bash
make phpunit-tests-unit          # Unit tests
make phpunit-tests-integration   # Integration tests
make phpunit-tests-functional    # Functional tests (currently commented out)
make phpunit-tests-acceptance    # Acceptance tests
```

**Run tests in Docker with environment:**
```bash
make ci-tests  # Full CI pipeline: install deps, create DB, migrate, run tests
```

**Test database setup:**
```bash
make create-database-test   # Create test database
make migrate-test           # Run migrations on test DB
```

### Database Migrations

**Run migrations:**
```bash
make migrate
```

**Create database:**
```bash
make create-database
```

### Code Quality

**Static analysis (PHPStan - if configured):**
```bash
make static-analysis
```

**Code style fixing:**
```bash
make cs-fix
```

### Symfony Console

**Access Symfony console commands:**
```bash
make bash  # Get shell in app container, then:
bin/console [command]
```

**Clear cache:**
```bash
make clear-cache
```

## Development Methodology

### Test Driven Development (TDD)

This project follows **Test Driven Development** with an **Outside-In** approach:

**Red → Green → Refactor Cycle:**
1. **Red**: Write a failing test first
2. **Green**: Write minimal code to make the test pass
3. **Refactor**: Improve the code while keeping tests green

**Outside-In Testing:**
- Start from the **outside** (high-level behavior/use case tests)
- Work **inward** to lower-level components (unit tests for collaborators)
- Write acceptance/use case tests first to define the behavior
- As you discover needed collaborators during implementation, write tests for those
- Use test doubles (InMemory implementations) instead of mocks where possible

**Key Principles:**
- Always write the test **before** the implementation
- Keep mocks to a minimum - prefer InMemory implementations
- Use Mother pattern (test data builders) for creating test objects
- Tests should be readable and express intent clearly
- Each test should verify one specific behavior

**Example Workflow:**
1. Write a use case test (outside) that describes the high-level behavior
2. Run test - it fails (RED)
3. Implement the use case with minimal code
4. Discover you need a repository method
5. Write a test for that repository method (inside)
6. Implement the repository method
7. Continue until the outside test passes (GREEN)
8. Refactor if needed

## Test Organization

Tests are organized by type in `lib/modules/tests/`:

- **unit/**: Fast, isolated tests with InMemory dependencies
- **integration/**: Tests that interact with real infrastructure (database, external APIs)
- **doubles/**: Test doubles (InMemory implementations, Mother pattern for object creation)
- **e2e/**: End-to-end browser tests (currently disabled)
- **functional/**: Functional tests (currently disabled)

PHPUnit configuration: `phpunit.xml`

## Key Patterns

### Value Objects

Heavily used for type safety and domain clarity:
- All IDs are value objects (e.g., `MemberId`, `TenantId`, `DiscordId`)
- Email addresses: `EmailAddress`
- Collections: `DiscordRoles`, `StripeSubscriptions` (typed collections)

### Aggregates

Domain entities extend `AggregateRoot` from `App\Core\Types\Aggregate\AggregateRoot`:
- `Member` is the primary aggregate in Frontend context
- Aggregates ensure consistency boundaries

### Repository Pattern

- Domain defines repository interfaces (e.g., `MemberRepository`)
- Infrastructure provides implementations (e.g., `DoctrineMemberRepository`)
- Repositories work with aggregates and value objects, not raw data

### Object Mothers (Test Data Builders)

Test doubles use the Mother pattern for creating test objects:
- Located in `lib/modules/tests/doubles/`
- Example: `MemberMother`, `TenantIdMother`

## Autoloading

```json
"App\\": "lib/modules/src/",
"App\\Core\\": "lib/core/src/"
```

Test namespaces:
- `Tests\Unit\App\`: unit tests
- `Tests\Integration\App\`: integration tests
- `Tests\Doubles\App\`: test doubles
- `Tests\Acceptance\App\`: acceptance tests

## Service Configuration

Services auto-configured in `config/services.yaml`:
- Auto-wiring enabled for all services in `lib/modules/src/`
- Explicit configuration for complex dependencies (Discord bot, Stripe client)
- Controllers tagged with `controller.service_arguments`

## Running Single Tests

```bash
# Inside container (make bash)
vendor/bin/phpunit path/to/TestFile.php
vendor/bin/phpunit --filter testMethodName
```

## Database

- PostgreSQL 17.5
- Doctrine ORM 3.5
- Connection: `postgresql://app:app@database:5432/app` (dev)
- Separate test database: `app_test`

## Frontend Assets

- Webpack Encore for asset compilation
- Stimulus + Turbo for interactive components
- Tailwind CSS 4.1
- Live Components for dynamic UI

## Environment Variables

Key variables in `.env`:
- `APP_ENV`: dev/test/prod
- `DISCORD_CLIENT_ID`, `DISCORD_CLIENT_SECRET`, `DISCORD_BOT_TOKEN`: Discord integration
- `OAUTH_STRIPE_CLIENT_ID`, `OAUTH_STRIPE_CLIENT_SECRET`: Stripe OAuth
- `DATABASE_URL`: PostgreSQL connection string
