<?php
// includes/frontend-revendeur-login.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Gère les messages d’erreur de connexion
if ( isset( $_GET['login'] ) && $_GET['login'] === 'failed' ) {
    echo '<p class="login-error">' . esc_html__( 'Identifiant ou mot de passe invalide.', 'immersi-travel-card' ) . '</p>';
}

$args = [
    'redirect'       => home_url( '/revendeur/dashboard' ),
    'form_id'        => 'itc-revendeur-login-form',
    'label_username' => __( 'Identifiant', 'immersi-travel-card' ),
    'label_password' => __( 'Mot de passe',   'immersi-travel-card' ),
    'label_remember' => __( 'Se souvenir de moi', 'immersi-travel-card' ),
    'label_log_in'   => __( 'Se connecter',  'immersi-travel-card' ),
];

echo '<div class="itc-login-container">';
wp_login_form( $args );
echo '</div>';
