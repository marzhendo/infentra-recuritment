<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Support\DriveLink;

class DriveLinkTest extends TestCase
{
    public function test_it_converts_open_id_links()
    {
        $this->assertEquals(
            'https://drive.google.com/file/d/1ABC123_xyz/preview',
            DriveLink::previewUrl('https://drive.google.com/open?id=1ABC123_xyz')
        );
    }

    public function test_it_converts_file_d_view_links()
    {
        $this->assertEquals(
            'https://drive.google.com/file/d/1ABC123_xyz/preview',
            DriveLink::previewUrl('https://drive.google.com/file/d/1ABC123_xyz/view?usp=sharing')
        );
    }

    public function test_it_converts_file_d_edit_links()
    {
        $this->assertEquals(
            'https://drive.google.com/file/d/1ABC123_xyz/preview',
            DriveLink::previewUrl('https://drive.google.com/file/d/1ABC123_xyz/edit')
        );
    }

    public function test_it_returns_null_for_invalid_or_empty_links()
    {
        $this->assertNull(DriveLink::previewUrl(''));
        $this->assertNull(DriveLink::previewUrl(null));
        $this->assertNull(DriveLink::previewUrl('https://google.com'));
    }
}
