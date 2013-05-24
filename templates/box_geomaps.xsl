<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">

  <xsl:param name="PAGE_THEME_PATH" />
  <xsl:param name="PAGE_WEB_PATH" />

  <!-- geo maps box template - needs apis and the geo maps di -->

  <xsl:output method="xml" encoding="utf-8" standalone="no" indent="no" omit-xml-declaration="yes" />

    <xsl:template match="geo-map">
      <!-- main template -->
      <xsl:if test="base/api/@type and base/api/@key and base/api/@key  != ''">
        <xsl:choose>
          <xsl:when test="static and static/@force = 1">
            <!-- force static content only -->
            <div id="map_{base/@id}" class="geoMap">
              <xsl:call-template name="static-content" />
            </div>
          </xsl:when>
          <xsl:otherwise>
            <!-- coordinates mode container -->
            <xsl:if test ="base/@coor-mode = 1">
              <div id="coor_{base/@id}" class="geoMapCoor"><xsl:text> </xsl:text></div>
            </xsl:if>
            <!-- map html container -->
            <div id="map_{base/@id}" class="geoMap" style="width: {settings/@width}px; height: {settings/@height}px;">
              <xsl:call-template name="noscript-content" /><xsl:text> </xsl:text>
            </div>
            <!-- js files -->
            <xsl:call-template name="include-js-files" />
            <!-- js content -->
            <xsl:call-template name="map-js-content" />
          </xsl:otherwise>
        </xsl:choose>

        <xsl:call-template name="trip-planner-link" />
      </xsl:if>
    </xsl:template>

    <xsl:template name="include-js-files">
      <!-- map js files -->
      <xsl:choose>
        <xsl:when test="base/api/@type = 'google'">
          <!-- google maps -->
          <script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={base/api/@key}"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
          <script type="text/javascript" src="{base/@scripts-path}geomaps-di/google.js"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
        </xsl:when>
        <xsl:otherwise>
          <!-- yahoo maps -->
          <script type="text/javascript" src="http://api.maps.yahoo.com/ajaxymap?v=3.4&amp;appid={base/api/@key}"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
          <script type="text/javascript" src="{base/@scripts-path}geomaps-di/yahoo.js"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
        </xsl:otherwise>
      </xsl:choose>

      <!-- script to handle map markers -->
      <xsl:if test="markers and markers/@mode != 'hidden'">
        <xsl:if test="base/api/@type = 'google' and markers/@clusterer = 1">
          <script type="text/javascript" src="{base/@scripts-path}geomaps-di/gmaps-utilities/markerclusterer_packed.js"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
          <script type="text/javascript" src="{base/@scripts-path}geomaps-di/clusterer_styles.js"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
        </xsl:if>
        <script type="text/javascript" src="{base/@scripts-path}geomaps-di/markers.js"><xsl:comment><xsl:text> </xsl:text></xsl:comment></script>
      </xsl:if>

    </xsl:template>

    <xsl:template name="map-js-content">
      <!-- map java script content -->
      <xsl:choose>
        <xsl:when test="base/api/@type = 'google'">
          <!-- google maps -->
          <script type="text/javascript"><xsl:comment>
            var currentUniqueId = '<xsl:value-of select="base/@id" />';
            initGoogleMaps(<xsl:value-of select="base/@coor-mode" />,
              <xsl:value-of select="settings/controls/@basic" />, <xsl:value-of select="settings/controls/@scale" />,
              <xsl:value-of select="settings/controls/@type" />, <xsl:value-of select="settings/controls/@overview" />,
              <xsl:value-of select="settings/center/@lat" />, <xsl:value-of select="settings/center/@lng" />, <xsl:value-of select="settings/@zoom" />,
              <xsl:value-of select="settings/@type" />, currentUniqueId,
              <xsl:value-of select="settings/@width" />, <xsl:value-of select="settings/@height" />
            );
            <xsl:call-template name="markers-js-content" />
          </xsl:comment></script>
        </xsl:when>
        <xsl:otherwise>
          <!-- yahoo maps -->
          <script type="text/javascript"><xsl:comment>
            var currentUniqueId = '<xsl:value-of select="base/@id" />';
            initYahooMaps(<xsl:value-of select="base/@coor-mode" />,
              <xsl:value-of select="settings/controls/@zoom" />, <xsl:value-of select="settings/controls/@pan" />, <xsl:value-of select="settings/controls/@type" />,
              <xsl:value-of select="settings/center/@lat" />, <xsl:value-of select="settings/center/@lng" />, <xsl:value-of select="settings/@zoom" />,
              <xsl:value-of select="settings/@type" />, currentUniqueId
            );
            <xsl:call-template name="markers-js-content" />
          </xsl:comment></script>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:template>

    <xsl:template name="markers-js-content">
      <!-- js content -->
      initMarkers(currentUniqueId);
      <xsl:if test="markers and markers/data-page/@url != ''">
        <xsl:choose>
          <xsl:when test="count(markers/marker) &gt; 0">
            geoMarkers[currentUniqueId] = new Array(
              <xsl:for-each select="markers/marker">
                new Array(null, '<xsl:copy-of select="description/node()" />', <xsl:value-of select="@lat" />, <xsl:value-of select="@lng" />, 
                <xsl:choose><xsl:when test="icon/@src">new Array('<xsl:value-of select="icon/@src" />', <xsl:value-of select="icon/@width" />, <xsl:value-of select="icon/@height" />)</xsl:when><xsl:otherwise>null</xsl:otherwise></xsl:choose>)<xsl:if test="position() != last()">, </xsl:if>
              </xsl:for-each>
            );
          </xsl:when>
          <xsl:when test="contains(markers/data-page/@url, '?')">
            <xsl:variable name="url" select="substring-before(markers/data-page/@url, '?')" />
            <xsl:variable name="params" select="substring-after(markers/data-page/@url, '?')" />
            <!-- markers ajax request -->
            addMarkers(currentUniqueId, '<xsl:value-of select="$url" />', '<xsl:value-of select="$params" />');
          </xsl:when>
          <xsl:otherwise>
            <!-- markers ajax request -->
            addMarkers(currentUniqueId, '<xsl:value-of select="markers/data-page/@url" />', '');
          </xsl:otherwise>
        </xsl:choose>
        <!-- get polyline -->
        <xsl:if test="markers/polyline/@active = 1">
          getPolyline(currentUniqueId, '<xsl:value-of select="markers/polyline/@color" />', '<xsl:value-of select="markers/polyline/@size" />');
        </xsl:if>
        
        <!-- get / show markers -->
        <xsl:if test="markers/@mode != 'hide'">
          getMarkers(currentUniqueId,
                     '<xsl:value-of select="markers/@mouse-desc-action" />', '<xsl:value-of select="markers/@mode" />',
                     <xsl:value-of select="markers/@rotation" />, <xsl:value-of select="markers/@show-description" />,
                     <xsl:value-of select="markers/@zoom-into-focus" />, '<xsl:value-of select="markers/@color" />',
                     <xsl:value-of select="markers/@clusterer" />);
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
        <xsl:when test="static/permalink/@url != ''">
          <!-- static image with permalink to static version -->
          <a href="{static/permalink/@url}" target="{base/@links-target}">
            <img src="{static/@image}" alt="{static/@alternative-text}" width="{settings/@width}" height="{settings/@height}" />
          </a>
        </xsl:when>
        <xsl:when test="permalink/@url != ''">
          <!-- static image with permalink to dynamic version -->
          <a href="{permalink/@url}" target="{base/@links-target}">
            <img src="{static/@image}" alt="{static/@alternative-text}" width="{settings/@width}" height="{settings/@height}" />
          </a>
        </xsl:when>
        <xsl:otherwise>
          <!-- static image only -->
          <img src="{static/@image}" alt="{static/@alternative-text}" width="{settings/@width}" height="{settings/@height}" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:template>

    <xsl:template name="trip-planner-link">
      <xsl:choose>
        <xsl:when test="static and static/@force = 1 and static/trip-planner/@caption != ''">
          <!-- forced static content -->
          <div id="map_tp_{base/@id}" class="geoMapTripPlanner">
            <a href="{static/trip-planner/@url}" target="{base/@links-target}"><xsl:value-of select="static/trip-planner/@caption" /></a>
          </div>
        </xsl:when>
        <xsl:when test="trip-planner/@caption != ''">
          <!-- default content -->
          <div id="map_tp_{base/@id}" class="geoMapTripPlanner">
            <a href="{trip-planner/@url}" target="{base/@links-target}"><xsl:value-of select="trip-planner/@caption" /></a>
          </div>
        </xsl:when>
      </xsl:choose>
    </xsl:template>

</xsl:stylesheet>

