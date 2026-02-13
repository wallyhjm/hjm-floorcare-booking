<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('WP_List_Table') ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HJM_Floorcare_Capacity_List_Table extends WP_List_Table {

    public function get_columns() {
        return [
            'service_date'  => 'Date',
            'is_closed'     => 'Closed',
            'total_minutes' => 'Total Minutes',
            'remaining'     => 'Remaining',
            'override'      => 'Override',
            'actions'       => 'Actions',
        ];
    }

    public function prepare_items() {
        global $wpdb;

        $table = $wpdb->prefix . 'hjm_floorcare_daily_capacity';

        $rows = $wpdb->get_results(
            "SELECT service_date, total_minutes, is_closed, is_override
             FROM {$table}
             ORDER BY service_date ASC",
            ARRAY_A
        );

        foreach ( $rows as &$row ) {
            $avail = hjm_floorcare_get_daily_availability( $row['service_date'] );
            $row['remaining'] = $avail['remaining'];
            $row['row_class'] = ! empty( $row['is_override'] ) ? 'hjm-capacity-override' : '';
        }

        $this->items = $rows;
        $this->_column_headers = [ $this->get_columns(), [], [] ];
    }

    protected function column_default( $item, $column_name ) {
        return isset( $item[ $column_name ] )
            ? esc_html( $item[ $column_name ] )
            : '';
    }

    protected function column_service_date( $item ) {
        return date( 'F j, Y', strtotime( $item['service_date'] ) );
    }

    protected function column_is_closed( $item ) {
        return $item['is_closed']
            ? '<span style="color:#b32d2e;font-weight:600;">Closed</span>'
            : 'Open';
    }

    protected function column_override( $item ) {
        return $item['is_override']
            ? '<span style="color:#b36b00;font-weight:600;">Yes</span>'
            : '-';
    }

    public function single_row( $item ) {
        $class = ! empty( $item['row_class'] ) ? $item['row_class'] : '';
        echo '<tr class="' . esc_attr( $class ) . '">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    protected function column_actions( $item ) {

        return sprintf(
            '<button
            type="button"
            class="button hjm-edit-capacity"
            data-date="%s"
            data-minutes="%d"
            data-closed="%d"
        >
            Edit
        </button>',
            esc_attr( $item['service_date'] ),
            (int) $item['total_minutes'],
            (int) $item['is_closed']
        );
    }
}
