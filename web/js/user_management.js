
var sort = 'asc';
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
				/*if (sort == 'desc')
					this.$('.users').prepend(content);
				else*/
				this.$('.users').append(content);
			});
			
		} else {
			$('.users').html('<tr class="alert_row"><td colspan="6"><div class="alert">'+
								'<button type="button" class="close" data-dismiss="alert">×</button>'+
								'У вас еще нет пользователей</div></td></tr>');
			$('#preloader').fadeOut('fast');
		}
		return this;
	}
});

// view list user
var ViewUser = Backbone.View.extend({
	
	tagName: "tr",
	className: "user",
	
	template: _.template(	'<td class="u_fullname"><input type="text" class="input-large fullname span2" name="fullname" value="<%= fullname %>"></td>'+
							'<td class="u_username"><input type="number" class="input-large username span2" name="username" value="<%= username %>"></td>'+
							'<td class="u_email"><input type="email" class="input-large email span2" name="email" value="<%= email %>"></td>'+
							'<td class="u_pass"><input type="password" class="input-large password span2" name="password" value=""></td>'+
							'<td class="u_role"></td>'+
							'<td class="u_restaurant"></td>'+
							'<td class="u_controls">'+
								'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
							'</td>'),
							
	events: {
		'change input':  'save',
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
		var u_role = $('.u_role', this.$el);
		var u_restaurant = $('.u_restaurant', this.$el);
		
		var user_model = this.model;
		
		u_role.html('');
		roles.each(function(r){
			var view = new RoleView({model:r});
			u_role.append(view.render().el);
			if (_.contains(user_model.attributes.roles, r.id)) {
				$('input', view.$el).attr('checked','checked');
			}
		})
		
		u_restaurant.html('');
		restaurants.each(function(r){
			var view = new RestaurantView({model:r});
			u_restaurant.append(view.render().el);
			if (_.contains(user_model.attributes.restaurants, r.id)) {
				$('input', view.$el).attr('checked','checked');
			}
		})
		
		
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
		
		this.model.save({	fullname: $('.fullname', this.el).val(), 
							username: $('.username', this.el).val(), 
							email: $('.email', this.el).val(), 
							password: $('.password', this.el).val(), 
							roles: roles,
							restaurants: restaurants,
						},{wait: true});
	},
	
	cancel: function() {
		return this.render().el;
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
        
		if (method == 'create') {
			userOptions.success = function(resp, status, xhr) {
				if (resp != null && typeof(resp.message) != 'undefined' ) {

				   $('#preloader').fadeOut('fast'); 
				   $('.alert-error strong').html(' (' + resp.message + '). ');
				   $(".alert-error").clone().appendTo('.forms');
				   $('.forms .alert-error').fadeIn();
				   users.remove(model, {silent:true});
				   return;
				   
				} else {
					
				   if (resp != null && typeof(resp.data) != 'undefined') {
				   
					   model.set(resp.data, {silent:true});
					   var view = new ViewUser({model:model});
					   var content = view.render().el;
					   $('.users').prepend(content);
					   $('.user').tooltip();
					   $('.username_add').val('');
					   $('.fullname_add').val('');
					   $('.email_add').val('');
					   $('.pass_add').val('');
						$(".forms input[name='roles[]']:checked").each(function() {
							$(this).removeAttr('checked');
						});
						
						$(".forms input[name='restaurants[]']:checked").each(function() {
							$(this).removeAttr('checked');
						});
					   
					   $(".alert-success").clone().appendTo('.forms');
					   $(".forms .alert-success strong").html('Пользователь добавлен');
					   $(".forms .alert-success").fadeIn()

					   //  for sort reload
					   view_users.remove()
					   view_users = new ViewUsers({collection: users});
					   $('#user_list').append(view_users.render().el);
					   view_users.renderAll();
					   return;
				   } else {
					   
					   $('#preloader').fadeOut('fast'); 
					   $('.alert-error strong').html(' (Некорректный ответ сервера). ');
					   $(".alert-error").clone().appendTo('.forms');
					   $('.forms .alert-error').fadeIn();
					   users.remove(model, {silent:true});   
					   return;
				   }
				   
				}
				return options.success(resp, status, xhr);
			};
			userOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
		}
		
        if (method == 'delete') {
			userOptions.success = function(resp, status, xhr) {
				$('#preloader').fadeOut('fast');
				if (resp != null && typeof(resp.data) != 'undefined' && resp.data == model.id) {
					$(model.view.el).remove();
					model.collection.remove(model, {silent: true});
				   
					return;
				} else {
					
				   $('.u_role', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка удаления! Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				}
				return options.success(resp, status, xhr);
			};
			
			userOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
			
			userOptions.url = 'user/'+this.attributes.id;
		}
		
        if (method == 'update') {
			userOptions.success = function(resp, status, xhr) {
				if (resp != null && typeof(resp.message) != 'undefined') {
					
					model.set(model.previousAttributes(), {silent: true});
					model.view.render();
					$('#preloader').fadeOut('fast'); 
					$('.u_role .alert', model.view.el).remove();
					$('.u_role', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка (' + resp.message + '). '+
													'Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				} else {
				   if (resp != null && typeof(resp.data) != 'undefined') {
					   model.set(resp.data,{silent: true});
					   model.view.render();
					   return;
				   } else {
					   model.set(model.previousAttributes(), {silent: true});
					   model.view.render();
					   $('.u_role .alert', model.view.el).remove();
					   $('#preloader').fadeOut('fast'); 
					   $('.u_role', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка. Попробуйте еще раз или обратитесь к администратору.</div>');
					   return;
				   }
				}
				return options.success(resp, status, xhr);
			};
			
			userOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
			
			userOptions.url = 'user/'+this.attributes.id;
			
		}
		
		Backbone.sync.call(this, method, model, userOptions);
   }
})

/**********************************************
 * Companies for add/edit Company Administrator
 **********************************************/
var OptionCompaies = Backbone.View.extend({
	
	tagName: "option",
	
	template: _.template('<%= name %>'),
	
	render: function() {
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		this.$el.attr('value', this.model.id)
		return this;
	},
	
})

/**********************************************
 * Role for add/edit Company Manager
 **********************************************/
var RoleView = Backbone.View.extend({
	
	tagName: "label",
	className: "checkbox",
	
	template: _.template('<%= name %>'),
	
	render: function() {
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		this.$el.prepend('<input type="checkbox" class="role_upd" name="roles[]" value="'+this.model.id+'">');
		return this;
	},
	
})


/****************************************
 * Collection roles
 ***************************************/
var Roles = Backbone.Collection.extend({

	url: '/role',

	initialize: function(){},
  
	parse: function(response) {
		if(response.code && response.data  && (response.code == 200)){
			return response.data;
		} else {
			console.log('error role request');
		}
	},
});

// Collection users
var Users = Backbone.Collection.extend({
  
	model: UserModel,
  
	url: '/company/'+href[2]+'/user',
	
	parse: function(response) {
		if(response.code && (response.code == 200)){
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

/****************************************
 * Collection restaurants
 ***************************************/
var Restaurants = Backbone.Collection.extend({

	url: '/company/'+href[2]+'/restaurant',

	initialize: function(){},
  
	parse: function(response) {
		if(response.code && response.data  && (response.code == 200)){
			return response.data;
		} else {
			error_fetch('Ошибка при получении ресторанов');
		}
	},
});
var RestaurantView = Backbone.View.extend({
	
	tagName: "label",
	className: "checkbox",
	
	template: _.template('<%= name %>'),
	
	render: function() {
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		this.$el.prepend('<input type="checkbox" class="restaurant_upd" name="restaurants[]" value="'+this.model.id+'">');
		return this;
	},
})

var users, roles, restaurants, view_users;

$(function() {
	users = new Users; 
	roles = new Roles;
	restaurants = new Restaurants;
	
	users.comparator = function(user) {
	  return user.get("fullname");
	};
	
	$(".forms .alert").remove();
	$('#preloader').width($('#add_row').width());
	$('#preloader').height($('#add_row').height());
	var p = $('#add_row').position();
	$('#preloader').css({'left':p.left, 'top': p.top});
	$('#preloader').fadeIn('fast');
		
	restaurants.fetch({
						success: function(collection, response){
							
							roles.fetch({
											success: function(collection, response){
												
													users.fetch({	success: function(collection, response){
																		view_users = new ViewUsers({collection: collection});
																		$('#user_list').append(view_users.render().el);
																		view_users.renderAll().el;
																	}, 
																	error: function(){
																		error_fetch('Ошибка при получении пользователей. Обновите страницу или обратитесь к администратору');
																	}

																});
											},
											error: function(){
												error_fetch('Ошибка при получении ролей пользователей. Обновите страницу или обратитесь к администратору');
											}
										});
						},
						error: function(){
							error_fetch('Ошибка при получении ресторанов. Обновите страницу или обратитесь к администратору');
						}
					
					});
	
	$('#add_company_admin').click(function() {
			restaurants.each(function(restaurant){
				$('.restaurants_add').append('<label class="checkbox">'+
												'<input type="checkbox" class="restaurant_add" name="restaurants[]" value="'+restaurant.id+'">'+
												restaurant.get("name")+
											'</label><br>');
			});
	});
	
	$('.add_user').click(function() {
		$(".forms .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		
		var roles = [];
		$(".forms input[name='roles[]']:checked").each(function() {
			roles.push(parseInt($(this).val()));
		});
		
		var restaurants = [];
		$(".forms input[name='restaurants[]']:checked").each(function() {
			restaurants.push(parseInt($(this).val()));
		});
		
		users.add([{
						fullname: $('.fullname_add').val(),
						username: $('.username_add').val(),
						email: $('.email_add').val(),
						password: $('.pass_add').val(),
						roles: roles,
						restaurants: restaurants
						}]);
		
		return false;
	})
})
