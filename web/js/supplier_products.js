
/****************************************
 * Supplier Products
 ****************************************/
var SupplierProductView = Backbone.View.extend({
	
	tagName: "tr",
	className: "supplier_product",
	
	template: _.template(	'<td class="ps_name" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit"><%= name %></td>'+
							'<td class="ps_price"><%= price %></td>'+
							'<td><% print(units[unit]); %></td>'+
							'<td><%= primary_supplier %>'+
								'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
							'</td>'),
	
	events: {
		'click': 'edit'
	},
	
	initialize: function() {
		this.model.view = this;
	},
	
	render: function(){
		var pr = products.get(this.model.escape('product'));
		this.$el.append('<td>'+ this.model.escape('supplier_name') +'<br>('+ this.model.escape('product') + '-' + pr.get('name') +')</td>');
		this.$el.append('<td>'+ this.model.escape('price') +'</td>');
		this.$el.append('<td>'+ this.model.escape('unit') +'</td>');
		this.$el.append('<td>'+ (this.model.escape('primary_supplier')?"Да":"Нет") + '</td>');
		return this;
	},
	
	edit: function() {
		console.log(this.model.get('id'));
	}
	
})

// view list supplier products
var ViewSupplierProducts = Backbone.View.extend({
	
	tagName: "tbody",
	className: "supplier_products",
	
	initialize: function() {
		_.bindAll(this);
		this.collection.on('reset', this.renderAll);
		this.collection.on('render', this.renderAll);
	},
	
	render: function() {
		return this;
	},
	
	renderAll: function() {
		
		this.collection.each(function(model){
			var view = new SupplierProductView({model:model});
			var content = view.render().el;
			this.$('.supplier_products').append(content);
		})
		
	}

});

// Model supplier products
var SupplierProductsModel = Backbone.Model.extend({ 

});

// get supplier products
var SupplierProducts = Backbone.Collection.extend({
  model: SupplierProductsModel,
  url: '/supplier/products/json'
});

supplier_products = new SupplierProducts; // init collection

var view_supplier_products = new ViewSupplierProducts({collection: supplier_products}); // init view

$('#supplier_product_list').append(view_supplier_products.render().el); // add main template

supplier_products.fetch();

$(document).ready(function(){
	
	// show add form
	$('#add_supplier_product').toggle(function() {
		$('i', this).attr('class', 'icon-minus-sign');
		$("#form_add_supplier_product .alert").remove();
		$('.name_add_sp').val('');
		$('#form_add_supplier_product').slideDown();
		$('.name_add_sp').focus();
		return false;
	}, function() {
		$('i', this).attr('class', 'icon-plus-sign');
		$('#form_add_supplier_product').slideUp();
		return false;
	});
	
})
