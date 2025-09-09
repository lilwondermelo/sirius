<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache has been cleared!";
} else {
    echo "OPcache is not enabled or the function opcache_reset() is not available.";
}
?>