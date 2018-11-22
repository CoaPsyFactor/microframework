<?php

namespace Framework;

/**
 * Description of Controller
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class Controller implements SingletonModule
{

    /** @var array */
    private static $_controllers = [];

    /**
     * 
     * Register route
     * 
     * example:
     * 	// in file app/controllers/home.php
     * 
     * 	$controller = \Framework\Singleton::get('controller');
     * 
     * 	$controller->registerRoute ('GET /welcome', function ($name = 'Guest') {
     * 	    echo "Hello, {$name}.";
     * 	});
     * 
     * example will register route home.welcome
     * paramateres without default values defined in route callback are required
     * 
     * can use GET, POST, PUT
     * 
     * @param string $routePath
     * @param \Closure $callback
     */
    public function registerRoute($routePath, \Closure $callback)
    {
        /* @var $method string */
        /* @var $route string */
        list($method, $route) = explode(' ', $routePath);

        self::$_controllers[strtolower($method)][$this->cleanRoute($route)] = $callback;
    }

    /**
     * 
     * Executes controller with matched route
     * 
     * example:
     * 	// in file app/controllers/home.php
     * 
     * 	$controller = \Framework\Singleton::get('controller');
     * 
     * 	$controller->registerRoute ('GET /welcome', function ($name = 'Guest') {
     * 	    echo "Hello, {$name}.";
     * 	});
     * 
     * call ?route=home.welcome&name=Peter
     * will output Hello, Peter
     * 
     * call ?route=home.welcome
     * will output Hello, Gues
     * 
     * 
     * @return mixed
     * @throws ControllerException
     */
    public function call($_route = '', $_method = null)
    {
        /* @var $input Input */
        $input = Singleton::get('input');

        if (($saveCache = $this->checkCache($input->get('route', '')))) {
            return false;
        }

        list($method, $route, $controller) = $this->getRouteData($_route, $_method);

        /* @var $controllerPath string */
        $controllerPath = $this->getControllerPath($controller);

        if (false === file_exists($controllerPath)) {
            throw (new ControllerException(ControllerException::CONTROLLER_NOT_FOUND))->setMessage("{$controllerPath} not found");
        }

        require_once $controllerPath;

        if (empty(self::$_controllers[$method][$route])) {
            throw (new ControllerException(ControllerException::ROUTE_NOT_REGISTERED))->setMessage("route {$method}:{$route} not found");
        }

        /* @var $function \ReflectionFunction */
        list($function, $arguments) = $this->preapareRouteCallback(self::$_controllers[$method][$route]);

        ob_start();
        $function->invokeArgs($arguments);
        Singleton::get('view')->makeView(ob_get_clean());

        return is_null($saveCache) ? false : $this->getCacheFilePath();
    }

    public function clearCache(array $input = [])
    {
        $cacheFilePath = $this->getCacheFilePath($input);

        if (file_exists($cacheFilePath)) {
            unlink($cacheFilePath);
        }
    }

    /**
     * 
     * @param string $route
     * @param array $parameters
     * @param type $stopExecution
     */
    public function redirect($route, array $parameters = [], $stopExecution = true)
    {
        /* @var $asset Asset */
        $asset = Singleton::get('asset');

        header("Location: {$asset->getRouteUrl($route, $parameters)}");

        if ($stopExecution) {
            exit;
        }
    }

    /**
     * 
     * @param string $defaultRoute
     * @return array
     */
    private function getRouteData($defaultRoute, $_method)
    {
        /* @var $input Input */
        $input = Singleton::get('input');

        $routeData = explode('.', empty($defaultRoute) ? $input->get('route', '', Input::INPUT_GET) : $defaultRoute);

        return [is_null($_method) ? strtolower($input->getOriginalRequestMethod()) : strtolower($_method), $this->cleanRoute(implode('.', array_slice($routeData, 1))), empty($routeData[0]) ? '' : $routeData[0]];
    }

    /**
     * 
     * @param string $route
     * @return boolean
     */
    private function checkCache($route)
    {
        if (false === (bool) Config::getCache()['enabled']) {
            return false;
        }

        if (false === in_array($route, Config::getCache()['routes'])) {
            return null;
        }

        $cacheFilePath = $this->getCacheFilePath();

        if (false === file_exists($cacheFilePath)) {
            return false;
        }

        $modTime = filemtime($cacheFilePath);

        if (time() >= $modTime + Config::getCache()['cache_interval']) {
            unlink($cacheFilePath);

            return false;
        }

        Singleton::get('view')->makeView(file_get_contents($cacheFilePath));

        return true;
    }

    /**
     * 
     * @return string
     */
    private function getCacheFilePath(array $_input = [])
    {
        /* @var $input Input */
        $input = Singleton::get('input')->getAll(null, $_input);

        /* @var $session Session */
        $session = Singleton::get('session');

        asort($input);

        return __DIR__ . '/../../' . Config::getCache(null)['cache_dir'] . '/controllers/' . $session->getSessionId() . base64_encode(json_encode($input));
    }

    /**
     * 
     * @param string $controller
     * @return string
     */
    private function getControllerPath($controller)
    {
        return __DIR__ . '/../../' . Config::getApplication()['controllers_path'] . '/' . $controller . '.php';
    }

    /**
     * 
     * @param \Closure $function
     * @return array
     * @throws ControllerException
     */
    private function preapareRouteCallback(\Closure $function)
    {
        /* @var $reflection ReflectionFunction */
        $reflection = new \ReflectionFunction($function);

        /* @var $arguments array */
        $arguments = [];

        /* @var $input Input */
        $input = Singleton::get('input');

        foreach ($reflection->getParameters() as $parameter) {
            $default = null;

            /* @var $parameter \ReflectionParameter */
            if (false === $parameter->isOptional() && is_null($input->get($parameter->getName(), null))) {
                throw (new ControllerException(ControllerException::ROUTE_MISSING_PARAMTER))->setMessage("Missing argument {$parameter->getName()}");
            } else if ($parameter->isOptional()) {
                $default = $parameter->getDefaultValue();
            }

            $arguments[] = $input->get($parameter->getName(), $default);
        }

        return [$reflection, $arguments];
    }

    /**
     * 
     * @param string $route
     * @return string
     */
    private function cleanRoute($route)
    {
        return str_replace('/', '.', trim($route, "\/\t\n\r\0\x0B"));
    }

    /**
     * 
     * Alias for singleton registration
     * 
     * @return string
     */
    public function getSingletonName()
    {
        return 'controller';
    }

}
