const isMobile =
  /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
    navigator.userAgent
  ) || window.innerWidth <= 768;

// Also listen for orientation changes and window resizes
window.addEventListener("resize", function () {
  // Update teeth layout if orientation changes
  if (isMobile) {
    // Give a small delay to let the resize complete
    setTimeout(initChart, 300);
  }
});

if (!document.querySelector('link[href*="chart.css"]')) {
  const link = document.createElement("link");
  link.rel = "stylesheet";
  link.type = "text/css";
  link.href = "chart.css";
  document.head.appendChild(link);
}

// Add these functions near the top of your file
function saveToLocalStorage() {
  const tempData = {
    upper: extractToothData(upper),
    lower: extractToothData(lower),
    timestamp: new Date().getTime(),
  };
  localStorage.setItem(`chart_${appointmentId}`, JSON.stringify(tempData));
  console.log("Auto-saved chart data to localStorage");
}

function loadFromLocalStorage() {
  const storedData = localStorage.getItem(`chart_${appointmentId}`);
  if (storedData) {
    try {
      const parsedData = JSON.parse(storedData);
      return parsedData;
    } catch (e) {
      console.error("Error parsing localStorage data:", e);
      return null;
    }
  }
  return null;
}

function clearLocalStorage() {
  localStorage.removeItem(`chart_${appointmentId}`);
  console.log("Cleared localStorage data after successful save");
}

// Add this function to the top of your chart.js file
function showNotification(message, type = "info", duration = 3000) {
  // Remove any existing notifications
  const existingNotifications = document.querySelectorAll(
    ".custom-notification"
  );
  existingNotifications.forEach((notification) => {
    notification.classList.add("hide");
    setTimeout(() => notification.remove(), 300);
  });

  // Create notification element
  const notification = document.createElement("div");
  notification.className = `custom-notification ${type}`;

  // Set icon based on type
  let icon = "";
  switch (type) {
    case "success":
      icon = '<i class="fas fa-check-circle"></i>';
      break;
    case "error":
      icon = '<i class="fas fa-exclamation-circle"></i>';
      break;
    case "warning":
      icon = '<i class="fas fa-exclamation-triangle"></i>';
      break;
    default:
      icon = '<i class="fas fa-info-circle"></i>';
  }

  notification.innerHTML = `
          <div class="notification-icon">${icon}</div>
          <div class="notification-message">${message}</div>
          <div class="notification-close"><i class="fas fa-times"></i></div>
      `;

  // Add to document
  document.body.appendChild(notification);

  // Show with animation
  setTimeout(() => notification.classList.add("show"), 10);

  // Auto dismiss after duration
  if (duration) {
    setTimeout(() => {
      notification.classList.add("hide");
      setTimeout(() => notification.remove(), 300);
    }, duration);
  }

  // Close button
  notification
    .querySelector(".notification-close")
    .addEventListener("click", () => {
      notification.classList.add("hide");
      setTimeout(() => notification.remove(), 300);
    });

  return notification;
}

// Ensure Chart.js is loaded
console.log("chart.js is loaded!");

// Get necessary DOM elements
const chart = document.getElementById("chart");
const upper = document.getElementById("upper");
const lower = document.getElementById("lower");
const upperLabel = document.getElementById("upperLabel");
const lowerLabel = document.getElementById("lowerLabel");
const saveButton = null;
const childTeethBtn = document.getElementById("childTeeth");
const adultTeethBtn = document.getElementById("adultTeeth");

// Define tooth numbers for child and adult sets
const childTeethNumbers = {
  upper: [
    "A",
    "B",
    "C",
    "D",
    "E",
    "F",
    "G",
    "H",
    "I",
    "J",
    "K",
    "L",
    "M",
    "N",
    "O",
    "P",
  ],
  lower: [
    "S",
    "R",
    "Q",
    "P",
    "O",
    "N",
    "M",
    "L",
    "K",
    "J",
    "I",
    "H",
    "G",
    "F",
    "E",
    "D",
    "C",
    "B",
    "A",
  ],
};

const adultTeethNumbers = {
  upper: [
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
    "10",
    "11",
    "12",
    "13",
    "14",
    "15",
    "16",
  ],
  lower: [
    "32",
    "31",
    "30",
    "29",
    "28",
    "27",
    "26",
    "25",
    "24",
    "23",
    "22",
    "21",
    "20",
    "19",
    "18",
    "17",
  ],
};

// Extract appointmentId from URL
const appointmentId = new URLSearchParams(window.location.search).get(
  "appointment_id"
);
if (!appointmentId) {
  console.error("No appointment_id provided in the URL.");
  alert("Unable to load data: missing appointment_id.");
  throw new Error("Missing appointment_id.");
}

console.log(`Appointment ID: ${appointmentId}`);

// Fetch patient's age and determine which chart to display
fetch(`get_patient_age.php?appointment_id=${appointmentId}`)
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      const age = data.age;

      // Toggle button visibility and auto-generate the appropriate teeth chart
      if (age <= 13) {
        childTeethBtn.style.display = "inline-block";
        adultTeethBtn.style.display = "none";
        generateTeeth(childTeethNumbers); // Auto-show child teeth
      } else {
        childTeethBtn.style.display = "none";
        adultTeethBtn.style.display = "inline-block";
        generateTeeth(adultTeethNumbers); // Auto-show adult teeth
      }
    } else {
      console.error(`Failed to fetch patient age: ${data.error}`);
      showNotification(`Failed to fetch patient age: ${data.error}`, "error");
    }
  })
  .catch((error) => {
    console.error(`Error fetching patient age: ${error.message}`);
    showNotification("An error occurred while fetching patient age.", "error");
  });

