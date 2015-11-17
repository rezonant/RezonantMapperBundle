<?php

namespace Rezonant\MapperBundle\Cache\Strategies;
use Rezonant\MapperBundle\Cache\CacheStrategy;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * A mapper cache strategy using the Symfony session as backing store (this is probably
 * silly, and you should consider looking at DoctrineCacheStrategy (and Doctrine Commons Cache) instead
 */
class SessionCacheStrategy extends CacheStrategy {
	
	public function __construct(Session $session, $bagName) {
		
		$this->bagName = $bagName;
		$this->session = $session;
		$this->bag = new AttributeBag($bagName);
		$this->session->registerBag($name);
	}
	
	/**
	 * @var string
	 */
	private $bagName;
	
	/**
	 * @var Session
	 */
	private $session;
	
	/**
	 * @var \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface
	 */
	private $bag;
	
	function getSessionBagName() {
		return $this->bagName;
	}
	
	/**
	 * @return Session
	 */
	function getSession() {
		return $this->session;
	}
	
	/**
	 * @return \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface
	 */
	function getSessionBag() {
		return $this->bag;
	}

	public function get($key) {
		$this->bag->get($key);
	}
	
	public function set($key, $value) {
		$this->bag->set($key, $value);
	}
}