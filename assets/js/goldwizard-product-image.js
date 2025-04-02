(function($) {
    'use strict';
    
    // Variables globales
    let galleryItems = [];
    let currentIndex = 0;
    let isLightboxOpen = false;
    let variationData = {};
    let debug = typeof goldwizardProductImage !== 'undefined' && goldwizardProductImage.debug === true;
    
    // Fonction de débogage
    function log(message, data) {
        if (debug) {
            console.log('[GoldWizard] ' + message, data || '');
        }
    }
    
    /**
     * Initialiser les images du produit
     */
    function initProductImages() {
        log('Initialisation des images du produit');
        
        // Sélectionner tous les conteneurs d'images
        $('.goldwizard-product-image-container').each(function() {
            const $container = $(this);
            
            // Éviter de réinitialiser si déjà initialisé
            if ($container.data('initialized')) {
                return;
            }
            
            // Marquer comme initialisé
            $container.data('initialized', true);
            
            // Initialiser la galerie
            initGallery($container);
            
            // Initialiser la lightbox
            initLightbox($container);
            
            // Initialiser les variations
            initVariationImages($container);
            
            log('Conteneur initialisé', $container);
        });
    }
    
    /**
     * Initialiser la galerie d'images
     */
    function initGallery($container) {
        const $galleryItems = $container.find('.goldwizard-gallery-item');
        const $mainImage = $container.find('.goldwizard-product-main-image img');
        const $prevNav = $container.find('.goldwizard-gallery-nav.prev');
        const $nextNav = $container.find('.goldwizard-gallery-nav.next');
        const $galleryContainer = $container.find('.goldwizard-gallery-items');
        
        // Stocker les éléments de la galerie
        galleryItems = $galleryItems.toArray();
        
        // Gérer le clic sur un élément de la galerie
        $galleryItems.on('click', function() {
            const $item = $(this);
            const fullImage = $item.data('full-image');
            
            // Mettre à jour l'image principale
            $mainImage.attr('src', fullImage);
            
            // Mettre à jour la classe active
            $galleryItems.removeClass('active');
            $item.addClass('active');
            
            // Mettre à jour l'index courant
            currentIndex = $galleryItems.index($item);
            
            log('Image de galerie cliquée', { index: currentIndex, src: fullImage });
        });
        
        // Gérer le clic sur l'image principale (ouvrir la lightbox)
        $mainImage.on('click', function() {
            openLightbox($container);
        });
        
        // Navigation dans la galerie
        $prevNav.on('click', function(e) {
            e.preventDefault();
            navigateGallery($container, 'prev');
        });
        
        $nextNav.on('click', function(e) {
            e.preventDefault();
            navigateGallery($container, 'next');
        });
        
        // Faire défiler la galerie avec la molette de la souris
        $galleryContainer.on('wheel', function(e) {
            e.preventDefault();
            
            const delta = e.originalEvent.deltaY;
            const scrollAmount = 100;
            
            if (delta > 0) {
                // Défiler vers la droite
                $galleryContainer.scrollLeft($galleryContainer.scrollLeft() + scrollAmount);
            } else {
                // Défiler vers la gauche
                $galleryContainer.scrollLeft($galleryContainer.scrollLeft() - scrollAmount);
            }
        });
    }
    
    /**
     * Naviguer dans la galerie
     */
    function navigateGallery($container, direction) {
        const $galleryItems = $container.find('.goldwizard-gallery-item');
        const $mainImage = $container.find('.goldwizard-product-main-image img');
        const $galleryContainer = $container.find('.goldwizard-gallery-items');
        
        if ($galleryItems.length <= 1) {
            return;
        }
        
        // Trouver l'élément actif
        const $activeItem = $galleryItems.filter('.active');
        let index = $galleryItems.index($activeItem);
        
        // Calculer le nouvel index
        if (direction === 'next') {
            index = (index + 1) % $galleryItems.length;
        } else {
            index = (index - 1 + $galleryItems.length) % $galleryItems.length;
        }
        
        // Récupérer le nouvel élément
        const $newItem = $galleryItems.eq(index);
        const fullImage = $newItem.data('full-image');
        
        // Mettre à jour l'image principale
        $mainImage.attr('src', fullImage);
        
        // Mettre à jour la classe active
        $galleryItems.removeClass('active');
        $newItem.addClass('active');
        
        // Faire défiler la galerie pour que l'élément soit visible
        const itemPosition = $newItem.position().left;
        const containerScrollLeft = $galleryContainer.scrollLeft();
        const containerWidth = $galleryContainer.width();
        
        if (itemPosition < 0) {
            // L'élément est à gauche de la zone visible
            $galleryContainer.scrollLeft(containerScrollLeft + itemPosition);
        } else if (itemPosition + $newItem.width() > containerWidth) {
            // L'élément est à droite de la zone visible
            $galleryContainer.scrollLeft(containerScrollLeft + itemPosition - containerWidth + $newItem.width() + 10);
        }
        
        // Mettre à jour l'index courant
        currentIndex = index;
        
        log('Navigation dans la galerie', { direction: direction, newIndex: index });
    }
    
    /**
     * Initialiser la lightbox
     */
    function initLightbox($container) {
        const $lightbox = $container.find('.goldwizard-lightbox');
        const $lightboxImage = $lightbox.find('.goldwizard-lightbox-image');
        const $closeButton = $lightbox.find('.goldwizard-lightbox-close');
        
        // Fermer la lightbox au clic sur le bouton de fermeture
        $closeButton.on('click', function() {
            closeLightbox($container);
        });
        
        // Fermer la lightbox au clic en dehors de l'image
        $lightbox.on('click', function(e) {
            if (e.target === this || $(e.target).hasClass('goldwizard-lightbox-content')) {
                closeLightbox($container);
            }
        });
        
        // Fermer la lightbox avec la touche Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && isLightboxOpen) {
                closeLightbox($container);
            }
        });
        
        // Navigation dans la lightbox avec les touches flèches
        $(document).on('keydown', function(e) {
            if (!isLightboxOpen) {
                return;
            }
            
            if (e.key === 'ArrowRight') {
                navigateLightbox($container, 'next');
            } else if (e.key === 'ArrowLeft') {
                navigateLightbox($container, 'prev');
            }
        });
        
        // Navigation dans la lightbox avec les clics
        $lightboxImage.on('click', function(e) {
            e.stopPropagation();
            navigateLightbox($container, 'next');
        });
    }
    
    /**
     * Ouvrir la lightbox
     */
    function openLightbox($container) {
        const $lightbox = $container.find('.goldwizard-lightbox');
        const $lightboxImage = $lightbox.find('.goldwizard-lightbox-image');
        const $galleryItems = $container.find('.goldwizard-gallery-item');
        const $activeItem = $galleryItems.filter('.active');
        
        // Si pas d'élément actif, utiliser le premier
        const index = $activeItem.length ? $galleryItems.index($activeItem) : 0;
        const fullImage = $activeItem.length ? $activeItem.data('full-image') : $container.find('.goldwizard-product-main-image img').attr('src');
        
        // Mettre à jour l'image de la lightbox
        $lightboxImage.attr('src', fullImage);
        
        // Afficher la lightbox
        $lightbox.fadeIn(300);
        
        // Marquer comme ouverte
        isLightboxOpen = true;
        
        // Ajouter une classe au body pour empêcher le défilement
        $('body').addClass('goldwizard-lightbox-open');
        
        // Mettre à jour l'index courant
        currentIndex = index;
        
        log('Lightbox ouverte', { index: index, src: fullImage });
    }
    
    /**
     * Fermer la lightbox
     */
    function closeLightbox($container) {
        const $lightbox = $container.find('.goldwizard-lightbox');
        
        // Masquer la lightbox
        $lightbox.fadeOut(300);
        
        // Marquer comme fermée
        isLightboxOpen = false;
        
        // Retirer la classe du body
        $('body').removeClass('goldwizard-lightbox-open');
        
        log('Lightbox fermée');
    }
    
    /**
     * Naviguer dans la lightbox
     */
    function navigateLightbox($container, direction) {
        const $galleryItems = $container.find('.goldwizard-gallery-item');
        const $lightboxImage = $container.find('.goldwizard-lightbox-image');
        
        if ($galleryItems.length <= 1) {
            return;
        }
        
        // Calculer le nouvel index
        let newIndex;
        if (direction === 'next') {
            newIndex = (currentIndex + 1) % $galleryItems.length;
        } else {
            newIndex = (currentIndex - 1 + $galleryItems.length) % $galleryItems.length;
        }
        
        // Récupérer le nouvel élément
        const $newItem = $galleryItems.eq(newIndex);
        const fullImage = $newItem.data('full-image');
        
        // Mettre à jour l'image de la lightbox
        $lightboxImage.attr('src', fullImage);
        
        // Mettre à jour la classe active dans la galerie
        $galleryItems.removeClass('active');
        $newItem.addClass('active');
        
        // Mettre à jour l'index courant
        currentIndex = newIndex;
        
        log('Navigation dans la lightbox', { direction: direction, newIndex: newIndex });
    }
    
    /**
     * Initialiser les images des variations
     */
    function initVariationImages($container) {
        const productId = $container.data('product-id');
        
        if (!productId) {
            log('Pas d\'ID de produit trouvé');
            return;
        }
        
        // Chercher le formulaire de variation dans différents contextes
        let $form = null;
        
        // Contexte 1: Dans le même conteneur que les images
        $form = $container.closest('form.variations_form');
        
        // Contexte 2: Dans le même produit mais pas dans le même conteneur
        if (!$form.length) {
            $form = $container.closest('.product').find('form.variations_form');
        }
        
        // Contexte 3: Dans un quick view
        if (!$form.length) {
            const $quickView = $container.closest('.dwc-quick-view, .dwc-quick-view-fetch-container, .quick-view-content, .woocommerce-quick-view');
            if ($quickView.length) {
                $form = $quickView.find('form.variations_form');
            }
        }
        
        // Contexte 4: Par ID de produit
        if (!$form.length) {
            $form = $('form.variations_form[data-product_id="' + productId + '"]');
        }
        
        // Contexte 5: Dernier recours, chercher n'importe quel formulaire de variation sur la page
        if (!$form.length) {
            $form = $('form.variations_form').first();
        }
        
        if (!$form.length) {
            log('Pas de formulaire de variation trouvé');
            return;
        }
        
        log('Formulaire de variation trouvé', $form);
        
        // Récupérer les données des variations
        const $variationData = $container.find('.goldwizard-variation-images-data .variation-image-data');
        
        if (!$variationData.length) {
            log('Pas de données de variation trouvées');
            return;
        }
        
        // Stocker les données des variations
        variationData = {};
        
        $variationData.each(function() {
            const $data = $(this);
            const imageId = $data.data('image-id');
            const imageUrl = $data.data('image-url');
            const attributes = $data.data('attributes');
            
            if (imageId && imageUrl && attributes) {
                variationData[attributes] = {
                    id: imageId,
                    url: imageUrl
                };
            }
        });
        
        log('Données de variation chargées', variationData);
        
        // Écouter les événements de changement de variation
        $form.on('found_variation', function(event, variation) {
            updateVariationImage($container, variation);
        });
        
        $form.on('reset_image', function() {
            resetVariationImage($container);
        });
        
        // Écouter les changements d'attributs
        $form.on('change', '.variations select', function() {
            const $selects = $form.find('.variations select');
            const attributes = [];
            
            $selects.each(function() {
                const name = $(this).attr('name');
                const value = $(this).val();
                
                if (name && value) {
                    attributes.push(name + '=' + value);
                }
            });
            
            const attributesString = attributes.join('&');
            
            if (variationData[attributesString]) {
                const variation = {
                    image_id: variationData[attributesString].id,
                    image: {
                        src: variationData[attributesString].url
                    }
                };
                
                updateVariationImage($container, variation);
            }
        });
    }
    
    /**
     * Mettre à jour l'image en fonction de la variation
     */
    function updateVariationImage($container, variation) {
        if (!variation || !variation.image || !variation.image.src) {
            log('Pas d\'image de variation trouvée');
            return;
        }
        
        const $mainImage = $container.find('.goldwizard-product-main-image img');
        const $galleryItems = $container.find('.goldwizard-gallery-item');
        
        // Chercher si l'image de variation existe déjà dans la galerie
        let found = false;
        let imageId = variation.image_id;
        
        $galleryItems.each(function() {
            const $item = $(this);
            const itemImageId = $item.data('image-id');
            
            if (itemImageId == imageId) {
                // Simuler un clic sur cet élément
                $item.trigger('click');
                found = true;
                return false; // Sortir de la boucle
            }
        });
        
        // Si l'image n'est pas trouvée dans la galerie, mettre à jour l'image principale
        if (!found) {
            $mainImage.attr('src', variation.image.src);
        }
        
        log('Image de variation mise à jour', { variationId: variation.variation_id, imageId: imageId });
    }
    
    /**
     * Réinitialiser l'image à l'image par défaut
     */
    function resetVariationImage($container) {
        const $mainImage = $container.find('.goldwizard-product-main-image img');
        const defaultImage = $mainImage.data('default-image');
        
        if (defaultImage) {
            $mainImage.attr('src', defaultImage);
        }
        
        // Réinitialiser la galerie
        const $galleryItems = $container.find('.goldwizard-gallery-item');
        $galleryItems.removeClass('active');
        $galleryItems.first().addClass('active');
        
        log('Image de variation réinitialisée');
    }
    
    /**
     * Réinitialiser les images du produit (pour les quick views)
     */
    function reinitProductImages() {
        log('Réinitialisation des images du produit');
        
        // Réinitialiser les conteneurs
        $('.goldwizard-product-image-container').each(function() {
            $(this).removeData('initialized');
        });
        
        // Réinitialiser les variables globales
        galleryItems = [];
        currentIndex = 0;
        isLightboxOpen = false;
        variationData = {};
        
        // Réinitialiser les images
        initProductImages();
    }
    
    // Exposer la fonction de réinitialisation globalement
    window.goldwizardReinitProductImages = reinitProductImages;
    
    // Initialiser les images au chargement du document
    $(document).ready(function() {
        initProductImages();
    });
    
    // Réinitialiser les images après un chargement AJAX
    $(document).on('ajaxComplete', function(event, xhr, settings) {
        // Vérifier si c'est un chargement de produit ou un quick view
        if (settings.url && (
            settings.url.indexOf('wc-ajax=get_refreshed_fragments') > -1 ||
            settings.url.indexOf('wc-ajax=quickview') > -1 ||
            settings.url.indexOf('quick-view') > -1 ||
            settings.url.indexOf('breakdance_quickview') > -1
        )) {
            setTimeout(reinitProductImages, 500);
        }
    });
    
    // Support pour Breakdance
    $(document).on('breakdance_quickview_opened breakdance_quickview_loaded', function() {
        setTimeout(reinitProductImages, 500);
    });
    
    // Support pour les variations
    $(document).on('show_variation', function() {
        setTimeout(reinitProductImages, 100);
    });
    
    // MutationObserver pour détecter les changements dans le DOM
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            let shouldReinit = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    for (let i = 0; i < mutation.addedNodes.length; i++) {
                        const node = mutation.addedNodes[i];
                        
                        if (node.nodeType === 1) {
                            // Vérifier si le nœud est un quick view ou contient un quick view
                            const isQuickView = 
                                (node.classList && (
                                    node.classList.contains('dwc-quick-view') || 
                                    node.classList.contains('quick-view-content') ||
                                    node.classList.contains('dwc-quick-view-fetch-container')
                                )) || 
                                $(node).find('.dwc-quick-view, .quick-view-content, .dwc-quick-view-fetch-container').length > 0;
                            
                            // Vérifier si le nœud contient une image de produit
                            const hasProductImage = 
                                $(node).find('.goldwizard-product-image-container').length > 0 ||
                                $(node).hasClass('goldwizard-product-image-container');
                            
                            if (isQuickView || hasProductImage) {
                                shouldReinit = true;
                                break;
                            }
                        }
                    }
                }
            });
            
            if (shouldReinit) {
                setTimeout(reinitProductImages, 500);
            }
        });
        
        // Observer le document entier
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Vérifier périodiquement si un quick view est présent
    setInterval(function() {
        const $quickViews = $('.dwc-quick-view:visible, .dwc-quick-view-fetch-container:visible, .quick-view-content:visible');
        
        if ($quickViews.length > 0 && $quickViews.find('.goldwizard-product-image-container').length > 0) {
            reinitProductImages();
        }
    }, 2000);
})(jQuery);
