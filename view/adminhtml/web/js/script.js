require(
    [
       'jquery'
    ],
    function ($) {
       $(document).ready(function () {
    //use the jquery only after the complete document has loaded so jquery event gets bound to the html elements
           $('.hiddensupportemail').parent().parent().hide();
           $('#show-email').parent().parent().hide();
       })
    }
);

function support(url,supportvalue)
{
    new Ajax.Request(url, {
        method : 'GET',
        parameters : {'supportType':supportvalue},
        asynchronous : false,
        onComplete: function (response) {

            if (response.responseText) {
                jQuery('#email').val(response.responseText);
                jQuery('#show-email').parent().parent().show();
                jQuery('#show-email').text(response.responseText).show();
                if (supportvalue == 6) {
                alert("This should be used only for emergency requests");
                }
            } else {
                jQuery('#show-email').parent().parent().hide();
            }
        }
    });

}
 
