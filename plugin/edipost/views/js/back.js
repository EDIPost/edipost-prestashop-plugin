/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
function message(msg, type) {
    var html_str = '<div class="alert alert-' + type + '">' + msg + '</div>';
    $('.edipost-wrapper #error-block').html(html_str);
    $('.edipost-wrapper #error-block').show();
}

/**
 * Open address in www.edipost.no
 */
$(document).on('click', '#edipost-open', function (e) {
    e.preventDefault();
    $('#edipost-open').attr("disabled", true);

    $.ajax({
        type: 'POST',
        dataType: 'JSON',
        cache: false,
        url: psr_controller_edipost_url,
        data: {
            id_order: $('#id_order').val(),
            ajax: true,
            action: 'OpenUrl',
        },

        success: function (data) {
            $('#edipost-open').attr("disabled", false);

            if (!(data.error)) {
                window.open(data.url, '_blank');
                message( 'OK', 'success');
            } else {
                console.log(JSON.stringify(data));
                message( JSON.stringify(data.error), 'error');
            }
        },
        error: function (data) {
            $('#edipost-open').attr("disabled", false);
            message( JSON.stringify(data.error), 'error');
        }
    });
});

$(document).on('change', 'select#edipost_ship_method', function (e) {
    e.preventDefault();
    edipost_check_selected_ship();
});

$(document).on('ready', function () {
    edipost_check_selected_ship();
});

function edipost_check_selected_ship(){  // enable buttons after choose shipping method
    if($('select#edipost_ship_method').val() != 0){
        $('#edipost-create').attr("disabled", false);
    } else {
        $('#edipost-create').attr("disabled", true);
    }
}


/**
 * Create consignment using the API
 */
$(document).on('click', '#edipost-create', function (e) {
    e.preventDefault();
    $('.edipost-wrapper .loader').show();
    var e_alert = 0;

    if ($('#edipost_e_alert').is(':checked')) {
        e_alert = 1;
    }

    $('#edipost-create').attr("disabled", true);
    // $('body').loader('show');

    $.ajax({
        type: 'POST',
        dataType: 'JSON',
        cache: false,
        url: psr_controller_edipost_url,
        data: {
            ajax: true,
            action: 'CreateShipment', // prestashop already set camel case before execute method
            id_order: $('#id_order').val(),
            product_id: $('#edipost_ship_method').val(),
            service_id: $('#edipost_ship_method').find(":selected").data('service'),
            e_alert: e_alert,
            reference: $('#edipost_reference').val(),
        },

        success: function (data) {
            $('#edipost-create').attr("disabled", false);

            if( ! data.error ){
                console.log(data.pdf);
                var a = document.createElement('a');
                a.href= data.pdf;
                a.target = '_blank';
                a.download = 'consignment.pdf';
                a.click();
                message( 'Consignment was created: <a href="' + data.pdf + '" target="_blank">consignment.pdf</a>', 'success' );
            } else {
                message( 'Error when creating consignment: ' + data.error, 'warning' );
            }
            $('.edipost-wrapper .loader').hide();
        },
        error: function (data) {
            $('#edipost-create').attr("disabled", false);
            message( 'Error when creating consignment: ' + data.error, 'error' );
            $('.edipost-wrapper .loader').hide();
        }
    });
});