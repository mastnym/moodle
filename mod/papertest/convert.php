<?php
require_once("question_types/tokenizer.php");
require_once("question_types/parser.php");
$chemStructs=array();
if (isset($_POST["val"])){
	$chemStructs=(array)json_decode($_POST["val"]);	
}

foreach ($chemStructs as $key=>$struct){
	$html="";
	
	$wordTokenizer=new WordTokenizer($struct);
	$wordToken = $wordTokenizer->next();
	while ($wordToken->type != WordToken::END){
		if ($wordToken->type == WordToken::FORMULA || $wordToken->type == WordToken::POSSIBLE_FORMULA){
			try{
				$tok = new ChemicalTokenizer($wordToken->value);
				$parser = new Parser($tok);
				$res=$parser->parse();
				$html.= createHtmlFromTokens($res);
			
			}
			catch (Exception $ex){
				$html.=$wordToken->value;
			}
		}
		else{//text
			$html.=$wordToken->value;
		}
		$wordToken=$wordTokenizer->next();
		
	}
	$chemStructs[$key]=$html;
}

echo json_encode($chemStructs);


function createHtmlFromTokens($tokens){
	$html="";
	$i=0;
	foreach ($tokens as $token){
		$nextToken=null;
		if (isset($tokens[$i+1])){
			$nextToken=$tokens[$i+1];
		}
		$val=$token->value;
		if ($token->italic){
			$val="<em>".$val."</em>";
		}
			
		if ($token->pos == MoleculePart::SUB && $nextToken && $nextToken->pos == MoleculePart::SUP){
			$spacing=strlen($token->value)*5;//magic
			$html.="<sub><span style='position:relative;letter-spacing:-".$spacing.".0pt'>".$token->value."</span></sub>";
		}
		else if ($token->pos == MoleculePart::SUB){
			$html.="<sub>".$token->value."</sub>";
		}
		else if($token->pos == MoleculePart::SUP){
			$html.="<sup>".$token->value."</sup>";
		}
		else{
			$html.=$token->value;
		}
		$i++;
	}
	return $html;
}
?>