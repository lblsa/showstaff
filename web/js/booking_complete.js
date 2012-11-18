/****************************************
 * Booking Product
 ****************************************/
$(function(){
	var sort = 'asc';

	var ViewBooking = Backbone.View.extend({
		tagName: "tr",
		className: "product",
	   
		template: _.template(	'<td class="ps_name"></td>'+
								'<td class="ps_amount"><%= amount %></td>'+
								'<td class="ps_unit"></td>'),

		events: {	},
		
		initialize: function() {
			this.model.view = this;
		},
		
		render: function(){
			
			var content = this.template(this.model.toJSON());
			this.$el.html(content);
			$('#preloader').fadeOut('fast');
			
			$('.ps_name', this.el).html(this.model.get('name')+' ['+units._byId[products._byId[this.model.get('product')].attributes.unit].get('name')+']');

			return this;
		},
		
		preloader: function() {
			$('#preloader').width(this.$el.width());
			$('#preloader').height(this.$el.height());
			var p = this.$el.position();
			$('#preloader').css({'left':p.left, 'top': p.top});
			$('#preloader').fadeIn('fast');
		},		
	});


	var ViewBookings = Backbone.View.extend({
	   
		tagName: "tbody",
		className: "bookings",
		
		initialize: function() {
			_.bindAll(this);
			this.collection.on('reset', this.renderAll);
		},
		
		render: function() {
			return this;
		},
		
		renderAll: function() {
			$('#preloader').hide();
			this.renderProducts();
			return this;
		},
		
		renderProducts: function() {
			if (this.collection.length > 0) {
				
				$('.bookings').html('');
				this.collection.each(function(model){
					var view = new ViewBooking({model:model});
					var content = view.render().el;
					
					if (sort == 'desc')
						$('.bookings').prepend(content);
					else
						$('.bookings').append(content);
				});
			
			} else {
			
				$('.bookings').append('<tr class="alert_row"><td colspan="3"><div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'У данного ресторана нет заказов на текущую дату</div></td></tr>');
			}
			
			return this;
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
			
			list.reset(bookings.models, {silent:true})
			this.collection = list;
			
			this.renderProducts();
			return false;
		},
		
		sort_by_amount: function() {
			var list = new Backbone.Collection;
			list.comparator = function(chapter) {
			  return chapter.get("amount");
			};
			
			if (sort == 'asc') {
				sort = 'desc';
				$('.sort_by_amount i').attr('class','icon-arrow-down');
			} else {
				sort = 'asc';
				$('.sort_by_amount i').attr('class','icon-arrow-up');
			}
			
			list.reset(bookings.models, {silent:true})
			this.collection = list;
			
			this.renderProducts();
			return false;
		},
		
	})

	// Model booking products
	var BookingModel = Backbone.Model.extend({	});

	var Products = Backbone.Collection.extend({
		url: '/api/company/'+parseInt(href[2])+'/product',
		parse: function(response, xhr){
			if(response.code && (response.code == 200)) {
						
				var result = [];
				_.each(response.data, function(product_data){
					if (product_data.supplier_product != 0)
						result.push(product_data);
				});
				
				return result;
			} else {
				error_fetch('Ошибка парсинга. Обновите страницу или обратитесь к администратору');
			}
		}
	});

	// Collection bookings
	var ContentBooking = Backbone.Collection.extend({
	  
		model: BookingModel,
	  
		url: function(){
			if (typeof(href[6])!='undefined')
				return '/api/company/'+href[2]+'/restaurant/'+href[4]+'/order/'+href[6];
			else
				return '/api/company/'+href[2]+'/restaurant/'+href[4]+'/order/'+$('.datepicker').val();
		},
	  
		parse: function(response){
			if(response && 'code' in response && response.code == 200 && 'data' in response) {
				return response.data;
			} else {
				error_fetch('Ошибка. Обновите страницу или обратитесь к администратору');
			}
		},
	  
		initialize: function(){
			this.bind('add', this.addBooking);
		},
	  
		addBooking: function(product){
			product.save({wait: true});
		},
	  
	});
		
	var products = new Products;
		
	var edit_mode = true;
	var bookings, view_content;
	
	bookings = new ContentBooking({}, {units:units}); // init collection

	view_content = new ViewBookings({collection: bookings}); // initialize view


	bookings.comparator = function(booking) {
	  return booking.get("name");
	};

	$('#bookin_list').append(view_content.render().el); // add template
	

	$('#preloader').width($('#bookin_list').width());
	$('#preloader').height($('#bookin_list').height());
	var p = $('#bookin_list').position();
	
	$('#preloader').css({'left':p.left, 'top': p.top});
	$('#preloader').fadeIn('fast');

	
	products.fetch({	success:function(){
							
									bookings.fetch({	success:function(collection, response){
															//console.log('success');
															
														},
														error:function(){
															console.log('error');
														}
													});
							
						}, error:function(){
							$('#preloader').fadeOut('fast');
							
							$('.bookings').html('<td colspan="4"><div class="alert">'+
												'<button type="button" class="close" data-dismiss="alert">×</button>'+
												'Ошибка на сервере, обновите страницу или обратитесь к администратору</div></td>');
							
							console.log('error get products')
						}
					});

	if (!edit_mode) {
		$('#bookin_list .remove').remove();
	}


    $('.create').click(function(){
		$('.product_add').html('');
        products.each(function(p){
			if (p.attributes.use == 0) {
				var view = new OptionProducts({model:p});
				$('.product_add').append(view.render().el);
			}
        });
        
        if ($('.product_add option').length == 0)
        	$('.create, .forms').fadeOut();
    })
    	
    products.each(function(p){
		if (p.attributes.use == 0) {
			var view = new OptionProducts({model:p});
			$('.product_add').append(view.render().el);
		}
    });
    
	$('.add_booking').click(function(){
		
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		
		bookings.add([{
						product: $('.product_add').val(),
						amount: $('.amount_add').val(),
						name: products._byId[$('.product_add').val()].attributes.name,
					}],{wait: true});
		return false;
	});
	
	$('.sort_by_name').click(function(){
		view_content.sort_by_name();
		return false;
	});
	
	$('.sort_by_amount').click(function(){
		view_content.sort_by_amount();
		return false;
	});
	
	$(document).keydown(function(e) {
		if (e.keyCode == 27) view_content.renderAll();
	});
})

