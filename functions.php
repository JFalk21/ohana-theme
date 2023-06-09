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

use Piggly\WooPixGateway\Core\Entities\PixEntity;
use Piggly\WooPixGateway\Vendor\Piggly\Pix\DynamicPayload;
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

function odwp_obter_abreviacao_estado($estado) {
    $estados = array(
        'Acre' => 'AC',
        'Alagoas' => 'AL',
        'Amapá' => 'AP',
        'Amazonas' => 'AM',
        'Bahia' => 'BA',
        'Ceará' => 'CE',
        'Distrito Federal' => 'DF',
        'Espírito Santo' => 'ES',
        'Goiás' => 'GO',
        'Maranhão' => 'MA',
        'Mato Grosso' => 'MT',
        'Mato Grosso do Sul' => 'MS',
        'Minas Gerais' => 'MG',
        'Pará' => 'PA',
        'Paraíba' => 'PB',
        'Paraná' => 'PR',
        'Pernambuco' => 'PE',
        'Piauí' => 'PI',
        'Rio de Janeiro' => 'RJ',
        'Rio Grande do Norte' => 'RN',
        'Rio Grande do Sul' => 'RS',
        'Rondônia' => 'RO',
        'Roraima' => 'RR',
        'Santa Catarina' => 'SC',
        'São Paulo' => 'SP',
        'Sergipe' => 'SE',
        'Tocantins' => 'TO'
    );

    $estado = ucwords(strtolower($estado)); // Converter para maiúsculas apenas o primeiro caractere de cada palavra

    if (isset($estados[$estado])) {
        return $estados[$estado];
    } else {
        return 'Estado inválido';
    }
}

function separarNumeroTelefone($numeroTelefone) {
    // Remover caracteres não numéricos
    $numeroTelefone = preg_replace('/[^0-9]/', '', $numeroTelefone);

    $codigoPais = '';
    $ddd = '';
    $telefone = '';

    if (strlen($numeroTelefone) >= 10) {
        // Extrair código do país (se existir)
        if (strlen($numeroTelefone) > 11) {
            $codigoPais = substr($numeroTelefone, 0, strlen($numeroTelefone) - 11);
        }

        // Extrair DDD
        $ddd = substr($numeroTelefone, -11, 2);

        // Extrair telefone
        $telefone = substr($numeroTelefone, -9);

        // Verificar se o nono dígito é facultativo
        if (strlen($telefone) == 8) {
            $telefone = substr($telefone, 0, 4) . '-' . substr($telefone, 4);
        } else if (strlen($telefone) == 9) {
            $telefone = substr($telefone, 0, 5) . '-' . substr($telefone, 5);
        }
    } else {
        // Número de telefone inválido
        return 'Número de telefone inválido';
    }

    // Adicionar código do Brasil (se necessário)
    if (empty($codigoPais)) {
        $codigoPais = '55';
    }

    // Retornar array com código do país, DDD e telefone
    return array(
        'codigo_pais' => $codigoPais,
        'ddd' => $ddd,
        'telefone' => $telefone
    );
}

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
function odwp_pgly_wc_piggly_pix_payload($payload, $pixEntity, $order){
    odwp_write_log("Payload");
    odwp_write_log($payload);

    odwp_write_log("Entity");
    odwp_write_log($pixEntity);

    return $payload;
}
//add_filter('pgly_wc_piggly_pix_payload', 'odwp_pgly_wc_piggly_pix_payload', 10, 3);

function odwp_pgly_wc_piggly_pix_process($pixEntity){
    $order = $pixEntity->getOrder();

    odwp_write_log($order->get_id());

    $pix_transaction = get_post_meta($order->get_id(), '_jfutils_pixtransaction', true);

    odwp_write_log($pix_transaction);
    if(!empty($pix_transaction) && intval($order->get_id()) > 620){
        $url = sprintf("https://sandbox.api.pagseguro.com/orders/%s", $pix_transaction);
        $args = array(
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . 'A9D04CAD2EC24628AEAA2DBE91EC66F1' 
            ),
        );

        $response = wp_remote_get($url, $args);
        
        $body = json_decode($response['body']);
        //odwp_write_log($body);
        if(isset($body->charges)) {

            if(strcasecmp($body->charges[0]->status, "PAID") == 0){
                odwp_write_log("TA PAGO CARALHO");
                $pixEntity->setstatus('paid');
            } 
        } else {
            odwp_write_log("Não ta pago");
        }
    }

    /**/
    return $pixEntity;
}
add_filter('pgly_wc_piggly_pix_process', 'odwp_pgly_wc_piggly_pix_process');

