*Geo maps modules for papaya CMS 5*

Revision 0.13, 2007-06-27
Author: Martin Kelm
E-Mail: martinkelm@idxsolutions.de


*Get markers data*
You have to create one markers data page in minimum which uses the
"gmaps_markers.xsl" template. You can use the page's id for any map box
later.

*Hint for Google Maps*
You should use this unload event in your body element:
<body onunload="if (typeof GUnload != 'undefined') GUnload();">

*Geo maps box modules for guestbooks (requirements)*
- Guestbook module 1.1: http://www.idxsolutions.de/dl-guestbook
- Current GeoIP databases (extract to "/papaya-lib/external/geoip/"):
http://www.maxmind.com/download/geoip/database/GeoIP.dat.gz (~1mb)
http://www.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz (~17mb)

*Template*
Move "gmaps_markers.xsl" to your template folder.

*CSS*
Add this css code to your stylesheet:
div.geoMap {
	width: 640px; 
	height: 480px; 
	border: 1px solid black;
}
div.geoMapDesc {
	width: 350px;
}

*License*
GNU General Public Licence (GPL): gpl.txt



*Changelog*

_Rev. 0.14 (2007-07-11)_
- Changed icons to new backend icons
- Added new custom icons based on tango icons

_Rev. 0.13 (2007-06-27)_
- added polyline output mode for content box modules
- added polyline functions to java scripts
- changed yahoo maps api from version 3.0 to 3.4
- some other optimizations

_Rev. 0.12 (2007-06-21)_
- added marker data xsl template
- Google Maps js syntax correction
- changed css class of marker descriptions to "geoMapDesc"

_Rev. 0.11 (2007-06-03)_
- primary marker data set to coordinates
- coordinates by address: removed on-demand geocoding (front-end)
- coordinates by address: added tool in edit formular (back-end)
- added keys management for api keys / ids (back-end)
- new database table for keys (host specific)
- changes in menubar and new toolbar for markers (back-end)
- combined box modules of each maps type (with dynamic edit fields)
- removed code overhead in geo maps module for guestbooks
- some other optimizations and new icons

_Rev. 0.10 (2007-05-31)_
- js markers handling outsorced to sperate java script file
- added yahoo maps box modules and new java script file
- renaming into geo maps module, to prevent naming conflicts
- added sort functions for markers
- improved back-end messages

_Rev. 0.9 (2007-05-27)_
- added export markers to kml function
- added markers kml/xml page (supports guestbook plugin)
- added xml http requests to get markers from data page (js)
- richtext editor initialization fixed
- some other bugfixes and optimizations

_Rev. 0.8 (2007-05-23)_
- added box module with guestbook plugin (see requirements above)
- changed description edit field to richtext editor field
- added marker rotation mode (js)
- added mousewheel zoom (js)
- some other optimizations

_Rev. 0.7 (2007-05-19)_
- fixed css ids to w3c norm
- added database table for folders
- added folders in backend to group markers
- modified box module to use a selected folder

_Rev. 0.6 (2007-05-17)_
- added database table for markers
- new administration module to view / manage markers in database
- modified box module to use markers in database
- modified module names

_Rev. 0.5 (2007-05-13)_
- added coordinates mode to show latitude and longitude on click
- added default map type to center settings
- added mouse action to marker settings
- added css class settings and use unique ids
- changed float num checks for better compatibility

_Rev. 0.4 (2007-05-12)_
- removed marker focus handling
- added settings to center map
- added setting for zoom level

_Rev. 0.3 (2007-05-12)_
- new backend mode to manage markers
- bugfixes

_Rev. 0.2 (2007-05-04)_
- fixed markers' popup text

_Rev. 0.1 (2007-04-30)_
- new box module with settings and marker definitions
