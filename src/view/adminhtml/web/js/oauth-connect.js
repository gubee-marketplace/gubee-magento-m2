define(['jquery', 'mage/dataPost'], function ($, dataPost) {
    'use strict';

    return function (config, element) {
        $(element).on('click', function (event) {
            event.preventDefault();
            dataPost().postData(config);
        });
    };
});
