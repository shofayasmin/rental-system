<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Property;
use App\Models\RentalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function list(Request $request)
    {
        $userId = Auth::id();
        $search = trim((string) $request->query('q', ''));

        $conversations = Conversation::with([
                'tenant',
                'agent',
                'property',
                'latestMessage.sender',
                'latestMessage.property',
                'latestMessage.rentalRequest',
            ])
            ->where(function ($query) use ($userId) {
                $query->where('tenant_id', $userId)
                    ->orWhere('agent_id', $userId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($inner) use ($like) {
                    $inner->whereHas('tenant', function ($q) use ($like) {
                        $q->where('name', 'like', $like);
                    })->orWhereHas('agent', function ($q) use ($like) {
                        $q->where('name', 'like', $like);
                    })->orWhereHas('property', function ($q) use ($like) {
                        $q->where('title', 'like', $like);
                    })->orWhereHas('latestMessage', function ($q) use ($like) {
                        $q->where('message', 'like', $like);
                    })->orWhereHas('latestMessage.property', function ($q) use ($like) {
                        $q->where('title', 'like', $like);
                    });
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        return view('messages.list', [
            'conversations' => $conversations,
            'search' => $search,
        ]);
    }

    public function index(RentalRequest $rentalRequest)
    {
        $rentalRequest->loadMissing('property');
        $this->authorizeRentalRequestChat($rentalRequest);

        $conversation = $this->findOrCreateByRentalRequest($rentalRequest);

        return $this->renderConversation(
            $conversation,
            '/messages/' . $rentalRequest->id,
            [
                'property_id' => $rentalRequest->property_id,
                'rental_request_id' => $rentalRequest->id,
            ]
        );
    }

    public function store(Request $request, RentalRequest $rentalRequest)
    {
        $rentalRequest->loadMissing('property');
        $this->authorizeRentalRequestChat($rentalRequest);

        $conversation = $this->findOrCreateByRentalRequest($rentalRequest);

        $this->storeMessage($conversation, $request, [
            'property_id' => $rentalRequest->property_id,
            'rental_request_id' => $rentalRequest->id,
        ]);

        return back();
    }

    public function indexByProperty(Property $property)
    {
        abort_unless(Auth::user()->role === 'tenant', 403);
        abort_if($property->agent_id === Auth::id(), 403);

        $conversation = $this->findOrCreateByPair(
            tenantId: Auth::id(),
            agentId: $property->agent_id,
            propertyId: $property->id,
            rentalRequestId: null
        );

        return $this->renderConversation(
            $conversation,
            '/messages/property/' . $property->id,
            ['property_id' => $property->id]
        );
    }

    public function storeByProperty(Request $request, Property $property)
    {
        abort_unless(Auth::user()->role === 'tenant', 403);
        abort_if($property->agent_id === Auth::id(), 403);

        $conversation = $this->findOrCreateByPair(
            tenantId: Auth::id(),
            agentId: $property->agent_id,
            propertyId: $property->id,
            rentalRequestId: null
        );

        $this->storeMessage($conversation, $request, [
            'property_id' => $property->id,
            'rental_request_id' => null,
        ]);

        return back();
    }

    public function showConversation(Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        return $this->renderConversation(
            $conversation,
            '/messages/conversations/' . $conversation->id,
            []
        );
    }

    public function storeConversation(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($conversation);
        $this->storeMessage($conversation, $request);

        return back();
    }

    private function renderConversation(Conversation $conversation, string $postUrl, array $context): \Illuminate\Contracts\View\View
    {
        $conversation->loadMissing([
            'tenant',
            'agent',
            'messages.sender',
            'messages.property',
            'messages.rentalRequest',
        ]);

        $counterparty = Auth::id() === $conversation->tenant_id
            ? $conversation->agent
            : $conversation->tenant;

        $activeContext = $this->resolveContextForConversation(
            $conversation,
            $context['property_id'] ?? null,
            $context['rental_request_id'] ?? null,
            false
        );

        return view('messages.index', [
            'conversation' => $conversation,
            'counterparty' => $counterparty,
            'postUrl' => $postUrl,
            'activeContext' => $activeContext,
        ]);
    }

    private function storeMessage(Conversation $conversation, Request $request, array $defaultContext = []): void
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'property_id' => 'nullable|integer',
            'rental_request_id' => 'nullable|integer',
        ]);

        $context = $this->resolveContextForConversation(
            $conversation,
            $validated['property_id'] ?? $defaultContext['property_id'] ?? null,
            $validated['rental_request_id'] ?? $defaultContext['rental_request_id'] ?? null,
            true
        );

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::id(),
            'message' => $validated['message'],
            'property_id' => $context['property_id'],
            'rental_request_id' => $context['rental_request_id'],
        ]);

        $conversation->update([
            'status' => 'open',
            'property_id' => $context['property_id'] ?? $conversation->property_id,
            'rental_request_id' => $context['rental_request_id'],
            'last_message_at' => now(),
        ]);
    }

    private function authorizeRentalRequestChat(RentalRequest $rentalRequest): void
    {
        $userId = Auth::id();
        $isTenant = $rentalRequest->tenant_id === $userId;
        $isAgent = $rentalRequest->property->agent_id === $userId;

        abort_unless($isTenant || $isAgent, 403);
    }

    private function authorizeConversation(Conversation $conversation): void
    {
        $userId = Auth::id();

        abort_unless(
            $conversation->tenant_id === $userId || $conversation->agent_id === $userId,
            403
        );
    }

    private function findOrCreateByRentalRequest(RentalRequest $rentalRequest): Conversation
    {
        return $this->findOrCreateByPair(
            tenantId: $rentalRequest->tenant_id,
            agentId: $rentalRequest->property->agent_id,
            propertyId: $rentalRequest->property_id,
            rentalRequestId: $rentalRequest->id
        );
    }

    private function findOrCreateByPair(int $tenantId, int $agentId, ?int $propertyId, ?int $rentalRequestId): Conversation
    {
        return DB::transaction(function () use ($tenantId, $agentId, $propertyId, $rentalRequestId) {
            $conversation = Conversation::where('tenant_id', $tenantId)
                ->where('agent_id', $agentId)
                ->lockForUpdate()
                ->first();

            if (!$conversation) {
                return Conversation::create([
                    'property_id' => $propertyId,
                    'tenant_id' => $tenantId,
                    'agent_id' => $agentId,
                    'rental_request_id' => $rentalRequestId,
                    'status' => 'open',
                ]);
            }

            $conversation->update([
                'status' => 'open',
                'property_id' => $propertyId ?? $conversation->property_id,
                'rental_request_id' => $rentalRequestId,
            ]);

            return $conversation;
        });
    }

    private function resolveContextForConversation(
        Conversation $conversation,
        ?int $propertyId,
        ?int $rentalRequestId,
        bool $strict
    ): array {
        if (!$propertyId && !$rentalRequestId) {
            return [
                'property_id' => null,
                'rental_request_id' => null,
                'property_title' => null,
            ];
        }

        if ($rentalRequestId) {
            $rentalRequest = RentalRequest::with('property:id,agent_id,title')
                ->find($rentalRequestId);

            if (!$rentalRequest) {
                abort_if($strict, 422, 'Rental request context not found.');
                return ['property_id' => null, 'rental_request_id' => null, 'property_title' => null];
            }

            $pairMatches = (
                $rentalRequest->tenant_id === $conversation->tenant_id
                && $rentalRequest->property
                && $rentalRequest->property->agent_id === $conversation->agent_id
            );

            if (!$pairMatches) {
                abort_if($strict, 422, 'Invalid rental request context for this conversation.');
                return ['property_id' => null, 'rental_request_id' => null, 'property_title' => null];
            }

            if ($propertyId && (int) $propertyId !== (int) $rentalRequest->property_id) {
                abort_if($strict, 422, 'Property context does not match rental request context.');
                return ['property_id' => null, 'rental_request_id' => null, 'property_title' => null];
            }

            return [
                'property_id' => $rentalRequest->property_id,
                'rental_request_id' => $rentalRequest->id,
                'property_title' => $rentalRequest->property->title ?? null,
            ];
        }

        $property = Property::query()->find($propertyId);

        if (!$property || $property->agent_id !== $conversation->agent_id) {
            abort_if($strict, 422, 'Invalid property context for this conversation.');
            return ['property_id' => null, 'rental_request_id' => null, 'property_title' => null];
        }

        return [
            'property_id' => $property->id,
            'rental_request_id' => null,
            'property_title' => $property->title,
        ];
    }
}
