// Fungsi membuat HTML modal secara dinamis jika belum ada di DOM
function injectImageModal() {
  if (!document.getElementById("imageModal")) {
    const modalHTML = `
      <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content modal-xl">
                  <div class="modal-header">
                      <h5 class="modal-title" id="imageModalLabel">Preview Image</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body text-center">
                      <img id="modalImage" src="" alt="Preview" style="max-width: 100%; height: auto;">
                  </div>
              </div>
          </div>
      </div>
    `;
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = modalHTML.trim();
    document.body.appendChild(tempDiv.firstChild);
  }
}

// Fungsi untuk menampilkan gambar di modal
function showImgLink(url) {
  // Pastikan modal terinjeksi sebelum dibuka
  injectImageModal();

  const modalImage = document.getElementById("modalImage");
  if (modalImage) {
    modalImage.src = url;
  }

  const imageModal = new bootstrap.Modal(document.getElementById("imageModal"));
  imageModal.show();
}

// Otomatis tambahkan event listener ke semua tag img kecuali yang ada di modal
document.addEventListener("DOMContentLoaded", function () {
  // Injeksi modal ke body saat DOM siap
  injectImageModal();

  const allImages = document.querySelectorAll("img");

  allImages.forEach(function (img) {
    // Cek apakah img berada di dalam modal
    const isInsideModal = img.closest("#imageModal");

    // Hanya tambahkan event jika TIDAK di dalam modal
    if (!isInsideModal) {
      // Tambahkan cursor pointer untuk indikasi bisa diklik
      img.style.cursor = "pointer";

      // Tambahkan event click
      img.addEventListener("click", function () {
        const imgSrc = this.getAttribute("src");
        if (imgSrc && imgSrc !== "") {
          showImgLink(imgSrc);
        }
      });
    }
  });
});
