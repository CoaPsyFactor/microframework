<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of ModelException
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class ModelException extends FrameworkException
{

    const MODEL_NOT_FOUND = 0b000;
    const BAD_RESULT = 0b001;
    const INVALID_FIELD = 0b010;

    protected $messages = [
	self::MODEL_NOT_FOUND => 'Requested model cannot be found',
	self::BAD_RESULT => 'Model result is empty',
	self::INVALID_FIELD => 'Request field is not valid',
    ];

}
