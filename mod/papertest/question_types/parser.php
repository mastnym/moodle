<?php

class MoleculePart{
	const SUP = "SUPERSCRIPT";
	const BASE = "BASELINE";
	const SUB = "SUBSCRIPT";
	
	function __construct($value,$position,$italic=false,$bold=false){
		$this->value=$value;
		$this->pos=$position;
		$this->italic=$italic;
		$this->bold=$bold;
	}
	function str(){
		return "VALUE: ".$this->value.", POSITION: ".$this->pos."<br/>";
	}
	
	
}

class Parser{
	public static $ELEMENTS2MASSES = array('Ac' => 89, 'Ag' => 47, 'Al' => 13, 'Am' => 95, 'Ar' => 18, 'As' => 33, 'At' => 85,
											 'Au' => 79, 'B' => 5, 'Ba' => 56, 'Be' => 4, 'Bh' => 107, 'Bi' => 83, 'Bk' => 97,
											 'Br' => 35, 'C' => 6, 'Ca' => 20, 'Cd' => 48, 'Ce' => 58, 'Cf' => 98, 'Cl' => 17,
											 'Cm' => 96, 'Cn' => 112, 'Co' => 27, 'Cr' => 24, 'Cs' => 55, 'Cu' => 29, 'Db' => 105,
											 'Ds' => 110, 'Dy' => 66, 'Er' => 68, 'Es' => 99, 'Eu' => 63, 'F' => 9, 'Fe' => 26,
											 'Fm' => 100, 'Fr' => 87, 'Ga' => 31, 'Gd' => 64, 'Ge' => 32, 'H' => 1, 'He' => 2,
											 'Hf' => 72, 'Hg' => 80, 'Ho' => 67, 'Hs' => 108, 'I' => 53, 'In' => 49, 'Ir' => 77,
											 'Kr' => 36, 'K' => 19, 'La' => 57, 'Li' => 3, 'Lr' => 103, 'Lu' => 71, 'Md' => 101,
											 'Mg' => 12, 'Mn' => 25, 'Mo' => 42, 'Mt' => 109, 'N' => 7, 'Na' => 11, 'Nb' => 41,
											 'Nd' => 60, 'Ne' => 10, 'Ni' => 28, 'No' => 102, 'Np' => 93, 'O' => 8, 'Os' => 76,
											 'P' => 15, 'Pa' => 91, 'Pb' => 82, 'Pd' => 46, 'Pm' => 61, 'Po' => 84, 'Pr' => 59,
											 'Pt' => 78, 'Pu' => 94, 'Ra' => 88, 'Rb' => 37, 'Re' => 75, 'Rf' => 104, 'Rg' => 111,
											 'Rh' => 45, 'Rn' => 86, 'Ru' => 44, 'S' => 16, 'Sb' => 51, 'Sc' => 21, 'Se' => 34,
											 'Sg' => 106, 'Si' => 14, 'Sm' => 62, 'Sn' => 50, 'Sr' => 38, 'Ta' => 73, 'Tb' => 65,
											 'Tc' => 43, 'Te' => 52, 'Th' => 90, 'Ti' => 22, 'Tl' => 81, 'Tm' => 69, 'U' => 92,
											 'Uuh' => 116, 'Uuo' => 118, 'Uup' => 115, 'Uuq' => 114, 'Uus' => 117, 'Uut' => 113,
											 'V' => 23, 'W' => 74, 'Xe' => 54, 'Y' => 39, 'Yb' => 70, 'Zn' => 30, 'Zr' => 40 );
	
	
	function __construct($tok){
		$this->chemTok=$tok;
		$this->token=$this->chemTok->next();
		
		$this->parsed=array();
		
		$this->remembered=array();
		$this->timesInMolecule=false;
		$this->rememberTokens=false;
		$this->toPop=0;
		
	}
	
