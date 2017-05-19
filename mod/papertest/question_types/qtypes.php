<?php
require_once 'tokenizer.php';
require_once 'parser.php';
class generalQtype{

	public function __construct($question,$category,$count,&$generator)
	{
		$this->question=$question;
		$this->category=$category;
		$this->count=$count;
		$this->generator = $generator;

		$this->findChemicalFormulas();
		$this->saveImages();
		$this->failed=false;

	}
	function findChemicalFormulas(){
		$text=$this->question->questiontext;
		$qtext=$this->parseChemFormula($text);
		$this->question->questiontext=$qtext;
	}
	function parseChemFormula($text){
		$ret="";
		$wordTok=new WordTokenizer($text);
		$wordToken=$wordTok->next();
		while ($wordToken->type !=WordToken::END){
			if ($wordToken -> type == WordToken::FORMULA){
				try{
					$chemTok = new ChemicalTokenizer(trim($wordToken -> value));
					$parser= new Parser($chemTok);
					$result =$parser->parse();
					$html=$this->createHtmlFromTokens($result);
					$ret.=$html;
				}catch (Exception $e){
					$failtext=$this->createConversionFailure($wordToken -> value);
					$ret.=$failtext;
					$this->failed=true;
				}
			}
			else{
				$ret.=$wordToken->value;
			}
			$wordToken=$wordTok->next();
		}
		return $ret;
	}

	function failed(){
		return $this->failed;
	}

	function saveImages(){
		//create images in text
		if (!empty($this->question->questiontextfiles) || strpos($this->question->questiontext, 'data:image/') !== FALSE){
			$this->saveImage($this->question->questiontext,$this->question->questiontextfiles);
		}
		//and in answers
		if (!empty($this->question->options->answers)) {
			foreach ($this->question->options->answers as $answer) {
				$this->saveImage($answer->answer,$answer->answerfiles);
			}
		}
	}

