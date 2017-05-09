
<style>
    .center-heading {
        text-align: center;
    }

    .pm-thr-01__promo {
        height: 100%;

        display: -ms-flexbox;
        display: -webkit-box;
        display: flex;
        flex-flow: column nowrap;
    }

    .pm-thr-01__promo-img {
        flex-shrink: 0; /* IE10-11 workaround */
    }

    .pm-thr-01__promo-content {
        -ms-flex: 1 0 auto;
        flex: 1 0 auto;

        display: -ms-flexbox;
        display: -webkit-box;
        display: flex;
        flex-flow: column nowrap;
        justify-content: space-between;


    }

    @media screen and (min-width: 62em) { /* 992px */
        .pm-thr-01-row {
            margin-left: -2.5rem;
            margin-right: -2.5rem;
        }
        .pm-thr-01-row .col-sm-4 {
            padding-left: 2.5rem;
            padding-right: 2.5rem;
        }
    }
    @media only screen and (min-width: 75em) { /* 1200px */
        .pm-thr-01-row {
            margin-left: -3.5rem;
            margin-right: -3.5rem;
        }
        .pm-thr-01-row .col-sm-4 {
            padding-left: 3.5rem;
            padding-right: 3.5rem;
        }
    }
</style>

<div class="bg-lightgrey section--large">

        <div class="container">

            <h2 class="center-heading block-mb-large">Promos three 01</h2>

            <div class="row pm-thr-01-row">

                <?php /* Images are inside divs to fix flexbox issue in IE */ ?>

                <div class="col-xs-12 col-sm-4 block-mb-large">
                    <div class="bg-white pm-thr-01__promo">

                        <div class="pm-thr-01__promo-img">
                            <img src="http://placehold.it/1370x685/e2e2e2?text=c1370x685-ct~20" width="1370" height="685" alt="">
                        </div>
                        <div class="box pm-thr-01__promo-content">
                            <h3>It's a lovely heading</h3>
                            <p>Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nunc sit amet viverra velit.</p>
                            <p><a href="/" class="button">Call to action</a></p>
                        </div>

                    </div>
                </div>

                <div class="col-xs-12 col-sm-4 block-mb-large">
                    <div class="bg-white pm-thr-01__promo">
                        <div class="pm-thr-01__promo-img">
                            <img src="http://placehold.it/1370x685/e2e2e2?text=c1370x685-ct~20" width="1370" height="685" alt="">
                        </div>
                        <div class="box pm-thr-01__promo-content">
                            <h3>It's a lovely heading</h3>
                            <p>Class aptent taciti sociosqu ad per inceptos himenaeos.</p>
                            <p><a href="/" class="button">Call to action</a></p>
                        </div>
                    </div>
                </div>

                <div class="col-xs-12 col-sm-4 block-mb-large">
                    <div class="bg-white pm-thr-01__promo">
                        <div class="pm-thr-01__promo-img">
                            <img src="http://placehold.it/1370x685/e2e2e2?text=c1370x685-ct~20" width="1370" height="685" alt="">
                        </div>
                        <div class="box pm-thr-01__promo-content">
                            <h3>It's a lovely heading</h3>
                            <p>Phasellus condimentum, odio non vulputate volutpat, tellus magna blandit sem, non suscipit libero neque sed augue.</p>
                            <p><a href="/" class="button">Call to action</a></p>
                        </div>

                    </div>
                </div>

            </div>

    </div>

</div>