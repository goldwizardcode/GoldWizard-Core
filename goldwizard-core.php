<?php
/**
 * Plugin Name: GoldWizard Core
 * Description: Extension professionnelle qui ajoute des fonctionnalités de personnalisation et de réduction pour WooCommerce.
 * Version: 1.0.2
 * Author: GoldWizard
 * Text Domain: goldwizard-core
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

// Déclaration de compatibilité avec WooCommerce HPOS (High-Performance Order Storage)
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes
define('GOLDWIZARD_CORE_VERSION', '1.0.1');
define('GOLDWIZARD_CORE_FILE', __FILE__);
define('GOLDWIZARD_CORE_PATH', plugin_dir_path(__FILE__));
define('GOLDWIZARD_CORE_URL', plugin_dir_url(__FILE__));
define('GOLDWIZARD_CORE_BASENAME', plugin_basename(__FILE__));

// Constante pour activer/désactiver la personnalisation de l'admin
// Mettre à false pour désactiver complètement la personnalisation de l'admin
if (!defined('GOLDWIZARD_ENABLE_ADMIN_CUSTOMIZATION')) {
    define('GOLDWIZARD_ENABLE_ADMIN_CUSTOMIZATION', false); // Désactivation complète de la personnalisation de l'admin
}

/**
 * DOCUMENTATION: PERSONNALISATION DE L'ADMIN
 * 
 * Pour désactiver complètement la personnalisation de l'admin, ajoutez cette ligne dans votre fichier wp-config.php :
 * define('GOLDWIZARD_ENABLE_ADMIN_CUSTOMIZATION', false);
 * 
 * Pour personnaliser la configuration, utilisez le filtre 'goldwizard_admin_customizer_config' dans votre thème ou plugin :
 * 
 * add_filter('goldwizard_admin_customizer_config', function($config) {
 *     // Changer l'email administrateur
 *     $config['admin_email'] = 'votre@email.com';
 *     
 *     // Changer les couleurs
 *     $config['colors']['primary'] = '#333333';
 *     
 *     // Masquer des menus supplémentaires
 *     $config['visibility']['hide_additional_menus'] = array('edit.php', 'upload.php');
 *     
 *     // Désactiver certaines options de visibilité
 *     $config['visibility']['hide_comments_menu'] = false;
 *     
 *     return $config;
 * });
 */

/**
 * Classe principale de l'extension
 */
class GoldWizard_Core {
    /**
     * Instance unique de la classe
     */
    private static $instance = null;
    
    /**
     * Instances des classes principales
     */
    protected $reduction;
    protected $personnalisation;
    protected $admin_customizer;
    protected $product_image;

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
        // Vérifier si WooCommerce est activé
        add_action('plugins_loaded', array($this, 'check_woocommerce'));
        
        // Charger les fichiers requis
        $this->includes();
        
