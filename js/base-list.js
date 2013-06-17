



function aaaart_render_thumbnail_subtext(file) {
	if (file.metadata.one_liner) {
		return $('<p class="lead">').html(file.metadata.one_liner);
  } else {
  	return '';
  }
}

function aaaart_render_thumbnail_title(file) {
	var $link = $("<a>")
    .prop('href', file.detail_url)
    .prop('title',file.metadata.title)
    .html(file.metadata.title);
  if (file.is_request) { $link.append($('<small>').addClass('muted').text(' [request]')); }
  var $title = $('<h4>')
    .append($link);
  return $title;
}

function aaaart_render_thumbnail_author(file) {
	return $('<h5>').html(file.metadata.maker);
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