<?php

require_once('../config.php');

$memex = aaaart_memex_load_from_query_string();
$force_fork = (!empty($_GET['fork']) && $_GET['fork']=='true');
$can_edit = aaaart_memex_check_perm('update', $memex);
$is_editing = ($can_edit && !$force_fork) ? true : false;

?>

<form id="memex-form">
  <fieldset>
    <input type="hidden" name="memex_id" value="<?php print (string)$memex['_id']; ?>">
    
    <?php if ($is_editing): ?>
    <input type="hidden" name="action" value="save">
    <?php else: ?>
    <input type="hidden" name="action" value="edit">
    <small class="text-success">You are about to open this trail for editing. Afterwards, you should save any changes you have made. (If you didn't create this trail, don't worry; you will be editing a new copy, not the original one.)</small>
    <?php endif; ?>
    <label>The title for the trail</label>
    <input type="text" name="title" value="<?php print (!empty($memex['title'])) ? $memex['title'] : ''; ?>" required>
    <label>Longer description.</label>
    <textarea data-provide="markdown" data-width="400" name="description" rows="5"><?php print (!empty($memex['description'])) ? $memex['description'] : ''; ?></textarea>
    <?php if ($is_editing): ?>
    <button type="submit" class="btn btn-success">Save</button>
    <?php else: ?>
    <button type="submit" class="btn btn-success">Begin editing</button>
    <?php endif; ?>
  </fieldset>
</form>

<script type="text/javascript">
  $("#memex-modal .modal-header h3").text("<?php print ($is_editing) ? 'Save changes' : 'Open for editing'; ?>");
	$("#memex-form textarea").markdown({autofocus:false,savable:false});
  $("#memex-form").on('submit', function(event) {
  	var $form = $(this);
  	$.ajax({
      type: "POST",
      url: base_url + "memex/index.php",
      data: $form.serialize(), 
      success: function(result){
        window.location.reload();   
      },
      error: function(){
        alert("Sorry, that didn't work!");
      }
	  });
  	return false;
  });
</script>