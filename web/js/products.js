//* update 2012-10-18 16:15:00 *//
/****************************************
 * Products
 ***************************************/ 
var sort = 'asc';
var view_products, products;
$(function(){

	$(".name_add").focusin(function() { 	clearTimeout(hide_add);  });
	$(".name_add").keydown(function() { 	clearTimeout(hide_add);  });
	$(".name_add").keypress(function() { 	clearTimeout(hide_add);  });
	$(".name_add").keyup(function() { 	clearTimeout(hide_add);  });

    function log( message ) {
        $( "<div>" ).text( message ).prependTo( "#log" );
        $( "#log" ).scrollTop( 0 );
    }
 
	$( ".name_add" ).autocomplete({

		delay: 500, 
        
        source: function( request, response ) {
            $.ajax({
                url: "/api/company/"+href[2]+"/product_search",
                dataType: "json",
                type: "POST",
                data: '{ "name_contains": "'+request.term+'" }',
                success: function( data ) {
                    response( $.map( data, function( item ) {
                        return { value: item.name }
                    }));
                }
            });
        },

        minLength: 2,

        open: function() {
            $( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
        },

        close: function() {
            $( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
        }
    });

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
			
			if (this.collection.length > 0) {
				$('.products').html('');
				this.collection.each(function(model){
					var view = new ViewProduct({model:model});
					var content = view.render().el;
					if (sort == 'desc')
						$('.products').prepend(content);
					else
						$('.products').append(content);
				});
				
			} else {
				$('.products').html('<tr class="alert_row"><td colspan="2"><div class="alert">'+
									'<button type="button" class="close" data-dismiss="alert">×</button>'+
									'У вас еще нет продуктов</div></td></tr>');
				$('#preloader').fadeOut('fast');
			}
			return this;
		},
	});

	// view one product
	var ViewProduct = Backbone.View.extend({
		
		tagName: "tr",
		className: "product",
		
		template: _.template(	'<td class="p_name">'+
									'<input type="text" class="input name" name="name" value="<%= name %>">'+
								'</td>'+
								'<td class="p_unit">'+
									'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
								'</td>'),
		
		events: {
			'change input.name':  'save',
			'change select.unit':  'save',
			'click .remove': 'remove',
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
			var this_model = this.model; 
			var option = '';
			units.each(function(u){
				option +=	'<option value="'+u.id+'"'+ ((this_model.get('unit') == u.id)?' selected="selected"':'') +'>'+
								u.get('name')+
							'</option>';
			})

			$('.p_unit', this.el).prepend('<select class="span2 unit" name="unit">'+ option+'</select>');
										
			$('#preloader').fadeOut('fast'); 
			return this;
		},
		
		save: function() {
			this.preloader();
			this.model.save({
							name: $('.name', this.el).val(), 
							unit: $('.unit', this.el).val()
							},{wait: true});
		},

		remove: function() {
			if ( confirm ("Будте осторожны, будут также удалены все связанные продукты.\r\nВы действительно хотите удалить элемент?") ) {
				this.preloader();
				this.model.destroy({wait: true });
			}
			return false;
		},
		
	})

	// Model product
	var ProductModel = Backbone.Model.extend({
	  
	  sync: function(method, model, options) {
			
			var productOptions = options;
			
			productOptions.url = '/api/company/'+href[2]+'/product/'+this.attributes.id;
			
			if (method == 'delete') {
				productOptions.success = function(resp, status, xhr) {
					$('#preloader').fadeOut('fast');
					if (resp != null && typeof(resp.data) != 'undefined' && resp.data == model.id) {
						$(model.view.el).remove();
						model.collection.remove(model, {silent: true});
					   
						var SP = {};
					   
					} else {
						
						$('#up .alert-error strong').html('');						
						$("#up .alert-error").width(model.view.$el.width()-50);
						$("#up .alert-error").height(model.view.$el.height()-14);
						var p = model.view.$el.position();
						$('#up .alert-error').css({'left':p.left, 'top': p.top-10});
						$('#up .alert-error').fadeIn();
						model.view.render();
					  
					}
				};
			}
			
			if (method == 'update') {
				productOptions.success = function(resp, status, xhr) {
				   model.set(resp.data,{silent: true});
				   var SP = {};
				   
				   //  for sort reload
				   products.sort({silent: true});
				   
				   view_products.remove()
				   view_products = new ViewProducts({collection: products});
				   $('#product_list').append(view_products.render().el);
				   view_products.renderAll()
				};				
			}
			
			if (method == 'create') {
				productOptions.success = function(resp, status, xhr) {
					if (resp != null && typeof(resp.data) != 'undefined') {
						model.set(resp.data, {silent:true});
						var view = new ViewProduct({model:model});
						var content = view.render().el;
						$('.products').prepend(content);
						$('.name_add').val('');
						$(".alert-success").clone().appendTo('.forms');
						$(".forms .alert-success").fadeIn();

						var SP = {};
						$('#close_all').click();
						//  for sort reload
						view_products.remove()
						view_products = new ViewProducts({collection: products});
						$('#product_list').append(view_products.render().el);
						view_products.renderAll();
					}
				};
				productOptions.error = function(jqXHR, textStatus, errorThrown) {
					$('#preloader').fadeOut('fast');
					if (typeof(jqXHR) != 'undefined' && typeof(jqXHR.responseText) != 'undefined')
						$('#up .alert-error strong').html('('+jqXHR.responseText+'). ');
					else
						$('#up .alert-error strong').html('(Некорректный ответ сервера). ');
						
					$("#up .alert-error").width($('.forms').width()-49);
					$("#up .alert-error").height($('.forms').height()-14);
					var p = $('.forms').position();
					$('#up .alert-error').css({'left':p.left, 'top': p.top-10});
					$('#up .alert-error').fadeIn();
					products.remove(model, {silent:true});
				}
				productOptions.url = '/api/company/'+href[2]+'/product';
			}
			
			Backbone.sync.call(this, method, model, productOptions);
		},
	});

	var Products = Backbone.Collection.extend({
	  
		model: ProductModel,
	  
		url: '/api/company/'+href[2]+'/product',
		  
		initialize: function(models, units){
			
			this.bind('add', this.addProduct);
			
			$('#preloader').width($('#add_row').width());
			$('#preloader').height($('#add_row').height());
			var p = $('#add_row').position();
			$('#preloader').css({'left':p.left, 'top': p.top});
			$('#preloader').fadeIn('fast');			
		},

		parse: function(response) {
			
			if(response.code && (response.code == 200)){
				return response.data;
			} else {
				$('.products').html('<tr class="alert_row"><td colspan="2"><div class="alert">'+
									'<button type="button" class="close" data-dismiss="alert">×</button>'+
									'У вас еще нет продуктов</div></td></tr>');
				$('#preloader').fadeOut('fast');
			}
		},
	  
		addProduct: function(product){
			product.save(product.toJSON(), {wait: true});
		},
	  
	});	

	products = new Products({}, {units:units}); // init collection
	
	products.comparator = function(product) {
	  return product.get("name");
	};

	products.fetch({	error:function(){
								$('.products').html('<tr class="alert_row"><td colspan="2"><div class="alert">'+
												'<button type="button" class="close" data-dismiss="alert">×</button>'+
												'Некорректный ответ, обновите страницу или обратитесь к администратору</div></td></tr>');
								$('#preloader').fadeOut('fast');	
							}
	});

	view_products = new ViewProducts({collection: products}); // initialize view

	$('#product_list').append(view_products.render().el); // add template	
	
	
	$('.create').click(function() {
		var option = '';
		units.each(function(u){
			option += '<option value="'+u.id+'">'+u.get('name')+'</option>';
		})						
		
		$('.unit_add').html(option);
		$('.name_add').focus();
	});
	
	
	$('.add_product').click(function() {
		$(".forms .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		products.add([{name: $('.name_add').val(), unit: $('.unit_add').val()}]);
		
		return false;
	})
	
    $('.del').click(function(){
		return confirm ("Будте осторожны, будут также удалены все связанные продукты.\r\nВы действительно хотите удалить элемент?");
	});
	
    $('.del_supplier_product').click(function(){
		return confirm ("Вы действительно хотите удалить элемент?");
	});
	
	$('.sort').toggle(function() {
		sort = 'desc';		
	    view_products.remove()
	    view_products = new ViewProducts({collection: products});
	    $('#product_list').append(view_products.render().el);
	    view_products.renderAll()
		
		$('i', this).attr('class','icon-arrow-down');
		return false;
	}, function() {
		
		sort = 'asc';		
	    view_products.remove()
	    view_products = new ViewProducts({collection: products});
	    $('#product_list').append(view_products.render().el);
	    view_products.renderAll()

		$('i', this).attr('class','icon-arrow-up');
		return false;
	});
	
	$(document).keydown(function(e) {
		if (e.keyCode == 27) {
			if(!$("#myModalDialog").length) {
				view_products.renderAll();
			}
		}
	});
	
	$('body').on('click', '.activate_old_product', function(){
		products.add(	{ id: parseInt($('#dialog_id').val()), name: $('#dialog_name').val(), unit: parseInt($('#dialog_unit').val()), active:1 },
						{ silent:true}	);
					
		products._byId[parseInt($('#dialog_id').val())].save();
		$('.name_add').val('');
		$("#myModalDialog").modal('hide');
	});
	
	$('body').on('click', '.create_new_product', function(){
		products.add(	{ name: $('#dialog_name').val(), unit: parseInt($('#dialog_unit').val()), active:1 });
					
		//products._byId[parseInt($('#dialog_id').val())].save();
		$('.name_add').val('');
		$("#myModalDialog").modal('hide');
	});
})
