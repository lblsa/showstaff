/****************************************
 * Supplier
 ****************************************/
var SP = {}; // коллекции продуктров поставщика
var VSP = {}; // коллекция видов

var ViewSupplier = Backbone.View.extend({
	
	tagName: "tr",
	className: "supplier",
	
	template: _.template(	'<td class="s_name" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit"><p>'+
							'<%= name %>'+
							' <a href="#" class="pull-right btn btn-small show visibl">Развернуть <i class="icon-plus-sign"></i></a></p>'+
							'<div class="hide sp_list" id="sp_<%= id %>"></div></td>'),
	
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
	
	preloader: function() {
		$('#preloader_s').width($('#suppliers').width());
		$('#preloader_s').height($('#suppliers').height());
		var p = $('#suppliers').position();
		$('#preloader_s').css({'left':p.left, 'top': p.top});
		$('#preloader_s').show();
		return;
	},
	
	show: function() {
		
		var model_id = this.model.id;
		var exist = 0;
		if(typeof(SP[model_id]) == "undefined") {

			var SupplierProductsCurent = SupplierProducts.extend({
			  url: '/supplier_products/supplier/'+this.model.id,
			});
			
			SP[model_id] = new SupplierProductsCurent;
			
			SP[model_id].comparator = function(product) {
			  return product.get("supplier_product_name");
			};
			
			SP[model_id].fetch();
		} else {
			exist = 1;			
		}
		
		VSP[model_id] = new ViewSupplierProducts({collection: SP[model_id]});
		
		$('#sp_'+model_id, this.$el).html(VSP[model_id].render({id:model_id}).el);		
		
		this.preloader();
		
		if (exist == 1)
			SP[model_id].trigger('reset');
	
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
		$('#preloader_s').hide();
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

var suppliers = new Suppliers; // init collection
var view_suppliers = new ViewSuppliers({collection: suppliers}); // init view
$('#suppliers').append(view_suppliers.render().el); // add main template

$('#preloader_s').width($('#suppliers').width());
$('#preloader_s').height($('#suppliers').height());
var p = $('#suppliers').position();
$('#preloader_s').css({'left':p.left, 'top': p.top});
$('#preloader_s').fadeIn('fast');

suppliers.comparator = function(supplier) {
  return supplier.get("name");
};

suppliers.fetch();


/****************************************
 * Supplier Products
 ****************************************/
var SupplierProductView = Backbone.View.extend({
	
	tagName: "tr",
	className: "supplier_product",
	
	template: _.template(	'<td class="ps_name" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit"><%= supplier_product_name %></td>'+
							'<td class="ps_price"><%= price %></td>'+
							'<td class="ps_product"><% print(units[products._byId[product].attributes.unit]); %></td>'+
							'<td class="ps_prime"><% if(primary_supplier) print("Да"); else print("Нет"); %>'+
								'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
							'</td>'),
	
	events: {
		'dblclick .ps_name': 'edit',
		'click .remove': 'remove',
		'click .save': 'save',
		'click .cancel': 'cancel',
	},
	
	initialize: function() {
		this.model.view = this;
	},
	
	preloader: function() {
		$('#preloader_s').width(this.$el.width());
		$('#preloader_s').height(this.$el.height());
		var p = this.$el.position();
		$('#preloader_s').css({'left':p.left, 'top': p.top});
		$('#preloader_s').fadeIn('fast');
	},
	
	render: function(){
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		$('#preloader_s').fadeOut('fast');
		return this;
	},
	
	edit: function() {
		$('.ps_name', this.el).html('<input type="text" value="'+this.model.get('supplier_product_name')+'" class="supplier_product_name">');
		$('.ps_price', this.el).html('<input type="text" value="'+this.model.get('price')+'" class="price input-small">');
		$('.ps_product', this.el).html('<select class="product"></select>');		
		
		var product_id = this.model.get('product');
		products.each(function(p){
			var view = new OptionProducts({model:p});
			$('.product', this.el).append(view.render().el);
			if(p.id == product_id) {
				$(view.render().el).attr('selected','selected');
			}
		});
		$('.ps_prime', this.el).html(	'<p><label class="checkbox"><input type="checkbox" class="input-small primary_supplier"> Первичный</label>'+
										' <a class="save btn btn-mini btn-success">save</a>'+
										' <a class="cancel btn btn-mini btn-danger">cancel</a></p>');
		
		if (this.model.get('primary_supplier')) {
			$('.primary_supplier', this.el).attr('checked','checked');
		}
	},
	
	remove: function() {
		if ( confirm ("Будте осторожны, будут также удалены все связанные продукты.\r\nВы действительно хотите удалить элемент?") ) {
			this.preloader();
			this.model.destroy({wait: true });
		}
		return false;
	},
	
	save: function() {
		this.preloader();
		this.model.save({
							supplier_product_name: $('.supplier_product_name', this.el).val(), 
							price: $('.price', this.el).val(),
							product: $('.product', this.el).val(),
							primary_supplier: $('.primary_supplier', this.el).is(':checked')?1:0,
							supplier: this.model.attributes.supplier
						},{wait: true});
	},
	
	cancel: function() {
		return this.render().el;	
	}
	
})

// view list supplier products
var ViewSupplierProducts = Backbone.View.extend({
	
	tagName: "table",
	className: "sproducts table table-bordered white",
	
	events: {
		'click .add_supplier_product_show': 'show_form',
		'click .close_form': 'close_form',
		'click .add_supplier_product_btn': 'add',
	},
	
	initialize: function() {
		_.bindAll(this);
		this.collection.on('reset', this.renderAll);
	},
	
	render: function(args) {
		this.args = args;
		return this;
	},
	
	renderAll: function() {
		if (typeof(this.args.id) != 'undefined') {
			$('#sp_'+this.args.id+' .sproducts').html('');
			$("#supplier_product_list .supplier_products_header").clone().appendTo('#sp_'+this.args.id+' .sproducts'); // Добавляем шапку с заголовками/сортировками/формой
			$('#sp_'+this.args.id+' .sproducts').append('<tbody class="supplier_products"></tbody>');
			$('#preloader_s').hide();
			$('#sp_'+this.args.id).slideDown();
			
			var id = this.args.id;
			
			if (this.collection.length > 0) {
				
				$('#sp_'+this.args.id+' .supplier_products').html('');
				this.collection.each(function(model){
					var view = new SupplierProductView({model:model});
					var content = view.render().el;
					$('#sp_'+id+' .supplier_products').append(content);
				});
				
				//$('.supplier_products .supplier_product td').attr('style','background-color: #fff;');
				$('.supplier_products .supplier_product:first-child td').attr('style','border-top:1px solid #ddd;');
			
			} else {
			
				this.$('.supplier_products').append('<tr class="alert_row"><td colspan="4"><div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'У данного поставщика еще нет продуктов</div></td></tr>');
			}
		}
		return this;
	},
	
	show_form: function() {
		var sid = this.args.id;
		
		//console.log(sid);
		
		$('#sp_'+sid+' .product_add_sp').html('');
		
		products.each(function(p){
			var view = new OptionProducts({model:p});
			$('#sp_'+sid+' .product_add_sp').append(view.render().el);
		});
		
		$('#sp_'+sid+' .form_add_supplier_product').slideDown(function(){
			$('#sp_'+sid+' .add_supplier_product_show').addClass('close_form');
			$('#sp_'+sid+' .add_supplier_product_show i').attr('class', 'icon-minus-sign');
		});
		
		$('#sp_'+sid+' .name_add_sp').focus();
		return false;
	},
	
	close_form: function() {
		var sid = this.args.id;
		$('#sp_'+sid+' .form_add_supplier_product').slideUp(function(){
			$('#sp_'+sid+' .add_supplier_product_show').removeClass('close_form');
			$('#sp_'+sid+' .add_supplier_product_show i').attr('class', 'icon-plus-sign');
		});
	},
	
	add: function() {
		$('#preloader_s').width($('#suppliers').width());
		$('#preloader_s').height($('#suppliers').height());
		var p = $('#suppliers').position();
		$('#preloader_s').css({'left':p.left, 'top': p.top});
		$('#preloader_s').show();
		
		this.collection.add([{	supplier_product_name:	$('#sp_'+this.args.id+' .name_add_sp').val(), 
								price:					$('#sp_'+this.args.id+' .price_add_sp').val(), 
								product:				$('#sp_'+this.args.id+' .product_add_sp').val(), 
								supplier:				this.args.id, 
								primary_supplier:		$('#sp_'+this.args.id+' .primary_supplier_add_sp').is(':checked')?1:0,
							}]);
	},

});


// Model supplier products
var SupplierProductsModel = Backbone.Model.extend({
  methodUrl:  function(method){
	 
	 //console.log(this.attributes); 
	 
	if(method == "delete"){
			return "/supplier_products/supplier/" + this.attributes.supplier + "/delete/" + this.attributes.id;
		} else if(method == "update"){
			return "/supplier_products/supplier/" + this.attributes.supplier + "/update/" + this.attributes.id;
		} else if(method == "create"){
			return "/supplier_products/supplier/" + this.attributes.supplier + "/create";
		} 
		return false;
  },
  
  sync: function(method, model, options) {
       var SProductOptions = options;
       
        if (method == 'delete') {
			SProductOptions.success = function(resp, status, xhr) {
				//console.log(status);
				$('#preloader_s').fadeOut('fast');
				if (resp == model.id) {
					$(model.view.el).remove();
					console.log(model.collection);
					model.collection.remove(model, {silent: true});
					return;
				} else {
				   $('.ps_prime', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка удаления! Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				}
				return options.success(resp, status, xhr);
			};
			SProductOptions.error = function(resp, status, xhr) {			
				return options.success(resp, status, xhr);
			}
		}

        if (method == 'update') {
			SProductOptions.success = function(resp, status, xhr) {
				
				if (resp != null && resp.has_error) {
					
				   $('#preloader_s').fadeOut('fast'); 
				   $('.ps_prime', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка (' + resp.errors + '). '+
													'Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				} else {
					console.log(resp);
				   if (resp != null && typeof(resp.id) != 'undefined' && resp.id > 0) {
					   model.set(resp,{silent: true});
					   model.view.render();
					   
					   //  for sort reload
					   /*products.sort({silent: true});
					   
					   view_products.remove()
					   view_products = new ViewProducts({collection: products});
					   $('#product_list').append(view_products.render().el);
					   view_products.renderAll()*/
					   
					   return;
				   } else {
					   $('#preloader_s').fadeOut('fast'); 
					   $('.ps_prime', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка. Попробуйте еще раз или обратитесь к администратору.</div>');
					   model.set(model.previousAttributes(),{silent: true});
					   return;
				   }
				}
				return options.success(resp, status, xhr);
			};
			SProductOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
		}

		if (method == 'create') {
			SProductOptions.success = function(resp, status, xhr) {
				
				$('#sp_'+model.attributes.supplier+' .alert').remove();
				
				console.log(resp);
				
				if (resp != null && typeof(resp.id) != 'undefined' && resp.id > 0) {
				   model.set(resp, {silent:true});
				   var view = new SupplierProductView({model:model});
				   var content = view.render().el;
				   $('#sp_'+model.attributes.supplier+' .supplier_products').prepend(content);
				   $("#up .alert-success").clone().appendTo('#sp_'+model.attributes.supplier+' .form_add_supplier_product');
				   $('#sp_'+model.attributes.supplier+' .form_add_supplier_product .alert-success').fadeIn();
				    
				   $('#sp_'+model.attributes.supplier+' .name_add_sp').val('');
				   $('#sp_'+model.attributes.supplier+' .price_add_sp').val('');
				  
				  
				  //  for sort reload
				   /*view_products.remove()
				   view_products = new ViewProducts({collection: products});
				   $('#product_list').append(view_products.render().el);
				   view_products.renderAll()*/
				   
				   return;
				   
				} else {
					
				   $('#preloader_s').fadeOut('fast'); 
				   
				   if (resp != null && resp.has_error)
						$('#up .alert-error strong').html(''+resp.errors);
						
				   $("#up .alert-error").clone().appendTo('.form_add_supplier_product');
				   $('#sp_'+model.attributes.supplier+' .alert-error').fadeIn();
				   return;
				}
				return options.success(resp, status, xhr);
			};
			SProductOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
		}
		
       if (model.methodUrl && model.methodUrl(method.toLowerCase())) {
      	   options = options || {};
      	   options.url = model.methodUrl(method.toLowerCase());
        }
		
		Backbone.sync.call(this, method, model, SProductOptions);
  },
});

// extend url in view ViewSupplier
var SupplierProducts = Backbone.Collection.extend({
  model: SupplierProductsModel,
  
  initialize: function(){
	  this.bind('add', this.addProduct);
  },
  
  addProduct: function(product){
	product.save({wait: true});
  },
  
});

$(document).ready(function(){
	
	$('#close_all').click(function(){
		$('.sp_list').html('');
		$('.sp_list').slideUp('fast');
		$('.visibl').removeClass('hd');
		$('.visibl').addClass('show');
		$('.visibl').html('Развернуть <i class="icon-plus-sign"></i>');	
		
		return false;
	});
	
})

/**********************************************
 * Option Product for add/edit Supplier Product
 **********************************************/
var OptionProducts = Backbone.View.extend({
	
	tagName: "option",
	
	template: _.template('<%= name %>'),
	
	render: function() {
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		this.$el.attr('value', this.model.id)
		return this;
	},
	
})

