<?php

namespace Framework;

/**
 * Description of Asset
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class Asset implements SingletonModule
{

    /**
     * 
     * @param string $asset
     * @return string
     */
    public function getAssetUrl($asset, $assetType = AssetType::ANY)
    {
        if ($assetType != AssetType::ANY) {
            $route = Config::getApplication()['assets_path'] . '/' . $assetType . '/' . trim($asset, '/');
        } else {
            $route = Config::getApplication()['assets_path'] . '/' . trim($asset, '/');
        }
        
        return file_exists($route) ? $route : '';
    }

    /**
     * 
     * @param string $route
     * @param array $parameters
     * @return string
     */
    public function getRouteUrl($route, array $parameters = [])
    {
        $url = "index.php?route={$route}&";

        foreach ($parameters as $parameter => $value) {
            $url .= "{$parameter}={$value}&";
        }

        return $url;
    }

    public function getSingletonName()
    {
        return 'asset';
    }

}
