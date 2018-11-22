<?php

namespace Framework;

/**
 * Description of GarbageCleaner
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class GarbageCleaner implements SingletonModule
{

    /** @var string */
    private $_configPath = __DIR__ . '/../config.json';

    /** @var array */
    private $_definition = [];

    /** @var int */
    private $_lastCheck = -1;

    /** @var int */
    private $_checkInterval = -1;

    /** @var array */
    private $_config = [];

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * removes old files from directories defined in config.json under garbage_cleaner->definitions
     */
    public function clean()
    {
        if (time() < $this->_lastCheck + $this->_checkInterval) {
            return null;
        }

        foreach ($this->_definition as $directory => $interval) {
            $directoryPath = __DIR__ . "/../../{$directory}";

            $this->cleanFiles($directoryPath, $interval);
        }

        $this->_config['garbage_cleaner']['last_check'] = time();

        file_put_contents($this->_configPath, json_encode($this->_config, 128));
    }

    private function finishUserRequest()
    {
        header('Connection: close');
        ignore_user_abort();
        flush();
    }

    /**
     * 
     * @param string $directory
     * @param int $interval
     */
    private function cleanFiles($directory, $interval)
    {
        $files = scandir($directory);

        foreach ($files as $file) {

            if (0 === strpos($file, '.')) {
                continue;
            }

            $filePath = "{$directory}/{$file}";
            $time = filemtime($filePath);

            if (time() - $time >= $interval) {
                unlink($filePath);
            }
        }
    }

    /**
     * 
     * @throws GarbageCleanerException
     */
    private function loadConfig()
    {
        $this->_config = Config::getDefault();

        $this->_definition = empty($this->_config['garbage_cleaner']['definition']) ? [] : $this->_config['garbage_cleaner']['definition'];
        $this->_lastCheck = empty($this->_config['garbage_cleaner']['last_check']) ? -1 : (int) $this->_config['garbage_cleaner']['last_check'];
        $this->_checkInterval = empty($this->_config['garbage_cleaner']['check_interval']) ? -1 : (int) $this->_config['garbage_cleaner']['check_interval'];
    }

    /**
     * 
     * @return string
     */
    public function getSingletonName()
    {
        return 'gcleaner';
    }

}
