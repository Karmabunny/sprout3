<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Sprout;
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>500 Internal Server Error</title>
    <style type="text/css">
    body {font-family:sans-serif;margin:0;padding:0;}
    #main {width: 600px; margin: 0 auto; padding: 0;}
    h1 {margin: 0 0 40px 0; text-align: center; background: #70B100; color: #FFF; padding: 15px; -webkit-border-bottom-right-radius: 3px; -webkit-border-bottom-left-radius: 3px; -moz-border-radius-bottomright: 3px;-moz-border-radius-bottomleft: 3px;border-bottom-right-radius: 3px;border-bottom-left-radius: 3px;}
    img {margin: 0; width: 227px; height: 300px; margin: 0 auto; display:block;background:url(data:image/gif;base64,R0lGODlhBQAEAKEBAAAAAP%2F%2F%2F%2F%2F%2F%2F%2F%2F%2F%2FyH5BAEKAAIALAAAAAAFAAQAAAIHDBypBncJCgA7) 50% 50% no-repeat;}
    #nice {background: #333; color: #eee; margin: 45px 0 0 0; padding: 1px 15px; font-size: 90%; border-radius: 3px; line-height: 1.7em; }
    #tech {background: #222; color: #eee; margin: 15px 0 0 0; padding: 1px 15px; font-size: 70%; border-radius: 3px;}
    </style>
</head>
<body>
    <div id="main">
        <h1>SERVER ERROR</h1>
        <img src="<?php echo Sprout::absRoot(); ?>media/images/system_error3.jpg" width="227" height="300">
        <div id="nice">
            <p>Something has gone wrong. This isn't your fault. The exact issue is being investigated. Hang tight.</p>
            <p>When reporting this error, quote the code <?php echo 'SE' . str_pad($log_id, 5, '0', STR_PAD_LEFT); ?>.</p>
        </div>
        <div id="tech">
            <h3><?php echo Enc::html($error) ?></h3>
            <p><?php echo Enc::html($description) ?></p>
            <p><code class="block"><?php echo Enc::html($message) ?></code></p>
        </div>
    </div>
</body>
</html>
