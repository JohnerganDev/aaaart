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

    // Modal submit
    $("#create-collection-form button#save").click(function(){
        console.log('saving!');
        $.ajax({
            type: "POST",
            url: base_url + "collection/index.php",
            data: $('#create-collection-form > form').serialize(), 
            success: function(msg){
                window.location.reload();    
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    });

    // making a request
    $("#request-form button#save").click(function(){
        $.ajax({
            type: "POST",
            url: base_url + "collection/index.php",
            data: $('#request-form > form').serialize(), 
            success: function(msg){
                window.location.reload();    
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    });

    // Adding document to a collection
    $("#sort-into-collection-button").click(function(){
        if ($coll_select.val()!="none") {
            var $modaldetail = $('.modal-footer .modal-detail');
            if ($modaldetail.length) {
                var file = $('.modal-footer .modal-detail').attr('href');
                var regex = /[.]*\?id=([\w-]+)/;
                var id = file.match(regex)[1]; // id = 'Ahg6qcgoay4'
            } else {
                var id = getURLParameter("id");
            } 
            if (id) {
                $.ajax({
                    type: "POST",
                    url: base_url + "collection/index.php",
                    data: { 
                        action: 'add',
                        collection_id: $coll_select.val(),
                        document_id: id,
                    }, 
                    dataType: 'json',
                    success: function(result){
                        if (result.collection) {
                            if ($coll_list.length) {
                                aaaart_add_collection_to_list($coll_list, result.collection);
                            }
                        } else if (result.message) { alert(result.message); 
                        } else { alert("Sorry, that didn't work!"); }
                    },
                    error: function(){
                        alert("Sorry, that didn't work!");
                    }
                });
            } else {
                alert("Sorry, that didn't work!");
                $("select#sort-into-collection").hide();
            }
        }
    });

    // Follow/ unfollow
    $(document).on("click", ".btn.follow", function(){
        var $ele = $(this);
        $.ajax({
            type: "POST",
            url: base_url + "collection/index.php",
            data: { 
                action: 'follow', 
                id: $(this).attr("id")
            }, 
            dataType: 'json',
            success: function(result) {
                var html = $.parseHTML( result.button );
                $ele.replaceWith( html );
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    });

    function loadCollections(id, into_ele) {
        // let's also include makers, not just collections
        $.ajax({
            type: "GET",
            url: base_url + 'image/index.php',
            data: {action: 'list_makers', id: id},
            dataType: 'json',
            success: function(result) {
                aaaart_build_makers_list(into_ele, result.makers);
            }
        });
        // collections
        $.ajax({
            type: "GET",
            url: base_url + 'collection/index.php',
            data: {action: 'list_collections', show: 'document', arg: id},
            dataType: 'json',
            success: function(result) {
                aaaart_build_collections_list(into_ele, result.collections);
                // remove collections from select list that are already in list
                if ($coll_select.length) {
                    $.each(result.collections, function (index, coll) {
                        $coll_select.find("option[value='"+coll._id+"']").remove();
                    });
                }
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    }

    // Things to execute every time this script runs...
    var $coll_block = $("#in-collections");
    var $coll_list = $coll_block.find("ul.collections");
    var $coll_select = $("#sort-into-collection");
    if ($coll_block.length) {
        var id = $coll_block.attr("data-objectid");
        loadCollections(id, $coll_list);
    }
});
