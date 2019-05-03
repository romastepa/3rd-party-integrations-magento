/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

require(
    [
        'jquery'
    ],
    function ($) {
        $(document).ready(function () {
            if (document.getElementById('emarsys_predict_feed_export_excludedcategories')) {
                document.getElementById('emarsys_predict_feed_export_excludedcategories').style.display = 'none';
            }
            //use the jquery only after the complete document has loaded so jquery event gets bound to the html elements
            $("#emarsyssync_customersync_syncdirection").on('change', function () {
                alert('If you change sync direction then attribute mapping direction will also change according to sync direction');
            });

            $("#emarsyssync_productsync_syncdirection").on('change', function () {
                alert('If you change sync direction then attribute mapping direction will also change according to sync direction');
            });
            $("#emarsyssync_categorysync_syncdirection").on('change', function () {
                alert('If you change sync direction then attribute mapping direction will also change according to sync direction');
            });
            $('.hiddensupportemail').parent().parent().hide();
            $('#show-email').parent().parent().hide();

            var radioValue = $("input[name='groups[initial_db_load][fields][initial_db_load][value]']:checked").val();
            if (!radioValue || radioValue == 'true' || radioValue == 'empty') {
                $("#contacts_synchronization_initial_db_load_initial_db_load_attribute").hide();
                $("#contacts_synchronization_initial_db_load_initial_db_load_attribute_value").hide();
            }
            $("#contacts_synchronization_initial_db_load_initial_db_load1").click(function () {
                $("#contacts_synchronization_initial_db_load_initial_db_load_attribute").hide();
                $("#contacts_synchronization_initial_db_load_initial_db_load_attribute_value").hide();
            });
            $("#contacts_synchronization_initial_db_load_initial_db_load2").click(function () {
                $("#contacts_synchronization_initial_db_load_initial_db_load_attribute").hide();
                $("#contacts_synchronization_initial_db_load_initial_db_load_attribute_value").hide();
            });
            $("#contacts_synchronization_initial_db_load_initial_db_load3").click(function () {
                $("#contacts_synchronization_initial_db_load_initial_db_load_attribute").show();
                $("#contacts_synchronization_initial_db_load_initial_db_load_attribute_value").show();
            });
            $("#emarsys_predict_feed_export_categories_inherit").change(function () {
                if ($(this).prop('checked')) {
                    $('#emarsys_predict_feed_export_excludedcategories_inherit').prop('checked', true);
                    $('#emarsys_predict_feed_export_excludedcategories').addClass('disabled');
                    $('#emarsys_predict_feed_export_excludedcategories').prop('disabled', true);
                } else {
                    $('#emarsys_predict_feed_export_excludedcategories_inherit').prop('checked', false);
                    $('#emarsys_predict_feed_export_excludedcategories').removeClass('disabled');
                    $('#emarsys_predict_feed_export_excludedcategories').prop('disabled', false);
                }
            });
            $('#emarsys_predict_feed_export_categories_inherit').trigger('change');
        })
    }
);

function support(url, supportvalue)
{
    url = url + '?supportType=' + supportvalue;
    jQuery.ajax({
        url: url,
        cache: false,
        success: function (html) {
            if (html) {
                jQuery('#email').val(html);
                jQuery('#show-email').parent().parent().show();
                jQuery('#show-email').text(html).show();
                if (supportvalue == 6) {
                    alert("This should be used only for emergency requests");
                }
            } else {
                jQuery('#show-email').parent().parent().hide();
            }
        }
    });
}

function changeEmarsysValue(url, emarsyseventId, magentoeventId, Id)
{
    var url = url + '?emarsyseventId=' + emarsyseventId + '&magentoeventId=' + magentoeventId + '&Id=' + Id;
    new Ajax.Request(url, {
        method: 'get',
        asynchronous: false
    });
}

