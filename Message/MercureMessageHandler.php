<?php
/*
 * This file is part of the Austral Notify Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Austral\NotifyBundle\Message;

use Austral\NotifyBundle\Mercure\Mercure;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Austral Mercure MessagerHandler.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class MercureMessageHandler implements MessageHandlerInterface
{

  /**
   * @var Mercure
   */
  protected Mercure $mercure;

  /**
   * EmailSenderSubscriber constructor.
   */
  public function __construct(Mercure $mercure)
  {
    $this->mercure = $mercure;
  }

  /**
   * @param MercureMessage $mercureMessage
   *
   */
  public function __invoke(MercureMessage $mercureMessage)
  {
    $this->mercure->setAsync(false)->publish(
      $mercureMessage->getTopics(),
      $mercureMessage->getData(),
      $mercureMessage->getPrivate(),
      $mercureMessage->getId(),
      $mercureMessage->getType(),
      $mercureMessage->getRetry()
    );
  }

}