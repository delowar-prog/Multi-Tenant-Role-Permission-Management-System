<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{

    public function index()
    {
        return SupportTicket::with(['messages','user'])->visibleTo(auth()->user())->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:support_categories,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
            'ticket_no' => 'unique',
        ]);

        $authUser = auth()->user();
        $lastTicket = SupportTicket::max('ticket_no');

        $ticketNo = $lastTicket ? $lastTicket + 1 : 1;

        // Ticket create
        $ticket = SupportTicket::create([
            'tenant_id' => $authUser->tenant_id, // multi-tenant হলে
            'user_id' => auth()->id(),
            'branch_id' => $authUser->active_branch_id,
            'category_id' => $validated['category_id'],
            'ticket_no' => $ticketNo,
            'subject' => $validated['subject'],
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {

            foreach ($request->file('attachments') as $file) {

                $path = $file->store('tickets', 'public');

                $attachments[] = [
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                ];
            }
        }

        $message = SupportMessage::create([
            'ticket_id'   => $ticket->id,
            'message'     => $validated['message'],
            'sender_id'     => auth()->id(),
            'sender_type' => $authUser->tenant_id ? 'tenant_user' : 'support',
            'attachments' => $attachments, // JSON হিসেবে save হবে
        ]);

        return response()->json([
            'message' => 'Ticket created successfully'
        ], 201);
    }

    public function show(string $id){
        return SupportTicket::with('messages')->findOrFail($id);
    }
}
