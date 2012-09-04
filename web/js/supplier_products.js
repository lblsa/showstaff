/****************************************
 * Supplier
 ****************************************/
var SP = {}; 
 
var ViewSupplier = Backbone.View.extend({
	
	tagName: "tr",
	className: "supplier",
	
	template: _.template(	'<td class="s_name" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit"><p>'+
							'<%= name %>'+
							' <a href="#" class="pull-right btn btn-small show visibl">Развернуть <i class="icon-plus-sign"></i></a></p>'+
							'<div class="hide" id="sp_<%= id %>"></div></td>'),
	
	events: {
		'click .show': 'show',
		'click .hd': 'hiden',
	},
	
	initialize: function() {
		this.model.view = this;
	},
		
	render: function(){
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		return this;
	},
	
	show: function() {
		
		var model_id = this.model.id;
		var exist = 0;
		if(typeof(SP[model_id]) == "undefined") {

			var SupplierProducts = Backbone.Collection.extend({
			  model: SupplierProductsModel,
			  url: '/supplier_products/supplier/'+this.model.id,
			});
			
			SP[model_id] = new SupplierProducts;	
			SP[model_id].fetch();
		} else {
			exist = 1;			
		}
		
		//console.log(this.model.id);
		
		view_supplier_products = new ViewSupplierProducts({collection: SP[model_id]});

		console.log(view_supplier_products);
		
		$('#sp_'+model_id, this.$el).append(view_supplier_products.render({id:model_id}).el);
		
		if (exist == 1)  {
			SP[model_id].trigger('reset');
		} else {
			$('#preloader').width(this.$el.width());
			$('#preloader').height(this.$el.height());
			var p = this.$el.position();
			$('#preloader').css({'left':p.left, 'top': p.top});
			$('#preloader').fadeIn('fast');
		}
		$('.visibl', this.$el).removeClass('show');
		$('.visibl', this.$el).addClass('hd');
		$('.visibl', this.$el).html('Свернуть <i class="icon-minus-sign"></i>');
		
		return false;
	},
	
	hiden: function() {
		$('#sp_'+this.model.id, this.$el).slideUp();
		
		$('.visibl', this.$el).removeClass('hd');
		$('.visibl', this.$el).addClass('show');
		$('.visibl', this.$el).html('Развернуть <i class="icon-plus-sign"></i>');		
		$('#sp_'+this.model.id, this.$el).html('');
		return false;
	}
});

var ViewSuppliers = Backbone.View.extend({
	
	tagName: "tbody",
	
	className: "suppliers",
	
	initialize: function() {
		_.bindAll(this);
		this.collection.on('reset', this.renderAll);
	},
	
	render: function() {
		return this;
	},
	
	renderAll: function() {
		this.collection.each(function(model){
			var view = new ViewSupplier({model:model});
			var content = view.render().el;
			this.$('.suppliers').append(content);
		});
	},
})


// Model Supplier
var SupplierModel = Backbone.Model.extend({ 

});

// get suppliers
var Suppliers = Backbone.Collection.extend({
  model: SupplierModel,
  url: '/supplier/json'
});

suppliers = new Suppliers; // init collection
var view_suppliers = new ViewSuppliers({collection: suppliers}); // init view
$('#suppliers').append(view_suppliers.render().el); // add main template
suppliers.fetch();


/****************************************
 * Supplier Products
 ****************************************/
var SupplierProductView = Backbone.View.extend({
	
	tagName: "tr",
	className: "supplier_product",
	
	template: _.template(	'<td class="ps_name" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit"><%= supplier_product_name %></td>'+
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
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		return this;
	},
	
	edit: function() {
		console.log(this.model.get('id'));
	}
	
})

// view list supplier products
var ViewSupplierProducts = Backbone.View.extend({
	
	tagName: "table",
	className: "sproducts table table-bordered white",
	
	initialize: function() {
		_.bindAll(this);
		this.collection.on('reset', this.renderAll);
	},
	
	render: function(args) {
		this.args = args;
		return this;
	},
	
	renderAll: function() {
		
		$("#supplier_products_header").clone().appendTo(this.$el); // Добавляем шапку с заголовками/сортировками/формой
		this.$el.append('<tbody class="supplier_products"></tbody>');
		
		$('#sp_'+this.args.id).slideDown();
		
		if (this.collection.length > 0) {
			
			this.collection.each(function(model){
				var view = new SupplierProductView({model:model});
				var content = view.render().el;
				this.$('.supplier_products').append(content);
			});
			
			$('.supplier_products .supplier_product td').attr('style','background-color: #fff;');
			$('.supplier_products .supplier_product:first-child td').attr('style','border-top:1px solid #ddd; background-color: #fff;');
		
		} else {
		
			this.$('.supplier_products').append('<tr><td colspan="4"><div class="alert">'+
												'<button type="button" class="close" data-dismiss="alert">×</button>'+
												'У данного поставщика еще нет продуктов</div></td></tr>');
		}
		
		$('#preloader').fadeOut('fast');
		return this;
	}

});



// Model supplier products
var SupplierProductsModel = Backbone.Model.extend({ 

});


// get supplier products
/*var SupplierProducts = Backbone.Collection.extend({
  model: SupplierProductsModel,
  url: '/supplier/products/json'
});*/
//console.log(SupplierProducts.url);
//supplier_products = new SupplierProducts; // init collection
//var view_supplier_products = new ViewSupplierProducts({collection: supplier_products}); // init view
//$('#supplier_product_list').append(view_supplier_products.render().el); // add main template
//supplier_products.fetch();

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
