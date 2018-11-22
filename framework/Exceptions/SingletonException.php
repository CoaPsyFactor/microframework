<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of SingletonException
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class SingletonException extends FrameworkException
{

    const CLASS_NOT_REGISTERED = 0b001;
    const CLASS_NOT_FOUND = 0b010;
    const CLASS_NOT_MODULE = 0b011;

    protected $messages = [
	self::CLASS_NOT_REGISTERED => 'Requested class not registered',
	self::CLASS_NOT_FOUND => 'Requested class does not exist',
	self::CLASS_NOT_MODULE => 'Requested class is not module',
    ];

}
