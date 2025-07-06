<?php
// includes/card-functions.php
// Gestion de l’activation d’une carte ITC et création du compte client via fonction réutilisable

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Activation pure des données d'une carte ITC.
 *
 * @param array $data {
 *   @type string $card_number            Numéro de carte (ITC-xxxxxx).
 *   @type string $revendeur_prenom       Prénom du revendeur.
 *   @type string $client_firstname       Prénom du client.
 *   @type string $client_lastname        Nom du client.
 *   @type string $client_email           E-mail du client.
 *   @type string $client_tel             Téléphone du client.
 *   @type string $client_country         Pays du client.
 *   @type string $activation_offer       Offre (solo, duo, trio, quatuor, groupe).
 * }
 * @return int|WP_Error Retourne le nombre d’activations réussies (1) ou une WP_Error.
 */
function itc_handle_activation_data( array $data ) {
    // Sanitize inputs
    $card_number      = sanitize_text_field( wp_unslash( $data['card_number'] ?? '' ) );
    $revendeur_prenom = sanitize_text_field( wp_unslash( $data['revendeur_prenom'] ?? '' ) );
    $client_firstname = sanitize_text_field( wp_unslash( $data['client_firstname'] ?? '' ) );
    $client_lastname  = sanitize_text_field( wp_unslash( $data['client_lastname'] ?? '' ) );
    $client_email     = sanitize_email( wp_unslash( $data['client_email'] ?? '' ) );
    $client_tel       = sanitize_text_field( wp_unslash( $data['client_tel'] ?? '' ) );
    $client_country   = sanitize_text_field( wp_unslash( $data['client_country'] ?? '' ) );
    $offer            = sanitize_text_field( wp_unslash( $data['activation_offer'] ?? '' ) );

    // Récupération de la carte CPT
    $cards = get_posts(array(
        'post_type'   => 'itc_card',
        'meta_key'    => '_itc_card_number',
        'meta_value'  => $card_number,
        'post_status' => 'publish',
        'fields'      => 'ids',
        'numberposts' => 1,
    ));
    if ( empty( $cards ) ) {
        return new WP_Error( 'card_not_found', __( 'Carte introuvable.', 'immersi-travel-card' ) );
    }
    $card_id = $cards[0];

    // Vérification du statut
    $status = get_post_meta( $card_id, '_itc_status', true );
    if ( 'active' === $status ) {
        return new WP_Error( 'already_active', __( 'Cette carte est déjà activée.', 'immersi-travel-card' ) );
    }
    if ( 'expired' === $status ) {
        return new WP_Error( 'already_expired', __( 'Cette carte est déjà expirée.', 'immersi-travel-card' ) );
    }

    // Calcul des dates
    $now_ts    = current_time( 'timestamp' );
    $now_mysql = date_i18n( 'Y-m-d H:i:s', $now_ts );
    $exp_ts    = $now_ts + DAY_IN_SECONDS * 7;
    $exp_mysql = date_i18n( 'Y-m-d H:i:s', $exp_ts );
    $exp_front = date_i18n( 'd-m-Y', $exp_ts );

    // Mise à jour des métas de la carte
    update_post_meta( $card_id, '_itc_status',           'active' );
    update_post_meta( $card_id, '_itc_activation_date',  $now_mysql );
    update_post_meta( $card_id, '_itc_expiration_date',  $exp_mysql );
    update_post_meta( $card_id, '_itc_expiration_front', $exp_front );
    update_post_meta( $card_id, '_itc_activation_offer', $offer );
    update_post_meta( $card_id, '_itc_revendeur_prenom', $revendeur_prenom );

    $revendeur_id = get_current_user_id();
    if ( $revendeur_id ) {
        update_post_meta( $card_id, '_itc_revendeur', intval( $revendeur_id ) );
    }

    // Infos client
    update_post_meta( $card_id, '_itc_client_firstname', $client_firstname );
    update_post_meta( $card_id, '_itc_client_lastname',  $client_lastname );
    update_post_meta( $card_id, '_itc_client_email',     $client_email );
    update_post_meta( $card_id, '_itc_client_tel',       $client_tel );
    update_post_meta( $card_id, '_itc_client_country',   $client_country );

    // Création/récupération de l'utilisateur WP
    if ( email_exists( $client_email ) ) {
        $user    = get_user_by( 'email', $client_email );
        $user_id = $user->ID;
    } else {
        // On prend le numéro de carte comme identifiant
        $login   = sanitize_user( $card_number, true );
        $pass    = wp_generate_password( 12, false );
        $user_id = wp_create_user( $login, $pass, $client_email );
        if ( is_wp_error( $user_id ) ) {
            return new WP_Error( 'user_create_failed', __( 'Impossible de créer le compte client.', 'immersi-travel-card' ) );
        }
        wp_update_user(array(
            'ID'         => $user_id,
            'first_name' => $client_firstname,
            'last_name'  => $client_lastname,
            'role'       => 'porteur_itc',
        ));
        // Envoi de l'email d'activation avec le mot de passe généré
        if ( function_exists( 'itc_send_activation_email' ) ) {
            itc_send_activation_email( $user_id, $card_number, $pass );
        }
    }

    // Liaison carte <-> utilisateur et journalisation
    if ( function_exists( 'itc_mark_card_activated' ) ) {
        itc_mark_card_activated( $card_id, $user_id );
    }
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'itc_stock_log',
        array(
            'card_id'      => $card_id,
            'revendeur_id' => intval( $revendeur_id ),
            'type'         => 'activation',
            'date'         => $now_mysql,
        ),
        array( '%d', '%d', '%s', '%s' )
    );

    return 1;
}

/**
 * Wrapper pour compatibilité et pour tests unitaires.
 *
 * @param array $args Paramètres issus du front ou du code existant.
 * @return true|WP_Error
 */
function itc_handle_activation( array $args ) {
    $result = itc_handle_activation_data(array(
        'card_number'      => $args['card_number'] ?? '',
        'revendeur_prenom' => $args['revendeur_prenom'] ?? '',
        'client_firstname' => $args['client_prenom']  ?? '',
        'client_lastname'  => $args['client_nom']     ?? '',
        'client_email'     => $args['client_email']   ?? '',
        'client_tel'       => $args['client_tel']     ?? '',
        'client_country'   => $args['client_country'] ?? '',
        'activation_offer' => $args['itc_activation_offer'] ?? '',
    ));

    if ( is_wp_error( $result ) ) {
        return $result;
    }
    return true;
}

/**
 * Marque une carte comme activée et lie l'utilisateur à la carte.
 * (repris pour compatibilité avec le handler AJAX)
 *
 * @param int $card_id  ID du post itc_card.
 * @param int $user_id  ID de l'utilisateur WordPress.
 */
function itc_mark_card_activated( $card_id, $user_id ) {
    if ( ! $card_id || ! $user_id ) {
        return;
    }
    update_post_meta( $card_id, '_itc_activated_at', current_time( 'mysql' ) );
    update_post_meta( $card_id, '_itc_status',       'active' );
    $exp_ts    = current_time( 'timestamp' ) + DAY_IN_SECONDS * 7;
    $exp_mysql = date_i18n( 'Y-m-d H:i:s', $exp_ts );
    update_post_meta( $card_id, '_itc_expiration_date', $exp_mysql );
    update_user_meta( $user_id, '_itc_card_id', $card_id );
}
