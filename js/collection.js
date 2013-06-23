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
    var $list = $('ul#collections');
    function loadCollections(show) {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'collection/index.php',
            data: {action: 'list_collections', show: show, arg: letter},
            dataType: 'json',
        }).done(function (result) {
            $list.empty();
            if (result.collections) {
                if (result.collections.initiated || result.collections.collaborating || result.collections.following) {
                    $.each(result.collections, function (index, collection_group) {
                        var $sublist = $('<ul>');
                        $.each(collection_group, function (index, collection) {
                          var $item = $('<li>').attr('id', collection._id).append(
                              $('<a>')
                              .attr('href', base_url + 'collection/detail.php?id=' + collection._id)
                              .html(collection.title)
                          );
                          $sublist.append($item);
                        });
                        $list.append($('<li>').html(index).append($sublist));
                    });
                } else {
                    aaaart_build_collections_list($list, result.collections);
                }
            }
        });
    }

    $("#collections-filter a.btn").click(function() {
        var new_letter = $(this).text();
        if (letter==new_letter) return false;
        else {
            letter = new_letter;
            loadCollections('filter');
        }
    });
    // ---

    if (getURLParameter('show')=='mine') {
        loadCollections('mine');
    } else if ($("#collections-filter").length) {
        loadCollections('filter');
    } else {
        loadCollections('all');
    }

});
