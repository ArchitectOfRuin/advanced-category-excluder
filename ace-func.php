<?php
function ace_getarchives_where($where,$r="")
{
  return ace_where($where,'is_archive');
}

function ace_getarchives_join($join,$r="")
{
//	return ace_join($where,'is_archive');
}

function ace_list_bookmarks($bookmarks)
{
	$filter = ace_get_section();
  
  $links_to_exclude = explode(",",get_option("ace_link_sections_".$filter));
	$linkcategories_to_exclude = explode(",",get_option("ace_linkcategory_sections_".$filter));
		
	//print_r($linkcategories_to_exclude);
	//print_r($bookmarks);
	
	$c = count($bookmarks);
	for($i=0; $i<$c; $i++)
	{
		if (in_array($bookmarks[$i]->link_id,$links_to_exclude) || in_array($bookmarks[$i]->term_id,$linkcategories_to_exclude))
		{
			unset($bookmarks[$i]);
		}
	}
	
	
	//print_r($bookmarks);
	//print_r($links_to_exclude);
	
	return $bookmarks;
}

function ace_list_pages_excludes($excludes)
{
    global $wp_query;

    if($wp_query->is_page_widget) return $excludes;
    
    $filter = ace_get_section();
    $posts_to_exclude = get_option("ace_page_sections_".$filter);
    
    return explode(",",$posts_to_exclude);
}

function ace_where($where,$filter="") 
{
    global $wpdb, $wp_query;

    if((!empty($wp_query->is_category) || !empty($wp_query->is_tag) || get_option("ace_settings_onlyinwidget"))) return $where;
    /* If we are in a category archive, tag archive, or only in widgets don't apply filters */
   
    if (empty($filter))
    {
    	$filter = ace_get_section();
    }    
    
    $cats_to_exclude = get_option("ace_categories_".$filter);
	    
    if ( !empty($filter) && !empty($cats_to_exclude) && strlen($cats_to_exclude) > 0)
    {
      $where .= " AND NOT EXISTS (";
      $where .= "SELECT * FROM ".$wpdb->term_relationships." JOIN ".$wpdb->term_taxonomy." ON ".$wpdb->term_taxonomy.".term_taxonomy_id = ".$wpdb->term_relationships.".term_taxonomy_id ";
      $where .= "WHERE ".$wpdb->term_relationships.".object_id = ".$wpdb->posts.".ID AND ".$wpdb->term_taxonomy.".taxonomy = 'category' AND ".$wpdb->term_taxonomy.".term_id IN (" . $cats_to_exclude . ") )";
    }
	
    return $where;   
}

function ace_join($join,$filter="") 
{
    global $wpdb, $wp_query;

    if(!empty($wp_query->is_category) || !empty($wp_query->is_tag) || get_option("ace_settings_onlyinwidget")) return $join;
    /* If we are in a category archive, tag archive, or only in widgets don't apply filters */

	if (empty($filter))
	{	
		$filter = ace_get_section();
	}

	$cats_to_exclude = get_option("ace_categories_".$filter);	
	
    if ( !empty($filter) && strlen($cats_to_exclude) > 0) 
    {
        if (!preg_match("/$wpdb->term_relationships/i",$join)) $join .=" LEFT JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) ";
        if (!preg_match("/$wpdb->term_taxonomy/i",$join)) $join .=" LEFT JOIN $wpdb->term_taxonomy ON $wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id";
    }
    
    return $join;
}

function ace_distinct($distinct) 
{
   global  $wp_query;
   
   // this won't hurt by default.
   
   return "distinct";
}

function ace_list_categories($args)
/* 
 * Manipulating category list 
 */
{
  global $wp_query;

  if (get_option("ace_settings_hide") == 1)
  /* Only if the user wants this function */
  {
  	$filter = ace_get_section();
  	
  	$cats = get_option("ace_categories_".$filter);
      
    if (!empty($cats) && count($cats) > 0)
    /* if there is any category to hide :) */
    {
      $args = str_replace('</h2><ul>','</h2><ul>'.chr(10),$args);
      /* Insert a line break after the heading. */
    
      $rows = explode("\n",$args);    
    
      $p = "";
      
      for ($i=0; $i <= count($cats); $i++)
      /* 
        Yes, we are now creating a regular expression for ereg.
      */
      {
        if ($cats[$i] != "")
        {
          $catData = get_category($cats[$i]);
          /**
           * Here we get the name of the category, because that's the only thing we can exclude by
           *  in early 2.x versions of WordPress           
           */
          $p .= $catData->cat_name;
          if ($i+1 < count($cats)) $p .= "|";
          /**
           * If we'll get more object to exclude we add a PREG pattern OR, which is a pipe '|'
           */                     
        }
        $pattern = "(".$p.")";
      }

      if (!empty($pattern))
      {
        for($j = 0; $j <= count($rows); $j++ )
        {
          if(preg_match("/\b".$pattern."\b/i",$rows[$j]))
          /* We have the <li> starting tag on the first line, and the ending
          on de second line, so we kill'em all :) */ 
          {
              unset($rows[$j]);
              unset($rows[$j+1]);
          }
        }
        $args = implode("\n",$rows);
      }
    }
  }
  
  return($args);
}

/**
 * This function exports your settings to XML Sitemap Generator plugin
 */ 
