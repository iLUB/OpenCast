<?php
/**
 * Class xoctCache
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctCache extends ilGlobalCache {

	const COMP_OPENCAST = ilOpenCastPlugin::PLUGIN_ID;
	/**
	 * @var bool
	 */
	protected static $override_active = false;
	/**
	 * @var array
	 */
	protected static $active_components = array(
		self::COMP_OPENCAST,
	);


	/**
	 * @return xoctCache
	 */
	public static function getCacheInstance() {
		require_once('./include/inc.ilias_version.php');
		$service_type = extension_loaded('apc') ? self::TYPE_APC : self::TYPE_STATIC;
		if (str_replace('.', '', ILIAS_VERSION_NUMERIC) >= 510) {
			//			$xoctCache = parent::getInstance(self::COMP_OPENCAST);
			$xoctCache = new self($service_type);
			$xoctCache->initCachingService();
			$xoctCache->setActive(true);
		} else {
			$xoctCache = new self($service_type);
		}
		$xoctCache->setActive(true);
		$xoctCache->setOverrideActive(true);

		return $xoctCache;
	}


	/**
	 * @param null $component
	 *
	 * @return ilGlobalCache|void
	 * @throws ilException
	 */
	public static function getInstance($component) {
		throw new ilException('xoctCache::getInstance() should not be called. Please call xoctCache::getCacheInstance() instead.');
	}


	protected function initCachingService() {
		/**
		 * @var $ilGlobalCacheService ilGlobalCacheService
		 */
		if (!$this->getComponent()) {
			$this->setComponent('default');
		}
		$serviceName = self::lookupServiceClassName($this->getServiceType());
		$ilGlobalCacheService = new $serviceName(self::$unique_service_id, $this->getComponent());
		$ilGlobalCacheService->setServiceType($this->getServiceType());
		$this->global_cache = $ilGlobalCacheService;
		$this->setActive(in_array($this->getComponent(), self::getActiveComponents()));
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
				break;
			case self::TYPE_MEMCACHED:
				return 'ilMemcache';
				break;
			case self::TYPE_XCACHE:
				return 'ilXcache';
				break;
			default:
				return 'ilStaticCache';
				break;
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

		return parent::flush($complete); // TODO: Change the autogenerated stub
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

		return parent::delete($key); // TODO: Change the autogenerated stub
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
	public function set($key, $value, $ttl = null) {
		//		$ttl = $ttl ? $ttl : 480;
		if (!$this->global_cache instanceof ilGlobalCacheService || !$this->isActive()) {
			return false;
		}
		$this->global_cache->setValid($key);

		return $this->global_cache->set($key, $this->global_cache->serialize($value), $ttl);
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
			if ($this->global_cache->isValid($key)) {
				return $unserialized_return;
			}
		}

		return null;
	}
}

?>
