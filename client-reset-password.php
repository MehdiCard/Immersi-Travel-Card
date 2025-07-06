<?php
// includes/client-reset-password.php
// Shortcode pour le formulaire front-end « Nouveau mot de passe »

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Affiche le formulaire de choix d'un nouveau mot de passe après clic sur le lien email
 */
function itc_shortcode_reset_password() {
    $errors = '';
    $success = '';

    // Récupération safe de la clé et du login depuis l’URL
    $key   = isset( $_REQUEST['key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) : '';
    $login = isset( $_REQUEST['login'] ) ? sanitize_user( wp_unslash( $_REQUEST['login'] ), true ) : '';

    // Vérification de la clé et du login
    $user = check_password_reset_key( $key, $login );
    if ( is_wp_error( $user ) ) {
        return '<p>' . implode( '<br>', $user->get_error_messages() ) . '</p>';
    }

    // Traitement du formulaire
    if ( isset( $_POST['itc_reset_pass_submit'] ) ) {
        $new_pass         = isset( $_POST['itc_new_password'] ) ? sanitize_text_field( wp_unslash( $_POST['itc_new_password'] ) ) : '';
        $new_pass_confirm = isset( $_POST['itc_new_password_confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['itc_new_password_confirm'] ) ) : '';

        if ( empty( $new_pass ) ) {
            $errors = '<p class="itc-error">' . esc_html__( 'Le mot de passe ne peut pas être vide.', 'immersi-travel-card' ) . '</p>';
        } elseif ( $new_pass !== $new_pass_confirm ) {
            $errors = '<p class="itc-error">' . esc_html__( 'Les mots de passe ne correspondent pas.', 'immersi-travel-card' ) . '</p>';
        } else {
            // Réinitialisation du mot de passe
            reset_password( $user, $new_pass );
            $success  = '<p class="itc-success">' . esc_html__( 'Votre mot de passe a bien été changé !', 'immersi-travel-card' ) . '</p>';
            $success .= '<p><a href="' . esc_url( add_query_arg( 'login', rawurlencode( $login ), home_url( '/acces-client/' ) ) ) . '">' . esc_html__( 'Se connecter', 'immersi-travel-card' ) . '</a></p>';
        }
    }

    // Affichage du formulaire
    ob_start();
    echo $errors;
    echo $success;

    if ( empty( $success ) ) {
        ?>
        <form method="post" class="itc-reset-password-form">
            <p>
                <label><?php esc_html_e( 'Numéro de carte', 'immersi-travel-card' ); ?><br>
                <input type="text" name="itc_login" value="<?php echo esc_attr( $login ); ?>" readonly></label>
            </p>
            <p>
                <label><?php esc_html_e( 'Nouveau mot de passe', 'immersi-travel-card' ); ?><br>
                <input type="password" name="itc_new_password" required></label>
            </p>
            <p>
                <label><?php esc_html_e( 'Confirmez le mot de passe', 'immersi-travel-card' ); ?><br>
                <input type="password" name="itc_new_password_confirm" required></label>
            </p>
            <p>
                <button type="submit" name="itc_reset_pass_submit"><?php esc_html_e( 'Enregistrer', 'immersi-travel-card' ); ?></button>
            </p>
        </form>
        <?php
    }

    return ob_get_clean();
}
