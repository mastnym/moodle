<?php


class WordTokenizer{
	public static $DELIMETER_CHARS=array (' ','!','?','.',',',';',':');
	public static $POSSIBLE_FORMULA_REGEX = "/([A-Z][a-z]?[0-9]+)/";
	function __construct($questionText){
		$this->text=$questionText;
		$this->it = new CharacterIterator($this->text);
		$this->buf = "";
	}
	
	
	function checkDollars(){
		$text=$this->text;
		if ( (strlen($text)-strlen(str_replace("$", "", $text)) ) %2!=0 ){
			throw new Exception("wrong dollar markup ". $text);
		}
	}
	function next(){
		$cur=$this->it->current();
		
		if($cur!=CharacterIterator::DONE){
			if ($cur=="$"){
				$cur= $this->it->next();
				while($cur!="$"){
					$this->buf.=$cur;
					$cur= $this->it->next();
				}
				$cur= $this->it->next();
				
				$ret=$this->buf;
				$this->buf="";
				return new WordToken(WordToken::FORMULA, trim($ret));	
			}
			
			else if (in_array($cur, WordTokenizer::$DELIMETER_CHARS)){
				$ret=$cur;
				$cur= $this->it->next();
				return new WordToken(WordToken::TEXT, $ret);
			}
			
			else{
				$this->buf.=$cur;
				$cur= $this->it->next();
				while ($cur != "$" && !in_array($cur, WordTokenizer::$DELIMETER_CHARS) && $cur!=CharacterIterator::DONE){
					$this->buf.=$cur;
					$cur= $this->it->next();
				}
				$ret=$this->buf;
				$this->buf="";
				if (preg_match(WordTokenizer::$POSSIBLE_FORMULA_REGEX, $ret)){
					return new WordToken(WordToken::POSSIBLE_FORMULA, trim($ret));
				}
				return new WordToken(WordToken::TEXT, $ret);
			}
		}
			
		return new WordToken(WordToken::END, "");
	}
	
	
}


class ChemicalTokenizer{
	public static $ELEMENTS= array ("Ac","Ag","Al","Am","Ar","As","At","Au","B","Ba","Be","Bh","Bi",
									"Bk","Br","C","Ca","Cd","Ce","Cf","Cl","Cm","Cn","Co","Cr","Cs",
									"Cu","Db","Ds","Dy","Er","Es","Eu","F","Fe","Fm","Fr","Ga","Gd",
									"Ge","H","He","Hf","Hg","Ho","Hs","I","In","Ir","K","Kr","La","Li",
									"Lr","Lu","Md","Mg","Mn","Mo","Mt","N","Na","Nb","Nd","Ne","Ni","No",
									"Np","O","Os","P","Pa","Pb","Pd","Pm","Po","Pr","Pt","Pu","Ra","Rb",
									"Re","Rf","Rg","Rh","Rn","Ru","S","Sb","Sc","Se","Sg","Si","Sm","Sn",
									"Sr","Ta","Tb","Tc","Te","Th","Ti","Tl","Tm","U","Uuh","Uuo","Uup",
									"Uuq","Uus","Uut","V","W","Xe","Y","Yb","Zn","Zr");
	public static $OTHER_CHEMICAL_COMPOUNDS= array ("Ph");
	public static $COMPOUNDS_STARTING_WITH_MINUS= array ("-O,O'","-O,S");
	public static $STATES= array ("aq","g","s","l");
	public static $MAX_ELEMENTS_LENGTH;//max len
	
	function __construct($formula){
		self::$MAX_ELEMENTS_LENGTH=max(array_map('strlen', self::$COMPOUNDS_STARTING_WITH_MINUS));
		$this->f=$this->replaceCharacters($formula);
		$this->it=new CharacterIterator($this->f);
		$this->buf="";
	}
	
	function replaceCharacters($formula){
		$formula=str_replace(array("–","—"),"-",$formula);
		$formula=str_replace(array("‘","´"),"'",$formula);
		return $formula;
	}
	
