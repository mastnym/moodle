<?php
//settings
$string['pluginname'] = 'Synchronizace kategorií s externím systémem';

$string['enabled']='Zapnout/vypnout plugin';
$string['enabled_desc']='';

$string['csvfile']='Cesta k CSV';
$string['csvfile_desc']='Lokalní nebo vzdálená cesta(např.: /tmp/csv.csv or https://domain.com/csv.csv)';

$string['mode']='Mód';
$string['mode_desc']='Vytváření nebo update kurzů nebo obojí';

$string['usecertificateauth']='Certifikace';
$string['usecertificateauth_desc']='Použít certifikát pro autentizaci přístupu k CSV';

$string['certpath']='Absolutní cesta k certifikátu(PEM)';
$string['certpath_desc']='Specifikujte absolutní cestu k certifikátu (PEM) (použito v případě, že je zaškrtnuta certifikace)';
$string['keypath']='Absolutní cesta k privátnímu klíči(KEY)';
$string['keypath_desc']='Specifikujte absolutní cestu ke klíči (KEY) (použito v případě, že je zaškrtnuta certifikace)';


//lang
$string['noconfig']='Nepodařilo se načíst konfiguraci';
$string['pluginnotenabled']='Plugin je vypnutý';
$string['nocsv']='Nemohu načíst CSV, zkontrolujte nastavení pluginu';
$string['nocategories']='V CSV nejsou žádné kategorie';
$string['csvbroken']='CSV soubor nemá správný formát';
$string['notopmost']='v CSV musí být alespoň jedna kategorie #TOP#';
$string['invalidparent']='Nadřazená kategorie neexistuje pro kategorii: {$a}';
$string['moodleexception']='Moodle vyjímka: {$a}';
$string['categorynotinmoodle']='Kategorie s id {$a} není v Moodle';
$string['invalidcertpath']='Nemohu nalézt certifikát';
$string['invalidkeypath']='Nemohu nalézt privátní klíč';
$string['csvmustbehttps']='Pokud používáte certifikátovou autentizaci, použijte https';
$string['httpnocsv']='Nemohu načíst CSV: návratová hodnota(https): {$a}';
$string['categoryexists']='Nemohu vytvořit kategorii, kategorie se stejným idnumber již existuje ({$a})';
$string['noneedtoupdate']='Nepotřebuje update - {$a}';
$string['processing']='Zpracovávám kategorii - {$a}';

?>