function ace_xsg_update($categories="",$section="")
{
  $active = get_option("active_plugins");
  if (!in_array("google-sitemap-generator/sitemap.php",$active)) return;
          
  if (empty($section))
  {
    $section = get_option("ace_settings_xsg_category");  
  }

  if (empty($categories) && !empty($section))
  {
    $categories = get_option("ace_categories_".$section);
  }
  
  if (!is_array($categories))
  {
    $categories = explode(",",$categories);
  }

  $sm_options = get_option("sm_options");
  $sm_options["sm_b_exclude_cats"]=$categories;
  update_option("sm_options",$sm_options);
}

function ace_head() 
{
  global $ace_targets, $wp_query;
  $modifyheader = false;

  /**
   * Only if we are a single post request
   */     
  if ($wp_query->is_single)
  {
    $cats = split(',',get_option("ace_categories_norobots"));
      
    foreach ($cats as $cat)
    {
      /**
       * If the post is in one category that has been selected for exclusion
       * we'll hide it from robots.       
       */             
      if(in_category($cat)) $modifyheader = true;
    }
    
    if($modifyheader)
    {
      echo '<meta name="robots" content="noindex, nofollow">'."\n";
      echo '<!-- A.C.E. by DjZoNe -->';
      return true;
    }
    return false;
  }
  return false;
}

function ace_get_section()
{
	global $wp_query, $ace_targets;
	
	if (is_array($ace_targets))
	{
		foreach ($ace_targets as $key=>$val) 
		{
		  if (!empty($wp_query->$key) && $wp_query->$key == 1) $filter = $key;             	
		}
	}
	return $filter;
}

/**
 * Since 1.5
 */
function ace_items_to_exclude($where='',$method='')
{
  if(empty($method)) $method = get_option('ace_settings_exclude_method');

  switch($method)
  {
    case "smart":  
      $cats_to_exclude = get_option("ace_categories_is_home");  
      if ($wp_query->is_single)
      {
        $cats = split(',',$cats_to_exclude);
        /**
         * If this is a single post, and the 
         */
        $c = count($cats);
        for($i=0;$i<$c;$i++)
        {
          /**
           * If the post is in one category that has been selected for exclusion 
           */             
          if(in_category($cats[$i])) 
          {
            unset($cats[$i]);
          }
        }
        $cats_to_exclude = join(",",(array) $cats);
      }
      elseif ($wp_query->is_category)
      {
        $cats = split(',',$cats_to_exclude);
        
        $c = count($cats);
        for($i=0;$i<$c;$i++)
        {
        /**
         * If this category is beeing listed 
         */          
          if($cats[$i] == $wp_query->query_vars['cat']) 
          {
            unset($cats[$i]);
          }
        }
        $cats_to_exclude = join(",",(array) $cats);
        unset($cats);
      }
      else
      {
        /**
         * The same as in normal mode. Keep in sync
         */                     
      	foreach ($ace_targets as $key=>$val) 
      	{
      	   if ($wp_query->$key == 1) $filter = $key;    	
      	}
      	
      	/**
      	 * If this is empty is_home exclusion is in affect
      	 */                   	
      	if (!empty($filter) && $filter != "")
        { 
          $cats_to_exclude = get_option("ace_categories_".$filter);
        }
      }
    break;
    
    case "front":
      $cats_to_exclude = get_option("ace_categories_is_home");      
    break; 
  
    case "none":
      $cats_to_exclude = "";
    break;
    
    default:
    case "normal":
    	foreach ($ace_targets as $key=>$val) 
    	{
    	   if ($wp_query->$key == 1) $filter = $key;    	
    	} 
      $cats_to_exclude = get_option("ace_categories_".$filter);
            
    break;
  }
}

/**
 * Since 1.5
 */ 
function ace_categories_dropdown_args($args)
{
  $filter = ace_get_section();  	
  $cats_to_exclude = get_option("ace_catwidget_".$filter);
   
  $args['exclude']=$cats_to_exclude;
  
  return $args;
}

/**
 * We only hook to this to remove, the value in wp_query
 * we set at ace_pages_args  
 */ 
function ace_list_pages($args)
{
  global $wp_query;

  unset($wp_query->is_page_widget);
  
  return $args;
}

function ace_pages_args($args)
{
  global $wp_query;
  //
  // work around, to skip double excluding
  //  
  $wp_query->is_page_widget=1;
  
  $filter = ace_get_section();  	
  $cats_to_exclude = get_option("ace_pagewidget_".$filter);
   
  $args['exclude']=$cats_to_exclude;
    
  return $args;
}

function ace_get_terms($terms, $taxonomies="", $args="")
{
  global $wp_query;  

	if (empty($terms) || empty($terms[0])) return $terms;

	$taxonomy = $terms[0]->taxonomy;
	
	$filter = ace_get_section();
	
	switch($taxonomy)
	{		
		case "link_category":
			$items = get_option("ace_linkcategory_sections_".$filter);
		break;
		
		case "category":
      $items = get_option("ace_categories_".$filter);		  
		break;
	}
	
	if (get_option("ace_settings_hide") == 1 && !empty($items))
	/* Only if the user wants this function */
	{
		$items = explode(",",$items);
		$c = count($items); 
	   
	    if ($c > 0)
	    /* if there is any category to hide :) */
	    {
			for ($i=0; $i <= $c; $i++)
			{			
				if (in_array($terms[$i]->term_id,$items))
				{
					unset($terms[$i]);					
				}
			}
		}
	}
	return $terms;
}
?>