<?php
global $wpdb;
?>
<div class="wrap">
<h2>Høringsgenerator</h2>
<?php
if($_GET['new']) {
    
}
?>
<form method="post" action="options.php">
<?php
$arguments = $wpdb->get_results("SELECT * FROM wp_horingsgenerator WHERE deleted =0");
foreach($arguments as $argument) {
    ?>
    <h3>Argument #<?php print $argument->argument_id; ?></h3>
    Tittel: <input name="brukargument<?php print $argument->argument_id ?>" type=textfield num="<?php print $argument->argument_id ?>" value="<?php print $argument->title; ?>" style="width:300px"/><br />
    <?php
}
?>
</form>
</div>
