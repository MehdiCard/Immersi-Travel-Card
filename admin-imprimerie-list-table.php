<?php
// includes/admin-imprimerie-list-table.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Classe pour afficher la table Imprimerie dans l'admin
 */
class Itc_Imprimerie_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __( 'Commande', 'immersi-travel-card' ),
            'plural'   => __( 'Commandes', 'immersi-travel-card' ),
            'ajax'     => false,
        ]);
    }

    /**
     * Colonnes de la table
     */
    public function get_columns() {
        return [
            'cb'        => '<input type="checkbox" />',
            'revendeur' => __( 'Revendeur', 'immersi-travel-card' ),
            'quantity'  => __( 'Quantité', 'immersi-travel-card' ),
            'date'      => __( 'Date', 'immersi-travel-card' ),
            'exported'  => __( 'Envoyé à l’imprimerie', 'immersi-travel-card' ),
            'received'  => __( 'Livré', 'immersi-travel-card' ),
            'actions'   => __( 'Actions', 'immersi-travel-card' ),
        ];
    }

    /**
     * Colonnes triables
     */
    public function get_sortable_columns() {
        return [
            'revendeur' => ['revendeur', true],
            'quantity'  => ['quantity', false],
            'date'      => ['date', false],
        ];
    }

    /**
     * Case à cocher pour chaque ligne
     */
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="order_ids[]" value="%d" />',
            $item->ID
        );
    }

    /**
     * Affichage par défaut des colonnes
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'revendeur':
                $rev_id = get_post_meta( $item->ID, '_itc_order_revendeur', true );
                $user   = get_userdata( $rev_id );
                $name   = $user ? trim( $user->first_name . ' ' . $user->last_name ) : $rev_id;
                return esc_html( $name );

            case 'quantity':
                return absint( get_post_meta( $item->ID, '_itc_order_quantity', true ) );

            case 'date':
                return date_i18n(
                    get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                    strtotime( $item->post_date )
                );

            case 'exported':
                $checked = get_post_meta( $item->ID, '_itc_exported', true ) ? 'checked' : '';
                return sprintf(
                    '<input type="checkbox" class="itc-toggle-status" data-order_id="%d" data-field="_itc_exported" %s />',
                    $item->ID,
                    $checked
                );

            case 'received':
                $checked = get_post_meta( $item->ID, '_itc_order_received', true ) ? 'checked' : '';
                return sprintf(
                    '<input type="checkbox" class="itc-toggle-status" data-order_id="%d" data-field="_itc_order_received" %s />',
                    $item->ID,
                    $checked
                );

            case 'actions':
                $view_url = admin_url( 'admin.php?page=itc-imprimerie&order_id=' . $item->ID );
                return sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( $view_url ),
                    esc_html__( 'Voir', 'immersi-travel-card' )
                );

            default:
                return '';
        }
    }

    /**
     * Actions en masse disponibles
     */
    public function get_bulk_actions() {
        return [
            'export_pdf' => __( 'Export PDF', 'immersi-travel-card' ),
            'export_csv' => __( 'Export CSV', 'immersi-travel-card' ),
            'delete'     => __( 'Supprimer', 'immersi-travel-card' ),
        ];
    }

    /**
     * Traitement des actions en masse
     */
    public function process_bulk_action() {
        if ( 'export_pdf' === $this->current_action() && ! empty( $_POST['order_ids'] ) ) {
            $ids      = array_map( 'intval', $_POST['order_ids'] );
            $redirect = admin_url( 'admin-post.php?action=itc_export_imprimerie_pdf&ids=' . implode( ',', $ids ) );
            wp_redirect( $redirect );
            exit;
        }

        if ( 'export_csv' === $this->current_action() && ! empty( $_POST['order_ids'] ) ) {
            $ids      = array_map( 'intval', $_POST['order_ids'] );
            $redirect = admin_url( 'admin-post.php?action=itc_export_imprimerie_csv&ids=' . implode( ',', $ids ) );
            wp_redirect( $redirect );
            exit;
        }

        if ( 'delete' === $this->current_action() && ! empty( $_POST['order_ids'] ) ) {
            $ids = array_map( 'intval', $_POST['order_ids'] );
            foreach ( $ids as $order_id ) {
                wp_trash_post( $order_id );
            }
        }
    }

    /**
     * Récupère les commandes pour la page
     */
    public static function get_order_data( $per_page = 20, $page_number = 1, $orderby = 'date', $order = 'DESC' ) {
        $args = [
            'post_type'      => 'itc_card_order',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'offset'         => ( $page_number - 1 ) * $per_page,
            'orderby'        => in_array( $orderby, ['revendeur', 'quantity', 'date'] ) ? $orderby : 'date',
            'order'          => in_array( strtoupper( $order ), ['ASC', 'DESC'] ) ? $order : 'DESC',
        ];
        $query = new WP_Query( $args );
        return $query->posts;
    }

    /**
     * Prépare les données pour l'affichage
     */
    public function prepare_items() {
        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
        $this->process_bulk_action();

        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $orderby      = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'date';
        $order        = isset( $_REQUEST['order'] ) ? sanitize_text_field( $_REQUEST['order'] ) : 'DESC';

        $this->items = self::get_order_data( $per_page, $current_page, $orderby, $order );

        $total_items = wp_count_posts( 'itc_card_order' )->publish;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ]);
    }
}
