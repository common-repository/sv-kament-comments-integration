<?php
function kamentcomments_validate_options($o) { return $o; }
add_action('admin_init', 'kamentcomments_init' );
function kamentcomments_init(){
	register_setting( 'kamentcomments_options', 'kament-comments', 'kamentcomments_validate_options' );
	$new_options = array(
		'use_counters' => '',
		'subdomain' => ''
	);

	// if old options exist, update to array
	$existing = get_option('kament-comments');
	foreach( $new_options as $key => $value ) {
		if(isset($existing[$key])) {
			$new_options[$key] = $existing[$key];
		}
	}
	add_option( 'kament-comments', $new_options );
}



add_action('admin_menu', 'show_kamentcomments_options');
function show_kamentcomments_options() {
	add_options_page(__('SV KAMENT Comments Options', 'kament-comments'), __('SV KAMENT Comments','kament-comments'), 'manage_options', 'kament-comments', 'kamentcomments_options');
}



// ADMIN PAGE
function kamentcomments_options() {
?>
    <link href="<?php echo plugins_url( 'admin.css' , __FILE__ ); ?>" rel="stylesheet" type="text/css">
    <div class="pea_admin_wrap">
        <div class="pea_admin_top">
            <h1><?php _e('SV KAMENT Comments plugin','kament-comments'); ?></h1>
        </div>

        <div class="pea_admin_main_wrap">
            <div class="pea_admin_main_left">

		<form method="post" action="options.php" id="options">
			<?php settings_fields('kamentcomments_options'); ?>
			<?php $options = get_option('kament-comments'); 
				if (!isset($options['use_counters'])) {$options['use_counters'] = "1";}
				if (!isset($options['subdomain'])) {$options['subdomain'] = "";}
			?>
<?php if ($options['subdomain']=="") { ?>
<div class="error">
			<h3 class="title"><?php _e('You Need to Set Up your SV KAMENT Community name (subdomain)','kament-comments');?></h3>
			<table class="form-table">
			<tr valign="top"><th scope="row"><a href="http://<?php echo KAMENT_COMMENTS_SERVER; ?>/create" style="text-decoration:none" target="_blank"><?php _e('Create new SV KAMENT community', 'kament-comments'); ?></a></th>
					<td><small><?php _e('Create new community if you havent done that yet', 'kament-comments'); ?></small><br><strong><?php _e('SV KAMENT community name', 'kament-comments'); ?>: </strong><input id="subdomain" type="text" name="kament-comments[subdomain]" value="<?php echo $options['subdomain']; ?>" /><br><br></td>
				</tr>
			</table>
</div>
<?php } else { ?>
		<h3 class="title"><?php _e('Plugin settings', 'kament-comments'); ?></h3>
			<table class="form-table">
				<tr valign="top"><th scope="row"><a href="http://<?php if ($options['subdomain'] != "") { echo $options['subdomain'] . '.' . KAMENT_COMMENTS_SERVER; } ?>/admin" style="text-decoration:none" target="_blank"><?php _e('Admin dashboard', 'kament-comments'); ?></a></th>
					<td><small><?php _e('Community settings & administration', 'kament-comments'); ?></small></td>
				</tr>
			</table>
<?php } ?>


			<h3 class="title"><?php _e('Main Settings', 'kament-comments'); ?></h3>
			<table class="form-table">
<?php if ($options['subdomain']!="") { ?>
					<tr valign="top"><th scope="row"><label for="appID"><?php _e('SV KAMENT community name(subdomain)', 'kament-comments'); ?></label></th>
					<td><input id="subdomain" type="text" name="kament-comments[subdomain]" value="<?php echo $options['subdomain']; ?>" /></td>
				</tr>
<?php } ?>

				<tr valign="top"><th scope="row"><label for="use_counters"><?php _e('Show comment counters', 'kament-comments'); ?></label></th>
					<td><input id="use_counters" name="kament-comments[use_counters]" type="checkbox" value="on" <?php checked('on', $options['use_counters']); ?> /> <small><?php _e('Show comment counters in post listings', 'kament-comments'); ?></small></td>
				</tr>

			</table>

			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>



</div>
<?php
}


add_action('admin_notices', 'kamentcomments_admin_notice');
function kamentcomments_admin_notice(){
	$options = get_option('kament-comments');
	if (!isset($options['subdomain']) || $options['subdomain']=="") {
		$adminurl = get_admin_url()."options-general.php?page=kament-comments";
		echo '<div class="error">' . 
			'<p>' . __('Please enter your SV KAMENT Community name for comments to work properly.', 'kament-comments') .
			' <a href="'.$adminurl.'"><input type="submit" value="' . __('Enter name', 'kament-comments') . '" class="button-secondary" /></a>' .
			'</p>' .
		'</div>';
	}
}

