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
                var $thumbnail = aaaart_render_thumbnail(file);
                gallery.append($('<li class="image">').append($thumbnail));
            });
            if (result.files.length===0) {
                $("button#more").hide();
            }
        });
    }

    loadDocs();
    
    $("button#more").click(function() {
        page = page + 1;
        loadDocs();
    });

});
