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

    var page = 0;
    var letter = 'a';
    var $list = $('#requests');
    // mode can be "documents" or "documents_and_sections"
    function loadRequests(sort, filter) {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'collection/index.php',
            data: {_p: page, action: 'requests', sort: sort, filter: filter},
            dataType: 'json',
        }).done(function (result) {
            $.each(result.files, function (index, file) {
                aaaart_add_item_to_gallery(file, $list);
            });
            if (result.files.length===0) {
                $("button#more").hide();
            }
        });
    }

    $("#makers-filter a.btn").click(function() {
        var new_letter = $(this).text();
        if (letter==new_letter) return false;
        else {
            $list.html('');
            letter = new_letter;
            loadRequests('maker', letter);
        }
    });

    $("button#more").click(function() {
        page = page + 1;
        loadRequests('date');
    });

    $('.sorter a').click(function () {
        if ($(this).hasClass('date')) {
            $list.html('');
            page = 0;
            loadRequests('date', false);
            $("button#more").show();
            $("#makers-filter").hide();
        } else {
            $list.html('');
            loadRequests('maker', 'a');
            $("button#more").hide();
            $("#makers-filter").show();
        }
        return false;
    });

    loadRequests('date', false);

});
