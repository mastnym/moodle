<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * English strings for papertest
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_papertest
 * @copyright  2011 Martin Mastny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Generování papírových testů';
$string['modulenameplural'] = 'Papírové testy';
$string['modulename_help'] = 'Slouží pro generování testů z Banky úloh ve formátu "doc"';
$string['moduleusage'] = 'Generuj test pro {$a}';
$string['papertestname'] = 'Generátor testů';
$string['papertestname_help'] = 'Jméno pro modul, např.: Generátor testů';
$string['papertest'] = 'Papírové testy';
$string['pluginadministration'] = 'Testy - administrace';
$string['pluginname'] = 'Generování papírových testů';
$string['defaultname'] = 'Generování papírových testů';

$string['downloaddoc'] = 'Stáhni test';
$string['downloadzip'] = 'stáhni zip s testy';

//rules
$string['rules'] = 'Pravidla generátoru';
$string['rule1'] = 'Kategorie otázek korespondují s těmi v Moodle';
$string['rule2'] = 'Pořadí kategorií je stejné jako v Moodle';
$string['rule3'] = 'Je potřeba alespoň jedna kategorie v Bance úloh';
$string['rule4'] = 'Pouze otázky z nejspodnějších kategorií jsou v testu zohledněny';
$string['rule5'] = 'Pokud změníte pořadí kategorií v Moodle, načtěte tuto stránku znova';
$string['rule6'] = 'Pokud si vyberete jinou než nejvrchnější kategorii tak budou exportovány všechny otázky z vybrané kategorie';
$string['rule7'] = 'Pole poznámky v nastavení slouží k objasnění kategorie';

//form
$string['choosecategoryheader'] = 'Vyberte kategorii a parametry';
$string['choosecategory'] = 'Vyberte kategorii';
$string['copies'] = 'Počet variant';
$string['copies_validation'] = 'Musí být nenulové číslo';
$string['show_numbers'] = 'Generovat pořadí otázek';
$string['show_points'] = 'Ukázat body za jednotlivé otázky';
$string['showanswers'] = 'Generovat také kopii s výsledky';
$string['all'] = 'Exportovat všechny otázky z vybrané kategorie';
$string['generate'] = 'Generovat';
$string['editsettings'] = 'Upravit nastavení';
$string['warning'] = 'V chemických vzorcích v testu se vyskytla chyba (test {$a}), je označena červenou barvou, prosím zkontrolujte správnost testu';
$string['ziperror'] = 'Nemohu vytvořit zip';

//settings
$string['settings'] = 'Nastavení';
$string['addsettings'] = 'Další nastavení';
$string['choosecategoryheaderandparams'] = 'Vyberte kategorii a doplńte parametry';
$string['settingssubmit'] = 'Upravit kategorii';
$string['nextedit'] = 'Další kategorie k editaci';
$string['savenext'] = 'Ulož a pokračuj na další kategorii';

$string['alternatename'] = 'Náhradní jméno';
$string['alternatenameerror'] = 'Může mít maximálně 512 znaků';
$string['alternatename_help'] = 'Pokud je vyplňeno, bude použito místo originálního jména kategorie';
$string['instructions'] = 'Instrukce ke kategorii';
$string['instructions_help'] = 'Pokud je vyplněno, objeví se u názvu kategorie';
$string['display'] = 'Použít kategorii v testu';
$string['display_help'] = 'Pokud není zaškrtnuté, tak se kategorie v testu neprojeví';
$string['points'] = 'Náhradní body za kategorii';
$string['points_help'] = 'Pokud jsou různé od nuly použijí se pro každou otázku v kategorii místo originálních bodů';
$string['questions'] = 'Počet otázek v kategorii';
$string['questions_help'] = 'Kolik otázek bude generováno do této kategorie';
$string['questionserror'] = 'Musí být alespoň 1, pokud nechcete kategorii v testu, odškrtňete použít kategorii';
$string['spaces'] = 'Počet prázdných odstavců';
$string['spaces_help'] = 'Vynechané místo pro odpověď';
$string['display_points'] = 'Zobrazit body';
$string['display_points_help'] = 'Za názvem kategorie zobrazit celkový počet bodů';


$string['categorysaved'] = 'Nastavení kategorie bylo úspěšné';
$string['points_test'] = '({$a}&nbsp;b.)';
$string['no_writing'] = 'Zde nepište!!!';

//settings
$string["no_capability"] = "Nemáte dostatečná oprávnění";

//cron
$string['nodir'] = 'Složka pro testy neexistuje';
$string['removed'] = 'Odstraněno: {$a}';
$string['notremoved'] = 'Nemohu odstranit: {$a} ';