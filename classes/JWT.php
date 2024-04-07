<?php

namespace SoftWorksPy\AppAuth\Classes;

use Firebase\JWT\JWT as Token;

class JWT
{
    private $secretKey;
    private $encrypt;
    private $token;

    function __construct($secretKey, $encrypt, $token)
    {
        $this->secretKey = $secretKey;
        $this->encrypt = $encrypt;
        $this->token = $token;
    }

    public function signIn($data, $exp = null)
    {
        $time = time();
        $token = [
            'iat' => $time,
            'data' => $data,
        ];

        if ($exp) {
            $token['exp'] = $time + (60 * $exp);
        }

        return Token::encode($token, $this->secretKey);
    }

    public function check()
    {
        if (empty($this->token)) abort(403, "Invalid token supplied.");

        try {
            Token::decode(
                $this->token,
                $this->secretKey,
                $this->encrypt
            );
        } catch (\Exception $e) {
            abort(403, "Invalid user logged in.");
        }
    }

    public function getData($attr = null)
    {
        if (empty($this->token)) return null;

        $data = Token::decode(
            $this->token,
            $this->secretKey,
            $this->encrypt
        )->data;

        return $attr ? $data->{$attr} : $data;
    }

    public function getToken(){
        return $this->token;
    }
}
