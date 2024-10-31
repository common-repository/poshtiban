<?php
namespace Poshtiban\Backup;
?>

<table class="widefat dup-pack-table">
    <thead>
    <tr>
        <th style="width: 100px;" ><?php use Poshtiban\Main;

	        esc_html_e("Created", $text_domain) ?></th>
        <th style="width: 70px;"><?php esc_html_e("Size", $text_domain) ?></th>
        <th><?php esc_html_e("Package Name", $text_domain) ?></th>
        <th style="text-align:center; width: 200px;">
			<?php esc_html_e("Package", $text_domain) ?>
        </th>
    </tr>
    </thead>

    <?php \DUP_Package::by_status_callback(['Poshtiban\Backup\Duplicator', 'table_row'], [], false, 0, '`id` DESC'); ?>
</table>
<script>
  jQuery(document).on('click', 'a.upload_backup', function (event) {
    event.preventDefault();
    var thisButton = jQuery(this);
    thisButton.addClass('disabled');
    thisButton.parents('.get-btns').find('.result').html('');
    var url = thisButton.attr('href');
    var type = thisButton.data('type');
    var id = thisButton.data('id');
    if( type === 'installer' ) {
      jQuery.get(url).success(function(data){
        url = data;
        jQuery.ajax({
          url: <?php printf('%s.ajaxUrl', $slug) ?>,
          type: 'POST',
          dataType: 'json',
          data: {
            action: '<?php printf( '%s_duplicator_backup_remote_upload_', Main::$slug ) ?>'+type,
            url,
            package_id: id,
          }
        })
          .done(function (result) {
            var color = result.success ? "#00a500" : "#d00";
            thisButton.parents('.get-btns').find('.result').css("color", color).html(result.message)
          })
          .fail(function (response)
          {

          })
          .always(function (result) {
            thisButton.removeClass('disabled');
          })
      });
    } else {
      jQuery.ajax({
        url: <?php printf('%s.ajaxUrl', $slug) ?>,
        type: 'POST',
        dataType: 'json',
        data: {
          action: '<?php printf( '%s_duplicator_backup_remote_upload_', Main::$slug ) ?>'+type,
          url,
          package_id: id,
        }
      })
        .done(function (result) {
          var color = result.success ? "#00a500" : "#d00";
          thisButton.parents('.get-btns').find('.result').css("color", color).html(result.message)
        })
        .fail(function (response)
        {

        })
        .always(function (result) {
          thisButton.removeClass('disabled');
        })
    }

  })
</script>