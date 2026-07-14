# Restock Notify — Changelog

## 1.0.0
- Initial port from `restock-notify` v1.2.0.
- Legacy class bodies bundled under `legacy/includes/`; assets at module root.
- Helper shims (`shopos_restock_option_defaults`, `shopos_restock_get_option`) re-declared in `legacy/helpers.php`.
- Table name (`{prefix}shopos_restock_subscribers`) and option keys preserved so existing data carries over automatically.
