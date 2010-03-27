<?php
/*
Plugin Name: Høringsgenerator
Plugin URI: http://svenni.dragly.com/v7/horingsgenerator/
Description: En generator for høringer.
Version: 0.1
Author: Svenn-Arne Dragly
Author URI: http://dragly.org
*/
/*  Copyright 2010 Svenn-Arne Dragly  (email : hnnashikama@gmail.com, s@dragly.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3 or later, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
class HoringsGenerator {
    function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . "horingsgenerator";
        $this->pagename = 'horingsgenerator-menu';
    }
    function menu () {
        add_options_page('Høringsgenerator', 'Høringsgenerator', 'administrator', $this->pagename, array($this, 'options'));
    }
    function mime() {
        // Override wordpress headers which might have been set by WP itself or other plugins.
        if(isset($_POST['horingsgeneratorpdf'])) {
            header('Content-type: application/pdf');
        }
    }
    function my_init_method() {
        global $wpdb;
        if(isset($_POST['horingsgeneratorpdf'])) {
            require('fpdf/fpdf.php');
            $pdf=new FPDF();
            $pdf->AddPage("Portrait","A4");
            $pdf->SetMargins(25.0,25.0);
            $pdf->SetXY(25.0,25.0);
            $pdf->SetFont('Times','',14);
            if(isset($_POST['pretext'])) {
                $pdf->Write(5,iconv('UTF-8', 'windows-1252',$_POST['pretext']));
                $pdf->Write(5,"\n\n");
            }
            foreach($_POST as $key=>$val) {
                $keypart = explode("-",$key);
                if(isset($keypart[1]) && $keypart[0] == "useargument") {
                    $text = $wpdb->get_var("SELECT text FROM " . $this->prefix . " WHERE argument_id = " . $wpdb->escape($keypart[1]));
                    $pdf->Write(5,iconv('UTF-8', 'windows-1252',$text));
                    $pdf->Write(5,"\n\n");
                }
            }
            if(isset($_POST['posttext'])) {
                $pdf->Write(5,iconv('UTF-8', 'windows-1252',$_POST['posttext']));
            }
            //$pdf->Output("horingssvar.pdf","I");
            $pdf->Output("horingssvar.pdf","D"); // forced download
            exit(); // don't let anything else output
        } else {
            wp_enqueue_script("horingsgenerator", WP_PLUGIN_URL . "/horingsgenerator/horing.js", array("jquery"));
        }
    }
    function get_horingsgenerator($attr) {
        global $wpdb;
        $arguments = $wpdb->get_results("SELECT * FROM wp_horingsgenerator WHERE deleted =0 ORDER BY ordering");
        $return = '';
        $return .= '
        <form action="" method="post" enctype="multipart/form-data" style="text-align:left;">
        <p>
        ';
        foreach($arguments as $argument) {
            $return .= '<input class="useargument" name="useargument-' . $argument->argument_id . '" id="useargument-' . $argument->argument_id . '" type=checkbox num="' . $argument->argument_id . '" /> <label for="useargument-' . $argument->argument_id . '">' . $argument->title .'</label><br />';
        }
        $return .= '</p>';
        $return .= "<h3>Ditt svar</h3>";
        foreach($arguments as $argument) {
            $return .= '<p id="argument'. $argument->argument_id .'">' . nl2br($argument->text) . '</p>';
        }
        $return .= '
        <input type="hidden" name="horingsgeneratorpdf" />
        <p><input type="submit" value="Last ned som PDF" /></p>
        <h3>Legg til tekst</h3>
        <p>Dersom du ønsker, kan du legge til egendefinert tekst før og etter teksten som vises i PDF-filen.</p>
        <p>Tekst før:</p>
        <textarea name="pretext" style="width: 80%; height:100px;"></textarea>
        <p>Tekst etter:</p>
        <textarea name="posttext" style="width: 80%;height:100px;"></textarea>
        <p>PS: Er du ikke fornøyd er det bare å gjøre endringer ovenfor og laste ned filen på nytt.</p>
        <p><input type="submit" value="Last ned som PDF" /></p>
        </form>';
        return $return;
    }
    function refreshOrdering() {
        global $wpdb;
        $arguments = $wpdb->get_results("SELECT title, argument_id, text, ordering FROM " . $this->prefix ." WHERE deleted =0 ORDER BY ordering");
        $i = 1;
        foreach($arguments as $argument) {
            $wpdb->query("UPDATE " . $this->prefix . " SET ordering = ". $i ." WHERE argument_id = " . $argument->argument_id);
            $i++;
        }
    }
    function options() {
        global $wpdb;
        ?>
        <div class="wrap">
        <h2>Høringsgenerator</h2>
        <form method="post" action="<?php print $_SERVER['PHP_SELF'] . "?page=" . $this->pagename; ?>">
        <p class="submit">
        <input type="submit" class="button-primary" name="update" value="<?php _e('Save Changes') ?>" />
        <input type="submit" class="button-primary" name="new" value="<?php _e('New Argument') ?>" />
        </p>
        <?php
        if(isset($_POST['update']) || isset($_POST['new'])) {
            foreach($_POST as $key=>$val) {
                $key = $wpdb->escape($key);
                $val = $wpdb->escape($val);
                $keysplit = explode("-", $key);
                if(isset($keysplit[1]) && $keysplit[0] == "title") {
                   $wpdb->query("UPDATE " . $this->prefix . " SET title = '" . $val . "' WHERE argument_id = " . $keysplit[1]);
                }
                if(isset($keysplit[1]) && $keysplit[0] == "text") {
                   $wpdb->query("UPDATE " . $this->prefix . " SET text = '" . $val . "' WHERE argument_id = " . $keysplit[1]);
                }
            }
            $this->refreshOrdering();
        }
        if(isset($_POST['new'])) {
             $maxOrder = $wpdb->get_var("SELECT MAX(ordering) FROM " . $this->prefix . " WHERE deleted = 0");
             $wpdb->query("INSERT INTO " . $this->prefix . " (ordering) VALUES (" . ($maxOrder + 1) . ")");
            $this->refreshOrdering();
        }
        if(isset($_GET['delete'])) {
            $wpdb->query("UPDATE " . $this->prefix . " SET deleted = 1 WHERE argument_id = " . $wpdb->escape($_GET['delete']));
            $this->refreshOrdering();
        }
        if(isset($_GET['moveup'])) {
            $currentOrder = $wpdb->get_var("SELECT ordering FROM " . $this->prefix . " WHERE argument_id = " . $wpdb->escape($_GET['moveup']));
            $above = $wpdb->get_row("SELECT argument_id, ordering FROM " . $this->prefix . " WHERE ordering = (SELECT MAX(ordering) FROM " . $this->prefix . " WHERE ordering < " . $currentOrder . ")");
            $wpdb->query("UPDATE " . $this->prefix . " SET ordering = " . $above->ordering . " WHERE argument_id = " . $wpdb->escape($_GET['moveup']));
            $wpdb->query("UPDATE " . $this->prefix . " SET ordering = " . $currentOrder . " WHERE argument_id = " . $above->argument_id);   
            //$this->refreshOrdering();
        }
        if(isset($_GET['movedown'])) {
            $currentOrder = $wpdb->get_var("SELECT ordering FROM " . $this->prefix . " WHERE argument_id = " . $wpdb->escape($_GET['movedown']));
            $above = $wpdb->get_row("SELECT argument_id, ordering FROM " . $this->prefix . " WHERE ordering = (SELECT MIN(ordering) FROM " . $this->prefix . " WHERE ordering > " . $currentOrder . ")");
            $wpdb->query("UPDATE " . $this->prefix . " SET ordering = " . $above->ordering . " WHERE argument_id = " . $wpdb->escape($_GET['movedown']));
            $wpdb->query("UPDATE " . $this->prefix . " SET ordering = " . $currentOrder . " WHERE argument_id = " . $above->argument_id);   
            //$this->refreshOrdering();
        }
        ?>
        <?php wp_nonce_field('update-options'); ?>
        <?php
        $arguments = $wpdb->get_results("SELECT title, argument_id, text, ordering FROM " . $this->prefix ." WHERE deleted =0 ORDER BY ordering");
        foreach($arguments as $argument) { 
            ?>
            <h3><?php _e("Argument") ?> #<?php print $argument->ordering; ?></h3>
            <p><?php _e("Title") ?>: <input name="title-<?php print $argument->argument_id ?>" type=textfield num="<?php print $argument->argument_id ?>" value="<?php print $argument->title; ?>" style="width:300px"/></p>
            
            <textarea name="text-<?php print $argument->argument_id ?>" style="width: 500px; height: 300px;"><?php print $argument->text ?></textarea>
            <p>
                <a href="<?php print $_SERVER['PHP_SELF'] . "?page=" . $this->pagename . "&moveup=" . $argument->argument_id ?>"><?php _e("Move up"); ?></a> | 
                <a href="<?php print $_SERVER['PHP_SELF'] . "?page=" . $this->pagename . "&movedown=" . $argument->argument_id ?>"><?php _e("Move down"); ?></a> | 
                <a href="<?php print $_SERVER['PHP_SELF'] . "?page=" . $this->pagename . "&delete=" . $argument->argument_id ?>"><?php _e("Delete argument"); ?></a>
            </p>
            <?php
        }
        ?>
        <p class="submit">
        <input type="submit" class="button-primary" name="update" value="<?php _e('Save Changes') ?>" />
        <input type="submit" class="button-primary" name="new" value="<?php _e('New Argument') ?>" />
        </p>
        <input type="hidden" name="action" value="update" />
        </form>
        </div>
        <?php
    }
    function install() {
        $sql = "CREATE TABLE IF NOT EXISTS `wp_horingsgenerator` (
          `argument_id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `text` text NOT NULL,
          `deleted` int(1) NOT NULL DEFAULT '0',
          `ordering` int(11) NOT NULL,
          PRIMARY KEY (`argument_id`)
        )";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
$horingsgenerator = new HoringsGenerator();

add_action('admin_menu', array($horingsgenerator, 'menu'));

add_action('init', array($horingsgenerator, 'my_init_method'));
add_action('send_headers', array($horingsgenerator, 'mime'));
add_shortcode("horingsgenerator",array($horingsgenerator, "get_horingsgenerator"));
register_activation_hook(__FILE__,array($horingsgenerator,'install'));
?>
