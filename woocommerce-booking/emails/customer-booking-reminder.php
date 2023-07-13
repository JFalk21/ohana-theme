<?php
/**
 * Customer booking confirmed email
 */
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>


<?php $order = new WC_order( $booking->order_id ); ?>
<?php if ( $message !== '' ) : ?>
	<?php echo wpautop( wptexturize( $message ) ); ?>

<?php else : ?>
	<?php

	if ( $order ) :
		$billing_first_name = $order->get_billing_first_name();
		?>
		<p><?php printf( __( 'Olá %s', 'woocommerce-booking' ), $billing_first_name ); ?></p>
		<h4><span style="color: #0a2062;"><strong>ATENÇÃO LEIA ATÉ O FINAL!</strong></span></h4>
		<h4><strong><span style="color: #0a2062;">CASO NÃO HAJA UMA DATA NOS DETALHES DO SEU PEDIDO, ENTRE</span></strong>
		<strong><span style="color: #0a2062;">EM CONTATO COM NOSSO WHATSAPP DE ATENDIMENTO POIS HOUVE</span></strong>
		<strong><span style="color: #0a2062;">ALGUM ERRO, E A SUA RESERVA NÃO ESTÁ CONFIRMADA!</span></strong></h4>
		<h4><span style="color: #0a2062;"><strong>COMO FUNCIONA O PASSEIO:</strong></span></h4>
		No dia você irá usar uma roupa de borracha, então é obrigatório que você
		venha com uma roupa de banho (biquíni, sunga e etc...).

		Nosso embarque é feito pela água, por uma embarcação de apoio, então leve com
		você a menor quantidade de pertences possíveis.

		Contamos com ducha fria pós mergulho gratuita.
		<h4><span style="color: #0a2062;"><strong>O QUE VOCÊ DEVE TRAZER:</strong></span></h4>
		Toalha, Protetor solar, Amarrador de cabelo e muita vontade de mergulhar!
		<h4><strong><span style="color: #0a2062;">É IMPORTANTE NOS AVISAR CASO:</span></strong></h4>
		Algum participante seja um mergulhador credenciado (que já tenha feito o
		curso de mergulho), lembre-se de apresentar a certificação na recepção!

		Caso venham crianças menores de 10 anos!
		<h4><span style="color: #0a2062;"><strong>HORÁRIO DE CHEGADA NA ESCOLA:</strong></span></h4>
		Você deve estar na escola 1 hora antes do período escolhido, ou seja:

		Se você escolheu o horário das 9:00, você deve estar na escola às 8:00 com
		tolerância até 8:15 e se você escolheu o horário das 14:30, você deve estar na
		escola às 13:30 com tolerância até 13:45. Caso chegue após o horário de
		tolerância não será mais permitido o embarque, e o seu pacote conta como
		utilizado.

		Contamos com seu compromisso!
		<h4><span style="color: #0a2062;"><strong>CASO VOCÊ QUEIRA CANCELAR OU TRANSFERIR O SEU MERGULHO:</strong></span></h4>
		Entre em contato com nosso <a href="https://api.whatsapp.com/send?phone=554797592179&amp;text=Ol%C3%A1,%20gostaria%20de%20reagendar%20meu%20mergulho!">WhatsApp</a> de atendimento com no mínimo 3 dias
		de antecedência a sua data escolhida.

		<span style="font-size: 14px;"><strong>O NÃO COMPARECIMENTO NA DATA OU NÃO CANCELAMENTO DO</strong></span>
		<span style="font-size: 14px;"><strong>MERGULHO, SERÁ CONSIDERADO REALIZADO E NÃO TERÁ</strong></span>
		<span style="font-size: 14px;"><strong>POSSIBILIDADE DE ESTORNO.</strong></span>
		<h3><strong><span style="color: #0a2062;">NOSSO ENDEREÇO:</span></strong></h3>
		Rua Servidão Deodato J. Lourencio, Nº 60, Centro
		Porto Belo SC – 88210-000

		Não possuímos estacionamento próprio, porém há locais para estacionar perto
		da escola.
		<h5><span style="color: #000080;">Abaixo você pode conferir as informações do seu agendamento, nos vemos em breve! </span></h5>
	<?php endif; ?>
<?php endif; ?>



<table cellspacing="0" cellpadding="6" style="width: 100%;border-color: #aaa; border: 1px solid #aaa;">
	<tbody>
		<tr>
			<th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Agendamento', 'woocommerce-booking' ); ?></th>
			<td scope="row" style="text-align:left; border: 1px solid #eee;"><?php echo $booking->product_title; ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( $booking->start_date_label, 'woocommerce-booking' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo $booking->item_booking_date; ?></td>
		</tr>
		<?php
		
		if ( isset( $booking->item_checkout_date ) && '' != $booking->item_checkout_date ) {
			?>
			<tr>
				<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( $booking->end_date_label, 'woocommerce-booking' ); ?></th>
				<td style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo $booking->item_checkout_date; ?></td>
			</tr>
			<?php
		}
		if ( isset( $booking->item_booking_time ) && '' != $booking->item_booking_time ) {
			?>
			<tr>
				<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( $booking->time_label, 'woocommerce-booking' ); ?></th>
				<td style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo $booking->item_booking_time; ?></td>
			</tr>
			<?php
		}
		if ( isset( $booking->resource_title ) && '' != $booking->resource_title ) {
			?>
			<tr>
				<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo $booking->resource_label; ?></th>
				<td style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo $booking->resource_title; ?></td>
			</tr>
			<?php
		}
		/*ADICIONANDO A QUANTIDADE DE PESSOAS AO E-MAIL DE LEMBRETE*/
		foreach ($order->get_items() as $item_id => $item ) {
			?>
			<tr>
				<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo "Quantidade" ?></th>
				<td style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo $item->get_quantity(); ?></td>
			</tr>
		<?php
		}

		if ( isset( $booking->zoom_meeting ) && '' != $booking->zoom_meeting ) {
			?>
			<tr>
				<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo bkap_zoom_join_meeting_label( $booking->product_id ); ?></th>
				<td style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo $booking->zoom_meeting; ?></td>
			</tr>
			<?php
		}
		?>

		<?php if ( $order && $booking->customer_id > 0 ) : ?>
			<tr>
				<th style="text-align:left; border: 1px solid #eee;">Pedido</th>
				<td style="text-align:left; border: 1px solid #eee;"><a href="<?php echo $order->get_view_order_url(); ?>"> Ver Pedido </a></td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>

<?php do_action( 'woocommerce_email_footer' ); ?>
