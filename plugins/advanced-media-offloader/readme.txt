=== Advanced Media Offloader ===
Contributors: masoudin, bahreynipour, wpfitter, teledark
Tags: s3, media library, cloudflare, offload
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 4.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Offload WordPress media to cloud storage (Amazon S3, Cloudflare R2, DigitalOcean, MinIO, Wasabi) to reduce server load & improve site performance.
== Description ==

**Advanced Media Offloader** helps you optimize your WordPress media handling by automatically uploading your media files to S3-compatible cloud storage services.

Struggling with server space limitations? Want to improve your site's performance by serving media through a CDN? This plugin handles the technical work of migrating your media to the cloud, rewriting URLs, and maintaining compatibility with your existing content.

= Key Benefits =

* Reduce server storage requirements and costs
* Decrease server load when serving media files
* Improve global site loading speeds when combined with CDN services
* Maintain full compatibility with WordPress media functions
* No need to modify existing content - URLs are automatically rewritten

= Supported Cloud Providers =

* **Amazon S3** - The industry standard object storage service
* **Cloudflare R2** - S3-compatible storage with zero egress fees
* **DigitalOcean Spaces** - Simple object storage from DigitalOcean
* **MinIO** - Self-hosted, S3-compatible object storage
* **Wasabi** - Hot cloud storage with predictable pricing

== Features ==

