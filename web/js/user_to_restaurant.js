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
							'<td class="u_restaurant"></td>'+
							'<td class="u_save"><button class="save btn btn-mini btn-success">save</button></td>'),
							
	events: {
		'click .save': 'save',
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
		
		var u_restaurant = $('.u_restaurant', this.$el);
		u_restaurant.html('');
		restaurants.each(function(r){
			var view = new RestaurantCheckboxView({model:r});
			u_restaurant.append(view.render().el);
			if (_.contains(user_model.attributes.restaurants, r.id)) {
				$('input', view.$el).attr('checked','checked');
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
		$('.u_username input', this.el).val();
		
		$('.u_email', this.el).html('<input type="email" class="input-large email" name="name" value="">');
		$('.u_email input', this.el).val();
		
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
		
		var restaurants = [];
		$(".restaurant_upd[name='restaurants[]']:checked", this.el).each(function() {
			restaurants.push(parseInt($(this).val()));	
		});
		
		console.log(restaurants);
		
		this.model.set({restaurants: restaurants},{wait: true});
		this.model.save(this.model,{wait: true});
	},

})

// Model user
var UserModel = Backbone.Model.extend({


  sync: function(method, model, options) {
        var userOptions = options;
		
        if (method == 'update') {
			userOptions.success = function(resp, status, xhr) {
				if (resp != null && typeof(resp.message) != 'undefined') {
				   $('#preloader').fadeOut('fast'); 
				   $('.u_save .alert', model.view.el).remove();
				   $('.u_save', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка (' + resp.message + '). '+
													'Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				} else {
				   if (resp != null && typeof(resp.data) != 'undefined') {
					   model.set(resp.data,{silent: true});
					   $('#preloader').fadeOut('fast');
					   $('.u_save', model.view.el).append('<div class="alert alert-success">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Успешно сохранено.</div>');
					   return;
				   } else {
					   $('.u_save .alert', model.view.el).remove();
					   $('#preloader').fadeOut('fast'); 
					   $('.u_save', model.view.el).append('<div class="alert">'+
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
			
			userOptions.url = 'user_to_restaurant/'+this.attributes.id;
			
		}
		
		Backbone.sync.call(this, method, model, userOptions);
   }
})


/**********************************************
 * Restaurant for add/edit Company Manager
 **********************************************/
var RestaurantCheckboxView = Backbone.View.extend({
	
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
