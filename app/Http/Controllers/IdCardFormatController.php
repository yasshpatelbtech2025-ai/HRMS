<?php

namespace App\Http\Controllers;

use App\Models\Firm;
use App\Support\IdCardFormats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Company › ID Card Formats: the card designs, with the one employee ID
 * cards are printed in.
 */
class IdCardFormatController extends Controller
{
    public function index(): View
    {
        $firm = Firm::current();
        $selected = IdCardFormats::selected($firm);

        return view('companies.id-card-formats', [
            'firm' => $firm,
            'selected' => $selected,
            // The chosen design first, then the rest.
            'formats' => IdCardFormats::all()->except($selected['id'])->values(),
            'sample' => IdCardFormats::sample($firm),
        ]);
    }

    public function select(Request $request): RedirectResponse
    {
        $data = $request->validate(['format' => ['required', 'integer', Rule::in(IdCardFormats::all()->keys()->all())]]);
        $firm = Firm::current();
        if (! $firm->exists) {
            $firm->establishment_name = config('app.name');
        }
        $firm->id_card_format = (int) $data['format'];
        $firm->save();

        return redirect()->route('company.id-card-formats')->with('status', __('ID card design :id selected; employee ID cards now print in it.', ['id' => $data['format']]));
    }
}
