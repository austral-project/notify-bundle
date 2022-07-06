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

/**
 * Austral Notify Messenger.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class MercureMessage
{
  /**
   * @var array|string
   */
  private $topics;

  /**
   * @var array
   */
  private array $data;

  /**
   * @var bool
   */
  private bool $private;

  /**
   * @var string|null
   */
  private ?string $id;

  /**
   * @var string|null
   */
  private ?string $type;

  /**
   * @var int|null
   */
  private ?int $retry;

  /**
   * EmailSenderEvent constructor.
   *
   */
  public function __construct($topics, array $data = array(), bool $private = false, string $id = null, string $type = null, int $retry = null)
  {
    $this->topics = $topics;
    $this->data = $data;
    $this->private = $private;
    $this->id = $id;
    $this->type = $type;
    $this->retry = $retry;
  }

  /**
   * @return array|string
   */
  public function getTopics()
  {
    return $this->topics;
  }

  /**
   * @return array
   */
  public function getData(): array
  {
    return $this->data;
  }

  /**
   * @return bool
   */
  public function getPrivate(): bool
  {
    return $this->private;
  }

  /**
   * @return string|null
   */
  public function getId(): ?string
  {
    return $this->id;
  }

  /**
   * @return string|null
   */
  public function getType(): ?string
  {
    return $this->type;
  }

  /**
   * @return int|null
   */
  public function getRetry(): ?int
  {
    return $this->retry;
  }

}