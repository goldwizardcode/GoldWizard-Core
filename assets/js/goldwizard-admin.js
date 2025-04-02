/**
 * GoldWizard Core - Admin JavaScript
 */
(function($) {
    'use strict';

    // Initialisation immédiate pour éviter le clignotement
    removeDuplicateOptions();

    // Initialisation au chargement du DOM
    $(document).ready(function() {
        // Supprimer les doublons une seconde fois après le chargement complet du DOM
        removeDuplicateOptions();
        
        // Initialiser les champs textuels
        initTextFields();
        
        // Observer les modifications du DOM pour détecter les nouveaux doublons
        observeDOMChanges();
    });

    // Fonction pour observer les modifications du DOM
    function observeDOMChanges() {
        // Créer un observateur de mutations
        var observer = new MutationObserver(function(mutations) {
            // Vérifier s'il y a des modifications dans les options de personnalisation
            var needsCheck = false;
            var reinitTextFields = false;
            var checkButton = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    for (var i = 0; i < mutation.addedNodes.length; i++) {
                        var node = mutation.addedNodes[i];
                        if (node.nodeType === 1) {
                            if ($(node).hasClass('options_group') || 
                                $(node).find('.options_group').length > 0 ||
                                $(node).hasClass('personnalisation-text-fields') ||
                                $(node).find('.personnalisation-text-fields').length > 0) {
                                needsCheck = true;
                            }
                            
                            // Si le nœud contient un bouton d'ajout de champ textuel, réinitialiser les champs textuels
                            if ($(node).find('.add_text_field').length > 0 || $(node).hasClass('add_text_field')) {
                                reinitTextFields = true;
                                checkButton = true;
                            }
                        }
                    }
                } else if (mutation.type === 'attributes') {
                    // Si l'attribut style d'un conteneur de champs textuels a changé (affichage/masquage)
                    if ($(mutation.target).hasClass('personnalisation-text-fields') && mutation.attributeName === 'style') {
                        reinitTextFields = true;
                        checkButton = true;
                    }
                    
                    // Si l'attribut disabled ou class du bouton a changé
                    if (($(mutation.target).hasClass('add_text_field') || mutation.target.id === 'add_text_field_button') && 
                        (mutation.attributeName === 'disabled' || mutation.attributeName === 'class')) {
                        checkButton = true;
                    }
                }
            });
            
            if (needsCheck) {
                removeDuplicateOptions();
            }
            
            if (reinitTextFields) {
                // Attendre un court instant pour que le DOM soit complètement mis à jour
                setTimeout(function() {
                    initTextFields();
                }, 100);
            }
            
            if (checkButton) {
                // Vérifier l'état du bouton immédiatement
                checkAddButtonState();
                
                // Et vérifier à nouveau après un court délai
                setTimeout(checkAddButtonState, 200);
                setTimeout(checkAddButtonState, 500);
            }
        });
        
        // Observer le conteneur des options de produit
        var targetNode = document.getElementById('woocommerce-product-data');
        if (targetNode) {
            observer.observe(targetNode, { 
                childList: true, 
                subtree: true, 
                attributes: true, 
                attributeFilter: ['style', 'class', 'disabled'] 
            });
        }
    }

    // Fonction pour supprimer les doublons d'options de personnalisation
    function removeDuplicateOptions() {
        // Rechercher toutes les sections "Options de personnalisation"
        var $optionsGroups = $('.options_group');
        var $personnalisationTitles = $optionsGroups.find('h3:contains("Options de personnalisation")');
        
        // S'il y a plus d'un titre, supprimer les doublons
        if ($personnalisationTitles.length > 1) {
            console.log('GoldWizard: Suppression de ' + ($personnalisationTitles.length - 1) + ' doublons de titres d\'options de personnalisation');
            $personnalisationTitles.each(function(index) {
                if (index > 0) {
                    // Trouver le parent options_group et le supprimer
                    $(this).closest('.options_group').remove();
                }
            });
        }
        
        // Rechercher tous les conteneurs de champs textuels personnalisés
        var $textFieldsContainers = $('.personnalisation-text-fields');
        
        // S'il y a plus d'un conteneur, supprimer les doublons
        if ($textFieldsContainers.length > 1) {
            console.log('GoldWizard: Suppression de ' + ($textFieldsContainers.length - 1) + ' doublons de conteneurs de champs textuels');
            $textFieldsContainers.each(function(index) {
                if (index > 0) {
                    $(this).remove();
                }
            });
        }
        
        // S'assurer qu'il n'y a qu'une seule case à cocher pour chaque option
        var $enableImageUpload = $('input#_enable_image_upload');
        var $enableTextFields = $('input#_enable_text_fields');
        
        if ($enableImageUpload.length > 1) {
            console.log('GoldWizard: Suppression de ' + ($enableImageUpload.length - 1) + ' doublons de cases à cocher pour l\'upload d\'images');
            $enableImageUpload.each(function(index) {
                if (index > 0) {
                    $(this).closest('p').remove();
                }
            });
        }
        
        if ($enableTextFields.length > 1) {
            console.log('GoldWizard: Suppression de ' + ($enableTextFields.length - 1) + ' doublons de cases à cocher pour les champs textuels');
            $enableTextFields.each(function(index) {
                if (index > 0) {
                    $(this).closest('p').remove();
                }
            });
        }
    }

    // Initialiser les champs textuels
    function initTextFields() {
        var $container = $('#personnalisation_text_fields_container');
        var $template = $('#personnalisation_text_field_template');
        var $addButton = $('.add_text_field');
        var $enableTextFields = $('#_enable_text_fields');
        var $textFieldsContainer = $('.personnalisation-text-fields');

        // Si les éléments n'existent pas, ne rien faire
        if (!$container.length || !$template.length) {
            return;
        }

        // S'assurer que le bouton d'ajout n'est pas désactivé
        $addButton.removeClass('button-disabled disabled').prop('disabled', false).css({
            'opacity': '1',
            'cursor': 'pointer',
            'pointer-events': 'auto'
        });

        // Afficher/masquer les champs textuels en fonction de l'option
        if ($enableTextFields.length) {
            $enableTextFields.on('change', function() {
                if ($(this).is(':checked')) {
                    $textFieldsContainer.show();
                    // S'assurer que le bouton est activé quand la case est cochée
                    $addButton.removeClass('button-disabled disabled').prop('disabled', false).css({
                        'opacity': '1',
                        'cursor': 'pointer',
                        'pointer-events': 'auto'
                    });
                } else {
                    $textFieldsContainer.hide();
                }
            }).trigger('change');
        } else {
            // Si l'élément n'existe pas, afficher quand même les champs
            $textFieldsContainer.show();
            // S'assurer que le bouton est activé
            $addButton.removeClass('button-disabled disabled').prop('disabled', false).css({
                'opacity': '1',
                'cursor': 'pointer',
                'pointer-events': 'auto'
            });
        }

        // Réinitialiser les gestionnaires d'événements pour éviter les doublons
        $addButton.off('click');

        // Événement de clic sur le bouton d'ajout
        $addButton.on('click', function(e) {
            e.preventDefault();
            
            // Vérifier si le bouton est désactivé visuellement et le réactiver
            if ($(this).hasClass('disabled') || $(this).hasClass('button-disabled') || $(this).prop('disabled')) {
                $(this).removeClass('button-disabled disabled').prop('disabled', false).css({
                    'opacity': '1',
                    'cursor': 'pointer',
                    'pointer-events': 'auto'
                });
                // Si le bouton était désactivé, attendre un peu avant de continuer
                setTimeout(function() {
                    addNewTextField();
                }, 100);
                return;
            }
            
            addNewTextField();
        });

        // Fonction pour ajouter un nouveau champ textuel
        function addNewTextField() {
            // Récupérer le nombre de champs existants
            var index = $container.find('.personnalisation-text-field').length;
            
            // Récupérer le template et remplacer l'index
            var html = $template.html().replace(/\{\{index\}\}/g, index);
            
            // Ajouter le champ au conteneur
            $container.append(html);
            
            // Activer automatiquement l'option si on ajoute un champ
            if ($enableTextFields.length && !$enableTextFields.is(':checked')) {
                $enableTextFields.prop('checked', true).trigger('change');
            }
        }

        // Supprimer les gestionnaires d'événements existants pour éviter les doublons
        $container.off('click', '.remove-text-field');

        // Événement de clic sur le bouton de suppression (délégation d'événement)
        $container.on('click', '.remove-text-field', function(e) {
            e.preventDefault();
            
            // Supprimer le champ parent
            $(this).closest('.personnalisation-text-field').fadeOut(300, function() {
                $(this).remove();
                
                // Réindexer les champs restants
                reindexTextFields();
                
                // Si plus aucun champ, désactiver l'option
                if ($container.find('.personnalisation-text-field').length === 0 && $enableTextFields.length) {
                    $enableTextFields.prop('checked', false).trigger('change');
                }
            });
        });

        // Fonction pour réindexer les champs
        function reindexTextFields() {
            $container.find('.personnalisation-text-field').each(function(index) {
                var $field = $(this);
                
                // Mettre à jour les attributs name des champs
                $field.find('input, select, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        }
    }

    // Ajouter une fonction pour vérifier l'état du bouton d'ajout de champ textuel
    function checkAddButtonState() {
        var $addButton = $('.add_text_field');
        var $enableTextFields = $('#_enable_text_fields');
        
        if ($addButton.length && $enableTextFields.length && $enableTextFields.is(':checked')) {
            // Forcer l'activation du bouton
            $addButton.removeClass('button-disabled disabled').prop('disabled', false).css({
                'opacity': '1',
                'cursor': 'pointer',
                'pointer-events': 'auto'
            });
            
            // Vérifier si le bouton a des gestionnaires d'événements
            var events = $._data($addButton[0], 'events');
            if (!events || !events.click || events.click.length === 0) {
                // Réinitialiser les champs textuels si le bouton n'a pas de gestionnaire d'événements
                initTextFields();
            }
        }
    }

    // Exécuter la vérification périodiquement pour s'assurer que le bouton est toujours actif
    setInterval(checkAddButtonState, 500);

    // Ajouter une fonction pour vérifier l'état de l'interface après le chargement complet
    $(window).on('load', function() {
        // Vérifier si les champs textuels doivent être affichés
        var $enableTextFields = $('#_enable_text_fields');
        var $textFieldsContainer = $('.personnalisation-text-fields');
        var $container = $('#personnalisation_text_fields_container');
        
        // Si l'option est cochée mais que les champs ne sont pas visibles, les afficher
        if ($enableTextFields.length && $enableTextFields.is(':checked') && $textFieldsContainer.is(':hidden')) {
            $textFieldsContainer.show();
        }
        
        // Si des champs existent mais que l'option n'est pas cochée, cocher l'option
        if ($container.length && $container.find('.personnalisation-text-field').length > 0 && $enableTextFields.length && !$enableTextFields.is(':checked')) {
            $enableTextFields.prop('checked', true).trigger('change');
        }
    });
})(jQuery);
