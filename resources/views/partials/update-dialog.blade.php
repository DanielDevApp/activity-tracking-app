<dialog id="update-dialog" class="dialog" aria-labelledby="dlg-title">
    <div class="dialog__head"><h2 id="dlg-title">Update activity</h2></div>
    <form method="POST" id="update-form" class="dialog__form">
        @csrf
        @method('PATCH')

        <fieldset class="seg" style="border:1px solid var(--line)">
            <legend class="sr-only">Status</legend>
            <input type="radio" name="status" id="st-done" value="done"><label for="st-done">Done</label>
            <input type="radio" name="status" id="st-pending" value="pending"><label for="st-pending">Pending</label>
        </fieldset>

        <label for="dlg-remark" class="field__label">Remark</label>
        <textarea id="dlg-remark" name="remark" rows="4" maxlength="1000"
                  placeholder="e.g. SMS count 10,230 vs logs 10,228, 2 test messages excluded"></textarea>
        <p class="hint" id="dlg-hint">Required while an activity is pending, so the next person knows where you stopped.</p>

        <div class="dialog__actions">
            <button type="button" class="btn btn--ghost" data-close>Cancel</button>
            <button type="submit" class="btn btn--primary"><x-icon name="check" />Save update</button>
        </div>
    </form>
</dialog>
