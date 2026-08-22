# Usage examples

## Matching Vite build configuration

The plain PHP, Yii2, and Yii3 production examples below target Vite 5 or later and assume this application-owned
configuration:

```js
import {defineConfig} from 'vite';

export default defineConfig({
    build: {
        outDir: 'public/build',
        manifest: '.vite/manifest.json',
        rollupOptions: {
            input: 'resources/js/app.js',
        },
    },
});
```

Because `build.manifest` is relative to `build.outDir`, this configuration writes
`<project-root>/public/build/.vite/manifest.json`. Each PHP example resolves that same file through the path mechanism of
its application or framework.

## Plain PHP

Select one immutable configuration at the application's composition root:

```php
use PHPForge\Vite\Configuration\DevelopmentConfiguration;
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Html\HtmlRenderer;
use PHPForge\Vite\Vite;

$configuration = $isDevelopment
    ? new DevelopmentConfiguration(
        devServerUrl: 'http://localhost:5173',
        entrypoints: ['resources/js/app.js'],
    )
    : new ProductionConfiguration(
        manifestPath: __DIR__ . '/public/build/.vite/manifest.json',
        assetBaseUrl: '/build',
        entrypoints: ['resources/js/app.js'],
    );

$vite = new Vite($configuration);

$assets = $vite->resolve();

echo (new HtmlRenderer())->render($assets);
```

The production example assumes `__DIR__` is the absolute project root used by the matching Vite configuration.

## Consume neutral assets

Applications may skip `HtmlRenderer` and integrate the neutral model with their own response, template, or asset system:

```php
use PHPForge\Vite\Asset\InlineModule;
use PHPForge\Vite\Asset\ModulePreload;
use PHPForge\Vite\Asset\ModuleScript;
use PHPForge\Vite\Asset\Stylesheet;

foreach ($vite->resolve() as $asset) {
    match (true) {
        $asset instanceof ModuleScript => $view->addModuleScript($asset->url),
        $asset instanceof Stylesheet => $view->addStylesheet($asset->url),
        $asset instanceof ModulePreload => $view->addModulePreload($asset->url),
        $asset instanceof InlineModule => $view->addInlineModule($asset->source),
    };
}
```

The example methods belong to the consuming application; they are not package APIs.

## Yii2 integration

Yii2 resolves aliases before constructing the framework-independent configuration. The package does not access
`Yii::getAlias()` or `yii\web\View`:

```php
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Html\HtmlRenderer;
use PHPForge\Vite\Vite;

$vite = new Vite(
    new ProductionConfiguration(
        manifestPath: Yii::getAlias('@webroot/build/.vite/manifest.json'),
        assetBaseUrl: Yii::getAlias('@web/build'),
        entrypoints: ['resources/js/app.js'],
    ),
);

echo (new HtmlRenderer())->render($vite->resolve());
```

Here, `@webroot` resolves to the `public` directory configured as the Vite output directory. The calls to Yii in this
example are entirely outside the package. A Yii2 application can instead register the configured `Vite` object in its
dependency-injection container and print the HTML from a view.

## Yii3 integration

Resolve the framework aliases in the application's dependency-injection configuration, then inject the same `Vite` class:

```php
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Vite;
use Yiisoft\Aliases\Aliases;

static function (Aliases $aliases): Vite {
    return new Vite(
        new ProductionConfiguration(
            manifestPath: $aliases->get('@public/build/.vite/manifest.json'),
            assetBaseUrl: '/build',
            entrypoints: ['resources/js/app.js'],
        ),
    );
};
```

Yii2 and Yii3 therefore share the package API; only the application's path-resolution and container wiring differ.
The `@public` alias must resolve to the `public` directory configured as `build.outDir`.

## Application-provided development preamble

React Refresh and comparable plugin preambles are application concerns. A project can provide a neutral inline module
without adding a React dependency to this package:

```php
use PHPForge\Vite\Asset\InlineModule;
use PHPForge\Vite\Configuration\DevelopmentConfiguration;
use PHPForge\Vite\Development\InlineModuleProviderInterface;

final class ReactRefreshPreamble implements InlineModuleProviderInterface
{
    public function provide(string $devServerUrl): InlineModule
    {
        $refreshUrl = json_encode($devServerUrl . '/@react-refresh', JSON_THROW_ON_ERROR);

        return new InlineModule(<<<JS
            import RefreshRuntime from {$refreshUrl};
            RefreshRuntime.injectIntoGlobalHook(window);
            window.\$RefreshReg\$ = () => {};
            window.\$RefreshSig\$ = () => type => type;
            window.__vite_plugin_react_preamble_installed__ = true;
            JS);
    }
}

$configuration = new DevelopmentConfiguration(
    devServerUrl: 'http://localhost:5173',
    entrypoints: ['resources/js/app.jsx'],
    inlineModuleProviders: [new ReactRefreshPreamble()],
);
```

Providers run in their configured order before `@vite/client` and the entrypoint scripts. The application owns the provider
code and the matching Vite plugin dependency.

## Optional Foxy usage

[`php-forge/foxy`](https://github.com/php-forge/foxy) may be used independently by a consuming project to coordinate its
Composer and JavaScript dependencies. It is not installed, invoked, or configured by this package.
