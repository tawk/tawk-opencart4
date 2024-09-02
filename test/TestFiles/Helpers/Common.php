<?php

namespace Tawk\Test\TestFiles\Helpers;

use Tawk\Test\TestFiles\Types\UrlConfig;

class Common {
	public static function get_env( string $env_var_name ): string {
		$env_var = getenv( $env_var_name );

		if ( false === $env_var ) {
			return '';
		}

		return $env_var;
	}

	public static function build_url( UrlConfig $url_config ) {
		$protocol = $url_config->https_flag ? 'https' : 'http';
		$host     = $url_config->host;
		$port     = $url_config->port;

		if ( true === empty( $port ) ) {
			return $protocol . '://' . $host . '/';
		}

		return $protocol . '://' . $host . ':' . $port . '/';
	}

	public static function build_selenium_url(
		UrlConfig $url_config,
		bool $is_hub = false
	): string {
		$url = self::build_url( $url_config );

		if ( false === $is_hub ) {
			return $url;
		}

		return $url . 'wd/hub';
	}
}