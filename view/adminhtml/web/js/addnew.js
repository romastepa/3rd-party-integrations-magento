require(
    [
        'jquery'
    ],
    function ($) {
        $(document).ready(function () {
            var maxField = 10; //Input fields increment limitation
            var addButton = $('.add_button'); //Add button selector
            var wrapper = $('.field_wrapper'); //Input field wrapper
            var fieldHTML = '<div class="emr_tbl emr_content"><input type="text" name="field_name[]" value="" class="fieldName"/><input type="text" name="field_label[]" value="" class="fieldLabel"/><select style="display:none;" name="attribute_type[]"><option value="varchar">Varchar</option><option value="text">Text</option><option value="character">Character</option><option value="integer">Integer</option><option value="String">String</option><option value="URL">URL</option><option value="StringValue">StringValue</option><option value="Boolean">Boolean</option><option value="Float">Float</option></select><a href="javascript:void(0);" class="remove_button action-default" title="Remove field">Remove</a></div>'; //New input field html
            var x = 1; //Initial field counter is 1
            $(addButton).click(function () { //Once add button is clicked
                if (x < maxField) { //Check maximum number of input fields
                    x++; //Increment field counter
                    $(wrapper).append(fieldHTML); // Add field html
                }
            });
            $(wrapper).on('click', '.remove_button', function (e) { //Once remove button is clicked
                e.preventDefault();
                $(this).parent('div').remove(); //Remove field html
                x--; //Decrement field counter
            });
        });


        jQuery("#emarAttribute").submit(function (e) {
            jQuery(".field_wrapper > div").each(function (f) {

                if (jQuery(this).find(".fieldName").val() == '') {
                    jQuery(this).find(".fieldName").css('border', '1px solid red');
                    e.preventDefault();
                } else {
                    jQuery(this).find(".fieldName").css('border', '1px solid #CCCCCC');
                }
                if (jQuery(this).find(".fieldLabel").val() == '') {
                    jQuery(this).find(".fieldLabel").css('border', '1px solid red');
                    e.preventDefault();
                } else {
                    jQuery(this).find(".fieldLabel").css('border', '1px solid #CCCCCC');
                }
            });
        });

    });
function editEmarAttr(url, Id) {
    
    var code = jQuery("#field_name_" + Id).val();
    var label = jQuery("#field_label_" + Id).val();
    var field_type = jQuery("#attribute_type_" + Id).val();
    var error = 0;


    if (code == "") {
        jQuery("#field_name_" + Id).css('border', '1px solid red');
        jQuery("#field_name_" + Id).focus();
        error++;
    } else {
        jQuery("#field_name_" + Id).css('border', '1px solid #CCCCCC');
    }
    if (label == "") {
        jQuery("#field_label_" + Id).css('border', '1px solid red');
        jQuery("#field_label_" + Id).focus();
        error++;
    } else {
        jQuery("#field_label_" + Id).css('border', '1px solid #CCCCCC');
    }

    if (error) {
        return false;
    }


    if (confirm("Do you want to Update?") == true) {

        jQuery.ajax({
            url: url,
            dataType: 'json',
            type: 'post',
            data: {'Id': Id, 'code': code, 'label': label, 'field_type': field_type},

            success: function (response) {
                if (response.status == "SUCCESS") {
                    window.location.href = window.location.href;
                } else {
                    alert('Data not deleted');
                }
            }

        });
    }
}

function deleteEmarAttr(url, Id, storeId) {

    if (confirm("Do you want to delete?") == true) {

        jQuery.ajax({
            url: url,
            dataType: 'json',
            type: 'post',
            data: {'storeId': storeId, 'Id': Id},

            success: function (response) {
                if (response.status == "SUCCESS") {
                    window.location.href = window.location.href;
                } else {
                    alert('Data not deleted');
                }
            }

        });
    }
}
	
	
