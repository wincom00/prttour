var pt1 = {}

pt1 = Object.assign({}, pt1 || {}, {
	

	
	initHotelList: function initHotelList() {
		var prodList = $('.js-hotelListTable')
		var dt = prodList.DataTable({
			// columnDefs: [
			// 	{ orderable: false, targets: 7 },
			// ],
			bFilter : true,
			order: [8, 'asc'],
		})
	},
	initHotelRList: function initHotelRList() {
		var prodList = $('.js-hotelRListTable')
		var dt = prodList.DataTable({
			// columnDefs: [
			// 	{ orderable: false, targets: 7 },
			// ],
			bFilter : true,
			"lengthChange": false,
			"pageLength": 50,
			
			order: [1, 'asc'],
		})
	},
	initProductDetailForm2: function initProductDetailForm2() {
	    console.log('initializing product detail form')
		var scope = $('.productDetailForm')
		for (var i = 0; i < scope.length; i++) {
			var self = $(scope[i])
			var getselectcate1 = self.find('.fst1')
			var getselectcate2 = self.find('.fst21')
			var getpickarea = self.find('.pickarea')
			
			// actions
			getselectcate1.off('change.fst1').on('change.fst1', this.getselectcate1)
			getselectcate2.off('change.fst21').on('change.fst21', this.getselectcate2)
			getpickarea.off('change.pickarea').on('change.pickarea', this.getpickarea)
			
			
		}
	},
	initBoardForm: function initBoardForm() {
		// console.log('initializing product detail form')
		var scope = $('#board_write')
		for (var i = 0; i < scope.length; i++) {
			var self = $(scope[i])
			
			var ckEditors = self.find('.js-ckEditor')

			// ck editor settup
			ckEditors.each(function (k, elem) {
				
				CKEDITOR.replace( elem,{
					filebrowserUploadUrl: 'upload.php',
					allowedContent : true,
					enterMode:'2',
					height : '505px',
						
				} )
			})
		}

			
	},
	inithotelDetailForm: function inithotelDetailForm() {
		// console.log('initializing product detail form')
		var scope = $('.HotelDetailForm')
		for (var i = 0; i < scope.length; i++) {
			var self = $(scope[i])
			var getselectcate1 = self.find('.fst1')
			
			// actions
			getselectcate1.off('change.fst1').on('change.fst1', this.getselectcate1)
	
			getselectcate2.off('change.fst21').on('change.fst21', this.getselectcate2)
		}

			
	},
	
	
	
	getselectcate1: function getselectcate1(e) {
		
		var code1 = $(this).val();
		$.getJSON("get_code2.php?code1="+code1, function(jsonData){
			 $(".fst2").empty();
			 $(".fst2").append('<option value="">분류선택2</option>');
			 $.each(jsonData, function(i,data){
				  var codev = data.lvcode1+data.lvcode2+data.lvcode3;
				  $(".fst2").append('<option value="'+codev+'">'+data.comment+'</option>');
									
			 });
			  
		});
		
	},
    getselectcate2: function getselectcate2(e) {
		
		var code1 = $(this).val();
		$.getJSON("get_code3.php?code1="+code1, function(jsonData){
			 $(".fst2").empty();
			 $(".fst2").append('<option value="">분류선택2</option>');
			 $.each(jsonData, function(i,data){
				  var codev = data.lvcode1+data.lvcode2+data.lvcode3+data.lvcode4;
				  $(".fst2").append('<option value="'+codev+'">'+data.comment+'</option>');
									
			 });
			  
		});
		
	},
	
	
	getpickarea: function getselectcate1(e) {
		
		var code1 = $(this).val();
		var par = $(this).next(); 
        
		$.getJSON("get_pickt.php?code1="+code1, function(jsonData){
			 
		     par.empty();
		     par.append('<option value="">픽업시간선택</option>');
			 $.each(jsonData, function(i,data){
				  
				  par.append('<option value="'+data.pick_time+'">'+data.pick_time+'</option>');
				  				
			 });
			  
		});
		
	},

	


})

$(document).ready(function () {
	
})