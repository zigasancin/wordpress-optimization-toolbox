<?php
/* * * * * * * * * * * * * * * * * * * * *
*
*  ██████╗ ███╗   ███╗ ██████╗ ███████╗
* ██╔═══██╗████╗ ████║██╔════╝ ██╔════╝
* ██║   ██║██╔████╔██║██║  ███╗█████╗
* ██║   ██║██║╚██╔╝██║██║   ██║██╔══╝
* ╚██████╔╝██║ ╚═╝ ██║╚██████╔╝██║
*  ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚═╝
*
* @package  : OMGF
* @author   : Daan van den Bergh
* @copyright: © 2024 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF;

use OMGF\Helper as OMGF;
use OMGF\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class StylesheetGenerator {
	/** @var $fonts */
	private $fonts;

	/** @var string $plugin */
	private $plugin;

	/**
	 * OMGF_GenerateStylesheet constructor.
	 */
	public function __construct(
		$fonts,
		string $plugin
	) {
		$this->fonts  = $fonts;
		$this->plugin = $plugin;
	}

	/**
	 * Generate a stylesheet based on the provided $fonts.
	 *
	 * @return string
	 */
	public function generate() {
		$font_display = OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION );
		$stylesheet   = "/**\n * Auto Generated by $this->plugin\n * @author: Daan van den Bergh\n * @url: https://daan.dev\n */\n\n";
		$n            = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ? "\n" : '';

		foreach ( $this->fonts as $font ) {
			if ( empty( $font->variants ) ) {
				continue; // @codeCoverageIgnore
			}

			foreach ( $font->variants as $variant ) {
				/**
				 * Filter $variant to allow custom modifications of e.g. unicode range, etc.
				 *
				 * @filter omgf_generate_stylesheet_font_variant
				 * @since  v5.6.0
				 */
				$variant = apply_filters( 'omgf_generate_stylesheet_font_variant', $variant );
				/**
				 * Filter font_family name.
				 *
				 * @since v4.5.1
				 */
				$font_family = apply_filters( 'omgf_generate_stylesheet_font_family', rawurldecode( $variant->fontFamily ) );
				$font_style  = $variant->fontStyle;
				$font_weight = $variant->fontWeight;
				$stylesheet  .= "@font-face{{$n}";
				$stylesheet  .= "font-family:'$font_family';$n";
				$stylesheet  .= "font-style:$font_style;$n";
				$stylesheet  .= "font-weight:$font_weight;$n";
				$stylesheet  .= "font-display:$font_display;$n";
				$stylesheet  .= 'src:' . $this->build_source_string( [ 'woff2' => $variant->woff2 ] );

				if ( isset( $variant->range ) ) {
					$stylesheet .= "unicode-range:$variant->range;$n";
				}

				$stylesheet .= "}$n";
			}
		}

		return apply_filters( 'omgf_generate_stylesheet_after', $stylesheet, $this->fonts );
	}

	/**
	 * @param        $sources
	 * @param string $type
	 * @param bool   $end_semi_colon
	 *
	 * @return string
	 */
	private function build_source_string( $sources, $type = 'url', $end_semi_colon = true ) {
		$last_src = end( $sources );
		$source   = '';
		$n        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ? "\n" : '';

		foreach ( $sources as $format => $url ) {
			$source .= "$type('$url')" . ( ! is_numeric( $format ) ? "format('$format')" : '' );

			if ( $url === $last_src && $end_semi_colon ) {
				$source .= ";$n";
			} else {
				$source .= ",$n";
			}
		}

		return $source;
	}
}