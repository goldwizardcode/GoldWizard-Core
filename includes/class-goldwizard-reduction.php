<?php
/**
 * Classe pour la fonctionnalité de réduction WooCommerce
 *
 * @package GoldWizard_Core
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe GoldWizard_Reduction
 */
class GoldWizard_Reduction {
    /**
     * Instance unique de la classe
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique de la classe
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    public function __construct() {
        // Enregistrer le shortcode
        add_shortcode('goldwizard_reduction', array($this, 'reduction_shortcode'));
        add_shortcode('reduction_woocommerce', array($this, 'reduction_shortcode'));
        
        // Ajouter le support pour les vues rapides
        add_action('init', array($this, 'quickview_support'));
        
        // Ajouter le support pour les blocs globaux et autres contextes
        add_filter('woocommerce_blocks_product_grid_item_html', array($this, 'add_reduction_to_product_blocks'), 10, 3);
        add_action('wp_footer', array($this, 'add_reduction_js'));
        
        // Ajouter le support AJAX
        add_action('wp_ajax_get_reduction_html', array($this, 'ajax_get_reduction_html'));
        add_action('wp_ajax_nopriv_get_reduction_html', array($this, 'ajax_get_reduction_html'));
        
        // Ajouter les styles CSS
        add_action('wp_head', array($this, 'add_reduction_css'));
    }

    /**
     * Shortcode pour afficher la réduction
     */
    public function reduction_shortcode($atts) {
        // Détecter quel shortcode a été utilisé
        $current_shortcode = false;
        if (isset($GLOBALS['shortcode_tags']) && is_array($GLOBALS['shortcode_tags'])) {
            foreach ($GLOBALS['shortcode_tags'] as $tag => $func) {
                if ($func === array($this, 'reduction_shortcode')) {
                    $current_shortcode = $tag;
                    break;
                }
            }
        }
        
        // Extraire les attributs en fonction du shortcode utilisé
        if ($current_shortcode === 'reduction_woocommerce') {
            $atts = shortcode_atts(array(
                'id' => 0,
                'product_id' => 0,
                'hide_shipping' => 'no',
            ), $atts, 'reduction_woocommerce');
        } else {
            $atts = shortcode_atts(array(
                'id' => 0,
                'product_id' => 0,
                'hide_shipping' => 'no',
            ), $atts, 'goldwizard_reduction');
        }

        // Récupérer l'ID du produit
        $product_id = absint($atts['id']) ? absint($atts['id']) : absint($atts['product_id']);

        // Si aucun ID n'est fourni, essayer de récupérer l'ID du produit actuel
        if (!$product_id) {
            global $product;
            
            // Vérifier si $product est défini
            if (isset($product) && is_object($product) && method_exists($product, 'get_id')) {
                $product_id = $product->get_id();
            } else {
                // Essayer de récupérer l'ID du produit depuis la requête
                global $wp_query;
                if (isset($wp_query->post) && $wp_query->post instanceof WP_Post) {
                    $product_id = $wp_query->post->ID;
                }
            }
        }

        // Si toujours pas d'ID, retourner une chaîne vide
        if (!$product_id) {
            return '';
        }

        // Récupérer le produit
        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }

        // Initialiser la sortie
        $output = '';

