<?php
$current_user = wp_get_current_user();
global $wpdb;

// ✅ Get teacher ID
$teacher = $wpdb->get_row($wpdb->prepare(
    "SELECT teacher_id FROM wpC_teachers_main WHERE Email = %s",
    $current_user->user_email
));
$teacher_id = $teacher ? (int)$teacher->teacher_id : 0;

$hourly_rate = 1000;
$today = new DateTime();

// ✅ Get completed lectures
$completed_lectures = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM wpC_student_lectures 
    WHERE teacher_id = %d AND status IN ('completed', 'retake-completed')
", $teacher_id));

// ✅ Calculate total/eligible earnings
$total_hours = 0;
$eligible_hours = 0;
foreach ($completed_lectures as $lec) {
    $lec_date = new DateTime($lec->lecture_book_date);
    $days_old = $today->diff($lec_date)->days;

    $total_hours += $lec->duration;
    if ($days_old >= 7) {
        $eligible_hours += $lec->duration;
    }
}
$total_earning = $total_hours * $hourly_rate;
$eligible_earning = $eligible_hours * $hourly_rate;

// ✅ Withdrawn amount
$withdrawn_amount = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount_withdrawal) FROM wpC_teacher_payment_received WHERE teacher_id = %d",
    $teacher_id
)) ?: 0;

// ✅ Handle new withdrawal request
if (isset($_POST['request_withdrawal']) && is_user_logged_in()) {
    $amount = intval($_POST['amount_withdrawal']);
    $wpdb->insert('wpC_teacher_payment_received', [
        'teacher_id' => $teacher_id,
        'amount_withdrawal' => $amount,
        'payment_withdrawal_date' => current_time('Y-m-d'),
        'created_at' => current_time('mysql'),
    ]);
    echo "<p style='color:green;'>✅ Withdrawal request submitted.</p>";
}

// ✅ Date filtering
$from = $_GET['from_date'] ?? '';
$to = $_GET['to_date'] ?? '';
$filtered = array_filter($completed_lectures, function ($lec) use ($from, $to) {
    if ($from && $lec->lecture_book_date < $from) return false;
    if ($to && $lec->lecture_book_date > $to) return false;
    return true;
});
?>

<div class="tab-inner">
  <h5 class="text-xl font-bold mb-4">💰 Financial Overview</h5>

  <div class="stats-cards">
    <div class="stat-card">
      <h6>Total Earnings</h6>
      <p>£ <?php echo number_format($total_earning); ?></p>
    </div>
    <div class="stat-card">
      <h6>Eligible for Payout</h6>
      <p>£ <?php echo number_format($eligible_earning); ?></p>
    </div>
    <div class="stat-card">
      <h6>Withdrawn</h6>
      <p>£ <?php echo number_format($withdrawn_amount); ?></p>
    </div>
  </div>

  <!-- 🔍 Filter -->
  <form method="get" style="display:flex; gap:15px; margin-bottom:20px;">
    <div>
      <label>From Date:</label><br>
      <input type="date" name="from_date" value="<?php echo esc_attr($from); ?>">
    </div>
    <div>
      <label>To Date:</label><br>
      <input type="date" name="to_date" value="<?php echo esc_attr($to); ?>">
    </div>
    <div>
      <button type="submit" class="export-btn">🔍 Filter</button>
      <a href="<?php echo esc_url(remove_query_arg(['from_date', 'to_date'])); ?>" class="export-btn" style="background:#777;">Reset</a>
    </div>
  </form>

  <!-- 📁 Export/Print -->
  <div style="margin-bottom:20px;">
    <button onclick="exportTableToExcel('earningsTable', 'Earnings')" class="export-btn">📁 Export to Excel</button>
    <button onclick="window.print()" class="export-btn">🖨️ Print as PDF</button>
  </div>

  <!-- 📘 Lecture-wise Table -->
  <table id="earningsTable" class="dashboard-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Time</th>
        <th>Topic</th>
        <th>Status</th>
        <th>Duration</th>
        <th>Earning</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($filtered): ?>
        <?php foreach ($filtered as $lec): ?>
          <tr>
            <td><?php echo esc_html($lec->lecture_book_date); ?></td>
            <td><?php echo esc_html($lec->lecture_time); ?></td>
            <td><?php echo esc_html($lec->topic); ?></td>
            <td><?php echo ucfirst(str_replace('-', ' ', $lec->status)); ?></td>
            <td><?php echo esc_html($lec->duration); ?> hr</td>
            <td>£ <?php echo number_format($lec->duration * $hourly_rate); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6">No lectures found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- 💳 Withdrawal Request -->
  <h5 class="mt-6 mb-2">💳 Request Withdrawal</h5>
  <form method="post" style="margin-bottom:30px;">
    <input type="number" name="amount_withdrawal" placeholder="Enter amount in Rs" required style="padding:8px; width:200px;">
    <button type="submit" name="request_withdrawal" class="export-btn">💰 Submit</button>
  </form>

  <!-- 📤 Withdrawal History -->
  <h5 class="mt-6 mb-2">📤 Withdrawal History</h5>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Amount</th>
        <th>Submitted At</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $withdrawals = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM wpC_teacher_payment_received WHERE teacher_id = %d ORDER BY created_at DESC",
        $teacher_id
      ));
      if ($withdrawals):
        foreach ($withdrawals as $w): ?>
          <tr>
            <td><?php echo esc_html($w->payment_withdrawal_date); ?></td>
            <td>£ <?php echo number_format($w->amount_withdrawal); ?></td>
            <td><?php echo esc_html($w->created_at); ?></td>
          </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="3">No withdrawals yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- 📁 Export Script -->
<script>
function exportTableToExcel(tableID, filename = '') {
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    filename = filename ? filename + '.xls' : 'exported_data.xls';

    var downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);

    if (navigator.msSaveOrOpenBlob) {
        var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}
</script>

<style>
.stats-cards {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  margin-bottom: 30px;
}
.stat-card {
  flex: 1 1 200px;
  background: #f9f9f9;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
  box-shadow: 0 1px 5px rgba(0,0,0,0.1);
}
.stat-card h6 {
  margin: 0;
  font-size: 16px;
  color: #444;
}
.stat-card p {
  font-size: 22px;
  font-weight: bold;
  margin-top: 8px;
  color: #0073aa;
}
.dashboard-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 40px;
}
.dashboard-table th, .dashboard-table td {
  padding: 10px;
  border: 1px solid #ccc;
}
.export-btn {
  padding: 8px 16px;
  background: #0073aa;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}
.export-btn:hover {
  background: #005a87;
}
.mt-6 { margin-top: 30px; }
.mb-2 { margin-bottom: 10px; }
</style>
