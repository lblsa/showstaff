/****************************************
 * Booking Product
 ****************************************/

var sort = 'asc';

var ViewBooking = Backbone.View.extend({
	tagName: "tr",
	className: "product",
   
	template: _.template(	'<td class="ps_name" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit"><% print(products._byId[product].attributes.name); %></td>'+
										'<td class="ps_amount"><%= amount %></td>'+
										'<td class="ps_unit"><% print(units[products._byId[product].attributes.unit]); %>'+
											'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
										'</td>'),

	events: {
		'dblclick .ps_name': 'edit',
		'click .remove'	: 'remove',
		'click .cancel'	: 'cancel',
		'click .save'	: 'save',
	},
	
	initialize: function() {
		this.model.view = this;
	},
	
	render: function(){
		//console.log(this);
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		$('#preloader').fadeOut('fast');
		return this;
	},
	
	preloader: function() {
		$('#preloader').width(this.$el.width());
		$('#preloader').height(this.$el.height());
		var p = this.$el.position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
	},
	
	edit: function() {
		$('.ps_name', this.el).html('<select class="product_edit span3"></select>');
		
		products._byId[this.model.get('product')].attributes.use = 0;
		
		products.each(function(p){
			if (p.attributes.use == 0) {
				var view = new OptionProducts({model:p});
				$('.product_edit', this.el).append(view.render().el);
			}
        });
		$('.product_edit option[value="'+this.model.get('product')+'"]', this.el).attr('selected', 'selected')
		
		$('.ps_amount', this.el).html('<input type="text" class="input-small amount" name="amount" value="">');
		$('.ps_amount input', this.el).val(this.model.get('amount'));
		
		$('.ps_unit', this.el).html('<p class="form-inline">'+
									' <a class="save btn btn-mini btn-success">save</a>'+
									' <a class="cancel btn btn-mini btn-danger">cancel</a></p>');
	},
	
	remove: function() {
		if ( confirm ("Вы действительно хотите удалить продукт из заказа?") ) {
			this.preloader();
			this.model.destroy({wait: true });
		}
		return false;
	},
	
	cancel: function() {
		
		
	},
	
	save: function() {
		this.preloader();
		this.model.save({
						product: $('.product_edit', this.el).val(),
						name: products._byId[$('.product_edit').val()].attributes.name,
						amount: $('.amount', this.el).val(),
						},{wait: true});
	},
	
	
    
})


var ViewBookings = Backbone.View.extend({
   
	tagName: "tbody",
	className: "bookings",
	
	initialize: function() {
		_.bindAll(this);
		this.collection.on('reset', this.renderAll);
	},
    
	render: function() {
		return this;
	},
	
	renderAll: function() {
		$('#preloader').hide();
		this.renderProducts();
		return this;
	},
    
    renderProducts: function() {
		if (this.collection.length > 0) {
			
			$('.bookings').html('');
			this.collection.each(function(model){
				var view = new ViewBooking({model:model});
				var content = view.render().el;
				
				if (sort == 'desc')
					$('.bookings').prepend(content);
				else
					$('.bookings').append(content);
			});
		
		} else {
		
			this.$('.bookings').append('<tr class="alert_row"><td colspan="3"><div class="alert">'+
												'<button type="button" class="close" data-dismiss="alert">×</button>'+
												'У данного поставщика еще нет продуктов</div></td></tr>');
		}
		
		return this;
    },
	
	sort_by_name: function() {
		var list = new Backbone.Collection;
		list.comparator = function(chapter) {
		  return chapter.get("name");
		};
		
		if (sort == 'asc') {
			sort = 'desc';
			$('.sort_by_name i').attr('class','icon-arrow-down');
		} else {
			sort = 'asc';
			$('.sort_by_name i').attr('class','icon-arrow-up');
		}
		
		list.reset(bookings.models, {silent:true})
		this.collection = list;
		
		this.renderProducts();
		return false;
	},
	
	sort_by_amount: function() {
		var list = new Backbone.Collection;
		list.comparator = function(chapter) {
		  return chapter.get("amount");
		};
		
		if (sort == 'asc') {
			sort = 'desc';
			$('.sort_by_amount i').attr('class','icon-arrow-down');
		} else {
			sort = 'asc';
			$('.sort_by_amount i').attr('class','icon-arrow-up');
		}
		
		list.reset(bookings.models, {silent:true})
		this.collection = list;
		
		this.renderProducts();
		return false;
	},
    
})

