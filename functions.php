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
function sf_child_theme_dequeue_style() {
    wp_dequeue_style( 'storefront-style' );
    wp_dequeue_style( 'storefront-woocommerce-style' );
}

/**
 * Dequeue the Storefront Parent theme core CSS
 */

/**
 * Note: DO NOT! alter or remove the code above this text and only add your custom PHP functions below this text.
 */

 /**
  * Defines
  */

if (!function_exists('odwp_write_log')) {

    function odwp_write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}

function odwp_enqueue_scripts() {
    //wp_enqueue_script('odwp-utils', get_stylesheet_directory_uri() . "/inc/js/utils.js", array('jquery'));
}

add_action('wp_enqueue_scripts', 'odwp_enqueue_scripts');

add_action( 'send_headers', 'send_frame_options_header', 10, 0 );

/**
 * Alterações referente a loja
 */

/**
 * Remove sugestões de produtos da loja
 */
add_filter('woocommerce_product_related_posts_query', '__return_empty_array', 100);
 
function odwp_remove_actions() {
	remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 10 );
    remove_action( 'storefront_page', 'storefront_page_header', 10 );
}
add_action( 'init', 'odwp_remove_actions' );

/**
 * Redireciona ao tentar acessar a loja
 * @return void
 */
function odwp_remove_shop_page() {
    if( is_shop() ){
        wp_redirect( home_url( '/produto/ingresso/' ) );
        exit();
    }
}
add_action( 'template_redirect', 'odwp_remove_shop_page' );

/**
 * Altera o campo e checkout "Notas do Pedido"
 * @param mixed $fields
 * @return mixed
 */
function odwp_override_checkout_fields( $fields ) {
     $fields['order']['order_comments']['placeholder'] = 'Notas adicionais(opcional)';
     $fields['order']['order_comments']['label'] = 'Algo que gostaria de nos informar?';
     return $fields;
}
add_filter( 'woocommerce_checkout_fields' , 'odwp_override_checkout_fields' );


/**
 * Redireciona a url da loja para de um produto fixo
 * @return bool
 */
function odwp_custom_empty_cart_redirect_url(){
    global $woocommerce;
    $redirect_product = wp_redirect('/produto/ingresso/');
    return $redirect_product;
}
add_filter( 'woocommerce_return_to_shop_redirect', 'odwp_custom_empty_cart_redirect_url' );


/**
 * Altera o texto do botão "Adicionar ao carrinho" na pagina de produtos simples
 * @return string
 */
function odwp_woocommerce_custom_single_add_to_cart_text() {
    return __( 'Continuar para a compra', 'woocommerce' ); 
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'odwp_woocommerce_custom_single_add_to_cart_text' ); 

/**
 * Remove os itens do carrinho antes de adicionar um novo item
 * @param mixed $passed
 * @param mixed $product_id
 * @param mixed $quantity
 * @return mixed
 */
function odwp_remove_cart_item_before_add_to_cart( $passed, $product_id, $quantity ) {
    if( ! WC()->cart->is_empty() )
        WC()->cart->empty_cart();
    return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'odwp_remove_cart_item_before_add_to_cart', 20, 3 );

/**
 * Redireciona direto pro checkout ao clicar em "Adicionar ao carrinho"
 * @return string
 */
function odwp_add_to_cart_redirect() {
    global $woocommerce;
    $redirect_checkout = wc_get_checkout_url();
    return $redirect_checkout;
}
add_filter('woocommerce_add_to_cart_redirect', 'odwp_add_to_cart_redirect');


/**
 * Inicia o processo de gerar o cupom e adicionar ao e-mail, caso seja um pedido válido para isso
 * @param mixed $order
 * @param mixed $sent_to_admin
 * @param mixed $plain_text
 * @return void
 */
function odwp_formata_email($order, $sent_to_admin, $plain_text){

    if(empty($order)){
        return;
    }

    $items = $order->get_items();

    $wc_options = get_option('jfutils-wc');


    if(!empty($wc_options)){
        if(empty($wc_options['jfu-wc-ticket-product']) && empty($wc_options['jfu-wc-booking-product'])) {
            return;
        }
    }


    if(strcmp($order->get_status(),'cancelled') == 0 || strcmp($order->get_status() , 'refunded') == 0 || strcmp($order->get_status() , 'on-hold') == 0  ||
        strcmp($order->get_status() , 'failed') == 0 || strcmp($order->get_status(), 'pending-payment') == 0){

        return;
    }

    foreach ($items as $item_id => $order_item) {
        $produto = $order_item->get_product_id();
        //Validar se o produto é o "ingresso"
        switch ($produto) {
            case $wc_options['jfu-wc-ticket-product']:

                $coupon_code = jfutils_generate_coupon($wc_options['jfu-wc-booking-product'], $order->get_id(), $order_item->get_quantity());

                if(!empty($coupon_code)) {
                    echo "<br><h2><strong>Cupom: " . $coupon_code ."<strong></h2><br/><br/>";
                }
                break;
            
            default:
                // nada
                break;
        }

    }
}
add_action('woocommerce_email_before_order_table', 'odwp_formata_email', 20, 3);

/**
 * Adiciona texto abaixo da quantidade do produto (single)
 * @return void
 */
function odwp_texto_apos_preco_agenda(){
    echo "<span style='color:midnightblue;font-weight:bold;font-size:20px;'>👆 Escolha o número de pessoas";
}
add_action('woocommerce_after_add_to_cart_form', 'odwp_texto_apos_preco_agenda', 0);

/**
 * Filtrando o Subject e o Heading dos emails do WooCommerce pra se adaptar ao ingresso e a agenda
 * @param mixed $subject
 * @param mixed $order
 * @return mixed
 */
function odwp_woocommerce_email_subject_customer_processing_order($subject, $order) {
    if(empty($order)){
        return;
    }

    $wc_options = get_option('jfutils-wc');
    $produto_ingresso = $wc_options['jfu-wc-ticket-product'];
    $produto_agenda = $wc_options['jfu-wc-booking-product'];

    if(strcmp($order->get_status(),'processing') == 0){
        foreach($order->get_items() as $item_id => $item) {
            if($item->get_product_id() == $produto_ingresso){
                $subject = "Sua compra foi confirmada!";
            }

            if($item->get_product_id() == $produto_agenda){
                $subject = "Seu agendamento foi aprovado!";
            }
        }

    }

    return $subject;
}
add_filter('woocommerce_email_subject_customer_processing_order', 'odwp_woocommerce_email_subject_customer_processing_order', 10, 2);
add_filter('woocommerce_email_heading_customer_processing_order', 'odwp_woocommerce_email_subject_customer_processing_order', 10, 2);

/**
 * Alterando o texto do endereço de faturamento no e-mail para Informações do Cliente
 * @param mixed $translated_text
 * @return mixed
 */
function wc_billing_field_strings( $translated_text) {
    switch ( $translated_text ) {
        case 'Endereço de faturamento' :
            $translated_text = __( 'Informações do Cliente', 'woocommerce' );
        break;
    }
    return $translated_text;
}
add_filter( 'gettext', 'wc_billing_field_strings', 10 );