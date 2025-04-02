<?php
/**
 * Classe pour gérer l'affichage d'images de produit dynamiques
 */
class GoldWizard_Product_Image {
    /**
     * Instance unique de la classe
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique de la classe
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct() {
        // Ajouter le shortcode
        add_shortcode('goldwizard_product_image', array($this, 'product_image_shortcode'));
        add_shortcode('product_image_woocommerce', array($this, 'product_image_shortcode'));
        
        // Ajouter les styles CSS
        add_action('wp_head', array($this, 'add_product_image_css'));
        
        // Ajouter les scripts JavaScript
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enregistrer les scripts
     */
    public function enqueue_scripts() {
        // Enregistrer le script principal
        wp_enqueue_script(
            'goldwizard-product-image',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/goldwizard-product-image.js',
            array('jquery'),
            GOLDWIZARD_CORE_VERSION,
            true
        );
        
        // Ajouter des variables localisées pour le script
        wp_localize_script(
            'goldwizard-product-image',
            'goldwizardProductImage',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('goldwizard-product-image'),
                'i18n' => array(
                    'close' => __('Fermer', 'goldwizard-core'),
                    'next' => __('Suivant', 'goldwizard-core'),
                    'prev' => __('Précédent', 'goldwizard-core'),
                ),
                'breakdanceQuickViewSelector' => '.dwc-quick-view, .dwc-quick-view-fetch-container',
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            )
        );
        
        // Ajouter le support pour les quick views
        add_action('wp_footer', array($this, 'add_quick_view_support'), 20);
        
