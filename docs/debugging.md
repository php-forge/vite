# Vite diagnostics

## PSR-14, and nothing else

`Vite` emits `PHPForge\Vite\Event\AssetsResolved` through an optional `Psr\EventDispatcher\EventDispatcherInterface`.
The application classes never import a debug contract or call a collector, and installing the contracts does not
activate a debugger.

The event carries the configuration, the **actual per-call entrypoints**, the returned assets, and the manifest a
successful resolver already used. The collector never loads a manifest or runs an inline provider. Each successful
resolution becomes a component row; repeated calls are recorded in order, not deduplicated into one. Failed resolutions
do not invent completion events.

## Development wiring

The package ships one collector and one panel, `PHPForge\Vite\Debug\ViteCollector` and
`PHPForge\Vite\Debug\VitePanel`; the host never reimplements collection or presentation. What differs between
frameworks is only how the dispatcher reaches the `PHPForge\Vite\Vite` service.

### Yii3, one flag and no application code

`yii3/debug` registers the collector and panel behind a flag and attaches the collector as a listener; the container
autowires `Psr\EventDispatcher\EventDispatcherInterface` into `Vite`, so the application adds nothing else.

```php
return [
    'yii3/debug' => [
        'extensions' => [
            'vite' => true,
        ],
    ],
];
```

Enabling the flag without `php-forge/vite` installed fails with an explicit container error.

### Yii2, one registration

Yii2 has no framework-native PSR-14 dispatcher, and its DI container does not autowire optional constructor arguments.
`Vite` emits exactly one event type, so `ViteCollector` is its own single-listener dispatcher and the application
writes no PSR-14 code.

Inside the existing `YII_DEBUG` configuration guard:

```php
use PHPForge\Vite\Debug\{ViteCollector, VitePanel};

$viteCollector = new ViteCollector();

// Keep the component ID already used by the application.
$config['components']['vite']['__construct()']['eventDispatcher'] = $viteCollector;
$config['modules']['debug']['collectors']['vite'] = $viteCollector;
$config['modules']['debug']['panels']['vite'] = new VitePanel();
```

If the application already owns a real PSR-14 dispatcher, register the collector as a listener on it and inject that
dispatcher instead; never replace a populated dispatcher with an empty one.

`yii2-extensions/debug` no longer ships a Vite collector or panel, so the `vite` ID is free: the module wraps the
portable objects in its generic adapters and groups them under Extensions. Retain the existing module bootstrap,
routing, access rules, and asset configuration.

Inject the dispatcher into the **actual** Vite service, not into a duplicate diagnostic-only one. Omitting it is safe:
resolution behaves normally and the panel simply stays empty.

## Lifecycle and errors

Register the collector once and let the host drive it. `startup()` enables the listener without discarding current
observations if called twice; `shutdown()` disables it and clears references. Events outside that window are ignored.

An active but unused collector captures `['components' => []]`; disabled collection captures `null`.

Attach the event only to trusted listeners: it carries real application data, so never point an unrestricted event
dumper at it. Listener exceptions propagate per PSR-14; they are never swallowed and never retried. The supplied
listener only buffers the event.

## Compatibility

Hosts previously shipped their own Vite collector and panel; both were removed, so the `vite` ID is free for the
provider-owned objects an application registers itself. An extension now declares its own ID, icon and title. Captures
written by the removed host collector still render, because the payload shape is unchanged.

`eventDispatcher` is an optional trailing argument: calls without one behave normally and emit no events. The branch
aliases still describe an unreleased linked prototype, not a published release.

## How this is verified

- The full PHPUnit suite runs through this package's own autoloader, without Debug Core installed.
- `python3 tools/check-provider-consumer.py` (in `php-forge/debug`) exports the package and its locked production
  dependencies into a temporary mirror, installs with Packagist and plugins disabled, then exercises real resolution,
  cleanup and replay with no debugger or framework present.
- `DEBUG_UI_SEED_FIXTURES=0 npx playwright test e2e/provider-events.spec.js` (in Debug Core) checks persisted values
  after a changed request, including an empty Vite capture, accessibility, and both themes and viewport sizes.

Reference: [PSR-14](https://www.php-fig.org/psr/psr-14/).

---

[← Back to documentation](../README.md#documentation)
