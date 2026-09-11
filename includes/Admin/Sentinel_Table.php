<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Sentinel event table.
 *
 * Displays ClientGuard Sentinel events for the current site.
 *
 * @since 1.7.0
 * 
 * @package Plugiva_ClientGuard
 */
class PCGD_Admin_Sentinel_Table extends WP_List_Table {

    /**
     * Sentinel table name.
     *
     * @var string
     */
    private $table_name;

	/**
	 * Constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
        global $wpdb;

        $this->table_name = $wpdb->prefix . 'pcgd_sentinel';

        parent::__construct(
            array(
                'singular' => 'sentinel_event',
                'plural'   => 'sentinel_events',
                'ajax'     => false,
            )
        );
    }

	/**
	 * Get table columns.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public function get_columns() {

		return array(
			'created_at'    => esc_html__( 'Time', 'plugiva-clientguard' ),
            'user'          => esc_html__( 'User', 'plugiva-clientguard' ),
			'category'      => esc_html__( 'Category', 'plugiva-clientguard' ),
			'details'       => esc_html__( 'Details', 'plugiva-clientguard' ),
		);
	}

    /**
     * Render the Time column.
     *
     * @since 1.7.0
     *
     * @param object $item Sentinel event.
     * @return string
     */
    public function column_created_at( $item ) {

        $timestamp = strtotime( $item->created_at );

        if ( ! $timestamp ) {
            return esc_html( $item->created_at );
        }

        return esc_html(
            wp_date(
                'M d, Y \a\t H:i:s \U\T\C',
                $timestamp,
                new DateTimeZone( 'UTC' )
            )
        );
    }

    /**
     * Render the User column.
     *
     * @since 1.7.0
     *
     * @param object $item Sentinel event.
     * @return string
     */
    public function column_user( $item ) {

        $user_id = absint( $item->user_id );

        if ( ! $user_id ) {
            return esc_html__( 'System', 'plugiva-clientguard' );
        }

        $user = get_userdata( $user_id );

        if ( ! $user ) {
            return esc_html__( 'Unknown', 'plugiva-clientguard' );
        }

        return esc_html( $user->display_name );
    }

    /**
     * Render the Category column.
     *
     * @since 1.7.0
     *
     * @param object $item Sentinel event.
     * @return string
     */
    public function column_category( $item ) {
        return esc_html( $item->category );
    }

    /**
     * Render the Event column.
     *
     * @since 1.7.0
     *
     * @param object $item Sentinel event.
     * @return string
     */
    public function column_event( $item ) {
        return esc_html( $item->event );
    }

    /**
     * Render the Context column.
     *
     * @since 1.7.0
     *
     * @param object $item Sentinel event.
     * @return string
     */
    public function column_context( $item ) {
        return esc_html( $item->context );
    }

    /**
     * Render the Target column.
     *
     * @since 1.7.0
     *
     * @param object $item Sentinel event.
     * @return string
     */
    public function column_target( $item ) {
        return esc_html( $item->target );
    }

    /**
     * Render the Details column.
     *
     * @since 1.7.0
     *
     * @param object $item Sentinel event.
     * @return string
     */
    public function column_details( $item ) {

        $details = json_decode( $item->details, true );

        if ( ! is_array( $details ) || ! isset( $details['text'] ) ) {
            return '';
        }

        return esc_html( $details['text'] );
    }

    /**
     * Render a single Sentinel table row.
     *
     * @since 1.7.0
     *
     * @param object $item Sentinel event.
     * @return void
     */
    public function single_row( $item ) {

        $row_class = '';

        if ( 'client_mode' === $item->context ) {
            $row_class = 'pcgd-sentinel-client-mode';
        }

        echo '<tr';
        if ( '' !== $row_class ) {
            echo ' class="' . esc_attr( $row_class ) . '"';
        }
        echo '>';

        $this->single_row_columns( $item );

        echo '</tr>';
    }

    /**
     * Get table classes.
     *
     * Removes the default striped row styling.
     *
     * @since 1.7.0
     *
     * @return array
     */
    protected function get_table_classes() {

        return array(
            'widefat',
            'fixed',
            $this->_args['plural'],
        );
    }

	/**
     * Prepare table items.
     *
     * Retrieves Sentinel events for the current site and prepares
     * pagination data for the WordPress list table.
     *
     * @since 1.7.0
     *
     * @return void
     */
    public function prepare_items() {
        global $wpdb;

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            array(),
        );

        $per_page       = 20;
        $current_page   = $this->get_pagenum();
        $offset         = ( $current_page - 1 ) * $per_page;

        $blog_id        = absint( get_current_blog_id() );

        $table_name = $this->table_name;

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted Sentinel table name; %i requires WordPress 6.2+.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom Sentinel table requires a direct query and should reflect current audit data.
        $total_items = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$table_name}
                WHERE blog_id = %d",
                $blog_id
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted Sentinel table name; %i requires WordPress 6.2+.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom Sentinel table requires a direct query and should reflect current audit data.
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, blog_id, user_id, category, event, context, target, details, created_at
                FROM {$table_name}
                WHERE blog_id = %d
                ORDER BY created_at DESC, id DESC
                LIMIT %d OFFSET %d",
                $blog_id,
                $per_page,
                $offset
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => (int) ceil( $total_items / $per_page ),
            )
        );
    }


}