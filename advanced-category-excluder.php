<?php
/*
Plugin Name: Advanced Category Excluder
Version: 1.5
Plugin URI: http://ace.dev.rain.hu
Description: This plugin helps you to manage your content, RSS feeds, sidebar widgets, and fine tune where you want to display your posts, pages, links, link categories, or hide.
Author: DjZoNe
Author URI: http://djz.hu/
*/

global $ace_targets, $ace_settings, $ace_version;

$ace_version = '1.5';

add_filter('posts_join', 'ace_join');
add_filter('posts_where', 'ace_where');
add_filter('posts_distinct', 'ace_distinct');

add_filter('get_terms','ace_get_terms');

//add_filter('wp_list_categories','ace_list_categories'); // deprecated

add_filter('getarchives_where','ace_getarchives_where');
//add_filter('getarchives_join','ace_getarchives_join');

add_filter('wp_list_pages_excludes','ace_list_pages_excludes');
add_filter('wp_list_bookmarks','ace_list_bookmarks');

add_action('wp_head', 'ace_head');

// new
add_action('widget_categories_dropdown_args', 'ace_categories_dropdown_args');
add_action('widget_categories_args', 'ace_categories_dropdown_args');

add_filter('widget_recent_posts','ace_widget_recent_posts');

add_action('widget_pages_args', 'ace_pages_args');
add_filter('wp_list_pages','ace_list_pages');
add_action('widget_links_args', 'ace_links_args');

require_once("ace-ui.php");
require_once("ace-func.php");

$aceui = new AceUI();
	
//add_action("admin_menu", );    

add_action('admin_menu', array($aceui, 'AdminMenu'));
add_action('admin_head', array($aceui, 'AdminHead'));
add_action('init',array($aceui, 'Init'));
add_action('admin_init',array($aceui, 'AdminInit'));

add_action('activate_advanced-category-excluder/advanced-category-excluder.php', array($aceui, 'install'));
add_action('deactivate_advanced-category-excluder/advanced-category-excluder.php', array($aceui, 'deinstall'));		

?>