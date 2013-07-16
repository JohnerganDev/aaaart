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
                aaaart_add_item_to_gallery(file, gallery);
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
            data: {action: 'list_collections', show: 'active'},
            dataType: 'json',
            success: function(result) {
                aaaart_build_collections_list($('#active-collections'), result.collections);
                if (result.collections) {
                    $('#active-collections').prepend($('<li><span class="label">active collections</span></li>'));   
                }
            },
            error: function(){
                //alert("Sorry, that didn't work!");
            }
        });
    }

    loadDocs();
    loadCollections();
    
    $("button#more").click(function() {
        page = page + 1;
        loadDocs();
    });

});
