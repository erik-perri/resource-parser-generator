# Resource Parser Generator

## Command

```shell
php artisan build:resource-parsers [--check] [--enum-config=build.enums] [--parser-config=build.resources]
```

Generate resource parsers based on the specified configuration.

#### Options

##### `--check`

Checks if the generated files are up-to-date and exits with a non-zero exit code if they are not.

##### `--config`

The Laravel configuration path to load parsers from. Default: `build.resources`

## Configuration

`config/build.php`

```php
<?php

return [
    'enums' => [
        // Where to put the generated files. (Required for enum generation)
        'output_path' => dirname(__DIR__) . '/resources/scripts/generated/enums',
        // The enums to include or customize if used by parser.
        'sources' => [
            // No overrides, type and file name generated from class name
            new EnumConfiguration(\App\Enums\Permission::class),

            // Overriding options, all options but class are optional and generated if not specified
            new EnumConfiguration(
                \App\Enums\Permission::class,
                enumFile: 'permissions.ts',
                typeName: 'Permissions',
            )
        ],
    ],
    'resources' => [
        // Where to put the generated files. (Required for parser generation)
        'output_path' => dirname(__DIR__) . '/resources/scripts/generated/parsers',
        // The parsers to include.
        'sources' => [
            // No overrides, parser name and file name generated from class and method names
            new ParserConfiguration([\App\Http\Resources\UserResource::class, 'base']),

            // Overriding options, all options but class and method are optional and generated if not specified
            new ParserConfiguration(
                [\App\Http\Resources\AnotherResource::class, 'base'],
                parserFile: 'custom.ts',
                typeName: 'CustomParser',
                variableName: 'customParser',
            ),
            
            // Search for all resources in this path, generate using generated parser and file names
            new ResourcePath(dirname(__DIR__) . '/app/Http/Resources'),
        ],
    ],
];
```
