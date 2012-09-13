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
	
	$('.create').toggle(function() {
		$('i', this).attr('class', 'icon-minus-sign');
		$("#form_add .alert").remove();
		$('.name_add').val('');
		$('#form_add').slideDown();
		$('.name_add').focus();
		return false;
	}, function() {
		$('i', this).attr('class', 'icon-plus-sign');
		$('#form_add').slideUp();
		return false;
	});
	
	
   /* $('.del').click(function(){
		return confirm ("Будте осторожны, будут также удалены все связанные продукты.\r\nВы действительно хотите удалить элемент?");
	});
	*/ 
	
})


var units = {1:'кг', 
			 2:'литр',
			 3:'шт',
			 4:'пучок',
			 5:'бутылка'};
