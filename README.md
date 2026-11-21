# CMS Pages in the Storefront Menu

Choose which CMS pages appear alongside categories in the storefront menu of each Maho store view.

## Install

The module targets Maho 27.1 and requires PHP 8.5.1 or later. The Composer installation was verified on the 27.1 development branch at `2de25e973` with SQLite.

Place the package in `../cms-navigation-package` relative to the store project. From the store project, add the path repository and install the module.

```bash
composer config repositories.cms-menu '{"type":"path","url":"../cms-navigation-package","options":{"symlink":false,"versions":{"ugoparsi/module-cms-menu":"0.1.0"}}}'
composer require ugoparsi/module-cms-menu:^0.1
./maho cache:flush
```

For a Maho 27.1 source checkout, set `COMPOSER_ROOT_VERSION=27.1.x-dev` on the Composer require command.

## Choose pages

Go to **System > Configuration > Web > Default Pages** and select the store view. Choose pages under **CMS Pages in the Storefront Menu**. An empty selection uses the store's existing menu.

Maho uses the active CMS page for the current store view when a page shares its identifier with a global page. When the localized page is inactive, the menu can use the active global page. The menu shows the current page title and refreshes when a page is edited, activated or disabled.

## Test

Run the integration test from an isolated Maho source checkout with the module installed. The example uses a store configured at `http://localhost:8935/`.

```bash
MAHO_BROWSER_PORT=8935 vendor/bin/pest vendor/ugoparsi/module-cms-menu/tests/Integration/StorefrontMenuPagesTest.php
```

## License

PHP source and tests use [OSL-3.0](LICENSE.txt). XML configuration uses [AFL-3.0](LICENSE_AFL.txt).