	function parse(){
		$this->parseMolecule();
		
		$this->expect(ChemToken::SPACE,ChemToken::END,ChemToken::STATE);
		
		if ($this->token->isType(ChemToken::STATE)){
			$this->parseState();
		}
		
		while ($this->token->isType(ChemToken::SPACE)){
			$this->timesInMolecule=false;
			$this->eatSpace();
			$this->expect(ChemToken::SIGN);
			
			$sign=$this->token->value;
			$this->next();
			$this->expect(ChemToken::SPACE,ChemToken::SYMBOL);
			if ($this->token->isType(ChemToken::SYMBOL)){
				$sign.=$this->token->value;
				$this->next();
			}
			$this->addPart($sign, MoleculePart::BASE);
			
			$this->eatSpace();
			
			if ($this->token->isType(ChemToken::ELECTRON)){
				$this->parseElectron();
				$this->expect(ChemToken::SPACE,ChemToken::SIGN);
				if ($this->token->isType(ChemToken::SIGN)){
					$this->addPart($this->token->value, MoleculePart::SUP);
					$this->next();
				}
			}else{//once again
				$this->parseMolecule();
				if ($this->token->isType(ChemToken::STATE)){
					$this->parseState();
				}
			}
			
		}
		$this->expect(ChemToken::END);
		return $this->parsed;
	}
	
	
	function parseMolecule(){
		if ($this->token->isType(ChemToken::NUMBER)){
			$this->startRemembering();
			try{
				$num=$this->parseNumber(3);
				$this->addPart($num, MoleculePart::SUB);
				$this->toPop+=1;
				$this->expect(ChemToken::ELEMENT);
				$element = $this->token->value;
				$this->addPart($element, MoleculePart::BASE);
				$this->next();
				
				$this->checkAtomicNumber($element, $num);
				
				if ($this->token->isType(ChemToken::END)){
					return;
				} 
				$this->parseCharge();
				return;
				
			}catch (Exception $e){
				$this->rememberTokens=false;
				while($this->toPop!=-1){
					$last=array_pop($this->parsed);
					$this->toPop-=1;
				}
				$this->toPop=0;
			}
			$this->next();
			$num=$this->parseNumber(2);
			$this->addPart($num, MoleculePart::BASE);
		}
		
		$this->parseRealMolecule();
		if ($this->token->isType(ChemToken::TIMES) && ! $this->timesInMolecule ){
				$this->timesInMolecule=true;
				$this->addPart($this->token->value, MoleculePart::BASE);
				$this->next();
				$this->parseMolecule();
			
		}
	}
	
	function parseRealMolecule(){
		while ($this->token->isType(ChemToken::L_BR,ChemToken::ELEMENT,ChemToken::GREEK_SYMBOL)){
			if ($this->token->isType(ChemToken::L_BR)){
				$this->addPart($this->token->value, MoleculePart::BASE);
				$this->next();
				while (!$this->token->isType(ChemToken::R_BR)){
					$this->expect(ChemToken::L_BR,ChemToken::ELEMENT,ChemToken::GREEK_SYMBOL);
					$this->parseRealMolecule();
				}
				$this->addPart($this->token->value, MoleculePart::BASE);
				$this->next();
				if ($this->token->isType(ChemToken::NUMBER)){
					$this->parseAfterElementPart();
				}
				//element, greek symbol
			}else{
				$this->parseMoleculePart();
			}	
		}
	}
	
	function parseMoleculePart(){
		$this->expect(ChemToken::ELEMENT,ChemToken::GREEK_SYMBOL);
		if ($this->token->isType(ChemToken::ELEMENT)){
			$this->addPart($this->token->value, MoleculePart::BASE);
			$this->next();
			if ($this->token->isType(ChemToken::SIGN,ChemToken::NUMBER)){
				$this->parseAfterElementPart();
			}
		}
		//Greek symbol
		else{
			$this->addPart($this->token->value, MoleculePart::BASE);
			$changeBehav=false;
			if ($this->token->value == "η"){
				$changeBehav=true;
			}
			$this->next();
			if ($this->token->isType(ChemToken::NUMBER)){
				$this->parseElementMultiplicity($changeBehav);
			}
			if ($this->token->isType(ChemToken::SIGN)){
				$this->addPart($this->token->value, MoleculePart::BASE);
				$this->next();
			}
		}
	}
	
	function parseNumber($maxDigits){
		$this->expect(ChemToken::NUMBER);
		$num="";
		while($maxDigits>0 && $this->token->isType(ChemToken::NUMBER)){
			$num.=$this->token->value;
			$maxDigits -= 1;
			$this->next();
		}
		return $num;
	}
	
