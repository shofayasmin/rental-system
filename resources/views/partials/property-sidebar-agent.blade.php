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

    @if(!empty($activeContract))
        <div class="apply-rent-card">
            <div class="apply-rent-body">
                <h5 class="apply-rent-title">Active Rental Contract</h5>
                <p class="apply-rent-note mb-1"><strong>Tenant:</strong> {{ data_get($activeContract, 'rentalRequest.tenant.name', '-') }}</p>
                <p class="apply-rent-note mb-1"><strong>Start Date:</strong> {{ $activeContract->start_date }}</p>
                <p class="apply-rent-note mb-2"><strong>End Date:</strong> {{ $activeContract->end_date }}</p>

                <form method="POST" action="/agent/contracts/{{ $activeContract->id }}/end-rental">
                    @csrf
                    <div class="mb-2">
                        <label for="early-checkout-reason" class="form-label mb-1">
                            Early Checkout Reason (Required)
                        </label>
                        <textarea
                            id="early-checkout-reason"
                            name="reason"
                            class="form-control @error('reason') is-invalid @enderror"
                            rows="3"
                            required
                            minlength="10"
                            maxlength="1000"
                            placeholder="Explain why rental is ended early."
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit"
                            class="btn btn-danger w-100"
                            onclick="return confirm('Are you sure you want to end this rental early?');">
                        End Rental
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
