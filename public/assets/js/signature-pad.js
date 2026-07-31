(function () {
	var canvas = document.getElementById('signature-pad');
	var clearBtn = document.getElementById('signature-clear');
	var hidden = document.getElementById('signature_data');
	var form = document.getElementById('quittung-form');

	if (!canvas || !hidden) {
		return;
	}

	var ctx = canvas.getContext('2d');
	var drawing = false;
	var hasStroke = false;

	function resizeCanvas() {
		var ratio = Math.max(window.devicePixelRatio || 1, 1);
		var width = canvas.offsetWidth || 520;
		var height = 90;
		canvas.width = width * ratio;
		canvas.height = height * ratio;
		ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
		ctx.lineWidth = 2;
		ctx.lineCap = 'round';
		ctx.lineJoin = 'round';
		ctx.strokeStyle = '#111';
	}

	function pos(e) {
		var rect = canvas.getBoundingClientRect();
		var clientX = e.touches ? e.touches[0].clientX : e.clientX;
		var clientY = e.touches ? e.touches[0].clientY : e.clientY;
		return {
			x: clientX - rect.left,
			y: clientY - rect.top
		};
	}

	function start(e) {
		drawing = true;
		var p = pos(e);
		ctx.beginPath();
		ctx.moveTo(p.x, p.y);
		e.preventDefault();
	}

	function move(e) {
		if (!drawing) {
			return;
		}
		var p = pos(e);
		ctx.lineTo(p.x, p.y);
		ctx.stroke();
		hasStroke = true;
		e.preventDefault();
	}

	function end() {
		drawing = false;
	}

	function clearPad() {
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		hasStroke = false;
		hidden.value = '';
	}

	resizeCanvas();
	window.addEventListener('resize', function () {
		var data = hasStroke ? canvas.toDataURL('image/png') : null;
		resizeCanvas();
		if (data) {
			var img = new Image();
			img.onload = function () {
				ctx.drawImage(img, 0, 0, canvas.offsetWidth, 90);
				hasStroke = true;
			};
			img.src = data;
		}
	});

	canvas.addEventListener('mousedown', start);
	canvas.addEventListener('mousemove', move);
	canvas.addEventListener('mouseup', end);
	canvas.addEventListener('mouseleave', end);
	canvas.addEventListener('touchstart', start, { passive: false });
	canvas.addEventListener('touchmove', move, { passive: false });
	canvas.addEventListener('touchend', end);

	if (clearBtn) {
		clearBtn.addEventListener('click', clearPad);
	}

	if (form) {
		form.addEventListener('submit', function () {
			if (hasStroke) {
				hidden.value = canvas.toDataURL('image/png');
			} else {
				hidden.value = '';
			}
		});
	}
})();
