<?php
//if (!isset($options['subdomain'])) {$options['subdomain'] = "";}

$counter_added = 0; // if counter code was already added

function is_svkament_installed()
{
	$options = get_option('kament-comments'); 
	return isset($options['subdomain']) && !empty($options['subdomain']);
}

function svkament_download_from_url($url) {
	try {
		if(!function_exists('curl_version'))
			return NULL;

		$ch = @curl_init ( $url );
		if(!$ch)
			return NULL;

		@curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); //supress base_dir warnings if any
		@curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		@curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE);    
		@curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE); 

		$output = curl_exec ( $ch );
		$httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		if (curl_errno ( $ch ) || $httpcode != 200) {
			return NULL;
		} else {
			curl_close ( $ch );
		}

		if( strpos($output, 'kament-plaintext-comments')===false )
			return NULL;

		return $output;
	} catch (Exception $e) {
		return NULL;
	}
}

function svkament_get_plain_comments($page_name) {
	global $wpdb;

	$options = get_option('kament-comments'); 

	$plain = '';

	$table_name = $wpdb->prefix . "kament_plain";
	$row = $wpdb->get_row("SELECT data, UNIX_TIMESTAMP(timestamp) as timestamp from $table_name WHERE page_name=\"$page_name\"");
	if($row)
		$plain = $row->data;

	// if no data found or it has timedout - update from kament server
	if(!$row || $row->timestamp < (time() - 1800) ) {
		$new_data = svkament_download_from_url('http://' . $options['subdomain'] . '.' . KAMENT_COMMENTS_SERVER . "/commentswidget/plain/?page_name=$page_name");
		if($new_data != NULL) {
			$plain = $new_data;
			$sql_name = $wpdb->escape($page_name);
			$sql_data = $wpdb->escape($plain);
			$wpdb->query("INSERT INTO $table_name (page_name, timestamp, data) VALUES(\"$sql_name\", NOW(), \"$sql_data\") ON DUPLICATE KEY UPDATE timestamp=NOW(),data=\"$sql_data\"");
		}
	}

	return $plain;
}

function svkament_get_page_name($post)
{
    return 'post_'.$post->ID;
}

function svkament_comments_number($comment_text) {
    global $post;
	$options = get_option('kament-comments'); 

    if ( is_svkament_installed() && isset($options['use_counters']) ) {
        return '<span class="svkament-postid" rel="'.htmlspecialchars(svkament_get_page_name($post)).'"></span>';
    } else {
        return $comment_text;
    }
}

function svkament_comments_counter() {
	$options = get_option('kament-comments'); 

	if(is_svkament_installed() && isset($options['use_counters'])) { ?>

		<!-- SV KAMENT Counters -->
		<script type="text/javascript">

            var nodes = document.getElementsByTagName('span');
            for (var i = 0, url; i < nodes.length; i++) {
                if (nodes[i].className.indexOf('svkament-postid') != -1) {
                    nodes[i].parentNode.setAttribute('data-kament-name', nodes[i].getAttribute('rel'));
                    url = nodes[i].parentNode.href.split('#', 1);
                    if (url.length == 1) { url = url[0]; }
                    else { url = url[1]; }
                    nodes[i].parentNode.href = url + '#kament_comments';
                }
            }


			/* * * НАСТРОЙКА * * */
			var kament_subdomain = '<?php echo $options['subdomain']; ?>';
			/* * * НЕ МЕНЯЙТЕ НИЧЕГО НИЖЕ ЭТОЙ СТРОКИ * * */
			(function () {
				var node = document.createElement('script'); node.type = 'text/javascript'; node.async = true;
				node.src = 'http://' + kament_subdomain + '.<?php echo KAMENT_COMMENTS_SERVER; ?>/js/counter.js';
				(document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(node);
			}());
		</script>
		<noscript>Для отображения комментариев требуется Javascript</noscript>
		<!-- /SV KAMENT Counters -->
<?php }
}

function svkament_comments_template($value)
{
	if(! is_svkament_installed() )
		return $value;
    return dirname(__FILE__) . '/comments.php';
}

add_filter('comments_template', 'svkament_comments_template');
add_action('wp_footer', 'svkament_comments_counter');
add_filter('comments_number', 'svkament_comments_number');
