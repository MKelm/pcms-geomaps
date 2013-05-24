<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <!-- geo maps markers template -->

  <!--
    NOTE: Use IE8 compatibility mode, to support coordinates click mode!
    <xsl:comment> &lt;meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7"/&gt; </xsl:comment>
  -->

  <xsl:output method="xml" encoding="utf-8" standalone="no" indent="yes" omit-xml-declaration="no" />

  <xsl:template match="/page">
    <xsl:choose>
      <xsl:when test="content/topic/markers/@base-kml = 0">
        <xsl:copy-of select="content/topic/markers/node()" />
      </xsl:when>
      <xsl:otherwise>
        <kml xmlns="http://earth.google.com/kml/2.2">
          <Document>
            <xsl:call-template name="placemarks" />
          </Document>
        </kml>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="placemarks">
    <!-- use cdata (1) or not (0) -->
    <xsl:variable name="useCDATA" select="'1'" />

    <!-- put placemark node for each marker -->
    <xsl:for-each select="content/topic/markers/Placemark">
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
        <!-- style for custom icons -->
        <xsl:if test="Style">
          <xsl:copy-of select="Style" />
        </xsl:if>
      </Placemark>
    </xsl:for-each>
  </xsl:template>

</xsl:stylesheet>
