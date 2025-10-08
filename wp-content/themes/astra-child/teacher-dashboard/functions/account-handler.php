if (isset($_POST['update_teacher_info'])) {
    global $wpdb;

    $wpdb->update('wpC_teachers_main', [
        'Phone'             => sanitize_text_field($_POST['phone']),
        'WhatsappNo'        => sanitize_text_field($_POST['whatsapp_no']),
        'Qualification'     => sanitize_text_field($_POST['qualification']),
        'UniversityName'    => sanitize_text_field($_POST['university_name']),
        'Country'           => sanitize_text_field($_POST['country']),
        'BankName'          => sanitize_text_field($_POST['bank_name']),
        'BankAccountNumber' => sanitize_text_field($_POST['bank_account_number']),
    ], [
        'Email' => $current_user->user_email
    ]);

    echo "<script>setTimeout(() => alert('✅ Your info was updated successfully.'), 300);</script>";
}
