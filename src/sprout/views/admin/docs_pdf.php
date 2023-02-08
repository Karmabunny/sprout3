<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Sprout;
?>
<html><head>
	<title><?= Enc::html($title) ?></title>
	<base href="<?php echo Sprout::absRoot(); ?>">
	<style><?php echo file_get_contents(APPPATH . 'media/css/admin_docs.css'); ?></style>
</head>
<body class="docpdf" id="main-content">
	<?php echo $main_content; ?>
</body>
</html>
