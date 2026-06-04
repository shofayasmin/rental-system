@extends('layouts.app')

@section('content')
<style>
    body.chat-page-lock {
        overflow: hidden;
    }
    body.chat-page-lock > .no-print.text-center.text-muted.py-3 {
        position: sticky;
        bottom: 0;
        z-index: 1030;
        margin-top: 0 !important;
    }
    body.chat-page-lock > main.container {
        height: calc(100dvh - var(--chat-navbar-h, 72px) - var(--chat-footer-h, 56px));
        overflow: hidden;
        display: flex;
        flex-direction: column;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        padding-top: 16px;
        padding-bottom: 16px;
    }
    .chat-page-root {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }
    .chat-shell {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        margin-bottom: 0 !important;
    }
    .chat-messages {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding: 16px;
        overscroll-behavior: contain;
    }
    .chat-composer {
        position: sticky;
        bottom: 0;
        z-index: 5;
        background: #fff;
        border-top: 1px solid #e2e8f0;
        padding: 12px 16px;
    }
    .chat-message-input {
        resize: none;
        min-height: 44px;
        max-height: 120px;
        overflow-y: auto;
    }
</style>

<div class="chat-page-root">
    <h2 class="mb-4">Chat</h2>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="fw-semibold">Conversation with {{ $counterparty->name }}</div>
                <div class="text-muted small">{{ ucfirst($counterparty->role) }}</div>
            </div>

            @if(($activeContext['property_id'] ?? null) || ($activeContext['rental_request_id'] ?? null))
                <div class="text-end">
                    <div class="small text-muted">Sending with context</div>
                    <div class="d-flex flex-wrap gap-1 justify-content-end">
                        @if(($activeContext['property_id'] ?? null) && data_get($activeContext, 'property_title'))
                            <span class="badge text-bg-light border">
                                Property: {{ data_get($activeContext, 'property_title') }}
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-4 chat-shell">
        <div class="chat-messages" id="chat-box">
            @forelse($conversation->messages as $msg)
                @php($isMine = $msg->sender_id == auth()->id())
                <div class="d-flex {{ $isMine ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                    <div class="{{ $isMine ? 'bg-primary text-white' : 'bg-light border' }} p-3 rounded" style="max-width: 75%;">
                        <div class="fw-bold">
                            {{ $isMine ? 'You' : $msg->sender->name }}
                            <small class="{{ $isMine ? 'opacity-75' : 'text-muted' }}">
                                ({{ ucfirst($msg->sender->role) }})
                            </small>
                        </div>

                        @if($msg->property_id)
                            <div class="my-2 d-flex flex-wrap gap-1">
                                @if($msg->property_id)
                                    <span class="badge {{ $isMine ? 'text-bg-info' : 'text-bg-secondary' }}">
                                        Property: {{ optional($msg->property)->title ?? ('#' . $msg->property_id) }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <div>{{ $msg->message }}</div>
                        <div class="{{ $isMine ? 'text-end small opacity-75' : 'text-muted small' }}">
                            {{ $msg->created_at
                                ->setTimezone(session('tz', config('app.timezone')))
                                ->format('d M Y, H:i') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted">No messages yet.</div>
            @endforelse
        </div>

        <div class="chat-composer">
            <form method="POST" action="{{ $postUrl }}">
                @csrf

                @if($activeContext['property_id'] ?? null)
                    <input type="hidden" name="property_id" value="{{ $activeContext['property_id'] }}">
                @endif
                @if($activeContext['rental_request_id'] ?? null)
                    <input type="hidden" name="rental_request_id" value="{{ $activeContext['rental_request_id'] }}">
                @endif

                <div class="d-flex gap-2 align-items-end">
                    <textarea name="message"
                              rows="1"
                              class="form-control chat-message-input"
                              id="chat-message-input"
                              placeholder="Type your message..."
                              aria-label="Type your message"
                              required></textarea>
                    <button class="btn btn-primary" aria-label="Send message">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.add('chat-page-lock');
        const navbar = document.querySelector('nav.navbar');
        const footer = document.querySelector('body > .no-print.text-center.text-muted.py-3');
        if (navbar) {
            const navbarHeight = Math.ceil(navbar.getBoundingClientRect().height);
            document.documentElement.style.setProperty('--chat-navbar-h', navbarHeight + 'px');
        }
        if (footer) {
            const footerHeight = Math.ceil(footer.getBoundingClientRect().height);
            document.documentElement.style.setProperty('--chat-footer-h', footerHeight + 'px');
        }

        const chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        const messageInput = document.getElementById('chat-message-input');
        if (messageInput) {
            const autoResize = () => {
                messageInput.style.height = 'auto';
                messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
            };
            messageInput.addEventListener('input', autoResize);
            autoResize();
        }

        window.addEventListener('pagehide', () => {
            document.body.classList.remove('chat-page-lock');
            document.documentElement.style.removeProperty('--chat-navbar-h');
            document.documentElement.style.removeProperty('--chat-footer-h');
        });
    });
</script>
@endsection
