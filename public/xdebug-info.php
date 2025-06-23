<?php


echo 'Xdebug: ' . (extension_loaded('xdebug') ? 'ENABLED' : 'DISABLED');
echo '<br>Xdebug mode: ' . ini_get('xdebug.mode');