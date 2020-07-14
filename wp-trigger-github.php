<?php

/**
 * @package WPTriggerGithub
 */
/*
Plugin Name: WP Trigger Github
Plugin URI: https://github.com/gglukmann/wp-trigger-github
Description: Save or update action triggers Github repository_dispatch action
Version: 1.2.1
Author: Gert Glükmann
Author URI: https://github.com/gglukmann
License: GPLv3
Text-Domain: wp-trigger-github
 */

if (!defined('ABSPATH')) {
  die;
}

class WPTriggerGithub
{
  function __construct()
  {
    add_action('admin_init', array($this, 'general_settings_section'));
    // add_action('save_post', array($this, 'run_hook'), 10, 3);
    add_action('wp_dashboard_setup', array($this, 'build_dashboard_widget'));
    add_action('admin_bar_menu', array($this, 'adminBarTriggerButton'));
    add_action('admin_footer', array($this, 'adminBarCssAndJs'));
    add_action('wp_footer', array($this, 'adminBarCssAndJs'));
    add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
    add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
  }

  public function activate()
  {
    flush_rewrite_rules();
    $this->general_settings_section();
  }

  public function deactivate()
  {
    flush_rewrite_rules();
  }

  function run_hook($post_id)
  {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!(wp_is_post_revision($post_id) || wp_is_post_autosave($post_id))) return;

    $github_token = get_option('option_token');
    $github_username = get_option('option_username');
    $github_repo = get_option('option_repo');