function createTooth(number) {
  const tooth = document.createElement("div");
  tooth.className = "tooth";
  tooth.textContent = number;
  tooth.setAttribute("data-tooth-number", number);

  // Use touchstart for mobile, click for desktop
  const eventType = isMobile ? "touchstart" : "click";
  tooth.addEventListener(eventType, (e) => {
    if (isMobile) {
      e.preventDefault(); // Prevent default touch behavior
    }

    // Remove existing modal if any
    document.querySelector(".tooth-modal")?.remove();
    document.querySelector(".modal-overlay")?.remove();

    // Rest of your existing tooth click handler code...
    const overlay = document.createElement("div");
    overlay.className = "modal-overlay";

    // Generate unique IDs
    const uniqueId = `${number}-${Date.now()}`;
    const regionSelectId = `regionSelect-${uniqueId}`;
    const commentInputId = `commentInput-${uniqueId}`;

    // Get tooth name from global toothNames object
    const toothName = toothNames[number] || `Tooth ${number}`;

    // Create modal
    const modal = document.createElement("div");
    modal.className = "tooth-modal";
    // Update this part in the tooth modal creation
    // Update this part in the tooth modal creation
    modal.innerHTML = `
<div class="modal-content">
    <h2>Tooth ${number} - ${toothName}</h2>
    <p>Select the affected region and add a comment</p>

    <div class="tooth-region-guide">
        <div class="tooth-diagram">
            <span class="region upper">Upper</span>
            <span class="region lower">Lower</span>
            <span class="region left">Left</span>
            <span class="region right">Right</span>
            <span class="region center">Center</span>
        </div>
    </div>

    <div class="current-comments">
        <h3>Current Comments</h3>
        <div class="comments-list"></div>
    </div>

    <div class="form-group">
        <label for="${regionSelectId}">Affected Region:</label>
        <select id="${regionSelectId}" class="regionSelect">
            <option value="Upper">Upper</option>
            <option value="Lower">Lower</option>
            <option value="Left">Left</option>
            <option value="Right">Right</option>
            <option value="Center">Center</option>
        </select>
    </div>

    <div class="form-group">
        <label for="${commentInputId}">Comment:</label>
        <textarea id="${commentInputId}" class="commentInput" placeholder="Enter your observations here..."></textarea>
    </div>

    <div class="form-group">
        <label for="statusSelect-${uniqueId}">Treatment Status:</label>
        <select id="statusSelect-${uniqueId}" class="statusSelect">
            <option value="pending">Pending (Requires Treatment)</option>
            <option value="completed">Completed</option>
        </select>
    </div>

    <div class="modal-buttons">
    <button class="btn btn-primary addComment">
        <i class="fas fa-plus"></i> Add Comment
    </button>
    <button class="btn btn-success saveAll">
        <i class="fas fa-save"></i> Save & Close
    </button>
    <button class="btn btn-secondary cancelComment">
        <i class="fas fa-times"></i> Cancel
    </button>
</div>
</div>
`;

    // Append elements
    document.body.appendChild(overlay);
    document.body.appendChild(modal);

    // ** Button Click Events **
    const regionSelect = modal.querySelector(".regionSelect");
    const commentInput = modal.querySelector(".commentInput");
    const commentsList = modal.querySelector(".comments-list");

    // Load existing comments if any
    let comments = [];
    if (tooth.hasAttribute("data-comments")) {
      comments = JSON.parse(tooth.getAttribute("data-comments"));
    } else if (
      tooth.hasAttribute("data-comment") &&
      tooth.getAttribute("data-comment")
    ) {
      // Migration support for old data format
      comments = [
        {
          region: tooth.getAttribute("data-region") || "",
          comment: tooth.getAttribute("data-comment") || "",
        },
      ];
    }

    let editingCommentIndex = -1;
    // Display existing comments
    updateCommentsList();

    function updateCommentsList() {
      commentsList.innerHTML = "";

      if (comments.length === 0) {
        commentsList.innerHTML = "<p>No comments added yet.</p>";
      } else {
        comments.forEach((item, index) => {
          const commentEl = document.createElement("div");
          commentEl.className = "comment-item";
          const statusColor = item.status === "completed" ? "blue" : "red";
          commentEl.innerHTML = `
            <div class="comment-header">
              <span class="comment-region">${item.region}:</span>
              <span class="status-badge ${item.status}">${
            item.status === "completed" ? "Completed" : "Pending"
          }</span>
            </div>
            <div class="comment-text" style="color: ${statusColor};">
              ${item.comment}
            </div>
            <div class="comment-footer">
              <div></div>
              <div class="comment-actions">
                <button class="btn btn-primary btn-sm edit-comment" data-index="${index}">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-danger btn-sm remove-comment" data-index="${index}">
                  <i class="fas fa-trash"></i> Remove
                </button>
                <button class="btn btn-warning btn-sm toggle-status" data-index="${index}">
                  <i class="fas fa-exchange-alt"></i> Status
                </button>
              </div>
            </div>
          `;
          commentsList.appendChild(commentEl);
        });

        // Add event listeners to buttons (keep your existing code)
        commentsList.querySelectorAll(".remove-comment").forEach((btn) => {
          btn.addEventListener("click", (e) => {
            const button = e.target.closest(".remove-comment");
            const idx = parseInt(button.getAttribute("data-index"));
            comments.splice(idx, 1);
            updateCommentsList();
            updateToothAppearance();
          });
        });

        // Add toggle status event listeners
        commentsList.querySelectorAll(".toggle-status").forEach((btn) => {
          btn.addEventListener("click", (e) => {
            const button = e.target.closest(".toggle-status");
            const idx = parseInt(button.getAttribute("data-index"));
            comments[idx].status =
              comments[idx].status === "completed" ? "pending" : "completed";
            updateCommentsList();
            updateToothAppearance();
          });
        });

        commentsList.querySelectorAll(".edit-comment").forEach((btn) => {
          btn.addEventListener("click", (e) => {
            const button = e.target.closest(".edit-comment");
            const idx = parseInt(button.getAttribute("data-index"));

            // Set the editing mode
            editingCommentIndex = idx;

            // Fill the form with current values
            regionSelect.value = comments[idx].region;
            commentInput.value = comments[idx].comment;
            modal.querySelector(".statusSelect").value =
              comments[idx].status || "pending";

            // Change add button text to update
            const addBtn = modal.querySelector(".addComment");
            addBtn.innerHTML = '<i class="fas fa-save"></i> Update';

            // Focus on the comment input
            commentInput.focus();
          });
        });
      }
    }

    function addComment() {
      const region = regionSelect.value;
      const comment = commentInput.value.trim();
      const status = modal.querySelector(".statusSelect").value;

      if (!comment) {
        showNotification("Please enter a comment.", "warning");
        return false;
      }

      // If we're editing an existing comment
      if (editingCommentIndex >= 0) {
        comments[editingCommentIndex] = { region, comment, status };

        // Reset editing mode
        editingCommentIndex = -1;
        modal.querySelector(".addComment").innerHTML =
          '<i class="fas fa-plus"></i> Add Comment';
      } else {
        // Add new comment
        comments.push({ region, comment, status });
      }

      updateCommentsList();
      updateToothAppearance();
      commentInput.value = "";
      return true;
    }

    function updateToothAppearance() {
      // Keep tooth border black always
      tooth.style.borderColor = "#000";
      tooth.style.borderWidth = "2px";

      // Update the comment indicator
      const oldComment = tooth.querySelector(".comment");
      if (oldComment) tooth.removeChild(oldComment);

      if (comments.length > 0) {
        const commentDiv = document.createElement("div");
        commentDiv.className = "comment"; // Use existing CSS class
        commentDiv.textContent = comments.length; // Just show the number
        tooth.appendChild(commentDiv);

        // Store comments data
        tooth.removeAttribute("data-region");
        tooth.removeAttribute("data-comment");
        tooth.setAttribute("data-comments", JSON.stringify(comments));
      } else {
        // If no comments, remove data attributes
        tooth.removeAttribute("data-comments");
        tooth.removeAttribute("data-region");
        tooth.removeAttribute("data-comment");
        tooth.style.borderWidth = ""; // Reset border if no comments
      }

      // Save changes to localStorage for persistence
      setTimeout(saveToLocalStorage, 100);
    }

    modal.querySelector(".addComment").addEventListener("click", addComment);

    modal.querySelector(".saveAll").addEventListener("click", () => {
      // If there's text in the input and we're in editing mode
      if (editingCommentIndex >= 0 && commentInput.value.trim()) {
        addComment();
      }
      // If there's text in the input and we're not in editing mode
      else if (commentInput.value.trim()) {
        addComment();
      }

      // Reset editing mode
      editingCommentIndex = -1;

      // Rest of your existing code...
      tooth.setAttribute("data-comments", JSON.stringify(comments));
      updateToothAppearance();
      saveToLocalStorage();
      document.body.removeChild(modal);
      document.body.removeChild(overlay);
    });

    modal.querySelector(".cancelComment").addEventListener("click", () => {
      document.body.removeChild(modal);
      document.body.removeChild(overlay);
    });

    overlay.addEventListener("click", () => {
      document.body.removeChild(modal);
      document.body.removeChild(overlay);
    });
  });

  return tooth;
}

