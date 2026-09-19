      <div id="ace_menu">
        <?php foreach($this->pages  as $id=>$page): ?>
<a href="options-general.php?page=<?php echo $id; ?>"><?php echo $page['1']; ?></a>&nbsp;|
        <?php endforeach; ?>
      </div>  