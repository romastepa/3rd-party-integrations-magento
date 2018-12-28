define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery',
    'ko',
    'underscore',
    'sidebar',
    'mage/translate'
], function (Component, customerData, $, ko, _) {
    'use strict';

    return function (Component) {
        return Component.extend({
            /**
             * @override
             * Update mini shopping cart content.
             *
             * @param {Object} updatedCart
             * @returns void
             */
            update: function (updatedCart) {
                var miniCart = $('[data-block="minicart"]');
                miniCart.trigger('cartUpdated');
                _.each(updatedCart, function (value, key) {
                    if (!this.cart.hasOwnProperty(key)) {
                        this.cart[key] = ko.observable();
                    }
                    this.cart[key](value);
                }, this);
            }
        });
    }
});