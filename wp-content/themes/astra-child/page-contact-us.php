<?php
/**
 * Template Name: Contact Us Page
 * Contact Us Page Template - Tutors Point
 *
 * @package Astra Child
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Don't load default header for this page
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo('name'); ?> - Contact Us</title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<style>
    /* Hide WordPress default header/footer for this page */
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

    /* Reset for this page */
    .front-page-wrapper * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .front-page-wrapper {
        font-family: 'League Spartan', sans-serif;
        background: linear-gradient(180deg, #e5f4ef 0%, #d8f0e8 50%, #cceee2 100%);
        position: relative;
        overflow-x: hidden;
        min-height: 100vh;
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
    }

    .logo {
        display: flex;
        align-items: center;
    }

    .logo svg {
        height: 30px;
        width: auto;
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
    }

    .user-icon:hover {
        background: #3dba9f;
        transform: scale(1.1);
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
    }

    /* Background blurred circles */
    .bg-blur {
        position: fixed;
        border-radius: 50%;
        filter: blur(120px);
        opacity: 0.6;
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
        top: -150px;
        right: -150px;
    }

    .blur-3 {
        width: 350px;
        height: 350px;
        background: #3dba9f;
        bottom: 150px;
        right: -100px;
    }

    .content-wrapper {
        position: relative;
        z-index: 1;
    }

    /* Hero */
    .hero {
        text-align: center;
        padding: 70px 30px 50px;
    }

    .hero-subtitle {
        font-size: 13px;
        color: #333;
        font-weight: 500;
        margin-bottom: 15px;
    }

    .hero h1 {
        font-size: 62px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 18px;
    }

    .hero h1 .affordable {
        font-weight: 300;
        font-style: italic;
        color: #5cd4b6;
    }

    .hero-ksl {
        font-size: 30px;
        font-weight: 700;
        color: #5cd4b6;
        margin: 22px 0 32px;
    }

    .btn-book-free {
        background: #3dba9f;
        color: white;
        border: none;
        padding: 14px 42px;
        font-size: 14px;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-book-free:hover {
        background: #2da889;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(61, 186, 159, 0.3);
    }

    /* Get Started Section */
    .get-started {
        padding: 70px 30px;
    }

    .section-title {
        text-align: center;
        font-size: 40px;
        font-weight: 700;
        margin-bottom: 55px;
    }

    .pricing-cards {
        display: flex;
        justify-content: center;
        gap: 25px;
        flex-wrap: wrap;
        max-width: 1150px;
        margin: 0 auto;
    }

    .pricing-card {
        background: white;
        border-radius: 22px;
        padding: 38px 28px;
        width: 250px;
        text-align: center;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .pricing-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .pricing-card h3 {
        font-size: 17px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .pricing-card .price-wrap {
        margin: 22px 0;
    }

    .pricing-card .price {
        font-size: 54px;
        font-weight: 700;
        color: #3dba9f;
        line-height: 0.9;
    }

    .pricing-card .currency {
        font-size: 32px;
    }

    .pricing-card .period {
        font-size: 12px;
        color: #999;
        margin-top: 8px;
        font-weight: 500;
    }

    .pricing-card .divider {
        width: 45px;
        height: 2px;
        background: #e5e5e5;
        margin: 22px auto;
    }

    .pricing-card ul {
        list-style: none;
        text-align: left;
        margin: 0 0 28px 0;
        font-size: 13px;
        color: #555;
    }

    .pricing-card ul li {
        margin: 9px 0;
        font-weight: 500;
    }

    .pricing-card ul li:first-child {
        font-weight: 600;
        color: #333;
    }

    .pricing-card .subjects-label {
        font-size: 11px;
        color: #888;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .btn-book-now {
        background: #3dba9f;
        color: white;
        border: none;
        padding: 11px 0;
        width: 100%;
        font-size: 13px;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-book-now:hover {
        background: #2da889;
    }

    /* Search Section */
    .search-section {
        padding: 70px 30px;
    }

    .search-container {
        max-width: 780px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.65);
        backdrop-filter: blur(30px);
        border-radius: 18px;
        padding: 42px 48px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
    }

    .search-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .search-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }

    .search-field select {
        width: 100%;
        padding: 11px 13px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 12px;
        font-family: 'League Spartan', sans-serif;
        background: white;
        font-weight: 500;
    }

    .btn-search {
        width: 100%;
        background: #3dba9f;
        color: white;
        border: none;
        padding: 13px;
        font-size: 14px;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-search:hover {
        background: #2da889;
    }

    /* Features Section */
    .features {
        padding: 70px 30px;
        max-width: 1250px;
        margin: 0 auto;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
    }

    .feature-card {
        background: white;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-10px);
    }

    .feature-img {
        height: 215px;
        background: linear-gradient(135deg, #54c7e8, #3dba9f);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .feature-img .circle {
        width: 115px;
        height: 115px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
    }

    .feature-content {
        padding: 32px 28px;
        text-align: center;
    }

    .feature-content h3 {
        font-size: 19px;
        font-weight: 700;
        margin-bottom: 14px;
        line-height: 1.3;
    }

    .feature-content p {
        font-size: 13px;
        color: #666;
        line-height: 1.7;
        font-weight: 500;
        margin-bottom: 24px;
    }

    .btn-learn {
        background: #3dba9f;
        color: white;
        border: none;
        padding: 11px 32px;
        font-size: 13px;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-learn:hover {
        background: #2da889;
    }

    /* Meet Our Tutors Section */
    .tutors-section {
        padding: 70px 30px;
        max-width: 1250px;
        margin: 0 auto;
    }

    .tutors-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 28px;
    }

    .tutor-card {
        background: white;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .tutor-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
    }

    .tutor-image {
        height: 280px;
        background-size: cover;
        background-position: center;
        position: relative;
        background-color: #e5f4ef;
    }

    .play-button {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        background: rgba(61, 186, 159, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        cursor: pointer;
        transition: all 0.3s ease;
        padding-left: 4px;
    }

    .play-button:hover {
        background: rgba(61, 186, 159, 1);
        transform: translate(-50%, -50%) scale(1.1);
    }

    .tutor-info {
        padding: 24px 20px;
        text-align: center;
    }

    .tutor-info h3 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #333;
    }

    .tutor-subjects {
        font-size: 13px;
        color: #666;
        margin-bottom: 18px;
        font-weight: 500;
        min-height: 36px;
    }

    .btn-view-profile {
        display: inline-block;
        background: #3dba9f;
        color: white;
        border: none;
        padding: 10px 26px;
        font-size: 13px;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-view-profile:hover {
        background: #2da889;
    }

    .btn-view-all {
        display: inline-block;
        background: white;
        color: #3dba9f;
        border: 2px solid #3dba9f;
        padding: 14px 38px;
        font-size: 14px;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-view-all:hover {
        background: #3dba9f;
        color: white;
    }

    /* Video Modal */
    .video-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(5px);
    }

    .video-modal.open {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .video-modal-content {
        background: white;
        padding: 30px;
        border-radius: 18px;
        max-width: 800px;
        width: 90%;
        position: relative;
    }

    .video-modal-content h3 {
        margin-bottom: 20px;
        font-size: 22px;
        color: #333;
        font-weight: 700;
    }

    .video-close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 32px;
        font-weight: 700;
        color: #999;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .video-close:hover {
        color: #333;
    }

    /* CTA Banner */
    .cta-banner {
        background: linear-gradient(135deg, #3dba9f, #5cd4b6);
        margin: 70px 70px;
        padding: 38px 55px;
        border-radius: 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        box-shadow: 0 10px 35px rgba(61, 186, 159, 0.3);
    }

    .cta-left {
        display: flex;
        align-items: center;
        gap: 22px;
    }

    .cta-icon-circle {
        width: 58px;
        height: 58px;
        background: white;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .cta-text h3 {
        font-size: 19px;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .cta-text p {
        font-size: 15px;
        font-weight: 500;
    }

    .cta-right {
        display: flex;
        align-items: center;
        gap: 28px;
    }

    .cta-phone {
        font-size: 17px;
        font-weight: 700;
    }

    .btn-contact {
        background: white;
        color: #3dba9f;
        border: none;
        padding: 13px 32px;
        font-size: 14px;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-contact:hover {
        background: #f5f5f5;
    }

    /* Footer Custom */
    .custom-footer {
        background: #d8f0e8;
        padding: 70px 70px 35px;
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
        margin-bottom: 22px;
        color: #000;
    }

    .footer-col p {
        font-size: 14px;
        color: #333;
        line-height: 1.8;
        font-weight: 400;
        margin-bottom: 30px;
    }

    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-col ul li {
        margin: 14px 0;
    }

    .footer-col a {
        color: #000;
        text-decoration: none;
        font-size: 14px;
        font-weight: 400;
        transition: color 0.3s ease;
    }

    .footer-col a:hover {
        color: #3dba9f;
    }

    .social-icons {
        display: flex;
        gap: 18px;
        margin-top: 35px;
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
        font-size: 22px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .social-icon:hover {
        background: #3dba9f;
        transform: scale(1.1);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 30px;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
        font-size: 14px;
        color: #000;
        font-weight: 400;
    }

    .about-section {
        margin-top: 35px;
    }

    .about-section h4 {
        margin-bottom: 18px;
    }

    @media (max-width: 1024px) {
        .pricing-cards,
        .features-grid {
            grid-template-columns: 1fr;
        }

        .tutors-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .footer-grid {
            grid-template-columns: 1fr;
        }

        .search-grid {
            grid-template-columns: 1fr;
        }

        .cta-banner {
            flex-direction: column;
            gap: 22px;
            text-align: center;
            margin: 50px 20px;
        }

        .cta-left {
            flex-direction: column;
        }

        .cta-right {
            flex-direction: column;
            gap: 15px;
        }

        .hero h1 {
            font-size: 42px;
        }
    }

    @media (max-width: 600px) {
        .tutors-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<div class="front-page-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>
    <div class="bg-blur blur-3"></div>

    <div class="content-wrapper">
        <!-- Custom Header -->
        <header class="custom-header">
            <div class="logo">
                <div class="logo-icon"></div>
                <a href="<?php echo home_url('/'); ?>" class="logo-text"><svg width="265" height="45" viewBox="0 0 265 45" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<rect width="265" height="45" fill="url(#pattern0_1_148)"/>
<defs>
<pattern id="pattern0_1_148" patternContentUnits="objectBoundingBox" width="1" height="1">
<use xlink:href="#image0_1_148" transform="scale(0.00087108 0.00512969)"/>
</pattern>
<image id="image0_1_148" width="1148" height="195" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
</defs>
</svg>
</a>
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
                <!-- Login/Signup Button for guests -->
                <div id="tp-auth-wrap-header"></div>
            <?php else:
                $user = wp_get_current_user();
                $roles = (array) $user->roles;
                $dash_url = in_array('teacher', $roles, true) ? home_url('/tutorsdashboard') :
                            (in_array('student', $roles, true) ? home_url('/student-dashboard') : admin_url());
            ?>
                <!-- Dashboard link for logged in users -->
                <a href="<?php echo esc_url($dash_url); ?>" class="user-icon" title="Dashboard">👤</a>
            <?php endif; ?>
        </header>

        <!-- Hero Section -->
        <!-- Hero Section -->
        <section class="hero">
            <p class="hero-subtitle">Let's work together</p>
            <h1>Get In <span class="affordable">Touch</span></h1>
            <p class="hero-ksl">Get in touch today and receive a complimentary consultation.</p>
        </section>

        <!-- Contact Info Section -->
        <section class="get-started">
            <h2 class="section-title">Contact Information</h2>
            <div class="pricing-cards">
                <div class="pricing-card">
                    <div style="font-size: 3rem; margin-bottom: 20px;">📞</div>
                    <h3>Phone</h3>
                    <div class="divider"></div>
                    <p style="padding: 20px 0; font-size: 18px;">+971 56 490 0376</p>
                </div>

                <div class="pricing-card">
                    <div style="font-size: 3rem; margin-bottom: 20px;">✉️</div>
                    <h3>Email</h3>
                    <div class="divider"></div>
                    <p style="padding: 20px 0; font-size: 18px;">info@tutorspoint.co.uk</p>
                </div>

                <div class="pricing-card">
                    <div style="font-size: 3rem; margin-bottom: 20px;">📍</div>
                    <h3>Location</h3>
                    <div class="divider"></div>
                    <p style="padding: 20px 0; font-size: 18px;">United Kingdom</p>
                </div>
            </div>
        </section>

        <!-- Contact Form Section -->
        <section class="features">
            <div style="max-width: 800px; margin: 0 auto;">
                <div style="background: white; border-radius: 25px; padding: 60px; box-shadow: 0 20px 60px rgba(0,0,0,0.1);">
                    <h2 style="text-align: center; font-size: 2.5rem; color: #3dba9f; margin-bottom: 40px;">Send Us a Message</h2>
                    
                    <form id="contact-form" method="post" action="">
                        <div style="margin-bottom: 25px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 16px;">Your Name *</label>
                            <input type="text" name="name" required style="width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-family: 'League Spartan', sans-serif; font-size: 16px;">
                        </div>

                        <div style="margin-bottom: 25px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 16px;">Your Email *</label>
                            <input type="email" name="email" required style="width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-family: 'League Spartan', sans-serif; font-size: 16px;">
                        </div>

                        <div style="margin-bottom: 25px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 16px;">Subject *</label>
                            <input type="text" name="subject" required style="width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-family: 'League Spartan', sans-serif; font-size: 16px;">
                        </div>

                        <div style="margin-bottom: 25px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 16px;">Message *</label>
                            <textarea name="message" required style="width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-family: 'League Spartan', sans-serif; font-size: 16px; min-height: 150px; resize: vertical;"></textarea>
                        </div>

                        <button type="submit" class="btn-book-free" style="width: 100%; padding: 18px; font-size: 18px; cursor: pointer;">Send Message</button>
                    </form>
                </div>

                <!-- Social Links -->
                <div style="display: flex; gap: 20px; justify-content: center; margin-top: 40px;">
                    <a href="#" style="width: 50px; height: 50px; background: linear-gradient(145deg, #3dba9f 0%, #2d9a80 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-size: 24px; transition: transform 0.3s;" title="Twitter">🐦</a>
                    <a href="#" style="width: 50px; height: 50px; background: linear-gradient(145deg, #3dba9f 0%, #2d9a80 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-size: 24px; transition: transform 0.3s;" title="LinkedIn">💼</a>
                    <a href="#" style="width: 50px; height: 50px; background: linear-gradient(145deg, #3dba9f 0%, #2d9a80 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-size: 24px; transition: transform 0.3s;" title="Facebook">👥</a>
                </div>
            </div>
        </section>

        <!-- Custom Footer -->
        <footer class="custom-footer">
            <div class="footer-grid">
                <div class="footer-col">
                    <p>Tuitional is an Online Ed-Tech Platform. We do live tutoring classes for Grades 4-8, IGCSE, GCSE, & A-Levels etc for all boards like Cambridge, Pearson Edexcel</p>
                    <div class="social-icons">
                        <div class="social-icon">f</div>
                        <div class="social-icon">📷</div>
                        <div class="social-icon">in</div>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Curriculums</h4>
                    <ul>
                        <li><a href="#">IGCSE Tuition</a></li>
                        <li><a href="#">IB Tuition</a></li>
                        <li><a href="#">PSLE Tuition</a></li>
                        <li><a href="#">Singapore O Level Tuition</a></li>
                        <li><a href="#">Singapore A Level Tuition</a></li>
                        <li><a href="#">SAT Tuition</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Subjects</h4>
                    <ul>
                        <?php
                        $footer_subjects = $wpdb->get_results("SELECT subject_id, SubjectName FROM wpC_subjects ORDER BY SubjectName LIMIT 12", ARRAY_A);
                        foreach ($footer_subjects as $footer_subject):
                        ?>
                            <li><a href="<?php echo home_url('/listofteachers/?subject=' . urlencode($footer_subject['SubjectName']) . '&subject_id=' . $footer_subject['subject_id']); ?>"><?php echo esc_html($footer_subject['SubjectName']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

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
</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<script>
// Move the auth button to header
document.addEventListener('DOMContentLoaded', function() {
    const authWrap = document.getElementById('tp-auth-wrap');
    const headerTarget = document.getElementById('tp-auth-wrap-header');

    if (authWrap && headerTarget) {
        // Clone the auth wrap content to header
        headerTarget.innerHTML = authWrap.innerHTML;

        // Hide the original auth wrap
        authWrap.style.display = 'none';

        // Make sure the button in header works
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
