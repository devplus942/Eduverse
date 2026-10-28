<?php
declare(strict_types=1);

class Jitsi
{
    public static function roomName(string $courseCode): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $courseCode)) ?: 'class';
        return 'eduverse-' . $slug . '-' . bin2hex(random_bytes(6));
    }

    public static function url(string $room): string
    {
        $domain = preg_replace('/[^a-zA-Z0-9.\-]/', '', (string)Env::get('JITSI_DOMAIN', 'meet.jit.si'));
        return 'https://' . $domain . '/' . rawurlencode($room);
    }
}
