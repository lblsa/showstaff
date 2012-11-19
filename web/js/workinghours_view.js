
var users, duties, view_workinghours;
$(function(){
	
	var ViewWorkinghours = Backbone.View.extend({
		
		tagName: "tbody",
		className: "workinghours",
		
		initialize: function() {
			_.bindAll(this);
			this.collection.on('reset', this.renderAll);
		},
		
		render: function() {
			return this;
		},
		
		renderAll: function() {
			if (this.collection.length > 0) {			
				$('.workinghours').html('');
				this.collection.each(function(model){
					var view = new ViewRow({model:model});
					
					if (model.get('agreed') == 0) {
						$(".agreed_all .btn").attr('rel','agreed');
						$(".agreed_all .btn").html('Утвердить всех');
					}
						
					
					var content = view.render().el;
					this.$('.workinghours').append(content);
				});
				
			} else {
				$('.workinghours').html('<tr class="alert_row"><td colspan="6"><div class="alert">'+
										'<button type="button" class="close" data-dismiss="alert">×</button>'+
										'На эту смену никто не назначен</div></td></tr>');
				$('#preloader').fadeOut('fast');
			}
			return this;
		}
	});
	
	var ViewRow = Backbone.View.extend({
		
		tagName: "tr",
		className: "user",
		
		template: _.template(	'<td class="u_user"><select class="user" disabled="disabled" name="user"></select></td>'+
								'<td class="u_duty"><select class="duty" disabled="disabled" name="duty"></select></td>'+
								'<td class="u_plan"><%= planhours %></td>'+
								'<td class="u_fact"><%= facthours %></td>'+
								'<td class="u_agreed"><% if(agreed) print("Да"); else print("Нет"); %></td>'),
								
		events: {	},
		
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
		
		render: function() {
			var content = this.template(this.model.toJSON());
			this.$el.html(content);
			var u_duty = $('.u_duty select', this.$el);
			var u_user = $('.u_user select', this.$el);
			var curent_model = this.model;
		
			u_duty.html('');
			duties.each(function(r){				
				if (curent_model.attributes.duty == r.id)
					$(u_duty).append('<option value="'+r.id+'" selected="selected">'+r.get("name")+'</option>');
				else
					$(u_duty).append('<option value="'+r.id+'">'+r.get("name")+'</option>');
			});
			
			u_user.html('');
			users.each(function(r){				
				if (curent_model.attributes.user == r.id)
					$(u_user).append('<option value="'+r.id+'" selected="selected">'+r.get("fullname")+' ('+r.get("username")+')</option>');
				else
					$(u_user).append('<option value="'+r.id+'">'+r.get("fullname")+' ('+r.get("username")+')</option>');
			});
			
			$('#preloader').fadeOut('fast'); 
			return this;
		},
	})
	
	var WorkinghoursModel = Backbone.Model.extend({ 	});
	
	var Workinghours = Backbone.Collection.extend({
	  
		model: WorkinghoursModel,
	  
		url: '/api/company/'+href[2]+'/restaurant/'+href[4]+'/shift/'+$('.wh_datepicker').val(),
		
		parse: function(response) {
			if(response.code && 'code' in response && response.code == 200 && 'data' in response ){
				return response.data;
			} else {
				error_fetch('Ошибка при получении пользователей');
			}
		},
		
		initialize: function(){
		  this.bind('add', this.addUser);
		},

		addUser: function(user){
			user.save({wait: true});
		},
	  
	});
	
	var Users = Backbone.Collection.extend({
		url: '/api/company/'+href[2]+'/user',
		parse: function(response, xhr){
			if(response && 'code' in response && response.code == 200 && 'data' in response) {
				return response.data;
			} else {
				error_fetch('Ошибка. Обновите страницу или обратитесь к администратору');
			}
		}
	});
	
	users = new Users;	
	
	var Duties = Backbone.Collection.extend({
		url: '/api/duty',
		parse: function(response, xhr){
			if(response && 'code' in response && response.code == 200 && 'data' in response) {
				return response.data;
			} else {
				error_fetch('Ошибка. Обновите страницу или обратитесь к администратору');
			}
		}
	});
	
	duties = new Duties;
	
	$('#preloader').width($('#shift_list').width());
	$('#preloader').height($('#shift_list').height());
	var p = $('#shift_list').position();
	
	$('#preloader').css({'left':p.left, 'top': p.top});
	$('#preloader').fadeIn('fast');
	
	
	workinghours = new Workinghours;
	
	duties.fetch({	success:function(collection, response){
							collection.each(function(duty){
								$('.duty_add').append('<option value="'+duty.id+'">'+duty.get('name')+'</option>');
							});
													
							users.fetch({	success:function(collection, response){
								
													collection.each(function(user){
														$('.user_add').append('<option value="'+user.id+'">'+user.get('fullname')+' ('+user.get('username')+')</option>');
													});
													
													workinghours.fetch({	success: function(collection, response) {
														
																				view_workinghours = new ViewWorkinghours({collection: collection});
																				$('#shift_list').append(view_workinghours.render().el);
																				view_workinghours.renderAll().el;
																				
																			}, 
																			error: function(){
																				error_fetch('Ошибка при получении смен. Обновите страницу или обратитесь к администратору');
																			}
																});
												},
												error:function(){
													error_fetch('Ошибка получения пользователей. Обновите страницу или обратитесь к администратору');
												}
											});
							
						}, error:function(){
							error_fetch('Ошибка получения должностей. Обновите страницу или обратитесь к администратору');
						}
					})
	
	
	$('#add_row, .contr').remove();
	
	$(".agreed_all .btn").click(function(){
		$.ajax({
		  type: "PUT",
		  url: "/api/company/"+href[2]+"/restaurant/"+href[4]+"/shift/"+$('.wh_datepicker').val()+"/"+$(".agreed_all .btn").attr('rel'),
		  data: function() {
			 return '{ "agreed": 1 }'
		  },
		  success: function(data) {
			 $('.agreed_all .alert').remove();
			  			  
			if ($(".agreed_all .btn").attr('rel') == 'agreed') {
				$(".agreed_all .btn").attr('rel','disagreed');
				$(".agreed_all .btn").html('Разутвердить всех');
			} else {
				$(".agreed_all .btn").attr('rel','agreed');
				$(".agreed_all .btn").html('Утвердить всех');
			}
			
			$('.workinghours').remove();
			
			workinghours.fetch({	success: function(collection, response) {
				
										view_workinghours = new ViewWorkinghours({collection: collection});
										$('#shift_list').append(view_workinghours.render().el);
										view_workinghours.renderAll().el;
										
									}, 
									error: function(){
										error_fetch('Ошибка при получении смен. Обновите страницу или обратитесь к администратору');
									}
						});
			
		  },
		  error: function(data) {
			  $('.agreed_all .alert').remove();
			  
		  	if (data != null && typeof(data.message) != 'undefined')
		  		$('.agreed_all').append('<span class="alert">'+data.message+'</span>');
		  	else
		  		$('.agreed_all').append('<span class="alert">Неизвестная ошибка.</span>');
		  },
		  dataType: "json"
		});
		
		return false;
	});	
	
	$('.wh_datepicker').datepicker({"format": "yyyy-mm-dd"})
		.on('changeDate', function(ev){
			var href = window.location.pathname.split('/');
			if (href[href.length-1] == 'shift')
				$('#link_to_date').attr( 'href', window.location.pathname+'/'+$('.wh_datepicker').val() );
			else
				$('#link_to_date').attr( 'href', $('.wh_datepicker').val() );
	});
});
