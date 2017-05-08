
<style>
    .hr-bnr-02 {
        /*image size ratio 4:3*/
        background-image: url(http://placehold.it/960x660/48b183/ffffff?text=c960x660-ct~30);
        background-repeat: no-repeat;
        background-position: center top;
        background-size: 100% auto;
        position: relative;
    }
    .hr-bnr-02:before {
        content: '\00a0';
        display: block;
        padding-top: 63%
    }
    .hr-bnr-02__heading {
        font-size: 2.4rem;
        line-height: 1.3;
        font-weight: bold;
    }
    .hr-bnr-02__content .container > *:last-child {
        margin-bottom: 0;
    }
    @media screen and (min-width: 30em) { /* 480px */
        .hr-bnr-02 {
            background-image: url(http://placehold.it/1536x1056/48b183/ffffff?text=c1536x1056-ct~35);
        }
        .hr-bnr-02:before {
            padding-top: 65%
        }
    }
    @media screen and (min-width:  48em) { /* 768px */
        .hr-bnr-02 {
            /*image size crop safe area */
            background-image: url(http://placehold.it/1984x1364/48b183/ffffff?text=c1984x1364-ct~35);
        }
        .hr-bnr-02:before {
            padding-top: 65.45%;
        }
        .hr-bnr-02__content {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,.5);
        }
    }
    @media screen and (min-width: 62em) { /* 992px */
        .hr-bnr-02 {
            /* image size ratio becomes 16:9 */
            background-image: url(http://placehold.it/2425x1364/48b183/ffffff?text=c2400x1364-ct~35);
        }
        .hr-bnr-02:before {
            padding-top: 53.5%;
        }
    }

    @media only screen and (min-width: 75em) { /* 1200px */
        .hr-bnr-02 {
            /*image size ratio starts gets really widescreen */
            background-image: url(http://placehold.it/3100x1364/48b183/ffffff?text=c3100x1364-ct~35);
        }
        .hr-bnr-02:before {
            padding-top: 41.45%;
        }
    }
</style>

<div class="hr-bnr-02 bg-light-grey">

    <div class="hr-bnr-02__content section">

        <div class="container">
            <p class="hr-bnr-02__heading">A big long call to action that's alittle long</p>
            <p>Some extra text that goes into a little bit more detail than the call to action above</p>
            <p><a class="button button-large -r-arrow-after" href="">A call to action</a></p>
        </div>

    </div>

</div>