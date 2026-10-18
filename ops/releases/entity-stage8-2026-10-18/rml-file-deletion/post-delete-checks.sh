#!/usr/bin/env bash
set -euo pipefail
cd /home2/nykvymmy/dev.ehpmi.org
test "$(wp config get DB_NAME 2>/dev/null)" = nykvymmy_ehpmidev
test "$(wp option get home 2>/dev/null)" = https://dev.ehpmi.org
test ! -e /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite
wp plugin list --format=json > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-plugin-list.json 2>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-plugin-list.stderr
if grep -Ei 'PHP Warning:|Warning:|Fatal error:|^Error:' /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-plugin-list.stderr; then exit 43; fi
wp eval-file /tmp/post-delete-verify.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-stage8.json 2>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
wp eval-file /tmp/post-delete-native.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-media-rest.json 2>>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
wp eval-file /home2/nykvymmy/backups/entity-stage8-2026-10-18/verify-assets.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-native-assets.json 2>>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
wp eval-file /home2/nykvymmy/backups/entity-stage7-2026-10-18/widget-content-incident/verify-stage3.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-stage3.txt 2>>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
wp eval-file /home2/nykvymmy/backups/entity-stage7-2026-10-18/widget-content-incident/verify-stage4.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-stage4.txt 2>>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
wp eval-file /home2/nykvymmy/backups/entity-stage8-2026-10-18/retained-stage5.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-stage5.txt 2>>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
wp eval-file /home2/nykvymmy/backups/entity-stage8-2026-10-18/verify-stage6-current.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-stage6.json 2>>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
wp eval-file /home2/nykvymmy/backups/entity-stage8-2026-10-18/verify-stage7-current.php /home2/nykvymmy/backups/entity-stage8-2026-10-18/stage7-baseline.json > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-stage7.json 2>>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
wp eval-file /home2/nykvymmy/backups/entity-stage8-2026-10-18/inventory.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-dev.json 2>>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
wp --path=/home2/nykvymmy/public_html eval-file /home2/nykvymmy/backups/entity-stage8-2026-10-18/inventory.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-production.json 2>>/home2/nykvymmy/backups/entity-stage8-2026-10-18/post-delete-checks.stderr
sha256sum -c /home2/nykvymmy/backups/entity-stage8-2026-10-18/production-rml-file-before.txt
printf 'PASS: post-deletion plugin list, Stage8, native Media REST/data/assets, retained Stages3-7 and production controls captured\n'
