
var users, duties, view_workinghours;
$(function(){
	
	var ViewWorkinghours = Backbone.View.extend({
		
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
		
		template: _.template(	'<td class="u_user"><select class="user" name="user"></select></td>'+
								'<td class="u_duty"><select class="duty" name="duty"></select></td>'+
								'<td class="u_plan"><input type="text" class="input-large planhours span2" name="email" value="<%= planhours %>"></td>'+
								'<td class="u_fact"><input type="text" class="input-large facthours span2" name="password" value="<%= facthours %>"></td>'+
								'<td class="u_agreed"><% if(agreed) print("Да"); else print("Нет"); %></td>'+
								'<td class="u_controls">'+
									'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
								'</td>'),
								
		events: {
			'change input':  'save',
			'change select':  'save',
			'click .remove': 'remove',
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
			var content = this.template(this.model.toJSON());
			this.$el.html(content);
			var u_duty = $('.u_duty select', this.$el);
			var u_user = $('.u_user select', this.$el);
			var curent_model = this.model;
		
			u_duty.html('');
			duties.each(function(r){				
				if (curent_model.attributes.duty == r.id)
					$(u_duty).append('<option value="'+r.id+'" selected="selected">'+r.get("name")+'</option>');
				else
					$(u_duty).append('<option value="'+r.id+'">'+r.get("name")+'</option>');
			});
			
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
			
			var roles = [];
			$(".role_upd[name='roles[]']:checked", this.el).each(function() {
				roles.push(parseInt($(this).val()));	
			});
			
			var restaurants = [];
			$(".restaurant_upd[name='restaurants[]']:checked", this.el).each(function() {
				restaurants.push(parseInt($(this).val()));	
			});
			
			this.model.save({	user: $('.user', this.el).val(), 
								duty: $('.duty', this.el).val(), 
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
					
					$('#shift_list .forms .alert-success').fadeIn();
					
					$('#shift_list .planhours_add').val('');
					$('#shift_list .facthours_add').val('');

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
	  
		url: function(){
			if (typeof(href[6])!='undefined')
				return '/api/company/'+href[2]+'/restaurant/'+href[4]+'/shift/'+href[6];
			else
				return '/api/company/'+href[2]+'/restaurant/'+href[4]+'/shift/'+$('.datepicker').val();
		},
		
		parse: function(response) {
			if(response.code && 'code' in response && response.code == 200 && 'data' in response ){
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
	
	var Duties = Backbone.Collection.extend({
		url: '/api/duty',
		parse: function(response, xhr){
			if(response && 'code' in response && response.code == 200 && 'data' in response) {
				return response.data;
			} else {
				error_fetch('Ошибка. Обновите страницу или обратитесь к администратору');
			}
		}
	});
	
	duties = new Duties;
	
	$('#preloader').width($('#shift_list').width());
	$('#preloader').height($('#shift_list').height());
	var p = $('#shift_list').position();
	
	$('#preloader').css({'left':p.left, 'top': p.top});
	$('#preloader').fadeIn('fast');
	
	
	workinghours = new Workinghours;
	
	duties.fetch({	success:function(collection, response){
							collection.each(function(duty){
								$('.duty_add').append('<option value="'+duty.id+'">'+duty.get('name')+'</option>');
							});
													
							users.fetch({	success:function(collection, response){
								
													collection.each(function(user){
														$('.user_add').append('<option value="'+user.id+'">'+user.get('fullname')+' ('+user.get('username')+')</option>');
													});
													
													workinghours.fetch({	success: function(collection, response) {
														
																				view_workinghours = new ViewWorkinghours({collection: collection});
																				$('#shift_list').append(view_workinghours.render().el);
																				view_workinghours.renderAll().el;
																				
																			}, 
																			error: function(){
																				error_fetch('Ошибка при получении пользователей. Обновите страницу или обратитесь к администратору');
																			}
																});
												},
												error:function(){
													error_fetch('Ошибка получения пользователей. Обновите страницу или обратитесь к администратору');
												}
											});
							
						}, error:function(){
							error_fetch('Ошибка получения должностей. Обновите страницу или обратитесь к администратору');
						}
					})
	
	
	$('.add_employee').click(function() {
		$(".forms .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		
		workinghours.add([{
							duty: $('.duty_add').val(),
							user: $('.user_add').val(),
							planhours: $('.planhours_add').val(),
							facthours: $('.facthours_add').val() 	}]);
	});
	
	$('.wh_datepicker').datepicker({"format": "yyyy-mm-dd"})
		.on('changeDate', function(ev){
			var href = window.location.pathname.split('/');
			if (href[href.length-1] == 'shift')
				$('#link_to_date').attr( 'href', window.location.pathname+'/'+$('.wh_datepicker').val() );
			else
				$('#link_to_date').attr( 'href', $('.wh_datepicker').val() );
	});
});
