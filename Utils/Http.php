<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class FediverseSync_Utils_Http
{
    /**
     * @var int 最近一次请求的 HTTP 状态码
     */
    private static $lastHttpCode = 0;

    /**
     * @var string|null 最近一次请求的原始响应
     */
    private static $lastResponse = null;

    /**
     * 获取最近一次请求的 HTTP 状态码
     *
     * @return int
     */
    public static function getLastHttpCode()
    {
        return self::$lastHttpCode;
    }

    /**
     * 获取最近一次请求的原始响应字符串
     *
     * @return string|null
     */
    public static function getRawResponse()
    {
        return self::$lastResponse;
    }

    /**
     * 发送GET请求
     */
    public function get($url, $headers = [])
    {
        $options = Helper::options()->plugin('FediverseSync');
        $instance_type = $options->instance_type;
        
        if ($instance_type === 'misskey') {
            // Misskey API 使用POST请求模拟GET（并使用'i'参数传递令牌）
            $data = ['i' => $options->access_token];
            return $this->post($url, $data, $headers);
        } else {
            // Mastodon/GoToSocial API
            $defaultHeaders = [
                'Authorization: Bearer ' . $options->access_token,
                'Accept: application/json',
                'Content-Type: application/json',
                'User-Agent: TypechoFediverseSync/1.4.0'
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0
            ]);
            
            // 设置超时
            if (!empty($options->api_timeout)) {
                curl_setopt($ch, CURLOPT_TIMEOUT, intval($options->api_timeout));
            }

            // 应用代理设置
            FediverseSync_Utils_Proxy::applyProxySettings($ch);

            self::$lastResponse = curl_exec($ch);
            self::$lastHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (self::$lastHttpCode === 200) {
                return json_decode(self::$lastResponse, true);
            }

            return null;
        }
    }

    /**
     * 发送POST请求（JSON 编码）
     */
    public function post($url, $data, $headers = [])
    {
        $options = Helper::options()->plugin('FediverseSync');
        $instance_type = $options->instance_type;
        
        if ($instance_type === 'misskey') {
            // Misskey 不使用Authorization头，而是将令牌作为i参数
            if (!isset($data['i']) && !empty($options->access_token)) {
                $data['i'] = $options->access_token;
            }
            
            $defaultHeaders = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: TypechoFediverseSync/1.4.0'
            ];
        } else {
            // Mastodon/GoToSocial API
            $defaultHeaders = [
                'Authorization: Bearer ' . $options->access_token,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: TypechoFediverseSync/1.4.0'
            ];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        // 设置超时
        if (!empty($options->api_timeout)) {
            curl_setopt($ch, CURLOPT_TIMEOUT, intval($options->api_timeout));
        }

        // 应用代理设置
        FediverseSync_Utils_Proxy::applyProxySettings($ch);

        self::$lastResponse = curl_exec($ch);
        self::$lastHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Misskey API可能返回204
        if (self::$lastHttpCode === 200 || ($instance_type === 'misskey' && self::$lastHttpCode === 204)) {
            return json_decode(self::$lastResponse, true);
        }

        return null;
    }

    /**
     * 发送 POST 请求（Form 编码，用于 Mastodon/GoToSocial）
     *
     * @param string $url
     * @param array  $data
     * @param array  $headers
     * @return array|null
     */
    public function postForm($url, $data, $headers = [])
    {
        $options = Helper::options()->plugin('FediverseSync');

        $defaultHeaders = [
            'Authorization: Bearer ' . $options->access_token,
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Accept: application/json',
            'User-Agent: FediverseSync/1.6.4'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        // 设置超时
        if (!empty($options->api_timeout)) {
            curl_setopt($ch, CURLOPT_TIMEOUT, intval($options->api_timeout));
        }

        // 应用代理设置
        FediverseSync_Utils_Proxy::applyProxySettings($ch);

        self::$lastResponse = curl_exec($ch);
        self::$lastHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (self::$lastHttpCode === 200 || self::$lastHttpCode === 204) {
            return json_decode(self::$lastResponse, true);
        }

        return null;
    }
}