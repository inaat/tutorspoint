<?php
/**
 * Template Name: About Us Page
 * About Us Page Template - Tutors Point
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
    <title><?php bloginfo('name'); ?> - About Us</title>
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
        <section class="hero" style="padding: 80px 20px 60px;">
            <h1 style="font-size: 3.5rem; margin-bottom: 60px; line-height: 1.2; text-align: center;">Who We Are & <span class="affordable">Why We Care</span></h1>

            <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
                <div style="border-radius: 25px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
                    <img src="<?php echo home_url('/wp-content/uploads/2025/06/pexels-olly-3807755-2048x1365.jpg'); ?>" alt="Students Learning Together" style="width: 100%; height: auto; display: block;">
                </div>

                <div style="padding: 20px;">
                    <p style="font-size: 2rem; font-weight: 600; line-height: 1.6; color: #3dba9f; margin: 0;">Live, tailored online tutoring Platform from trusted educators — at a price families can afford.</p>
                </div>
            </div>
        </section>

        <!-- Our Story Section -->
        <section class="get-started" style="padding: 80px 20px;">
            <div style="text-align: center; margin-bottom: 50px;">
                <div style="width: 150px; height: 150px; margin: 0 auto 20px; background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: inline-flex; align-items: center; justify-content: center;">
                    <img src="<?php echo home_url('/wp-content/uploads/2025/06/story.png'); ?>" alt="Our Story" style="width: 100px; height: 100px; object-fit: contain;">
                </div>
                <h2 class="section-title" style="font-size: 2.5rem; margin: 0; color: #999;">Our Story</h2>
            </div>

            <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
                <div style="text-align: left;">
                    <p style="margin-bottom: 25px; line-height: 2; font-size: 18px; color: #3dba9f;">TutorsPoint was founded with a mission: to make high-quality, teacher-led education accessible to every learner — without the high cost.</p>
                    <p style="margin-bottom: 25px; line-height: 2; font-size: 18px; color: #3dba9f;">We saw a problem. Too many families were priced out of private tutoring, while students were left struggling with generic learning apps or crowded classrooms. At the same time, amazing educators around the world were looking for flexible, meaningful work.</p>
                    <p style="margin-bottom: 25px; line-height: 2; font-size: 20px; font-weight: 700; color: #3dba9f;">TutorsPoint brings the two together.</p>
                    <p style="margin-bottom: 0; line-height: 2; font-size: 18px; color: #3dba9f;">From our home in the UK, we've built a platform where families can connect with qualified teachers for live, scheduled online lessons that don't break the bank. No pre-recorded videos. No algorithms. Just real learning, in real time — at a price that makes sense.</p>
                </div>

                <div style="border-radius: 25px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
                    <img src="<?php echo home_url('/wp-content/uploads/2025/06/pexels-olly-3769021.jpg'); ?>" alt="Happy Student" style="width: 100%; height: auto; display: block;">
                </div>
            </div>
        </section>

        <!-- Mission & Vision Section -->
        <section class="get-started" style="padding: 80px 20px;">
            <h2 class="section-title" style="font-size: 2.5rem; margin-bottom: 50px;">Our Mission & Vision</h2>
            <div class="pricing-cards" style="gap: 40px;">
                <div class="pricing-card" style="padding: 50px 40px; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <h3 style="font-size: 1.8rem; margin-bottom: 20px; color: #3dba9f;">Our Mission</h3>
                    <div class="divider" style="width: 80px; height: 4px; margin: 0 0 25px 0;"></div>
                    <p style="text-align: left; padding: 0; line-height: 2; font-size: 17px; color: #555;">To empower learners of all backgrounds with personalized, affordable online tutoring — led by real teachers who care.</p>
                </div>

                <div class="pricing-card" style="padding: 50px 40px; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <h3 style="font-size: 1.8rem; margin-bottom: 20px; color: #3dba9f;">Our Vision</h3>
                    <div class="divider" style="width: 80px; height: 4px; margin: 0 0 25px 0;"></div>
                    <p style="text-align: left; padding: 0; line-height: 2; font-size: 17px; color: #555;">A future where every student, regardless of postcode or income, has access to top academic support, right from home.</p>
                </div>
            </div>
        </section>

        <!-- Core Values Section -->
        <section class="get-started" style="padding: 80px 20px; background: rgba(61, 186, 159, 0.05);">
            <h2 class="section-title" style="font-size: 2.5rem; margin-bottom: 50px;">Our Core Values</h2>
            <div class="pricing-cards" style="gap: 30px;">
                <div class="pricing-card" style="padding: 40px 30px; border: 2px solid rgba(61, 186, 159, 0.2); transition: all 0.3s ease;">
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #3dba9f;">✦ Quality</h3>
                    <div class="divider" style="width: 60px; height: 3px; margin: 0 auto 20px;"></div>
                    <p style="text-align: center; padding: 0; line-height: 1.8; font-size: 16px; color: #555;">Only qualified, experienced tutors make it onto our platform</p>
                </div>

                <div class="pricing-card" style="padding: 40px 30px; border: 2px solid rgba(61, 186, 159, 0.2); transition: all 0.3s ease;">
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #3dba9f;">✦ Affordability</h3>
                    <div class="divider" style="width: 60px; height: 3px; margin: 0 auto 20px;"></div>
                    <p style="text-align: center; padding: 0; line-height: 1.8; font-size: 16px; color: #555;">Fair pricing for families. Fair pay for teachers.</p>
                </div>

                <div class="pricing-card" style="padding: 40px 30px; border: 2px solid rgba(61, 186, 159, 0.2); transition: all 0.3s ease;">
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #3dba9f;">✦ Clarity</h3>
                    <div class="divider" style="width: 60px; height: 3px; margin: 0 auto 20px;"></div>
                    <p style="text-align: center; padding: 0; line-height: 1.8; font-size: 16px; color: #555;">Easy scheduling, transparent fees, no surprises.</p>
                </div>

                <div class="pricing-card" style="padding: 40px 30px; border: 2px solid rgba(61, 186, 159, 0.2); transition: all 0.3s ease;">
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #3dba9f;">✦ Support</h3>
                    <div class="divider" style="width: 60px; height: 3px; margin: 0 auto 20px;"></div>
                    <p style="text-align: center; padding: 0; line-height: 1.8; font-size: 16px; color: #555;">We're here for students, parents, and tutors every step of the way.</p>
                </div>
            </div>
        </section>

        <!-- Who We Serve Section -->
        <section class="get-started" style="padding: 80px 20px;">
            <h2 class="section-title" style="font-size: 2.5rem; margin-bottom: 50px;">Who We Serve</h2>
            <div class="pricing-cards" style="gap: 40px;">
                <div class="pricing-card" style="padding: 50px 40px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf9 100%); transition: all 0.3s ease;">
                    <h3 style="font-size: 1.8rem; margin-bottom: 20px; color: #3dba9f;">👨‍🎓 Students</h3>
                    <div class="divider" style="width: 80px; height: 4px; margin: 0 auto 25px;"></div>
                    <p style="text-align: center; padding: 0; line-height: 2; font-size: 17px; color: #555;">Struggling or excelling, we match you with the right tutor to grow in confidence and capability.</p>
                </div>

                <div class="pricing-card" style="padding: 50px 40px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf9 100%); transition: all 0.3s ease;">
                    <h3 style="font-size: 1.8rem; margin-bottom: 20px; color: #3dba9f;">👩‍🏫 Tutors</h3>
                    <div class="divider" style="width: 80px; height: 4px; margin: 0 auto 25px;"></div>
                    <p style="text-align: center; padding: 0; line-height: 2; font-size: 17px; color: #555;">We give passionate teachers a platform to earn fairly and teach flexibly.</p>
                </div>

                <div class="pricing-card" style="padding: 50px 40px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf9 100%); transition: all 0.3s ease;">
                    <h3 style="font-size: 1.8rem; margin-bottom: 20px; color: #3dba9f;">👨‍👩‍👧 Parents</h3>
                    <div class="divider" style="width: 80px; height: 4px; margin: 0 auto 25px;"></div>
                    <p style="text-align: center; padding: 0; line-height: 2; font-size: 17px; color: #555;">We support you with tools to monitor progress, manage bookings, and trust the process.</p>
                </div>
            </div>
        </section>

        <!-- What Makes Us Different Section -->
        <section class="features" style="padding: 100px 20px; background: linear-gradient(180deg, #ffffff 0%, #e5f4ef 50%, #d8f0e8 100%); position: relative;">
            <h2 class="section-title" style="font-size: 3rem; margin-bottom: 70px; color: #3dba9f; text-align: center;">What Makes TutorsPoint Different</h2>

            <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center;">
                <div style="text-align: left;">
                    <div style="margin-bottom: 35px; display: flex; align-items: flex-start; gap: 15px;">
                        <span style="font-size: 1.8rem; color: #3dba9f; flex-shrink: 0;">✅</span>
                        <p style="margin: 0; line-height: 1.8; font-size: 20px; color: #3dba9f;"><strong>Trusted</strong> — Tutors are vetted, trained, and monitored</p>
                    </div>

                    <div style="margin-bottom: 35px; display: flex; align-items: flex-start; gap: 15px;">
                        <span style="font-size: 1.8rem; color: #3dba9f; flex-shrink: 0;">✅</span>
                        <p style="margin: 0; line-height: 1.8; font-size: 20px; color: #3dba9f;"><strong>Flexible</strong> — You choose the time that works</p>
                    </div>

                    <div style="margin-bottom: 35px; display: flex; align-items: flex-start; gap: 15px;">
                        <span style="font-size: 1.8rem; color: #3dba9f; flex-shrink: 0;">✅</span>
                        <p style="margin: 0; line-height: 1.8; font-size: 20px; color: #3dba9f;"><strong>Transparent</strong> — Clear pricing and no hidden fees</p>
                    </div>

                    <div style="margin-bottom: 50px; display: flex; align-items: flex-start; gap: 15px;">
                        <span style="font-size: 1.8rem; color: #3dba9f; flex-shrink: 0;">✅</span>
                        <p style="margin: 0; line-height: 1.8; font-size: 20px; color: #3dba9f;"><strong>Affordable</strong> — Elite learning that respects your budget.</p>
                    </div>

                    <div style="text-align: left; margin-top: 50px;">
                        <p style="font-size: 1.5rem; font-weight: 700; color: #3dba9f; line-height: 1.6; margin: 0;">We believe education is a right — not a luxury.</p>
                    </div>
                </div>

                <div style="border-radius: 25px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
                    <img src="<?php echo home_url('/wp-content/uploads/2025/06/pexels-markusspiske-1679618-scaled.jpg'); ?>" alt="Standing Out" style="width: 100%; height: auto; display: block;">
                </div>
            </div>
        </section>

        <!-- Join Our Journey Section -->
        <section class="get-started" style="padding: 100px 20px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf9 100%);">
            <h2 class="section-title" style="font-size: 2.5rem; margin-bottom: 40px;">Join Our Journey</h2>
            <div style="max-width: 900px; margin: 0 auto; text-align: center; padding: 50px; background: white; border-radius: 25px; box-shadow: 0 15px 40px rgba(0,0,0,0.1);">
                <p style="margin-bottom: 25px; line-height: 2; font-size: 18px; color: #555;">Whether you're a student aiming higher, a tutor looking to teach, or a parent who wants something better — you've found the right place.</p>
                <p style="margin-bottom: 25px; line-height: 2; font-size: 18px; font-weight: 600; color: #333;">TutorsPoint is more than a platform. It's a promise:</p>
                <p style="margin-bottom: 40px; line-height: 1.6; font-size: 2rem; font-weight: 800; color: #3dba9f; padding: 20px; background: rgba(61, 186, 159, 0.1); border-radius: 15px;">Real teachers. Real learning. Reasonable prices.</p>
                <button class="btn-book-free" style="font-size: 1.2rem; padding: 18px 50px; border-radius: 50px; transition: all 0.3s ease;" onclick="document.getElementById('tp-open-auth')?.click()">Get Started Today</button>
            </div>
        </section>

        <!-- Custom Footer -->
        <?php include(get_stylesheet_directory() . "/ofooter.php"); ?>
    </div>
</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>



<?php wp_footer(); ?>
</body>
</html>
