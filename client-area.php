<?php
// includes/client-area.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function itc_client_area_shortcode() {
    // Bypass pour l'édition en back-office / preview
    if ( current_user_can( 'edit_posts' ) || is_preview() || isset( $_GET['elementor-preview'] ) ) {
        return '<div class="itc-client-area-placeholder"></div>';
    }

    // Déconnexion
    if ( isset( $_GET['itc_logout'] ) ) {
        wp_logout();
        wp_redirect( home_url( '/acces-client/' ) );
        exit;
    }

    $debug = '';
    $error = '';

    // Traitement du formulaire de connexion
    if ( ! empty( $_POST['itc_card_number'] ) && ! empty( $_POST['itc_password'] ) ) {
        $card_number = trim( sanitize_text_field( wp_unslash( $_POST['itc_card_number'] ) ) );
        $password    = wp_unslash( $_POST['itc_password'] );

        $debug .= '<!-- Tentative login : ' . esc_html( $card_number ) . ' -->';
        // 1) Recherche par login WP
        $user = get_user_by( 'login', $card_number );

        // 2) Fallback : recherche du post itc_card & récupération de l'utilisateur via meta _itc_card_id
        if ( ! $user ) {
            $debug .= '<!-- get_user_by login a échoué -->';
            $card_post = get_page_by_title( $card_number, OBJECT, 'itc_card' );
            if ( $card_post ) {
                $fallback_users = get_users( array(
                    'meta_key'   => '_itc_card_id',
                    'meta_value' => $card_post->ID,
                    'number'     => 1,
                    'fields'     => 'all',
                ) );
                if ( ! empty( $fallback_users ) ) {
                    $user = $fallback_users[0];
                    $debug .= '<!-- Fallback user trouvé ID : ' . esc_html( $user->ID ) . ' -->';
                }
            } else {
                $debug .= '<!-- Aucun post itc_card trouvé pour : ' . esc_html( $card_number ) . ' -->';
            }
        }

        // Validation du mot de passe
        if ( ! $user ) {
            $error = '<p class="itc-error">Numéro de carte ou mot de passe incorrect.</p>';
        } elseif ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
            $debug .= '<!-- wp_check_password a échoué pour user ID : ' . esc_html( $user->ID ) . ' -->';
            $error = '<p class="itc-error">Numéro de carte ou mot de passe incorrect.</p>';
        } else {
            // Vérification de la carte liée
            $card_id = get_user_meta( $user->ID, '_itc_card_id', true );
            if ( ! $card_id || get_post_type( $card_id ) !== 'itc_card' ) {
                $error = '<p class="itc-error">Carte introuvable ou non liée à votre compte.</p>';
            } else {
                // Statut & expiration
                $status = get_post_meta( $card_id, '_itc_status', true );
                $exp    = get_post_meta( $card_id, '_itc_expiration_date', true );
                $now_ts = current_time( 'timestamp' );

                if ( $status !== 'active' ) {
                    wp_redirect( home_url( '/carte-non-active/' ) );
                    exit;
                }
                if ( $exp && strtotime( $exp ) < $now_ts ) {
                    wp_redirect( home_url( '/carte-expiree/' ) );
                    exit;
                }

                // Connexion WP
                wp_set_current_user( $user->ID );
                wp_set_auth_cookie( $user->ID );
                wp_redirect( home_url( '/offres/' ) );
                exit;
            }
        }
    }

    // Si déjà connecté
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $card_id = get_user_meta( $user_id, '_itc_card_id', true );
        $status  = get_post_meta( $card_id, '_itc_status', true );
        $exp     = get_post_meta( $card_id, '_itc_expiration_date', true );
        $now_ts  = current_time( 'timestamp' );

        if ( get_post_type( $card_id ) !== 'itc_card' ) {
            wp_redirect( home_url( '/carte-non-active/' ) );
            exit;
        }
        if ( $status !== 'active' ) {
            wp_redirect( home_url( '/carte-non-active/' ) );
            exit;
        }
        if ( $exp && strtotime( $exp ) < $now_ts ) {
            wp_redirect( home_url( '/carte-expiree/' ) );
            exit;
        }

        // Affichage zone client
        ob_start();
        echo '<div class="itc-client-area">';
        echo '<p>Bienvenue ' . esc_html( wp_get_current_user()->first_name ) . ' ! ';
        echo '<a href="?itc_logout=1">Déconnexion</a> | ';
        echo '<a href="' . esc_url( get_edit_profile_url( $user_id ) ) . '">Modifier mon mot de passe</a></p>';

        if ( shortcode_exists( 'wte_offers' ) ) {
            echo do_shortcode( '[wte_offers]' );
        } else {
            $offers = new WP_Query( array( 'post_type' => 'wte_offer', 'posts_per_page' => -1 ) );
            if ( $offers->have_posts() ) {
                echo '<ul class="itc-offers-list">';
                while ( $offers->have_posts() ) {
                    $offers->the_post();
                    echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
                }
                echo '</ul>';
                wp_reset_postdata();
            } else {
                echo '<p>' . esc_html__( 'Aucune offre disponible.', 'immersi-travel-card' ) . '</p>';
            }
        }

        echo '</div>';
        return ob_get_clean();
    }

    // Affichage formulaire de connexion
    ob_start();
    echo $error;
    echo $debug;
    ?>
    <form method="post" class="itc-client-login">
      <p><label>Numéro de carte<br>
        <input name="itc_card_number" required></label></p>
      <p><label>Mot de passe<br>
        <input name="itc_password" type="password" required></label></p>
      <p><button type="submit">Se connecter</button></p>
      <p><a href="<?php echo esc_url( wp_lostpassword_url( get_permalink() ) ); ?>">Mot de passe oublié ?</a></p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'itc_client_area', 'itc_client_area_shortcode' );
