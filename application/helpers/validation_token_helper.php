<?php

/**
 * validation token admin
 */
function validate_token() {
    $token = token_header();
    if (!$token) return false;

    $ci = &get_instance();
    $ci->load->model('CoreAuth');
    $result = $ci->CoreAuth->validate($token);
    return $result;
}

function validate_token_user() {
    $token = token_header();
    if (!$token) return false;

    $ci = &get_instance();
    $ci->load->model('CoreAuth');
    $result = $ci->CoreAuth->validateUser($token);
    return $result;
}


function token_header() {
    $ci = &get_instance();
    $token = $ci->input->get_request_header('X-Token-Secret');

    return $token;
}