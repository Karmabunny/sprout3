<?php
use Sprout\Helpers\Enc;


?>


<div class="inline-buttons sidebar-action-buttons -clearfix">
    <a class="icon-after icon-add button button-small" href="SITE/admin/add/extra_page">Add snippet page</a>
</div>

<?php if (count($snippets)): ?>
<div class="sidebar-box">
    <ul class="list-style-1">
    <?php foreach($snippets as $id => $label): ?>
        <li class="ext_txt"><a href="SITE/admin/edit/extra_page/<?php echo Enc::html($id); ?>">Edit <?php echo Enc::html($label); ?></a></li>
     <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
