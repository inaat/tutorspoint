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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="privacy-page-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <?php include(get_stylesheet_directory() . '/oheader.php'); ?>

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
            <?php include(get_stylesheet_directory() . "/ofooter.php"); ?>

</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<script>

</script>

<?php wp_footer(); ?>
</body>
</html>
