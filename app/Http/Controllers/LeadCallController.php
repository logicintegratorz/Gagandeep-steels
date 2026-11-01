<?php
namespace App\Http\Controllers;

use App\Models\{Lead, LeadActivity};
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class LeadCallController
{
    public function start(Request $request, Lead $lead)
    {
        $this->access($request, $lead);
        abort_if($this->phone($lead) === '', 422, 'This lead has no phone number.');
        // Opening the call window must not lock the lead page or reopen the
        // window after an unrelated action such as a stage update.
        $request->session()->forget('active_lead_call_id');

        return response()->json(['locked' => false, 'lead_url' => route('leads.show', $lead)]);
    }

    public function qr(Request $request, Lead $lead)
    {
        $this->access($request, $lead);
        $phone = $this->phone($lead);
        abort_if($phone === '', 404, 'This lead has no phone number.');

        $qrCode = new QrCode(
            data: 'tel:'.$phone,
            encoding: new Encoding('ISO-8859-1'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 280,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $result = (new SvgWriter())->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function outcome(Request $request, Lead $lead)
    {
        $this->access($request, $lead);
        $data = $request->validate([
            'outcome' => ['required', Rule::in(['Connected', 'Unanswered'])],
            'details' => ['required_if:outcome,Connected', 'nullable', 'string', 'max:20000'],
            'request_key' => ['required', 'uuid'],
            'dashboard' => ['nullable', 'boolean'],
            'stay_dashboard' => ['nullable', 'boolean'],
            'return_to_dashboard' => ['nullable', 'boolean'],
            'return_filter' => ['nullable', Rule::in(['archive'])],
        ]);

        DB::transaction(function () use ($request, $lead, $data) {
            $locked = Lead::lockForUpdate()->findOrFail($lead->id);
            $this->access($request, $locked);
            if (LeadActivity::withTrashed()->where('request_key', $data['request_key'])->exists()) return;
            $locked->activities()->create([
                'actor' => $request->user()->name,
                'actor_id' => $request->user()->id,
                'kind' => 'Call log',
                'action' => 'Outgoing call: '.$data['outcome'],
                'details' => trim($data['details'] ?? ''),
                'request_key' => $data['request_key'],
            ]);
        });

        $request->session()->forget('active_lead_call_id');

        // "Exit" from the dashboard saves the call without opening the lead.
        // The regular Submit action continues to the exact lead that was called.
        if ($request->boolean('dashboard') && $request->boolean('stay_dashboard')) return redirect()->route('home')->with('success', 'Call activity saved.');
        if ($data['outcome'] === 'Connected' && $request->boolean('return_to_dashboard')) {
            $dashboardUrl = route('home', array_filter([
                'contacts' => $lead->id,
                'contacts_filter' => ($data['return_filter'] ?? null) === 'archive' ? 'touched' : null,
            ]));

            return redirect()->to($dashboardUrl.'#contacts-table')->with('success', 'Call activity saved.');
        }
        return redirect()->to(route('leads.show', [$lead, 'activity' => 'Call log', 'call_filter' => 'outgoing']).'#lead-history');
    }

    private function access(Request $request, Lead $lead): void
    {
        abort_unless(Lead::visibleTo($request->user())->whereKey($lead->id)->exists(), 404);
    }

    private function phone(Lead $lead): string
    {
        $phone = preg_replace('/[^\d+]/', '', (string) $lead->phone);
        return preg_replace('/(?!^)\+/', '', $phone) ?? '';
    }
}
