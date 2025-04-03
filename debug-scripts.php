<?php
/**
 * Script de débogage pour vérifier le chargement des fichiers CSS et JS
 * 
 * Instructions :
 * 1. Copiez ce fichier dans le répertoire de votre thème
 * 2. Ajoutez la ligne suivante dans le fichier functions.php de votre thème :
 *    include_once( get_template_directory() . '/debug-scripts.php' );
 * 3. Visitez n'importe quelle page de votre site
 * 4. Consultez la console du navigateur (F12) pour voir les fichiers chargés
 * 5. N'oubliez pas de supprimer l'inclusion après le débogage
 */

// Ne pas exécuter directement
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajoute un script de débogage pour vérifier les scripts et styles chargés
 */
function goldwizard_debug_enqueued_scripts() {
    // Ne pas exécuter en admin
    if (is_admin()) {
        return;
    }
    
    // Récupérer tous les scripts et styles enregistrés
    global $wp_scripts, $wp_styles;
    
    // Créer des tableaux pour stocker les informations
    $scripts_data = array();
    $styles_data = array();
    
    // Récupérer les informations sur les scripts
    foreach ($wp_scripts->queue as $handle) {
        $scripts_data[] = array(
            'handle' => $handle,
            'src' => $wp_scripts->registered[$handle]->src,
            'deps' => $wp_scripts->registered[$handle]->deps,
            'ver' => $wp_scripts->registered[$handle]->ver
        );
    }
    
    // Récupérer les informations sur les styles
    foreach ($wp_styles->queue as $handle) {
        $styles_data[] = array(
            'handle' => $handle,
            'src' => $wp_styles->registered[$handle]->src,
            'deps' => $wp_styles->registered[$handle]->deps,
            'ver' => $wp_styles->registered[$handle]->ver
        );
    }
    
    // Ajouter un script pour afficher les informations dans la console
    wp_add_inline_script('jquery', '
        console.log("=== GOLDWIZARD DEBUG: SCRIPTS CHARGÉS ===");
        console.log(' . json_encode($scripts_data) . ');
        console.log("=== GOLDWIZARD DEBUG: STYLES CHARGÉS ===");
        console.log(' . json_encode($styles_data) . ');
        
        // Vérifier spécifiquement les fichiers FAQ
        var faqScriptLoaded = false;
        var faqStyleLoaded = false;
        
        ' . json_encode($scripts_data) . '.forEach(function(script) {
            if (script.handle === "goldwizard-faq-script") {
                faqScriptLoaded = true;
                console.log("%cGOLDWIZARD FAQ JS CHARGÉ ✅", "color: green; font-weight: bold");
            }
        });
        
        ' . json_encode($styles_data) . '.forEach(function(style) {
            if (style.handle === "goldwizard-faq-style") {
                faqStyleLoaded = true;
                console.log("%cGOLDWIZARD FAQ CSS CHARGÉ ✅", "color: green; font-weight: bold");
            }
        });
        
        if (!faqScriptLoaded) {
            console.log("%cGOLDWIZARD FAQ JS NON CHARGÉ ❌", "color: red; font-weight: bold");
        }
        
        if (!faqStyleLoaded) {
            console.log("%cGOLDWIZARD FAQ CSS NON CHARGÉ ❌", "color: red; font-weight: bold");
        }
        
        // Vérifier si jQuery est chargé
        if (typeof jQuery !== "undefined") {
            console.log("%cjQuery est chargé (version " + jQuery.fn.jquery + ") ✅", "color: green; font-weight: bold");
        } else {
            console.log("%cjQuery n\'est pas chargé ❌", "color: red; font-weight: bold");
        }
        
        // Vérifier si le script FAQ est initialisé
        jQuery(document).ready(function($) {
            setTimeout(function() {
                if ($(".goldwizard-faq-container").length) {
                    console.log("%cDes conteneurs FAQ ont été trouvés sur la page ✅", "color: green; font-weight: bold");
                    console.log("Nombre de conteneurs FAQ : " + $(".goldwizard-faq-container").length);
                } else {
                    console.log("%cAucun conteneur FAQ trouvé sur la page ❌", "color: red; font-weight: bold");
                }
            }, 1000);
        });
    ');
}
add_action('wp_enqueue_scripts', 'goldwizard_debug_enqueued_scripts', 9999);

/**
 * Ajoute un indicateur visuel pour confirmer que le débogage est actif
 */
function goldwizard_debug_indicator() {
    echo '<div id="goldwizard-debug-indicator" style="position: fixed; bottom: 10px; left: 10px; background-color: rgba(255, 0, 0, 0.7); color: white; padding: 5px 10px; border-radius: 3px; z-index: 9999; font-size: 12px;">GoldWizard Debug Actif</div>';
    echo '<script>setTimeout(function() { document.getElementById("goldwizard-debug-indicator").style.display = "none"; }, 5000);</script>';
}
add_action('wp_footer', 'goldwizard_debug_indicator');