        // Ajouter des styles CSS globaux
        $this->add_global_styles();
    }
    
    /**
     * Ajouter des styles CSS globaux
     */
    private function add_global_styles() {
        ?>
        <style>
            /* Styles globaux pour les images de produit */
            .goldwizard-product-image-container {
                position: relative;
                margin-bottom: 20px;
            }
            
            /* Styles pour la lightbox */
            .goldwizard-lightbox {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.8);
                z-index: 9999;
                display: none;
            }
            
            /* Styles pour les quick views */
            .dwc-quick-view .goldwizard-lightbox,
            .dwc-quick-view-fetch-container .goldwizard-lightbox {
                z-index: 100000;
            }
            
            /* Empêcher le défilement quand la lightbox est ouverte */
            body.goldwizard-lightbox-open {
                overflow: hidden !important;
            }
        </style>
        <?php
    }
    
    /**
     * Ajouter un support spécifique pour les quick views
     */
    public function add_quick_view_support() {
        ?>
        <script type="text/javascript">
            (function($) {
                // Fonction pour réinitialiser les images dans les quick views
                function reinitQuickViewImages() {
                    console.log('Réinitialisation des images dans le quick view');
                    if (typeof window.goldwizardReinitProductImages === 'function') {
                        window.goldwizardReinitProductImages();
                    }
                }
                
                // Support pour les quick views WooCommerce
                $(document).on('click', '.quick-view-button, .quick-view, .quickview-button, .dwc-quick-view-btn', function() {
                    console.log('Quick view button clicked');
                    setTimeout(reinitQuickViewImages, 500);
                });
                
                // Support pour Breakdance
                $(document).on('breakdance_quickview_opened', function() {
                    console.log('Breakdance quick view opened');
                    setTimeout(reinitQuickViewImages, 500);
                });
                
                // Support pour les variations dans les quick views
                $(document).on('show_variation', function() {
                    console.log('Variation shown');
                    setTimeout(reinitQuickViewImages, 100);
                });
                
                // Support pour la navigation dans les quick views
                $(document).on('click', '.dwc-quick-view-next-btn, .dwc-quick-view-prev-btn', function() {
                    console.log('Quick view navigation');
                    setTimeout(reinitQuickViewImages, 300);
                });
                
                // Support pour les touches de clavier (flèches gauche/droite)
                $(document).on('keydown', function(e) {
                    if ((e.key === 'ArrowLeft' || e.key === 'ArrowRight') && $('.dwc-quick-view-fetch-container').is(':visible')) {
                        console.log('Arrow key navigation in quick view');
                        setTimeout(reinitQuickViewImages, 300);
                    }
                });
                
                // Support général pour les quick views
                $(document).on('ajaxComplete', function(event, xhr, settings) {
                    if (settings.url && (
                        settings.url.indexOf('wc-ajax=get_refreshed_fragments') > -1 ||
                        settings.url.indexOf('wc-ajax=quickview') > -1 ||
                        settings.url.indexOf('quick-view') > -1 ||
                        settings.url.indexOf('breakdance_quickview') > -1
                    )) {
                        console.log('Quick view AJAX detected');
                        setTimeout(reinitQuickViewImages, 500);
                    }
                });
                
                // Réinitialiser après le chargement de la page
                $(window).on('load', function() {
                    setTimeout(reinitQuickViewImages, 500);
                });
                
                // Mutation Observer pour détecter les changements dans le DOM
                if (typeof MutationObserver !== 'undefined') {
                    // Observer les changements dans le DOM pour détecter l'ajout de quick views
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                                // Vérifier si un quick view a été ajouté
                                for (let i = 0; i < mutation.addedNodes.length; i++) {
                                    const node = mutation.addedNodes[i];
                                    if (node.nodeType === 1) {
                                        // Vérifier si le nœud est un quick view ou contient un quick view
                                        const isQuickView = 
                                            (node.classList && (
                                                node.classList.contains('dwc-quick-view') || 
                                                node.classList.contains('quick-view-content') ||
                                                node.classList.contains('dwc-quick-view-fetch-container')
                                            )) || 
                                            $(node).find('.dwc-quick-view, .quick-view-content, .dwc-quick-view-fetch-container').length > 0;
                                        
                                        if (isQuickView) {
                                            console.log('Quick view added to DOM via mutation');
                                            setTimeout(reinitQuickViewImages, 500);
                                            break;
                                        }
                                    }
                                }
                            }
                        });
                    });
                    
                    // Observer le document entier
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }
                
                // Support spécifique pour Breakdance
                $(document).on('breakdance_quickview_loaded', function() {
                    console.log('Breakdance quick view loaded');
                    setTimeout(reinitQuickViewImages, 300);
                });
                
                // Vérifier périodiquement si un quick view est présent
                setInterval(function() {
                    if ($('.dwc-quick-view:visible, .dwc-quick-view-fetch-container:visible, .quick-view-content:visible').length > 0) {
                        reinitQuickViewImages();
                    }
                }, 1000);
                
                // Réinitialiser immédiatement si le document est déjà chargé
                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                    setTimeout(reinitQuickViewImages, 100);
                }
            })(jQuery);
        </script>
        <?php
    }

    /**
     * Shortcode pour afficher l'image du produit
     */
    public function product_image_shortcode($atts) {
        // Extraire les attributs
        $atts = shortcode_atts(array(
            'id' => 0,
            'product_id' => 0,
            'width' => '100%',
            'height' => 'auto',
        ), $atts, 'goldwizard_product_image');
        
        // Récupérer l'ID du produit
        $product_id = absint($atts['id']) ? absint($atts['id']) : absint($atts['product_id']);
        
        // Si aucun ID n'est spécifié, essayer de récupérer le produit courant
        if (!$product_id) {
            global $product;
            
            if (!$product || !is_object($product)) {
                // Essayer de récupérer le produit depuis la requête
                $product_id = get_query_var('product_id');
                
                if (!$product_id) {
                    // Essayer de récupérer le produit depuis le post courant
                    $product_id = get_the_ID();
                }
                
                if ($product_id) {
                    $product = wc_get_product($product_id);
                }
            } else {
                $product_id = $product->get_id();
            }
        } else {
            $product = wc_get_product($product_id);
        }
        
        // Vérifier que le produit existe
        if (!$product || !is_object($product)) {
            return '<div class="goldwizard-error">Produit non trouvé</div>';
        }
        
        // Commencer à capturer la sortie
        ob_start();
        
        // Récupérer l'image principale
        $image_id = $product->get_image_id();
        $image_url = wp_get_attachment_image_url($image_id, 'large');
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        
        // Si pas d'image, utiliser l'image par défaut
        if (!$image_url) {
            $image_url = wc_placeholder_img_src('large');
            $image_alt = __('Image par défaut', 'goldwizard-core');
        } else if (empty($image_alt)) {
            $image_alt = $product->get_name();
        }
        
        // Récupérer les images de la galerie
        $gallery_image_ids = $product->get_gallery_image_ids();
        
        // Ajouter l'image principale au début de la galerie seulement si elle existe
        if ($image_id) {
            array_unshift($gallery_image_ids, $image_id);
        }
        
        // Génération de la sortie HTML
        echo '<div class="goldwizard-product-image-container" data-product-id="' . esc_attr($product_id) . '">';
        
        // Image principale
        echo '<div class="goldwizard-product-main-image">';
        echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" data-default-image="' . esc_url($image_url) . '" />';
        echo '</div>';
        
        // Galerie d'images
        if (count($gallery_image_ids) > 1) {
            echo '<div class="goldwizard-gallery-container">';
            echo '<div class="goldwizard-gallery-nav prev"><span>&lt;</span></div>';
            echo '<div class="goldwizard-gallery-items">';
            
            $first = true;
            foreach ($gallery_image_ids as $gallery_image_id) {
                $gallery_image_url = wp_get_attachment_image_url($gallery_image_id, 'thumbnail');
                $gallery_image_full = wp_get_attachment_image_url($gallery_image_id, 'large');
                $gallery_image_alt = get_post_meta($gallery_image_id, '_wp_attachment_image_alt', true);
                
                // Vérifier que l'URL existe
                if (!$gallery_image_url || !$gallery_image_full) {
                    continue;
                }
                
                if (empty($gallery_image_alt)) {
                    $gallery_image_alt = $product->get_name() . ' - ' . __('Image', 'goldwizard-core');
                }
                
                echo '<div class="goldwizard-gallery-item ' . ($first ? 'active' : '') . '" data-image-id="' . esc_attr($gallery_image_id) . '" data-full-image="' . esc_url($gallery_image_full) . '">';
                echo '<img src="' . esc_url($gallery_image_url) . '" alt="' . esc_attr($gallery_image_alt) . '" />';
                echo '</div>';
                
                $first = false;
            }
            
            echo '</div>';
            echo '<div class="goldwizard-gallery-nav next"><span>&gt;</span></div>';
            echo '</div>';
        }
        
        // Données des variations
        if ($product->is_type('variable')) {
            echo '<div class="goldwizard-variation-images-data" style="display: none;">';
            
            $variations = $product->get_available_variations();
            
            foreach ($variations as $variation) {
                if (isset($variation['image_id']) && $variation['image_id']) {
                    $variation_image_url = wp_get_attachment_image_url($variation['image_id'], 'large');
                    
                    // Vérifier que l'URL existe
                    if (!$variation_image_url) {
                        continue;
                    }
                    
                    $attributes = array();
                    
                    foreach ($variation['attributes'] as $key => $value) {
                        $attributes[] = $key . '=' . $value;
                    }
                    
                    $attributes_string = implode('&', $attributes);
                    
                    echo '<div class="variation-image-data" data-image-id="' . esc_attr($variation['image_id']) . '" data-image-url="' . esc_url($variation_image_url) . '" data-attributes="' . esc_attr($attributes_string) . '"></div>';
                }
            }
            
            echo '</div>';
        }
        
        // Lightbox
        echo '<div class="goldwizard-lightbox" style="display: none;">';
        echo '<div class="goldwizard-lightbox-content">';
        echo '<div class="goldwizard-lightbox-close">&times;</div>';
        echo '<div class="goldwizard-lightbox-image-container">';
        echo '<img class="goldwizard-lightbox-image" src="" alt="' . esc_attr($product->get_name()) . '" />';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // CSS inline pour le conteneur
        ?>
        <style>
            .goldwizard-product-image-container {
                width: <?php echo esc_attr($atts['width']); ?>;
                height: <?php echo esc_attr($atts['height']); ?>;
                position: relative;
                margin-bottom: 20px;
            }
            
            .goldwizard-product-main-image {
                width: 100%;
                height: 0;
                padding-bottom: 100%;
                position: relative;
                overflow: hidden;
                cursor: pointer;
                border: 1px solid #eee;
                border-radius: 4px;
            }
            
            .goldwizard-product-main-image img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            
            .goldwizard-gallery-container {
                display: flex;
                align-items: center;
                margin-top: 10px;
                position: relative;
            }
            
            .goldwizard-gallery-items {
                display: flex;
                overflow-x: auto;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                -ms-overflow-style: none;
                flex-grow: 1;
            }
            
            .goldwizard-gallery-items::-webkit-scrollbar {
                display: none;
            }
            
            .goldwizard-gallery-item {
                flex: 0 0 auto;
                width: 60px;
                height: 60px;
                margin-right: 10px;
                border: 1px solid #eee;
                border-radius: 4px;
                cursor: pointer;
                overflow: hidden;
                transition: border-color 0.3s;
            }
            
            .goldwizard-gallery-item.active {
                border-color: #0a3152;
            }
            
            .goldwizard-gallery-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .goldwizard-gallery-nav {
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #f5f5f5;
                border-radius: 50%;
                cursor: pointer;
                z-index: 1;
                flex-shrink: 0;
                transition: background-color 0.3s;
            }
            
            .goldwizard-gallery-nav:hover {
                background-color: #e0e0e0;
            }
            
            .goldwizard-gallery-nav.prev {
                margin-right: 10px;
            }
            
            .goldwizard-gallery-nav.next {
                margin-left: 10px;
            }
            
            /* Lightbox */
            .goldwizard-lightbox {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.8);
                z-index: 9999;
                display: none;
            }
            
            .goldwizard-lightbox-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                max-width: 90%;
                max-height: 90%;
            }
            
            .goldwizard-lightbox-close {
                position: absolute;
                top: -40px;
                right: 0;
                color: white;
                font-size: 30px;
                cursor: pointer;
                z-index: 10000;
            }
            
            .goldwizard-lightbox-image-container {
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .goldwizard-lightbox-image {
                max-width: 100%;
                max-height: 80vh;
                object-fit: contain;
            }
            
            /* Support pour les quick views */
            .dwc-quick-view .goldwizard-product-image-container,
            .dwc-quick-view-fetch-container .goldwizard-product-image-container,
            .quick-view-content .goldwizard-product-image-container,
            .woocommerce-quick-view .goldwizard-product-image-container {
                width: 100%;
                height: auto;
            }
            
            .dwc-quick-view .goldwizard-gallery-container,
            .dwc-quick-view-fetch-container .goldwizard-gallery-container,
            .quick-view-content .goldwizard-gallery-container,
            .woocommerce-quick-view .goldwizard-gallery-container {
                margin-top: 5px;
            }
            
            .dwc-quick-view .goldwizard-gallery-item,
            .dwc-quick-view-fetch-container .goldwizard-gallery-item,
            .quick-view-content .goldwizard-gallery-item,
            .woocommerce-quick-view .goldwizard-gallery-item {
                width: 50px;
                height: 50px;
                margin-right: 5px;
            }
            
            /* Styles spécifiques pour les quick views Breakdance */
            .dwc-quick-view .goldwizard-lightbox,
            .dwc-quick-view-fetch-container .goldwizard-lightbox {
                z-index: 100000; /* Z-index plus élevé pour passer au-dessus du quick view */
            }
            
            /* Style pour le body quand la lightbox est ouverte */
            body.goldwizard-lightbox-open {
                overflow: hidden;
            }
        </style>
        <?php
        
        // Récupérer la sortie
        return ob_get_clean();
    }

    /**
     * Ajouter les styles CSS dans le head
     */
    public function add_product_image_css() {
        ?>
        <style type="text/css">
            /* Conteneur principal */
            .goldwizard-product-image-container {
                width: 100%;
                max-width: 600px;
                margin: 0 auto;
                position: relative;
            }
            
            /* Image principale */
            .goldwizard-product-main-image {
                position: relative;
                width: 100%;
                margin-bottom: 10px;
                overflow: hidden;
                border-radius: 8px;
                cursor: pointer;
            }
            
            .goldwizard-product-main-image img {
                width: 100%;
                height: auto;
                display: block;
                transition: transform 0.3s ease;
            }
            
            .goldwizard-product-main-image:hover img {
                transform: scale(1.05);
            }
            
            .goldwizard-zoom-icon {
                position: absolute;
                bottom: 10px;
                right: 10px;
                background-color: rgba(0, 0, 0, 0.5);
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .goldwizard-product-main-image:hover .goldwizard-zoom-icon {
                opacity: 1;
            }
            
            /* Galerie d'images */
            .goldwizard-product-gallery {
                display: flex;
                align-items: center;
                width: 100%;
                position: relative;
            }
            
            .goldwizard-gallery-items {
                display: flex;
                overflow-x: auto;
                scroll-behavior: smooth;
                scrollbar-width: none; /* Firefox */
                -ms-overflow-style: none; /* IE and Edge */
                gap: 10px;
                padding: 5px 0;
                flex-grow: 1;
            }
            
            .goldwizard-gallery-items::-webkit-scrollbar {
                display: none; /* Chrome, Safari, Opera */
            }
            
            .goldwizard-gallery-item {
                flex: 0 0 80px;
                height: 80px;
                border-radius: 4px;
                overflow: hidden;
                cursor: pointer;
                border: 2px solid transparent;
                transition: border-color 0.3s ease;
            }
            
            .goldwizard-gallery-item.active {
                border-color: #0E1B4D;
            }
            
            .goldwizard-gallery-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .goldwizard-gallery-nav {
                width: 30px;
                height: 30px;
                background-color: #fff;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                z-index: 2;
                flex-shrink: 0;
                transition: background-color 0.3s ease;
            }
            
            .goldwizard-gallery-nav:hover {
                background-color: #f5f5f5;
            }
            
            .goldwizard-gallery-nav.prev {
                margin-right: 10px;
            }
            
            .goldwizard-gallery-nav.next {
                margin-left: 10px;
            }
            
            /* Lightbox */
            .goldwizard-lightbox {
                display: none;
                position: fixed;
                z-index: 9999;
                padding-top: 100px;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.9);
            }
            
            .goldwizard-lightbox-content {
                position: relative;
                margin: auto;
                padding: 0;
                width: 90%;
                max-width: 1200px;
            }
            
            .goldwizard-lightbox-close {
                color: white;
                position: absolute;
                top: 10px;
                right: 25px;
                font-size: 35px;
                font-weight: bold;
                cursor: pointer;
            }
            
            .goldwizard-lightbox-image {
                display: block;
                width: 100%;
                max-height: 80vh;
                object-fit: contain;
            }
            
            .goldwizard-lightbox-caption {
                margin: auto;
                width: 80%;
                max-width: 700px;
                text-align: center;
                color: #ccc;
                padding: 10px 0;
                height: 150px;
            }
        </style>
        <?php
    }
}

// Initialiser la classe
GoldWizard_Product_Image::instance();
