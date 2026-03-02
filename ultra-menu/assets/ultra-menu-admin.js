(function ($) {
    'use strict';

    function renderPreview($container, url) {
        if (!url) {
            $container.html('');
            return;
        }

        $container.html('<img src="' + url + '" alt="" style="max-width:120px;height:auto;display:block;" />');
    }

    $(document).on('click', '.ultra-menu-select-image', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $field = $button.closest('.ultra-menu-image-field');
        var $input = $field.find('.edit-menu-item-ultra-menu-image-id');
        var $preview = $field.find('.ultra-menu-image-preview');

        var frame = wp.media({
            title: 'Select menu image',
            button: { text: 'Use image' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.id);
            renderPreview($preview, attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url);
        });

        frame.open();
    });

    $(document).on('click', '.ultra-menu-remove-image', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $field = $button.closest('.ultra-menu-image-field');

        $field.find('.edit-menu-item-ultra-menu-image-id').val('');
        renderPreview($field.find('.ultra-menu-image-preview'), '');
    });
}(jQuery));
