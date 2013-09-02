/*
 * jQuery File Upload Plugin JS Example 8.0
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document */

$(function () {
    'use strict';

    // Initialize the jQuery File Upload widget:
    var action = $('#fileupload').attr('action'); 
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: action
    });

    /*
    // Enable iframe cross-domain access via redirect option:
    $('#fileupload').fileupload(
        'option',
        'redirect',
        window.location.href.replace(
            /\/[^\/]*$/,
            '/cors/result.html?%s'
        )
    );
    */

    // check empty fields
    $('#fileupload').on("change textInput input", 'input[type=text]', function () {
        var ok = true;
        var v;
        $('#fileupload').find('input').each( function() {
            if ($(this).attr("required")) {
                v = $(this).val();
                if ($.trim(v)=='') {
                    ok = false;
                }
            }
        } );
        if (ok) {
            $(this).closest('tr').find('button.start').attr('disabled', false);
        } 
    });

    $('form#import-video,form#import-html').submit(function() {
        var v;
        $(this).find('input').each( function() {
            if ($(this).attr("required")) {
                v = $(this).val();
                if ($.trim(v)=='') {
                    alert('Please fill out all fields!');
                    return false;
                }
            }
        } );
        $.ajax({
            type: "POST",
            url: base_url + "image/index.php",
            data: $(this).serialize(), 
            dataType: 'json',
            success: function(result){
                console.log(result);
                 if (result.success) {
                    document.location.href = base_url + "image/detail.php?id=" + result.document_id;
                 } else {
                    alert("That didn't work! We might not be able to import URLs from that website");
                 }
            },
            error: function(){
                alert("Sorry, that didn't work! ");
            }
        });
        return false;
    });

});
