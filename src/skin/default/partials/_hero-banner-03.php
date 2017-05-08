
<style>
    .hr-bnr-03 {
        /*image size ratio 4:3*/
        background-image: url(http://placehold.it/960x660/48b183/ffffff?text=c960x660-ct~30);
        background-repeat: no-repeat;
        background-position: center top;
        background-size: 100% auto;
        position: relative;
    }
    .hr-bnr-03:before {
        content: '\00a0';
        display: block;
        padding-top: 45%
    }

    @media screen and (min-width: 30em) { /* 480px */
        .hr-bnr-03 {
            background-image: url(http://placehold.it/1536x1056/48b183/ffffff?text=c1536x1056-ct~35);
        }
    }
    @media screen and (min-width:  48em) { /* 768px */
        .hr-bnr-03 {
            /*image size crop safe area */
            background-image: url(http://placehold.it/1984x1364/48b183/ffffff?text=c1984x1364-ct~35);
        }
        .hr-bnr-03:before {
            padding-top: 74.45%;
        }
        .hr-bnr-03__content {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
        }
    }
    @media screen and (min-width: 62em) { /* 992px */
        .hr-bnr-03 {
            /*image size ratio changes */
            background-image: url(http://placehold.it/2425x1364/48b183/ffffff?text=c2400x1650-ct~30);
        }
    }

    @media only screen and (min-width: 75em) { /* 1200px */
        .hr-bnr-03 {
            background-image: url(http://placehold.it/3100x1800/48b183/ffffff?text=c3100x1800-ct~25);
        }
        .hr-bnr-03:before {
            padding-top: 56.45%;
        }
    }
</style>

<div class="hr-bnr-03 bg-light-grey">

    <div class="hr-bnr-03__content">

        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-4">
                    <div class="bg-white block-mb-large">
                        <img src="http://placehold.it/1370x685/e2e2e2?text=c1370x685-ct~20" alt="">
                        <div class="box">
                            <h3>It's a lovely heading</h3>
                            <p>This is a lovely paragraph</p>
                            <p><a href="/" class="button">Call to action</a></p>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4">
                    <div class="bg-white block-mb-large">
                        <img src="http://placehold.it/1370x685/e2e2e2?text=c1370x685-ct~20" alt="">
                        <div class="box">
                            <h3>It's a lovely heading</h3>
                            <p>This is a lovely paragraph</p>
                            <p><a href="/" class="button">Call to action</a></p>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4">
                    <div class="bg-white block-mb-large">
                        <img src="http://placehold.it/1370x685/e2e2e2?text=c1370x685-ct~20" alt="">
                        <div class="box">
                            <h3>It's a lovely heading</h3>
                            <p>This is a lovely paragraph</p>
                            <p><a href="/" class="button">Call to action</a></p>
                        </div>
                    </div>
                </div>
            </div>
                    </div>

    </div>

</div>