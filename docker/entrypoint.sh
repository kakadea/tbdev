#!/bin/sh
set -eu

cache_dir="${TBDEV_CACHE_DIR:-/var/lib/tbdev/runtime/cache}"
mkdir -p "$cache_dir"

if [ ! -f "$cache_dir/timezones.php" ] && [ -f /var/www/html/cache/timezones.php ]; then
  cp /var/www/html/cache/timezones.php "$cache_dir/timezones.php"
fi
if [ ! -f "$cache_dir/rep_cache.php" ] && [ -f /var/www/html/cache/rep_cache.php ]; then
  cp /var/www/html/cache/rep_cache.php "$cache_dir/rep_cache.php"
fi

if [ ! -f "$cache_dir/bans_cache.php" ]; then
  printf '%s\n' '<?php' '$bans = array();' '?>' > "$cache_dir/bans_cache.php"
fi
if [ ! -f "$cache_dir/rep_settings_cache.php" ]; then
  printf '%s\n' '<?php' '$GVARS = array(' '  '\''rep_is_online'\'' => 1,' '  '\''rep_default'\'' => 10,' '  '\''rep_undefined'\'' => '\''is off the scale'\'', '  '\''rep_userrates'\'' => 5,' '  '\''rep_adminpower'\'' => 5,' '  '\''rep_rdpower'\'' => 365,' '  '\''rep_pcpower'\'' => 1000,' '  '\''rep_kppower'\'' => 100,' '  '\''rep_minpost'\'' => 50,' '  '\''rep_minrep'\'' => 10,' '  '\''rep_maxperday'\'' => 10,' '  '\''rep_repeat'\'' => 20,' '  '\''g_rep_negative'\'' => true,' '  '\''g_rep_seeown'\'' => true' ');' '?>' > "$cache_dir/rep_settings_cache.php"
fi
if [ ! -f "$cache_dir/stats.php" ]; then
  printf '%s\n' '<?php' '$stats = '\''\'\'';' '?>' > "$cache_dir/stats.php"
fi

chown -R www-data:www-data "$cache_dir"
find "$cache_dir" -type f -name '*.php' -exec chmod 0660 {} \;

exec "$@"