	function next(){
		$cur=$this->it->current();
		if ($cur!=CharacterIterator::DONE){
			//element
			if (ctype_upper($cur)){
				$this->addToBuffer($cur);
				$cur=$this->getNext();
				while(ctype_lower($cur)){
					$this->addToBuffer($cur);
					$cur=$this->getNext();
				}
				$ret = $this->buf;
				if (!$this->isElement($ret)){
					throw new Exception("not an element ".$ret);
				}
				$this->emptyBuffer();
				return new ChemToken(ChemToken::ELEMENT, $ret);			
			}
			//number
			else if(ctype_digit($cur)){
				$c=$this->getNext();
				return new ChemToken(ChemToken::NUMBER, $cur);
			}
			// + -
			else if ($cur=="+" || $cur == "-"){
				$el=$cur;
				$c= $this->getNext();
				$el.=$c;
				$beginIndex=$this->it->key();
				//+/-
				if (in_array($c, array("v","i"," ",">","&",CharacterIterator::DONE))){
					return new ChemToken(ChemToken::SIGN, $cur);
				}//complicated compounds
				else{
					while(strlen($el)<self::$MAX_ELEMENTS_LENGTH+1){
						if (in_array($el, self::$COMPOUNDS_STARTING_WITH_MINUS)){
							$c=$this->getNext();
							return new ChemToken(ChemToken::ELEMENT, $el);
						}
						$el.=$this->getNext();
					}
					$this->it->setKey($beginIndex);
					return new ChemToken(ChemToken::SIGN, $cur);
				}
			}
			else if ($cur=="v" || $cur == "i"){
				$this->addToBuffer($cur);
				$cur=$this->getNext();
				while ($cur == "i" || $cur == "v"){
					$this->addToBuffer($cur);
					$cur=$this->getNext();
				}
				$ret = $this->buf;
				$this->emptyBuffer();
				return new ChemToken(ChemToken::OXIDATION_NUMBER, $ret);
			}
			else if (strpos("([{", $cur)!==false){
				$br=$cur;
				$cur = $this->getNext();
				$index = $this->it->key();
				$localbuf="";
				
				while (strpos("()[]{}", $cur) === false){
					$localbuf.=$cur;
					$cur=$this->getNext();
				}
				if (strpos("([{", $cur)!==false || strlen($localbuf)>self::$MAX_ELEMENTS_LENGTH){
					$this->it->setKey($index);
					return new ChemToken(ChemToken::L_BR, $br);
				}
				elseif (strpos(")]}", $cur)!==false){
					if (in_array($localbuf, self::$STATES)){
						$c=$this->getNext();
						return new ChemToken(ChemToken::STATE,$br.$localbuf.$cur);
					}
					else{
						$this->it->setKey($index);
						return new ChemToken(ChemToken::L_BR, $br);
					}
				}
				
				
			}
			else if (strpos(")]}", $cur)!==false){
				$c = $this->getNext();
				return new ChemToken(ChemToken::R_BR, $cur);
			}
			else if (strpos(">&", $cur)!==false){
				if ($cur=="&"){
					while ($cur != ";"){
						$cur=$this->getNext();
					}
				}
				$cur=$this->getNext();
				return new ChemToken(ChemToken::SYMBOL, ">");
			}
			else if ($cur == "."){
				$c = $this->getNext();
				return new ChemToken(ChemToken::TIMES, $cur);
			}
			else if (preg_match("/\\s|\xc2\xa0/", $cur)){
				$c = $this->getNext();
				return new ChemToken(ChemToken::SPACE, $cur);
			}
			else if (preg_match('/\p{Greek}/',$cur)){
				$c = $this->getNext();
				return new ChemToken(ChemToken::GREEK_SYMBOL, $cur);
			}
			
			else if (ctype_lower($cur)){
				$this->addToBuffer($cur);
				$cur=$this->getNext();
				while (ctype_lower($cur)){
					$this->addToBuffer($cur);
					$cur=$this->getNext();
				}
				$ret = $this->buf;
				$this->emptyBuffer();
				if ($ret=='e'){
					return new ChemToken(ChemToken::ELECTRON, $ret);
				}
				return new ChemToken(ChemToken::ELEMENT, $ret);
			}else {
				throw  new Exception("Unexpected token "+ $cur);
			}
			
			
		}
		return new ChemToken(ChemToken::END, "");
	}
	
	
	function emptyBuffer(){
		$this->buf="";
	}
	function addToBuffer($cur){
		$this->buf.=$cur;
	}
	function getNext(){
		return $this->it->next();
	}
	function isElement($el){
		return in_array($el, self::$ELEMENTS) || in_array($el, self::$OTHER_CHEMICAL_COMPOUNDS);
	}
	
}


class Token{
	function __construct($type,$value){
		$this->type=$type;
		$this->value=$value;
	}
	function str(){
		return "TYPE: ".$this->type.", VALUE: ".$this->value."<br/>";
	}
}

class WordToken extends Token{
	const FORMULA = 'FORMULA';
	const POSSIBLE_FORMULA = 'POSSIBLE_FORMULA';
	const TEXT = 'TEXT';
	const END = 'END';
}


class ChemToken extends Token{
	const ELEMENT="ELEMENT";    // capital followed by small letters
	const NUMBER="NUMBER";		//any number
	const SIGN="SIGN";			//+,-
	const OXIDATION_NUMBER="OXIDATION_NUMBER";	// small letters v,i
	const L_BR="L_BR";			//all types of left brackets
	const R_BR="R_BR";		//all types of right brackets
	const GREEK_SYMBOL="GREEK_SYMBOL";//all greek letters
	const SYMBOL="SYMBOL";	//  Symbols:">."
	const SPACE="SPACE";//one or more spaces
	const ELECTRON="ELECTRON";//just letter e
	const TIMES="TIMES"; // .
	const STATE="STATE";	//(g),(aq),(s),(l)
	const END="END";
	
	function isType(){
		$types=func_get_args();
		foreach ($types as $type){
			if ($this->type==$type){
				return true;
			}
		}
		return false;
	}
}



class CharacterIterator implements Iterator
{
	
	const DONE = "!!done!!";
	
	protected $_contents;
	protected $_offset;


	public function __construct($contents)
	{
		$this->_contents = $this->mb_str_split($contents);
		$this->_offset=0;
	}

	public function current()
	{
		if ($this->valid()){
			return $this->_contents[$this->key()];
		}
		return CharacterIterator::DONE;
	}

	public function next()
	{
		$this->_offset++;
		return $this->current();
	}
	
	//not needed right now, must be implemented
	public function key(){
		return $this->_offset;
	}
	public function rewind(){
		$this->_offset=0;
	}
	public function valid(){
		return isset($this->_contents[$this->key()]);
	}
	public function setKey($key){
		$this->_offset=$key;
	}
	
	function mb_str_split( $string ) {
		return preg_split('/(?<!^)(?!$)/u', $string );
	}
}