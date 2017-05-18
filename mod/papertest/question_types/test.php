<?php
require_once 'tokenizer.php';
require_once 'parser.php';
require_once 'qtypes.php';
$tok2 = new ChemicalTokenizer("(He(Ψ+ii))");
$token2=$tok2->next();
while ($token2->type != ChemToken::END){
	echo "      ".$token2->str();
	$token2=$tok2->next();
}

echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
$testStrings=array (" As(s) + NaClO(aq) + NaOH(aq) -> Na3AsO4(aq) + NaCl(aq)",
					" As(s) + NaClO(aq) + NaOH(aq) -&gt; Na3AsO4(aq) + NaCl(aq)","CS2(l)  +   OH1-(aq)     ->     CO32-(aq)   +   CS32-(aq)",
					"Hg2","Hg222","26Fe","26Fe2+","26Fe2+H","H2SO4","H2S2iO4","2PbO4.PbO","Hg2+","(NH4)1+","Hg-2","(SO4)-vii",
					"(He(Ψ+ii))","(He(Ψ8))","(SO4)22-","(SO4)2222-","Na2SO4.10H2O.5CO2","Na2(SO)4 + 10H2O -> Na2SO4.10H2O",
					"N2(g) -> H2O (l)","CO2 + e- -> Na2SO4.10H2O(l)",
					"H2S+viO4","O-ii");

foreach ($testStrings as $ts){
	echo $ts;
	echo "<br/>";
	try{
		$tok = new ChemicalTokenizer(trim($ts));
		$parser = new Parser($tok);
		$res=$parser->parse();
		echo generalQtype::createHtmlFromTokens($res);
		
	}
	catch (Exception $ex){
		print_r($ex);
	}
	echo "<br/>";
	echo "<br/>";
	echo $ts . " done";
	echo "<br/>";
	echo "-----------------------------------------------------------------------------------";
	echo "<br/>";
	
	
	
}