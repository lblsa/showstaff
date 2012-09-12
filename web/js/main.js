$(document).ready(function(){
	$('.del').click(function(){
		return confirm ("Вы действительно хотите удалить элемент?");
	})
	
	$('#form_add_company').toggle(function(){
		$('.form_add_company').slideUp();
		$(this).html('Show Form');
	}, function(){
		$('.form_add_company').slideDown();
		$(this).html('Hide Form');
	})
})

var units = {1:'кг', 
			 2:'литр',
			 3:'шт',
			 4:'пучок',
			 5:'бутылка'};
