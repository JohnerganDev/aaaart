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

    // re-use the memex modal by flushing data on close
    $('#add-section-modal.modal').on('hidden', function () { 
        $(this).off('.modal').removeData('modal') 
    });

    $.ajax({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: base_url + 'collection/index.php',
        data: {action: 'documents_and_sections', id: collection_id},
        dataType: 'json',
    }).done(function (result) {
        var gallery = $('#gallery'),
            url;
        var collection_sections = new Array();
        // Sorting documents into sections
        var $sortButton = $('<div class="btn-group" style="display:none;">').append($('<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#">Move to section <span class="caret"></span></a>')).append($('<ul class="dropdown-menu">'));
        // Adding sections
        var $addSectionDiv = $('<div>')
            .attr('id','add-section')
            .attr('style' , 'display: none;')
            .addClass('alert alert-success');
        var $addSection = $('<a>')
            .attr('data-toggle','modal')
            .attr('data-target','#add-section-modal')
            .attr('href',base_url + 'collection/section.php?collection=' + collection_id)
            .text('click here');
        var $addSectionList = $('<ul>');
        // Loop through each section and do a few things:
        $.each(result.sections, function (index, section) {
            // a. Build an array of sections (ultimately displayed to user) 
            collection_sections[section.id] = $('<li>')
                .addClass('section well')
                .html(section.description)
                .prepend($('<h4>').text(section.title))
                .append($('<ul>').addClass('items'));
            gallery.append(collection_sections[section.id]);
            // b. create the section to put in a list of sections during "Edit contents"
            var $section = $('<a>')
                .attr('data-toggle','modal')
                .attr('data-target','#add-section-modal')
                .attr('href',base_url + 'collection/section.php?collection=' + collection_id + '&section=' + section.id)
                .text(section.title);
            $addSectionList.append(
                $('<li>').append($section)
            );
            // c. Add to a dropdown list of sections
            var $sortLink = $('<a>').attr('href','#').text(section.title);
            $sortButton.find('ul.dropdown-menu').append(
                $('<li>').append($sortLink)
            );
            $sortLink.click(function() {
                $.ajax({
                    // Uncomment the following to send cross-domain cookies:
                    //xhrFields: {withCredentials: true},
                    type: "POST",
                    url: base_url + 'collection/index.php',
                    data: {
                        action: 'sort_section', 
                        collection_id: collection_id,
                        document_id: $(this).closest('li.image').attr('data-id'),
                        section_id: section.id
                    },
                    dataType: 'json',
                });
                var ele = $(this).closest('li.image').detach();
                collection_sections[section.id].find('ul.items').append(ele);
                return false;
            });
        });
        // Create the administrative block for adding/ editing sections
        $addSectionDiv.html('In addition to removing items from this collection, you can organize the collection into sections. To add a new section ');
        $addSectionDiv.append($addSection);
        $addSectionDiv.append($addSectionList);
        gallery.prepend($addSectionDiv);

        // Now loop through the documents and add them into sections            
        $.each(result.files, function (index, file) {
            var $thumbnail = aaaart_render_thumbnail(file);
            var $removeButton = $('<button>')
                .addClass('btn')
                .addClass('btn-mini')
                .addClass('btn-danger')
                .attr('type','button')
                .attr('style' , 'display: none;')
                .html('<i class="icon-remove icon-white"></i> Remove');
            $removeButton.click(function() {
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
            if (file.section && (file.section in collection_sections)) {
                aaaart_add_item_to_gallery(file, collection_sections[file.section].find('ul.items'))
                    .prepend($sortButton.clone(true))
                    .prepend($removeButton);
            } else {
                aaaart_add_item_to_gallery(file, gallery)
                    .prepend($sortButton.clone(true))
                    .prepend($removeButton);
            }
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

    //loadMakersForCollection(collection_id);

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
        $('ul#gallery li.image button.btn').toggle();
        $('ul#gallery li.image div.btn-group').toggle();
        $('#add-section').toggle();
    });

});
