<?php
// includes/ajax-handlers.php
// Handlers AJAX pour historique revendeur, export PDF et activation de cartes via AJAX

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX: Récupère l'historique des commandes pour DataTables
 */
add_action( 'wp_ajax_itc_get_order_history', 'itc_ajax_get_order_history' );
add_action( 'wp_ajax_nopriv_itc_get_order_history', 'itc_ajax_get_order_history' );
function itc_ajax_get_order_history() {
    check_ajax_referer( 'itc_history_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! current_user_can( 'itc_revendeur' ) ) {
        wp_send_json_error( 'Unauthorized', 401 );
    }

    if ( ! function_exists( 'itc_get_order_history' ) ) {
        wp_send_json_error( 'Function itc_get_order_history not found', 500 );
    }

    $data = itc_get_order_history();
    if ( is_wp_error( $data ) ) {
        wp_send_json_error( $data->get_error_message(), 500 );
    }

    $rows = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : [];
    foreach ( $rows as &$row ) {
        if ( isset( $row['ID'] ) ) {
            $row['order_id'] = $row['ID'];
        } elseif ( isset( $row['id'] ) ) {
            $row['order_id'] = $row['id'];
        } else {
            $row['order_id'] = '';
        }
    }
    unset( $row );

    // On renvoie dans le format { success: true, data: […] }
    wp_send_json_success( [ 'data' => $rows ] );
}

/**
 * AJAX: Récupère l'historique des activations pour DataTables
 */
add_action( 'wp_ajax_itc_get_activation_history', 'itc_ajax_get_activation_history' );
add_action( 'wp_ajax_nopriv_itc_get_activation_history', 'itc_ajax_get_activation_history' );
function itc_ajax_get_activation_history() {
    check_ajax_referer( 'itc_history_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! current_user_can( 'itc_revendeur' ) ) {
        wp_send_json_error( 'Unauthorized', 401 );
    }

    $revendeur_id = get_current_user_id();

    // Récupère toutes les cartes actives du revendeur
    $query = new WP_Query( [
        'post_type'      => 'itc_card',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            [ 'key' => '_itc_status',    'value' => 'active' ],
            [ 'key' => '_itc_revendeur', 'value' => $revendeur_id, 'compare' => '=', 'type' => 'NUMERIC' ],
        ],
    ] );

    $results = [];
    foreach ( $query->posts as $post ) {
        $card_id              = $post->ID;
        $activation_date_meta = get_post_meta( $card_id, '_itc_activation_date', true );
        $expiration_date_meta = get_post_meta( $card_id, '_itc_expiration_date', true );
        $status_meta          = get_post_meta( $card_id, '_itc_status', true );
        $rev_prenom_meta      = get_post_meta( $card_id, '_itc_revendeur_prenom', true );

        $results[] = [
            'card_number'      => esc_html( get_post_meta( $card_id, '_itc_card_number', true ) ),
            'activation_date'  => $activation_date_meta ? esc_html( date_i18n( 'd/m/Y', strtotime( $activation_date_meta ) ) ) : '-',
            'expiration_date'  => $expiration_date_meta ? esc_html( date_i18n( 'd/m/Y', strtotime( $expiration_date_meta ) ) ) : '-',
            'status'           => esc_html( ucfirst( $status_meta ) ),
            'revendeur_prenom' => esc_html( $rev_prenom_meta ),
        ];
    }

    // On renvoie dans le format { success: true, data: […] }
    wp_send_json_success( [ 'data' => $results ] );
}

/**
 * AJAX: Active des cartes (handler AJAX)
 */
