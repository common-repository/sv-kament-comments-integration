<?php
	$svkament_options = get_option('kament-comments');
	$svkament_subdomain = '';
	if( isset($svkament_options['subdomain']) )
		$svkament_subdomain = $svkament_options['subdomain'];

	$kament_page_name = 'post_' . get_the_ID();
	$plain_comments = svkament_get_plain_comments($kament_page_name);
?>

<?php if(comments_open()) : ?>
<!-- KAMENT -->
<div id='kament_comments'>
	<?php echo $plain_comments; ?>
</div>
<script type='text/javascript'>
	/* * * НАСТРОЙКА * * */
	var kament_subdomain = '<?php echo $svkament_subdomain; ?>';
	var kament_page_name = '<?php echo $kament_page_name; ?>';
	var kament_page_url = '<?php echo get_permalink(); ?>';
	var kament_page_title = '<?php echo get_the_title(); ?>';

	/* * * НЕ МЕНЯЙТЕ НИЧЕГО НИЖЕ ЭТОЙ СТРОКИ * * */
	(function() {
		var node = document.createElement('script'); node.type = 'text/javascript'; node.async = true;
		node.src = 'http://' + kament_subdomain + '.svkament.ru/js/embed.js';
		(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(node);
	})();
</script>
<noscript>Для отображения комментариев нужно включить Javascript</noscript>
<!-- /KAMENT -->
<?php endif; ?>
