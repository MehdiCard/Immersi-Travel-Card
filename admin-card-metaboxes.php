<?php
// includes/admin-card-metaboxes.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enregistre la meta-box pour les détails de la carte ITC
 */
function itc_register_card_metabox() {
    add_meta_box(
        'itc_card_details',
        __( 'Détails de la carte ITC', 'immersi-travel-card' ),
        'itc_render_card_metabox',
        'itc_card',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'itc_register_card_metabox' );

/**
 * Charge les scripts pour les datepickers sur l'écran de modification itc_card
 */
function itc_enqueue_admin_scripts( $hook ) {
    global $post;
    if ( 'post.php' === $hook && isset( $post->post_type ) && 'itc_card' === $post->post_type ) {
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
        wp_add_inline_script(
            'jquery-ui-datepicker',
            "jQuery(document).ready(function($){ $('#itc_activation_date, #itc_expiration_date').datepicker({ dateFormat: 'yy-mm-dd' }); });"
        );
    }
}
add_action( 'admin_enqueue_scripts', 'itc_enqueue_admin_scripts' );

/**
 * Affiche la meta-box pour éditer statut, dates et QR code
 */
function itc_render_card_metabox( $post ) {
    wp_nonce_field( 'itc_save_card_metabox', 'itc_card_metabox_nonce' );

    // Statut et dates brutes
    $status   = get_post_meta( $post->ID, '_itc_status', true );
    $act_raw  = get_post_meta( $post->ID, '_itc_activation_date', true );
    $exp_raw  = get_post_meta( $post->ID, '_itc_expiration_date', true );

    // Récupération du QR code (clé ancienne ou nouvelle)
    $qr_url = get_post_meta( $post->ID, '_itc_qr_code_url', true );
    if ( ! $qr_url ) {
        $qr_url = get_post_meta( $post->ID, '_itc_qr_url', true );
    }

    // Format des dates pour affichage (YYYY-MM-DD)
    $act_date = '';
    if ( $act_raw ) {
        $dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $act_raw );
        if ( $dt ) {
            $act_date = $dt->format( 'Y-m-d' );
        }
    }
    $exp_date = '';
    if ( $exp_raw ) {
        $dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $exp_raw );
        if ( $dt ) {
            $exp_date = $dt->format( 'Y-m-d' );
        }
    }
    ?>
    <p>
        <label for="itc_status"><?php esc_html_e( 'Statut de la carte', 'immersi-travel-card' ); ?></label><br>
        <select name="itc_status" id="itc_status">
            <option value="non_active" <?php selected( $status, 'non_active' ); ?>><?php esc_html_e( 'Non activée', 'immersi-travel-card' ); ?></option>
            <option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'immersi-travel-card' ); ?></option>
            <option value="expired" <?php selected( $status, 'expired' ); ?>><?php esc_html_e( 'Expirée', 'immersi-travel-card' ); ?></option>
        </select>
    </p>
    <p>
        <label for="itc_activation_date"><?php esc_html_e( 'Date d\'activation (YYYY-MM-DD)', 'immersi-travel-card' ); ?></label><br>
        <input type="text" name="itc_activation_date" id="itc_activation_date" value="<?php echo esc_attr( $act_date ); ?>" />
    </p>
    <p>
        <label for="itc_expiration_date"><?php esc_html_e( 'Date d\'expiration (YYYY-MM-DD)', 'immersi-travel-card' ); ?></label><br>
        <input type="text" name="itc_expiration_date" id="itc_expiration_date" value="<?php echo esc_attr( $exp_date ); ?>" />
    </p>
    <p>
        <label><?php esc_html_e( 'QR Code', 'immersi-travel-card' ); ?></label><br>
        <?php if ( $qr_url ) : ?>
            <img src="<?php echo esc_url( $qr_url ); ?>" alt="<?php esc_attr_e( 'QR Code', 'immersi-travel-card' ); ?>" style="max-width:150px;height:auto;" />
        <?php else : ?>
            <em><?php esc_html_e( 'Pas de QR généré.', 'immersi-travel-card' ); ?></em>
        <?php endif; ?>
    </p>
    <?php
}

/**
 * Sauvegarde des données de la meta-box, avec expiration automatique J+7
 */
function itc_save_card_metabox( $post_id ) {
    // Vérification du nonce et des permissions
    if ( empty( $_POST['itc_card_metabox_nonce'] )
        || ! wp_verify_nonce( $_POST['itc_card_metabox_nonce'], 'itc_save_card_metabox' )
        || ! current_user_can( 'edit_post', $post_id )
    ) {
        return;
    }

    // Statut
    if ( isset( $_POST['itc_status'] ) ) {
        update_post_meta( $post_id, '_itc_status', sanitize_text_field( $_POST['itc_status'] ) );
    }

    // Date d'activation
    if ( ! empty( $_POST['itc_activation_date'] ) ) {
        $act_input = sanitize_text_field( $_POST['itc_activation_date'] );
        $act_dt    = DateTime::createFromFormat( 'Y-m-d', $act_input );
        if ( $act_dt ) {
            $act_dt->setTime( 0, 0, 0 );
            update_post_meta( $post_id, '_itc_activation_date', $act_dt->format( 'Y-m-d H:i:s' ) );

            // Expiration automatique J+7 si non renseignée
            if ( empty( $_POST['itc_expiration_date'] ) ) {
                $exp_dt = clone $act_dt;
                $exp_dt->modify( '+7 days' );
                $exp_dt->setTime( 23, 59, 59 );
                update_post_meta( $post_id, '_itc_expiration_date', $exp_dt->format( 'Y-m-d H:i:s' ) );
            }
        }
    }

    // Date d'expiration manuelle (prend le pas sur automatique)
    if ( ! empty( $_POST['itc_expiration_date'] ) ) {
        $exp_input = sanitize_text_field( $_POST['itc_expiration_date'] );
        $exp_dt    = DateTime::createFromFormat( 'Y-m-d', $exp_input );
        if ( $exp_dt ) {
            $exp_dt->setTime( 23, 59, 59 );
            update_post_meta( $post_id, '_itc_expiration_date', $exp_dt->format( 'Y-m-d H:i:s' ) );
        }
    }
}
add_action( 'save_post_itc_card', 'itc_save_card_metabox' );