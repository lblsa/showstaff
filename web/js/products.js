/****************************************
 * Products
 ****************************************/
var units = {1:'кг', 
			 2:'литр',
			 3:'шт',
			 4:'пучок',
			 5:'бутылка'};

var sort = 'asc';

/*
Backbone.emulateHTTP = true;
Backbone.emulateJSON = true;
*/

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
			this.$('.products').html('');
			this.collection.each(function(model){
				var view = new ViewProduct({model:model});
				var content = view.render().el;
				if (sort == 'desc')
					this.$('.products').prepend(content);
				else
					this.$('.products').append(content);
			});
			
		} else {
			$('.products').html('<tr class="alert_row"><td colspan="2"><div class="alert">'+
								'<button type="button" class="close" data-dismiss="alert">×</button>'+
								'У вас еще нет продуктов</div></td></tr>');
			$('#preloader').fadeOut('fast');
		}
	},
});

// view one product
var ViewProduct = Backbone.View.extend({
	
	tagName: "tr",
	className: "product",
	
	template: _.template(	'<td class="p_name" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit"><%= name %></td>'+
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
		$('.product').tooltip();
		$('#preloader').fadeOut('fast'); 
		return this;
	},
	
	edit: function() {
		$('.p_name', this.el).html('<input type="text" class="input-small name" name="name" value="">');
		$('.p_name input', this.el).val(this.model.get('name'));
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
		this.preloader();
		this.model.save({
						name: $('.name', this.el).val(), 
						unit: $('.unit', this.el).val()
						},{wait: true});
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


// Model products
var ProductsModel = Backbone.Model.extend({  
  
  methodUrl:  function(method){
	if(method == "delete"){
			return "/product/" + this.attributes.id+"/delete";
		}else if(method == "update"){
			return "/product/" + this.attributes.id+"/update";
		}else if(method == "create"){
			return "/product/create/ajax";
		} 
		return false;
  },

  sync: function(method, model, options) {
        var productOptions = options;
        
        if (method == 'delete') {
			productOptions.success = function(resp, status, xhr) {
				$('#preloader').fadeOut('fast');
				if (resp != null && typeof(resp.data) != 'undefined' && resp.data == model.id) {
					$(model.view.el).remove();
					model.collection.remove(model, {silent: true});
				   
				   var SP = {};
				   $('#close_all').click();
				   
					return;
				} else {
					
				   $('.p_unit', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка удаления! Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				}
				return options.success(resp, status, xhr);
			};
			
			productOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
			
			productOptions.url = 'product/'+this.attributes.id;
		}
        
        if (method == 'update') {
			productOptions.success = function(resp, status, xhr) {
				if (resp != null && typeof(resp.message) != 'undefined') {
				   $('#preloader').fadeOut('fast'); 
				   $('.p_unit .alert', model.view.el).remove();
				   $('.p_unit', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка (' + resp.message + '). '+
													'Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				} else {
				   if (resp != null && typeof(resp.data) != 'undefined') {
					   model.set(resp.data,{silent: true});
					   model.view.render();
					   
					   var SP = {};
				       $('#close_all').click();
					   
					   //  for sort reload
					   products.sort({silent: true});
					   
					   view_products.remove()
					   view_products = new ViewProducts({collection: products});
					   $('#product_list').append(view_products.render().el);
					   view_products.renderAll()
					   
					   return;
				   } else {
					   $('.p_unit .alert', model.view.el).remove();
					   $('#preloader').fadeOut('fast'); 
					   $('.p_unit', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка. Попробуйте еще раз или обратитесь к администратору.</div>');
					   return;
				   }
				}
				return options.success(resp, status, xhr);
			};
			
			productOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
			
			productOptions.url = 'product/'+this.attributes.id;
			
		}
		
		if (method == 'create') {
			productOptions.success = function(resp, status, xhr) {
				if (resp != null && typeof(resp.message) != 'undefined' ) {

				   $('#preloader').fadeOut('fast'); 
				   $('.alert-error strong').html(' (' + resp.message + '). ');
				   $(".alert-error").clone().appendTo('#form_add');
				   $('#form_add .alert-error').fadeIn();
				   products.remove(model, {silent:true});
				   return;
				   
				} else {
					
				   if (resp != null && typeof(resp.data) != 'undefined') {
				   
					   model.set(resp.data, {silent:true});
					   var view = new ViewProduct({model:model});
					   var content = view.render().el;
					   $('.products').prepend(content);
					   $('.product').tooltip();  
					   $('.name_add').val('');
					   $(".alert-success").clone().appendTo('#form_add');
					   $("#form_add .alert-success").fadeIn();
					   
					   var SP = {};
					   $('#close_all').click();
					   
					   
					   //  for sort reload
					   view_products.remove()
					   view_products = new ViewProducts({collection: products});
					   $('#product_list').append(view_products.render().el);
					   view_products.renderAll()
					   return;
				   } else {
					   
					   $('#preloader').fadeOut('fast'); 
					   $('.alert-error strong').html(' (Некорректный ответ сервера). ');
					   $(".alert-error").clone().appendTo('#form_add');
					   $('#form_add .alert-error').fadeIn();
					   products.remove(model, {silent:true});   
					   return;
				   }
				   
				}
				return options.success(resp, status, xhr);
			};
			productOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
		}
		
		Backbone.sync.call(this, method, model, productOptions);
   }
});




/****************************************
 * 
 ***************************************/
	


//view_products.on('sync', function() { alert('eee'); })
 
$(document).ready(function(){
	
	$('.create').toggle(function() {
		$('i', this).attr('class', 'icon-minus-sign');
		var option = '';
		for(var key in units) {
			option += '<option value="'+key+'" >'+units[key]+'</option>';
		}
		$("#form_add .alert").remove();
		$('.name_add').val('');
		$('#form_add').slideDown();
		$('.unit_add').html(option);
		$('.name_add').focus();
		return false;
	}, function() {
		$('i', this).attr('class', 'icon-plus-sign');
		$('#form_add').slideUp();
		return false;
	});
	
	$('.add_product').click(function() {
		$("#form_add .alert").remove();
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

})
