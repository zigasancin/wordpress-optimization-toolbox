<?php
/**
 * Lazy load images class: Lazy
 *
 * @since 3.2.0
 * @package Smush\Core\Modules
 */

namespace Smush\Core\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Lazy
 */
class Lazy extends Abstract_Module {

	/**
	 * Lazy loading settings.
	 *
	 * @since 3.2.0
	 * @var array $settings
	 */
	private $options;

	/**
	 * Page parser.
	 *
	 * @since 3.2.2
	 * @var Helpers\Parser $parser
	 */
	protected $parser;

	/**
	 * Lazy constructor.
	 *
	 * @since 3.2.2
	 * @param Helpers\Parser $parser  Page parser instance.
	 */
	public function __construct( Helpers\Parser $parser ) {
		$this->parser = $parser;
		parent::__construct();
	}

	/**
	 * Initialize module actions.
	 *
	 * @since 3.2.0
	 */
	public function init() {
		// Only run on front end and if lazy loading is enabled.
		if ( is_admin() || ! $this->settings->get( 'lazy_load' ) ) {
			return;
		}

		$this->options = $this->settings->get_setting( WP_SMUSH_PREFIX . 'lazy_load' );

		// Enabled without settings? Don't think so... Exit.
		if ( ! $this->options ) {
			return;
		}

		// Skip AMP pages.
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			return;
		}

