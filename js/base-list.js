

var current_date = '';

function aaaart_render_thumbnail_subtext(file) {
	if (file.metadata.one_liner) {
		return $('<p class="lead">').html(file.metadata.one_liner);
  } else {
  	return '';
  }
}

function aaaart_render_thumbnail_title(file) {
	var $link = $("<a>")
    .addClass('title')
    .prop('href', file.detail_url)
    .prop('title',file.metadata.title)
    .html(file.metadata.title);
  if (file.is_request) { $link.append(' ').append($('<small>').addClass('label').text('request')); }
  if (file.is_media) { $link.append(' ').append($('<small>').addClass('label').text('video')); }
  var $title = $('<h5>')
    .append($link);
  return $title;
}

function aaaart_render_thumbnail_author(file) {
	return $('<h6>').html(file.metadata.maker);
}

function aaaart_render_thumbnail(file, show_author) {
	show_author = (typeof show_author === "undefined") ? true : show_author;
	var $title = aaaart_render_thumbnail_title(file);
	var $subtext = aaaart_render_thumbnail_subtext(file);
  var $container = $('<div>');
  $container.append($title);
  if (show_author) {
  	$author = aaaart_render_thumbnail_author(file);
  	$container.append($author);
  }
  $container.append($subtext);
  return $container;
}

function aaaart_add_item_to_gallery(file, gallery, show_maker, show_date) {
  show_maker = (typeof show_maker === "undefined") ? true : show_maker;
  show_date = (typeof show_date === "undefined") ? false : show_date;
  if (file.date) {
    if (file.date!=current_date) {
      current_date = file.date;
      gallery.append($('<li>').append($('<h4>').addClass('muted').text(current_date)));
    }
  }
  var $thumbnail = aaaart_render_thumbnail(file, show_maker);
  var $item = $('<li class="image">').attr('data-id',file.document_id).append($thumbnail);
  gallery.append($item);
  return $item;
}