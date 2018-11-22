<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of SessionException
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class SessionException extends FrameworkException
{

    const CONFIG_NOT_FOUND = 0b000;
    const INVALID_CONFIG = 0b001;
    const INVALID_SESSION_FILE = 0b010;

    protected $messages = [
	self::CONFIG_NOT_FOUND => 'Session configuratoin file is not found',
	self::INVALID_CONFIG => 'Invalid session configuration file',
	self::INVALID_SESSION_FILE => 'Invalid session file'
    ];

}
