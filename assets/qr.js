document.querySelectorAll("[data-qr]").forEach((el) => {
  const qr = qrcode(0, "M");
  qr.addData(location.origin + el.dataset.qr);
  qr.make();
  el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 4, scalable: true });
  el.firstElementChild.style.maxWidth = "220px";
});
