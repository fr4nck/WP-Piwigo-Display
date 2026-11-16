<?php
/**
 * Bounded Piwigo API traversal budget.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shares network, time and image-volume limits across one gallery traversal.
 */
final class WPD_Request_Budget {
	/**
	 * Upper bound for POST and fallback GET requests in one traversal.
	 */
	private const MAX_HTTP_CALLS = 32;

	/**
	 * Maximum number of image rows accepted, including duplicates.
	 */
	private const MAX_IMAGE_ROWS = 10000;

	/**
	 * Maximum elapsed time, including calls with an HTTP 403 fallback.
	 */
	private const MAX_ELAPSED_SECONDS = 25;

	/**
	 * Max response body size set on WordPress HTTP requests.
	 */
	public const MAX_RESPONSE_BYTES = 8 * 1024 * 1024;

	/**
	 * Traversal start time.
	 *
	 * @var float
	 */
	private float $started_at;

	/**
	 * Total reserved HTTP calls.
	 *
	 * @var int
	 */
	private int $http_calls = 0;

	/**
	 * Total image rows read from pages, including duplicates.
	 *
	 * @var int
	 */
	private int $image_rows = 0;

	/**
	 * Begins one traversal. An explicit timestamp allows deterministic tests.
	 *
	 * @param float|null $started_at Optional start timestamp.
	 */
	public function __construct( ?float $started_at = null ) {
		$this->started_at = null === $started_at ? microtime( true ) : $started_at;
	}

	/**
	 * Charges one HTTP request and returns its remaining timeout.
	 *
	 * @return int|WP_Error Timeout, or a fail-closed budget error.
	 */
	public function reserve_http_call() {
		$remaining = self::MAX_ELAPSED_SECONDS - ( microtime( true ) - $this->started_at );
		if ( 1 > $remaining || self::MAX_HTTP_CALLS <= $this->http_calls ) {
			return self::error();
		}

		++$this->http_calls;
		return min( 10, (int) floor( $remaining ) );
	}

	/**
	 * Charges all records in one API page, before they enter any image array.
	 *
	 * @param int $rows Number of returned image records.
	 * @param int $page_size Requested maximum page size.
	 * @return bool|WP_Error True if the page remains inside budget.
	 */
	public function accept_page( int $rows, int $page_size ) {
		if ( 0 > $rows || $rows > $page_size || self::MAX_IMAGE_ROWS - $this->image_rows < $rows ) {
			return self::error();
		}

		$this->image_rows += $rows;
		return true;
	}

	/**
	 * Returns a stable WP_Error, never an incomplete list of gallery photos.
	 *
	 * @return WP_Error Budget exceeded.
	 */
	private static function error(): WP_Error {
		return new WP_Error(
			'wpd_api_budget_exceeded',
			__( 'La galerie Piwigo dépasse les limites de chargement autorisées. Réduisez la sélection ou le nombre d’albums.', 'wp-piwigo-display' )
		);
	}
}
