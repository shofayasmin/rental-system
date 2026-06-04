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
            <a href="/messages/property/{{ $property->id }}" class="btn btn-success">
                Chat Agent
            </a>
        </div>
    </div>

    @guest
        <div class="apply-rent-card">
            <div class="apply-rent-body">
                <h5 class="apply-rent-title">Apply for Rent</h5>
                <p class="apply-rent-note">Please login first to submit your rental request.</p>
                <a href="/login" class="btn btn-outline-primary w-100">Login to Apply</a>
            </div>
        </div>
    @endguest

    @auth
        @if($property->status === 'to-let')
            <div class="apply-rent-card">
                <div class="apply-rent-body">
                    <h5 class="apply-rent-title">Apply for Rent</h5>
                    <form method="POST" action="/tenant/properties/{{ $property->id }}/request">
                        @csrf
                        <button class="btn btn-primary w-100">Apply Rent</button>
                    </form>
                </div>
            </div>
        @else
            <div class="apply-rent-card">
                <div class="apply-rent-body">
                    <h5 class="apply-rent-title mb-2">Apply for Rent</h5>
                    <p class="apply-rent-note mb-0">
                        Applications are disabled while this property is {{ str_replace('-', ' ', $property->status) }}.
                    </p>
                </div>
            </div>
        @endif
    @endauth
</div>
