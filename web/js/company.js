/****************************************
 * Companies
 ****************************************/

var sort = 'asc';

// view list companies
var ViewCompanies = Backbone.View.extend({
	
	tagName: "tbody",
	className: "companies",
	
	initialize: function() {
		_.bindAll(this);
		this.collection.on('reset', this.renderAll);
	},
	
	render: function() {
		return this;
	},
	
	renderAll: function() {
		
		if (this.collection.length > 0) {			
			$('.companies').html('');
			this.collection.each(function(model){
				var view = new ViewCompany({model:model});
				var content = view.render().el;
				if (sort == 'desc')
					this.$('.companies').prepend(content);
				else
					this.$('.companies').append(content);
			});
			
		} else {
			$('.companies').html('<tr class="alert_row"><td colspan="4"><div class="alert">'+
								'<button type="button" class="close" data-dismiss="alert">×</button>'+
								'У вас еще нет компаний</div></td></tr>');
			$('#preloader').fadeOut('fast');
		}
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
		
		list.reset(companies.models, {silent:true})
		this.collection = list;
		
		this.renderAll();
		return false;
	},
	
	sort_by_inn: function() {
		var list = new Backbone.Collection;
		list.comparator = function(chapter) {
		  return chapter.get("inn");
		};

		if (sort == 'asc') {
			sort = 'desc';
			$('.sort_by_inn i').attr('class','icon-arrow-down');
		} else {
			sort = 'asc';
			$('.sort_by_inn i').attr('class','icon-arrow-up');
		}
		
		list.reset(companies.models, {silent:true})
		this.collection = list;	
		
		this.renderAll();
		return false;
	},
	
	sort_by_exname: function() {
		var list = new Backbone.Collection;
		list.comparator = function(chapter) {
		  return chapter.get("extended_name");
		};
		
		if (sort == 'asc') {
			sort = 'desc';
			$('.sort_by_prime i').attr('class','icon-arrow-down');
		} else {
			sort = 'asc';
			$('.sort_by_prime i').attr('class','icon-arrow-up');
		}
		
		list.reset(companies.models, {silent:true})
		this.collection = list;	
		
		this.renderAll();
		return false;
	}
});

// view one company
var ViewCompany = Backbone.View.extend({
	
	tagName: "tr",
	className: "company",
	
	template: _.template(	'<td class="p_name" rel="tooltip" data-placement="bottom" data-original-title="Double click for edit">'+
								'<%= name %>'+
							'</td>'+
							'<td class="p_extended_name"><%= extended_name %></td>'+
							'<td class="p_inn">'+
								'<%= inn %> '+
								'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a>'+
							'</td>'+
							'<td class="p_link">'+
								'<a href="/company/<%= id %>/product" class="link">Продукты компании</a> &nbsp;|&nbsp;'+
								' <a href="/company/<%= id %>/supplier" class="link">Поставщики компании</a>&nbsp;|&nbsp; '+
								' <a href="/company/<%= id %>/restaurant" class="link">Рестораны компании</a><br> '+
								' <a href="/company/<%= id %>/order" class="link">Заказы компании</a>&nbsp;|&nbsp; '+
								' <a href="/company/<%= id %>/user" class="link">Менеджеры компании</a>'+
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
		$('.company').tooltip();
		$('#preloader').fadeOut('fast'); 
		return this;
	},
	
	edit: function() {
		$('.p_name', this.el).html('<input type="text" class="input-small name" tabindex="1" name="name" value="">');
		$('.p_name input', this.el).val(this.model.get('name'));

		$('.p_extended_name', this.el).html('<input type="text" class="extended_name" tabindex="2" name="extended_name" value="">');
		$('.p_extended_name input', this.el).val(this.model.get('extended_name'));
		
		$('.p_inn', this.el).html('<input type="text" class="inn" name="inn" tabindex="3" value="">');
		$('.p_inn input', this.el).val(this.model.get('inn'));
		
		
		$('.p_inn', this.el).append('<p class="form-inline">'+
									'<button class="save btn btn-mini btn-success" tabindex="4">save</button> '+
									' <button class="cancel btn btn-mini btn-danger" tabindex="5">cancel</button></p>');
	},
	
	save: function() {
		this.preloader();
		this.model.save({	name: $('.name', this.el).val(), 
							extended_name: $('.extended_name', this.el).val(), 
							inn: $('.inn', this.el).val(), 	
						},{wait: true});
	},
	
	cancel: function() {
		return this.render().el;
	},

	remove: function() {
		if ( confirm ("Будте осторожны, будут также удалены все связанные элементы.\r\nВы действительно хотите удалить элемент?") ) {
			this.preloader();
			this.model.destroy({wait: true });
		}
		return false;
	},
	
})

