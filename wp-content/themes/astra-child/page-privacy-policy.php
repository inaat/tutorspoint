<?php
/**
 * Template Name: Privacy Policy
 * Template for Privacy Policy page
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
    <title>Privacy Policy - <?php bloginfo('name'); ?></title>
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

    .privacy-page-wrapper {
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
    .privacy-content {
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

    .privacy-content h1 {
        font-size: 42px;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
        text-align: center;
    }

    .privacy-content .effective-date {
        text-align: center;
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
        font-weight: 500;
    }

    .privacy-content .company-name {
        text-align: center;
        font-size: 16px;
        color: #3dba9f;
        font-weight: 600;
        margin-bottom: 40px;
    }

    .privacy-content h2 {
        font-size: 26px;
        font-weight: 700;
        color: #3dba9f;
        margin-top: 40px;
        margin-bottom: 18px;
    }

    .privacy-content h3 {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-top: 28px;
        margin-bottom: 14px;
    }

    .privacy-content p {
        font-size: 15px;
        line-height: 1.8;
        color: #555;
        margin-bottom: 18px;
        font-weight: 500;
    }

    .privacy-content ul {
        margin: 18px 0;
        padding-left: 28px;
    }

    .privacy-content ul li {
        font-size: 15px;
        line-height: 1.8;
        color: #555;
        margin-bottom: 12px;
        font-weight: 500;
    }

    .privacy-content ul ul {
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .privacy-content a {
        color: #3dba9f;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .privacy-content a:hover {
        color: #2da889;
        text-decoration: underline;
    }

    .privacy-content strong {
        color: #333;
        font-weight: 700;
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

        .privacy-content {
            margin: 20px;
            padding: 30px 25px;
        }

        .privacy-content h1 {
            font-size: 32px;
        }

        .privacy-content h2 {
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

<div class="privacy-page-wrapper">
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

    <!-- Privacy Policy Content -->
    <div class="privacy-content">
        <h1>Privacy Policy</h1>
        <p class="effective-date"><strong>Effective Date:</strong> <?php echo date('F d, Y'); ?></p>
        <p class="company-name">Tutors Point Ltd</p>

        <h2>1. Introduction</h2>
        <p>Tutors Point ("we", "us") is a UK platform offering 1-to-1 live tutoring for UK students (KS1–A-Level). This policy explains how we collect, use, share, and protect personal data, drawing on practices at MyTutor.co.uk and aligning with UK GDPR and the Data Protection Act 2018.</p>

        <h2>2. Personal Data We Collect</h2>
        <ul>
            <li><strong>Students & Parents:</strong> Name, contact details, billing info, lesson history, communications.</li>
            <li><strong>Tutors:</strong> Name, email, CV, qualifications, ID, DBS (if UK), teaching profile, and performance data.</li>
            <li><strong>Session Content:</strong>
                <ul>
                    <li>Real-time word-level transcripts for monitoring language (via ZEGOCLOUD AI)</li>
                    <li>Screenshots every 3 seconds, only optionally stored if flagged; otherwise automatically deleted.</li>
                </ul>
            </li>
            <li><strong>Technical Data:</strong> IP, browser, device, cookies, usage patterns.</li>
        </ul>

        <h2>3. Purposes & Legal Basis for Processing</h2>
        <ul>
            <li><strong>Service delivery & contractual necessity</strong> – matching, scheduling, conducting, and billing lessons.</li>
            <li><strong>Legal obligations</strong> – safeguarding, tax compliance, record keeping.</li>
            <li><strong>Legitimate interests</strong> – improving platform functionality, safeguarding content integrity (e.g., monitoring during sessions).</li>
            <li><strong>Consent</strong> – for marketing, recorded sessions, and screenshot usage beyond default monitoring.</li>
        </ul>

        <h2>4. Use of ZEGOCLOUD Monitoring Mechanisms</h2>
        <ul>
            <li>AI word monitoring ensures ethical behavior—any flagged language is reviewed and appropriate action taken.</li>
            <li>Automated screenshots taken every 3 seconds during live sessions. These are processed by AI for inappropriate content and immediately deleted if no issue is detected.</li>
        </ul>

        <h2>5. How We Share Data</h2>
        <p><strong>Shared with:</strong></p>
        <ul>
            <li><strong>Payment platforms</strong> – Stripe & Apple Pay (for billing purposes)</li>
            <li><strong>ZEGOCLOUD</strong> – for session delivery and monitoring under signed confidentiality and data processing agreements</li>
            <li>Hosting providers</li>
            <li>Legal/Regulatory bodies as required (e.g., safeguarding authorities)</li>
        </ul>

        <h2>6. International Transfers</h2>
        <p>Tutors Point employs tutors globally (e.g., Pakistan, India). We conduct transfers to third countries under UK GDPR-compliant safeguards (e.g., Standard Contractual Clauses).</p>

        <h2>7. Data Retention Policy</h2>
        <ul>
            <li><strong>Lesson recordings & screenshots:</strong> processed and deleted within 7 days unless flagged.</li>
            <li><strong>Profile data:</strong> retained for active accounts + statutory period.</li>
            <li><strong>Financial records:</strong> stored for 6 years per HMRC requirements.</li>
        </ul>

        <h2>8. Your Data Rights</h2>
        <p>You may:</p>
        <ul>
            <li>Access, correct, delete, restrict, or port your data</li>
            <li>Withdraw marketing consent at any time</li>
        </ul>
        <p><strong>Contact:</strong> <a href="mailto:privacy@tutorspoint.co.uk">privacy@tutorspoint.co.uk</a></p>
        <p>Complaints can be made to the ICO.</p>

        <h2>9. Cookies & Tracking</h2>
        <p>Used for site performance, analytics, and processing sessions. Manage preferences via browser or cookie banner. See full details in our Cookie Policy.</p>

        <h2>10. Safeguarding Children</h2>
        <p>For users under 18, parental consent is mandatory. ZEGOCLOUD's live monitoring enhances compliance and safety.</p>

        <h2>11. Changes</h2>
        <p>We will notify changes to this policy via email or a notice on the platform.</p>
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
