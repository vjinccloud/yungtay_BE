$(function() {
	setLoading();
	setScroll();
	setMobileMenu();	
	setSider();
});
function setLoading(){
	if($('#loading').length>0){
		$('#loading').fadeOut(1500);//1.5秒
	}
}

function setOverLapToggle(){
	//按空白的地方關閉
	$(document).on("click","main,.overlap", function (event) {		
		$('body').removeClass('body-popup-active');	
	}); 
}

function isInView(el) {
    windowTop = $(window).scrollTop();
    windowButtom = windowTop + $(window).height();
    elTop = $(el).offset().top-$('header').height();
    elButtom = elTop + $(el).height();

    return (elTop >= windowTop && elButtom <= windowButtom);
}
function setPopupOpen(id){	
	$('body').addClass('body-popup-active');
	$('.popup-div').removeClass('active');
	$('#'+ id).addClass('active');
}
function setPopupHide(){
	if($('.popup-div').length>0){
		$('.popup-div .popup-title i').click(function(){
			var id = $(this).attr('data-close-popup-id');
			$('body').removeClass('body-popup-active');			
			$('#'+ id).removeClass('active');
		});		
	}
}


function copyUrl(id) {
    $("body").after("<input id='copyVal' />");
    var text = id;
    var input = document.getElementById("copyVal");
    input.value = text;
    input.select();
    input.setSelectionRange(0, input.value.length);   
    document.execCommand("copy");
    $("#copyVal").remove();
}
function getQuery(q) {
	return (window.location.search.match(new RegExp('[?&]' + q + '=([^&]+)')) || [, null])[1];
}
function setMobileMenu(){	
	if($('#mobile-nav').length>0){
		var headerNaviHtml = $('header .header-div .navi').html();
		var headerSocietyContactHtml = $('header .header-div .society-contact').html();
		$('#mobile-nav .navi').append(headerNaviHtml);	
		$('#mobile-nav .society-contact').append(headerSocietyContactHtml);	
		$('.toggle-btn').click(function(){
			$('body').toggleClass('body-toggle-open');
			var memberCartHei = $('.member-cart').outerHeight();					
			var naviMaxHeight = $(window).height() - memberCartHei - 20;
			$('#mobile-nav .navi').css('max-height',naviMaxHeight+'px');
		});
		$('#mobile-nav .navi ul li .link').click(function(){
			$(this).parent().toggleClass('active').siblings('.active').removeClass('active');
		});
		$('.overlap').click(function(){
			$('body').removeClass('body-toggle-open');
		});
		$(document).on("click"," #mobile-nav .navi ul li a:not('.link')", function (event) {		
			$('body').removeClass('body-toggle-open');
		}); 
		
	}	
}
function setScroll() {
	$(window).scroll(function() {
			var scrollVal = $(this).scrollTop();
			if (scrollVal > 100) {
					$('body').addClass('scroll');
			} else {
					$('body').removeClass('scroll');
			}
	});
}
function setSider(){
	if($('.swiperNewsList').length>0){
		var swiperNewsList = new Swiper(".swiperNewsList", {	
			loop:true,
			navigation: {
				nextEl: ".swiperNewsList .swiper-button-next",
				prevEl: ".swiperNewsList .swiper-button-prev",
			},		
			pagination: {
				el: ".swiperNewsList .swiper-pagination",
				clickable: true,
			},
		});
	}
	if($('.swiperHome').length>0){
		var swiperHome = new Swiper(".swiperHome", {	
			loop:true,
			navigation: {
				nextEl: ".swiperHome .swiper-button-next",
				prevEl: ".swiperHome .swiper-button-prev",
			},		
			pagination: {
				el: ".swiperHome .swiper-pagination",
				clickable: true,
			},
		});
	}
	if($('.swiperNews').length>0){
		var swiperNews = new Swiper(".swiperNews", {	
			loop:true,
			navigation: {
				nextEl: ".news-swiper .swiper-button-next",
				prevEl: ".news-swiper .swiper-button-prev",
			},		
			pagination: {
				el: ".swiperNews .swiper-pagination",
				clickable: true,
			},
			spaceBetween: 0,
			breakpoints: {
				640: {
				slidesPerView: 1,				
				},
				768: {
				slidesPerView: 2,				
				},
				1024: {
				slidesPerView: 3,				
				},
				1366: {
				slidesPerView: 3,				
				},
			},
			
		});
	}
	if($('.swiperInfluence').length>0){
		var swiperInfluence = new Swiper(".swiperInfluence", {	
			loop:true,
			navigation: {
				nextEl: ".influence-swiper .swiper-button-next",
				prevEl: ".influence-swiper .swiper-button-prev",
			},		
			pagination: {
				el: ".swiperInfluence .swiper-pagination",
				clickable: true,
			},
			spaceBetween: 0,
			breakpoints: {
				640: {
				slidesPerView: 1,				
				},
				768: {
				slidesPerView: 2,				
				},
				1024: {
				slidesPerView: 3,				
				},
				1366: {
				slidesPerView: 3,				
				},
			},
			
		});
	}	
	if($('.swiperHealth').length>0){
		var swiperHealth = new Swiper(".swiperHealth", {	
			loop:true,
			/*
			navigation: {
				nextEl: ".health-swiper .swiper-button-next",
				prevEl: ".health-swiper .swiper-button-prev",
			},	
			*/	
			pagination: {
				el: ".swiperHealth .swiper-pagination",
				clickable: true,
			},
			spaceBetween: 0,
			breakpoints: {
				480: {
				slidesPerView: 1,				
				},
				640: {
				slidesPerView: 2,				
				},
				768: {
				slidesPerView: 3,				
				},
				1024: {
				slidesPerView: 4,				
				},
				1366: {
				slidesPerView: 5,				
				},
			},
			
		});
	}
	if($('.swiperAutonomy').length>0){
		var swiperAutonomy = new Swiper(".swiperAutonomy", {	
			loop:true,
			/*
			navigation: {
				nextEl: ".health-swiper .swiper-button-next",
				prevEl: ".health-swiper .swiper-button-prev",
			},	
			*/	
			pagination: {
				el: ".swiperAutonomy .swiper-pagination",
				clickable: true,
			},
			spaceBetween: 0,
			breakpoints: {
				480: {
				slidesPerView: 1,				
				},
				640: {
				slidesPerView: 2,				
				},
				768: {
				slidesPerView: 3,				
				},
				1024: {
				slidesPerView: 4,				
				},
				1366: {
				slidesPerView: 5,				
				},
			},
			
		});
	}
}
