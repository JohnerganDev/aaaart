$(function () {

	$('#site-invite-form button#invite').click(function(){
    $.ajax({
      type: "POST",
      url: base_url + "user/index.php",
      data: $('#site-invite-form > form').serialize(), 
      success: function(msg){
          alert('An invite has been sent - you might want to let the person know it is coming');
          $("#site-invite-form").modal('hide');
      },
      error: function(){
          alert("Sorry, that didn't work!");
      }
    });
  });


  $('#login-form > form').submit(function(){
    $.ajax({
      type: "POST",
      url: base_url + "user/index.php",
      data: $('#login-form > form').serialize(), 
      dataType: 'json',
      success: function(data){
        if (data.result) {
          window.location.reload(); 
        } else {
          console.log(data);
        }
      },
      error: function(){
        alert("Sorry, that didn't work!");
      }
    });
    return false;
  });

  $('#modal-login-form .modal-footer button.login').click(function() {
    $.ajax({
      type: "POST",
      url: base_url + "user/index.php",
      data: $('#modal-login-form > form').serialize(), 
      dataType: 'json',
      success: function(data){
        if (data.result) {
          window.location.reload(); 
        }
      },
      error: function(){
        $("#modal-login-form").modal('hide');
        alert("Sorry, that didn't work!");
      }
    });
    return false;
  });

	$("#user-logout").click(function (e) {
	    $.get(base_url + "user/index.php", { action: 'logout' });
	});

});