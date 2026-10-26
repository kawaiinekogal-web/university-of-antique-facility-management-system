<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\MaintenanceTicket;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MaintenanceTicketController extends Controller
{
    public function __construct(protected NotificationService $notifications)
    {
    }

    public function index()
    {
        $tickets = MaintenanceTicket::with('facility')
            ->where('requester_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('org.maintenance-tickets.index', compact('tickets'));
    }

    public function create()
    {
        $facilities = Facility::where('is_active', true)->orderBy('name')->get();

        return view('org.maintenance-tickets.create', compact('facilities'));
    }

    public function store(Request $request)
    {
        $request->merge(['requester_last_name' => auth()->user()->last_name]);

        $validated = $request->validate([
            'requester_last_name' => ['required', 'string', 'max:120'],
            'facility_id' => ['required', Rule::exists('facilities', 'id')->where('is_active', true)],
            'subject' => ['required', 'string', 'max:255'],
            'issue_description' => ['required', 'string', 'max:10000'],
        ], [
            'requester_last_name.required' => 'Update your account with a separate last name before submitting a maintenance request.',
        ]);
        unset($validated['requester_last_name']);

        do {
            $ticketCode = 'RM-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (MaintenanceTicket::where('ticket_code', $ticketCode)->exists());

        $ticket = MaintenanceTicket::create([
            ...$validated,
            'ticket_code' => $ticketCode,
            'requester_id' => auth()->id(),
            'request_method' => 'email',
            'status' => 'notified',
            'requested_at' => now(),
        ]);

        $ticketData = ['ticket_id' => $ticket->id, 'ticket_code' => $ticket->ticket_code];
        $this->notifications->notifyUser(
            auth()->id(),
            'maintenance_ticket_submitted',
            'Repair request sent',
            "Your repair and maintenance request ({$ticketCode}) was sent to the administrators.",
            $ticketData
        );
        $this->notifications->notifyAdmins(
            'maintenance_ticket_submitted',
            'New repair request',
            "{$ticketCode}: {$ticket->subject} was submitted by " . auth()->user()->name . '.',
            $ticketData
        );

        return redirect()->route('org.maintenance-tickets.show', $ticket)
            ->with('status', "Request sent. Track it with code {$ticketCode}.");
    }

    public function show(MaintenanceTicket $ticket)
    {
        abort_unless($ticket->requester_id === auth()->id(), 404);

        $ticket->load('facility');

        return view('org.maintenance-tickets.show', compact('ticket'));
    }
}
