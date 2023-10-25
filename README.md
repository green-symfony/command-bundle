green-symfony/command-bundle
========

# Description


This bundle provides:
| Class name | Description |
| ------------- | ------------- |
| [AbstractCommand](https://github.com/green-symfony/command-bundle/blob/main/src/Command/AbstractCommand.php) | The basic class which realizes the Symfony Command class |
| [Traits](https://github.com/green-symfony/command-bundle/tree/main/src/Trait) | For users' options and abstactions |

## AbstractCommand

- See the "CONSTANTS CHANGE ME" section.
- See the "PUBLIC API" section for your services.
- See the "API" and "YOU CAN OVERRIDE IT" section for your extended commands.
- See the "REALIZED ABSTRACT" to make parent::METHOD() and add something new in the basic realization.

### Translations

For several functions you can add your own translations:

| Functions | Extra information |
| ------------- | ------------- |
| AbstractCommand::getInfoDescription() |  |
| AbstractCommand::exit() |  |
| AbstractCommand::isOk() | Should always be in English |

For the "ru" locale add your translations into the directory:
`%kernel.project_dir%/translations/GS/Command/messages.ru.yaml`

### Progress Bar

Into your command your can use [Progress Bar](https://symfony.com/doc/current/components/console/helpers/progressbar.html)

When you have the known max steps use into your command `$this->setMaxSteps()` method:
```php
$this->progressBar->setMaxSteps(KNOWN_MAX_STEPS);
$this->progressBar->start();
```

### Initial state of the AbstractCommand

| AbstractCommand state | Description |
| ------------- | ------------- |
| $this->initialCwd | `\getcwd()` |

## Traits

| Trait | Description | Code |
| ------------- | ------------- | ------------- |
| AskAbleTrait | Adds option which allows user to choose whether to ask him or not. | [Code](https://github.com/green-symfony/command-bundle/blob/main/src/Trait/AskAbleTrait.php) |
| DepthAbleTrait | Adds option which allows user to indicate depth. |[Code](https://github.com/green-symfony/command-bundle/blob/main/src/Trait/DepthAbleTrait.php) |
| DumpInfoAbleTrait | Adds option which allows user to dump information or not. [\GS\Service\Service\DumpInfoService::dumpInfo()](https://github.com/green-symfony/service-bundle/blob/main/src/Service/DumpInfoService.php) from the other bundle relies on `DepthAbleTrait::isDumpInfo()` method before the dump but it's not crucial. | [Code](https://github.com/green-symfony/command-bundle/blob/main/src/Trait/DumpInfoAbleTrait.php) |
| MakeLockAbleTrait | Adds option which allows user to choose whether to lock or not. | [Code](https://github.com/green-symfony/command-bundle/blob/main/src/Trait/MakeLockAbleTrait.php) |
| MoveAbleTrait | Adds option which allows user to choose whether to move or not. | [Code](https://github.com/green-symfony/command-bundle/blob/main/src/Trait/MoveAbleTrait.php) |
| OverrideAbleTrait | Adds option which allows user to choose whether to override or not. | [Code](https://github.com/green-symfony/command-bundle/blob/main/src/Trait/OverrideAbleTrait.php) |
| AbstractConstructedFromToCommandTrait | Abstraction for doing something with the constructed files. | [Code](https://github.com/green-symfony/command-bundle/blob/main/src/Trait/AbstractConstructedFromToCommandTrait.php) |
| AbstractPatternAbleCommandTrait | Abstraction for processing the passed pattern. | [Code](https://github.com/green-symfony/command-bundle/blob/main/src/Trait/AbstractPatternAbleCommandTrait.php) |

# Installation


### Step 1: Download the bundle

[Before git clone](https://github.com/green-symfony/docs/blob/main/docs/bundles_green_symfony%20mkdir.md)

```console
git clone "https://github.com/green-symfony/command-bundle.git"
```

```console
git clone "https://github.com/green-symfony/service-bundle.git"
```

### Step 2: Require the bundle

In your `%kernel.project_dir%/composer.json`

```json
"require": {
	"green-symfony/command-bundle": "VERSION"
},
"repositories": [
	{
		"type": "path",
		"url": "./bundles/green-symfony/command-bundle"
	},
	{
		"type": "path",
		"url": "./bundles/green-symfony/service-bundle"
	}
]
```

Open your console into your main project directory and execute:

```console
composer require "green-symfony/command-bundle"
```

[Binds](https://github.com/green-symfony/docs/blob/main/docs/borrow-services.yaml-section.md)

**Monolog customization**

In your `%kernel.project_dir%/config/packages/monolog.yaml`

```yaml
###> TODO: REALIZE IT IN YOUR monolog.yaml ###
when@dev:
    monolog:
        handlers:
            gs_command.dev_logger:
                type:           rotating_file
                max_files:      1
                path:           "%kernel.logs_dir%/gs_command_%kernel.environment%.log"
                level:          debug
                channels:       ["gs_command.dev_logger"]
###< TODO: REALIZE IT IN YOUR monolog.yaml ###
```

### Step 3: Extend the AbstractCommand in your Command