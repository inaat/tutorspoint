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
                        global $wpdb;
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

<script src="<?php echo get_stylesheet_directory_uri(); ?>/mobile-menu.js"></script>