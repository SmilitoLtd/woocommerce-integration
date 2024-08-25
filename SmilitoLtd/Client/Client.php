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

        $resp = new LoginResponse();
        $resp->jwt = $res['jwt'];
        $resp->settings->claimableRewardsEmailEnabled = $res['claimable_reward_email_enabled'];

        $this->jwt = $resp->jwt;

        return $resp;
    }

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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            error_log('Failed to send Smilito claimable rewards via email');
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