/**
 * GoldWizard Personnalisation JavaScript
 */
(function($) {
    'use strict';

    // Variables globales
    var uploadedImages = [];
    var maxFileSize = goldwizard_personnalisation_vars.max_file_size;
    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    var i18n = goldwizard_personnalisation_vars.i18n;

    // Initialisation
    $(document).ready(function() {
        initImageUpload();
    });

    // Initialiser l'upload d'images
    function initImageUpload() {
        var $dropzone = $('#goldwizard-personnalisation-dropzone');
        var $fileInput = $('#goldwizard-personnalisation-images');
        var $preview = $('#goldwizard-personnalisation-preview');
        var $imageData = $('#goldwizard-personnalisation-image-data');

        // Si les éléments n'existent pas, ne rien faire
        if (!$dropzone.length || !$fileInput.length) {
            return;
        }

        // Événement de clic sur le bouton de sélection
        $dropzone.find('.goldwizard-personnalisation-select-button').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $fileInput.click();
        });

        // Événement de clic sur la zone de dépôt
        $dropzone.on('click', function(e) {
            if ($(e.target).is($dropzone) || $(e.target).is('.goldwizard-personnalisation-dropzone-inner') || $(e.target).is('.goldwizard-personnalisation-dropzone-text')) {
                $fileInput.click();
            }
        });

        // Événements de glisser-déposer
        $dropzone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        $dropzone.on('dragleave dragend drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        $dropzone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var files = e.originalEvent.dataTransfer.files;
            handleFiles(files);
        });

        // Événement de changement du champ de fichier
        $fileInput.on('change', function(e) {
            var files = e.target.files;
            handleFiles(files);
            // Réinitialiser le champ de fichier pour permettre la sélection du même fichier
            $(this).val('');
        });

        // Fonction pour gérer les fichiers sélectionnés
        function handleFiles(files) {
            if (!files || !files.length) {
                return;
            }

            // Parcourir les fichiers
            for (var i = 0; i < files.length; i++) {
                var file = files[i];

                // Vérifier le type de fichier
                if (allowedTypes.indexOf(file.type) === -1) {
                    showError(i18n.invalid_file_type);
                    continue;
                }

                // Vérifier la taille du fichier
                if (file.size > maxFileSize) {
                    showError(i18n.file_too_large);
                    continue;
                }

                // Uploader le fichier
                uploadFile(file);
            }
        }

        // Fonction pour uploader un fichier
        function uploadFile(file) {
            var formData = new FormData();
            formData.append('action', 'goldwizard_personnalisation_upload_image');
            formData.append('nonce', goldwizard_personnalisation_vars.nonce);
            formData.append('file', file);

            // Ajouter un indicateur de chargement
            var $loading = $('<div class="goldwizard-personnalisation-loading"></div>');
            $dropzone.find('.goldwizard-personnalisation-select-button').prepend($loading);
            $dropzone.find('.goldwizard-personnalisation-select-button').text(i18n.uploading);

            // Envoyer la requête AJAX
            $.ajax({
                url: goldwizard_personnalisation_vars.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Supprimer l'indicateur de chargement
                    $loading.remove();
                    $dropzone.find('.goldwizard-personnalisation-select-button').text(i18n.select_images);

                    if (response.success) {
                        // Ajouter l'image à la liste
                        uploadedImages.push(response.data);

                        // Mettre à jour le champ caché
                        $imageData.val(JSON.stringify(uploadedImages));

                        // Ajouter l'image à la prévisualisation
                        addImagePreview(response.data);
                    } else {
                        showError(response.data.message);
                    }
                },
                error: function() {
                    // Supprimer l'indicateur de chargement
                    $loading.remove();
                    $dropzone.find('.goldwizard-personnalisation-select-button').text(i18n.select_images);

                    showError(i18n.upload_error);
                }
            });
        }

        // Fonction pour ajouter une image à la prévisualisation
        function addImagePreview(imageData) {
            var $imagePreview = $('<div class="goldwizard-personnalisation-image-preview" data-index="' + (uploadedImages.length - 1) + '"></div>');
            var $image = $('<img src="' + imageData.thumb_url + '" alt="">');
            var $removeButton = $('<button type="button" class="remove-image" title="' + i18n.remove_image + '">×</button>');

            $imagePreview.append($image).append($removeButton);
            $preview.append($imagePreview);

            // Événement de clic sur le bouton de suppression
            $removeButton.on('click', function() {
                var index = $imagePreview.data('index');
                uploadedImages.splice(index, 1);
                $imageData.val(uploadedImages.length ? JSON.stringify(uploadedImages) : '');
                $imagePreview.remove();

                // Mettre à jour les indices des images restantes
                $preview.find('.goldwizard-personnalisation-image-preview').each(function(i) {
                    $(this).data('index', i);
                });
            });
        }

        // Fonction pour afficher une erreur
        function showError(message) {
            var $error = $('<div class="goldwizard-personnalisation-error">' + message + '</div>');
            $dropzone.after($error);

            // Supprimer le message d'erreur après 5 secondes
            setTimeout(function() {
                $error.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

})(jQuery);
