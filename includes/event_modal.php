<div id="eventModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Create New Event</h2>
            <button class="close-modal-btn" onclick="closeEventModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="eventForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="eventId">

                <div class="form-grid">
                    <div class="form-group span-2">
                        <label>Event Name</label>
                        <input type="text" name="event_name" id="eventName" required placeholder="e.g. Global Tech Summit 2024">
                    </div>

                    <div class="form-group">
                        <label>Event Type</label>
                        <select name="event_type" id="eventType">
                            <option value="Corporate Event">Corporate Event</option>
                            <option value="Wedding">Wedding</option>
                            <option value="Birthday Party">Birthday Party</option>
                            <option value="Charity">Charity</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="event_date" id="eventDate" required>
                    </div>

                    <div class="form-group">
                        <label>Total Budget</label>
                        <input type="number" name="total_budget" id="totalBudget" placeholder="100000" required>
                    </div>

                    <div class="form-group">
                        <label>Currency</label>
                        <select name="currency" id="currency">
                            <option value="USD">USD</option>
                            <option value="PKR">PKR</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Venue Name</label>
                        <input type="text" name="venue_name" id="venueName" placeholder="e.g. The Grand Atrium">
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" id="location" placeholder="City, State">
                    </div>

                    <div class="form-group">
                        <label>Guest Count</label>
                        <input type="number" name="guest_count" id="guestCount" placeholder="0">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="status">
                            <option value="Planning">Planning</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <div class="form-group span-2">
                        <label>Description</label>
                        <textarea name="description" id="description" rows="3" placeholder="Additional details..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="btnDeleteEvent" style="display: none; border-color: var(--danger); color: var(--danger);" onclick="deleteEvent()">Delete Event</button>
                    <div style="flex: 1;"></div>
                    <button type="button" class="btn btn-outline" onclick="closeEventModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveEvent">Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>
