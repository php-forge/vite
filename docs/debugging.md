# Transparent Vite and Inertia diagnostics

## One strategy for both providers: PSR-14

Vite and Inertia emit domain events through an optional `Psr\EventDispatcher\EventDispatcherInterface`. Their application
classes never import the debug contracts or call a collector. The development configuration attaches the provider-owned
collector as a listener and registers it with the host lifecycle. Installing contracts does not activate a debugger.

Events beat logging here: resolved props, URLs and negotiation metadata must not reach the ordinary application log.

- `PHPForge\Vite\Event\AssetsResolved` contains the configuration, **actual per-call entrypoints**, returned assets,
  and the manifest already used by a successful resolver. The collector never loads a manifest or executes an inline
  provider. Each successful resolution becomes a component row; repeated calls are recorded in order, not deduplicated
  as though they were a single resolution. Failed resolutions do not invent completion events.
- `PHPForge\Inertia\Event\ProtocolResultCreated` contains the validated request context and the returned protocol
  result. The collector retains the latest result of the active request, without resolving props or serializing them
  at dispatch. Pages, version conflicts, external locations, fragment redirects, and ordinary redirects are distinct.
  Its `resultType` field prevents navigation responses with status 409 from being mislabeled as version conflicts.

Inertia captures the **protocol result**, not a later middleware-modified HTTP response. Inspect the host's Request
panel for the final HTTP status. The Yii3 adapter currently performs a preliminary protocol page call before the final
page call; the last result replaces the preliminary one. Neither invocation is repeated by the debugger.

## Development wiring

Both providers ship one collector and one panel each, owned by the provider package. The host never reimplements
collection or presentation. What differs between the frameworks is only how the PSR-14 dispatcher reaches the
`PHPForge\Vite\Vite` and `PHPForge\Inertia\Protocol` services.

### Yii3 — one flag, no application code

`yii3/debug` wires everything behind the flags it already exposed. Enabling a flag registers the provider-owned
collector and panel and attaches the collector as a listener; the container autowires its
`Psr\EventDispatcher\EventDispatcherInterface` into `Vite` and `Protocol`, so the application adds nothing else.

```php
return [
    'yii3/debug' => [
        'extensions' => [
            'inertia' => true,
            'vite' => true,
        ],
    ],
];
```

Enabling a flag without the corresponding provider package installed fails with an explicit container error. The
Inertia collector receives the host `CapturePolicy` redaction callbacks automatically.

### Yii2 — one dispatcher, two registrations per provider

Yii2 has no framework-native PSR-14 dispatcher, and its DI container does not autowire optional constructor
arguments. Each service here emits exactly one event type, so the collector is its own single-listener dispatcher and
the application writes no PSR-14 code.

Inside the existing `YII_DEBUG` configuration guard:

```php
use PHPForge\Inertia\Debug\{InertiaCollector, InertiaPanel};
use PHPForge\Inertia\Protocol;
use PHPForge\Vite\Debug\{ViteCollector, VitePanel};

$viteCollector = new ViteCollector();
$inertiaCollector = new InertiaCollector();

// Keep the component IDs already used by the application.
$config['components']['vite']['__construct()']['eventDispatcher'] = $viteCollector;
$config['components']['inertia']['protocol'] = Protocol::create(eventDispatcher: $inertiaCollector);
$config['modules']['debug']['collectors'] = ['vite' => $viteCollector, 'inertia' => $inertiaCollector];
$config['modules']['debug']['panels'] = ['vite' => new VitePanel(), 'inertia' => new InertiaPanel()];
```

If the application already owns a real PSR-14 dispatcher, register the collectors as listeners on it and inject that
dispatcher instead — never replace a populated dispatcher with an empty one.

`yii2-extensions/debug` no longer ships Vite or Inertia collectors and panels, so these IDs are free: the module wraps
the portable objects in its generic adapters and groups them under Extensions without a catalog entry. Pass
`$policy->redact(...)` and `$policy->redactUrl(...)` to `InertiaCollector` to apply the host redaction rules; the
default keeps the captured values unchanged. Retain the existing module bootstrap, routing, access rules, and asset
configuration.

Inject the dispatcher into the **actual** Vite and Protocol services, not into duplicate diagnostic-only ones.
Omitting it is safe: the services behave normally and the panel simply stays empty.

## Lifecycle, privacy, and errors

Register each collector once and let the host drive it. `startup()` enables the listener without discarding current
observations if called twice; `shutdown()` disables it and clears references. Events outside that window are ignored.

An active but unused Vite collector captures `['components' => []]`. An Inertia collector with no protocol result
captures `null`, because it cannot invent an HTTP status or a page. Disabled collection always captures `null`.

Inertia's two callbacks run at capture, never inside the protocol operation: the first sanitizes the **complete capture
array** — page props, request headers and shared-key metadata — and the second sanitizes page and navigation URLs. The
result must still satisfy the presenter schema; a policy failure or unencodable payload becomes an isolated host
capture failure.

Attach these events only to trusted listeners. An event carries real application data, and capture-time redaction does
not protect it from an unrelated listener that logs it, so never point an unrestricted event dumper at them. Listener
exceptions propagate per PSR-14 — never swallowed, never retried. The supplied listeners only buffer the event.

## Compatibility

Both hosts previously shipped their own Vite and Inertia collectors and panels; all of them were removed, so the `vite`
and `inertia` IDs are free for the provider-owned objects an application registers itself. An extension now declares its
own ID, icon and title.

Captures written by the removed host collectors are not decoded. The Vite payload shape is unchanged and still renders;
an old Inertia capture stays readable through the host's raw JSON fallback.

Every dispatcher argument used here is an optional trailing argument: calls without one behave normally and emit no
events. The branch aliases still describe an unreleased linked prototype, not a published release.

## How this is verified

- The full Vite and Inertia PHPUnit suites run through each package's own autoloader, without Debug Core installed.
- `python3 tools/check-provider-consumer.py` (in `php-forge/debug`) exports both providers and their locked production
  dependencies into a temporary mirror, installs with Packagist and plugins disabled, then exercises real resolution,
  sanitization, cleanup and replay with no debugger or framework present.
- `DEBUG_UI_SEED_FIXTURES=0 npx playwright test e2e/provider-events.spec.js` (in Debug Core) checks persisted values
  after a changed request: navigation, props/JSON, empty Vite capture, non-conflict 409, sanitization, accessibility,
  and both themes and viewport sizes.

References: [PSR-14](https://www.php-fig.org/psr/psr-14/), [PSR-3](https://www.php-fig.org/psr/psr-3/).

---

[← Back to documentation](../README.md#documentation)
