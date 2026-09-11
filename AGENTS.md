
This package integrates Spatie Ignition with Maho Commerce to provide error pages and
diagnostics for installation and runtime errors, with optional OpenAI and Flare support.
Keep changes scoped to this package, compatible with PHP 8.3+, and consistent with Maho's
module/MVC architecture.

## Essential Rules

- Do not modify the Maho core (`vendor/mahocommerce/maho`) to fix package behavior.
- Keep changes minimal and preserve the existing module architecture and responsibilities.
- Use only the existing `mage_run_installed_exception` and `mage_run_exception` hooks.
- Keep installation errors, runtime errors, UI, OpenAI, and Flare responsibilities separate.
- Keep OpenAI and Flare optional. Never commit API keys, tokens, credentials, sensitive stack
  traces, or personal payloads.
- Validate the supported `theme` and `editor` settings when configuration changes.
- Update `tests/smoke.php` when behavior or configuration changes require coverage.
- Do not invent utilities or scripts: `composer.json` is the source of truth.

## Package Layout

```text
app/etc/modules/Maho_Ignition.xml                    # Module declaration
app/code/community/Maho/Ignition/etc/config.xml      # Module configuration and observers
app/code/community/Maho/Ignition/etc/system.xml      # Admin configuration, theme/editor
app/code/community/Maho/Ignition/Model/Observer/     # Exception and registration observers
app/code/community/Maho/Ignition/Helper/             # Configuration, Flare, and OpenAI helpers
app/code/community/Maho/Ignition/Controller/         # Router
app/code/community/Maho/Ignition/controllers/        # Controllers
tests/smoke.php                                       # Package smoke test
.php-cs-fixer.php                                     # PHP-CS-Fixer configuration
.phpstan.dist.neon                                    # PHPStan configuration
rector.php                                            # Rector configuration
composer.json                                         # Authoritative package scripts
```

## Package Utility Commands

These are the scripts actually defined in this package's `composer.json`. The standard mode uses
the local Composer/PHP tools and is not equivalent to verification in the Harbor environment.
Always use `--working-dir`; do not replace it with `cd`:

- `composer --working-dir=./vendor/empiricompany/maho-ignition test` — runs `php tests/smoke.php`; expected exit code: `0` when the smoke test passes.
- `composer --working-dir=./vendor/empiricompany/maho-ignition lint` — runs `php -l` for PHP files under `app` and `tests`; expected exit code: `0` when syntax checks pass.
- `composer --working-dir=./vendor/empiricompany/maho-ignition cs` — runs PHP-CS-Fixer with `--dry-run --diff`; expected exit code: `0` when no style changes are needed.
- `composer --working-dir=./vendor/empiricompany/maho-ignition phpstan` — runs PHPStan with `.phpstan.dist.neon`; expected exit code: `0` when no analysis errors are reported.
- `composer --working-dir=./vendor/empiricompany/maho-ignition rector` — runs Rector with `--dry-run`; expected exit code: `0` when no refactoring is proposed.
- `composer --working-dir=./vendor/empiricompany/maho-ignition check` — Composer alias for `test`, `lint`, `cs`, `phpstan`, and `rector` in sequence; expected exit code: `0` only when all five checks pass.

## Harbor Commands

These commands are available only when `./vendor/bin/harbor` exists and the environment is
Harbor. Run them from the project root:

For local development inside the Maho installation, `localdev/maho-ignition/vendor` may be a
local ignored symlink to the root `vendor` directory. This reuses the installed Maho and tools
without running a nested `composer install`; CI still installs the package dependencies normally.

- `./vendor/bin/harbor composer --working-dir=./vendor/empiricompany/maho-ignition test` — smoke test; expected exit code: `0`.
- `./vendor/bin/harbor composer --working-dir=./vendor/empiricompany/maho-ignition lint` — PHP syntax checks; expected exit code: `0`.
- `./vendor/bin/harbor composer --working-dir=./vendor/empiricompany/maho-ignition cs` — PHP-CS-Fixer dry-run; expected exit code: `0`.
- `./vendor/bin/harbor composer --working-dir=./vendor/empiricompany/maho-ignition phpstan` — PHPStan analysis; expected exit code: `0`.
- `./vendor/bin/harbor composer --working-dir=./vendor/empiricompany/maho-ignition rector` — Rector dry-run; expected exit code: `0`.
- `./vendor/bin/harbor composer --working-dir=./vendor/empiricompany/maho-ignition check` — all five checks through the Composer alias; expected exit code: `0`.

If Harbor is unavailable or the environment does not use it, use the standard commands above.
Report the context explicitly as a local/non-Harbor run and note relevant differences in PHP,
dependencies, containers, or configuration.

## Validation Order and Reporting

When applicable, run `test`, `lint`, `cs`, `phpstan`, and `rector` in that order. Use `check` when
the combined alias is sufficient. Exit code `0` means success; any other exit code means a failed
check, environment error, or unmet condition. Report every command actually run, its context
(Harbor or fallback), its exit code, and any errors. Never claim a check that was not run and never
replace a failed command with an assumed success.

## Runtime and Security Verification

When behavior requires runtime validation, verify Contacts/Flare delivery, dashboard project,
environment and release, event grouping, stack trace, useful context, IP anonymization, and the
absence of secrets. Keep API keys outside source code, fixtures, logs, and reports; use mocks or
temporary credentials.

Distinguish the following cases:

- A **runtime error** after bootstrap may be intercepted by the observers and sent to Contacts/Flare.
- A **fatal error** may stop execution before the observers are registered.
- A **parse error before bootstrap** may prevent the code from loading and therefore never reach
  Ignition or the observers.

A successful runtime test does not prove handling of fatal errors or pre-bootstrap parse errors;
report only what was actually verified.
