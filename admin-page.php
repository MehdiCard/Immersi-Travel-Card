<?php
// includes/admin-page.php
// Affichage de la page pour la génération de cartes

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Callback pour afficher et traiter le formulaire de génération de cartes
 */
function itc_generate_cards_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Génération de Cartes ITC', 'immersi-travel-card' ); ?></h1>
        <form method="post" action="">
            <label for="quantity"><?php esc_html_e( 'Quantité de cartes à générer :', 'immersi-travel-card' ); ?></label>
            <input type="number" id="quantity" name="quantity" min="1" required />
            <?php wp_nonce_field( 'itc_generate_cards_action', 'itc_generate_cards_nonce' ); ?>
            <input type="submit" name="generate_cards" value="<?php esc_attr_e( 'Générer les cartes', 'immersi-travel-card' ); ?>" class="button button-primary" />
        </form>

        <?php
        // Traitement du formulaire
        if ( isset( $_POST['generate_cards'] )
             && isset( $_POST['itc_generate_cards_nonce'] )
             && wp_verify_nonce( $_POST['itc_generate_cards_nonce'], 'itc_generate_cards_action' ) ) {

            $quantity = intval( $_POST['quantity'] );
            if ( $quantity > 0 ) {
                itc_generate_cards( $quantity );
            }
        }
        ?>
    </div>
    <?php
}

/**
 * Génère un lot de cartes ITC
 */
function itc_generate_cards( $quantity ) {
    for ( $i = 0; $i < $quantity; $i++ ) {
        // Générer un numéro unique pour la carte
        $card_number = 'ITC-' . str_pad( mt_rand( 1, 999999 ), 6, '0', STR_PAD_LEFT );

        // Insérer la carte comme Custom Post Type
        $post_id = wp_insert_post( array(
            'post_title'  => $card_number,
            'post_type'   => 'itc_card',
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $post_id ) ) {
            continue;
        }

        // Générer le QR code (URL d’activation)
        $activation_url = home_url( '/activate?card=' . $card_number );
        $qr_code_url    = itc_generate_qr_code( $activation_url, $card_number );

        // Stocker les métas initiales
        update_post_meta( $post_id, '_itc_card_number',     $card_number );
        update_post_meta( $post_id, '_itc_qr_url',          $qr_code_url );
        update_post_meta( $post_id, '_itc_status',          'non_active' );
        update_post_meta( $post_id, '_itc_activation_date', '' );
        update_post_meta( $post_id, '_itc_expiration_date', '' );
    }

    // Message de confirmation
    printf(
        '<div class="updated notice"><p>%s</p></div>',
        sprintf( _n( '%d carte a été générée.', '%d cartes ont été générées.', $quantity, 'immersi-travel-card' ), $quantity )
    );
}
