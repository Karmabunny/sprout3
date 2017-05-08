<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2016 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use Sprout\Helpers\Admin;
use Sprout\Helpers\Fb;




Fb::setData($data);
?>


<?php Fb::heading('Text'); ?>
<?php Fb::richtext('text', 600, 400); ?>


<?php Fb::heading('Metadata'); ?>
<?php Fb::section(); ?>

<?php Fb::title('Keywords'); ?>
<?php Fb::text('meta_keywords', 'size="30"'); ?>

<?php Fb::title('Description'); ?>
<?php Fb::text('meta_description', 'size="30"'); ?>

<?php Fb::title('Alt web-browser title'); ?>
<?php Fb::text('alt_browser_title', 'size="30"'); ?>

<?php Fb::endsection(); ?>


<?php Admin::clearFieldErrors(); ?>
