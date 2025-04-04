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
    define('GOLDWIZARD_ENABLE_ADMIN_CUSTOMIZATION', true); // Activation de la personnalisation de l'admin
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
        
        // Inclure la classe de personnalisation de l'admin
        require_once GOLDWIZARD_CORE_PATH . 'includes/class-goldwizard-admin-customizer.php';
        
        // Inclure le correctif pour l'accès aux extensions (version simplifiée)
        require_once GOLDWIZARD_CORE_PATH . 'fix-plugin-access-simple.php';
    }
    
    /**
     * Initialiser les hooks
     */
    public function init_hooks() {
        try {
            // Initialiser la réduction
            $this->reduction = new GoldWizard_Reduction();
            
            // Initialiser la personnalisation
            $this->personnalisation = GoldWizard_Personnalisation::instance();
            
            // Initialiser la personnalisation de l'admin
            if (class_exists('GoldWizard_Admin_Customizer') && GOLDWIZARD_ENABLE_ADMIN_CUSTOMIZATION) {
                $this->admin_customizer = GoldWizard_Admin_Customizer();
            }
            
            // Initialiser l'image produit (en utilisant la méthode instance() car le constructeur est privé)
            $this->product_image = GoldWizard_Product_Image::instance();
            
            // Ajouter les hooks
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            
            // Ajouter les hooks pour la personnalisation de l'admin
            add_action('admin_init', array($this, 'restore_plugin_capabilities'), 1);
        } catch (Exception $e) {
            // error_log('GoldWizard Core ERROR: ' . $e->getMessage());
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
        wp_enqueue_style('goldwizard-faq-style', GOLDWIZARD_CORE_URL . 'assets/css/goldwizard-faq.css', array(), GOLDWIZARD_CORE_VERSION . '.' . time(), 'all');
        
        // Ajouter un hook pour charger les styles avec une priorité très élevée
        add_action('wp_head', array($this, 'add_inline_styles'), 999);
        
        // Enregistrer les scripts
        wp_enqueue_script('goldwizard-variation-price', GOLDWIZARD_CORE_URL . 'assets/js/goldwizard-variation-price.js', array('jquery'), GOLDWIZARD_CORE_VERSION . '.' . time(), true);
        wp_enqueue_script('goldwizard-faq-script', GOLDWIZARD_CORE_URL . 'assets/js/goldwizard-faq.js', array('jquery'), GOLDWIZARD_CORE_VERSION . '.' . time(), true);
        
        // Ajouter un hook pour s'assurer que le script FAQ est correctement initialisé
        add_action('wp_footer', array($this, 'ensure_faq_initialization'), 999);
        
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
                background-color: #1A2C5E;
                color: white;
            }
        </style>
        <?php
    }
    
    /**
     * S'assurer que le script FAQ est correctement initialisé
     * Cette fonction ajoute un script d'initialisation dans le footer
     */
    public function ensure_faq_initialization() {
        ?>
        <script type="text/javascript">
            (function() {
                // Vérifier si jQuery est disponible
                if (typeof jQuery === 'undefined') {
                    console.error('GoldWizard FAQ: jQuery n\'est pas disponible dans le footer.');
                    return;
                }
                
                // Vérifier si la fonction de réinitialisation existe
                if (typeof window.goldwizardResetFAQ === 'function') {
                    // Attendre que le DOM soit complètement chargé
                    jQuery(document).ready(function() {
                        // Attendre un peu pour s'assurer que tous les scripts sont chargés
                        setTimeout(function() {
                            // Vérifier si des conteneurs FAQ existent
                            var faqContainers = jQuery('.goldwizard-faq-container');
                            if (faqContainers.length > 0) {
                                console.log('GoldWizard FAQ: ' + faqContainers.length + ' conteneur(s) FAQ trouvé(s) - Initialisation forcée');
                                window.goldwizardResetFAQ();
                            }
                        }, 500);
                    });
                    
                    // Écouter les événements de chargement de page dynamique
                    jQuery(document).on('breakdance_loaded ready ajaxComplete', function() {
                        setTimeout(function() {
                            if (typeof window.goldwizardResetFAQ === 'function') {
                                window.goldwizardResetFAQ();
                            }
                        }, 500);
                    });
                }
            })();
        </script>
        <?php
    }

    /**
     * Enregistrer les scripts et styles pour l'admin
     */
    public function admin_enqueue_scripts($hook) {
        // Enregistrer le style admin pour les pages d'édition de produit
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            global $post;
            if ($post && $post->post_type == 'product') {
                wp_enqueue_style('goldwizard-admin', GOLDWIZARD_CORE_URL . 'assets/css/goldwizard-admin.css', array(), GOLDWIZARD_CORE_VERSION);
            }
        }
        
        // Enregistrer le style de personnalisation de l'admin si activé
        if (GOLDWIZARD_ENABLE_ADMIN_CUSTOMIZATION && class_exists('GoldWizard_Admin_Customizer')) {
            wp_enqueue_style('goldwizard-admin-customizer', GOLDWIZARD_CORE_URL . 'assets/css/goldwizard-admin-customizer.css', array(), GOLDWIZARD_CORE_VERSION);
            
            // Récupérer la configuration de personnalisation
            $admin_customizer = GoldWizard_Admin_Customizer();
            $config = $admin_customizer->get_config();
            
            // Ajouter les variables CSS pour la personnalisation
            $custom_css = "
                :root {
                    --goldwizard-primary-color: {$config['colors']['primary']};
                    --goldwizard-secondary-color: {$config['colors']['secondary']};
                    --goldwizard-accent-color: {$config['colors']['accent']};
                    --goldwizard-hover-color: {$config['colors']['hover']};
                    --goldwizard-text-color: {$config['colors']['text']};
                    --goldwizard-logo-url: url('{$config['logo']['url']}');
                    --goldwizard-logo-height: {$config['logo']['height']};
                    --goldwizard-login-logo-height: {$config['logo']['login_height']};
                }
            ";
            
            wp_add_inline_style('goldwizard-admin-customizer', $custom_css);
        }
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
        // Ne rien faire - Fonction désactivée pour éviter les messages de débogage
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
