//* update 2012-10-18 16:15:00 *//
var href = window.location.pathname.split('/');
var Units, units;
var hide_add;
$(document).ready(function(){
	reloadIfBack();
	
	if (/chrome/.test(navigator.userAgent.toLowerCase())) $('.win-backbutton').css('line-height', '35px');
	
	if (/firefox/.test(navigator.userAgent.toLowerCase())) $('.btn-circle i').css('line-height', '20px');
	
	var OldSync = Backbone.sync; 
	
	Backbone.sync = function(method, model, options) {
		
		if (method == 'update' || method == 'delete') {
			options.error = function(jqXHR, textStatus, errorThrown) {
				$('#preloader').fadeOut('fast');
				if (typeof(jqXHR) != 'undefined' && typeof(jqXHR.responseText) != 'undefined')
					$('#up .alert-error strong').html('('+jqXHR.responseText+'). ');
				else
					$('#up .alert-error strong').html('(Некорректный ответ сервера). ');
					
				$("#up .alert-error").width(model.view.$el.width()-50);
				$("#up .alert-error").height(model.view.$el.height()-14);
				var p = model.view.$el.position();
				$('#up .alert-error').css({'left':p.left, 'top': p.top-10});
				$('#up .alert-error').fadeIn();
				model.view.render();
			}
		}
		
		OldSync.call(this, method, model, options);
		
	}
	
	
	Units = Backbone.Collection.extend({
		url: '/api/units',
		initialize: function(){
			this.fetch({
							error: function(){
								error_fetch('Ошибка получения единиц измерения');
							}
						});
		},
		parse: function(response, xhr){		
			if(response && 'code' in response && response.code == 200 && 'data' in response) {
				return response.data;
			} else {
				error_fetch('Ошибка получения единиц измерения');
			}
		}
	});
	units = new Units;
	
	$('.del').click(function(){
		return confirm ("Вы действительно хотите удалить элемент?");
	});
	
	$('.create').click(function() {
		$(".forms .alert").remove();
		if (!$('.forms').is(":visible")) {
			$(".forms .alert").remove();
			$('.name_add').val('');
			$('.forms').slideDown();
			$('.name_add').focus();
		} else {
			$('.forms').slideUp();
		}

		return false;
	});
	
	$('.datepicker').datepicker({"format": "yyyy-mm-dd"})
		.on('changeDate', function(ev){
			var href = window.location.pathname.split('/');
			if (href[href.length-1] == 'order')
				$('#link_to_date').attr( 'href', window.location.pathname+'/'+$('.datepicker').val() );
			else
				$('#link_to_date').attr( 'href', $('.datepicker').val() );
	});
	
	
	$(document).on("keypress", '.pass_add', function() {
		if ($('.pass_add').val().length > 0)
			$('.controls-pass .help-block a').slideDown();
		else
			$('.controls-pass .help-block a').slideUp();
	});
	
	$(document).on("focusout", '.pass_add', function() {
		if ($('.pass_add').val().length > 0)
			$('.controls-pass .help-block a').slideDown();
		else
			$('.controls-pass .help-block a').slideUp();
	});
	
	$(document).on("click", '.alert .close', function() {
		$(this).closest(".alert").fadeOut();
	});
	
	$('.showpass').toggle(function(){
		var pass = $('.pass_add').val();
		$('.pass_add').remove();
		$('.controls-pass').prepend(	'<input name="pass_add" value="'+pass+
										'" type="text" placeholder="Password" class="pass_add span2">');
		$('small', this).html('Скрыть пароль');
	}, function(){
		var pass = $('.pass_add').val();
		$('.pass_add').remove();
		$('.controls-pass').prepend(	'<input name="pass_add" value="'+pass+
										'" type="password" placeholder="Password" class="pass_add span2">');
		$('small', this).html('Показать пароль');
	});
	
	
	$(".agreed_all .btn").click(function(){
		
		var agreed = $(this).attr('rel');
		
		$.ajax({
		  type: "PUT",
		  url: "/api/company/"+href[2]+"/restaurant/"+href[4]+"/shift/"+$('.wh_datepicker').val()+"/"+agreed,
		  data: function() {
			 return '{ "agreed": '+agreed+' }'
		  },
		  success: function(data) {
			 $('.agreed_all .alert').remove();
			
			$('.workinghours').html();
			
			workinghours.fetch({	success: function(collection, response) {
				
										view_workinghours = new ViewWorkinghours({collection: collection});
										$('#shift_list').append(view_workinghours.render().el);
										view_workinghours.renderAll().el;
										
									}, 
									error: function(){
										error_fetch('Ошибка при получении смен. Обновите страницу или обратитесь к администратору');
									}
						});
			
		  },
		  error: function(data) {
			  $('.agreed_all .alert').remove();
			  
		  	if (data != null && typeof(data.message) != 'undefined')
		  		$('.agreed_all').append('<span class="alert">'+data.message+'</span>');
		  	else
		  		$('.agreed_all').append('<span class="alert">Неизвестная ошибка.</span>');
		  },
		  dataType: "json"
		});
		
		return false;
	});	
	
	$('.add').mouseout(function(){
		hide_add = setTimeout('$(".forms").fadeOut()', 3000);
	});

	$('.add').mouseover(function(){
		clearTimeout(hide_add);
	});

});

function error_fetch(message) {
	$('.span12').append('<div class="alert"><button type="button" class="close" data-dismiss="alert">×</button>'+message+'</div>');
	$('#preloader').fadeOut('fast');
}
// Refrech page after back button
function reloadIfBack() {
	if ($('#refreshed').val()=="no") {
		$('#refreshed').val("yes");
	} else {
		$('#refreshed').val("no");
	}
}
