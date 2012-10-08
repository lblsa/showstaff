$(document).ready(function(){
	$('.del').click(function(){
		return confirm ("Вы действительно хотите удалить элемент?");
	})
	
	$('.create').toggle(function() {
		$('i', this).attr('class', 'icon-minus-sign');
		$(".forms .alert").remove();
		$('.name_add').val('');
		$('.forms').slideDown();
		$('.name_add').focus();
		return false;
	}, function() {
		$('i', this).attr('class', 'icon-plus-sign');
		$('.forms').slideUp();
		return false;
	});
	
	$('.datepicker').datepicker({"format": "yyyy-mm-dd"})
		.on('changeDate', function(ev){
			var href = window.location.pathname.split('/');
			if (href[href.length-1] == 'order')
				$('#link_to_date').attr( 'href', window.location.pathname+'/'+$('.datepicker').val() );
			else
				$('#link_to_date').attr( 'href', $('.datepicker').val() );
	});
	
})

var href = window.location.pathname.split('/');
var Units = Backbone.Collection.extend({
	url: '/units',
	initialize: function(){
		this.fetch();
	}
});

var units = new Units;

