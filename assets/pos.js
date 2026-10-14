(() => {
  const search = document.getElementById("pos-search"),
    lines = [...document.querySelectorAll("[data-product]")];
  function total() {
    let sum = 0;
    lines.forEach((row) => {
      const cents = Math.round(Number(row.dataset.price) * 100),
        qty = Number(row.querySelector("input").value) || 0;
      sum += cents * qty;
      row.querySelector(".line-total").textContent = (
        (cents * qty) /
        100
      ).toFixed(2);
    });
    document.getElementById("pos-total").textContent = (
      sum / 100
    ).toLocaleString("th-TH", { minimumFractionDigits: 2 });
  }
  function add(id) {
    const row = lines.find((row) => Number(row.dataset.product) === id);
    if (!row) {
      alert("ไม่พบสินค้าที่เปิดขาย");
      return;
    }
    const input = row.querySelector("input");
    if (Number(input.value) >= Number(input.max)) {
      alert("สินค้าใน Stock ไม่พอ");
      return;
    }
    input.value = Number(input.value) + 1;
    search.value = "";
    lines.forEach((row) => (row.hidden = false));
    total();
    row.scrollIntoView({ block: "center", behavior: "smooth" });
  }
  search.addEventListener("input", () => {
    const term = search.value.toLowerCase();
    lines.forEach(
      (row) =>
        (row.hidden =
          !row.dataset.name.toLowerCase().includes(term) &&
          row.dataset.product !== term),
    );
  });
  search.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      event.preventDefault();
      const id = productIdFromQR(search.value);
      if (id) add(id);
    }
  });
  lines.forEach((row) =>
    row.querySelector("input").addEventListener("input", total),
  );
  attachScanner(add);
  total();
})();
