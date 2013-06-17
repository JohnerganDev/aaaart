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
    var collection_id = $('#metadata #collection-id').val(); 
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: action
    });

    $.ajax({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: base_url + 'collection/index.php',
        data: {action: 'documents', id: collection_id},
        dataType: 'json',
    }).done(function (result) {
        var gallery = $('#gallery'),
            url;
        $.each(result.files, function (index, file) {
            console.log(file);
            var $thumbnail = aaaart_render_thumbnail(file);
            var $button = $('<button>')
                .addClass('btn')
                .addClass('btn-mini')
                .addClass('btn-danger')
                .attr('type','button')
                .attr('style' , 'display: none;')
                .html('<i class="icon-remove icon-white"></i> Remove');
            $button.click(function() {
                $.ajax({
                    type: "DELETE",
                    url: base_url + "collection/index.php?collection=" + collection_id + '&document=' + file.document_id,
                    success: function(msg){
                        $button.parent().hide();  
                    },
                    error: function(){
                        alert("Sorry, that didn't work!");
                    }
                });
            });
            gallery.append($('<li class="image">').append($button).append($thumbnail));
        });
    });

    function loadMakersForCollection(collection_id) {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'collection/index.php',
            data: {'action': 'makers_for_collection', 'id': collection_id},
            dataType: 'json',
        }).done(function (result) {
            var $list = $('#makers-list'),
            url;
            $.each(result.makers, function (index, maker) {
                var $item = $('<li>').attr('id', maker._id).append(
                    $('<a>')
                    .attr('href', base_url + 'collection/maker.php?id=' + maker._id)
                    .html(maker.display)
                );
                $list.append($item);
            });
        });
    }

    loadMakersForCollection(collection_id);

    // Modal submit
    $("button#save").click(function(){
        console.log('saving!');
        $.ajax({
            type: "POST",
            url: base_url + "collection/index.php",
            data: $('#edit-form > form').serialize(), 
            success: function(msg){
                document.location.href = base_url + "collection/detail.php?id=" + collection_id    
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
            url: base_url + "collection/index.php?collection=" + collection_id,
            success: function(msg){
                document.location.href = base_url   
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    });

    // Modal delete
    $("button#invite").click(function(){
        console.log('inviting!');
        $.ajax({
            type: "POST",
            url: base_url + "collection/index.php",
            data: $('#invite-form > form').serialize(), 
            success: function(msg){
                $("#invite-form").modal('hide');
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    });

    // Toggle remove buttons
    $("#edit-contents-toggle").click(function(){
        $('ul#gallery li.image .btn').toggle();
    });

});
