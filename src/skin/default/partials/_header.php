<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Navigation;
?>

<header id="header">

    <div class="section section--header bg-navyblue">

        <div class="container">

            <div class="row">

                <div class="col-xs-12 col-sm-6 col-md-7">

                    <div class="header__logo">

                        <a href="ROOT/" onclick="ga('send', 'event', 'Skin', 'LogoClick');">

                            <img class="header__logo__img" src="SKIN/images/sprout-logo-white.svg" alt="Logo for <?php echo Enc::html(Kohana::config('sprout.site_title')); ?>">

                        </a>

                    </div>

                </div>

                <div class="col-xs-12 col-sm-6 col-md-5">

                    <div class="header__search">

                        <form method="get" action="search">

                            <div class="row">

                                <div class="col-xs-8">

                                    <div class="field-element field-element--text field-element--white field-element--hidden-label">

                                        <div class="field-label">

                                            <label for="fm-site-search">Search the <?php echo Enc::html(Kohana::config('sprout.site_title')); ?> website</label>

                                        </div>

                                        <div class="field-input">

                                            <input id="fm-site-search" class="textbox" type="text" name="q" value="<?php echo Enc::html(@$_GET['q']); ?>" placeholder="Enter your search here">

                                        </div>

                                    </div>

                                </div>

                                <div class="col-xs-4">

                                    <button type="submit" class="button button-block">Search</button>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="section section--nav bg-orange">

        <nav id="frankenmenu">

            <div class="container">

                <?php Navigation::simpleMenu(); ?>

            </div>

        </nav>

    </div>

</header>