* **Automatic Offloading** - New media uploads are automatically sent to your cloud storage
* **Bulk Migration** - Easily move existing media to the cloud (50 files per batch)
* **WP CLI Support** - Powerful command-line interface for bulk operations and automation ([Learn more](https://wpfitter.com/blog/advmo-bulk-offload-with-wp-cli))
* **Smart URL Rewriting** - All media URLs are automatically rewritten to serve from cloud storage
* **File Versioning** - Add unique timestamps to media paths to prevent caching issues
* **Flexible Retention** - Choose to keep local copies or remove them after successful offloading
* **Mirror Deletion** - Optionally remove files from cloud storage when deleted from WordPress
* **Custom Paths** - Configure custom path prefixes in your cloud storage
* **Developer-Friendly** - Action hooks for extending functionality


== Installation ==

1. Upload the plugin files to `/wp-content/plugins/advanced-media-offloader/` or install directly through WordPress
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to "Media Offloader" in the admin menu to configure settings
4. Add your cloud provider credentials to `wp-config.php` (see configuration examples below)
5. Test your connection and start offloading media

== Configuration ==

For security, cloud provider credentials are stored in your `wp-config.php` file rather than the database.

**[Cloudflare R2](https://developers.cloudflare.com/r2/) Configuration**
`
	define('ADVMO_CLOUDFLARE_R2_KEY', 'your-access-key');
	define('ADVMO_CLOUDFLARE_R2_SECRET', 'your-secret-key');
	define('ADVMO_CLOUDFLARE_R2_BUCKET', 'your-bucket-name');
    define('ADVMO_CLOUDFLARE_R2_DOMAIN', 'your-domain-url');
    define('ADVMO_CLOUDFLARE_R2_ENDPOINT', 'your-endpoint-url');
`

**[DigitalOcean Spaces](https://www.digitalocean.com/products/spaces) Configuration**
`
	define('ADVMO_DOS_KEY', 'your-access-key');
	define('ADVMO_DOS_SECRET', 'your-secret-key');
	define('ADVMO_DOS_BUCKET', 'your-bucket-name');
    define('ADVMO_DOS_DOMAIN', 'your-domain-url');
    define('ADVMO_DOS_ENDPOINT', 'your-endpoint-url');
`

**[MinIO](https://min.io/docs/minio/linux/administration/identity-access-management/minio-user-management.html) Configuration**
`
	define('ADVMO_MINIO_KEY', 'your-access-key');
	define('ADVMO_MINIO_SECRET', 'your-secret-key');
	define('ADVMO_MINIO_BUCKET', 'your-bucket-name');
    define('ADVMO_MINIO_DOMAIN', 'your-domain-url');
    define('ADVMO_MINIO_ENDPOINT', 'your-endpoint-url');
	define('ADVMO_MINIO_PATH_STYLE_ENDPOINT', false); // Optional. Set to true if your MinIO server requires path-style URLs (most self-hosted MinIO setups). Default is false.
	define('ADVMO_MINIO_REGION', 'your-bucket-region'); // Optional. Set your MinIO bucket region if needed. Default is 'us-east-1'.
`

**[Amazon S3](https://aws.amazon.com/s3/) Configuration**
`
	define('ADVMO_AWS_KEY', 'your-access-key');
	define('ADVMO_AWS_SECRET', 'your-secret-key');
	define('ADVMO_AWS_BUCKET', 'your-bucket-name');
    define('ADVMO_AWS_REGION', 'your-bukcet-region');
    define('ADVMO_AWS_DOMAIN', 'your-domain-url');
`

**[Wasabi](https://docs.wasabi.com/docs/creating-a-new-access-key) Configuration**
`
	define('ADVMO_WASABI_KEY', 'your-access-key');
	define('ADVMO_WASABI_SECRET', 'your-secret-key');
	define('ADVMO_WASABI_BUCKET', 'your-bucket-name');
    define('ADVMO_WASABI_REGION', 'your-bukcet-region');
    define('ADVMO_WASABI_DOMAIN', 'your-domain-url');
`

== Frequently Asked Questions ==

= Does this plugin support other cloud storage platforms? =

Currently supports Cloudflare R2, DigitalOcean Spaces, Amazon S3, Wasabi & MinIO. Additional providers are on the roadmap based on user demand.

= What happens to the media files already uploaded on my server? =

Existing files remain untouched until you explicitly use the bulk offload feature. New uploads are automatically processed based on your settings.

= How exactly does URL rewriting work? =

The plugin hooks into WordPress core media functions using `wp_get_attachment_url` and related filters. This ensures compatibility with themes, plugins, and core functions without modifying database URLs.

= Can I rollback if needed? =

Files offloaded with "Retain Local Files" can be served locally by deactivating the plugin. For full cloud migrations, you'll need to re-download media files if you want to revert.

= How are image sizes and thumbnails handled? =

All generated image sizes are offloaded alongside the original. URL rewriting works for all sizes and srcset attributes.

= Will this work with page builders and media-heavy plugins? =

Yes, since the plugin uses WordPress core hooks for URL rewriting, it's compatible with Elementor, Beaver Builder, WooCommerce, and most other plugins that use standard WordPress media functions.

= Does it support private files with access control? =

The free version only supports publicly accessible files. Private files with authenticated access may be added in a future premium version.

= What happens if I remove a media file from the WordPress Media Library? =

With "Mirror Delete" enabled, corresponding cloud files are automatically removed. Otherwise, files remain in cloud storage, potentially creating orphaned objects.

= How can I debug issues with file offloading? =

The plugin logs errors to attachment metadata. Check the Media Overview page for detailed error reporting or enable WordPress debug logging for more information.

= What's the recommended bucket configuration? =

For optimal performance:
1. Enable CORS configuration
2. Set appropriate public read permissions
3. Configure proper region (closest to your audience)
4. Consider using a CDN for global distributions

== Changelog ==
= 4.0.0 =
* Added: WP CLI command `wp advmo offload` for bulk operations and automation ([Learn more](https://wpfitter.com/blog/advmo-bulk-offload-with-wp-cli))
* Added: Individual "Offload Now" button in attachment edit screen for on-demand offloading
* Added: Retry functionality for failed offloads with dedicated "Retry Offload" button
* Fixed: Admin notices from other plugins now properly disabled on Media Overview page
* Improved: Enhanced admin interface consistency across all plugin pages

= 3.3.5 =
* Fixed: Minor improvements and bug fixes

= 3.3.4 =
* Fixed: Minor improvements and bug fixes
* Updated: WordPress compatibility improvements

= 3.3.3 =
* Fixed: Use original file's directory for sized image deletion, resolving an issue where thumbnails in older uploads weren't being deleted properly
* Fixed: Corrected a bug in the mirror delete functionality ensuring cloud files are properly removed when local files are deleted
* Added: HTTPS protocol requirement notices for domain and endpoint URLs for improved security
* Refactored: Standardized plugin settings approach for better code maintainability
* Optimized: Improved bulk processing with direct SQL queries for better performance
* Fixed: Added proper nonce verification and capability checks to all AJAX endpoints for enhanced security
* Fixed: Preserved checkbox values during settings sanitization to prevent settings from being inadvertently reset

= 3.3.2 =
- Improved accessibility and consistency in admin interface
- Added RTL stylesheet and conditional loading for better localization support
- Fixed minor bugs and made improvements.

= 3.3.1 =
- Fixed minor bugs and made improvements.

= 3.3.0 =
* Added service container and dependency injection architecture
* Improved connection testing with better error handling
* Fixed bulk offload process to prevent stuck operations
* Enhanced UI with clearer cloud provider selection
* Improved documentation with detailed provider setup instructions

= 3.2.0 =
- Added support for Wasabi cloud storage
- Enhanced plugin performance and stability
- Fix minor bugs

= 3.1.0 =
- Fixed and optimized connection test button functionality
- Fixed minor bugs and made improvements.

= 3.0.0 =
- Introduced a new user interface (UI) and improved user experience (UX) for the settings page.
- Added functionality to offload and sync edited images with cloud storage.
- Improved bulk offloading to cloud storage by fixing various bugs.
- Implemented error logging for bulk offload operations.
- Added ability to download a CSV file with detailed logs for attachments that encountered errors during offloading.
- Enhanced overall security of the plugin.
- Fixed various issues related to bulk offload JavaScript functionality.
- Improved error handling and notifications for media attachments in the library.
- Refactored attachment deletion methods for better performance and reliability.

= 2.1.0 =
- Implemented php-scoper to isolate AWS PHP SDK namespaces, preventing conflicts with other plugins using different versions of the same packages.
- Fixed minor bugs and made improvements.

= 2.0.3 =
- Fixed minor bugs and made improvements.

= 2.0.2 =
- Display offloaded version of images in post content when already offloaded to improve loading times and reduce bandwidth usage.
- Fixed the srcset attribute not displaying for images when object versioning is enabled.

= 2.0.1 =
- Fixed minor bugs and made improvements.

= 2.0.0 =
- Refactored the Advanced Media Offloader codebase.
- Added new action hooks for custom actions before and after critical operations.
- Fixed compatibility issue with the Performance Lab WordPress plugin.
- Fixed a bug in bulk offloading media files.
- Added support for MinIO path-style endpoint configuration using the ADVMO_MINIO_PATH_STYLE_ENDPOINT constant.
- Fixed minor bugs and made improvements.

= 1.6.0 =
- Refactored the code base to improve maintainability and readability, resulting in enhanced performance across the plugin.
- Resolved an issue where the bulk offload process would become unresponsive
- Added a button to cancel the bulk offload process, providing users with greater control during file transfers.

= 1.5.2 =
- Fix a minor bug related to the path of existing media files when deleting local files.

= 1.5.1 =
- Fix minor bugs to improve bulk offload process

= 1.5.0 =
- Added support for Amazon S3 cloud storage
- Enhanced plugin performance and stability
- Fix minor bugs

= 1.4.5 =
- Fix minor bugs with Min.io

= 1.4.4 =
- New Feature: Custom Path Prefix for Cloud Storage
- Fix minor bugs

= 1.4.3 = 
- Add Version to Bucket Path: Automatically add unique timestamps to your media file paths to ensure the latest versions are always delivered
- Add Mirror Delete: Automatically delete local files after successful upload to Cloud Storage.
- Improve Settings UI: Enhanced the user interface of the settings page.

= 1.4.2 =
- Added 'Sync Local and Cloud Deletions' feature to automatically remove media from cloud storage when deleted locally.
- Enhanced WooCommerce compatibility: Added support for WooCommerce-specific image sizes and optimized handling of product images.

= 1.4.1 =
- Fix minor bugs related to Bulk offloading the existing media files

= 1.4.0 =
- Added bulk offload feature for media files (50 per batch in free version)
- Fixed subdir path issue for non-image files
- UI Improvements
- Fixed minor bugs

= 1.3.0 =
- UI Improvements
- Fixed minor bugs

= 1.2.0 =
- Added MinIO as a new cloud storage provider
- Introduced an option to choose if local files should be deleted after offloading to cloud storage
- Implemented UI improvements for the plugin settings page
- Added Offload status to Attachment details section in Media Library
- Fixed minor bugs

= 1.1.0 =
- Improved the code base to fix some issues
- Added support for DigitalOcean Spaces

= 1.0.0 =
- Initial release.

== Upgrade Notice ==
= 4.0.0 =
This update fixes admin notices display issues on the Media Overview page for a cleaner admin experience.

= 1.0.0 =
Initial release. Please provide feedback and report any issues through the support forum.

== Using the S3 PHP SDK ==

The Advanced Media Offloader utilizes the AWS SDK for PHP to interact with S3-compatible cloud storage. This powerful SDK provides an easy-to-use API for managing your cloud storage operations, including file uploads, downloads, and more. The SDK is maintained by Amazon Web Services, ensuring high compatibility and performance with S3 services.

For more information about the AWS SDK for PHP, visit:
[https://aws.amazon.com/sdk-for-php/](https://aws.amazon.com/sdk-for-php/)

== Screenshots ==

1. Plugin settings page - Configure your cloud storage settings and offload options.
2. Media Overview page - Media Overview and Bulk Offload
3. Attachment details page - View the offload status of individual media files.