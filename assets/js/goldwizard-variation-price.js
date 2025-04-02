/**
 * GoldWizard Variation Price JavaScript
 * Gère l'affichage dynamique des prix des variations
 */
(function($) {
    'use strict';

    // Initialisation
    $(document).ready(function() {
        initVariationPriceDisplay();
        
        // Initialiser également pour les shortcodes existants
        initExistingShortcodes();
    });
    
    // Fonction pour initialiser les shortcodes existants
    function initExistingShortcodes() {
        // Vérifier si des éléments avec la classe goldwizard-variation-price-display existent déjà (via shortcode)
        var $existingDisplays = $('.goldwizard-variation-price-display[data-shortcode="true"]');
        
        if ($existingDisplays.length) {
            // Pour chaque élément, vérifier s'il est dans un bloc-projet et cacher les frais de livraison si nécessaire
            $existingDisplays.each(function() {
                var $display = $(this);
                if (isInBlocProjet($display)) {
                    $display.find('.frais-livraison').hide();
                }
            });
        }
    }

    // Fonction pour initialiser l'affichage des prix des variations
    function initVariationPriceDisplay() {
        var $form = $('form.variations_form');

        // Si le formulaire de variations n'existe pas, ne rien faire
        if (!$form.length) {
            return;
        }

        // Vérifier si un élément avec la classe goldwizard-variation-price-display existe déjà
        var $existingDisplay = $('.goldwizard-variation-price-display');
        
        // Si un élément existe déjà, l'utiliser
        if ($existingDisplay.length) {
            // Utiliser le premier élément existant
            window.goldwizardPriceDisplay = $existingDisplay.first();
            
            // Cacher les autres éléments avec la même classe pour éviter le double affichage
            $('.goldwizard-variation-price-display').not(':first').hide();
        } else {
            // Créer un nouvel élément
            $form.find('.variations').before('<div class="goldwizard-variation-price-display"></div>');
            window.goldwizardPriceDisplay = $('.goldwizard-variation-price-display').first();
        }
        
        // Écouter les événements de changement de variation
        $form.on('found_variation', function(event, variation) {
            updatePriceDisplay(variation);
        });

        // Écouter les événements de réinitialisation
        $form.on('reset_data', function() {
            updateDefaultPriceDisplay($form);
        });

        // Vérifier si une variation est déjà sélectionnée au chargement
        setTimeout(function() {
            var currentVariation = getCurrentVariation($form);
            if (currentVariation) {
                updatePriceDisplay(currentVariation);
            } else {
                // Afficher les prix par défaut du produit
                updateDefaultPriceDisplay($form);
            }
        }, 300);
    }

    // Fonction pour obtenir la variation actuellement sélectionnée
    function getCurrentVariation($form) {
        if (!$form || !$form.length) {
            $form = $('form.variations_form');
        }

        // Récupérer les données de variation depuis le formulaire
        var variationsData = $form.data('product_variations');
        if (!variationsData || !variationsData.length) {
            return null;
        }

        // Récupérer les attributs sélectionnés
        var selectedAttributes = {};
        $form.find('.variations select').each(function() {
            var attributeName = $(this).data('attribute_name') || $(this).attr('name');
            var attributeValue = $(this).val() || '';
            if (attributeValue) {
                selectedAttributes[attributeName] = attributeValue;
            }
        });

        // Si aucun attribut n'est sélectionné, retourner null
        if (Object.keys(selectedAttributes).length === 0) {
            return null;
        }

        // Trouver la variation correspondant aux attributs sélectionnés
        for (var i = 0; i < variationsData.length; i++) {
            var variation = variationsData[i];
            var attributes = variation.attributes;
            var match = true;

            for (var attrName in selectedAttributes) {
                if (selectedAttributes.hasOwnProperty(attrName)) {
                    var selectedValue = selectedAttributes[attrName];
                    var variationValue = attributes[attrName];

                    // Si la variation a une valeur spécifique et qu'elle ne correspond pas
                    if (variationValue && variationValue !== selectedValue && variationValue !== '') {
                        match = false;
                        break;
                    }
                }
            }

            if (match) {
                return variation;
            }
        }

        return null;
    }

    // Fonction pour vérifier si un élément est dans un bloc-projet
    function isInBlocProjet($element) {
        // Vérifier si l'élément lui-même ou l'un de ses parents a la classe bloc-projet
        return $element.hasClass('bloc-projet') || $element.parents('.bloc-projet').length > 0;
    }

    // Fonction pour mettre à jour l'affichage des prix
    function updatePriceDisplay(variation) {
        // Récupérer le conteneur d'affichage des prix
        var $priceDisplay = window.goldwizardPriceDisplay || $('.goldwizard-variation-price-display').first();
        
        // Si le conteneur n'existe pas, ne rien faire
        if (!$priceDisplay.length) {
            return;
        }
        
        // Récupérer les prix
        var regularPrice = variation.display_regular_price;
        var salePrice = variation.display_price;
        
        // Calculer le pourcentage d'économie
        var percentage = 0;
        if (regularPrice > 0 && salePrice < regularPrice) {
            percentage = Math.round(100 - (salePrice / regularPrice * 100));
        }
        
        // Vérifier si nous sommes dans un bloc-projet
        var inBlocProjet = isInBlocProjet($priceDisplay);
        
        // Vider le conteneur
        $priceDisplay.empty();
        
        // Appliquer les styles au conteneur
        $priceDisplay.attr('style', 'display: flex !important; flex-wrap: wrap !important; align-items: center !important; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important; font-weight: 300 !important; line-height: 1.2 !important; background-color: transparent !important; padding: 0 !important; margin: 5px 0 !important;');
        
        // Si le produit est en promotion
        if (salePrice < regularPrice) {
            // Ajouter les prix et le pourcentage d'économie
            $priceDisplay.append('<span class="regular-price" style="text-decoration: line-through !important; color: #777 !important; font-size: 16px !important; margin-right: 10px !important;">' + formatPrice(regularPrice) + '</span>');
            $priceDisplay.append('<span class="sale-price" style="font-weight: bold !important; color: #0E1B4D !important; font-size: 16px !important; margin-right: 10px !important;">' + formatPrice(salePrice) + '</span>');
            $priceDisplay.append('<span class="economisez" style="color: #D9534F !important; font-size: 14px !important;">Économisez ' + percentage + '%</span>');
            
            // Ajouter les frais de livraison uniquement si nous ne sommes pas dans un bloc-projet
            if (!inBlocProjet) {
                $priceDisplay.append('<span class="frais-livraison" style="color: #5CB85C !important; font-size: 14px !important; display: block !important; width: 100% !important; margin-top: 5px !important;">Frais de livraison offerts.</span>');
            }
        } else {
            // Si le produit n'est pas en promotion, afficher seulement le prix normal
            $priceDisplay.append('<span class="sale-price" style="font-weight: bold !important; color: #0E1B4D !important; font-size: 16px !important; margin-right: 10px !important;">' + formatPrice(salePrice) + '</span>');
            
            // Ajouter les frais de livraison uniquement si nous ne sommes pas dans un bloc-projet
            if (!inBlocProjet) {
                $priceDisplay.append('<span class="frais-livraison" style="color: #5CB85C !important; font-size: 14px !important; display: block !important; width: 100% !important; margin-top: 5px !important;">Frais de livraison offerts.</span>');
            }
        }
    }
    
    // Fonction pour afficher les prix par défaut du produit
    function updateDefaultPriceDisplay($form) {
        // Récupérer le produit
        var productId = $form.data('product_id');
        
        // Récupérer le conteneur d'affichage des prix
        var $priceDisplay = window.goldwizardPriceDisplay || $('.goldwizard-variation-price-display').first();
        
        // Vérifier si nous sommes dans un bloc-projet
        var inBlocProjet = isInBlocProjet($priceDisplay);
        
        // Vider le conteneur
        $priceDisplay.empty();
        
        // Récupérer les prix depuis les attributs data du formulaire
        var regularPrice = $form.data('regular_price') || 0;
        var salePrice = $form.data('sale_price') || 0;
        
        // Si les prix ne sont pas disponibles dans les attributs data, essayer de les récupérer depuis le HTML
        if (!regularPrice || !salePrice) {
            // Essayer de récupérer les prix depuis les éléments HTML
            var $regularPriceElement = $('.price del .amount, .price del .woocommerce-Price-amount');
            var $salePriceElement = $('.price ins .amount, .price ins .woocommerce-Price-amount');
            
            if ($regularPriceElement.length && $salePriceElement.length) {
                // Extraire les prix (supprimer les symboles de devise et les espaces)
                regularPrice = parseFloat($regularPriceElement.text().replace(/[^0-9,.]/g, '').replace(',', '.'));
                salePrice = parseFloat($salePriceElement.text().replace(/[^0-9,.]/g, '').replace(',', '.'));
            } else {
                // Si nous n'avons pas trouvé les éléments de prix, utiliser le prix par défaut
                var $priceElement = $('.price .amount, .price .woocommerce-Price-amount');
                if ($priceElement.length) {
                    salePrice = parseFloat($priceElement.text().replace(/[^0-9,.]/g, '').replace(',', '.'));
                    regularPrice = salePrice;
                }
            }
        }
        
        // Calculer le pourcentage d'économie
        var percentage = 0;
        if (regularPrice > 0 && salePrice < regularPrice) {
            percentage = Math.round(100 - (salePrice / regularPrice * 100));
        }
        
        // Appliquer les styles au conteneur
        $priceDisplay.attr('style', 'display: flex !important; flex-wrap: wrap !important; align-items: center !important; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important; font-weight: 300 !important; line-height: 1.2 !important; background-color: transparent !important; padding: 0 !important; margin: 5px 0 !important;');
        
        // Si le produit est en promotion
        if (salePrice < regularPrice) {
            // Ajouter les prix et le pourcentage d'économie
            $priceDisplay.append('<span class="regular-price" style="text-decoration: line-through !important; color: #777 !important; font-size: 16px !important; margin-right: 10px !important;">' + formatPrice(regularPrice) + '</span>');
            $priceDisplay.append('<span class="sale-price" style="font-weight: bold !important; color: #0E1B4D !important; font-size: 16px !important; margin-right: 10px !important;">' + formatPrice(salePrice) + '</span>');
            $priceDisplay.append('<span class="economisez" style="color: #D9534F !important; font-size: 14px !important;">Économisez ' + percentage + '%</span>');
            
            // Ajouter les frais de livraison uniquement si nous ne sommes pas dans un bloc-projet
            if (!inBlocProjet) {
                $priceDisplay.append('<span class="frais-livraison" style="color: #5CB85C !important; font-size: 14px !important; display: block !important; width: 100% !important; margin-top: 5px !important;">Frais de livraison offerts.</span>');
            }
        } else {
            // Si le produit n'est pas en promotion, afficher seulement le prix normal
            $priceDisplay.append('<span class="sale-price" style="font-weight: bold !important; color: #0E1B4D !important; font-size: 16px !important; margin-right: 10px !important;">' + formatPrice(salePrice) + '</span>');
            
            // Ajouter les frais de livraison uniquement si nous ne sommes pas dans un bloc-projet
            if (!inBlocProjet) {
                $priceDisplay.append('<span class="frais-livraison" style="color: #5CB85C !important; font-size: 14px !important; display: block !important; width: 100% !important; margin-top: 5px !important;">Frais de livraison offerts.</span>');
            }
        }
    }

    // Fonction pour formater les prix
    function formatPrice(price) {
        // Formater le prix avec le symbole de l'euro
        return price.toFixed(2).replace('.', ',') + '€';
    }

    // Initialiser après les événements AJAX (pour les vues rapides, etc.)
    $(document).on('wc_variation_form', function() {
        setTimeout(function() {
            initVariationPriceDisplay();
            initExistingShortcodes();
        }, 100);
    });
    
    // Support pour différents types de vues rapides
    $(document).on('quick-view-displayed', function() {
        setTimeout(function() {
            initVariationPriceDisplay();
            initExistingShortcodes();
        }, 300);
    });
    
    // Support pour d'autres plugins de vue rapide
    $(document).on('quickview-loaded', function() {
        setTimeout(function() {
            initVariationPriceDisplay();
            initExistingShortcodes();
        }, 300);
    });
    
    // Intercepter les requêtes AJAX pour initialiser après le chargement
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Vérifier si la requête est liée à une vue rapide ou à un produit
        if (settings.url && (
            settings.url.indexOf('quickview') > -1 ||
            settings.url.indexOf('quick-view') > -1 ||
            settings.url.indexOf('product_id') > -1 ||
            settings.url.indexOf('add-to-cart') > -1 ||
            settings.url.indexOf('product') > -1
        )) {
            setTimeout(function() {
                initVariationPriceDisplay();
                initExistingShortcodes();
            }, 300);
        }
    });
    
    // Réinitialiser lors du chargement de la page ou après un changement d'onglet
    $(window).on('load', function() {
        setTimeout(function() {
            initVariationPriceDisplay();
            initExistingShortcodes();
        }, 300);
    });
    
    // Support pour les onglets et accordéons
    $(document).on('click', '.wc-tabs li a, .woocommerce-tabs li a, .tab-title, .accordion-title', function() {
        setTimeout(function() {
            initVariationPriceDisplay();
            initExistingShortcodes();
        }, 300);
    });
    
    // Support pour les blocs Breakdance
    $(document).on('breakdance_block_loaded', function() {
        setTimeout(function() {
            initVariationPriceDisplay();
            initExistingShortcodes();
        }, 300);
    });
    
    // Support pour les blocs Elementor
    $(window).on('elementor/frontend/init', function() {
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/widget', function() {
                setTimeout(function() {
                    initVariationPriceDisplay();
                    initExistingShortcodes();
                }, 300);
            });
        }
    });
})(jQuery);
