#!/usr/bin/env bash
set -euo pipefail
cd /home2/nykvymmy/dev.ehpmi.org
test "$(pwd -P)" = /home2/nykvymmy/dev.ehpmi.org
test "$(wp config get DB_NAME 2>/dev/null)" = nykvymmy_ehpmidev
test "$(wp option get home 2>/dev/null)" = https://dev.ehpmi.org
if wp plugin is-active real-media-library-lite 2>/dev/null; then exit 42; fi
test "$(realpath /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite)" = /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite
test "$(find /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite -type f | wc -l | tr -d ' ')" = 296
test "$(du -sk /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite | cut -f 1)" = 6584
test -z "$(find /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite -type l -print)"
test -z "$(find /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite -type d -name uploads -print)"
test "$(sha256sum /home2/nykvymmy/backups/entity-stage8-2026-10-18/rml-plugin-before.tar.gz | cut -d ' ' -f 1)" = e7c0ebd3d77205987a2d4256fa36dbdf308efe75ecc7efbcdcc14f0b211531ca
test "$(sha256sum /home2/nykvymmy/backups/entity-stage8-2026-10-18/20261018T052553Z_nykvymmy_ehpmidev_pre-stage8.sql.gz | cut -d ' ' -f 1)" = 2b8c7ee05d8383ee728f6d5470f796cdf9485adf1b65bb286ed60e6780508307
gzip -t /home2/nykvymmy/backups/entity-stage8-2026-10-18/rml-plugin-before.tar.gz
gzip -t /home2/nykvymmy/backups/entity-stage8-2026-10-18/20261018T052553Z_nykvymmy_ehpmidev_pre-stage8.sql.gz
test ! -e /home2/nykvymmy/backups/entity-stage8-2026-10-18/rml-file-deletion.json
sha256sum /home2/nykvymmy/public_html/wp-content/plugins/real-media-library-lite/index.php > /home2/nykvymmy/backups/entity-stage8-2026-10-18/production-rml-file-before.txt
rm -rf /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite
test ! -e /home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite
sha256sum -c /home2/nykvymmy/backups/entity-stage8-2026-10-18/production-rml-file-before.txt
php -r 'echo json_encode(array("status"=>"PASS","deleted_utc"=>gmdate("c"),"operator_authorization"=>"Удалить RML plugin files на dev","path"=>"/home2/nykvymmy/dev.ehpmi.org/wp-content/plugins/real-media-library-lite","files_removed"=>296,"disk_kib_before"=>6584,"target_absent"=>true,"production_plugin_control"=>"UNCHANGED","uploads_deleted"=>0,"rml_tables_deleted"=>0,"recovery_archive"=>"/home2/nykvymmy/backups/entity-stage8-2026-10-18/rml-plugin-before.tar.gz"),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),"\n";' | tee /home2/nykvymmy/backups/entity-stage8-2026-10-18/rml-file-deletion.json
