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
            'hide_plugins_menu' => true,
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
            'admin_notice_title' => 'Besoin d\'aide pour l\'entretien et la maintenance de votre site Web ?',
            'admin_notice_button' => 'Découvrez nos offres',
            'dashboard_widget_title' => 'Besoin d\'aide ?',
            'dashboard_widget_content' => 'Vous avez des questions ou besoin d\'assistance ? <a href="#" target="_blank">Contactez-nous ici</a>.',
            'admin_bar_title' => 'Besoin d\'aide ?',
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
        $this->config['texts']['dashboard_widget_content'] = 'Vous avez des questions ou besoin d\'assistance ? <a href="' . $this->config['custom_links']['contact'] . '" target="_blank">Contactez-nous ici</a>.';
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
            error_log('GoldWizard Admin Customizer: Configuration invalide, email administrateur manquant');
            return;
        }
        
        // Ajouter les hooks pour vérifier l'utilisateur après l'initialisation de WordPress
        add_action('admin_init', array($this, 'setup_admin_customization'), 1);
        
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
        // S'assurer que tous les menus sont visibles pour l'administrateur principal
        // Cette action doit être exécutée en dernier pour surcharger toutes les autres
        add_action('admin_menu', array($this, 'ensure_all_menus_for_admin'), 9999);
        
        // Vérifier si l'utilisateur actuel est l'administrateur principal
        $current_user = wp_get_current_user();
        $is_main_admin = ($current_user && $current_user->user_email === $this->config['admin_email']);
        
        // Si c'est l'administrateur principal, ne pas appliquer les restrictions
        if ($is_main_admin) {
            // Ajouter un message de débogage
            add_action('admin_footer', function() {
                echo '<script>console.log("GoldWizard: Administrateur principal détecté, pas de restrictions appliquées");</script>';
            });
            
            // Restaurer les capacités pour l'administrateur principal
            $this->restore_admin_capabilities();
            
            // Ajouter un bouton pour restaurer les menus
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-info is-dismissible">
                    <p><strong>GoldWizard</strong> - Vous êtes connecté en tant qu'administrateur principal.</p>
                    <p><button id="goldwizard-restore-menus-btn" class="button button-primary">Restaurer tous les menus</button></p>
                </div>
                <script>
                document.getElementById('goldwizard-restore-menus-btn').addEventListener('click', function() {
                    // Créer les menus manquants
                    var adminMenu = document.getElementById('adminmenu');
                    if (!adminMenu) return;
                    
                    // Définir les menus à restaurer
                    var menusToRestore = [
                        {id: 'menu-plugins', label: 'Extensions', icon: 'dashicons-admin-plugins', url: 'plugins.php'},
                        {id: 'menu-tools', label: 'Outils', icon: 'dashicons-admin-tools', url: 'tools.php'},
                        {id: 'menu-settings', label: 'Réglages', icon: 'dashicons-admin-settings', url: 'options-general.php'},
                        {id: 'menu-comments', label: 'Commentaires', icon: 'dashicons-admin-comments', url: 'edit-comments.php'},
                        {id: 'toplevel_page_cfw-settings', label: 'CheckoutWC', icon: 'dashicons-cart', url: 'admin.php?page=cfw-settings'},
                        {id: 'toplevel_page_snippets', label: 'Snippets', icon: 'dashicons-editor-code', url: 'admin.php?page=snippets'},
                        {id: 'toplevel_page_activity_log_page', label: 'Journal d\'activité', icon: 'dashicons-backup', url: 'admin.php?page=activity_log_page'}
                    ];
                    
                    // Restaurer chaque menu
                    menusToRestore.forEach(function(menu) {
                        if (!document.getElementById(menu.id)) {
                            var menuItem = document.createElement('li');
                            menuItem.id = menu.id;
                            menuItem.className = 'menu-top menu-icon-generic';
                            menuItem.innerHTML = `
                                <a href="${menu.url}" class="menu-top">
                                    <div class="wp-menu-arrow"><div></div></div>
                                    <div class="wp-menu-image dashicons-before ${menu.icon}"><br></div>
                                    <div class="wp-menu-name">${menu.label}</div>
                                </a>
                            `;
                            adminMenu.appendChild(menuItem);
                            console.log('Menu restauré: ' + menu.label);
                        }
                    });
                    
                    alert('Menus restaurés avec succès! Rafraîchissez la page pour voir les changements.');
                });
                </script>
                <?php
            });
            
            // Forcer l'affichage de tous les menus via CSS
            add_action('admin_head', function() {
                echo '<style>
                    /* Forcer affichage menus admin principal */
                    #adminmenu li.menu-top { 
                        display: block !important; 
                    }
                    #adminmenu li.wp-has-submenu ul.wp-submenu { 
                        display: block !important; 
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
                    }
                    /* Sous-menus au survol */
                    #adminmenu li.wp-has-submenu:hover ul.wp-submenu {
                        display: block !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                    }
                </style>';
            });
            
            return; // Ne pas appliquer les autres personnalisations pour l'admin principal
        }
        
        // Personnaliser les capacités des administrateurs
        $this->custom_admin_capabilities();
        
        // Masquer les menus d'administration
        if ($this->config['visibility']['hide_plugins_menu']) {
            add_action('admin_menu', array($this, 'hide_plugins_menu'), 999);
        }
        
        if ($this->config['visibility']['hide_tools_menu']) {
            add_action('admin_menu', array($this, 'hide_tools_menu'), 999);
        }
        
        if ($this->config['visibility']['hide_options_menu']) {
            add_action('admin_menu', array($this, 'hide_options_menu'), 999);
        }
        
        if ($this->config['visibility']['hide_comments_menu']) {
            add_action('admin_menu', array($this, 'hide_comments_menu'), 999);
        }
        
        // Masquer les éléments de l'interface d'administration
        add_action('admin_head', array($this, 'hide_admin_elements_css'), 999);
        
        // Masquer les notices de mises à jour
        add_action('admin_head', array($this, 'hide_update_notices'), 1);
        
        // Masquer le journal d'activité pour tous sauf l'administrateur spécifique
        add_action('admin_menu', array($this, 'hide_activity_log_menu'), 999);
        
        // Personnaliser le pied de page de l'admin
        add_filter('admin_footer_text', array($this, 'custom_admin_footer'));
        
        // Personnaliser le texte de version de WordPress
        add_filter('update_footer', array($this, 'custom_admin_version_text'), 999);
    }
    
    /**
     * Restaurer toutes les capacités pour l'administrateur principal
     */
    public function restore_admin_capabilities() {
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_options');
            $role->add_cap('edit_theme_options');
            $role->add_cap('install_plugins');
            $role->add_cap('activate_plugins');
            $role->add_cap('update_plugins');
            $role->add_cap('delete_plugins');
            $role->add_cap('edit_plugins');
            
            // Ajouter un message de confirmation
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>🔓 Capacités administrateur restaurées avec succès!</p></div>';
            });
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
            'plugins.php', // Page des extensions
            'plugin-install.php', // Installation d'extensions
            'tools.php', // Outils
            'options-general.php', // Paramètres
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
        if ($current_user && $current_user->user_email !== $this->config['admin_email']) {
            // Essayer différentes variantes du nom de menu car les plugins peuvent l'implémenter différemment
            remove_menu_page('activity_log_page');
            remove_menu_page('activity-log-page');
            
            // Masquer les sous-menus
            remove_submenu_page('activity_log_page', 'activity_log_page');
            remove_submenu_page('activity_log_page', 'activity_log_settings');
            remove_submenu_page('activity-log-page', 'activity-log-page');
            remove_submenu_page('activity-log-page', 'activity-log-settings');
            
            // Masquer via CSS au cas où les fonctions ci-dessus ne fonctionnent pas
            add_action('admin_head', function() {
                echo '<style>
                    #toplevel_page_activity_log_page,
                    #toplevel_page_activity-log-page { 
                        display: none !important; 
                    }
                </style>';
            });
        }
    }
    
    /**
     * Masquer les onglets pour tous sauf le compte spécifique
     */
    public function custom_hide_menu_items() {
        $current_user = wp_get_current_user();

        // Si ce n'est pas l'administrateur principal, on masque les éléments
        if ($current_user && $current_user->user_email !== $this->config['admin_email']) {
            // Masquer les menus pour tous les autres utilisateurs, y compris les administrateurs
            if ($this->config['visibility']['hide_plugins_menu']) {
                remove_menu_page('plugins.php'); // Masque le menu des extensions
            }
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
     * Ajouter une permission pour l'utilisateur spécifique
     * et masquer les onglets pour tous les autres.
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
        
        // Vérifier si l'utilisateur actuel est l'administrateur principal
        if ($current_user && $current_user->user_email === $this->config['admin_email']) {
            // Afficher un message de confirmation pour l'administrateur principal
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>🚀 Permissions restaurées avec succès pour l\'administrateur principal !</p></div>';
            });
        }
        
        // Ne pas supprimer les capacités des autres administrateurs
        // Nous avons modifié cette approche pour éviter les problèmes d'accès
    }
    
    /**
     * Afficher les sous-menus Extensions pour un administrateur spécifique
     */
    public function ensure_extensions_menu_for_admin() {
        $current_user = wp_get_current_user();

        // Si l'utilisateur est celui spécifié
        if ($current_user && $current_user->user_email === $this->config['admin_email']) {
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
            // Masquer les sous-menus Extensions pour les autres utilisateurs
            remove_submenu_page('plugins.php', 'plugins.php'); // Supprime le sous-menu "Extensions"
            remove_submenu_page('plugins.php', 'plugin-install.php'); // Supprime le sous-menu "Ajouter une extension"
            remove_submenu_page('plugins.php', 'plugin-editor.php'); // Supprime le sous-menu "Éditeur"
        }
    }
    
    /**
     * Personnaliser le pied de page de l'admin
     */
    public function custom_admin_footer() {
        return 'Propulsé par GoldWizard';
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
     */
    public function custom_login_logo_title($title) {
        return 'GoldWizard';
    }
    
    /**
     * Masquer le menu Snippets pour tous sauf l'administrateur spécifique
     */
    public function toggle_snippets_menu() {
        $current_user = wp_get_current_user();
        
        // Si ce n'est pas l'administrateur principal, on masque le menu
        if ($current_user && $current_user->user_email !== $this->config['admin_email']) {
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
     * Mettre à jour la configuration
     */
    public function update_config($new_config) {
        $this->config = wp_parse_args($new_config, $this->config);
        return $this->config;
    }
    
    /**
     * Obtenir la configuration actuelle
     */
    public function get_config() {
        return $this->config;
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
        $is_main_admin = ($current_user && $current_user->user_email === $this->config['admin_email']);
        
        // Style de base pour tous les utilisateurs
        echo '<style>
            /* Masquer les éléments de l\'interface d\'administration */
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
            #toplevel_page_woocommerce-marketing';
        
        // Ajouter les sélecteurs pour masquer les menus uniquement pour les non-administrateurs principaux
        if (!$is_main_admin) {
            echo ',
            #menu-appearance,
            #menu-plugins,
            #menu-tools,
            #menu-settings,
            #menu-comments,
            #toplevel_page_cfw-settings,
            #toplevel_page_activity_log_page,
            #toplevel_page_activity-log-page,
            #toplevel_page_snippets';
        }
        
        echo ' {
                display: none !important;
            }
        </style>';
        
        // Styles spécifiques pour l'administrateur principal
        if ($is_main_admin) {
            echo '<style>
                /* Forcer l\'affichage des menus pour l\'administrateur principal */
                #menu-plugins,
                #menu-tools,
                #menu-settings,
                #menu-comments,
                #toplevel_page_cfw-settings,
                #toplevel_page_activity_log_page,
                #toplevel_page_activity-log-page,
                #toplevel_page_snippets {
                    display: block !important;
                }
            </style>';
        }
    }
    
    /**
     * Masquer le menu des plugins
     */
    public function hide_plugins_menu() {
        $current_user = wp_get_current_user();
        // Ne pas masquer pour l'administrateur principal
        if ($current_user && $current_user->user_email === $this->config['admin_email']) {
            return;
        }
        remove_menu_page('plugins.php');
    }
    
    /**
     * Masquer le menu des outils
     */
    public function hide_tools_menu() {
        $current_user = wp_get_current_user();
        // Ne pas masquer pour l'administrateur principal
        if ($current_user && $current_user->user_email === $this->config['admin_email']) {
            return;
        }
        remove_menu_page('tools.php');
    }
    
    /**
     * Masquer le menu des options
     */
    public function hide_options_menu() {
        $current_user = wp_get_current_user();
        // Ne pas masquer pour l'administrateur principal
        if ($current_user && $current_user->user_email === $this->config['admin_email']) {
            return;
        }
        remove_menu_page('options-general.php');
    }
    
    /**
     * Masquer le menu des commentaires
     */
    public function hide_comments_menu() {
        $current_user = wp_get_current_user();
        // Ne pas masquer pour l'administrateur principal
        if ($current_user && $current_user->user_email === $this->config['admin_email']) {
            return;
        }
        remove_menu_page('edit-comments.php');
    }
    
    /**
     * S'assurer que tous les menus sont visibles pour l'administrateur principal
     */
    public function ensure_all_menus_for_admin() {
        $current_user = wp_get_current_user();
        
        // Vérifier si l'utilisateur actuel est l'administrateur principal
        if ($current_user && $current_user->user_email === $this->config['admin_email']) {
            // Ajouter un message de débogage dans la console
            add_action('admin_footer', function() {
                echo '<script>
                    console.log("DEBUG: Restauration des menus pour admin principal");
                    console.log("DEBUG: Email admin configuré: ' . esc_js($this->config['admin_email']) . '");
                    console.log("DEBUG: Email utilisateur actuel: ' . esc_js(wp_get_current_user()->user_email) . '");
                </script>';
            });
            
            // Forcer l'affichage de tous les menus via CSS avec priorité plus élevée
            add_action('admin_head', function() {
                echo '<style>
                    /* Forcer affichage menus admin principal - Priorité maximale */
                    #adminmenu li.menu-top { 
                        display: block !important; 
                    }
                    #adminmenu li.wp-has-submenu ul.wp-submenu { 
                        display: block !important; 
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
                    }
                    /* Sous-menus au survol */
                    #adminmenu li.wp-has-submenu:hover ul.wp-submenu {
                        display: block !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                    }
                </style>';
            }, 9999); // Priorité très élevée pour s'assurer que ce CSS est appliqué en dernier
            
            // Script JavaScript pour restaurer les menus dynamiquement après le chargement de la page
            add_action('admin_footer', function() {
                echo '<script>
                    // Fonction pour restaurer les menus cachés
                    function goldwizardRestoreMenus() {
                        console.log("Exécution de la restauration des menus...");
                        
                        // Définir les menus à restaurer
                        var menusToRestore = [
                            {id: "menu-plugins", label: "Extensions", icon: "dashicons-admin-plugins", url: "plugins.php"},
                            {id: "menu-tools", label: "Outils", icon: "dashicons-admin-tools", url: "tools.php"},
                            {id: "menu-settings", label: "Réglages", icon: "dashicons-admin-settings", url: "options-general.php"},
                            {id: "menu-comments", label: "Commentaires", icon: "dashicons-admin-comments", url: "edit-comments.php"},
                            {id: "toplevel_page_cfw-settings", label: "CheckoutWC", icon: "dashicons-cart", url: "admin.php?page=cfw-settings"},
                            {id: "toplevel_page_snippets", label: "Snippets", icon: "dashicons-editor-code", url: "admin.php?page=snippets"},
                            {id: "toplevel_page_activity_log_page", label: "Journal d\'activité", icon: "dashicons-backup", url: "admin.php?page=activity_log_page"}
                        ];
                        
                        // Restaurer les menus existants
                        menusToRestore.forEach(function(menu) {
                            var menuElement = document.getElementById(menu.id);
                            if (menuElement) {
                                menuElement.style.display = "block";
                                console.log("Menu restauré: " + menu.label);
                            }
                        });
                        
                        // Créer les menus manquants
                        var adminMenu = document.getElementById("adminmenu");
                        if (adminMenu) {
                            menusToRestore.forEach(function(menu) {
                                if (!document.getElementById(menu.id)) {
                                    var menuItem = document.createElement("li");
                                    menuItem.id = menu.id;
                                    menuItem.className = "menu-top menu-icon-generic";
                                    menuItem.innerHTML = `
                                        <a href="${menu.url}" class="menu-top">
                                            <div class="wp-menu-arrow"><div></div></div>
                                            <div class="wp-menu-image dashicons-before ${menu.icon}"><br></div>
                                            <div class="wp-menu-name">${menu.label}</div>
                                        </a>
                                    `;
                                    adminMenu.appendChild(menuItem);
                                    console.log("Menu créé: " + menu.label);
                                }
                            });
                        }
                    }
                    
                    // Exécuter immédiatement
                    goldwizardRestoreMenus();
                    
                    // Exécuter à nouveau après un court délai
                    setTimeout(goldwizardRestoreMenus, 500);
                </script>';
            }, 9999);
            
            // Restaurer les capacités pour s'assurer que l'utilisateur peut accéder aux pages
            $role = get_role('administrator');
            if ($role) {
                $role->add_cap('manage_options');
                $role->add_cap('edit_theme_options');
                $role->add_cap('install_plugins');
                $role->add_cap('activate_plugins');
                $role->add_cap('update_plugins');
                $role->add_cap('delete_plugins');
                $role->add_cap('edit_plugins');
            }
            
            // Ajouter des menus spécifiques si nécessaire
            $this->ensure_menu_item('plugins.php', 'Extensions', 'activate_plugins', 'dashicons-admin-plugins', 65);
            $this->ensure_menu_item('tools.php', 'Outils', 'edit_posts', 'dashicons-admin-tools', 75);
            $this->ensure_menu_item('options-general.php', 'Réglages', 'manage_options', 'dashicons-admin-settings', 80);
        }
    }
    
    /**
     * Fonction utilitaire pour s'assurer qu'un élément de menu existe
     */
    public function ensure_menu_item($menu_slug, $menu_title, $capability, $icon, $position) {
        global $menu;
        
        // Vérifier si le menu existe déjà
        $menu_exists = false;
        if (is_array($menu)) {
            foreach ($menu as $item) {
                if (isset($item[2]) && $item[2] === $menu_slug) {
                    $menu_exists = true;
                    break;
                }
            }
        }
        
        // Ajouter le menu s'il n'existe pas
        if (!$menu_exists) {
            add_menu_page(
                $menu_title,
                $menu_title,
                $capability,
                $menu_slug,
                '',
                $icon,
                $position
            );
        }
    }
}

// Fonction d'aide pour obtenir l'instance
function GoldWizard_Admin_Customizer() {
    return GoldWizard_Admin_Customizer::instance();
}
