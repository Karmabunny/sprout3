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

use Sprout\Helpers\BaseView;
use Sprout\Helpers\Validator;


/**
 * A basic demonstration of how {@see MultiStepFormController} works
 */
class MultiStepDemoController extends \Sprout\Controllers\MultiStepFormController
{
    protected $steps = [
        1 => 'email',
        2 => 'phone',
        3 => 'details',
    ];
    protected $session_key = 'multistep_demo';
    protected $route = 'multistep_demo';
    protected $page_title = 'Multistep demo';
    protected $view_dir = 'modules/Demo/steps';
    protected $table = 'multistep_demo_submissions';


    /**
     * @route multistep_demo/complete
     */
    public function complete()
    {
        parent::complete();
    }


    /**
     * @route multistep_demo
     * @route multistep_demo/{step}
     * @param int $step
     */
    public function form($step = -1) {
        $step = (int) $step;
        $first_step = $this->firstStep();
        if ($step < $first_step) $step = $first_step;

        $view = parent::form($step);
        $content = $view->render();

        $page_view = BaseView::create('skin/inner');
        $page_view->main_content = $content;
        $page_view->page_title = "{$this->page_title}: step {$view->step} of {$view->steps}";
        echo $page_view->render();
    }


    /**
     * @route multistep_demo/submit/{step}
     * @param int $step
     */
    public function submit($step)
    {
        $step = (int) $step;
        $first_step = $this->firstStep();
        if ($step < $first_step) $step = $first_step;

        parent::submit($step);
    }


    protected function emailSubmit($step)
    {
        $required = ['email'];
        $rules = [
            ['email', 'Validity::email'],
            ['email', 'Validity::length', 0, 60],
        ];
        $this->validate($step, $required, $rules);
    }


    protected function phoneSubmit($step)
    {
        $required = [];
        $rules = [
            ['phone', 'Validity::phone'],
            ['phone', 'Validity::length', 0, 20],
            ['mobile', 'Validity::phone'],
            ['mobile', 'Validity::length', 0, 20],
        ];

        Validator::trim($_POST);
        $valid = new Validator($_POST);
        $valid->multipleCheck(['phone', 'mobile'], 'Validity::oneRequired');
        $this->validate($step, $required, $rules, $valid);
    }


    protected function detailsSubmit($step)
    {
        Validator::trim($_POST);
        $valid = new Validator($_POST);
        $valid->setFieldLabel('why_love', 'Why you love us');
        $required = ['why_love'];
        $rules = [
            ['how_heard', 'Validity::inEnum', 'multistep_demo_submissions', 'how_heard'],
            ['why_love', 'Validity::length', 0, PHP_INT_MAX],
        ];
        $this->validate($step, $required, $rules, $valid);
    }

}