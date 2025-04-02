/**
 * GoldWizard Lightbox - JavaScript pour gérer la lightbox des images de produit
 */
(function($) {
    'use strict';

    // Initialiser quand le DOM est prêt
    $(document).ready(function() {
        initLightbox();
        
        // Réinitialiser lors d'un chargement AJAX (pour les vues rapides)
        $(document).on('ajaxComplete', function() {
            setTimeout(function() {
                initLightbox();
            }, 500);
        });
    });

    /**
     * Initialiser la lightbox
     */
    function initLightbox() {
        const $lightboxes = $('.goldwizard-lightbox');
        
        if ($lightboxes.length) {
            $lightboxes.each(function() {
                const $lightbox = $(this);
                const $container = $lightbox.closest('.goldwizard-product-image-container');
                const $lightboxImage = $lightbox.find('.goldwizard-lightbox-image');
                const $closeButton = $lightbox.find('.goldwizard-lightbox-close');
                const $prevButton = $lightbox.find('.goldwizard-lightbox-nav.prev');
                const $nextButton = $lightbox.find('.goldwizard-lightbox-nav.next');
                const $galleryItems = $container.find('.goldwizard-gallery-item');
                
                // Fermer la lightbox
                $closeButton.on('click', function() {
                    $lightbox.removeClass('active');
                    setTimeout(function() {
                        $lightbox.css('display', 'none');
                        $('body').css('overflow', 'auto');
                    }, 300);
                });
                
                // Fermer la lightbox en cliquant sur l'overlay
                $lightbox.find('.goldwizard-lightbox-overlay').on('click', function(e) {
                    if (e.target === this) {
                        $lightbox.removeClass('active');
                        setTimeout(function() {
                            $lightbox.css('display', 'none');
                            $('body').css('overflow', 'auto');
                        }, 300);
                    }
                });
                
                // Navigation dans la lightbox
                $prevButton.on('click', function() {
                    navigateLightbox('prev', $lightbox, $galleryItems, $lightboxImage);
                });
                
                $nextButton.on('click', function() {
                    navigateLightbox('next', $lightbox, $galleryItems, $lightboxImage);
                });
                
                // Navigation au clavier
                $(document).on('keydown', function(e) {
                    if ($lightbox.is(':visible')) {
                        if (e.key === 'Escape') {
                            $lightbox.removeClass('active');
                            setTimeout(function() {
                                $lightbox.css('display', 'none');
                                $('body').css('overflow', 'auto');
                            }, 300);
                        } else if (e.key === 'ArrowLeft') {
                            navigateLightbox('prev', $lightbox, $galleryItems, $lightboxImage);
                        } else if (e.key === 'ArrowRight') {
                            navigateLightbox('next', $lightbox, $galleryItems, $lightboxImage);
                        }
                    }
                });
                
                // Zoom et déplacement de l'image
                let scale = 1;
                let panning = false;
                let pointX = 0;
                let pointY = 0;
                let start = { x: 0, y: 0 };
                
                $lightboxImage.on('wheel', function(e) {
                    e.preventDefault();
                    const delta = e.originalEvent.deltaY;
                    
                    if (delta > 0) {
                        // Zoom out
                        scale = Math.max(1, scale - 0.1);
                    } else {
                        // Zoom in
                        scale = Math.min(3, scale + 0.1);
                    }
                    
                    updateImageTransform($lightboxImage, scale, pointX, pointY);
                });
                
                $lightboxImage.on('mousedown', function(e) {
                    e.preventDefault();
                    if (scale > 1) {
                        panning = true;
                        start = {
                            x: e.clientX - pointX,
                            y: e.clientY - pointY
                        };
                    }
                });
                
                $(document).on('mousemove', function(e) {
                    if (panning && scale > 1) {
                        pointX = e.clientX - start.x;
                        pointY = e.clientY - start.y;
                        updateImageTransform($lightboxImage, scale, pointX, pointY);
                    }
                });
                
                $(document).on('mouseup', function() {
                    panning = false;
                });
                
                // Double-clic pour réinitialiser le zoom
                $lightboxImage.on('dblclick', function() {
                    scale = 1;
                    pointX = 0;
                    pointY = 0;
                    updateImageTransform($lightboxImage, scale, pointX, pointY);
                });
                
                // Support tactile
                let touchStartX = 0;
                let touchStartY = 0;
                let touchMoveX = 0;
                let touchMoveY = 0;
                
                $lightbox.on('touchstart', function(e) {
                    const touch = e.originalEvent.touches[0];
                    touchStartX = touch.clientX;
                    touchStartY = touch.clientY;
                });
                
                $lightbox.on('touchmove', function(e) {
                    if (e.originalEvent.touches.length === 1) {
                        const touch = e.originalEvent.touches[0];
                        touchMoveX = touch.clientX - touchStartX;
                        touchMoveY = touch.clientY - touchStartY;
                    }
                });
                
                $lightbox.on('touchend', function() {
                    // Swipe horizontal pour naviguer
                    if (Math.abs(touchMoveX) > 50 && Math.abs(touchMoveY) < 50) {
                        if (touchMoveX > 0) {
                            navigateLightbox('prev', $lightbox, $galleryItems, $lightboxImage);
                        } else {
                            navigateLightbox('next', $lightbox, $galleryItems, $lightboxImage);
                        }
                    }
                    
                    touchStartX = 0;
                    touchStartY = 0;
                    touchMoveX = 0;
                    touchMoveY = 0;
                });
            });
        }
    }

    /**
     * Naviguer dans la lightbox
     */
    function navigateLightbox(direction, $lightbox, $galleryItems, $lightboxImage) {
        // Trouver l'image actuellement affichée
        const currentSrc = $lightboxImage.attr('src');
        let currentIndex = -1;
        
        $galleryItems.each(function(index) {
            if ($(this).data('full-image') === currentSrc) {
                currentIndex = index;
                return false;
            }
        });
        
        // Si l'image actuelle n'est pas trouvée, utiliser la première
        if (currentIndex === -1) {
            currentIndex = 0;
        }
        
        // Calculer le nouvel index
        let newIndex;
        if (direction === 'prev') {
            newIndex = (currentIndex - 1 + $galleryItems.length) % $galleryItems.length;
        } else {
            newIndex = (currentIndex + 1) % $galleryItems.length;
        }
        
        // Mettre à jour l'image
        const newSrc = $galleryItems.eq(newIndex).data('full-image');
        
        // Animation de transition
        $lightboxImage.fadeOut(150, function() {
            $lightboxImage.attr('src', newSrc).fadeIn(150);
            
            // Réinitialiser le zoom
            updateImageTransform($lightboxImage, 1, 0, 0);
        });
    }

    /**
     * Mettre à jour la transformation de l'image
     */
    function updateImageTransform($image, scale, x, y) {
        $image.css('transform', `translate(${x}px, ${y}px) scale(${scale})`);
    }

})(jQuery);
