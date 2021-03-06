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

// Default function parameter
function valOrDefault(v, d) {
    return (typeof v === "undefined") ? d : v;
}

// Gets a URL parameter
function getURLParameter(name) {
    return decodeURI(
        (RegExp(name + '=' + '(.+?)(&|$)').exec(location.search)||[,null])[1]
    );
}

function aaaart_add_maker_to_list(list, maker) {
    list.append(
        $('<li>').attr('id', maker._id).append(
            $('<a>')
            //.addClass('label label-warning')
            .addClass('text-success')
            .prop('title',maker.display)
            .attr('href', base_url + 'collection/maker.php?id=' + maker._id)
            .html(maker.display)
    ));
}

function aaaart_add_collection_to_list(list, collection) {
    list.append(
        $('<li>').attr('id', collection._id).append(
            $('<a>')
            //.addClass('label label-success')
            .addClass('text-danger')
            .prop('title',collection.title)
            .attr('href', base_url + 'collection/detail.php?id=' + collection._id)
            .html(collection.title)
    ));
}

function aaaart_add_comment_thread_to_list(list, comment) {
    list.append(
        $('<li>').addClass('list-group-item')
            .append($('<p>').addClass('lead').append($('<a>')
                    .attr('href', comment.thread_url)
                    .attr('data-toggle','modal')
                    .attr('data-target', '#comments')
                    .addClass('comments')
                    .addClass('comments-title')
                    .html(comment.thread_title)
            ))
            .append($('<p>')
                .append($('<span>').addClass('text-muted').html(comment.text))
                .append($('<span>').addClass('text-muted').html(' ' + comment.display_user + ' on ' + comment.display_date))
            )
    );
}

// adds list items of makers
function aaaart_build_makers_list(list, arr) {
    $.each(arr, function (index, maker) {
        aaaart_add_maker_to_list(list, maker);
    });
}

// adds list items of collections
function aaaart_build_collections_list(list, arr) {
    $.each(arr, function (index, collection) {
        aaaart_add_collection_to_list(list, collection);
    });
}

// adds list items of comments
function aaaart_build_comment_threads_list(list, arr) {
    $.each(arr, function (index, thread) {
        aaaart_add_comment_thread_to_list(list, thread);
    });
}


// Toggles a document in and out of a user's library
function aaaart_save_document(id) {
    // @todo: this should have error checking, waiting properly, etc
    $.ajax({
        type: "POST",
        url: base_url + "image/index.php",
        data: { 
            action: 'save_document', 
            id: id
        }, 
    });
}


// adds save buttons to documents in a list
function aaaart_add_save_buttons(list, saved, remove_from_list) {
    remove_from_list = valOrDefault(remove_from_list, false);
    if ($('body').hasClass('logged-out')) return;
    list.children('li.image').each( function(i, item) {
        var $item = $(item);
        var id = $item.attr("data-id");
        var add_button_text = 'add to your library';
        var remove_button_text = 'saved!';
        if ($item.hasClass('request')) {
            add_button_text = '+1 this request';
            remove_button_text = 'requested!';
        } 
        if (id) {
            var $button = $('<button class="btn btn-xs saver" type="button">'); 
            if ($.inArray(id, saved)==-1) {
                $button.text(add_button_text);
                $button.addClass('do-add');
            } else {
                $button.text(remove_button_text);
                $button.addClass('do-remove btn-success');
            }
            $item.append($button);
            $item.hoverIntent( function () {
                $button.toggle();
            });
            $button.click(function() {
                aaaart_save_document(id);
                if ($button.hasClass('do-add')) {
                    $button.text(remove_button_text);
                    $button.addClass('do-remove btn-success');
                    $button.removeClass('do-add');
                } else {
                    $button.text(add_button_text);
                    $button.addClass('do-add');
                    $button.removeClass('do-remove btn-success');
                    if (remove_from_list) {
                        $item.hide();
                    }
                }
            });
        }
    });
}

// For adding reference button to markdown
function aaaart_markdown_buttons() {
    return [
    [{
          name: "aaaartReference",
          data: [{
            name: "cmdRef",
            toggle: true, // this param only take effect if you load bootstrap.js
            title: "Add a Reference",
            icon: "glyphicon glyphicon-share",
            callback: function(e){
                // Replace selection with some drinks
                var chunk, cursor, 
                  selected = e.getSelection(), 
                  content = e.getContent(),
                  $modal = $('#create-reference-form')

                $modal.on('hide', function() {
                    // Set the insertion text
                    var id = $modal.attr("data-found"), display = $modal.attr("data-display")
                    if (id!='') {
                        chunk = '['+display+']{'+id+'}'
                        // transform selection and set the cursor into chunked text
                        e.replaceSelection(chunk)
                        cursor = selected.start

                        // Set the cursor
                        e.setSelection(cursor,cursor+chunk.length)
                    }
                })

                $('#create-reference-form').modal()
            }
          }]
    }]
  ];
}


$(function () {
    'use strict';
    
    // tooltips
    $("[rel='tooltip']").tooltip();

    // help popover
    $('.uploading-help-trigger').popover({
        title: 'Upload notes',
        content: $("#uploading-help").html(),
        html: true
    });

    // select styling
    $('.selectpicker').selectpicker();

    var $list = $("#gallery.search-results");
    var $list2 = $("#makers-and-collections-list");
    var $list3 = $("#discussions-list");
    
    function doSearch(q, f) {
        var filter = valOrDefault(f, false);
        $list.html('Looking...');
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'collection/index.php',
            data: {action: 'search', filter: filter, q: q},
            dataType: 'json',
        }).done(function (result) {
            $list.html('');
            if (result.files) {
                $.each(result.files, function (index, file) {
                    aaaart_add_item_to_gallery(file, $list);
                });
            }
            if (result.makers) {
                aaaart_build_makers_list($list2, result.makers);
            }
            if (result.collections) {
                aaaart_build_collections_list($list2, result.collections);
            }
            if (result.discussions) {
                aaaart_build_comment_threads_list($list3, result.discussions);
            }
            /*
            // @todo: paginating search results
            if (result.files.length===0) {
                $("button#more").hide();
            }
            */
        });
    }
    
    // When search button is clicked
    $("form.form-search").submit(function() {
        var q = $("form.form-search .search-query").val();
        if (q.length) {
            if ($list.length) {
                doSearch(q);
            } else {
                document.location.href = base_url + 'collection/search.php?q=' + encodeURI(q);
            }
        }
        return false;
    });

    // When search button is clicked
    $("form.form-search").on('click', '#search-discussions', function() {
        var q = $("form.form-search .search-query").val();
        if (q.length) {
            if ($list.length) {
                doSearch(q, 'discussions');
            } else {
                document.location.href = base_url + 'collection/search.php?f=discussions&q=' + encodeURI(q);
            }
        }
        return false;
    });

    // When the page loads, check if there is a search results section and if so, check if there's a query to run
    $(document).ready(function() {
        if ($list.length) {
            var q = getURLParameter('q');
            var f = getURLParameter('f');
            doSearch(q, f);
        }
    });

});
