<?php
// includes/revendeur-role.php
// Gestion du rôle « Revendeur ITC » et des champs supplémentaires utilisateur

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Assure l'existence du rôle « itc_revendeur » à chaque chargement.
 */
add_action( 'init', 'itc_ensure_revendeur_role' );
function itc_ensure_revendeur_role() {
    if ( ! get_role( 'itc_revendeur' ) ) {
        add_role(
            'itc_revendeur',
            __( 'Revendeur ITC', 'immersi-travel-card' ),
            [ 'read' => true ]
        );
    }
}

/**
 * Optionnel : suppression du rôle à la désactivation du plugin
 */
register_deactivation_hook( plugin_dir_path( __FILE__ ) . '../immersi-travel-card.php', 'itc_remove_revendeur_role' );
function itc_remove_revendeur_role() {
    remove_role( 'itc_revendeur' );
}

/**
 * Affiche les champs « Adresse » et « Téléphone » dans le profil utilisateur (back-office)
 */
add_action( 'show_user_profile', 'itc_revendeur_profile_fields' );
add_action( 'edit_user_profile', 'itc_revendeur_profile_fields' );
function itc_revendeur_profile_fields( $user ) {
    if ( ! in_array( 'itc_revendeur', (array) $user->roles, true ) ) {
        return;
    }
    ?>
    <h2><?php esc_html_e( 'Infos Revendeur ITC', 'immersi-travel-card' ); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="itc_billing_address"><?php esc_html_e( 'Adresse', 'immersi-travel-card' ); ?></label></th>
            <td>
                <input type="text" name="itc_billing_address" id="itc_billing_address"
                       value="<?php echo esc_attr( get_user_meta( $user->ID, 'itc_billing_address', true ) ); ?>"
                       class="regular-text" />
                <p class="description"><?php esc_html_e( 'Adresse postale du revendeur.', 'immersi-travel-card' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="itc_billing_phone"><?php esc_html_e( 'Téléphone', 'immersi-travel-card' ); ?></label></th>
            <td>
                <input type="text" name="itc_billing_phone" id="itc_billing_phone"
                       value="<?php echo esc_attr( get_user_meta( $user->ID, 'itc_billing_phone', true ) ); ?>"
                       class="regular-text" />
                <p class="description"><?php esc_html_e( 'Numéro de téléphone du revendeur.', 'immersi-travel-card' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Sauvegarde des champs « Adresse » et « Téléphone »
 */
add_action( 'personal_options_update', 'itc_save_revendeur_profile_fields' );
add_action( 'edit_user_profile_update', 'itc_save_revendeur_profile_fields' );
function itc_save_revendeur_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return;
    }
    $user = get_userdata( $user_id );
    if ( ! in_array( 'itc_revendeur', (array) $user->roles, true ) ) {
        return;
    }
    if ( isset( $_POST['itc_billing_address'] ) ) {
        update_user_meta( $user_id, 'itc_billing_address', sanitize_text_field( wp_unslash( $_POST['itc_billing_address'] ) ) );
    }
    if ( isset( $_POST['itc_billing_phone'] ) ) {
        update_user_meta( $user_id, 'itc_billing_phone', sanitize_text_field( wp_unslash( $_POST['itc_billing_phone'] ) ) );
    }
}

/**
 * Blocage de l'accès wp-admin pour les revendeurs
 */
add_action( 'admin_init', 'itc_block_revendeur_admin' );
function itc_block_revendeur_admin() {
    if ( is_user_logged_in() && current_user_can( 'itc_revendeur' ) && ! wp_doing_ajax() ) {
        wp_safe_redirect( home_url( '/revendeur/login' ) );
        exit;
    }
}
