# Usage examples

## Matching Vite build configuration

Every example below targets Vite 5 or later and assumes the application-owned `vite.config.js` from
[Installation](installation.md), which writes `<project-root>/public/build/.vite/manifest.json`. Each example resolves
that same file through the path mechanism of its application or framework.

## Plain PHP

Select one immutable configuration at the application's composition root:

```php
use PHPForge\Vite\Configuration\DevelopmentConfiguration;
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Html\HtmlRenderer;
use PHPForge\Vite\Vite;

$configuration = $isDevelopment
    ? DevelopmentConfiguration::create(
        devServerUrl: 'http://localhost:5173',
    )
    : ProductionConfiguration::create(
        manifestPath: __DIR__ . '/public/build/.vite/manifest.json',
        assetBaseUrl: '/build',
    );

$vite = Vite::create($configuration, entrypoints: ['resources/js/app.js']);

$assets = $vite->resolve();

echo HtmlRenderer::create()->render($assets);
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

Register the facade as a component so Yii2 owns its lazy construction. `__construct()` is Yii2 container syntax and its
values reach the framework-independent constructor unchanged; it is the same `$configuration` built above. The package never
touches `Yii::getAlias()`, the service locator, or `yii\web\View`:

```php
$config['components']['vite'] = [
    'class' => PHPForge\Vite\Vite::class,
    '__construct()' => [
        'configuration' => $configuration,
        'entrypoints' => ['resources/js/app.js'],
    ],
];

echo HtmlRenderer::create()->render(Yii::$app->get('vite')->resolve());
```

## Yii3 integration

Resolve the framework aliases in the application's dependency-injection configuration, then inject the same `Vite` class:

```php
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Vite;
use Yiisoft\Aliases\Aliases;

static function (Aliases $aliases): Vite {
    return Vite::create(
        ProductionConfiguration::create(
            manifestPath: $aliases->get('@public/build/.vite/manifest.json'),
            assetBaseUrl: '/build',
        ),
        entrypoints: ['resources/js/app.js'],
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
use PHPForge\Vite\Vite;

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

$configuration = DevelopmentConfiguration::create(
    devServerUrl: 'http://localhost:5173',
    inlineModuleProviders: [new ReactRefreshPreamble()],
);

$vite = Vite::create($configuration, entrypoints: ['resources/js/app.jsx']);
```

Providers run in their configured order before `@vite/client` and the entrypoint scripts. The application owns the provider
code and the matching Vite plugin dependency.

---

[← Back to documentation](../README.md#documentation)
