README:
JAK ZPROVOZNIT MODUL UACH:

1)Vlozit slozku uach do moodle/mod

2)Provest nahrazeni:
  uach/uach_files/draftfile.php -> moodle/draftfile.php
  uach/uach_files/lib.php -> moodle/lib/editor/tinymce/lib.php
  uach/uach_files/module.js -> moodle//lib/editor/tinymce/module.js 
  uach/uach_files/formslib.php -> moodle/lib/formslib.php 
  uach/uach_files/questionlib.php -> moodle/lib/questionlib.php
  uach/uach_files/editlib.php -> moodle/question/editlib.php  
  uach/uach_files/edit.php -> moodle/question/edit.php 
  uach/uach_files/question.php -> moodle/question/question.php 
  uach/uach_files/edit_question_form.php -> moodle/question/type/edit_question_form.php

3)Vytvorit uzivatele
  Username:"ucitel"
  Password:"Teacher1."

4)nastavit soubor moodle/mod/uach/uach_config.php (java se nachazi ve slozce moodle/mod/uach/java)

5)Pokud moodle hlasi pri generovani error o nedostatku pameti(soubor categories_all.xml >= 30MB) - pridat do 
    moodle/config.php  "ini_set('memory_limit', '-1');"
    -1 se da nahradit konkretnim cislem vyhrazene op. pameti napr. 512M 

        
