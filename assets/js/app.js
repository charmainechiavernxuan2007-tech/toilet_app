document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-camera]').forEach(function (camera) {
    var form = camera.closest('form');
    var video = camera.querySelector('[data-camera-video]');
    var canvas = camera.querySelector('[data-camera-canvas]');
    var input = camera.querySelector('[data-camera-input]');
    var preview = camera.querySelector('[data-camera-preview]');
    var status = camera.querySelector('[data-camera-status]');
    var startButton = camera.querySelector('[data-camera-start]');
    var captureButton = camera.querySelector('[data-camera-capture]');
    var stopButton = camera.querySelector('[data-camera-stop]');
    var photos = [];
    var stream = null;

    function stopCamera() {
      if (stream) stream.getTracks().forEach(function (track) { track.stop(); });
      stream = null;
      video.srcObject = null;
      video.hidden = true;
      captureButton.disabled = true;
      stopButton.hidden = true;
      startButton.hidden = false;
    }

    function renderPhotos() {
      preview.innerHTML = '';
      photos.forEach(function (photo, index) {
        var item = document.createElement('div');
        item.className = 'photo-preview-item';
        var image = document.createElement('img');
        image.src = URL.createObjectURL(photo);
        image.className = 'photo-thumb';
        image.onload = function () { URL.revokeObjectURL(image.src); };
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-danger btn-sm';
        remove.innerHTML = '<i class="bi bi-x"></i>';
        remove.setAttribute('aria-label', 'Remove photo');
        remove.addEventListener('click', function () {
          photos.splice(index, 1);
          renderPhotos();
        });
        item.appendChild(image);
        item.appendChild(remove);
        preview.appendChild(item);
      });
    }

    function syncFiles() {
      var transfer = new DataTransfer();
      photos.forEach(function (photo, index) {
        transfer.items.add(new File([photo], 'camera-' + (index + 1) + '.jpg', { type: photo.type }));
      });
      input.files = transfer.files;
    }

    startButton.addEventListener('click', function () {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        status.textContent = 'Camera access is not supported by this browser.';
        return;
      }
      navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
        .then(function (newStream) {
          stream = newStream;
          video.srcObject = stream;
          video.hidden = false;
          captureButton.disabled = false;
          startButton.hidden = true;
          stopButton.hidden = false;
          status.textContent = 'Camera ready. You can take up to 10 photos.';
        })
        .catch(function () {
          status.textContent = 'Camera permission was denied or the camera is unavailable.';
        });
    });

    captureButton.addEventListener('click', function () {
      if (!stream || photos.length >= 10) return;
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
      canvas.toBlob(function (blob) {
        if (blob) {
          photos.push(blob);
          syncFiles();
          renderPhotos();
          status.textContent = photos.length + ' photo(s) ready.';
        }
      }, 'image/jpeg', 0.9);
    });

    stopButton.addEventListener('click', stopCamera);
    form.addEventListener('submit', function () {
      syncFiles();
      stopCamera();
    });
    window.addEventListener('pagehide', stopCamera);
  });

  // Confirm before destructive actions
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });
});
