<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Sprout;


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no">
    <!--[if !mso]><!-- -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <!--<![endif]-->
    <!--[if mso]>
    <style type=”text/css”>
    body,
    .fallback-text {
    font-family: Arial, sans-serif;
    }
    </style>
    <![endif]-->
    <title><?= Enc::html(!empty($html_title)? $html_title : Kohana::config('sprout.site_title')); ?></title>

<style type="text/css">
    /* Hotmail fixes*/
    .ReadMsgBody{
        width:100%;
        background-color: #ededed;
    }
    .ExternalClass{
        width:100%;
        background-color: #ededed;
    }

    body{
        -webkit-font-smoothing:antialiased;
        background-color: #ededed
    }
    h1,h2,h3,h4,h5 { margin-top:0; margin-right:0; margin-left:0; margin-bottom:26px; }
    h1,.h1{
        color:#000000 !important;
        font-family: 'Open Sans', 'Franklin Gothic Medium', Futura, Trebuchet MS, Arial, sans-serif;
        font-size:24px;
        font-weight:bold;
        mso-line-height-rule:exactly;
        line-height:32px;
    }
    h2,.h2{
        color:#F77752 !important;
        font-family: 'Open Sans', 'Franklin Gothic Medium', Futura, Trebuchet MS, Arial, sans-serif;
        font-size:22px;
        font-weight:bold;
        mso-line-height-rule:exactly;
        line-height:32px;
    }
    h3,.h3{
        color:#000000 !important;
        font-family: 'Open Sans', 'Franklin Gothic Medium', Futura, Trebuchet MS, Arial, sans-serif;
        font-size:20px;
        font-weight:bold;
        mso-line-height-rule:exactly;
        line-height: 26px;
    }
    h4,.h4{
        color:#F77752 !important;
        font-family: 'Open Sans', 'Franklin Gothic Medium', Futura, Trebuchet MS, Arial, sans-serif;
        font-size:16px;
        font-weight:bold;
    }
    h5,.h5{
        color:#303030 !important;
        font-family: 'Open Sans', 'Franklin Gothic Medium', Futura, Trebuchet MS, Arial, sans-serif;
        font-size:16px;
        font-weight:bold;
    }
    p, li, ul, ol {
        font-size: 16px;
        color: #303030;
        font-weight: normal;
        text-align: left;
        font-family: 'Open Sans', Arial, Helvetica, sans-serif;
        mso-line-height-rule:exactly;
        line-height: 26px;
        margin-top: 0px;
        margin-bottom: 26px;
    }
    ul, ol {
        padding-left: 20px;
    }
    a, a:link,a:visited,a .yshortcuts{
        color: #68AF98 !important;
    }
    a:hover,a:focus,a:active {
        color: #68AF98 !important;
        text-decoration: none;
    }
    /* Style iOS auto links to not look like links */
    .autolink-fix a {
        color: #303030 !important;
        text-decoration: none !important;
    }
    a img {
        border: 0;
    }

    table{
        border-collapse:collapse;
    }

    @media only screen and (max-device-width: 619px), screen and (max-width: 619px){
        .wrappper {
            padding: 10px!important;
        }
        .center-mob{
            text-align:center!important;
        }
        .right-mob{
            text-align:right!important;
        }
        .left-mob{
            text-align:left!important;
        }
        .deviceWidth,
        .paddedDeviceWidth {
            padding:0;
            max-width:100%!important;
        }
        .deviceWidth {
            width:480px!important;
            max-height:none!important;
        }
        .paddedDeviceWidth {
            width:440px!important;
        }
    }
    @media only screen and (max-device-width: 560px), screen and (max-width: 560px){
        .right-mob-sm {
            text-align:right!important;
        }
        .left-mob-sm {
            text-align:left!important;
        }
        .deviceWidth {
            width:380px!important;
        }
        .paddedDeviceWidth {
            width:340px!important;
        }
    }
    @media only screen and (max-device-width: 420px), screen and (max-width: 420px){
        .right-mob-xs{
            text-align:right!important;
        }
        .left-mob-xs{
            text-align:left!important;
        }
        .deviceWidth,
        .paddedDeviceWidth {
            width:100%!important;
        }
    }
</style>
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" style="width:100%; margin:0; padding:0;background-color: #ededed;font-size: 16px; color: #303030; font-weight: normal; text-align: left; font-family: 'Open Sans', Arial, Helvetica, sans-serif; mso-line-height-rule:exactly; line-height: 26px;">

    <!-- Wrapper -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ededed">
        <tr>
            <td class="wrappper" width="100%" valign="top" style="padding: 20px;">

                <table width="600" class="deviceWidth" border="0" cellpadding="25" cellspacing="0" align="center" bgcolor="#ffffff">
                    <tr>
                        <td align="center">

                            <img src="<?php echo Enc::html(Sprout::absRoot()); ?>skin/default/images/logo_email.gif" alt="<?php echo Enc::html(Kohana::config('sprout.site_title')); ?>">

                        </td>
                    </tr>
                </table>

                <table width="600" class="deviceWidth" border="0" cellpadding="25" cellspacing="0" align="center" bgcolor="#ffffff">
                    <tr>
                        <td class="fallback-text" style="font-size: 16px; color: #303030; font-weight: normal; text-align: left; font-family: 'Open Sans', Arial, Helvetica, sans-serif; mso-line-height-rule:exactly; line-height: 26px; vertical-align: top;padding-bottom: 30px;">

                            <?php echo $content; ?>

                        </td>
                    </tr>
                </table>

                <table width="600" class="deviceWidth" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ededed">
                    <tr>
                        <td style="font-size: 16px; color: #303030; font-weight: normal; text-align: left; font-family: 'Open Sans', Arial, Helvetica, sans-serif; mso-line-height-rule:exactly; line-height: 26px; vertical-align: top;padding-top: 10px;padding-bottom: 15px;">

                            <br>
                            <strong><?php echo Enc::html(Kohana::config('sprout.site_title')); ?></strong> <br>

                            <div class="footer-link">
                                <a href="<?php echo Enc::html(Sprout::absRoot()); ?>"><?php echo Enc::html(Sprout::absRoot()); ?></a>
                                <br>1 Example Street, Example Town SA
                                <br><a class="icon-desktop_mac-disable-link" href="tel:08-0000-0000">08 0000 0000</a>
                            </div>

                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table> <!-- End Wrapper -->


</body>
</html>
