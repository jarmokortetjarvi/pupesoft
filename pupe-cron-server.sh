#!/bin/bash

#"Ohje: /polku/pupesoftiin/pupe-cron-server.sh"
#"Esim: /var/www/html/pupesoft/pupe-cron-server.sh"

POLKU=`dirname $0`

# Tarkistetaan mysql-tietokannan taulut
cd ${POLKU};php check-tables.php

# Päivitetään pupesoftin valuuttakurssit
cd ${POLKU};php hae_valuutat_cron.php

# Siivotaan dataout dirikasta vanhat failit pois
touch ${POLKU}/dataout
find ${POLKU}/dataout -mtime +30 -not -path '*/.svn*' -delete
