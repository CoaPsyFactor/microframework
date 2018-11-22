<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of GarbageCleanerException
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class GarbageCleanerException extends FrameworkException
{

    const CONFIG_NOT_FOUND = 0b000;
    const INVALID_CONFIG = 0b001;

    protected $messages = [
	self::CONFIG_NOT_FOUND => 'GarbageCleaner configuration file not found',
	self::INVALID_CONFIG => 'GarbageCleaner configuration file is invalid',
    ];

}