        // Vérifier si le produit est en promotion
        if ($product->is_on_sale()) {
            // Récupérer les prix
            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            
            // Si c'est un produit variable, récupérer les prix min et max
            if ($product->is_type('variable')) {
                $regular_price = $product->get_variation_regular_price('min');
                $sale_price = $product->get_variation_price('min');
            }
            
            // Calculer le pourcentage d'économie
            $percentage = 0;
            if ($regular_price > 0) {
                $percentage = round(100 - ($sale_price / $regular_price * 100));
            }
            
            // Formater les prix
            $regular_price_formatted = wc_price($regular_price);
            $sale_price_formatted = wc_price($sale_price);
            
            // Créer la sortie avec la structure exacte de la capture d'écran et des styles inline pour garantir l'affichage
            $output .= '<div class="goldwizard-variation-price-display" data-shortcode="true" style="display: flex !important; flex-wrap: wrap !important; align-items: center !important; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif !important; font-weight: 300 !important; line-height: 1.2 !important; background-color: transparent !important; padding: 0 !important; margin: 5px 0 !important;">';
            $output .= '<span class="regular-price" style="text-decoration: line-through !important; color: #777 !important; font-size: 16px !important; margin-right: 10px !important;">' . $regular_price_formatted . '</span>';
            $output .= '<span class="sale-price" style="font-weight: bold !important; color: #0E1B4D !important; font-size: 16px !important; margin-right: 10px !important;">' . $sale_price_formatted . '</span>';
            $output .= '<span class="economisez" style="color: #D9534F !important; font-size: 14px !important;">' . 'Économisez ' . $percentage . '%</span>';
            
            // Ajouter les frais de livraison si hide_shipping n'est pas "yes"
            if ($atts['hide_shipping'] !== 'yes') {
                $output .= '<span class="frais-livraison" style="color: #5CB85C !important; font-size: 14px !important; display: block !important; width: 100% !important; margin-top: 5px !important;">Frais de livraison offerts.</span>';
            }
            
            $output .= '</div>';
        } else {
            // Si le produit n'est pas en promotion, afficher seulement le prix normal
            $price = $product->get_price();
            $price_formatted = wc_price($price);
            
            $output .= '<div class="goldwizard-variation-price-display" data-shortcode="true" style="display: flex !important; flex-wrap: wrap !important; align-items: center !important; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif !important; font-weight: 300 !important; line-height: 1.2 !important; background-color: transparent !important; padding: 0 !important; margin: 5px 0 !important;">';
            $output .= '<span class="sale-price" style="font-weight: bold !important; color: #0E1B4D !important; font-size: 16px !important; margin-right: 10px !important;">' . $price_formatted . '</span>';
            
            // Ajouter les frais de livraison si hide_shipping n'est pas "yes"
            if ($atts['hide_shipping'] !== 'yes') {
                $output .= '<span class="frais-livraison" style="color: #5CB85C !important; font-size: 14px !important; display: block !important; width: 100% !important; margin-top: 5px !important;">Frais de livraison offerts.</span>';
            }
            
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Ajouter le pourcentage de réduction aux blocs de produits WooCommerce
     */
    public function add_reduction_to_product_blocks($html, $data, $product) {
        if ($product->is_on_sale()) {
            $regular_price = (float) $product->get_regular_price();
            $sale_price = (float) $product->get_sale_price();
            
            // Pour les produits variables, obtenir le prix régulier et le prix de vente
            if ($product->is_type('variable')) {
                $prices = $product->get_variation_prices();
                if (!empty($prices['regular_price']) && !empty($prices['sale_price'])) {
                    $regular_price = min($prices['regular_price']);
                    $sale_price = min($prices['sale_price']);
                }
            }
            
            if ($regular_price > 0) {
                $percentage = round(100 - ($sale_price / $regular_price * 100));
                $reduction_html = '<span class="goldwizard-reduction" data-product-id="' . esc_attr($product->get_id()) . '">';
                $reduction_html .= sprintf(__('Économisez %d%%', 'goldwizard-core'), $percentage);
                $reduction_html .= '</span>';
                
                // Insérer le HTML de réduction dans le HTML du produit
                $html = str_replace('<li class="wc-block-grid__product">', '<li class="wc-block-grid__product">' . $reduction_html, $html);
            }
        }
        
        return $html;
    }
    
    /**
     * Ajouter le JavaScript pour remplacer dynamiquement les shortcodes dans les contextes AJAX
     */
    public function add_reduction_js() {
        ?>
        <script type="text/javascript">
        (function($) {
            // Fonction pour remplacer les shortcodes par le HTML de réduction
            function replaceReductionShortcodes() {
                // Trouver tous les éléments contenant le shortcode
                $('body').find(':contains("[reduction_woocommerce]"), :contains("[goldwizard_reduction]")').each(function() {
                    var $element = $(this);
                    var content = $element.html();
                    
                    // Vérifier si le contenu contient le shortcode
                    if (content && (content.indexOf('[reduction_woocommerce]') !== -1 || content.indexOf('[goldwizard_reduction]') !== -1)) {
                        // Obtenir l'ID du produit du contexte
                        var productId = 0;
                        
                        // Essayer de trouver l'ID du produit à partir de l'URL
                        var urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('product_id')) {
                            productId = urlParams.get('product_id');
                        } else if (urlParams.has('id')) {
                            productId = urlParams.get('id');
                        }
                        
                        // Essayer de trouver l'ID du produit à partir des attributs data
                        if (!productId) {
                            var $product = $element.closest('.product, [data-product-id]');
                            if ($product.length) {
                                productId = $product.data('product-id') || $product.attr('id').replace('product-', '');
                            }
                        }
                        
                        // Si nous avons un ID de produit, remplacer le shortcode
                        if (productId) {
                            // Faire une requête AJAX pour obtenir le HTML de réduction
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                type: 'POST',
                                data: {
                                    action: 'get_reduction_html',
                                    product_id: productId
                                },
                                success: function(response) {
                                    if (response) {
                                        // Remplacer les shortcodes par le HTML de réduction
                                        content = content.replace(/\[reduction_woocommerce\]/g, response);
                                        content = content.replace(/\[goldwizard_reduction\]/g, response);
                                        $element.html(content);
                                    }
                                }
                            });
                        }
                    }
                });
            }
            
            // Exécuter la fonction au chargement de la page
            $(document).ready(function() {
                replaceReductionShortcodes();
            });
            
            // Exécuter la fonction lorsque le contenu est mis à jour via AJAX
            $(document).ajaxComplete(function() {
                replaceReductionShortcodes();
            });
            
            // Exécuter la fonction lorsque le contenu est mis à jour via les événements de WooCommerce
            $(document).on('wc_fragments_refreshed wc_fragments_loaded updated_checkout updated_cart_totals', function() {
                replaceReductionShortcodes();
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Fonction AJAX pour récupérer le HTML de réduction
     */
    public function ajax_get_reduction_html() {
        // Vérifier si l'ID du produit est fourni
        if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
            wp_send_json('');
        }
        
        $product_id = absint($_POST['product_id']);
        
        // Récupérer le produit
        $product = wc_get_product($product_id);
        
        // Vérifier si le produit existe
        if (!$product) {
            wp_send_json('');
        }
        
        // Vérifier si le produit est en promotion
        if ($product->is_on_sale()) {
            // Récupérer les prix
            $regular_price = (float) $product->get_regular_price();
            $sale_price = (float) $product->get_sale_price();
            
            // Pour les produits variables, obtenir le prix régulier et le prix de vente
            if ($product->is_type('variable')) {
                $prices = $product->get_variation_prices();
                if (!empty($prices['regular_price']) && !empty($prices['sale_price'])) {
                    $regular_price = min($prices['regular_price']);
                    $sale_price = min($prices['sale_price']);
                }
            }
            
            // Calculer le pourcentage d'économie
            if ($regular_price > 0) {
                $percentage = round(100 - ($sale_price / $regular_price * 100));
                
                // Créer le HTML pour afficher le message
                $output = '<span class="goldwizard-reduction">';
                $output .= sprintf(__('Économisez %d%%', 'goldwizard-core'), $percentage);
                $output .= '</span>';
                
                wp_send_json($output);
            }
        }
        
        wp_send_json('');
    }

    /**
     * Ajouter un hook pour les vues rapides et autres contextes AJAX
     */
    public function quickview_support() {
        // Ajouter le support pour les vues rapides qui utilisent l'action woocommerce_before_single_product
        add_action('woocommerce_before_single_product', array($this, 'set_global_product'), 5);
        
        // Ajouter le support pour les vues rapides qui utilisent l'action woocommerce_single_product_summary
        add_action('woocommerce_single_product_summary', array($this, 'set_global_product'), 5);
    }

    /**
     * Fonction pour définir le produit global dans les contextes AJAX
     */
    public function set_global_product() {
        global $product;
        
        // Si le produit global n'est pas défini et que nous avons un ID de produit dans la requête
        if (!$product && isset($_REQUEST['product_id'])) {
            $product_id = absint($_REQUEST['product_id']);
            $product = wc_get_product($product_id);
        }
    }

    /**
     * Ajouter les styles CSS dans le head
     */
    public function add_reduction_css() {
        ?>
        <style type="text/css">
            /* Cacher les frais de livraison dans les blocs projet */
            .bloc-projet .goldwizard-variation-price-display .frais-livraison,
            [class*="bloc-projet"] .goldwizard-variation-price-display .frais-livraison {
                display: none !important;
            }
            
            /* Cacher le prix WooCommerce par défaut sur les pages produit */
            .woocommerce div.product p.price,
            .woocommerce div.product span.price,
            .breakdance-woocommerce div.product p.price,
            .breakdance-woocommerce div.product span.price {
                display: none !important;
            }
            
            /* Cacher les éléments avec data-product-page sur les pages autres que produit */
            .products .goldwizard-variation-price-display[data-product-page="true"],
            .wc-block-grid__products .goldwizard-variation-price-display[data-product-page="true"] {
                display: none !important;
            }
            
            /* Annuler les styles de Breakdance pour nos éléments de prix personnalisés */
            .goldwizard-variation-price-display .regular-price,
            .goldwizard-variation-price-display .sale-price,
            .goldwizard-variation-price-display .economisez,
            .goldwizard-variation-price-display .frais-livraison,
            .goldwizard-variation-price-display .woocommerce-Price-amount,
            .goldwizard-variation-price-display .woocommerce-Price-amount bdi,
            .goldwizard-variation-price-display .woocommerce-Price-currencySymbol {
                color: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
                font-size: inherit !important;
                font-family: inherit !important;
                display: inline !important;
            }
            
            /* Styles spécifiques pour écraser ceux de Breakdance */
            .breakdance-woocommerce .goldwizard-variation-price-display .regular-price,
            .breakdance-woocommerce .goldwizard-variation-price-display .regular-price .woocommerce-Price-amount,
            .breakdance-woocommerce .goldwizard-variation-price-display .regular-price .woocommerce-Price-amount bdi {
                text-decoration: line-through !important;
                color: #777 !important;
                font-size: 16px !important;
                font-weight: 300 !important;
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
            }
            
            .breakdance-woocommerce .goldwizard-variation-price-display .sale-price,
            .breakdance-woocommerce .goldwizard-variation-price-display .sale-price .woocommerce-Price-amount,
            .breakdance-woocommerce .goldwizard-variation-price-display .sale-price .woocommerce-Price-amount bdi {
                font-weight: bold !important;
                color: #0E1B4D !important;
                font-size: 16px !important;
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
            }
            
            .breakdance-woocommerce .goldwizard-variation-price-display .economisez {
                color: #D9534F !important;
                font-size: 14px !important;
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
            }
            
            .breakdance-woocommerce .goldwizard-variation-price-display .frais-livraison {
                color: #5CB85C !important;
                font-size: 14px !important;
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
            }
            
            /* NOUVELLES MODIFICATIONS */
            
            /* Cacher le bouton "Effacer" et l'indicateur de stock */
            .reset_variations,
            .stock.in-stock {
                display: none !important;
            }
            
            /* Styliser les labels des variations */
            .variations th.label label,
            .variations th.label .woo-selected-variation-item-name {
                color: #0E1B4D !important;
                font-family: Helvetica, Arial, sans-serif !important;
                font-weight: 300 !important;
                text-transform: uppercase !important;
                margin-bottom: 0px !important;
                letter-spacing: 1.5px !important;
            }
            
            /* Styliser le titre de personnalisation */
            .goldwizard-personnalisation-container h3 {
                color: #0E1B4D !important;
                font-family: Helvetica, Arial, sans-serif !important;
                font-weight: 300 !important;
                text-transform: uppercase !important;
                margin-bottom: 0px !important;
                letter-spacing: 1.5px !important;
            }
            
            /* Forcer la direction des colonnes pour le conteneur de variation */
            .breakdance-woocommerce .single_variation_wrap,
            .breakdance-woocommerce .woocommerce-variation-add-to-cart {
                flex-direction: column !important;
                display: flex !important;
            }
            
            /* Styliser les boutons de variation */
            .variable-items-wrapper.button-variable-items-wrapper .variable-item {
                padding: 7px 15px 7px !important;
                border-radius: 50px !important;
                background-color: transparent !important;
                box-shadow: 0 0 0 1px #e8e8e1 !important;
                transition: all 0.3s ease !important;
            }
            
            /* Styliser les boutons de variation sélectionnés */
            .variable-items-wrapper.button-variable-items-wrapper .variable-item.selected {
                background-color: #000000 !important;
                box-shadow: none !important;
            }
            
            /* Styliser le texte des boutons de variation */
            .variable-items-wrapper.button-variable-items-wrapper .variable-item .variable-item-span {
                color: #0E1B4D !important;
                transition: all 0.3s ease !important;
            }
            
            /* Styliser le texte des boutons de variation sélectionnés */
            .variable-items-wrapper.button-variable-items-wrapper .variable-item.selected .variable-item-span {
                color: #FFFFFF !important;
            }
        </style>
        <?php
    }
}

// Initialiser la classe
GoldWizard_Reduction::instance();
