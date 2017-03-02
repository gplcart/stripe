/* global window, GplCart, Stripe, jQuery */
(function (window, GplCart, Stripe, $) {

    "use strict";

    /**
     * 
     * @returns {undefined}
     */
    GplCart.onload.submitStripe = function () {

        if (!GplCart.settings.stripe || !GplCart.settings.stripe.key) {
            return;
        }

        Stripe.setPublishableKey(GplCart.settings.stripe.key);

        var form = $('form#stripe-payment-form');

        form.submit(function (e) {
            e.preventDefault();
            form.find(':submit').prop('disabled', true);
            Stripe.card.createToken(form, responseHandler);
            return false;
        });
    };

    /**
     * 
     * @param {type} status
     * @param {type} response
     * @returns {undefined}
     */
    var responseHandler = function (status, response) {

        var form = $('form#stripe-payment-form');

        if (response.error) {
            form.find('.payment-errors').text(response.error.message);
            form.find(':submit').prop('disabled', false);
        } else {
            form.append($('<input type="hidden" name="stripeToken">').val(response.id));
            form.get(0).submit();
        }
    };

})(window, GplCart, Stripe, jQuery);