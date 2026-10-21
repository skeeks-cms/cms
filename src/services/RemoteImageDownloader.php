<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\services;

/**
 * Downloads a picture by URL for the rich-text editor: text copied from
 * another site brings its pictures to us as if they were chosen from a disk.
 *
 * The URL comes from a browser clipboard, so it is untrusted: only http(s)
 * on standard ports, every address of the host must be public (no loopback,
 * private, link-local or reserved ranges), the checked address is pinned for
 * the connection (no DNS rebinding), redirects are followed by hand and
 * checked again, the size and time are limited, and only raster images by
 * their content are accepted.
 */
class RemoteImageDownloader
{
    public const MAX_SIZE = 15 * 1024 * 1024;

    private const MAX_REDIRECTS = 3;
    private const TIMEOUT = 15;

    /**
     * @return array bytes, name, type
     * @throws \RuntimeException with a message for the employee
     */
    public static function download(string $url): array
    {
        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            [$host, $port, $ip] = self::target($url);
            $response = self::fetch($url, $host, $port, $ip);
            if ($response['location'] === null) {
                break;
            }
            $url = self::absolute($response['location'], $url);
        }
        if ($response['location'] !== null) {
            throw new \RuntimeException('Слишком много перенаправлений.');
        }
        if ($response['status'] !== 200) {
            throw new \RuntimeException('Сайт ответил кодом '.$response['status'].'.');
        }
        $bytes = $response['body'];
        $type = (string)(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (!TemporaryUploads::isImage($type)) {
            throw new \RuntimeException('По ссылке не картинка.');
        }
        $name = rawurldecode(basename((string)parse_url($url, PHP_URL_PATH)));
        $extension = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'][$type];
        $name = preg_replace('/\.[a-z0-9]{1,5}$/i', '', $name) ?: 'картинка';

        return ['bytes' => $bytes, 'name' => mb_substr($name, 0, 100).'.'.$extension, 'type' => $type];
    }

    /** @return array host, port and a checked public IP address */
    private static function target(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower(trim((string)($parts['host'] ?? ''), '[]'));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new \RuntimeException('Неподходящая ссылка на картинку.');
        }
        $port = (int)($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if (!in_array($port, [80, 443], true)) {
            throw new \RuntimeException('Неподходящая ссылка на картинку.');
        }
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : self::resolve($host);
        if (!$ips) {
            throw new \RuntimeException('Сайт картинки не найден.');
        }
        foreach ($ips as $ip) {
            if (!self::isPublic($ip)) {
                throw new \RuntimeException('Картинки с внутренних адресов не загружаются.');
            }
        }

        return [$host, $port, $ips[0]];
    }

    /** @return string[] IPv4 and IPv6 addresses of a host */
    private static function resolve(string $host): array
    {
        $ips = [];
        foreach ((array)@dns_get_record($host, DNS_A | DNS_AAAA) as $record) {
            $ips[] = $record['ip'] ?? $record['ipv6'] ?? null;
        }
        if (!$ips) {
            $ips = (array)@gethostbynamel($host);
        }

        return array_values(array_unique(array_filter($ips)));
    }

    public static function isPublic(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        // Ranges the PHP flags do not cover: carrier-grade NAT, benchmarking, IPv4-mapped and unique local IPv6.
        $long = ip2long($ip);
        if ($long !== false) {
            return !(($long & 0xFFC00000) === ip2long('100.64.0.0') || ($long & 0xFFFE0000) === ip2long('198.18.0.0'));
        }
        $ip = strtolower($ip);

        return strpos($ip, '::ffff:') !== 0 && !preg_match('/^f[cd]/', $ip) && !preg_match('/^fe[89ab]/', $ip);
    }

    private static function fetch(string $url, string $host, int $port, string $ip): array
    {
        $body = '';
        $tooBig = false;
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RESOLVE => [$host.':'.$port.':'.(strpos($ip, ':') !== false ? '['.$ip.']' : $ip)],
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SkeekS CMS image import)',
            CURLOPT_HTTPHEADER => ['Accept: image/webp,image/png,image/jpeg,image/gif,image/*;q=0.8'],
            CURLOPT_ENCODING => '',
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body, &$tooBig) {
                if (strlen($body) + strlen($chunk) > self::MAX_SIZE) {
                    $tooBig = true;
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $location = curl_getinfo($curl, CURLINFO_REDIRECT_URL) ?: null;
        $error = curl_errno($curl);
        curl_close($curl);
        if ($tooBig) {
            throw new \RuntimeException('Картинка больше '.(self::MAX_SIZE / 1024 / 1024).' МБ.');
        }
        if ($error && !$status) {
            throw new \RuntimeException('Сайт картинки не ответил.');
        }

        return ['status' => $status, 'location' => $status >= 300 && $status < 400 ? $location : null, 'body' => $body];
    }

    private static function absolute(string $location, string $base): string
    {
        if (preg_match('~^https?://~i', $location)) {
            return $location;
        }
        $parts = parse_url($base);
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        if (strpos($location, '//') === 0) {
            return $parts['scheme'].':'.$location;
        }
        if (strpos($location, '/') === 0) {
            return $origin.$location;
        }

        return $origin.rtrim(dirname((string)($parts['path'] ?? '/')), '/').'/'.$location;
    }
}
