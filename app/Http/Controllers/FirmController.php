<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Firm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Company › Firm: the contractor firm's establishment details, owners and
 * factory manager, stamp / signature images and mail setup.
 */
class FirmController extends Controller
{
    /** The images that can be uploaded: field => [column, label]. */
    public const IMAGES = [
        'logo' => ['logo_path', 'Logo'],
        'stamp' => ['stamp_path', 'Stamp image'],
        'signature' => ['signature_path', 'Signature image'],
        'stamp_signature' => ['stamp_signature_path', 'Stamp and signature image'],
    ];

    public function show(): View
    {
        // Address suggestions: the states list and what companies already use.
        $choices = fn (string $column, array $defaults = []) => collect($defaults)->merge(Company::whereNotNull($column)->distinct()->pluck($column))
            ->map(fn ($v) => trim($v))->filter()->unique(fn ($v) => mb_strtolower($v))->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        return view('companies.firm', [
            'firm' => Firm::current(),
            'states' => $choices('state', CompanyController::STATES),
            'districts' => $choices('district'),
            'talukas' => $choices('taluka'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $text = ['nullable', 'string', 'max:255'];
        $person = collect(Firm::PERSON_FIELDS)->keys()->mapWithKeys(fn ($f) => [$f => match ($f) {
            'dob', 'position_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'email' => ['nullable', 'email', 'max:255'],
            default => $text,
        }]);

        $data = $request->validate([
            'establishment_name' => ['required', 'string', 'max:255'],
            'sector' => ['nullable', Rule::in(Firm::SECTORS)],
            'organization_type' => ['nullable', Rule::in(Firm::ORGANIZATION_TYPES)],
            'establishment_category' => ['nullable', Rule::in(Firm::CATEGORIES)],
            'premise_no' => $text, 'pin_code' => ['nullable', 'regex:/^\d{6}$/'],
            'registered_on' => ['nullable', 'date'], 'commenced_on' => ['nullable', 'date'],
            'registration_no' => $text, 'is_factory_registered' => ['boolean'], 'pan' => ['nullable', 'regex:/^[A-Za-z]{5}\d{4}[A-Za-z]$/'],
            'address_line_1' => $text, 'address_line_2' => $text, 'village' => $text, 'state' => $text, 'district' => $text, 'taluka' => $text,
            'nearest_police_station' => $text,
            'logo' => ['nullable', 'image', 'max:2048'], 'stamp' => ['nullable', 'image', 'max:2048'], 'signature' => ['nullable', 'image', 'max:2048'], 'stamp_signature' => ['nullable', 'image', 'max:2048'],
            'owners' => ['nullable', 'array', 'max:20'],
            'owners.*.is_primary' => ['boolean'], 'owners.*.is_factory_manager' => ['boolean'],
            'factory_manager' => ['nullable', 'array'],
            'mail_protocol' => $text, 'mail_host' => $text, 'mail_port' => ['nullable', 'integer', 'between:1,65535'],
            'mail_username' => ['nullable', 'string', 'max:255'], 'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_type' => ['required', Rule::in(['html', 'text'])],
        ] + $person->mapWithKeys(fn ($rules, $f) => ["owners.*.{$f}" => $rules, "factory_manager.{$f}" => $rules])->all(), [], [
            'pan' => 'PAN', 'pin_code' => 'PIN code', 'owners.*.pan' => 'owner PAN', 'owners.*.email' => 'owner email',
        ]);

        $firm = Firm::current();
        $clean = fn (?array $person) => collect($person ?? [])->only([...array_keys(Firm::PERSON_FIELDS), 'is_primary', 'is_factory_manager'])
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)->filter(fn ($v) => $v !== null && $v !== '')->all();

        $firm->fill(collect($data)->except(['logo', 'stamp', 'signature', 'stamp_signature', 'owners', 'factory_manager', 'mail_password'])->all());
        $firm->pan = filled($data['pan'] ?? null) ? strtoupper($data['pan']) : null;
        $firm->is_factory_registered = $request->boolean('is_factory_registered');
        $firm->owners = collect($data['owners'] ?? [])->map($clean)->filter(fn ($o) => ($o['first_name'] ?? '') !== '' || ($o['last_name'] ?? '') !== '')->values()->all();
        $firm->factory_manager = $clean($data['factory_manager'] ?? null);
        if (filled($data['mail_password'] ?? null)) {
            $firm->mail_password = $data['mail_password']; // left blank: keep the saved one
        }

        $disk = Storage::disk(config('filesystems.employee_files'));
        foreach (self::IMAGES as $field => [$column]) {
            if ($request->hasFile($field)) {
                if ($firm->{$column}) {
                    $disk->delete($firm->{$column});
                }
                $file = $request->file($field);
                $firm->{$column} = $disk->putFileAs('firm', $file, $field.'-'.Str::random(8).'.'.$file->guessExtension());
            }
        }

        $firm->save();

        return redirect()->route('company.firm', ['step' => $request->input('step')])->with('status', __('Firm details saved.'));
    }

    public function image(string $image): StreamedResponse
    {
        $column = self::IMAGES[$image][0] ?? abort(404);
        $firm = Firm::query()->first();
        $disk = Storage::disk(config('filesystems.employee_files'));
        abort_unless($firm?->{$column} && $disk->exists($firm->{$column}), 404);

        return $disk->response($firm->{$column});
    }

    /**
     * Send a test email to the signed-in user through the saved mail setup.
     */
    public function testMail(Request $request): RedirectResponse
    {
        $firm = Firm::query()->first();
        if (! $firm?->mailConfig()) {
            return back()->with('error', __('Save the mail setup (host, user and password) first.'));
        }

        try {
            config(['mail.mailers.firm-test' => $firm->mailConfig()]);
            Mail::mailer('firm-test')->raw(__('This is a test email from the HRMS mail setup of :firm.', ['firm' => $firm->establishment_name]), function ($message) use ($request, $firm) {
                $message->to($request->user()->email)->from($firm->mail_username, $firm->establishment_name)->subject(__('HRMS mail setup test'));
            });
        } catch (Throwable $e) {
            return back()->with('error', __('The test email could not be sent: :reason', ['reason' => Str::limit($e->getMessage(), 200)]));
        }

        return back()->with('status', __('Test email sent to :email.', ['email' => $request->user()->email]));
    }
}
