<!-- markdownlint-disable MD041 -->
<p align="center">
    <a href="https://github.com/php-forge/vite" target="_blank">
      <img src="https://avatars.githubusercontent.com/u/103309199?s=400&u=ca3561c692f53ed7eb290d3bb226a2828741606f&v=4" width="30%" alt="PHP Forge">
    </a>
    <h1 align="center">Vite</h1>
    <br>
</p>
<!-- markdownlint-enable MD041 -->

<p align="center">
    <a href="https://github.com/php-forge/vite/actions/workflows/build.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/php-forge/vite/build.yml?style=for-the-badge&label=PHPUnit&logo=github" alt="PHPUnit">
    </a>
    <a href="https://dashboard.stryker-mutator.io/reports/github.com/php-forge/vite/main" target="_blank">
        <img src="https://img.shields.io/endpoint?style=for-the-badge&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fphp-forge%2Fvite%2Fmain" alt="Mutation Testing">
    </a>
    <a href="https://github.com/php-forge/vite/actions/workflows/ecs.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/php-forge/vite/ecs.yml?style=for-the-badge&label=ECS&logo=github" alt="Easy Coding Standard">
    </a>
    <a href="https://github.com/php-forge/vite/actions/workflows/security.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/php-forge/vite/security.yml?style=for-the-badge&label=Security&logo=github" alt="Security">
    </a>
</p>

<p align="center">
    <strong>A framework-agnostic PHP integration for resolving Vite development and production assets.</strong>
</p>

## Features

<picture>
    <source media="(min-width: 768px)" srcset="./docs/svgs/features.svg">
    <img src="./docs/svgs/features-mobile.svg" alt="Feature overview" style="width: 100%;">
</picture>

## Installation

```bash
composer require php-forge/vite:^0.1
```

HTML output is generated with [`ui-awesome/html`](https://github.com/ui-awesome/html) while asset resolution remains
independent from its representation.

The consuming application owns Vite and every JavaScript dependency. Configure Vite to write a build manifest:

```js
import {defineConfig} from 'vite';

export default defineConfig({
    build: {
        manifest: true,
    },
});
```

## Quick start

### Development

```php
use PHPForge\Vite\Configuration\DevelopmentConfiguration;
use PHPForge\Vite\Html\HtmlRenderer;
use PHPForge\Vite\Vite;

$vite = new Vite(
    new DevelopmentConfiguration(
        devServerUrl: 'http://localhost:5173',
    ),
    entrypoints: ['resources/js/app.js'],
);

echo (new HtmlRenderer())->render($vite->resolve());
```

### Production

```php
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Html\HtmlRenderer;
use PHPForge\Vite\Vite;

$vite = new Vite(
    new ProductionConfiguration(
        manifestPath: '/srv/app/public/build/.vite/manifest.json',
        assetBaseUrl: '/build',
    ),
    entrypoints: ['resources/js/app.js'],
);

echo (new HtmlRenderer())->render($vite->resolve());
```

## Documentation

- [Installation guide](docs/installation.md)
- [Configuration reference](docs/configuration.md)
- [Manifest resolution](docs/manifest.md)
- [Usage examples](docs/examples.md)
- [Security and CSP](docs/security.md)
- [Testing guide](docs/testing.md)

## Package information

[![PHP](https://img.shields.io/badge/%3E%3D8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/releases/8.3/en.php)
[![Latest Stable Version](https://img.shields.io/packagist/v/php-forge/vite.svg?style=for-the-badge&logo=packagist&logoColor=white&label=Stable)](https://packagist.org/packages/php-forge/vite)
[![Total Downloads](https://img.shields.io/packagist/dt/php-forge/vite.svg?style=for-the-badge&logo=composer&logoColor=white&label=Downloads)](https://packagist.org/packages/php-forge/vite)

## Code quality

[![Codecov](https://img.shields.io/codecov/c/github/php-forge/vite.svg?style=for-the-badge&logo=codecov&logoColor=white&label=Coverage)](https://codecov.io/gh/php-forge/vite)
[![PHPStan Level Max](https://img.shields.io/badge/PHPStan-Level%20Max-4F5D95.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.com/php-forge/vite/actions/workflows/static.yml)
[![Quality](https://img.shields.io/github/actions/workflow/status/php-forge/vite/quality.yml?style=for-the-badge&label=Quality&logo=github)](https://github.com/php-forge/vite/actions/workflows/quality.yml)
[![Dependency Check](https://img.shields.io/github/actions/workflow/status/php-forge/vite/dependency-check.yml?style=for-the-badge&label=Dependency%20Check&logo=github)](https://github.com/php-forge/vite/actions/workflows/dependency-check.yml)

## Social networks

[![Follow on X](https://img.shields.io/badge/-Follow%20on%20X-1DA1F2.svg?style=for-the-badge&logo=x&logoColor=white&labelColor=000000)](https://x.com/Terabytesoftw)

## License

[![License](https://img.shields.io/badge/License-BSD--3--Clause-brightgreen.svg?style=for-the-badge&logo=opensourceinitiative&logoColor=white&labelColor=555555)](LICENSE)
