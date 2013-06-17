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
    var $list = $('ul#makers');
    function loadMakers() {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'collection/index.php',
            data: {'action': 'list_makers', 'filter': letter},
            dataType: 'json',
        }).done(function (result) {
            $list.empty();
            aaaart_build_makers_list($list, result.makers);
        });
    }

    $("#makers-filter a.btn").click(function() {
        var new_letter = $(this).text();
        if (letter==new_letter) return false;
        else {
            letter = new_letter;
            loadMakers();
        }
    });
    // ---

    var action = $('#fileupload').attr('action'); 
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: action
    });

    var $maker_id = $('#metadata #maker-id'); 
    function loadDocumentsForMaker(maker_id) {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'collection/index.php',
            data: {'action': 'documents_by_maker', 'id': maker_id},
            dataType: 'json',
        }).done(function (result) {
            var gallery = $('#gallery'),
            url;
            $.each(result.files, function (index, file) {
                var $thumbnail = aaaart_render_thumbnail(file, false);
                gallery.append($('<li class="image">').append($thumbnail));
            });
        });
    }

    function loadCollectionsForMaker(maker_id) {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'collection/index.php',
            data: {'action': 'collections_for_maker', 'id': maker_id},
            dataType: 'json',
        }).done(function (result) {
            var $list = $('#collections-list'),
            url;
            aaaart_build_collections_list($list, result.collections);
        });
    }

    if ($list.length) {
        loadMakers();
    } else if ($maker_id.length) {
        loadDocumentsForMaker($maker_id.val());
        loadCollectionsForMaker($maker_id.val());
    }

});