	function createConversionFailure($tokenValue){
		return "<span style='color:red; fontsize:20px;'>".$tokenValue."</span>";
	}

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
				$html.="<sub><span style='position:relative;top:2.0pt;mso-text-raise:-2.0pt;letter-spacing:-".$spacing.".0pt'>".$token->value."</span></sub>";
			}
			else if ($token->pos == MoleculePart::SUB){
				$html.="<sub><span style='position:relative;top:2.0pt;mso-text-raise:-2.0pt'>".$token->value."</span></sub>";
			}
			else if($token->pos == MoleculePart::SUP){
				$html.="<sup><span style='position:relative;top:-2.0pt;mso-text-raise:2.0pt'>".$token->value."</span></sup>";
			}
			else{
				$html.=$token->value;
			}
			$i++;
		}
		return $html;
	}

	function saveImage( &$text,$files){
		$d = new DOMDocument('1.0', 'utf-8');
		$text=mb_convert_encoding($text, 'HTML-ENTITIES', "UTF-8");//proper utf-8 repr
		$d->loadHTML($text);
		$xpathsearch = new DOMXPath($d);
		$nodes = $xpathsearch->query('//img[@src]');

		$savedir=$this->generator->tmpdir.DIRECTORY_SEPARATOR."test_files";
		if (!file_exists($savedir) && !is_dir($savedir)) {
			mkdir($savedir);
		}

		foreach ($nodes as $node){
			$src=$node->getAttribute("src");
			//base64
			if (startsWith($src, "data:image/")){
				$exp=explode(",",$src);
                                $imagedata = end($exp);
				$imagefile=tempnam($savedir, "img");
				file_put_contents($imagefile, base64_decode($imagedata));

				$basename=basename($imagefile);
				$node->setAttribute("src","test_files".DIRECTORY_SEPARATOR.$basename);


			}
			//saved moodle file
			else{
				$filename=basename($src);
				foreach ($files as $file){
					if ($file->get_filename()==urldecode($filename)){
						$newsrc= "test_files".DIRECTORY_SEPARATOR.$file->get_filename();
						$node->setAttribute("src",$newsrc);

						file_put_contents($savedir.DIRECTORY_SEPARATOR.$filename, $file->get_content());
						break;
					}
				}
			}
		}
		$text=$this->removeExtraHtml( $d->saveHTML());
	}

	function getCount(){
		if ($this->count==0){
			return "&nbsp;";
		}
		return $this->count;
	}
	function getText(){
		return $this->question->questiontext;
	}

	function getPoints(){
		if ($this->category->points!=0){
			return $points=$this->category->points;
		}
		return $points=$this->question->defaultmark;
	}

	function format_points(){
		if (!$this->generator->question_points){
			return "&nbsp;";
		}
		$points = $this->getPoints();
		if ($points==0){
			return "";
		}
		return get_string("points_test","papertest",round($points,2));

	}

	//when manipulating dom as htm-> get rid of extra tegs
	function removeExtraHtml($text){
		return preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $text));
	}
	function addBlankRow(){
		//margin 0->save space
		return "<p style='margin-top:0cm'>&nbsp;</p>";
	}

	function exportQuestion(){
		$html = "<tr class=\"question_row\">";
		if ($this->generator->question_numbers){
			$html.="<td class=\"question_num\" width=\"3%\"><p class=\"question_num\">".$this->getCount().".&nbsp;</p></td>";
		}
                $spaces = $this->createSpaces();
		$html.="<td class=question width=\"92%\" colspan=\"2\"><div class=question>".$this->getText()."</div>$spaces</td>";
		if ($this->generator->question_points){
			$html.="<td class=question_points width=\"5%\"><p class=question_points>".$this->format_points()."</p></td>";
		}
		$html.="</tr>";
                $html.=$this->generateAnswerRow();

		return $html;
	}

	function generateAnswerRow(){
		return "";
	}

	function createSpaces(){
		$i=$this->category->spaces;
		$html="";
		while ($i>0){
			$html.=$this->addBlankRow();
			$i--;
		}
		return $html;
	}

	function generateMetadata(){
		$points = round($this->getPoints());

		$metadata=array();
		$metadata["id"]=intval($this->question->id);
		$metadata["qt"]=$this->question->qtype;
		$metadata["pt"]=round($points,2);
		$metadata["ans"] = array();
		$id = 1;
		if ($points>10){
			for ($i=10;$i<100;$i+=10){
				$answer_details=array();
				$answer_details["id"]=$id;
				$answer_details["fr"]=round($i/(float)$points,2);
				$metadata["ans"][]=$answer_details;
				$id++;
			}
		}
		for ($i=1;$i<10;$i+=1){
			$answer_details=array();
			$answer_details["id"]=$id;
			$answer_details["fr"]=round($i/(float)$points,2);
			$metadata["ans"][]=$answer_details;
		}
		if ($points<=10){
			$answer_details=array();
			$answer_details["id"]=$id;
			$answer_details["fr"]=round(10/(float)$points,2);
			$metadata["ans"][]=$answer_details;
			$id++;
		}
		$this->generator->metadata[]=$metadata;
	}

	function generateCheckboxesForSemiAutomaticCorrection(){
		if ($this->getPoints()>10){

			$table = "<div align=center>
							<table class=MsoTableGrid border=1 cellspacing=0 cellpadding=0
							 style='border-collapse:collapse;border:none;mso-border-alt:solid windowtext .5pt;
							 mso-yfti-tbllook:1184;mso-padding-alt:0cm 5.4pt 0cm 5.4pt'>
							 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;height:14.5pt'>
							  <td width=75 rowspan=2 style='width:56.05pt;border:solid windowtext 1.0pt;
							  mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:14.5pt'>
							  <p align=center style='margin-top:0cm;text-align:center'>".get_string("no_writing","papertest")."</p>
							  </td>
							  <td width=38 style='width:28.45pt;border:none;border-top:solid windowtext 1.0pt;
							  mso-border-left-alt:solid windowtext .5pt;mso-border-top-alt:solid windowtext .5pt;
							  mso-border-left-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;
							  height:14.5pt'>
							  <p align=right style='margin-top:0cm;text-align:right'>10x</p>
							  </td>
							  <td width=298 valign=top style='width:223.85pt;border-top:solid windowtext 1.0pt;
							  border-left:none;border-bottom:none;border-right:solid windowtext 1.0pt;
							  mso-border-top-alt:solid windowtext .5pt;mso-border-right-alt:solid windowtext .5pt;
							  padding:0cm 5.4pt 0cm 5.4pt;height:14.5pt'>
							  <p style='margin-top:0cm'>1<span style='font-size:22.0pt;font-family:\"Wingdings 2\";
							  mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:\"Times New Roman\";
							  mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>2<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>3<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>4<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>5<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>6<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>7<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>8<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>9<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span></p>
							  </td>
							 </tr>
							 <tr style='mso-yfti-irow:1;mso-yfti-lastrow:yes;height:15.15pt'>
							  <td width=38 style='width:28.45pt;border:none;border-bottom:solid windowtext 1.0pt;
							  mso-border-left-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
							  mso-border-bottom-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;
							  height:15.15pt'>
							  <p align=right style='margin-top:0cm;text-align:right'>1x</p>
							  </td>
							  <td width=298 valign=top style='width:223.85pt;border-top:none;border-left:
							  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
							  mso-border-bottom-alt:solid windowtext .5pt;mso-border-right-alt:solid windowtext .5pt;
							  padding:0cm 5.4pt 0cm 5.4pt;height:15.15pt'>
							  <p style='margin-top:0cm'>1<span style='font-size:22.0pt;font-family:\"Wingdings 2\";
							  mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:\"Times New Roman\";
							  mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>2<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>3<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>4<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>5<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>6<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>7<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>8<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>9<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span></p>
							  </td>
							 </tr>
							</table>
							</div>";
					}
					else{
						$table = "<div align=center>
							<table class=MsoTableGrid border=1 cellspacing=0 cellpadding=0
							 style='border-collapse:collapse;border:none;mso-border-alt:solid windowtext .5pt;
							 mso-yfti-tbllook:1184;mso-padding-alt:0cm 5.4pt 0cm 5.4pt'>
							 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes;
							  height:16.3pt'>
							  <td width=111 style='width:83.4pt;border:solid windowtext 1.0pt;mso-border-alt:
							  solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:16.3pt'>
							  <p style='margin-top:0cm'>".get_string("no_writing","papertest")."</p>
							  </td>
							  <td width=340 valign=top style='width:9.0cm;border:solid windowtext 1.0pt;
							  border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:
							  solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:16.3pt'>
							  <p style='margin-top:0cm'>1<span style='font-size:22.0pt;font-family:\"Wingdings 2\";
							  mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:\"Times New Roman\";
							  mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>2<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>3<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>4<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>5<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>6<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>7<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>8<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>9<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span>10<span style='font-size:22.0pt;
							  font-family:\"Wingdings 2\";mso-ascii-font-family:\"Times New Roman\";mso-hansi-font-family:
							  \"Times New Roman\";mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'><span
							  style='mso-char-type:symbol;mso-symbol-font-family:\"Wingdings 2\"'>*</span></span><span
							  style='font-size:10.0pt;color:#FAFAFA'>*</span></p>
							  </td>
							 </tr>
							</table>
							</div>" ;
					}
			return $table;

	}

}




