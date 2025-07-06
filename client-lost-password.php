<?php
// includes/client-lost-password.php
// Shortcode pour le formulaire front-end « Mot de passe oublié »

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Affiche le formulaire de demande de réinitialisation du mot de passe
 */
function itc_shortcode_lost_password() {
    // Si utilisateur connecté, on propose de se déconnecter
    if ( is_user_logged_in() ) {
        return '<p>' . esc_html__( 'Vous êtes déjà connecté.', 'immersi-travel-card' ) . ' <a href="?itc_logout=1">' . esc_html__( 'Déconnexion', 'immersi-travel-card' ) . '</a></p>';
    }

    $error   = '';
    $success = '';

    // Traitement du formulaire
    if ( isset( $_POST['itc_lost_pass_submit'] ) ) {
        $login = sanitize_user( wp_unslash( $_POST['itc_lost_pass_login'] ?? '' ), true );
        if ( empty( $login ) ) {
            $error = '<p class="itc-error">' . esc_html__( 'Veuillez entrer votre numéro de carte.', 'immersi-travel-card' ) . '</p>';
        } else {
            $result = retrieve_password( $login );
            if ( is_wp_error( $result ) ) {
                foreach ( $result->get_error_messages() as $message ) {
                    $error .= '<p class="itc-error">' . esc_html( $message ) . '</p>';
                }
            } else {
                $success = '<p class="itc-success">' . esc_html__( 'Un petit e-mail vient de partir vers l’adresse associée à votre carte.', 'immersi-travel-card' ) . '</p>';
            }
        }
    }

    // Affichage du formulaire
    ob_start();
    echo $error;
    echo $success;
    ?>
    <form method="post" class="itc-lost-password-form">
        <p><label><?php esc_html_e( 'Numéro de carte', 'immersi-travel-card' ); ?><br>
        <input type="text" name="itc_lost_pass_login" required></label></p>
        <p><button type="submit" name="itc_lost_pass_submit"><?php esc_html_e( 'Envoyer le lien', 'immersi-travel-card' ); ?></button></p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'itc_lost_password', 'itc_shortcode_lost_password' );
