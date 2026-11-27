# Oihana PHP Commands - OpenSource library - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added

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

