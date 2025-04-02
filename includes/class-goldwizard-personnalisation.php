<?php
/**
 * Classe pour la fonctionnalité de personnalisation WooCommerce
 *
 * @package GoldWizard_Core
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe GoldWizard_Personnalisation
 */
class GoldWizard_Personnalisation {
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
    private function __construct() {
        // Ajouter les champs de personnalisation avant le bouton d'ajout au panier
        add_action('woocommerce_before_add_to_cart_button', array($this, 'personnalisation_fields'), 10);
        
        // Support pour Breakdance Builder Quick View
        add_action('breakdance_quickview_before_add_to_cart_button', array($this, 'personnalisation_fields'), 10);
        add_action('bde_quickview_before_add_to_cart_button', array($this, 'personnalisation_fields'), 10);
        add_action('breakdance_before_add_to_cart_button', array($this, 'personnalisation_fields'), 10);
        
        // Support pour les vues rapides personnalisées
        add_action('woocommerce_single_product_summary', array($this, 'personnalisation_fields_for_quickview'), 25);
        
        // Valider les champs lors de l'ajout au panier
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validation'), 10, 3);
        
        // Ajouter les données de personnalisation aux métadonnées du panier
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        
        // Afficher les données de personnalisation dans le panier
        add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 10, 2);
        
        // Ajouter les données de personnalisation aux métadonnées de la commande
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_item_meta'), 10, 4);
        
        // Ajouter le support AJAX pour l'upload d'images
        add_action('wp_ajax_goldwizard_personnalisation_upload_image', array($this, 'ajax_upload_image'));
        add_action('wp_ajax_nopriv_goldwizard_personnalisation_upload_image', array($this, 'ajax_upload_image'));
        
