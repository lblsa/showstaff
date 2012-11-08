//* update 2012-10-18 16:15:00 *//
var companies, view_users, users;
var sort = 'asc';
$(function(){
	// view list users
	var ViewUsers = Backbone.View.extend({
		
		tagName: "tbody",
		className: "users",
		
		initialize: function() {
			_.bindAll(this);
			this.collection.on('reset', this.renderAll);
		},
		
		render: function() {
			return this;
		},
		
		renderAll: function() {
			if (this.collection.length > 0) {			
				$('.users').html('');
				this.collection.each(function(model){
					var view = new ViewUser({model:model});
					var content = view.render().el;
					if (sort == 'desc')
						this.$('.users').prepend(content);
					else
						this.$('.users').append(content);
				});
				
			} else {
				$('.users').html('<tr class="alert_row"><td colspan="6"><div class="alert">'+
									'<button type="button" class="close" data-dismiss="alert">×</button>'+
									'У вас еще нет пользователей</div></td></tr>');
				$('#preloader').fadeOut('fast');
			}
		}
	});

	// view list user
	var ViewUser = Backbone.View.extend({
		
		tagName: "tr",
		className: "user",
		
		template: _.template(	'<td class="u_fullname">'+
									'<input type="text" class="input-large fullname" name="fullname" value="<%= fullname %>">'+
								'</td>'+
								'<td class="u_username">'+
									'<input type="number" class="input-large username" name="username" value="<%= username %>">'+
								'</td>'+
								'<td class="u_email">'+
									'<input type="email" class="input-large email" name="email" value="<%= email %>"> '+
								'</td>'+
								'<td class="u_password">'+
									'<input type="password" class="input-large password" disabled placeholder="Password" name="password" value="">'+
									'<span class="help-block"><a href="#" class="changepass"><small>Редактирование пароля</small></a></span>'+
								'</td>'+
								'<td class="u_company">'+
									'<select class="company" name="company">'+
										'<option value="0"></option>'+
									'</select>'+
								'</td>'+
								'<td class="del">'+
									'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
								'</td>'), // <% print((company>0)?companies._byId[company].get("name"):""); %>
								
		events: {
			'change .fullname':	'save',
			'change .username':	'save',
			'change .email':	'save',
			'change .password': 'save',
			'change .company': 'save',
			'click .remove': 	'remove',
			'click .changepass':'changepass',
			//'click .show_pass':'showPass', 'click .hide_pass':'hidePass',	'keypress .password':'showHide',
		},
		
		render: function() {
			var content = this.template(this.model.toJSON());
			this.$el.html(content);
			var user_row = this.$el;
			companies.each(function(company){
				$('.company', user_row).append('<option value="'+company.id+'">'+company.get("name")+'</option>');
			});
			
			if (this.model.get("company") > 0)
				$('.company option[value="'+this.model.get("company")+'"]', user_row).attr('selected', 'selected');

			$('#preloader').fadeOut('fast'); 
			return this;
		},
		
		/*showHide: function() {
			$('.u_password .help-block', this.$el).html('<a href="#" class="show_pass"><small>Показать пароль</small></a>');
		},
		
		showPass: function(){
			var pass = $('.password', this.$el).val();
			$('.password', this.$el).remove();
			$('.u_password .help-block', this.$el).prepend('<input type="text" class="input-large password" disabled placeholder="Password" name="password" value="'+pass+'">');
			$('.u_password .help-block', this.$el).html('<a href="#" class="hide_pass"><small>Скрыть пароль</small></a>');
		},
		
		hidePass: function(){
			var pass = $('.password', this.$el).val();
			$('.password', this.$el).remove();
			$('.u_password .help-block', this.$el).prepend('<input type="password" class="input-large password" disabled placeholder="Password" name="password" value="'+pass+'">');
			$('.u_password .help-block', this.$el).html('<a href="#" class="show_pass"><small>Показать пароль</small></a>');
		},*/
		
		changepass: function() {
			
			if ($('.password', this.$el).attr('disabled')) {
				$('.password', this.$el).removeAttr('disabled');
			} else {
				$('.password', this.$el).attr('disabled', 'disabled');
			}
			
			return false;
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

		
		save: function() {
			if ($('.password', this.el).val().length > 0 && $('.password', this.el).val().length < 6)
			{
				$('.u_password', this.$el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Пароль должен содержать более 5 символов.</div>');
			} else {
				this.preloader();
				this.model.save({	fullname:	$('.fullname', this.el).val(), 
									username:	$('.username', this.el).val(), 
									email	:	$('.email', this.el).val(), 
									password:	$('.password', this.el).val(), 
									company:	$('.company', this.el).val(), 
								},{wait: true});
			}
		},
		
		remove: function() {
			if ( confirm ("Будте осторожны, будут также удалены все связанные элементы.\r\nВы действительно хотите удалить элемент?") ) {
				this.preloader();
				this.model.destroy({wait: true });
			}
			return false;
		}
	})

	// Model user
	var UserModel = Backbone.Model.extend({


	  sync: function(method, model, options) {
			var userOptions = options;
			
			userOptions.url = '/api/user/'+this.attributes.id;
			
			if (method == 'create') {
				
				userOptions.url = '/api/user';
				
				userOptions.success = function(resp, status, xhr) {
				   
					   model.set(resp.data, {silent:true});
					   var view = new ViewUser({model:model});
					   var content = view.render().el;
					   $('.users').prepend(content);
					   $('.username_add').val('');
					   $('.fullname_add').val('');
					   $('.email_add').val('');
					   $('.pass_add').val('');
					   $('.company_add option:first').attr('selected', 'selected');
					   $(".alert-success").clone().appendTo('.forms');
					   $(".forms .alert-success strong").html('Пользователь добавлен');
					   $(".forms .alert-success").fadeIn()

					   //  for sort reload
					   view_users.remove()
					   view_users = new ViewUsers({collection: users});
					   $('#user_list').append(view_users.render().el);
					   view_users.renderAll();
				};
				
				userOptions.error = function(jqXHR, textStatus, errorThrown) {
					$('#preloader').fadeOut('fast');
					if (typeof(jqXHR) != 'undefined' && typeof(jqXHR.responseText) != 'undefined')
						$('#up .alert-error strong').html('('+jqXHR.responseText+'). ');
					else
						$('#up .alert-error strong').html('(Некорректный ответ сервера). ');
						
					$("#up .alert-error").width($('.forms').width()-49);
					$("#up .alert-error").height($('.forms').height()-14);
					var p = $('.forms').position();
					$('#up .alert-error').css({'left':p.left, 'top': p.top-10});
					$('#up .alert-error').fadeIn();
					users.remove(model, {silent:true});
				}
			}
			
			if (method == 'delete') {
				userOptions.success = function(resp, status, xhr) {
					$('#preloader').fadeOut('fast');
					if (resp.data == model.id) {
						$(model.view.el).remove();
						model.collection.remove(model, {silent: true});
					
					} else {
						
						$('#preloader').fadeOut('fast');
						if (typeof(jqXHR) != 'undefined' && typeof(jqXHR.responseText) != 'undefined')
							$('#up .alert-error strong').html('('+jqXHR.responseText+'). ');
						else
							$('#up .alert-error strong').html('(Некорректный ответ сервера). ');
							
						$("#up .alert-error").width($('.forms').width()-49);
						$("#up .alert-error").height($('.forms').height()-14);
						var p = $('.forms').position();
						$('#up .alert-error').css({'left':p.left, 'top': p.top-10});
						$('#up .alert-error').fadeIn();
					
					}
				};
			}
			
			if (method == 'update') {
				userOptions.success = function(resp, status, xhr) {
					model.set(resp.data,{silent: true});
					model.view.render();			   
				   //  for sort reload
				   
					view_users.remove()
					view_users = new ViewUsers({collection: users});
					$('#user_list').append(view_users.render().el);
					view_users.renderAll();				
				};			
			}
			
			Backbone.sync.call(this, method, model, userOptions);
	   }
	})

	
	// Collection products
	var Users = Backbone.Collection.extend({
	  
		model: UserModel,

		url: '/api/user',

		initialize: function(){
			this.bind('add', this.addUser);
		},
		  
		parse: function(response) {
			if(response && 'code' in response && response.code == 200 && 'data' in response) {
				return response.data;
			} else {
				error_fetch('Ошибка. Обновите страницу или обратитесь к администратору');
			}
		},
			
		addUser: function(user){
			user.save({wait: true});
		},
	  
	});
	
	var ViewCompany = Backbone.View.extend({
		tagName: "option",
		
		className: "company",
		
		template: _.template(	'<%= name %>'),
		
		render: function() {
			var content = this.template(this.model.toJSON());
			this.$el.html(content);
			this.$el.attr('value',this.model.id);
			return this;
		},
	})
	
	var Companies = Backbone.Collection.extend({
		url: '/api/company',
		parse: function(response, xhr){
			if(response.code && (response.code == 200)){
				return response.data;
			} else {
				console.log('bad request');
			}
		}
	})
	
	companies = new Companies;
	
	users = new Users; 	
	companies.fetch({	success:function(collection, response) {
		
									collection.each(function(company){
										var view = new ViewCompany({model:company});
										var content = view.render().el;
										$('.company_add').append(content);
									});
									
									users.fetch({
													error:function(){
														error_fetch('Ошибка на сервере, обновите страницу или обратитесь к администратору');
													}
													
												});
									
									view_users = new ViewUsers({collection: users});
									$('#user_list').append(view_users.render().el);
									users.comparator = function(user) {
									  return user.get("name");
									};
									
								},
						error:function() {
								console.log('error');
							}
					});
	
	$(".forms .alert").remove();
	$('#preloader').width($('#add_row').width());
	$('#preloader').height($('#add_row').height());
	var p = $('#add_row').position();
	$('#preloader').css({'left':p.left, 'top': p.top});
	$('#preloader').fadeIn('fast');
	
	$('.add_user').click(function() {
		$(".forms .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		users.add([{
						fullname: $('.fullname_add').val(),
						username: $('.username_add').val(),
						email: $('.email_add').val(),
						password: $('.pass_add').val(),
						company: $('.company_add').val(),
					}]);
		
		return false;
	});
	
	$(document).keydown(function(e) {
		if (e.keyCode == 27) view_users.renderAll();
	});
})
