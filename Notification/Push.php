<?php
/*
 * This file is part of the Austral Notify Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\NotifyBundle\Notification;

use Austral\EmailBundle\Services\EmailSender;
use Austral\NotifyBundle\Mercure\Mercure;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Austral Push notification email and / or mercure.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Push
{

  /** @var string  */
  const TYPE_MERCURE = "mercure";

  /** @var string  */
  const TYPE_MAIL = "mail";

  /**
   * @var EmailSender
   */
  protected EmailSender $emailSend;

  /**
   * @var Mercure
   */
  protected Mercure $mercure;

  /**
   * @var array
   */
  protected array $resolverByType = array();

  /**
   * @var array
   */
  protected array $notifications;

  /**
   * @param EmailSender $emailSender
   * @param Mercure $mercure
   */
  public function __construct(EmailSender $emailSender, Mercure $mercure)
  {
    $this->resolverByType[self::TYPE_MERCURE] = $this->createResolverByType(self::TYPE_MERCURE);
    $this->resolverByType[self::TYPE_MAIL] = $this->createResolverByType(self::TYPE_MAIL);
    $this->emailSend = $emailSender;
    $this->mercure = $mercure;
    $this->notifications = array(
      self::TYPE_MERCURE => array(),
      self::TYPE_MAIL => array(),
    );
  }

  /**
   * @param string $type
   *
   * @return OptionsResolver
   */
  protected function createResolverByType(string $type): OptionsResolver
  {
    $resolver = new OptionsResolver();
    if($type === self::TYPE_MAIL)
    {
      $resolver->setDefaults(array(
          "email_template_keyname"  =>  null,
          "language"                =>  null,
          "object"                  =>  null,
          "vars"                    =>  array()
        )
      );
      $resolver->setRequired("email_template_keyname")
        ->setRequired("language")
        ->setRequired("object")
        ->setRequired("vars");

      $resolver->setAllowedTypes('email_template_keyname', array('string'));
      $resolver->setAllowedTypes('language', array('string'));
      $resolver->setAllowedTypes('object', array('object', "null"));
      $resolver->setAllowedTypes('vars', array('array', "null"));
    }
    elseif($type === self::TYPE_MERCURE)
    {
      $resolver->setDefaults(array(
          "topics"  => null,
          "values"  => array(),
        )
      );
      $resolver->setDefault("options", function (OptionsResolver $resolverChild) {
        $resolverChild->setDefaults(array(
            "private"  =>  false,
            "id"       =>  null,
            "type"     =>  null,
            "retry"    =>  null,
          )
        );
        $resolverChild->setAllowedTypes('private', array('boolean'));
        $resolverChild->setAllowedTypes('id', array('string', "null"));
        $resolverChild->setAllowedTypes('type', array('string', "null"));
        $resolverChild->setAllowedTypes('retry', array('int', "null"));
      });
      $resolver->setRequired("topics")
        ->setRequired("values");

      $resolver->setAllowedTypes('topics', array("string", 'string[]'));
      $resolver->setAllowedTypes('values', array('array'));
    }
    return $resolver;
  }

  /**
   * @param string $type
   * @param array $values
   *
   * @return $this
   * @throws \Exception
   */
  public function add(string $type, array $values): Push
  {
    if(!array_key_exists($type, $this->resolverByType))
    {
      throw new \Exception("Type notification {$type} is not defined");
    }

    $values = $this->resolverByType[$type]->resolve($values);
    $this->notifications[$type][] = $values;
    return $this;
  }


  /**
   * @param bool $clear
   * @param bool|null $async
   */
  public function push(bool $clear = true, ?bool $async = null)
  {
    $errorPush = false;
    try {
      /** @var array $data */
      foreach($this->notifications[self::TYPE_MAIL] as $data)
      {
        $this->emailSend->setAsync($async)
          ->setLanguage($data["language"])
          ->initEmailTemplateByKeymane($data["email_template_keyname"])
          ->setObject($data['object'])
          ->addVars(array_key_exists("vars", $data) ? $data['vars'] : array())
          ->execute();
      }
    } catch (\Exception $e) {
      $errorPush = true;
    }

    try {
      /** @var array $data */
      foreach($this->notifications[self::TYPE_MERCURE] as $data)
      {
        if(is_string($data["topics"]))
        {
          $data["topics"] = array(
            $data["topics"]
          );
        }
        $options = $data['options'];
        unset($data["options"]);
        $this->mercure->setAsync($async)
          ->publish($data["topics"], $data["values"], $options["private"], $options["id"], $options["type"], $options["retry"]);
      }
    } catch (\Exception $e) {
      $errorPush = true;
    }
    if($errorPush === true && $async === true)
    {
      $this->push($clear, false);
    }
    elseif($clear)
    {
      $this->notifications = array(
        self::TYPE_MERCURE => array(),
        self::TYPE_MAIL => array(),
      );
    }
  }


}