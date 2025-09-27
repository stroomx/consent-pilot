<?php
/**
 * Consent Controller table.
 *
 * @package ConsentPilot
 */

namespace Frogrammer\ConsentPilot\Admin;

use Frogrammer\ConsentPilot\Settings\Settings;
use WP_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

class ConsentController extends \WP_List_Table {

	public $query_args = array(
		'status'  => 'active',
		'order'   => 'desc',
		'orderby' => 'id',
		'm'       => '',
		's'       => '',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'consentpilot-log',
				'plural'   => 'consentpilot-logs',
				'ajax'     => false,
			)
		);

		foreach ( array_keys( $this->query_args ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( 's' === $key ) {
					$this->query_args['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				} else {
					$val = sanitize_key( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					switch ( $key ) {
						case 'm':
							$this->query_args['m'] = preg_replace( '/[^0-9]/', '', $val );
							break;
						case 'orderby':
							if ( in_array( $val, array( 'consent_date', 'consent_expiry' ) ) ) {
								$this->query_args['orderby'] = $val;
							}
							break;
						case 'order':
							if ( in_array( $val, array( 'asc', 'desc' ) ) ) {
								$this->query_args['order'] = $val;
							}
							break;
						case 'status':
							if ( in_array( $val, array( 'active', 'inactive', 'all' ) ) ) {
								$this->query_args['status'] = $val;
							}
							break;
					}
				}
			}
		}
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'consent_date'   => __( 'Timestamp', 'consent-pilot' ),
			'consent_id'     => __( 'Consent ID', 'consent-pilot' ),
			'analytics'      => __( 'Analytics', 'consent-pilot' ),
			'marketing'      => __( 'Marketing', 'consent-pilot' ),
			'preferences'    => __( 'Preferences', 'consent-pilot' ),
			'ip_address'     => __( 'IP Address', 'consent-pilot' ),
			'user_agent'     => __( 'User Agent', 'consent-pilot' ),
			'consent_expiry' => __( 'Expiry', 'consent-pilot' ),
			'status'         => __( 'Status', 'consent-pilot' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'consent_date'   => array( 'consent_date', true ),
			'consent_expiry' => array( 'consent_expiry', true ),
		);
	}

	/**
	 * Views (All/Active/Inactive) with caching.
	 *
	 * @return array
	 */
	public function get_views() {
		global $wpdb;
		$table = $wpdb->prefix . CONSENTPILOT_PREFIX;

		$now_gmt = gmdate( 'Y-m-d H:i:s' );

		$counts = wp_cache_get( 'counts', CONSENTPILOT_PREFIX );
		if ( false === $counts ) {
			$counts = array(
				'all'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
				'active'   => (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table} WHERE consent_expiry > %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$now_gmt
					)
				),
				'inactive' => (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table} WHERE consent_expiry <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$now_gmt
					)
				),
			);

			// cache set
			wp_cache_set( 'counts', $counts, CONSENTPILOT_PREFIX );
		}

		$current  = $this->query_args['status'];
		$base_url = add_query_arg(
			array( 'tab' => 'logs' ),
			menu_page_url( CONSENTPILOT_SLUG, false )
		);

		$views = array(
			'active'   => __( 'Active', 'consent-pilot' ),
			'inactive' => __( 'Inactive', 'consent-pilot' ),
			'all'      => __( 'All', 'consent-pilot' ),
		);

		foreach ( $views as $key => &$label ) {
			$url   = add_query_arg( array( 'status' => $key ), $base_url );
			$class = ( $current === $key ) ? 'class="current"' : '';
			$count = isset( $counts[ $key ] ) ? (int) $counts[ $key ] : 0;

			$label = sprintf(
				'<a href="%1$s" %2$s>%3$s <span class="count">(%4$d)</span></a>',
				esc_url( $url ),
				$class,
				esc_html( $label ),
				$count
			);
		}

		return $views;
	}

	/**
	 * Default column rendering.
	 *
	 * @param array  $item
	 * @param string $column_name
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$consent_keys = array( 'analytics', 'marketing', 'preferences' );

		if ( in_array( $column_name, $consent_keys, true ) ) {
			$consent = array();

			if ( ! empty( $item['consent'] ) ) {
				$decoded = json_decode( (string) $item['consent'], true );
				if ( is_array( $decoded ) ) {
					$consent = $decoded;
				}
			}

			$val = isset( $consent[ $column_name ] ) ? $consent[ $column_name ] : null;

			switch ( $val ) {
				case "true":
					return '<span aria-hidden="true" style="color:green;">&check;</span><span class="screen-reader-text">' . esc_html__( 'Enabled', 'consent-pilot' ) . '</span>';
				case "false":
					return '<span aria-hidden="true" style="color:red;">&#10008;</span><span class="screen-reader-text">' . esc_html__( 'Disabled', 'consent-pilot' ) . '</span>';
			}
			return '<span aria-hidden="true">&ndash;</span><span class="screen-reader-text">' . esc_html__( 'Not set', 'consent-pilot' ) . '</span>';
		}

		switch ( $column_name ) {
			case 'consent_date':
			case 'consent_expiry':
				$time = strtotime( (string) $item[ $column_name ] );
				return $time ? esc_html( wp_date( 'Y-m-d H:i:s', $time ) ) : '';

			case 'consent_id':
			case 'ip_address':
				return esc_html( (string) $item[ $column_name ] );

			case 'user_agent':
				$ua = (string) ( $item['user_agent'] ?? '' );
				return '<span title="' . esc_attr( $ua ) . '">' . esc_html( mb_strimwidth( $ua, 0, 48, '…' ) ) . '</span>';

			case 'status':
				$is_active = ( strtotime( (string) $item['consent_expiry'] ) > current_time( 'timestamp', true ) );
				$status    = $is_active ? __( 'Active', 'consent-pilot' ) : __( 'Inactive', 'consent-pilot' );
				$colour    = $is_active ? 'green' : 'gray';
				return '<span style="color:' . esc_attr( $colour ) . '">' . esc_html( $status ) . '</span>';
		}

		return '';
	}

	/**
	 * Build WHERE clause from filters/search.
	 *
	 * @return array{where_sql:string,params:array}
	 */
	private function filter_query() {
		global $wpdb;

		$where  = array();
		$params = array();

		// Status tab.
		$now_gmt = gmdate( 'Y-m-d H:i:s' );
		if ( 'active' === $this->query_args['status'] ) {
			$where[] = 'consent_expiry > %s';
			$params[] = $now_gmt;
		} elseif ( 'inactive' === $this->query_args['status'] ) {
			$where[] = 'consent_expiry <= %s';
			$params[] = $now_gmt;
		}

		// Search (ID or IP).
		$search = $this->query_args['s'];
		if ( '' !== $search ) {
			$where[]  = '(consent_id LIKE %s OR ip_address LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		// Month dropdown (YYYYMM).
		if ( ! empty( $this->query_args['m'] ) ) {
			$year  = (int) substr( $this->query_args['m'], 0, 4 );
			$month = (int) substr( $this->query_args['m'], 4, 2 );
			if ( $year > 0 && $month > 0 && $month <= 12 ) {
				$where[]  = 'YEAR(consent_date) = %d AND MONTH(consent_date) = %d';
				$params[] = $year;
				$params[] = $month;
			}
		}

		return array(
			'where_sql' => $where ? 'WHERE ' . implode( ' AND ', $where ) : '',
			'params'    => $params,
		);
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;
		$table = $wpdb->prefix . CONSENTPILOT_PREFIX;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = 20;
		$paged    = max( 1, (int) $this->get_pagenum() );
		$offset   = ( $paged - 1 ) * $per_page;

		$filter_query = $this->filter_query();
		$orderby = sanitize_sql_orderby( "{$this->query_args['orderby']} {$this->query_args['order']}");

		// Build & run the main query
		$cache_key = sprintf(
			'items:%s:%s:%s:%s:%d',
			$this->query_args['status'],
			$orderby,
			$this->query_args['s'],
			$this->query_args['m'],
			$paged
		);
		$items = wp_cache_get( $cache_key, CONSENTPILOT_PREFIX );
		$total_items = wp_cache_get( $cache_key . ':total', CONSENTPILOT_PREFIX );

		if ( false === $items || false === $total_items ) {
			// Main query
			$sql        = "SELECT * FROM {$table} {$filter_query['where_sql']} ORDER BY %s LIMIT %d OFFSET %d";
			$all_params = array_merge( $filter_query['params'], array( $orderby, $per_page, $offset ) );
			$items      = $wpdb->get_results( $wpdb->prepare( $sql, ...$all_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

			$total_sql   = "SELECT COUNT(*) FROM {$table} {$filter_query['where_sql']}";
			$total_items = $filter_query['params'] ? (int) $wpdb->get_var( $wpdb->prepare( $total_sql, ...$filter_query['params'] ) ) : (int) $wpdb->get_var( $total_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

			wp_cache_set( $cache_key, $items, CONSENTPILOT_PREFIX );
			wp_cache_set( $cache_key . ':total', $total_items, CONSENTPILOT_PREFIX );
		}

		$months = wp_cache_get( 'months', CONSENTPILOT_PREFIX );
		if ( false === $months ) {
			// Build months list (like Posts screen).
			$months = $wpdb->get_results( "SELECT DISTINCT YEAR(consent_date) AS year, MONTH(consent_date) AS month FROM {$table} ORDER BY consent_date DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			wp_cache_set( 'months', $months, CONSENTPILOT_PREFIX );
		}

		add_filter(
			'months_dropdown_results',
			static function () use ( $months ) {
				$results = array();
				foreach ( (array) $months as $m ) {
					if ( empty( $m->year ) || empty( $m->month ) ) {
						continue;
					}
					$ym        = sprintf( '%04d%02d', (int) $m->year, (int) $m->month );
					$results[] = (object) array(
						'year'  => (int) $m->year,
						'month' => (int) $m->month,
						'text'  => date_i18n( 'F Y', mktime( 0, 0, 0, (int) $m->month, 1, (int) $m->year ) ),
						'value' => $ym,
					);
				}
				return $results;
			}
		);

		$this->items = $items;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Extra controls above/below the table.
	 *
	 * @param string $which
	 */
	public function extra_tablenav( $which ) {
		// Rebuild current query safely for export link.
		$current_query = array();

		$export_url = wp_nonce_url(
			add_query_arg(
				array_merge(
					array( 'action' => 'consentpilot_export' ),
					$this->query_args
				),
				admin_url( 'admin-post.php' )
			),
			'consentpilot_export',
			'_wpnonce'
		);

		if ( 'top' === $which ) :
			$months = apply_filters( 'months_dropdown_results', array() );
			?>
			<div class="alignleft actions">
				<label for="filter-by-date" class="screen-reader-text"><?php esc_html_e( 'Filter by date', 'consent-pilot' ); ?></label>
				<select name="m" id="filter-by-date">
					<option value="0"><?php esc_html_e( 'All dates', 'consent-pilot' ); ?></option>
					<?php foreach ( (array) $months as $month ) : ?>
						<option <?php selected( $m, $month->value, true ); ?> value="<?php echo esc_attr( $month->value ); ?>">
							<?php echo esc_html( $month->text ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( esc_html__( 'Filter', 'consent-pilot' ), '', '', false ); ?>
				<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Export as CSV', 'consent-pilot' ); ?>
				</a>
			</div>
			<?php
		endif;
	}

	/**
	 * CSV Export (respects current filters).
	 *
	 * @return void
	 */
	public function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
		}
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'consentpilot_export' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'consent-pilot' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . CONSENTPILOT_PREFIX;

		$filter_query = $this->filter_query();
		$sql          = "SELECT * FROM {$table} {$filter_query['where_sql']} ORDER BY consent_date DESC";
		$results      = $filter_query['params']
			? $wpdb->get_results( $wpdb->prepare( $sql, ...$filter_query['params'] ), ARRAY_A ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
			: $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		if ( ob_get_length() ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="consent_sessions.csv"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo "\xEF\xBB\xBF"; // BOM for Excel.

		$columns = $this->get_columns();
		$output  = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		fputcsv(
			$output,
			array(
				$columns['consent_date'],
				$columns['consent_id'],
				$columns['analytics'],
				$columns['marketing'],
				$columns['preferences'],
				$columns['ip_address'],
				$columns['user_agent'],
				__( 'Consent Expiry', 'consent-pilot' ),
				$columns['status'],
			),
			',',
			'"',
			'\\'
		);

		foreach ( (array) $results as $item ) {
			$consent = array();
			if ( ! empty( $item['consent'] ) ) {
				$decoded = json_decode( (string) $item['consent'], true );
				if ( is_array( $decoded ) ) {
					$consent = $decoded;
				}
			}

			$consent_date = strtotime( (string) $item['consent_date'] );
			$consent_date = wp_date( 'Y-m-d H:i:s', $consent_date );
			$consent_expiry = strtotime( (string) $item['consent_expiry'] );
			$status = ( $consent_expiry > current_time( 'timestamp', true ) )
				? __( 'Active', 'consent-pilot' )
				: __( 'Inactive', 'consent-pilot' );

			$consent_expiry = wp_date( 'Y-m-d H:i:s', $consent_expiry );
			fputcsv(
				$output,
				array(
					esc_html( $consent_date ),
					(string) $item['consent_id'],
					isset( $consent['analytics'] ) ? (int) ( (bool) $consent['analytics'] ) : '',
					isset( $consent['marketing'] ) ? (int) ( (bool) $consent['marketing'] ) : '',
					isset( $consent['preferences'] ) ? (int) ( (bool) $consent['preferences'] ) : '',
					(string) $item['ip_address'],
					(string) $item['user_agent'],
					esc_html( $consent_expiry ),
					esc_html( $status ),
				),
				',',
				'"',
				'\\'
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Insert consent record & mark old one expired.
	 *
	 * @param array|object $consent
	 * @return array
	 */
	public static function add_consent_records( $consent ) {
		$consent = (object) $consent;

		// Sanitize JSON passed by JavaScript
		foreach ( $consent as $k => $v ) {
			$consent->$k = sanitize_key( wp_unslash( $v ) );
		}

		if ( empty( $consent->id ) ) {
			return array( 'status' => 'fail' );
		}

		global $wpdb;
		$table = $wpdb->prefix . CONSENTPILOT_PREFIX;

		if ( ! empty( $consent->expired_id ) ) {
			$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE consent_id = %s", (string) $consent->expired_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			
			if ( $exists ) {
				$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$table,
					array( 'consent_expiry' => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) ) ),
					array( 'consent_id' => (string) $consent->expired_id ),
					array( '%s' ),
					array( '%s' )
				);
			}
		}

		$ip = self::get_ip_address();
		$consent_expiry = strtotime( "+" . absint( Settings::$options['consent_validity'] ) . "days", current_time( 'timestamp', true ) );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array(
				'consent_id'     => (string) $consent->id,
				'consent'        => wp_json_encode( isset( $consent->prefs ) ? $consent->prefs : array() ),
				'ip_address'     => $ip,
				'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'consent_date'	 => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) ),
				'consent_expiry' => gmdate( 'Y-m-d H:i:s', $consent_expiry ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		wp_cache_flush_group( CONSENTPILOT_PREFIX );
		
		return array(
			'status' => 'success',
			'expiry' => $consent_expiry,
		);
	}

	/**
	 * Get client IP (respect settings; validate format).
	 *
	 * @return string
	 */
	private static function get_ip_address() {
		if ( empty( Settings::$options['log_ip'] ) ) {
			return __( 'N/A', 'consent-pilot' );
		}

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = trim( (string) wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$list = explode( ',', (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$ip   = trim( $list[0] ?? '' );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		// Validate IPv4/IPv6.
		if ( isset( $ip ) && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return __( 'N/A', 'consent-pilot' );
	}
}
