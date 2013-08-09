

var masonryInitialized = false;

function initMasonry(container, item_selector) {
	container.masonry({
		itemSelector: item_selector
	}).imagesLoaded(function() {
		if (masonryInitialized) {
			container.masonry('reloadItems');
		}
	});
	masonryInitialized = true;
}

function aaaart_render_thumbnail_image(file) {
	/*
	if (file.placeholder) {
		var $image = $('<a data-gallery="gallery"/>')
	    .append(
	    	$('<img>').prop('src', file.thumbnail_url)
	    )
	    .append(
	    	$('<h5>').addClass('overlay').html(file.metadata.title + ' // ' + file.metadata.maker)	
	    )
	    .attr('data-detail', file.detail_url)
		  .prop('href', file.url)
	    .prop('title', file.metadata.title)
	    .prop('download', file.name);
	} else {
		var $image = $('<a data-gallery="gallery"/>')
	    .append($('<img>').prop('src', file.thumbnail_url))
	    .attr('data-detail', file.detail_url)
		  .prop('href', file.url)
	    .prop('title', file.metadata.title)
	    .prop('download', file.name);
	}
	*/
	var $image = $('<a>')
	    .append($('<img>').prop('src', file.thumbnail_url))
	    //.attr('data-detail', file.detail_url)
		  .prop('href', file.detail_url)
	    .prop('title',file.metadata.title)
	    //.prop('download', file.name);
  return $image;
}

function aaaart_render_thumbnail_title(file, show_maker) {
	/*
	if (file.placeholder) {
		var $title = $("<a data-gallery='gallery'>")
	    .attr('data-detail', file.detail_url)
		  .prop('href', file.url)
	    .prop('title',file.metadata.title)
	    .prop("download",file.name)
	    .html('');
	} else {
		var $title = $('<small>')
	    .append($("<a data-gallery='gallery'>")
	    .attr('data-detail', file.detail_url)
		  .prop('href', file.url)
	    .prop('title',file.metadata.title)
	    .prop("download",file.name)
	    .html(file.metadata.title));
	}
	*/
	var $title = $('<small>')
    .append($("<a>")
    .addClass('title')
    .prop('href', file.detail_url)
    .prop('title',file.metadata.title)
    .html(file.metadata.title));
  if (show_maker) {
  	$title.append(" ").append($("<span>")
  		.html(file.metadata.maker)
  	);
  }
  return $title;
}

function aaaart_render_thumbnail(file, show_maker) {
	show_maker = (typeof show_maker === "undefined") ? true : show_maker;
	var $title = aaaart_render_thumbnail_title(file, show_maker);
	var $image = aaaart_render_thumbnail_image(file);
  var $container = $('<div>');
  $container.append($image).append($title);
  return $container;
}

function aaaart_add_item_to_gallery(file, gallery, show_maker) {
	show_maker = (typeof show_maker === "undefined") ? true : show_maker;
	var $thumbnail = aaaart_render_thumbnail(file, show_maker);
  var $box = $('<li class="image">').attr('data-id',file.document_id).append($thumbnail);
  if (!masonryInitialized) {
      gallery.append($box);
      $box.imagesLoaded(function() {
      	initMasonry(gallery, '.image');
      });
  } else {
      gallery.append($box);
      $box.imagesLoaded(function() {
      	gallery.masonry('appended', $box, true);
      });
  }
  return $box;
}