// Function to generate teeth
function generateTeeth(teethNumbers) {
  upper.innerHTML = "";
  lower.innerHTML = "";
  upperLabel.style.display = "block";
  lowerLabel.style.display = "block";

  teethNumbers.upper.forEach((number) =>
    upper.appendChild(createTooth(number))
  );
  teethNumbers.lower.forEach((number) =>
    lower.appendChild(createTooth(number))
  );

  // Call initChart after generating teeth
  initChart();
}

function initChart() {
  if (isMobile) {
    // Get both upper and lower jaw elements
    const upper = document.getElementById("upper");
    const lower = document.getElementById("lower");

    // Ensure consistent container styling for both jaws
    upper.style.display = lower.style.display = "grid";
    upper.style.width = lower.style.width = "100%";
    upper.style.justifyContent = lower.style.justifyContent = "center";
    upper.style.gridGap = lower.style.gridGap = "2px";

    // Set the same responsive grid template for both
    upper.style.gridTemplateColumns = "repeat(auto-fill, minmax(30px, 1fr))";
    lower.style.gridTemplateColumns = "repeat(auto-fill, minmax(30px, 1fr))";

    // Make sure teeth boxes have consistent sizing
    const allTeeth = document.querySelectorAll(".tooth");
    allTeeth.forEach((tooth) => {
      tooth.style.width = "30px";
      tooth.style.height = "30px";
      tooth.style.fontSize = "10px";
      tooth.style.margin = "2px";
      tooth.style.padding = "0";
      tooth.style.display = "flex";
      tooth.style.alignItems = "center";
      tooth.style.justifyContent = "center";
    });

    // Ensure chart container is properly scrollable
    const chartContainer = document.querySelector(".chart-container");
    if (chartContainer) {
      chartContainer.style.overflowX = "auto";
      chartContainer.style.WebkitOverflowScrolling = "touch";
      chartContainer.style.paddingBottom = "10px";
    }
  }
}

// Event listeners for child and adult teeth buttons
childTeethBtn.addEventListener("click", () => {
  hideTeeth();
  generateTeeth(childTeethNumbers);
});

