//* update 2012-10-18 16:15:00 *//
/****************************************
 * Supplier Products
 ****************************************/
var supplier_products, VSP;
$(function(){
	var SupplierProductView = Backbone.View.extend({
		
		tagName: "tr",
		className: "supplier_product",
		
		template: _.template(	'<td class="ps_name">'+
									'<input type="text" value="<%= supplier_product_name %>" class="supplier_product_name">'+
								'</td>'+
								'<td class="ps_price">'+
									'<input type="text" value="<%= price %>" class="price span1">'+
								'</td>'+
								'<td class="ps_product"></td>'+
								'<td class="ps_prime"><% if(primary_supplier) print("Да"); else print("Нет"); %>'+
									'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
								'</td>'),
		
		events: {
			'click .remove'	: 'remove',
			'change input.supplier_product_name':  'save',
			'change input.price':  'save',
			'change input.primary_supplier':  'save',
			'change select.product':  'save',
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
			$('.ps_product', this.el).html('<select class="product span3"></select>');		
			var select_product = $('.product', this.el);
			
			var product_id = this.model.attributes.product;
			
			products._byId[product_id].attributes.use = 0;
			
			products.each(function(p){
				
				if (p.attributes.use == 0) {
					
					var view = new OptionProducts({model:p});
					$(select_product).append(view.render().el);
					if(p.id == product_id) {
						$(view.render().el).attr('selected','selected');
					}
				}
			});
			products._byId[product_id].attributes.use = 1;
			
			$('.ps_prime', this.el).prepend('<p>'+
							'<label class="checkbox"><input type="checkbox" class="input-small primary_supplier"> Первичный</label>'+
							'</p>');
			if (this.model.get('primary_supplier'))
				$('.primary_supplier', this.el).attr('checked','checked');
				
			$('#preloader').fadeOut('fast');
			return this;
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
							},{wait: true});
		},	
	})

	// view list supplier products
	var ViewSupplierProducts = Backbone.View.extend({
		
		tagName: "tbody",
		className: "supplier_products",
		
		events: {
			'click .close_form': 'close_form',
			'click .sort_by_name': 'sort_by_name',
			'click .sort_by_price': 'sort_by_price',
			'click .sort_by_prime': 'sort_by_prime',
		},
		
		initialize: function() {
			_.bindAll(this);
			this.collection.on('reset', this.renderAll);	
		},
		
		render: function() {
			return this;
		},
		
		renderAll: function() {
			$('#preloader').fadeOut('fast');
			this.renderProducts();
			return this;
		},
		
		renderProducts: function() {
			if (this.collection.length > 0) {
				
				$('.supplier_products').html('');
				this.collection.each(function(model){
				
					var view = new SupplierProductView({model:model});
					var content = view.render().el;
					
					if (supplier_products_sort == 'desc')
						$('.sp_list .supplier_products').prepend(content);
					else
						$('.sp_list .supplier_products').append(content);
						
					
				});
			
			} else {
			
				$('.supplier_products').append('<tr class="alert_row"><td colspan="4"><div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'У данного поставщика еще нет продуктов</div></td></tr>');
			}
			
			return this;
			
		},
		
		close_form: function() {
			$('.sp_list .form_add_supplier_product').slideUp(function(){
				$('.sp_list .add_supplier_product_show').removeClass('close_form');
				$('.sp_list .add_supplier_product_show i').attr('class', 'icon-plus-sign');
			});
		},

		sort_by_name: function() {
			var list = new Backbone.Collection;
			list.comparator = function(chapter) {
			  return chapter.get("supplier_product_name");
			};
			
			if (supplier_products_sort == 'asc') {
				supplier_products_sort = 'desc';
				$('.sp_list .sort_by_name i').attr('class','icon-arrow-down');
			} else {
				supplier_products_sort = 'asc';
				$('.sp_list .sort_by_name i').attr('class','icon-arrow-up');
			}
			
			list.reset(supplier_products.models, {silent:true})
			this.collection = list;
			
			this.renderProducts();
			return false;
		},
		
		sort_by_price: function() {
			var list = new Backbone.Collection;
			list.comparator = function(chapter) {
			  return chapter.get("price");
			};

			if (supplier_products_sort == 'asc') {
				supplier_products_sort = 'desc';
				$('.sp_list .sort_by_price i').attr('class','icon-arrow-down');
			} else {
				supplier_products_sort = 'asc';
				$('.sp_list .sort_by_price i').attr('class','icon-arrow-up');
			}
			
			list.reset(supplier_products.models, {silent:true})
			this.collection = list;	
			
			this.renderProducts();
			return false;
		},
		
		sort_by_prime: function() {
			var list = new Backbone.Collection;
			list.comparator = function(chapter) {
			  return chapter.get("primary_supplier");
			};
			
			if (supplier_products_sort == 'asc') {
				supplier_products_sort = 'desc';
				$('.sp_list .sort_by_prime i').attr('class','icon-arrow-down');
			} else {
				supplier_products_sort = 'asc';
				$('.sp_list .sort_by_prime i').attr('class','icon-arrow-up');
			}
			
			list.reset(supplier_products.models, {silent:true})
			this.collection = list;	
			
			this.renderProducts();
			return false;
		}
	});


	// Model supplier products
	var SupplierProductsModel = Backbone.Model.extend({
	  
	  sync: function(method, model, options) {
		   var SProductOptions = options;
		   
			if (method == 'delete') {
				SProductOptions.success = function(resp, status, xhr) {
					$('#preloader').fadeOut('fast');
					if (resp.data == model.id) {
						
						products._byId[model.attributes.product].attributes.use = 0;
						$('.product_add_sp').html('');
						products.each(function(p){
							if (p.attributes.use == 0) {
								var view = new OptionProducts({model:p});
								$('.product_add_sp').append(view.render().el);
							}
						});
						
						if ($('.product_add_sp option').length > 0)
							$('.create, .forms').fadeIn();
						
						$(model.view.el).remove();
						model.collection.remove(model, {silent: true});
						VSP.renderAll().el
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
					
					if (resp != null && typeof(resp.message) != 'undefined') {
						
					   $('#preloader').fadeOut('fast'); 
					   $('.ps_prime', model.view.el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Ошибка (' + resp.errors + '). '+
														'Попробуйте еще раз или обратитесь к администратору.</div>');
					   return;
					} else {
					   if (resp != null && typeof(resp.data) != 'undefined') {
						
							if (resp.data.product != model.attributes.product ) {
								
								products._byId[model.attributes.product].attributes.use = 0;
								products._byId[resp.data.product].attributes.use = 1;
								$('.product_add_sp').html('');
								products.each(function(p){
									if (p.attributes.use == 0) {
										var view = new OptionProducts({model:p});
										$('.product_add_sp').append(view.render().el);
									}
								});
							}
						
						   model.set(resp.data,{silent: true});
						   model.view.render();
						   supplier_products.sort({silent: true});
						   VSP.remove()
						   VSP = new ViewSupplierProducts({collection: supplier_products});
						   
							$('.sproducts').append(VSP.render().el);
							VSP.renderAll().el
							return;
					   } else {
						   $('#preloader').fadeOut('fast'); 
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
					
					$('.sp_list .alert').remove();
					
					if (resp != null && typeof(resp.data) != 'undefined') {
						model.set(resp.data, {silent:true});

						products._byId[resp.data.product].attributes.use = 1;
						$('.product_add_sp option[value="'+resp.data.product+'"]').remove();
					   
						if ($('.product_add_sp option').length == 0)
							$('.create, .forms').fadeOut();
					   
					   var view = new SupplierProductView({model:model});
					   var content = view.render().el;
					   $('.sp_list .supplier_products').prepend(content);
					   $("#up .alert-success").clone().appendTo('.sp_list .form_add_supplier_product');
					   $('.sp_list .form_add_supplier_product .alert-success').fadeIn();
						
					   $('.sp_list .name_add_sp').val('');
					   $('.sp_list .price_add_sp').val('');

						supplier_products.sort({silent: true});
						VSP.remove()
						VSP = new ViewSupplierProducts({collection: supplier_products});
						$('.sproducts').append(VSP.render().el);
						VSP.renderAll().el
					   
					   return;
					   
					} else {
						
					   $('#preloader').fadeOut('fast'); 
					   
					   if (resp != null && typeof(resp.message) != 'undefined')
							$('#up .alert-error strong').html(''+resp.message);
							
					   $("#up .alert-error").clone().appendTo('.form_add_supplier_product');
					   $('.sp_list .alert-error').fadeIn();
					   supplier_products.remove(model, {silent:true});
					   
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
	
		url: '/company/'+parseInt(href[2])+'/supplier/'+parseInt(href[4])+'/product',
	 
		initialize: function(models, options) {
			this.bind('add', this.addProduct);
			
			this.fetch({	
							error: function(){
								$('#preloader').fadeOut('fast');
								console.log(' error =( ');

								$('#add_row').html('<td colspan="4"><div class="alert">'+
												'<button type="button" class="close" data-dismiss="alert">×</button>'+
												'Ошибка на сервере, обновите страницу или обратитесь к администратору</div></td>');
							},
							success: function(collection, response){
								
								collection.each(function(model){									
									products._byId[model.get('product')].set({use:1}, {silent: true});
								})
								
								VSP = new ViewSupplierProducts({collection: collection});

								$('.sproducts').append(VSP.render().el);
								VSP.renderAll().el;
								
							}
				});
		},
	  
		parse: function(response) {
			
			if(response.code && (response.code == 200)){
				return response.data;
			} else {
				alert('error product request');
			}
		},
	  
		addProduct: function(product){
			product.save({wait: true});
		},
	  
	});

	/**********************************************
	 * Option Product for add/edit Supplier Product
	 **********************************************/
	var OptionProducts = Backbone.View.extend({
		
		tagName: "option",
		
		template: _.template('<%= name %> [<% print(units._byId[unit].get("name")) %>]'),
		
		render: function() {
			//console.log(units);
			var content = this.template(this.model.toJSON());
			this.$el.html(content);
			this.$el.attr('value', this.model.id)
			return this;
		},
		
	})

	var Products = Backbone.Collection.extend({
		url: '/company/'+parseInt(href[2])+'/product',
		parse: function(response, xhr){
			if(response.code && (response.code == 200)){
				return response.data;
			} else {
				console.log('bad request');
			}
		}
	})
	
	var products = new Products;
	
	$(".forms .alert").remove();
	$('#preloader').width($('#add_row').width());
	$('#preloader').height($('#add_row').height());
	var p = $('#add_row').position();
	$('#preloader').css({'left':p.left, 'top': p.top});
	$('#preloader').fadeIn('fast');
	
	products.fetch({	success:function(){
							
							supplier_products = new SupplierProducts({}, {units: units, products: products}); // init collection

							supplier_products.comparator = function(product) {
							  return product.get("supplier_product_name");
							};
							
						}, error:function(){
							$('#preloader').fadeOut('fast');
							
							$('#add_row').html('<td colspan="4"><div class="alert">'+
												'<button type="button" class="close" data-dismiss="alert">×</button>'+
												'Ошибка на сервере, обновите страницу или обратитесь к администратору</div></td>');
							
							console.log('error get products')
						}
					});

	var supplier_products_sort = 'asc';
	
	$('.sort_by_price').click(function(){
		VSP.sort_by_price();
		return false;
	});
	$('.sort_by_prime').click(function(){
		VSP.sort_by_prime();
		return false;
	});
	$('.sort_by_name').click(function(){
		VSP.sort_by_name();
		return false;
	});
	
	$('.add_supplier_product_show').toggle(function(){
		$('.sp_list .product_add_sp').html('');
		
		products.each(function(p){
			if (p.attributes.use == 0) {
				var view = new OptionProducts({model:p});
				$('.product_add_sp').append(view.render().el);
			}
		});
		
		$('.form_add_supplier_product').slideDown(function(){
			$('.add_supplier_product_show i').attr('class', 'icon-minus-sign');
		});
		
		$('.sp_list .name_add_sp').focus();
		return false;
	}, function(){
		$('.form_add_supplier_product').slideUp(function(){
			$('.add_supplier_product_show i').attr('class', 'icon-plus-sign');
		});
		return false;
	});
	
	$('.add_supplier_product_btn').click(function(){
		
		$(".forms .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		
		supplier_products.add([{
						supplier_product_name: $('.name_add_sp').val(), 
						price: $('.price_add_sp').val(),
						product: $('.product_add_sp').val(),
						primary_supplier: $('.primary_supplier_add_sp').is(':checked')?1:0,
					}],{wait: true});
		return false;
	});
	
	$(document).keydown(function(e) {
		if (e.keyCode == 27) VSP.renderAll();
	});
})
