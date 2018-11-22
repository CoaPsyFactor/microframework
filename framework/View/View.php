<?php

namespace Framework;

/**
 * Description of View
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class View implements SingletonModule
{

    /** @var array */
    private static $_queue;

    /** @var array */
    private static $_removed;

    /**
     * 
     * @param string $path
     * @param array $values
     * @return int
     * @throws ViewException
     */
    public function make($path, array $values = [], $layer = ViewLayer::CONTENT)
    {
        /* @var $viewPath string */
        $viewPath = $this->getViewPath($path);

        if (false === file_exists($viewPath)) {
            throw (new ViewException(ViewException::VIEW_NOT_FOUND))->setMessage("View {$viewPath} not found");
        }

        if (empty(self::$_queue['paths'][$layer])) {
            self::$_queue['paths'][$layer] = [];
        }

        self::$_queue['paths'][$layer][] = $viewPath;

        if (empty(self::$_queue['values'])) {
            self::$_queue['values'] = [];
        }

        self::$_queue['values'] = array_merge(self::$_queue['values'], $values);

        return $this;
    }

    /**
     * 
     * @param string $html
     */
    public function makeView($html = '', $layer = ViewLayer::CONTENT)
    {
        if (empty(self::$_queue['paths'][$layer])) {
            self::$_queue['paths'][$layer] = [];
        }

        self::$_queue['paths'][$layer][] = [$html];

        return $this;
    }

    /**
     * 
     * @param int $layer
     * @return View
     */
    public function removeLayer($layer)
    {
        if (isset(self::$_queue['paths'][$layer])) {
            self::$_removed[$layer] = self::$_queue['paths'][$layer];
            self::$_queue['paths'][$layer] = [];
        }

        return $this;
    }

    /**
     * 
     * @return \Framework\View
     */
    public function revert()
    {
        if (false === is_array(self::$_removed)) {
            self::$_removed = [];
        }

        foreach (self::$_removed as $layer => $path) {
            self::$_queue['paths'][$layer] = $path;
        }

        ksort(self::$_queue['paths']);

        return $this;
    }

    public function render()
    {
        self::$_queue['values']['assetObj'] = Singleton::get('asset');
        self::$_queue['values']['input'] = Singleton::get('input');
        self::$_queue['values']['session'] = Singleton::get('session');
        self::$_queue['values']['currentRoute'] = self::$_queue['values']['input']->get('route', '');

        extract(self::$_queue['values']);

        $this->preapareForAjax();
        
        foreach (self::$_queue['paths'] as $paths) {
            if (false === is_array($paths)) {
                continue;
            }

            foreach ($paths as $path) {
                if (is_array($path)) {
                    echo $path[0];
                } else {
                    require $path;
                }
            }
        }

        $this->finishAjaxRequest();
        
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getSingletonName()
    {
        return 'view';
    }

    private function preapareForAjax()
    {
        /* @var $input Input */
        $input = Singleton::get('input');

        if (false === $input->isAjax()) {
            return;
        }

        header('Content-Type: application/json');
        $this->removeLayer(ViewLayer::HEADER)->removeLayer(ViewLayer::FOOTER);

        ob_start();
    }

    private function finishAjaxRequest()
    {
        /* @var $input Input */
        $input = Singleton::get('input');
        
        if (false === $input->isAjax()) {
            return;
        }
        
        $content = ob_get_clean();
        
        echo json_encode(['data' => $content]);
    }
    
    /**
     * 
     * @param string $path
     * @return string
     */
    private function getViewPath($path)
    {
        return __DIR__ . '/../../' . Config::getApplication()['views_path'] . '/' . $path . '.php';
    }

}