adultTeethBtn.addEventListener("click", () => {
  hideTeeth();
  generateTeeth(adultTeethNumbers);
});

// Function to hide teeth before displaying a new set
function hideTeeth() {
  upper.innerHTML = "";
  lower.innerHTML = "";
  upperLabel.style.display = "none";
  lowerLabel.style.display = "none";
}

function showTeeth() {
  upper.style.display = "grid";
  lower.style.display = "grid";
}

document.addEventListener("DOMContentLoaded", function () {
  // Find your clear drawing button and attach the event listener
  const clearDrawingBtn = document.getElementById("clearCanvas");
  if (clearDrawingBtn) {
    clearDrawingBtn.addEventListener("click", clearDrawing);
  }
});

// Load chart data
fetch(`load_chart.php?appointment_id=${appointmentId}`)
  .then((response) => response.json())
  .then((data) => {
    if (!data.success) {
      showNotification(`Failed to load chart: ${data.error}`, "error");
      return;
    }

    let hasServerData = false;

    if (data.chart) {
      hasServerData = true;
      // Prevent duplicate images
      let existingImg = document.getElementById("loadedChartImage");
      if (!existingImg) {
        const img = new Image();
        img.src = data.chart;
        img.id = "loadedChartImage"; // Unique ID to prevent multiple images
        document.body.appendChild(img);
      }
    }

    // If there's server data, use it and clear localStorage
    if (data.tooth_data && Object.keys(data.tooth_data).length > 0) {
      loadToothData(data.tooth_data);
      clearLocalStorage(); // Clear localStorage as we now have official data
    }
    // If no server data but we have localStorage data, use that
    else {
      const localData = loadFromLocalStorage();
      if (localData) {
        const hoursSinceLastEdit =
          (new Date().getTime() - localData.timestamp) / (1000 * 60 * 60);

        // Only use local data if it's less than 24 hours old
        if (hoursSinceLastEdit < 24) {
          loadToothData(localData);
          showNotification(
            "Restored your unsaved changes from your last session.",
            "info",
            5000
          );
        } else {
          // Data is too old, clear it
          clearLocalStorage();
        }
      }
    }
  })
  .catch((error) => {
    console.error(`Error fetching chart: ${error.message}`);
    showNotification("An error occurred while loading the chart.", "error");

    // Try to load from localStorage as a fallback
    const localData = loadFromLocalStorage();
    if (localData) {
      loadToothData(localData);
      showNotification(
        "Loaded your unsaved changes from your last session.",
        "info",
        5000
      );
    }
  });

// Function to load tooth data
function loadToothData(toothData) {
  if (toothData.labels) {
    upperLabel.textContent = toothData.labels.upper || "Upper Jaw";
    lowerLabel.textContent = toothData.labels.lower || "Lower Jaw";
  }

  toothData.upper.forEach((tooth) => appendComment(upper, tooth));
  toothData.lower.forEach((tooth) => appendComment(lower, tooth));
}

function appendComment(jaw, tooth) {
  const toothDiv = Array.from(jaw.childNodes).find(
    (t) => t.textContent === tooth.number
  );
  if (!toothDiv) return;

  // Handle both new format (comments array) and old format (single comment)
  if (tooth.comments && tooth.comments.length > 0) {
    toothDiv.setAttribute("data-comments", JSON.stringify(tooth.comments));

    const commentDiv = document.createElement("div");
    commentDiv.className = "comment";
    // Just show the number - consistent with updateToothAppearance
    commentDiv.textContent = tooth.comments.length;
    toothDiv.appendChild(commentDiv);
  } else if (tooth.comment) {
    // Legacy support
    const commentDiv = document.createElement("div");
    commentDiv.className = "comment";
    commentDiv.textContent = "1"; // Just show "1" for legacy single comments
    toothDiv.appendChild(commentDiv);

    toothDiv.setAttribute("data-region", tooth.region || "");
    toothDiv.setAttribute("data-comment", tooth.comment);
  }
}

const toothNames = {
  // Adult upper teeth
  1: "Third Molar (Wisdom Tooth)",
  2: "Second Molar",
  3: "First Molar",
  4: "Second Premolar",
  5: "First Premolar",
  6: "Canine",
  7: "Lateral Incisor",
  8: "Central Incisor",
  9: "Central Incisor",
  10: "Lateral Incisor",
  11: "Canine",
  12: "First Premolar",
  13: "Second Premolar",
  14: "First Molar",
  15: "Second Molar",
  16: "Third Molar (Wisdom Tooth)",
  // Adult lower teeth
  17: "Third Molar (Wisdom Tooth)",
  18: "Second Molar",
  19: "First Molar",
  20: "Second Premolar",
  21: "First Premolar",
  22: "Canine",
  23: "Lateral Incisor",
  24: "Central Incisor",
  25: "Central Incisor",
  26: "Lateral Incisor",
  27: "Canine",
  28: "First Premolar",
  29: "Second Premolar",
  30: "First Molar",
  31: "Second Molar",
  32: "Third Molar (Wisdom Tooth)",
  // Child upper teeth
  A: "Primary Second Molar",
  B: "Primary First Molar",
  C: "Primary Canine",
  D: "Primary Lateral Incisor",
  E: "Primary Central Incisor",
  F: "Primary Central Incisor",
  G: "Primary Lateral Incisor",
  H: "Primary Canine",
  I: "Primary First Molar",
  J: "Primary Second Molar",
  K: "Primary Third Molar",
  L: "Primary Third Molar",
  M: "Primary Third Molar",
  N: "Primary Third Molar",
  O: "Primary Third Molar",
  P: "Primary Third Molar",
  // Child lower teeth
  S: "Primary Second Molar",
  R: "Primary First Molar",
  Q: "Primary Canine",
  P: "Primary Lateral Incisor",
  O: "Primary Central Incisor",
  N: "Primary Central Incisor",
  M: "Primary Lateral Incisor",
  L: "Primary Canine",
  K: "Primary First Molar",
  J: "Primary Second Molar",
  I: "Primary Third Molar",
  H: "Primary Third Molar",
  G: "Primary Third Molar",
  F: "Primary Third Molar",
  E: "Primary Third Molar",
  D: "Primary Third Molar",
  C: "Primary Third Molar",
  B: "Primary Third Molar",
  A: "Primary Third Molar",
};

