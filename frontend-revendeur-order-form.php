<?php
// includes/frontend-revendeur-order-form.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$sent         = false;
$errors       = [];

// Traitement du formulaire de commande
if ( 'POST' === $_SERVER['REQUEST_METHOD']
  && isset( $_POST['itc_order_cards_nonce'] )
  && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['itc_order_cards_nonce'] ) ), 'itc_order_cards' )
) {
    $order_prenom  = sanitize_text_field( wp_unslash( $_POST['order_prenom'] ) );
    $order_qty     = absint( wp_unslash( $_POST['order_qty'] ) );
    $order_comment = sanitize_textarea_field( wp_unslash( $_POST['order_comment'] ) );

    // Validation
    if ( empty( $order_prenom ) ) {
        $errors[] = __( 'Veuillez indiquer votre prénom.', 'immersi-travel-card' );
    }
    if ( $order_qty < 1 ) {
        $errors[] = __( 'La quantité doit être au moins 1.', 'immersi-travel-card' );
    }

    // Création de la commande si aucune erreur
    if ( empty( $errors ) ) {
        // Créer le post CPT itc_card_order
        $order_data = [
            'post_type'   => 'itc_card_order',
            'post_title'  => sprintf( 'Commande %s - %s', $current_user->user_login, current_time( 'mysql' ) ),
            'post_status' => 'publish',
        ];
        $order_id = wp_insert_post( $order_data );
        if ( ! is_wp_error( $order_id ) ) {
            // Sauvegarde des méta
            update_post_meta( $order_id, '_itc_order_revendeur', $current_user->ID );
            update_post_meta( $order_id, '_itc_order_quantity', $order_qty );
            update_post_meta( $order_id, '_itc_order_note',     $order_comment );
            update_post_meta( $order_id, '_itc_exported',       0 );
            update_post_meta( $order_id, '_itc_order_received',  0 );

            // Initialiser generation
            update_post_meta( $order_id, '_itc_cards_generated', 0 );

            // Génération immédiate des cartes
            if ( function_exists( 'itc_handle_new_order' ) ) {
                $order_post = get_post( $order_id );
                itc_handle_new_order( $order_id, $order_post, false );
            }
        }

        // Envoi du mail à l'admin
        $admin_email = get_option( 'admin_email' );
        $subject     = sprintf(
            /* translators: 1: revendeur prénom, 2: site name */
            __( 'Nouvelle commande de cartes par %1$s – %2$s', 'immersi-travel-card' ),
            $current_user->first_name,
            get_bloginfo( 'name' )
        );

        // Coordonnées revendeur
        $rev_login   = $current_user->user_login;
        $rev_adresse = get_user_meta( $current_user->ID, 'itc_billing_address', true );
        $rev_phone   = get_user_meta( $current_user->ID, 'itc_billing_phone', true );

        // Corps du mail
        $body  = sprintf( __( "Identifiant revendeur : %s\n", 'immersi-travel-card' ), $rev_login );
        $body .= sprintf( __( "Prénom émetteur : %s\n",       'immersi-travel-card' ), $order_prenom );
        $body .= sprintf( __( "Adresse complète : %s\n",     'immersi-travel-card' ), $rev_adresse );
        $body .= sprintf( __( "Téléphone : %s\n\n",        'immersi-travel-card' ), $rev_phone );
        $body .= sprintf( __( "Quantité demandée : %d\n",     'immersi-travel-card' ), $order_qty );
        $body .= sprintf( __( "Commentaire :\n%s\n",        'immersi-travel-card' ), $order_comment );

        wp_mail( $admin_email, $subject, $body );
        $sent = true;
    }
}

// Affichage des messages
if ( $sent ) : ?>
    <div class="itc-success" style="border:1px solid #4CAF50; padding:1em; background:#f0fff0; margin-bottom:1em;">
        <?php esc_html_e( 'Votre commande a été envoyée avec succès !', 'immersi-travel-card' ); ?>
    </div>
<?php endif; ?>

<?php if ( ! empty( $errors ) ) : ?>
    <div class="itc-error" style="border:1px solid #e74c3c; padding:1em; background:#ffecec; margin-bottom:1em;">
        <?php foreach ( $errors as $e ) : ?>
            <p><?php echo esc_html( $e ); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Formulaire de commande, toujours affiché -->
<form method="post" class="itc-order-form" style="max-width:400px;">
    <?php wp_nonce_field( 'itc_order_cards', 'itc_order_cards_nonce' ); ?>

    <p>
        <label for="order_prenom"><?php esc_html_e( 'Votre prénom :', 'immersi-travel-card' ); ?></label><br>
        <input type="text" name="order_prenom" id="order_prenom" value="<?php echo isset( $order_prenom ) ? esc_attr( $order_prenom ) : ''; ?>" required style="width:100%; padding:0.5em;">
    </p>

    <p>
        <label for="order_qty"><?php esc_html_e( 'Nombre de cartes souhaitées :', 'immersi-travel-card' ); ?></label><br>
        <input type="number" min="1" name="order_qty" id="order_qty" value="<?php echo isset( $order_qty ) ? esc_attr( $order_qty ) : '1'; ?>" required style="width:100%; padding:0.5em;">
    </p>

    <p>
        <label for="order_comment"><?php esc_html_e( 'Commentaire :', 'immersi-travel-card' ); ?></label><br>
        <textarea name="order_comment" id="order_comment" rows="4" style="width:100%; padding:0.5em;"><?php echo isset( $order_comment ) ? esc_textarea( $order_comment ) : ''; ?></textarea>
    </p>

    <p>
        <button type="submit" class="button button-primary" style="padding:0.5em 1em;">
            <?php esc_html_e( 'Envoyer la commande', 'immersi-travel-card' ); ?>
        </button>
    </p>
</form>
