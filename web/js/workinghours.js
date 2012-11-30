var users, duties, view_workinghours, ViewWorkinghours;
var agreed = 0;
var edit_mode = 0;
$(function(){
	
	ViewWorkinghours = Backbone.View.extend({
		
		tagName: "tbody",
		className: "workinghours",
		
		initialize: function() {
			_.bindAll(this);
			this.collection.on('reset', this.renderAll);
		},
		
		render: function() {
			return this;
		},
		
		renderAll: function() {
			if (this.collection.length > 0) {			
				$('.workinghours').html('');
				this.collection.each(function(model){
					var view = new ViewRow({model:model});
					var content = view.render().el;
					this.$('.workinghours').append(content);
				});
				
			} else {
				$('.workinghours').html('<tr class="alert_row"><td colspan="6"><div class="alert">'+
										'<button type="button" class="close" data-dismiss="alert">×</button>'+
										'На эту смену никто не назначен</div></td></tr>');
				$('#preloader').fadeOut('fast');
			}
			return this;
		}
	});
	
	var ViewRow = Backbone.View.extend({
		
		tagName: "tr",
		className: "user",
		
		template_mode1: _.template(	'<td class="u_user"><select class="user" name="user"></select></td>'+
									'<td class="u_plan"><input type="text" class="input-large planhours span2" name="planhours" value="<%= planhours %>"></td>'+
									'<td class="u_fact"><input type="text" class="input-large facthours span2" name="facthours" value="<%= facthours %>"></td>'+
									'<td class="u_description"><textarea class="description"><%= description %></textarea></td>'+
									'<td class="u_controls">'+
										'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
									'</td>'),

		template_mode2: _.template(	'<td class="u_user"><select class="user" name="user" disabled="disabled"></select></td>'+
										'<td class="u_plan"><%= planhours %></td>'+
										'<td class="u_fact"><input type="text" class="input-large facthours span2" name="facthours" value="<%= facthours %>"></td>'+
										'<td class="u_description"><%= description %></td>'+
										'<td class="u_controls"></td>'),

		template_mode0: _.template(	'<td class="u_user"><select class="user" name="user" disabled="disabled"></select></td>'+
										'<td class="u_plan"><%= planhours %></td>'+
										'<td class="u_fact"><%= facthours %></td>'+
										'<td class="u_description"><%= description %></td>'+
										'<td class="u_controls"></td>'),
								
		events: {
			'change input':  	'save',
			'change select': 	'save',
			'change textarea':  'save',
			'click .remove': 	'remove',
		},
		
		initialize: function() {
			_.bindAll(this);
			this.model.view = this;
		},
		
		preloader: function() {
			$('#preloader').width(this.$el.width());
			$('#preloader').height(this.$el.height());
			var p = this.$el.position();
			$('#preloader').css({'left':p.left, 'top': p.top});
			$('#preloader').fadeIn('fast');
		},
		
		render: function() {
			
			if (edit_mode == 0) var content = this.template_mode0(this.model.toJSON());
			if (edit_mode == 1) var content = this.template_mode1(this.model.toJSON());
			if (edit_mode == 2) var content = this.template_mode2(this.model.toJSON());

			this.$el.html(content);
			var u_user = $('.u_user select', this.$el);
			var curent_model = this.model;
			
			u_user.html('');
			users.each(function(r){				
				if (curent_model.attributes.user == r.id)
					$(u_user).append('<option value="'+r.id+'" selected="selected">'+r.get("fullname")+' ('+r.get("username")+')</option>');
				else
					$(u_user).append('<option value="'+r.id+'">'+r.get("fullname")+' ('+r.get("username")+')</option>');
			});
			
			$('#preloader').fadeOut('fast'); 
			return this;
		},
		
		save: function() {
			this.preloader();
			
			this.model.save({	user: $('.user', this.el).val(), 
								description: $('.description', this.el).val(), 
								planhours: $('.planhours', this.el).val(), 
								facthours: $('.facthours', this.el).val(),
							},{wait: true});
		},
		
		cancel: function() {
			return this.render().el;
		},
		
		remove: function() {
			if ( confirm ("Вы действительно хотите удалить элемент?") ) {
				this.preloader();
				this.model.destroy({wait: true });
			}
			return false;
		}
	})
	
	var WorkinghoursModel = Backbone.Model.extend({
		sync: function(method, model, options) {
			var userOptions = options;
			
			if (method == 'create') {
				userOptions.success = function(resp, status, xhr) {
					
					$('#shift_list .alert').remove();
					$('#preloader').fadeOut('fast');
					
					model.set(resp.data, {silent:true});
					
					$('.workinghours .alert_row').remove();
					
					var view = new ViewRow({model:model});
					var content = view.render().el;
					$('#shift_list .bookings').prepend(content);
					$("#up .alert-success").clone().appendTo('#shift_list .forms');
					$('#shift_list .forms .alert-success strong').html('Сотрудник добавлен');
					$('#shift_list .forms .alert-success').fadeIn();
					
					$('.planhours_add').val('');
					$('.facthours_add').val('');
					$('.description_add').val('');

					$('.workinghours').remove();
					view_content = new ViewWorkinghours({collection: workinghours});
					$('#shift_list').append(view_content.render().el);
					view_content.renderAll().el;

				};
				userOptions.error = function(jqXHR, textStatus, errorThrown) {
					$('#preloader').fadeOut('fast');
					if (typeof(jqXHR) != 'undefined' && typeof(jqXHR.responseText) != 'undefined')
						$('#up .alert-error strong').html(' ('+jqXHR.responseText+'). ');
					else
						$('#up .alert-error strong').html(' (Некорректный ответ сервера). ');
											
					$("#up .alert-error").width($('.forms').width()-49);
					$("#up .alert-error").height($('.forms').height()-14);
					var p = $('.forms').position();
					$('#up .alert-error').css({'left':p.left, 'top': p.top});
					$('#up .alert-error').fadeIn();
					workinghours.remove(model, {silent:true});
				}
			}
			
			if (method == 'delete') {
				userOptions.success = function(resp, status, xhr) {
					$('#preloader').fadeOut('fast');
					if (resp != null && typeof(resp.data) != 'undefined' && resp.data == model.id) {
						$(model.view.el).remove();
						model.collection.remove(model, {silent: true});
					   
					} else {
						$('#up .alert-error strong').html('');
						$("#up .alert-error").width(model.view.$el.width()-50);
						$("#up .alert-error").height(model.view.$el.height()-14);
						var p = model.view.$el.position();
						$('#up .alert-error').css({'left':p.left, 'top': p.top-10});
						$('#up .alert-error').fadeIn();
					}
				};
			}
			
			if (method == 'update') {
				userOptions.success = function(resp, status, xhr) {
					model.set(resp.data, {silent: true});
					model.view.render();
					$('#preloader').fadeOut('fast');
				};
			}
			
			Backbone.sync.call(this, method, model, userOptions);
		}
	});
	
	var Workinghours = Backbone.Collection.extend({
	  
		model: WorkinghoursModel,
	  
		url: '/api/company/'+href[2]+'/restaurant/'+href[4]+'/shift/'+$('.wh_datepicker').val(),
		
		parse: function(response) {
			if(response.code && 'code' in response && response.code == 200 && 'data' in response && 'agreed' in response && 'edit_mode' in response ) {
				agreed = response.agreed;
				edit_mode = response.edit_mode;
				
				if (edit_mode == 1)
					$('#add_row').fadeIn();
				else
					$('#add_row').fadeOut();

				return response.data;
			} else {
				error_fetch('Ошибка при получении пользователей');
			}
		},
		
		initialize: function(){
		  this.bind('add', this.addUser);
		},

		addUser: function(user){
			user.save({wait: true});
		},
	  
	});
	
	var Users = Backbone.Collection.extend({
		url: '/api/company/'+href[2]+'/user',
		parse: function(response, xhr){
			if(response && 'code' in response && response.code == 200 && 'data' in response) {
				return response.data;
			} else {
				error_fetch('Ошибка. Обновите страницу или обратитесь к администратору');
			}
		}
	});
	
	users = new Users;
	
	$('#preloader').width($('#shift_list').width());
	$('#preloader').height($('#shift_list').height());
	var p = $('#shift_list').position();
	
	$('#preloader').css({'left':p.left, 'top': p.top});
	$('#preloader').fadeIn('fast');
	
	
	workinghours = new Workinghours;
	

													
	users.fetch({	success:function(collection, response){
		
							collection.each(function(user){
								$('.user_add').append('<option value="'+user.id+'">'+user.get('fullname')+' ('+user.get('username')+')</option>');
							});
							
							workinghours.fetch({	success: function(collection, response) {
								
														view_workinghours = new ViewWorkinghours({collection: collection});
														$('#shift_list').append(view_workinghours.render().el);
														view_workinghours.renderAll().el;
														
													if ( $('.wh_datepicker').val() < strDate)
														$('.agreed_all').fadeIn();
													else
														$('.agreed_all').fadeOut();
													}, 
													error: function(){
														error_fetch('Ошибка при получении смен. Обновите страницу или обратитесь к администратору');
													}
										})
						},
						error:function(){
							error_fetch('Ошибка получения пользователей. Обновите страницу или обратитесь к администратору');
						}
					});
	

	
	
	$('.add_employee').click(function() {
		$(".forms .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		
		workinghours.add([{	description: $('.description_add').val(),
							user: $('.user_add').val(),
							planhours: $('.planhours_add').val(),
							facthours: $('.facthours_add').val() 	}]);
	});

	$( "#smena_datapicker" ).datepicker({
		onSelect: function(strDate, inst){	update(strDate); },
		showOtherMonths: true,
		selectOtherMonths: true,
	});
	$( "#smena_datapicker" ).datepicker( "setDate", $('.wh_datepicker').val() );
	
	$('#prev_day, #next_day').click(function(){

		var today = $("#smena_datapicker").datepicker("getDate");
		var new_day = today;

		if ($(this).attr('id') == 'next_day')
			new_day.setDate(today.getDate() + 1);
		
		if ($(this).attr('id') == 'prev_day')
			new_day.setDate(today.getDate() - 1);

		$("#smena_datapicker").datepicker( "setDate", new_day );

		var dd = new_day.getDate()<10?'0'+new_day.getDate():new_day.getDate();
		var mm = new_day.getMonth()+1; //January is 0!
		var yyyy = new_day.getFullYear();

		update(yyyy+'-'+mm+'-'+dd);

		return false;
	});

});

function update(strDate){

		$('.curent-date-header').html(strDate);
		$('.wh_datepicker').val(strDate);
		
		workinghours.url = '/api/company/'+href[2]+'/restaurant/'+href[4]+'/shift/'+strDate;

		document.title = $('.curent-page-title').text();
		window.history.pushState({}, $('.curent-page-title').text(), '/company/'+href[2]+'/restaurant/'+href[4]+'/shift/'+strDate);
		
		var today = new Date();
		var dd = today.getDate()<10?'0'+today.getDate():today.getDate();
		var mm = today.getMonth()+1; //January is 0!
		var yyyy = today.getFullYear();

		if ( yyyy+'-'+mm+'-'+dd < strDate)
			$('.agreed_all').fadeIn();
		else
			$('.agreed_all').fadeOut();

		$('#preloader').width($('#shift_list').width());
		$('#preloader').height($('#shift_list').height());
		var p = $('#shift_list').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');

		workinghours.fetch({	success: function(collection, response) {
											view_workinghours.remove();
											$('.workinghours').remove();
											view_workinghours = new ViewWorkinghours({collection: collection});
											$('#shift_list').append(view_workinghours.render().el);
											view_workinghours.renderAll().el;
										}, 
										error: function(){
											error_fetch('Ошибка при получении смен. Обновите страницу или обратитесь к администратору');
										}
							});
}