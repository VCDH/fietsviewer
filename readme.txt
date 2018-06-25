================================================================================
            fietsviewer - grafische weergave van fietsdata - README
================================================================================
fietsviewer (stylistisch: fietsvᴉewer) is een webgebaseerde grafische interface 
om fietsdata visueel te ontsluiten. Het doel is om fietsdata inzichtelijk te 
maken en om snel eenvoudige analyses te kunnen uitvoeren. Daartoe is 
fietsviewer meettechniek-onafhankelijk en kan overweg met verschillende 
verkeerskundige grootheden.

fietsviewer is bedacht en ontwikkeld door Gemeente Den Haag, afdeling 
Bereikbaarheid en Verkeersmanagement en aldaar geprogrammeerd door Jasper Vries.
 De broncode is als open source software beschikbaar gesteld, om het voor alle 
wegbeheerders mogelijk te maken om gebruik te maken van deze ontwikkeling. Het 
formele auteursrecht berust bij de Gemeente Den Haag.


================================================================================
0. Inhoudsopgave
================================================================================

1. Systeemvereisten en benodigdheden
2. Installatie
3. Dataformat
4. Licentie
5. Verkrijgen van de broncode


================================================================================
1. Systeemvereisten en benodigdheden
================================================================================

De grafische interface is geschreven in HTML5 in combinatie met JavaScript. 
Hiervoor is een recente webbrowser met ondersteuning voor HTML5 nodig. Primaire 
ontwikkeling vindt plaats in Mozilla Firefox. Er wordt gebruik gemaakt van de 
standaardlibraries JQuery, Leaflet in combinatie met OpenStreetMap, 
Leaflet Rotated Marker en JavaScript Cookie.

De backend is geschreven in PHP (5.3+) en gebruikt een MySQL (5+) of 
MariaDB (5+) DBMS.  Voor PHP < 5.5.0 wordt gebruik gemaakt van de bibliotheek
password_compat voor een in-plaats alternatief voor de PHP password_* functies.

URLs:
JQuery: https://jquery.com
Leaflet: https://leafletjs.com
Leaflet Rotated Marker: https://github.com/bbecquet/Leaflet.RotatedMarker
OpenStreetMap: https://www.openstreetmap.org
JavaScript Cookie: https://github.com/js-cookie/js-cookie
PHP: http://php.net
password_compat: https://github.com/ircmaxell/password_compat
MySQL: https://www.mysql.com
MariaDB: https://mariadb.org
Mozilla Firefox: https://www.mozilla.org/firefox


================================================================================
2. Installatie
================================================================================

De installatie maakt de databasetabellen aan. Voer install.php uit vanuit de 
opdrachtregel om het installatieprogramma te doorlopen. Hou de 
database-credentials bij de hand, hier wordt tijdens de installatie om gevraagd.

Bij het doorlopen van het programma wordt het bestand dbconfig.inc.php 
aangemaakt. Dit bestand is hierna desgewenst handmatig aan te passen, maar wordt 
overschreven wanneer het installatieprogramma opnieuw wordt uitgevoerd.
Overige configuratie staat in config.inc.php. Deze hoeft normaal gesproken niet 
aangepast te worden en is daardoor geen onderdeel van het installatieprogramma.


================================================================================
3. Dataformat
================================================================================

Voor import van fietsdata wordt gebruik gemaakt van het format dat is 
vastgesteld voor het Data Platform Fiets (CROW-Fietsberaad), wat voor het doel 
van fietsviewer is uitgebreid voor andere grootheden dan intensiteiten. Zie 
docs/fietsviewer dataformat.pdf of fietsviewer dataformat.docx voor een 
beschrijving van het dataformat.


================================================================================
4. Licentie
================================================================================

De broncode van fietsviewer is vrijgegeven onder de voorwaarde van de 
GNU General Public License versie 3 of hoger. Voor gebundelde libraries kunnen 
andere licentievoorwaarden van toepassing zijn. Zie hiervoor de documentatie in 
de betreffende submappen.

Met uitzondering van gebundelde libraries is voor fietsviewer het volgende van 
toepassing:

 	fietsviewer - grafische weergave van fietsdata
    Copyright (C) 2018 Jasper Vries, Gemeente Den Haag
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.


================================================================================
5. Verkrijgen van de broncode
================================================================================

De broncode van de fietsviewer is gepubliceerd op Bitbucket.
https://bitbucket.org/vcdh/fietsviewer/
