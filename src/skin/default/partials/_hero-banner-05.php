
<style>
    .hr-bnr-05 {
        /*image size ratio 4:3*/
        background-image: url(http://placehold.it/960x660/48b183/ffffff?text=c960x660-ct~30);
        background-repeat: no-repeat;
        background-position: center top;
        background-size: 100% auto;
        position: relative;
    }
    .hr-bnr-05:before {
        content: '\00a0';
        display: block;
        padding-top: 45%
    }
    @media screen and (min-width: 30em) { /* 480px */
        .hr-bnr-05 {
            background-image: url(http://placehold.it/1536x1056/48b183/ffffff?text=c1536x1056-ct~35);
        }
    }
    @media screen and (min-width:  48em) { /* 768px */
        .hr-bnr-05 {
            /*image size crop safe area */
            background-image: url(http://placehold.it/1984x1364/48b183/ffffff?text=c1984x1364-ct~35);
        }
        .hr-bnr-05:before {
            padding-top: 74.45%;
        }
        .hr-bnr-05__content {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
        }
        /* increasing default column spacing */
        .hr-bnr-05__content .row {
            margin-right: -2.5rem;
            margin-left: -2.5rem;
        }
        .hr-bnr-05__content .col-sm-6 {
            padding-right: 2.5rem;
            padding-left: 2.5rem;
        }
    }
    @media screen and (min-width: 62em) { /* 992px */
        .hr-bnr-05 {
            /*image size ratio changes */
            background-image: url(http://placehold.it/2425x1364/48b183/ffffff?text=c2400x1650-ct~30);
        }
    }

    @media only screen and (min-width: 75em) { /* 1200px */
        .hr-bnr-05 {
            background-image: url(http://placehold.it/3100x1800/48b183/ffffff?text=c3100x1800-ct~25);
        }
        .hr-bnr-05:before {
            padding-top: 56.45%;
        }
    }
</style>

<div class="hr-bnr-02 bg-lightgrey">

    <div class="hr-bnr-02__content">

        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-6">
                    <div class="bg-white block-mb">
                        <div class="box">
                            <h3>It's a lovely heading</h3>
                            <p>This is a lovely paragraph</p>
                            <p><a href="/" class="button">Call to action</a></p>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6">
                    <div class="bg-white block-mb">
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