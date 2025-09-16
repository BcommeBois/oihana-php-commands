# Oihana PHP Commands - OpenSource library - Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added

- oihana\commands\exceptions\MissingPassphraseException class

- oihana\commands\helpers\comment
- oihana\commands\helpers\error
- oihana\commands\helpers\format
- oihana\commands\helpers\info
- oihana\commands\helpers\warning
 
- oihana\commands\traits\ChainedCommandsTrait

- oihana\commands\style\JsonStyle class
- oihana\commands\style\OutputStyle class

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

