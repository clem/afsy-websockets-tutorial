<?php

namespace AppBundle;

use AppBundle\DependencyInjection\AfsyChatExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * {@inheritdoc}
 */
class AppBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new AfsyChatExtension();
    }
}
