@extends('layouts.app')

@section('content')
<style>
    .chat-list-tools {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 14px;
    }
    .chat-search-form {
        width: 100%;
        max-width: 380px;
    }
    .chat-search-wrap {
        position: relative;
    }
    .chat-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        opacity: .7;
        pointer-events: none;
    }
    .chat-search-input {
        padding-left: 38px;
    }
    .chat-list {
        display: grid;
        gap: 12px;
    }
    .chat-card {
        display: block;
        border: 1px solid #dbe3ee;
        border-radius: 14px;
        background: #fff;
        padding: 14px;
        text-decoration: none;
        color: inherit;
        transition: box-shadow .16s ease, transform .16s ease;
    }
    .chat-card:hover {
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.10);
        transform: translateY(-1px);
        text-decoration: none;
        color: inherit;
    }
    .chat-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .chat-card-left {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-width: 0;
    }
    .chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        background: #eef2f7;
        border: 1px solid #dbe3ee;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
    }
    .chat-main {
        min-width: 0;
    }
    .chat-name {
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        margin: 0 0 2px;
    }
    .chat-context {
        font-size: .82rem;
        color: #64748b;
        line-height: 1.3;
        margin: 0;
    }
    .chat-time {
        color: #64748b;
        font-size: .8rem;
        white-space: nowrap;
        flex: 0 0 auto;
        margin-top: 2px;
    }
    .chat-preview {
        margin-top: 10px;
        color: #334155;
        font-size: .92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .chat-empty-search {
        display: none;
    }
</style>

<div class="container">
    <h2 class="mb-3">Chats</h2>

    <div class="chat-list-tools">
        <div class="chat-search-form">
            <div class="chat-search-wrap">
                <img src="{{ asset('icons/search-icon.svg') }}"
                     alt=""
                     width="18"
                     height="18"
                     class="chat-search-icon"
                     onerror="this.style.display='none'">
                <input type="text"
                       id="chat-list-search"
                       value="{{ $search ?? '' }}"
                       class="form-control chat-search-input"
                       placeholder="Search by name or property..."
                       aria-label="Search conversations">
            </div>
        </div>
    </div>

    @if($conversations->isEmpty())
        <div class="alert alert-secondary">
            {{ !empty($search) ? 'No conversations match your search.' : 'No conversations yet.' }}
        </div>
    @else
        <div class="chat-list" id="chat-list">
            @foreach($conversations as $conversation)
                @php($isTenant = auth()->id() === $conversation->tenant_id)
                @php($counterparty = $isTenant ? $conversation->agent : $conversation->tenant)
                @php($lastMessage = $conversation->latestMessage)
                @php($contextProperty = $conversation->property ?? data_get($lastMessage, 'property'))
                @php($updatedAt = $conversation->last_message_at ?? $conversation->created_at)
                @php($searchText = strtolower(trim(
                    ($counterparty->name ?? '') . ' ' .
                    ($counterparty->role ?? '') . ' ' .
                    (data_get($contextProperty, 'title', '')) . ' ' .
                    (data_get($lastMessage, 'message', ''))
                )))
                <a href="/messages/conversations/{{ $conversation->id }}"
                   class="chat-card"
                   data-chat-card
                   data-search-text="{{ $searchText }}"
                   aria-label="Open chat with {{ $counterparty->name }}">
                    <div class="chat-card-top">
                        <div class="chat-card-left">
                            <span class="chat-avatar">
                                <img src="{{ asset('icons/profile-icon.svg') }}"
                                     alt=""
                                     width="22"
                                     height="22"
                                     onerror="this.style.display='none'">
                            </span>
                            <div class="chat-main">
                                <p class="chat-name">{{ $counterparty->name }}</p>
                                <p class="chat-context">
                                    {{ ucfirst($counterparty->role) }}
                                    @if($contextProperty)
                                        · {{ $contextProperty->title }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="chat-time">
                            {{ $updatedAt?->setTimezone(session('tz', config('app.timezone')))->format('d M Y, H:i') }}
                        </div>
                    </div>
                    <div class="chat-preview">
                        {{ $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->message, 120) : 'No message yet' }}
                    </div>
                </a>
            @endforeach
        </div>
        <div id="chat-empty-search" class="alert alert-secondary mt-3 chat-empty-search">
            No conversations match your search.
        </div>
    @endif
</div>

<script>
    (function () {
        const searchInput = document.getElementById('chat-list-search');
        const cards = Array.from(document.querySelectorAll('[data-chat-card]'));
        const emptySearch = document.getElementById('chat-empty-search');
        if (!searchInput || cards.length === 0) {
            return;
        }

        const render = () => {
            const term = (searchInput.value || '').trim().toLowerCase();
            let visibleCount = 0;

            cards.forEach((card) => {
                const haystack = (card.dataset.searchText || '').toLowerCase();
                const matched = term === '' || haystack.includes(term);
                card.style.display = matched ? '' : 'none';
                if (matched) {
                    visibleCount += 1;
                }
            });

            if (emptySearch) {
                emptySearch.style.display = visibleCount === 0 ? '' : 'none';
            }
        };

        searchInput.addEventListener('keyup', render);
        render();
    })();
</script>
@endsection
