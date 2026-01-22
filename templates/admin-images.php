<?php
if (!defined('ABSPATH')) {
    exit;
}

$cards = array(
    'maruti' => 'Maruti',
    'ganpati' => 'Ganpati',
    'superman' => 'Superman',
    'spiderman' => 'Spiderman'
);

$types = array(
    'normal' => 'Normal',
    'winner' => 'Winner',
    'loss' => 'Loss'
);
?>

<div class="wrap">
    <h1>Card Flip Game - Card Images</h1>

    <form method="post" action="options.php">
        <?php settings_fields('cfg_images_group'); ?>
        <?php do_settings_sections('cfg_images_group'); ?>

        <?php foreach ($cards as $card_key => $card_name): ?>
            <h2><?php echo esc_html($card_name); ?></h2>
            <table class="form-table">
                <?php foreach ($types as $type_key => $type_name): ?>
                    <?php
                    $option_name = 'cfg_card_' . $card_key . '_' . $type_key;
                    $image_url = get_option($option_name, '');
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($option_name); ?>"><?php echo esc_html($type_name); ?> Image</label>
                        </th>
                        <td>
                            <input type="text" id="<?php echo esc_attr($option_name); ?>" name="<?php echo esc_attr($option_name); ?>" value="<?php echo esc_url($image_url); ?>" class="regular-text cfg-image-url">
                            <button type="button" class="button cfg-upload-image-btn" data-target="<?php echo esc_attr($option_name); ?>">Upload Image</button>
                            <?php if ($image_url): ?>
                                <div class="cfg-image-preview">
                                    <img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; height: auto; margin-top: 10px;">
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>

        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var mediaUploader;

    $('.cfg-upload-image-btn').on('click', function(e) {
        e.preventDefault();

        var button = $(this);
        var targetInput = button.data('target');

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#' + targetInput).val(attachment.url);

            var preview = $('#' + targetInput).siblings('.cfg-image-preview');
            if (preview.length) {
                preview.find('img').attr('src', attachment.url);
            } else {
                $('#' + targetInput).after('<div class="cfg-image-preview"><img src="' + attachment.url + '" style="max-width: 150px; height: auto; margin-top: 10px;"></div>');
            }
        });

        mediaUploader.open();
    });
});
</script>
