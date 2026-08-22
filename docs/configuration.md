# Configuration reference

Configuration objects are immutable and accept only resolved filesystem paths and URLs. Framework aliases such as
`@webroot`, `@web`, and `@app` are not recognized by this package.

## Development configuration

```php
use PHPForge\Vite\Configuration\DevelopmentConfiguration;

$configuration = new DevelopmentConfiguration(
    devServerUrl: 'http://localhost:5173',
    includeViteClient: true,
    inlineModuleProviders: [],
);
```

| Argument                | Type                                  | Default | Purpose                                                     |
| ----------------------- | ------------------------------------- | ------- | ----------------------------------------------------------- |
| `devServerUrl`          | `string`                              | none    | Absolute HTTP(S) Vite development-server URL.               |
| `includeViteClient`     | `bool`                                | `true`  | Adds the `@vite/client` module script.                      |
| `inlineModuleProviders` | `list<InlineModuleProviderInterface>` | `[]`    | Adds application-owned inline modules before Vite's client. |

The URL may include a path prefix, but not a query or fragment.

## Production configuration

```php
use PHPForge\Vite\Configuration\ProductionConfiguration;

$configuration = new ProductionConfiguration(
    manifestPath: '/srv/app/public/build/.vite/manifest.json',
    assetBaseUrl: '/build',
    modulePreload: true,
);
```

| Argument        | Type     | Default | Purpose                                                      |
| --------------- | -------- | ------- | ------------------------------------------------------------ |
| `manifestPath`  | `string` | none    | Concrete absolute filesystem path to Vite's client manifest. |
| `assetBaseUrl`  | `string` | none    | URL prefix joined to each manifest output path.              |
| `modulePreload` | `bool`   | `true`  | Emits neutral modulepreload assets for static JS imports.    |

`assetBaseUrl` may be empty, root-relative, path-relative, or an absolute HTTP(S) URL. Protocol-relative URLs, query
strings, fragments, and non-HTTP schemes are rejected.

## Resolve assets

```php
use PHPForge\Vite\Vite;

$vite = new Vite(
    configuration: $configuration,
    entrypoints: ['resources/js/app.js'],
);

$defaultAssets = $vite->resolve();
$pageAssets = $vite->resolve('resources/js/admin.js');
$combinedAssets = $vite->resolve(['resources/js/app.js', 'resources/js/admin.js']);
```

Default entrypoints belong to the facade because they apply equally to development and production. Duplicate entrypoints
are removed while preserving the first occurrence. At least one entrypoint must be available when `Vite::resolve()` is
called.

An explicit argument to `resolve()` replaces the facade's default entrypoints for that call. Entrypoint identifiers are
Vite manifest keys or development source paths, not filesystem paths.

## Manifest loading and cache

`ManifestLoader` validates a manifest on first use and caches the parsed representation by absolute path and file metadata.
It reloads the manifest after the file's inode, size, modification time, or change time changes.

For long-running processes that replace a manifest without changing observable metadata, explicitly clear the cache:

```php
$vite->clearManifestCache();
```

A shared loader can also clear one path or every cached manifest:

```php
use PHPForge\Vite\Manifest\ManifestLoader;

$loader = new ManifestLoader();
$loader->clear('/srv/app/public/build/.vite/manifest.json');
$loader->clear();
```

## Rendering configuration

Resolution does not produce HTML. Use `HtmlRenderer` only when the application wants package-provided markup:

```php
use PHPForge\Vite\Html\HtmlRenderer;
use PHPForge\Vite\Html\HtmlRenderOptions;

$html = (new HtmlRenderer())->render(
    $vite->resolve(),
    new HtmlRenderOptions(
        nonce: $nonce,
        moduleScriptAttributes: ['crossorigin' => true],
        stylesheetAttributes: ['media' => 'screen'],
    ),
);
```

`HtmlRenderer` maps the neutral asset objects to `ui-awesome/html` `Script` and `Link` elements. Applications that consume
`AssetCollection` directly do not depend on the renderer's markup structure.

See [Security and CSP](security.md) for the attribute and nonce policy.

## Neutral asset collection

`Vite::resolve()` returns an immutable `AssetCollection` containing these explicit asset types:

- `ModuleScript` with a public `url`;
- `Stylesheet` with a public `url`;
- `ModulePreload` with a public `url`;
- `InlineModule` with public application-owned JavaScript `source`.

The collection is countable and iterable. Use `all()`, `moduleScripts()`, `stylesheets()`, `modulePreloads()`, or
`inlineModules()` to obtain ordered lists. `append()` and `prepend()` return new deduplicated collections without mutating the
original instance.

## Exception hierarchy

Every package exception implements `PHPForge\Vite\Exception\ViteException`:

```text
ViteException
├── ConfigurationException
│   └── InvalidEntrypointException
├── HtmlRenderingException
└── ManifestException
    ├── EntrypointNotFoundException
    ├── InvalidManifestException
    ├── ManifestNotFoundException
    └── ManifestReadException
```

Configuration and rendering exceptions extend `InvalidArgumentException`. Manifest exceptions extend `RuntimeException`.
Callers can catch one specific failure, a category base class, or the common marker interface.
