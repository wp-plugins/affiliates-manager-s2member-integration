<?php

/*
  Plugin Name: Affiliates Manager S2Member Integration
  Plugin URI: https://wpaffiliatemanager.com
  Description: Process an affiliate commission via Affiliates Manager after a S2Member payment.
  Version: 1.0.3
  Author: wp.insider, affmngr
  Author URI: https://wpaffiliatemanager.com
 */

add_action("ws_plugin__s2member_before_sc_paypal_button_after_shortcode_atts", "wpam_s2member_integration");
add_action("ws_plugin__s2member_pro_before_sc_paypal_form_after_shortcode_atts", "wpam_s2member_integration");
add_action("ws_plugin__s2member_pro_before_sc_authnet_form_after_shortcode_atts", "wpam_s2member_integration");
add_action("plugins_loaded", "wpam_s2member_notification_url");
add_action("init", "wpam_s2member_process_affiliate_commission");

function wpam_s2member_integration($vars = array()) {
    if(isset($_COOKIE['wpam_id']))  //checking new tracking cookie first
    {
        $strRefKey = $_COOKIE['wpam_id'];
        $vars["__refs"]["attr"]["custom"] .= "|" . $strRefKey;
    }
    else if (isset($_COOKIE[WPAM_PluginConfig::$RefKey])) {
        $strRefKey = $_COOKIE[WPAM_PluginConfig::$RefKey];
        $vars["__refs"]["attr"]["custom"] .= "|" . $strRefKey;
    }
}

function wpam_s2member_notification_url() {
    $urls = &$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["payment_notification_urls"];
    $notification_url = home_url() . '/?wpam_s2member=1&refkey=%%cv1%%&sale_amt=%%amount%%&txn_id=%%txn_id%%';
    $pos = strpos($urls, $notification_url);
    if ($pos === false) {
        $urls = trim($urls . "\n" . $notification_url);
    }

    $specific_post_page_urls = &$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["sp_sale_notification_urls"];
    $pos2 = strpos($specific_post_page_urls, $notification_url);
    if ($pos2 === false) {
        $specific_post_page_urls = trim($specific_post_page_urls . "\n" . $notification_url);
    }
}

function wpam_s2member_process_affiliate_commission() {
    if (isset($_REQUEST['wpam_s2member']) && isset($_REQUEST['refkey'])) {
        WPAM_Logger::log_debug('S2Member Integration - notification received.');
        $strRefKey = strip_tags($_REQUEST['refkey']);
        if (!empty($strRefKey)) {
            WPAM_Logger::log_debug('S2Member Integration - Tracking data present. Need to track affiliate commission. Tracking value: ' . $strRefKey);
            $sale_amt = strip_tags($_REQUEST['sale_amt']);
            $txn_id = strip_tags($_REQUEST['txn_id']);
            $requestTracker = new WPAM_Tracking_RequestTracker();
            $requestTracker->handleCheckoutWithRefKey($txn_id, $sale_amt, $strRefKey);
            WPAM_Logger::log_debug('S2Member Integration - Commission tracked for transaction ID: ' . $txn_id . '. Purchase amt: ' . $sale_amt);
        }
    }
}
