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

    var letter = 'a';
    var gallery = $('#gallery');
    function loadDocs() {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'image/index.php',
            data: {'action': 'saved_documents', 'filter': letter},
            dataType: 'json',
        }).done(function (result) {
            gallery.empty();
            $.each(result.files, function (index, file) {
                aaaart_add_item_to_gallery(file, gallery, true);
            });
            // add save buttons
            if (result.saved) {
               aaaart_add_save_buttons(gallery, result.saved, true); 
            }
        });
    }

    $("#makers-filter a.btn").click(function() {
        var new_letter = $(this).text();
        if (letter==new_letter) return false;
        else {
            letter = new_letter;
            loadDocs();
        }
    });

    loadDocs();

});
