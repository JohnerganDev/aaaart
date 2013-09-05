<?php

require_once('../config.php');

$thread = aaaart_comment_load_thread_from_query_string();
$can_comment = (aaaart_comment_check_perm('create')) ? true : false;
$can_edit = (aaaart_comment_check_perm('update_thread', $thread)) ? true : false;
$ref_link = aaaart_comment_get_reference_link($thread);
// remove from user activity
aaaart_user_pull_activity('comment/thread.php?id='.(string)$thread['_id']);

?>
<?php if ($ref_link): ?>
from: <?php print $ref_link; ?>
<?php endif; ?>

<?php if ($can_comment): ?>
<form id="comment-form">
	<?php if (empty($thread)): ?>
  <input type="hidden" name="action" value="create_thread">
  <div class="form-group">
    <label>New thread</label>
    <input type="text" name="title" class="form-control" placeholder="Give a title">
    <p class="help-block">What is the new discussion thread about?</p>
  </div>
  <?php else: ?>
  <input type="hidden" name="action" value="create">
  <input type="hidden" name="thread_id" value="<?php print $thread['_id']; ?>">
  <?php endif; ?>
  <div class="form-group">
    <textarea data-provide="markdown" data-width="400" name="message" class="form-control" rows="4"></textarea>
    <span class="help-block">Write a message here.</span>
    <button type="submit" class="btn btn-success">Submit</button>
  </div>
  <?php if (!empty($_GET['ref_type']) && !empty($_GET['ref_id'])): ?>
  <input type="hidden" name="ref_type" value="<?php print $_GET['ref_type']; ?>">
  <input type="hidden" name="ref_id" value="<?php print $_GET['ref_id']; ?>">
  <?php endif; ?>
</form>
<?php endif; ?>

<?php if (!empty($thread['posts'])): ?>
<?php $posts = aaaart_comment_get_ordered_posts($thread); ?>
<table class="comments-list table table-striped">
	<?php foreach ($posts as $post) { ?>
	<tr>
		<td>
			<h5><?php printf('%s on %s', $post['display_user'], $post['display_date']); ?></h5>
			<p><?php print $post['text']; ?></p>
		</td>
	</tr>
	<?php } ?>
</table>
<?php endif; ?>


<script type="text/javascript">
  $("#comments .modal-header h4").text("<?php print (empty($thread)) ? 'Comment' : $thread['title']; ?>");
<?php if ($can_comment): ?>  
	$("#comment-form textarea").markdown({autofocus:false, savable:false, additionalButtons: aaaart_markdown_buttons() });
  $("#comment-form").on('submit', function(event) {
  	var $form = $(this);
  	$.ajax({
      type: "POST",
      url: base_url + "comment/index.php",
      data: $form.serialize(), 
      success: function(result){
        window.location.href = base_url + "comment/discussions.php";   
      },
      error: function(){
        alert("Sorry, that didn't work!");
      }
	  });
  	return false;
  });
<?php endif; ?>  
</script>