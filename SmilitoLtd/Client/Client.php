<?php

namespace SmilitoLtd\Client;

class Client
{

    private $baseUrl = 'https://api.smilito.io';

    private $jwt;

    /**
     * @throws \Exception
     */
    public function login($email, $password): LoginResponse
    {
        $ch = curl_init($this->baseUrl . '/serve/v1/login');
        $payload = json_encode([
            'email' => $email,
            'password' => $password
        ]);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            throw new \Exception('Failed to login via Smilito API');
        }

        $res = json_decode($result, true);

        $settings = [];
        if (array_key_exists('settings', $res)) {
            $settings = $res['settings'];
        }

        $resp = new LoginResponse();
        if (array_key_exists('jwt', $res)) {
            $resp->jwt = $res['jwt'];
        }
        if (array_key_exists('claimableRewardsEmailEnabled', $settings)) {
            $resp->settings->claimableRewardsEmailEnabled = $settings['claimableRewardsEmailEnabled'];
        }

        $this->jwt = $resp->jwt;

        return $resp;
    }

    /**
     * @throws \Exception
     */
    public function sendClaimableRewardEmail($email, $name, $basketId, $basketValue)
    {
        $ch = curl_init($this->baseUrl . '/serve/v1/send-claimable-rewards-email');
        $payload = json_encode([
            'email' => $email,
            'name' => $name,
            'basket_id' => $basketId,
            'basket_value' => $basketValue
        ]);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'authorization:Bearer ' . $this->jwt));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            throw new \Exception('Failed to send ClaimableRewardEmail...' . curl_error($ch));
        }
    }

}

class LoginResponse
{
    /**
     * @var string
     */
    public $jwt = '';

    /**
     * @var IntegrationSettings
     */
    public $settings;

    public function __construct()
    {
        $this->settings = new IntegrationSettings();
    }
}

class IntegrationSettings
{
    public $claimableRewardsEmailEnabled = false;
}