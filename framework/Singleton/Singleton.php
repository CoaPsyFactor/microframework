<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of Singleton
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class Singleton
{

    /** @var array */
    private static $instances;

    /**
     * 
     * @param string $alias
     * @return object
     * @throws SingletonException
     */
    public static function get($alias)
    {
	$args = func_get_args();
	unset($args[0]);

	if (empty(self::$instances[$alias])) {
	    throw (new SingletonException())->setMessage("Alias {$alias} not registered.");
	}

	return self::$instances[$alias];
    }

    /**
     * 
     * @param string $class
     * @param array $arguments
     * @throws SingletonException
     */
    public static function registerClass($class, array $arguments = [])
    {
	if (false === class_exists($class)) {
	    throw (new SingletonException())->setMessage("Class {$class} not found");
	}

	$reflection = new \ReflectionClass($class);

	if (false === $reflection->isSubclassOf('Framework\\SingletonModule')) {
	    throw (new SingletonException(SingletonException::CLASS_NOT_MODULE))->setMessage("{$class} is not singleton module");
	}

	/* @var $instance SingletonModule */
	$instance = $reflection->newInstanceArgs($arguments);
	self::$instances[$instance->getSingletonName()] = $instance;
    }

}
