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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


class ChurchDeskExtension extends Extension implements PrependExtensionInterface {


    /**
     * {@inheritdoc}
     */
    public function prepend( ContainerBuilder $container ): void {

        $configuration = new Configuration((string) $container->getParameter('kernel.project_dir'));
        $config = $this->processConfiguration($configuration, $container->getExtensionConfig($this->getAlias()));


        if( array_key_exists('api', $config) ) {
            $container->setParameter('contao.churchdesk.api.organization_id', $config['api']['organization_id'] ?? '');
            $container->setParameter('contao.churchdesk.api.partner_token', $config['api']['partner_token'] ?? '');
        }
    }


    /**
     * {@inheritdoc}
     */
    public function load( array $mergedConfig, ContainerBuilder $container ): void {

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('commands.yaml');
        $loader->load('listener.yaml');
        $loader->load('services.yaml');
    }
}
