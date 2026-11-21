# CMS Pages in the Storefront Menu

Choose which CMS pages appear alongside categories in the storefront menu of each Maho store view.

## Install

The module requires PHP 8.5.1 or later. It supports Maho 26.11.3 and later 26.11 patches, and the 27.1 series. The Composer installation and menu tests passed with SQLite on Maho 26.11.3 and on 27.1 development commit `2de25e973`. Installation also passed in Maho Starter 3.0.0 with Maho 26.11.3.

From your Maho Composer project, register the GitHub repository and install the module.

```bash
composer config repositories.cms-menu vcs https://github.com/ugoparsi/module-cms-menu
composer require 'ugoparsi/module-cms-menu:^0.1'
./maho cache:flush
```

The same `composer require` command switches an existing preview installation to a versioned release.

## Choose pages

Go to **System > Configuration > Web > Default Pages** and select the store view. Choose pages under **CMS Pages in the Storefront Menu**. An empty selection uses the store's existing menu.

Maho uses the active CMS page for the current store view when a page shares its identifier with a global page. When the localized page is inactive, the menu can use the active global page. The menu shows the current page title and refreshes when a page is edited, activated or disabled.

## Test

Install the module in an isolated Maho source checkout. Set `COMPOSER_ROOT_VERSION=26.11.3` on `composer require` for that tag, or `COMPOSER_ROOT_VERSION=27.1.x-dev` for the development branch tested here. Enable the cache and set the port to match the store's base URL. The example uses `http://localhost:8935/`.

```bash
./maho cache:enable
MAHO_BROWSER_PORT=8935 vendor/bin/pest vendor/ugoparsi/module-cms-menu/tests/Integration/StorefrontMenuPagesTest.php
```

## License

PHP source and tests use [OSL-3.0](LICENSE.txt). XML configuration uses [AFL-3.0](LICENSE_AFL.txt).
