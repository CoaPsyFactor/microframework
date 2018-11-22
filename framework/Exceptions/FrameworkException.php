<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of FrameworkException
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class FrameworkException extends \Exception
{

    /** @var array */
    protected $messages = [];

    /** @var string */
    protected $message;

    public function __construct($code = 0, \Exception $previous = null)
    {
	$this->message = $this->message ? $this->message : empty($this->messages[$code]) ? null : $this->messages[$code];

	if (empty($this->message)) {
	    $class = get_called_class();

	    parent::__construct("Unknown framework exception in '{$class}'", 0, $previous);
	} else {
	    parent::__construct($this->message, (int) $code, $previous);
	}
    }

    public function setMessage($message)
    {
	$this->message = $message;

	return $this;
    }

}
