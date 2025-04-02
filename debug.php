<?php
/**
 * Script de débogage pour GoldWizard
 */

// Charger WordPress
require_once('../../../../wp-load.php');

// Afficher l'en-tête
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Débogage GoldWizard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; }
        h2 { margin-top: 0; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>';

// Charger les styles de WordPress et WooCommerce
wp_head();

echo '</head>
<body>
    <h1>Débogage GoldWizard</h1>';

// 1. Vérifier les shortcodes enregistrés
echo '<div class="debug-section">
    <h2>Shortcodes enregistrés</h2>';
if (isset($GLOBALS['shortcode_tags']) && is_array($GLOBALS['shortcode_tags'])) {
    echo '<pre>';
    foreach ($GLOBALS['shortcode_tags'] as $tag => $func) {
        if (is_array($func)) {
            if (is_object($func[0])) {
                echo htmlspecialchars($tag . ' => ' . get_class($func[0]) . '->' . $func[1]) . "\n";
            } else {
                echo htmlspecialchars($tag . ' => ' . $func[0] . '::' . $func[1]) . "\n";
            }
        } else {
            echo htmlspecialchars($tag . ' => ' . (is_string($func) ? $func : 'Closure')) . "\n";
        }
    }
    echo '</pre>';
} else {
    echo '<p>Aucun shortcode enregistré.</p>';
}
echo '</div>';

// 2. Tester le shortcode reduction_woocommerce
echo '<div class="debug-section">
    <h2>Test du shortcode reduction_woocommerce</h2>';

// Récupérer un produit en promotion
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 1,
    'meta_query' => array(
        array(
            'key' => '_sale_price',
            'value' => 0,
            'compare' => '>',
            'type' => 'NUMERIC'
        )
    )
);
$products = new WP_Query($args);

if ($products->have_posts()) {
    $products->the_post();
    $product_id = get_the_ID();
    
    echo '<p>Produit en promotion trouvé : ' . get_the_title() . ' (ID: ' . $product_id . ')</p>';
    
    // Afficher le HTML généré par le shortcode
    echo '<h3>HTML généré par le shortcode</h3>';
    echo '<pre>' . htmlspecialchars(do_shortcode('[reduction_woocommerce id="' . $product_id . '"]')) . '</pre>';
    
    // Afficher le rendu visuel
    echo '<h3>Rendu visuel</h3>';
    echo '<div style="padding: 20px; border: 1px dashed #ccc;">';
    echo do_shortcode('[reduction_woocommerce id="' . $product_id . '"]');
    echo '</div>';
} else {
    echo '<p>Aucun produit en promotion trouvé.</p>';
}
echo '</div>';

// 3. Vérifier les styles chargés
echo '<div class="debug-section">
    <h2>Styles CSS chargés</h2>';
global $wp_styles;
if (isset($wp_styles) && is_object($wp_styles) && isset($wp_styles->registered)) {
    echo '<pre>';
    foreach ($wp_styles->registered as $handle => $style) {
        if (strpos($handle, 'goldwizard') !== false) {
            echo htmlspecialchars($handle . ' => ' . $style->src) . "\n";
        }
    }
    echo '</pre>';
} else {
    echo '<p>Impossible de récupérer les styles enregistrés.</p>';
}
echo '</div>';

// 4. Bouton Achat Rapide
echo '<div class="debug-section">
    <h2>Bouton Achat Rapide</h2>';
echo '<p>Vérification des hooks et fonctions pour le bouton "Achat rapide"</p>';

// Vérifier si la fonction existe
if (function_exists('wc_quick_view_button')) {
    echo '<p>La fonction wc_quick_view_button() existe.</p>';
} else {
    echo '<p>La fonction wc_quick_view_button() n\'existe pas. Vérifiez quelle extension fournit cette fonctionnalité.</p>';
}

// Vérifier les hooks liés à la vue rapide
$hooks = array(
    'woocommerce_after_shop_loop_item',
    'woocommerce_before_shop_loop_item_title',
    'woocommerce_after_shop_loop_item_title',
    'woocommerce_single_product_summary'
);

echo '<h3>Hooks liés à la vue rapide</h3>';
echo '<pre>';
foreach ($hooks as $hook) {
    global $wp_filter;
    if (isset($wp_filter[$hook])) {
        echo htmlspecialchars($hook . ' a ' . count($wp_filter[$hook]) . ' callbacks enregistrés.') . "\n";
        foreach ($wp_filter[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    if (is_object($callback['function'][0])) {
                        echo htmlspecialchars('  - ' . $priority . ': ' . get_class($callback['function'][0]) . '->' . $callback['function'][1]) . "\n";
                    } else {
                        echo htmlspecialchars('  - ' . $priority . ': ' . $callback['function'][0] . '::' . $callback['function'][1]) . "\n";
                    }
                } else {
                    echo htmlspecialchars('  - ' . $priority . ': ' . (is_string($callback['function']) ? $callback['function'] : 'Closure')) . "\n";
                }
            }
        }
    } else {
        echo htmlspecialchars($hook . ' n\'a pas de callbacks enregistrés.') . "\n";
    }
}
echo '</pre>';
echo '</div>';

// Pied de page
echo '</body>
</html>';
