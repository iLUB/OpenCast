<?php

/**
 * Class xoctCache
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctCache extends ilGlobalCache {

	const COMP_PREFIX = ilOpenCastPlugin::PLUGIN_ID;
	/**
	 * @var bool
	 */
	protected static $override_active = false;
	/**
	 * @var array
	 */
	protected static $active_components = array(
		self::COMP_PREFIX,
	);


	/**
	 * @return xoctCache
	 */
	public static function getInstance($component) {
		$service_type = self::getSettings()->getService();
		$xoctCache = new self($service_type);

		$xoctCache->setActive(false);
		self::setOverrideActive(false);

		return $xoctCache;
	}


	//	/**
	//	 * @param null $component
	//	 *
	//	 * @return ilGlobalCache|void
	//	 * @throws ilException
	//	 */
	//	public static function getInstance($component) {
	//		throw new ilException('xoctCache::getInstance() should not be called. Please call xoctCache::getCacheInstance() instead.');
	//	}

	public function init() {
		$this->initCachingService();
		$this->setActive(true);
		self::setOverrideActive(true);
	}


	protected function initCachingService() {
		/**
		 * @var $ilGlobalCacheService ilGlobalCacheService
		 */
		if (!$this->getComponent()) {
			$this->setComponent(ilOpenCastPlugin::PLUGIN_NAME);
		}

		if ($this->isOpenCastCacheEnabled()) {
			$serviceName = self::lookupServiceClassName($this->getServiceType());
			$ilGlobalCacheService = new $serviceName(self::$unique_service_id, $this->getComponent());
			$ilGlobalCacheService->setServiceType($this->getServiceType());
		} else {
			$serviceName = self::lookupServiceClassName(self::TYPE_STATIC);
			$ilGlobalCacheService = new $serviceName(self::$unique_service_id, $this->getComponent());
			$ilGlobalCacheService->setServiceType(self::TYPE_STATIC);
		}

		$this->global_cache = $ilGlobalCacheService;
		$this->setActive(in_array($this->getComponent(), self::getActiveComponents()));
	}


	/**
	 * Checks if live voting is able to use the global cache.
	 *
	 * @return bool
	 */
	private function isOpenCastCacheEnabled() {
		try {
			return (int)xoctConf::getConfig(xoctConf::F_ACTIVATE_CACHE);
		} catch (Exception $exceptione) //catch exception while dbupdate is running. (xoctConf is not ready at that time).
		{
			return false;
		}
	}


	/**
	 * @param $service_type
	 *
	 * @return string
	 */
	public static function lookupServiceClassName($service_type) {
		switch ($service_type) {
			case self::TYPE_APC:
				return 'ilApc';
			case self::TYPE_MEMCACHED:
				return 'ilMemcache';
			case self::TYPE_XCACHE:
				return 'ilXcache';
			case self::TYPE_STATIC:
			default:
				return 'ilStaticCache';
		}
	}


	/**
	 * @return array
	 */
	public static function getActiveComponents() {
		return self::$active_components;
	}


	/**
	 * @param bool $complete
	 *
	 * @return bool
	 * @throws RuntimeException
	 */
	public function flush($complete = false) {
		if (!$this->global_cache instanceof ilGlobalCacheService || !$this->isActive()) {
			return false;
		}

		return parent::flush(true);
	}


	/**
	 * @param $key
	 *
	 * @throws RuntimeException
	 * @return bool
	 */
	public function delete($key) {
		if (!$this->global_cache instanceof ilGlobalCacheService || !$this->isActive()) {
			return false;
		}

		return parent::delete($key);
	}


	/**
	 * @return bool
	 */
	public function isActive() {
		return self::isOverrideActive();
	}


	/**
	 * @return boolean
	 */
	public static function isOverrideActive() {
		return self::$override_active;
	}


	/**
	 * @param boolean $override_active
	 */
	public static function setOverrideActive($override_active) {
		self::$override_active = $override_active;
	}


	/**
	 * @param      $key
	 * @param      $value
	 * @param null $ttl
	 *
	 * @return bool
	 */
	public function set($key, $value, $ttl = NULL) {
		//		$ttl = $ttl ? $ttl : 480;
		if (!$this->global_cache instanceof ilGlobalCacheService || !$this->isActive()) {
			return false;
		}

		$return = $this->global_cache->set($key, $this->global_cache->serialize($value), $ttl);

		return $return;
	}


	/**
	 * @param $key
	 *
	 * @return bool|mixed|null
	 */
	public function get($key) {
		if (!$this->global_cache instanceof ilGlobalCacheService || !$this->isActive()) {
			return false;
		}
		$unserialized_return = $this->global_cache->unserialize($this->global_cache->get($key));

		if ($unserialized_return) {
			return $unserialized_return;
		}

		return NULL;
	}
}

?>
