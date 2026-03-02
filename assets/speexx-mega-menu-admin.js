(function ($) {
  function openMediaFrame($wrap) {
    const frame = wp.media({
      title: 'Select mega menu image',
      button: { text: 'Use this image' },
      multiple: false,
    });

    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON();
      const previewUrl = (attachment.sizes && (attachment.sizes.medium || attachment.sizes.thumbnail || attachment.sizes.full))
        ? (attachment.sizes.medium || attachment.sizes.thumbnail || attachment.sizes.full).url
        : attachment.url;

      $wrap.find('.speexx-mega-image-id').val(attachment.id);
      $wrap.find('.speexx-mega-image-preview').html('<img src="' + previewUrl + '" alt="" style="max-width:200px;height:auto;display:block;" />');
    });

    frame.open();
  }

  $(document).on('click', '.speexx-mega-upload-image', function (event) {
    event.preventDefault();
    openMediaFrame($(this).closest('.speexx-mega-item-editor-wrap'));
  });

  $(document).on('click', '.speexx-mega-remove-image', function (event) {
    event.preventDefault();
    const $wrap = $(this).closest('.speexx-mega-item-editor-wrap');
    $wrap.find('.speexx-mega-image-id').val('');
    $wrap.find('.speexx-mega-image-preview').empty();
  });
})(jQuery);