// Extract the save chart functionality to a reusable function
// The 'redirect' parameter controls whether to redirect after saving
function saveChart(redirect = false) {
  const chartData = {
    upper: extractToothData(upper),
    lower: extractToothData(lower),
  };

  // Check if there are any comments in any teeth before saving
  const hasComments = [...chartData.upper, ...chartData.lower].some(
    (tooth) => tooth.comments && tooth.comments.length > 0
  );

  if (!hasComments) {
    showNotification(
      "Please add at least one comment to a tooth before saving.",
      "warning"
    );
    return Promise.reject("No comments found"); // Stop execution if no comments found
  }

  // Get the chart container dimensions to match
  const chartContainer = document.querySelector(".chart-container");
  const containerWidth = chartContainer ? chartContainer.offsetWidth : 800;

  // Calculate appropriate diagram size based on container
  const diagramWidth = Math.min(700, containerWidth - 40); // Margin on sides
  const scaleFactor = diagramWidth / 700; // Scale everything proportionally

  // For mobile screens, adjust the canvas width
  const canvas = document.createElement("canvas");
  if (isMobile) {
    // Use a more suitable size for mobile rendering
    canvas.width = 800; // Slightly smaller
    canvas.height = 1000; // Slightly smaller
  } else {
    canvas.width = 900;
    canvas.height = 1200;
  }
  const ctx = canvas.getContext("2d");

  ctx.fillStyle = "#fff";
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.strokeStyle = "#000";
  ctx.lineWidth = 2;
  ctx.font = "14px Arial";

  const markedTeeth = [
    ...(chartData.upper || []),
    ...(chartData.lower || []),
  ].filter((tooth) => tooth.comments && tooth.comments.length > 0);
  console.log("markedTeeth:", markedTeeth);

  function drawTeethDiagram(ctx, startX, startY, markedTeeth) {
    // Use the existing function but scale everything by scaleFactor
    const toothSpacing = 48 * scaleFactor;
    const archWidth = diagramWidth; // Calculated based on container
    const archHeight = 120 * scaleFactor;

    function drawTooth(ctx, x, y, markedTooth) {
      // Scale tooth dimensions
      const outerRadius = 20 * scaleFactor;
      const innerRadius = 12 * scaleFactor;
      const xOffset = 15 * scaleFactor;
      const innerOffset = 8 * scaleFactor;

      // Always use black for the tooth outline
      ctx.lineWidth = 2;
      ctx.strokeStyle = "#000";
      ctx.fillStyle = "#fff";

      // Outer Circle (Tooth Border)
      ctx.beginPath();
      ctx.arc(x, y, outerRadius, 0, 2 * Math.PI);
      ctx.fill();
      ctx.stroke();

      // Inner Circle (Tooth Center)
      ctx.beginPath();
      ctx.arc(x, y, innerRadius, 0, 2 * Math.PI);
      ctx.fill();
      ctx.stroke();

      // Dividing Lines (X Shape)
      ctx.beginPath();
      ctx.moveTo(x - xOffset, y - xOffset);
      ctx.lineTo(x - innerOffset, y - innerOffset);
      ctx.moveTo(x + xOffset, y + xOffset);
      ctx.lineTo(x + innerOffset, y + innerOffset);
      ctx.moveTo(x + xOffset, y - xOffset);
      ctx.lineTo(x + innerOffset, y - innerOffset);
      ctx.moveTo(x - xOffset, y + xOffset);
      ctx.lineTo(x - innerOffset, y + innerOffset);
      ctx.stroke();

      // Highlight the Selected Region
      if (
        markedTooth &&
        markedTooth.comments &&
        markedTooth.comments.length > 0
      ) {
        markedTooth.comments.forEach((comment) => {
          // Set fill color based on comment status
          ctx.fillStyle =
            comment.status === "completed" ? "#0000FF" : "#FF0000"; // Blue for completed, Red for pending

          const region = comment.region;
          switch (region) {
            case "Upper":
              ctx.beginPath();
              ctx.arc(
                x,
                y,
                outerRadius,
                (315 * Math.PI) / 180,
                (225 * Math.PI) / 180,
                true
              );
              ctx.arc(
                x,
                y,
                innerRadius,
                (225 * Math.PI) / 180,
                (315 * Math.PI) / 180,
                false
              );
              ctx.closePath();
              ctx.fill();
              break;

            case "Lower":
              ctx.beginPath();
              ctx.arc(
                x,
                y,
                outerRadius,
                (135 * Math.PI) / 180,
                (45 * Math.PI) / 180,
                true
              );
              ctx.arc(
                x,
                y,
                innerRadius,
                (45 * Math.PI) / 180,
                (135 * Math.PI) / 180,
                false
              );
              ctx.closePath();
              ctx.fill();
              break;

            case "Left":
              ctx.beginPath();
              ctx.arc(
                x,
                y,
                outerRadius,
                (225 * Math.PI) / 180,
                (135 * Math.PI) / 180,
                true
              );
              ctx.arc(
                x,
                y,
                innerRadius,
                (135 * Math.PI) / 180,
                (225 * Math.PI) / 180,
                false
              );
              ctx.closePath();
              ctx.fill();
              break;

            case "Right":
              ctx.beginPath();
              ctx.arc(
                x,
                y,
                outerRadius,
                (45 * Math.PI) / 180,
                (315 * Math.PI) / 180,
                true
              );
              ctx.arc(
                x,
                y,
                innerRadius,
                (315 * Math.PI) / 180,
                (45 * Math.PI) / 180,
                false
              );
              ctx.closePath();
              ctx.fill();
              break;

            case "Center":
              ctx.beginPath();
              ctx.arc(x, y, innerRadius - 2, 0, 2 * Math.PI);
              ctx.fill();
              break;
          }
        });
      }
    }

    // Upper teeth - parabolic curve facing downward
    for (let i = 0; i < 16; i++) {
      const normalizedPos = i / 7.5 - 1;
      let x = startX + (archWidth / 16) * i;
      let y = startY - archHeight * (1 - Math.pow(normalizedPos, 2));

      // Find matching tooth
      let tooth = null;
      const isAdultTeeth = adultTeethBtn.style.display === "inline-block";

      if (isAdultTeeth) {
        tooth = markedTeeth.find((t) => t.number === String(i + 1));
      } else {
        tooth = markedTeeth.find(
          (t) => t.number === childTeethNumbers.upper[i]
        );
      }

      drawTooth(ctx, x, y, tooth);
    }

    // Lower teeth - parabolic curve facing upward
    for (let i = 0; i < 16; i++) {
      const normalizedPos = i / 7.5 - 1;
      let x = startX + (archWidth / 16) * i;
      let y =
        startY +
        180 * scaleFactor +
        archHeight * (1 - Math.pow(normalizedPos, 2));

      // Find matching tooth
      let tooth = null;
      const isAdultTeeth = adultTeethBtn.style.display === "inline-block";

      if (isAdultTeeth) {
        tooth = markedTeeth.find((t) => t.number === String(32 - i));
      } else {
        tooth = markedTeeth.find(
          (t) => t.number === childTeethNumbers.lower[i]
        );
      }

      drawTooth(ctx, x, y, tooth);
    }

    // Draw jaw labels
    ctx.fillStyle = "#000";
    ctx.font = `bold ${16 * scaleFactor}px Arial`;
    ctx.fillText("Upper Jaw", startX, startY - archHeight - 20 * scaleFactor);
    ctx.fillText(
      "Lower Jaw",
      startX,
      startY + 180 * scaleFactor + archHeight + 40 * scaleFactor
    );

    // Number the teeth
    ctx.font = `${12 * scaleFactor}px Arial`;
    const isAdultTeeth = adultTeethBtn.style.display === "inline-block";
    const upperNumbers = isAdultTeeth
      ? adultTeethNumbers.upper
      : childTeethNumbers.upper;
    const lowerNumbers = isAdultTeeth
      ? adultTeethNumbers.lower
      : childTeethNumbers.lower;

    for (let i = 0; i < Math.min(16, upperNumbers.length); i++) {
      const normalizedPosUpper = i / 7.5 - 1;
      let xUpper = startX + (archWidth / 16) * i;
      let yUpper = startY - archHeight * (1 - Math.pow(normalizedPosUpper, 2));
      ctx.fillText(
        upperNumbers[i],
        xUpper - 5 * scaleFactor,
        yUpper - 25 * scaleFactor
      );
    }

    for (let i = 0; i < Math.min(16, lowerNumbers.length); i++) {
      const normalizedPosLower = i / 7.5 - 1;
      let xLower = startX + (archWidth / 16) * i;
      let yLower =
        startY +
        180 * scaleFactor +
        archHeight * (1 - Math.pow(normalizedPosLower, 2));
      ctx.fillText(
        lowerNumbers[i],
        xLower - 5 * scaleFactor,
        yLower + 35 * scaleFactor
      );
    }
  }

  // Position the diagram
  const canvasWidth = canvas.width;
  const canvasHeight = canvas.height;
  const centeredX = (canvasWidth - diagramWidth) / 2; // Center horizontally
  const centeredY = 280; // Move down by 80px from original position (was 200)

  drawTeethDiagram(ctx, centeredX, centeredY, markedTeeth);

  // Draw descriptions at a lower position as well
  ctx.fillStyle = "#000";
  ctx.font = "16px Arial";
  ctx.fillText("Teeth Descriptions:", 50, 730); // Move down from 650
  let descriptionY = 750; // Move down from 670

  // Helper function to wrap text on canvas
  function wrapText(text, x, y, maxWidth, lineHeight) {
    const words = text.split(" ");
    let line = "";
    let testLine = "";
    let lineCount = 0;

    for (let n = 0; n < words.length; n++) {
      testLine = line + words[n] + " ";
      const metrics = ctx.measureText(testLine);
      const testWidth = metrics.width;

      if (testWidth > maxWidth && n > 0) {
        ctx.fillText(line, x, y);
        line = words[n] + " ";
        y += lineHeight;
        lineCount++;
      } else {
        line = testLine;
      }
    }

    ctx.fillText(line, x, y);
    return { finalY: y + lineHeight, lineCount: lineCount + 1 };
  }

  const maxDescriptionWidth = canvas.width - 100;

  chartData.upper.concat(chartData.lower).forEach((tooth) => {
    if (tooth.comments && tooth.comments.length > 0) {
      const toothName = toothNames[tooth.number] || `Tooth ${tooth.number}`;

      // Draw tooth header
      ctx.font = "bold 16px Arial";
      ctx.fillStyle = "#000";
      ctx.fillText(`${tooth.number} - ${toothName}:`, 50, descriptionY);
      descriptionY += 25;

      // Draw each comment with wrapped text
      ctx.font = "16px Arial";
      tooth.comments.forEach((comment) => {
        // Create the comment text with region
        const commentText = `- ${comment.region}: ${comment.comment}`;

        // Set the status-based color
        ctx.fillStyle = comment.status === "completed" ? "#0000FF" : "#FF0000";

        // Draw the wrapped comment text
        const result = wrapText(
          commentText,
          70,
          descriptionY,
          maxDescriptionWidth,
          25
        );
        descriptionY = result.finalY + 5; // Add a small gap between comments

        // Reset text color
        ctx.fillStyle = "#000";
      });

      // Add some space after all comments for a tooth
      descriptionY += 10;
    }
  });

  if (descriptionY > canvas.height - 50) {
    // Resize the canvas if needed
    const newHeight = descriptionY + 50;
    const tempCanvas = document.createElement("canvas");
    tempCanvas.width = canvas.width;
    tempCanvas.height = newHeight;
    const tempCtx = tempCanvas.getContext("2d");

    // Copy the content from the original canvas
    tempCtx.drawImage(canvas, 0, 0);

    // Update canvas size
    canvas.height = newHeight;
    ctx.drawImage(tempCanvas, 0, 0);

    // Restore context settings
    ctx.fillStyle = "#000";
    ctx.font = "16px Arial";
  }

  // NEW CODE: Show a preview of the chart before saving
  // Remove any existing preview modal
  document.querySelector(".chart-preview-modal")?.remove();
  document.querySelector(".modal-overlay")?.remove();

  // Create overlay background
  const overlay = document.createElement("div");
  overlay.className = "modal-overlay";

  // Create preview modal
  const modal = document.createElement("div");
  modal.className = "chart-preview-modal";

  function createModalForMobile(modal) {
    if (isMobile) {
      // Get device screen dimensions
      const screenWidth = window.innerWidth;

      // For extremely small screens, use even smaller sizes
      if (screenWidth <= 360) {
        modal.style.width = "98%";
        modal.style.maxWidth = "98%";
        modal.style.padding = "5px";
      } else {
        // For other mobile screens
        modal.style.width = "95%";
        modal.style.maxWidth = "95%";
        modal.style.padding = "8px";
      }

      // Apply additional mobile-specific styles
      modal.style.margin = "0";
      modal.style.maxHeight = "85vh";
      modal.style.fontSize = "14px";

      // Override any potentially problematic styles
      modal.style.left = "50%";
      modal.style.right = "auto";
      modal.style.transform = "translate(-50%, -50%) scale(0.98)";

      // Make sure this function is called BEFORE adding any HTML content
      setTimeout(() => {
        // Fix button styling
        const buttons = modal.querySelectorAll(".btn");
        buttons.forEach((btn) => {
          btn.style.margin = "4px 0";
          btn.style.padding = "10px";
          btn.style.width = "100%";
          btn.style.fontSize = "14px";
          btn.style.minHeight = "40px";
        });

        // Reduce padding in elements
        const contentElements = modal.querySelectorAll('div[class*="content"]');
        contentElements.forEach((el) => {
          el.style.padding = "5px";
        });

        // Smaller header
        const headers = modal.querySelectorAll("h2");
        headers.forEach((h) => {
          h.style.fontSize = "16px";
          h.style.margin = "0 0 8px 0";
        });
      }, 0);
    }
  }

  // Call this when you create your preview modal
  createModalForMobile(modal);

  modal.innerHTML = `
  <h2>Preview Chart</h2>
  <div class="preview-container" style="text-align:center; margin-bottom:20px;"></div>
  <div style="text-align:center; margin-top: 20px;">
    <button id="confirmSave" class="btn btn-success">
      <i class="fas fa-check"></i> ${
        redirect ? "Save & Return to Treatment" : "Save & Continue Editing"
      }
    </button>
    <button id="cancelSave" class="btn btn-secondary">
      <i class="fas fa-times"></i> Cancel
    </button>
  </div>
`;

  // Append elements
  document.body.appendChild(overlay);
  document.body.appendChild(modal);

  // Add the canvas preview
  const previewContainer = modal.querySelector(".preview-container");

  // In the preview code:
  if (isMobile) {
    // Ensure the canvas preview fits well on mobile
    canvas.style.maxWidth = "100%";
    canvas.style.height = "auto";

    // Add horizontal scrolling container for the canvas if needed
    const scrollContainer = document.createElement("div");
    scrollContainer.style.overflowX = "auto";
    scrollContainer.style.width = "100%";
    scrollContainer.style.WebkitOverflowScrolling = "touch";
    scrollContainer.appendChild(canvas);
    previewContainer.appendChild(scrollContainer);
  } else {
    previewContainer.appendChild(canvas);
  }

  // Return a Promise that resolves when user confirms
  return new Promise((resolve, reject) => {
    // Handle save confirmation
    document.getElementById("confirmSave").addEventListener("click", () => {
      // Convert canvas to an image and save
      const dataURL = canvas.toDataURL();

      // Remove the preview
      document.body.removeChild(modal);
      document.body.removeChild(overlay);

      // Proceed with saving to server
      fetch("save_chart.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          appointment_id: appointmentId,
          chart: dataURL,
          tooth_data: chartData,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            clearLocalStorage();
            if (redirect) {
              showNotification(
                "Chart saved successfully! Returning to treatments...",
                "success"
              );
              setTimeout(
                () => (window.location.href = "../treatment.php"),
                1500
              );
            } else {
              showNotification(
                "Chart saved successfully! You can continue editing.",
                "success"
              );
            }
            resolve(true);
          } else {
            showNotification(`Failed to save chart: ${data.error}`, "error");
            resolve(false);
          }
        })
        .catch((error) => {
          showNotification(`Failed to save chart: ${error.message}`, "error");
          resolve(false);
        });
    });

    // Handle cancel
    document.getElementById("cancelSave").addEventListener("click", () => {
      document.body.removeChild(modal);
      document.body.removeChild(overlay);
      reject("Save canceled");
    });

    // Also handle clicking on overlay
    overlay.addEventListener("click", () => {
      document.body.removeChild(modal);
      document.body.removeChild(overlay);
      reject("Save canceled");
    });
  });
}

