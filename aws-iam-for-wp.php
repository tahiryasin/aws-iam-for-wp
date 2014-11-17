<?php
/*
  Plugin Name: AWS IAM for WP
  Plugin URI: http://scriptbaker.com
  Description: This plugin creates an IAM user, adds it to 'upload-user' group, creates access keys for the user and sends to the user.
  Author: Tahir Yasin
  Author URI: https://about.me/tahiryasin
  Text Domain: awsiam-for-wp
  Version: 1.0
 * */
if (!defined('AWS_PLUGIN_DIR'))
    define('AWS_PLUGIN_DIR', untrailingslashit(dirname(__FILE__)));
// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';

use Aws\Sts\StsClient;
use Aws\S3\S3Client;
use Aws\Iam\IamClient;
//use Guzzle\Common\Collection;

class AWS_AIM {

    var $S3;
    var $Iam;
    var $bucket;

    function __construct() {
        add_action('init', array($this, 'init'));
        add_action('show_user_profile', array($this, 'show_extra_fields'));
        add_action('edit_user_profile', array($this, 'show_extra_fields'));
        add_action('user_new_form', array($this, 'show_extra_fields'));
        add_action('personal_options_update', array($this, 'save_extra_fields'));
        add_action('edit_user_profile_update', array($this, 'save_extra_fields'));
        add_action('user_register', array($this, 'save_extra_fields'));
    }

    function show_extra_fields($user) {
        $createBucket = 'no';
        ?>

        <h3>Amazon AWS</h3>

        <table class="form-table">

            <tr>
                <th><label for="twitter">Create User</label></th>

                <td>
                    <input <?php checked($createBucket, 'yes'); ?> type="checkbox" id="create_aws_folder" name="create_aws_folder" value="yes" />
                    <span class="description">Check if you want to create aws IAM user.</span>
                </td>
            </tr>

        </table>
        <?php
    }

    function init() {
        /*
          If you instantiate a new client for Amazon Simple Storage Service (S3) with
          no parameters or configuration, the AWS SDK for PHP will look for access keys
          in the AWS_ACCESS_KEY_ID and AWS_SECRET_KEY environment variables.

          For more information about this interface to Amazon S3, see:
          http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-s3.html#creating-a-client
         */
        $key = esc_attr(get_option('aws_api_key'));
        $secret = esc_attr(get_option('aws_api_secret'));
        $this->bucket = esc_attr(get_option('aws_bucket'));

        $config = array(
            'key' => $key,
            'secret' => $secret,
                //'region' => 'us-west-2',
                //  'base_url' => 'http://localhost:8000'
        );
        $this->S3 = S3Client::factory($config);
        $this->Iam = IamClient::factory($config);
    }

    function save_extra_fields($user_id) {

        if (!current_user_can('edit_user', $user_id))
            return FALSE;

        $createBucket = isset($_POST['create_aws_folder']) ? $_POST['create_aws_folder'] : "no";
        if ($createBucket == 'yes') {
            $user_info = get_userdata($user_id);
            $key = $user_info->user_login . '/';
            $params = array(
                'Path' => '/' . $this->bucket . '/',
                // UserName is required
                'UserName' => $user_info->user_login,
            );
            $this->createUser($params);
        }
    }

    function createUser($params) {
        try {
            $user = $this->Iam->createUser($params);
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
        try {
            $result = $this->Iam->addUserToGroup(array(
                // GroupName is required
                'GroupName' => 'upload-user',
                // UserName is required
                'UserName' => $params['UserName'],
            ));
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }

        try {
            $keys = $this->Iam->createAccessKey(array(
                'UserName' => $params['UserName'],
            ));
            $data = $keys->toArray();
            $SecretAccessKey = $data['AccessKey']['SecretAccessKey'];
            $AccessKeyId = $data['AccessKey']['AccessKeyId'];

            $to = $_POST['email'];
            $subject = "AWS API Credentails";
            $name = $_POST['nickname'];
            $message = "Hi $name, <br />";
            $message.="Below are you API credentials.<br /><br />";
            $message.="Access Key Id: $AccessKeyId<br />";
            $message.="Secret Access Key: $SecretAccessKey <br /><br />";
            $message.="Regards,<br />";
            $message.=get_bloginfo('name');

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            wp_mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }
}

class aws_options_page {

    function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    function admin_menu() {
        add_options_page('AWS Settings', 'AWS Settings', 'manage_options', 'options_page_slug', array($this, 'settings_page'));
    }

    function settings_page() {
        ob_start();
        include AWS_PLUGIN_DIR . '/inc/settings.php';
        $html = ob_get_clean();
        echo $html;
    }

    function register_settings() {
        //register our settings
        register_setting('aws-settings-group', 'aws_api_key');
        register_setting('aws-settings-group', 'aws_api_secret');
        register_setting('aws-settings-group', 'aws_bucket');
    }

}
new aws_options_page;

$GLOBALS['AWS_AIM'] = new AWS_AIM();
?>