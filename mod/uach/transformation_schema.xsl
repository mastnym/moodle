<?xml version="1.0" encoding="UTF-8"?>
<!--  SERVEROVÁ VERZE -->
<!-- pro serverovou verzi!!!!!!!!!!!!!!!!!!!!!!!!!! -->
<!-- 1)pridat globalni parametr :                                                                    <xsl:param name="settings" required="yes"/> -->
<!-- 2) zmenit volani ext funkce number                         nahradit document('settings.xml')  za  $settings -->
<!-- 3)globalni parametry                                                   vymenit zakomentovane s nezakoment.        -->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="2.0" 
    xmlns:ve="http://schemas.openxmlformats.org/markup-compatibility/2006"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
    xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
    xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
    xmlns:w10="urn:schemas-microsoft-com:office:word"
    xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
    xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:saxon="http://saxon.sf.net/"
    xmlns:ext="java:cz.mastnym.XSLSaxon">
    
   
    <xsl:param name="course"  required="yes"/>
    <xsl:param name="categoryid"  required="yes"/>
    <xsl:param name="top_level" required="yes"/>
   <xsl:param name="imageRoot" required="yes"/>
    <xsl:param name="questionXML" required="yes"/>
    <xsl:param name="showNumbers" required="yes"/>
    <xsl:param name="header" required="yes"/>
    <xsl:param name="variant" required="yes"/>
    <xsl:param name="showAnswers" required="yes"/>
    <xsl:param name="showCheckSquares" required="yes"/>
    <xsl:param name="openCV" required="yes"/>
    <xsl:output  encoding="UTF-8"  method="xml" indent="yes"></xsl:output>
    <xsl:strip-space elements="*"/>
    <!-- ###################################  ROOT,QUIZ###################################  -->
    <xsl:template match="/" >
        
 <!--<xsl:result-document method="html" href="local_testing/document.xml">-->
        
            
        <w:document xmlns:ve="http://schemas.openxmlformats.org/markup-compatibility/2006"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
            xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
            xmlns:v="urn:schemas-microsoft-com:vml"
            xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
            xmlns:w10="urn:schemas-microsoft-com:office:word"
            xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml">
            <w:body>
           
                <xsl:apply-templates></xsl:apply-templates>
            
                <w:sectPr >
                    <w:pgSz w:w="11906" w:h="16838"/>
                    <w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="708"
                        w:footer="708" w:gutter="0"/>
                    <w:cols w:space="708"/>
                    <w:docGrid w:linePitch="360"/>
                </w:sectPr>
                </w:body>
            
        </w:document>
