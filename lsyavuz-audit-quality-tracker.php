<?php
/**
 * Plugin Name: Lsyavuz Audit & Quality Tracker
 * Description: A quality control tool that logs content, plugin, and system activities.
 * Version: 1.0.0
 * Author: Levent Sadık Yavuz
 * License: GPL v2 or later
 * Text Domain: lsyavuz-audit-quality-tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPLAT_VERSION', '1.0.0' );
define( 'WPLAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, 'wplat_create_audit_table' );

function wplat_create_audit_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wplat_audit_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        action_type varchar(50) NOT NULL,
        object_type varchar(50) NOT NULL,
        object_name varchar(255) NOT NULL,
        action_details text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

function wplat_insert_log( $action_type, $object_type, $object_name, $details = '' ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wplat_audit_logs';
    $user_id    = get_current_user_id();
    $action_type = sanitize_text_field( $action_type );
    $object_type = sanitize_text_field( $object_type );
    $object_name = sanitize_text_field( $object_name );
    $details     = sanitize_textarea_field( $details );

    $wpdb->insert(
        $table_name,
        array(
            'user_id'        => $user_id,
            'action_type'    => $action_type,
            'object_type'    => $object_type,
            'object_name'    => $object_name,
            'action_details' => $details,
            'created_at'     => current_time( 'mysql' )
        ),
        array( '%d', '%s', '%s', '%s', '%s', '%s' )
    );
}

add_action( 'save_post', 'wplat_log_post_changes', 10, 3 );
function wplat_log_post_changes( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( $post->post_status === 'auto-draft' ) return;

    $ignored_types = array( 'nav_menu_item', 'wp_global_styles', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation' );
    if ( in_array( $post->post_type, $ignored_types ) ) return;

    $action = $update ? 'updated' : 'created';
    wplat_insert_log( $action, $post->post_type, $post->post_title );
}

add_action( 'wp_trash_post', 'wplat_log_post_trash' );
function wplat_log_post_trash( $post_id ) {
    $post = get_post( $post_id );
    if ( $post ) {
        wplat_insert_log( 'trashed', $post->post_type, $post->post_title );
    }
}

add_action( 'activated_plugin', 'wplat_log_plugin_activation', 10, 2 );
function wplat_log_plugin_activation( $plugin, $network_wide ) {
    wplat_insert_log( 'activated', 'plugin', $plugin );
}

add_action( 'deactivated_plugin', 'wplat_log_plugin_deactivation', 10, 2 );
function wplat_log_plugin_deactivation( $plugin, $network_wide ) {
    wplat_insert_log( 'deactivated', 'plugin', $plugin );
}

add_action( 'wp_login', 'wplat_log_user_login', 10, 2 );
function wplat_log_user_login( $user_login, $user ) {
    wplat_insert_log( 'logged_in', 'user', $user_login );
}

add_action( 'admin_menu', 'wplat_add_admin_menu' );
function wplat_add_admin_menu() {
    add_menu_page(
        __( 'Audit & Quality Reports', 'lsyavuz-audit-quality-tracker' ), 
        __( 'Quality Audit', 'lsyavuz-audit-quality-tracker' ),
        'manage_options',
        'wplat-dashboard',
        'wplat_render_admin_page',
        'dashicons-chart-pie',
        30
    );
}

add_action( 'admin_enqueue_scripts', 'wplat_enqueue_admin_assets' );
function wplat_enqueue_admin_assets( $hook ) {
    if ( $hook != 'toplevel_page_wplat-dashboard' ) {
        return;
    }

    wp_enqueue_script( 'chart-js', plugin_dir_url( __FILE__ ) . 'assets/js/chart.min.js', array(), '3.9.1', true );
    wp_enqueue_style( 'lsaqt-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css', array(), '1.0.0' );
    wp_enqueue_script( 'lsaqt-admin-script', plugin_dir_url( __FILE__ ) . 'assets/js/admin-script.js', array('chart-js'), '1.0.0', true );
    wp_localize_script( 'lsaqt-admin-script', 'wplat_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'wplat_dashboard_nonce' )
    ));
}

function wplat_render_admin_page() {
    ?>
    <div class="wrap wplat-wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Site Audit and Changes Report', 'lsyavuz-audit-quality-tracker' ); ?></h1>
        <hr class="wp-header-end">

        <div class="wplat-filters">
            <button class="wplat-filter-btn active" data-range="daily"><?php esc_html_e( 'Daily', 'lsyavuz-audit-quality-tracker' ); ?></button>
            <button class="wplat-filter-btn" data-range="weekly"><?php esc_html_e( 'Weekly', 'lsyavuz-audit-quality-tracker' ); ?></button>
            <button class="wplat-filter-btn" data-range="monthly"><?php esc_html_e( 'Monthly', 'lsyavuz-audit-quality-tracker' ); ?></button>
            <button class="wplat-filter-btn" data-range="yearly"><?php esc_html_e( 'Yearly', 'lsyavuz-audit-quality-tracker' ); ?></button>
        </div>

        <div class="wplat-dashboard-grid">
            <div class="wplat-card">
                <h3><?php esc_html_e( 'Activity Distribution', 'lsyavuz-audit-quality-tracker' ); ?></h3>
                <canvas id="wplatActivityChart" width="400" height="400"></canvas>
            </div>
            <div class="wplat-card">
                <h3><?php esc_html_e( 'Recent Records', 'lsyavuz-audit-quality-tracker' ); ?></h3>
                <div id="wplat-table-container">
                    <p style="color:#666;"><?php esc_html_e( 'Loading data...', 'lsyavuz-audit-quality-tracker' ); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

add_action( 'wp_ajax_wplat_get_dashboard_data', 'wplat_ajax_get_dashboard_data' );

function wplat_ajax_get_dashboard_data() {
    check_ajax_referer( 'wplat_dashboard_nonce', 'security' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'wplat_audit_logs';
    $range = isset( $_POST['range'] ) ? sanitize_text_field( $_POST['range'] ) : 'daily';
    
    $interval = '1 DAY';
    if ( $range === 'weekly' ) $interval = '1 WEEK';
    if ( $range === 'monthly' ) $interval = '1 MONTH';
    if ( $range === 'yearly' ) $interval = '1 YEAR';

    $date_query = "AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)";

    $chart_data = array(0, 0, 0, 0);
    
    $counts = $wpdb->get_results( "
        SELECT action_type, COUNT(*) as count 
        FROM $table_name 
        WHERE 1=1 $date_query 
        GROUP BY action_type
    " );

    foreach ( $counts as $row ) {
        if ( $row->action_type === 'created' ) $chart_data[0] = (int)$row->count;
        if ( $row->action_type === 'updated' ) $chart_data[1] = (int)$row->count;
        if ( $row->action_type === 'trashed' ) $chart_data[2] = (int)$row->count;
        if ( $row->action_type === 'logged_in' ) $chart_data[3] = (int)$row->count;
    }

    $logs = $wpdb->get_results( "
        SELECT * FROM $table_name 
        WHERE 1=1 $date_query 
        ORDER BY created_at DESC 
        LIMIT 15
    " );

    $table_html = '<table class="wplat-table">';
    $table_html .= '<thead><tr><th>Date</th><th>Subject</th><th>Transaction</th><th>User ID</th></tr></thead>';
    $table_html .= '<tbody>';

    if ( $logs ) {
        foreach ( $logs as $log ) {
            $badge_class = 'badge-updated';
            $action_text = 'Updated';
            
            if ( $log->action_type === 'created' ) { $badge_class = 'badge-created'; $action_text = 'Created'; }
            if ( $log->action_type === 'trashed' || $log->action_type === 'deleted' ) { $badge_class = 'badge-deleted'; $action_text = 'Trashed'; }
            if ( $log->action_type === 'logged_in' ) { $badge_class = 'badge-login'; $action_text = 'Logged In'; }
            if ( $log->action_type === 'activated' || $log->action_type === 'deactivated' ) { $badge_class = 'badge-login'; $action_text = 'Plugin Installation'; }

            $formatted_date = date( 'd.m.Y H:i', strtotime( $log->created_at ) );

            $table_html .= '<tr>';
            $table_html .= '<td>' . esc_html( $formatted_date ) . '</td>';
            $table_html .= '<td><strong>' . esc_html( $log->object_name ) . '</strong> <span style="color:#888; font-size:11px;">(' . esc_html( $log->object_type ) . ')</span></td>';
            $table_html .= '<td><span class="wplat-badge ' . $badge_class . '">' . $action_text . '</span></td>';
            $table_html .= '<td>' . esc_html( $log->user_id ) . '</td>';
            $table_html .= '</tr>';
        }
    } else {
        $table_html .= '<tr><td colspan="4">' . esc_html__( 'No records found in this date range.', 'lsyavuz-audit-quality-tracker' ) . '</td></tr>';
    }
    
    $table_html .= '</tbody></table>';

    wp_send_json_success( array(
        'chart' => $chart_data,
        'table' => $table_html
    ));
}