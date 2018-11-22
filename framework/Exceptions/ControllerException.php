<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of ControllerException
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class ControllerException extends FrameworkException
{

    const CONTROLLER_NOT_FOUND = 0b000;
    const ROUTE_NOT_REGISTERED = 0b001;
    const ROUTE_MISSING_PARAMTER = 0b010;

    protected $messages = [
	self::CONTROLLER_NOT_FOUND => 'Request controller is not found',
	self::ROUTE_NOT_REGISTERED => 'Request route is not registered',
	self::ROUTE_MISSING_PARAMTER => 'Requested route is missing some paramters',
    ];

}
