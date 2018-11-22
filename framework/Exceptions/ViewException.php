<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of ViewException
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class ViewException extends FrameworkException
{

    const VIEW_NOT_FOUND = 0b000;

    protected $messages = [
	self::VIEW_NOT_FOUND => 'Request view not found'
    ];

}
