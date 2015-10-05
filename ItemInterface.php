<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
 */
namespace humhub\modules\cfiles;

/**
 *
 * @author luke
 */
interface ItemInterface
{

    public function getItemId();

    public function getIconClass();

    public function getTitle();

    public function getSize();

    public function getUrl();

    public function getCreator();
}
