
/****************************************
 * Supplier Products
 ****************************************/
var SupplierProductView = Backbone.View.extend({
	
	tagName: "tr",
	className: "supplier_product",
	
	events: {
		'click': 'edit'
	},
	
	initialize: function() {
		this.model.view = this;
	},
	
	render: function(){
		this.$el.append('<td>'+ this.model.escape('supplier_name') +'<br>('+ this.model.escape('product') +')</td>');
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
  url: '/supplier/products/list.json'
});

supplier_products = new SupplierProducts; // init collection
var view_supplier_products = new ViewSupplierProducts({collection: supplier_products}); // init view
$('#supplier_product_list').append(view_supplier_products.render().el); // add main template
supplier_products.fetch();
