<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace SproutModules\Karmabunny\Demo\Controllers;

use InvalidArgumentException;

use Sprout\Controllers\Controller;
use Sprout\Helpers\FrontEndEntrance;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\TreenodeRedirectMatcher;
use Sprout\Helpers\View;


/**
 * A basic demonstration of a front-end controller
 */
class DemoController extends Controller implements FrontEndEntrance
{

    public function _getEntranceArguments()
    {
        return [
            'aaa' => 'AAA',
            'bbb' => 'BBB',
        ];
    }


    public function entrance($argument)
    {
        switch ($argument) {
            case 'aaa': $this->aaa(); break;
            case 'bbb': $this->bbb(); break;
            default:
                throw new InvalidArgumentException();
        }
    }


    public function aaa()
    {
        Navigation::setPageNodeMatcher(new TreenodeRedirectMatcher('aaa'));

        $view = new View('modules/Demo/aaa');

        $skin = new View('skin/inner');
        $skin->page_title = 'AAA';
        $skin->main_content = $view->render();
        echo $skin->render();
    }


    public function bbb()
    {
        Navigation::setPageNodeMatcher(new TreenodeRedirectMatcher('bbb'));

        $view = new View('modules/Demo/bbb');

        $skin = new View('skin/wide');
        $skin->page_title = 'BBB';
        $skin->main_content = $view->render();
        echo $skin->render();
    }

}