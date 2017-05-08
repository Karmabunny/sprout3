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
?>
<p>Hi <?= Enc::html($addressee); ?>,</p>

<p>Your changes to the page "<?= Enc::html($page_name); ?>" have been approved.</p>

<?php if (!empty($message)): ?>
    <p><b>The following message was included:</b></p>
    <p><?= Enc::html($message); ?></p>
<?php endif; ?>

<br>
<p>To view the updated page, use the following link:
<br><a href="<?php echo Enc::html($url); ?>"><?php echo Enc::html($url); ?></a></p>