		// Load js file that is required in public facing pages.
		add_action( 'wp_head', array( $this, 'add_inline_styles' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Allow lazy load attributes in img tag.
		add_filter( 'wp_kses_allowed_html', array( $this, 'add_lazy_load_attributes' ) );

		$this->parser->enable( 'lazy_load' );
		add_filter( 'wp_smush_should_skip_parse', array( $this, 'maybe_skip_parse' ), 10 );

		// Filter images.
		if ( ! isset( $this->options['output']['content'] ) || ! $this->options['output']['content'] ) {
			add_filter( 'the_content', array( $this, 'exclude_from_lazy_loading' ), 100 );
		}
		if ( ! isset( $this->options['output']['thumbnails'] ) || ! $this->options['output']['thumbnails'] ) {
			add_filter( 'post_thumbnail_html', array( $this, 'exclude_from_lazy_loading' ), 100 );
		}
		if ( ! isset( $this->options['output']['gravatars'] ) || ! $this->options['output']['gravatars'] ) {
			add_filter( 'get_avatar', array( $this, 'exclude_from_lazy_loading' ), 100 );
		}
		if ( ! isset( $this->options['output']['widgets'] ) || ! $this->options['output']['widgets'] ) {
			add_action( 'dynamic_sidebar_before', array( $this, 'filter_sidebar_content_start' ), 0 );
			add_action( 'dynamic_sidebar_after', array( $this, 'filter_sidebar_content_end' ), 1000 );
		}
	}

	/**
	 * Add inline styles at the top of the page for pre-loaders and effects.
	 *
	 * @since 3.2.0
	 */
	public function add_inline_styles() {
		if ( ! $this->options['animation']['selected'] ) {
			return;
		}

		// Spinner.
		if ( 'spinner' === $this->options['animation']['selected'] ) {
			$loader = WP_SMUSH_URL . 'app/assets/images/smush-lazyloader-' . $this->options['animation']['spinner']['selected'] . '.gif';
			if ( isset( $this->options['animation']['spinner']['selected'] ) && 5 < (int) $this->options['animation']['spinner']['selected'] ) {
				$loader = wp_get_attachment_image_src( $this->options['animation']['spinner']['selected'], 'full' );
				$loader = $loader[0];
			}
			$background = 'rgba(255, 255, 255, 0)';
		} else {
			// Placeholder.
			$loader     = WP_SMUSH_URL . 'app/assets/images/smush-placeholder.png';
			$background = '#FAFAFA';
			if ( isset( $this->options['animation']['placeholder']['selected'] ) && 2 === (int) $this->options['animation']['placeholder']['selected'] ) {
				$background = '#333333';
			}
			if ( isset( $this->options['animation']['placeholder']['selected'] ) && 2 < (int) $this->options['animation']['placeholder']['selected'] ) {
				$loader = wp_get_attachment_image_src( $this->options['animation']['placeholder']['selected'], 'full' );
				$loader = $loader[0];

				if ( isset( $this->options['animation']['placeholder']['color'] ) ) {
					$background = $this->options['animation']['placeholder']['color'];
				}
			}
		}

		// Fade in.
		$fadein = isset( $this->options['animation']['fadein']['duration'] ) ? $this->options['animation']['fadein']['duration'] : 0;
		$delay  = isset( $this->options['animation']['fadein']['delay'] ) ? $this->options['animation']['fadein']['delay'] : 0;
		?>
		<style>
			.no-js img.lazyload { display: none; }
			figure.wp-block-image img.lazyloading { min-width: 150px; }
			<?php if ( 'fadein' === $this->options['animation']['selected'] ) : ?>
				.lazyload, .lazyloading { opacity: 0; }
				.lazyloaded {
					opacity: 1;
					transition: opacity <?php echo esc_html( $fadein ); ?>ms;
					transition-delay: <?php echo esc_html( $delay ); ?>ms;
				}
			<?php else : ?>
				.lazyload { opacity: 0; }
				.lazyloading {
					border: 0 !important;
					opacity: 1;
					background: <?php echo esc_attr( $background ); ?> url('<?php echo esc_url( $loader ); ?>') no-repeat center !important;
					background-size: 16px auto !important;
				}
			<?php endif; ?>
		</style>
		<?php
	}

	/**
	 * Enqueue JS files required in public pages.
	 *
	 * @since 3.2.0
	 */
	public function enqueue_assets() {
		$in_footer = isset( $this->options['footer'] ) ? $this->options['footer'] : true;

		wp_enqueue_script(
			'smush-lazy-load',
			WP_SMUSH_URL . 'app/assets/js/smush-lazy-load.min.js',
			array(),
			WP_SMUSH_VERSION,
			$in_footer
		);

		$custom = "window.lazySizesConfig = window.lazySizesConfig || {};

window.lazySizesConfig.lazyClass    = 'lazyload';
window.lazySizesConfig.loadingClass = 'lazyloading';
window.lazySizesConfig.loadedClass  = 'lazyloaded';

lazySizesConfig.loadMode = 1;"; // Page is optimized for fast onload event.

		wp_add_inline_script( 'smush-lazy-load', $custom, 'before' );

		wp_add_inline_script( 'smush-lazy-load', 'lazySizes.init();' );
	}

	/**
	 * Make sure WordPress does not filter out img elements with lazy load attributes.
	 *
	 * @since 3.2.0
	 *
	 * @param array $allowedposttags  Allowed post tags.
	 *
	 * @return mixed
	 */
	public function add_lazy_load_attributes( $allowedposttags ) {
		if ( ! isset( $allowedposttags['img'] ) ) {
			return $allowedposttags;
		}

		$smush_attributes = array(
			'data-src'    => true,
			'data-srcset' => true,
		);

		$img_attributes = array_merge( $allowedposttags['img'], $smush_attributes );

		$allowedposttags['img'] = $img_attributes;

		return $allowedposttags;
	}

	/**
	 * Check if we need to skip parsing of this page.
	 *
	 * @since 3.2.2
	 * @param bool $skip  Skip parsing.
	 *
	 * @return bool
	 */
	public function maybe_skip_parse( $skip ) {
		// Don't lazy load for feeds, previews.
		if ( is_feed() || is_preview() ) {
			$skip = true;
		}

		if ( ! $this->is_allowed_post_type() || $this->is_exluded_uri() ) {
			$skip = true;
		}

		return $skip;
	}

	/**
	 * Parse image for Lazy load.
	 *
	 * @since 3.2.2
	 *
	 * @param string $src    Image URL.
	 * @param string $image  Image tag (<img>).
	 *
	 * @return string
	 */
	public function parse_image( $src, $image ) {
		/**
		 * Filter to skip a single image from lazy load.
		 *
		 * @since 3.3.0 Added $image param.
		 *
		 * @param bool   $skip   Should skip? Default: false.
		 * @param string $src    Image url.
		 * @param string $image  Image.
		 */
		if ( apply_filters( 'smush_skip_image_from_lazy_load', false, $src, $image ) ) {
			return $image;
		}

		// Avoid conflicts if attributes are set (another plugin, for example).
		if ( false !== strpos( $image, 'data-src' ) ) {
			return $image;
		}

		/**
		 * Check if some image formats are excluded.
		 */
		if ( in_array( false, $this->options['format'], true ) ) {
			$ext = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
			$ext = 'jpg' === $ext ? 'jpeg' : $ext;

			if ( isset( $this->options['format'][ $ext ] ) && ! $this->options['format'][ $ext ] ) {
				return $image;
			}
		}

		if ( $this->has_excluded_class_or_id( $image ) ) {
			return $image;
		}

		$new_image = $image;

		$src = Helpers\Parser::get_attribute( $new_image, 'src' );
		Helpers\Parser::remove_attribute( $new_image, 'src' );
		Helpers\Parser::add_attribute( $new_image, 'data-src', $src );

		// Change srcset to data-srcset attribute.
		$new_image = preg_replace( '/<img(.*?)(srcset=)(.*?)>/i', '<img$1data-$2$3>', $new_image );

		// Add .lazyload class.
		$class = Helpers\Parser::get_attribute( $new_image, 'class' );
		if ( $class ) {
			$class .= ' lazyload';
		} else {
			$class = 'lazyload';
		}
		Helpers\Parser::remove_attribute( $new_image, 'class' );
		Helpers\Parser::add_attribute( $new_image, 'class', apply_filters( 'wp_smush_lazy_load_classes', $class ) );

		Helpers\Parser::add_attribute( $new_image, 'src', 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' );

		// Use noscript element in HTML to load elements normally when JavaScript is disabled in browser.
		$new_image .= '<noscript>' . $image . '</noscript>';

		return $new_image;
	}

	/**
	 * Get images from content and add exclusion class.
	 *
	 * @since 3.2.2
	 *
	 * @param string $content  Page/block content.
	 *
	 * @return string
	 */
	public function exclude_from_lazy_loading( $content ) {
		$images = Helpers\Parser::get_images_from_content( $content );

		if ( empty( $images ) ) {
			return $content;
		}

		foreach ( $images[0] as $key => $image ) {
			$new_image = $image;

			// Add .no-lazyload class.
			$class = Helpers\Parser::get_attribute( $new_image, 'class' );
			if ( $class ) {
				Helpers\Parser::remove_attribute( $new_image, 'class' );
				$class .= ' no-lazyload';
			} else {
				$class = 'no-lazyload';
			}
			Helpers\Parser::add_attribute( $new_image, 'class', $class );

			$content = str_replace( $image, $new_image, $content );
		}

		return $content;
	}

	/**
	 * Check if this is part of the allowed post type.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	private function is_allowed_post_type() {
		// If not settings are set, probably, all are disabled.
		if ( ! is_array( $this->options['include'] ) ) {
			return false;
		}

		// Static home page is selected (is_home() is false, is_front_page() is true).
		if ( is_front_page() ) {
			return isset( $this->options['include']['frontpage'] ) && $this->options['include']['frontpage'];
		}

		// Latest posts selected as homepage (both is_home() and is_front_page() will return true).
		if ( is_home() ) {
			return isset( $this->options['include']['home'] ) && $this->options['include']['home'];
		}

		if ( is_page() && isset( $this->options['include']['page'] ) && $this->options['include']['page'] ) {
			return true;
		} elseif ( is_single() && isset( $this->options['include']['single'] ) && $this->options['include']['single'] ) {
			return true;
		} elseif ( is_category() && isset( $this->options['include']['category'] ) && ! $this->options['include']['category'] ) {
			return false; // Show false, because a category is also an archive.
		} elseif ( is_tag() && isset( $this->options['include']['tag'] ) && ! $this->options['include']['tag'] ) {
			return false;
		} elseif ( is_archive() && isset( $this->options['include']['archive'] ) && $this->options['include']['archive'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the page has been added to Post, Pages & URLs filter in lazy loading settings.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	private function is_exluded_uri() {
		// No exclusion rules defined.
		if ( ! isset( $this->options['exclude-pages'] ) || empty( $this->options['exclude-pages'] ) ) {
			return false;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Remove empty values.
		$uri_pattern = array_filter( $this->options['exclude-pages'] );
		$uri_pattern = implode( '|', $uri_pattern );

		if ( preg_match( "#{$uri_pattern}#i", $request_uri ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the image has a defined class or ID.
	 *
	 * @since 3.2.0
	 *
	 * @param string $image  Image.
	 *
	 * @return bool
	 */
	private function has_excluded_class_or_id( $image ) {
		$image_classes = Helpers\Parser::get_attribute( $image, 'class' );
		$image_classes = explode( ' ', $image_classes );
		$image_id      = '#' . Helpers\Parser::get_attribute( $image, 'id' );

		if ( in_array( $image_id, $this->options['exclude-classes'], true ) ) {
			return true;
		}

		foreach ( $image_classes as $class ) {
			// Skip Revolution Slider images.
			if ( 'rev-slidebg' === $class ) {
				return true;
			}

			// Internal class to skip images.
			if ( 'no-lazyload' === $class ) {
				return true;
			}

			if ( in_array( ".{$class}", $this->options['exclude-classes'], true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Buffer sidebar content.
	 *
	 * @since 3.2.0
	 */
	public function filter_sidebar_content_start() {
		ob_start();
	}

	/**
	 * Process buffered content.
	 *
	 * @since 3.2.0
	 */
	public function filter_sidebar_content_end() {
		$content = ob_get_clean();

		echo $this->exclude_from_lazy_loading( $content );

		unset( $content );
	}

}