        // Ajouter les options de personnalisation dans l'admin des produits (une seule fois)
        add_action('woocommerce_product_options_general_product_data', array($this, 'admin_product_options'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_options'));
        
        // Ajouter un script pour initialiser les champs dans les vues rapides
        add_action('wp_footer', array($this, 'quickview_support_js'));
        
        // Ajouter un hook pour la vue rapide personnalisée
        add_action('wp_ajax_goldwizard_load_personnalisation_fields', array($this, 'ajax_load_personnalisation_fields'));
        add_action('wp_ajax_nopriv_goldwizard_load_personnalisation_fields', array($this, 'ajax_load_personnalisation_fields'));
        
        // Ajouter le script JavaScript pour l'admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    /**
     * Ajouter les options de personnalisation dans l'admin des produits
     */
    public function admin_product_options() {
        global $woocommerce, $post;
        
        echo '<div class="options_group">';
        
        // Titre de la section
        echo '<h3>' . __('Options de personnalisation', 'goldwizard-core') . '</h3>';
        
        // Option pour activer l'upload d'images
        woocommerce_wp_checkbox(array(
            'id' => '_enable_image_upload',
            'label' => __('Activer l\'upload d\'images', 'goldwizard-core'),
            'description' => __('Cochez cette case pour permettre aux clients d\'uploader des images pour personnaliser ce produit.', 'goldwizard-core')
        ));
        
        // Option pour activer les champs textuels
        woocommerce_wp_checkbox(array(
            'id' => '_enable_text_fields',
            'label' => __('Activer les champs textuels', 'goldwizard-core'),
            'description' => __('Cochez cette case pour permettre aux clients de saisir du texte pour personnaliser ce produit.', 'goldwizard-core')
        ));
        
        echo '</div>';
        
        // Récupérer les champs textuels existants
        $text_fields = get_post_meta($post->ID, '_personnalisation_text_fields', true);
        if (!is_array($text_fields)) {
            $text_fields = array();
        }
        
        echo '<div class="options_group personnalisation-text-fields" style="padding: 10px; border: 1px solid #eee; margin: 10px 0; background-color: #f8f8f8;">';
        
        // Titre de la section avec style
        echo '<h4 style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;">' . __('Configuration des champs textuels personnalisés', 'goldwizard-core') . '</h4>';
        
        // Instructions
        echo '<p class="description" style="margin-bottom: 15px;">' . __('Configurez les champs textuels que vos clients pourront remplir lors de l\'achat de ce produit. Ces champs apparaîtront sur la page produit et dans la vue rapide.', 'goldwizard-core') . '</p>';
        
        // Conteneur pour les champs
        echo '<div id="personnalisation_text_fields_container">';
        
        // Afficher les champs existants
        if (!empty($text_fields)) {
            foreach ($text_fields as $index => $field) {
                $this->text_field_html($index, $field);
            }
        }
        
        echo '</div>';
        
        // Bouton pour ajouter un nouveau champ
        echo '<p style="margin-top: 15px;"><button type="button" class="button button-primary add_text_field" id="add_text_field_button">' . __('Ajouter un champ textuel', 'goldwizard-core') . '</button></p>';
        
        // Template pour les nouveaux champs (caché)
        echo '<div id="personnalisation_text_field_template" style="display: none;">';
        $this->text_field_html('{{index}}', array(
            'label' => '',
            'type' => 'text',
            'placeholder' => '',
            'description' => '',
            'required' => '1'
        ));
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Sauvegarder les options de personnalisation
     */
    public function save_product_options($post_id) {
        // Sauvegarder l'option d'upload d'images
        $enable_image_upload = isset($_POST['_enable_image_upload']) ? 'yes' : 'no';
        update_post_meta($post_id, '_enable_image_upload', $enable_image_upload);
        
        // Sauvegarder l'option de champs textuels
        $enable_text_fields = isset($_POST['_enable_text_fields']) ? 'yes' : 'no';
        update_post_meta($post_id, '_enable_text_fields', $enable_text_fields);
        
        // Vérifier si les champs textuels sont activés
        if ($enable_text_fields === 'yes') {
            $this->save_text_fields_options($post_id);
        } else {
            update_post_meta($post_id, '_personnalisation_text_fields', array());
        }
    }

    /**
     * HTML pour un champ textuel
     */
    public function text_field_html($index, $field) {
        ?>
        <div class="personnalisation-text-field" style="padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; background-color: #fff; border-radius: 4px; position: relative;">
            <span class="remove-text-field" style="position: absolute; top: 10px; right: 10px; cursor: pointer; color: #a00; font-weight: bold;">×</span>
            
            <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                <div style="flex: 1; min-width: 250px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('Libellé du champ', 'goldwizard-core'); ?>
                    </label>
                    <input type="text" name="personnalisation_text_fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr($field['label']); ?>" style="width: 100%;" placeholder="<?php _e('Ex: Texte à graver', 'goldwizard-core'); ?>" />
                    <p class="description" style="margin-top: 5px; font-style: italic;">
                        <?php _e('Ce texte sera affiché comme libellé du champ sur la page produit.', 'goldwizard-core'); ?>
                    </p>
                </div>
                
                <div style="flex: 1; min-width: 250px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('Type de champ', 'goldwizard-core'); ?>
                    </label>
                    <select name="personnalisation_text_fields[<?php echo $index; ?>][type]" style="width: 100%;">
                        <option value="text" <?php selected($field['type'], 'text'); ?>><?php _e('Texte court', 'goldwizard-core'); ?></option>
                        <option value="textarea" <?php selected($field['type'], 'textarea'); ?>><?php _e('Texte long', 'goldwizard-core'); ?></option>
                        <option value="number" <?php selected($field['type'], 'number'); ?>><?php _e('Nombre', 'goldwizard-core'); ?></option>
                        <option value="email" <?php selected($field['type'], 'email'); ?>><?php _e('Email', 'goldwizard-core'); ?></option>
                        <option value="tel" <?php selected($field['type'], 'tel'); ?>><?php _e('Téléphone', 'goldwizard-core'); ?></option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 15px;">
                <div style="flex: 1; min-width: 250px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('Placeholder', 'goldwizard-core'); ?>
                    </label>
                    <input type="text" name="personnalisation_text_fields[<?php echo $index; ?>][placeholder]" value="<?php echo esc_attr($field['placeholder']); ?>" style="width: 100%;" placeholder="<?php _e('Ex: Saisissez votre texte ici', 'goldwizard-core'); ?>" />
                    <p class="description" style="margin-top: 5px; font-style: italic;">
                        <?php _e('Texte d\'exemple qui apparaît dans le champ vide.', 'goldwizard-core'); ?>
                    </p>
                </div>
                
                <div style="flex: 1; min-width: 250px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('Description', 'goldwizard-core'); ?>
                    </label>
                    <input type="text" name="personnalisation_text_fields[<?php echo $index; ?>][description]" value="<?php echo esc_attr($field['description']); ?>" style="width: 100%;" placeholder="<?php _e('Ex: Maximum 50 caractères', 'goldwizard-core'); ?>" />
                    <p class="description" style="margin-top: 5px; font-style: italic;">
                        <?php _e('Texte d\'aide affiché sous le champ.', 'goldwizard-core'); ?>
                    </p>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <label style="display: inline-flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="personnalisation_text_fields[<?php echo $index; ?>][required]" value="1" <?php checked($field['required'], '1'); ?> style="margin-right: 8px;" />
                    <span style="font-weight: bold;"><?php _e('Champ obligatoire', 'goldwizard-core'); ?></span>
                </label>
                <p class="description" style="margin-top: 5px; font-style: italic;">
                    <?php _e('Si coché, le client devra obligatoirement remplir ce champ pour ajouter le produit au panier.', 'goldwizard-core'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Sauvegarder les options de champs textuels
     */
    public function save_text_fields_options($post_id) {
        // Récupérer les champs textuels
        $text_fields = isset($_POST['personnalisation_text_fields']) ? $_POST['personnalisation_text_fields'] : array();
        
        // Nettoyer les données et filtrer les champs vides
        $clean_text_fields = array();
        if (!empty($text_fields)) {
            foreach ($text_fields as $field) {
                // Ne pas ajouter les champs sans libellé (vides)
                if (empty(trim($field['label']))) {
                    continue;
                }
                
                $clean_text_fields[] = array(
                    'label' => sanitize_text_field($field['label']),
                    'type' => sanitize_text_field($field['type']),
                    'placeholder' => sanitize_text_field($field['placeholder']),
                    'description' => sanitize_text_field($field['description']),
                    'required' => isset($field['required']) ? '1' : '0'
                );
            }
        }
        
        // Sauvegarder les champs textuels
        update_post_meta($post_id, '_personnalisation_text_fields', $clean_text_fields);
        
        // Si aucun champ n'est défini après nettoyage, désactiver l'option
        if (empty($clean_text_fields)) {
            update_post_meta($post_id, '_enable_text_fields', 'no');
        }
    }

    /**
     * Vérifier si le produit nécessite une personnalisation
     */
    public function is_required($product_id, $type = 'image') {
        if ($type === 'image') {
            return get_post_meta($product_id, '_enable_image_upload', true) === 'yes';
        } elseif ($type === 'text') {
            return get_post_meta($product_id, '_enable_text_fields', true) === 'yes';
        }
        
        return false;
    }

    /**
     * Récupérer les champs de personnalisation textuelle pour un produit
     */
    public function get_text_fields($product_id) {
        $text_fields = get_post_meta($product_id, '_personnalisation_text_fields', true);
        if (!is_array($text_fields)) {
            $text_fields = array();
        }
        
        return $text_fields;
    }

    /**
     * Afficher les champs de personnalisation
     */
    public function personnalisation_fields() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        $enable_image_upload = $this->is_required($product_id, 'image');
        $enable_text_fields = $this->is_required($product_id, 'text');
        
        // Si aucune personnalisation n'est activée, ne rien afficher
        if (!$enable_image_upload && !$enable_text_fields) {
            return;
        }
        
        echo '<div class="goldwizard-personnalisation-container">';
        echo '<h3>' . __('Personnalisation', 'goldwizard-core') . '</h3>';
        
        // Afficher la zone d'upload d'images
        if ($enable_image_upload) {
            echo '<div class="goldwizard-personnalisation-image-upload">';
            echo '<label>' . __('Vos photos', 'goldwizard-core') . ' <span class="required">*</span></label>';
            echo '<div id="goldwizard-personnalisation-dropzone" class="goldwizard-personnalisation-dropzone">';
            echo '<div class="goldwizard-personnalisation-dropzone-inner">';
            echo '<button type="button" class="goldwizard-personnalisation-select-button">' . __('Sélectionner des photos', 'goldwizard-core') . '</button>';
            echo '<p class="goldwizard-personnalisation-dropzone-text">' . __('ou glissez-déposez vos photos ici', 'goldwizard-core') . '</p>';
            echo '</div>';
            echo '</div>';
            echo '<div id="goldwizard-personnalisation-preview" class="goldwizard-personnalisation-preview"></div>';
            echo '<input type="hidden" name="goldwizard_personnalisation_images" id="goldwizard-personnalisation-image-data" value="">';
            echo '<input type="file" id="goldwizard-personnalisation-images" style="display: none;" multiple accept="image/jpeg,image/png,image/gif">';
            echo '</div>';
        }
        
        // Afficher les champs textuels
        if ($enable_text_fields) {
            $text_fields = $this->get_text_fields($product_id);
            
            if (!empty($text_fields)) {
                echo '<div class="goldwizard-personnalisation-text-fields">';
                
                foreach ($text_fields as $index => $field) {
                    $field_id = 'goldwizard_personnalisation_text_' . $index;
                    $required = $field['required'] === '1' ? ' <span class="required">*</span>' : '';
                    
                    echo '<div class="goldwizard-personnalisation-text-field">';
                    echo '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label']) . $required . '</label>';
                    
                    if ($field['type'] === 'textarea') {
                        echo '<textarea id="' . esc_attr($field_id) . '" name="goldwizard_personnalisation_text[' . $index . ']" placeholder="' . esc_attr($field['placeholder']) . '"' . ($field['required'] === '1' ? ' required' : '') . '></textarea>';
                    } else {
                        echo '<input type="text" id="' . esc_attr($field_id) . '" name="goldwizard_personnalisation_text[' . $index . ']" placeholder="' . esc_attr($field['placeholder']) . '"' . ($field['required'] === '1' ? ' required' : '') . '>';
                    }
                    
                    if (!empty($field['description'])) {
                        echo '<p class="description">' . esc_html($field['description']) . '</p>';
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>';
            }
        }
        
        echo '</div>';
        
        // Marquer les champs comme affichés
        do_action('goldwizard_personnalisation_fields_displayed');
    }

    /**
     * Fonction pour afficher les champs de personnalisation dans les vues rapides
     */
    public function personnalisation_fields_for_quickview() {
        // Vérifier si nous sommes dans une vue rapide
        $is_quickview = false;
        
        // Vérifier les paramètres de requête explicites pour les vues rapides
        if (isset($_REQUEST['quickview']) || isset($_REQUEST['breakdance']) || isset($_REQUEST['quick-view'])) {
            $is_quickview = true;
        }
        
        // Vérifier si nous sommes dans une requête AJAX
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $is_quickview = true;
        }
        
        // Vérifier le référent pour des motifs de vue rapide
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            if (strpos($referer, 'quick-view') !== false || 
                strpos($referer, 'quickview') !== false || 
                strpos($referer, 'popup') !== false) {
                $is_quickview = true;
            }
        }
        
        // Vérifier si nous sommes sur une page produit standard (éviter la duplication)
        if (is_product() && !$is_quickview) {
            return;
        }
        
        // Vérifier si les champs sont déjà présents sur la page
        if (did_action('goldwizard_personnalisation_fields_displayed')) {
            return;
        }
        
        // Seulement si nous sommes dans une vue rapide, afficher les champs
        if ($is_quickview) {
            $this->personnalisation_fields();
        }
    }

    /**
     * Valider les champs lors de l'ajout au panier
     */
    public function validation($passed, $product_id, $quantity) {
        $enable_image_upload = $this->is_required($product_id, 'image');
        $enable_text_fields = $this->is_required($product_id, 'text');
        
        // Valider l'upload d'images
        if ($enable_image_upload) {
            $images = isset($_POST['goldwizard_personnalisation_images']) ? sanitize_text_field($_POST['goldwizard_personnalisation_images']) : '';
            
            if (empty($images)) {
                wc_add_notice(__('Veuillez uploader au moins une image pour personnaliser ce produit.', 'goldwizard-core'), 'error');
                $passed = false;
            }
        }
        
        // Valider les champs textuels
        if ($enable_text_fields) {
            $text_fields = $this->get_text_fields($product_id);
            $text_values = isset($_POST['goldwizard_personnalisation_text']) ? $_POST['goldwizard_personnalisation_text'] : array();
            
            foreach ($text_fields as $index => $field) {
                if ($field['required'] === '1' && (!isset($text_values[$index]) || trim($text_values[$index]) === '')) {
                    wc_add_notice(sprintf(__('Le champ "%s" est obligatoire.', 'goldwizard-core'), $field['label']), 'error');
                    $passed = false;
                }
            }
        }
        
        return $passed;
    }

    /**
     * Ajouter les données de personnalisation aux métadonnées du panier
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $enable_image_upload = $this->is_required($product_id, 'image');
        $enable_text_fields = $this->is_required($product_id, 'text');
        
        // Ajouter les images
        if ($enable_image_upload && isset($_POST['goldwizard_personnalisation_images'])) {
            $cart_item_data['goldwizard_personnalisation_images'] = sanitize_text_field($_POST['goldwizard_personnalisation_images']);
        }
        
        // Ajouter les champs textuels
        if ($enable_text_fields && isset($_POST['goldwizard_personnalisation_text'])) {
            $text_fields = $this->get_text_fields($product_id);
            $text_values = $_POST['goldwizard_personnalisation_text'];
            
            $cart_item_data['goldwizard_personnalisation_text'] = array();
            
            foreach ($text_fields as $index => $field) {
                if (isset($text_values[$index])) {
                    $cart_item_data['goldwizard_personnalisation_text'][$index] = array(
                        'label' => $field['label'],
                        'value' => sanitize_textarea_field($text_values[$index])
                    );
                }
            }
        }
        
        return $cart_item_data;
    }

    /**
     * Afficher les données de personnalisation dans le panier
     */
    public function get_item_data($item_data, $cart_item) {
        // Afficher les images
        if (isset($cart_item['goldwizard_personnalisation_images'])) {
            $images = json_decode(stripslashes($cart_item['goldwizard_personnalisation_images']), true);
            
            if (!empty($images)) {
                $image_count = count($images);
                
                $item_data[] = array(
                    'key' => __('Images personnalisées', 'goldwizard-core'),
                    'value' => sprintf(_n('%d image', '%d images', $image_count, 'goldwizard-core'), $image_count)
                );
            }
        }
        
        // Afficher les champs textuels
        if (isset($cart_item['goldwizard_personnalisation_text'])) {
            foreach ($cart_item['goldwizard_personnalisation_text'] as $field) {
                $item_data[] = array(
                    'key' => $field['label'],
                    'value' => $field['value']
                );
            }
        }
        
        return $item_data;
    }

    /**
     * Ajouter les données de personnalisation aux métadonnées de la commande
     */
    public function add_order_item_meta($item, $cart_item_key, $values, $order) {
        // Ajouter les images
        if (isset($values['goldwizard_personnalisation_images'])) {
            $images = json_decode(stripslashes($values['goldwizard_personnalisation_images']), true);
            
            if (!empty($images)) {
                $image_urls = array();
                
                foreach ($images as $image) {
                    $image_urls[] = $image['url'];
                }
                
                $item->add_meta_data(__('Images personnalisées', 'goldwizard-core'), implode(', ', $image_urls));
            }
        }
        
        // Ajouter les champs textuels
        if (isset($values['goldwizard_personnalisation_text'])) {
            foreach ($values['goldwizard_personnalisation_text'] as $field) {
                $item->add_meta_data($field['label'], $field['value']);
            }
        }
    }

    /**
     * Fonction AJAX pour l'upload d'images
     */
    public function ajax_upload_image() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'goldwizard_personnalisation_nonce')) {
            wp_send_json_error(array('message' => __('Erreur de sécurité. Veuillez rafraîchir la page et réessayer.', 'goldwizard-core')));
        }
        
