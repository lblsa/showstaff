$(document).ready(function(){
	$('body').click(function(e){
		if (e.ctrlKey){
			$('body').append('<div class="modal" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
								 '<div class="modal-header">'+
									'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'+
									'<h3>Опишите проблему или ошибку</h3>'+
								  '</div>'+
								  '<div class="modal-body">'+
									'<textarea class="span5 feedback_message">Ваше сообщение ...</textarea>'+
								  '</div>'+
								  '<div class="modal-footer">'+
									'<a href="#" class="btn btn-primary" id="send_feedback">Отправить</a>'+
								'</div></div>');
			$('#myModal').modal('show');
		}
	});
	
	
	$('#send_feedback').click(function(){
		alert('send');
		if ($('.feedback_message').val() != '') {
			alert($('.feedback_message').val());
			
			$.ajax({
			  url: "/feedback",
			  type: "PUT",
			  data: '{ "feedback_message": '+$('.feedback_message').val()+'}',
			  success: function(data) {
				if (data != null && typeof(data.message) != 'undefined'){
					$('.modal-body').html('<span class="alert alert-success">Сообщение успешно отправлено</span>');
					$('#myModal').fadeOut('slow', function(){
						$('.modal-body').html('');
					});
				}	
			  },
			  error: function(data) {
				$('.modal-body').append('<span class="alert">Ошибка отправки</span>');
				
				if (data != null && typeof(data.message) != 'undefined')
					$('.modal-body .alert').append(' ('+data.message+')');
			  },
			  dataType: "json"
			});
		}
		
		return false;
	})
})
