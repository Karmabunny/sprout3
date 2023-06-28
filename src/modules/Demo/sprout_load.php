<?php
use Sprout\Helpers\Register;
use SproutModules\Sample\Demo\Controllers\DemoController;

Register::frontEndController(DemoController::class, 'Demo');
