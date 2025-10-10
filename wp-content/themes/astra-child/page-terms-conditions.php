<?php
/**
 * Template Name: Terms & Conditions
 * Template for Terms & Conditions page
 *
 * @package Astra Child
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<style>
    /* Hide WordPress default header/footer */
    .site-header,
    .site-footer,
    #masthead,
    #colophon {
        display: none !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
    }

    .terms-page-wrapper {
        font-family: 'League Spartan', sans-serif;
        background: linear-gradient(180deg, #e5f4ef 0%, #d8f0e8 50%, #cceee2 100%);
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
    }

    /* Background blurred circles */
    .bg-blur {
        position: fixed;
        border-radius: 50%;
        filter: blur(120px);
        opacity: 0.4;
        pointer-events: none;
        z-index: 0;
    }

    .blur-1 {
        width: 450px;
        height: 450px;
        background: #3dba9f;
        top: -200px;
        left: -200px;
    }

    .blur-2 {
        width: 400px;
        height: 400px;
        background: #5cd4b6;
        bottom: -150px;
        right: -150px;
    }

    /* Custom Header */
    .custom-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 70px;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-radius: 20px;
        margin: 10px 20px;
        position: relative;
        z-index: 10;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo-icon {
        width: 38px;
        height: 38px;
        background: linear-gradient(135deg, #3dba9f, #5cd4b6);
        border-radius: 50%;
    }

    .logo-text {
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #000;
        text-decoration: none;
    }

    .custom-header nav ul {
        display: flex;
        gap: 38px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .custom-header nav a {
        text-decoration: none;
        color: #000;
        font-size: 13px;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .custom-header nav a:hover {
        color: #3dba9f;
    }

    .user-icon {
        width: 33px;
        height: 33px;
        border: 2px solid #3dba9f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .user-icon:hover {
        background: #3dba9f;
        transform: scale(1.1);
    }

    /* Content Container */
    .terms-content {
        max-width: 1000px;
        margin: 40px auto;
        padding: 50px 70px;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20px);
        border-radius: 22px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 1;
    }

    .terms-content h1 {
        font-size: 42px;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
        text-align: center;
    }

    .terms-content .effective-date {
        text-align: center;
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
        font-weight: 500;
    }

    .terms-content .company-name {
        text-align: center;
        font-size: 16px;
        color: #3dba9f;
        font-weight: 600;
        margin-bottom: 40px;
    }

    .terms-content h2 {
        font-size: 26px;
        font-weight: 700;
        color: #3dba9f;
        margin-top: 40px;
        margin-bottom: 18px;
    }

    .terms-content h3 {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-top: 28px;
        margin-bottom: 14px;
    }

    .terms-content h4 {
        font-size: 17px;
        font-weight: 600;
        color: #555;
        margin-top: 22px;
        margin-bottom: 12px;
    }

    .terms-content p {
        font-size: 15px;
        line-height: 1.8;
        color: #555;
        margin-bottom: 18px;
        font-weight: 500;
    }

    .terms-content ul {
        margin: 18px 0;
        padding-left: 28px;
    }

    .terms-content ul li {
        font-size: 15px;
        line-height: 1.8;
        color: #555;
        margin-bottom: 12px;
        font-weight: 500;
    }

    .terms-content ul ul {
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .terms-content a {
        color: #3dba9f;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .terms-content a:hover {
        color: #2da889;
        text-decoration: underline;
    }

    .terms-content strong {
        color: #333;
        font-weight: 700;
    }

    .section-divider {
        border-top: 2px solid #e0e0e0;
        margin: 50px 0;
    }

    /* Footer */
    .custom-footer {
        background: #d8f0e8;
        padding: 70px 70px 35px;
        position: relative;
        z-index: 1;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr 1fr;
        gap: 80px;
        max-width: 1400px;
        margin: 0 auto 50px;
    }

    .footer-col h4 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 18px;
        color: #000;
    }

    .footer-col p {
        font-size: 13px;
        color: #666;
        line-height: 1.8;
        font-weight: 500;
        margin-bottom: 18px;
    }

    .footer-col ul {
        list-style: none;
        padding: 0;
    }

    .footer-col ul li {
        margin: 11px 0;
    }

    .footer-col a {
        color: #666;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .footer-col a:hover {
        color: #3dba9f;
    }

    .social-icons {
        display: flex;
        gap: 13px;
        margin-top: 22px;
    }

    .social-icon {
        width: 48px;
        height: 48px;
        background: #000;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .social-icon:hover {
        background: #3dba9f;
        transform: translateY(-3px);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 28px;
        border-top: 1px solid #e0e0e0;
        font-size: 13px;
        color: #888;
        font-weight: 500;
    }

    .about-section {
        margin-top: 32px;
    }

    @media (max-width: 768px) {
        .custom-header {
            flex-direction: column;
            gap: 15px;
            padding: 15px 20px;
        }

        .custom-header nav ul {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .terms-content {
            margin: 20px;
            padding: 30px 25px;
        }

        .terms-content h1 {
            font-size: 32px;
        }

        .terms-content h2 {
            font-size: 22px;
        }

        .custom-footer {
            padding: 30px 20px 20px;
        }

        .footer-grid {
            grid-template-columns: 1fr;
            gap: 35px;
        }
    }
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<div class="terms-page-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <!-- Custom Header -->
    <header class="custom-header">
        <div class="logo">
            <div class="logo-icon"></div>
            <a href="<?php echo home_url('/'); ?>" class="logo-text">TUTORS POINT</a>
        </div>
        <nav>
            <ul>
                <li><a href="<?php echo home_url('/'); ?>">Home</a></li>
                <li><a href="<?php echo home_url('/about-us'); ?>">About Us</a></li>
                <li><a href="<?php echo home_url('/contact-us'); ?>">Contact Us</a></li>
                <li><a href="<?php echo home_url('/blog'); ?>">Blogs</a></li>
            </ul>
        </nav>

        <?php if (!is_user_logged_in()): ?>
            <div id="tp-auth-wrap-header"></div>
        <?php else:
            $user = wp_get_current_user();
            $roles = (array) $user->roles;
            $dash_url = in_array('teacher', $roles, true) ? home_url('/tutorsdashboard') :
                        (in_array('student', $roles, true) ? home_url('/student-dashboard') : admin_url());
        ?>
            <a href="<?php echo esc_url($dash_url); ?>" class="user-icon" title="Dashboard">👤</a>
        <?php endif; ?>
    </header>

    <!-- Terms & Conditions Content -->
    <div class="terms-content">
        <h1>Terms & Conditions</h1>
        <p class="effective-date"><strong>Effective Date:</strong> <?php echo date('F d, Y'); ?></p>
        <p class="company-name">Tutors Point Ltd</p>

        <h2>1. Overview</h2>
        <p>Tutors Point is modeled after UK platforms like MyTutor. We facilitate live, 1-to-1 tutoring via integrated video (e.g., ZEGOCLOUD). By using our site, you agree to these Terms.</p>

        <h2>2. Accounts & Verification</h2>
        <ul>
            <li><strong>Students/Parents:</strong> provide accurate details; minors need guardian oversight.</li>
            <li><strong>Tutors:</strong> must verify identity/qualifications, pass DBS or equivalent (if tutoring minors), and agree to AI-monitoring and screenshot policies.</li>
        </ul>

        <h2>3. Booking, Payment & Pricing</h2>
        <ul>
            <li>Book lessons through the platform.</li>
            <li>Flat rates: £15/hr (junior) · £20/hr (GCSE) · £25/hr (A-Level).</li>
            <li>Payments via Stripe; must be received before lesson start.</li>
            <li>Tutors Point remits tutor payments after deducting any platform fees.</li>
        </ul>

        <h2>4. Cancellation & Refund Policy</h2>
        <ul>
            <li>Full refund if cancelled before 24 hours of the booked session.</li>
            <li>No refund within 24 hours unless tutor fails to attend — in which case you get a full refund.</li>
            <li>First lesson satisfaction guarantee: refundable if not satisfied.</li>
        </ul>

        <h2>5. Live Monitoring</h2>
        <ul>
            <li>Sessions are monitored in real time by AI word detection.</li>
            <li>Screenshots every 3 seconds are captured and immediately discarded unless flagged for review.</li>
            <li>This is legal and consented to by all users when they join a session.</li>
        </ul>

        <h2>6. Recording & Materials</h2>
        <ul>
            <li>Sessions may be recorded for progress tracking; recordings deleted in 7 days.</li>
            <li>Tutors retain IP on their original materials but grant students access for session purposes.</li>
        </ul>

        <h2>7. Conduct Rules</h2>
        <ul>
            <li>Respect is mandatory.</li>
            <li>No sharing of private contact info.</li>
            <li>Misconduct or breach of policy may result in warning, suspension, or permanent ban.</li>
        </ul>

        <h2>8. Disclaimers & Liabilities</h2>
        <ul>
            <li>Tutors Point is a facilitator, not liable for student performance.</li>
            <li>Maximum liability is limited to fees paid.</li>
            <li>No indirect, special, or consequential damages.</li>
        </ul>

        <h2>9. Safeguarding</h2>
        <ul>
            <li>Tutors must undergo necessary background checks.</li>
            <li>Recordings and screenshots support safeguarding compliance.</li>
            <li>Report any safeguarding concerns to <a href="mailto:safeguarding@tutorspoint.co.uk">safeguarding@tutorspoint.co.uk</a>.</li>
        </ul>

        <h2>10. Termination</h2>
        <p>We may terminate accounts for breaches, fraud, or policy violations with no refund.</p>

        <h2>11. Intellectual Property</h2>
        <ul>
            <li><strong>Site content:</strong> owned by Tutors Point.</li>
            <li><strong>Tutor content:</strong> owned by tutor; licensed to students for educational use.</li>
        </ul>

        <h2>12. Governing Law</h2>
        <p>These Terms are governed by England & Wales law. Disputes go to courts there.</p>

        <div class="section-divider"></div>

        <h2>📜 Tutor Agreement</h2>
        <p>This Agreement is made on <?php echo date('F d, Y'); ?> between:</p>
        <p><strong>TutorsPoint Ltd</strong>, a company registered in England and Wales with its registered office at [Insert Address] ("the Company");</p>
        <p>and</p>
        <p><strong>[Tutor Name]</strong>, residing at [Tutor Address] ("the Tutor").</p>

        <h3>1. Engagement</h3>
        <p>1.1 The Company engages the Tutor as an independent contractor to provide online tutoring services via the TutorsPoint platform.</p>
        <p>1.2 Nothing in this Agreement creates an employment relationship, partnership, or agency between the parties.</p>

        <h3>2. Services</h3>
        <p>2.1 The Tutor shall deliver 1-to-1 online tutoring sessions in [Subject(s)] at the times booked by students via the platform.</p>
        <p>2.2 The Tutor agrees to:</p>
        <ul>
            <li>Prepare and deliver lessons professionally;</li>
            <li>Use only the platform-approved tools (e.g., ZEGOCLOUD video, whiteboard, monitoring systems);</li>
            <li>Refrain from sharing personal contact details (phone, email, social media) with students or parents;</li>
            <li>Maintain safeguarding standards at all times.</li>
        </ul>

        <h3>3. Safeguarding & Conduct</h3>
        <p>3.1 Tutors must maintain high ethical and professional standards.</p>
        <p>3.2 Tutors working with under-18s must comply with safeguarding procedures.</p>
        <ul>
            <li><strong>UK-based tutors:</strong> valid DBS check required.</li>
            <li><strong>Overseas tutors:</strong> valid Criminal Record Certificate / Police Clearance / Character Certificate from the relevant authority.</li>
        </ul>
        <p>3.3 Sessions are subject to AI monitoring (word scanning + automated screenshots every 3 seconds). By signing this Agreement, the Tutor consents to such monitoring.</p>

        <h3>4. Payment</h3>
        <p>4.1 TutorsPoint shall pay the Tutor a fixed hourly rate of £[Rate] per hour for each completed lesson.</p>
        <p>4.2 Payments will be made monthly in arrears to the Tutor's nominated bank account (or payment service such as Wise/Payoneer).</p>
        <p>4.3 Tutors are responsible for paying all applicable taxes in their own country. TutorsPoint shall not deduct PAYE, NI, or similar contributions.</p>

        <h3>5. Cancellations & Refunds</h3>
        <p>5.1 If a student cancels ≥24 hours in advance, no payment is made for that lesson.</p>
        <p>5.2 If a student cancels within 24 hours, payment is due unless TutorsPoint issues a discretionary refund.</p>
        <p>5.3 If the Tutor cancels or fails to attend, no payment is due and repeated failures may lead to termination.</p>

        <h3>6. Lesson Recording & Data</h3>
        <p>6.1 At present, TutorsPoint does not record lessons. If lesson recording is introduced in future, recordings will be retained for max 30 days and then automatically deleted, unless flagged for safeguarding.</p>
        <p>6.2 Tutors consent to the processing of their personal data under TutorsPoint's Privacy Policy.</p>

        <h3>7. Confidentiality</h3>
        <p>7.1 The Tutor agrees not to disclose any confidential information relating to TutorsPoint, students, or parents.</p>
        <p>7.2 Confidentiality obligations continue after termination of this Agreement.</p>

        <h3>8. Intellectual Property</h3>
        <p>8.1 Lesson plans, teaching materials, and resources created by the Tutor remain the Tutor's property.</p>
        <p>8.2 The Tutor grants the student a limited, non-transferable license to use such materials for personal study only.</p>

        <h3>9. Term & Termination</h3>
        <p>9.1 This Agreement begins on <?php echo date('F d, Y'); ?> and continues until terminated.</p>
        <p>9.2 Either party may terminate with 14 days' written notice.</p>
        <p>9.3 TutorsPoint may terminate immediately in cases of misconduct, safeguarding concerns, or breach of this Agreement.</p>

        <h3>10. Liability</h3>
        <p>10.1 The Tutor shall perform services to a professional standard but does not guarantee exam outcomes.</p>
        <p>10.2 TutorsPoint's liability is limited to amounts paid for lessons.</p>

        <h3>11. Governing Law</h3>
        <p>11.1 This Agreement is governed by the laws of England and Wales.</p>
        <p>11.2 Disputes shall be subject to the exclusive jurisdiction of the courts of England and Wales.</p>

        <div class="section-divider"></div>

        <h2>📜 Safeguarding Policy</h2>
        <p class="effective-date"><strong>Effective Date:</strong> <?php echo date('F d, Y'); ?></p>
        <p>TutorsPoint Ltd is committed to ensuring the safety and welfare of children and young people using our services. Safeguarding is central to our mission.</p>

        <h3>1. Tutor Vetting</h3>
        <ul>
            <li><strong>UK Tutors:</strong> must hold a valid DBS certificate.</li>
            <li><strong>Overseas Tutors:</strong> must provide a Criminal Record Certificate / Police Clearance / Character Certificate issued by the relevant national authority.</li>
            <li>Verification is compulsory before onboarding.</li>
        </ul>

        <h3>2. Live Session Monitoring</h3>
        <ul>
            <li>All lessons are conducted via ZEGOCLOUD with built-in AI safeguards.</li>
            <li>Real-time word scanning flags inappropriate or unsafe language.</li>
            <li>Automated screenshots every 3 seconds are reviewed by AI for safeguarding breaches. If no issue is detected, screenshots are immediately deleted.</li>
        </ul>

        <h3>3. Lesson Recording</h3>
        <ul>
            <li>Currently not in place due to cost constraints.</li>
            <li>If recording is introduced in future: retention = 30 days, then auto-deletion.</li>
        </ul>

        <h3>4. Parental Consent & Responsibility</h3>
        <ul>
            <li>Students under 18 require parental consent.</li>
            <li>Parents are encouraged to supervise younger learners during online lessons.</li>
        </ul>

        <h3>5. Reporting Concerns</h3>
        <p>Concerns must be reported to: <a href="mailto:safeguarding@tutorspoint.co.uk">📧 safeguarding@tutorspoint.co.uk</a></p>
        <p>TutorsPoint will escalate safeguarding concerns to UK or international authorities where necessary.</p>

        <h3>6. Zero Tolerance</h3>
        <p>TutorsPoint has a zero-tolerance policy for abuse, exploitation, or harassment. Breaches will result in immediate termination and possible referral to authorities.</p>

        <div class="section-divider"></div>

        <h2>📜 Refund & Cancellation Policy</h2>
        <p class="effective-date"><strong>Effective Date:</strong> <?php echo date('F d, Y'); ?></p>
        <p>At TutorsPoint, fairness and transparency are fundamental.</p>

        <h3>1. Student Cancellations</h3>
        <ul>
            <li>≥24 hours notice → full refund.</li>
            <li>&lt;24 hours notice → no refund, unless exceptional circumstances.</li>
        </ul>

        <h3>2. Tutor Cancellations</h3>
        <p>If a tutor cancels, students will receive a full refund or free rescheduling.</p>

        <h3>3. First Lesson Guarantee</h3>
        <p>If you are unsatisfied with your first paid lesson, you are entitled to a full refund.</p>

        <h3>4. Technical Issues</h3>
        <ul>
            <li>If a session fails due to TutorsPoint's systems, a refund or reschedule will be offered.</li>
            <li>No refunds for issues caused by student internet/device problems.</li>
        </ul>

        <h3>5. Refund Process</h3>
        <ul>
            <li>Refunds are processed via Stripe or Apple Pay (depending on original payment method).</li>
            <li>Processing time: typically 5–10 working days.</li>
        </ul>

        <div class="section-divider"></div>

        <h2>📜 Cookie Policy</h2>
        <p class="effective-date"><strong>Effective Date:</strong> <?php echo date('F d, Y'); ?></p>
        <p>TutorsPoint uses cookies to provide the best possible experience.</p>

        <h3>1. What Are Cookies?</h3>
        <p>Cookies are small files stored on your device to help our website function effectively.</p>

        <h3>2. Types of Cookies We Use</h3>
        <ul>
            <li><strong>Strictly Necessary Cookies</strong> – essential for logins, bookings, and payments.</li>
            <li><strong>Performance Cookies</strong> – to measure and improve website functionality.</li>
            <li><strong>Functional Cookies</strong> – to remember user preferences.</li>
            <li><strong>Analytics Cookies</strong> – anonymised tracking of user behaviour.</li>
            <li><strong>Marketing Cookies</strong> – only used with explicit consent.</li>
        </ul>

        <h3>3. Managing Cookies</h3>
        <ul>
            <li>Manage via our cookie banner or your browser settings.</li>
            <li>Disabling cookies may reduce functionality of the platform.</li>
        </ul>
    </div>

    <!-- Custom Footer -->
    <footer class="custom-footer">
        <div class="footer-grid">
            <!-- Column 1: Description + Social Icons -->
            <div class="footer-col">
                <p>Tuitional is an Online Ed-Tech Platform. We do live tutoring classes for Grades 4-8, IGCSE, GCSE, & A-Levels etc for all boards like Cambridge, Pearson Edexcel</p>
                <div class="social-icons">
                    <div class="social-icon">f</div>
                    <div class="social-icon">📷</div>
                    <div class="social-icon">in</div>
                </div>
            </div>

            <!-- Column 2: Curriculums -->
            <div class="footer-col">
                <h4>Curriculums</h4>
                <ul>
                    <li><a href="#">IGCSE Tuition</a></li>
                    <li><a href="#">IB Tuition</a></li>
                    <li><a href="#">PSLE Tuition</a></li>
                    <li><a href="#">Singapore O/A Level Tuition</a></li>
                    <li><a href="#">SAT Tuition</a></li>
                </ul>
            </div>

            <!-- Column 3: Subjects from database -->
            <div class="footer-col">
                <h4>Subjects</h4>
                <ul>
                    <?php
                    global $wpdb;
                    $footer_subjects = $wpdb->get_results("SELECT subject_id, SubjectName FROM wpC_subjects ORDER BY SubjectName LIMIT 8", ARRAY_A);
                    foreach ($footer_subjects as $footer_subject):
                    ?>
                        <li><a href="<?php echo home_url('/listofteachers/?subject=' . urlencode($footer_subject['SubjectName']) . '&subject_id=' . $footer_subject['subject_id']); ?>"><?php echo esc_html($footer_subject['SubjectName']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Column 4: Get Help + About us -->
            <div class="footer-col">
                <h4>Get Help</h4>
                <ul>
                    <li><a href="#">Features</a></li>
                    <li><a href="<?php echo home_url('/privacy-policy'); ?>">Privacy policy</a></li>
                    <li><a href="<?php echo home_url('/terms-conditions'); ?>">Terms & Conditions</a></li>
                </ul>
                <div class="about-section">
                    <h4>About us</h4>
                    <ul>
                        <li><a href="<?php echo home_url('/about-us'); ?>">Company</a></li>
                        <li><a href="#">Careers</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            All Rights Reserved ©<?php echo date('Y'); ?> Tutors Point
        </div>
    </footer>
</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<script>
// Move the auth button to header
document.addEventListener('DOMContentLoaded', function() {
    const authWrap = document.getElementById('tp-auth-wrap');
    const headerTarget = document.getElementById('tp-auth-wrap-header');

    if (authWrap && headerTarget) {
        headerTarget.innerHTML = authWrap.innerHTML;
        authWrap.style.display = 'none';

        const headerBtn = headerTarget.querySelector('#tp-open-auth');
        if (headerBtn) {
            headerBtn.addEventListener('click', function() {
                const modal = document.getElementById('tp-auth-modal');
                if (modal) {
                    modal.classList.add('open');
                    modal.removeAttribute('aria-hidden');
                    setTimeout(() => {
                        const emailInput = document.getElementById('tp-login-email');
                        if (emailInput) emailInput.focus();
                    }, 50);
                }
            });
        }
    }
});
</script>

<?php wp_footer(); ?>
</body>
</html>
