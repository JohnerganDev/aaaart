
$(function () {
    'use strict';

    // re-use the comments modal by flushing data on close
    $('#comments.modal').on('hidden', function () { 
        $(this).off('.modal').removeData('modal') 
    });

    var page = 0;
    var $list = $('ul#discussions');
    function loadDiscussions(type) {
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: base_url + 'comment/index.php',
            data: {action: 'get_comments', type: type, _p: page},
            dataType: 'json',
        }).done(function (result) {
            if (page==0) $list.empty();
            if (result.comments) {
                $.each(result.comments, function (index, comment) {
                    $list.append(
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
                });
            }
        });
    }

    function loadMoreComments() {
        if (getURLParameter("show")=="commented") {
            loadDiscussions("commented");
        } else {
            loadDiscussions("new");
        }
    }

    if ($list.length) {
        loadMoreComments();
    }

    $("button#more").click(function() {
        page = page + 1;
        loadMoreComments();
    });

});