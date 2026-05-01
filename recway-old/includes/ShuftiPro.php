<?php

class ShuftiPro
{
    public function getShuftiProLink($candidate)
    {
        $client_id = 'vZpxoXUTfPN2THPulz1dQ3WSidU0xGk8lv1oLxeKScsymxsvCU1756979095';
        $secret_key = 'BO7ZessXBsL8MhfCxCn89hqInEhtjo2W';
        $url = 'https://api.shuftipro.com/';
        $verification_request = [
            //your unique request reference
            "reference" => 'ref-'.rand(4, 444).rand(4, 444),
            //URL where you will receive the webhooks from Shufti
            "callback_url" => 'https://dev.orderspi.se/api/shufti-callback?customer_id='.$candidate->cus_id .'&candidate_id='.$candidate->id,
            //end-user email
            "email" => $candidate->email,
            //end-user country
            "country" => "",
            //URL where end-user will be redirected after verification completed
            "redirect_url" => "",
            //what kind of proofs will be provided to Shufti for verification?
            "verification_mode" => "any",
            //allow end-user to capture photos/videos with the camera only.
            "allow_offline" => "0",
            //allow end-user to upload already captured images or videos.
            "allow_online" => "0",
            //privacy policy screen will be shown to end-user
            "show_privacy_policy" => "1",
            //verification results screen will be shown to end-user
            "show_results" => "1",
            //consent screen will be shown to end-user
            "show_consent" => "1",
            //User cannot send Feedback
            "show_feedback_form" => "0",
        ];
        //face onsite verification
        $verification_request['face'] = [];
        //document onsite verification with OCR
        $verification_request['document'] = [
            'name' => "",
            'dob' => "",
            'gender' => "",
            'document_number' => "",
            'expiry_date' => "",
            'issue_date' => "",
            'supported_types' => ['id_card','passport','driving_license'],
        ];
        $auth = $client_id.":".$secret_key;
        $headers = ['Content-Type: application/json'];
        $post_data = json_encode($verification_request);
        //Calling Shufti request API using curl
        $response = $this->send_curl($url, $post_data, $headers, $auth);
        //get Shufti API Response
        $response_data = $response['body'];
        //get Shufti Signature
        $sp_signature = $this->get_header_keys($response['headers'])['signature'];
        //calculating signature for verification
        $calculate_signature = hash('sha256', $response_data.hash('sha256', $secret_key));
        $decoded_response = json_decode($response_data, true);

        if ($sp_signature == $calculate_signature) {
            return $response_data;
        } else {
            return "Invalid signature : $response_data";
        }
    }

    //method to send CURL request
    public function send_curl($url, $post_data, $headers, $auth)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $html_response = curl_exec($ch);
        $curl_info = curl_getinfo($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($html_response, 0, $header_size);
        $body = substr($html_response, $header_size);
        curl_close($ch);
        return ['headers' => $headers,'body' => $body];
    }

    //following method is to get response headers, here the main intention is to get response Signature from the response headers
    public function get_header_keys($header_string)
    {
        $headers = [];
        $exploded = explode("\n", $header_string);
        if (! empty($exploded)) {
            foreach ($exploded as $key => $header) {
                if (! $key) {
                    $headers[] = $header;
                } else {
                    $header = explode(':', $header);
                    $headers[trim($header[0])] = isset($header[1]) ? trim($header[1]) : "";
                }
            }
        }
        return $headers;
    }
}
