<?php

require_once('../config.php');

if (empty($_GET['collection'])) {
  print 'Sorry there has been a problem!'; exit;
} else {
  $collection_id = $_GET['collection'];
}

$section_id = (empty($_GET['section'])) ? false : $_GET['section'];

$collection = aaaart_collection_get($collection_id);
$can_edit = (aaaart_collection_check_perm('update', $collection)) ? true : false;

if (empty($collection) || !$can_edit) {
  print 'Sorry there has been a problem!'; exit;
}

$section = ($section_id) ? aaaart_collection_get_section($collection, $section_id) : false;
$num_sections = (!empty($collection['sections'])) ? (($section) ? count($collection['sections']) : count($collection['sections'])+1) : 1;

?>

<form id="section-form">
  
    <input type="hidden" name="collection_id" value="<?php print $collection_id; ?>">
    <?php if ($section): ?>
    <input type="hidden" name="action" value="save_section">
    <input type="hidden" name="section_id" value="<?php print $section_id; ?>">

    <?php else: ?>
    <input type="hidden" name="action" value="add_section">

    <?php endif; ?>
    <div class="form-group"> 
      <label><h5>Name</h5></label>
      <input type="text" class="form-control" name="title" value="<?php print (!empty($section['title'])) ? $section['title'] : ''; ?>">
    </div>
    <div class="form-group"> 
      <label><h5>Order (of sections - 1st, 2nd, 3rd, etc)</h5></label>
      <select name="order">
      <?php for ($i=1; $i<=$num_sections; $i++) { printf('<option%s>%s</option>', (!empty($section['order']) && $section['order']==$i) ? ' selected' : '' , $i); } ?>
      </select>
    </div>
    <div class="form-group"> 
      <label><h5>Description</h5></label>
      <textarea data-provide="markdown" rows="4" class="form-control" name="description"><?php print (!empty($section['description'])) ? $section['description'] : ''; ?></textarea>
    </div>
  
</form>

<script type="text/javascript">
  $("#add-section-modal .modal-header h3").text("<?php print (!empty($section['title'])) ? $section['title'] : 'Add a new section'; ?>");
	$("#add-section-modal textarea").markdown({autofocus:false,savable:false});
  $("#add-section-modal #save-section").on('click', function(event) {
  	var $form = $('#section-form');
  	$.ajax({
      type: "POST",
      url: base_url + "collection/index.php",
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