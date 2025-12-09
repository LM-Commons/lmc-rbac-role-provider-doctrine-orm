# Doctrine ORM Role Provider for LmcRbac

[![Build](https://github.com/lm-commons/lmc-rbac-role-provider-doctrince-orm/actions/workflows/build_test.yml/badge.svg)](https://github.com/lm-commons/lmc-rbac-role-provider-doctrince-orm/actions/workflows/build_test.yml)
[![Version](https://poser.pugx.org/lm-commons/lmc-rbac-role-provider-doctrine-orm/v)](https://packagist.org/packages/lm-commons/lmc-rbac-role-provider-doctrine-orm)
[![Total Downloads](https://poser.pugx.org/lm-commons/lmc-rbac-role-provider-doctrine-orm/downloads)](//packagist.org/packages/lm-commons/lmc-rbac-role-provider-doctrine-orm)
[![License](https://poser.pugx.org/lm-commons/lmc-rbac-role-provider-doctrine-orm/license)](https://packagist.org/packages/lm-commons/)

![Dynamic JSON Badge](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fapi.github.com%2Frepos%2Flm-commons%2Flmc-rbac-role-provider-doctrine-orm%2Fproperties%2Fvalues&query=%24%5B%3A1%5D.value&label=Maintenance%20Status)

Doctrine ORM Role Provider for LmcRbac.

## Requirements

- PHP 8.2 or higher
- LmcRbac 2.0 or higher
- Doctrine ORM 3.0 or higher

## Installation

LmcRbacRoleProviderDoctrineORM only officially supports installation through Composer.

Install the package:

```sh
$ php composer.phar require lm-commons/lmc-rbac-role-provider-doctrine-orm
```

Enable the module by adding `LmcRbac` key to your `application.config.php` file.
Customize the module by copy-pasting the `config.global.php` file to your
`config/autoload` folder.

You can also find some Doctrine entities in the [/data](/data) folder that will
help you to more quickly take advantage of LmcRbac.

## Support

- File issues at on [github](https://github.com/LM-Commons/lmc-rbac-role-provider-doctrine-orm/issues).
- Ask questions on [the LM-Commons Discord](https://discord.gg/nAAu7AhR).
