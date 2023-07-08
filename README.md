# Resource Parser Generator

## Command

```shell
php artisan build:resource-parsers [--check] [--config=build.resource_parsers]
```

Generate resource parsers based on the specified configuration.

#### Options

##### `--check`

Checks if the generated files are up-to-date and exits with a non-zero exit code if they are not.

##### `--config`

The Laravel configuration path to load parsers from. Default: `build.resource_parsers`

## Configuration

`config/build.php`

```php
<?php

return [
    'resource_parsers' => [
        // Where to put the generated files. (Required)
        'output_path' => dirname(__DIR__) . '/resources/scripts/generated',
        // The parsers to include. (Required)
        'sources' => [
            // No overrides, parser name and file name generated from class and method names
            new ResourceConfiguration([\App\Http\Resources\UserResource::class, 'base']),

            // Overriding options, all options but class and method are optional and generated if not specified
            new ResourceConfiguration(
                [\App\Http\Resources\AnotherResource::class, 'base'],
                parserFile: 'custom.ts',
                typeName: 'CustomParser',
                variableName: 'customParser',
            ),
        ],
    ],
];
```
