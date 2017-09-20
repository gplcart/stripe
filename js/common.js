/* global window, Gplcart, Stripe, jQuery */
(function (window, Gplcart, Stripe, $) {

    "use strict";

    /**
     * 
     * @returns {undefined}
     */
    Gplcart.onload.submitStripe = function () {

        if (!Gplcart.settings.stripe || !Gplcart.settings.stripe.key) {
            return;
        }

        Stripe.setPublishableKey(Gplcart.settings.stripe.key);

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

})(window, Gplcart, Stripe, jQuery);