<?php
// includes/client-login.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode [itc_client_login] for client login via card number.
 * Displays a wp_login_form() with "Numéro de carte" label and a "Mot de passe oublié" link.
 */
function itc_shortcode_client_login( $atts ) {
    if ( is_user_logged_in() ) {
        return '<p>' . esc_html__( 'Vous êtes déjà connecté.', 'immersi-travel-card' ) . '</p>';
    }

    $args = array(
        'echo'           => false,
        'form_id'        => 'itc-client-login-form',
        'label_username' => __( 'Numéro de carte', 'immersi-travel-card' ),
        'label_password' => __( 'Mot de passe', 'immersi-travel-card' ),
        'id_username'    => 'user_login',
        'id_password'    => 'user_pass',
        'id_submit'      => 'wp-submit',
        'remember'       => true,
        'redirect'       => home_url( '/offres/' ),
        'value_username' => '',
        'value_remember' => false,
    );

    $form = wp_login_form( $args );
    $lost = '<p class="itc-lostpassword"><a href="' . esc_url( wp_lostpassword_url() ) . '">' . esc_html__( 'Mot de passe oublié ?', 'immersi-travel-card' ) . '</a></p>';

    return '<div class="itc-client-login">' . $form . $lost . '</div>';
}
add_shortcode( 'itc_client_login', 'itc_shortcode_client_login' );

/**
 * Authenticate users by card number instead of WP username.
 * Intercepts the credentials and maps card number to actual WP user.
 */
function itc_card_authenticate( $user, $username, $password ) {
    // If WP user exists normally, allow default authentication
    if ( username_exists( $username ) ) {
        return $user;
    }

    // Attempt to treat login as card number
    $card_number = sanitize_text_field( $username );
    $cards = get_posts( array(
        'post_type'      => 'itc_card',
        'meta_key'       => '_itc_card_number',
        'meta_value'     => $card_number,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ) );
    if ( empty( $cards ) ) {
        return $user;
    }
    $card_id = $cards[0];

    // Find associated WP user
    $users = get_users( array(
        'meta_key'   => '_itc_card_id',
        'meta_value' => $card_id,
        'number'     => 1,
        'fields'     => 'all',
    ) );
    if ( empty( $users ) ) {
        return $user;
    }
    $wp_user = $users[0];

    // Authenticate using actual WP username and password
    return wp_authenticate_username_password( null, $wp_user->user_login, $password );
}
add_filter( 'authenticate', 'itc_card_authenticate', 20, 3 );