	function parseCharge(){
		$num=$this->parseNumber(1);
		$this->expect(ChemToken::SIGN);
		if ($num=="1"){
			$num=="";
		}
		$this->addPart($num.$this->token->value, MoleculePart::SUP);
		$this->next();
	}
	function parseElementMultiplicity($changeDef){
		$num=$this->parseNumber(2);
		if (!$changeDef){
			$this->addPart($num, MoleculePart::SUB);
		}
		else{
			$this->addPart($num, MoleculePart::SUP);
		}
	}
	
	function parseAfterElementPart(){
		if ($this->token->isType(ChemToken::NUMBER)){
			$nums=array();
			$i=0;
			while($this->token->isType(ChemToken::NUMBER) && $i<3){
				$nums[$i]=intval($this->token->value);
				$i+=1;
				$this->next();
			}
			if (!$this->token->isType(ChemToken::SIGN)){
				if ($i>2){
					throw new Exception("Multiplicity has more than 2 digits");
				}
				$multiplicity=$this->createNumber($nums, $i);
				$this->addPart($multiplicity, MoleculePart::SUB);
			}
			else{
				$sign=$this->token->value;
				$this->next();
				if ($this->token->isType(ChemToken::OXIDATION_NUMBER)){
					$multiplicity=$this->createNumber($nums, $i);
					$this->addPart($multiplicity, MoleculePart::SUB);
					$oxState=$this->createStateRepr($sign, $this->token->value);
					$this->addPart($oxState, MoleculePart::SUP);
					$this->next();
				}
				else{
					$multiplicity=$this->createNumber($nums, $i-1);
					if ($multiplicity==0){
						$multiplicity="";
					}
					$this->addPart($multiplicity, MoleculePart::SUB);
					
					$charge=$sign;
					if ($nums[$i-1]!=1){
						$charge=strval($nums[$i-1]).$sign;
					}
					$this->addPart($charge, MoleculePart::SUP);
					
				}
			}
		}
		//SIGN
		else{
			$this->expect(ChemToken::SIGN);
			$sign=$this->token->value;
			$this->next();
			$this->expect(ChemToken::OXIDATION_NUMBER);
			$oxState=$this->createStateRepr($sign, $this->token->value);
			$this->addPart($oxState, MoleculePart::SUP);
			$this->next();
		}
	}
	function parseState(){
		$this->expect(ChemToken::STATE);
		$this->addPart($this->token->value, MoleculePart::BASE,true);
		$this->next();
		
	}
	function parseElectron(){
		$this->expect(ChemToken::ELECTRON);
		$this->addPart($this->token->value, MoleculePart::BASE);
		$this->next();
	}
	
	function createStateRepr($sign,$value){
		if ($sign=="+"){
			$sign="";
		}
		return $sign.strtoupper($value);
	}
	
	function createNumber($numbers,$maxDigits){
		$sum=0;
		for ($i=0;$i<$maxDigits;$i++){
			$sum=$sum*10+$numbers[$i];
		}
		return $sum;
	}
	
	
	function startRemembering(){
		$this->rememberTokens = true;
		$this->remembered[] = $this->token;
	}
	function expect($type,$type1=null,$type2=null){
		if (!$this->token->isType($type,$type1,$type2)){
			throw new Exception("Expecting: ".$type." or ".strval($type1)." or ".strval($type2)." Got: ".$this->token->str());
		}
	}
	
	
	function next(){
		if ($this->rememberTokens){
			$this->token=$this->chemTok->next();
			$this->remembered [] = $this->token;
		}
		else{
			if (empty($this->remembered)){
				$this->token=$this->chemTok->next();
			}else{
				$this->token= array_shift($this->remembered);
			}
		}
	}
	
	function addPart($value,$position){
		$this->parsed []= new MoleculePart($value, $position);
	}
	
	function eatSpace(){
		while($this->token->isType(ChemToken::SPACE)){
			$this->addPart($this->token->value, MoleculePart::BASE);
			$this->next();
		}
	}
	function checkAtomicNumber($el,$num){
		if (!isset(self::$ELEMENTS2MASSES[$el])){
			throw new Exception("Element ".$el." does not have a mass");
		}
		return self::$ELEMENTS2MASSES[$el]==$num;
	} 
	
}