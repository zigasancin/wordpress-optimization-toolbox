<?php
/**
 * Synonyms Feature
 *
 * @package elasticpress
 */

namespace ElasticPress\Feature\Search;

use ElasticPress\Elasticsearch;
use ElasticPress\FeatureRequirementsStatus;
use ElasticPress\Features;
use ElasticPress\Indexables;
use ElasticPress\REST;
use ElasticPress\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Synonyms Feature
 *
 * @since 3.4
 * @package ElasticPress\Feature\Synonyms
 */
class Synonyms {

	/**
	 * Internal name of the post type
	 */
	const POST_TYPE_NAME = 'ep-synonym';

	/**
	 * Indices that should receive the synonym filter.
	 *
	 * @var array
	 */
	public $affected_indices;

	/**
	 * Elasticsearch Synonym Filter Name
	 *
	 * @var string
	 */
	public $filter_name;

	/**
	 * Synonym post id.
	 *
	 * @var int
	 */
	protected $synonym_post_id;

	/**
	 * Initialize feature setting it's config
	 *
	 * @since  3.4
	 */
	public function __construct() {
		$this->filter_name      = 'ep_synonyms_filter';
		$this->affected_indices = [ 'post' ];
	}

	/**
	 * Get search feature.
	 *
	 * @return Search
	 */
	public function get_search_feature() {
		/** Features Class @var Features $features */
		$features = Features::factory();

		/** Search Feature @var Feature\Search\Search $search */
		return $features->get_registered_feature( 'search' );
	}

	/**
	 * Returns requirements status of feature
	 *
	 * Requires the search feature to be activated
	 *
	 * @return FeatureRequirementsStatus
	 */
	public function requirements_status() {
		$search = $this->get_search_feature();

		if ( ! $search->is_active() ) {
			return new FeatureRequirementsStatus( 2, esc_html__( 'This feature requires the "Post Search" feature to be enabled', 'elasticpress' ) );
		}

		return new FeatureRequirementsStatus( 0 );
	}

