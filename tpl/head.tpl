		{ISSET:PAGETITLE}<title>{VAR:PAGETITLE}</title>{/ISSET:PAGETITLE}{ISSET:DESCRIPTION}
		<meta name="description" content="{VAR:DESCRIPTION}" />{/ISSET:DESCRIPTION}{ISSET:KEYWORDS}
		<meta name="keywords" content="{VAR:KEYWORDS}" />{/ISSET:KEYWORDS}

		<meta charset="utf-8" />
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />

		<script type="text/javascript" src="fancybox/lib/jquery-1.7.2.min.js"></script>
		<script type="text/javascript" src="fancybox/lib/jquery.mousewheel-3.0.6.pack.js"></script>

		<link rel="stylesheet" href="fancybox/source/jquery.fancybox.css?v=2.0.6" type="text/css" media="screen" />
		<script type="text/javascript" src="fancybox/source/jquery.fancybox.pack.js?v=2.0.6"></script>

		<link rel="stylesheet" href="fancybox/source/helpers/jquery.fancybox-buttons.css?v=1.0.2" type="text/css" media="screen" />
		<script type="text/javascript" src="fancybox/source/helpers/jquery.fancybox-buttons.js?v=1.0.2"></script>
		<script type="text/javascript" src="fancybox/source/helpers/jquery.fancybox-media.js?v=1.0.0"></script>

		<link rel="stylesheet" href="fancybox/source/helpers/jquery.fancybox-thumbs.css?v=2.0.6" type="text/css" media="screen" />
		<script type="text/javascript" src="fancybox/source/helpers/jquery.fancybox-thumbs.js?v=2.0.6"></script>

		<link rel="stylesheet" type="text/css" href="tpl/style.css" />

		<script type="text/javascript">
			$(document).ready(function() {
				$(".fancybox").fancybox({
					'mouseWheel'	: false,
					'type'			: 'ajax',
					'afterShow'		: function() {
						$(".comment").mouseenter(function(){
							$(this).children(".commentdate").stop().clearQueue();
							$(this).children(".commentdate").animate({ "opacity": 0.9 }, 'fast' );
						});
						$(".comment").mouseleave(function(){
							$(this).children(".commentdate").stop().clearQueue();
							$(this).children(".commentdate").animate({ "opacity": 0 }, 'slow');
						});
					}
				});


				$("#morelink").show();
				$("#moretags").hide();
				var moreshown = false;
				$("#morelink").click(function(){
					if( moreshown ) {
						$(this).html( '[+] mehr' );
						$("#moretags").slideUp();
					} else {
						$(this).html( '[-] weniger' );
						$("#moretags").slideDown();
					}
					moreshown = !moreshown;
					return false;
				});

				$(".imageblacklist").hide();
				$(".pic").mouseenter(function(){
					$(this).children(".imageblacklist").fadeIn(200);
				});
				$(".pic").mouseleave(function(){
					$(this).children(".imageblacklist").fadeOut(200);
				});
				$(".imageblacklist").mouseenter(function(){
					$(this).parent().click(function(){
						var pid = $(this).attr("id").substr(4);
						var t = $(this);
						$.post("admin.php?a=4", {"pid": pid}, function(ret){
							if( ret.substr(0,7) != "success" ) {
								alert( "Ein Fehler ist aufgetreten." );
							} else {
								if( ret.substr(7) == "add" ) {
									t.children(".imageblacklist").after( '<div class="blacklisted">Geblacklistet</div>' );
								} else {
									t.children(".blacklisted").remove();
								}
							}
						});
						return false;
					});
				});
				$(".imageblacklist").mouseleave(function(){
					$(this).parent().unbind("click");
				});
			});
		</script>