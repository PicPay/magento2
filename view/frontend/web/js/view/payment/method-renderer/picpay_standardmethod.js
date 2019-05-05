define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Picpay_Payment/payment/standard'
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            getInstructions: function () {
                return 'TODO';//window.checkoutConfig.payment.instructions[this.item.method];
            },
        });
    }
);
