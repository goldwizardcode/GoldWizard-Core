# GoldWizard Core

Extension WordPress professionnelle qui ajoute des fonctionnalités de personnalisation et de réduction pour WooCommerce.

## Description

GoldWizard Core est une extension WordPress qui améliore votre boutique WooCommerce en ajoutant deux fonctionnalités principales :

1. **Personnalisation de produits** : Permet à vos clients d'uploader des images et de saisir du texte pour personnaliser leurs produits.
2. **Affichage des réductions** : Affiche automatiquement le pourcentage d'économie sur les produits en promotion.

## Fonctionnalités

### Personnalisation de produits

- Upload d'images par glisser-déposer
- Champs textuels personnalisables
- Configuration par produit
- Affichage des personnalisations dans le panier et les commandes
- Interface utilisateur moderne et responsive

### Affichage des réductions

- Calcul automatique du pourcentage d'économie
- Affichage via shortcode `[goldwizard_reduction]`
- Support des vues rapides et des contextes AJAX
- Détection automatique du produit courant

## Installation

1. Téléchargez le dossier `goldwizard-core` dans le répertoire `/wp-content/plugins/` de votre site WordPress
2. Activez l'extension via le menu 'Extensions' dans WordPress
3. Assurez-vous que WooCommerce est installé et activé

## Configuration

### Personnalisation de produits

1. Éditez un produit dans WooCommerce
2. Dans l'onglet "Données produit", vous trouverez la section "Options de personnalisation"
3. Cochez "Activer l'upload d'images" et/ou "Activer les champs textuels"
4. Si vous activez les champs textuels, vous pouvez configurer les champs personnalisés en dessous

### Affichage des réductions

Utilisez le shortcode `[goldwizard_reduction]` dans vos modèles de produit ou dans la description du produit.

Options du shortcode :
- `id` : ID du produit (facultatif, détecté automatiquement si non spécifié)

Exemple : `[goldwizard_reduction id="123"]`

## Compatibilité

- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.2+
- Navigateurs modernes (Chrome, Firefox, Safari, Edge)

## Support

Pour toute question ou assistance, veuillez contacter notre support à l'adresse support@goldwizard.com.

## Licence

Copyright © 2025 GoldWizard. Tous droits réservés.
