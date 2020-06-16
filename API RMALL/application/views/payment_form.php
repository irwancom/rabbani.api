<?php if ($payment_channel == '02') { ?>
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1" charset="utf-8">
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
            <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

            <script src="https://staging.doku.com/doku-js/assets/js/doku-1.2.js"></script>
            <link href="https://staging.doku.com/doku-js/assets/css/doku.css" rel="stylesheet">
            <script src='https://staging.doku.com/doku-js/assets/js/jquery.payment.min.js'></script>
        </head>
        <body>
            <div class="container">
                <div class="col-md-6 col-md-offset-3">
                    <div class="panel panel-default" style="margin-top:30px;">
                        <div class="panel-body" style="padding:20px;">
                            <h4>Mandiri Clickpay</h4>
                            <?= form_open(site_url('payments/charge_mandiri_clickpay')) ?>
                            <div id="mandiriclickpay" class="channel">
                                <div style="margin:20px 0;">
                                    <p>Pastikan bahwa kartu Anda telah diaktivasi melalui layanan Mandiri Internet Banking Bank Mandiri pada menu Authorized Payment agar dapat melakukan transaksi Internet Payment</p>
                                </div>
                                <div class="validasi">
                                    <div class="styled-input fleft width100">
                                        <input type="text" required="" class="cc-number" name="cc_number"> <label>No. Kartu</label>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="alert alert-info">
                                    <p>Gunakan token pin mandiri untuk bertransaksi. Nilai yang dimasukkan pada token Anda (Metode APPLI 3)</p>
                                </div>
                                <div class="list-chacode">
                                    <ul>
                                        <li>
                                            <div class="text-chacode">Challenge Code 1</div>
                                            <input type="text" id="CHALLENGE_CODE_1" name="CHALLENGE_CODE_1" readonly="true" required/>
                                            <div class="clear"></div>
                                        </li>
                                        <li>
                                            <div class="text-chacode">Challenge Code 2</div>
                                            <div class="num-chacode">0000100000</div>
                                            <input type="hidden" name="CHALLENGE_CODE_2" value="0000100000"/>
                                            <div class="clear"></div>
                                        </li>
                                        <li>
                                            <div class="text-chacode">Challenge Code 3</div>
                                            <div class="num-chacode" id="challenge_div_3"></div>
                                            <input type="hidden" name="CHALLENGE_CODE_3" id="CHALLENGE_CODE_3" value=""/>
                                            <div class="clear"></div>
                                        </li>
                                        <div class="clear"></div>
                                    </ul>
                                </div>
                                <div class="validasi">
                                    <div class="styled-input fleft width50">
                                        <input type="text" required="" name="response_token"> <label>Respon Token</label>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <input type="hidden" name="invoice_no" value="<?php echo $invoice ?>">
                                <input type="button" value="Process Payment" class="default-btn" onclick="this.form.submit();">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <script type="text/javascript">
                    jQuery(function ($) {
                        $('.cc-number').payment('formatCardNumber');
                        var challenge3 = Math.floor(Math.random() * 999999999);
                        $("#CHALLENGE_CODE_3").val(challenge3);
                    });

                    $(function () {
                        var data = new Object();
                        data.req_cc_field = 'cc_number';
                        data.req_challenge_field = 'CHALLENGE_CODE_1';
                        dokuMandiriInitiate(data);
                    });
                </script>
        </body>
    </html>
<?php } else { ?>
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1" charset="utf-8">
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.pack.js"></script>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css" rel="stylesheet">
            <script src="https://staging.doku.com/doku-js/assets/js/doku-1.2.js"></script>
            <link href="https://staging.doku.com/doku-js/assets/css/doku.css" rel="stylesheet">
        </head>
        <body>
            <?= form_open(site_url('payments/charge_cc'), 'id="payment-form" style="margin-top:30px;"') ?>
            <div doku-div='form-payment'>
                <input id="doku-token" name="doku-token" type="hidden" />
                <input id="doku-pairing-code" name="doku-pairing-code" type="hidden" />
            </div>
        </form>
        <script type="text/javascript">
            $(function () {
                var data = new Object();
                data.req_merchant_code = '<?php echo $store_id ?>';
                data.req_chain_merchant = 'NA';
                data.req_payment_channel = '<?php echo $payment_channel ?>';
                data.req_transaction_id = '<?php echo $invoice ?>';
                data.req_currency = '<?php echo $currency ?>';
                data.req_amount = '<?php echo $amount ?>';
                data.req_words = '<?php echo $words ?>';
                data.req_form_type = 'full';
                getForm(data, 'staging');
            });
        </script>
    </body>
    </html>
<?php
}?>