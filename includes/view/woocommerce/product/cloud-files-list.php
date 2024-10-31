<?php
use Poshtiban\Main;
?>
<div class="<?php printf( '%s_woocommerce_file_selector_sections', $slug ) ?>">
    <p style="text-align: center; margin-top: 15px">
        <a href="#" class="button-secondary disabled" data-target=".selector"><?php _e( 'Select file', $text_domain ); ?></a>
        <a href="#" class="button-secondary" data-target=".upload"><?php _e( 'Upload file', $text_domain ); ?></a>
    </p>

    <div class="section upload" style="display: none">
        <div id="<?php printf('%s-drag-drop-area', Main::$slug) ?>"></div>
    </div>

    <div class="section selector">
        <div class="tree ">
            <ul>

				<?php
				foreach ( $files as $key => $file ): ?>
					<?php if ( $file->type === 'Folder' ): ?>
                        <li class="parent_li">
                        <span class="title" data-id="<?php echo $file->id ?>">
                            <div class="spinner"></div>
                            <span class="dashicons dashicons-plus"></span>
                            <?php echo $file->name; ?>
                        </span>
                            <ul></ul>
                        </li>
					<?php else: ?>
                        <li><span class="file" data-id="<?php echo $file->id ?>"><?php echo $file->name; ?></span></li>
					<?php endif; ?>

				<?php endforeach; ?>

            </ul>
        </div>
    </div>
</div>


<script>

  (function ($) {
    $(document).ready(function ($) {
      function renderList(files) {
        var html = ''
        jQuery.map(files, function (file, i) {
          if (file.type === 'Folder') {
            html += ' <li class="parent_li"><span class="title" data-id="' + file.id + '"><div class="spinner"></div><span class="dashicons dashicons-plus"></span>' + file.name + '</span><ul></ul></li>';
          } else {
            html += '<li><span class="file" data-id="' + file.id + '">' + file.name + '</span></li>';
          }
        });
        // html += '</ul>';
        return html;
      }

      jQuery(document).on('click', "<?php printf( '.%s_woocommerce_file_selector_sections', $slug ) ?> > p > a", function (e) {
        $("<?php printf( '.%s_woocommerce_file_selector_sections', $slug ) ?> > p > a").removeClass('disabled');
        $(this).addClass('disabled');
        var target = $(this).data("target");
        $("<?php printf( '.%s_woocommerce_file_selector_sections', $slug ) ?> .section").slideUp("fast");
        $(target).slideDown("fast");
      });

      jQuery(document).on('click', 'span.title', function (e) {
        var children = jQuery(this).parent('li.parent_li').find(' > ul > li');
        if (children.is(":visible")) {
          children.hide('fast');
          jQuery(this).find(' > span').addClass('dashicons-plus').removeClass('dashicons-minus');
        } else {
          var thisEl = jQuery(this);
          if (thisEl.parent().find('ul').children().length > 0) {
            children.show('fast');
            thisEl.find(' > span').addClass('dashicons-minus').removeClass('dashicons-plus');
          } else {
            var folder_id = jQuery(this).data('id');
            thisEl.parent().addClass('loading');

            jQuery.ajax({
              url: <?php printf('%s.ajaxUrl', $slug) ?>,
              type: 'POST',
              dataType: 'json',
              data: {
                action: '<?php printf( '%s_browse_folder', Main::$slug ) ?>',
                folder_id,
              }
            })
              .done(function (result) {
                if (result.success === true) {
                  jQuery(this).attr('title', 'Collapse this branch').find(' > span').addClass('dashicons-minus').removeClass('dashicons-plus');
                  thisEl.parent().addClass("asd");
                  thisEl.parent().find('ul').html(renderList(result.payload));
                  children.show('fast');
                  thisEl.parent().removeClass('loading');
                  thisEl.find(' > span').addClass('dashicons-minus').removeClass('dashicons-plus');
                } else {
                  alert(result.message)
                }
              })
              .fail(function (result) {
              })
              .always(function (result) {
              })
          }
        }
        e.stopPropagation();
      });

      jQuery(document).on('click', 'span.file', function (e) {
        var fileId = jQuery(this).data('id');
        var fileName = jQuery(this).html();
        var currentSelector = $('.currentSelector');
        currentSelector.parents('.mirror_row').find('td.file_id input').val(fileId);
        currentSelector.parents('.mirror_row').find('td.file_name input').val(fileName);
        $("<?php printf('.%s_file_selector', $slug) ?>").removeClass('currentSelector');
        self.parent.tb_remove();
      });


      if ($(<?php printf('%s.uppySelector', $slug) ?>).length) {
        var product_id = $('#post_ID').val();
        var uppy = Uppy.Core({
          locale: Uppy.locales[<?php printf('%s.locale', $slug) ?>],
          meta: {
            token: <?php printf('%s.helper.token', $slug) ?>,
            folder_id: <?php printf('%s.woocommerceUploadPathId', $slug) ?>,
            partitionPath: '/product-' + product_id,
            public: "private",
          }
        })
          .use(Uppy.Dashboard, {
            inline: true,
            target: <?php printf('%s.uppySelector', $slug) ?>,
            removeFingerprintOnSuccess: true,
            proudlyDisplayPoweredByUppy: false,
            chunkSize: 1000 * 1000 * 10,
            limit: 5,
          })
          .use(Uppy.Tus, {
            endpoint: <?php printf('%s.helper.urls.tus', $slug) ?>
          })
          .use(Uppy.Url, {
            target: Uppy.Dashboard,
            companionUrl: <?php printf('%s.helper.urls.companion', $slug) ?>,
          })
          .use(Uppy.GoogleDrive, {
            target: Uppy.Dashboard,
            companionUrl: <?php printf('%s.helper.urls.companion', $slug) ?>,
          })
          .use(Uppy.Dropbox, {
            target: Uppy.Dashboard,
            companionUrl: <?php printf('%s.helper.urls.companion', $slug) ?>,
          })
          .use(Uppy.Webcam, {
            target: Uppy.Dashboard,
            onBeforeSnapshot: () => Promise.resolve(),
          });


        uppy.on('upload-success', (file, response) => {
          var uploadURL = response.uploadURL;
          var upload_id = uploadURL.replace(<?php printf('%s.helper.urls.tus', $slug) ?>.concat('/'), '');
          $.ajax({
            url: <?php printf('%s.ajaxUrl', $slug) ?>,
            type: 'POST',
            dataType: 'json',
            data: {
              action: "<?php printf('%s_add_attachment_by_uppy', $slug) ?>",
              path: '/product-' + product_id,
              upload_id: upload_id
            }
          })
            .done(function (result) {
              console.log('done', result)
            })
            .fail(function (response) {

            })
            .always(function (result) {
            })
        });
      }


    });
  })(jQuery);


</script>