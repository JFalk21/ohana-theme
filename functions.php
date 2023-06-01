<?php

/**
 * Storefront automatically loads the core CSS even if using a child theme as it is more efficient
 * than @importing it in the child theme style.css file.
 *
 * Uncomment the line below if you'd like to disable the Storefront Core CSS.
 *
 * If you don't plan to dequeue the Storefront Core CSS you can remove the subsequent line and as well
 * as the sf_child_theme_dequeue_style() function declaration.
 */
//add_action( 'wp_enqueue_scripts', 'sf_child_theme_dequeue_style', 999 );

/**
 * Dequeue the Storefront Parent theme core CSS
 */

define('APWP_PRODUTO_INGRESSO', 110);
define('APWP_PRODUTO_AGENDAMENTO', 494);

if (!function_exists('apwp_write_log')) {

    function apwp_write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}

function sf_child_theme_dequeue_style() {
    wp_dequeue_style( 'storefront-style' );
    wp_dequeue_style( 'storefront-woocommerce-style' );
}

/**
 * Note: DO NOT! alter or remove the code above this text and only add your custom PHP functions below this text.
 */

/**
 * Alterações referente a loja
 */

add_action( 'init', 'abwp_remove_actions' );
 
function abwp_remove_actions() {
	remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 10 );
	//remove_action( 'storefront_footer', 'storefront_credit' );
	remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 );
	//add_filter( 'wc_add_to_cart_message', '__return_false' );
	/*remove_action( 'storefront_footer', 'storefront_footer', 999 );*/
	


}

function abwp_remove_shop_page() {
    if( is_shop() ){
        wp_redirect( home_url( '/produto/passeio-de-lancha/' ) );
        exit();
    }
}
add_action( 'template_redirect', 'abwp_remove_shop_page' );

add_filter( 'woocommerce_checkout_fields' , 'abwp_override_checkout_fields' );
function abwp_override_checkout_fields( $fields ) {
     $fields['order']['order_comments']['placeholder'] = 'Notas adicionais(opcional)';
     $fields['order']['order_comments']['label'] = 'Algo que gostaria de nos informar?';
     return $fields;
}

add_filter( 'woocommerce_return_to_shop_redirect', 'apwp_custom_empty_cart_redirect_url' );
function apwp_custom_empty_cart_redirect_url(){
    global $woocommerce;
    $redirect_product = wp_redirect('/produto/passeio-de-lancha/');
    return $redirect_product;
}

add_filter( 'woocommerce_product_single_add_to_cart_text', 'abwp_woocommerce_custom_single_add_to_cart_text' ); 
function abwp_woocommerce_custom_single_add_to_cart_text() {
    return __( 'Continuar para a compra', 'woocommerce' ); 
}

add_filter( 'woocommerce_add_to_cart_validation', 'apwp_remove_cart_item_before_add_to_cart', 20, 3 );
function apwp_remove_cart_item_before_add_to_cart( $passed, $product_id, $quantity ) {
    if( ! WC()->cart->is_empty() )
        WC()->cart->empty_cart();
    return $passed;
}

add_filter('woocommerce_add_to_cart_redirect', 'apwp_add_to_cart_redirect');
function apwp_add_to_cart_redirect() {
    global $woocommerce;
    $redirect_checkout = wc_get_checkout_url();
    return $redirect_checkout;
}


function apwp_formata_email($order, $sent_to_admin, $plain_text){
    $items = $order->get_items();
    apwp_write_log("entrou: " . $order->get_status());
    if(strcmp($order->get_status(),'cancelled') == 0 || strcmp($order->get_status() , 'refunded') == 0 || strcmp($order->get_status() , 'on-hold') == 0  ||
        strcmp($order->get_status() , 'failed') == 0 || strcmp($order->get_status(), 'pending-payment') == 0){

        return;
    }

    foreach ($items as $item_id => $order_item) {
        $produto = $order_item->get_product_id();
        //Validar se o produto é o "ingresso"
        switch ($produto) {
            case 110:
                apwp_write_log("entrou 2");

                $coupon_code = "";
                
                if($sent_to_admin){
                    $coupon_code = apwp_generate_coupon(APWP_PRODUTO_AGENDAMENTO, $order->get_id(), $order_item->get_quantity());
                } else {
                    $coupon_code = get_post_meta($order->get_id(), '_apwp_cupom_passeio', true);
                }
                echo "<br><h2><strong>Cupom: " . $coupon_code ."<strong></h2><br/><br/>";
                break;
            
            default:
                // nada
                break;
        }

    }
}
add_action('woocommerce_email_before_order_table', 'apwp_formata_email', 0, 3);

function apwp_generate_coupon($product_id, $order_id, $quantity){

        apwp_write_log("entrou 3");


    $characters = "ABCDEFGHJKMNPQRSTUVWXYZ123456789";
    $char_length = "10";
    $coupon_code = "";
    $coupon_id = 99;

    do {
        //gera uma string de cupom aleatoria, depois verifica se já existe
        $coupon_code = substr( str_shuffle( $characters ),  0, $char_length );
        $coupon_id = wc_get_coupon_id_by_code($coupon_code, $order_id);
    } while ($coupon_id != 0);


    apwp_write_log("codigo gerado:" . $coupon_code);
    $product = wc_get_product( $product_id );
    apwp_write_log("codigo gerado:" . $product->get_price());
    
        
    $discount_type = 'fixed_cart';
    $amount = $quantity * $product->get_price();

    $coupon = array(
        'post_title' => $coupon_code,
        'post_content' => '',
        'post_status' => 'publish',
        'post_author' => 1,
        'post_type' => 'shop_coupon'
    );

    $new_coupon_id = wp_insert_post( $coupon );

    update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
    update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
    update_post_meta( $new_coupon_id, 'individual_use', 'no' );
    update_post_meta( $new_coupon_id, 'product_ids', '$product_id' );
    update_post_meta( $new_coupon_id, 'usage_limit', '1' );
    update_post_meta( $new_coupon_id, 'expiry_date', '' );
    update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
    update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

    update_post_meta($order_id, '_apwp_cupom_passeio', $coupon_code);
    return $coupon_code;
}

function apwp_texto_apos_preco_agenda(){
    echo "<span style='color:midnightblue;font-weight:bold;font-size:20px;'>👆 Escolha o úmero de pessoas";
}
add_action('woocommerce_after_add_to_cart_form', 'apwp_texto_apos_preco_agenda', 0);

function apwp_bkap_update_order($order_id, $old_status, $new_status) {
    apwp_write_log("Váriavel VALUES");
    apwp_write_log($old_status);
    apwp_write_log("Váriavel result");
    apwp_write_log($new_status);
}
add_action('woocommerce_order_status_changed', 'apwp_bkap_update_order', 0, 3);