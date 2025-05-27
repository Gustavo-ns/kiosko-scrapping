#!/bin/bash
cd "$(dirname "$0")"
/usr/bin/php update_all.php >> cron_update.log 2>&1 