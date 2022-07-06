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

use Symfony\Component\HttpFoundation\Cookie as CookieHttp;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\HubInterface;

/**
 * Austral Generate Cookie to Authorize.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Cookie
{

  private const AUTHORIZATION_COOKIE_NAME = 'mercureAuthorization';

  /**
   * @var Request|null
   */
  protected ?Request $request;

  /**
   * @var HubInterface
   */
  protected HubInterface $hub;

  /**
   * MercureCookieGenerate constructor.
   *
   * @param RequestStack|null $request
   */
  public function __construct(?RequestStack $request = null)
  {
    $this->request = $request ? $request->getCurrentRequest() : null;
  }

  /**
   * @param HubInterface $hub
   *
   * @return $this
   */
  public function setHub(HubInterface $hub): Cookie
  {
    $this->hub = $hub;
    return $this;
  }

  /**
   * @param array $subscribe
   * @param array $publish
   * @param array $additionalClaims
   * @param int|null $cookieLifetime
   *
   * @return CookieHttp
   * @throws \Exception
   */
  public function generate(array $subscribe = array(), array $publish = array(), array $additionalClaims = array(), ?int $cookieLifetime = null): CookieHttp
  {
    if(!$this->hub) {
      throw new InvalidArgumentException(sprintf('The "%s" hub does not initialise.', 'default'));
    }

    $cookieLifetime = $cookieLifetime ?? (int) ini_get('session.cookie_lifetime');
    $tokenFactory = $this->hub->getFactory();
    if (null === $tokenFactory) {
      throw new InvalidArgumentException(sprintf('The "%s" hub does not contain a token factory.', 'default'));
    }

    if (\array_key_exists('exp', $additionalClaims)) {
      if (null !== $additionalClaims['exp']) {
        $cookieLifetime = $additionalClaims['exp'];
      }
    } else {
      $additionalClaims['exp'] = new \DateTimeImmutable(0 === $cookieLifetime ? '+1 hour' : "+{$cookieLifetime} seconds");
    }

    $token = $tokenFactory->create($subscribe, $publish, $additionalClaims);
    if (!$cookieLifetime instanceof \DateTimeInterface && 0 !== $cookieLifetime) {
      $cookieLifetime = new \DateTimeImmutable("+{$cookieLifetime} seconds");
    }

    return CookieHttp::create(
      self::AUTHORIZATION_COOKIE_NAME,
      $token,
      $cookieLifetime,
      '/',
      strtolower($this->request->getHost()),
      false,
      false,
      false,
      CookieHttp::SAMESITE_LAX
    );
  }

}

