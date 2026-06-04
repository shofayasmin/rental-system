<div class="detail-sidebar">
    <div class="agent-contact-card">
        <div class="agent-contact-header">
            <img src="{{ asset('icons/profile-icon.svg') }}"
                 alt="Agent profile icon"
                 class="agent-contact-avatar"
                 onerror="this.style.display='none'">
            <div class="agent-contact-meta">
                <div class="agent-contact-name">{{ $property->agent->name }}</div>
                <div class="agent-contact-role">Property Agent</div>
                <p class="agent-contact-email">{{ $property->agent->email }}</p>
                @if($property->agent->phone)
                    <p class="agent-contact-email mb-0">{{ $property->agent->phone }}</p>
                @endif
            </div>
        </div>
        <div class="agent-contact-actions">
            <a href="/agent/properties/{{ $property->id }}/edit" class="btn btn-warning text-dark">Edit Property</a>
        </div>
    </div>
</div>
