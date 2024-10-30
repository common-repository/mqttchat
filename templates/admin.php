<h1>MqttChat Plugin</h1>
<form method="post" action="options.php">
  <?php settings_fields("mqttchat-settings-group") ;  ?>
  <?php do_settings_sections("mqttchat-admin") ;  ?>
  <?php submit_button();  ?>
</form>
