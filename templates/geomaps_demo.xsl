<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">

<xsl:output method="xml" encoding="utf-8" standalone="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" indent="yes" omit-xml-declaration="yes" />

<!-- Params -->
<xsl:param name="PAGE_THEME_PATH" />
<xsl:param name="PAGE_WEB_PATH" />
<xsl:param name="PAGE_TITLE" />

<xsl:template match="page">
<html lang="{$PAGE_LANGUAGE}">
  <head>
    <title><xsl:value-of select="meta/metatags/pagetitle"/> - <xsl:value-of select="$PAGE_TITLE" disable-output-escaping="yes" /></title>
    <meta name="robots" content="index,follow" />
  </head>
  <body>
    <xsl:if test="count(boxes/box[@group = 'geomaps']) &gt; 0">
      <xsl:for-each select="boxes/box[@group = 'geomaps']">
        <xsl:value-of select="." disable-output-escaping="yes"/>
      </xsl:for-each>
    </xsl:if>
  </body>
</html>
</xsl:template>

</xsl:stylesheet>
