<?php
/*
 * This file is part of the Austral Notify Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\NotifyBundle\Listener;


use Austral\AdminBundle\Configuration\ConfigurationChecker;
use Austral\AdminBundle\Configuration\ConfigurationCheckerValue;
use Austral\AdminBundle\Event\ConfigurationCheckerEvent;
use Austral\NotifyBundle\Configuration\NotifyConfiguration;

/**
 * Austral ConfigurationChecker Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ConfigurationCheckerListener
{

  /**
   * @var NotifyConfiguration
   */
  protected NotifyConfiguration $notifyConfiguration;

  /**
   * @param NotifyConfiguration $notifyConfiguration
   */
  public function __construct(NotifyConfiguration $notifyConfiguration)
  {
    $this->notifyConfiguration = $notifyConfiguration;
  }

  /**
   * @param ConfigurationCheckerEvent $configurationCheckerEvent
   *
   * @throws \Exception
   */
  public function configurationChecker(ConfigurationCheckerEvent $configurationCheckerEvent)
  {
    $configurationCheckModules = $configurationCheckerEvent->getConfigurationChecker()->getChild("modules");

    $configurationCheckerNotify = new ConfigurationChecker("notify");
    $configurationCheckerNotify->setName("configuration.check.modules.notify.title")
      ->setIsTranslatable(true)
      ->setWidth(ConfigurationChecker::$WIDTH_FULL)
      ->setParent($configurationCheckModules);

    $configurationCheckerValue = new ConfigurationCheckerValue("async", $configurationCheckerNotify);
    $configurationCheckerValue->setName("configuration.check.modules.notify.async.entitled")
      ->setIsTranslatable(true)
      ->setIsTranslatableValue(true)
      ->setType(ConfigurationCheckerValue::$TYPE_CHECKED)
      ->setStatus($this->notifyConfiguration->get('async') ? ConfigurationCheckerValue::$STATUS_SUCCESS : ConfigurationCheckerValue::$STATUS_NONE)
      ->setValue($this->notifyConfiguration->get('async') ? "configuration.check.choices.yes" : "configuration.check.choices.no");

    $configurationCheckerValue = new ConfigurationCheckerValue("enabled", $configurationCheckerNotify);
    $configurationCheckerValue->setName("configuration.check.modules.notify.enabled.entitled")
      ->setIsTranslatable(true)
      ->setIsTranslatableValue(true)
      ->setType(ConfigurationCheckerValue::$TYPE_CHECKED)
      ->setStatus($this->notifyConfiguration->get('mercure.enabled') ? ConfigurationCheckerValue::$STATUS_SUCCESS : ConfigurationCheckerValue::$STATUS_NONE)
      ->setValue($this->notifyConfiguration->get('mercure.enabled') ? "configuration.check.choices.yes" : "configuration.check.choices.no");


    $configurationCheckerValue = new ConfigurationCheckerValue("mercure", $configurationCheckerNotify);
    $configurationCheckerValue->setName("configuration.check.modules.notify.mercure.entitled")
      ->setIsTranslatable(true)
      ->setIsTranslatableValue(false)
      ->setType(ConfigurationCheckerValue::$TYPE_STRING)
      ->setStatus(ConfigurationCheckerValue::$STATUS_NONE)
      ->setValue($this->notifyConfiguration->get('mercure.domain'));

  }
}