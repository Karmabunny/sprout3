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
use Sprout\Helpers\Text;
?>
<p>Hi <?php echo Enc::html($approval_operator['name']); ?>,</p>

<p>The page "<?php echo Enc::html($page['name']); ?>" has been updated by <?php echo Enc::html($request_operator['name']); ?>.</p>

<?php if ($changes_made): ?>
<p><b>The following changes were made:</b></p>
<?= Text::richtext($changes_made); ?>
<?php endif; ?>

<br>
<p>To preview and approve or reject the new revision, use the following link:
<br><a href="<?php echo Enc::html($url); ?>"><?php echo Enc::html($url); ?></a></p>