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

use Austral\NotifyBundle\Mercure\Mercure;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Austral Response Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class MercureListener
{
  /**
   * @var Mercure
   */
  protected Mercure $mercure;

  /**
   * ControllerListener constructor.
   *
   * @param Mercure $mercure
   */
  public function __construct(Mercure $mercure)
  {
    $this->mercure = $mercure;
  }

  /**
   * @param ResponseEvent $responseEvent
   *
   * @throws \Exception
   */
  public function onResponse(ResponseEvent $responseEvent)
  {
    $responseEvent->getResponse()->headers->setCookie($this->mercure->generateCookie());
    $responseEvent->getResponse()->headers->set("austral-tab-uuid", $this->mercure->getUserTabId());
  }


}
