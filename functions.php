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

define('ODWP_PRODUTO_INGRESSO', 20);
define('ODWP_PRODUTO_AGENDAMENTO', 571);

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
    wp_enqueue_script('odwp-utils', get_stylesheet_directory_uri() . "/inc/js/utils.js", array('jquery'));
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

    if(strcmp($order->get_status(),'cancelled') == 0 || strcmp($order->get_status() , 'refunded') == 0 || strcmp($order->get_status() , 'on-hold') == 0  ||
        strcmp($order->get_status() , 'failed') == 0 || strcmp($order->get_status(), 'pending-payment') == 0){

        return;
    }

    foreach ($items as $item_id => $order_item) {
        $produto = $order_item->get_product_id();
        //Validar se o produto é o "ingresso"
        switch ($produto) {
            case ODWP_PRODUTO_INGRESSO:

                $coupon_code = "";
                
                if($sent_to_admin){
                    $coupon_code = odwp_generate_coupon(ODWP_PRODUTO_AGENDAMENTO, $order->get_id(), $order_item->get_quantity());
                } else {
                    $coupon_code = get_post_meta($order->get_id(), '_odwp_cupom_passeio', true);
                }
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
 * Cria um cupom para ser enviado por e-mail na compra de um ingresso
 * @param mixed $product_id
 * @param mixed $order_id
 * @param mixed $quantity
 * @return string
 */
function odwp_generate_coupon($product_id, $order_id, $quantity){

    $characters = "ABCDEFGHJKMNPQRSTUVWXYZ123456789";
    $char_length = "10";
    $coupon_code = "";
    $coupon_id = 99;

    do {
        //gera uma string de cupom aleatoria, depois verifica se já existe
        $coupon_code = substr( str_shuffle( $characters ),  0, $char_length );
        $coupon_id = wc_get_coupon_id_by_code($coupon_code);
    } while ($coupon_id != 0);

    odwp_write_log("codigo gerado:" . $coupon_code);
    $product = wc_get_product( $product_id );
    odwp_write_log("preço produto:" . $product->get_price());
        
    $discount_type = 'fixed_cart';
    $amount = $quantity * intval($product->get_price());

    $coupon = array(
        'post_title' => $coupon_code,
        'post_content' => '',
        'post_status' => 'publish',
        'post_author' => 1,
        'post_type' => 'shop_coupon'
    );

    $begin_date = strtotime('tomorrow');
    $end_date = strtotime('tomorrow +2 years');

    $new_coupon_id = wp_insert_post( $coupon );

    update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
    update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
    update_post_meta( $new_coupon_id, 'individual_use', 'no' );
    update_post_meta( $new_coupon_id, 'product_ids', '$product_id' );
    update_post_meta( $new_coupon_id, 'usage_limit', '1' );
    update_post_meta( $new_coupon_id, 'expiry_date', '' );
    update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
    update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
    update_post_meta( $new_coupon_id, 'bkap_coupon_start_date', $begin_date );
    update_post_meta( $new_coupon_id, 'bkap_coupon_end_date', $end_date );

    update_post_meta($order_id, '_odwp_cupom_passeio', $coupon_code);
    return $coupon_code;
}

/**
 * Adiciona texto abaixo da quantidade do produto (single)
 * @return void
 */
function odwp_texto_apos_preco_agenda(){
    echo "<span style='color:midnightblue;font-weight:bold;font-size:20px;'>👆 Escolha o úmero de pessoas";
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

    if(strcmp($order->get_status(),'processing') == 0){
        foreach($order->get_items() as $item_id => $item) {
            if($item->get_product_id() == ODWP_PRODUTO_INGRESSO){
                $subject = "Sua compra foi confirmada!";
            }

            if($item->get_product_id() == ODWP_PRODUTO_AGENDAMENTO){
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


/**
 * PIX
 * Vou deixar comentado aqui para reativar quando a merda do PagSeguro Sandbox voltar a funcionar
 */
/*function odwp_pgly_wc_piggly_pix_payload($payload, $pixEntity, $order){
    odwp_write_log($payload);

    return $payload;
}
add_filter('pgly_wc_piggly_pix_payload', 'odwp_pgly_wc_piggly_pix_payload', 10, 3);

function odwp_pgly_wc_piggly_pix_process($pixEntity){
    odwp_write_log("LETS GOOO");
}
add_action('pgly_wc_piggly_pix_process', 'odwp_pgly_wc_piggly_pix_process');*/


/// STATIC PAYLOAD
/*
Octavio Pedro Alves
Piggly\WooPixGateway\Vendor\Piggly\Pix\StaticPayload Object
(
    [mpm:protected] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\MPM Object
        (
            [emvs:protected] => Array
                (
                    [00] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                        (
                            [value:protected] => 
                            [default:protected] => 01
                            [id:protected] => 00
                            [name:protected] => Payload Format Indicator
                            [size:protected] => 2
                            [required:protected] => 1
                        )

                    [01] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                        (
                            [value:protected] => 
                            [default:protected] => 11
                            [id:protected] => 01
                            [name:protected] => Point of Initiation Method
                            [size:protected] => 2
                            [required:protected] => 
                        )

                    [26] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\MultiField Object
                        (
                            [minId:protected] => 0
                            [maxId:protected] => 99
                            [fields:protected] => Array
                                (
                                    [00] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                                        (
                                            [value:protected] => 
                                            [default:protected] => br.gov.bcb.pix
                                            [id:protected] => 00
                                            [name:protected] => Globally Unique Identifier
                                            [size:protected] => 32
                                            [required:protected] => 1
                                        )

                                    [01] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                                        (
                                            [value:protected] => 48a8ea7c-1162-45fa-a110-51b6478ea6a6
                                            [default:protected] => 
                                            [id:protected] => 01
                                            [name:protected] => Pix Key
                                            [size:protected] => 36
                                            [required:protected] => 1
                                        )

                                    [02] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                                        (
                                            [value:protected] => COMPRA EM OHANA DIVE
                                            [default:protected] => 
                                            [id:protected] => 02
                                            [name:protected] => Payment Description
                                            [size:protected] => 40
                                            [required:protected] => 
                                        )

                                )

                            [id:protected] => 26
                            [name:protected] => Merchant Account Information
                            [size:protected] => 99
                            [required:protected] => 1
                        )

                    [52] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                        (
                            [value:protected] => 
                            [default:protected] => 0000
                            [id:protected] => 52
                            [name:protected] => Merchant Category Code
                            [size:protected] => 4
                            [required:protected] => 1
                        )

                    [53] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                        (
                            [value:protected] => 
                            [default:protected] => 986
                            [id:protected] => 53
                            [name:protected] => Transaction Currency
                            [size:protected] => 3
                            [required:protected] => 1
                        )

                    [54] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                        (
                            [value:protected] => 280.00
                            [default:protected] => 
                            [id:protected] => 54
                            [name:protected] => Transaction Amount
                            [size:protected] => 13
                            [required:protected] => 
                        )

                    [58] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                        (
                            [value:protected] => 
                            [default:protected] => BR
                            [id:protected] => 58
                            [name:protected] => Country Code
                            [size:protected] => 2
                            [required:protected] => 1
                        )

                    [59] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                        (
                            [value:protected] => OCTAVIO PEDRO ALVES
                            [default:protected] => 
                            [id:protected] => 59
                            [name:protected] => Merchant Name
                            [size:protected] => 25
                            [required:protected] => 1
                        )

                    [60] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                        (
                            [value:protected] => ITAPEMA
                            [default:protected] => 
                            [id:protected] => 60
                            [name:protected] => Merchant City
                            [size:protected] => 15
                            [required:protected] => 1
                        )

                    [61] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                        (
                            [value:protected] => 
                            [default:protected] => 
                            [id:protected] => 61
                            [name:protected] => Postal Code
                            [size:protected] => 10
                            [required:protected] => 
                        )

                    [62] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\MultiField Object
                        (
                            [minId:protected] => 0
                            [maxId:protected] => 99
                            [fields:protected] => Array
                                (
                                    [05] => Piggly\WooPixGateway\Vendor\Piggly\Pix\Emv\Field Object
                                        (
                                            [value:protected] => O2WUE3PSLLI6Z5HEFX7FCWW04
                                            [default:protected] => ***
                                            [id:protected] => 05
                                            [name:protected] => Reference Label
                                            [size:protected] => 25
                                            [required:protected] => 
                                        )

                                )

                            [id:protected] => 62
                            [name:protected] => Additional Data Field Template
                            [size:protected] => 99
                            [required:protected] => 1
                        )

                )

            [code:protected] => 
        )

)



*/