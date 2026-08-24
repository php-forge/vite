# Installation guide

## Requirements

- [PHP](https://www.php.net/downloads) 8.3 or later.
- The `mbstring` PHP extension.
- [Composer](https://getcomposer.org/download/) 2.x.
- A consumer-managed [Vite](https://vite.dev/) 5 or later installation when assets are built or served locally.

The package has no runtime dependency on Yii, Inertia, React, Vue, Foxy, Node.js, or a JavaScript package manager.
Its optional HTML rendering API uses the framework-agnostic [`ui-awesome/html`](https://github.com/ui-awesome/html)
library, which includes `ui-awesome/html-helper` transitively.

## Install the PHP package

```bash
composer require php-forge/vite:^0.1
```

## Configure the consuming project

Install Vite and any desired plugins in the application with its chosen JavaScript package manager. The PHP package never
modifies `package.json`, installs Vite, or runs a package manager.

Production resolution requires Vite's client build manifest. Enable it in the application's Vite configuration:

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

This configuration writes the manifest to `<project-root>/public/build/.vite/manifest.json`: `build.outDir` is relative to
the Vite project root, and a string `build.manifest` value is relative to `build.outDir`. Pass that resulting absolute
filesystem path to `ProductionConfiguration`.

For supported Vite 5 and later releases, [`manifest: true`](https://vite.dev/config/build-options#build-manifest) also
defaults to `.vite/manifest.json` inside `build.outDir`. [Vite 4](https://github.com/vitejs/vite/blob/v4.5.14/docs/config/build-options.md#buildmanifest)
placed the default `manifest: true` output directly at `<outDir>/manifest.json`; it is not part of the supported major
range. Always resolve the concrete path produced by the consuming application's Vite configuration instead of assuming a
universal output location.

Vite injects its modulepreload polyfill into each HTML entry by default. A backend integration that uses a non-HTML custom
entry must import `vite/modulepreload-polyfill` when the polyfill is needed. This package only renders modulepreload assets;
it does not provide that JavaScript polyfill.

## Next steps

- ⚙️ [Configuration reference](configuration.md)
- 📦 [Manifest resolution](manifest.md)
- 💡 [Usage examples](examples.md)
- 🔒 [Security and CSP](security.md)
- 🧪 [Testing guide](testing.md)
