<?php
use Sprout\Helpers\Enc;
?>
<div id="popup-wrap">
    <?php if (!empty($page_title)): ?>
        <h1><?php echo Enc::html($page_title); ?></h1>
    <?php endif; ?>
    <?php echo $main_content; ?>
 </div>
