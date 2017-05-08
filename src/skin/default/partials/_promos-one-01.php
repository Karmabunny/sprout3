
<style>

    .pm-one-01__navigation {
        list-style-type: none;
    }

    .pm-one-01__promo-content {
        flex: 1;
        display: flex;
        flex-flow: column nowrap;
        justify-content: center;
    }

    .pm-one-01__promo-content__header {
        padding-top: 20px;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid rgba(0,0,0,.5) ;
    }

    .pm-one-01__navigation__item {
        list-style: none;
        display: inline-block;
    }

    .pm-one-01__navigation__item {
        border-left: 1px solid rgba(0,0,0,.5) ;
    }

    .pm-one-01__navigation__item:first-child {
        border-left: 0;
    }

    .pm-one-01__navigation__item a {
        padding: 0 8px;
    }

    @media screen and (min-width: 48em) { /* 768px */
        .pm-one-01__navigation {
            text-align: right;
        }
    }

    @media screen and (min-width: 62em) { /* 992px */
        .pm-one-01-row {
            margin-left: -2.5rem;
            margin-right: -2.5rem;
        }
        .pm-one-01-row .pm-one-01__promo-img,
        .pm-one-01-row .pm-one-01__promo-content {
            padding-left: 2.5rem;
            padding-right: 2.5rem;
        }
    }
    @media only screen and (min-width: 75em) { /* 1200px */
        .pm-one-01-row {
            margin-left: -3.5rem;
            margin-right: -3.5rem;
        }
        .pm-one-01-row .pm-one-01__promo-img,
        .pm-one-01-row .pm-one-01__promo-content {
            padding-left: 3.5rem;
            padding-right: 3.5rem;
        }
    }
</style>

<div class="bg-light-grey section--large">

        <div class="container">

            <div class="row pm-one-01-row">

                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-6 pm-one-01__promo-img">
                    <img src="http://placehold.it/960x660/e8e8e8?text=c880x960-ct~20" alt="">
                </div>

                <div class="col-xs-12 col-sm-6 col-md-8 col-lg-6 pm-one-01__promo-content">
                    <div class="pm-one-01__promo-content__header">
                        <div class="row">
                            <h2 class="col-xs-12 col-sm-7 col-md-8">News &amp; Updates</h2>
                            <ul class="col-xs-12 col-sm-5 col-md-4 pm-one-01__navigation">
                                <li class="pm-one-01__navigation__item"><a href="">1</a></li>
                                <li class="pm-one-01__navigation__item"><a href="">2</a></li>
                                <li class="pm-one-01__navigation__item"><a href="">3</a></li>
                                <li class="pm-one-01__navigation__item"><a href="">all <span class="-vis-hidden">news</a></li>
                            </ul>
                        </div>
                    </div>


                    <article>
                        <h3>A news article heading</h3>
                        <p><strong>1 Jan:</strong> Aenean ut nisl egestas urna sagittis molestie. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. </p>
                        <p>Sed a rhoncus urna. Aliquam sollicitudin sapien lacus, sit amet mattis erat interdum nec. Nunc et orci purus...</p>
                        <p><a href="/">Read more <span class="-vis-hidden">about A news article heading</span></a></p>
                    </article>


                </div>

            </div>

    </div>

</div>