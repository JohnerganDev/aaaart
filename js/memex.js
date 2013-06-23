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

    function memexUpdate(url) {
        $.ajax({
            type: "POST",
            url: base_url + "memex/index.php",
            data: {action: 'update_path_and_reload', url: url}, 
            dataType: 'json',
            success: function(data){
                if (data.memex) {
                    $container.html(data.memex);
                }
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    }

    var $container = $('#footer');

    if ($container.is(":visible")) {
        memexUpdate(document.URL);
    }

});
