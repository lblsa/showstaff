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
			$('.users').html('<tr class="alert_row"><td colspan="5"><div class="alert">'+
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
	
	template: _.template(	'<td class="u_fullname" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit">'+
								'<%= fullname %>'+
							'</td>'+
							'<td class="u_username"><%= username %></td>'+
							'<td class="u_email"><%= email %></td>'+
							'<td class="u_role"></td>'+
							'<td class="u_controls">'+
								'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
							'</td>'),
							
	events: {
		'dblclick': 'edit',
		'click .save': 'save',
		'click .cancel': 'cancel',
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
		var user_model = this.model;
		u_role.html('');
		roles.each(function(r){
			var view = new RolesView({model:r});
			u_role.append(view.render().el);
			if (_.contains(user_model.attributes.roles, r.id)) {
				view.$el.addClass('label-success');
			}
		})
		$('.user').tooltip();
		$('#preloader').fadeOut('fast'); 
		return this;
	},
	
	edit: function() {
		$('.u_fullname', this.el).html('<input type="text" class="input-large fullname" name="name" value="">');
		$('.u_fullname input', this.el).val(this.model.get('fullname'));
		
		$('.u_username', this.el).html('<input type="number" class="input-large username" name="name" value="">');
		$('.u_username input', this.el).val(this.model.get('username'));
		
		$('.u_email', this.el).html('<input type="email" class="input-large email" name="name" value="">');
		$('.u_email input', this.el).val(this.model.get('email'));
		
		var user_model = this.model;
		var u_role = $('.u_role', this.el);
		u_role.html('');
		roles.each(function(r){
			var view = new RolesCheckboxView({model:r});
			u_role.append(view.render().el);
			if (_.contains(user_model.attributes.roles, r.id)) {
				$('input', view.$el).attr('checked','checked');
			}
		})
		
		
		$('.u_controls', this.el).html(	'<p class="form-inline">'+
										'<a class="save btn btn-mini btn-success">save</a>'+
										' <a class="cancel btn btn-mini btn-danger">cancel</a></p>');
		
	},
	
	save: function() {
		this.preloader();
		
		var roles = [];
		$(".role_upd[name='roles[]']:checked", this.el).each(function() {
			roles.push(parseInt($(this).val()));	
		});
		
		this.model.save({	fullname: $('.fullname', this.el).val(), 
							username: $('.username', this.el).val(), 
							email: $('.email', this.el).val(), 	
							roles: roles, 	
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
				   $(".alert-error").clone().appendTo('#form_add');
				   $('#form_add .alert-error').fadeIn();
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
					   $(".alert-success").clone().appendTo('#form_add');
					   $("#form_add .alert-success strong").html('Пользователь добавлен');
					   $("#form_add .alert-success").fadeIn()

					   //  for sort reload
					   view_users.remove()
					   view_users = new ViewUsers({collection: users});
					   $('#user_list').append(view_users.render().el);
					   view_users.renderAll();
					   return;
				   } else {
					   
					   $('#preloader').fadeOut('fast'); 
					   $('.alert-error strong').html(' (Некорректный ответ сервера). ');
					   $(".alert-error").clone().appendTo('#form_add');
					   $('#form_add .alert-error').fadeIn();
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
					   users.sort({silent: true});
					   
					   view_users.remove()
					   view_users = new ViewUsers({collection: users});
					   $('#user_list').append(view_users.render().el);
					   view_users.renderAll()
					   
					   return;
				   } else {
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
var RolesCheckboxView = Backbone.View.extend({
	
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
var RolesView = Backbone.View.extend({
	
	tagName: "span",
	className: "label",
	
	template: _.template('<%= name %>'),
	
	render: function() {
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		return this;
	},
	
})

$(document).ready(function(){
	$('.add_user').click(function() {
		$("#form_add .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		
		var roles = [];
		$("input[name='roles[]']:checked").each(function() {	roles.push(parseInt($(this).val()));	});
		
		users.add([{
						fullname: $('.fullname_add').val(),
						username: $('.username_add').val(),
						email: $('.email_add').val(),
						roles: roles
						}]);
		
		return false;
	})
})
