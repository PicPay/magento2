define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url'
    ],
    function (Component, redirectOnSuccessAction, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Picpay_Payment/payment/standard'
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.picpay_instructions;
            },
            afterPlaceOrder: function () {
                if(window.checkoutConfig.payment.picpay_checkout_mode == "3") {
                    redirectOnSuccessAction.redirectUrl = url.build('picpay/standard/redirect');
                    this.redirectAfterPlaceOrder = true;
                }
            },
        });
    }
);
