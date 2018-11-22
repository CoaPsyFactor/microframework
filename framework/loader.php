<?php

// framework contains ~1700 lines of code


use Framework\Config;
use Framework\Controller;
use Framework\Singleton;
use Framework\View;
use Framework\ViewLayer;

$pathMap = [
    'Framework\\FrameworkException' => __DIR__ . '/Exceptions/FrameworkException.php',
    'Framework\\DatabaseException' => __DIR__ . '/Exceptions/DatabaseException.php',
    'Framework\\SingletonException' => __DIR__ . '/Exceptions/SingletonException.php',
    'Framework\\SessionException' => __DIR__ . '/Exceptions/SessionException.php',
    'Framework\\ControllerException' => __DIR__ . '/Exceptions/ControllerException.php',
    'Framework\\ConfigException' => __DIR__ . '/Exceptions/ConfigException.php',
    'Framework\\ViewException' => __DIR__ . '/Exceptions/ViewException.php',
    'Framework\\GarbageCleanerException' => __DIR__ . '/Exceptions/GarbageCleanerException.php',
    'Framework\\ModelException' => __DIR__ . '/Exceptions/ModelException.php',
    'Framework\\SingletonModule' => __DIR__ . '/Singleton/SingletonModule.php',
    'Framework\\Singleton' => __DIR__ . '/Singleton/Singleton.php',
    'Framework\\Database' => __DIR__ . '/Database/Database.php',
    'Framework\\Input' => __DIR__ . '/Input/Input.php',
    'Framework\\Event' => __DIR__ . '/Event/Event.php',
    'Framework\\Session' => __DIR__ . '/Session/Session.php',
    'Framework\\GarbageCleaner' => __DIR__ . '/GarbageCleaner/GarbageCleaner.php',
    'Framework\\View' => __DIR__ . '/View/View.php',
    'Framework\\Controller' => __DIR__ . '/Controller/Controller.php',
    'Framework\\Model' => __DIR__ . '/Model/Model.php',
    'Framework\\Config' => __DIR__ . '/Config/Config.php',
    'Framework\\Asset' => __DIR__ . '/Asset/Asset.php',
    'Framework\\AssetType' => __DIR__ . '/Asset/AssetType.php',
    'Framework\\ViewLayer' => __DIR__ . '/View/ViewLayer.php'
];

spl_autoload_register(function ($class) use ($pathMap) {

    if (false === isset($pathMap[$class])) {
        return;
    }

    $filePath = $pathMap[$class];

    if (false === file_exists($filePath)) {
        throw new RuntimeException("Framework class {$filePath} not found");
    }

    require_once $filePath;
});


Singleton::registerClass('Framework\\Database');
Singleton::registerClass('Framework\\Input');
Singleton::registerClass('Framework\\Event');
Singleton::registerClass('Framework\\Session', [Singleton::get('database')]);
Singleton::registerClass('Framework\\GarbageCleaner');
Singleton::registerClass('Framework\\Model', [Singleton::get('database')]);
Singleton::registerClass('Framework\\View');
Singleton::registerClass('Framework\\Controller');
Singleton::registerClass('Framework\\Config');
Singleton::registerClass('Framework\\Asset');

Singleton::get('session')->initialize();

/* @var $view View */
$view = Singleton::get('view');

/* @var $controller Controller */
$controller = Singleton::get('controller');

$view->make('header', ['pageTitle' => 'TinyFramework', 'navMenuItems' => []], ViewLayer::HEADER);

$cachePath = $controller->call();

$view->make('footer', [], ViewLayer::FOOTER);

if ($cachePath && Config::getCache()['enabled']) {
    $view->removeLayer(ViewLayer::HEADER)->removeLayer(ViewLayer::FOOTER);

    ob_start();

    $view->render();

    $content = ob_get_clean();

    file_put_contents($cachePath, $content);

    $view->revert();
}

$view->render();

Singleton::get('gcleaner')->clean();
