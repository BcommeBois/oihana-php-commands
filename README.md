# Oihana PHP - Commands

![Oihana Php Core](https://raw.githubusercontent.com/BcommeBois/oihana-php-commands/main/assets/images/oihana-php-commands-logo-inline-512x160.png)

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
- Manage MySQL databases — create users, grant privileges, run backups, and restore data.
- Automate backups — schedule and execute incremental or full backups for databases and web apps.
- Install & update applications — deploy, upgrade, and maintain web projects with ease.
- Control caches — clear and manage systems like Memcached and Redis.
- Handle SSL certificates — automate issuance and renewal with Let’s Encrypt / Certbot.
- Configure NGINX — generate modular server configs and reload services.
- …and much more, from system maintenance scripts to environment setup.

The framework includes:
- Modular Symfony Console integration for creating custom CLI commands.
- Pre-built command plugins for databases, caches, web servers, certificates, and more.
- Flexible options system for easy configuration and automation, based on [PSR-11 Container](https://www.php-fig.org/psr/psr-11/).
- Extensible architecture for adding your own tasks, workflows, and plugins.

Perfect for:
- DevOps automation
- Web hosting management
- Deployment pipelines
- Local and production environment setup

## 🔌 Plugins

### 🧠 Oihana PHP MemCached

The available plugins of the Oihana PHP Commands library are :

| 🔌 | Plugin               | Description                                   | 
|----|----------------------|-----------------------------------------------|
| 🧠 | Oihana PHP MemCached | Managing Memcached in-memory key-value store. |
| 🤖 | Oihana PHP Robots    | Create and remove a **robots.txt** file.      |  

---

### 🧠 Oihana PHP MemCached

This plugin provides CLI commands to control Memcached directly, using the PHP [Memcached extension](https://www.php.net/manual/en/book.memcached.php).

<a href="https://github.com/BcommeBois/oihana-php-robots">
    <img src="https://raw.githubusercontent.com/BcommeBois/oihana-php-memcached/main/assets/images/oihana-php-memcached-logo-inline-512x160.png" width="256px" height="80px"/>
</a>

Memcached is an in-memory key–value store that accelerates dynamic web applications by caching data in RAM, reducing database load and latency.

---

### 🤖 Oihana PHP Robots

A simple CLI tool to create or remove a **robots.txt** file for your website.

<a href="https://github.com/BcommeBois/oihana-php-robots">
    <img src="https://raw.githubusercontent.com/BcommeBois/oihana-php-robots/main/assets/images/oihana-php-robots-logo-inline-512x160.png" width="256px" height="80px"/>
</a>

---
## ✅ Running Unit Tests

To run all tests:
```bash
composer test
```

To run a specific test file:
```bash
composer test ./tests/oihana/commands/ProcessTest.php
```

## 🧾 License

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