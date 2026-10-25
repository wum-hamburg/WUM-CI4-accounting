<?= $this->extend('layout_public') ?>
<?= $this->section('content') ?>

<?php if (! empty($isDemoBanner)): ?>
<div class="alert alert-info order-confirmation-card mb-3">Demo-Umgebung – E-Mail-Versand ist deaktiviert.</div>
<?php endif; ?>

<?php $flashError = session()->getFlashdata('error'); ?>

<div class="order-confirmation-card">
	<p class="order-confirmation-lead mb-4">
		Hiermit beauftrage ich das Angebot Nr. <strong><?= esc($quote['quote_number']) ?></strong>.
	</p>

	<form method="post" action="<?= site_url('auftragsbestaetigung/' . esc($token, 'url')) ?>" id="order-confirmation-form">
		<?= csrf_field() ?>

		<div class="mb-3">
			<label for="notes" class="form-label fw-semibold">Ich habe noch folgende Anmerkungen:</label>
			<textarea name="notes" id="notes" class="form-control" rows="8"><?= esc(old('notes')) ?></textarea>
		</div>

		<div class="mb-3">
			<label for="confirmation_date" class="form-label fw-semibold">Datum <span class="text-danger">*</span></label>
			<input type="date" name="confirmation_date" id="confirmation_date" class="form-control"
				value="<?= esc($confirmationDate) ?>" required>
		</div>

		<div class="mb-3">
			<label class="form-label fw-semibold">Unterschrift <span class="text-danger">*</span></label>
			<div class="order-confirmation-signature-wrap">
				<canvas id="signature-pad" width="600" height="120"></canvas>
				<input type="hidden" name="signature_data" id="signature_data"
					value="<?= esc(old('signature_data') ?? '', 'attr') ?>">
			</div>
			<button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="signature-clear">Unterschrift löschen</button>
		</div>

		<div class="mb-3 form-check">
			<input type="checkbox" name="email_copy" value="1" class="form-check-input" id="email_copy"
				<?= ! empty($isDemoBanner) ? 'disabled' : '' ?>
				<?= empty($isDemoBanner) && old('email_copy') ? 'checked' : '' ?>>
			<label class="form-check-label" for="email_copy">
				Kopie der E-Mail erhalten
				<?php if (! empty($isDemoBanner)): ?>
					<span class="text-muted">(in der Demo gesperrt)</span>
				<?php endif; ?>
			</label>
		</div>

		<div class="mb-4 form-check">
			<input type="checkbox" name="privacy_consent" value="1" class="form-check-input" id="privacy_consent"
				<?= old('privacy_consent') ? 'checked' : '' ?>>
			<label class="form-check-label" for="privacy_consent">
				Ich habe die
				<a href="<?= esc($privacyPolicyUrl) ?>" target="_blank" rel="noopener noreferrer">Datenschutzerklärung</a>
				gelesen und stimme zu. <span class="text-danger">*</span>
			</label>
		</div>

		<div class="order-confirmation-actions">
			<button type="submit" class="btn btn-primary btn-lg">Auftrag senden</button>
			<button type="submit" formaction="<?= site_url('auftragsbestaetigung/' . esc($token, 'url') . '/pdf') ?>"
				formmethod="post" class="btn btn-outline-secondary btn-lg">PDF speichern</button>
		</div>
	</form>
</div>

<div class="modal fade" id="order-confirmation-error-modal" tabindex="-1"
	aria-labelledby="order-confirmation-error-title" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content order-confirmation-error-modal">
			<div class="modal-header">
				<h5 class="modal-title" id="order-confirmation-error-title">
					<i class="fa-solid fa-circle-exclamation me-2"></i>Hinweis
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
			</div>
			<div class="modal-body" id="order-confirmation-error-message"><?= esc($flashError ?? '') ?></div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/signature-pad.js') ?>"
	data-form-id="order-confirmation-form"
	data-canvas-id="signature-pad"
	data-hidden-id="signature_data"
	data-clear-id="signature-clear"></script>
<script>
(function () {
	var modalEl = document.getElementById('order-confirmation-error-modal');
	var messageEl = document.getElementById('order-confirmation-error-message');
	var form = document.getElementById('order-confirmation-form');
	var privacy = document.getElementById('privacy_consent');
	var flashError = <?= json_encode($flashError ?: '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

	if (!modalEl || typeof bootstrap === 'undefined') {
		return;
	}

	var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

	function syncSignature() {
		if (window.wumSignaturePad && typeof window.wumSignaturePad.sync === 'function') {
			return window.wumSignaturePad.sync();
		}

		var hidden = document.getElementById('signature_data');
		return hidden ? hidden.value : '';
	}

	function showError(message, focusEl) {
		if (messageEl) {
			messageEl.textContent = message;
		}
		modal.show();
		if (focusEl) {
			modalEl.addEventListener('hidden.bs.modal', function onHidden() {
				modalEl.removeEventListener('hidden.bs.modal', onHidden);
				focusEl.focus();
			});
		}
	}

	if (flashError) {
		showError(flashError);
	}

	if (form) {
		form.addEventListener('submit', function (event) {
			var signature = syncSignature();
			if (!signature || signature.indexOf('data:image/') !== 0) {
				event.preventDefault();
				showError('Bitte unterschreiben Sie im Unterschriftsfeld.');
				return;
			}

			if (privacy && !privacy.checked) {
				event.preventDefault();
				showError('Bitte stimmen Sie der Datenschutzerklärung zu.', privacy);
			}
		});
	}
})();
</script>
<?= $this->endSection() ?>
