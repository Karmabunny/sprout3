<?php
use Sprout\Helpers\Constants;
use Sprout\Helpers\Enc;
use Sprout\Helpers\I18n;
?>


<div class="highlight row">
    <div class="col-xs-12 col-sm-4">
        <b>Revision status:</b>
        <br>
        <?= Enc::html(Constants::$rev_statuses[$page['status']]); ?>
    </div>
    <div class="col-xs-12 col-sm-4">
        <b>Editor:</b>
        <br>
        <?= Enc::html($page['modified_editor']); ?>
    </div>
    <div class="col-xs-12 col-sm-4">
        <b>Modified:</b>
        <br>
        <?= I18n::shortdate(strtotime($page['date_modified'])); ?>
    </div>
</div>
