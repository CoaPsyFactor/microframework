<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework;

/**
 * Description of SingletonModule
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
interface SingletonModule
{
    /**
     * @return string
     */
    public function getSingletonName();
}
