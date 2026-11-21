<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 */

declare(strict_types=1);

use Maho\Config\Observer;
use Maho\Data\Tree\Node;
use Maho\Event\Observer as Event;

final class Storefront_CmsMenu_Model_Observer
{
    public const CONFIG_PATH = 'web/default/cms_menu_pages';

    #[Observer('core_block_abstract_to_html_before', area: 'frontend')]
    public function varyMenuCache(Event $event): void
    {
        $block = $event->getData('block');
        if (!$block instanceof Mage_Page_Block_Html_Topmenu || $block->getData('cms_menu_cache_varied')) {
            return;
        }

        $selection = (string) Mage::getStoreConfig(self::CONFIG_PATH);
        if ($selection === '') {
            return;
        }

        $cacheKey = hash('sha256', implode('|', [
            $block->getCacheKey(),
            $selection,
            Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_HOME_PAGE),
            Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_NO_ROUTE_PAGE),
        ]));
        $block->setCacheKey('CMS_MENU_' . $cacheKey);
        $block->setData('cms_menu_cache_varied', true);
        $block->addCacheTag(Mage_Cms_Model_Page::CACHE_TAG);
    }

    #[Observer('page_block_html_topmenu_gethtml_before')]
    public function addPageLinks(Event $event): void
    {
        $configuration = (string) Mage::getStoreConfig(self::CONFIG_PATH);
        if ($configuration === '') {
            return;
        }

        $helper = Mage::helper('cms/page');
        $noRoute = $helper->getConfigPageIdentifier(Mage_Cms_Helper_Page::XML_PATH_NO_ROUTE_PAGE);
        $home = $helper->getConfigPageIdentifier(Mage_Cms_Helper_Page::XML_PATH_HOME_PAGE);
        $identifiers = array_map(static fn(string $value): string => explode('|', $value, 2)[0], explode(',', $configuration));
        $identifiers = array_values(array_unique(array_filter(
            $identifiers,
            static fn(string $identifier): bool => $identifier !== '' && $identifier !== $noRoute,
        )));
        if ($identifiers === []) {
            return;
        }

        /** @var Node $menu */
        $menu = $event->getData('menu');
        $store = Mage::app()->getStore();
        $pages = $this->getVisiblePages($identifiers, (int) $store->getId());
        $query = $this->getStoreQuery($store);
        foreach ($identifiers as $identifier) {
            if (!isset($pages[$identifier])) {
                continue;
            }
            $page = $pages[$identifier];
            $params = ['_nosid' => true, '_query' => $query];
            if ($identifier !== $home) {
                $params['_direct'] = $identifier;
            }
            $menu->addChild(new Node([
                'id' => 'cms-menu-page-' . $page['page_id'],
                'name' => $page['title'],
                'url' => Mage::getUrl('', $params),
            ], 'id', $menu->getTree(), $menu));
        }
    }

    /** @return array<string, string> */
    private function getStoreQuery(Mage_Core_Model_Store $store): array
    {
        $helper = Mage::helper('core/url');
        if (method_exists($helper, 'getCanonicalStoreQuery')) {
            return $helper->getCanonicalStoreQuery($store);
        }

        $url = Mage::getModel('core/url')->setStore($store)->getBaseUrl();
        $website = $store->getWebsite();
        $group = $store->getGroup();
        foreach ([$website ? $website->getDefaultStore() : null, $group ? $group->getDefaultStore() : null] as $default) {
            if ($default
                && (int) $default->getId() !== (int) $store->getId()
                && Mage::getModel('core/url')->setStore($default)->getBaseUrl() === $url
            ) {
                return ['___store' => $store->getCode()];
            }
        }

        return [];
    }

    /**
     * @param list<string> $identifiers
     * @return array<string, array{page_id: int, title: string}>
     */
    private function getVisiblePages(array $identifiers, int $storeId): array
    {
        $resource = Mage::getSingleton('core/resource');
        $adapter = $resource->getConnection('core_read');
        $select = $adapter->select()
            ->from(['p' => $resource->getTableName('cms/page')], ['page_id', 'identifier', 'title'])
            ->joinInner(['s' => $resource->getTableName('cms/page_store')], 's.page_id = p.page_id', [])
            ->where('p.identifier IN (?)', $identifiers)
            ->where('p.is_active = ?', 1)
            ->where('s.store_id IN (?)', [Mage_Core_Model_App::ADMIN_STORE_ID, $storeId])
            ->order('s.store_id DESC');

        $pages = [];
        foreach ($adapter->fetchAll($select) as $page) {
            $pages[$page['identifier']] ??= [
                'page_id' => (int) $page['page_id'],
                'title' => (string) $page['title'],
            ];
        }
        return $pages;
    }
}
