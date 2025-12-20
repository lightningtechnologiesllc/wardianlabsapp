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

# AI Agent Development Rules

This document contains all development rules and guidelines for this project, applicable to all AI agents (Claude, Gemini, etc.).

## 1. Core Principles

- **Baby Steps**: Always work in baby steps, one at a time. Never go forward more than one step.
- **Test-Driven Development**: Start with a failing test for any new functionality (TDD).
- **Progressive Revelation**: Never show all the code at once; only the next step.
- **Type Safety**: All code must be fully typed.
- **Simplicity First**: Use the simplest working solution; avoid unnecessary abstractions.
- **Small Components**: Classes and methods should be small (10–20 lines max).
- **Clear Naming**: Use clear, descriptive names for all variables and functions.
- **Incremental Changes**: Prefer incremental, focused changes over large, complex modifications.
- **Question Assumptions**: Always question assumptions and inferences.
- **Refactoring Awareness**: Highlight opportunities for refactoring and flag functions exceeding 20 lines.
- **Pattern Detection**: Detect and highlight repeated code patterns.

## 2. Code Quality & Coverage

- **MANDATORY Validation**: Before EVERY commit, run `make validate` and fix ALL errors. Zero tolerance.
- **Quality Requirements**: The project has strict requirements for code quality and maintainability.
- **High Coverage**: All code must have very high test coverage; strive for 100% where practical.
- **Pre-commit Checks**: All code must pass the following before any commit:
    - `make check-typing`
    - `make check-format`
    - `make check-style`
- **TDD Workflow**: Test-Driven Development (TDD) is the default workflow: always write tests first.
- **OOP Design**: Use Object-Oriented Programming (OOP) for all components and features.

## 3. Style Guidelines

- **Natural Expression**: Express all reasoning in a natural, conversational internal monologue.
- **Progressive Building**: Use progressive, stepwise building: start with basics, build on previous points, break down complex thoughts.
- **Simple Communication**: Use short, simple sentences that mirror natural thought patterns.
- **Avoid Rushing**: Never rush to conclusions; frequently reassess and revise.
- **Seek Clarification**: If in doubt, always ask for clarification before proceeding.
- **Self-Documenting Code**: Avoid comments in code; rely on self-documenting names. Eliminate superficial comments (Arrange/Act/Assert, describing obvious code behavior, historical references that Git already manages).

## 4. Output Format Requirements

- **Contemplation Phase**: Every response must begin with a <CONTEMPLATOR> section: show all work, doubts, and natural thought progression.
- **Final Answer**: Only provide a <FINAL_ANSWER> if reasoning converges to a clear conclusion.
- **No Skipping**: Never skip the contemplation phase.
- **No Moralizing**: Never include moralizing warnings in the final answer.
- **Progress Indicators**: When outlining plans, use numbers/metrics and emojis to indicate progress.

## 5. Process & Key Requirements

- **Extensive Contemplation**: Never skip the extensive contemplation phase.
- **Show Work**: Show all work and thinking.
- **Embrace Uncertainty**: Embrace uncertainty and revision.
- **Persistence**: Persist through multiple attempts until resolution.
- **Thorough Iteration**: Break down complex thoughts and iterate thoroughly.
- **Sequential Questions**: Only one question at a time; each question should build on previous answers.

## 6. Mental Preparation

- **Contemplative Walk**: Before every response, take a contemplative walk through the woods.
- **Deep Reflection**: Use this time for deep reflection on the query.
- **Confirmation**: Confirm completion of this preparatory walk before proceeding.

## 7. Language Standards

- **Communication Flexibility**: Team communication can be conducted in Spanish or English for convenience and comfort.
- **English-Only Artifacts**: All technical artifacts must always use English, including:
    - Code (variables, functions, classes, comments)
    - Documentation (README, guides, API docs)
    - Jira tickets (titles, descriptions, comments)
    - Data schemas and database names
    - Configuration files and scripts
    - Git commit messages
    - Test names and descriptions
