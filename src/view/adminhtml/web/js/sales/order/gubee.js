require(['jquery'], function ($) {
    $(document).ready(function () {
        $('#link_to_gubee').on('change', function () {
            let gubeeStatus = $('#gubee_status');
            if ($(this).val() == '1') {
                gubeeStatus.prop('disabled', false);
            } else {
                gubeeStatus.prop('disabled', true);
            }
        });
    });
});
