/****************************************
 * Restaurants
 ****************************************/

var sort = 'asc';

// view list Restaurants
var ViewRestaurants = Backbone.View.extend({
	
	tagName: "tbody",
	className: "restaurants",
	
	initialize: function() {
		_.bindAll(this);
		this.collection.on('reset', this.renderAll);
	},
	
	render: function() {
		return this;
	},
	
	renderAll: function() {

		if (this.collection.length > 0) {
			$('.restaurants').html('');
			this.collection.each(function(model){
				var view = new ViewRestaurant({model:model});
				var content = view.render().el;
				if (sort == 'desc')
					this.$('.restaurants').prepend(content);
				else
					this.$('.restaurants').append(content);
			});
			
		} else {
			$('.restaurants').html('<tr class="alert_row"><td colspan="3"><div class="alert">'+
								'<button type="button" class="close" data-dismiss="alert">×</button>'+
								'У вас еще нет рестаранов</div></td></tr>');
			$('#preloader').fadeOut('fast');
		}
	},
	
	

	sort_by_name: function() {
		var list = new Backbone.Collection;
		list.comparator = function(chapter) {
		  return chapter.get("name");
		};
		
		if (sort == 'asc') {
			sort = 'desc';
			$('.sort_by_name i').attr('class','icon-arrow-down');
		} else {
			sort = 'asc';
			$('.sort_by_name i').attr('class','icon-arrow-up');
		}
		
		list.reset(restaurants.models, {silent:true})
		this.collection = list;
		
		this.renderAll();
		return false;
	},
	
	sort_by_address: function() {
		var list = new Backbone.Collection;
		list.comparator = function(chapter) {
		  return chapter.get("address");
		};

		if (sort == 'asc') {
			sort = 'desc';
			$('.sort_by_address i').attr('class','icon-arrow-down');
		} else {
			sort = 'asc';
			$('.sort_by_address i').attr('class','icon-arrow-up');
		}
		
		list.reset(restaurants.models, {silent:true})
		this.collection = list;	
		
		this.renderAll();
		return false;
	},
	
	sort_by_director: function() {
		var list = new Backbone.Collection;
		list.comparator = function(chapter) {
		  return chapter.get("director");
		};
		
		if (sort == 'asc') {
			sort = 'desc';
			$('.sort_by_director i').attr('class','icon-arrow-down');
		} else {
			sort = 'asc';
			$('.sort_by_director i').attr('class','icon-arrow-up');
		}
		
		list.reset(restaurants.models, {silent:true})
		this.collection = list;	
		
		this.renderAll();
		return false;
	}
});

