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
composer require php-forge/vite:^0.4
```

The consuming application owns Vite and every JavaScript dependency. Production resolution needs Vite's build
manifest; see [Installation](docs/installation.md) for the `vite.config.js` settings and the resulting manifest path.

## Quick start

### Development

```php
use PHPForge\Vite\Configuration\DevelopmentConfiguration;
use PHPForge\Vite\Html\HtmlRenderer;
use PHPForge\Vite\Vite;

$vite = Vite::create(
    DevelopmentConfiguration::create(
        devServerUrl: 'http://localhost:5173',
    ),
    entrypoints: ['resources/js/app.js'],
);

echo HtmlRenderer::create()->render($vite->resolve());
```

### Production

```php
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Html\HtmlRenderer;
use PHPForge\Vite\Vite;

$vite = Vite::create(
    ProductionConfiguration::create(
        manifestPath: '/srv/app/public/build/.vite/manifest.json',
        assetBaseUrl: '/build',
    ),
    entrypoints: ['resources/js/app.js'],
);

echo HtmlRenderer::create()->render($vite->resolve());
```

## Debugger integration

`Vite::resolve()` emits `AssetsResolved` through an optional PSR-14 dispatcher. The asset resolution path never
imports a debug contract or calls a collector, so installing the package does not activate a debugger and a call
without a dispatcher emits no events. The collector and panel that do implement those contracts live apart, in
`PHPForge\Vite\Debug`, owned by this package.

See [Debugger integration](docs/debugging.md) for the Yii2 and Yii3 wiring. It remains an unreleased prototype.

<details>
<summary>Yii2</summary>
<picture>
    <source media="(prefers-color-scheme: dark)" srcset="docs/images/yii2-dark.png">
    <source media="(prefers-color-scheme: light)" srcset="docs/images/yii2-light.png">
    <img src="docs/images/yii2-light.png" alt="Vite panel in Yii2">
</picture>
</details>

<details>
<summary>Yii3</summary>
<picture>
    <source media="(prefers-color-scheme: dark)" srcset="docs/images/yii3-dark.png">
    <source media="(prefers-color-scheme: light)" srcset="docs/images/yii3-light.png">
    <img src="docs/images/yii3-light.png" alt="Vite panel in Yii3">
</picture>
</details>

## Documentation

- 📚 [Installation guide](docs/installation.md)
- ⚙️ [Configuration reference](docs/configuration.md)
- 🗂️ [Manifest resolution](docs/manifest.md)
- 💡 [Usage examples](docs/examples.md)
- 🛡️ [Security and CSP](docs/security.md)
- 🐞 [Debugger integration](docs/debugging.md)
- 🧪 [Testing guide](docs/testing.md)

## Package information

[![PHP](https://img.shields.io/badge/%3E%3D8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/releases/8.3/en.php)
[![Latest Stable Version](https://img.shields.io/packagist/v/php-forge/vite.svg?style=for-the-badge&logo=packagist&logoColor=white&label=Stable)](https://packagist.org/packages/php-forge/vite)
[![Total Downloads](https://img.shields.io/packagist/dt/php-forge/vite.svg?style=for-the-badge&logo=composer&logoColor=white&label=Downloads)](https://packagist.org/packages/php-forge/vite)

## Code quality

[![Codecov](https://img.shields.io/codecov/c/github/php-forge/vite.svg?style=for-the-badge&logo=codecov&logoColor=white&label=Coverage)](https://codecov.io/gh/php-forge/vite)
[![PHPStan Level Max](https://img.shields.io/badge/PHPStan-Level%20Max-4F5D95.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.com/php-forge/vite/actions/workflows/static.yml)
[![Quality](https://img.shields.io/github/actions/workflow/status/php-forge/vite/quality.yml?style=for-the-badge&label=Quality&logo=github)](https://github.com/php-forge/vite/actions/workflows/quality.yml)
[![StyleCI](https://img.shields.io/badge/StyleCI-Passed-44CC11.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.styleci.io/repos/1342863441?branch=main)

## Social networks

[![Follow on X](https://img.shields.io/badge/-Follow%20on%20X-1DA1F2.svg?style=for-the-badge&logo=x&logoColor=white&labelColor=000000)](https://x.com/Terabytesoftw)

## License

[![License](https://img.shields.io/badge/License-BSD--3--Clause-brightgreen.svg?style=for-the-badge&logo=opensourceinitiative&logoColor=white&labelColor=555555)](LICENSE)
