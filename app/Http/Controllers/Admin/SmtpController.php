<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportSchedule;
use App\Models\SmtpSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SmtpController extends Controller
{
    // ── SMTP Settings ─────────────────────────────────────────────────────

    // GET /admin/smtp — list semua (global + per-branch)
    public function index()
    {
        $settings = SmtpSetting::with('branch:id,name,code')
            ->orderByRaw('branch_id IS NULL DESC')
            ->get()
            ->map(function ($s) {
                $arr = $s->toArray();
                $arr['password'] = '••••••••'; // sembunyikan password di list
                return $arr;
            });

        return response()->json($settings);
    }

    // GET /admin/smtp/{smtp}
    public function show(SmtpSetting $smtp)
    {
        $data = $smtp->load('branch:id,name,code')->toArray();
        $data['password'] = '••••••••';
        return response()->json($data);
    }

    // POST /admin/smtp
    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'    => 'nullable|exists:branches,id',
            'driver'       => 'required|in:smtp,sendmail,mailgun',
            'host'         => 'required|string',
            'port'         => 'required|integer',
            'encryption'   => 'required|in:tls,ssl,none',
            'username'     => 'required|string',
            'password'     => 'required|string',
            'from_address' => 'required|email',
            'from_name'    => 'required|string|max:255',
            'is_active'    => 'boolean',
        ]);

        // Nonaktifkan SMTP lain untuk branch yang sama
        SmtpSetting::where('branch_id', $data['branch_id'] ?? null)
            ->update(['is_active' => false]);

        $smtp = SmtpSetting::create($data);
        return response()->json($smtp->load('branch:id,name,code'), 201);
    }

    // PUT /admin/smtp/{smtp}
    public function update(Request $request, SmtpSetting $smtp)
    {
        $data = $request->validate([
            'driver'       => 'sometimes|in:smtp,sendmail,mailgun',
            'host'         => 'sometimes|string',
            'port'         => 'sometimes|integer',
            'encryption'   => 'sometimes|in:tls,ssl,none',
            'username'     => 'sometimes|string',
            'password'     => 'sometimes|string',
            'from_address' => 'sometimes|email',
            'from_name'    => 'sometimes|string|max:255',
            'is_active'    => 'boolean',
        ]);

        $smtp->update($data);
        return response()->json($smtp->fresh('branch:id,name,code'));
    }

    // DELETE /admin/smtp/{smtp}
    public function destroy(SmtpSetting $smtp)
    {
        $smtp->delete();
        return response()->json(['message' => 'SMTP setting deleted']);
    }

    // POST /admin/smtp/{smtp}/test — kirim email test
    public function test(Request $request, SmtpSetting $smtp)
    {
        $request->validate(['to' => 'required|email']);

        // Override mail config dengan setting ini
        Config::set('mail.mailers.smtp.host', $smtp->host);
        Config::set('mail.mailers.smtp.port', $smtp->port);
        Config::set('mail.mailers.smtp.encryption', $smtp->encryption === 'none' ? null : $smtp->encryption);
        Config::set('mail.mailers.smtp.username', $smtp->username);
        Config::set('mail.mailers.smtp.password', $smtp->password);
        Config::set('mail.from.address', $smtp->from_address);
        Config::set('mail.from.name', $smtp->from_name);

        try {
            Mail::raw('Test email dari KINAYA POS — SMTP setting berhasil dikonfigurasi.', function ($msg) use ($request, $smtp) {
                $msg->to($request->to)
                    ->subject('Test SMTP - KINAYA POS')
                    ->from($smtp->from_address, $smtp->from_name);
            });

            return response()->json(['message' => 'Test email berhasil dikirim ke ' . $request->to]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 422);
        }
    }

    // ── Report Schedule ───────────────────────────────────────────────────

    // GET /admin/report-schedules
    public function scheduleIndex()
    {
        $schedules = ReportSchedule::with('branch:id,name,code')->get();
        return response()->json($schedules);
    }

    // GET /admin/report-schedules/{branch}
    public function scheduleShow(int $branchId)
    {
        $schedule = ReportSchedule::where('branch_id', $branchId)
            ->with('branch:id,name,code')
            ->firstOrNew(['branch_id' => $branchId]);

        return response()->json($schedule);
    }

    // POST/PUT /admin/report-schedules/{branch} — upsert
    public function scheduleUpsert(Request $request, int $branchId)
    {
        $data = $request->validate([
            'enabled'      => 'boolean',
            'send_at'      => 'required|date_format:H:i',
            'recipients'   => 'required|string',   // comma-separated emails
            'report_types' => 'array',
            'report_types.*' => 'in:sales,stocks,purchases,receivables,payables',
        ]);

        $schedule = ReportSchedule::updateOrCreate(
            ['branch_id' => $branchId],
            array_merge($data, ['branch_id' => $branchId])
        );

        return response()->json($schedule->load('branch:id,name,code'));
    }

    // DELETE /admin/report-schedules/{branch}
    public function scheduleDelete(int $branchId)
    {
        ReportSchedule::where('branch_id', $branchId)->delete();
        return response()->json(['message' => 'Report schedule deleted']);
    }
}
