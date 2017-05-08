<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Router;

Form::setData($data);
?>

<h2>Existing files</h2>
<p><a href="<?= Enc::html(Router::$current_uri); ?>?file=<?= Enc::url('43_already.jpg'); ?>">43_already.jpg</a></p>
<p><a href="<?= Enc::html(Router::$current_uri); ?>?file=<?= Enc::url('39_oh.mp3'); ?>">39_oh.mp3</a></p>
<p><a href="<?= Enc::html(Router::$current_uri); ?>?file=">BLANK</a></p>

<form method="post">
<?php
Form::nextFieldDetails('File', false);
echo Form::chunkedUpload('file', [], ['sess_key' => 'testing_file_upload']);
?>

<p><input type="submit" class="button"></p>
</form>