/*document.addEventListener("DOMContentLoaded", function () {
  // Map each badge to its modal
  const mappings = [
    { btn: "upcomingBadge", modal: "upcomingModal" },
    { btn: "teachersBadge", modal: "teachersModal" },
    { btn: "hoursBadge", modal: "hoursModal" },
  ];

  mappings.forEach(({ btn, modal }) => {
    const button = document.getElementById(btn);
    const modalEl = document.getElementById(modal);

    if (button && modalEl) {
      button.addEventListener("click", () => {
        modalEl.style.display = "block";
      });

      // Optional: handle close buttons inside modal
      const closeBtn = modalEl.querySelector(".modal-close");
      if (closeBtn) {
        closeBtn.addEventListener("click", () => {
          modalEl.style.display = "none";
        });
      }
    }
  });
});
*/