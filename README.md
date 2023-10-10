green-symfony/command-bundle
========

## Description

This bundle provides:
| Class name | Description |
| ------------- | ------------- |
| AbstractCommand | The basic class which realizes the Symfony Command class |

### AbstractCommand Features

[AbstractCommand](https://github.com/green-symfony/command-bundle/blob/main/src/Command/AbstractCommand.php)

- See the "CONSTANTS CHANGE ME" section.
- See the "PUBLIC API" section for your services.
- See the "API" and "YOU CAN OVERRIDE IT" section for your extended commands.
- See the "REALIZED ABSTRACT" to make parent::METHOD() and add something new in the basic realization.

## Installation

### Step 1: Download the bundle

[Before git clone]("https://github.com/green-symfony/docs/blob/main/docs/bundles_green_symfony%20mkdir.md")

```console
git clone "https://github.com/green-symfony/command-bundle.git"
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
	}
]
```

Open your console into your main project directory and execute:

```console
composer require "green-symfony/command-bundle"
```

### Step 4: Extend the AbstractCommand in your Command