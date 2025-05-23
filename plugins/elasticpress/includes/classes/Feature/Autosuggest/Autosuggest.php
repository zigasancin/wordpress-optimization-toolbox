<?php
/**
 * Autosuggest feature
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
 *
 * @package elasticpress
 */

namespace ElasticPress\Feature\Autosuggest;

use ElasticPress\Elasticsearch;
use ElasticPress\Feature;
use ElasticPress\FeatureRequirementsStatus;
use ElasticPress\Features;
use ElasticPress\Indexables;
use ElasticPress\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Autosuggest feature class
 */
class Autosuggest extends Feature {

	/**
	 * Autosuggest query generated by intercept_search_request
	 *
	 * @var array
	 */
	public $autosuggest_query = [];

	/**
	 * Initialize feature setting it's config
	 *
	 * @since  3.0
	 */
	public function __construct() {
		$this->slug = 'autosuggest';

		$this->requires_install_reindex = true;

		$this->default_settings = [
			'endpoint_url'         => '',
			'autosuggest_selector' => '',
			'trigger_ga_event'     => '0',
		];

		$this->available_during_installation = true;

		$this->is_powered_by_epio = Utils\is_epio();

		parent::__construct();
	}

	/**
	 * Sets i18n strings.
	 *
	 * @return void
	 * @since 5.2.0
	 */
	public function set_i18n_strings(): void {
		$this->title = esc_html__( 'Autosuggest', 'elasticpress' );

		$this->short_title = esc_html__( 'Autosuggest', 'elasticpress' );

		$this->summary = '<p>' . __( 'Input fields of type "search" or with the CSS class "search-field" or "ep-autosuggest" will be enhanced with autosuggest functionality. As text is entered into the search field, suggested content will appear below it, based on top search results for the text. Suggestions link directly to the content.', 'elasticpress' ) . '</p>';

		$this->docs_url = __( 'https://www.elasticpress.io/documentation/article/configuring-elasticpress-via-the-plugin-dashboard/#autosuggest', 'elasticpress' );
	}

	/**
	 * Output feature box long
	 *
	 * @since 2.4
	 */
	public function output_feature_box_long() {
		?>
		<p><?php esc_html_e( 'Input fields of type "search" or with the CSS class "search-field" or "ep-autosuggest" will be enhanced with autosuggest functionality. As text is entered into the search field, suggested content will appear below it, based on top search results for the text. Suggestions link directly to the content.', 'elasticpress' ); ?></p>
		<?php
	}

