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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="terms-page-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <?php include(get_stylesheet_directory() . '/oheader.php'); ?>

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
               <?php include(get_stylesheet_directory() . "/ofooter.php"); ?>

</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<script>

</script>

<?php wp_footer(); ?>
</body>
</html>
