<?php
/**
 * Lazy load images class: WP_Smush_Lazy_Load
 *
 * @since 3.2.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_Lazy_Load
 */
class WP_Smush_Lazy_Load extends WP_Smush_Content {

	/**
	 * Lazy loading settings.
	 *
	 * @since 3.2.0
	 * @var array $settings
	 */
	private $options;

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

		// Load js file that is required in public facing pages.
		add_action( 'wp_head', array( $this, 'add_inline_styles' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Allow lazy load attributes in img tag.
		add_filter( 'wp_kses_allowed_html', array( $this, 'add_lazy_load_attributes' ) );

		// Filter images.
		if ( isset( $this->options['output']['content'] ) && $this->options['output']['content'] ) {
			add_filter( 'the_content', array( $this, 'set_lazy_load_attributes' ), 100 );
		}
		if ( isset( $this->options['output']['thumbnails'] ) && $this->options['output']['thumbnails'] ) {
			add_filter( 'post_thumbnail_html', array( $this, 'set_lazy_load_attributes' ), 100 );
		}
		if ( isset( $this->options['output']['gravatars'] ) && $this->options['output']['gravatars'] ) {
			add_filter( 'get_avatar', array( $this, 'set_lazy_load_attributes' ), 100 );
		}
		if ( isset( $this->options['output']['widgets'] ) && $this->options['output']['widgets'] ) {
			add_action( 'dynamic_sidebar_before', array( $this, 'filter_sidebar_content_start' ), 0 );
			add_action( 'dynamic_sidebar_after', array( $this, 'filter_sidebar_content_end' ), 1000 );
		}
	}

	/**
	 * Add inline styles at the top of the page for preloaders and effects.
	 *
	 * @since 3.2.0
	 */
	public function add_inline_styles() {
		if ( $this->options['animation']['disabled'] ) {
			return;
		}

		$loader = WP_SMUSH_URL . 'app/assets/images/icon-lazyloader.svg';
		$fadein = isset( $this->options['animation']['duration'] ) ? $this->options['animation']['duration'] : 0;
		$delay  = isset( $this->options['animation']['delay'] ) ? $this->options['animation']['delay'] : 0;
		?>
		<style>
			.no-js img.lazyload { display: none; }
			figure.wp-block-image img.lazyloading { min-width: 150px; }
			<?php if ( $this->options['animation']['spinner'] ) : ?>
				@-webkit-keyframes spin {
					0% {
						-webkit-transform: rotate(0deg);
						transform: rotate(0deg);
					}
					100% {
						-webkit-transform: rotate(360deg);
						transform: rotate(360deg);
					}
				}
				@keyframes spin {
					0% {
						-webkit-transform: rotate(0deg);
						transform: rotate(0deg);
					}
					100% {
						-webkit-transform: rotate(360deg);
						transform: rotate(360deg);
					}
				}
				.lazyload { opacity: 0; }
				.lazyloading {
					opacity: 1;
					background: #fff url('<?php echo esc_url( $loader ); ?>') no-repeat center;
					-webkit-animation: spin 1.3s linear infinite;
					animation: spin 1.3s linear infinite;
				}
			<?php else : ?>
				.lazyload, .lazyloading { opacity: 0; }
				.lazyloaded {
					opacity: 1;
					transition: opacity <?php echo esc_html( $fadein ); ?>ms;
					transition-delay: <?php echo esc_html( $delay ); ?>ms;
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
	 * Process images from content and add appropriate lazy load attributes.
	 *
	 * @since 3.2.0
	 *
	 * @param string $content  Page/block content.
	 *
	 * @return string
	 */
	public function set_lazy_load_attributes( $content ) {
		// Don't lazy load for feeds, previews.
		if ( is_feed() || is_preview() ) {
			return $content;
		}

		if ( ! $this->is_allowed_post_type() || $this->is_exluded_uri() ) {
			return $content;
		}

		// Avoid conflicts if attributes are set (another plugin, for example).
		if ( false !== strpos( $content, 'data-src' ) ) {
			return $content;
		}

		$images = $this->get_images_from_content( $content );

		if ( empty( $images ) ) {
			return $content;
		}

		foreach ( $images[0] as $key => $image ) {
			/**
			 * Check if some image formats are excluded.
			 */
			if ( in_array( false, $this->options['format'], true ) ) {
				$ext = strtolower( pathinfo( $images['img_url'][ $key ], PATHINFO_EXTENSION ) );
				$ext = 'jpg' === $ext ? 'jpeg' : $ext;

				if ( isset( $this->options['format'][ $ext ] ) && ! $this->options['format'][ $ext ] ) {
					continue;
				}
			}

			if ( $this->has_excluded_class_or_id( $image ) ) {
				return $content;
			}

			$new_image = $image;

			$this->remove_attribute( $new_image, 'src' );
			$this->add_attribute( $new_image, 'data-src', $images['img_url'][ $key ] );
			$this->add_attribute( $new_image, 'data-sizes', 'auto' );

			// Change srcset to data-srcset attribute.
			$new_image = preg_replace( '/<img(.*?)(srcset=)(.*?)>/i', '<img$1data-$2$3>', $new_image );

			// Add .lazyload class.
			$class = $this->get_attribute( $new_image, 'class' );
			if ( $class ) {
				$this->remove_attribute( $new_image, 'class' );
				$class .= ' lazyload';
			} else {
				$class = 'lazyload';
			}
			$this->add_attribute( $new_image, 'class', $class );

			$this->add_attribute( $new_image, 'src', 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' );

			// Use noscript element in HTML to load elements normally when JavaScript is disabled in browser.
			$new_image .= '<noscript>' . $image . '</noscript>';

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

		$blog_is_frontpage = ( 'posts' === get_option( 'show_on_front' ) && ! is_multisite() ) ? true : false;

		if ( is_front_page() && isset( $this->options['include']['frontpage'] ) && $this->options['include']['frontpage'] ) {
			return true;
		} elseif ( is_home() && isset( $this->options['include']['home'] ) && $this->options['include']['home'] && ! $blog_is_frontpage ) {
			return true;
		} elseif ( is_page() && isset( $this->options['include']['page'] ) && $this->options['include']['page'] ) {
			return true;
		} elseif ( is_single() && isset( $this->options['include']['single'] ) && $this->options['include']['single'] ) {
			return true;
		} elseif ( is_category() && isset( $this->options['include']['category'] ) && ! $this->options['include']['category'] ) {
			// Show false, because a category is also an archive.
			return false;
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

		$request_uri = filter_input( INPUT_ENV, 'REQUEST_URI', FILTER_SANITIZE_URL );

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
		$image_classes = $this->get_attribute( $image, 'class' );
		$image_classes = explode( ' ', $image_classes );
		$image_id      = '#' . $this->get_attribute( $image, 'id' );

		if ( in_array( $image_id, $this->options['exclude-classes'], true ) ) {
			return true;
		}

		foreach ( $image_classes as $class ) {
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

		echo $this->set_lazy_load_attributes( $content );

		unset( $content );
	}

}
