<?php
/*
 * This file is part of the Austral Notify Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\NotifyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Austral Email Configuration.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Configuration implements ConfigurationInterface
{
  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder(): TreeBuilder
  {
    $treeBuilder = new TreeBuilder('austral_notify');

    $rootNode = $treeBuilder->getRootNode();
    $rootNode->children()
        ->booleanNode("async")->end()
        ->arrayNode('mercure')
          ->addDefaultsIfNotSet()
          ->children()
            ->scalarNode("domain")->end()
          ->end()
        ->end()
      ->end()
    ->end();
    return $treeBuilder;
  }

  /**
   * @return array
   */
  public function getConfigDefault(): array
  {
    return array(
      "async"             =>  true,
      "mercure"           =>  array(
        'domain'            =>  "austral.dev"
      )
    );
  }

}
