# CMS Pages in the Storefront Menu

Choose which CMS pages appear alongside categories in the storefront menu of each Maho store view.

## Install the preview

The module supports Maho 26.11.3 and the 27.1 series. It requires PHP 8.5.1 or later. The Composer installation and menu tests passed on Maho 26.11.3 and on 27.1 development commit `2de25e973` with SQLite.

From your Maho Composer project, register the GitHub repository and install the preview.

```bash
composer config repositories.cms-menu vcs https://github.com/ugoparsi/module-cms-menu
composer require ugoparsi/module-cms-menu:dev-main
./maho cache:flush
```

For a Maho source checkout, set `COMPOSER_ROOT_VERSION` on the Composer require command. Use `26.11.3` for that tag or `27.1.x-dev` for the development branch tested here.

## Choose pages

Go to **System > Configuration > Web > Default Pages** and select the store view. Choose pages under **CMS Pages in the Storefront Menu**. An empty selection uses the store's existing menu.

Maho uses the active CMS page for the current store view when a page shares its identifier with a global page. When the localized page is inactive, the menu can use the active global page. The menu shows the current page title and refreshes when a page is edited, activated or disabled.

## Test

Run the integration test from an isolated Maho source checkout with the module installed. Enable block caching to exercise cache invalidation. Set the port to match the store's base URL. The example uses `http://localhost:8935/`.

```bash
MAHO_BROWSER_PORT=8935 vendor/bin/pest vendor/ugoparsi/module-cms-menu/tests/Integration/StorefrontMenuPagesTest.php
```

## License

PHP source and tests use [OSL-3.0](LICENSE.txt). XML configuration uses [AFL-3.0](LICENSE_AFL.txt).
