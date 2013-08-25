$(function () {

	$('#site-invite-form button#invite').click(function(){
    $.ajax({
      type: "POST",
      url: base_url + "user/index.php",
      data: $('#site-invite-form .modal-dialog .modal-content > form').serialize(), 
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

  // Initiates login process
  $('#modal-login-form .modal-footer button.login').click(function() {
    $.ajax({
      type: "POST",
      url: base_url + "user/index.php",
      data: $('#modal-login-form .modal-dialog .modal-content > form').serialize(), 
      dataType: 'json',
      success: function(data){
        if (data.result) {
          window.location.reload(); 
        } else {
          $("#modal-login-form").find('.message').text(data.message);
        }
      },
      error: function(){
        console.log(data);
        $("#modal-login-form").modal('hide');
        alert("Sorry, that didn't work!");
      }
    });
    return false;
  });

  // Initiates reset password process
  $('#modal-login-form .modal-footer button.reset').click(function() {
    $('#modal-login-form .modal-dialog .modal-content > form input[name="action"]').val('reset');
    $.ajax({
      type: "POST",
      url: base_url + "user/index.php",
      data: $('#modal-login-form .modal-dialog .modal-content > form').serialize(), 
      dataType: 'json',
      success: function(data){
        if (data.result) {
          $("#modal-login-form").find('.message').text('Check your email for information about resetting your password');
        } else {
          $("#modal-login-form").find('.message').text(data.message);
        }
      },
      error: function(){
        $("#modal-login-form").modal('hide');
        alert("Sorry, that didn't work!");
      }
    });
    toggleLoginReset();
    return false;
  });  

  // Toggle between login and forgot my password
  function toggleLoginReset() {
    $('#modal-login-form .modal-dialog .modal-content > form input[name="pass"]').toggle();
    $('#modal-login-form .modal-dialog .modal-content > form input[name="pass"]').prev('label').toggle();
    $('#modal-login-form .modal-dialog .modal-content > .modal-footer button.reset').toggle();
    $('#modal-login-form .modal-dialog .modal-content > .modal-footer button.login').toggle();
  }

  // Forgot my password link
  $('#modal-login-form .modal-dialog .modal-content > form a.forgot').click(function() {
    toggleLoginReset();
    return false;
  });

	$("#user-logout").click(function (e) {
	    $.get(base_url + "user/index.php", { action: 'logout' });
	});

});