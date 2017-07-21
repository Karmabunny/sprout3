<?php
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
?>


<form action="" method="post">
	<?= Csrf::token(); ?>
	<div class="sql-wrapper white-box -clearfix">

		<div class="columns -clearfix">

			<div class="column column-8">

				<div id="sql-table-wrapper">

				</div>

				<div id="sql-query-wrapper">
					<div class="field-element field-element--textbox">
						<div class="field-label -vis-hidden">
							<label for="query-box">Refine</label>
						</div>
						<div class="field-input sql-input-field">
							<textarea id="query-box" placeholder="Enter query" name="sql" spellcheck="false" class="sql textbox" rows="20"><?= Enc::html(@$_POST['sql']); ?></textarea>
						</div>
					</div>
				</div>

				<div class="variable-replacement">
					<div class="variable-replacement-rows">

					<?php
					$row_num = 0;
					$hidden_class = '';
					foreach ($vars as $var) {
						if ($row_num) $hidden_class = ' field-element--hidden-label';
						?>

						<div class="variable-replacement-row columns">
							<div class="column column-6">
								<div class="field-element field-element--text<?= $hidden_class; ?>">
									<div class="field-label">
										<label for="variable-<?= $row_num; ?>">Variable</label>
										<div class="field-helper">Named: don't include leading colon &nbsp; Numeric: leave blank</div>
									</div>
									<div class="field-input">
										<input type="text" name="vars[<?= $row_num; ?>][key]" class="textbox" placeholder="e.g. name" id="variable-<?= $row_num; ?>" value="<?= Enc::html(@$var['key']); ?>">
									</div>
								</div>
							</div>

							<div class="column column-6">
								<div class="field-element field-element--text<?= $hidden_class; ?>">
									<div class="field-label">
										<label for="replacement-<?= $row_num; ?>">Value</label>
										<div class="field-helper">This will be auto-escaped by PDO</div>
									</div>
									<div class="field-input">
										<input type="text" name="vars[<?= $row_num; ?>][val]" class="textbox" placeholder="e.g. John" id="replacement-<?= $row_num; ?>" value="<?= Enc::html(@$var['val']); ?>">
									</div>
								</div>
							</div>
						</div>

						<?php
						++$row_num;
					}
					?>

					</div>
					<button type="button" class="button button-grey button-icon icon-before icon-add variable-replacement-button-add-row"><span class="-vis-hidden">Add</span></button>
				</div>

				<div class="submit-bar">
					<div class="field-element field-element--checkbox">
						<div class="field-element__input-set">
							<div class="fieldset-input">
								<input type="checkbox" id="do-explain" name="explain" <?= (!empty($_POST['explain']) ? 'checked' : ''); ?> value="1">
								<label for="do-explain">Do EXPLAIN as well</label>
							</div>
						</div>
					</div>

					<div class="field-element field-element--checkbox">
						<div class="field-element__input-set">
							<div class="fieldset-input">
								<input type="checkbox" id="enable-profiling" name="profile" <?= (!empty($_POST['profile']) ? 'checked' : ''); ?> value="1">
								<label for="enable-profiling">Enable Profiling</label>
							</div>
						</div>
					</div>

					<button type="submit" class="save button button-regular button-green icon-after icon-storage">Execute queries</button>
				</div>

			</div>

			<div id="table-list-wrap" class="column column-4">

				<div class="field-element field-element--text field-element--icon-after field-element--icon-after--search field-element--hidden-label">
					<div class="field-label">
						<label for="refine-queries">Refine</label>
					</div>
					<div class="field-input">
						<input type="text" name="refine" class="textbox" placeholder="Refine" id="refine-queries" autocomplete="off">
					</div>
				</div>

				<div class="field-element field-element--select field-element--select--multiple field-element--hidden-label">
					<div class="field-label">
						<label for="quick-query">Refine</label>
					</div>
					<div class="field-input">
						<select class="table-list" size="2" id="quick-query">
							<?php
							foreach ($tables as $t) {
								echo '<option>', Enc::html($t), '</option>';
							}
							?>
						</select>
					</div>
				</div>

			</div>

		</div>

	</div>
</form>