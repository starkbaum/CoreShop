<?php

namespace CoreShop\Bundle\StorageListBundle\DependencyInjection;

use CoreShop\Bundle\StorageListBundle\Controller\StorageListController;
use CoreShop\Component\StorageList\Context\CompositeStorageListContext;
use CoreShop\Component\StorageList\Context\StorageListContextInterface;
use CoreShop\Component\StorageList\Factory\AddToStorageListFactory;
use CoreShop\Component\StorageList\SessionStorageManager;
use CoreShop\Component\StorageList\StorageListModifierInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('coreshop_storage_list');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $this->addStorageListSection($rootNode);

        return $treeBuilder;
    }

    private function addStorageListSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('list')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('context')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('interface')->defaultValue(StorageListContextInterface::class)->end()
                                    ->scalarNode('composite')->defaultValue(CompositeStorageListContext::class)->end()
                                    ->scalarNode('tag')->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('services')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('manager')->defaultValue(SessionStorageManager::class)->end()
                                    ->scalarNode('modifier')->defaultValue(StorageListModifierInterface::class)->end()
                                    ->scalarNode('enable_default_store_based_decorator')->defaultFalse()->end()
                                ->end()
                            ->end()
                            ->arrayNode('resource')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('interface')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('product_repository')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('repository')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('item_repository')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('factory')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('item_factory')->cannotBeEmpty()->isRequired()->end()
                                    ->scalarNode('add_to_list_factory')->defaultValue(AddToStorageListFactory::class)->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('form')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('type')->cannotBeEmpty()->end()
                                    ->scalarNode('add_type')->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('routes')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('summary')->cannotBeEmpty()->end()
                                    ->scalarNode('index')->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('templates')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('add_to_cart')->cannotBeEmpty()->end()
                                    ->scalarNode('summary')->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('session')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('enabled')->defaultFalse()->end()
                                    ->scalarNode('key')->defaultValue('storage_list')->end()
                                ->end()
                            ->end()
                            ->arrayNode('controller')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('enabled')->defaultFalse()->end()
                                    ->scalarNode('class')->defaultValue(StorageListController::class)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