	/**
	 * Setup Feature Functionality
	 *
	 * @return bool
	 */
	public function setup() {
		if ( (bool) $this->requirements_status()->code ) {
			return false;
		}

		// Register a post type to hold the synonyms post.
		add_action( 'init', [ $this, 'register_post_type' ] );

		// Setup the UI.
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 50 );
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );

		// Add the synonyms to the elasticsearch query.
		add_filter( 'ep_config_mapping', [ $this, 'add_search_synonyms' ], 20, 2 );

		// Register REST routes.
		add_action( 'rest_api_init', [ $this, 'setup_endpoint' ] );

		return true;
	}

	/**
	 * Enqueues scripts and styles.
	 *
	 * @return void
	 */
	public function scripts() {
		if ( ! $this->is_synonym_page() ) {
			return;
		}

		wp_enqueue_script(
			'ep_synonyms_scripts',
			EP_URL . 'dist/js/synonyms-script.js',
			Utils\get_asset_info( 'synonyms-script', 'dependencies' ),
			Utils\get_asset_info( 'synonyms-script', 'version' ),
			true
		);

		wp_set_script_translations( 'ep_synonyms_scripts', 'elasticpress' );

		wp_enqueue_style( 'wp-edit-post' );

		wp_enqueue_style(
			'ep_synonyms_scripts',
			EP_URL . 'dist/css/synonyms-script.css',
			[ 'wp-components', 'wp-edit-post' ],
			Utils\get_asset_info( 'synonyms-styles', 'version' ),
			'all'
		);

		wp_enqueue_style(
			'ep_synonyms_styles',
			EP_URL . 'dist/css/synonyms-styles.css',
			Utils\get_asset_info( 'synonyms-styles', 'dependencies' ),
			Utils\get_asset_info( 'synonyms-styles', 'version' ),
			'all'
		);

		$api_url  = rest_url( 'elasticpress/v1/synonyms' );
		$sync_url = Utils\get_sync_url();

		wp_localize_script(
			'ep_synonyms_scripts',
			'epSynonyms',
			[
				'apiUrl'        => esc_url_raw( $api_url ),
				'defaultIsSolr' => $this->synonyms_editor_mode() === 'advanced',
				'defaultSolr'   => $this->get_synonyms_raw(),
				'syncUrl'       => esc_url_raw( $sync_url ),
			]
		);
	}

	/**
	 * Adds the synonyms settings page to the admin menu.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_submenu_page(
			'elasticpress',
			esc_html__( 'ElasticPress Synonyms', 'elasticpress' ),
			esc_html__( 'Synonyms', 'elasticpress' ),
			Utils\get_capability( 'synonyms' ),
			'elasticpress-synonyms',
			[ $this, 'admin_page' ]
		);
	}

	/**
	 * Renders the synonyms settings page.
	 *
	 * @return void
	 */
	public function admin_page() {
		include EP_PATH . '/includes/partials/header.php';

		?>
		<div class="wrap">
			<div id="ep-synonyms"></div>
		</div>
		<?php
	}

	/**
	 * Admin notices.
	 *
	 * @return void
	 * @deprecated 5.1.0
	 */
	public function admin_notices() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::admin_notices', '5.1.0' );

		if ( ! $this->is_synonym_page() ) {
			return;
		}

		$update = filter_input( INPUT_GET, 'ep_synonym_update', FILTER_SANITIZE_SPECIAL_CHARS );

		if ( ! in_array( $update, [ 'success', 'error-update-post', 'error-update-index' ], true ) ) {
			return;
		}

		$class   = ( 'success' === $update ? 'notice-success' : 'notice-error' ) . ' notice';
		$message = '';

		switch ( $update ) {
			case 'success':
				$message = __( 'Successfully updated synonym filter.', 'elasticpress' );
				break;
			case 'error-update-post':
				$message = __( 'There was an error storing your synonyms.', 'elasticpress' );
				break;
			case 'error-update-index':
				$message = __( 'There was a problem updating the index with your synonyms. If you have not indexed your data, please run an index.', 'elasticpress' );
				break;
			default:
				$message = __( 'There was an error updating the synonym list.', 'elasticpress' );
		}

		printf(
			'<div class="%1$s"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $message )
		);
	}

	/**
	 * Registers a post type for our synonyms post storage.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$args = [
			'description'        => esc_html__( 'Elasticsearch Synonyms', 'elasticpress' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => true,
			'capabilities'       => Utils\get_post_map_capabilities( 'synonyms' ),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 100,
			'supports'           => [ 'title' ],
		];

		register_post_type( self::POST_TYPE_NAME, $args );
	}

	/**
	 * Get the post id of the post holding our synonyms.
	 *
	 * @return int The synonym post ID.
	 */
	public function get_synonym_post_id() {
		if ( ! $this->synonym_post_id ) {
			$query_synonym_post = new \WP_Query(
				array(
					'fields'         => 'ids',
					'post_type'      => self::POST_TYPE_NAME,
					'posts_per_page' => 1,
					'orderby'        => 'modified',
					'post_status'    => 'any',
				)
			);

			$this->synonym_post_id = ( $query_synonym_post->post_count >= 1 ) ? $query_synonym_post->posts[0] : false;

			if ( ! $this->synonym_post_id ) {
				$this->synonym_post_id = $this->insert_default_synonym_post();
			}
		}

		return $this->synonym_post_id;
	}

	/**
	 * Get synonyms in their raw format.
	 *
	 * @return string
	 */
	public function get_synonyms_raw() {
		$post = get_post( $this->get_synonym_post_id() );

		if ( ! $post ) {
			return '';
		}

		return $post->post_content;
	}

	/**
	 * Get an array of user defined synonyms.
	 *
	 * @return array
	 */
	public function get_synonyms() {
		$synonyms_raw = $this->get_synonyms_raw();
		$synonyms     = array_values(
			array_filter(
				array_map( [ $this, 'validate_synonym' ], preg_split( '/\r\n|\r|\n/', $synonyms_raw ) )
			)
		);

		/**
		 * Filter array of synonyms to add to a custom synonym filter.
		 *
		 * @hook ep_synonyms
		 * @return  {array} The new array of search synonyms.
		 */
		return apply_filters( 'ep_synonyms', $synonyms );
	}

	/**
	 * Validate a synonym.
	 *
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-synonym-tokenfilter.html#_solr_synonyms
	 * @param  string $synonym The synonym.
	 * @return string|boolean  String synonym if valid, boolean false if validation failed.
	 */
	public function validate_synonym( $synonym ) {
		// Don't use empty lines.
		if ( empty( trim( $synonym ) ) ) {
			return false;
		}

		// Don't use lines that start with "#", those are comments.
		if ( 0 === strpos( $synonym, '#' ) ) {
			return false;
		}

		// Don't use lines that start with "//" though not in Solr spec.
		if ( 0 === strpos( $synonym, '//' ) ) {
			return false;
		}

		return sanitize_text_field( $synonym, true );
	}

	/**
	 * Add search synonyms.
	 *
	 * @param array  $mapping Elasticsearch mapping.
	 * @param string $index  Index name.
	 * @return array
	 */
	public function add_search_synonyms( $mapping, $index ) {
		$synonyms    = $this->get_synonyms();
		$indices     = $this->get_affected_indices();
		$filter_name = $this->get_synonym_filter_name();

		// Ensure we should affect this mapping.
		if ( ! in_array( $index, $indices, true ) ) {
			return $mapping;
		}

		// Ensure we have synonyms to add.
		if ( ! is_array( $synonyms ) || empty( $synonyms ) ) {
			return $mapping;
		}

		// Ensure we have filters and that it is an array.
		if ( ! isset( $mapping['settings']['analysis']['filter'] )
			|| ! is_array( $mapping['settings']['analysis']['filter'] )
		) {
			return $mapping;
		}

		// Ensure we have analyzers and that it is an array.
		if ( ! isset( $mapping['settings']['analysis']['analyzer']['default_search']['filter'] )
			|| ! is_array( $mapping['settings']['analysis']['analyzer']['default_search']['filter'] )
		) {
			return $mapping;
		}

		// Create a custom synonym filter for EP.
		$mapping['settings']['analysis']['filter'][ $filter_name ] = $this->get_synonym_filter();

		// Tell the analyzer to use our newly created filter.
		$mapping['settings']['analysis']['analyzer']['default_search']['filter'] = $this->maybe_change_filter_position(
			array_values(
				array_merge(
					[ $filter_name ],
					$mapping['settings']['analysis']['analyzer']['default_search']['filter'],
				)
			)
		);

		return $mapping;
	}

	/**
	 * Handles updating the synonym list.
	 *
	 * @return void
	 * @deprecated 5.1.0
	 */
	public function handle_update_synonyms() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::handle_update_synonyms', '5.1.0' );

		$nonce   = filter_input( INPUT_POST, $this->get_nonce_field(), FILTER_SANITIZE_SPECIAL_CHARS );
		$referer = filter_input( INPUT_POST, '_wp_http_referer', FILTER_SANITIZE_URL );
		$post_id = false;
		$update  = false;

		if ( wp_verify_nonce( $nonce, $this->get_nonce_action() ) ) {
			$synonyms = filter_input( INPUT_POST, $this->get_synonym_field(), FILTER_CALLBACK, [ 'options' => 'wp_strip_all_tags' ] );
			$mode     = filter_input( INPUT_POST, 'synonyms_editor_mode', FILTER_SANITIZE_SPECIAL_CHARS );
			$content  = trim( sanitize_textarea_field( $synonyms ) );

			// Content can't be empty.
			if ( empty( $content ) ) {
				$lines   = $this->example_synonym_list( true );
				$content = implode( PHP_EOL, [ $lines[0], $lines[2], $lines[3] ] );
			}

			$post_id = $this->update_synonym_post( $content );

			// Update Elasticsearch
			$update = $this->update_synonyms();

			// Save editor mode.
			if ( in_array( $mode, [ 'advanced', 'simple' ], true ) ) {
				$this->save_editor_mode( $mode );
			}
		}

		$result = 'success';

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			$result = 'error-update-post';
		}

		if ( ! $update ) {
			$result = 'error-update-index';
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'ep_synonym_update' => $result,
				],
				esc_url_raw( $referer )
			)
		);
		exit;
	}

	/**
	 * Update synonyms.
	 *
	 * @return boolean
	 */
	public function update_synonyms() {
		return array_reduce(
			$this->get_affected_indices(),
			function ( $success, $index ) {
				$filter  = $this->get_synonym_filter();
				$mapping = Elasticsearch::factory()->get_mapping( $index );

				if ( empty( $mapping ) || empty( $mapping[ $index ] ) ) {
					return false;
				}

				$filters = (array) $mapping[ $index ]['settings']['index']['analysis']['analyzer']['default_search']['filter'];

				/*
				 * Due to limitations in Elasticsearch, we can't remove the filter and analyzer
				 * once set on the index settings and synonyms array can't be empty.  So we set a
				 * fallback synonyms array here if the user supplied synonym array is empty.
				 */
				if ( empty( $filter['synonyms'] ) ) {
					$filter['synonyms'] = [ 'odd,unusual' ];
				}

				// Construct the synonym filter.
				$setting['index']['analysis']['filter']['ep_synonyms_filter'] = $filter;

				// Add the analyzer.
				$setting['index']['analysis']['analyzer']['default_search']['filter'] = $this->maybe_change_filter_position(
					array_values(
						array_unique(
							array_merge(
								[ $this->get_synonym_filter_name() ],
								$filters
							)
						)
					)
				);

				// Put it to Elasticsearch.
				$update = Elasticsearch::factory()->update_index_settings( $index, $setting, true );
				return $success ? $update : false;
			},
			true
		);
	}

	/**
	 * Get affected indices.
	 *
	 * @return array
	 */
	public function get_affected_indices() {
		/**
		 * Filter the indices that use the synonym filter.
		 *
		 * @return array Array of index names.
		 */
		$indices = apply_filters( 'ep_synonyms_affected_indices', $this->affected_indices );

		return array_filter(
			array_map(
				function ( $index ) {
					$indexable = Indexables::factory()->get( $index );
					return $indexable ? $indexable->get_index_name() : false;
				},
				$indices
			)
		);
	}

	/**
	 * Get synonym filter name.
	 *
	 * @return string
	 */
	public function get_synonym_filter_name() {
		/**
		 * Filter name of the synonym filter set in elasticsearch.
		 *
		 * @hook ep_synonyms_filter_name
		 * @return  {string} The name of the synonyms filter.
		 */
		return apply_filters( 'ep_synonyms_filter_name', $this->filter_name );
	}

	/**
	 * Get synonym filter.
	 *
	 * @return array
	 */
	public function get_synonym_filter() {
		/**
		 * Filter the synonym filter set in elasticsearch.
		 *
		 * @hook ep_synonyms_filter
		 * @return  {array} The synonym search filter.
		 */
		return apply_filters(
			'ep_synonyms_filter',
			[
				'type'     => 'synonym_graph',
				'lenient'  => true,
				'synonyms' => $this->get_synonyms(),
			]
		);
	}

	/**
	 * Get form action for admin page.
	 *
	 * @access protected
	 * @return string The admin post form action url.
	 * @deprecated 5.1.0
	 */
	public function get_form_action() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::get_form_action', '5.1.0' );

		return esc_url_raw( admin_url( 'admin-post.php' ) );
	}

	/**
	 * Render admin page form hidden fields.
	 *
	 * @return void
	 * @deprecated 5.1.0
	 */
	public function form_hidden_fields() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::get_form_action', '5.1.0', );

		wp_nonce_field( $this->get_nonce_action(), $this->get_nonce_field() );
		?>
		<input type="hidden" name="action" value="<?php echo esc_attr( $this->get_action() ); ?>" />
		<?php
	}

	/**
	 * Get nonce action for admin page form.
	 *
	 * @return string
	 * @deprecated 5.1.0
	 */
	public function get_nonce_action() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::get_form_action', '5.1.0', );

		return $this->get_action();
	}

	/**
	 * Get nonce field for admin page form.
	 *
	 * @return string
	 * @deprecated 5.1.0
	 */
	public function get_nonce_field() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::get_nonce_field', '5.1.0', );

		return 'ep_synonyms_nonce';
	}

	/**
	 * Get synonym field name for admin page form.
	 *
	 * @return string
	 * @deprecated 5.1.0
	 */
	public function get_synonym_field() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::get_synonym_field', '5.1.0', );

		return 'ep_synonyms';
	}

	/**
	 * Get the action slug for admin page form.
	 *
	 * @return string
	 * @deprecated 5.1.0
	 */
	public function get_action() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::get_action', '5.1.0', );

		return 'ep_synonyms_update';
	}

	/**
	 * Is this our synonym page.
	 *
	 * @return boolean
	 */
	public function is_synonym_page() {
		if ( ! function_exists( '\get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		return ( 'elasticpress_page_elasticpress-synonyms' === $screen->base );
	}

	/**
	 * An example synonym that we initialize new synonyms lists with.
	 *
	 * @param  bool $as_array Optional. Return an array of synonym lines. Default false.
	 * @return string
	 */
	public function example_synonym_list( $as_array = false ) {
		$lines = [
			__( '# Defined synonyms.', 'elasticpress' ),
			'runner, running shoe, sneaker, tennis shoe, trainer',
			'',
			__( '# Defined hyponyms.', 'elasticpress' ),
			'blue => blue, aqua, azure, cerulean, cyan, ultramarine',
			'',
			__( '# Defined replacements.', 'elasticpress' ),
			'supposably => supposedly',
			'flustrated => flustered, frustrated',
			'intensive purposes => intents and purposes',
		];

		return $as_array ? $lines : implode( PHP_EOL, $lines );
	}

	/**
	 * Gets localized strings for use on the front end.
	 *
	 * @return array
	 * @deprecated 5.1.0
	 */
	public function get_localized_strings() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::get_localized_strings', '5.1.0' );

		return array(
			'pageHeading'                  => __( 'Manage Synonyms', 'elasticpress' ),
			'pageDescription'              => __( 'Synonyms enable more flexible search results that show relevant results even without an exact match. Synonyms can be defined as a sets where all words are synonyms for each other, or as alternatives where searches for the primary word will also match the rest, but no vice versa.', 'elasticpress' ),
			'pageToggleAdvanceText'        => __( 'Switch to Advanced Text Editor', 'elasticpress' ),
			'pageToggleSimpleText'         => __( 'Switch to Visual Editor', 'elasticpress' ),

			'setsTitle'                    => __( 'Sets', 'elasticpress' ),
			'setsDescription'              => __( 'Sets are terms that will all match each other for search results. This is useful where all words are considered equivalent, such as product renaming or regional variations like sneakers, tennis shoes, trainers, and runners.', 'elasticpress' ),
			'setsInputHeading'             => __( 'Comma separated list of terms', 'elasticpress' ),
			'setsAddButtonText'            => __( 'Add Set', 'elasticpress' ),
			'setsErrorMessage'             => __( 'This set must contain at least 2 terms.', 'elasticpress' ),

			'alternativesTitle'            => __( 'Alternatives', 'elasticpress' ),
			'alternativesDescription'      => __( 'Alternatives are terms that will also be matched when you search for the primary term. For instance, a search for shoes can also include results for sneaker, sandals, boots, and high heels.', 'elasticpress' ),
			'alternativesPrimaryHeading'   => __( 'Primary term', 'elasticpress' ),
			'alternativesInputHeading'     => __( 'Comma separated list of alternatives', 'elasticpress' ),
			'alternativesAddButtonText'    => __( 'Add Alternative', 'elasticpress' ),
			'alternativesErrorMessage'     => __( 'You must enter both a primary term and at least one alternative term.', 'elasticpress' ),

			'solrTitle'                    => __( 'Advanced Synonym Editor', 'elasticpress' ),
			'solrDescription'              => __( 'When you add Sets and Alternatives above, we reduce them to SolrSynonyms which Elasticsearch can understand. If you are an advanced user, you can edit synonyms directly using Solr synonym formatting. This is beneficial if you want to import a large dictionary of synonyms, or want to export this site\'s synonyms for use on another site.', 'elasticpress' ),
			'solrInputHeading'             => __( 'SolrSynonym Text', 'elasticpress' ),
			'solrAlternativesErrorMessage' => __( 'Alternatives must have both a primary term and at least one alternative term.', 'elasticpress' ),
			'solrSetsErrorMessage'         => __( 'Sets must contain at least 2 terms.', 'elasticpress' ),

			'removeItemText'               => __( 'Remove', 'elasticpress' ),
			'submitText'                   => __( 'Update Synonyms', 'elasticpress' ),

			'synonymsTextareaInputName'    => $this->get_synonym_field(),
		);
	}

	/**
	 * Get data to export to the frontend with localization strings.
	 *
	 * @return array
	 * @deprecated 5.1.0
	 */
	public function get_localized_data() {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::get_localized_strings', '5.1.0' );

		$data     = array(
			'sets'         => array(),
			'alternatives' => array(),
			'initialMode'  => $this->synonyms_editor_mode(),
		);
		$synonyms = $this->get_synonyms();

		foreach ( $synonyms as $line ) {
			$synonym = array();
			if ( strpos( $line, '=>' ) ) {
				$tokens = explode( '=>', $line );
				array_push( $synonym, self::prepare_localized_token( $tokens[0], true ) );
				array_push(
					$synonym,
					...array_map(
						array( __CLASS__, 'prepare_localized_token' ),
						explode( ',', $tokens[1] )
					)
				);
				array_push( $data['alternatives'], $synonym );
			} else {
				array_push(
					$synonym,
					...array_map(
						array( __CLASS__, 'prepare_localized_token' ),
						explode( ',', $line )
					)
				);
				array_push( $data['sets'], $synonym );
			}
		}

		return $data;
	}

	/**
	 * Saves the editor mode.
	 *
	 * @param  string $mode The mode, one of "advanced" or "simple".
	 * @return void
	 */
	public function save_editor_mode( $mode ) {
		$search   = $this->get_search_feature();
		$settings = $search->get_settings();

		if ( isset( $settings['synonyms_editor_mode'] ) && $settings['synonyms_editor_mode'] === $mode ) {
			return;
		}

		$settings['synonyms_editor_mode'] = $mode;
		$features                         = Features::factory();
		$features->update_feature( $search->slug, $settings, false );
	}

	/**
	 * Get the stored editor mode. Default simple.
	 *
	 * @return boolean
	 */
	public function synonyms_editor_mode() {
		$search   = $this->get_search_feature();
		$settings = $search->get_settings();
		$settings = wp_parse_args( is_array( $settings ) ? $settings : [], $search->default_settings );
		$mode     = $settings['synonyms_editor_mode'];

		/**
		 * Filter the default synonyms editor mode.
		 *
		 * @hook ep_synonyms_editor_mode
		 * @return  {string} One of 'simple' or 'advanced'.
		 */
		$filtered = apply_filters( 'ep_synonyms_editor_mode', $mode );

		return in_array( $filtered, [ 'simple', 'advanced' ], true ) ? $filtered : 'simple';
	}

	/**
	 * Prepare localized token.
	 *
	 * @param string  $token    The synonym token to prepare.
	 * @param boolean $primary Whether this string is the primary term of an alternative.
	 * @return array
	 * @deprecated 5.1.0
	 */
	public static function prepare_localized_token( $token, $primary = false ) {
		_deprecated_function( 'ElasticPress\Feature\Search\Synonyms::prepare_localized_token', '5.1.0' );

		return array(
			'label'   => trim( sanitize_text_field( $token ) ),
			'value'   => trim( sanitize_text_field( $token ) ),
			'primary' => $primary,
		);
	}

	/**
	 * Insert default synonym post
	 *
	 * @return int|WP_Error
	 */
	private function insert_default_synonym_post() {
		return wp_insert_post(
			[
				'post_content' => $this->example_synonym_list(),
				'post_type'    => self::POST_TYPE_NAME,
			],
			true
		);
	}

	/**
	 * Update synonym post
	 *
	 * @param string $content The content post.
	 * @return int|WP_Error
	 */
	public function update_synonym_post( $content ) {
		$synonym_post_id = $this->get_synonym_post_id();

		if ( ! $synonym_post_id ) {
			return $synonym_post_id;
		}

		return wp_insert_post(
			[
				'ID'           => $synonym_post_id,
				'post_content' => $content,
				'post_type'    => self::POST_TYPE_NAME,
			],
			true
		);
	}

	/**
	 * Setup REST endpoints
	 *
	 * @since 5.1.0
	 */
	public function setup_endpoint() {
		$controller = new REST\Synonyms();
		$controller->register_routes();
	}

	/**
	 * Change the position of the lowercase filter to the beginning of the array.
	 *
	 * @since 5.1.0
	 * @param array $filters Array of filters.
	 * @return array
	 */
	protected function maybe_change_filter_position( array $filters ): array {
		$lowercase_filter = array_search( 'lowercase', $filters, true );

		if ( false !== $lowercase_filter ) {
			unset( $filters[ $lowercase_filter ] );
			array_unshift( $filters, 'lowercase' );
		}

		return $filters;
	}
}
