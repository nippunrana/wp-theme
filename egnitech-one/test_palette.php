<?php
require_once '/var/www/dosiqai.com/html/wp-load.php';

$palette_theme = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
$palette_default = wp_get_global_settings( array( 'color', 'palette', 'default' ) );
$palette_custom = wp_get_global_settings( array( 'color', 'palette', 'custom' ) );

echo "Theme Colors:\n";
print_r($palette_theme);
echo "\nCustom Colors:\n";
print_r($palette_custom);
echo "\nDefault Colors:\n";
print_r($palette_default);

$full_color = wp_get_global_settings( array('color', 'palette') );
echo "\nFull Color Palette:\n";
print_r($full_color);
