<?php
    global $wpdb, $ace_targets;
    
    $targets = $ace_targets;
    unset($targets['norobots']);
    unset($targets['is_comment_feed']);
    unset($targets['is_feed']);

    if ($_POST['submit'])
    // is this form submited?
    {
      if (!empty($_POST['pages']))
      {  
        if (is_array($targets))
  	    {
          $xsg_category = get_option("ace_settings_xsg_category");
  	    
          foreach ($targets as $key=>$val)
      	  {
        		$data = $_POST['pages_'.$key];
        		if (!empty($data))
        		// do we have selected pages?
        		{
        		    $c = count($data)-1; $s = "";
        		    foreach ($data as $k=>$v)
        		    {
                  $s.=trim($v); if ($c != $k) $s.=",";
                }
        		    update_option("ace_pagewidget_".$key,$s);
        		}
        		// no, so we clean up.
        		else update_option("ace_pagewidget_".$key,'');
        		
        		if($key == $xsg_category)
        		{
        		  if (empty($data)) $data = array();
              ace_xsg_update($data);
            }
            
        		unset($data);
      	  }
  	    }
	    }
    }
    
    if ($_POST['empty'])
    {
    	foreach ($targets as $key=>$val)
    	{
    	    update_option("ace_pagewidget_".$key,"");
    	}
    }

   $pages = get_pages();  
?>
<div class="wrap">
  <h2><?php _e('Advanced Category Excluder','ace'); ?>&nbsp;-&nbsp;<?php _e('Pages Widget','ace'); ?></h2>
  <?php require_once("_menu.php"); ?>
  <div class="metabox-holder" id="poststuff">
  <form method="post">
    <div id="ace_categories_usage" class="postbox">
      <h3 class="hndle">
        <span><?php _e('Usage','ace'); ?></span>
      </h3>
      <div class="inside">
      	<ul>
      	 <li>
         <?php echo sprintf(__("Here are the %s different targets you can set an exclusion on.<br />  Which pages would you like to hide from the <strong>Pages Widget</strong>?","ace"), count($targets)); ?><br /><br />
         <?php  _e('These options has nothing to do with the options you may already set under the <strong>Pages</strong> submenu. These options below only effect how the Pages Widget work, but NOT how the page listing works.',"ace"); ?>
         </li>      	 
      	</ul>
      </div>
    </div>
    <div class="postbox">
        <h3 class="hndle"><?php _e('Sections','ace'); ?></h3>
        <div class="inside">                      
     <?php     
      
      foreach ($targets as $key=>$val):
      $_pages = explode(",",get_option("ace_pagewidget_".$key));
     ?>
        <div style="width: <?php echo floor(100/(count($targets))) ?>%; float: left;">             
          <br />
          <strong><?php echo $val ?></strong><br />
          <br />                      
          <?php foreach ($pages as $page): ?>
    		    <label>
              <input type="checkbox" name="pages_<?php echo $key; ?>[]" value="<?php echo $page->ID; ?>" <?php if (in_array($page->ID,$_pages)) echo "checked"; ?> /><?php echo $page->post_title; ?>
            </label>
            <br class="clear"/>
  		    <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
        <br class="clear"/>
        <br />
      	<input type="hidden" name="pages" value="1">    	
        <input class="button-primary action" type="submit" name="submit" value="<?php _e('Doit!','ace'); ?>"/>
        <input class="button-secondary action" type="submit" name="empty" value="<?php _e('Reset','ace'); ?>">       
        </div>
      </div>
      </form>      
      <?php require_once("donate.php"); ?>  
  </div>
</div>    
<?php
    $wpdb->flush();
?>