// Model booking products
var BookingModel = Backbone.Model.extend({
  
  sync: function(method, model, options) {
       var BookingOptions = options;
       
        if (method == 'delete') {
			BookingOptions.success = function(resp, status, xhr) {
				$('#bookin_list .alert').remove();
				$('#preloader').fadeOut('fast');
				
				if (resp.data == model.id) {
					
					products._byId[model.attributes.product].attributes.use = 0;
					$('.product_add').html('');
					products.each(function(p){
						if (p.attributes.use == 0) {
							var view = new OptionProducts({model:p});
							$('.product_add').append(view.render().el);
						}
					});
					
					if ($('.product_add option').length > 0)
        				$('.create, #form_add').fadeIn();
					
					$(model.view.el).remove();
					model.collection.remove(model, {silent: true});
					return;
				} else {
					
					$('.ps_unit', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка удаления! Попробуйте еще раз или обратитесь к администратору.</div>');
													
					if (resp != null && typeof(resp.message) != 'undefined')
						$('.ps_unit .alert').append('<br>('+resp.message+')');
					
					return;
				}
				return options.success(resp, status, xhr);
			};
			BookingOptions.error = function(resp, status, xhr) {			
				return options.success(resp, status, xhr);
			}
		}

        if (method == 'update') {
			BookingOptions.success = function(resp, status, xhr) {
								
				$('#bookin_list .alert').remove();
				$('#preloader').fadeOut('fast');
				
				if (resp != null && typeof(resp.message) != 'undefined') {
					
				   $('#preloader').fadeOut('fast'); 
				   $('.ps_unit', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка (' + resp.message + '). '+
													'Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				} else {
					if (resp != null && typeof(resp.data) != 'undefined') {
						
						if (resp.data.product != model.attributes.product ) {
							
							products._byId[model.attributes.product].attributes.use = 0;
							products._byId[resp.data.product].attributes.use = 1;
							$('.product_add').html('');
							products.each(function(p){
								if (p.attributes.use == 0) {
									var view = new OptionProducts({model:p});
									$('.product_add').append(view.render().el);
								}
							});
						}
						
						model.set(resp.data,{silent: true});
						model.view.render();

						bookings.sort({silent: true});
						view_content.remove()
						view_content = new ViewBookings({collection: bookings});
						$('#bookin_list').append(view_content.render().el);
						view_content.renderAll().el
					
						return;
				   } else {
					   $('#preloader').fadeOut('fast'); 
					   $('.ps_unit', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка. Попробуйте еще раз или обратитесь к администратору.</div>');
					   model.set(model.previousAttributes(),{silent: true});
					   return;
				   }
				}
				return options.success(resp, status, xhr);
			};
			BookingOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
		}

		if (method == 'create') {
			BookingOptions.success = function(resp, status, xhr) {
				
				$('#bookin_list .alert').remove();
				$('#preloader').fadeOut('fast');
				
				if (resp != null && typeof(resp.data) != 'undefined') {
					
					model.set(resp.data, {silent:true});
					
					products._byId[resp.data.product].attributes.use = 1;
					$('.product_add option[value="'+resp.data.product+'"]').remove();
					
					if ($('.product_add option').length == 0)
        				$('.create, #form_add').fadeOut();
					
					var view = new ViewBooking({model:model});
					var content = view.render().el;
					$('#bookin_list .bookings').prepend(content);
					$("#up .alert-success").clone().appendTo('#bookin_list #form_add');
					$('#bookin_list #form_add .alert-success').fadeIn();
				    
					$('#bookin_list .amount_add').val('');

					bookings.sort({silent: true});
					view_content.remove()
					view_content = new ViewBookings({collection: bookings});
					$('#bookin_list').append(view_content.render().el);
					view_content.renderAll().el
				   
				   return;
				   
				} else {
					
					
					if (resp != null && typeof(resp.message) != 'undefined')
						$('#up .alert-error strong').html(''+resp.message);
						
				   $("#up .alert-error").clone().appendTo('#form_add');
				   $('#bookin_list .alert-error').fadeIn();
					bookings.remove(model, {silent:true});   
				
				   return;
				}
				return options.success(resp, status, xhr);
			};
			BookingOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
		}
		
		
		Backbone.sync.call(this, method, model, BookingOptions);
  },
});


/**********************************************
 * Option Product for add/edit Supplier Product
 **********************************************/
var OptionProducts = Backbone.View.extend({
	
	tagName: "option",
	
	template: _.template('<%= name %> [ <% print(units[unit]); %> ]'),
	
	render: function() {
		var content = this.template(this.model.toJSON());
		this.$el.html(content);
		this.$el.attr('value', this.model.id)
		return this;
	},
	
})

$(document).ready(function(){

    $('.create').click(function(){
		$('.product_add').html('');
        products.each(function(p){
			if (p.attributes.use == 0) {
				var view = new OptionProducts({model:p});
				$('.product_add').append(view.render().el);
			}
        });
        
        if ($('.product_add option').length == 0)
        	$('.create, #form_add').fadeOut();
    })
    
	$('.product_add').html('');
    products.each(function(p){
		if (p.attributes.use == 0) {
			var view = new OptionProducts({model:p});
			$('.product_add').append(view.render().el);
		}
    });
    
    if ($('.product_add option').length == 0)
    	$('.create, #form_add').fadeOut();
    
    
	$('.add_booking').click(function(){
		
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		
		bookings.add([{
						product: $('.product_add').val(),
						amount: $('.amount_add').val(),
						name: products._byId[$('.product_add').val()].attributes.name,
					}],{wait: true});
		return false;
	});

	$('.datepicker').datepicker({"format": "yyyy-mm-dd"})
		.on('changeDate', function(ev){
			$('#link_to_date').attr( 'href', $('.datepicker').val() );
	});
	
	$('.sort_by_name').click(function(){
		view_content.sort_by_name();
		return false;
	});
	
	$('.sort_by_amount').click(function(){
		view_content.sort_by_amount();
		return false;
	});
	
})