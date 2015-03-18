**Caution! Not for use with live sites.**

Magento-Stage-Media
===================

Instead of duplicating the "media" directory of an installation for development,
Stage Media only downloads public files as they are needed.
This saves space and time.

Stage Media will attempt to auto-configure on installation and use the base media URL as the source.
For better control see "System > Configuration > System > Storage Configuration for Media > Remote URL" in admin.

Stage Media is potentially slow and should only be used on private servers.
Currently only these files are handled automatically:

- Incoming requests which are redirected to `get.php` and are allowed by `var/resource_config.json`. See [how to add more folders](http://stackoverflow.com/q/19166555/471559).
- Product images as they are being resized through `Mage::helper('catalog/image')->init()`