	/**
	 * Setup feature functionality
	 *
	 * @since  2.4
	 */
	public function setup() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'ep_post_mapping', [ $this, 'mapping' ] );
		add_filter( 'ep_post_sync_args', [ $this, 'filter_term_suggest' ], 10 );
		add_filter( 'ep_post_fuzziness_arg', [ $this, 'set_fuzziness' ], 10, 3 );
		add_filter( 'ep_weighted_query_for_post_type', [ $this, 'adjust_fuzzy_fields' ], 10, 3 );
		add_filter( 'ep_saved_weighting_configuration', [ $this, 'epio_send_autosuggest_public_request' ] );
		add_filter( 'wp', [ $this, 'epio_send_autosuggest_allowed' ] );
		add_filter( 'ep_pre_sync_index', [ $this, 'epio_send_autosuggest_public_request' ] );
	}

	/**
	 * Display decaying settings on dashboard.
	 *
	 * @since 2.4
	 */
	public function output_feature_box_settings() {
		$settings = $this->get_settings();
		?>
		<div class="field">
			<div class="field-name status"><label for="feature_autosuggest_selector"><?php esc_html_e( 'Autosuggest Selector', 'elasticpress' ); ?></label></div>
			<div class="input-wrap">
				<input value="<?php echo empty( $settings['autosuggest_selector'] ) ? '.ep-autosuggest' : esc_attr( $settings['autosuggest_selector'] ); ?>" type="text" name="settings[autosuggest_selector]" id="feature_autosuggest_selector">
				<p class="field-description"><?php esc_html_e( 'Input additional selectors where you would like to include autosuggest separated by a comma. Example: .custom-selector, #custom-id, input[type="text"]', 'elasticpress' ); ?></p>
			</div>
		</div>

		<div class="field">
			<div class="field-name status"><?php esc_html_e( 'Google Analytics Events', 'elasticpress' ); ?></div>
			<div class="input-wrap">
				<label><input name="settings[trigger_ga_event]" <?php checked( (bool) $settings['trigger_ga_event'] ); ?> type="radio" value="1"><?php esc_html_e( 'Enabled', 'elasticpress' ); ?></label><br>
				<label><input name="settings[trigger_ga_event]" <?php checked( ! (bool) $settings['trigger_ga_event'] ); ?> type="radio" value="0"><?php esc_html_e( 'Disabled', 'elasticpress' ); ?></label>
				<p class="field-description"><?php esc_html_e( 'When enabled, a gtag tracking event is fired when an autosuggest result is clicked.', 'elasticpress' ); ?></p>
			</div>
		</div>
		<?php

		if ( Utils\is_epio() ) {
			$this->epio_allowed_parameters();
			return;
		}

		$endpoint_url = ( defined( 'EP_AUTOSUGGEST_ENDPOINT' ) && EP_AUTOSUGGEST_ENDPOINT ) ? EP_AUTOSUGGEST_ENDPOINT : $settings['endpoint_url'];
		?>

		<div class="field">
			<div class="field-name status"><label for="feature_autosuggest_endpoint_url"><?php esc_html_e( 'Endpoint URL', 'elasticpress' ); ?></label></div>
			<div class="input-wrap">
				<input <?php disabled( defined( 'EP_AUTOSUGGEST_ENDPOINT' ) && EP_AUTOSUGGEST_ENDPOINT ); ?> value="<?php echo esc_url( $endpoint_url ); ?>" type="text" name="settings[endpoint_url]" id="feature_autosuggest_endpoint_url">

				<?php if ( defined( 'EP_AUTOSUGGEST_ENDPOINT' ) && EP_AUTOSUGGEST_ENDPOINT ) : ?>
					<p class="field-description"><?php esc_html_e( 'Your autosuggest endpoint is set in wp-config.php', 'elasticpress' ); ?></p>
				<?php endif; ?>

				<p class="field-description"><?php esc_html_e( 'This address will be exposed to the public.', 'elasticpress' ); ?></p>
			</div>
		</div>

		<?php
	}

	/**
	 * Add mapping for suggest fields
	 *
	 * @param  array $mapping ES mapping.
	 * @since  2.4
	 * @return array
	 */
	public function mapping( $mapping ) {
		$post_indexable = Indexables::factory()->get( 'post' );

		$mapping = $post_indexable->add_ngram_analyzer( $mapping );
		$mapping = $post_indexable->add_term_suggest_field( $mapping );

		// Note the assignment by reference below.
		if ( version_compare( (string) Elasticsearch::factory()->get_elasticsearch_version(), '7.0', '<' ) ) {
			$mapping_properties = &$mapping['mappings']['post']['properties'];
		} else {
			$mapping_properties = &$mapping['mappings']['properties'];
		}

		$text_type = $mapping_properties['post_content']['type'];

		$mapping_properties['post_title']['fields']['suggest'] = array(
			'type'            => $text_type,
			'analyzer'        => 'edge_ngram_analyzer',
			'search_analyzer' => 'standard',
		);

		return $mapping;
	}

	/**
	 * Ensure both search and autosuggest use fuziness with type auto
	 *
	 * @param integer $fuzziness Fuzziness
	 * @param array   $search_fields Search Fields
	 * @param array   $args Array of ES args
	 * @return array
	 */
	public function set_fuzziness( $fuzziness, $search_fields, $args ) {
		if ( Utils\is_integrated_request( $this->slug, $this->get_contexts() ) && ! empty( $args['s'] ) ) {
			return 'auto';
		}
		return $fuzziness;
	}

	/**
	 * Handle ngram search fields for fuzziness fields
	 *
	 * @param array  $query ES Query arguments
	 * @param string $post_type Post Type
	 * @param array  $args WP_Query args
	 * @return array $query adjusted ES Query arguments
	 */
	public function adjust_fuzzy_fields( $query, $post_type, $args ) {
		if ( ! Utils\is_integrated_request( $this->slug, $this->get_contexts() ) || empty( $args['s'] ) ) {
			return $query;
		}

		if ( ! isset( $query['bool'] ) || ! isset( $query['bool']['must'] ) ) {
			return $query;
		}

		/**
		 * Filter autosuggest ngram fields
		 *
		 * @hook ep_autosuggest_ngram_fields
		 * @param  {array} $fields Fields available to ngram
		 * @return  {array} New fields array
		 */
		$ngram_fields = apply_filters(
			'ep_autosuggest_ngram_fields',
			[
				'post_title'        => 'post_title.suggest',
				'terms\.(.+)\.name' => 'term_suggest',
			]
		);

		/**
		 * At this point, `$query` might look like this (using the 3.5 search algorithm):
		 *
		 * [
		 *     [bool] => [
		 *         [must] => [
		 *             [0] => [
		 *                 [bool] => [
		 *                     [should] => [
		 *                         [0] => [
		 *                             [multi_match] => [
		 *                                 [query] => ep_autosuggest_placeholder
		 *                                 [type] => phrase
		 *                                 [fields] => [
		 *                                     [0] => post_title^1
		 *                                     ...
		 *                                     [n] => terms.category.name^27
		 *                                 ]
		 *                                 [boost] => 3
		 *                             ]
		 *                         ]
		 *                         [1] => [
		 *                             [multi_match] => [
		 *                                 [query] => ep_autosuggest_placeholder
		 *                                 [fields] => [ ... ]
		 *                                 [type] => phrase
		 *                                 [slop] => 5
		 *                             ]
		 *                         ]
		 *                     ]
		 *                 ]
		 *             ]
		 *         ]
		 *     ]
		 *     ...
		 * ]
		 *
		 * Also, note the usage of `&$must_query`. This means that by changing `$must_query`
		 * you will be actually changing `$query`.
		 */
		foreach ( $query['bool']['must'] as &$must_query ) {
			if ( ! isset( $must_query['bool'] ) || ! isset( $must_query['bool']['should'] ) ) {
				continue;
			}
			foreach ( $must_query['bool']['should'] as &$current_bool_should ) {
				if ( ! isset( $current_bool_should['multi_match'] ) || ! isset( $current_bool_should['multi_match']['fields'] ) ) {
					continue;
				}

				/**
				 * `fuzziness` is used in the original algorithm.
				 * `slop` is used in `3.5`.
				 *
				 * @see \ElasticPress\Indexable\Post\Post::format_args()
				 */
				if ( empty( $current_bool_should['multi_match']['fuzziness'] ) && empty( $current_bool_should['multi_match']['slop'] ) ) {
					continue;
				}

				$fields_to_add = [];

				/**
				 * If the regex used in `$ngram_fields` matches more than one field,
				 * like taxonomies, for example, we use the min value - 1.
				 */
				foreach ( $current_bool_should['multi_match']['fields'] as $field ) {
					foreach ( $ngram_fields as $regex => $ngram_field ) {
						if ( preg_match( '/^(' . $regex . ')(\^(\d+))?$/', $field, $match ) ) {
							$weight = 1;
							if ( isset( $match[4] ) && $match[4] > 1 ) {
								$weight = $match[4] - 1;
							}

							if ( isset( $fields_to_add[ $ngram_field ] ) ) {
								$fields_to_add[ $ngram_field ] = min( $fields_to_add[ $ngram_field ], $weight );
							} else {
								$fields_to_add[ $ngram_field ] = $weight;
							}
						}
					}
				}

				foreach ( $fields_to_add as $field => $weight ) {
					$current_bool_should['multi_match']['fields'][] = "{$field}^{$weight}";
				}
			}
		}

		return $query;
	}

	/**
	 * Add term suggestions to be indexed
	 *
	 * @param array $post_args Array of ES args.
	 * @since  2.4
	 * @return array
	 */
	public function filter_term_suggest( $post_args ) {
		$suggest = [];

		if ( ! empty( $post_args['terms'] ) ) {
			foreach ( $post_args['terms'] as $taxonomy ) {
				foreach ( $taxonomy as $term ) {
					$suggest[] = $term['name'];
				}
			}
		}

		if ( ! empty( $suggest ) ) {
			$post_args['term_suggest'] = $suggest;
		}

		return $post_args;
	}

	/**
	 * Enqueue our autosuggest script
	 *
	 * @since  2.4
	 */
	public function enqueue_scripts() {
		if ( Utils\is_indexing() ) {
			return;
		}

		$host     = Utils\get_host();
		$settings = $this->get_settings();

		if ( defined( 'EP_AUTOSUGGEST_ENDPOINT' ) && EP_AUTOSUGGEST_ENDPOINT ) {
			$endpoint_url = EP_AUTOSUGGEST_ENDPOINT;
		} elseif ( Utils\is_epio() ) {
				$endpoint_url = trailingslashit( $host ) . Indexables::factory()->get( 'post' )->get_index_name() . '/autosuggest';
		} else {
			$endpoint_url = $settings['endpoint_url'];
		}

		if ( empty( $endpoint_url ) ) {
			return;
		}

		wp_enqueue_script(
			'elasticpress-autosuggest',
			EP_URL . 'dist/js/autosuggest-script.js',
			Utils\get_asset_info( 'autosuggest-script', 'dependencies' ),
			Utils\get_asset_info( 'autosuggest-script', 'version' ),
			true
		);

		wp_set_script_translations( 'elasticpress-autosuggest', 'elasticpress' );

		wp_enqueue_style(
			'elasticpress-autosuggest',
			EP_URL . 'dist/css/autosuggest-styles.css',
			Utils\get_asset_info( 'autosuggest-styles', 'dependencies' ),
			Utils\get_asset_info( 'autosuggest-styles', 'version' )
		);

		/** Features Class @var Features $features */
		$features = Features::factory();

		/** Search Feature @var Feature\Search\Search $search */
		$search = $features->get_registered_feature( 'search' );

		$query = $this->generate_search_query();

		$epas_options = [
			'query'               => $query['body'],
			'placeholder'         => $query['placeholder'],
			'endpointUrl'         => esc_url( untrailingslashit( $endpoint_url ) ),
			'selector'            => empty( $settings['autosuggest_selector'] ) ? 'ep-autosuggest' : esc_html( $settings['autosuggest_selector'] ),
			/**
			 * Filter autosuggest default selectors.
			 *
			 * @hook ep_autosuggest_default_selectors
			 * @since 3.6.0
			 * @param {string} $selectors Default selectors used to attach autosuggest.
			 * @return {string} Selectors used to attach autosuggest.
			 */
			'defaultSelectors'    => apply_filters( 'ep_autosuggest_default_selectors', '.ep-autosuggest, input[type="search"], .search-field' ),
			'action'              => 'navigate',
			'mimeTypes'           => [],
			/**
			 * Filter autosuggest HTTP headers
			 *
			 * @hook ep_autosuggest_http_headers
			 * @param  {array} $headers Autosuggest HTTP headers in name => value format
			 * @return  {array} HTTP headers
			 */
			'http_headers'        => apply_filters( 'ep_autosuggest_http_headers', [] ),
			'triggerAnalytics'    => ! empty( $settings['trigger_ga_event'] ),
			'addSearchTermHeader' => false,
			'requestIdBase'       => Utils\get_request_id_base(),
		];

		if ( Utils\is_epio() ) {
			$epas_options['addSearchTermHeader'] = true;
		}

		$search_settings = $search->get_settings();

		if ( ! $search_settings ) {
			$search_settings = [];
		}

		$search_settings = wp_parse_args( $search_settings, $search->default_settings );

		if ( ! empty( $search_settings ) && $search_settings['highlight_enabled'] ) {
			$epas_options['highlightingEnabled'] = true;
			$epas_options['highlightingTag']     = apply_filters( 'ep_highlighting_tag', $search_settings['highlight_tag'] );
			$epas_options['highlightingClass']   = apply_filters( 'ep_highlighting_class', 'ep-highlight' );
		}

		/**
		 * Output variables to use in Javascript
		 * index: the Elasticsearch index name
		 * endpointUrl:  the Elasticsearch autosuggest endpoint url
		 * postType: which post types to use for suggestions
		 * action: the action to take when selecting an item. Possible values are "search" and "navigate".
		 */
		wp_localize_script(
			'elasticpress-autosuggest',
			'epas',
			/**
			 * Filter autosuggest JavaScript options
			 *
			 * @hook ep_autosuggest_options
			 * @param  {array} $options Autosuggest options to be localized
			 * @return  {array} New options
			 */
			apply_filters(
				'ep_autosuggest_options',
				$epas_options
			)
		);
	}

	/**
	 * Build a default search request to pass to the autosuggest javascript.
	 * The request will include a placeholder that can then be replaced.
	 *
	 * @return array Generated ElasticSearch request array( 'placeholder'=> placeholderstring, 'body' => request body )
	 */
	public function generate_search_query() {

		/**
		 * Filter autosuggest query placeholder
		 *
		 * @hook ep_autosuggest_query_placeholder
		 * @param  {string} $placeholder Autosuggest placeholder to be replaced later
		 * @return  {string} New placeholder
		 */
		$placeholder = apply_filters( 'ep_autosuggest_query_placeholder', 'ep_autosuggest_placeholder' );

		/** Features Class @var Features $features */
		$features = Features::factory();

		$post_type = $features->get_registered_feature( 'search' )->get_searchable_post_types();

		/**
		 * Filter post types available to autosuggest
		 *
		 * @hook ep_term_suggest_post_type
		 * @param  {array} $post_types Post types
		 * @return  {array} New post types
		 */
		$post_type = apply_filters( 'ep_term_suggest_post_type', array_values( $post_type ) );

		$post_status = get_post_stati(
			[
				'public'              => true,
				'exclude_from_search' => false,
			]
		);

		/**
		 * Filter post statuses available to autosuggest
		 *
		 * @hook ep_term_suggest_post_status
		 * @param  {array} $post_statuses Post statuses
		 * @return  {array} New post statuses
		 */
		$post_status = apply_filters( 'ep_term_suggest_post_status', array_values( $post_status ) );

		add_filter( 'ep_intercept_remote_request', [ $this, 'intercept_remote_request' ] );
		add_filter( 'ep_weighting_configuration', [ $features->get_registered_feature( $this->slug ), 'apply_autosuggest_weighting' ] );

		add_filter( 'ep_do_intercept_request', [ $features->get_registered_feature( $this->slug ), 'intercept_search_request' ], 10, 2 );

		add_filter( 'posts_pre_query', [ $features->get_registered_feature( $this->slug ), 'return_empty_posts' ], 100, 1 ); // after ES Query to ensure we are not falling back to DB in any case

		new \WP_Query(
			/**
			 * Filter WP Query args of the autosuggest query template.
			 *
			 * If you want to display 20 posts in autosuggest:
			 *
			 * ```
			 * add_filter(
			 *     'ep_autosuggest_query_args',
			 *     function( $args ) {
			 *         $args['posts_per_page'] = 20;
			 *         return $args;
			 *     }
			 * );
			 * ```
			 *
			 * @since 4.4.0
			 * @hook ep_autosuggest_query_args
			 * @param {array} $args Query args
			 * @return {array} New query args
			 */
			apply_filters(
				'ep_autosuggest_query_args',
				[
					'post_type'    => $post_type,
					'post_status'  => $post_status,
					's'            => $placeholder,
					'ep_integrate' => true,
				]
			)
		);

		remove_filter( 'posts_pre_query', [ $features->get_registered_feature( $this->slug ), 'return_empty_posts' ], 100 );

		remove_filter( 'ep_do_intercept_request', [ $features->get_registered_feature( $this->slug ), 'intercept_search_request' ] );

		remove_filter( 'ep_weighting_configuration', [ $features->get_registered_feature( $this->slug ), 'apply_autosuggest_weighting' ] );

		remove_filter( 'ep_intercept_remote_request', [ $this, 'intercept_remote_request' ] );

		return [
			'body'        => $this->autosuggest_query,
			'placeholder' => $placeholder,
		];
	}

	/**
	 * Ensure we do not fallback to WPDB query for this request
	 *
	 * @param array $posts array of post objects
	 * @return array $posts
	 */
	public function return_empty_posts( $posts = [] ) {
		return [];
	}

	/**
	 * Allow applying custom weighting configuration for autosuggest
	 *
	 * @param array $config current configuration
	 * @return array $config desired configuration
	 */
	public function apply_autosuggest_weighting( $config = [] ) {
		/**
		 * Filter autosuggest weighting configuration
		 *
		 * @hook ep_weighting_configuration_for_autosuggest
		 * @param  {array} $config Configuration
		 * @return  {array} New config
		 */
		$config = apply_filters( 'ep_weighting_configuration_for_autosuggest', $config );
		return $config;
	}

	/**
	 * Store intercepted request value and return a fake successful request result
	 *
	 * @param array $response Response
	 * @param array $query    ES Query
	 * @return array $response Response
	 */
	public function intercept_search_request( $response, $query = [] ) {
		$this->autosuggest_query = $query['args']['body'];

		$message = wp_json_encode(
			[
				esc_html__( 'This is a fake request to build the ElasticPress Autosuggest query. It is not really sent.', 'elasticpress' ),
			]
		);

		return [
			'is_ep_fake_request' => true,
			'body'               => $message,
			'response'           => [
				'code'    => 200,
				'message' => $message,
			],
		];
	}

	/**
	 * Tell user whether requirements for feature are met or not.
	 *
	 * @return array $status Status array
	 * @since 2.4
	 */
	public function requirements_status() {
		$status = new FeatureRequirementsStatus( 0 );

		$status->message = [];

		$status->message[] = esc_html__( 'This feature modifies the site’s default user experience by presenting a list of suggestions below detected search fields as text is entered into the field.', 'elasticpress' );

		if ( ! Utils\is_epio() ) {
			$status->code      = 1;
			$status->message[] = wp_kses_post( __( "You aren't using <a href='https://elasticpress.io'>ElasticPress.io</a> so we can't be sure your host is properly secured. Autosuggest requires a publicly accessible endpoint, which can expose private content and allow data modification if improperly configured.", 'elasticpress' ) );
		}

		return $status;
	}

	/**
	 * Do a non-blocking search query to force the autosuggest hash to update.
	 *
	 * This request has to happen in a public environment, so all code testing if `is_admin()`
	 * are properly executed.
	 *
	 * @param bool $blocking If the request should block the execution or not.
	 */
	public function epio_send_autosuggest_public_request( $blocking = false ) {
		if ( ! Utils\is_epio() ) {
			return;
		}

		$url = add_query_arg(
			[
				's'                       => 'search test',
				'ep_epio_set_autosuggest' => 1,
				'ep_epio_nonce'           => wp_create_nonce( 'ep-epio-set-autosuggest' ),
				'nocache'                 => time(), // Here just to avoid the request hitting a CDN.
			],
			home_url( '/' )
		);

		// Pass the same cookies, so the same authenticated user is used (and we can check the nonce).
		$cookies = [];
		foreach ( $_COOKIE as $name => $value ) {
			if ( ! is_string( $name ) || ! is_string( $value ) ) {
				continue;
			}

			$cookies[] = new \WP_Http_Cookie(
				[
					'name'  => $name,
					'value' => $value,
				]
			);
		}

		wp_remote_get(
			$url,
			[
				'cookies'  => $cookies,
				'blocking' => (bool) $blocking,
			]
		);
	}

	/**
	 * Send the allowed parameters for autosuggest to ElasticPress.io.
	 */
	public function epio_send_autosuggest_allowed() {
		if ( empty( $_REQUEST['ep_epio_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['ep_epio_nonce'] ), 'ep-epio-set-autosuggest' ) ) {
			return;
		}

		if ( empty( $_GET['ep_epio_set_autosuggest'] ) ) {
			return;
		}

		/**
		 * Fires before the request is sent to EP.io to set Autosuggest allowed values.
		 *
		 * @hook ep_epio_pre_send_autosuggest_allowed
		 * @since  3.5.x
		 */
		do_action( 'ep_epio_pre_send_autosuggest_allowed' );

		/**
		 * The same ES query sent by autosuggest.
		 *
		 * Sometimes it'll be a string, sometimes it'll be already an array.
		 */
		$es_search_query = $this->generate_search_query()['body'];
		$es_search_query = ( is_array( $es_search_query ) ) ? $es_search_query : json_decode( $es_search_query, true );

		/**
		 * Filter autosuggest ES query
		 *
		 * @since  3.5.x
		 * @hook ep_epio_autosuggest_es_query
		 * @param  {array} The ES Query.
		 */
		$es_search_query = apply_filters( 'ep_epio_autosuggest_es_query', $es_search_query );

		/**
		 * Here is a chance to short-circuit the execution. Also, during the sync
		 * the query will be empty anyway.
		 */
		if ( empty( $es_search_query ) ) {
			return;
		}

		$index = Indexables::factory()->get( 'post' )->get_index_name();

		add_filter( 'ep_format_request_headers', [ $this, 'add_ep_set_autosuggest_header' ] );

		Elasticsearch::factory()->query( $index, 'post', $es_search_query, [] );

		remove_filter( 'ep_format_request_headers', [ $this, 'add_ep_set_autosuggest_header' ] );

		/**
		 * Fires after the request is sent to EP.io to set Autosuggest allowed values.
		 *
		 * @hook ep_epio_sent_autosuggest_allowed
		 * @since  3.5.x
		 */
		do_action( 'ep_epio_sent_autosuggest_allowed' );
	}

	/**
	 * Set a header so EP.io servers know this request contains the values
	 * that should be stored as allowed.
	 *
	 * @since 3.5.x
	 * @param array $headers The Request Headers.
	 * @return array
	 */
	public function add_ep_set_autosuggest_header( $headers ) {
		$headers['EP-Set-Autosuggest'] = true;
		return $headers;
	}

	/**
	 * Retrieve the allowed parameters for autosuggest from ElasticPress.io.
	 *
	 * @return array
	 */
	public function epio_retrieve_autosuggest_allowed() {
		$response = Elasticsearch::factory()->remote_request(
			Indexables::factory()->get( 'post' )->get_index_name() . '/get-autosuggest-allowed'
		);

		$body = wp_remote_retrieve_body( $response, true );
		return json_decode( $body, true );
	}

	/**
	 * Output the current allowed parameters for autosuggest stored in ElasticPress.io.
	 */
	public function epio_allowed_parameters() {
		global $wp_version;

		$allowed_params = $this->epio_autosuggest_set_and_get();
		if ( empty( $allowed_params ) ) {
			return;
		}
		?>
		<div class="field js-toggle-feature" data-feature="<?php echo esc_attr( $this->slug ); ?>">
			<div class="field-name status"><?php esc_html_e( 'Connection', 'elasticpress' ); ?></div>
			<div class="input-wrap">
			<?php
			$epio_link                = 'https://elasticpress.io';
			$epio_autosuggest_kb_link = 'https://www.elasticpress.io/documentation/article/elasticpress-io-autosuggest/';
			$status_report_link       = defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ? network_admin_url( 'admin.php?page=elasticpress-status-report' ) : admin_url( 'admin.php?page=elasticpress-status-report' );

			printf(
				/* translators: 1: <a> tag (ElasticPress.io); 2. </a>; 3: <a> tag (KB article); 4. </a>; 5: <a> tag (Site Health Debug Section); 6. </a>; */
				esc_html__( 'You are directly connected to %1$sElasticPress.io%2$s, ensuring the most performant Autosuggest experience. %3$sLearn more about what this means%4$s or %5$sclick here for debug information%6$s.', 'elasticpress' ),
				'<a href="' . esc_url( $epio_link ) . '">',
				'</a>',
				'<a href="' . esc_url( $epio_autosuggest_kb_link ) . '">',
				'</a>',
				'<a href="' . esc_url( $status_report_link ) . '">',
				'</a>'
			);
			?>
			</div>
		</div>
		<?php
	}

	/**
	 * Try to get the allowed parameters. If they are not set, set it and try to get them again.
	 *
	 * @since 3.5.x
	 * @return array
	 */
	public function epio_autosuggest_set_and_get() {
		$allowed_params = [];
		$errors_count   = 1;
		for ( $i = 0; $i <= $errors_count; $i++ ) {
			$allowed_params = $this->epio_retrieve_autosuggest_allowed();

			if ( is_wp_error( $allowed_params ) || ( isset( $allowed_params['status'] ) && 200 !== $allowed_params['status'] ) ) {
				$allowed_params = [];
				break;
			}

			// We have what we need, no need to retry.
			if ( ! empty( $allowed_params ) ) {
				break;
			}

			// Send to EP.io what should be autosuggest's allowed values and try to get them again.
			$this->epio_send_autosuggest_public_request( true );
		}

		return $allowed_params;
	}

	/**
	 * Return true, so EP knows we want to intercept the remote request
	 *
	 * As we add and remove this function from `ep_intercept_remote_request`,
	 * using `__return_true` could remove a *real* `__return_true` added by someone else.
	 *
	 * @since 4.7.0
	 * @see https://github.com/10up/ElasticPress/issues/2887
	 * @return true
	 */
	public function intercept_remote_request() {
		return true;
	}

	/**
	 * Conditionally add EP.io information to the settings schema
	 *
	 * @since 5.0.0
	 */
	protected function maybe_add_epio_settings_schema() {
		if ( ! Utils\is_epio() ) {
			return;
		}

		$epio_link                = 'https://elasticpress.io';
		$epio_autosuggest_kb_link = 'https://www.elasticpress.io/documentation/article/elasticpress-io-autosuggest/';
		$status_report_link       = defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ? network_admin_url( 'admin.php?page=elasticpress-status-report' ) : admin_url( 'admin.php?page=elasticpress-status-report' );

		$this->settings_schema[] = [
			'key'   => 'epio',
			'label' => sprintf(
				/* translators: 1: <a> tag (ElasticPress.io); 2. </a>; 3: <a> tag (KB article); 4. </a>; 5: <a> tag (Site Health Debug Section); 6. </a>; */
				__( 'You are directly connected to %1$sElasticPress.io%2$s, ensuring the most performant Autosuggest experience. %3$sLearn more about what this means%4$s or %5$sclick here for debug information%6$s.', 'elasticpress' ),
				'<a href="' . esc_url( $epio_link ) . '">',
				'</a>',
				'<a href="' . esc_url( $epio_autosuggest_kb_link ) . '">',
				'</a>',
				'<a href="' . esc_url( $status_report_link ) . '">',
				'</a>'
			),
			'type'  => 'markup',
		];
	}

	/**
	 * Set the `settings_schema` attribute
	 *
	 * @since 5.0.0
	 */
	protected function set_settings_schema() {
		$this->settings_schema = [
			[
				'default' => '.ep-autosuggest',
				'help'    => __( 'Input additional selectors where you would like to include autosuggest, separated by a comma. Example: <code>.custom-selector, #custom-id, input[type="text"]</code>', 'elasticpress' ),
				'key'     => 'autosuggest_selector',
				'label'   => __( 'Additional selectors', 'elasticpress' ),
				'type'    => 'text',
			],
			[
				'default' => '0',
				'key'     => 'trigger_ga_event',
				'help'    => __( 'Enable to fire a gtag tracking event when an autosuggest result is clicked.', 'elasticpress' ),
				'label'   => __( 'Trigger Google Analytics events', 'elasticpress' ),
				'type'    => 'checkbox',
			],
		];

		$this->maybe_add_epio_settings_schema();

		if ( ! Utils\is_epio() ) {
			$set_in_wp_config = defined( 'EP_AUTOSUGGEST_ENDPOINT' ) && EP_AUTOSUGGEST_ENDPOINT;

			$this->settings_schema[] = [
				'disabled' => $set_in_wp_config,
				'help'     => ! $set_in_wp_config ? __( 'A valid URL starting with <code>http://</code> or <code>https://</code>. This address will be exposed to the public.', 'elasticpress' ) : '',
				'key'      => 'endpoint_url',
				'label'    => __( 'Endpoint URL', 'elasticpress' ),
				'type'     => 'url',
			];
		}
	}

	/**
	 * DEPRECATED. Delete the cached query for autosuggest.
	 *
	 * @since 3.5.5
	 */
	public function delete_cached_query() {
		_doing_it_wrong(
			__METHOD__,
			esc_html__( 'This method should not be called anymore, as autosuggest requests are not sent regularly anymore.' ),
			'ElasticPress 4.7.0'
		);
	}

	/**
	 * Get the contexts for autosuggest.
	 *
	 * @since 5.1.0
	 * @return array
	 */
	protected function get_contexts(): array {
		/**
		 * Filter contexts for autosuggest.
		 *
		 * @hook ep_autosuggest_contexts
		 * @since 5.1.0
		 * @param {array} $contexts Contexts for autosuggest
		 * @return {array} New contexts
		 */
		return apply_filters( 'ep_autosuggest_contexts', [ 'public', 'ajax' ] );
	}
}
