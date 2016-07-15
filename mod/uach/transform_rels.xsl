<xsl:stylesheet version="2.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:def="http://schemas.openxmlformats.org/package/2006/relationships">

 <xsl:param name="filenames" required="yes"></xsl:param>
        <xsl:output method="xml" encoding="UTF-8" standalone="yes"></xsl:output>

    <xsl:template match="/">
        <xsl:apply-templates select="def:Relationships">
        </xsl:apply-templates>
    </xsl:template>
    
  
    
    <xsl:template match="def:Relationships">
     <xsl:copy>
         <xsl:apply-templates></xsl:apply-templates>
         
         <xsl:for-each select="tokenize($filenames,' ')">
             <xsl:variable name="extension">
                 <xsl:call-template name="substring-after-last">
                     <xsl:with-param name="string" select="current()" />
                     <xsl:with-param name="delimiter" select="'.'" />
                 </xsl:call-template>
             </xsl:variable>
             <xsl:element name="Relationship" namespace="http://schemas.openxmlformats.org/package/2006/relationships">
                 <xsl:attribute name="Id" select="concat('rId',replace(substring-after(current(),'image' ),concat('.',$extension),''))"></xsl:attribute>
                 <xsl:attribute name="Type" select="'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image'"></xsl:attribute>
                 <xsl:attribute name="Target" select="concat('media/' ,current() )"></xsl:attribute>
             </xsl:element>
         </xsl:for-each>
     </xsl:copy>
    </xsl:template>
    
    
    
    <xsl:template match="def:Relationship">
        <xsl:copy >
            <xsl:copy-of select="@*"></xsl:copy-of>
        </xsl:copy>
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
    
</xsl:stylesheet>