<div id="taskModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="taskModalTitle">Add New Task</h2>
            <button class="close-modal-btn" onclick="closeTaskModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <button class="ai-suggestion-btn" type="button" style="width: 100%; background: #fdf4ff; border: 1px solid #f0abfc; color: #c026d3; padding: 12px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; margin-bottom: 20px; transition: all 0.2s;" onclick="alert('AI Auto-fill coming soon!')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                Auto-Fill Details with AI
            </button>

            <form id="taskForm">
                <input type="hidden" name="action" id="taskFormAction" value="create">
                <input type="hidden" name="id" id="taskId">
                <!-- event_id is available in scope because this is included in pages/events.php -->
                <input type="hidden" name="event_id" id="taskEventId" value="<?php echo htmlspecialchars($event['id'] ?? ''); ?>">

                <div class="form-grid">
                    <div class="form-group span-2">
                        <label>Task Name</label>
                        <input type="text" name="task_name" id="taskName" required placeholder="e.g., Book the photographer">
                    </div>

                    <div class="form-group">
                        <label>Phase</label>
                        <select name="phase" id="taskPhase">
                            <option value="Pre-Planning">Pre-Planning</option>
                            <option value="Preparation">Preparation</option>
                            <option value="Day-Of">Day-Of</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" id="taskDueDate">
                    </div>

                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" id="taskPriority">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="taskStatus">
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                            <option value="Skipped">Skipped</option>
                        </select>
                    </div>

                    <div class="form-group span-2">
                        <label>Description</label>
                        <textarea name="notes" id="taskNotes" rows="3" placeholder="Additional notes, contacts, or details..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="btnDeleteTask" style="display: none; border-color: var(--danger); color: var(--danger);" onclick="deleteTask()">Delete Task</button>
                    <div style="flex: 1;"></div>
                    <button type="button" class="btn btn-outline" onclick="closeTaskModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveTask">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
