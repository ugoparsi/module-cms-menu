<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 */

declare(strict_types=1);

use Symfony\Component\HttpClient\HttpClient;
use Tests\Browser\MahoServer;
use Tests\MahoBackendTestCase;

uses(MahoBackendTestCase::class);

function storefrontMenuLinks(string $path): array
{
    $response = HttpClient::create(['max_redirects' => 0])->request('GET', MahoServer::baseUrl() . $path);
    expect($response->getStatusCode())->toBe(200);
    $document = new DOMDocument();
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $links = [];
    foreach (new DOMXPath($document)->query('//nav[@id="nav"]//a') as $link) {
        $links[trim($link->textContent)] = $link->getAttribute('href');
    }
    return $links;
}

function makeStorefrontMenuPage(string $identifier, string $title, int $storeId, bool $active = true): Mage_Cms_Model_Page
{
    return Mage::getModel('cms/page')->setIdentifier($identifier)->setTitle($title)
        ->setContent('<p>Demo storefront menu page</p>')->setIsActive($active)->setStores([$storeId])->save();
}

it('adds the selected active CMS links to the native menu per store view', function (): void {
    $default = Mage::app()->getDefaultStoreView();
    Mage::app()->setCurrentStore($default);
    $defaultId = (int) $default->getId();
    $code = 'demo_menu_view';
    $other = Mage::getModel('core/store')->setCode($code)->setName('Demo menu view')
        ->setWebsiteId((int) $default->getWebsiteId())->setGroupId((int) $default->getGroupId())->setIsActive()->save();
    Mage::app()->reinitStores();
    $otherId = (int) $other->getId();
    $key = 'demo-menu-' . bin2hex(random_bytes(4));
    $root = Mage::getModel('catalog/category')->load($default->getRootCategoryId());
    $category = Mage::getService('catalog/category')->newCategory((int) $root->getId())
        ->setName('Demo category link')->setUrlKey($key . '-category')->setIncludeInMenu(1)->setIsActive(1)->save();
    $pages = [];
    $config = Mage::getConfig();
    $setting = Storefront_CmsMenu_Model_Observer::CONFIG_PATH;
    $title = 'Guide <Draft> & FAQ';
    $localizedTitle = 'Guide et réponses';

    try {
        $pages[] = makeStorefrontMenuPage($key, $title, 0);
        $pages[] = makeStorefrontMenuPage($key, $localizedTitle, $otherId);
        $pages[] = makeStorefrontMenuPage($key . '-local', 'Demo alternate help', $otherId);
        $pages[] = makeStorefrontMenuPage($key . '-disabled', 'Demo disabled help', 0, false);
        $path = '/customer/account/login/';
        $alternatePath = $path . '?___store=' . $code;
        expect(storefrontMenuLinks($path))->toHaveKey('Demo category link')->not->toHaveKey($title);

        $selected = implode(',', [$key, $key . '-local', $key . '-disabled', $key . '-missing']);
        $config->saveConfig($setting, $selected, 'stores', $defaultId);
        $config->saveConfig($setting, $selected . ',' . $key . '|' . $pages[1]->getId(), 'stores', $otherId);
        $config->cleanCache();

        $defaultLinks = storefrontMenuLinks($path);
        expect($defaultLinks)->toHaveKey('Demo category link')
            ->and($defaultLinks)->toHaveKey($title)
            ->and($defaultLinks[$title])->toEndWith('/' . $key . '/')
            ->and($defaultLinks)->not->toHaveKey('Demo disabled help')
            ->and($defaultLinks)->not->toHaveKey('Demo alternate help')
            ->and($defaultLinks)->not->toHaveKey($localizedTitle);

        $pages[0]->setTitle('Guide updated')->save();
        $updatedLinks = storefrontMenuLinks($path);
        expect($updatedLinks)->toHaveKey('Guide updated')->not->toHaveKey($title);

        $alternate = storefrontMenuLinks($alternatePath);
        expect($alternate)->toHaveKey('Demo category link')
            ->and($alternate[$localizedTitle])->toEndWith('/' . $key . '/?___store=' . $code)
            ->and($alternate['Demo alternate help'])->toEndWith('/' . $key . '-local/?___store=' . $code)
            ->and($alternate)->not->toHaveKey('Guide updated')
            ->and($alternate)->not->toHaveKey('Demo disabled help');

        $pages[1]->setIsActive(false)->save();
        $fallback = storefrontMenuLinks($alternatePath);
        expect($fallback)->toHaveKey('Guide updated')->not->toHaveKey($localizedTitle);

        $pages[1]->setIsActive()->save();
        expect(storefrontMenuLinks($alternatePath))->toHaveKey($localizedTitle)->not->toHaveKey('Guide updated');

        $pages[0]->setIsActive(false)->save();
        expect(storefrontMenuLinks($path))->not->toHaveKey('Guide updated');

        $config->saveConfig($setting, '', 'stores', $otherId);
        $config->cleanCache();
        expect(storefrontMenuLinks($alternatePath))->not->toHaveKey($localizedTitle);
    } finally {
        foreach ($pages as $page) {
            $page->delete();
        }
        $category->delete();
        $other->delete();
        Mage::app()->reinitStores();
        $config->deleteConfig($setting, 'stores', $defaultId);
        $config->cleanCache();
    }
});
