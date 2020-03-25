<?php

namespace AppBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * {@inheritdoc}
 */
class AfsyChatExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Initialize
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Set container parameter
        $container->setParameter('afsy_chat.websocket', $config['websocket']);
    }
}
