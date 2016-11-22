$(document).ready(function() {
         $('#msg').corner();
         $('#erreur').corner();
        //$(".menu_btn").click(function(){$('#main').fadeOut("slow");});
	//$('a[rel*=facebox]').facebox({loading_image : 'css/img/loading.gif', close_image: 'css/img/closelabel.gif'}); 
	//$("#btn_enregistrer").click(function(){$('#main_zone').hide();$('#logo_bg').fadeIn("slow");});
	//$("#btn_ajouter").click(function(){$('#photo_upload').toggle();$('#photo_loading').fadeIn();});
	//$("#btn_ajouter").click(function(){$('#main_zone').hide();$('#logo_bg').fadeIn();$('#loading').fadeIn("slow");});
	$("#wait_btn1").click(function(){$('#loading_box').fadeIn("slow");});
	$("#wait_btn2").click(function(){$('#loading_box').fadeIn("slow");});
	//$("#btn_login").click(function(){$('#login_box').fadeOut("slow");});
	//$("#module_photos").click(function(){$('#module_article').hide();});
	//$("#btn_ajouter").click(function(){$('#photo_loading').fadeIn();});
	// Affiche le message des actions
    // Bloque le menu contextuel/clic droit  
	//$(document).bind("contextmenu",function(e){return false;}); 
	// Effacement des photos
	//$(".delete").hover(function(){$(this).parent().css("background-color","#900");});
	$(".delete").click(function(){$(this).parent().fadeOut("slow")});;
 	$("#form_titre").keyup(function() {$("#btn_enregistrer").removeClass("save_btn");$("#btn_enregistrer").addClass("hot_save_btn");});
 	$('#import').keyup(function(){
 		var txt = jQuery.trim($(this).val());
 		console.log($(this).val());
 		//$txt = txt.replace(/\t\t?/, "#");
 		$(this).val(txt.replace(/\s?\t\t?/g, "#") + '\n');
 		//$(this).val(txt.replace(/##/, "#"));
 		//alert(this.value);
 		});
 // On cache les sous-menus 
    // sauf celui qui porte la classe "open_at_load" : 
    $(".navigation ul.subMenu:not('.open_at_load')").hide(); 
    // On sélectionne tous les items de liste portant la classe "toggleSubMenu" 
 
    // et on remplace l'élément span qu'ils contiennent par un lien : 
    $(".navigation li.toggleSubMenu span").each( function () { 
        // On stocke le contenu du span : 
        var TexteSpan = $(this).text(); 
        $(this).replaceWith('<a href="" title="Afficher le sous-menu">' + TexteSpan + '<\/a>') ; 
    } ) ; 
 
    // On modifie l'évènement "click" sur les liens dans les items de liste 
    // qui portent la classe "toggleSubMenu" : 
    $(".navigation li.toggleSubMenu > a").click( function () { 
        // Si le sous-menu était déjà ouvert, on le referme : 
        if ($(this).next("ul.subMenu:visible").length != 0) { 
            $(this).next("ul.subMenu").slideUp("normal", function () { $(this).parent().removeClass("open") } ); 
        } 
        // Si le sous-menu est caché, on ferme les autres et on l'affiche : 
        else { 
            $(".navigation ul.subMenu").slideUp("normal", function () { $(this).parent().removeClass("open") }); 
            $(this).next("ul.subMenu").slideDown("normal", function () { $(this).parent().addClass("open") } ); 
        } 
        // On empêche le navigateur de suivre le lien : 
        return false; 
    });
});

$(window).load(function() {
	$("#msg").fadeIn("slow").animate({opacity: 1.0}, 3000).fadeOut("slow");
	$("#erreur").fadeIn("slow").animate({opacity: 1.0}, 3000).fadeOut("slow");
        //$("#main").fadeIn("slow");
});

/**
* Met les lignes de tableau en surbrillance lors du passage du pointeur
*/
function markRowsInit() {
    // for every table row ...
    var rows = document.getElementsByTagName('tr');
    for ( var i = 0; i < rows.length; i++ ) {
        // ... with the class 'odd' or 'even' ...
        if ( 'odd' != rows[i].className.substr(0,3) && 'even' != rows[i].className.substr(0,4) ) {
            continue;
        }
        // ... add event listeners ...
        // ... to highlight the row on mouseover ...
        if ( navigator.appName == 'Microsoft Internet Explorer' ) {
            // but only for IE, other browsers are handled by :hover in css
            rows[i].onmouseover = function() {
                this.className += ' hover';
            }
            rows[i].onmouseout = function() {
                this.className = this.className.replace( ' hover', '' );
            }
        }
        // Do not set click events if not wanted
        if (rows[i].className.search(/noclick/) != -1) {
            continue;
        }
    }
}
window.onload=markRowsInit;
