<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2015 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Needs;

Needs::googleMaps();
Needs::fileGroup('map_directions');
?>

<div class="directions" data-address="<?php echo Enc::html($address); ?>" data-zoom="<?php echo Enc::html($zoom); ?>">
    <div class="directions-map"></div>

    <form action="javascript:;" class="directions-form -clearfix">

        <p>Enter a street name and/or suburb below to get directions</p>

        <div class="row">

            <div class="col-xs-12 col-lg-8">
                <?php
                Form::nextFieldDetails('Address', false, 'Enter a street name and/or suburb below to get directions');
                echo Form::text('start-address', ['-wrapper-class' => 'white hidden-label', 'placeholder' => 'Street name and/or suburb', 'id' => 'start-address', 'class' => 'directions-txt']);
                ?>
            </div>

            <div class="col-xs-12 col-lg-4">
                <button type="submit" class="button button-block directions-btn no-disable">Get directions</button>
            </div>

        </div>

    </form>

    <div class="directions-list"></div>
</div>
