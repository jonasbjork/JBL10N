<?php
/*
Plugin Name: jbL10N
Description: Cache Localization object (l10n)
Author: Jonas Bjork, jonas.bjork@aller.se
Version: 0.1
Author URI: http://aller.se
*/

// If you want debug output (as HTML-comments) set this to TRUE
define('JBL10N_DEBUG', FALSE);

class jbL10N {

	private $cachePath;
	private $debug;
	private $locale;

	function __construct() {

		if (defined('WP_ADMIN') && WP_ADMIN) {
			$this->cachePath = WP_CONTENT_DIR . '/cache/';
			$this->debug .= "\ncachePath: ".$this->cachePath."\n";
			$this->locale = get_option('WPLANG', 'unknown');
			$this->debug .= "WPLANG: {$this->locale}\n";
			// TODO: Check if cachePath is writeable
			add_filter('override_load_textdomain', array(&$this, 'loadCache'), 1, 3);
			add_action('admin_footer', array(&$this, 'addFooter'));
		}
	}

	private function _cacheFile($domain, $mofile) {
		global $wp_version;
		return sprintf("%sl10n-%s-%s-%s", $this->cachePath, $domain, $this->locale, sha1($mofile.$wp_version));
	}

	public function loadCache($override, $domain, $mofile) {
		global $l10n;
		
		$cacheFile = $this->_cacheFile($domain, $mofile);
		$this->debug .= "\ncacheFile: ".$cacheFile."\n";
		
		if (file_exists($cacheFile)) {
			$this->debug .= "Have cache for ".$cacheFile."\n";

			$o = unserialize(file_get_contents($cacheFile));

			if ($o instanceof MO) {
				$l10n[$domain] = $o;
				$this->debug .= "Valid object type for {$domain}\n";
				return TRUE;
			} else {
				$this->debug .= "Not valid object type for {$domain}\n";
				@unlink($cacheFile);
				return FALSE;
			}
		
		} else {
			// We have no cache
			$this->debug .= "No cache found for {$domain}\n";
			$mo = new MO();
			if ( !$mo->import_from_file( $mofile ) ) {
				$this->debug .= "Could not find mofile: {$mofile}\n";
				return FALSE;
			}
			if ( isset( $l10n[$domain] ) ) {
				$mo->merge_with( $l10n[$domain] );
			}
			$l10n[$domain] = &$mo;
			$this->debug .= "storing cache for {$domain} {$mofile}\n";
			$o = $l10n[$domain];
			if (!empty($o)) {
				file_put_contents($cacheFile, serialize($o));
				return TRUE;
			} else {
				return FALSE;
			}
		}

	}
	
	public function addFooter() {
		if (JBL10N_DEBUG) print "\n<!-- jbL10N:\n {$this->debug} \n -->\n";
	}

}

new jbL10N();
