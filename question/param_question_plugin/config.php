<?php
$CFG->param_question_subjects= Array('CIS'=>true,'N101002'=>true,'CHI'=>true);
$CFG->sample_draft_id="452829389";//udela se tak, ze se provede 1 obycejny import a mdl_files se pak smaze posledni draft,
						// KTERY NEMA FILENAME (MA .), TIM SE NEODSTRANI Z DATABAZE
						// A IMPORT JE OKLAMAN, draft se zaroven nesmaze protoze tam neni soubor s teckou