*Geo maps modules for papaya CMS 5*

Revision 0.23, 2008-11-15

Authors:
Martin Kelm <martinkelm@idxsolutions.de>
Bastian Feder <info@papaya-cms.com> <extensions>

Support and good fairy:
geomaps@idxsolutions.de

*Templates*
- "box_geomaps.xsl": use a text/html output filter
- "geomaps_markers.xsl": use a text/xml output filter
- "geomaps_demo.xsl": use a text/html output filter

*License*
GNU General Public Licence (GPL): gpl.txt

*General todos / ideas / whishes*
- More colors for dynamic markers
- OpenLayers support
- Use xslt parser for KML export
- More export formats
- Import for KML, ...

*Changelog*

_Rev. 0.23 (2008-11-15)_
- Added antoher connector method to get a marker by id
- Some smaller java script improvements
- Added demo page xsl to show Geo Maps boxes
- Reimplemented lost changes from rev. 0.21

_Rev. 0.22 (2008-11-13)_
- Added connector with two methods (folders list, add marker)
- Improved method to save new markers and fixed zip code
- Changed description to optional parameter
- Added seperate js function for coordinates mode

_Rev. 0.21 (2008-11-10)_
- Fixed PHP4 compatibility
- Fixed papaya CMS RC1 compatibility
- Some smaller improvements

_Rev. 0.20 (2008-11-10)_
- Refactored output again to get a better xml structure and options handling
- Added a log error message for missing API keys (thanks to Bastian)
- Optimized edit fields' arrangement in box modules
- Set GUnload event to Google Maps initialization
- Reimplemented static markers size / decorations
- Fixed markers rotation mode
- Fixed noscript content position in template
- Added center modes for different ways to center map
- Added a calculation method to get markers' center point (thanks to Bastian)
- Added checks / corrections for zoom levels
- Extended address data in database and modified backend dialogs
- Fixed / extended get coordinates by address feature in backend
- Fixed view modes selection
- Fixed param name in content module and added base kml param for ajax requests
- Added full kml data as default content output
- Fixed latitude / longitude range checks
- Fixed richtext description output and removed auto-breaklines
- Fixed backend functions to change marker's position

_Rev. 0.19 (2008-11-05)_
- Changed "gmaps" naming to "geomaps" (use DB script to migrate!)
- Optimized code structure / style and refactored output to get xml only
- Rebuild box modules for Google Maps and Yahoo Maps
- Added / extended static map / permalink support for Yahoo / Google Maps
- Added new box template to get html / js code by xml
- Fixed coordinates mode in Yahoo Maps and did some other js improvements
- Enhanced KML export + Google Eath compatibility
- Added option to use first marker as center point
- Fixed and added new Google Maps types
- Added optional CDATA option in markers data xslt

_Rev. 0.18 (2008-10-31)_
- Merged Google Maps extensions by Bastian Feder (thanks!):
  * Static Google Map with markers
  * Permalink to Google Maps
  * Link to Google Maps trip planer
  * Zoom Into Focus
  * Extended marker settings in static maps (colors, size, decoration)
  * JavaScript improvements

_Rev. 0.17 (2008-08-01)_
- Fixed Firefox3 JS-Bug in coordinates mode
- Fixed Latitude / Longitude value in KML parser
- Moved JS-Scripts Folder to own folder
- Removed CDATA from XSL to get extended HTML descriptions, i.e. embedded videos
- Fixed edit map api key

_Rev. 0.16 (2008-01-17)_
- fixed icons / glyphs
- fixed get coordinates by address again
- added folder tag to kml export and removed address tag
- removed guestbook plugin, no longer supported

_Rev. 0.15 (2007-08-18)_
- moved custom icons to module's pics folder
- fixed get coordinates by address feature to work in new backend
- some other optimizations

_Rev. 0.14 (2007-07-11)_
- changed icons to new backend icons
- added new custom icons based on tango icons

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
