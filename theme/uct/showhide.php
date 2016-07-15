<?php
require_once '../../config.php';
global $SESSION;
$show = optional_param("show", "show", PARAM_ALPHA);

$SESSION->showside = $show;

echo $show;



