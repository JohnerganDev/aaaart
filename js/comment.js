
$(function () {
    'use strict';

    // re-use the comments modal by flushing data on close
    $('#comments.modal').on('hidden', function () { 
        $(this).off('.modal').removeData('modal') 
    });

    var $list = $('table#discussions');
    function loadDiscussions(type) {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'comment/index.php',
            data: {action: 'get_comments', type: type},
            dataType: 'json',
        }).done(function (result) {
            $list.empty();
            if (result.comments) {
                $.each(result.comments, function (index, comment) {
                    $list.append(
                        $('<tr>').append($('<td>')
                            .append(
                                $('<h4>').append($('<a>')
                                    .attr('href', comment.thread_url)
                                    .attr('data-toggle','modal')
                                    .attr('data-target', '#comments')
                                    .addClass('comments')
                                    .html(comment.thread_title)
                            ))
                            .append($('<p>').addClass('lead').html(comment.text))
                            .append($('<small>').html(comment.display_user + ' on ' + comment.display_date))
                    ));
                });
            }
        });
    }

    if ($list.length) {
        loadDiscussions("new");
    }

});