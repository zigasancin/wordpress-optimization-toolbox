<?php
namespace Smush\App;

use Smush\Core\Array_Utils;

class Settings_Row {
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $class;

	/**
	 * @var string
	 */
	private $title;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var array
	 */
	private $args;

	/**
	 * @var callable
	 */
	private $title_filter_callback;

	/**
	 * @var callable
	 */
	private $description_filter_callback;

	/**
	 * @var callable
	 */
	private $content_callback;

	/**
	 * @var Array_Utils
	 */
	private $array_utils;

	public function __construct( $title, $description, $content_callback, $args = array() ) {
		if ( is_callable( $title ) ) {
			$this->title_filter_callback = $title;
		} else {
			$this->title = $title;
		}

		if ( is_callable( $description ) ) {
			$this->description_filter_callback = $description;
		} else {
			$this->description = $description;
		}

		$this->content_callback = $content_callback;
		$this->args             = $args;
		$this->array_utils      = new Array_Utils();
	}

	public function get_id() {
		if ( ! $this->id ) {
			$this->id = $this->prepare_id();
		}

		return $this->id;
	}

	private function prepare_id() {
		$id = $this->array_utils->get_array_value( $this->args, 'id' );
		if ( ! $id ) {
			$id = sanitize_key( $this->title );
		}

		return $id;
	}

	public function get_class() {
		if ( ! $this->class ) {
			$this->class = $this->prepare_class();
		}

		return $this->class;
	}

	private function prepare_class() {
		$class = $this->array_utils->get_array_value( $this->args, 'class', '' );

		return trim( 'sui-box-settings-row ' . $class );
	}

	public function set_title_filter_callback( $callback ) {
		$this->title_filter_callback = $callback;
	}

	public function set_description_filter_callback( $callback ) {
		$this->description_filter_callback = $callback;
	}

	public function set_content_callback( $callback ) {
		$this->content_callback = $callback;
	}

	public function render_title() {
		if ( is_callable( $this->title_filter_callback ) ) {
			return call_user_func( $this->title_filter_callback, $this->title );
		}

		echo esc_html( $this->title );
	}

	public function render_description() {
		if ( is_callable( $this->description_filter_callback ) ) {
			return call_user_func( $this->description_filter_callback, $this->description );
		}

		echo esc_html( $this->description );
	}

	public function render_content() {
		if ( is_callable( $this->content_callback ) ) {
			return call_user_func( $this->content_callback );
		}
	}

	public function render() {
		?>
		<div id="<?php echo esc_attr( $this->get_id() ); ?>" class="<?php echo esc_attr( $this->get_class() ); ?>">
			<div class="sui-box-settings-col-1">
				<span class="sui-settings-label">
					<?php $this->render_title(); ?>
				</span>
				<span class="sui-description">
					<?php $this->render_description(); ?>
				</span>
			</div>
			<div class="sui-box-settings-col-2">
				<?php $this->render_content(); ?>
			</div>
		</div>
		<?php
	}
}