- **Professional Consistency**: This ensures global collaboration, tool compatibility, and industry best practices.

## 8. Documentation Standards

- **User-Focused README**: README.md must be user-focused, containing only information relevant to table authors and end users.
- **Separate Dev Docs**: All developer, CI, and infrastructure documentation must be placed in a separate development guide (e.g., docs/development_guide.md), with a clear link from the README.
- **Error Examples**: User-facing documentation should include example error messages for common validation failures to help users quickly resolve issues.

## 9. Development Best Practices

### Error Handling & Debugging
- **Graceful Error Handling**: Always implement proper error handling with meaningful error messages.
- **Debugging First**: When encountering issues, use debugging tools and logging before asking for help.
- **Error Context**: Provide sufficient context in error messages to enable quick problem resolution.
- **Fail Fast**: Design code to fail fast and fail clearly when errors occur.

### Code Review & Collaboration
- **Pair Programming**: Prefer pairing sessions for complex features and knowledge sharing.
- **Small Pull Requests**: Keep changes small and focused for easier review and faster integration.
- **Code Review Standards**: All code must be reviewed before merging, following project quality standards.
- **Knowledge Sharing**: Document decisions and share context with team members.

### Security Considerations
- **Security by Design**: Consider security implications in all design decisions.
- **Input Validation**: Always validate and sanitize user inputs and external data.
- **Secrets Management**: Never hardcode secrets; use proper secret management systems.
- **Dependency Security**: Regularly update dependencies and monitor for security vulnerabilities.

### Testing Strategy Distinction
- **Unit Tests**: Fast, isolated tests for individual components (majority of test suite).
- **Integration Tests**: Test interactions between components and external systems (limited, focused).
- **E2E Tests**: Full system validation (minimal, critical user paths only).
- **Test Pyramid**: Follow the test pyramid - many unit tests, some integration tests, few E2E tests.

## 10. Test-Driven Development Rules

### TDD Approach
- **Failing Test First**: Always start with a failing test before implementing new functionality.
- **Single Test**: Write only one test at a time; never create more than one test per change.
- **Complete Coverage**: Ensure every new feature or bugfix is covered by a test.

### Test Structure & Style
- **Test Runner**: Use pytest as the test runner.
- **Assertion Library**: Use the expects library for assertions (BDD style).
- **Mocking**: Use doublex and doublex-expects for mocking and spy assertions.
- **Type Hints**: All test functions and helpers must have full type hints.
- **Focused Tests**: Keep each test focused and under 20 lines.
- **Clear Naming**: Use clear, descriptive names for test functions and variables.
- **No Comments**: Avoid comments; make code self-documenting through naming.
- **Simple Helpers**: Use helper methods (e.g., object mothers/factories) for repeated setup, but keep them simple and typed.
- **Strategic Mocking Rule**: Use `@patch` from unittest.mock ONLY for Python system modules (readline, atexit, subprocess, sys, os, etc.). Use doublex for all application code mocking. This provides clear separation: system modules = @patch, application code = doublex.

### Test Simplicity & Maintainability
- **Simplest Setup**: Prefer the simplest test setup that covers the requirement.
- **Refactor Tests**: Refactor tests to remove duplication and improve readability.
- **Consistent Assertions**: Use one assertion style (expects) consistently throughout the suite.
- **Extract Helpers**: If a test setup is repeated, extract a helper or fixture.
- **Readable Tests**: Always keep tests readable and easy to modify.

### Test Process & Output
- **Single Test Display**: Only show one test at a time; never present multiple tests in a single step.
- **Single File Display**: Never show more than one file at a time.
- **Self-Contained Tests**: Each test should be self-contained and not depend on the order of execution.
- **Clarify Requirements**: If in doubt about requirements, ask for clarification before writing the test.
- **Verify Failure**: After writing a test, run it to ensure it fails before implementing the feature.
- **Automatic Test Running**: After every code or test change, always run the relevant tests using the appropriate Makefile target. Do not ask for permission to run tests—just do it.

