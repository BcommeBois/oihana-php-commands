# Oihana PHP - Commands

An open-source PHP framework designed to create advanced command-line applications. Built on top of the Oihana PHP ecosystem, it provides ready-to-use tools to manage and automate common server and web application tasks.

## 📚 Documentation

Full project documentation is available at:  
👉 https://bcommebois.github.io/oihana-php-commands

## 📦 Installation

> **Requires [PHP 8.4+](https://php.net/releases/)**

Install via [Composer](https://getcomposer.org):
```bash
composer require oihana/php-commands
```

## ✨ Features

With it, you can:
- Manage MySQL databases — create users, grant privileges, perform backups, and restore.
- Automate database & web app backups — schedule and run incremental or full backups.
- Install & update applications — deploy, upgrade, and maintain web projects.
- Manage caches — clear and control systems like Memcached and Redis.
- Handle SSL certificates — automate Let’s Encrypt / Certbot renewal and installation.
- Configure NGINX servers — generate modular configuration files and reload services.
- And much more — from system maintenance scripts to environment setup.

The framework includes:
- A modular Symfony Console integration for creating custom CLI commands.
- Pre-built command modules for database, cache, web server, and certificate management.
- A flexible options system for easy configuration and automation.
- Extensible architecture for adding your own tasks and workflows.

Perfect for:
- DevOps automation
- Web hosting management
- Deployment pipelines
- Local and production environment setup

## ✅ Running Unit Tests

To run all tests:
```bash
composer run-script test
```

To run a specific test file:
```bash
composer run test ./tests/oihana/date/TimeIntervalTest.php
```

## 🧾 Licence

This project is licensed under the [Mozilla Public License 2.0 (MPL-2.0)](https://www.mozilla.org/en-US/MPL/2.0/).

## 👤 About the author

* Author : Marc ALCARAZ (aka eKameleon)
* Mail : marc@ooop.fr
* Website : http://www.ooop.fr

## 🛠️ Generate the Documentation

We use [phpDocumentor](https://phpdoc.org/) to generate the documentation into the ./docs folder.

### Usage
Run the command :
```bash
composer doc
```