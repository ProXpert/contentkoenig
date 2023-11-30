<?php
class Contentkoenig_Api {
    private $rootUrl;
    private $uid;

    public function __construct($uid = null) {
        $this->rootUrl = 'https://wordpressautoblog.com';
        $this->uid = $uid;
    }

    public function getSiteUrl(){
        $url = get_site_url();

        if(str_contains($url, '//')){
            return explode('//', $url)[1];
        }

        return $url;
    }

    public function licenceCheck($licence, $add = false){
        return $this->postRequest('/user/licence_check', [
            'licence' => $licence,
            'url' => $this->getSiteUrl(),
            'add' => $add
        ]);
    }

    public function post($promptType, $prompt, $callbackUrl, $language = 'en', $rewriter_api_key = null){
        return $this->postRequest('/post', [
            'prompt_type' => $promptType,
            'prompt' => $prompt,
            'callback_url' => $callbackUrl,
            'site_url' => $this->getSiteUrl(),
            'language' => $language,
            'bypaiss_api_key' => $rewriter_api_key,
        ]);
    }

    public function authorityLinks($query){
        return $this->postRequest('/post/authority_links', [
            'query' => $query
        ]);
    }

    private function postRequest($url, $data, $desiredStatus = 200){
        $data = wp_remote_post("$this->rootUrl$url",[
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'UID' => !is_null($this->uid) ? $this->uid : ''
            ],
            'body' => json_encode($data),
            'method' => 'POST',
            'data_format' => 'body',
            'timeout' => 15,
        ]);

        if ( is_wp_error($data) ) {
            return [
                'error' => true,
                'response' => null
            ];
        }

        $code = $data['response']['code'];
        $response = json_decode($data['body'], true);
        $error = $code !== $desiredStatus || (isset($response['error']) && $response['error'] === true);

        return [
            'error' => $error,
            'response' => $response
        ];
    }
}