// Model company
var CompanyModel = Backbone.Model.extend({

  sync: function(method, model, options) {
        var companyOptions = options;
        
        if (method == 'delete') {
			companyOptions.success = function(resp, status, xhr) {
				$('#preloader').fadeOut('fast');
				if (resp != null && typeof(resp.data) != 'undefined' && resp.data == model.id) {
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
			
			companyOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
			
			companyOptions.url = 'company/'+this.attributes.id;
		}
        
        if (method == 'update') {
			companyOptions.success = function(resp, status, xhr) {
				if (resp != null && typeof(resp.message) != 'undefined') {
				   $('#preloader').fadeOut('fast'); 
				   $('.p_inn .alert', model.view.el).remove();
				   $('.p_inn', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка (' + resp.message + '). '+
													'Попробуйте еще раз или обратитесь к администратору.</div>');
				   return;
				} else {
				   if (resp != null && typeof(resp.data) != 'undefined') {
					   model.set(resp.data,{silent: true});
					   model.view.render();			   
					   //  for sort reload
					   companies.sort({silent: true});
					   
					   view_companies.remove()
					   view_companies = new ViewCompanies({collection: companies});
					   $('#companies_list').append(view_companies.render().el);
					   view_companies.renderAll()
					   
					   return;
				   } else {
					   $('.p_inn .alert', model.view.el).remove();
					   $('#preloader').fadeOut('fast'); 
					   $('.p_inn', model.view.el).append('<div class="alert">'+
													'<button type="button" class="close" data-dismiss="alert">×</button>'+
													'Ошибка. Попробуйте еще раз или обратитесь к администратору.</div>');
					   return;
				   }
				}
				return options.success(resp, status, xhr);
			};
			
			companyOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
			
			companyOptions.url = 'company/'+this.attributes.id;
			
		}
		
		if (method == 'create') {
			companyOptions.success = function(resp, status, xhr) {
				if (resp != null && typeof(resp.message) != 'undefined' ) {

				   $('#preloader').fadeOut('fast'); 
				   $('.alert-error strong').html(' (' + resp.message + '). ');
				   $(".alert-error").clone().appendTo('#form_add');
				   $('#form_add .alert-error').fadeIn();
				   companies.remove(model, {silent:true});
				   return;
				   
				} else {
					
				   if (resp != null && typeof(resp.data) != 'undefined') {
				   
					   model.set(resp.data, {silent:true});
					   var view = new ViewCompany({model:model});
					   var content = view.render().el;
					   $('.companies').prepend(content);
					   $('.company').tooltip();  
					   $('.name_add').val('');
					   $('.extended_name').val('');
					   $('.inn_add').val('');
					   $(".alert-success").clone().appendTo('#form_add');
					   $("#form_add .alert-success strong").html('Компания успешно создана');
					   $("#form_add .alert-success").fadeIn()

					   //  for sort reload
					   view_companies.remove()
					   view_companies = new ViewCompanies({collection: companies});
					   $('#companies_list').append(view_companies.render().el);
					   view_companies.renderAll()
					   return;
				   } else {
					   
					   $('#preloader').fadeOut('fast'); 
					   $('.alert-error strong').html(' (Некорректный ответ сервера). ');
					   $(".alert-error").clone().appendTo('#form_add');
					   $('#form_add .alert-error').fadeIn();
					   companies.remove(model, {silent:true});   
					   return;
				   }
				   
				}
				return options.success(resp, status, xhr);
			};
			companyOptions.error = function(resp, status, xhr) {
				return options.success(resp, status, xhr);
			}
		}
		
		Backbone.sync.call(this, method, model, companyOptions);
   }
});

$(document).ready(function(){
	$('.add_company').click(function() {
		$("#form_add .alert").remove();
		$('#preloader').width($('#add_row').width());
		$('#preloader').height($('#add_row').height());
		var p = $('#add_row').position();
		$('#preloader').css({'left':p.left, 'top': p.top});
		$('#preloader').fadeIn('fast');
		companies.add([{
						name: $('.name_add').val(),
						extended_name: $('.extended_name_add').val(),
						inn: $('.inn_add').val(),
						}]);
		
		return false;
	})
	
	$('.sort_by_exname').click(function(){
		view_companies.sort_by_exname();
		return false;
	});
	$('.sort_by_inn').click(function(){
		view_companies.sort_by_inn();
		return false;
	});
	$('.sort_by_name').click(function(){
		view_companies.sort_by_name();
		return false;
	});
});
