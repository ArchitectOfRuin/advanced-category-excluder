<?php
  class AceUI
  {
    private $pages = array();
	   
    function AceUI()
    {
      $this->pages['ace_main'] = array( __('ACE', 'ace'), __('Basic Settings', 'ace'), 'manage_options', array($this, '_displayPage'));
      $this->pages['ace_home'] = array( __('ACE &lsaquo; Home', 'ace'), __('Home', 'ace'), 'manage_options', array($this, '_displayPage'));      	   
      $this->pages['ace_cats'] = array( __('ACE &lsaquo; Categories', 'ace'), __('Categories', 'ace'), 'manage_options', array($this, '_displayPage'));
      $this->pages['ace_pages'] = array( __('ACE &lsaquo; Pages', 'ace'), __('Pages', 'ace'), 'manage_options', array($this, '_displayPage'));      
      $this->pages['ace_links'] = array( __('ACE &lsaquo; Links', 'ace'), __('Links', 'ace'), 'manage_options', array($this, '_displayPage'));
      $this->pages['ace_link_cats'] = array( __('ACE &lsaquo; Link categories', 'ace'), __('Link categories', 'ace'), 'manage_options', array($this, '_displayPage'));
      $this->pages['ace_widget_cats'] = array( __('ACE &lsaquo; Widgets &lsaquo; Categories', 'ace'), __('Category Widget', 'ace'), 'manage_options', array($this, '_displayPage'));
      $this->pages['ace_widget_pages'] = array( __('ACE &lsaquo; Widgets &lsaquo; Categories', 'ace'), __('Page Widget', 'ace'), 'manage_options', array($this, '_displayPage'));
      $this->pages['ace_tags'] = array( __('ACE &lsaquo; Tags', 'ace'), __('Tags', 'ace'), 'manage_options', array($this, '_displayPage'));        
      $this->pages['ace_homepages'] = array( __('ACE &lsaquo; Homepages', 'ace'), __('Homepages', 'ace'), 'manage_options', array($this, '_displayPage'));                                                     
    }
    
    function AdminInit()
    {
      global $plugin_page, $title, $submenu_file;
      
      if (!empty($this->pages[$plugin_page]))
      {
        $title=$this->pages[$plugin_page][0];
        /**
         * This IS a F*CKIN' nasty hack... 
         * but this way ACE menu gets highlighted, even in sub pages                  
         */                 
        $submenu_file = 'ace_main';
      }
    }
	
    function AdminHead() 
    {
      global $ace_version, $plugin_page, $title;
          
      $_ace_version = get_option('ace_version');
      
     if ($_ace_version != $ace_version)
     {
        add_action('admin_notices',array($this,'AdminNotices'));
        update_option('ace_version',$ace_version);    
     }
     remove_filter('posts_join', 'ace_join');
     remove_filter('posts_where', 'ace_where');
     remove_filter('posts_distinct', 'ace_distinct');
     remove_filter('wp_list_bookmarks', 'ace_get_bookmarks');
     remove_filter('getarchives_where','ace_getarchives_where');
     remove_filter('get_terms','ace_get_terms');
    }

    function Install()
    {
      global $wpdb, $ace_targets, $ace_settings;
    
      foreach ($ace_targets as $key=>$v)
      {
        add_option("ace_categories_".$key,'','',true);
      }
      
      foreach ($ace_settings as $key=>$v)
      {
        switch($key)
        {
          case 'showempty':
            $val = 1;
          break;
          
          case 'exclude_method':
            $val = 'smart';
          break;
          
          default:
            $val = 0;
          break;
        }
        
        add_option("ace_settings_".$key,$val,'',true);
      }
    }

    function Uninstall()
    {
      global $ace_version;
      
      $_ace_version = get_option('ace_version');
      
      if (!$_ace_version)
      {
        add_option("ace_version",$ace_version);
      }
      elseif ($_ace_version != $ace_version)
      {
        update_option("ace_version",$ace_version);
      }
    }
    
    function Init()
    {
      global $ace_targets, $ace_settings, $ace_methods;
    
    	if (function_exists('load_plugin_textdomain')) 
      {
        load_plugin_textdomain('ace', false, dirname(plugin_basename(__FILE__)) . '/lang');
    	}
    	
    	$ace_targets = array('is_404'=>__('404 error','ace'),'is_attachment'=>__('Attachment','ace'),'is_archive'=>__('Archive','ace'),'norobots'=>__('Disable robots','ace'),'is_home'=>__('Home','ace'), 'is_page'=>__('Pages', ace),'is_feed'=>__('RSS Posts','ace'),'is_comment_feed'=>__('RSS Comments','ace'),'is_search'=>__('Search','ace'), 'is_single'=>__('Single Post', ace));
      
      $ace_settings = array( 
          'hide'=>__('Do you want the categories selected for <strong>Home</strong> section, to be hidden from <strong>category list</strong> as well?','ace'),      
          'showempty'=>__('Do you want the category lister, to list the empty categories?','ace'),
          'ec3'=>__('Do you want to display Event Calendar default category in the Categories tab?','ace'),
          'exclude_method'=>__('What <strong>exclusion method</strong> do you want to use in recent comments / recent posts <strong>widgets</strong>?','ace'),
          'xsg_category'=>__('Select a section to export excluded categories into <strong>XML Sitemap Generator</strong>:','ace')
      );
          
      $ace_methods = array(
        'smart' => __('This means, what widgets shows on the front shows everywhere on the site, exept when listing an excluded category, reading a post that is in an excluded category, or meet with another rule (archive, search). In that case the related comments/posts from that category will be shown as well. This method is introduced in ACE 1.3.','ace'),
        'front' => __('This means, that the widgets displays what they would on the front. No exeption.','ace'),
        'normal' => __('This means widget always using the actualy exclusion rules, depends on what part of the page you are browsing. It could be different on the front, in the search and in the archive','ace'),
        'none' => __('No exclusion in widgets','ace')
      );
    }
    
    function AdminNotices($msg)
    {
      global $ace_version;
      
      $url = 'http://ace.dev.rain.hu';   
      $msg = sprintf(__('It seems <strong>ACE plugin</strong> is just upgraded to the latest version %1$s. Please <strong>review the changes</strong> at our homepage <a href="%2$s" target="_blank">%2$s</a>','ace'),$ace_version,$url);
    
      echo "<div id='update-nag'>$msg</div>";  
    }    
    
/**
 * ACE Dashboard page functions
 **/
    function AddPage($page_title, $menu_title, $access_level, $file, $function = '', $parent = 'ace' )
    {
      global $_wp_submenu_nopriv, $_registered_pages;
      
      if ( !current_user_can( $access_level ) ) 
      {
      	$_wp_submenu_nopriv[$parent][$file] = true;
      	return false;
      }
      $hookname = 'settings_page_'.$file;
            
      if (!empty ( $function ) && !empty ( $hookname )) add_action( $hookname, $function );
      $_registered_pages[$hookname] = true; 
    }

    function AdminMenu()
    {
        if ( function_exists('add_menu_page') ) add_options_page(__('ACE', 'ace'), __('ACE', 'ace'), 'manage_options', 'ace_main', array($this,'_displayPage')); 
        
      	if (function_exists('add_submenu_page')) 
        {   
            foreach($this->pages as $name=>$page)
            {
              if ($name == 'main') continue;
              $this->AddPage($page[0],$page[1],$page[2],$name,$page[3]);
            }  			
        }
    }
    

    function _displayPage() 
    {
      global $plugin_page;  
      if (!empty($this->pages[$plugin_page])) require_once("pages/".$plugin_page.'.php');
    }
        
    
	}