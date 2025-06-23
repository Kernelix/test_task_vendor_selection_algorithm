<?php

echo 'JIT enabled: ' . (opcache_get_status()['jit']['enabled'] ? 'YES' : 'NO');
echo ' JIT buffer size: ' . opcache_get_status()['jit']['buffer_size'];