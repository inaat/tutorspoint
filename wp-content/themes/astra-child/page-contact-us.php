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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="front-page-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>
    <div class="bg-blur blur-3"></div>

    <?php include(get_stylesheet_directory() . '/oheader.php'); ?>

    <div class="content-wrapper">


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
                    <?php include(get_stylesheet_directory() . "/ofooter.php"); ?>

    </div>
</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<script>

</script>

<?php wp_footer(); ?>
</body>
</html>
