<?php

use WHMCS\Database\Capsule;

// Define the hook function
function hook_show_pending_invoices($vars)
{
    $productId = (int) $vars['id'];

    // Get the current client's ID
    $clientId = $_SESSION['uid'];

    // Fetch the last unpaid or partially paid invoice for the specific service and client
    $lastUnpaidInvoice = Capsule::table('tblinvoices')
        ->join('tblinvoiceitems', 'tblinvoices.id', '=', 'tblinvoiceitems.invoiceid')
        ->join('tblhosting', 'tblhosting.id', '=', 'tblinvoiceitems.relid')
        ->where('tblinvoices.userid', $clientId)
        ->where('tblhosting.id', $productId)
        ->whereIn('tblinvoices.status', ['Unpaid', 'Partially Paid'])
        ->orderBy('tblinvoices.date', 'desc')
        ->first();

    // Fetch the client's currency code and symbol
    $clientCurrencyCode = '';
    $clientCurrencySymbol = '';
    if ($clientId > 0) {
        $clientDetails = localAPI('GetClientsDetails', array('clientid' => $clientId));
        if ($clientDetails['result'] == 'success') {
            $clientCurrencyCode = $clientDetails['client']['currency_code'];
            $clientCurrencySymbol = $clientDetails['client']['currency'];
        }
    }

    // Prepare the data to pass to the template
    $data = [];
    if ($lastUnpaidInvoice) {
        $data['pendingInvoice'] = [
            'invoiceId' => $lastUnpaidInvoice->invoiceid,
//            'invoiceNum' => $lastUnpaidInvoice->invoicenum,
            'invoiceTotal' => $lastUnpaidInvoice->total,
            'clientCurrencyCode' => $clientCurrencyCode,
            'clientCurrencySymbol' => $clientCurrencySymbol,
        ];
    } else {
        $data['pendingInvoice'] = null;
    }

    return $data;
}

// Register the hook
add_hook('ClientAreaPageProductDetails', 1, 'hook_show_pending_invoices');
