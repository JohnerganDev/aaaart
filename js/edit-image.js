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
    var document_id = $('#metadata #document-id').val(); 
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: action
    });

    // Add markdown to descrption
    $('textarea').markdown({width:500, autofocus:false, savable:false, additionalButtons: aaaart_markdown_buttons() });

    // Load existing files:
    $('#fileupload').addClass('fileupload-processing');
    $.ajax({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: base_url + 'image/index.php',
        data: {action: 'get_files', id: document_id},
        dataType: 'json',
        context: $('#fileupload')[0]
    }).always(function (result) {
        $(this).removeClass('fileupload-processing');
    }).done(function (result) {
        $(this).fileupload('option', 'done')
            .call(this, null, {result: result});
    });

    // Modal submit
    $("button#save").click(function(){
        console.log('saving!');
        $.ajax({
            type: "POST",
            url: base_url + "image/index.php",
            data: $('#edit-form > form').serialize(), 
            success: function(msg){
                document.location.href = base_url + "image/detail.php?id=" + document_id    
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    });

    // Modal delete
    $("button#delete").click(function(){
        console.log('deleting!');
        $.ajax({
            type: "DELETE",
            url: base_url + "image/index.php?image=" + document_id,
            success: function(msg){
                document.location.href = base_url   
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    });
});
