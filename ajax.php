<?php
/**
 * @see https://makitweb.com/how-to-send-ajax-request-from-plugin-wordpress
 */

/**
 * Load configurations from the database.
 */
function fn_mtn_momo_ajax_get_configurations() {
	global $wpdb;

	$table = "{$wpdb->prefix}mtn_momo_configurations";

	$filter = '';

	if (isset($_POST['product'])) {
		$product = $_POST['product'];
		$filter .= " WHERE `name` LIKE '{$product}%' ";
	}

	$sql = "SELECT `name`, `value` FROM {$table} {$filter};";

	$configurations = $wpdb->get_results($sql);

	echo json_encode(array('configurations' => $configurations));

	wp_die();
}

function fn_mtn_momo_ajax_get_transaction_status() {
	global $wpdb;

	$table = "{$wpdb->prefix}mtn_momo_transactions";

	$errors = array();

	if (! isset($_POST['product'])) {
		$errors['product'][] = 'The product parameter is required';
	} elseif (! in_array($_POST['product'], array('collection'))) {
		$errors['product'][] = 'Invalid product chosen';
	}

	if (! isset($_POST['momo_transaction_id'])) {
		$errors['momo_transaction_id'][] = 'The momo_transaction_id parameter is required';
	}

	if ($errors) {
		wp_send_json_error(array_merge(array(
			'message' => 'The given data was invalid.',
		), $errors), 422);
	}

	$transaction = null;

	if ($_POST['product'] == 'collection') {
		$collection = new MTN_MOMO_Collection();
		$transaction = $collection->get_transaction_status($_POST['momo_transaction_id']);
	} elseif ($_POST['product'] == 'disbursement') {
		// $disbursement = new MTN_MOMO_Disbursement();
		// $transaction = $disbursement->get_transaction_status($_POST['momo_transaction_id']);
	} elseif ($_POST['product'] == 'remittance') {
		// $remittance = new MTN_MOMO_Remittance();
		// $transaction = $remittance->get_transaction_status($_POST['momo_transaction_id']);
	}

	if (! $transaction) {
		wp_send_json_error(array('message' => 'Failed to check transaction status'), 500);
	}

	$wpdb->update(
		$table,
		array(
			'status' => $transaction['status'],
			// reason is an array 🙄
			'reason' => fn_mtn_momo_array_get($transaction, 'reason.code')
		),
		array('external_id' => $_POST['momo_transaction_id']),
		array('%s', '%s'),
		array('%s')
	);

	wp_send_json($transaction, 200);
}
