
$(".loginsub").after('<div id="shiblogin" class="subcontent ">');
var lang = $('html').attr('lang');
var login_text = lang == "en" ? "Login with ICT account(@vscht.cz)" : "Přihlásit se pomocí údajů VŠCHT Praha"
var shib_form = $('<form method="GET" action="/auth/shibboleth/index.php"><input type="submit" class="form-submit" value="'+login_text+'"/></form>');
$("#shiblogin").append(shib_form);
