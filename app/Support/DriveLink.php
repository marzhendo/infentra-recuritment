<?php

namespace App\Support;

class DriveLink
{
    public static function previewUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Handle open?id=X
        if (preg_match('/open\?id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://drive.google.com/file/d/{$matches[1]}/preview";
        }

        // Handle file/d/X/view or edit
        if (preg_match('/file\/d\/([a-zA-Z0-9_-]+)\/(?:view|edit)/', $url, $matches)) {
            return "https://drive.google.com/file/d/{$matches[1]}/preview";
        }

        return null;
    }
}
