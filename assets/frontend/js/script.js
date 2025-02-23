(function ($) {
  $(document).ready(function ($) {

    $(document).on('click', 'a.poshtiban_get_download_link', function (event) {
      event.preventDefault();
      var thisAction = $(this);
      $(this).addClass('loading');
      var file_id = $(this).data('file-id');
      var post_id = $(this).data('id');
      var order_id = $(this).data('order-id');

      $.ajax({
        url: poshtiban.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'poshtiban_download_generator',
          file_id,
          product: post_id,
          order_id,
          nonce: poshtiban.nonce
        }
      })
        .done(function (result) {
          if (result.success === true) {
            window.location = result.data;
          } else {
            alert(result.data)
          }
        })
        .fail(function (result) {
          alert(poshtiban.error)
        })
        .always(function (result) {
          thisAction.removeClass('loading');
        })

    });

  });
})(jQuery);
