#!/bin/sh
cd "$(dirname "$0")" || exit 1
php newsletter_cron_worker.php
