<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of Input
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class Input implements SingletonModule
{

    const INPUT_GET = INPUT_GET;
    const INPUT_POST = INPUT_POST;
    const INPUT_SERVER = INPUT_SERVER;

    private $input = [];

    public function __construct()
    {

        $this->input = [
            self::INPUT_GET => filter_input_array(INPUT_GET),
            self::INPUT_POST => filter_input_array(INPUT_POST),
            self::INPUT_SERVER => filter_input_array(INPUT_SERVER)
        ];
    }

    /**
     * 
     * @param string $name
     * @param mixed $_default
     * @param int|null $_method
     * @return mixed
     */
    public function get($name, $_default = null, $_method = null)
    {
        $method = is_null($_method) ? $this->getRequestMethod() : $_method;

        if (false === isset($this->input[$method][$name]) || empty($this->input[$method][$name]) && false === is_numeric($this->input[$method][$name])) {
            return $_default;
        }

        return $this->input[$method][$name];
    }

    /**
     * 
     * @param int $_method
     * @param array $_default
     * @return array
     */
    public function getAll($_method = null, $_default = [])
    {
        $method = is_null($_method) ? $this->getRequestMethod() : $_method;

        return false === empty($this->input[$method]) ? $this->input[$method] : $_default;
    }

    /**
     * 
     * @param string $name
     * @param int $_method
     * @return bool
     */
    public function has($name, $_method = null)
    {
        $method = is_null($_method) ? $this->getRequestMethod() : $_method;

        return isset($this->input[$method][$name]);
    }

    /**
     * 
     * @param string $name
     * @param \Closure $callback
     * @param int $method
     */
    public function ifHas($name, \Closure $callback, $method = null)
    {
        if ($this->has($name, $method)) {
            return $callback();
        }

        return -1;
    }

    /**
     * 
     * @return int
     */
    public function getRequestMethod()
    {
        switch ($this->getOriginalRequestMethod()) {
            case 'POST':
            case 'PUT':
                return self::INPUT_POST;
            default:
                return self::INPUT_GET;
        }
    }

    /**
     * 
     * @return string
     */
    public function getOriginalRequestMethod()
    {
        return $this->input[self::INPUT_SERVER]['REQUEST_METHOD'];
    }

    /**
     * 
     * @return bool
     */
    public function isAjax()
    {
        $requestWith = empty($this->input[self::INPUT_SERVER]['HTTP_X_REQUESTED_WITH']) ? '' : $this->input[self::INPUT_SERVER]['HTTP_X_REQUESTED_WITH'];

        return false === empty($requestWith) && strtolower($requestWith) == 'xmlhttprequest';
    }

    /**
     * 
     * @return string
     */
    public function getSingletonName()
    {
        return 'input';
    }

}