        // Vérifier si un fichier a été uploadé
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            wp_send_json_error(array('message' => __('Aucun fichier n\'a été uploadé.', 'goldwizard-core')));
        }
        
        // Vérifier le type de fichier
        $file_type = wp_check_filetype(basename($_FILES['file']['name']));
        if (!$file_type['ext'] || !in_array($file_type['type'], array('image/jpeg', 'image/jpg', 'image/png', 'image/gif'))) {
            wp_send_json_error(array('message' => __('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.', 'goldwizard-core')));
        }
        
        // Vérifier la taille du fichier
        if ($_FILES['file']['size'] > wp_max_upload_size()) {
            wp_send_json_error(array('message' => __('Le fichier est trop volumineux.', 'goldwizard-core')));
        }
        
        // Uploader le fichier
        $upload = wp_upload_bits($_FILES['file']['name'], null, file_get_contents($_FILES['file']['tmp_name']));
        
        if ($upload['error']) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        // Créer une miniature
        $file_path = $upload['file'];
        $file_url = $upload['url'];
        $thumb_url = $file_url;
        
        // Générer une miniature si possible
        $editor = wp_get_image_editor($file_path);
        if (!is_wp_error($editor)) {
            $editor->resize(100, 100, true);
            $thumb_file = $editor->save();
            
            if (!is_wp_error($thumb_file)) {
                $thumb_url = str_replace(basename($file_url), basename($thumb_file['path']), $file_url);
            }
        }
        
        // Envoyer la réponse
        wp_send_json_success(array(
            'url' => $file_url,
            'thumb_url' => $thumb_url
        ));
    }

    /**
     * Fonction AJAX pour charger les champs de personnalisation
     */
    public function ajax_load_personnalisation_fields() {
        // Vérifier si l'ID du produit est fourni
        if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
            wp_send_json_error('Aucun ID de produit fourni');
        }
        
        $product_id = absint($_POST['product_id']);
        
        // Récupérer le produit
        $product = wc_get_product($product_id);
        
        // Vérifier si le produit existe
        if (!$product) {
            wp_send_json_error('Produit non trouvé');
        }
        
        // Définir le produit global pour que les fonctions WooCommerce fonctionnent correctement
        global $post, $product;
        $post = get_post($product_id);
        
        // Réinitialiser l'action pour éviter la détection de duplication
        remove_all_actions('goldwizard_personnalisation_fields_displayed');
        
        // Capturer la sortie des champs de personnalisation
        ob_start();
        $this->personnalisation_fields();
        $fields_html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $fields_html
        ));
    }

    /**
     * Ajouter un script JavaScript pour supporter les vues rapides
     */
    public function quickview_support_js() {
        ?>
        <script type="text/javascript">
        (function($) {
            // Fonction pour initialiser les champs de personnalisation dans les vues rapides
            function initPersonnalisationFields() {
                // Initialiser l'upload d'images
                if (typeof initGoldWizardImageUpload === 'function') {
                    initGoldWizardImageUpload();
                }
                
                // Support pour la vue rapide personnalisée
                $(document).on('click', '.dwc-quick-view-btn, .quick-view-button, [data-quick-view], .quickview-button', function() {
                    var productId = $(this).data('id') || $(this).attr('data-id') || $(this).data('product_id') || $(this).closest('.product').data('product-id');
                    if (productId) {
                        setTimeout(function() {
                            // Vérifier si les champs de personnalisation sont déjà présents
                            if ($('.dwc-quick-view .goldwizard-personnalisation-container, .quick-view-content .goldwizard-personnalisation-container, .quickview-wrapper .goldwizard-personnalisation-container').length === 0) {
                                // Charger les champs de personnalisation via AJAX
                                $.ajax({
                                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                    type: 'POST',
                                    data: {
                                        action: 'goldwizard_load_personnalisation_fields',
                                        product_id: productId
                                    },
                                    success: function(response) {
                                        if (response.success && response.data.html) {
                                            // Vérifier à nouveau si les champs n'ont pas été ajoutés entre-temps
                                            if ($('.dwc-quick-view .goldwizard-personnalisation-container, .quick-view-content .goldwizard-personnalisation-container, .quickview-wrapper .goldwizard-personnalisation-container, .modal-quickview .goldwizard-personnalisation-container').length === 0) {
                                                // Insérer les champs de personnalisation avant le bouton d'ajout au panier dans différents conteneurs
                                                $('.dwc-quick-view .single_add_to_cart_button, .quick-view-content .single_add_to_cart_button, .quickview-wrapper .single_add_to_cart_button, .modal-quickview .single_add_to_cart_button').before(response.data.html);
                                                
                                                // Initialiser l'upload d'images
                                                if (typeof initGoldWizardImageUpload === 'function') {
                                                    initGoldWizardImageUpload();
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }, 1000); // Attendre que la vue rapide soit chargée
                    }
                });
                
                // Écouter les événements AJAX et les événements de vue rapide
                $(document).on('ajaxComplete', function(event, xhr, settings) {
                    // Vérifier si c'est une requête de vue rapide
                    if (settings.url && (
                        settings.url.indexOf('wc-ajax=') !== -1 || 
                        settings.url.indexOf('quickview') !== -1 ||
                        settings.url.indexOf('quick-view') !== -1 ||
                        settings.url.indexOf('breakdance') !== -1
                    )) {
                        // Réinitialiser les champs après un court délai pour s'assurer que le DOM est prêt
                        setTimeout(function() {
                            if (typeof initGoldWizardImageUpload === 'function' && $('.goldwizard-personnalisation-container').length > 0) {
                                initGoldWizardImageUpload();
                            }
                        }, 500);
                    }
                });
                
                // Support étendu pour différents systèmes de vue rapide
                $(document).on('breakdance_quickview_loaded quick-view--active quick-view-displayed quickview-loaded modal-opened popup-opened quick-view-open quickview-open', function() {
                    setTimeout(function() {
                        if (typeof initGoldWizardImageUpload === 'function' && $('.goldwizard-personnalisation-container').length > 0) {
                            initGoldWizardImageUpload();
                        }
                    }, 500);
                });
                
                // Support pour les vues rapides qui utilisent la classe body
                $(document).on('DOMNodeInserted', function(e) {
                    if (
                        $(e.target).hasClass('dwc-quick-view') || 
                        $(e.target).find('.dwc-quick-view').length > 0 ||
                        $(e.target).hasClass('quick-view-content') || 
                        $(e.target).find('.quick-view-content').length > 0 ||
                        $(e.target).hasClass('quickview-wrapper') || 
                        $(e.target).find('.quickview-wrapper').length > 0 ||
                        $(e.target).hasClass('modal-quickview') || 
                        $(e.target).find('.modal-quickview').length > 0
                    ) {
                        setTimeout(function() {
                            if (typeof initGoldWizardImageUpload === 'function' && $('.goldwizard-personnalisation-container').length > 0) {
                                initGoldWizardImageUpload();
                            }
                        }, 500);
                    }
                });
                
                // Observer les changements de classe sur le body
                var bodyObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'class') {
                            var classList = document.body.classList;
                            if (
                                classList.contains('quick-view--active') || 
                                classList.contains('quickview-open') || 
                                classList.contains('quick-view-open') || 
                                classList.contains('modal-open')
                            ) {
                                setTimeout(function() {
                                    if (typeof initGoldWizardImageUpload === 'function' && $('.goldwizard-personnalisation-container').length > 0) {
                                        initGoldWizardImageUpload();
                                    }
                                }, 500);
                            }
                        }
                    });
                });
                
                bodyObserver.observe(document.body, { attributes: true });
            }
            
            // Initialiser au chargement de la page
            $(document).ready(function() {
                initPersonnalisationFields();
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Enqueue les scripts et styles pour l'admin
     */
    public function admin_enqueue_scripts($hook) {
        // N'ajouter les scripts que sur la page d'édition de produit
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        // Vérifier si nous sommes sur un produit WooCommerce
        global $post;
        if (!$post || 'product' !== get_post_type($post)) {
            return;
        }
        
        // Enregistrer et ajouter le script d'administration
        wp_enqueue_script(
            'goldwizard-admin',
            GOLDWIZARD_CORE_URL . 'assets/js/goldwizard-admin.js',
            array('jquery'),
            GOLDWIZARD_CORE_VERSION,
            true
        );
        
        // Enregistrer et ajouter le style d'administration
        wp_enqueue_style(
            'goldwizard-admin',
            GOLDWIZARD_CORE_URL . 'assets/css/goldwizard-admin.css',
            array(),
            GOLDWIZARD_CORE_VERSION
        );
    }
}

// Initialiser la classe
GoldWizard_Personnalisation::instance();
