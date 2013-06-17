



function aaaart_render_thumbnail_image(file) {
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
  return $image;
}

function aaaart_render_thumbnail_title(file) {
	if (file.placeholder) {
		var $title = $("<a data-gallery='gallery'>")
	    .attr('data-detail', file.detail_url)
		  .prop('href', file.url)
	    .prop('title',file.metadata.title)
	    .prop("download",file.name)
	    .html('');
	} else {
		var $title = $('<h5>')
	    .append($("<a data-gallery='gallery'>")
	    .attr('data-detail', file.detail_url)
		  .prop('href', file.url)
	    .prop('title',file.metadata.title)
	    .prop("download",file.name)
	    .html(file.metadata.title));
	}
  return $title;
}

function aaaart_render_thumbnail(file) {
	var $title = aaaart_render_thumbnail_title(file);
	var $image = aaaart_render_thumbnail_image(file);
  var $container = $('<div>');
  $container.append($image).append($title);
  return $container;
}

