// Live preview of selected photos before form submission
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    var previewId = input.getAttribute('data-preview');
    var previewEl = document.getElementById(previewId);
    if (!previewEl) return;

    input.addEventListener('change', function () {
      previewEl.innerHTML = '';
      var files = Array.from(input.files || []);
      if (files.length > 10) {
        alert('Please select a maximum of 10 photos at a time.');
        input.value = '';
        return;
      }
      files.forEach(function (file) {
        if (!file.type.startsWith('image/')) return;
        var reader = new FileReader();
        reader.onload = function (e) {
          var img = document.createElement('img');
          img.src = e.target.result;
          img.className = 'photo-thumb';
          previewEl.appendChild(img);
        };
        reader.readAsDataURL(file);
      });
    });
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