// Add this at the end of your file for the submit button
document.addEventListener("DOMContentLoaded", function () {
  // Get the submit button
  const submitButton = document.getElementById("submitChart");

  if (submitButton) {
    submitButton.addEventListener("click", () => {
      // Call saveChart function with redirect=true to return to treatment page after saving
      saveChart(true).catch((err) => {
        console.log("Save chart was canceled:", err);
      });
    });
  } else {
    console.error("Submit chart button not found!");
  }
});

// Helper function
function extractToothData(jaw) {
  return Array.from(jaw.childNodes).map((tooth) => {
    const toothNumber = tooth.getAttribute("data-tooth-number");
    let comments = [];

    if (tooth.hasAttribute("data-comments")) {
      comments = JSON.parse(tooth.getAttribute("data-comments"));
    } else if (
      tooth.hasAttribute("data-comment") &&
      tooth.getAttribute("data-comment")
    ) {
      // Legacy support for old single-comment format
      comments = [
        {
          region: tooth.getAttribute("data-region") || "",
          comment: tooth.getAttribute("data-comment") || "",
        },
      ];
    }

    return {
      number: toothNumber,
      comments: comments,
    };
  });
}

// Add this to your chart.js file where you initialize the canvas
const drawingCanvas = document.getElementById("drawingCanvas");

