/**
 * some of the code is based on the Admin Addon Media Rename by Dávid Szabó
 *     see https://github.com/david-szabo97/grav-plugin-admin-addon-media-rename
 */
$(function() {

    adminAddonMediaMetadata.metadata = {};

    var clickedElement;
    var fileName;
    var $elementName;
    var modal;
    var $modal;
    var isPageMedia = false;

    // Append modal
    $('body').append(adminAddonMediaMetadata.MODAL);

    // add filepath to postable fields once
    metadataFormFields.push('filepath');

    $(document).off('click', '[data-dz-name], .dz-metadata-edit');
    $(document).on('click', '[data-dz-name], .dz-metadata-edit', function(e) {
        clickedElement = $(this);
        modal = $.remodal.lookup[$('[data-remodal-id=modal-admin-addon-media-metadata]').data('remodal')];
        $modal = modal.$modal;
        modal.open();

        // Populate fields
        elementName = clickedElement.closest('.dz-preview').find('[data-dz-name]')
        fileName = elementName.text();
        $('[name=filename]', $modal).val(fileName);
        if (mediaListOnLoad[fileName] !== undefined) {
            for (var i = 0; i < metadataFormFields.length; i++) {
                $('[name=' + metadataFormFields[i] + ']', $modal).val(mediaListOnLoad[fileName][metadataFormFields[i]]);
            }
        } else {
            for (var i = 0; i < metadataFormFields.length; i++) {
                $('[name=' + metadataFormFields[i] + ']', $modal).val('');
            }
        }
        $modal.find('form span.filename').text(fileName);

        // Reset loading state
        $('.loading', $modal).addClass('hidden');
//		$('.button', $modal).removeClass('hidden').css('visibility', 'hidden');
        $('.button', $modal).removeClass('hidden');

        isPageMedia = !clickedElement.closest('.dz-preview').hasClass('dz-no-editor');
        $modal.find('.block-toggle').toggleClass('hidden', !isPageMedia);
        $modal.find('.page-media-info').toggleClass('hidden', !isPageMedia);
        $modal.find('.non-page-media-info').toggleClass('hidden', isPageMedia);
    });

    /**
     * add the new data dynamically to mediaListOnLoad JSON object
     * for newly uploaded media files as well as for “older” files, overwriting the data already in that JSON object
     * and save it in the YAML file
     * Variables from above ($(document).on), like fileName and $modal are still available
     */
    $(document).on('click', '[data-remodal-id=modal-admin-addon-media-metadata] .button', function(e) {
        // hiding the button and showing the loading icon
        $('.loading', $modal).removeClass('hidden');
        $('.button', $modal).addClass('hidden');

        // create the file object for newly uploaded files
        if (mediaListOnLoad[fileName] === undefined) {
            mediaListOnLoad[fileName] = new Object();
            mediaListOnLoad[fileName]['filename'] = fileName;
        }

        // Do request with JS Fetch API: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
        var data = new FormData();
        data.append('filename', fileName);
        data.append('admin-nonce', GravAdmin.config.admin_nonce);

        // add the new values to mediaListOnLoad (for later updates before page reload)
        // append the new values to the FormData (data.append)
        for (var i = 0; i < metadataFormFields.length; i++) {
            var newVal = $('[name=' + metadataFormFields[i] + ']', $modal).val();
            mediaListOnLoad[fileName][metadataFormFields[i]] = newVal;
            data.append(metadataFormFields[i], newVal);
        }

        // mock the filepath for freshly (unsaved) files
        if ( !data.get( 'filepath' ) )
        {
            data.set( 'filepath', adminAddonMediaMetadataPath );
        }

        fetch(adminAddonMediaMetadata.PATH, { method: 'POST', body: data, credentials: 'same-origin' })
            .then(res => res.json())
            .then(result => {
                if (result.error) {
                    var alertModal = $.remodal.lookup[$('[data-remodal-id=modal-admin-addon-media-metadata-alert]').data('remodal')];
                    alertModal.open();
                    $('p', alertModal.$modal).html(result.error.msg);
                    return;
                }

                modal.close();
            });
    });

    // adding the “edit metadata” button next to all media files
    setInterval(function addMetadataButton() {
        $('.dz-preview').each(function(i, dz) {
            if ($(this).find('.dz-metadata-edit').length == 0) {
                var editButton = document.createElement('a');
                //editButton.href = 'javascript:undefined;';
                editButton.title = adminAddonMediaMetadataButton;
                editButton.className = 'dz-metadata-edit';
                editButton.innerText = adminAddonMediaMetadataButton;
                var fileName = $(this).find('[data-dz-name]').text();
                editButton.setAttribute('data-filename', fileName);
                $(this).append(editButton);
            }
            $(this).find('.dz-metadata').remove();
        });
    }, 1000);
});

