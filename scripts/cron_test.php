<?php
$date = date('Y-m-d H:i:s T');
file_put_contents(__DIR__ . '/cron_test.log', "Ejecutado a: $date\n", FILE_APPEND);
