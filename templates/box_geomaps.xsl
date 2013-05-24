<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">

<xsl:param name="PAGE_THEME_PATH" />
<xsl:param name="PAGE_WEB_PATH" />

<!-- geo maps box template, author: martin kelm -->

<xsl:output method="xml" encoding="utf-8" standalone="no" indent="no" omit-xml-declaration="yes" />

  <xsl:template match="geomap">
    <!-- main template -->
    <xsl:choose>
      <xsl:when test="static and static/@force = 1">
        <!-- use static content only -->
        <xsl:call-template name="static-content" />
      </xsl:when>
      <xsl:otherwise>
        <!-- use default content -->
        <xsl:call-template name="html-containers" />
        <xsl:call-template name="include-js-files" />
        <xsl:call-template name="map-js-content" />
        <xsl:call-template name="noscript-content" />
      </xsl:otherwise>
    </xsl:choose>

    <xsl:call-template name="trip-planner-link" />

  </xsl:template>

  <xsl:template name="html-containers">
    <!-- coordinates html container -->
    <xsl:if test ="options/@coor-mode = 1">
      <div id="coor_{options/@id}"><xsl:text> </xsl:text></div>
    </xsl:if>
    <!-- map html container -->
    <div id="map_{options/@id}" style="width: {size/@width}px; height: {size/@height}px;"><xsl:text> </xsl:text></div>
  </xsl:template>

  <xsl:template name="include-js-files">
    <!-- map js files -->
    <xsl:choose>
      <xsl:when test="@type = 0">
        <!-- google maps -->
        <script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={@api-key}"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
        <script type="text/javascript" src="{options/@scripts-path}geomaps_google.js"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
      </xsl:when>
      <xsl:otherwise>
        <!-- yahoo maps -->
        <script type="text/javascript" src="http://api.maps.yahoo.com/ajaxymap?v=3.4&amp;appid={@api-key}"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
        <script type="text/javascript" src="{options/@scripts-path}geomaps_yahoo.js"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
      </xsl:otherwise>
    </xsl:choose>

    <!-- script to handle map markers -->
    <xsl:if test="markers and markers/@mode != 'hidden'">
      <script type="text/javascript" src="{options/@scripts-path}geomaps_markers.js"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
    </xsl:if>

  </xsl:template>

  <xsl:template name="map-js-content">
    <!-- map java script content -->
    <xsl:choose>
      <xsl:when test="@type = 0">
        <!-- google maps -->
        <script type="text/javascript"><xsl:comment>
          initGoogleMaps(<xsl:value-of select="options/@coor-mode" />,
            <xsl:value-of select="controls/@basic" />, <xsl:value-of select="controls/@scale" />,
            <xsl:value-of select="controls/@type" />, <xsl:value-of select="controls/@overview" />,
            <xsl:value-of select="center/@lat" />, <xsl:value-of select="center/@lng" />, <xsl:value-of select="center/@zoom" />,
            <xsl:value-of select="options/@map-type" />, '<xsl:value-of select="options/@id" />',
            <xsl:value-of select="size/@width" />, <xsl:value-of select="size/@height" />
          );
          <xsl:call-template name="markers-js-content" />
        </xsl:comment></script>
      </xsl:when>
      <xsl:otherwise>
        <!-- yahoo maps -->
        <script type="text/javascript"><xsl:comment>
          initYahooMaps(<xsl:value-of select="options/@coor-mode" />,
            <xsl:value-of select="controls/@zoom" />, <xsl:value-of select="controls/@pan" />, <xsl:value-of select="controls/@type" />,
            <xsl:value-of select="center/@lat" />, <xsl:value-of select="center/@lng" />, <xsl:value-of select="center/@zoom" />,
            <xsl:value-of select="options/@map-type" />, '<xsl:value-of select="options/@id" />'
          );
          <xsl:call-template name="markers-js-content" />
        </xsl:comment></script>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="markers-js-content">
    <!-- js content -->
    <xsl:if test="markers and markers/@url != ''">
      <xsl:choose>
        <xsl:when test="contains(markers/@url, '?')">
          <xsl:variable name="url" select="substring-before(markers/@url, '?')" />
          <xsl:variable name="params" select="substring-after(markers/@url, '?')" />
          <!-- markers ajax request -->
          addMarkers('<xsl:value-of select="$url" />', '<xsl:value-of select="$params" />');
        </xsl:when>
        <xsl:otherwise>
          <xsl:variable name="url" select="markers/@url" />
          <!-- markers ajax request -->
          addMarkers('<xsl:value-of select="$url" />', '');
        </xsl:otherwise>
      </xsl:choose>
      <!-- get polyline -->
      <xsl:if test="markers/polyline/@active = 1">
        getPolyline('<xsl:value-of select="markers/polyline/@color" />', '<xsl:value-of select="markers/polyline/@size" />');
      </xsl:if>
      <!-- get / show markers -->
      <xsl:if test="markers/@mode != 'hide'">
        getMarkers('<xsl:value-of select="markers/@action" />', '<xsl:value-of select="markers/@mode" />',
                    <xsl:value-of select="markers/@rotation" />, <xsl:value-of select="markers/@description" />,
                    <xsl:value-of select="markers/@zoom-focus" />, '<xsl:value-of select="markers/@color" />');
      </xsl:if>
    </xsl:if>
  </xsl:template>

  <xsl:template name="noscript-content">
    <!-- no script content -->
    <noscript>
      <xsl:choose>
        <!-- static image -->
        <xsl:when test="static">
          <xsl:call-template name="static-content" />
        </xsl:when>
        <xsl:otherwise>
          <!-- no script text -->
          <xsl:value-of select="no-script-text" />
        </xsl:otherwise>
      </xsl:choose>
      <xsl:text> </xsl:text>
    </noscript>
  </xsl:template>

  <xsl:template name="static-content">
    <!-- static content -->
    <xsl:choose>
      <xsl:when test="static/link/@url != ''">
        <!-- static image with permalink to static version -->
        <a href="{static/link/@url}" target="{static/link/@target}">
          <img src="{static/image/@url}" alt="{static/image/@text}" width="{static/image/@width}" height="{static/image/@height}" />
        </a>
      </xsl:when>
      <xsl:when test="center/@permalink != ''">
        <!-- static image with permalink to dynamic version -->
        <a href="{center/@permalink}" target="{static/link/@target}">
          <img src="{static/image/@url}" alt="{static/image/@text}" width="{static/image/@width}" height="{static/image/@height}" />
        </a>
      </xsl:when>
      <xsl:otherwise>
        <!-- static image only -->
        <img src="{static/image/@url}" alt="{static/image/@text}" width="{static/image/@width}" height="{static/image/@height}" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="trip-planner-link">
    <xsl:choose>
      <xsl:when test="static and static/@force = 1 and static/trip-planner/text()">
        <!-- forced static content -->
        <div id="map_tp_{options/@id}"><a href="{static/trip-planner/@href}"><xsl:value-of select="static/trip-planner/text()" /></a></div>
      </xsl:when>
      <xsl:when test="trip-planner/text()">
        <!-- default content -->
        <div id="map_tp_{options/@id}"><a href="{trip-planner/@href}"><xsl:value-of select="trip-planner/text()" /></a></div>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>

