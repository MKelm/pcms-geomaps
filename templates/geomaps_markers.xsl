<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- geo maps markers template, author: martin kelm -->

<xsl:output method="xml" encoding="utf-8" standalone="no" indent="yes" omit-xml-declaration="no" />

<xsl:template match="/page">
  <!-- use cdata (1) or not (0) -->
  <xsl:variable name="useCDATA" select="'0'" />

  <kml xmlns="http://earth.google.com/kml/2.1">
  <Document>
    <!-- put placemark node for each marker -->
    <xsl:for-each select="content/topic/markers/*[name() = 'Placemark']">
      <Placemark>
        <xsl:copy-of select="./@*" />
        <!-- name / title -->
        <xsl:if test="name">
          <xsl:copy-of select="name" />
        </xsl:if>
        <!-- set description if available with or without cdata -->
        <xsl:if test="description">
          <description>
            <xsl:choose>
              <xsl:when test="$useCDATA = 1">
                <xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
                <xsl:copy-of select="description/node()" />
                <xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:copy-of select="description/node()" />
              </xsl:otherwise>
            </xsl:choose>
          </description>
        </xsl:if>
        <!-- address -->
        <xsl:if test="address">
          <xsl:copy-of select="address" />
        </xsl:if>
        <!-- longitude / latitude -->
        <xsl:if test="Point">
          <xsl:copy-of select="Point" />
        </xsl:if>
      </Placemark>
    </xsl:for-each>

  </Document>
  </kml>
</xsl:template>

</xsl:stylesheet>