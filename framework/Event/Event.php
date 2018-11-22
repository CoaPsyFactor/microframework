<?php

namespace Framework;

/**
 * Description of Event
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class Event implements SingletonModule
{

    /** @var array */
    private static $_events = [];

    /** @var array */
    private static $_queue = [];

    /** @var bool */
    private static $_initialized;

    /** @var string */
    private $prefix = '';

    /**
     * 
     * Register event
     * 
     * @param string $name
     * @param \Closure $callback
     * @return int id of event
     */
    public function registerEvent($name, \Closure $callback)
    {
        $realName = "{$this->prefix}{$name}";

        if (empty(self::$_events[$realName])) {
            self::$_events[$realName] = [];
        }

        self::$_events[$realName][] = $callback;

        return count(self::$_events[$realName]) - 1;
    }

    /**
     * 
     * Fire group of events registered under same alias, or specify id to execute specific
     * 
     * @param string $name
     * @return mixed
     */
    public function fire($name, array $args = [], $id = null)
    {
        $this->loadEvents();

        if (empty(self::$_events[$name])) {
            return false;
        }

        if (count(self::$_events[$name]) === 1) {
            $id = key(self::$_events[$name]);
        }

        $results = [];

        if (false === is_null($id) && false === empty(self::$_events[$name][$id])) {
            $reflection = new \ReflectionFunction(self::$_events[$name][$id]);
            $results = $reflection->invokeArgs($args);
        } else if (false == $id) {
            foreach (self::$_events[$name] as $event) {
                $reflection = new \ReflectionFunction($event);
                $results[] = $reflection->invokeArgs($args);
            }
        }

        return $results;
    }

    /**
     * 
     * Add list of events or one specified event to queue list
     * 
     * @param string $name
     * @param int $id
     * @return null
     */
    public function queue($name, array $args = [], $id = null)
    {
        if (empty(self::$_events[$name])) {
            return;
        }

        $event = is_null($id) ? self::$_events[$name] : self::$_events[$name][(int) $id];

        array_unshift($args, $this, Singleton::get('database'), Singleton::get('session'), Singleton::get('input'), Singleton::get('model'));

        self::$_queue[] = [$event, $args];
    }

    /**
     * Fires all events registered in queue list
     */
    public function fireQueue()
    {
        $this->loadEvents();

        foreach (self::$_queue as $queue) {
            if (false === is_array($queue[0])) {
                (new \ReflectionFunction($queue[0]))->invokeArgs($queue[1]);
                continue;
            }

            foreach ($queue[0] as $q) {
                (new \ReflectionFunction($q))->invokeArgs($queue[1]);
            }
        }
    }

    /**
     * 
     * Changes prefix of event
     * 
     * example:
     * 	$event = Singleton::get('event');
     * 	
     * 	$event->setGroup('admins');
     * 
     * 	$event->registerEvent('new-client', function ($username) {
     * 	    echo "New client registered, username {$username};
     * 	});
     * 
     * triggering of this event would look like this
     * 
     * 	$event->fire('admins.new-client', 'Test User'); 
     *  
     *
     * 
     * @param string $prefix
     */
    public function setGroup($prefix)
    {
        $this->prefix = trim($prefix, ".\t\b\r\0\x0B") . '.';
    }

    /**
     * 
     * @return string
     */
    public function getSingletonName()
    {
        return 'event';
    }

    private function loadEvents()
    {
        if (self::$_initialized) {
            return;
        }

        $eventsDirectory = __DIR__ . '/../../' . Config::getApplication()['events_path'];

        foreach (scandir($eventsDirectory) as $eventFile) {
            if (0 === strpos($eventFile, '.')) {
                continue;
            }

            require_once "{$eventsDirectory}/{$eventFile}";
        }
    }

}
