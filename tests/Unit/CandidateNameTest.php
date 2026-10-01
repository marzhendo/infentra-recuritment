<?php

namespace Tests\Unit;

use App\Models\Candidate;
use PHPUnit\Framework\TestCase;

class CandidateNameTest extends TestCase
{
    public function test_name_override()
    {
        $c = new Candidate(['name' => 'JOHN DOE', 'name_override' => 'Johnny Doe']);
        $this->assertEquals('Johnny Doe', $c->display_name);
    }

    public function test_all_uppercase_title_case()
    {
        $c = new Candidate(['name' => 'MUHAMMAD RIZKY']);
        $this->assertEquals('Muhammad Rizky', $c->display_name);
    }

    public function test_all_lowercase_title_case()
    {
        $c = new Candidate(['name' => 'muhammad rizky']);
        $this->assertEquals('Muhammad Rizky', $c->display_name);
    }

    public function test_mixed_case_unchanged()
    {
        $c = new Candidate(['name' => 'mUhaMmad riZky']);
        $this->assertEquals('mUhaMmad riZky', $c->display_name);
    }

    public function test_particles()
    {
        $c = new Candidate(['name' => 'AHMAD BIN ALI AL MUBARAK']);
        $this->assertEquals('Ahmad bin Ali al Mubarak', $c->display_name);
    }

    public function test_punctuation()
    {
        $c = new Candidate(['name' => "MUTA'ALYA-NISA"]);
        $this->assertEquals("Muta'Alya-Nisa", $c->display_name);
    }

    public function test_collapses_spaces()
    {
        $c = new Candidate(['name' => 'JOHN    DOE']);
        $this->assertEquals('John Doe', $c->display_name);
    }
}
