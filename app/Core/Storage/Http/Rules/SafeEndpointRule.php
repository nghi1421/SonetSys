<?php

declare(strict_types=1);

namespace App\Core\Storage\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blocks a tenant-configured S3 endpoint from pointing at loopback/private/
 * link-local/reserved network ranges (including cloud metadata endpoints
 * like 169.254.169.254) — the server itself connects to this URL on every
 * upload/read/delete, so an unrestricted value is a straightforward SSRF
 * vector even though the actor is only "trusted" for their own tenant.
 *
 * This checks DNS resolution at save time; it does not re-resolve on every
 * request, so it does not defend against DNS rebinding (a host that
 * resolves to a public IP now and an internal one later). That deeper
 * mitigation is not implemented here.
 */
final class SafeEndpointRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = parse_url((string) $value, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        $ips = $this->resolve($host);

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $fail('The :attribute must not point to a private or internal network address.');

                return;
            }
        }
    }

    /**
     * @return list<string>
     */
    private function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ipv4 = @gethostbynamel($host);

        return $ipv4 !== false ? $ipv4 : [];
    }
}
