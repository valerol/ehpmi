# EHPMI WordPress project

This repository preserves the unique, reproducible part of the EHPMI WordPress site while retaining the history of the earlier static prototype.

## Source of truth

- WordPress theme: `wp-content/themes/ehpmi/`.
- Domain and recovery protocol: `docs/EHPMI_DOMAIN_PROTOCOL.md`.
- Dependency and release metadata: `ops/`.
- Database and dynamic media: verified Google Drive backup packages, not Git.

The current baseline was captured from `https://dev.ehpmi.org`. Production files are runtime state and are not the development source of truth.

## Not stored in Git

- WordPress core;
- ordinary WordPress.org plugins;
- hosting-specific credentials and `wp-config.php`;
- `wp-content/uploads/`, root `files/` and root `images/`;
- database dumps and backup archives;
- hosting-specific `wp-content/mu-plugins/sso.php`.

Plugins are reinstalled in the versions recorded in `ops/plugins.yml`. Dynamic content is restored from the Drive package identified by the matching baseline manifest and `SHA256SUMS`.

## Safety boundary

Development is performed on `dev.ehpmi.org` with its separate database. Production deployment requires its own approval, fresh database snapshot, accepted Git release, rollback plan, and completed dev QA.

See `docs/EHPMI_DOMAIN_PROTOCOL.md` for the complete restoration and migration procedure.

