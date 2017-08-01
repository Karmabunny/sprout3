<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\MultiEdit;
?>


<?php
if (!empty($_POST)) {
	echo '<pre>', Enc::html(print_r($_POST, true)), '</pre>';
}
?>


<form action="" method="post">
	<div id="multiedit-demo">
		<div class="columns">
			<div class="column column-4">
				<?php
				Form::nextFieldDetails('Name', true);
				echo Form::text('m_name', [], []);
				?>
			</div>
			<div class="column column-4">
				<?php
				Form::nextFieldDetails('DOB', true);
				echo Form::datepicker('m_dob', [], []);
				?>
			</div>
			<div class="column column-4">
				<?php
				Form::nextFieldDetails('Photo', true);
				echo Form::fileSelector('m_photo', [], []);
				?>
			</div>
		</div>
	</div>

	<?php
	MultiEdit::display('demo', @$_POST['multiedit_demo'], []);
	?>

	<button type="submit" class="button right">Submit form</button>
</form>