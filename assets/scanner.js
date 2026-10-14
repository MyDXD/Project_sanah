window.productIdFromQR = function (value) {
  if (/^\d+$/.test(value.trim())) return Number(value);
  try {
    const url = new URL(value, location.origin);
    if (
      url.origin !== location.origin ||
      !["/product_content.php", "/product.php"].includes(url.pathname)
    )
      return null;
    const id = url.searchParams.get("id");
    return /^\d+$/.test(id || "") ? Number(id) : null;
  } catch {
    return null;
  }
};
window.attachScanner = function (onResult) {
  const start = document.getElementById("start-scan"),
    stop = document.getElementById("stop-scan"),
    video = document.getElementById("scanner-video"),
    area = document.getElementById("scanner-area"),
    message = document.getElementById("scanner-message");
  if (!start) return;
  let stream = null,
    raf = null;
  const canvas = document.createElement("canvas"),
    ctx = canvas.getContext("2d", { willReadFrequently: true });
  function close() {
    if (raf) cancelAnimationFrame(raf);
    if (stream) stream.getTracks().forEach((t) => t.stop());
    stream = null;
    area.hidden = true;
  }
  async function scan() {
    if (!stream) return;
    if (video.readyState >= 2) {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0);
      const frame = ctx.getImageData(0, 0, canvas.width, canvas.height),
        qr = jsQR(frame.data, canvas.width, canvas.height);
      if (qr) {
        const id = productIdFromQR(qr.data);
        if (id) {
          close();
          onResult(id);
          return;
        }
        message.textContent = "QR นี้ไม่ใช่ QR สินค้าของร้าน";
      }
    }
    raf = requestAnimationFrame(scan);
  }
  start.addEventListener("click", async () => {
    close();
    area.hidden = false;
    message.textContent = "กำลังเปิดกล้อง…";
    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: "environment" },
        audio: false,
      });
      video.srcObject = stream;
      await video.play();
      message.textContent = "วาง QR สินค้าให้อยู่ในภาพ";
      scan();
    } catch {
      message.textContent =
        "เปิดกล้องไม่ได้ กรุณาอนุญาตกล้องและใช้ HTTPS หรือ localhost หรือใช้ช่องรหัสสินค้า";
    }
  });
  stop.addEventListener("click", close);
  window.addEventListener("pagehide", close);
};
