<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of Session
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class Session implements SingletonModule
{

    /** @var bool */
    private static $initialized;

    /** @var array */
    private $_config = [];

    /** @var string */
    private $_sessionId = null;

    /** @var string */
    private $_sessionFile = null;

    /** @var array */
    private $_sessionData = [];

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * 
     * @return string
     */
    public function getSessionId()
    {
        $this->_sessionId = filter_input(INPUT_COOKIE, $this->_config->name);

        if (false == $this->_sessionId) {
            $this->generateSessionId();
        }

        return $this->_sessionId;
    }

    /**
     * 
     * @param string $name
     * @param mixed $value
     * @param bool $save
     */
    public function set($name, $value, $save = true)
    {
        $this->_sessionData[$name] = $value;

        if ($save) {
            $this->save();
        }
    }

    /**
     * 
     * @param array $data
     */
    public function setAll(array $data = [])
    {
        foreach ($data as $name => $value) {
            $this->set($name, $value, false);
        }

        $this->save();
    }

    /**
     * 
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return empty($this->_sessionData[$name]) ? $default : $this->_sessionData[$name];
    }

    /**
     * 
     * @param array $names
     * @return array
     */
    public function getAll(array $names = [])
    {
        $results = [];

        foreach ($names as $name => $default) {
            $results[$name] = $this->get($name, $default);
        }

        return $results;
    }

    /**
     * 
     * @return bool
     */
    public function save()
    {
        $dataEncrypted = openssl_encrypt(json_encode($this->_sessionData), 'aes128', $this->_config->secret);

        return file_put_contents($this->_sessionFile, $dataEncrypted);
    }

    /**
     * 
     * @return boolean
     */
    public function initialize()
    {

        if (self::$initialized) {
            return;
        }

        $this->_sessionFile = "{$this->_config->save_dir}/{$this->getSessionId()}";

        $fileExists = file_exists($this->_sessionFile);
        $fileValid = $fileExists ? time() <= filemtime($this->_sessionFile) + $this->_config->lifetime * 60 : false;

        if (false === $fileExists) {
            touch($this->_sessionFile);
        } else if ($fileExists && false === $fileValid) {
            $this->destroy();
            
            return $this->initialize();
        }

        $this->loadSessionData();

        self::$initialized = true;

        return true;
    }

    public function destroy()
    {
        setcookie($this->_config->name, $this->_sessionId, time() - 3600);

        unlink($this->_sessionFile);

        $this->_sessionData = [];
        $this->_sessionFile = null;
        $this->_sessionId = null;

        self::$initialized = false;
    }

    /**
     * 
     * @throws SessionException
     */
    private function loadSessionData()
    {
        if (false === file_exists($this->_sessionFile)) {
            $this->_sessionData = [];

            return;
        }

        $dataEncrypted = file_get_contents($this->_sessionFile);

        $data = openssl_decrypt($dataEncrypted, 'aes128', $this->_config->secret);

        $this->_sessionData = json_decode($data, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw (new SessionException(SessionException::INVALID_SESSION_FILE))->setMessage(json_last_error_msg());
        }
    }

    private function generateSessionId()
    {
        $this->_sessionId = hash('sha256', uniqid('_sess_', true));

        setcookie($this->_config->name, $this->_sessionId, time() + ($this->_config->lifetime * 60));
    }

    /**
     * @throws DatabaseException
     */
    private function loadConfig()
    {
        $this->_config = Config::getSession(new SessionException(SessionException::CONFIG_NOT_FOUND), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw (new SessionException(SessionException::INVALID_CONFIG))->setMessage(json_last_error_msg());
        }
    }

    /**
     * 
     * @return string
     */
    public function getSingletonName()
    {
        return 'session';
    }

}