class multianswerQtype extends generalQtype{

	public function __construct($question,$category,$count,$generator){
		parent::__construct($question,$category,$count,$generator);
		$this->replaceSpecialConstructs();
	}
	function replaceSpecialConstructs(){
		$replace_regex="/\{#(\d{1,2})\}/u";
		if (!$this->generator->results){
			$replacement=".......................";
			$this->alternateQText=preg_replace($replace_regex,$replacement, $this->question->questiontext);
			return;
		}
		$matches=array();
		preg_match_all($replace_regex, $this->question->questiontext,$matches,PREG_SET_ORDER);
		$subquestions=$this->question->options->questions;
		$this->alternateQText=$this->question->questiontext;
		foreach($matches as $match){
			$subquestion=$subquestions[$match[1]]->questiontext;
			$replacement=preg_replace("/\{[0-9]{0,2}:(NUMERICAL|SHORTANSWER):=(.*?):.*?\}/u",
				 "$2", $subquestion);
			$this->alternateQText=str_replace($match[0],$replacement, $this->alternateQText);

		}
	}
	function getText(){
		return $this->alternateQText;
	}
}

class multichoiceQtype extends generalQtype{

	public function __construct($question,$category,$count,$generator){
		parent::__construct($question,$category,$count,$generator);
                if (!$generator->results){
                    shuffle($this->question->options->answers);
                }	
	}
	function getText(){
		return $this->question->questiontext;
	}

	function generateMetadata(){
		$metadata=array();
		$metadata["id"]=intval($this->question->id);
		$metadata["qt"]=$this->question->qtype;
		$metadata["pt"]=round($this->getPoints(),2);
		$metadata["ans"] = array();
		foreach ($this->question->options->answers as $answer){
			$answer_details=array();
			$answer_details["id"]=intval($answer->id);
			$answer_details["fr"]=round($answer->fraction,2);
			$metadata["ans"][]=$answer_details;
		}
		$this->generator->metadata[]=$metadata;
	}

	function generateCheckboxesForSemiAutomaticCorrection(){
		//checkboxes next to each question
		return "";

	}
	function generateAnswerRow(){
            $html = "";    
            foreach ($this->question->options->answers as $answer){
                $html.="<tr>";
                if ($this->generator->question_numbers){
                        $html.="<td width=\"3%\"><p>&nbsp;</p></td>";
                }
                $html.="<td class=\"answer_a\" width=\"82%\">";
                $html.=$this->parseChemFormula($answer->answer);
                $html.="</td>";
                //crossed square
                if ($this->generator->results && intval($answer->fraction)>0){
                        $html.="<td class=answer_box align=right width=\"10%\"><p class=answer_box><span class=square>S</span></p></td>";
                }//square
                else{
                    $html.="<td class=answer_box align=right width=\"10%\"><p class=answer_box><span class=square>*</span>";

                    if ($this->generator->cvVersion){
                            $html.="<span class=star>*</span>";
                    }
                    $html.="</p>
                    </td>";
                }
                
                if ($this->generator->question_points){
                        $html.="<td width=\"5%\"><p>&nbsp;</p></td>";
                }
                $html.="</tr>";
                }
            return $html;
	}

}


function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}
