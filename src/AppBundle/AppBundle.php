<?php

namespace AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use AppBundle\DependencyInjection\AfsyChatExtension;

/**
 * @inheritdoc
 */
class AppBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function getContainerExtension()
    {
        return new AfsyChatExtension();
    }
}
