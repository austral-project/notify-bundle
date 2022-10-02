<?php
/*
 * This file is part of the Austral Notify Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\NotifyBundle\Mercure;

use Austral\NotifyBundle\Configuration\NotifyConfiguration;
use Austral\NotifyBundle\Message\MercureMessage;
use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Austral\ToolsBundle\AustralTools;
use Austral\ToolsBundle\Services\ServicesStatusChecker;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

/**
 * Austral Generate Cookie to Authorize.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Mercure
{

  /**
   * @var HubInterface
   */
  protected HubInterface $hub;

  /**
   * @var string|null
   */
  protected ?string $userTabId = null;

  /**
   * @var bool
   */
  protected bool $enabled;

  /**
   * @var Cookie
   */
  protected Cookie $cookie;

  /**
   * @var MessageBusInterface|null
   */
  protected ?MessageBusInterface $bus;

  /**
   * @var NotifyConfiguration
   */
  protected NotifyConfiguration $notifyConfiguration;

  /**
   * @var array
   */
  protected array $subscribe = array(
    "default"
  );

  /**
   * @var array
   */
  protected array $publish = array();

  /** @var HttpClientInterface */
  protected HttpClientInterface $httpClient;

  /**
   * @var array
   */
  protected array $additionalClaims = array();

  /**
   * @var string|\Symfony\Component\Security\Core\User\UserInterface|UserInterface|null
   */
  protected $user = null;

  /**
   * @var mixed
   */
  protected $async = "default";

  /**
   * @var bool
   */
  protected bool $asyncServiceStart = false;

  /**
   * @var string
   */
  protected string $mercureDomain;

  /**
   * MercureCookieGenerate constructor.
   *
   * @param HubInterface $hub
   * @param Cookie $cookie
   * @param NotifyConfiguration $notifyConfiguration
   * @param ServicesStatusChecker $servicesStatusChecker
   * @param UsageTrackingTokenStorage $token
   * @param MessageBusInterface|null $bus
   *
   * @throws \Exception
   */
  public function __construct(HubInterface $hub, Cookie $cookie, NotifyConfiguration $notifyConfiguration, ServicesStatusChecker $servicesStatusChecker, UsageTrackingTokenStorage $token, ?MessageBusInterface $bus)
  {
    $this->hub = $hub;
    $this->cookie = $cookie;
    $this->notifyConfiguration = $notifyConfiguration;
    $this->bus = $bus;
    $this->mercureDomain = $this->notifyConfiguration->get('mercure.domain');
    $this->enabled = (bool) $this->notifyConfiguration->get('mercure.enabled');
    $this->user = $token->getToken() ? $token->getToken()->getUser() : null;
    $this->httpClient = HttpClient::create();
    if($this->user instanceof UserInterface) {
      $this->addAdditionalClaims("mercure", array("payload" => array("user-id" => $this->user->getId())));
      $this->addSubscribe("/user/{$this->user->getId()}");
    }
    if($servicesStatusChecker->getServiceIsRunByCommand("messenger:consume"))
    {
      $this->asyncServiceStart = true;
    }
  }

  /**
   * @var int|null $cookieLifetime
   *
   * @return \Symfony\Component\HttpFoundation\Cookie
   * @throws \Exception
   */
  public function generateCookie(?int $cookieLifetime = null): \Symfony\Component\HttpFoundation\Cookie
  {
    return $this->cookie->setHub($this->hub)->generate(
      $this->getSubscribes(),
      $this->publish,
      $this->additionalClaims,
      $cookieLifetime
    );
  }

  /**
   * @param $topics
   * @param array $data
   * @param bool $private
   * @param string|null $id
   * @param string|null $type
   * @param int|null $retry
   *
   * @return Mercure
   */
  public function publish($topics, array $data = array(), bool $private = false, string $id = null, string $type = null, int $retry = null): Mercure
  {
    if($this->enabled)
    {
      if($this->asyncServiceStart && $this->bus && (($this->notifyConfiguration->get("async") && $this->async == "default") || $this->async === true))
      {
        $this->bus->dispatch(new MercureMessage(
            $topics,
            $data,
            $private,
            $id,
            $type,
            $retry
          )
        );
      }
      else
      {
        $this->hub->publish(new Update(
          $this->initTopics($topics),
          json_encode($data),
          $private,
          $id,
          $type,
          $retry
        ));
      }
    }
    return $this;
  }

  /**
   * @param string|null $topic
   *
   * @return array
   */
  public function listSubscribes(?string $topic = null): array
  {
    $jwt = $this->hub->getFactory()->create(array("*"), array("*"));
    $this->validateJwt($jwt);
    try {
      $return = $this->httpClient
        ->request('GET', $this->hub->getUrl()."/subscriptions".($topic ? "/".urlencode($this->topicWithDomain($topic)) : ""), [
        'auth_bearer' => $jwt,
      ])->getContent();
      return json_decode($return, true);
    } catch (ExceptionInterface $exception) {
      throw new RuntimeException('Failed to send an update.', 0, $exception);
    }
  }

  /**
   * @return array
   */
  public function listSubscribesByTopics(): array
  {
    $listSubscribesByTopics = array();
    try {
      foreach(AustralTools::getValueByKey($this->listSubscribes(), "subscriptions", array()) as $subscription)
      {
        $topicKey = $subscription["topic"];
        if(!array_key_exists($topicKey, $listSubscribesByTopics))
        {
          $listSubscribesByTopics[$topicKey] = array();
        }
        preg_match("^.*\/(user-tab)-(\w{8}-\w{4}-\w{4}-\w{4}-\w{12})^", $subscription["topic"], $matches);
        if(array_key_exists(1, $matches) && array_key_exists(2, $matches))
        {
          $topicKey = "user-tab";
          $subscription['pushTopic'] = "user-tab-{$matches[2]}";
          $subscription["userTabId"] = $matches[2];
          $listSubscribesByTopics[$topicKey][$subscription["subscriber"]] = $subscription;
        }
        else
        {
          $listSubscribesByTopics[$topicKey][$subscription["subscriber"]] = $subscription;
        }
      }
    }
    catch(\Exception $e) {
    }
    return $listSubscribesByTopics;
  }

  /**
   * Regex ported from Windows Azure Active Directory IdentityModel Extensions for .Net.
   *
   * @throws InvalidArgumentException
   *
   * @license MIT
   * @copyright Copyright (c) Microsoft Corporation
   *
   * @see https://github.com/AzureAD/azure-activedirectory-identitymodel-extensions-for-dotnet/blob/6e7a53e241e4566998d3bf365f03acd0da699a31/src/Microsoft.IdentityModel.JsonWebTokens/JwtConstants.cs#L58
   */
  private function validateJwt(string $jwt): void
  {
    if (!preg_match('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/', $jwt)) {
      throw new InvalidArgumentException('The provided JWT is not valid');
    }
  }

  /**
   * @return HubInterface
   */
  public function getHub(): HubInterface
  {
    return $this->hub;
  }

  /**
   * @param $async
   *
   * @return $this
   */
  public function setAsync($async): Mercure
  {
    $this->async = $async !== null ? $async : "default";
    return $this;
  }

  /**
   * @return bool
   */
  public function getEnabled(): bool
  {
    return $this->enabled;
  }

  /**
   * @param bool $enabled
   *
   * @return $this
   */
  public function setEnabled(bool $enabled): Mercure
  {
    $this->enabled = $enabled;
    return $this;
  }

  /**
   * @param array $subscribe
   *
   * @return $this
   */
  public function setSubscribe(array $subscribe): Mercure
  {
    $this->subscribe = $subscribe;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getUserTabId(): ?string
  {
    return $this->userTabId;
  }

  /**
   * @param string $userTabId
   *
   * @return $this
   */
  public function setUserTabId(string $userTabId): Mercure
  {
    $this->userTabId = $userTabId;
    $this->addSubscribe("/user-tab-{$userTabId}");
    return $this;
  }

  /**
   * @param string $subscribe
   *
   * @return $this
   */
  public function addSubscribe(string $subscribe): Mercure
  {
    $this->subscribe[] = $subscribe;
    return $this;
  }

  /**
   * @param array $publish
   *
   * @return $this
   */
  public function setPublish(array $publish): Mercure
  {
    $this->publish = $publish;
    return $this;
  }

  /**
   * @param string $publish
   *
   * @return $this
   */
  public function addPublish(string $publish): Mercure
  {
    $this->publish[] = $publish;
    return $this;
  }

  /**
   * @param array $additionalClaims
   *
   * @return $this
   */
  public function setAdditionalClaims(array $additionalClaims): Mercure
  {
    $this->additionalClaims = $additionalClaims;
    return $this;
  }

  /**
   * @param string $key
   * @param mixed $value
   *
   * @return $this
   */
  public function addAdditionalClaims(string $key, $value): Mercure
  {
    $this->additionalClaims[$key] = $value;
    return $this;
  }

  /**
   * @param UserInterface $user
   *
   * @return $this
   */
  public function setUser(UserInterface $user): Mercure
  {
    $this->user = $user;
    return $this;
  }

  /**
   * @return array
   */
  public function getSubscribes(): array
  {
    $subscribes = array();
    if($this->user)
    {
      $this->subscribe[] = "authenticated";
      if(method_exists($this->user , "getMercureSubscribe"))
      {
        $this->subscribe = array_merge($this->subscribe, (array) $this->user->getMercureSubscribe($this->mercureDomain));
      }
    }
    else
    {
      $this->subscribe[] = "anonymous";
    }

    if(count($this->subscribe) > 0)
    {
      foreach($this->subscribe as $subscribe)
      {
        $subscribeKey = $this->topicWithDomain($subscribe);
        $subscribes[$subscribeKey] = $subscribeKey;
      }
    }
    return array_values($subscribes);
  }

  /**
   * @param $topics
   *
   * @return array
   */
  private function initTopics($topics): array
  {
    if(!is_array($topics))
    {
      $topics = array($topics);
    }

    $finalTopics = array();
    foreach($topics as $topic)
    {
      $topicKey = $this->topicWithDomain($topic);
      $finalTopics[$topicKey] = $topicKey;
    }
    return array_values($finalTopics);
  }

  /**
   * @param string $topic
   *
   * @return string
   */
  public function topicWithDomain(string $topic): string
  {
    return u('/')->join([$this->mercureDomain, $topic])->replace("//", "/")->toString();
  }

  /**
   * @param string $topic
   *
   * @return string
   */
  public function topicWithoutDomain(string $topic): string
  {
    return str_replace($this->mercureDomain, "", $topic);
  }


}

