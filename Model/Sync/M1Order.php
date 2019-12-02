<?php

class Manzilak_Integration_Model_Observer {
    public function checkOrder(Varien_Event_Observer $observer) {

        $order = $observer->getEvent()->getOrder();
        $status = $order->getStatus();
        $invoice = $order->getInvoiceCollection();
        $has_invoice = $order->hasInvoices();

        $order_date = $order->getCreatedAt();
        $order_date = date('Y-m-dH:i:s', strtotime( $order_date) );

        $shipping_fee = $order->getShippingAmount();

        $tax_info = $order->getFullTaxInfo();

        $tax_rate = isset( $tax_info[0]['percent'] ) ? $tax_info[0]['percent'] : 5;

        $total_vat = isset( $tax_info[0]['amount'] ) ? $tax_info[0]['amount'] : 0;


        // $cod_fee = $order->getGrandTotal() - $order->getSubtotal() - $order->getDiscountAmount() - $total_vat - $shipping_fee;
        $cod_fee = ( $order->getCodFee() ) ? $order->getCodFee() : 0;;

        $shipping_fee = $shipping_fee + $cod_fee;

        if($order->getCustomerIsGuest()){
            $customer_id = 0;
            $enroll_status = 'N';
        }else{
            $customer_id = $order->getCustomerId();

            $customer = Mage::getModel('customer/customer')->load($customer_id);
            $customer_id = ( $customer->getAs400CustomerId() ) ? $customer->getAs400CustomerId() : 0;

            $enroll_status = ( $customer->getAs400EnrollStatus() == 1 ) ? 'Y' : 'N';
        }


        $customer_name = array();

        if( $order->getCustomerFirstname() ){
            $customer_name[] = ucfirst( $order->getCustomerFirstname() );
        }
        if( $order->getCustomerMiddlename() ){
            $customer_name[] = ucfirst( $order->getCustomerMiddlename() );
        }
        if( $order->getCustomerLastname() ){
            $customer_name[] = ucfirst( $order->getCustomerLastname() );
        }
        $voucher_code = $order->getCouponCode();

        if( !$voucher_code )  $voucher_code = 0;
        $discount_amout = $order->getDiscountAmount();

        $customer_name = urlencode( implode(' ', $customer_name) );
        $order_id = $order->getId();




        // Get Invoice ID
        // Get Invoice Date
        // Get Product SKUs
        // Get Product Quantities
        // Get Product Prices
        // Set Invoice Type
        // Then pass all to cURL file

        if($status == 'return_to_stock' or $status == 'delivered' ) {
            $order_date_c = strtotime( $order->getCreatedAt() );


            if($has_invoice) {

                $tempSkus = array();
                $dts =  array();


                $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();

                $payment_type = explode('_',$payment_method_code);

                if( $payment_type[0] == 'directdepositpro'){
                    $payment_method_code = 'bank';
                }

                $payment_method_title = $order->getPayment()->getMethodInstance()->getTitle();

                $items_vat = 0;

                $apply_after_discount = Mage::getStoreConfig('tax/calculation/apply_after_discount');


                foreach($invoice as $inv) {
                    $id[] = $inv->getIncrementId();
                    $date[] = $inv->getCreatedAt();
                    foreach ($inv->getAllItems() as $item) {
                        // if(!in_array($item->getSku() , $tempSkus)){


                        // $vat_total = ( ( $item->getPrice() * $item->getQty() ) * $tax_rate ) / 100;
                        $vat = $item->getPriceInclTax() - $item->getPrice();
                        // $vat = ( ( $item->getPrice() ) * $tax_rate ) / 100;
                        // echo $vat.'<br>';
                        // echo 'item tax'. $item->getTaxAmount().'<br>';
                        // // echo ( ( $item->getPrice() * $item->getQty() ) * $tax_rate ) / 100;
                        // echo '<br>';

                        // $vat_total = $vat * $item->getQty();
                        $vat_total = $item->getTaxAmount();

                        // ignore orders before jan 1 2018
                        if( $order_date_c < 1514764800 ){
                            $vat = 0;
                        }
                        $item_discount = ( $item->getBaseDiscountAmount() ) ? $item->getBaseDiscountAmount() : 0;
                        $item_discount_qty = ( $item->getBaseDiscountAmount() ) ? $item->getBaseDiscountAmount() / $item->getQty() : 0;

                        // substract the discounted vat amount in total vat
                        if( $item_discount > 0 && $apply_after_discount === 1 ){
                            $vat = $vat - ( ( $item_discount_qty * $tax_rate ) / 100 );
                            $vat_total = $vat_total - ( $item->getQty() * ( ( $item_discount * $tax_rate ) / 100 ) );
                        }

                        $items_vat = $items_vat + $vat_total;
                        // if(preg_match("/f/", $item->getSku())) {
                        // 		$dts[] = trim("dt=".substr(trim($item->getSku()),1).",".number_format($item->getQty(),0,'','').",".number_format($item->getPrice(),0,'','').",".$item_discount_qty.",".$vat );
                        // }elseif(preg_match("/a/", $item->getSku())) {
                        // 		$dts[] = trim("dt=".substr(trim($item->getSku()),1).",".number_format($item->getQty(),0,'','').",".number_format($item->getPrice(),0,'','').",".$item_discount_qty.",".$vat );
                        // }elseif(strpos($dts[0] , trim($item->getSku()))) {
                        // 		continue;
                        // }else{
                        // 	$dts[] = trim("dt=".trim($item->getSku()).",".number_format($item->getQty(),0,'','').",".number_format($item->getPrice(),0,'','').",".$item_discount_qty.",".$vat );
                        // }
                        if(preg_match("/f/", $item->getSku())) {
                            $dts[] = trim("dt=".substr(trim($item->getSku()),1).",".number_format($item->getQty(),0,'','').",".$item->getPrice().",".$item_discount_qty.",".$vat );
                        }elseif(preg_match("/a/", $item->getSku())) {
                            $dts[] = trim("dt=".substr(trim($item->getSku()),1).",".number_format($item->getQty(),0,'','').",".$item->getPrice().",".$item_discount_qty.",".$vat );
                        }elseif(strpos($dts[0] , trim($item->getSku()))) {
                            continue;
                        }else{
                            $dts[] = trim("dt=".trim($item->getSku()).",".number_format($item->getQty(),0,'','').",".$item->getPrice().",".$item_discount_qty.",".$vat );
                        }
                    }
                    $tempSkus[] = $item->getSku();
                    // }
                }
                // die;

                // $shipping_fee = ( ($shipping_fee - $total_vat) > 0 ) ? $shipping_fee - $total_vat : 0;
                $shipping_vat = ( $total_vat - $items_vat ) > 0 ? $total_vat - $items_vat : 0;
                // echo $shipping_fee;
                // die;
                // echo "total vat ". $total_vat . "\n";
                // echo "items vat ". $items_vat . "\n";
                // echo $shipping_vat . "\n";
                // die;

                // ignore orders before jan 1 2018
                if( $order_date_c < 1514764800 ){
                    $shipping_vat = 0;
                }

                $shipping_vat = round( $shipping_vat, 2 );
                //echo $shipping_vat;
                //die;

                // Gather all SKUs inside the invoice with their quantities


                // duplication issue fix begin
// echo "<pre>";
// 			print_r( $dts );
                if( count( $dts ) ){
                    $rq_skus = [];
                    foreach( $dts as $d_key => $d_value ){
                        $d_break = explode(',', $d_value);

                        $rq_skus[ $d_break[0] ][] = array(
                            'key'	=> $d_key,
                            'qty'	=> $d_break[1],
                            'price'	=> $d_break[2],
                        );

                    }
                    // loop around the
                    foreach( $rq_skus as $rq_sku ){

                        if( count($rq_sku) > 1 ){

                            foreach( $rq_sku as $possible_dup ){

                                // check if item is duplicated
                                if( $possible_dup['qty'] == 1 && $possible_dup['price'] == 0 ){

                                    // item duplication confirmed, delete that
                                    unset( $dts[ $possible_dup['key'] ] );
                                }
                            }
                        }
                    }
                }
                // print_r( $dts );
                // die;
                $dt = implode("&", $dts);

                // echo $dt;die;
                // duplication issue fix end


                //var_export( $dts );
                //print_r( curl_exec($in_curl) );
                //die;


                //Mage::throwException(Mage::helper('adminhtml')->__($dt));die;
                // Send only the date without time
                $date = explode(' ', $date[0]);

                // Bijay changes
                $new_date = implode('', $date);
                // Decide if the invoice is to increment quantity or subtract.
                switch($status) {
                    case 'delivered':
                        $type = '1';
                        break;

                    case 'return_to_stock':
                        $type = '2';
                        break;
                }

                // check if invoice exists or not in as400
                $in_curl = curl_init();

                curl_setopt_array($in_curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => "http://172.16.20.160/cmsinteg/Service.asmx/Check_Invoice",
                    CURLOPT_POST => 1,
                    CURLOPT_POSTFIELDS => "CMSInvID=$id[0]&InvType=$type",
                    CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0",
                ));

                // print_r( simplexml_load_string(curl_exec($in_curl)) );
                // die;

                $in_xml = simplexml_load_string(curl_exec($in_curl)) or die("Error: Something went wrong. Please contact the system administrator.");

                if(is_object($in_xml)) {
                    if( $in_xml->Code == 100 ){

                        $request_params = array(
                            'InvoideId'		=> $id[0],
                            'invoiceDate'	=> $order_date,
                            'invoicetype'	=> $type,
                            'Cust_Id'		=> $customer_id,
                            'enrolled'		=> $enroll_status,
                            'payment_code'	=> $payment_method_code,
                            'Bank_name'		=> ( $payment_method_code == 'bank' ) ? $payment_method_title : '',
                            'AWB'			=> '',
                            'Fees'			=> $shipping_fee,
                            'Vchr_Id'		=> $voucher_code,
                            'Vchr_amt'		=> $discount_amout,
                            'token'			=> '8sv38a219324sia',
                            'FeesVat'		=> $shipping_vat,
                        );

                        $request_query = http_build_query($request_params) . '&Cust_Name=' . $customer_name . '&' . $dt;

// print_r( $request_params );
                        // echo $request_query;
                        // die;


                        // echo "http://172.16.20.160/IBM1/Service.asmx/CreateOrder?".$request_query;
                        // die;
                        // Get cURL resource
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_RETURNTRANSFER => 1,
                            CURLOPT_URL => "http://172.16.20.160/IBM1/Service.asmx/CreateOrder?".$request_query,
                            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0",
                        ));


                        Mage::log('Invoice ID:'.$id[0].' response code: '. $in_xml->Code, null, 'invoice.log');
                        Mage::log("http://172.16.20.160/IBM1/Service.asmx/UpdateN?InvoideId=$id[0]&invoiceDate=$order_date&$dt&invoicetype=$type&Cust_Id=$customer_id&Cust_Name=$customer_name&Fees=$shipping_fee&Vchr_Id=$voucher_code&Vchr_amt=$discount_amout", null, 'invoice.log');


                        $res = curl_exec($curl);
                        //echo "http://172.16.20.160/IBM1/Service.asmx/CreateOrder?".$request_query;
                        // echo "http://172.16.20.160/IBM1/Service.asmx/CreateOrder?".$request_query;
                        // print_r( simplexml_load_string(curl_exec($curl)) );die;
                        $xml = simplexml_load_string(curl_exec($curl)) or die("Error: Something went wrong. Please contact the system administrator.");


                        if(is_object($xml)) {
                            switch($xml[0]) {
                                case '1':
                                    Mage::throwException(Mage::helper('adminhtml')->__('No Details (Data table is empty).'));
                                    break;
                                case '2':
                                    Mage::throwException(Mage::helper('adminhtml')->__('Invoice Id is Blank.'));
                                    break;
                                case '3':
                                    Mage::throwException(Mage::helper('adminhtml')->__('Invoice Date is Blank.'));
                                    break;
                                case '4':
                                    Mage::throwException(Mage::helper('adminhtml')->__('Validation Error.'));
                                    break;
                                case '5':
                                    Mage::throwException(Mage::helper('adminhtml')->__('line id - SKU NOT FOUND IN MASTER FILE.'));
                                    break;
                                case '6':
                                    Mage::throwException(Mage::helper('adminhtml')->__('Invoice Type is Not correct not in (1,2).'));
                                    break;
                                case '7':
                                    Mage::throwException(Mage::helper('adminhtml')->__('date validation.'));
                                    break;
                                case '8':
                                    Mage::throwException(Mage::helper('adminhtml')->__('Invoice Already Updated.'));
                                    break;
                                case '9':
                                    Mage::throwException(Mage::helper('adminhtml')->__('Items count More Than 50.'));
                                    break;
                                // case '10':
                                // Mage::throwException(Mage::helper('adminhtml')->__('The invoice number already exists.'));
                                // break;
                                case '501':
                                    Mage::throwException(Mage::helper('adminhtml')->__('Please Check the SKU for this product.'));
                                    break;
                            }
                        } else {
                            Mage::throwException(Mage::helper('adminhtml')->__('An error happened while processing this request.'));
                        }

                        // Close request to clear up some resources
                        curl_close($curl);
                        //}
                    }
                }


                curl_close($in_curl);



            }
        }
    }
}
