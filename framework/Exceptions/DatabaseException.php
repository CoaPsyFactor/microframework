<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of DatabaseException
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class DatabaseException extends FrameworkException
{

    const CONFIG_NOT_FOUND = 0b001;
    const INVALID_CONFIG = 0b010;

    protected $messages = [
	self::CONFIG_NOT_FOUND => 'Database configuration file not found',
	self::INVALID_CONFIG => 'Database configuration file is not valid json'
    ];

}
