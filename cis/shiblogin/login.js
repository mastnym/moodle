var lang = $('html').attr('lang');
var novscht = lang == "en" ? "I use other email address to login" : "Pro přihlášení používám jinou emailovou adresu";
$(".loginsub").before('<div id="shiblogin" class="subcontent "/><div class="subcontent"><a href="#" id="show-regular-login">'+novscht +'</a></div>');

var login_text = lang == "en" ? "Login with ICT account(@vscht.cz)" : "Přihlásit se pomocí údajů VŠCHT Praha"
var shib_form = $('<form method="GET" action="/auth/shibboleth/index.php"><input type="submit" class="form-submit" value="'+login_text+'"/></form>');
$("#shiblogin").append(shib_form);
$(document).ready(function(){
    $("#show-regular-login").click(function(){
        $(".loginsub").toggle();
        return false;
    });
})
