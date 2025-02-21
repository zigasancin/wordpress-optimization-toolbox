<?php

namespace Smush\Core;

class Format_Utils {
	public function convert_to_megabytes( $size_in_bytes ) {
		if ( empty( $size_in_bytes ) ) {
			return 0;
		}
		$unit_mb = pow( 1024, 2 );
		return round( $size_in_bytes / $unit_mb, 2 );
	}
}
