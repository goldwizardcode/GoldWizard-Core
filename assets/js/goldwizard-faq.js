/**
 * GoldWizard FAQ - Script pour les sections FAQ
 * 
 * Ce script permet de créer des sections FAQ interactives
 * avec des questions/réponses qui s'ouvrent et se ferment au clic.
 * 
 * Compatible avec Breakdance et autres constructeurs de page.
 */

(function($) {
  'use strict';
  
  // Configuration par défaut
  var config = {
    debug: true, // Mode debug pour afficher les messages dans la console
    faqContainerSelector: '.goldwizard-faq-container', // Sélecteur pour les conteneurs FAQ
    activeClass: 'goldwizard-faq-active', // Classe pour les éléments actifs
    animationDuration: 300, // Durée de l'animation en ms
    singleOpen: true, // Si true, un seul élément peut être ouvert à la fois
    breakdanceSelectors: {
      questionSelectors: ['.bde-heading', 'h3', 'h4', 'strong.goldwizard-faq-question', 'p strong'],
      answerSelectors: ['.goldwizard-faq-answer', '.bde-text']
    }
  };
  
  // Stockage des hauteurs initiales
  var initialHeights = {};
  var initialPositions = {};
  
  /**
   * Initialise le plugin FAQ
   */
  function init() {
    if (config.debug) console.log('GoldWizard FAQ: Initialisation');
    
    // Trouver tous les conteneurs FAQ
    var $containers = $(config.faqContainerSelector);
    if (config.debug) console.log('GoldWizard FAQ: ' + $containers.length + ' conteneur(s) trouvé(s)');
    
    // Parcourir chaque conteneur
    $containers.each(function(containerIndex) {
      var $container = $(this);
      
      if (config.debug) {
        console.log('GoldWizard FAQ: Analyse du conteneur #' + containerIndex);
      }
      
      // Rechercher les éléments de FAQ dans Breakdance
      var $faqItems = $container.find('.goldwizard-faq-item, .bde-div');
      
      if (config.debug) {
        console.log('GoldWizard FAQ: ' + $faqItems.length + ' éléments trouvés');
      }
      
      // Parcourir chaque élément pour trouver les paires question/réponse
      $faqItems.each(function(index) {
        var $item = $(this);
        
        // Sauvegarder la hauteur et position initiale
        var itemId = 'item-' + containerIndex + '-' + index;
        initialHeights[itemId] = $item.outerHeight();
        initialPositions[itemId] = $item.offset();
        
        if (config.debug) {
          console.log('GoldWizard FAQ: Hauteur initiale de l\'élément #' + index + ': ' + initialHeights[itemId] + 'px');
          console.log('GoldWizard FAQ: Position initiale de l\'élément #' + index + ':', initialPositions[itemId]);
        }
        
        // Vérifier si cet élément contient une question
        var $question = $item.find('.bde-heading, h3.goldwizard-faq-question, .goldwizard-faq-question').first();
        
        if ($question.length > 0) {
          if (config.debug) {
            console.log('GoldWizard FAQ: Question trouvée dans l\'élément #' + index + ': ' + $question.text().trim());
          }
          
          // Chercher la réponse associée
          var $answer;
          
          // Stratégie 1: Chercher dans les enfants directs
          $answer = $item.children('.goldwizard-faq-answer');
          
          // Stratégie 2: Chercher le frère suivant
          if (!$answer.length) {
            var $questionContainer = $question.closest('.bde-div');
            if ($questionContainer.length > 0) {
              $answer = $questionContainer.next('.goldwizard-faq-answer');
              
              if (config.debug && $answer.length) {
                console.log('GoldWizard FAQ: Réponse trouvée comme frère suivant');
              }
            }
          }
          
          // Stratégie 3: Recherche plus large
          if (!$answer.length) {
            $answer = $item.find('.goldwizard-faq-answer');
            
            if (config.debug && $answer.length) {
              console.log('GoldWizard FAQ: Réponse trouvée par recherche large');
            }
          }
          
          if ($answer.length > 0) {
            // Configurer la paire question/réponse
            setupFAQPair($question, $answer, index, containerIndex);
          } else if (config.debug) {
            console.log('GoldWizard FAQ: Pas de réponse trouvée pour la question #' + index);
          }
        }
      });
      
      // Recherche spécifique pour la structure Breakdance imbriquée
      if (config.debug) {
        console.log('GoldWizard FAQ: Recherche de structure Breakdance imbriquée');
      }
      
      // Trouver tous les éléments qui contiennent une question
      $container.find('.bde-heading, h3.goldwizard-faq-question').each(function(index) {
        var $question = $(this);
        var $questionContainer = $question.closest('.bde-div');
        
        if ($questionContainer.length > 0) {
          // Chercher la réponse qui est le frère suivant du conteneur de question
          var $answer = $questionContainer.next('.goldwizard-faq-answer, .bde-div');
          
          if ($answer.length > 0) {
            if (config.debug) {
              console.log('GoldWizard FAQ: Paire détectée (structure imbriquée): question=' + $question.text().trim());
              console.log('GoldWizard FAQ: Réponse:', $answer[0]);
            }
            
            // Sauvegarder la hauteur initiale du conteneur de question
            var itemId = 'item-nested-' + containerIndex + '-' + index;
            initialHeights[itemId] = $questionContainer.outerHeight();
            initialPositions[itemId] = $questionContainer.offset();
            
            if (config.debug) {
              console.log('GoldWizard FAQ: Hauteur initiale du conteneur imbriqué #' + index + ': ' + initialHeights[itemId] + 'px');
            }
            
            // Configurer la paire
            setupFAQPair($question, $answer, index + 1000, containerIndex); // Offset pour éviter les doublons
          }
        }
      });
    });
  }
  
  /**
   * Configure une paire question/réponse
   * @param {jQuery} $question - L'élément question
   * @param {jQuery} $answer - L'élément réponse
   * @param {number} index - Index de la paire
   * @param {number} containerIndex - Index du conteneur
   */
  function setupFAQPair($question, $answer, index, containerIndex) {
    // Ajouter des classes si elles n'existent pas déjà
    if (!$question.hasClass('goldwizard-faq-question')) {
      $question.addClass('goldwizard-faq-question');
    }
    
    if (!$answer.hasClass('goldwizard-faq-answer')) {
      $answer.addClass('goldwizard-faq-answer');
    }
    
    // Trouver l'élément parent qui sera basculé
    var $faqItem = $question.closest('.goldwizard-faq-item, .bde-div');
    
    // Si on n'a pas trouvé d'élément parent, utiliser le parent du conteneur de question
    if ($faqItem.length === 0) {
      $faqItem = $question.parent().closest('.bde-div');
    }
    
    // Ajouter la classe goldwizard-faq-item
    if (!$faqItem.hasClass('goldwizard-faq-item')) {
      $faqItem.addClass('goldwizard-faq-item');
    }
    
    // Ajouter un identifiant unique
    var itemId = 'item-' + containerIndex + '-' + index;
    $faqItem.attr('data-faq-id', itemId);
    
    // Définir la hauteur initiale à 0 pour la réponse
    $answer.css({
      height: '0',
      overflow: 'hidden',
      opacity: '0',
      padding: '0',
      margin: '0',
      display: 'none'
    });
    
    // Ajouter l'écouteur d'événement pour le clic sur la question
    $question.on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      if (config.debug) console.log('GoldWizard FAQ: Clic sur la question #' + index + ' dans le conteneur #' + containerIndex);
      toggleFAQItem($faqItem, $answer, $question.closest(config.faqContainerSelector));
    });
    
    // Ajouter également l'écouteur sur l'icône si elle existe
    var $icon = $question.parent().find('.bde-icon');
    if ($icon.length > 0) {
      $icon.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (config.debug) console.log('GoldWizard FAQ: Clic sur l\'icône de la question #' + index);
        toggleFAQItem($faqItem, $answer, $question.closest(config.faqContainerSelector));
      });
    }
    
    if (config.debug) console.log('GoldWizard FAQ: Paire #' + index + ' configurée dans le conteneur #' + containerIndex);
  }
  
  /**
   * Ferme un élément FAQ avec animation puis display:none
   * @param {jQuery} $faqItem - L'élément FAQ à fermer
   * @param {jQuery} $answer - L'élément réponse
   * @param {string} itemId - ID de l'élément
   */
  function closeFAQItem($faqItem, $answer, itemId) {
    if (config.debug) console.log('GoldWizard FAQ: Fermeture de l\'élément ' + itemId);
    
    // Retirer la classe active
    $faqItem.removeClass(config.activeClass);
    
    // Restaurer la hauteur initiale
    if (itemId && initialHeights[itemId]) {
      if (config.debug) {
        console.log('GoldWizard FAQ: Restauration de la hauteur initiale pour ' + itemId + ': ' + initialHeights[itemId] + 'px');
      }
      
      // Appliquer la hauteur initiale
      $faqItem.css({
        'height': initialHeights[itemId] + 'px',
        'min-height': 'auto',
        'max-height': 'none'
      });
      
      // Si on a une position initiale, la restaurer
      if (initialPositions[itemId]) {
        if (config.debug) {
          console.log('GoldWizard FAQ: Restauration de la position initiale pour ' + itemId, initialPositions[itemId]);
        }
      }
    }
    
    // Animation de fermeture
    $answer.css({
      'height': '0',
      'opacity': '0',
      'padding': '0',
      'margin': '0'
    });
    
    // Après l'animation, mettre display: none
    setTimeout(function() {
      $answer.css('display', 'none');
      if (config.debug) console.log('GoldWizard FAQ: Élément ' + itemId + ' maintenant en display: none');
    }, config.animationDuration);
  }
  
  /**
   * Ouvre un élément FAQ avec animation
   * @param {jQuery} $faqItem - L'élément FAQ à ouvrir
   * @param {jQuery} $answer - L'élément réponse
   * @param {string} itemId - ID de l'élément
   */
  function openFAQItem($faqItem, $answer, itemId) {
    if (config.debug) console.log('GoldWizard FAQ: Ouverture de l\'élément ' + itemId);
    
    // Ajouter la classe active
    $faqItem.addClass(config.activeClass);
    
    // D'abord, s'assurer que l'élément est visible mais avec hauteur 0
    $answer.css({
      'display': 'block',
      'visibility': 'visible',
      'height': '0',
      'opacity': '0'
    });
    
    // Force reflow
    $answer[0].offsetHeight;
    
    // Méthode 1: Calculer la hauteur via scrollHeight
    var contentHeight = 0;
    if ($answer[0] && typeof $answer[0].scrollHeight !== 'undefined' && $answer[0].scrollHeight > 0) {
      contentHeight = $answer[0].scrollHeight;
      if (config.debug) console.log('GoldWizard FAQ: Hauteur du contenu via scrollHeight:', contentHeight + 'px');
    } else {
      // Méthode 2: Calculer la hauteur en clonant l'élément
      var $clone = $answer.clone()
        .css({
          'position': 'absolute',
          'visibility': 'hidden',
          'display': 'block',
          'height': 'auto',
          'width': $answer.width() + 'px',
          'padding': '',
          'margin': '',
          'opacity': '1'
        })
        .appendTo('body');
      
      contentHeight = $clone.outerHeight(true);
      $clone.remove();
      
      if (config.debug) console.log('GoldWizard FAQ: Hauteur du contenu via clone:', contentHeight + 'px');
    }
    
    // Méthode 3: Calculer la hauteur via les enfants
    if (contentHeight <= 20) {
      contentHeight = 0;
      $answer.children().each(function() {
        var $child = $(this);
        contentHeight += $child.outerHeight(true);
      });
      
      if (config.debug) console.log('GoldWizard FAQ: Hauteur du contenu via enfants:', contentHeight + 'px');
    }
    
    // Si on n'a toujours pas de hauteur valide, utiliser une valeur par défaut
    if (contentHeight <= 20) {
      contentHeight = 100;
      if (config.debug) console.log('GoldWizard FAQ: Utilisation de la hauteur par défaut:', contentHeight + 'px');
    }
    
    // Ajouter un peu de marge pour éviter les coupures
    contentHeight += 20;
    
    // Appliquer l'animation
    $answer.css({
      'height': contentHeight + 'px',
      'opacity': '1',
      'padding': '',
      'margin': ''
    });
    
    // Ajuster la hauteur du conteneur parent si nécessaire
    var requiredHeight = initialHeights[itemId] + contentHeight;
    if (config.debug) console.log('GoldWizard FAQ: Hauteur requise pour le conteneur:', requiredHeight + 'px');
    
    $faqItem.css({
      'height': 'auto',
      'min-height': requiredHeight + 'px'
    });
    
    // Solution de secours - forcer l'affichage après un court délai
    setTimeout(function() {
      if (config.debug) console.log('GoldWizard FAQ: Application de la solution de secours');
      $answer.css({
        'height': 'auto',
        'min-height': contentHeight + 'px',
        'opacity': '1'
      });
      
      // S'assurer que le conteneur parent est suffisamment grand
      $faqItem.css({
        'height': 'auto',
        'min-height': requiredHeight + 'px'
      });
    }, config.animationDuration + 50);
  }
  
  /**
   * Bascule l'état ouvert/fermé d'un élément FAQ
   * @param {jQuery} $faqItem - L'élément FAQ à basculer
   * @param {jQuery} $answer - L'élément réponse
   * @param {jQuery} $container - Le conteneur parent
   */
  function toggleFAQItem($faqItem, $answer, $container) {
    var isActive = $faqItem.hasClass(config.activeClass);
    var itemId = $faqItem.attr('data-faq-id');
    
    if (config.debug) {
      console.log('GoldWizard FAQ: Basculement de l\'état - État actuel: ' + (isActive ? 'Ouvert' : 'Fermé'));
      console.log('GoldWizard FAQ: ID de l\'élément:', itemId);
    }
    
    // Si on doit fermer les autres éléments ouverts
    if (config.singleOpen && !isActive) {
      var $activeItems = $container.find('.' + config.activeClass);
      if (config.debug && $activeItems.length) console.log('GoldWizard FAQ: Fermeture de ' + $activeItems.length + ' élément(s) ouvert(s)');
      
      $activeItems.each(function() {
        var $activeItem = $(this);
        if (!$activeItem.is($faqItem)) {
          // Trouver l'élément de réponse associé
          var $activeAnswer = $activeItem.find('.goldwizard-faq-answer');
          if (!$activeAnswer.length) {
            $activeAnswer = $activeItem.next('.goldwizard-faq-answer');
          }
          
          if ($activeAnswer.length) {
            var activeItemId = $activeItem.attr('data-faq-id');
            closeFAQItem($activeItem, $activeAnswer, activeItemId);
          }
        }
      });
    }
    
    // Basculer l'état de l'élément actuel
    if (isActive) {
      // Fermer l'élément
      closeFAQItem($faqItem, $answer, itemId);
      if (config.debug) console.log('GoldWizard FAQ: Élément fermé');
    } else {
      // Ouvrir l'élément
      openFAQItem($faqItem, $answer, itemId);
      if (config.debug) console.log('GoldWizard FAQ: Élément ouvert');
    }
  }
  
  // Initialiser le plugin quand le document est prêt
  $(document).ready(function() {
    init();
  });
  
})(jQuery);