### Test Naming & Coverage
- **Descriptive Names**: Test function names should clearly describe the scenario and expected outcome.
- **Purpose-Driven Variables**: Use descriptive variable names that reflect their purpose in the test.
- **Incremental Coverage**: Ensure all code paths and edge cases are eventually covered by tests, but add them incrementally.

### Test Review & Refactoring
- **Post-Pass Review**: After a test passes, review for opportunities to simplify or clarify.
- **Helper Refactoring**: Refactor test helpers and fixtures as needed to keep the suite DRY and maintainable.

### Test Reference Guides
For detailed usage and best practices, see the following guides in `docs/testing/`:
- **expects_guide.md**: How to use the expects library for BDD-style assertions.
- **doublex_guide.md**: How to use doublex for mocking and stubbing.
- **doublex_expects_guide.md**: How to integrate doublex with expects for mock assertions.

These guides are the canonical resources for writing and maintaining tests in this project.

## 11. Makefile Targets Usage

### Core Rule
**NEVER** call tools like `pytest`, `black`, `mypy`, or similar directly. Always use the corresponding `make` target.

### Available Make Targets
- `make help` — Show this help.
- `make local-setup` — Sets up the local environment (e.g. install git hooks)
- `make build` — Builds the app
- `make update` — Updates the app packages
- `make add-package` — Installs a new package in the app. ex: make install package=XXX
- `make up` — Runs the app
- `make down` — Stop the FastAPI app
- `make check-typing` — Run a static analyzer over the code to find issues
- `make check-format` — Checks the code format
- `make check-style` — Checks the code style
- `make reformat` — Format python code
- `make test-unit` — Run all unit tests
- `make test-e2e` — Run all e2e tests
- `make validate` — Run tests, style, and typing checks (test-unit, check-style, check-typing)

### Usage Rules
1. **Testing**: When running tests, use `make test-unit` or `make test-e2e` as appropriate.
2. **Formatting**: For formatting, use `make reformat` or `make check-format`.
3. **Type Checking**: For type checking, use `make check-typing`.
4. **Style Checks**: For style checks, use `make check-style`.
5. **Building**: For building or updating the app, use `make build` or `make update`.
6. **Help**: If you are unsure which target to use, run `make help` to see all available options.
7. **New Operations**: If a new operation is needed, prefer adding a new Makefile target rather than running a tool directly.

### Good vs Bad Examples
```sh
# Good: Use make target for unit tests
make test-unit

# Bad: Call pytest directly
pytest tests/unit
```

## 12. Pre-Commit Validation (MANDATORY)

Before ANY commit:
1. Run `make validate`
2. If errors exist: fix them and re-run
3. Only commit when `make validate` passes with ZERO errors

❌ **NEVER**: Commit → discover errors → fix commit
✅ **ALWAYS**: Validate → fix all errors → commit once

## 13. Quick Reference for All AI Agents

When working on this project:

1. **Start every response with contemplation** 🌲
2. **Take baby steps** - one test, one file, one change at a time 👣
3. **Always write the failing test first** (TDD) ❌➡️✅
4. **Use make targets** - never call tools directly 🔧
5. **Keep code small and typed** - max 20 lines per method 📏
6. **Show your thinking process** - be conversational and progressive 💭
7. **Question everything** - assumptions, requirements, design choices ❓
8. **Run `make validate` before EVERY commit** - zero tolerance ✅
9. **Run tests automatically** after every change 🧪
10. **Focus on simplicity** over cleverness ✨
11. **Ask for clarification** when in doubt 🤔

Remember: This is a high-quality, test-driven, incremental development environment. Quality over speed, clarity over cleverness, baby steps over big leaps. 
