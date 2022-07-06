<?php
/*
 * This file is part of the Austral Notify Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\NotifyBundle;
use Austral\NotifyBundle\DependencyInjection\Compiler\MessengerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Austral ListMapper Bundle.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class AustralNotifyBundle extends Bundle
{

  public function build(ContainerBuilder $container)
  {
    parent::build($container);
    $container->addCompilerPass(new MessengerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
  }
  
  
}
