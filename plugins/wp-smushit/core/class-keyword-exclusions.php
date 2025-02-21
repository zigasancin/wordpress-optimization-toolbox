<?php

namespace Smush\Core;

class Keyword_Exclusions {
	const ID_PREFIX    = '#';
	const CLASS_PREFIX = '.';
	const FILE_PREFIX  = '@';

	/**
	 * Excluded keywords.
	 *
	 * @var array
	 */
	private $excluded_keywords;

	/**
	 * Excluded URL Keywords.
	 *
	 * @var array
	 */
	private $excluded_url_keywords = array();

	/**
	 * Excluded IDs.
	 *
	 * @var array
	 */
	private $excluded_ids = array();

	/**
	 * Excluded Classes.
	 *
	 * @var array
	 */
	private $excluded_classes = array();

	/**
	 * Other excluded keywords.
	 *
	 * @var array
	 */
	private $common_excluded_keywords = array();

	/**
	 * Exclusion_Utils constructor.
	 *
	 * @param array $excluded_keywords  Excluded keywords.
	 *
	 * @return void
	 */
	public function __construct( $excluded_keywords ) {
		$this->excluded_keywords = $this->sanitize_keywords( $excluded_keywords );
	}

	/**
	 * Sanitize keywords.
	 *
	 * @param array $keywords Keywords.
	 *
	 * @return array
	 */
	private function sanitize_keywords( $keywords ) {
		if ( empty( $keywords ) ) {
			return array();
		}

		$keywords = (array) $keywords;

		$sanitized_keywords = array_filter(
			$keywords,
			function ( $keyword ) {
				return is_string( $keyword ) && '' !== trim( $keyword );
			}
		);

		$sanitized_keywords = array_map( 'trim', $sanitized_keywords );

		return array_unique( $sanitized_keywords );
	}

	/**
	 * Check if excluded keywords are set.
	 *
	 * @return bool
	 */
	public function has_excluded_keywords() {
		return ! empty( $this->excluded_keywords );
	}

	/**
	 * Get excluded url keywords from excluded keywords.
	 *
	 * @return array
	 */
	public function get_excluded_url_keywords() {
		if ( ! $this->excluded_url_keywords ) {
			$this->excluded_url_keywords = $this->prepare_excluded_url_keywords();
		}

		return $this->excluded_url_keywords;
	}

	/**
	 * Prepare excluded URL keywords.
	 *
	 * @return array
	 */
	private function prepare_excluded_url_keywords() {
		$excluded_url_keywords = array_reduce(
			$this->excluded_keywords,
			function ( $url_keywords, $keyword ) {
				if ( ! str_starts_with( $keyword, self::FILE_PREFIX ) ) {
					return $url_keywords;
				}

				$keyword = ltrim( $keyword, self::FILE_PREFIX . ' \r\t\v\0' );
				if ( ! empty( $keyword ) ) {
					$url_keywords[] = $keyword;
				}

				return $url_keywords;
			},
			array()
		);

		return array_unique( $excluded_url_keywords );
	}

	/**
	 * Get excluded other excluded keywords.
	 *
	 * @return array
	 */
	public function get_common_excluded_keywords() {
		if ( ! $this->common_excluded_keywords ) {
			$this->common_excluded_keywords = $this->prepare_common_excluded_keywords();
		}

		return $this->common_excluded_keywords;
	}

	/**
	 * Prepare other excluded keywords.
	 *
	 * @return array
	 */
	private function prepare_common_excluded_keywords() {
		$common_excluded_keywords = array_filter(
			$this->excluded_keywords,
			function ( $keyword ) {
				$is_id_or_class_name = str_starts_with( $keyword, self::ID_PREFIX )
										|| str_starts_with( $keyword, self::CLASS_PREFIX );
				$is_url_keyword      = str_starts_with( $keyword, self::FILE_PREFIX );

				return ! $is_id_or_class_name && ! $is_url_keyword;
			}
		);

		return array_unique( $common_excluded_keywords );
	}

