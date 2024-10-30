<?php
/*
Plugin Name: Comment Recovery
Plugin URI: http://wordpress.designpraxis.at
Description: Recovers comments from "New comment" emails
Version: 1.1
Author: Roland Rust
Author URI: http://wordpress.designpraxis.at

*/

add_action('init', 'dprx_comment_rec_init_locale');
function dprx_comment_rec_init_locale() {
	$locale = get_locale();
	$mofile = dirname(__FILE__) . "/locale/".$locale.".mo";
	load_textdomain('dprx_comment_rec', $mofile);
}

add_action('admin_menu', 'dprx_comment_rec_add_admin_pages');

function dprx_comment_rec_add_admin_pages() {
	add_submenu_page('edit-comments.php', 'Comment Recovery', 'Comment Recovery', 10, __FILE__, 'dprx_comment_rec_options_page');
}

function dprx_comment_rec_options_page() {
	global $wpdb;
	
	// Saving the comment
	if (!empty($_POST['dprx_comment_rec_save'])) {
		?>
			<div id="message" class="updated fade">
		<?php
			$sql = "SELECT * FROM ".$wpdb->comments." 
				WHERE comment_author = '".$_POST['dprx_comment_rec_author']."'
				AND comment_author_email = '".$_POST['dprx_comment_rec_aemail']."'
				AND comment_author_url = '".$_POST['dprx_comment_rec_aurl']."'
				AND comment_author_IP = '".$_POST['dprx_comment_rec_aip']."'
				AND comment_content LIKE '".substr($_POST['dprx_comment_rec_comment'], 0, 10)."%'";
				echo $sql;
		if (empty($_POST['dprx_comment_rec_comment']) || empty($_POST['dprx_comment_rec_author'])) {
			?>
			<p><?php _e('No comment author or content.') ?></p>
			<?php
		} else {
			$sql = "SELECT * FROM ".$wpdb->comments." 
				WHERE comment_author = '".$_POST['dprx_comment_rec_author']."'
				AND comment_author_email = '".$_POST['dprx_comment_rec_aemail']."'
				AND comment_author_url = '".$_POST['dprx_comment_rec_aurl']."'
				AND comment_author_IP = '".$_POST['dprx_comment_rec_aip']."'
				AND comment_content LIKE '".substr($_POST['dprx_comment_rec_comment'], 0, 10)."%'";
			$gotcomment = @$wpdb->get_results($sql);
			if (is_array($gotcomment) && (count($gotcomment) > 0)) {
				?>
				<p><?php _e('Comment allready exists!') ?></p>
				<?php
			} else {
			$sql = "INSERT INTO ".$wpdb->comments." (
				comment_ID, 
				comment_post_ID, 
				comment_author, 
				comment_author_email, 
				comment_author_url, 
				comment_author_IP, 
				comment_date, 
				comment_date_gmt, 
				comment_content, 
				comment_karma, 
				comment_approved, 
				comment_agent, 
				comment_type, 
				comment_parent, 
				user_id) 
				VALUES (
				'', 
				'".$_POST['dprx_comment_rec_postid']."', 
				'".$_POST['dprx_comment_rec_author']."', 
				'".$_POST['dprx_comment_rec_aemail']."', 
				'".$_POST['dprx_comment_rec_aurl']."', 
				'".$_POST['dprx_comment_rec_aip']."', 
				'".$_POST['dprx_comment_rec_date']."', 
				'".$_POST['dprx_comment_rec_date']."', 
				'".$_POST['dprx_comment_rec_comment']."', 
				0, 
				'1', 
				'Mozilla/5.0', 
				'', 
				0, 
				0)";
				$saved = @$wpdb->query($sql);
				if (!empty($wpdb->insert_id)) {
				?>
				<p><?php _e('Comment saved.') ?></p>
				<?php
				} else {
				?>
				<p><?php _e('Could not save Comment.') ?></p>
				<?php
				}
			}
		}
		?>
		</div>
		<?php
	}
	
	// Parsing the comment
	if (!empty($_POST['dprx_comment_rec'])) {
		$lines = explode("\n",$_POST['dprx_comment_rec']);
		foreach ($lines as $l) {
			// get the post
			if (eregi("#comments",$l)) {
				$postid = explode("#",$l);
				$guid = $postid[0];
				$sql = "SELECT * FROM ".$wpdb->posts." WHERE guid = '".$guid."'";
				$postid = @$wpdb->get_results($sql);
				foreach($postid as $p) {
					$postid = $p->ID; continue;
				}
				$post_rec = get_post($postid, ARRAY_A);
			}
			// get the Date
			if (eregi("Date:",$l)) {
				$date = explode(": ",$l);
				$date = $date[1];
				$date = strtotime($date);
				$date = date("Y-m-d H:i:s",$date);
			}
			// get the Author
			if (eregi("Author : ",$l)) {
				$author = explode(" : ",$l);
				$authorline = explode("(",$author[1]);
				$author = $authorline[0];
				// get the Author IP
				$ip =  explode(" , ",$authorline[1]);
				$ip =  explode(": ",$ip[0]);
				$ip = $ip[1];
			}
			// get the Author Email
			if (eregi("E-mail : ",$l)) {
				$email = explode(" : ",$l);
				$email = $email[1];
			}
			// get the Author Url
			if (eregi("URL    : ",$l)) {
				$url = explode(" : ",$l);
				$url = $url[1];
			}
			// get the Comment
			if (eregi("You can see all comments on this post here:",$l)) {
				$commentstart = 0;
			}
			if ($commentstart == 1) {
				$comment .= $l;
			}
			if (eregi("Comment:",$l)) {
				$commentstart = 1;
				$comment = "";
			}
		}
			$comment = trim($comment);
	}
	?>
	<div class=wrap>
		<h2><?php _e('Comment Recovery') ?></h2>
	<?php
	if (!empty($_POST['dprx_comment_rec'])) {
		?>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<fieldset name="dprx_comment_rec_paste"  class="options">
			<legend><?php _e('Parsing Resuts:', 'dprx_comment_rec') ?></legend>
				<p>
				<?php _e('Post/Page:', 'dprx_comment_rec') ?><input type="text" name="dprx_comment_rec_postid" id="dprx_comment_rec_postid" value="<?php echo $postid; ?>" /> <a href="<?php echo $guid; ?>"><?php echo $post_rec['post_title']; ?></a>
				</p>
				<p>
				<?php _e('Date:', 'dprx_comment_rec') ?><input type="text" name="dprx_comment_rec_date" id="dprx_comment_rec_date" value="<?php echo $date; ?>" />
				</p>
				<p>
				<?php _e('Author:', 'dprx_comment_rec') ?><input type="text" name="dprx_comment_rec_author" id="dprx_comment_rec_author" value="<?php echo $author; ?>" />
				</p>
				<p>
				<?php _e('Author IP:', 'dprx_comment_rec') ?><input type="text" name="dprx_comment_rec_aip" id="dprx_comment_rec_aip" value="<?php echo $ip; ?>" />
				</p>
				<p>
				<?php _e('Author Email:', 'dprx_comment_rec') ?><input type="text" name="dprx_comment_rec_aemail" id="dprx_comment_rec_aemail" value="<?php echo $email; ?>" />
				</p>
				<p>
				<?php _e('Author URL:', 'dprx_comment_rec') ?><input type="text" name="dprx_comment_rec_aurl" id="dprx_comment_rec_aurl" value="<?php echo $url; ?>" />
				</p>
				<label for="dprx_comment_rec_comment">
					 <textarea style="width:50%; height: 150px;" id="dprx_comment_rec_comment" name="dprx_comment_rec_comment"><?php echo $comment; ?></textarea>
				</label>
				<p class="submit">
				<input type="submit" id="dprx_comment_rec_save" name="dprx_comment_rec_save" Value="<?php _e('Save this Comment','dprx_comment_rec'); ?>" />	
				</p>
		</fieldset>
		</form>
		<?php
	}
	?>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<fieldset name="dprx_comment_rec_paste"  class="options">
		<legend><?php _e('Paste your "New comment" email Source here', 'dprx_comment_rec') ?></legend>
		<p>
			<?php _e('Do not forget to include the mail headers, otherways you wont have a date on that comment.', 'dprx_comment_rec') ?>
		</p>
			<label for="dprx_comment_rec">
				<textarea style="width:100%; height: 150px;" id="dprx_comment_rec" name="dprx_comment_rec"></textarea>
			</label>
					<p class="submit">
					<input type="submit" id="parse" name="parse" Value="<?php _e('recover this comment','dprx_comment_rec'); ?>" />	
					</p>
	</fieldset>
	</form>
	</div>
	<div class="wrap">
		<p>
		<?php _e("Running into Troubles? Features to suggest?","bkpwp"); ?>
		<a href="http://wordpress.designpraxis.at/">
		<?php _e("Drop me a line","bkpwp"); ?> &raquo;
		</a>
		</p>
		<div style="display: block; height:30px;">
			<div style="float:left; font-size: 16px; padding:5px 5px 5px 0;">
			<?php _e("Do you like this Plugin?","bkpwp"); ?>
			<?php _e("Consider to","bkpwp"); ?>
			</div>
			<div style="float:left;">
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_xclick">
			<input type="hidden" name="business" value="rol@rm-r.at">
			<input type="hidden" name="no_shipping" value="0">
			<input type="hidden" name="no_note" value="1">
			<input type="hidden" name="currency_code" value="EUR">
			<input type="hidden" name="tax" value="0">
			<input type="hidden" name="lc" value="AT">
			<input type="hidden" name="bn" value="PP-DonationsBF">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" border="0" name="submit" alt="Please donate via PayPal!">
			<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
			</div>
		</div>
	</div>
	<?php
}
?>