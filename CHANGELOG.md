# Oihana PHP Commands - OpenSource library - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added

- **Send a heartbeat to a monitoring tool** (`oihana\commands\heartbeat`). A machine where nothing
  runs any more — stopped, its scheduler down, its crontab emptied — fails at nothing and stays
  silent; a tool on another machine (Uptime Kuma, Healthchecks…) waits for a call on a push address
  and alerts when the calls stop. `HeartbeatCommand <signal>` makes that call: its signals are
  names → push addresses, called as they are, through the PSR-18 client and the PSR-17 request
  factory it is given. No address: nothing is done.

  The call watches the tool the other way round: when the tool does not answer — a timeout, a
  refused connection, a status out of 2xx, or a JSON body whose `ok` is `false` (how Uptime Kuma
  refuses a push) —, the command ends on `1`, so the job that runs it can alert: once when the
  failure starts, then once every `alertEvery` seconds (3600 by default) at most; the passes in
  between end on `0`. `HeartbeatState` remembers the failure in a small JSON file per signal of a
  state directory; without one, every failed pass alerts. A signal that is not a name, or a missing
  client or factory, ends on `2`. **A push address is never printed**: only its scheme, host and
  port; in an error message the address and its last segment — the token — become `***`.
  `HeartbeatParam` holds the initialization keys. New dependencies: `psr/http-client` and
  `psr/http-factory` (interfaces only).
- **Keep a crontab in line with the jobs an application declares** (`oihana\commands\cron`).
  An application declares its jobs — a name, a schedule, a command, enabled or not, a log — and
  `CronCommand` makes the crontab of the current user hold them:
  - `list` shows each job, its line in the crontab, and what `sync` would do to it ;
  - `sync` shows the changes the crontab needs and **writes nothing**; `sync --apply` writes them ;
  - `sync --check` writes nothing either and ends on `1` when the crontab is not up to date, so a
    script — a deployment, a health check — can tell whether it still matches the declarations ;
  - `enable <name>`, `disable <name>` (un)comment the line of a job, `remove <name>` takes it out —
    until the next `sync`, when the declaration says otherwise, and the command says so.

  A managed line ends with a marker, `# oihana-cron:<scope>:<name>`. **A line without a marker, or
  with the marker of another scope, is never touched**: comments, `PATH=` and the other tools keep
  their lines, in their place; two instances of an application on one machine (`myapp-production`,
  `myapp-staging`) share the crontab of their user without seeing each other's lines. A new job
  goes after the last line of its scope; a disabled job keeps its line, commented out.

  Before each write the crontab is copied into a dated file of a backup directory (mode `0600`,
  the 10 most recent kept). The writing actions share one lock, whatever the scope. A wrong
  declaration does not stop the console from starting: the command says what is wrong when it runs.

  The pieces:
  - `CronJob` — a job, checked when built: an identifier for a name, a schedule `CronSchedule`
    accepts, a command on one line without an unescaped `%` (cron turns it into a new line), an
    absolute log path ;
  - `CronSchedule::isValid()` — five fields or a macro (`@daily`…), with ranges, lists, steps, and
    month and day names; a step on a single value (`5/10`) and a backwards range (`5-1`) are
    refused, since cron daemons read them differently ;
  - `CronLine` — the marker, the scope and name a line belongs to, and its state ;
  - `CrontabPlan` — what a crontab must become, and the changes (`CronChange`): added, updated,
    enabled, disabled, removed ;
  - `Crontab` — reads (`crontab -l`; no crontab is an empty one, any other failure raises), writes
    (a temporary file given to `crontab`, always removed) and copies the crontab ;
  - `CronAction` and `CronParam` — the actions and the initialization keys of `CronCommand`.

- `runProcess()` — runs a process and reads both of its outputs **at the same time**
  (`stream_select()`), with an optional time limit. When the limit runs out, the process **and
  every process it started** get SIGTERM, then SIGKILL after a grace period (`$grace`, 5 s by
  default); the result says `timedOut`, with the status 124 of coreutils' `timeout`. Standard
  input is `/dev/null`, so a process that asks a question reads an end of file instead of waiting
  forever. `$onOutput` receives standard output in chunks, for an output too big to hold in
  memory. A failing command does not raise; a process that cannot start does, naming its program
  and never its arguments.
