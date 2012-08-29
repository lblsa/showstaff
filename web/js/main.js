/****************************************
 * Delete product OR supplier_products
 ***************************************/
$(document).ready(function(){
    $('.del').click(function(){
		return confirm ("Будте осторожны, будут также удалены все связанные продукты.\r\nВы действительно хотите удалить элемент?");
	});
	
    $('.del_supplier_product').click(function(){
		return confirm ("Вы действительно хотите удалить элемент?");
	});
})


/****************************************
 * Products
 ****************************************/
var units = {1:'кг', 
			 2:'литр',
			 3:'шт',
			 4:'пучок',
			 5:'бутылка'};

Backbone.emulateHTTP = true;
Backbone.emulateJSON = true;
// view list products
var ViewProducts = Backbone.View.extend({
	
	tagName: "tbody",
	className: "products",
	
	initialize: function() {
		_.bindAll(this);
		this.collection.on('reset', this.renderAll);
	},
	
	render: function() {
		return this;
	},
	
	renderAll: function() {
		this.collection.each(function(model){
			var view = new ViewProduct({model:model});
			var content = view.render().el;
			this.$('.products').append(content);
		})	
	},

});

// view one product
var ViewProduct = Backbone.View.extend({
	
	tagName: "tr",
	className: "product",
	
	template: _.template(	'<td class="p_name"><%= name %></td>'+
							'<td class="p_unit"><% print(units[unit]); %>'+
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
		this.model.on('add', this.create);
	},
	
	render: function(){
		var content = this.template(this.model.toJSON());
		this.$el.append(content);
		return this;
	},
	
	edit: function() {
		$('.p_name', this.el).html('<input type="text" class="input-small name" name="name" value="'+ this.model.get('name') +'">');
		var option = '';
		for(var key in units) {
			option += '<option value="'+key+'"'+ ((this.model.get('unit') == key)?' selected="selected"':'') +'>'+units[key]+'</option>';
		}
		$('.p_unit', this.el).html('<p class="form-inline">'+
									'<select class="span1 unit" name="unit">'+ option+'</select>'+
									' <a class="save btn btn-mini btn-success">save</a>'+
									' <a class="cancel btn btn-mini btn-danger">cancel</a></p>');
	},
	
	save: function() {
		this.model.save({
						name: $('.name', this.el).val(), 
						unit: $('.unit', this.el).val()
						});
		
		return this.render().el;
	},
	
	cancel: function() {
		return this.render().el;
	},

	remove: function() {
		if ( confirm ("Будте осторожны, будут также удалены все связанные продукты.\r\nВы действительно хотите удалить элемент?") ) {
			this.model.destroy({wait: true});
		}
	},
	
	create: function() {
		alert(12);
	}
	
})

// Model products
var ProductsModel = Backbone.Model.extend({
  sync: function(method, model, options) {
        var productOptions = options;
        
        if (method == 'delete') {
			productOptions.success = function(resp, status, xhr) {
				if (resp == model.id) {
					$(model.view.el).remove();
					model.collection.remove(model, {silent: true});
					return;
				} else {
				   $('.p_unit', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка удаления! Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				}
				return options.success(resp, status, xhr);
			};
		}
        
        if (method == 'update') {
			productOptions.success = function(resp, status, xhr) {
				if (resp.has_error) {
				   console.log('Error', resp);
				   return;
				} else {
				   console.log('Ok', resp);
				   return;
				}
				return options.success(resp, status, xhr);
			};
		}
		
		Backbone.sync.call(this, method, model, productOptions);
   }
});


$(document).ready(function () {

	var Products = Backbone.Collection.extend({
	  
	  model: ProductsModel,
	  
	  url: '/product/json',
	  
	  initialize: function(){
		  this.bind('add', this.addProduct);
	  },
	  
	  addProduct: function(product){
		product.save({wait: true});
		var view = new ViewProduct({model:product});
		var content = view.render().el;
		$('.products').append(content);
	  }
	  
	});
	
	products = new Products; // init collection
	var view_products = new ViewProducts({collection: products}); // initialize view
	$('#product_list').append(view_products.render().el); // add template
	products.fetch();
	
	$('.create').toggle(function() {
		var option = '';
		for(var key in units) {
			option += '<option value="'+key+'" >'+units[key]+'</option>';
		}
		$('#form_add').slideDown();
		$('.unit_add').html(option);
		$('.name_add').focus();
		return false;
	}, function() {
		$('#form_add').slideUp();
		return false;
	});
	
	$('.add_product').click(function() {
		products.add([{name: $('.name_add').val(), unit: $('.unit_add').val()}]);
		return false;
	})
});

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
