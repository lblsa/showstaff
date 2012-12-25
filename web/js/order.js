//* update 2012-10-18 16:15:00 *//
var	OrderModel, OrderCollection, ViewOrdersByRestaurant, ViewOrdersBySupplier, 
	ViewOrders, ViewOrdersS, ViewSupplier, ViewRestaurant, products, restaurants, 
	edit_mode = 0, suppliers, orders, view_order_by_rest, Products, Restaurants, 
	Suppliers, completed;

$(function(){

	$('#preloader').width($('#order_list').width());
	$('#preloader').height($('#order_list').height());
	var p = $('#order_list').position();
	$('#preloader').css({'left':p.left, 'top': p.top});
	$('#preloader').fadeIn('fast');

	OrderModel = Backbone.Model.extend({ });

	ViewOrdersByRestaurant = Backbone.View.extend({
   
		tagName: "tbody",
		className: "orders_by_rest",
		
		initialize: function() {
			_.bindAll(this);
			this.collection.on('reset', this.renderAll);
		},
		
		render: function() {
			return this;
		},
		
		renderAll: function() {

			if (restaurants.length > 0) {
				$('#preloader').fadeOut();
				$('.orders_by_rest').html('');
				restaurants.each(function(model){

					var view = new ViewRestaurant({model:model});
					var content = view.render().el;

					$('.orders_by_rest').append(content);
					
					// get orders for restaurant
					var ordersByRestaurant = new Backbone.Collection;
					ordersByRestaurant.reset(_.filter(orders.models,	function(order){ return order.get('restaurant')==model.get('id') }));

					if (ordersByRestaurant.length > 0) {
						$('td', content).append('<table class="table table-bordered"><thead><tr>'+
													'<th>Название Продукта</th>'+
													'<th>Количество</th>'+
													'<th>Цена</th>'+
													'<th>Единицы</th>'+
													'<th>Поставщик</th>'+
												'</tr></thead><tbody></tbody></table>');
						ordersByRestaurant.each(function(order_model) {
							var order_view = new ViewOrders({model:order_model, units: units});
							var order_content = order_view.render().el;
							$('td tbody', content).append(order_content);
						});
						
					} else {
						
						$('td', content).append('<span class="label pull-right">У ресторана нет заказов</span>');
						
					}
				});
			} else {
				
				$('.orders_by_rest').append('У вас нет ресторанов');
				
			}
			return this;
		}
	})

	ViewOrdersBySupplier = Backbone.View.extend({
	   
		tagName: "tbody",
		className: "order_by_supp",
		
		initialize: function() {
			_.bindAll(this);
			this.collection.on('reset', this.renderAll);
		},
		
		render: function() {
			return this;
		},
		
		renderAll: function() {
			if (suppliers.length > 0) {
				$('.order_by_supp').html('');
				suppliers.each(function(model){
					var view = new ViewSupplier({model:model});
					var content = view.render().el;
					
					$('.order_by_supp').append(content);
					
					var ordersBySupplier = new Backbone.Collection;
					ordersBySupplier.reset(_.filter(orders.models,	function(order){ return order.get('supplier')==model.get('id') }));
					
					if (ordersBySupplier.length > 0) {
						$('td', content).append('<table class="table table-bordered"><thead><tr>'+
													'<th>Название Продукта</th>'+
													'<th>Количество</th>'+
													'<th>Цена</th>'+
													'<th>Единицы</th>'+
													'<th>Ресторан</th>'+
												'</tr><thead><tbody></tbody></table>');
						ordersBySupplier.each(function(order_model) {
							var order_view = new ViewOrdersS({model:order_model});
							var order_content = order_view.render().el;
							$('td tbody', content).append(order_content);
						});
					} else {
						
						$('td', content).append('<span class="label pull-right">К поставщику нет заказов</span>');
						$('td', content).addClass('hide');
						
					}
			
				});
			}
			return this;
		}
	});

	ViewOrders = Backbone.View.extend({
		tagName: "tr",
		
		className: "order",
		
		events: {
			'change .change_supplier select':  'change_supplier',
		},

		template_one: _.template(	'<td><%= supplier_name %> (<%= name %>)</td>'+
									'<td><%= amount %></td>'+
									'<td><%= price %></td>'+
									'<td><% print(units._byId[unit].attributes.name); %></td>'+
									'<td class="change_supplier"><% print(suppliers._byId[supplier].attributes.name) %></td>'),
								
		template_two: _.template(	'<td><%= name %></td>'+
									'<td><%= amount %></td>'+
									'<td><%= price %></td>'+
									'<td><% print(units._byId[unit].attributes.name); %></td>'+
									'<td class="change_supplier"><% print(suppliers._byId[supplier].attributes.name) %></td>'),
		
		initialize: function() {
			this.model.view = this;
		},
		
		change_supplier: function (){
			$('#preloader').width($('#order_list').width());
			$('#preloader').height($('#order_list').height());
			var p = $('#order_list').position();
			$('#preloader').css({'left':p.left, 'top': p.top});
			$('#preloader').fadeIn('fast');

			$.ajax({
			  type: "PUT",
			  url: "/api/company/"+href[2]+"/restaurant/"+this.model.get("restaurant")+"/order/"+$('.wh_datepicker').val()+'/'+this.model.id,
			  data: '{ "supplier": '+$('.change_supplier select', this.$el).val()+' }',
			  success: function(data) {
			  	
			  	update($('.wh_datepicker').val());
			  		
			  },
			  error: function(data) {
			  	alert('Ошибка!');
			  	console.log(data);
			  },
			  dataType: "json"
			});
		},

		render: function() {
			
			if (this.model.get('name') == this.model.get('supplier_name') || this.model.get('supplier_name') == '')
				var content = this.template_two(this.model.toJSON());
			else
				var content = this.template_one(this.model.toJSON());

			this.$el.html(content);

			if (products._byId[this.model.get('product')].attributes.available_supplier != 'undefined') {

				$('.change_supplier', this.$el).html('<select class="span4"></select>');

				var available_suppliers = products._byId[this.model.get('product')].attributes.available_supplier;
				var select = $('.change_supplier select', this.$el);
				//console.log(available_suppliers);		console.log('--');
				_.each(available_suppliers, function(available_supplier){
					console.log(available_supplier.supplier_name);
					select.append(	'<option value="'+available_supplier.supplier+'">'+
										available_supplier.supplier_name+
										' ('+available_supplier.supplier_product_name+' '+
										available_supplier.price+'руб)'+
									'</option>');
				});

			}

			$('.change_supplier option[value='+this.model.get("supplier")+']', this.$el).attr('selected', 'selected')
			
			return this;
		}
	});

	ViewOrdersS = Backbone.View.extend({
		tagName: "tr",
		className: "order",
		
		template_one: _.template(	'<td><%= supplier_name %> (<%= name %>)</td>'+
									'<td><%= amount %></td>'+
									'<td><%= price %></td>'+
									'<td><% print(units._byId[unit].attributes.name); %></td>'+
									'<td><% print(restaurants._byId[restaurant].attributes.name); %></td>'),
								
		template_two: _.template(	'<td><%= name %></td>'+
									'<td><%= amount %></td>'+
									'<td><%= price %></td>'+
									'<td><% print(units._byId[unit].attributes.name); %></td>'+
									'<td><% print(restaurants._byId[restaurant].attributes.name); %></td>'),
		
		initialize: function() {
			this.model.view = this;
		},
		
		render: function(){
			if (this.model.get('name') == this.model.get('supplier_name') || this.model.get('supplier_name') == '')
				var content = this.template_two(this.model.toJSON());
			else
				var content = this.template_one(this.model.toJSON());
			this.$el.html(content);
			return this;
		}
	});

	ViewSupplier = Backbone.View.extend({
	   
		tagName: "tr",
		className: "supplier",
		
		template: _.template(	'<td><h4 class="pull-left"><%= name %></h4></td>'),
		
		initialize: function() {
			this.model.view = this;
		},
		
		render: function(){
			var content = this.template(this.model.toJSON());
			this.$el.html(content);
			return this;
		}
	})

	ViewRestaurant = Backbone.View.extend({
	   
		tagName: "tr",
		className: "restaurant",
		
		template: _.template(	'<td>'+
									'<h4 class="pull-left"> <%= name %> <span class="edit_order">( '+
										'<a href="">править заказ ресторана</a>'+
									' )</span></h4>'+
								'</td>'),
		
		initialize: function() {
			this.model.view = this;
		},
		
		render: function(){
			var content = this.template(this.model.toJSON());
			this.$el.html(content);

			$('.edit_order a', this.$el).attr('href', '/company/'+href[2]+'/restaurant/'+this.model.id+'/order/'+$('.wh_datepicker').val());

			if (!edit_mode) $('.edit_order', this.$el).remove();

			return this;
		}
	})

	Products = Backbone.Collection.extend({
		url: '/api/company/'+parseInt(href[2])+'/product',
		parse: function(response, xhr){
			if(response.code && (response.code == 200)){
				return response.data;
			} else {
				error_fetch('bad product request');
			}
		}
	})
	Suppliers = Backbone.Collection.extend({
		url: '/api/company/'+parseInt(href[2])+'/supplier',
		parse: function(response, xhr){
			if(response.code && (response.code == 200)){
				return response.data;
			} else {
				error_fetch('bad supplier request');
			}
		}
	})
	Restaurants = Backbone.Collection.extend({
		url: '/api/company/'+parseInt(href[2])+'/restaurant',
		parse: function(response, xhr){
			if(response.code && (response.code == 200)){
				return response.data;
			} else {
				error_fetch('bad rest request');
			}
		}
	})

	OrderCollection = Backbone.Collection.extend({
	  
	  model: OrderModel,
	  url: '/api/company/'+href[2]+'/order/'+$('.wh_datepicker').val(),
	  parse: function(response, xhr){
		if(response.code && (response.code == 200)){

			if ('completed' in response) completed = response.completed;
			if ('edit_mode' in response) edit_mode = response.edit_mode;

			if ('completed_mode' in response && response.completed_mode == 1) {
				$('.completed').fadeIn();

				if (completed){
					$('.order_close_msg').show();
					$('.order_open_msg').hide();
					$('.completed_yes').addClass('disabled');
					$('.completed_no').removeClass('disabled');
				} else {
					$('.order_close_msg').hide();
					$('.order_open_msg').show();
					$('.completed_no').addClass('disabled');
					$('.completed_yes').removeClass('disabled');	
				}
			} else {
				$('.completed').fadeOut();
			}

			return response.data;
		} else {
			error_fetch('bad order request');
		}
	  }  
	});

	$(document).on('click', "#group_by_supp:not(.disabled)", function(){
		$('.order_by_supp').remove();
		$('.orders_by_rest').remove();
		var view_order_by_supp = new ViewOrdersBySupplier({collection: orders});
		$('#order_list').append(view_order_by_supp.render().el);
		$('#order_list').append(view_order_by_supp.renderAll().el);
		
		$('#group_by_supp').addClass('disabled');
		$('#group_by_rest').removeClass('disabled');
		return false;
	})

	$(document).on('click', "#group_by_rest:not(.disabled)", function(){
		$('.order_by_supp').remove();
		$('.orders_by_rest').remove();
		var view_order_by_rest = new ViewOrdersByRestaurant({collection: orders});
		$('#order_list').append(view_order_by_rest.render().el);
		$('#order_list').append(view_order_by_rest.renderAll().el);
		
		$('#group_by_rest').addClass('disabled');
		$('#group_by_supp').removeClass('disabled');
		return false;
	})
	
	$('.completed_yes').click(function(){
		$.ajax({
		  type: "PUT",
		  url: "/api/company/"+href[2]+"/order/"+$('.wh_datepicker').val(),
		  data: '{ "completed": 1 }',
		  success: function(data) {
		  	$('.completed .alert').remove();
		  	if (data != null && typeof(data.message) != 'undefined')
		  		$('.completed').append('<span class="alert">'+data.message+'</span>');
		  		
		  	$('.completed_yes').addClass('disabled');
		  	$('.completed_no').removeClass('disabled');
		  },
		  error: function(data) {
		  	$('.completed .alert').remove();
		  	
		  	if (data != null && typeof(data.message) != 'undefined')
		  		$('.completed').append('<span class="alert">'+data.message+'</span>');
		  	else
		  		$('.completed').append('<span class="alert">Неизвестная ошибка.</span>');
		  },
		  dataType: "json"
		});
		
		return false;
	});
	
	$('.completed_no').click(function(){
		$.ajax({
		  type: "PUT",
		  url: "/api/company/"+href[2]+"/order/"+$('.wh_datepicker').val(),
		  data: '{ "completed": 0 }',
		  success: function(data) {
		  	$('.completed .alert').remove();
		  	if (data != null && typeof(data.message) != 'undefined')
		  		$('.completed').append('<span class="alert">'+data.message+'</span>');
		  		
		  	$('.completed_no').addClass('disabled');
		  	$('.completed_yes').removeClass('disabled');
		  },
		  error: function(data) {
		  	$('.completed .alert').remove();
		  	
		  	if (data != null && typeof(data.message) != 'undefined')
		  		$('.completed').append('<span class="alert">'+data.message+'</span>');
		  	else
		  		$('.completed').append('<span class="alert">Неизвестная ошибка.</span>');
		  },
		  dataType: "json"
		});
		
		return false;
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

	products = new Products;
	restaurants = new Restaurants;
	suppliers = new Suppliers;

	units.fetch({	
		success: function(){

			restaurants.fetch({	
				success:function(collection, response){

					products.fetch({
						success:function(collection, response){

							suppliers.fetch({
								success:function(collection, response){

									orders = new OrderCollection({}, {units: units, products: products}); // init collection


									orders.fetch({	success:function(collection, response){

														view_order_by_rest = new ViewOrdersByRestaurant({collection: collection}); // initialize view
														$('#order_list').append(view_order_by_rest.render().el); // add tbody
														view_order_by_rest.renderAll().el; // add inner

													}, error: function(){
															error_fetch('Ошибка при получении поставщиков');
													}
											});

								}, error:function(){
									error_fetch('Ошибка при получении поставщиков');
								}
							});

						}, error:function(){
							error_fetch('Ошибка при получении продуктов');
						}
					});

				}, error:function(){
					error_fetch('Ошибка при получении ресторанов');
				}
			});

		}
	});
	
	if (!edit_mode) $('.restaurant .edit_order').remove();
})

function update(strDate){
		strDate = strDate;
		$('.curent-date-header').html(strDate);
		$('.wh_datepicker').val(strDate);

		$('.download_excel').attr('href', '/company/'+href[2]+'/order/export/'+strDate);
		
		$( "#smena_datapicker" ).html('');
		$( "#smena_datapicker" ).removeClass('hasDatepicker');

		$( "#smena_datapicker" ).datepicker({
			onSelect: function(strDate, inst){	update(strDate); },
			showOtherMonths: true,
			selectOtherMonths: true,
		});
		$( "#smena_datapicker" ).datepicker( "setDate", strDate );
		
		orders.url = '/api/company/'+href[2]+'/order/'+strDate;

		document.title = $('.curent-page-title').text();
		window.history.pushState({}, $('.curent-page-title').text(), '/company/'+href[2]+'/order/'+strDate);
		
		var today = new Date();
		var dd = today.getDate()<10?'0'+today.getDate():today.getDate();
		var mm = today.getMonth()+1; //January is 0!
		var yyyy = today.getFullYear();

		/*if ( yyyy+'-'+mm+'-'+dd < strDate)
			$('.agreed_all').fadeIn();
		else
			$('.agreed_all').fadeOut();*/

		$('#preloader').width($('#order_list').width());
		$('#preloader').height($('#order_list').height());
		var p = $('#order_list').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');

		orders.fetch({	success: function(collection, response) {
											
											$('#group_by_rest').addClass('disabled');
											$('#group_by_supp').removeClass('disabled');

											view_order_by_rest.remove();
											$('.orders_by_rest').remove();
											$('.order_by_supp').remove();
											
											view_order_by_rest = new ViewOrdersByRestaurant({collection: collection}); // initialize view
											$('#order_list').append(view_order_by_rest.render().el); // add template
											view_order_by_rest.renderAll().el;
											/*view_workinghours.remove();
											$('.workinghours').remove();
											view_workinghours = new ViewWorkinghours({collection: collection});
											$('#shift_list').append(view_workinghours.render().el);
											view_workinghours.renderAll().el; */
										}, 
										error: function(){
											error_fetch('Ошибка при получении консолидированного заказа. Обновите страницу или обратитесь к администратору');
										}
							});
}