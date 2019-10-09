<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SyliusConsumerBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\SyliusConsumerBundle\Model\Product\ProductInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class SyliusConsumerAdmin extends Admin
{
    const PRODUCT_SECURITY_CONTEXT = 'sulu.global.products';

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RouteBuilderFactoryInterface
     */
    private $routeBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        if ($this->securityChecker->hasPermission(static::PRODUCT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $products = new NavigationItem('sulu_sylius_product.products');
            $products->setPosition(45);
            $products->setIcon('fa-cube');
            $products->setMainRoute('sulu_sylius_product.products_list');
            $rootNavigationItem->addChild($products);
        }

        return new Navigation($rootNavigationItem);
    }

    public function getRoutes(): array
    {
        $locales = array_values(
            array_map(
                function (Localization $localization) {
                    return $localization->getLocale();
                },
                $this->webspaceManager->getAllLocalizations()
            )
        );

        $contentFormToolbarActions = [];
        $detailsFormToolbarActions = [];
        if ($this->securityChecker->hasPermission(static::PRODUCT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $contentFormToolbarActions[] = 'sulu_admin.save_with_publishing';
            $contentFormToolbarActions[] = 'sulu_admin.type';
            $detailsFormToolbarActions[] = 'sulu_admin.save';
        }

        return [
            $this->routeBuilderFactory->createListRouteBuilder('sulu_sylius_product.products_list', '/products/:locale')
                ->setResourceKey(ProductInterface::RESOURCE_KEY)
                ->setListKey(ProductInterface::LIST_KEY)
                ->setTitle('sulu_sylius_product.products')
                ->addListAdapters(['table'])
                ->setEditRoute('sulu_sylius_product.product_edit_form.detail')
                ->addLocales($locales)
                ->setDefaultLocale($locales[0])
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder('sulu_sylius_product.product_edit_form', '/products/:locale/:id')
                ->setResourceKey(ProductInterface::RESOURCE_KEY)
                ->setBackRoute('sulu_sylius_product.products_list')
                ->setTitleProperty('name')
                ->addLocales($locales)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_sylius_product.product_edit_form.detail', '/details')
                ->setResourceKey(ProductInterface::RESOURCE_KEY)
                ->setFormKey(ProductInterface::FORM_KEY)
                ->setTabTitle('sulu_sylius_product.details')
                ->addToolbarActions($detailsFormToolbarActions)
                ->setParent('sulu_sylius_product.product_edit_form')
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_sylius_product.product_edit_form.content', '/content')
                ->setResourceKey(ProductInterface::CONTENT_RESOURCE_KEY)
                ->setFormKey(ProductInterface::CONTENT_FORM_KEY)
                ->setTabTitle('sulu_sylius_product.content')
                ->addToolbarActions($contentFormToolbarActions)
                ->setParent('sulu_sylius_product.product_edit_form')
                ->getRoute(),
        ];
    }

    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Global' => [
                    static::PRODUCT_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::EDIT,
                    ],
                ],
            ],
        ];
    }
}
