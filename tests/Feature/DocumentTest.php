<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DocumentAssignment;
use App\Models\DocumentFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_slots_upload_assign_and_delete(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['name' => 'Srympex']);
        $this->actingAs($user);
        $pi = Company::factory()->create(['establishment_name' => 'PI Industry']);
        Company::factory()->create(['establishment_name' => 'Amneal']);
        $slot = "agreement-{$pi->id}-pi-industry-agreement";

        $this->get(route('documents.index'))
            ->assertOk()
            ->assertSeeInOrder(['Select Documents', 'All Documents', 'AGREEMENT', 'PF', 'PT', 'ESIC', 'INSURANCE'])
            ->assertSeeInOrder(['AGREEMENT', 'Amneal AGREEMENT', 'PI Industry AGREEMENT', 'PROVIDENT FUND (PF)', 'PF REGISTRATION CERTIFICATE',
                'PROFESSIONAL TAX (PT)', 'Amneal PROFESSIONAL REGISTRATION FORM (FORM-3)', 'PI Industry PROFESSIONAL REGISTRATION FORM (FORM-3)',
                'EMPLOYEES STATE INSURANCE CORPORATION (ESIC)', 'ESIC REGISTRATION FORM', 'INSURANCE', 'WC POLICY DETAILS'])
            ->assertSee('Assigned To:');

        $this->post(route('documents.upload', $slot), ['files' => [UploadedFile::fake()->create('agreement.pdf', 200, 'application/pdf'), UploadedFile::fake()->image('page2.jpg')]])
            ->assertSessionHasNoErrors()->assertSessionHas('status', '2 files uploaded to PI Industry AGREEMENT.');
        $this->post(route('documents.upload', $slot), ['files' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors('files.0');
        $this->post(route('documents.upload', 'made-up-slot'), ['files' => [UploadedFile::fake()->create('a.pdf', 10)]])->assertNotFound();

        $file = DocumentFile::where('original_name', 'agreement.pdf')->sole();
        $this->assertSame($pi->id, $file->company_id);
        $this->assertSame('agreement', $file->category);
        Storage::disk('local')->assertExists($file->path);

        $this->get(route('documents.index'))->assertSee('2 files')->assertSee('agreement.pdf');
        $this->get(route('documents.show', $slot))->assertOk()->assertSeeInOrder(['AGREEMENT', 'PI Industry AGREEMENT', 'agreement.pdf', 'Srympex']);
        $this->get(route('documents.file', $file))->assertOk();

        $this->put(route('documents.assign', $slot), ['user_id' => $user->id])->assertSessionHas('status');
        $this->assertSame($user->id, DocumentAssignment::where('slot', $slot)->value('user_id'));
        $this->put(route('documents.assign', $slot), ['user_id' => ''])->assertSessionHas('status');
        $this->assertFalse(DocumentAssignment::exists());

        $this->delete(route('documents.destroy', $file))->assertSessionHas('status');
        Storage::disk('local')->assertExists($file->path); // kept until the record is pruned
        $this->assertSoftDeleted($file);
    }
}
