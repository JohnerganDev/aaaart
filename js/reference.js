$(function () {
    'use strict';

    var $modal = $('#create-reference-form'),
    $list = $('#create-reference-form ul.images'),
    $makers = $('#create-reference-form ul.makers'),
    $collections = $('#create-reference-form ul.collections'),
    $query = $('#create-reference-form .modal-body input[name="q"]'),
    $button = $('#create-reference-form .modal-body button.search')


    $modal.on('hidden', function (e) { 
        $(this).off('.modal').removeData('modal') 
    });

    $modal.on('show', function(e) {
    	$modal.attr("data-found", '')
    	$modal.attr("data-display", '')
    });

    $list.on('click', 'li.image a.title', function(e) {
    	e.preventDefault()
    	$modal.attr("data-found", 'images:' + $(this).closest("li").attr("data-id"))
    	$modal.attr("data-display", $(this).prop("title"))
    	$modal.modal('hide')
    	return false
    });

    $makers.on('click', 'li a', function(e) {
    	e.preventDefault()
    	$modal.attr("data-found", 'makers:' + $(this).closest("li").attr("id"))
    	$modal.attr("data-display", $(this).prop("title"))
    	$modal.modal('hide')
    	return false
    });

    $collections.on('click', 'li a', function(e) {
    	e.preventDefault()
    	$modal.attr("data-found", 'collections:' + $(this).closest("li").attr("id"))
    	$modal.attr("data-display", $(this).prop("title"))
    	$modal.modal('hide')
    	return false
    });

		function doSearch(q) {
      $.ajax({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: base_url + 'collection/index.php',
        data: {action: 'search', q: q},
        dataType: 'json',
      }).done(function (result) {
        $.each(result.files, function (index, file) {
          aaaart_add_item_to_gallery(file, $list);
        });
        aaaart_build_makers_list($makers, result.makers);
        aaaart_build_collections_list($collections, result.collections);
      });
    }

    // When search button is clicked
    $button.on('click', function(e) {
      var q = $query.val();
      if (q.length) {
      	$list.html('Searching...');
      	$makers.html('');
      	$collections.html('');
        doSearch(q);
      }
      return false;
    });

});