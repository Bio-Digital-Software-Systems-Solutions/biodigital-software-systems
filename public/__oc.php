<?php
header('Content-Type: text/plain');
$s = opcache_get_status(false);
if (!$s) { echo "OPcache DISABLED in FPM\n"; exit; }
$st = $s['opcache_statistics'];
printf("enabled        : %s\n", $s['opcache_enabled'] ? 'yes' : 'no');
printf("cache_full     : %s\n", $s['cache_full'] ? 'YES (memory exhausted!)' : 'no');
printf("scripts cached : %d / %s\n", $st['num_cached_scripts'], ini_get('opcache.max_accelerated_files'));
printf("hits=%d misses=%d hit_rate=%.1f%%\n", $st['hits'], $st['misses'], $st['opcache_hit_rate']);
printf("oom_restarts   : %d\n", $st['oom_restarts']);
printf("mem used=%.0fMB free=%.0fMB wasted=%.0fMB\n", $s['memory_usage']['used_memory']/1048576, $s['memory_usage']['free_memory']/1048576, $s['memory_usage']['wasted_memory']/1048576);
printf("validate_timestamps=%s revalidate_freq=%s\n", ini_get('opcache.validate_timestamps'), ini_get('opcache.revalidate_freq'));
