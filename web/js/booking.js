//* update 2012-10-25 08:33:00 *//
/****************************************
 * Booking Product
 ****************************************/
var sort = 'asc';
var products, bookings, view_content, ViewBooking, ViewBookings, edit_mode = 0;
$(function(){

	ViewBooking = Backbone.View.extend({
		tagName: "tr",
		className: "product",
	   
		template: _.template(	'<td class="ps_name"></td>'+
								'<td class="ps_amount">'+
									'<input type="text" class="input-small amount" name="amount" value="<%= amount %>">'+
								'</td>'+
								'<td class="ps_unit">'+
									'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a></td>'),

		template_no_edit: _.template(	'<td class="ps_name"></td>'+
										'<td class="ps_amount"><%= amount %></td>'+
										'<td class="ps_unit"></td>'),

		events: {
			'click .remove'	: 'remove',
			'change input.amount':  'save',
			'change select.product_edit':  'save',
		},
		
		initialize: function() {
			this.model.view = this;
		},
		
		render: function(){
			if (edit_mode) {
				var content = this.template(this.model.toJSON());
			} else {
				var content = this.template_no_edit(this.model.toJSON());
			}
			this.$el.html(content);
			$('#preloader').fadeOut('fast');
			
			if (edit_mode) {
				$('.ps_name', this.el).html('<select class="product_edit span3"></select>');
				
				var select = $('.product_edit', this.el);
				
				if (	typeof(products._byId[this.model.get('product')]) != 'undefined' &&
						typeof(products._byId[this.model.get('product')].attributes) != 'undefined' )
					products._byId[this.model.get('product')].attributes.use = 0;

				products.each(function(p){
					if (p.attributes.use == 0) {
						var view = new OptionProducts({model:p});
						$(select).append(view.render().el);
					}
				});
				
				$('.product_edit option[value="'+this.model.get('product')+'"]', this.el).attr('selected', 'selected');
				
				if (	typeof(products._byId[this.model.get('product')]) != 'undefined' &&
						typeof(products._byId[this.model.get('product')].attributes.use) != 'undefined'	)
					products._byId[this.model.get('product')].attributes.use = 1;
					
			} else {
				$('.ps_name', this.el).html(this.model.get('name')+' ['+units._byId[products._byId[this.model.get('product')].attributes.unit].get('name')+']');
			}

			return this;
		},
		
		preloader: function() {
			$('#preloader').width(this.$el.width());
			$('#preloader').height(this.$el.height());
			var p = this.$el.position();
			$('#preloader').css({'left':p.left, 'top': p.top});
			$('#preloader').fadeIn('fast');
		},
		
		remove: function() {
			if (edit_mode) {
				if ( confirm ("Вы действительно хотите удалить продукт из заказа?") ) {
					this.preloader();
					this.model.destroy({wait: true });
				}
				return false;
			}
		},
		
		save: function() {
			if (edit_mode) {
				this.preloader();
				this.model.save({
								product: $('.product_edit', this.el).val(),
								name: products._byId[$('.product_edit').val()].attributes.name,
								amount: $('.amount', this.el).val(),
								},{wait: true});
			}
		},
	})


	ViewBookings = Backbone.View.extend({
	   
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
			
				$('.bookings').html('<tr class="alert_row"><td colspan="3"><div class="alert">'+
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
	var BookingModel = Backbone.Model.extend({
	  
	  sync: function(method, model, options) {
		   var BookingOptions = options;
		   
		   //console.log(BookingOptions.url);
		   
			if (method == 'delete') {
				BookingOptions.success = function(resp, status, xhr) {
					$('#bookin_list .alert').remove();
					$('#preloader').fadeOut('fast');
					
					if (resp.data == model.id) {
						
						products._byId[model.attributes.product].attributes.use = 0;
						$('.product_add').html('');
						products.each(function(p){
							if (p.attributes.use == 0) {
								var view = new OptionProducts({model:p});
								$('.product_add').append(view.render().el);
							}
						});
						
						if ($('.product_add option').length > 0)
							$('#add_row').fadeIn();
						
						$(model.view.el).remove();
						model.collection.remove(model, {silent: true});

						view_content.renderAll().el;

					} else {
						
						$('.ps_unit', model.view.el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Ошибка удаления! Попробуйте еще раз или обратитесь к администратору.</div>');
					}
				};
			}

			if (method == 'update') {
				BookingOptions.success = function(resp, status, xhr) {
									
					$('#bookin_list .alert').remove();
					$('#preloader').fadeOut('fast');
						
					if (resp.data.product != model.attributes.product ) {
						
						products._byId[model.attributes.product].attributes.use = 0;
						products._byId[resp.data.product].attributes.use = 1;
						$('.product_add').html('');
						products.each(function(p){
							if (p.attributes.use == 0) {
								var view = new OptionProducts({model:p});
								$('.product_add').append(view.render().el);
							}
						});
					}
					
					model.set(resp.data,{silent: true});
					model.view.render();

					bookings.sort({silent: true});
					view_content.remove()
					view_content = new ViewBookings({collection: bookings});
					$('#bookin_list').append(view_content.render().el);
					view_content.renderAll().el
				};
			}

			if (method == 'create') {
				BookingOptions.success = function(resp, status, xhr) {
					
					$('#bookin_list .alert').remove();
					$('#preloader').fadeOut('fast');
					
					if (resp != null && typeof(resp.data) != 'undefined') {
						
						model.set(resp.data, {silent:true});
						
						products._byId[resp.data.product].attributes.use = 1;
						$('.product_add option[value="'+resp.data.product+'"]').remove();
						
						if ($('.product_add option').length == 0)
							$('#add_row').fadeOut();
						
						$('.bookings .alert_row').remove();
						
						var view = new ViewBooking({model:model});
						var content = view.render().el;
						$('#bookin_list .bookings').prepend(content);
						$("#up .alert-success").clone().appendTo('#bookin_list .forms');
						$('#bookin_list .forms .alert-success').css('float','none');
						$('.controls').css('float','none');
						
						$('#bookin_list .forms .alert-success').fadeIn();
						
						$('#bookin_list .amount_add').val('');

						bookings.sort({silent: true});
						view_content.remove()
						view_content = new ViewBookings({collection: bookings});
						$('#bookin_list').append(view_content.render().el);
						view_content.renderAll().el;
					   
					} else {
						
						
						if (resp != null && typeof(resp.message) != 'undefined')
							$('#up .alert-error strong').html(''+resp.message);
							
						$("#up .alert-error").clone().appendTo('.forms');
						$('#bookin_list .alert-error').fadeIn();
						bookings.remove(model, {silent:true});

					}
				};
				BookingOptions.error = function(jqXHR, textStatus, errorThrown) {
					$('#preloader').fadeOut('fast'); 
					if (typeof(jqXHR) != 'undefined' && typeof(jqXHR.responseText) != 'undefined')
					   $('.alert-error strong').html(' (' + jqXHR.responseText + '). ');
					else   
					   $('.alert-error strong').html(' (Некорректный ответ сервера). ');
					$(".alert-error").clone().appendTo('.forms');
					$('.forms .alert-error').fadeIn();
					bookings.remove(model, {silent:true});
				}
			}
			
			
			Backbone.sync.call(this, method, model, BookingOptions);
	  },
	});


	/**********************************************
	 * Option Product for add/edit Supplier Product
	 **********************************************/
	var OptionProducts = Backbone.View.extend({
		
		tagName: "option",
		
		template: _.template('<%= name %> [ <% print(units._byId[unit].get("name")); %> ]'),
		
		render: function() {
			var content = this.template(this.model.toJSON());
			this.$el.html(content);
			this.$el.attr('value', this.model.id)
			return this;
		},
		
	})

	var Products = Backbone.Collection.extend({
		url: '/api/company/'+parseInt(href[2])+'/product',
		parse: function(response, xhr){
			if(response.code && (response.code == 200)) {

				// remove product without supplier				
				var result = [];
				_.each(response.data, function(product_data){
					if (product_data.supplier_product != 0)
						result.push(product_data);
				});
				
				return result;
			} else {
				console.log('bad request');
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
				return '/api/company/'+href[2]+'/restaurant/'+href[4]+'/order/'+$('.wh_datepicker').val();
		},
	  
		parse: function(response){
			if(response && 'code' in response && response.code == 200 && 'data' in response) {

				if('edit_mode' in response) edit_mode = response.edit_mode;

				if (edit_mode)
					$('#add_row').fadeIn();
				else
					$('#add_row').fadeOut();

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
		
	products = new Products;

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

													products.each(function(p){ p.attributes.use = 0; });

													collection.each(function(b){
														if (typeof(products._byId[b.attributes.product]) != 'undefined')
															products._byId[b.attributes.product].attributes.use = 1;
													})

													
													var show_add_row = 0;
													products.each(function(p){ 
														if (p.attributes.use == 0 )
															show_add_row = 1					
													});

													if (show_add_row == 0)
														$('#add_row').fadeOut();

													
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

	if (!edit_mode) $('#bookin_list .remove').remove();


    $('.create').click(function(){
		$('.product_add').html('');
        products.each(function(p){
			if (p.attributes.use == 0) {
				var view = new OptionProducts({model:p});
				$('.product_add').append(view.render().el);
			}
        });
        
        if ($('.product_add option').length == 0)
        	$('#add_row').fadeOut();
    })
    	
    products.each(function(p){
		if (p.attributes.use == 0) {
			var view = new OptionProducts({model:p});
			$('.product_add').append(view.render().el);
		}
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

function update(strDate){

		$('.forms').slideUp();

		$('.curent-date-header').html(strDate);
		$('.wh_datepicker').val(strDate);
		
		$( "#smena_datapicker" ).html('');
		$( "#smena_datapicker" ).removeClass('hasDatepicker');

		$( "#smena_datapicker" ).datepicker({
			onSelect: function(strDate, inst){	update(strDate); },
			showOtherMonths: true,
			selectOtherMonths: true,
		});
		$( "#smena_datapicker" ).datepicker( "setDate", strDate );
		
		bookings.url = '/api/company/'+href[2]+'/restaurant/'+href[4]+'/order/'+strDate;

		document.title = $('.curent-page-title').text();
		window.history.pushState({}, $('.curent-page-title').text(), '/company/'+href[2]+'/restaurant/'+href[4]+'/order/'+strDate);
		
		var today = new Date();
		var dd = today.getDate()<10?'0'+today.getDate():today.getDate();
		var mm = today.getMonth()+1; //January is 0!
		var yyyy = today.getFullYear();

		if ( yyyy+'-'+mm+'-'+dd < strDate)
			$('.agreed_all').fadeIn();
		else
			$('.agreed_all').fadeOut();

		$('#preloader').width($('#bookin_list').width());
		$('#preloader').height($('#bookin_list').height());
		var p = $('#bookin_list').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');

		bookings.fetch({	success: function(collection, response) {

								products.each(function(p){ p.attributes.use = 0; });

								collection.each(function(b){
									if (typeof(products._byId[b.attributes.product]) != 'undefined')
										products._byId[b.attributes.product].attributes.use = 1;
								})

								view_content.remove();
								$('.bookings').remove();
								view_content = new ViewBookings({collection: collection});
								$('#bookin_list').append(view_content.render().el);
								view_content.renderAll().el;

								var show_add_row = 0;
								products.each(function(p){ 
									if (p.attributes.use == 0 )
										show_add_row = 1					
								});

								if (show_add_row == 0)
									$('#add_row').fadeOut();

							}, 
							error: function(){
								error_fetch('Ошибка при получении продуктов. Обновите страницу или обратитесь к администратору');
							}
						});
}