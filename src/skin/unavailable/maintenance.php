<?php
use Sprout\Helpers\Enc;

$site_email = Kohana::config('sprout.info_email');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Website Currently Undergoing Maintenance</title>

    <link href="SKIN/style.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
</head>
<body>

    <div class="content">

        <div class="container">
            <img class="logo" src="SKIN/logo.svg" alt="">

            <h1><span>Website Currently <br></span>Undergoing Maintenance</h1>
        </div>

        <div class="content_details reverse-text container">

            <p>For any enquiries, please contact:<br></p>

            <h2><?php echo Kohana::config('sprout.site_title'); ?></h2>

            <p><a href="mailto:<?= Enc::html($site_email); ?>"><?= Enc::html($site_email); ?></a></p>

        </div>
    </div>

</div>
</body>
</html>
