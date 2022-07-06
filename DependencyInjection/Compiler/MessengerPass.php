<?php
/*
 * This file is part of the Austral Notify Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\NotifyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Austral Messenger Pass.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class MessengerPass implements CompilerPassInterface
{
  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container)
  {
    if($container->hasDefinition('messenger.senders_locator'))
    {
      $definition = $container->findDefinition('messenger.senders_locator');
      $resolveMessengers = $container->getParameter("austral.resolve.messenger.routing.notify");

      $arguments = $definition->getArguments();
      foreach ($arguments as $key => $argument)
      {
        if(is_array($argument))
        {
          $argument = array_merge($argument, $resolveMessengers);
          $definition->replaceArgument($key, $argument);
        }
      }
    }
  }
}