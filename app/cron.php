<?php

/*
 * Cron runner — all scheduled background jobs live here
 *
 * Usage:
 *   php app/cron.php <command>
 *
 * Add to server crontab (replace paths and schedule as needed):
 *   star/5 * * * * php /path/to/project/app/cron.php example-job
 *   (replace "star" with the * character)
 *
 * For local dev, just run manually:
 *   php app/cron.php example-job
 */

require_once __DIR__ . '/init.php';

$command = $argv[1] ?? '';

match ($command) {
    'example-job' => cron_example_job(),
    default       => print("Usage: php app/cron.php <command>\n\nAvailable commands:\n  example-job   A placeholder job — replace with your own\n"),
};


function cron_example_job(): void {
    echo "[" . date('Y-m-d H:i:s') . "] Running example job...\n";
    // TODO: replace with your actual cron logic
    echo "Done.\n";
}
