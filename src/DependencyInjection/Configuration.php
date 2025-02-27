<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface {


    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder {

        $treeBuilder = new TreeBuilder('church_desk');
        $treeBuilder
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('api')
                ->children()
                    ->scalarNode('organization_id')
                        ->info('Defines the organization id for the ChurchDesk API requests.')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('partner_token')
                        ->info('Defines the partner token for the ChurchDesk API requests.')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