// Make the canvas ignore pointer events when not in drawing mode
drawingCanvas.style.pointerEvents = "none";

// When you want to enable drawing mode:
function enableDrawingMode() {
  drawingCanvas.style.pointerEvents = "auto";
}

// When you want to disable drawing mode:
function disableDrawingMode() {
  drawingCanvas.style.pointerEvents = "none";
}

// Replace the existing clearDrawing function with this one
function clearDrawing() {
  // Find the existing saved chart image
  const existingImg = document.getElementById("loadedChartImage");
  if (existingImg) {
    // Remove the existing chart image
    existingImg.parentNode.removeChild(existingImg);

    // Reset any tooth markings/comments if needed
    const allTeeth = document.querySelectorAll(".tooth");
    allTeeth.forEach((tooth) => {
      tooth.removeAttribute("data-comments");
      tooth.removeAttribute("data-region");
      tooth.removeAttribute("data-comment");

      // Remove comment indicators
      const commentIndicator = tooth.querySelector(".comment");
      if (commentIndicator) {
        tooth.removeChild(commentIndicator);
      }
    });

    showNotification("Chart cleared successfully", "success");
  } else {
    showNotification("No chart to clear", "info");
  }
}

// Add this to your chart.js file to enable pinch-zooming on the chart
function enableMobilePinchZoom() {
  if (!isMobile) return;

  const chartContainer = document.querySelector(".chart-container");
  let initialDistance = 0;
  let currentScale = 1;

  chartContainer.addEventListener("touchstart", function (e) {
    if (e.touches.length === 2) {
      initialDistance = getDistance(e.touches[0], e.touches[1]);
    }
  });

  chartContainer.addEventListener("touchmove", function (e) {
    if (e.touches.length === 2) {
      const currentDistance = getDistance(e.touches[0], e.touches[1]);
      const newScale = currentScale * (currentDistance / initialDistance);

      // Limit scale to reasonable bounds
      if (newScale >= 0.5 && newScale <= 2) {
        const chart = document.getElementById("chart");
        chart.style.transform = `scale(${newScale})`;
      }

      initialDistance = currentDistance;
      currentScale = newScale;
      e.preventDefault(); // Prevent page scrolling
    }
  });

  function getDistance(touch1, touch2) {
    const dx = touch1.clientX - touch2.clientX;
    const dy = touch1.clientY - touch2.clientY;
    return Math.sqrt(dx * dx + dy * dy);
  }
}

