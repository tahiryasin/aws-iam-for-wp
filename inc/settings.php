<div class="wrap">
    <h2>AWS Settings</h2>

    <form method="post" action="options.php">
        <?php settings_fields('aws-settings-group'); ?>
        <?php do_settings_sections('aws-settings-group'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">API Key</th>
                <td><input size="60" type="text" name="aws_api_key" value="<?php echo esc_attr(get_option('aws_api_key')); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">API Secret</th>
                <td><input size="60" type="text" name="aws_api_secret" value="<?php echo esc_attr(get_option('aws_api_secret')); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Bucket Name</th>
                <td><input size="60" type="text" name="aws_bucket" value="<?php echo esc_attr(get_option('aws_bucket')); ?>" /></td>
            </tr>
        </table>

<?php submit_button(); ?>

    </form>
</div>