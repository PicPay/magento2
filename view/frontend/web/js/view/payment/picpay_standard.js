define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'picpay_standard',
                component: 'Picpay_Payment/js/view/payment/method-renderer/picpay_standardmethod'
            }
        );
        return Component.extend({});
    }
);