<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Candidate;
use App\Models\Division;
use App\Models\InterviewSlot;
use App\Models\User;
use App\Policies\DecisionPolicy;
use App\Policies\ScorePolicy;
use Illuminate\Contracts\Console\Kernel;

// Set up
$div1 = Division::factory()->create(['name' => 'Div 1']);
$div2 = Division::factory()->create(['name' => 'Div 2']);
$div3 = Division::factory()->create(['name' => 'Div 3']);

$candidate = Candidate::factory()->create(['pilihan_1_id' => $div1->id, 'pilihan_2_id' => $div2->id, 'is_hmif' => false]);
$slot = InterviewSlot::factory()->create(['candidate_id' => $candidate->id]);
$hmif = Candidate::factory()->create(['pilihan_1_id' => $div1->id, 'pilihan_2_id' => $div2->id, 'is_hmif' => true]);

$head = User::factory()->create(['is_head_interviewer' => true, 'name' => 'Head']);
$koor1 = User::factory()->create(['division_id' => $div1->id, 'is_head_interviewer' => false, 'name' => 'Koor Div 1']);
$koor2 = User::factory()->create(['division_id' => $div2->id, 'is_head_interviewer' => false, 'name' => 'Koor Div 2']);
$koor3 = User::factory()->create(['division_id' => $div3->id, 'is_head_interviewer' => false, 'name' => 'Koor Div 3']);
$admin = User::factory()->create(['is_head_interviewer' => false, 'division_id' => null, 'name' => 'Admin']);

$scorePolicy = new ScorePolicy;
$decisionPolicy = new DecisionPolicy;

$users = [$head, $koor1, $koor2, $koor3, $admin];

echo "| User | Score Normal | Score HMIF | Decide Div1 (Pil1) | Decide Div3 (Not Pil) |\n";
echo "|---|---|---|---|---|\n";

foreach ($users as $user) {
    $scoreNormal = $scorePolicy->create($user, $slot) ? 'Allowed' : 'Denied';
    // HMIF has no slot, but let's test if they could decide
    $decideDiv1 = $decisionPolicy->create($user, $candidate, $div1) ? 'Allowed' : 'Denied';
    $decideDiv3 = $decisionPolicy->create($user, $candidate, $div3) ? 'Allowed' : 'Denied';

    // HMIF decide
    $hmifDecide1 = $decisionPolicy->create($user, $hmif, $div1) ? 'Allowed' : 'Denied';

    echo "| {$user->name} | {$scoreNormal} | {$hmifDecide1} | {$decideDiv1} | {$decideDiv3} |\n";
}
