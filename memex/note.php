<?php

require_once('../config.php');

if (empty($_GET['id'])) {
  print 'Sorry there has been a problem!'; exit;
} else {
  $id = $_GET['id'];
}
$memex = aaaart_memex_item_lookup($id);
$item = aaaart_memex_get_item($memex, $id);
if (empty($item)) {
  print 'Sorry there has been a problem!'; exit;
}
?>

<form id="memex-form">
  <fieldset>
    <input type="hidden" name="memex_id" value="<?php print (string)$memex['_id']; ?>">
    <input type="hidden" name="item_id" value="<?php print $id; ?>">
    <input type="hidden" name="action" value="save_note">
    
    <label>Note for "<?php print $item['title']; ?>".</label>
    <textarea data-provide="markdown" data-width="400" name="note" rows="5"><?php print (!empty($item['note'])) ? $item['note'] : ''; ?></textarea>
    <button type="submit" class="btn btn-success">Save</button>
  </fieldset>
</form>

<script type="text/javascript">
  $("#memex-modal .modal-header h3").text("Annotation");
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