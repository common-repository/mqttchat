<?php
/**
 * @package Mqttchat
 */
/**
 * Plugin Name: MQTT CHAT
 * Plugin URI: https://doc.mqtt-chat.com/mqttchat-cloud-web/custom-plugins/wordpress
 * Description: MQTT CHAT is a chat module that can be easily integrated  into your website or android application.
 * Version: 1.3.0
 * Author: Gaddour Mohamed
 * Author URI: https://mqtt-chat.com
 * License:    GPL v2 or later
 */
/*
{Plugin Name} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
{Plugin Name} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {URI to Plugin License}.
*/

defined ('ABSPATH') or die('Forbidden Access!');

class mqttchat{

    private $_pluginName;
    private $_userId;
    private $_userName;
    private $_userSurname;
    private $_userGender;
    private $_userAvatar;
    private $_userLink;
    
    
    function __construct(){
     add_action('init',array($this,'init')) ; 
     $this->_pluginName=plugin_basename(__FILE__);
    }

    function register(){
      add_action('admin_menu',array($this,'add_admin_pages'));
      add_filter("plugin_action_links_$this->_pluginName",array($this,"settings_link"));
    }

    function activate(){   
    }

    function desactivate(){
    } 
    
    function uninstall(){
        delete_option("app_id");
        delete_option("uf");
    } 

    function init(){
        $user = wp_get_current_user(); 
        
        if ( ($user instanceof WP_User) ) {

            $this->_userId= esc_html( $user->ID );
            $this->_userName=esc_html( $user->user_firstname );
            $this->_userSurname=$user->user_lastname;
            $this->_userAvatar=get_avatar_url( $user, 50 );

            add_action( 'wp_enqueue_scripts',array($this,'include_mqttchat_script'));

            add_shortcode('mqttchat',array($this,'shortcode'));

        }        
    }


    /** privte functions */

    function settings_link($links){
       $settings_link='<a href="options-general.php?page=mqttchat-admin">Settings</a>';
       array_push($links,$settings_link);
       return $links;
    }

    function add_admin_pages(){
      add_submenu_page( 'options-general.php','MQTT-CHAT Plugin','MqttChat','manage_options','mqttchat-admin',array($this,'admin_index'));
      add_action('admin_init',array($this,"mqttchat_custom_settings"));
    }   


    function admin_index(){
       require_once plugin_dir_path(__FILE__).'templates/admin.php';
    }

    function mqttchat_custom_settings(){
     register_setting("mqttchat-settings-group","app_id",array($this,"mqttchat_app_id_validation"));
     register_setting("mqttchat-settings-group","uf");
     add_settings_section("mqttchat-settings-options","Settings",array($this,"mqttchat_settings_options"),"mqttchat-admin");
     add_settings_field("mqttchat-app-id","Your APP_ID",array($this,"mqttchat_app_id"),"mqttchat-admin","mqttchat-settings-options");
     add_settings_field("mqttchat-uf","Enable UF ?",array($this,"mqttchat_uf"),"mqttchat-admin","mqttchat-settings-options");
    }

    function mqttchat_settings_options(){
        $mqttchat_doc_link="https://doc.mqtt-chat.com/mqttchat-cloud-web/custom-plugins/wordpress";
        $mqttchat_uf_link="https://doc.mqtt-chat.com/mqttchat-cloud-web/integration#friends-feature";
        echo '<p>Please enter MQTT Chat <a href="'.$mqttchat_doc_link.'" target="_blank">APP_ID</a> 
              and <a href="'.$mqttchat_uf_link.'" target="_blank">UF</a> settings.</p>';
    }


    function mqttchat_app_id(){
     $app_id=esc_attr(get_option("app_id"));
     echo '<input type="text" name="app_id" value="'.$app_id.'" placeholder="mqttchat-xxxxxxxx"/>';
    }

    function mqttchat_uf(){
        $uf=esc_attr(get_option("uf"));
        echo '<input type="checkbox" name="uf" '.($uf?"checked":"").'>';
    }
    
    function mqttchat_app_id_validation($input){
        if(!preg_match("/^mqttchat-[0-9]{8}$/i",$input)){
            add_settings_error(
                'app_id',
                esc_attr( 'app_id' ), //becomes part of id attribute of error message
                __( 'Invalid MQTT CHAT APP_ID format!', 'wordpress' ), //default text zone
                'error'
            );
            $input = get_option('app_id' ); //keep old value
        }
        return $input;
    }


    function include_mqttchat_script(){
        $wp_locale=get_locale();
        $app_id="";
        $uf=0;
        if(get_option("app_id")){
            $app_id=get_option("app_id");            
        }  
        if(get_option("uf")){
            $uf=get_option("uf")?1:0;
        }
        
        wp_register_script(
            'mqttchat-script', // name your script so that you can attach other scripts and de-register, etc.
            'https://cluster1.telifoun.com/webapi/'.substr(get_locale(),0,2).'/mqttchat.js?appid='.$app_id.'&uf='.$uf, // this is the location of your script file
            array('jquery') ,// this array lists the scripts upon which your script depends
            '',
            true 
         );
         
        wp_enqueue_script('mqttchat-script');         
        
    }



    function shortcode($atts = [], $content = null,$tag=''){
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $mqttchat_atts = shortcode_atts(
            array(
                'class' => 'mqttchat-default',
                'layout' => 'docked',
                'width'=>'0',
                'height'=>'0'
            ), $atts, $tag
        );   
        $content='<div id="mqttchat" 
                       data-layout = "'.esc_html__($mqttchat_atts["layout"]).'"
                       data-user-id = '.$this->_userId.'
                       data-user-name="'.$this->_userName.'"
                       data-user-surname="'.$this->_userSurname.'"
                       data-user-avatar= "'.$this->_userAvatar.'"                      
                       data-width="'.(isset($mqttchat_atts["width"])?esc_html__($mqttchat_atts["width"]):"0").'"
                       data-height="'.(isset($mqttchat_atts["height"])?esc_html__($mqttchat_atts["height"]):"0").'"
                       class="'.(isset($mqttchat_atts["class"])?esc_html__($mqttchat_atts["class"]):"mqttchat-default").'"></div>'  ;  
        return $content;
    }
}



if(class_exists('mqttchat')){
  $mqttchatPlugin=new mqttchat();
  $mqttchatPlugin->register();



//plugin activation
register_activation_hook(__FILE__,array($mqttchatPlugin,"activate"));


//plugin desactivation
register_deactivation_hook(__FILE__,array($mqttchatPlugin,"desactivate"));


//plugin uninstall
register_deactivation_hook(__FILE__,array($mqttchatPlugin,"uninstall"));

}