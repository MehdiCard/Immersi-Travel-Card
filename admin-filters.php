<?php
// includes/admin-filters.php
// Ajout de filtres “Statut”, “Revendeur” et “Date d’activation” à la liste Cartes ITC

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Affiche les filtres au-dessus de la liste des CPT itc_card
 */
function itc_register_admin_filters() {
    global $typenow, $wpdb;

    if ( $typenow !== 'itc_card' ) {
        return;
    }

    // Statuts définis pour le plugin
    $statuses = array(
        ''           => __( 'Tous statuts',       'immersi-travel-card' ),
        'non_active' => __( 'Non activée',        'immersi-travel-card' ),
        'active'     => __( 'Active',             'immersi-travel-card' ),
        'expired'    => __( 'Expirée',            'immersi-travel-card' ),
    );
    $current_status = isset( $_GET['itc_filter_status'] ) ? sanitize_text_field( $_GET['itc_filter_status'] ) : '';

    echo '<select name="itc_filter_status">';
    foreach ( $statuses as $value => $label ) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr( $value ),
            selected( $current_status, $value, false ),
            esc_html( $label )
        );
    }
    echo '</select> ';

    // Revendeurs : récupérer tous les IDs distincts enregistrés en meta
    $rev_ids = $wpdb->get_col( "
        SELECT DISTINCT meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_itc_revendeur'
          AND meta_value <> ''
    " );
    $current_rev = isset( $_GET['itc_filter_revendeur'] ) ? sanitize_text_field( $_GET['itc_filter_revendeur'] ) : '';

    echo '<select name="itc_filter_revendeur">';
    echo '<option value="">' . esc_html__( 'Tous revendeurs', 'immersi-travel-card' ) . '</option>';
    foreach ( $rev_ids as $rev_id ) {
        $user = get_userdata( intval( $rev_id ) );
        if ( ! $user ) {
            continue;
        }
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr( $rev_id ),
            selected( $current_rev, $rev_id, false ),
            esc_html( $user->display_name )
        );
    }
    echo '</select> ';

    // Filtre date d’activation (exacte)
    $current_date = isset( $_GET['itc_filter_date'] ) ? sanitize_text_field( $_GET['itc_filter_date'] ) : '';
    printf(
        '<input type="date" name="itc_filter_date" value="%s" />',
        esc_attr( $current_date )
    );
}
add_action( 'restrict_manage_posts', 'itc_register_admin_filters' );


/**
 * Applique les filtres à la requête admin des CPT itc_card
 */
function itc_apply_admin_filters( $query ) {
    global $pagenow;

    if ( ! is_admin() || $pagenow !== 'edit.php' || $query->get('post_type') !== 'itc_card' ) {
        return;
    }

    $meta_query = array();

    // Filtrer par statut
    if ( ! empty( $_GET['itc_filter_status'] ) ) {
        $meta_query[] = array(
            'key'     => '_itc_status',
            'value'   => sanitize_text_field( $_GET['itc_filter_status'] ),
            'compare' => '=',
        );
    }

    // Filtrer par revendeur
    if ( ! empty( $_GET['itc_filter_revendeur'] ) ) {
        $meta_query[] = array(
            'key'     => '_itc_revendeur',
            'value'   => sanitize_text_field( $_GET['itc_filter_revendeur'] ),
            'compare' => '=',
        );
    }

    // Filtrer par date d’activation (YYYY-MM-DD)
    if ( ! empty( $_GET['itc_filter_date'] ) ) {
        $meta_query[] = array(
            'key'     => '_itc_activation_date',
            'value'   => sanitize_text_field( $_GET['itc_filter_date'] ),
            'compare' => 'LIKE',
        );
    }

    if ( ! empty( $meta_query ) ) {
        $query->set( 'meta_query', $meta_query );
    }
}
add_action( 'pre_get_posts', 'itc_apply_admin_filters' );
