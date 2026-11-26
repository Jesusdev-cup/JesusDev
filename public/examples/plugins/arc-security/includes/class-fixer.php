<?php
/**
 * One-click fixes for filesystem-level items the scanner flags.
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Fixer {

	/**
	 * SEC-0800: no PHP execution inside uploads/ (Apache 2.4 + 2.2 syntax).
	 *
	 * @return true|WP_Error
	 */
	public static function protect_uploads() {
		$path = ARC_SEC_Scanner::uploads_htaccess_path();
		if ( file_exists( $path ) && false !== strpos( (string) file_get_contents( $path ), 'Require all denied' ) ) {
			return true;
		}
		$rules  = "# ARC Security - SEC-0800\n";
		$rules .= "<FilesMatch \"\\.(php|phtml|php[3-8]?|phps|phar|cgi|pl|py|sh)$\">\n";
		$rules .= "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n";
		$rules .= "<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
		$rules .= "</FilesMatch>\n";
		return false === file_put_contents( $path, $rules ) ? new WP_Error( 'arc_sec_write', 'Could not write ' . $path ) : true;
	}

	/**
	 * SEC-0801/0802/0803/0782: marker block in the root .htaccess — directory
	 * indexing off, dotfiles/env/composer/package/lock/docs denied, xmlrpc.php
	 * closed at server level too.
	 *
	 * @return true|WP_Error
	 */
	public static function harden_root_htaccess() {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		$path  = ABSPATH . '.htaccess';
		$rules = array(
			'Options -Indexes',
			'<FilesMatch "^(\\..*|readme\\.html|license\\.txt|composer\\.(json|lock)|package(-lock)?\\.json|.*\\.(md|log|sql|bak|old|dist|swp|ini|sh))$">',
			'<IfModule mod_authz_core.c>',
			'Require all denied',
			'</IfModule>',
			'<IfModule !mod_authz_core.c>',
			'Deny from all',
			'</IfModule>',
			'</FilesMatch>',
			'<Files "xmlrpc.php">',
			'<IfModule mod_authz_core.c>',
			'Require all denied',
			'</IfModule>',
			'<IfModule !mod_authz_core.c>',
			'Deny from all',
			'</IfModule>',
			'</Files>',
		);
		return insert_with_markers( $path, 'ARC Security', $rules ) ? true : new WP_Error( 'arc_sec_write', 'Could not update ' . $path );
	}
}