function changeValue(url, attribute, coulmnAttr, value, entityTypeId)
{
    var url = url + '?attribute=' + attribute + '&entityTypeId=' + entityTypeId + '&attributeValue=' + value + '&coulmnAttr=' + coulmnAttr;
    new Ajax.Request(url, {
        method: 'get',
        asynchronous: false
    });
}

require([
    'jquery',
    'tinymce',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'loadingPopup',
    'mage/backend/floating-header'
], function (jQuery, tinyMCE, confirm) {
    'use strict';

    /**
     * Sync Category
     */
    function syncNow(url)
    {
        confirm({
            content: 'Are you sure you want to sync?',
            actions: {
                confirm: function () {
                    location.href = url;
                }
            }
        });
    }

    function displayLoadingMask()
    {
        jQuery('body').loadingPopup();
    }

    window.syncNow = syncNow;
    window.displayLoadingMask = displayLoadingMask;
});

function placeholderRedirect(url)
{
    window.location = url;
}

function placeHolderValidation()
{
    var placeHolder = document.getElementsByName("emarsys_placeholder_name");
    var arrayOfObjects = [];
    var errorMessages = document.getElementsByClassName("placeholder-error");
    var totalErrorCount = 0;
    for (var i = 0; i < placeHolder.length; i++) {
        emarsys_placeholder = placeHolder[i].value;
        emarsys_placeholder_id = placeHolder[i].id;

        var obj = {};
        obj[emarsys_placeholder_id] = emarsys_placeholder;
        arrayOfObjects.push(obj);
        var regexp = /^[a-zA-Z0-9-_]+$/;
        if (emarsys_placeholder.search(regexp) == -1) {
            totalErrorCount = totalErrorCount + 1;
            errorMessagesShow(i);
        } else {
            errorMessagesHide(i);
        }
    }
    if (totalErrorCount == 0) {
        document.getElementById('placeholderData').value = JSON.stringify(arrayOfObjects);
        document.getElementById('eventsPlaceholderForm').submit();
    }
}

function errorMessagesShow(id)
{
    document.getElementById('divErrPlaceholder_' + id).style.display = '';
}

function errorMessagesHide(id)
{
    document.getElementById('divErrPlaceholder_' + id).style.display = 'none';
}

function openMyPopup(url)
{
    jQuery(".loading-mask").css("display", "block");
    jQuery.ajax({
        url: url,
        method: "GET",
        success: function (data) {
            document.getElementById('json-data-container').innerHTML = '<pre>' + data + '</pre>';
            jQuery(".loading-mask").css("display", "none");
            jQuery("#myModal").css("display", "block");
        },
        error: function (jqXhr, textStatus, errorThrown) {
            console.log(errorThrown);
        }
    });
}

window.onload = function () {
    if (document.getElementById('contacts_synchronization_initial_db_load_initial_db_load_inherit') && document.getElementById('contacts_synchronization_initial_db_load_initial_db_load_inherit').checked) {
        document.getElementById('contacts_synchronization_initial_db_load_initial_db_load1').disabled = true;
        document.getElementById('contacts_synchronization_initial_db_load_initial_db_load2').disabled = true;
        document.getElementById('contacts_synchronization_initial_db_load_initial_db_load3').disabled = true;
    }
    if (document.getElementById('row_emarsys_settings_ftp_settings_apiurl')) {
        document.getElementById('row_emarsys_settings_ftp_settings_apiurl').style.display = 'none';
    }
};

function Unchecked(value) {
    document.getElementById('catCheckBox_' + value).click();
}

function categoryClick(value,catName) {
    if(document.getElementById('catCheckBox_' + value).checked == true)
    {
        document.getElementById('selectedCategories').innerHTML += '<span id=\''+value+'\' onclick=\'Unchecked('+value+')\' class=\"admin__action-multiselect-crumb\">'+catName+'<button class=\"action-close\" type=\"button\"><span class=\"action-close-text\"></span></button></span>';
    } else {
        document.getElementById(value).remove();
    }
}
