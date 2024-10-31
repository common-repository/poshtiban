<p><?php printf(__('%s is not installed on your wordpress website.', $text_domain), $title); ?></p>
<p>
    <?php
    printf(
        __('For using this feature, please %s first', $text_domain),
        sprintf('<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/plugins/duplicator/',__('Install it', $text_domain))
    );
    ?>
</p>
