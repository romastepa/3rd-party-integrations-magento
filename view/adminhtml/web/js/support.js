
require(["jquery", "prototype"], function ($) {
    $("#support_support_type").change(function () {
        $(this).find("option:selected").each(function () {
            if ($(this).attr("value")=="1" || ($(this).attr("value")=="3") ) {
                $('#support_email_field').remove();
                $('<div id="support_email_field" class="admin__field field"><label class="label admin__field-label"> Email</label><div class="admin__field-control control admin__control-text">support@emarsys.com</div></div>').insertAfter(".field-support_type");
            } else if ($(this).attr("value")=="2" || $(this).attr("value")=="4" || $(this).attr("value")=="5" || $(this).attr("value")=="7" || $(this).attr("value")=="8" || $(this).attr("value")=="9") {
                $('#support_email_field').remove();
                $('<div id="support_email_field" class="admin__field field"><label class="label admin__field-label"> Email</label><div class="admin__field-control control admin__control-text">support@emarsys.com</div></div>').insertAfter(".field-support_type");
            } else {
                $('#support_email_field').remove();
            }
        });
    }).change();
});
