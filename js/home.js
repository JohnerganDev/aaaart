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
    var $collections_ele = $('#active-collections');
    var $discussions_ele = $('#active-discussions');

    function loadDocs() {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'collection/index.php',
            data: {_p: page},
            dataType: 'json',
        }).done(function (result) {
            var gallery = $('#gallery'),
                url;
            $.each(result.files, function (index, file) {
                aaaart_add_item_to_gallery(file, gallery, true, true);
            });
            if (result.files.length===0) {
                $("button#more").hide();
            }
        });
    }

    function loadCollections() {
        $.ajax({
            type: "GET",
            url: base_url + 'collection/index.php',
            data: {action: 'active_collections'},
            dataType: 'json',
            success: function(result) {
                $collections_ele.html(result.list);
            },
            error: function(){
                //alert("Sorry, that didn't work!");
            }
        });
    }

    function loadDiscussions() {
        $.ajax({
            type: "GET",
            url: base_url + 'comment/index.php',
            data: {action: 'get_comments', type: 'new', _p: 0},
            dataType: 'json',
            success: function(result) {
                if (result.comments) {
                    $discussions_ele.append('<h5 class="muted">recent comments</h5>');
                    $.each(result.comments, function (index, comment) {
                        $discussions_ele.append(
                            $('<li>')
                                .append($('<h4>').append($('<a>')
                                    .attr('href', comment.thread_url)
                                    .attr('data-toggle','modal')
                                    .attr('data-target', '#comments')
                                    .addClass('comments-title')
                                    .html(comment.thread_title)))
                                .append($('<p>').html(comment.text))
                                .append($('<small>').addClass('muted').html(' ' + comment.display_user + ' on ' + comment.display_date))
                        );
                    });
                }
            },
            error: function(){
                //alert("Sorry, that didn't work!");
            }
        });
    }

    loadDocs();
    if ($collections_ele.length) {
        loadCollections();
    }
    if ($discussions_ele.length) {
        loadDiscussions();
    }

    $("button#more").click(function() {
        page = page + 1;
        loadDocs();
    });

});