    if ($github_token && $github_username && $github_repo) {
      $url = 'https://api.github.com/repos/' . $github_username . '/' . $github_repo . '/dispatches';
      $args = array(
        'method'  => 'POST',
        'body'    => json_encode(array(
          'event_type' => 'dispatch'
        )),
        'headers' => array(
          'Accept' => 'application/vnd.github.everest-preview+json',
          'Content-Type' => 'application/json',
          'Authorization' => 'token ' . $github_token
        ),
      );

      wp_remote_post($url, $args);
    }
  }

  function general_settings_section()
  {
    add_settings_section(
      'general_settings_section',
      'WP Trigger Github Settings',
      array($this, 'my_section_options_callback'),
      'general'
    );
    add_settings_field(
      'option_username',
      'Repository Owner Name',
      array($this, 'my_textbox_callback'),
      'general',
      'general_settings_section',
      array(
        'option_username'
      )
    );
    add_settings_field(
      'option_repo',
      'Repository Name',
      array($this, 'my_textbox_callback'),
      'general',
      'general_settings_section',
      array(
        'option_repo'
      )
    );
    add_settings_field(
      'option_token',
      'Personal Access Token',
      array($this, 'my_password_callback'),
      'general',
      'general_settings_section',
      array(
        'option_token'
      )
    );
    add_settings_field(
      'option_workflow',
      'Actions Workflow Name',
      array($this, 'my_textbox_callback'),
      'general',
      'general_settings_section',
      array(
        'option_workflow'
      )
    );
    add_settings_field(
      'option_badge',
      'Actions Badge URL',
      array($this, 'my_textbox_callback'),
      'general',
      'general_settings_section',
      array(
        'option_badge'
      )
    );

    register_setting('general', 'option_token', 'esc_attr');
    register_setting('general', 'option_username', 'esc_attr');
    register_setting('general', 'option_repo', 'esc_attr');
    register_setting('general', 'option_workflow', 'esc_attr');
    register_setting('general', 'option_badge', 'esc_attr');
  }

  function my_section_options_callback()
  {
    echo '<p>Add repository owner name, repository name and generated personal access token to trigger Actions workflow.<br />If you want to see status badge on dashboard, add workflow name.</p>';
  }

  function my_textbox_callback($args)
  {
    $option = get_option($args[0]);
    echo '<input type="text" id="' . $args[0] . '" name="' . $args[0] . '" value="' . $option . '" />';
  }

  function my_password_callback($args)
  {
    $option = get_option($args[0]);
    echo '<input type="password" id="' . $args[0] . '" name="' . $args[0] . '" value="' . $option . '" />';
  }

  /**
   * Create Dashboard Widget for Github Actions deploy status
   */
  function build_dashboard_widget()
  {
    global $wp_meta_boxes;

    wp_add_dashboard_widget('github_actions_dashboard_status', 'Deployment Status', array($this, 'build_dashboard_status'));
  }

  function build_dashboard_status()
  {
    $github_username = get_option('option_username');
    $github_repo = get_option('option_repo');
    $github_workflow = rawurlencode(get_option('option_workflow'));
    $github_badge = get_option('option_badge');

    $markup = '<p>If the badge below shows an error (FAILED), please seek help. If the badge is green (PASSED), all is well 😃</p>';
    $markup .= '<img src="' . $github_badge . '" alt="Github Actions Status" />';

    echo $markup;
  }

  function adminBarTriggerButton($bar)
  {
    $bar->add_node([
      'id' => 'wp-trigger-github',
      'title' => 'Deploy Website <svg aria-hidden="true" focusable="false" data-icon="upload" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M296 384h-80c-13.3 0-24-10.7-24-24V192h-87.7c-17.8 0-26.7-21.5-14.1-34.1L242.3 5.7c7.5-7.5 19.8-7.5 27.3 0l152.2 152.2c12.6 12.6 3.7 34.1-14.1 34.1H320v168c0 13.3-10.7 24-24 24zm216-8v112c0 13.3-10.7 24-24 24H24c-13.3 0-24-10.7-24-24V376c0-13.3 10.7-24 24-24h136v8c0 30.9 25.1 56 56 56h80c30.9 0 56-25.1 56-56v-8h136c13.3 0 24 10.7 24 24zm-124 88c0-11-9-20-20-20s-20 9-20 20 9 20 20 20 20-9 20-20zm64 0c0-11-9-20-20-20s-20 9-20 20 9 20 20 20 20-9 20-20z"></path></svg>',
      'parent' => 'top-secondary',
      'href' => 'javascript:void(0)',
      'meta' => [
        'class' => 'wp-trigger-github-deploy-button'
      ]
    ]);
  }

  function adminBarCssAndJs()
  {
    if (!is_admin_bar_showing()) {
        return;
    }

    ?><style>

    #wpadminbar .wp-trigger-github-deploy-button > a {
      background-color: rgba(255, 255, 255, .2) !important;
      color: #FFFFFF !important;
    }
    #wpadminbar .wp-trigger-github-deploy-button > a:hover,
    #wpadminbar .wp-trigger-github-deploy-button > a:focus {
      background-color: rgba(255, 255, 255, .25) !important;
    }

    #wpadminbar .wp-trigger-github-deploy-button svg {
      width: 12px;
      height: 12px;
      margin-left: 5px;
    }

    #wpadminbar .wp-trigger-github-deploy-button > .ab-item {
      display: flex;
      align-items: center;
    }

    </style><?php
  }

  function enqueueScripts()
  {
    wp_enqueue_script(
      'wp-trigger-github-adminbar',
      plugins_url('', __FILE__ ).'/assets/admin.js',
      ['jquery']
    );

    $github_token = get_option('option_token');
    $github_username = get_option('option_username');
    $github_repo = get_option('option_repo');

    wp_localize_script('wp-trigger-github-adminbar', 'wpjd', [
      'token' => $github_token,
      'repo' => $github_repo,
      'username' => $github_username,
      'url' => 'https://api.github.com/repos/' . $github_username . '/' . $github_repo . '/dispatches'
    ]);
  }
}

if (class_exists('WPTriggerGithub')) {
  $WPTriggerGithub = new WPTriggerGithub();
}

// activation
register_activation_hook(__FILE__, array($WPTriggerGithub, 'activate'));

// deactivate
register_deactivation_hook(__FILE__, array($WPTriggerGithub, 'deactivate'));