add_action( 'wp_ajax_itc_handle_activation', 'itc_handle_activation_ajax' );
add_action( 'wp_ajax_nopriv_itc_handle_activation', 'itc_handle_activation_ajax' );
function itc_handle_activation_ajax() {
    check_ajax_referer( 'itc_activation_nonce', 'security' );

    if ( ! is_user_logged_in() || ! current_user_can( 'itc_revendeur' ) ) {
        wp_send_json_error( 'Unauthorized', 401 );
    }

    $codes      = isset( $_POST['itc_card_codes'] )            ? array_map( 'sanitize_text_field', wp_unslash( $_POST['itc_card_codes'] ) )            : [];
    $firstnames = isset( $_POST['itc_client_firstname'] )      ? array_map( 'sanitize_text_field', wp_unslash( $_POST['itc_client_firstname'] ) )      : [];
    $lastnames  = isset( $_POST['itc_client_lastname'] )       ? array_map( 'sanitize_text_field', wp_unslash( $_POST['itc_client_lastname'] ) )       : [];
    $emails     = isset( $_POST['itc_client_email'] )          ? array_map( 'sanitize_email',      wp_unslash( $_POST['itc_client_email'] ) )          : [];
    $tels       = isset( $_POST['itc_client_tel'] )            ? array_map( 'sanitize_text_field', wp_unslash( $_POST['itc_client_tel'] ) )            : [];
    $countries  = isset( $_POST['itc_client_country'] )        ? array_map( 'sanitize_text_field', wp_unslash( $_POST['itc_client_country'] ) )        : [];
    $offer      = isset( $_POST['itc_activation_offer'] )      ? sanitize_text_field( wp_unslash( $_POST['itc_activation_offer'] ) )                 : '';
    $rev_prenom = isset( $_POST['revendeur_prenom'] )          ? sanitize_text_field( wp_unslash( $_POST['revendeur_prenom'] ) )                     : '';

    if ( empty( $codes ) ) {
        wp_send_json_error( 'Aucun code fourni', 400 );
    }

    $activated = 0;
    foreach ( $codes as $index => $code ) {
        $card = get_page_by_title( $code, OBJECT, 'itc_card' );
        if ( ! $card ) {
            continue;
        }
        $card_id = $card->ID;

        if ( 'active' === get_post_meta( $card_id, '_itc_status', true ) ) {
            continue;
        }

        $email = $emails[ $index ] ?? '';
        if ( email_exists( $email ) ) {
            $user_id = get_user_by( 'email', $email )->ID;
        } else {
            $login   = sanitize_user( $code, true );
            $pass    = wp_generate_password( 12, false );
            $user_id = wp_create_user( $login, $pass, $email );
            if ( is_wp_error( $user_id ) ) {
                continue;
            }
            wp_update_user( [ 'ID' => $user_id, 'role' => 'porteur_itc' ] );
            if ( function_exists( 'itc_send_activation_email' ) ) {
                itc_send_activation_email( $user_id, $code, $pass );
            }
        }

        // Metas de carte et utilisateur
        update_user_meta( $user_id, '_itc_card_id',            $card_id );
        update_post_meta( $card_id, '_itc_status',              'active' );
        update_post_meta( $card_id, '_itc_revendeur',           get_current_user_id() );
        update_post_meta( $card_id, '_itc_revendeur_prenom',    $rev_prenom );
        update_post_meta( $card_id, '_itc_activation_date',     current_time( 'mysql' ) );
        update_post_meta( $card_id, '_itc_expiration_date',     date( 'Y-m-d H:i:s', strtotime( '+7 days' ) ) );
        update_post_meta( $card_id, '_itc_activation_offer',    $offer );
        update_post_meta( $card_id, '_itc_client_firstname',    $firstnames[ $index ] ?? '' );
        update_post_meta( $card_id, '_itc_client_lastname',     $lastnames[ $index ] ?? '' );
        update_post_meta( $card_id, '_itc_client_email',        $emails[ $index ] );
        update_post_meta( $card_id, '_itc_client_tel',          $tels[ $index ] ?? '' );
        update_post_meta( $card_id, '_itc_client_country',      $countries[ $index ] ?? '' );

        // Log en base
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'itc_stock_log',
            [
                'card_id'      => $card_id,
                'revendeur_id' => get_current_user_id(),
                'type'         => 'activation',
                'date'         => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s' ]
        );

        $activated++;
    }

    if ( $activated > 0 ) {
        wp_send_json_success( [ 'message' => sprintf( __( '%d carte(s) activée(s) avec succès', 'immersi-travel-card' ), $activated ) ] );
    }

    wp_send_json_error( __( 'Aucune activation effectuée', 'immersi-travel-card' ), 400 );
}