- `processTable()` — the parent and the state of every process, from `ps -A -o pid= -o ppid= -o
  stat=` (same output on Linux and macOS), or from text given in that format.
- `processDescendants()` — the descendants of a process, the deepest first. A table read while
  processes come and go can hold a loop; the walk still ends.
- `terminateProcessTree()` — SIGTERM to a process and all its descendants, listed before the
  first signal, then SIGKILL to those still running after the grace period. A zombie counts as
  stopped. Through `posix_kill()`, or the `kill` command without the posix extension.
- `Process::$timedOut` and `Process::$seconds`, with the `TIMED_OUT` and `SECONDS` constants;
  `toArray()` and `toJson()` include them.
- `CommandTrait::proc()` takes a time limit, `$timeout` (seconds, `null` for none).

- `EncryptTrait::resolveEncrypt()` — resolves the encryption flag from the console input **and** the
  caller's own configuration: the `--encrypt` option wins when it was given, otherwise the value the
  caller declares (e.g. a `[backup] encrypt` or `[archive] encrypt` setting), otherwise the inherited
  `$encrypt` default. A configuration declaring nothing (`null`) keeps that default instead of silently
  turning encryption off.

- Continuous integration: GitHub Actions `ci.yml` (PHPUnit on PHP 8.4) and `docs.yml` (phpDocumentor build + GitHub Pages deploy) workflows.
- Coverage tooling: the composer `coverage` and `coverage:md` scripts plus `tools/clover-to-markdown.php`, producing a Clover/HTML report and a Markdown summary under `build/coverage/`.
- `CONTRIBUTING.md` — setup, tests and coverage instructions, matching the other `oihana/php-*` libraries.

### Fixed

- `CommandTrait::proc()` no longer blocks on a command with a lot to say on its error output. It
  read standard output to its end, then the error output: past about 64 KB on the error pipe, the
  command waited for that pipe to be read, and `proc()` for the end of standard output — both for
  ever. It now runs through `runProcess()`, which reads both at once.

- `LifecycleTrait::endCommand()` shows the whole closing duration. `Helper::formatTime()` keeps a
  single unit by default and drops the rest, so a command that ran 1 min 47 s ended on "Done in
  1 min", one of 59 min 59 s on "Done in 59 min" — right under a progress bar reading "1 min, 47 s".
  The duration now reads like that progress bar: two units on whole seconds (`1 min, 47 s`, `5 s`),
  milliseconds only under one second (`400 ms`). The precision is the new
  `LifecycleTrait::DURATION_PRECISION` constant (`2`).

- `EncryptTrait::shouldEncrypt()` no longer throws when the command does not declare the
  `--encrypt` option. It now delegates to `resolveEncrypt()`, which checks `hasOption()` first
  — as `PassphraseTrait` already did — and falls back to the `$encrypt` default. The option
  value is also cast to `bool`, so a string-valued option resolves like every other flag.

- `ChownTrait::chown()` honours `strict: false` on a path that does not exist. Its contract
  promised a warning and `ExitCode::SUCCESS` when a required value was missing, but only an
  empty path was ever checked: for a path absent from disk, `getOwnershipInfos()` raised
  `Path '…' does not exist.` however `strict` was set, so a caller asking for a best-effort
  chown got an exception instead of the documented no-op. The case matters where it is most
  natural to ask — chowning a directory a following step is about to create.

  A missing path and a non-existent one are now reported apart. Naming the path in the second
  message says which of the two happened without reading the caller.

