var config = {
    paths: {
        'bynderjs': 'DamConsultants_JPW/js/bynder',
        'select2': 'DamConsultants_JPW/js/select2'
    },
    shim: {
        'bynderjs': {
            deps: ['jquery']
        },
        'select2': {
            deps: ['jquery']
        },
    },
	map: {
        '*': {
            'Magento_PageBuilder/template/form/element/html-code.html': 'DamConsultants_JPW/template/form/element/html-code.html',
            'Magento_PageBuilder/js/form/element/html-code': 'DamConsultants_JPW/js/form/element/html-code',
        },
    }
};