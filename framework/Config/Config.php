<?php

namespace Framework;

/**
 * Description of Config
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class Config implements SingletonModule
{

    /**  @var string */
    private static $_configPath;

    /** @var array */
    private static $_config;

    /** @var array */
    private static $_loaded;

    public static function __callStatic($name, $arguments)
    {
	if (0 !== stripos($name, 'get')) {
	    return;
	}

	self::loadConfig();

	$value = strtolower(substr($name, 3));

	if (empty(self::$_config[$value]) && isset($arguments[0]) && $arguments[0] instanceof FrameworkException) {
	    throw $arguments[0]->setMessage("{$value} configuration not found");
	}

	return empty($arguments[1]) ? self::$_config[$value] : (object) self::$_config[$value];
    }

    /**
     * 
     * @param bool $object
     * @return array
     */
    public static function getDefault($object = false)
    {
	return $object ? (object) self::$_config : self::$_config;
    }

    private static function loadConfig()
    {
	if (self::$_loaded) {
	    return;
	}

        self::$_configPath = __DIR__ . '/../config.json';
        
	if (false === file_exists(self::$_configPath)) {
	    throw new ConfigException(ConfigException::CONFIG_NOT_FOUND);
	}

	$json = file_get_contents(self::$_configPath);
	self::$_config = json_decode($json, true);

	if (JSON_ERROR_NONE !== json_last_error()) {
	    throw new ConfigException(ConfigException::INVALID_CONFIG);
	}

	self::$_loaded = true;
    }

    /**
     * 
     * @return string
     */
    public function getSingletonName()
    {
	return 'config';
    }

}
