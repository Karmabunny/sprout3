
<style>
    .center-heading {
        text-align: center;
    }

    .pm-two-01__promo {
        height: 100%;
        display: flex;
        flex-flow: column nowrap;
    }

    .pm-two-01__promo-content {
        flex: 1;
        display: flex;
        flex-flow: column nowrap;
        justify-content: center;
    }

    .pm-two-01__promo-content-img {
        display: none;
    }


    @media screen and (min-width: 30em) { /* 480px */
        .pm-two-01__promo {
            flex-flow: row nowrap;
        }
        .pm-two-01__promo-content {
            width: 55%;
        }
        .pm-two-01__promo-content-img {
            width: 45%;
            display: block;
            flex-shrink: 0; /* IE10-11 workaround */
        }
    }

    @media screen and (min-width: 62em) { /* 992px */
        .pm-two-01-row {
            margin-left: -2.5rem;
            margin-right: -2.5rem;
        }
        .pm-two-01-row .col-sm-6 {
            padding-left: 2.5rem;
            padding-right: 2.5rem;
        }
    }
    @media only screen and (min-width: 75em) { /* 1200px */
        .pm-two-01-row {
            margin-left: -3.5rem;
            margin-right: -3.5rem;
        }
        .pm-two-01-row .col-sm-6 {
            padding-left: 3.5rem;
            padding-right: 3.5rem;
        }
    }
</style>

<div class="bg-lightgrey section--large">

        <div class="container">
            <h2 class="center-heading block-mb">Two Promos 01</h2>
            <div class="row pm-two-01-row">
                <div class="col-xs-12 col-sm-6 block-mb">
                    <div class="bg-white pm-two-01__promo">
                        <div class="pm-two-01__promo-content-img">
                            <img src="http://placehold.it/880x960/cbcdcf?text=c880x960-ct~20" alt="">
                        </div>
                        <div class="box pm-two-01__promo-content">
                            <h3>It's a lovely heading</h3>
                            <p><a href="/" class="button">Call to action</a></p>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 block-mb">
                    <div class="bg-white pm-two-01__promo">
                        <div class="pm-two-01__promo-content-img">
                            <img src="http://placehold.it/880x960/cbcdcf?text=c880x960-ct~20" alt="">
                        </div>
                        <div class="box pm-two-01__promo-content">
                            <h3>Aenean ut nisl egestas urna molestie penatibus</h3>
                            <p><a href="/" class="button">Call to action</a></p>
                        </div>
                    </div>
                </div>
            </div>

    </div>

</div>