<?php
use Sprout\Helpers\Enc;

$site_email = Kohana::config('sprout.info_email');
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8">

    <title>Website Currently Undergoing Maintenance</title>

    <link href="SKIN/style.css" rel="stylesheet">
    <style>
    body {  background-color: #FFF;  color: #333;  }
    h1, h2, em {                           color: #ED712D;  }
    a:link, a:visited {                    color: #ED712D;  }
    a:hover,a:focus,a:active {    background-color: #ED712D;  color: #fff;  }
    </style>
</head>
<body id="home">
<div id="wrap">

    <div id="logo">
        <img src="SKIN/logo.png" alt="">
    </div>

    <div id="content">
        <h1>Website Currently Undergoing Maintenance</h1>

        <div id="content-inner">

            <p>For any enquiries, please contact:<br></p>

            <h2><?php echo Kohana::config('sprout.site_title'); ?></h2>

            <p><a href="mailto:<?= Enc::html($site_email); ?>"><?= Enc::html($site_email); ?></a></p>

        </div>
    </div>

</div>
</body>
</html>
