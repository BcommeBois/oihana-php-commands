# Oihana PHP Commands - OpenSource library - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

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