        // Initialiser les hooks
        $this->init_hooks();
    }
    
    /**
     * Vérifier si WooCommerce est activé
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
    }
    
    /**
     * Afficher un message si WooCommerce n'est pas activé
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>' . __('GoldWizard Core nécessite WooCommerce pour fonctionner. Veuillez installer et activer WooCommerce.', 'goldwizard-core') . '</p></div>';
    }
    
    /**
     * Inclure les fichiers requis
     */
    public function includes() {
        // Inclure les classes
        require_once GOLDWIZARD_CORE_PATH . 'includes/class-goldwizard-reduction.php';
        require_once GOLDWIZARD_CORE_PATH . 'includes/class-goldwizard-product-image.php';
        require_once GOLDWIZARD_CORE_PATH . 'includes/class-goldwizard-personnalisation.php';
        
        // La classe de personnalisation de l'admin est désactivée
        // require_once GOLDWIZARD_CORE_PATH . 'includes/class-goldwizard-admin-customizer.php';
    }
    
    /**
     * Initialiser les hooks
     */
    public function init_hooks() {
        // Débogage
        error_log('GoldWizard Core: Initialisation des hooks');
        
        try {
            // Initialiser la réduction
            error_log('GoldWizard Core: Initialisation de la réduction');
            $this->reduction = new GoldWizard_Reduction();
            
            // Initialiser la personnalisation
            error_log('GoldWizard Core: Initialisation de la personnalisation');
            $this->personnalisation = GoldWizard_Personnalisation::instance();
            
            // La personnalisation de l'admin est désactivée
            /*
            error_log('GoldWizard Core: Vérification de la personnalisation de l\'admin');
            if (class_exists('GoldWizard_Admin_Customizer') && GOLDWIZARD_ENABLE_ADMIN_CUSTOMIZATION) {
                error_log('GoldWizard Core: Initialisation de la personnalisation de l\'admin');
                // Utiliser la fonction d'aide pour obtenir l'instance
                $this->admin_customizer = GoldWizard_Admin_Customizer();
            }
            */
            
            // Initialiser l'image produit (en utilisant la méthode instance() car le constructeur est privé)
            error_log('GoldWizard Core: Initialisation de l\'image produit');
            $this->product_image = GoldWizard_Product_Image::instance();
            
            // Ajouter les hooks
            error_log('GoldWizard Core: Ajout des hooks');
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            
            // Les fonctions liées à la personnalisation de l'admin sont désactivées
            // add_action('admin_init', array($this, 'restore_plugin_capabilities'), 1);
            // add_action('admin_init', array($this, 'debug_admin_menus'), 1);
            
            error_log('GoldWizard Core: Initialisation terminée avec succès');
        } catch (Exception $e) {
            error_log('GoldWizard Core ERROR: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialiser l'extension
     */
    public function init() {
        // Charger les traductions
        load_plugin_textdomain('goldwizard-core', false, dirname(GOLDWIZARD_CORE_BASENAME) . '/languages');
    }

    /**
     * Enregistrer les scripts et les styles
     */
    public function enqueue_scripts() {
        // Enregistrer les styles
        wp_enqueue_style('goldwizard-reduction', GOLDWIZARD_CORE_URL . 'assets/css/goldwizard-reduction.css', array(), GOLDWIZARD_CORE_VERSION . '.' . time(), 'all');
        wp_enqueue_style('goldwizard-personnalisation', GOLDWIZARD_CORE_URL . 'assets/css/goldwizard-personnalisation.css', array(), GOLDWIZARD_CORE_VERSION . '.' . time(), 'all');
        wp_enqueue_style('formulaire-reservation', GOLDWIZARD_CORE_URL . 'assets/css/formulaire-reservation.css', array(), GOLDWIZARD_CORE_VERSION . '.' . time(), 'all');
        wp_enqueue_style('goldwizard-additional', GOLDWIZARD_CORE_URL . 'assets/css/goldwizard-additional.css', array(), GOLDWIZARD_CORE_VERSION . '.' . time(), 'all');
        
        // Ajouter un hook pour charger les styles avec une priorité très élevée
        add_action('wp_head', array($this, 'add_inline_styles'), 999);
        
        // Enregistrer les scripts
        wp_enqueue_script('goldwizard-variation-price', GOLDWIZARD_CORE_URL . 'assets/js/goldwizard-variation-price.js', array('jquery'), GOLDWIZARD_CORE_VERSION . '.' . time(), true);
        
        // N'enregistrer les scripts de personnalisation que sur les pages produit et quickview
        if (is_product() || isset($_REQUEST['wc-ajax']) || isset($_REQUEST['product_id']) || isset($_REQUEST['quickview']) || isset($_REQUEST['breakdance']) || isset($_REQUEST['quick-view'])) {
            wp_enqueue_script('goldwizard-personnalisation', GOLDWIZARD_CORE_URL . 'assets/js/goldwizard-personnalisation.js', array('jquery'), GOLDWIZARD_CORE_VERSION, true);
            
            // Ajouter les variables locales pour le script
            wp_localize_script('goldwizard-personnalisation', 'goldwizard_personnalisation_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('goldwizard_personnalisation_nonce'),
                'max_file_size' => wp_max_upload_size(),
                'i18n' => array(
                    'upload_error' => __('Erreur lors de l\'upload. Veuillez réessayer.', 'goldwizard-core'),
                    'file_too_large' => __('Le fichier est trop volumineux.', 'goldwizard-core'),
                    'invalid_file_type' => __('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.', 'goldwizard-core'),
                    'remove_image' => __('Supprimer', 'goldwizard-core'),
                    'select_images' => __('Sélectionner des photos', 'goldwizard-core'),
                    'uploading' => __('Téléchargement en cours...', 'goldwizard-core'),
                )
            ));
        }
    }
    
    /**
     * Ajouter des styles CSS inline
     */
    public function add_inline_styles() {
        ?>
        <style type="text/css">
            /* Styles pour les boutons d'achat rapide */
            .btn-achat-rapide,
            .dwc-quick-view-btn,
            .quick-view-button,
            [data-quick-view],
            .quickview-button {
                display: inline-block;
                background-color: #0E1B4D;
                color: white;
                padding: 8px 15px;
                border-radius: 4px;
                text-decoration: none;
                margin-top: 10px;
                font-weight: bold;
                transition: background-color 0.3s ease;
            }
            
            .btn-achat-rapide:hover,
            .dwc-quick-view-btn:hover,
            .quick-view-button:hover,
            [data-quick-view]:hover,
            .quickview-button:hover {
                background-color: #0a1435;
                color: white;
                text-decoration: none;
            }
            
            /* Cacher les frais de livraison dans les blocs projet */
            .bloc-projet .goldwizard-variation-price-display .frais-livraison,
            [class*="bloc-projet"] .goldwizard-variation-price-display .frais-livraison {
                display: none !important;
            }
        </style>
        <?php
    }

    /**
     * Enregistrer les scripts et styles pour l'admin
     */
    public function admin_enqueue_scripts($hook) {
        // N'enregistrer les scripts que sur les pages d'édition de produit
        if ($hook != 'post.php' && $hook != 'post-new.php') {
            return;
        }
        
        global $post;
        if (!$post || $post->post_type != 'product') {
            return;
        }
        
        // Enregistrer le style admin
        wp_enqueue_style('goldwizard-admin', GOLDWIZARD_CORE_URL . 'assets/css/goldwizard-admin.css', array(), GOLDWIZARD_CORE_VERSION);
    }
    
    /**
     * Restaurer les permissions des extensions pour tous les administrateurs
     * Cette fonction est utile en cas de problème avec les permissions
     */
    public function restore_plugin_capabilities() {
        // Vérifier si la constante est définie pour forcer la restauration
        if (defined('GOLDWIZARD_RESTORE_CAPABILITIES') && GOLDWIZARD_RESTORE_CAPABILITIES) {
            $role = get_role('administrator');
            if ($role) {
                $role->add_cap('install_plugins');
                $role->add_cap('activate_plugins');
                $role->add_cap('update_plugins');
                $role->add_cap('delete_plugins');
                $role->add_cap('edit_plugins');
                
                // Afficher un message de confirmation
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>🚀 Permissions des extensions restaurées avec succès pour tous les administrateurs !</p></div>';
                });
            }
        }
    }

    /**
     * Fonction de débogage pour les menus cachés
     * Cette fonction est utile pour vérifier pourquoi les menus sont cachés
     */
    public function debug_admin_menus() {
        // Vérifier si l'utilisateur est connecté et est un administrateur
        if (!is_admin() || !current_user_can('administrator')) {
            return;
        }
        
        // Obtenir l'utilisateur actuel
        $current_user = wp_get_current_user();
        
        // Ajouter des logs de débogage dans la console JavaScript
        add_action('admin_footer', function() use ($current_user) {
            ?>
            <script>
                console.log("======= DÉBOGAGE GOLDWIZARD =======");
                console.log("Utilisateur actuel: <?php echo esc_js($current_user->user_login); ?>");
                console.log("Email utilisateur: <?php echo esc_js($current_user->user_email); ?>");
                console.log("Rôles: <?php echo esc_js(implode(', ', $current_user->roles)); ?>");
                console.log("Capacités: ");
                <?php 
                foreach ($current_user->allcaps as $cap => $value) {
                    if ($value) {
                        echo 'console.log("  - ' . esc_js($cap) . '");';
                    }
                }
                ?>
                
                // Vérifier les menus cachés
                console.log("Menus visibles/cachés:");
                setTimeout(function() {
                    var menus = [
                        { id: "#menu-plugins", nom: "Extensions" },
                        { id: "#menu-tools", nom: "Outils" },
                        { id: "#menu-settings", nom: "Réglages" },
                        { id: "#menu-comments", nom: "Commentaires" },
                        { id: "#toplevel_page_cfw-settings", nom: "CheckoutWC" },
                        { id: "#toplevel_page_snippets", nom: "Snippets" },
                        { id: "#toplevel_page_activity_log_page", nom: "Journal d'activité" }
                    ];
                    
                    menus.forEach(function(menu) {
                        var element = document.querySelector(menu.id);
                        if (element) {
                            var style = window.getComputedStyle(element);
                            console.log("Menu " + menu.nom + ": " + (style.display !== "none" ? "VISIBLE" : "CACHÉ"));
                        } else {
                            console.log("Menu " + menu.nom + ": NON TROUVÉ");
                        }
                    });
                    
                    // Forcer l'affichage des menus
                    console.log("Tentative de restauration des menus...");
                    menus.forEach(function(menu) {
                        var element = document.querySelector(menu.id);
                        if (element) {
                            element.style.display = "block";
                            console.log("Menu " + menu.nom + " restauré via JavaScript");
                        }
                    });
                }, 1000);
            </script>
            <?php
        });
        
        // Ajouter un bouton de débogage dans l'interface d'administration
        add_action('admin_notices', function() use ($current_user) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong>Débogage GoldWizard</strong><br>
                    Utilisateur: <?php echo esc_html($current_user->user_login); ?><br>
                    Email: <?php echo esc_html($current_user->user_email); ?><br>
                    <button id="goldwizard-restore-menus" class="button button-primary">Restaurer les menus cachés</button>
                </p>
            </div>
            <script>
                document.getElementById('goldwizard-restore-menus').addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    var menus = [
                        "#menu-plugins", 
                        "#menu-tools", 
                        "#menu-settings", 
                        "#menu-comments", 
                        "#toplevel_page_cfw-settings", 
                        "#toplevel_page_snippets", 
                        "#toplevel_page_activity_log_page"
                    ];
                    
                    menus.forEach(function(menuId) {
                        var element = document.querySelector(menuId);
                        if (element) {
                            element.style.display = "block";
                            console.log("Menu " + menuId + " restauré manuellement");
                        }
                    });
                    
                    alert("Tentative de restauration des menus terminée. Vérifiez la console pour plus de détails.");
                });
            </script>
            <?php
        });
    }

    /**
     * Ajouter le lien de configuration dans la page des extensions
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=goldwizard_core') . '">' . __('Paramètres', 'goldwizard-core') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

/**
 * Fonction d'aide pour obtenir l'instance de GoldWizard_Core
 */
function GoldWizard_Core() {
    return GoldWizard_Core::instance();
}

// Initialiser l'extension
GoldWizard_Core();
