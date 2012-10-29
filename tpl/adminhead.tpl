		{ISSET:PAGETITLE}<title>{VAR:PAGETITLE}</title>{/ISSET:PAGETITLE}
		<meta charset="utf-8" />
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />

		<script type="text/javascript" src="fancybox/lib/jquery-1.7.2.min.js"></script>

		<link rel="stylesheet" type="text/css" href="tpl/style.css" />

		<script type="text/javascript">
			$(document).ready(function() {
				$("#newbltag").keypress(function(e){
					if( e.which == 13 ) {
						return false;
					}
				});
				$("#newbltag").keyup(function(e){
					if( e.which == 13 && $(this).val() != "" ) {
						var c = parseInt( $("#tagcount").val() );
						$(this).before( '<span class="bltag">' + $(this).val() + '<input type="hidden" name="blt_' + c + '" value="' + $(this).val() + '"><a href="#" class="del_bltag">x</a></span>' );
						$("#tagcount").val( c+1 );
						$(this).val('');

						return false;
					}
				});
				$(".del_bltag").live("click", function(){
					$(this).parent().remove();
					return false;
				});
			});
		</script>