function odwp_pgly_wc_piggly_pix_to_pay($pixEntity) {


    $order = $pixEntity->getOrder();

    $items = [];

    foreach($order->get_items() as $item) {
        $product = wc_get_product($item->get_product_id());
        $pixItem = array(
            "name" => $item->get_name(),
            "quantity" => $item->get_quantity(),
            "unit_amount" => $product->get_price(),
        );
        $items[] = $pixItem;
    }

    $billing_neighborhood = get_post_meta($order->get_id(), '_billing_neighborhood', true);
    $billing_number = get_post_meta($order->get_id(), '_billing_number', true);
    $billing_postcode = get_post_meta($order->get_id(), '_billing_postcode', true);
    $billing_cpf = get_post_meta($order->get_id(), '_billing_cpf', true);
    $billing_cnpj = get_post_meta($order->get_id(), '_billing_cnpj', true);
    $billing_tax_id = empty($billing_cpf) ? (empty($billing_cnpj) ? "ERROR" : $billing_cnpj) : $billing_cpf;
    $phone_number = separarNumeroTelefone($order->get_billing_phone());

    $pix_data = array(
        "reference_id"=> 'OD-'. $pixEntity->getTxid(),
        "customer" => array(
            "name" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            "email" => $order->get_billing_email(),
            "tax_id" => preg_replace('/[^0-9]/', "", $billing_tax_id),
            "phones" => [ [
                    "country"=> $phone_number['codigo_pais'],
                    "area"=> $phone_number["ddd"],
                    "number"=> str_replace('-', '', $phone_number["telefone"]),
                    "type"=> "MOBILE"
                ] ]
            ),
        "items" =>  $items,
        "qr_codes"=> arraY(
             array(
                "amount"=> array(
                    "value"=> $pixEntity->getAmount()
                ),
                "expiration_date"=> $pixEntity->getExpiresAt()->format('c'),
            )
        ),
        "shipping" => array(
            "address" => array(
                "street"=>  $order->get_billing_address_1(),
                "number"=> strval($billing_number),
                "complement"=> (empty($order->get_billing_address_2()) ? "-" : $order->get_billing_address_2()),
                "locality"=> $billing_neighborhood,
                "city"=> $order->get_billing_city(),
                "region_code"=> $order->get_billing_state(),
                "country"=> "BRA",
                "postal_code"=> str_replace('-', '', strval($billing_postcode)),
            )
        ),
        "notification_urls"=> [
            "https://ohanadive.com.br"
        ]
    );

    $url = "https://api.pagseguro.com/orders";
    $args = array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . '3f0873fc-c351-4fee-a22a-6130e3e990c8ca39f9e54191bc18b65720377bab910bbd40-9fa4-4a9d-bf17-16631338b5fb' 
        ),
        'body' => json_encode($pix_data),
    );

    $return = wp_remote_post($url, $args);

    if(!is_wp_error($return)) {
        odwp_write_log("Retorno");
        odwp_write_log($return);

        if(strcmp(wp_remote_retrieve_response_code($return), '201') == 0) {
            $body = $return['body'];
            $body = json_decode($body);

            //odwp_write_log($body);

            add_post_meta($order->get_id(), '_jfutils_pixtransaction', $body->qr_codes[0]->id, true);

            odwp_write_log("Body");
            odwp_write_log($body);
        }

    } else {
        odwp_write_log("Retorno falho");

        odwp_write_log($return);
    }
}
add_action('pgly_wc_piggly_pix_to_pay', 'odwp_pgly_wc_piggly_pix_to_pay');

/// STATIC PAYLOAD
/* 

ERROS DA CHAMADA DO PAGSEGURO PRA CORRIGIR


{
    "error_messages": [
        {
            "code": "40002",
            "description": "must be a valid region code by ISO 3166-2:BR",
            "parameter_name": "shipping.address.region_code"
        },
        {
            "code": "40002",
            "description": "must be between 10000000 and 999999999",
            "parameter_name": "customer.phones[0].number"
        },
        {
            "code": "40002",
            "description": "must not be blank",
            "parameter_name": "shipping.address.complement"
        },
        {
            "code": "40002",
            "description": "must be a valid CPF or CNPJ",
            "parameter_name": "customer.tax_id"
        },
        {
            "code": "40002",
            "description": "must have 8 digits",
            "parameter_name": "shipping.address.postal_code" OK
        }
    ]
}
*/