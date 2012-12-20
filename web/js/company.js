//* update 2012-10-18 16:15:00 *//
/****************************************
 * Companies
 ****************************************/
var sort = 'asc';
var Companies, companies, view_companies, CompanyModel, ViewCompany, ViewCompanies;
$(function(){
	// view list companies
	ViewCompanies = Backbone.View.extend({
		
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
	ViewCompany = Backbone.View.extend({
		
		tagName: "tr",
		className: "company",
		
		template: _.template(	'<td class="p_name">'+
									'<input type="text" class="name" tabindex="1" name="name" value="<%= name %>">'+
								'</td>'+
								'<td class="p_extended_name">'+
									'<input type="text" class="extended_name" tabindex="2" name="extended_name" value="<%= extended_name %>">'+
								'</td>'+
								'<td class="p_inn">'+
									'<input type="text" class="inn" name="inn" tabindex="3" value="<%= inn %>">'+
								'</td>'+
								'<td>'+
									'<a href="#" class="btn btn-mini pull-right remove"><i class="icon-remove-circle"></i></a> '+
								'</td>'),
		
		events: {
			"change input.name":  "save",
			"change input.extended_name":  "save",
			"change input.inn":  "save",
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
	CompanyModel = Backbone.Model.extend({

	  sync: function(method, model, options) {
			var companyOptions = options;
			
			companyOptions.url = '/api/company/'+this.attributes.id;
			
			if (method == 'delete') {
				companyOptions.success = function(resp, status, xhr) {
					$('#preloader').fadeOut('fast');
					if (resp != null && typeof(resp.data) != 'undefined' && resp.data == model.id) {
						$(model.view.el).remove();
						model.collection.remove(model, {silent: true});
					} else {
						
					   $('.p_inn', model.view.el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Ошибка удаления! Попробуйте еще раз или обратитесь к администратору.</div>');
					}
				};
				
				companyOptions.error = function(jqXHR, textStatus, errorThrown) {
					$('#preloader').fadeOut('fast');
					
					
					$('.p_inn .alert', model.view.el).remove();
					
					if (typeof(jqXHR) != 'undefined' && typeof(jqXHR.responseText) != 'undefined')
						$('.p_inn', model.view.el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Ошибка (' + jqXHR.responseText + '). '+
														'Попробуйте еще раз или обратитесь к администратору.</div>');
					else
						$('.p_inn', model.view.el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Ошибка (Некорректный ответ сервера). '+
														'Попробуйте еще раз или обратитесь к администратору.</div>');
				}
			}
			
			if (method == 'update') {
				companyOptions.success = function(resp, status, xhr) {
					if (resp != null && typeof(resp.message) != 'undefined') {
					   $('#preloader').fadeOut('fast'); 
					   
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
						   
					   } else {
						   $('.p_inn .alert', model.view.el).remove();
						   $('#preloader').fadeOut('fast'); 
						   $('.p_inn', model.view.el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Ошибка. Попробуйте еще раз или обратитесь к администратору.</div>');
					   }
					}
				};
				
				companyOptions.error = function(jqXHR, textStatus, errorThrown) {
					$('#preloader').fadeOut('fast');
					model.view.render();
					
					$('.p_inn .alert', model.view.el).remove();
					
					if (typeof(jqXHR) != 'undefined' && typeof(jqXHR.responseText) != 'undefined')
						$('.p_inn', model.view.el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Ошибка (' + jqXHR.responseText + '). '+
														'Попробуйте еще раз или обратитесь к администратору.</div>');
					else
						$('.p_inn', model.view.el).append('<div class="alert">'+
														'<button type="button" class="close" data-dismiss="alert">×</button>'+
														'Ошибка (Некорректный ответ сервера). '+
														'Попробуйте еще раз или обратитесь к администратору.</div>');
						
					$('.forms .alert-error').fadeIn();
					
				}				
			}
			
			if (method == 'create') {
				companyOptions.success = function(resp, status, xhr) {		
				   if (resp != null && typeof(resp.data) != 'undefined') {
				   
					   model.set(resp.data, {silent:true});
					   var view = new ViewCompany({model:model});
					   var content = view.render().el;
					   $('.companies').prepend(content);
					   $('.name_add').val('');
					   $('.extended_name_add').val('');
					   $('.inn_add').val('');
					   $(".alert-success").clone().appendTo('.forms');
					   $(".forms .alert-success strong").html('Компания успешно создана');
					   $(".forms .alert-success").fadeIn()

					   //  for sort reload
					   view_companies.remove()
					   view_companies = new ViewCompanies({collection: companies});
					   $('#companies_list').append(view_companies.render().el);
					   view_companies.renderAll()
				   } else {
					   
					   $('#preloader').fadeOut('fast'); 
					   $('.alert-error strong').html(' (Некорректный ответ сервера). ');
					   $(".alert-error").clone().appendTo('.forms');
					   $('.forms .alert-error').fadeIn();
					   companies.remove(model, {silent:true});
				   
				   }
				};
				companyOptions.error = function(jqXHR, textStatus, errorThrown) {
					$('#preloader').fadeOut('fast'); 
					if (typeof(jqXHR) != 'undefined' && typeof(jqXHR.responseText) != 'undefined')
						$('.alert-error strong').html(' (' + jqXHR.responseText + '). ');
					else
						$('.alert-error strong').html(' (Некорректный ответ сервера). ');
						
					$(".alert-error").clone().appendTo('.forms');
					$('.forms .alert-error').fadeIn();
					companies.remove(model, {silent:true});
				}
				companyOptions.url = '/api/company';
			}
			
			Backbone.sync.call(this, method, model, companyOptions);
	   }
	});


	$('.add_company').click(function() {
		$(".forms .alert").remove();
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
	
	
	$(document).keydown(function(e) {
		if (e.keyCode == 27) view_companies.renderAll();
	});
			
	// Collection products
	Companies = Backbone.Collection.extend({
	  
	  model: CompanyModel,
	  
	  url: 'api/company',
	  
	  initialize: function(){
		  this.bind('add', this.addCompany);
	  },
	  
	  addCompany: function(company){
		company.save({wait: true});
	  },		

	  parse: function(response, xhr){

	  	$('#preloader').fadeOut('fast');

		if(response && 'code' in response && response.code == 200 && 'data' in response) {
			return response.data;
		} else {
			error_fetch('Ошибка. Обновите страницу или обратитесь к администратору');
		}
	}
	  
	});
			
	companies = new Companies; 
			
	 // initialize view
			
	companies.comparator = function(company) {
	  return company.get("name");
	};
	
	 // add template

	$('#preloader').width($('#add_row').width());
	$('#preloader').height($('#add_row').height());
	var p = $('#add_row').position();
	$('#preloader').css({'left':p.left, 'top': p.top});
	$('#preloader').fadeIn('fast');

	companies.fetch({	success: function(collection, response) {
							$('#preloader').fadeOut('fast');
							view_companies = new ViewCompanies({collection: collection});
							$('#companies_list').append(view_companies.render().el);
							view_companies.renderAll();	
						},
						error: function(){
							$('#preloader').fadeOut('fast');
							error_fetch('Ошибка при получении смен. Обновите страницу или обратитесь к администратору');
						}
					});
				
		
});
