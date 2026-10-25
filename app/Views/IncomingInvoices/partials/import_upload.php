<div class="mb-4 p-3 border rounded bg-light" style="max-width: 40rem;">
	<label class="form-label fw-semibold">Daten aus Datei übernehmen</label>
	<p class="small text-muted mb-2">PDF (ZUGFeRD/Factur-X oder Text) oder X-Rechnung-XML. Die Datei wird nicht gespeichert.</p>
	<div class="d-flex flex-wrap gap-2 align-items-end">
		<div class="flex-grow-1" style="min-width: 14rem;">
			<input type="file" id="invoice_import_file" class="form-control" accept=".pdf,.xml,application/pdf,text/xml,application/xml">
		</div>
		<button type="button" class="btn btn-outline-primary" id="invoice_import_btn">Auslesen</button>
	</div>
	<div id="invoice_import_status" class="small mt-2"></div>
</div>
<script>
(function () {
	const fileInput = document.getElementById('invoice_import_file');
	const btn = document.getElementById('invoice_import_btn');
	const status = document.getElementById('invoice_import_status');
	if (!fileInput || !btn || !status) {
		return;
	}

	const csrfName = <?= json_encode(csrf_token()) ?>;
	const csrfHash = <?= json_encode(csrf_hash()) ?>;
	const uploadUrl = <?= json_encode(site_url('incoming-invoices/parse-upload')) ?>;

	btn.addEventListener('click', function () {
		const file = fileInput.files && fileInput.files[0];
		if (!file) {
			status.className = 'small mt-2 text-danger';
			status.textContent = 'Bitte zuerst eine Datei wählen.';
			return;
		}

		const formData = new FormData();
		formData.append('invoice_file', file);
		formData.append(csrfName, csrfHash);

		btn.disabled = true;
		status.className = 'small mt-2 text-muted';
		status.textContent = 'Datei wird ausgelesen…';

		fetch(uploadUrl, {
			method: 'POST',
			body: formData,
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
		})
			.then(function (response) { return response.json(); })
			.then(function (data) {
				btn.disabled = false;
				if (!data || !data.ok) {
					status.className = 'small mt-2 text-danger';
					status.textContent = (data && data.warnings && data.warnings.length)
						? data.warnings.join(' ')
						: 'Auslesen fehlgeschlagen.';
					return;
				}

				const numberField = document.querySelector('input[name="invoice_number"]');
				const dateField = document.querySelector('input[name="invoice_date"]');
				const amountField = document.querySelector('input[name="amount_euro"]');
				if (data.invoice_number && numberField) {
					numberField.value = data.invoice_number;
				}
				if (data.invoice_date && dateField) {
					dateField.value = data.invoice_date;
				}
				if (data.amount_euro && amountField) {
					amountField.value = data.amount_euro;
				}

				let msg = 'Daten übernommen';
				if (data.source === 'zugferd') {
					msg += ' (ZUGFeRD/XML)';
				} else if (data.source === 'pdf_text') {
					msg += ' (PDF-Text)';
				}
				if (data.warnings && data.warnings.length) {
					msg += '. Hinweis: ' + data.warnings.join(' ');
				}
				status.className = 'small mt-2 text-success';
				status.textContent = msg + '.';
			})
			.catch(function () {
				btn.disabled = false;
				status.className = 'small mt-2 text-danger';
				status.textContent = 'Auslesen fehlgeschlagen.';
			});
	});
})();
</script>
