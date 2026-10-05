<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with([
            'order.customer:id,customer_code,full_name,phone,address,subcity',
            'order.items.service:id,name_en,name_am',
            'team:id,team_name,phone',
        ]);

        if ($view = $request->input('view')) {
            if ($view === 'today') {
                $query->whereDate('appointment_date', Carbon::today());
            } elseif ($view === 'week') {
                $query->whereBetween('appointment_date', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
            } elseif ($view === 'month') {
                $query->whereBetween('appointment_date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
            }
        } elseif ($date = $request->input('date')) {
            $query->whereDate('appointment_date', $date);
        }

        if ($teamId = $request->input('team_id')) {
            $query->where('cleaning_team_id', $teamId);
        }

        $appointments = $query->orderBy('appointment_date')->orderBy('start_time')->get();

        $appointments->transform(function ($appt) {
            $eth = EthiopianCalendarService::toEthiopian($appt->appointment_date);
            $appt->eth_date = $eth['formatted_am'];
            $appt->eth_date_en = $eth['formatted_en'];
            return $appt;
        });

        return response()->json($appointments);
    }

    public function reschedule(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'appointment_date' => 'required|date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'cleaning_team_id' => 'nullable|exists:cleaning_teams,id',
            'notes' => 'nullable|string',
        ]);

        $appointment->update($validated);

        if ($appointment->order) {
            $appointment->order->update([
                'appointment_date' => $validated['appointment_date'],
                'order_status' => 'rescheduled',
            ]);
        }

        return response()->json([
            'message' => 'Appointment rescheduled successfully',
            'appointment' => $appointment->fresh(['team', 'order.customer']),
        ]);
    }
}