add_action( 'admin_action_svkament_export_wpcomments', 'svkament_export_wpcomments_admin_action' );
function svkament_export_wpcomments_admin_action()
{
	global $wpdb;
	$offset = 0;
	$chunk_size = 100;

	$data = array(
		#'post_id' => array(
		#	'post_url' => 'link here',
		#	'post_created' => 'post creation date',
		#	'post_title' => 'post_title',
		#	'kament_page_name' => 'internal kament page_name'
		#	'comments' => array(
		#
		#		ARRAY OF COMMENTS
		#
		#	)
		#),
	);

	do {
		$sql = 'SELECT comment_ID, comment_post_ID, comment_parent, comment_author, comment_author_email, comment_author_IP, comment_date, comment_content, post_date, post_title, guid ' . 
				"FROM  `$wpdb->comments` " .
				"INNER JOIN  `$wpdb->posts` p ON comment_post_ID = p.ID " . 
				'ORDER BY comment_ID ' .
				"LIMIT $offset , $chunk_size";

		$comments_raw = $wpdb->get_results($sql);


		if($comments_raw) {
			foreach ($comments_raw as $c){
				if( !isset($data[ $c->comment_post_ID ] ) ) {
					$data[ $c->comment_post_ID ] = array(
						'post_url' => $c->guid,
						'post_created' => $c->post_date,
						'post_title' => $c->post_title,
						'kament_page_name' => 'post_' . $c->comment_post_ID,
						'comments' => array(),
					);
				}

				$data[ $c->comment_post_ID ]['comments'][] = array(
					'internal_id' => $c->comment_ID,
					'parent_id' => $c->comment_parent,
					'author_name' => $c->comment_author,
					'author_email' => $c->comment_author_email,
					'ip_address' => $c->comment_author_IP,
					'created_date' => $c->comment_date,
					'message' => $c->comment_content,
				);
			}
		}

		$offset += $chunk_size;
	} while( $comments_raw );


	$svk_xml = '<?xml version="1.0" encoding="UTF-8" ?>'."\n".
				"<SVKImport>\n";

	foreach ($data as $page_data){
		$svk_xml .= "\t<page>\n";
			$svk_xml .= "\t\t<title><![CDATA[" . $page_data['post_title'] . "]]></title>\n";
			$svk_xml .= "\t\t<url><![CDATA[" . $page_data['post_url'] . "]]></url>\n";
			$svk_xml .= "\t\t<created_date>" . $page_data['post_created'] . "</created_date>\n";
			$svk_xml .= "\t\t<page_name><![CDATA[" . $page_data['kament_page_name'] . "]]></page_name>\n";
			foreach ($page_data['comments'] as $c){
				$svk_xml .= "\t\t<comment>\n";
					$svk_xml .= "\t\t\t<id>".$c['internal_id']."</id>\n";
					$svk_xml .= "\t\t\t<parent_id>".$c['parent_id']."</parent_id>\n";
					$svk_xml .= "\t\t\t<message><![CDATA[".$c['message']."]]></message>\n";
					$svk_xml .= "\t\t\t<date>".$c['created_date']."</date>\n";
					$svk_xml .= "\t\t\t<author_name><![CDATA[".$c['author_name']."]]></author_name>\n";
					$svk_xml .= "\t\t\t<author_email><![CDATA[".$c['author_email']."]]></author_email>\n";
					$svk_xml .= "\t\t\t<author_ip>".$c['ip_address']."</author_ip>\n";
				$svk_xml .= "\t\t</comment>\n";
			}
		$svk_xml .= "\t</page>\n";
	}

	$svk_xml .= "</SVKImport>";


	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename=wp-to-svkament.xml');
	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
	echo $svk_xml;
	exit();
}

add_action( 'admin_menu', 'svkament_export_wpcomments_admin_menu' );
function svkament_export_wpcomments_admin_menu()
{
    add_management_page( 'Export WP Comments', 'Export WP Comments', 'administrator', 'svkament_export_wpcomments', 'svkament_export_wpcomments_form' );
}


function svkament_export_wpcomments_form()
{
?>
	<p><?php echo __('Export old WP comments in SV Kament format', 'kament-comments'); ?> </p>
	<form method="POST" action="<?php echo admin_url( 'admin.php' ); ?>">
		<input type="hidden" name="action" value="svkament_export_wpcomments" />
		<input type="submit" value="<?php echo __('Export', 'kament-comments'); ?>" class="button-primary"/>
	</form>
<?php
}