	/**
	 * Check if URL has excluded keywords.
	 *
	 * @param string $url URL.
	 *
	 * @return bool
	 */
	public function is_url_excluded( $url ) {
		return $this->is_string_excluded( $url, $this->get_excluded_url_keywords() );
	}

	/**
	 * Check if markup has excluded attribute values.
	 *
	 * @param string $markup_html Markup HTML.
	 *
	 * @return bool
	 */
	public function is_markup_excluded( $markup_html ) {
		return $this->is_string_excluded( $markup_html );
	}

	/**
	 * Check if string has excluded keywords.
	 *
	 * @param string $str               String.
	 * @param array  $excluded_keywords Excluded keywords (sanitized).
	 *
	 * @return bool
	 */
	private function is_string_excluded( $str, $excluded_keywords = array() ) {
		if ( empty( $str ) || ! is_string( $str ) ) {
			return false;
		}

		$common_excluded_keywords = $this->get_common_excluded_keywords();
		$excluded_keywords        = array_merge( $common_excluded_keywords, (array) $excluded_keywords );

		if ( empty( $excluded_keywords ) ) {
			return false;
		}

		foreach ( $excluded_keywords as $excluded_keyword ) {
			if ( strpos( $str, $excluded_keyword ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if ID attribute is excluded.
	 *
	 * @param string $id_attribute ID attribute.
	 *
	 * @return bool
	 */
	public function is_id_attribute_excluded( $id_attribute ) {
		$excluded_ids = $this->get_excluded_ids();

		if ( empty( $excluded_ids ) || empty( $id_attribute ) || ! is_string( $id_attribute ) ) {
			return false;
		}

		$element_id = $this->add_id_prefix( $id_attribute );

		return in_array( $element_id, $excluded_ids, true );
	}

	/**
	 * Check if class attribute is excluded.
	 *
	 * @param string $class_attribute class attribute.
	 *
	 * @return bool
	 */
	public function is_class_attribute_excluded( $class_attribute ) {
		$excluded_classes = $this->get_excluded_classes();

		if ( empty( $excluded_classes ) || empty( $class_attribute ) || ! is_string( $class_attribute ) ) {
			return false;
		}

		$element_classes = explode( ' ', $class_attribute );
		foreach ( $element_classes as $element_class ) {
			$element_class = $this->add_class_prefix( $element_class );
			if ( in_array( $element_class, $excluded_classes, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get excluded IDs.
	 *
	 * @return array
	 */
	private function get_excluded_classes() {
		if ( ! $this->excluded_classes ) {
			$this->excluded_classes = $this->prepare_excluded_classes();
		}

		return $this->excluded_classes;
	}

	/**
	 * Prepare excluded ID attributes.
	 *
	 * @return array
	 */
	private function prepare_excluded_classes() {
		$excluded_classes = array_filter(
			$this->excluded_keywords,
			function ( $keyword ) {
				return str_starts_with( $keyword, self::CLASS_PREFIX );
			}
		);

		return array_unique( $excluded_classes );
	}


	/**
	 * Get excluded IDs.
	 *
	 * @return array
	 */
	private function get_excluded_ids() {
		if ( ! $this->excluded_ids ) {
			$this->excluded_ids = $this->prepare_excluded_ids();
		}

		return $this->excluded_ids;
	}

	/**
	 * Prepare excluded ID attributes.
	 *
	 * @return array
	 */
	private function prepare_excluded_ids() {
		$excluded_ids = array_filter(
			$this->excluded_keywords,
			function ( $keyword ) {
				return str_starts_with( $keyword, self::ID_PREFIX );
			}
		);

		return array_unique( $excluded_ids );
	}

	/**
	 * Add ID prefix.
	 *
	 * @param string $id_name ID attribute name.
	 *
	 * @return string
	 */
	private function add_id_prefix( $id_name ) {
		return self::ID_PREFIX . $id_name;
	}

	/**
	 * Add class prefix.
	 *
	 * @param mixed $class_name Class attribute name.
	 *
	 * @return string
	 */
	private function add_class_prefix( $class_name ) {
		return self::CLASS_PREFIX . $class_name;
	}
}
