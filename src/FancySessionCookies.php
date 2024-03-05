<?php

declare(strict_types=1);

namespace Badcfe;

class FancySessionCookies
{
    public static function setName(bool $isSecure, string $path): void
    {
        session_name(self::getPrefixedName(self::getName(), $isSecure, $path));
    }

    public static function getName(): string
    {
        $sessionName = session_name();
        return $sessionName === false ? "" : $sessionName;
    }

    private static function getPrefixedName(string $name, bool $isSecure, string $path): string
    {
        if ((strpos($name, "__Host-") || strpos($name, "__Secure-")) === false) {
            if ($isSecure && $path == "/") {
                return "__Host-" . $name;
            } elseif ($isSecure) {
                return "__Secure-" . $name;
            }
        }
        return $name;
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @param string|false $id
     * @param array<string, mixed> $params
     * @param SameSite $sameSite
     * @return string
     */
    public static function buildCookieString(string $name, string|false $id, array $params, SameSite $sameSite): string
    {
        $cookieString = sprintf("%s=%s;", $name, $id);
        $secure = $params['secure'];
        if ($secure === true) {
            $cookieString .= " Secure;";
        }
        $path = $params['path'];
        if (is_string($path) && $path !== "") {
            $cookieString .= " Path=$path;";
        }
        $lifetime = $params['lifetime'];
        if (is_int($lifetime) && $lifetime > 0) {
            $cookieString .= " Max-Age=$lifetime;";
        }
        $httponly = $params['httponly'];
        if ($httponly === true) {
            $cookieString .= " HttpOnly;";
        }
        if ($sameSite === SameSite::None) {
            $cookieString .= " SameSite=None; Partitioned;";
        } else {
            $cookieString .= " SameSite=Lax;";
        }
        return $cookieString;
    }

    public static function startNewSession(): void
    {
        $params  = session_get_cookie_params();
        self::setName($params['secure'], $params['path']);
        session_start();
        $sameSite = SameSite::tryFrom($params['samesite']) ?? SameSite::Lax;
        header(
            sprintf(
                'Set-Cookie: %s',
                self::buildCookieString(
                    self::getName(),
                    session_id(),
                    $params,
                    $sameSite
                )
            )
        );
    }
}
