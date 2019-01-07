var config = {
    "map": {
        "*": {
            'Magento_Checkout/js/model/shipping-save-processor/default': 'Emarsys_Emarsys/js/model/shipping-save-processor/default'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Emarsys_Emarsys/js/minicart-mixin': true
            }
        }
    }
};
