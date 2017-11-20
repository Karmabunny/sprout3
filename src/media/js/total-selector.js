// Total selector - Luke Underwood 2017

var totalSelector = $.fn.totalSelector = function() {

    var $this = $(this);

    var totalSelectorInit = function() {

        $this.each(function(){

            var $totalSelector = $(this);
            var $fields = $totalSelector.find(".field-element--totalselector__fields .field-element");

            // Add dropdown wrapper
            $('<div class="field-element--totalselector__dropdown bg-white"><div class="total-selector__dropdown__fields"></div><div class="total-selector__dropdown__close__button-wrap"><button type="button" class="total-selector__dropdown__close__button button button-small">Done</button></div></div>').insertAfter($totalSelector.find(".field-input"));

            $fields.each(function(){

                var $this = $(this);

                var fieldLabel = $this.find("label").html();
                var fieldHelper = $this.find(".field-helper").html();
                var fieldValue = $this.find(".field-input input").val();
                var fieldId = $this.find(".field-input input").attr("id");
                var fieldMin = parseInt($this.find(".field-input input").attr("min"));
                var fieldMax = parseInt($this.find(".field-input input").attr("max"));
                if(fieldValue === '') {
                    fieldValue = 0;
                }

                if(fieldValue < fieldMin) {
                    $this.find(".field-input input").val(fieldMin);
                    fieldValue = fieldMin;
                }

                var dropdown = '<div class="total-selector__dropdown__field" data-id="' + fieldId + '">';
                dropdown += '<div class="total-selector__dropdown__field__labels">';
                dropdown += '<p class="total-selector__dropdown__field__labels__title">' + fieldLabel + '</p>';
                if(fieldHelper) {
                    dropdown += '<p class="total-selector__dropdown__field__labels__helper">' + fieldHelper + '</p>';
                }
                dropdown += '</div>';
                dropdown += '<div class="total-selector__dropdown__field__buttons">';
                dropdown += '<button class="total-selector__dropdown__field__button total-selector__dropdown__field__button--decrease';


                if(fieldValue === fieldMin) {
                    dropdown += ' total-selector__dropdown__field__button--min';
                }

                dropdown += '" type="button"><span class="-vis-hidden">Decrease</span></button>';
                dropdown += '<div class="total-selector__dropdown__field__total">' + fieldValue + '</div>';
                dropdown += '<button class="total-selector__dropdown__field__button total-selector__dropdown__field__button--increase';

                if(fieldMax != undefined && fieldValue+1 >= fieldMax) {
                    dropdown += ' total-selector__dropdown__field__button--max';
                }

                dropdown += '" type="button"><span class="-vis-hidden">Increase</span></button>';
                dropdown += '</div>';
                dropdown += '</div>';

                $(dropdown).appendTo($totalSelector.find(".field-element--totalselector__dropdown .total-selector__dropdown__fields"));

            });

            updateTotal($totalSelector);
        });

        $(".total-selector__dropdown__field__button").on("click", function() {

            var $this = $(this);
            var $totalSelector = $this.closest(".field-element--totalselector");

            $this.siblings().removeClass("total-selector__dropdown__field__button--min total-selector__dropdown__field__button--max");


            // Check which button was clicked
            if($this.hasClass("total-selector__dropdown__field__button--increase")) {
                var step = "increase";
            } else {
                var step = "decrease";
            }

            var $totalLabel = $this.siblings(".total-selector__dropdown__field__total");
            var fieldId = $this.closest(".total-selector__dropdown__field").attr("data-id");
            var $field = $totalSelector.find("input#" + fieldId);

            var fieldValue = $field.val();
            // If value is not set, assume 0
            if(fieldValue === '') {
                fieldValue = 0;
            }

            if(step === "increase") {
                var newValue = parseFloat(fieldValue)+1;
                if (newValue >= $field.attr('max')) {
                    newValue = $field.attr('max');
                    $this.addClass("total-selector__dropdown__field__button--max");
                }
            } else {
                var newValue = parseFloat(fieldValue)-1;
                if (newValue <= $field.attr('min')) {
                    newValue = $field.attr('min');
                    $this.addClass("total-selector__dropdown__field__button--min");
                }
            }

            $totalLabel.html(newValue);
            $field.val(newValue);


            updateTotal($totalSelector);
        });

        function updateTotal($totalSelector) {


            // Update main total field
            var total = 0;
            $totalSelector.find(".field-element--totalselector__fields .field-input input").each(function() {
                var fieldValue = $(this).val();
                if(fieldValue === '') {
                    fieldValue = 0;

                }
                total += parseFloat(fieldValue);

            });

            var $outputField = $totalSelector.find(".total-selector__output");

            if(total === 1) {
                var unitLabel = $outputField.attr("data-singular");
            } else {
                var unitLabel = $outputField.attr("data-plural");
            }



            $outputField.val(total + ' ' + unitLabel).trigger('change');
        }

        // Open dropdown on click/focus
        $(".total-selector__output").on("click focus", function() {
            $(".field-element--totalselector--active").removeClass("field-element--totalselector--active");

            $(this).closest(".field-element--totalselector").addClass("field-element--totalselector--active");

            $("body").on("click", checkDropdownBoundary);

        });

        // Checks if click is out of bounds mobile menu
        function checkDropdownBoundary(e) {

            if(!$(e.target).is(".field-element--totalselector") && !$(e.target).parents(".field-element--totalselector").length) {

                $(".field-element--totalselector--active").removeClass("field-element--totalselector--active");

                $("body").off("click", checkDropdownBoundary);


            }
        }

        // Close dropdown on blur, unless dropdown buttons are focused
        $(".total-selector__output, .total-selector__dropdown__field__button, .total-selector__dropdown__close__button").on("blur", function(e) {

            setTimeout(function() {

                if(!$(".total-selector__dropdown__field__button:focus").length && !$(".total-selector__dropdown__field__button:active").length && !$(".total-selector__dropdown__field__button:hover").length) {

                    $(".field-element--totalselector--active").removeClass("field-element--totalselector--active");

                    $("body").off("click", checkDropdownBoundary);

                }

            }, 1);

        });

        // Close dropdown on click of close button
        $(".total-selector__dropdown__close__button").on("click", function(){

            $(".field-element--totalselector--active").removeClass("field-element--totalselector--active");

            $("body").off("click", checkDropdownBoundary);

        });

    }


    totalSelectorInit();

}

