//* update 2012-10-18 16:15:00 *//
/****************************************
 * Suppliers
 ****************************************/
var sort = 'asc';
var view_suppliers;
$(function(){
	// view list supplier
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
			
			if (this.collection.length > 0) {
				$('.suppliers').html('');
				this.collection.each(function(model){
					var view = new ViewSupplier({model:model});
					var content = view.render().el;
					if (sort == 'desc')
						$('.suppliers').prepend(content);
					else
						$('.suppliers').append(content);
				});
				
			} else {
				$('.suppliers').html('<tr class="alert_row"><td colspan="2"><div class="alert">'+
									'<button type="button" class="close" data-dismiss="alert">×</button>'+
									'У вас еще нет поставщиков</div></td></tr>');
				$('#preloader').fadeOut('fast');
			}
		},
	});

	// view one supplier
	var ViewSupplier = Backbone.View.extend({
		
		tagName: "tr",
		className: "supplier",
		
		template: _.template(	'<td class="p_name">'+
									'<input type="text" class="input name" name="name" value="<%= name %>">'+
									'<a href="supplier/<%= id %>/product" class="link pull-right ">Продукты поставщика</a>'+
								'</td>'+
								'<td class="p_unit">'+
									'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
								'</td>'),
		
		events: {
			'change input.name':  'save',
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
			$('#preloader').fadeOut('fast'); 
			return this;
		},
		
		save: function() {
			this.preloader();
			this.model.save({	name: $('.name', this.el).val() 	},{wait: true});
		},
		
		cancel: function() {
			return this.render().el;
		},

		remove: function() {
			if ( confirm ("Будте осторожны, будут также удалены все связанные продукты.\r\nВы действительно хотите удалить элемент?") ) {
				this.preloader();
				this.model.destroy({wait: true });
			}
			return false;
		},
		
	})


	// Model supplier
	var SupplierModel = Backbone.Model.extend({

	  sync: function(method, model, options) {
			var supplierOptions = options;
			
			supplierOptions.url = '/api/company/'+href[2]+'/supplier/'+this.attributes.id;
			
			if (method == 'delete') {
				supplierOptions.success = function(resp, status, xhr) {
					$('#preloader').fadeOut('fast');
					if (resp != null && typeof(resp.data) != 'undefined' && resp.data == model.id) {
						$(model.view.el).remove();
						model.collection.remove(model, {silent: true});
					} else {
						
					   $('.p_unit', model.view.el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Ошибка удаления! Попробуйте еще раз или обратитесь к администратору.</div>');
					}
				};				
			}
			
			if (method == 'update') {
				supplierOptions.success = function(resp, status, xhr) {
				   model.set(resp.data,{silent: true});
				   model.view.render();			   
				   //  for sort reload
				   suppliers.sort({silent: true});
				   
				   view_suppliers.remove()
				   view_suppliers = new ViewSuppliers({collection: suppliers});
				   $('#supplier_list').append(view_suppliers.render().el);
				   view_suppliers.renderAll();
				};
			}
			
			if (method == 'create') {
				
				supplierOptions.url = '/api/company/'+href[2]+'/supplier';
				
				supplierOptions.success = function(resp, status, xhr) {
				   model.set(resp.data, {silent:true});
				   var view = new ViewSupplier({model:model});
				   var content = view.render().el;
				   $('.suppliers').prepend(content); 
				   $('.name_add').val('');
				   $(".alert-success").clone().appendTo('.forms');
				   $(".forms .alert-success strong").html('Поставщик успешно создан');
				   $(".forms .alert-success").fadeIn();

				   //  for sort reload
				   view_suppliers.remove();
				   view_suppliers = new ViewSuppliers({collection: suppliers});
				   $('#supplier_list').append(view_suppliers.render().el);
				   view_suppliers.renderAll();
				};
				
				supplierOptions.error = function(jqXHR, textStatus, errorThrown) {
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
					suppliers.remove(model, {silent:true});
				}
			}
			
			Backbone.sync.call(this, method, model, supplierOptions);
	   }
	});
	
	// Collection supplier
	var Suppliers = Backbone.Collection.extend({
	  
		model: SupplierModel,

		url: '/api/company/'+href[2]+'/supplier',

		initialize: function(){
			this.bind('add', this.addSupplier);
			
			$('#preloader').width($('#add_row').width());
			$('#preloader').height($('#add_row').height());
			var p = $('#add_row').position();
			$('#preloader').css({'left':p.left, 'top': p.top});
			$('#preloader').fadeIn('fast');
		},		  
		  
		parse: function(response) {
		
			if(response.code && response.data  && (response.code == 200)){
				return response.data;
			} else {
				$('.suppliers').html('<tr class="alert_row"><td colspan="2"><div class="alert">'+
									'<button type="button" class="close" data-dismiss="alert">×</button>'+
									'У вас еще нет поставщиков</div></td></tr>');
				$('#preloader').fadeOut('fast');
			}
		},

		addSupplier: function(supplier){
			supplier.save({wait: true});
		},
	  
	});
	
	var suppliers = new Suppliers; // init collection

	view_suppliers = new ViewSuppliers({collection: suppliers}); // initialize view

	suppliers.comparator = function(supplier) {
	  return supplier.get("name");
	};
	
	suppliers.fetch({	error:function(){
								$('.suppliers').html('<tr class="alert_row"><td colspan="2"><div class="alert">'+
												'<button type="button" class="close" data-dismiss="alert">×</button>'+
												'Некорректный ответ, обновите страницу или обратитесь к администратору</div></td></tr>');
								$('#preloader').fadeOut('fast');	
							},
						success:function(){ }
						});
						
	if ($('#refreshed').val()=="yes") {
		suppliers.fetch({	error:function(){
									$('.suppliers').html('<tr class="alert_row"><td colspan="2"><div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Некорректный ответ, обновите страницу или обратитесь к администратору</div></td></tr>');
									$('#preloader').fadeOut('fast');	
								},
							success:function(){ }
							});
	}

	$('#supplier_list').append(view_suppliers.render().el); // add template
	
	$('.add_supplier').click(function() {
		$(".forms .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		suppliers.add([{name: $('.name_add').val()}]);
		
		return false;
	})
	
	$('.sort').toggle(function() {
		sort = 'desc';		
	    view_suppliers.remove()
	    view_suppliers = new ViewSuppliers({collection: suppliers});
	    $('#supplier_list').append(view_suppliers.render().el);
	    view_suppliers.renderAll()
		
		$('i', this).attr('class','icon-arrow-down');
		return false;
	}, function() {		
		sort = 'asc';		
	    view_suppliers.remove()
	    view_suppliers = new ViewSuppliers({collection: suppliers});
	    $('#supplier_list').append(view_suppliers.render().el);
	    view_suppliers.renderAll()

		$('i', this).attr('class','icon-arrow-up');
		return false;
	});

	$(document).keydown(function(e) {
		if (e.keyCode == 27) view_suppliers.renderAll();
	});
})
