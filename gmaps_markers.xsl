<?xml version="1.0"?> 
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml" encoding="utf-8" standalone="no" indent="yes" omit-xml-declaration="no" />

<xsl:template match="/page">
	<kml xmlns="http://earth.google.com/kml/2.1">
	<Document>
	  <xsl:for-each select="content/topic/markers/*[name() = 'Placemark']">
	    <Placemark>
	      <xsl:copy-of select="./@*" />
	      <xsl:if test="name">
	      	<xsl:copy-of select="name" />
	      </xsl:if>
	      <xsl:if test="description">
					<description>
					  <xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
						<xsl:copy-of select="description/*" />
						<xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
					</description>
	      </xsl:if>
	      <xsl:if test="address">
	      	<xsl:copy-of select="address" />
	      </xsl:if>
	      <xsl:if test="Point">
	      	<xsl:copy-of select="Point" />
	      </xsl:if>
	    </Placemark>
	  </xsl:for-each>
		
	</Document>
	</kml>
</xsl:template>

</xsl:stylesheet>
