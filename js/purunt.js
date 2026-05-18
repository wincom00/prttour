var pt = {}

pt = Object.assign({}, pt || {}, {
	imagePlaceholder: {
		_140x140: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9InllcyI/PjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMTQwIiBoZWlnaHQ9IjE0MCIgdmlld0JveD0iMCAwIDE0MCAxNDAiIHByZXNlcnZlQXNwZWN0UmF0aW89Im5vbmUiPjwhLS0KU291cmNlIFVSTDogaG9sZGVyLmpzLzE0MHgxNDAKQ3JlYXRlZCB3aXRoIEhvbGRlci5qcyAyLjYuMC4KTGVhcm4gbW9yZSBhdCBodHRwOi8vaG9sZGVyanMuY29tCihjKSAyMDEyLTIwMTUgSXZhbiBNYWxvcGluc2t5IC0gaHR0cDovL2ltc2t5LmNvCi0tPjxkZWZzPjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+PCFbQ0RBVEFbI2hvbGRlcl8xNjZjMjdhNGY4MyB0ZXh0IHsgZmlsbDojQUFBQUFBO2ZvbnQtd2VpZ2h0OmJvbGQ7Zm9udC1mYW1pbHk6QXJpYWwsIEhlbHZldGljYSwgT3BlbiBTYW5zLCBzYW5zLXNlcmlmLCBtb25vc3BhY2U7Zm9udC1zaXplOjEwcHQgfSBdXT48L3N0eWxlPjwvZGVmcz48ZyBpZD0iaG9sZGVyXzE2NmMyN2E0ZjgzIj48cmVjdCB3aWR0aD0iMTQwIiBoZWlnaHQ9IjE0MCIgZmlsbD0iI0VFRUVFRSIvPjxnPjx0ZXh0IHg9IjQ0LjA1NDY4NzUiIHk9Ijc0LjUiPjE0MHgxNDA8L3RleHQ+PC9nPjwvZz48L3N2Zz4=',
	},
	defaults: {
		datepicker: {
			// startDate: '-1d',
			// endDate: '+1y',
			format: "yyyy-mm-dd",
			todayBtn: 'linked',
			clearBtn: true,
			orientation: 'bottom left',
			autoclose: true,
			todayHighlight: true,
			// datesDisabled: this.datesDisabled,
			toggleActive: true,
			clearBtn: false,
		},
		clockpicker: {
			placement: 'bottom',
			autoclose: true,
		},
	},

	init: function init() {
		// console.log('initializing pt')
		this.initDataTable()
	},

	initDataTable: function initDataTable() {
		$.extend(true, $.fn.dataTable.defaults, {
			searching: false,
			pageLength: 100,
		   // ordering: false,
			//lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
		})
	},

	initProductList: function initProductList() {
		var prodList = $('.js-productListTable')
		var dt = prodList.DataTable({
			// columnDefs: [
			// 	{ orderable: false, targets: 7 },
			// ],
			pageLength: 100,
			bFilter: false,
			order: [1, 'asc'],
		})
	},

	initProductDetailForm: function initProductDetailForm() {
		// console.log('initializing product detail form')
		var scope = $('.productDetailForm')
		for (var i = 0; i < scope.length; i++) {
			var self = $(scope[i])
			var addPickupBtns = self.find('.js-addPickup')
			var removePickupBtns = self.find('.js-removePickup')
			var addBlockDateBtns = self.find('.js-addBlockDate')
			var removeBlockDateBtns = self.find('.js-removeBlockDate')
			var addReservationDateBtns = self.find('.js-addReservationDate')
			var removeReservationDateBtns = self.find('.js-removeReservationDate')
			var addSingleDayTourBtns = self.find('.js-addSingleDayTour')
			var removeSingleDayTourBtns = self.find('.js-removeSingleDayTour')

			var formSaveBtn = self.find('.js-formSave')
			var formDeleteBtn = self.find('.js-formDelete')

			var emptyImgs = self.find('img[src=""]')

			var ckEditors = self.find('.js-ckEditor')

			var scheduleModal = self.find('.js-openScheduleModal')
			var singleDayTourModal = self.find('.js-openSingleDayTourModal')

			// button actions
			addPickupBtns.off('click.addPickup').on('click.addPickup', this.addPickup)
			removePickupBtns.off('click.removePickup').on('click.removePickup', this.removePickup)
			addBlockDateBtns.off('click.addBlockDate').on('click.addBlockDate', this.addBlockDate)
			removeBlockDateBtns.off('click.removeBlockDate').on('click.removeBlockDate', this.removeBlockDate)
			addReservationDateBtns.off('click.addReservationDate').on('click.addReservationDate', this.addReservationDate)
			removeReservationDateBtns.off('click.removeReservationDate').on('click.removeReservationDate', this.removeReservationDate)
			addSingleDayTourBtns.off('click.addSingleDayTour').on('click.addSingleDayTour', this.addSingleDayTour)
			removeSingleDayTourBtns.off('click.removeSingleDayTour').on('click.removeSingleDayTour', this.removeSingleDayTour)

			// form buttons on the top
			formSaveBtn.off('click.formSave').on('click.formSave', this.productDetailFormSave)
			formDeleteBtn.off('click.formDelete').on('click.formDelete', this.productDetailFormDelete)

			// empty images placeholder image
			emptyImgs.attr({ src: this.imagePlaceholder._140x140 })

			// ck editor settup
			ckEditors.each(function (k, elem) {
				CKEDITOR.replace( elem, {
					filebrowserUploadUrl: 'upload.php',
				} )
			})

			// schedule modal
			scheduleModal.on('shown.bs.modal', function (e) {
				// console.log('schdule modal opened')
			})
			scheduleModal.on('hidden.bs.modal', function (e) {
				// console.log('schdule modal closed')
			})
			
			// single day tour modal
			singleDayTourModal.on('show.bs.modal', function (e) {
				// console.log('single day tour modal about to open')
				var modal = $(e.target)
				var sourceBtn = $(e.relatedTarget)
				var prevSelection = sourceBtn.closest('.js-tourSet').find('.js-tourCode').val()
				if (prevSelection) {
					modal.find(':input[value="' + prevSelection + '"]').prop('checked', true)
				}
			})
			singleDayTourModal.on('shown.bs.modal', (function (e) {
				// console.log('single day tour modal opened')
				var modal = $(e.target)
				var sourceBtn = $(e.relatedTarget)
				var singleDayTourSearch = modal.find('.js-searchSingleDayTour')
				var saveSelectionBtn = modal.find('.js-saveSelection')
				singleDayTourSearch.on('keyup.singleDayTourSearch', this.util.searchSingleDayTour)
				saveSelectionBtn.on('click.saveSelection', { modal: modal, sourceBtn: sourceBtn }, this.util.saveSingleDayTour)
			}).bind(this))
			singleDayTourModal.on('hide.bs.modal', function (e) {
				// console.log('single day tour modal is about to close')
				// stash style assigned on body to re-apply on modal close
				// this is done to revert style removal upon modal close but it shouldn't as this is second level modal
				var style = $(document.body).attr('style')
				$(document.body).data('style', style)
			})
			singleDayTourModal.on('hidden.bs.modal', function (e) {
				// console.log('single day tour modal closed')
				var modal = $(e.target)
				var singleDayTourSearch = modal.find('.js-searchSingleDayTour')
				var saveSelectionBtn = modal.find('.js-saveSelection')
				singleDayTourSearch.off('keyup.singleDayTourSearch')
				saveSelectionBtn.off('click.saveSelection')

				// re-apply stashed style
				$(document.body).addClass('modal-open').attr('style', $(document.body).data('style')).data('style', '')
			})
		}
	},

	initMainPage: function initMainPage() {
		var self = $('.js-mainPage')
		var reservationChangesTable = self.find('.js-recentReservationChangesTable')
		var rct_dt = reservationChangesTable.DataTable({
			order: [8, 'desc'],
		})
	},
	
   
	initReservationList: function initReservationList() {
		var productTable = $('.js-productTable')
		var dates = $('.tourDate')
		var rct_dt = productTable.DataTable({pageLength: 100})

		dates.datepicker($.extend({}, this.defaults.datepicker, {
			
			//
		}))
	},

	initReservationDetail: function initReservationDetail() {
		var scope = $('.reservationDetailForm')

		for (var i = 0; i < scope.length; i++) {
			var self = $(scope[i])
			var dateInputs = self.find('.js-dateInput')
			var dateInputBtns = self.find('.js-dateInputBtn')
			var timeInputs = self.find('.js-timeInput')
			var timeInputsBtns = self.find('.js-timeInputBtn')
			var addTravelerBtns = self.find('.js-addTraveler')
			var removeTravelerBtns = self.find('.js-removeTraveler')
			var resetTravelerBtns = self.find('.js-resetTraveler')
			var hideShowToggleBtns = self.find('.js-hideShowToggle')
			var numTouristsInput = self.find('.js-numTourists')
			var getselectcatearea1 = self.find('.areaf')
			var getselectcatearea2 = self.find('.areaf2')
			// datepicker
			dateInputs.datepicker($.extend({}, this.defaults.datepicker, {}))

			// clockpicker
			timeInputs.clockpicker($.extend({}, this.defaults.clockpicker, {}))

			// button actions
			dateInputBtns.off('click.showDatepicker').on('click.showDatepicker', function (e) {
				e.stopPropagation()
				$(this).closest('.input-group').find('input').datepicker('show')
			})
			timeInputsBtns.off('click.showDatepicker').on('click.showDatepicker', function (e) {
				e.stopPropagation()
				$(this).closest('.input-group').find('input').clockpicker('show')
			})
			//addTravelerBtns.off('click.addTraveler').on('click.addTraveler', { self: self }, this.addTraveler)
			//removeTravelerBtns.off('click.removeTraveler').on('click.removeTraveler', { self: self }, this.removeTraveler)
			//resetTravelerBtns.off('click.resetTraveler').on('click.resetTraveler', this.resetTraveler)
			hideShowToggleBtns.off('click.showDatepicker').on('click.showDatepicker', function (e) {
				var btn = $(this)
				var chevron = btn.find('.glyphicon')
				var isShown = chevron.hasClass('glyphicon-chevron-down')
				var trToToggle = btn.closest('tr').siblings()
				if (isShown) {
					chevron.removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-right')
					trToToggle.addClass('hidden')
				} else {
					chevron.removeClass('glyphicon-chevron-right').addClass('glyphicon-chevron-down')
					trToToggle.removeClass('hidden')
				}
			})
			//numTouristsInput.off('change.initNumTouristsInfo').on('change.initNumTouristsInfo', { self: self }, this.initNumTouristsInfo)
			getselectcatearea1.off('change.getselectcatearea1').on('change.getselectcatearea1', { self: self }, this.getselectcatearea1)
			getselectcatearea2.off('change.getselectcatearea2').on('change.getselectcatearea2', { self: self }, this.getselectcatearea2)
		



		}
	},

	iniTourStatus: function iniTourStatus() {
		var tourStatusTable = $('.js-tourStatusTable')
		var dt = tourStatusTable.DataTable({
			// columnDefs: [
			// 	{ orderable: false, targets: 7 },
			// ],
			order: [0, 'asc'],
		})
	},

	productDetailFormSave: function productDetailFormSave(e) {
		console.log('product detail page form save clicked')
	},

	productDetailFormDelete: function productDetailFormDelete(e) {
		console.log('product detail page form delete clicked')
	},

	addPickup: function addPickup(e) {
		var btn = $(this)
		var set = $(btn.closest('tr').find('.js-pickupSet')[0])
		var parent = set.closest('td')
		set.clone(true)
		.find('.js-removePickup').removeClass('hidden')
		.end().find(':input').val('')
		.end().appendTo(parent)
	},

	removePickup: function removePickup(e) {
		var btn = $(this)
		var actionActive = !btn.hasClass('hidden')
		var set = btn.closest('.js-pickupSet')
		if (actionActive) {
			set.remove()
		}
	},

	addBlockDate: function addBlockDate(e) {
		var btn = $(this)
		var set = $(btn.closest('tr').find('.js-blockDateSet')[0])
		var parent = set.closest('td')
		set.clone(true)
		.find('.js-removeBlockDate').removeClass('hidden')
		.end().find('.inname').val('')
		.end().appendTo(parent)
	},

	removeBlockDate: function removeBlockDate(e) {
		var btn = $(this)
		var actionActive = !btn.hasClass('hidden')
		var set = btn.closest('.js-blockDateSet')
		if (actionActive) {
			set.remove()
		}
	},

	addReservationDate: function addReservationDate(e) {
		var btn = $(this)
		var set = $(btn.closest('tr').find('.js-reservationDateSet')[0])
		var parent = set.closest('td')
		set.clone(true)
		.find('.js-removeReservationDate').removeClass('hidden')
		.end().find(':input').val('')
		.end().appendTo(parent)
	},

	removeReservationDate: function removeReservationDate(e) {
		var btn = $(this)
		var actionActive = !btn.hasClass('hidden')
		var set = btn.closest('.js-reservationDateSet')
		if (actionActive) {
			set.remove()
		}
	},

	addSingleDayTour: function addSingleDayTour(e) {
		var btn = $(this)
		var set = $(btn.closest('tr').find('.js-tourSet')[0])
		var parent = set.closest('td')
		set.clone(true)
		.find('.js-removeSingleDayTour').removeClass('hidden')
		.end().find(':input').val('')
		.end().appendTo(parent)
	},

	removeSingleDayTour: function removeSingleDayTour(e) {
		var btn = $(this)
		var actionActive = !btn.hasClass('hidden')
		var set = btn.closest('.js-tourSet')
		if (actionActive) {
			set.remove()
		}
	},

	addTraveler: function addTraveler(e) {
		var eData = e.data || {}
		var self = eData.self
		var btn = $(this)

		var set = btn.closest('tr').find('.js-touristInfo').last()
		var sel = btn.closest('tr').find('.js-touristInfo').last().find('#sexType').val()
		var pick = btn.closest('tr').find('.js-touristInfo').last().find('#pickuploc').val()
		var room = btn.closest('tr').find('.js-touristInfo').last().find('#pickRoomType1').val()
		var price = btn.closest('tr').find('.js-touristInfo').last().find('#pickPriceType1').val()
		var parent = set.closest('td')
		set.clone(true)
		.find('.js-removeTraveler').removeClass('hidden')
		//.end().find(':input').val('')
		//.end().find('select > option:first-child').prop({ selected: true })
		.end().find("#sexType").val(sel)
		.end().find("#pickuploc").val(pick)
		.end().find("#pickRoomType1").val(room)
		.end().find("#pickPriceType1").val(price)
		.end().appendTo(parent)
	   
		if (self) {
			var numTouristsElem = self.find('.js-numTourists')
			var numTourists = parseInt(numTouristsElem.val())
			if (numTourists >= 1) {
				numTouristsElem.val(parseInt(numTourists) + 1)
			} else {
				numTouristsElem.val(2).prop({ readonly: true })
			}
		}
		calc()
	},

	removeTraveler: function removeTraveler(e) {
		var eData = e.data || {}
		var self = eData.self
		var btn = $(this)
		var actionActive = !btn.hasClass('hidden')
		var set = btn.closest('.innerTable')
		if (actionActive) {
			set.remove()
			
			if (self) {
				var numTouristsElem = self.find('.js-numTourists')
				numTouristsElem.val(parseInt(numTouristsElem.val()) - 1)
			}
		}
		//calcminus()
		calc(1);
	},

	resetTraveler: function resetTraveler(e) {
		var btn = $(this)
		var set = btn.closest('.js-touristInfo')
		set.find(':input').val('')
		.end().find('select > option:first-child').prop({ selected: true })
	},

	initNumTouristsInfo: function initNumTouristsInfo(e) {
		var eData = e.data || {}
		var self = eData.self
		var input = $(this)
		var numTourists = input.val()
		if (numTourists > 0) {
			if (numTourists > 1) {
				var addTravelerBtns = self.find('.js-addTraveler')
				input.val(1)
				for (var j = 1; j < numTourists; j++) {
					addTravelerBtns.trigger('click.addTraveler')
				}
			}
			input
			.off('change.initNumTouristsInfo')
			.prop({ readonly: true })
		}
		calc(1);
				
	},

	getselectcatearea1: function getselectcatearea1(e) {
		
		var code1 = $(this).val();
	
		$.getJSON("get_comp.php?code1="+code1, function(jsonData){
			 $(".comp").empty();
			 $(".comp").append('<option value="">-협력사 선택하세요');
			 $.each(jsonData, function(i,data){
				  var codev = data.userid;
				  $(".comp").append('<option value="'+codev+'">'+'['+codev+']'+' '+data.kor_name+'');
									
			 });
			 //$(".comp").data("placeholder","Select").trigger('chosen:updated'); 
		});
		
	},
    getselectcatearea2: function getselectcatearea2(e) {
		
		var code1 = $(this).val();
	
		$.getJSON("get_comp.php?code1="+code1, function(jsonData){
			 $(".comp2").empty();
			 $(".comp2").append('<option value="">-협력사 선택하세요');
			 $.each(jsonData, function(i,data){
				  var codev = data.userid;
				  $(".comp2").append('<option value="'+codev+'">'+'['+codev+']'+' '+data.kor_name+'');
									
			 });
			  
		});
		
	},
	beforeShowDayFunc: function beforeShowDayFunc(date, set) {
		// if daysOfWeekDisabled is set, it will override anything set in this function
		var daysOfWeekEnabledSet = set.daysOfWeekEnabled && set.daysOfWeekEnabled.length <= 1 || true
		var datesEnabledSet = set.datesEnabled && set.datesEnabled.length >= 1 || false
		var datesOnlySet = set.datesOnly && set.datesOnly.length >= 1 || false
		var enabled
		var datest = set.st && set.st.length >= 1 || false
		
		//if (daysOfWeekEnabledSet || datesEnabledSet || datesOnlySet) {
			if (datesOnlySet) {
				var mdy = moment(date).format('YYYY-MM-DD')
				if (set.datesOnly.indexOf(mdy) !== -1) {
					enabled = true
				} else {
					enabled = false
				}
				//console.log(set.st+'qqqq');
				var mdy = moment(date).format('YYYY-MM-DD')
				//if (mdy >= set.st && mdy <= set.et) {
					if (daysOfWeekEnabledSet) {
						var day = date.getDay()
						if (set.daysOfWeekEnabled.indexOf(day) !== -1) {
							enabled = true
						} else {
							enabled = false
						}
					}
				//}
			} else {
				var mdy = moment(date).format('YYYY-MM-DD')
				if (mdy >= set.st && mdy <= set.et) {
				
					if (daysOfWeekEnabledSet) {
						var day = date.getDay()
						if (set.daysOfWeekEnabled.indexOf(day) !== -1) {
							enabled = true
						} else {
							enabled = false
						}
					}
					//console.log(  set.st+'tttt');
					//console.log(  set.et+'tttt');
					
					
					
				} else {
					enabled = false
				}

				if (datesEnabledSet) {
					var datesDisabledSet = set.datesDisabled && set.datesDisabled.length >= 1 || false
					var mdy = moment(date).format('YYYY-MM-DD')
					if (datesDisabledSet) {
						set.datesDisabled = this.removeSameItem(set.datesDisabled,set.datesEnabled)
					}
					if (set.datesEnabled.indexOf(mdy) !== -1) {
						enabled = true
					}
				}
				
			}
		//}
		return enabled
	},

	removeSameItem: function removeSameItem(on, from) {
		var _on = on.slice()
		for (var i = _on.length - 1; i >= 0; i--) {
			var onElem = moment(_on[i])
			for (var j = 0; j < from.length; j++) {
				var fromElem = moment(from[j] + ' +0000', 'MM/DD/YYYY Z')
				if (onElem.isSame(fromElem)) {
					_on.splice(i, 1)
					break
				}
			}
		}
		return _on
	},

	changeTourStartDate: function changeTourStartDate(e) {
		var selectedDate = e.date
		
		var eData = e.data
		var self = eData.self
		var tourEndDate = self.find('.js-tourEndDate')
		var tourday = self.find('#cday')
		var pcode = self.find('#pcode').val()
		var pcnt = self.find('#tcnt').val()
		var pcnt1 = self.find('#tcnt')
		var today = new Date(selectedDate);
	
		var selectedDate = e.date
		
		var eData = e.data
		var self = eData.self
		var tourEndDate = self.find('.js-tourEndDate')
		var tourday = self.find('#cday')
		var pcode = self.find('#pcode').val()
		var pcnt = self.find('#tcnt').val()
		var pcnt1 = self.find('#tcnt')
		var today = new Date(selectedDate);
		
		var year = today.getFullYear();
        //today.setMonth(today.getMonth()+1);
		//var month = today.getMonth();
		
		if (tourday.val() == 1)
		{
			today.setDate(today.getDate())
			var day = today.getDate();
			var month = today.getMonth()+1;
		} else {
			var pday =parseInt(tourday.val()) -1;
			today.setDate(today.getDate() + pday);
			var day = today.getDate();
			var month = today.getMonth()+1;
			
		}
		//alert('1')
		var day1 = today.getDate()
		if(month<10) month = "0" + month;
		if(day<10) day = "0" + day;
		if(day1<10) day1 = "0" + day1;
		var stDate2 = year+'-'+month+'-'+day1;
		if  (selectedDate) {
			if (tourEndDate.prop('disabled')) {
				
				tourEndDate
				.val(year+'-'+month+'-'+day)
				.prop({ "readOnly": false })
				.datepicker($.extend({}, pt.defaults.datepicker, {
					startDate: selectedDate,
					defaultViewDate: selectedDate,
				}))
				.closest('.input-group').find('button').prop({ disabled: false })
			} else {
				
				tourEndDate
				.val(year+'-'+month+'-'+day)
				.datepicker('destroy')
				tourEndDate
				.datepicker($.extend({}, pt.defaults.datepicker, {
					startDate: selectedDate,
					defaultViewDate: selectedDate,
				}))
			}
		} else {
			tourEndDate
			.val(year+'-'+month+'-'+day)
			.prop({ "readOnly": true })
			.datepicker('destroy')
			.closest('.input-group').find('button').prop({ disabled: true })
		}
		$.getJSON("get_pgrev.php?pcode="+pcode+"&st="+stDate2, function(jsonData){
			 
			 $.each(jsonData, function(i,data){
				
				 $(".pcntid").empty();
			     
				 var pcnt2 = data.pcnt;
				 pcnt1.val(pcnt2);
				
				 $(".pcntid").html(pcnt2+"명");
				 									
			 });
			  
		});
		$.getJSON("get_rev.php?pcode="+pcode+"&st="+stDate2, function(jsonData){
			 
			 $.each(jsonData, function(i,data){
				
				 $(".acntid").empty();
			     $(".cntid").empty();
				 var cnt = data.cnt;
				 var acnt = pcnt - cnt;
				 if (cnt == null) {
					cnt = 0;
				 }
			
				 $(".acntid").html(acnt+"명");
				 $(".cntid").html(cnt+"명");
				 if (acnt > pcnt) {
					$("#order_status").val("WAIT");
					
				 } else {
					$("#order_status").val("READY");
				 }
									
			 });
			  
		});
        
		
	},
	changeTourStartDate1: function changeTourStartDate1(e) {
		var selectedDate = e.date
		var eData = e.data
		var self = eData.self
		var tourEndDate = self.find('.js-tourEndDate')
		var tourday = self.find('#cday')
		//alert(tourday.val());
		var today = new Date(selectedDate);
		
		var year = today.getFullYear();
        var month = today.getMonth() + 1;
        if (tourday.val()==	1)
		{
			var day = today.getDate();
		} else {
			var day = today.getDate()- 1;//;+parseInt(tourday.val())
		}
		if(month<10) month = "0" + month;
		if(day<10) day = "0" + day;
		if  (selectedDate) {
			if (tourEndDate.prop('disabled')) {
				
				tourEndDate
				.val(year+'-'+month+'-'+day)
				.prop({ "readOnly": false })
				.datepicker($.extend({}, pt.defaults.datepicker, {
					startDate: selectedDate,
					defaultViewDate: selectedDate,
				}))
				.closest('.input-group').find('button').prop({ disabled: false })
			} else {
				
				tourEndDate
				.val(year+'-'+month+'-'+day)
				.datepicker('destroy')
				tourEndDate
				.datepicker($.extend({}, pt.defaults.datepicker, {
					startDate: selectedDate,
					defaultViewDate: selectedDate,
				}))
			}
		} else {
			tourEndDate
			.val(year+'-'+month+'-'+day)
			.prop({ "readOnly": true })
			.datepicker('destroy')
			.closest('.input-group').find('button').prop({ disabled: true })
		}
	},

	util: {
		/**
		 * return number at the end of the string as an integer
		 * last character of the string must be a digit
		 * this function does not handle number bigger than Int MAX
		 * @param {String} str string with number at the end
	                 	 * @returns {Int}
		 */
		postFixNumber: function postFixNumber(str) {
			var number = parseInt(str.substr(str.length - 1))
			for (var i = str.length - 1; i >= 0; i--) {
				var numStr = str.substr(i)
				if (!isNaN(parseInt(numStr))) {
					number = parseInt(numStr)
				}
			}
			return number
		},

		/**
		 * only shows selection that partially matches search string entered
		 * [data-search-str] needs to be all lower case
		 * @param {Event} e 
		 */
		searchSingleDayTour: function searchSingleDayTour(e) {
			var search = $(this)
			var modal = search.closest('.modal-body')
			var searchStr = search.val().toLowerCase()
			var allItems = modal.find('input[data-search-str]')
			if (searchStr !== '') {
				var matches = modal.find('input[data-search-str*="' + searchStr + '"]')
				allItems.closest('.radio').addClass('hidden')
				matches.closest('.radio').removeClass('hidden')
			} else {
				allItems.closest('.radio').removeClass('hidden')
			}
		},

		saveSingleDayTour: function saveSingleDayTour(e) {
			var modal = e.data.modal
			var sourceBtn = e.data.sourceBtn
			var selection = modal.find(':input:checked')
			var tourName = selection.data().tourName
			var tourCode = selection.data().tourCode
			sourceBtn.closest('.js-tourSet').find('.js-tourName').val(tourName)
			.end().find('.js-tourCode').val(tourCode)
			modal.modal('hide')
		},

	},

})

$(document).ready(function () {
	pt.init()
})