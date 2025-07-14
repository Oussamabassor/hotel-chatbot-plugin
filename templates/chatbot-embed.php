<?php
if ( ! function_exists( 'esc_attr' ) ) {
	require_once( dirname( __FILE__, 4 ) . '/wp-load.php' );
}
?>
<div id="hotel-chatbot-widget" data-hotel-id="<?php echo esc_attr($atts['hotel_id']); ?>"></div>