<!--</xsl:result-document>-->
    </xsl:template>
    
    <!-- ##################### SETTINGS,COURSE ##################### -->
    <xsl:template match="settings">
       <xsl:choose> 
            
            <xsl:when test="contains($top_level,$categoryid)">
                <xsl:choose>
                    <xsl:when test="$header='1'">
                        <xsl:call-template name="header1">
                            <xsl:with-param name="points" select="sum(course[@name=$course]/category[@id=$categoryid]/descendant-or-self::category[@points]/(@points * @questionsInSection))"></xsl:with-param>
                            <xsl:with-param name="desc" select="course[@name=$course]/category[@id=$categoryid]/@instructions"></xsl:with-param>
                            <xsl:with-param name="display" select="course[@name=$course]/category[@id=$categoryid]/@display" ></xsl:with-param>
                            <xsl:with-param name="displayPoints" select="course[@name=$course]/category[@id=$categoryid]/@displayPoints"></xsl:with-param>
                        </xsl:call-template>
                    </xsl:when>
                    <xsl:when test="$header='2'">
                            <xsl:call-template name="header2">
                                <xsl:with-param name="points" select="sum(course[@name=$course]/category[@id=$categoryid]/descendant-or-self::category[@points]/(@points * @questionsInSection))"></xsl:with-param>
                                <xsl:with-param name="desc" select="course[@name=$course]/category[@id=$categoryid]/@instructions"></xsl:with-param>
                                <xsl:with-param name="display" select="course[@name=$course]/category[@id=$categoryid]/@display" ></xsl:with-param>
                                <xsl:with-param name="displayPoints" select="course[@name=$course]/category[@id=$categoryid]/@displayPoints"></xsl:with-param>
                            </xsl:call-template>
                    </xsl:when>
                </xsl:choose>
                <xsl:apply-templates select="course[@name=$course]" />
            </xsl:when>
            
            <xsl:otherwise>
                <xsl:apply-templates select="//category" mode="cat"></xsl:apply-templates>
            </xsl:otherwise>
            
        </xsl:choose>
        
    </xsl:template>
    
    <xsl:template match="course">
        
        <xsl:apply-templates select="category[@id=$categoryid]"></xsl:apply-templates>
    
    </xsl:template>
    
    
    
    
    <!-- ##################### KATEGORIE #####################-->
    <!-- test -->
    <xsl:template match="category[count(ancestor::category)=0]">
        
        <xsl:apply-templates select="category">
            <xsl:sort select="@pos"></xsl:sort>
        </xsl:apply-templates>
    
    </xsl:template>
    
    <!-- ostatni kategorie -->
    <xsl:template match="category">
        
        <xsl:param name="numberOfAncestorCategories" select="count(ancestor::category)-1"/>
        <xsl:param name="id" select="string(@id)"></xsl:param>
        <xsl:param name="q" select="number(@questionsInSection)"></xsl:param> 
        <xsl:param name="s" select="number(@spaceAfterQuestion)"></xsl:param>
        <xsl:param name="p" select="number(@points)"></xsl:param>

        <xsl:choose>
            <!-- nadrazena kategorie -->
            <xsl:when test="@display">
                <xsl:if test="@display=1">
                    <xsl:call-template name="category_template">
                        <xsl:with-param name="category" select="@name"/>
                        <xsl:with-param name="instructions" select="@instructions"/>
                        <xsl:with-param name="weight" select="$numberOfAncestorCategories*2"></xsl:with-param>
                        <xsl:with-param name="displayPoints" select="@displayPoints"></xsl:with-param>
                        <xsl:with-param name="points" select="sum(descendant-or-self::category[@points]/(@points * @questionsInSection))"></xsl:with-param>
                    </xsl:call-template>
                </xsl:if>
            </xsl:when>
            <!-- kategorie s otazkama -->
            <xsl:otherwise>
                
                <xsl:if test="@questionsInSection&gt;0">
                    
                    <xsl:call-template name="category_template">
                        <xsl:with-param name="category" select="@name"/>
                        <xsl:with-param name="weight" select="$numberOfAncestorCategories*2"></xsl:with-param>
                    </xsl:call-template>
               
                   <xsl:for-each select="doc($questionXML)/quiz/category[attribute::id=$id]/question[position()&lt;=$q]">
                        <xsl:apply-templates select=".">
                            <xsl:with-param name="spaces" select="$s" tunnel="yes"></xsl:with-param>
                            <xsl:with-param name="points" select="$p" tunnel="yes"></xsl:with-param>
                        </xsl:apply-templates>
                    </xsl:for-each>
                
                </xsl:if>
            
            </xsl:otherwise>
        </xsl:choose>
        
        <xsl:apply-templates select="category">
            <xsl:sort select="@pos"></xsl:sort>
        </xsl:apply-templates>
  
   
    </xsl:template>
    
    <xsl:template match="category" mode="cat">
        <xsl:param name="id" select="string(@id)"></xsl:param>
        <xsl:param name="s" select="number(@spaceAfterQuestion)"></xsl:param>
        <xsl:param name="p" select="number(@points)"></xsl:param>
        <xsl:if test="$id=doc($questionXML)//@id">
            <xsl:call-template name="category_template">
                <xsl:with-param name="category" select="@name"/>
                <xsl:with-param name="weight" select="0"></xsl:with-param>
                <xsl:with-param name="displayPoints" select="@displayPoints"/>
                <xsl:with-param name="points" select="sum(descendant-or-self::category[@points]/(@points * @questionsInSection))" ></xsl:with-param>
                <xsl:with-param name="instructions" select="@instructions"></xsl:with-param>
            </xsl:call-template>
            <xsl:for-each select="doc($questionXML)/quiz/category[attribute::id=$id]/question">
                <xsl:apply-templates select=".">
                    <xsl:with-param name="spaces" select="$s" tunnel="yes"></xsl:with-param>
                    <xsl:with-param name="points" select="$p" tunnel="yes"></xsl:with-param>
                </xsl:apply-templates>
            </xsl:for-each>
        </xsl:if>
    </xsl:template>
    <!-- ########################################## -->
     
    <!-- ##################### QUESTION  #####################-->
    <xsl:template match="question[@type!='category']">
        <xsl:param name="spaces" tunnel="yes" /> 
        <xsl:value-of select="questiontext/text"></xsl:value-of>
        <xsl:apply-templates select="questiontext/text">
            <!-- cislovani otazek -->
            <xsl:with-param name="Qnum" select="count(preceding-sibling::question)+1 " tunnel="yes"/> <!-- pro cislovani otazek -->
            <xsl:with-param name="imagefiles" tunnel="yes" select="questiontext/file"></xsl:with-param>
        </xsl:apply-templates>
        
        <xsl:apply-templates select="answer/text"/>
    	
    	<xsl:call-template name="recursive">
            <xsl:with-param name="spaces" select="$spaces"></xsl:with-param>
        </xsl:call-template>
        <!-- 
        <xsl:if test="$showCheckSquares='1' and @type!='multichoice'">
        		<xsl:call-template name='teacherPoints'></xsl:call-template>
        </xsl:if> -->
    </xsl:template>
   
   
   
    
    <xsl:template match="text[parent::questiontext]">   
        <xsl:variable name="html">
            <![CDATA[<html>]]>
           <xsl:value-of select="ext:removeMarkupBetweenDollars(.,$showAnswers)"></xsl:value-of>
            <![CDATA[</html>]]>
        </xsl:variable>
        
        <xsl:variable name="parse" select="saxon:parse($html)" />
        
        <xsl:apply-templates select="$parse/html" mode="question">
        </xsl:apply-templates>   
    </xsl:template>
    
      <xsl:template match="html" mode="question">
        <xsl:param name="Qnum" tunnel="yes"></xsl:param>
        <xsl:param name="points" tunnel="yes"></xsl:param>
        <w:tbl>
            <w:tblPr>
                <w:tblW w:w="10314" w:type="dxa"/>
                <w:tblBorders>
                    <w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                </w:tblBorders>
                <w:tblLayout w:type="fixed"/>
            </w:tblPr>
            <w:tblGrid>
                <w:gridCol w:w="392"/>
                <w:gridCol w:w="8788"/>
                <w:gridCol w:w="1134"/>
            </w:tblGrid>
            <w:tr >
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="392" w:type="dxa"/>
                    </w:tcPr>
                    <w:p>
                        <w:pPr>
                            <w:rPr>
                                <w:b/>
                            </w:rPr>
                        </w:pPr>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                                <w:sz w:val="20"/>
                                <w:szCs w:val="20"/>
                            </w:rPr>
                            <xsl:if test="$showNumbers='1'">
                                <w:t xml:space="preserve"><xsl:value-of select="concat($Qnum,'. ')"/></w:t>
                            </xsl:if>
                        </w:r>
                    </w:p>
                </w:tc>
                
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="8788" w:type="dxa"/>
                    </w:tcPr>
                    <xsl:apply-templates select="child::*"></xsl:apply-templates>
                </w:tc>
                
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="1134" w:type="dxa"/>
                    </w:tcPr>
                    <w:p >
                        <w:pPr>
                            <w:rPr>
                                <w:i/>
                            </w:rPr>
                        </w:pPr>
                        <xsl:variable name="points_val">
                            <xsl:call-template name="points">
                                <xsl:with-param name="body" select="$points"></xsl:with-param>
                            </xsl:call-template>
                        </xsl:variable>
                        <xsl:call-template name="questionFragmentItalic">
                            <xsl:with-param name="val">
                                <xsl:value-of select="$points_val"/>
                            </xsl:with-param>
                        </xsl:call-template>
                    </w:p>
                </w:tc>
                
            </w:tr>
        </w:tbl>
    </xsl:template>
    
    
    <xsl:template match="text[parent::answer]"> 
   			 <xsl:variable name="alphabet" select="'abcdefghijklmnopqrstuvwxyz'"/>
   			 
        <xsl:variable name="html">
            <![CDATA[<html>]]>
           <xsl:value-of select="ext:removeMarkupBetweenDollars(.,$showAnswers)"></xsl:value-of>
            <![CDATA[</html>]]>
        </xsl:variable>
        <xsl:variable name="parse" select="saxon:parse($html)" />
        
        <xsl:apply-templates select="$parse/html" mode="answer">
        <xsl:with-param name="count" select="concat(substring($alphabet,count(parent::answer/preceding-sibling::answer)+1,1),') ')"/>
        <xsl:with-param name="fraction" select="parent::answer/@fraction"/>
        </xsl:apply-templates>
       </xsl:template>
 
   
   <xsl:template match="html" mode="answer">
   			<xsl:param name="count"></xsl:param>
        <xsl:param name="fraction"></xsl:param>
        <w:tbl>
            <w:tblPr>
                <w:tblW w:w="10314" w:type="dxa"/>
                <w:tblBorders>
                    <w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                </w:tblBorders>
                <w:tblLayout w:type="fixed"/>
            </w:tblPr>
            <w:tblGrid>
                <w:gridCol w:w="392"/>
                <w:gridCol w:w="8788"/>
                <w:gridCol w:w="1134"/>
            </w:tblGrid>
            <w:tr >
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="392" w:type="dxa"/>
                    </w:tcPr>
                    <w:p>
                        <w:pPr>
                            <w:rPr>
                            </w:rPr>
                        </w:pPr>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                                <w:sz w:val="16"/>
                                <w:szCs w:val="16"/>
                            </w:rPr>
                        </w:r>
                    </w:p>
                </w:tc>
                
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="8788" w:type="dxa"/>
                    </w:tcPr>
                    <xsl:apply-templates select="child::*">
                    	<xsl:with-param name="text-before" select="$count"></xsl:with-param>
                    </xsl:apply-templates>
                </w:tc>
                
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="1134" w:type="dxa"/>
                    </w:tcPr>
                    <w:p >
                        <w:pPr>
                            <w:rPr>
                                <w:i/>
                            </w:rPr>
                        </w:pPr>
                       <w:r>
                       <xsl:if test="$showCheckSquares='1'">
	                       <xsl:choose>
		                       <xsl:when test="$showAnswers='1' and $fraction != '0'">
					                	<w:sym w:font="Wingdings 2" w:char="F053"/>
		                		</xsl:when>
	                       	<xsl:otherwise>
	                       		<w:rPr>
				                    <w:sz w:val="32"/>
				                    <w:szCs w:val="32"/>
	                			</w:rPr>
	                			<w:sym w:font="Wingdings 2" w:char="F02A"/>
	                       	</xsl:otherwise>
	                       </xsl:choose>
	                     </xsl:if>
                       </w:r>
                    </w:p>
                </w:tc>
                
            </w:tr>
        </w:tbl>
    </xsl:template>
   
    <!--################################### ODPOVIDA CELE OTAZCE -obsahu CDATA ###################################  -->
   <xsl:template match="p | pre ">
        <xsl:param name="points" tunnel="yes"></xsl:param>
        <xsl:param name="Qnum" tunnel="yes"></xsl:param>
        <xsl:param name="style" select="@style"></xsl:param> 
		<xsl:param name="text-before"></xsl:param>
		  <w:p> 
            <w:pPr>
                <w:keepLines/>
                 <xsl:call-template name="parseStyleAttribute"></xsl:call-template>
               
            </w:pPr>
            <xsl:if test="count(preceding-sibling::p)+count(preceding-sibling::pre)=0"></xsl:if>
            <w:r>
                    <w:rPr>
                    	<w:i/>
                    </w:rPr>
                    <w:t xml:space="preserve"><xsl:value-of select="$text-before"></xsl:value-of></w:t>
             </w:r>
            <xsl:apply-templates></xsl:apply-templates>
        </w:p>  
    </xsl:template>
    
    <xsl:template match="sup">
        <xsl:apply-templates>
            <xsl:with-param name="sup" tunnel="yes">
                <w:vertAlign w:val="superscript"/>
            </xsl:with-param>
        </xsl:apply-templates>
    </xsl:template>
    
    <xsl:template match="sub">        
        <xsl:apply-templates> 
            <xsl:with-param name="sub" tunnel="yes">
                <w:vertAlign w:val="subscript"/>
            </xsl:with-param>
        </xsl:apply-templates>
    </xsl:template>
    
    <xsl:template match="span">
        <xsl:apply-templates>
            <xsl:with-param name="style" tunnel="yes">
                <xsl:call-template name="parseStyleAttribute"></xsl:call-template>
            </xsl:with-param>
        </xsl:apply-templates>
    </xsl:template>
    
    <xsl:template match="em">
        <xsl:apply-templates>
            <xsl:with-param name="italic" tunnel="yes">
                <w:i/>
            </xsl:with-param>
        </xsl:apply-templates>
    </xsl:template>
    
    <xsl:template match="strong">
        <xsl:apply-templates>
            <xsl:with-param name="bold" tunnel="yes">
                <w:b/>
            </xsl:with-param>
        </xsl:apply-templates>
    </xsl:template>
    
    <xsl:template match="h1|h2|h3|h4|h5|h6">
        <xsl:param name="Qnum" tunnel="yes"></xsl:param>
        <xsl:param name="hOrder" select="concat('Nadpis',substring(name(.),2))"></xsl:param>
        <xsl:param name="style" select="@style"></xsl:param>
        <w:p> 
            <w:pPr>
                        <xsl:call-template name="parseStyleAttribute"></xsl:call-template>
                <w:pStyle w:val="{$hOrder}"/><!-- potreba soubor styles.xml z dp/work/temp/word -->
            </w:pPr> 
            <xsl:apply-templates/>        
        </w:p>
    </xsl:template>
    
    <xsl:template match="ul|ol">
        <xsl:param name="listID" select="ext:getListID(.)"/>
        <xsl:apply-templates>
            <xsl:with-param name="typeOfList" tunnel="yes">
                <w:numId w:val="{$listID}"/>
            </xsl:with-param>
        </xsl:apply-templates>
    </xsl:template>

    
    <xsl:template match="li">
        <xsl:param name="typeOfList" tunnel="yes"></xsl:param>
        <w:p> 
            <w:pPr>
                <w:pStyle w:val="Odstavecseseznamem"/><!-- potreba soubor numbering.xml z dp/work/temp/word -->
                <w:keepLines/>
                <w:numPr> 
                    <w:ilvl w:val="0"/>
                    <xsl:copy-of select="$typeOfList"></xsl:copy-of>
                </w:numPr>
                <w:ind w:left="1100"/>
                
            </w:pPr> 
            <xsl:apply-templates>
            </xsl:apply-templates>
        </w:p>
    </xsl:template>
    
    
    <xsl:template match="img">
        <xsl:param name="imagefiles" tunnel="yes"></xsl:param>
        
        <xsl:param name="imageNumber" select="ext:getImageNumber()"/>
        <xsl:variable name="float">
            
        </xsl:variable>
        
        <xsl:variable name="extension">
            <xsl:call-template name="substring-after-last">
                <xsl:with-param name="string" select="@src" />
                <xsl:with-param name="delimiter" select="'.'" />
            </xsl:call-template>
        </xsl:variable>
        
        
        <xsl:variable name="height" >
            <xsl:choose>
                <xsl:when test="@height">
                    <xsl:value-of select="@height"></xsl:value-of>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="-1"></xsl:value-of>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        
        
        <xsl:variable name="width" as="xs:integer">
            <xsl:choose>
                <xsl:when test="@width">
                    <xsl:value-of select="@width"></xsl:value-of>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="-1"></xsl:value-of>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
               <xsl:variable name="imageDimensions"> 
           <xsl:value-of select="ext:drawImage(concat('image',string($imageNumber),'.',$extension),
                $imagefiles,
                $imageRoot,
                @src,
                $width,
                $height)"></xsl:value-of>
        </xsl:variable>
        
        <xsl:variable name="imageHeight" select="substring-before($imageDimensions,' ')"/>
        <xsl:variable name="imageWidth" select="substring-after($imageDimensions,' ')"/>
        
        <xsl:variable name="float">
            <wp:anchor distT="0" distB="0" distL="114300" distR="114300" simplePos="0"
                relativeHeight="251658240" behindDoc="0" locked="0" layoutInCell="1"
                allowOverlap="1">
                <wp:simplePos x="0" y="0"/>
                <wp:positionH relativeFrom="margin">
                    <wp:align><xsl:value-of  select="normalize-space(substring-before(substring-after(@style,'float:'),';'))"></xsl:value-of></wp:align>
                </wp:positionH>
                <wp:positionV relativeFrom="paragraph">
                    <wp:align>top</wp:align>
                </wp:positionV>
                <wp:extent cx="{$imageWidth}" cy="{$imageHeight}"/> <!-- zvysuje rozmery obrazku  --> 
                <wp:effectExtent l="0" t="0" r="0" b="0"/> <!-- Additional Extent on Left Edge..... -->
                <wp:wrapSquare wrapText="bothSides"/>
                <wp:docPr id="{$imageNumber}" name="{@alt}" descr="{@alt}"/>  <!-- id-Unique Identifier, name- jmeno obrazku, descr-alternativni popis-->  
                <wp:cNvGraphicFramePr>
                    <a:graphicFrameLocks
                        xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
                        noChangeAspect="0"/><!-- Disallow Aspect Ratio Change --><!-- puvodne bylo 1 -->
                </wp:cNvGraphicFramePr>
                <a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
                    <a:graphicData
                        uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
                        <pic:pic
                            xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
                            <pic:nvPicPr>
                                <pic:cNvPr id="{$imageNumber}" name="{@alt}"/><!-- id-Unique Identifier, name- jmeno obrazku-->
                                <pic:cNvPicPr/>
                            </pic:nvPicPr>
                            <pic:blipFill>
                                <a:blip r:embed="{concat('rId',$imageNumber)}" cstate="print"/>
                                <a:stretch>
                                    <a:fillRect/>
                                </a:stretch>
                            </pic:blipFill>
                            <pic:spPr>
                                <a:xfrm>
                                    <a:off x="0" y="0"/>   <!-- offset x ,y -->
                                    <a:ext cx="{$imageWidth}" cy="{$imageHeight}"/>  <!-- extend width lkength -->
                                </a:xfrm>
                                <a:prstGeom prst="rect">  <!-- Preset Shape, a:ST_ShapeType-->
                                    <a:avLst/>
                                </a:prstGeom>
                            </pic:spPr>
                        </pic:pic>
                    </a:graphicData>
                </a:graphic>
            </wp:anchor>
        </xsl:variable>
        
        <xsl:variable name="inline">
            <wp:inline><!-- vzdalenosti textu okolo obrazku -->
                <wp:extent cx="{$imageWidth}" cy="{$imageHeight}"/> <!-- zvysuje rozmery obrazku  --> 
                <wp:effectExtent l="0" t="0" r="0" b="0"/> <!-- Additional Extent on Left Edge..... -->
                <wp:docPr id="{$imageNumber}" name="{@alt}" descr="{@alt}"/>  <!-- id-Unique Identifier, name- jmeno obrazku, descr-alternativni popis-->  
                <wp:cNvGraphicFramePr>
                    <a:graphicFrameLocks
                        xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
                        noChangeAspect="0"/><!-- Disallow Aspect Ratio Change --><!-- puvodne bylo 1 -->
                </wp:cNvGraphicFramePr>
                <a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
                    <a:graphicData
                        uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
                        <pic:pic
                            xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
                            <pic:nvPicPr>
                                <pic:cNvPr id="{$imageNumber}" name="{@alt}"/><!-- id-Unique Identifier, name- jmeno obrazku-->
                                <pic:cNvPicPr/>
                            </pic:nvPicPr>
                            <pic:blipFill>
                                <a:blip r:embed="{concat('rId',$imageNumber)}" cstate="print"/>
                                <a:stretch>
                                    <a:fillRect/>
                                </a:stretch>
                            </pic:blipFill>
                            <pic:spPr>
                                <a:xfrm>
                                    <a:off x="0" y="0"/>   <!-- offset x ,y -->
                                    <a:ext cx="{$imageWidth}" cy="{$imageHeight}"/>  <!-- extend width lkength -->
                                </a:xfrm>
                                <a:prstGeom prst="rect">  <!-- Preset Shape, a:ST_ShapeType-->
                                    <a:avLst/>
                                </a:prstGeom>
                            </pic:spPr>
                        </pic:pic>
                    </a:graphicData>
                </a:graphic>
            </wp:inline>
        </xsl:variable>
        <w:r>
        <w:rPr>
            <xsl:call-template name="parseStyleAttribute"></xsl:call-template>
        </w:rPr>
        <w:drawing>
            <xsl:choose>
                <xsl:when test="contains(@style,'float')">
                    
                    <xsl:copy-of select="$float"></xsl:copy-of>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:copy-of select="$inline"></xsl:copy-of>
                </xsl:otherwise>
            </xsl:choose>
            
        </w:drawing>
        </w:r>
        <w:r>
            <w:t> </w:t>
        </w:r>
    </xsl:template>
    
    <xsl:template match="table">
        <w:tbl>
            <w:tblPr>
                <w:tblW w:w="8000" w:type="dxa"/>
                <w:jc w:val="center"/>
                <xsl:choose>
                    <xsl:when test="@border&gt;0">
                        <w:tblBorders>
                            <w:top w:val="single" w:sz="{@border*8}" w:space="0" w:color="auto"/>
                            <w:left w:val="single" w:sz="{@border*8}" w:space="0" w:color="auto"/>
                            <w:bottom w:val="single" w:sz="{@border*8}" w:space="0" w:color="auto"/>
                            <w:right w:val="single" w:sz="{@border*8}" w:space="0" w:color="auto"/>
                            <w:insideH w:val="single" w:sz="{@border*8}" w:space="0" w:color="auto"/>
                            <w:insideV w:val="single" w:sz="{@border*8}" w:space="0" w:color="auto"/>
                        </w:tblBorders>
                    </xsl:when>
                    <xsl:otherwise>
                        <w:tblBorders>
                            <w:top w:val="none"/>
                            <w:left w:val="none" />
                            <w:bottom w:val="none" />
                            <w:right w:val="none" />
                            <w:insideH w:val="none" />
                            <w:insideV w:val="none" />
                        </w:tblBorders>
                    </xsl:otherwise>
                </xsl:choose>
            </w:tblPr>
            <w:tblGrid/>
            
            <xsl:apply-templates/>
        </w:tbl>
        <w:p/>
    </xsl:template>
    
    <xsl:template match="tbody">
        <xsl:apply-templates />
    </xsl:template>
    
    <xsl:template match="tr">
        <w:tr ><xsl:apply-templates><xsl:with-param name="totalRowWidth" select="sum(child::td/@width)"></xsl:with-param></xsl:apply-templates></w:tr>
    </xsl:template>
    
    <xsl:template match="td">
        <xsl:param name="totalRowWidth"/>
        <xsl:choose>
            <!--<xsl:when test="(name(*[1])='img' )">
                <w:tc>
                    <w:tcPr>
                        <xsl:if test="$totalRowWidth">
                            <w:tcW w:w="{concat(string(@width div $totalRowWidth*100),'%')}" w:type="pct"/>
                        </xsl:if>
                        <xsl:if test="@valign">
                                <w:vAlign w:val="{@valign}"/>
                        </xsl:if>
                        <xsl:call-template name="parseStyleAttribute"></xsl:call-template>
                    </w:tcPr>
                    <w:p>
                        <w:pPr>
                            <w:rPr>
                                <w:b/>
                            </w:rPr>
                        </w:pPr>
                        <xsl:apply-templates/>
                    </w:p>
                </w:tc>
            </xsl:when>-->
            
            <!-- pokud je 1 child text nebo node ktery si nevytvari paragraf(vsechny krom p a pre) -->
            <xsl:when test="child::node()[1]=text() or child::node()[1]=*[not(self::p or self::pre)]">
                <w:tc>
                    <w:tcPr>
                        <xsl:if test="$totalRowWidth">
                            <w:tcW w:w="{concat(string(@width div $totalRowWidth*100),'%')}" w:type="pct"/>
                        </xsl:if>
                        <xsl:if test="@valign">
                            <w:vAlign w:val="{@valign}"/>
                        </xsl:if>
                        <xsl:call-template name="parseStyleAttribute"></xsl:call-template>
                    </w:tcPr>
                    <w:p>
                        <w:pPr>
                            <!-- if a parent table cell contains align, apply it -->
                            <xsl:if test="@align">
                                <w:jc w:val="{@align}"/>
                            </xsl:if>
                            <w:rPr>
                                
                            </w:rPr>
                        </w:pPr>
                        <xsl:apply-templates/>
                    </w:p>
                </w:tc>
            </xsl:when>
            <xsl:otherwise>
                <w:tc>
                    <w:tcPr>
                        <xsl:if test="$totalRowWidth and @width">
                            <w:tcW w:w="{concat(string(@width div $totalRowWidth*100),'%')}" w:type="pct"/>
                        </xsl:if>
                        <xsl:if test="@valign">
                            <w:vAlign w:val="{@valign}"/>
                        </xsl:if>
                        <xsl:call-template name="parseStyleAttribute"></xsl:call-template>
                    </w:tcPr>
                    <xsl:apply-templates> </xsl:apply-templates>
                </w:tc>     
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
    <xsl:template match="text()">
        
        <xsl:param name="sup" tunnel="yes"></xsl:param>
        <xsl:param name="sub" tunnel="yes"></xsl:param>
        <xsl:param name="style" tunnel="yes"></xsl:param>
        <xsl:param name="bold" tunnel="yes"></xsl:param>
        <xsl:param name="italic" tunnel="yes"></xsl:param>
        <xsl:param name="h" tunnel="yes"></xsl:param>
        <xsl:variable name="propForMath">
            <xsl:value-of select="string($bold)"></xsl:value-of>
        </xsl:variable>
        
        <xsl:variable name="list" select="ext:generateWordMarkup(.)"></xsl:variable>
        <xsl:for-each select="$list">
            <xsl:choose>
                <xsl:when test="starts-with(current(),'&lt;m:oMathPara')">
                    <xsl:value-of select="current()" disable-output-escaping="yes"></xsl:value-of>
                </xsl:when>
                <xsl:otherwise>
                    <w:r>
                        <w:rPr>
                            <xsl:copy-of select="$bold"></xsl:copy-of>
                            <xsl:copy-of select="$italic"></xsl:copy-of>
                            <xsl:copy-of select="$h"></xsl:copy-of>
                            <xsl:copy-of select="$sub"></xsl:copy-of>
                            <xsl:copy-of select="$sup"></xsl:copy-of>
                            <xsl:copy-of select="$style"></xsl:copy-of>
                        </w:rPr>
                        <w:t xml:space="preserve"><xsl:value-of select="."/></w:t>
                    </w:r>
                </xsl:otherwise>
            </xsl:choose>
            
        </xsl:for-each>
        
        
    </xsl:template>
    <xsl:template name="parseStyleAttribute">
        <xsl:param name="style" select="tokenize(string(@style),';')"></xsl:param>
        <xsl:variable name="currentElement" select="name()"></xsl:variable>
        <!-- jednotlive atributy style -->
        <xsl:variable name="properties">
            <style name="text-align">
                <value name="left"><w:jc w:val="left"/></value>
                <value name="center"><w:jc w:val="center"/></value>
                <value name="right"><w:jc w:val="right"/></value>
            </style>
            <style name="padding-left">
                <value name="30px"><w:ind w:left="708"/></value>
                <value name="60px"><w:ind w:left="1062"/></value>
                <value name="90px"><w:ind w:left="1416"/></value>
            </style>
            <style name="text-decoration">
                <value name="underline"> <w:u w:val="single"/></value>
                <value name="line-through"><w:strike/></value>
            </style>
            <style name="font-size">
                <value name="xx-small"><w:sz w:val="16"/><w:szCs w:val="16"/></value>
                <value name="x-small"><w:sz w:val="20"/><w:szCs w:val="20"/></value>
                <value name="small"><w:sz w:val="24"/><w:szCs w:val="24"/></value>
                <value name="medium"><w:sz w:val="28"/><w:szCs w:val="28"/></value>
                <value name="large"><w:sz w:val="36"/><w:szCs w:val="36"/></value>
                <value name="x-large"><w:sz w:val="48"/><w:szCs w:val="48"/></value>
                <value name="xx-large"><w:sz w:val="72"/><w:szCs w:val="72"/></value>
            </style>
            <!-- color reseny jinde -->
            <style name="background-color">
                <value name="#ffff00"><w:highlight w:val="yellow"/></value>
                <value name="#00ff00"><w:highlight w:val="green"/></value>
                <value name="#00ffff"><w:highlight w:val="cyan"/></value>
                <value name="#ff00ff"><w:highlight w:val="magenta"/></value>
                <value name="#0000ff"><w:highlight w:val="blue"/></value>
                <value name="#ff0000"><w:highlight w:val="red"/></value>
                <value name="#008080"><w:highlight w:val="darkCyan"/></value>
                <value name="#008000"><w:highlight w:val="darkGreen"/></value>
                <value name="#800080"><w:highlight w:val="darkMagenta"/></value>
                <value name="#800000"><w:highlight w:val="darkRed"/></value>
                <value name="#808000"><w:highlight w:val="darkYellow"/></value>
                <value name="#999999"><w:highlight w:val="darkGray"/></value>
                <value name="#c0c0c0"><w:highlight w:val="lightGray"/></value>
                <value name="#000000"><w:highlight w:val="black"/></value>
            </style>
            <style name="vertical-align">
                <value name="top"><w:position w:val="{-0.68*2*@height}"/></value>
                <value name="text-top"><w:position w:val="{-0.68*2*@height}"/></value>
                <value name="middle"><w:position w:val="{-0.7*@height}"/></value>
                <value name="bottom"/>
                <value name="text-bottom"/>
                <value name="baseline"/>
            </style>
        </xsl:variable>
        
        <xsl:for-each select="$style" >
            <xsl:if test="not(position()=count($style))">
                <xsl:variable name="property2value" select="tokenize(.,':')"></xsl:variable>
                <xsl:choose >
                    <!-- text color in paragraph -->
                    <xsl:when test="normalize-space($property2value[1])='color' ">
                        <xsl:variable name="colorElement">
                            <w:color w:val="{upper-case(substring(normalize-space($property2value[2]),2))}"/>
                        </xsl:variable>
                        <xsl:copy-of select="$colorElement"></xsl:copy-of>
                    </xsl:when>
                    <!-- table cell width -->
                    <xsl:when test="$currentElement='td' and normalize-space($property2value[1])='width'">
                        <xsl:variable name="cellWidthElement">
                            <w:tcW w:w="{number(substring-before(normalize-space($property2value[2]),'px'))*20}" w:type="dxa"></w:tcW>
                        </xsl:variable>
                        <xsl:copy-of select="$cellWidthElement"></xsl:copy-of>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:copy-of select="$properties/style[@name=normalize-space($property2value[1])]/value[@name=normalize-space($property2value[2])]/child::*"></xsl:copy-of>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:if>
        </xsl:for-each>   
    </xsl:template>
   
    <!--################################### Help templates ###################################-->
    <xsl:template name="category_template">
        <xsl:param name="weight"></xsl:param>
        <xsl:param name="category"></xsl:param>
        <xsl:param name="instructions"></xsl:param>
        <xsl:param name="displayPoints"></xsl:param>
        <xsl:param name="points"></xsl:param>
        <xsl:call-template name="space"></xsl:call-template>
        <xsl:if test="$instructions!=''">
            <w:p>
                <w:pPr>
                    <w:rPr>
                        <w:i/>
                        <w:sz w:val="20"/>
                        <w:szCs w:val="20"/>
                    </w:rPr>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:i/>
                        <w:sz w:val="24"/>
                        <w:szCs w:val="24"/>
                    </w:rPr>
                    <w:t xml:space="preserve"> <xsl:value-of select="$instructions"></xsl:value-of> </w:t>
                </w:r>
            </w:p>
        </xsl:if> 
        <w:p>
            <w:pPr>
                <w:rPr>
                    <w:b/>
                    <w:i/>
                    <w:sz w:val="{24-$weight}"/>
                    <w:szCs w:val="{24-$weight}"/>
                </w:rPr>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:i/>
                    <w:sz w:val="{24-$weight}"/>
                    <w:szCs w:val="{24-$weight}"/>
                </w:rPr>
                <w:t xml:space="preserve"><xsl:value-of select="$category"/> <xsl:if test="@displayPoints=1"><xsl:call-template name="points"><xsl:with-param name="body" select="$points"></xsl:with-param></xsl:call-template></xsl:if></w:t>
            </w:r>
        </w:p>
    </xsl:template>
    
    <xsl:template name="points">
        <xsl:param name="body"></xsl:param>
        <xsl:choose>
            <xsl:when test="$body=1"><xsl:text>(1 bod)</xsl:text></xsl:when>
            <xsl:when test="$body&gt;1 and $body &lt;5">
                <xsl:text>(</xsl:text><xsl:value-of select="$body"/><xsl:text> body)</xsl:text></xsl:when>
            <xsl:when test="$body&gt;4">
                <xsl:text>(</xsl:text><xsl:value-of select="$body"/><xsl:text> bodů)</xsl:text></xsl:when>
        </xsl:choose>
    </xsl:template>
    <xsl:template name="academic-year">
        <xsl:variable name="date" select="tokenize(current-date() cast as xs:string,'-')"></xsl:variable>
        
        <xsl:variable name="year" select="$date[1] cast as xs:integer"></xsl:variable>
        <xsl:variable name="month" select="$date[2] cast as xs:integer"></xsl:variable>
        <xsl:value-of select="floor($year - (1 - $month div 9))"></xsl:value-of><xsl:text>/</xsl:text>
        <xsl:value-of select="floor($year + ($month div 9))"></xsl:value-of>
    </xsl:template>
    
    <xsl:template name="questionFragmentItalic">
        <xsl:param name="val"/>
        <w:r>
            <w:rPr>
                <w:i/>
                <w:sz w:val="22"/>
                <w:szCs w:val="22"/>
                
            </w:rPr>
            <w:t xml:space="preserve"><xsl:value-of select="$val"></xsl:value-of></w:t>
        </w:r>
    </xsl:template>
    
    <!-- Vlozi do dokumentu prazdny odstavec -->
    <xsl:template name="space">
        <w:p>
            <w:pPr>
                <w:rPr>
                    <w:i/>
                    <w:sz w:val="24"/>
                    <w:szCs w:val="24"/>
                </w:rPr>
            </w:pPr>
        </w:p>
    </xsl:template>
    
    <xsl:template name="tab">
        <w:r>
            <w:tab/>
        </w:r>
    </xsl:template>
    
    
    <xsl:template name="header1">
        <xsl:param name="desc"/>
        <xsl:param name="points"></xsl:param>
        <xsl:param name="display"></xsl:param>
        <xsl:param name="displayPoints"></xsl:param>
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:rPr>
                    <w:b/>
                    <w:i/>
                    <w:sz w:val="24"/>
                    <w:szCs w:val="24"/>
                </w:rPr>
            </w:pPr>
            <xsl:if test="$display!=0">
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:i/>
                    <w:sz w:val="28"/>
                    <w:szCs w:val="28"/>
                </w:rPr>
                <w:t xml:space="preserve"><xsl:value-of select="upper-case($desc)"/> <xsl:if test="$displayPoints!=0"><xsl:call-template name="points"><xsl:with-param name="body" select="$points"></xsl:with-param></xsl:call-template></xsl:if></w:t>
            </w:r>
            </xsl:if>
            <w:r>
                <w:rPr>
                    <w:sz w:val="18"/>
                    <w:szCs w:val="18"/>
                </w:rPr>
                <w:t xml:space="preserve"> (<xsl:value-of select="ext:generateTestCode()"/>)</w:t>
            </w:r>
        </w:p>
        <w:p >
            <w:pPr>
                <w:jc w:val="center"/>
                <w:rPr>
                    <w:i/>
                    <w:sz w:val="24"/>
                    <w:szCs w:val="24"/>
                </w:rPr>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:i/>
                    <w:sz w:val="24"/>
                    <w:szCs w:val="24"/>
                </w:rPr>
                <w:t xml:space="preserve">Jméno:                               </w:t>
            </w:r>
            <w:r>
                <w:rPr>
                    <w:i/>
                    <w:sz w:val="24"/>
                    <w:szCs w:val="24"/>
                </w:rPr>
                <w:tab/>
                <w:t xml:space="preserve">Přednášející:                                Studijní skupina:                                </w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:rPr>
                    <w:i/>
                    <w:sz w:val="24"/>
                    <w:szCs w:val="24"/>
                </w:rPr>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:i/>
                    <w:sz w:val="24"/>
                    <w:szCs w:val="24"/>
                </w:rPr>
                <w:t xml:space="preserve">Datum:</w:t>
            </w:r>
        </w:p>
        <!--<xsl:if test="$settings/settings/course[@name=$course]/test[@name=$test]/desc" >-->
        <w:p>
            <w:pPr>
                <w:rPr>
                    <w:i/>
                    <w:sz w:val="24"/>
                    <w:szCs w:val="24"/>
                </w:rPr>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:sz w:val="24"/>
                    <w:szCs w:val="24"/>
                </w:rPr>
                <w:t xml:space="preserve"><!--<xsl:value-of select="$settings/settings/course[@name=$course]/test[@name=$test]/desc"></xsl:value-of>--></w:t>
            </w:r>
        </w:p>
        <!--</xsl:if>-->
    </xsl:template>
    
    
    <xsl:template name="header2">
        <xsl:param name="desc"/>
        <xsl:param name="points"></xsl:param>
        <xsl:param name="display"></xsl:param>
        <xsl:param name="displayPoints"></xsl:param>
        <w:tbl>
            <w:tblPr>
                <w:tblStyle w:val="Mkatabulky"/>
                <w:tblW w:w="0" w:type="auto"/>
                <w:tblBorders>
                    <w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                </w:tblBorders>
                <w:tblLook w:val="04A0" w:firstRow="1" w:lastRow="0" w:firstColumn="1"
                    w:lastColumn="0" w:noHBand="0" w:noVBand="1"/>
            </w:tblPr>
            <w:tblGrid>
                <w:gridCol w:w="2093"/>
                <w:gridCol w:w="2410"/>
                <w:gridCol w:w="1134"/>
                <w:gridCol w:w="2693"/>
                <w:gridCol w:w="882"/>
            </w:tblGrid>
            <w:tr w:rsidR="00767541" w:rsidTr="00767541">
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="5637" w:type="dxa"/>
                        <w:gridSpan w:val="3"/>
                    </w:tcPr>
                    <w:p w:rsidR="00767541" w:rsidRPr="00767541" w:rsidRDefault="00767541">
                        <w:pPr>
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:b/>
                                <w:sz w:val="32"/>
                                <w:szCs w:val="32"/>
                            </w:rPr>
                        </w:pPr>
                        <w:r w:rsidRPr="00767541">
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:b/>
                                <w:sz w:val="32"/>
                                <w:szCs w:val="32"/>
                            </w:rPr>
                            <w:t xml:space="preserve"><xsl:value-of select="$desc"/> <xsl:if test="$displayPoints!=0"><xsl:call-template name="points"><xsl:with-param name="body" select="$points"></xsl:with-param></xsl:call-template></xsl:if></w:t>
                        </w:r>
                    </w:p>
                </w:tc>
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="2693" w:type="dxa"/>
                    </w:tcPr>
                    <w:p w:rsidR="00767541" w:rsidRPr="00767541" w:rsidRDefault="00767541">
                        <w:pPr>
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:b/>
                                <w:sz w:val="32"/>
                                <w:szCs w:val="32"/>
                            </w:rPr>
                        </w:pPr>
                        <w:r w:rsidRPr="00767541">
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:b/>
                                <w:sz w:val="32"/>
                                <w:szCs w:val="32"/>
                            </w:rPr>
                            <w:t xml:space="preserve">(<xsl:call-template name="academic-year"></xsl:call-template>)</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="882" w:type="dxa"/>
                    </w:tcPr>
                    <w:p w:rsidR="00767541" w:rsidRDefault="00767541" w:rsidP="00767541">
                        <w:r w:rsidRPr="00767541">
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:b/>
                                <w:sz w:val="32"/>
                                <w:szCs w:val="32"/>
                            </w:rPr>
                            <w:t>(</w:t>
                        </w:r>
                        <w:r>
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:b/>
                                <w:sz w:val="32"/>
                                <w:szCs w:val="32"/>
                            </w:rPr>
                            <w:t><xsl:value-of select="format-number($variant,'00')"></xsl:value-of></w:t>
                        </w:r>
                        <w:r w:rsidRPr="00767541">
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:b/>
                                <w:sz w:val="32"/>
                                <w:szCs w:val="32"/>
                            </w:rPr>
                            <w:t>)</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
            </w:tr>
            <w:tr w:rsidR="00767541" w:rsidTr="00767541">
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="2093" w:type="dxa"/>
                    </w:tcPr>
                    <w:p w:rsidR="00767541" w:rsidRPr="00767541" w:rsidRDefault="00767541">
                        <w:pPr>
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:i/>
                                <w:sz w:val="24"/>
                                <w:szCs w:val="24"/>
                            </w:rPr>
                        </w:pPr>
                        <w:r w:rsidRPr="00767541">
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:i/>
                                <w:sz w:val="24"/>
                                <w:szCs w:val="24"/>
                            </w:rPr>
                            <w:t>Datum</w:t>
                        </w:r>
                        <w:r>
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:i/>
                                <w:sz w:val="24"/>
                                <w:szCs w:val="24"/>
                            </w:rPr>
                            <w:t>:</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="2410" w:type="dxa"/>
                    </w:tcPr>
                    <w:p w:rsidR="00767541" w:rsidRPr="00767541" w:rsidRDefault="00767541">
                        <w:pPr>
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:i/>
                                <w:sz w:val="24"/>
                                <w:szCs w:val="24"/>
                            </w:rPr>
                        </w:pPr>
                        <w:r w:rsidRPr="00767541">
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:i/>
                                <w:sz w:val="24"/>
                                <w:szCs w:val="24"/>
                            </w:rPr>
                            <w:t xml:space="preserve">Stud. </w:t>
                        </w:r>
                        <w:proofErr w:type="gramStart"/>
                        <w:r w:rsidRPr="00767541">
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:i/>
                                <w:sz w:val="24"/>
                                <w:szCs w:val="24"/>
                            </w:rPr>
                            <w:t>skup.</w:t>
                        </w:r>
                        <w:proofErr w:type="gramEnd"/>
                        <w:r w:rsidRPr="00767541">
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:i/>
                                <w:sz w:val="24"/>
                                <w:szCs w:val="24"/>
                            </w:rPr>
                            <w:t>:</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="4709" w:type="dxa"/>
                        <w:gridSpan w:val="3"/>
                    </w:tcPr>
                    <w:p w:rsidR="00767541" w:rsidRPr="00767541" w:rsidRDefault="00767541"
                        w:rsidP="00767541">
                        <w:pPr>
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:i/>
                                <w:sz w:val="24"/>
                                <w:szCs w:val="24"/>
                            </w:rPr>
                        </w:pPr>
                        <w:r w:rsidRPr="00767541">
                            <w:rPr>
                                <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"
                                    w:cs="Times New Roman"/>
                                <w:i/>
                                <w:sz w:val="24"/>
                                <w:szCs w:val="24"/>
                            </w:rPr>
                            <w:t>Jméno:</w:t>
                        </w:r>
                    </w:p>
                </w:tc>
            </w:tr>
        </w:tbl>
       
        
    </xsl:template>
    
    
    <xsl:template name='teacherPoints'>
    <xsl:param name="points" tunnel="yes"></xsl:param>
    	<w:tbl>
            <w:tblPr>
                <w:tblW w:w="10314" w:type="dxa"/>
                <w:tblBorders>
                    <w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                    <w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>
                </w:tblBorders>
                <w:tblLayout w:type="fixed"/>
            </w:tblPr>
            <w:tblGrid>
                <w:gridCol w:w="392"/>
                <w:gridCol w:w="8788"/>
                <w:gridCol w:w="1134"/>
            </w:tblGrid>
            <w:tr >
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="392" w:type="dxa"/>
                    </w:tcPr>
                    <w:p>
                        <w:pPr>
                            <w:rPr>
                            </w:rPr>
                        </w:pPr>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                                <w:sz w:val="16"/>
                                <w:szCs w:val="16"/>
                            </w:rPr>
                        </w:r>
                    </w:p>
                </w:tc>
                
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="392" w:type="dxa"/>
                    </w:tcPr>
                    <w:p>
                        <w:pPr>
                            <w:rPr>
                            </w:rPr>
                        </w:pPr>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                                <w:sz w:val="16"/>
                                <w:szCs w:val="16"/>
                            </w:rPr>
                            <w:t>Jméno:bla<xsl:value-of select="$points"></xsl:value-of>
                            <xsl:for-each select="0 to xs:integer($points)">
    					  <xsl:value-of select="current()"/>
    					</xsl:for-each>
    </w:t>
                        </w:r>
                    </w:p>
                </w:tc>
                
                <w:tc>
                    <w:tcPr>
                        <w:tcW w:w="1134" w:type="dxa"/>
                    </w:tcPr>
                    <w:p >
                        <w:pPr>
                            <w:rPr>
                                <w:i/>
                            </w:rPr>
                        </w:pPr>
                       <w:r>
                       
	                	<w:sym w:font="Wingdings 2" w:char="F02A"/>
	                       	
                       </w:r>
                    </w:p>
                </w:tc>
                
            </w:tr>
        </w:tbl>
     </xsl:template>
    
    
    
    <xsl:template name="substring-after-last">
        <xsl:param name="string" />
        <xsl:param name="delimiter" />
        <xsl:choose>
            <xsl:when test="contains($string, $delimiter)">
                <xsl:call-template name="substring-after-last">
                    <xsl:with-param name="string"
                        select="substring-after($string, $delimiter)" />
                    <xsl:with-param name="delimiter" select="$delimiter" />
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise><xsl:value-of select="$string" /></xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
    <xsl:template name="recursive">
        <xsl:param name="spaces"></xsl:param>
        <xsl:param name="num" select="0"></xsl:param> 
        <xsl:if test="not($num = $spaces)">
            <xsl:call-template name="space"/>
            <xsl:call-template name="recursive">
                <xsl:with-param name="num" select="$num+1"/>
                <xsl:with-param name="spaces" select="$spaces"></xsl:with-param>
            </xsl:call-template>
        </xsl:if>
    </xsl:template>
      
</xsl:stylesheet>