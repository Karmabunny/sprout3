<?php ?>

<?php echo $error ?>

<?php if ($description) echo "\n", $description, "\n" ?>

<?php if ( ! empty($line) AND ! empty($file)): ?>
<?php echo "WHERE: ", $file, ': ', $line ?>
<?php endif ?>

<?php echo "\nMESSAGE:\n", $message, "\n" ?>

<?php echo "Log ID {$log_id}\n"; ?>

<?php if ( ! empty($trace)): ?>
STACK TRACE:
<?php echo strip_tags($trace) ?>
<?php endif ?>

