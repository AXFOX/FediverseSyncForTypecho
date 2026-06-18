<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * cURL 代理设置工具
 * 支持 HTTP 和 SOCKS5 代理（SOCKS5 使用远端 DNS 解析以避免 DNS 污染）
 */
class FediverseSync_Utils_Proxy
{
    /**
     * 为 cURL 句柄应用代理设置
     *
     * @param resource $ch cURL 句柄
     */
    public static function applyProxySettings($ch)
    {
        $options = Helper::options()->plugin('FediverseSync');

        if (empty($options->enable_proxy) || $options->enable_proxy !== '1') {
            return;
        }

        if (empty($options->proxy_url)) {
            return;
        }

        curl_setopt($ch, CURLOPT_PROXY, $options->proxy_url);

        // 代理类型：SOCKS5 使用远端 DNS 解析（CURLPROXY_SOCKS5_HOSTNAME）避免 DNS 污染
        $proxyType = $options->proxy_type ?? 'http';
        if ($proxyType === 'socks5') {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
        } else {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        }

        // 代理认证（可选）
        if (!empty($options->proxy_username) && !empty($options->proxy_password)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $options->proxy_username . ':' . $options->proxy_password);
        }
    }
}