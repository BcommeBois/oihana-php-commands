# Oihana PHP - Commands

![Oihana Php Core](https://raw.githubusercontent.com/BcommeBois/oihana-php-commands/main/assets/images/oihana-php-commands-logo-inline-512x160.png)

An open-source PHP framework designed to create advanced command-line applications. 

[![Latest Version](https://img.shields.io/packagist/v/oihana/php-commands.svg?style=flat-square)](https://packagist.org/packages/oihana/php-commands)  
[![Total Downloads](https://img.shields.io/packagist/dt/oihana/php-commands.svg?style=flat-square)](https://packagist.org/packages/oihana/php-commands)  
[![License](https://img.shields.io/packagist/l/oihana/php-commands.svg?style=flat-square)](LICENSE)

Built on top of the **Oihana PHP** ecosystem, it provides ready-to-use tools to manage and automate common server and web application tasks.

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

Discover the available plugins in the Oihana PHP Commands library. Each plugin extends CLI capabilities to easily manage different services.

### 🧠 Oihana PHP MemCached

The available plugins of the Oihana PHP Commands library are :

| 🔌 | Plugin                                                                     | Description                                                   | 
|----|----------------------------------------------------------------------------|---------------------------------------------------------------|
| 🧠 | [Oihana PHP MemCached](https://github.com/BcommeBois/oihana-php-memcached) | CLI commands to manage Memcached in-memory key-value caching. |
| 🤖 | [Oihana PHP Robots](https://github.com/BcommeBois/oihana-php-robots)       | Simple CLI tool to create or remove a robots.txt file.        |  

---

### 🧠 [Oihana PHP MemCached](https://github.com/BcommeBois/oihana-php-memcached)

<a href="https://github.com/BcommeBois/oihana-php-memcached">
    <img src="https://raw.githubusercontent.com/BcommeBois/oihana-php-memcached/main/assets/images/oihana-php-memcached-logo-inline-512x160.png" width="256px" height="80px"/>
</a>

A CLI plugin to control Memcached using the official [Memcached extension](https://www.php.net/manual/en/book.memcached.php) PHP extension.  
Memcached is an in-memory caching system that speeds up your applications by reducing database load.

🔗 [View repository](https://github.com/BcommeBois/oihana-php-memcached)

---

### 🤖 [Oihana PHP Robots](https://github.com/BcommeBois/oihana-php-robots)

<a href="https://github.com/BcommeBois/oihana-php-robots">
    <img src="https://raw.githubusercontent.com/BcommeBois/oihana-php-robots/main/assets/images/oihana-php-robots-logo-inline-512x160.png" width="256px" height="80px"/>
</a>

A simple CLI tool to easily create or remove a **robots.txt** file for your website.

🔗 [View repository](https://github.com/BcommeBois/oihana-php-robots)

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