// view one restaurant
var ViewRestaurant = Backbone.View.extend({
	
	tagName: "tr",
	className: "restaurant",
	
	template: _.template(	'<td class="p_name" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit">'+
								'<a href="/company/<% print(restaurants.company_id); %>/restaurant/<%= id %>" class="link">#<%= id%></a>'+ 
								'<%= name %> '+
							'</td>'+
							'<td class="p_address" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit">'+
								'<%= address %> '+
							'</td>'+
							'<td class="p_director" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit">'+
								'<%= director %>'+
								'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a><br>'+
								'<a href="/company/<% print(restaurants.company_id); %>/restaurant/<%= id %>/order" class="link">Заказ продуктов</a>'+
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
	
	render: function(){
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		$('.restaurant').tooltip();
		$('#preloader').fadeOut('fast'); 
		return this;
	},
	
	edit: function() {
		$('.p_name', this.el).html('<input type="text" class="input-small name" name="name" value="">');
		$('.p_name input', this.el).val(this.model.get('name'));
		
		$('.p_address', this.el).html('<input type="text" class="input-small address" name="name" value="">');
		$('.p_address input', this.el).val(this.model.get('address'));
		
		$('.p_director', this.el).html('<input type="text" class="input-small director" name="name" value="">');
		$('.p_director input', this.el).val(this.model.get('director'));
		
		
		$('.p_director', this.el).append('<p class="form-inline"> <a class="save btn btn-mini btn-success">save</a>'+
									' <a class="cancel btn btn-mini btn-danger">cancel</a></p>');
	},
	
	save: function() {
		this.preloader();
		this.model.save({	
							name: $('.name', this.el).val(),
							address: $('.address', this.el).val(),
							director: $('.director', this.el).val() 	
						},{wait: true});
	},
	
	cancel: function() {
		return this.render().el;
	},

	remove: function() {
		if ( confirm ("Будте осторожны, будут также удалены все связанные продукты.\r\nВы действительно хотите удалить элемент?") ) {
			this.preloader();
			this.model.destroy({wait: true });
		}
		return false;
	},
	
})


// Model restaurant
var RestaurantModel = Backbone.Model.extend({

  sync: function(method, model, options) {
        var restaurantOptions = options;
        
        if (method == 'delete') {
			restaurantOptions.success = function(resp, status, xhr) {
				$('#preloader').fadeOut('fast');
				if (resp != null && typeof(resp.data) != 'undefined' && resp.data == model.id) {
					$(model.view.el).remove();
					model.collection.remove(model, {silent: true});
				   
					return;
				} else {
					
				   $('.p_director', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка удаления! Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				}
				return options.success(resp, status, xhr);
			};
			
			restaurantOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
			
			restaurantOptions.url = 'restaurant/'+this.attributes.id;
		}
        
        if (method == 'update') {
			restaurantOptions.success = function(resp, status, xhr) {
				if (resp != null && typeof(resp.message) != 'undefined') {
				   $('#preloader').fadeOut('fast'); 
				   $('.p_director .alert', model.view.el).remove();
				   $('.p_director', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка (' + resp.message + '). '+
													'Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				} else {
				   if (resp != null && typeof(resp.data) != 'undefined') {
					   model.set(resp.data,{silent: true});
					   model.view.render();
					   
					   restaurants.sort({silent: true});
					   
					   view_restaurants.remove()
					   view_restaurants = new ViewRestaurants({collection: restaurants});
					   $('#restaurants_list').append(view_restaurants.render().el);
					   view_restaurants.renderAll()
					   
					   return;
				   } else {
					   $('.p_director .alert', model.view.el).remove();
					   $('#preloader').fadeOut('fast'); 
					   $('.p_director', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка. Попробуйте еще раз или обратитесь к администратору.</div>');
					   return;
				   }
				}
				return options.success(resp, status, xhr);
			};
			
			restaurantOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
			
			restaurantOptions.url = 'restaurant/'+this.attributes.id;
			
		}
		
		if (method == 'create') {
			restaurantOptions.success = function(resp, status, xhr) {
				if (resp != null && typeof(resp.message) != 'undefined' ) {

				   $('#preloader').fadeOut('fast'); 
				   $('.alert-error strong').html(' (' + resp.message + '). ');
				   $(".alert-error").clone().appendTo('#form_add');
				   $('#form_add .alert-error').fadeIn();
				   restaurants.remove(model, {silent:true});
				   return;
				   
				} else {
					
				   if (resp != null && typeof(resp.data) != 'undefined') {
				   
					   model.set(resp.data, {silent:true});
					   
					   var view = new ViewRestaurant({model:model});
					   var content = view.render().el;
					   
					   $('.restaurants').prepend(content);
					   $('.restaurant').tooltip();  
					   $('.name_add').val('');
					   $(".alert-success").clone().appendTo('#form_add');
					   $("#form_add .alert-success").fadeIn()

					   //  for sort reload
					   view_restaurants.remove()
					   view_restaurants = new ViewRestaurants({collection: restaurants});
					   $('#restaurants_list').append(view_restaurants.render().el);
					   view_restaurants.renderAll()
					   return;
				   } else {
					   
					   $('#preloader').fadeOut('fast'); 
					   $('.alert-error strong').html(' (Некорректный ответ сервера). ');
					   $(".alert-error").clone().appendTo('#form_add');
					   $('#form_add .alert-error').fadeIn();
					   restaurants.remove(model, {silent:true});   
					   return;
				   }
				   
				}
				return options.success(resp, status, xhr);
			};
			restaurantOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
		}
		
		Backbone.sync.call(this, method, model, restaurantOptions);
   }
});




/****************************************
 * 
 ***************************************/
 
$(document).ready(function(){
	
	$('.add_restaurant').click(function() {
		$("#form_add .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		
		restaurants.add([{
							name: $('.name_add').val(),
							address: $('.address_add').val(),
							director: $('.director_add').val(),
						}]);
		
		return false;
	})

	$('.sort_by_name').click(function(){
		view_restaurants.sort_by_name();
		return false;
	});
	$('.sort_by_address').click(function(){
		view_restaurants.sort_by_address();
		return false;
	});
	$('.sort_by_director').click(function(){
		view_restaurants.sort_by_director();
		return false;
	});
})