// Call this function when the page loads
document.addEventListener("DOMContentLoaded", function () {
  enableMobilePinchZoom();
});

// Add this function to ensure the loadedChartImage is responsive
function makeChartImageResponsive() {
  const chartImage = document.getElementById("loadedChartImage");
  if (chartImage) {
    chartImage.style.maxWidth = "100%";
    chartImage.style.height = "auto";
    chartImage.style.display = "block";
    chartImage.style.margin = "20px auto 0";

    // If on mobile, make it scrollable if too large
    if (isMobile) {
      const imageContainer = document.createElement("div");
      imageContainer.style.overflowX = "auto";
      imageContainer.style.WebkitOverflowScrolling = "touch"; // Smooth scrolling on iOS
      imageContainer.style.marginTop = "20px";
      imageContainer.style.maxWidth = "100%";

      // If image is already in the DOM, wrap it
      if (chartImage.parentNode) {
        chartImage.parentNode.insertBefore(imageContainer, chartImage);
        imageContainer.appendChild(chartImage);
      }
    }
  }
}

// Call this function when loading an image
document.addEventListener("DOMContentLoaded", function () {
  // Initial call for any images already in the DOM
  makeChartImageResponsive();

  // Set up a MutationObserver to watch for new images being added
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.addedNodes) {
        mutation.addedNodes.forEach((node) => {
          if (node.id === "loadedChartImage") {
            makeChartImageResponsive();
          }
        });
      }
    });
  });

  // Start observing the document body for changes
  observer.observe(document.body, { childList: true });
});
