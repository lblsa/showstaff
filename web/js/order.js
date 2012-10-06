var OrderModel = Backbone.Model.extend({
})

var ViewOrdersByRestaurant = Backbone.View.extend({
   
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
											'</tr><thead><tbody></tbody></table>');
					ordersByRestaurant.each(function(order_model) {
						var order_view = new ViewOrders({model:order_model});
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


var ViewOrdersBySupplier = Backbone.View.extend({
   
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
					
				}
		
			});
		}
		return this;
	}
})


var ViewOrders = Backbone.View.extend({
	tagName: "tr",
	className: "order",
	
	template: _.template(	'<td><%= supplier_name %> (<%= name %>)</td>'+
							'<td><%= amount %></td>'+
							'<td><%= price %></td>'+
							'<td><% print(units[unit]); %></td>'+
							'<td><% print(suppliers._byId[supplier].attributes.name) %></td>'),
	
	initialize: function() {
		this.model.view = this;
	},
	
	render: function(){
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		return this;
	}
});

var ViewOrdersS = Backbone.View.extend({
	tagName: "tr",
	className: "order",
	
	template: _.template(	'<td><%= supplier_name %> (<%= name %>)</td>'+
							'<td><%= amount %></td>'+
							'<td><%= price %></td>'+
							'<td><% print(units[unit]); %></td>'+
							'<td><% print(restaurants._byId[restaurant].attributes.name); %></td>'),
	
	initialize: function() {
		this.model.view = this;
	},
	
	render: function(){
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		return this;
	}
});

var ViewSupplier = Backbone.View.extend({
   
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

var ViewRestaurant = Backbone.View.extend({
   
	tagName: "tr",
	className: "restaurant",
	
	template: _.template(	'<td>'+
								'<h4 class="pull-left"> <%= name %> <span class="edit_order">( <a href="/company/<%= company %>/restaurant/<%= id %>/order">править заказ ресторана</a> )</span></h4>'+
							'</td>'),
	
	initialize: function() {
		this.model.view = this;
	},
	
	render: function(){
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		if (edit_mode) {
			var href = window.location.pathname.split('/');
			if (href[href.length-1] != 'order') {
				var link = $('.edit_order a').attr('href')+'/'+href[href.length-1];
				$('.edit_order a').attr( 'href', link);
			}
		} else {
			$('.edit_order', this.$el).remove();
		}
		return this;
	}
})

$(document).ready(function(){
	$('#group_by_supp').click(function(){
		$('.orders_by_rest').remove();
		var view_order_by_supp = new ViewOrdersBySupplier({collection: orders});
		$('#order_list').append(view_order_by_supp.render().el);
		$('#order_list').append(view_order_by_supp.renderAll().el);
		
		$('#order_list h3').html('Поставщики');
		
		$(this).addClass('disabled');
		$('#group_by_rest').removeClass('disabled');
		return false;
	})

	$('#group_by_rest').click(function(){
		$('.order_by_supp').remove();
		var view_order_by_rest = new ViewOrdersByRestaurant({collection: orders});
		$('#order_list').append(view_order_by_rest.render().el);
		$('#order_list').append(view_order_by_rest.renderAll().el);
		
		$('#order_list h3').html('Рестораны');
		
		$(this).addClass('disabled');
		$('#group_by_supp').removeClass('disabled');
		return false;
	})
	
	$('.completed_yes').click(function(){
		$.ajax({
		  type: "PUT",
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
	})
	
	$('.completed_no').click(function(){
		$.ajax({
		  type: "PUT",
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
	})
})