- `ChownOptionsTest` takes the quoting of an option value from `formatOptionValue()` instead of
  holding it as a literal. `Options::getOptions()` (oihana/php-files) used to write a string with
  `json_encode()` — `--from="root:root"` — and now writes it with `escapeShellArgument()` —
  `--from='root:root'`. The test expected the double-quoted form, so it failed on the GNU branch
  (the Linux CI) while staying green on macOS, where `--from` and `--reference` are excluded from
  the fragment. The expected text now goes through the same helper as the option string, so the
  quoting of the library is no longer written down twice.

### Changed

- **The tests run on PHPUnit 13** (`^13`, was `^12`). `phpunit.xml` speaks the 13.4 schema:
  `recordTestRunHistory="false"` replaces `cacheResult="false"`, and `resolveDependencies="true"`
  replaces `executionOrder="depends,defects"`, removed in PHPUnit 14. The container mocks of
  `InflectorTraitTest` declare how often they are called, as PHPUnit 14 will require.

- `CommandTrait::proc()` gives its command `/dev/null` as standard input (it was an unused pipe),
  and the message of a command that cannot start names its program only:
  ``Failed to execute the command `mysql`.`` The full command line could carry a password.
  `proc()` no longer calls `proc_open()` itself, so a test that replaced `proc_open()` in the
  `oihana\commands\traits` namespace no longer reaches it.

- `.gitignore`: ignore the whole `build/` directory and the generated phpDocumentor `docs/` output; the previously-committed generated `docs/` files are no longer tracked (the Docs workflow rebuilds and deploys them to GitHub Pages).

## [1.0.4] - 2026-06-21

### Added

- `oihana\commands\exceptions\MissingPassphraseException` — thrown when a required passphrase is missing.

- `oihana\commands\helpers\format` — wraps a message in Symfony Console color/style tags, with the ready-made shortcuts `comment` (magenta), `error` (red), `info` (cyan) and `warning` (yellow).

- `oihana\commands\traits\ChainedCommandsTrait` — run arrays of commands or callables before and after a Symfony Console command (before/after chaining).

- `oihana\commands\styles\OutputStyle` — abstract base wrapping Symfony Console's `OutputInterface`, a unified surface for building custom console output styles.
- `oihana\commands\styles\JsonStyle` — an `OutputStyle` that renders PHP data structures as syntax-highlighted (colorized) JSON in the console.

### Changed

- Dependencies: replaced `oihana/php-system` with the focused `oihana/php-logging` and `oihana/php-traits` packages (namespaces `oihana\logging` and `oihana\traits`). Drops the heavy Slim/Twig/Symfony stack that `php-system` pulled in.
- `Kernel`: the relocated `DateTrait` is now imported from `oihana\traits\DateTrait` instead of `oihana\date\traits\DateTrait` (moved out of `php-system` into `php-traits`). Behaviour unchanged.

## [1.0.3] - 2025-08-13

### Added
- use the Options and Option classes

## [1.0.2] - 2025-08-13

### Added
- use oihana-php-reflect

## [1.0.1] - 2025-08-12

### Removed
- Remove the Nginx and Certbot dependencies in the CommandOption class. 

## [1.0.0] - 2025-08-12

### Added

#### Foundation of the Oihana PHP Commands library:
Symfony Console integration with a Kernel base command and PSR-3 logging. 
  
#### Options system

  - CommandOption, 
  - CommandOptions, 
  - ServerOptions, 
  - SudoCommandOptions, 
  - ChownOptions

#### Reusable command traits: 

- CommandTrait
- ConsoleLoggerTrait 
- DateTrait 
- FileTrait
- HelperTrait
- IDTrait
- InflectorTrait 
- JsonOptionsTrait
- LifecycleTrait 
- ServerTrait
- SudoTrait
- UITrait

#### Helpers

  - assertDomain() 
  - clearConsole() 
  - domainExists() 
  - escapeForPrintf() 
  - makeCommand() 
  - silent() for non-interactive mode
 
#### Enums and constants for CLI integration:

- CommandParam 
- ExitCode
- BrewCommands, 
- SystemCTLCommands

#### Process utility to execute system commands with I/O management.
- Documentation generation via phpDocumentor and a published docs site.
- Unit tests for file operations (FileTrait) and helpers (silent).

