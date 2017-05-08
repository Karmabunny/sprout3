<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Text;
use Sprout\Helpers\SocialNetworking;
?>

<style>
    .ft-01 {
        text-align: center;
    }
    .social-media-list {
        list-style: none;
        padding: 0;
    }
    .social-media-list__item {
        list-style: none;
        display: inline-block;
        margin-left: 12px;
    }
    .social-media-list__item:first-child {
        margin: 0;
    }

    .social-media-list__item a {
        opacity: 1;
        transition: opacity 200ms ease-in-out;
    }

    .social-media-list__item a:hover,
    .social-media-list__item a:focus,
    .social-media-list__item a:active {
        opacity: .7
    }
</style>

<footer id="footer" class="section bg-darkest-grey pale-reverse-text ft-01">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-6 col-sm--left-align">
                <p>Copyright &copy; <?php echo Enc::html(Text::copyright('2015')); ?> <?php echo Enc::html(Kohana::config('sprout.site_title')); ?>
                        | <a href="http://getsproutcms.com" target="_blank" rel="nofollow">Powered by SproutCMS<span class="-vis-hidden">, view their website in a new window</span></a>
                </p>
            </div>
            <div class="col-xs-12 col-sm-6 col-sm--right-align">
                <ul class="social-media-list">
                    <li class="social-media-list__item"><a href=""><img src="SKIN/images/icon_facebook-white.svg" alt="Follow us on Facebook"></a></li>
                    <!-- <li class="social-media-list__item"><?php echo SocialNetworking::pageLink('facebook'); ?><img src="SKIN/images/icon_facebook-white.svg" alt="Follow us on Facebook"></a></li> -->
                    <li class="social-media-list__item"><a href=""><img src="SKIN/images/icon_twitter-white.svg" alt="Follow us on Twitter"></a></li>
                    <li class="social-media-list__item"><a href=""><img src="SKIN/images/icon_youtube-white.svg" alt="Follow us on YouTube"></a></li>
                    <li class="social-media-list__item"><a href=""><img src="SKIN/images/icon_instagram-white.svg" alt="Follow us on Instagram"></a></li>
                    <li class="social-media-list__item"><a href=""><img src="SKIN/images/icon_pinterest-white.svg" alt="Follow us on Pinterest"></a></li>
                    <li class="social-media-list__item"><a href=""><img src="SKIN/images/icon_linkedin-white.svg" alt="Follow us on Linked In"></a></li>
                </ul>
            </div>
        </div>

    </div>
</footer>
